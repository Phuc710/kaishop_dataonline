<?php
/**
 * Sepay Webhook Handler
 * Automatically processes payments when receiving webhook from Sepay
 * 
 * Webhook URL: https://kaishop.id.vn/api/sepay-webhook.php
 * 
 * Setup in Sepay:
 * 1. Login to Sepay dashboard: https://my.sepay.vn
 * 2. Go to Settings > Webhook
 * 3. Add webhook URL
 * 4. Save webhook secret key to database
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

// ========== API KEY AUTHENTICATION ==========
// Get Authorization header
$headers = getallheaders();
$auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

// Get API key from database settings
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'sepay_api_key' LIMIT 1");
$stmt->execute();
$sepay_api_key = $stmt->fetchColumn();

// Validate API Key
if (empty($sepay_api_key)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fail API ==> Please DM Admin'
    ]);
    exit;
}

// Expected format: "ApiKey YOUR_API_KEY"
$expected_auth = "ApiKey " . $sepay_api_key;

if ($auth_header !== $expected_auth) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Invalid API Key'
    ]);
    exit;
}

// Get raw POST data
$raw_data = file_get_contents('php://input');
$data = json_decode($raw_data, true);

// Validate webhook data
if (!$data || !isset($data['content']) || !isset($data['amount'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid webhook data'
    ]);
    exit;
}

try {
    $sepay_transaction_id = $data['id'] ?? $data['transaction_id'] ?? null;
    $amount = floatval($data['amount'] ?? 0);
    $content = $data['content'] ?? '';
    $account_number = $data['account_number'] ?? '';
    $transaction_date = $data['transaction_date'] ?? date('Y-m-d H:i:s');
    $transfer_type = $data['transfer_type'] ?? 'in';
    $gate = $data['gate'] ?? $data['bank_brand_name'] ?? '';

    // ========== DUPLICATE CHECK ==========
    // Prevent double-processing of the same Sepay transaction
    if ($sepay_transaction_id) {
        $stmt = $pdo->prepare("SELECT id FROM sepay_webhook_logs WHERE transaction_id = ? AND processed = 1 LIMIT 1");
        $stmt->execute([$sepay_transaction_id]);
        if ($stmt->fetch()) {
            echo json_encode([
                'success' => true,
                'message' => 'Transaction already processed (duplicate webhook)',
                'duplicate' => true
            ]);
            exit;
        }
    }

    // Log to sepay_webhook_logs table
    $stmt = $pdo->prepare("
        INSERT INTO sepay_webhook_logs 
        (transaction_id, amount, content, account_number, transfer_type, gate, transaction_date, raw_data) 
        VALUES 
        (:transaction_id, :amount, :content, :account_number, :transfer_type, :gate, :transaction_date, :raw_data)
    ");

    $stmt->execute([
        'transaction_id' => $sepay_transaction_id,
        'amount' => $amount,
        'content' => $content,
        'account_number' => $account_number,
        'transfer_type' => $transfer_type,
        'gate' => $gate,
        'transaction_date' => $transaction_date,
        'raw_data' => $raw_data
    ]);

    $webhook_log_id = $pdo->lastInsertId();

    // Only process incoming transfers
    if ($transfer_type !== 'in') {
        echo json_encode([
            'success' => true,
            'message' => 'Not an incoming transfer, skipped'
        ]);
        exit;
    }

    // Extract transaction code from content
    // Format: kai[CODE] or any text containing kai[CODE]
    preg_match('/kai([A-Z0-9]{15})/i', $content, $matches);

    if (!$matches || !isset($matches[0])) {
        echo json_encode([
            'success' => false,
            'message' => 'No valid transaction code found in transfer content'
        ]);
        exit;
    }

    $transaction_code = strtolower($matches[0]); // kai + 15 chars

    // Find pending transaction with this code
    $stmt = $pdo->prepare("
        SELECT * FROM payment_transactions 
        WHERE transaction_code = ? AND status = 'pending'
    ");
    $stmt->execute([$transaction_code]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        echo json_encode([
            'success' => false,
            'message' => 'Transaction not found or already processed'
        ]);
        exit;
    }

    // Check if transaction is expired
    if (strtotime($transaction['expires_at']) < time()) {
        $stmt = $pdo->prepare("UPDATE payment_transactions SET status = 'expired' WHERE id = ?");
        $stmt->execute([$transaction['id']]);

        echo json_encode([
            'success' => false,
            'message' => 'Transaction has expired'
        ]);
        exit;
    }

    // Verify amount matches (allow some tolerance for fees)
    $expected_amount = floatval($transaction['amount']);
    $tolerance = 0.01; // 1% tolerance

    if (abs($amount - $expected_amount) > ($expected_amount * $tolerance)) {
        echo json_encode([
            'success' => false,
            'message' => "Amount mismatch: expected {$expected_amount}, got {$amount}"
        ]);
        exit;
    }

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // Update transaction status
        $stmt = $pdo->prepare("
            UPDATE payment_transactions 
            SET status = 'completed', 
                bank_transaction_id = :bank_transaction_id,
                payment_info = :payment_info,
                completed_at = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            'bank_transaction_id' => $sepay_transaction_id,
            'payment_info' => json_encode($data),
            'id' => $transaction['id']
        ]);

        // Calculate bonus based on amount
        $bonus_percent = 0;
        if ($expected_amount >= 500000) {
            $bonus_percent = 20;
        } elseif ($expected_amount >= 200000) {
            $bonus_percent = 15;
        } elseif ($expected_amount >= 100000) {
            $bonus_percent = 10;
        }

        $bonus_amount = ($expected_amount * $bonus_percent) / 100;
        $total_received = $expected_amount + $bonus_amount;

        // Get current balance before adding
        $stmt = $pdo->prepare("SELECT balance_vnd FROM users WHERE id = ?");
        $stmt->execute([$transaction['user_id']]);
        $balance_before = floatval($stmt->fetchColumn());
        $balance_after = $balance_before + $total_received;

        // Add balance to user account (amount + bonus)
        $stmt = $pdo->prepare("
            UPDATE users 
            SET balance_vnd = balance_vnd + :amount 
            WHERE id = :user_id
        ");

        $stmt->execute([
            'amount' => $total_received,
            'user_id' => $transaction['user_id']
        ]);

        // Insert into balance_transactions for deposit history display
        $bank_name = $gate ?: 'MB Bank'; // Use gate (bank_brand_name) or default to MB Bank

        $bt_id = generateSnowflakeId();

        $note = 'Nạp tiền qua QR - ' . $transaction_code . ' | Bank: ' . $bank_name;
        if ($bonus_amount > 0) {
            $note .= " (Gốc: " . number_format($expected_amount) . "đ + KM {$bonus_percent}%: " . number_format($bonus_amount) . "đ)";
        }

        $stmt = $pdo->prepare("
            INSERT INTO balance_transactions 
            (id, user_id, type, amount, currency, balance_before, balance_after, note, created_at) 
            VALUES 
            (:id, :user_id, 'deposit', :amount, 'VND', :balance_before, :balance_after, :note, NOW())
        ");
        $stmt->execute([
            'id' => $bt_id,
            'user_id' => $transaction['user_id'],
            'amount' => $total_received,
            'balance_before' => $balance_before,
            'balance_after' => $balance_after,
            'note' => $note
        ]);

        // Mark webhook as processed
        $stmt = $pdo->prepare("
            UPDATE sepay_webhook_logs 
            SET processed = 1, processed_at = NOW() 
            WHERE id = :id
        ");
        $stmt->execute(['id' => $webhook_log_id]);

        // Commit transaction
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Payment processed successfully',
            'transaction_code' => $transaction_code,
            'amount' => $expected_amount
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
