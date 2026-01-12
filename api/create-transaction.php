<?php
/**
 * Create Payment Transaction API
 * Generates unique transaction code with 'kai' prefix
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để tiếp tục'
    ]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['amount']) || !isset($input['currency'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin bắt buộc'
    ]);
    exit;
}

$amount = floatval($input['amount']);
$currency = $input['currency'];
$user = getCurrentUser();

// Validate amount
$min_amount = $currency === 'VND' ? 10000 : 5;
if ($amount < $min_amount) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => "Số tiền nạp tối thiểu là " . ($currency === 'VND' ? '10,000₫' : '$5')
    ]);
    exit;
}

// Get payment method (default to mbbank)
$payment_method = $input['payment_method'] ?? 'mbbank';

try {
    // Generate unique transaction code with 'kai' prefix
    $transaction_code = generateUniqueTransactionCode($pdo);
    
    // Get expiration time from settings (default 5 minutes)
    $stmt = $pdo->prepare("SELECT setting_value FROM payment_settings WHERE setting_key = 'transaction_expire_minutes'");
    $stmt->execute();
    $expire_minutes = $stmt->fetchColumn() ?: 5;
    
    // Calculate expiration time
    $expires_at = date('Y-m-d H:i:s', strtotime("+{$expire_minutes} minutes"));
    
    // Insert transaction
    $stmt = $pdo->prepare("
        INSERT INTO payment_transactions 
        (user_id, transaction_code, amount, currency, payment_method, status, expires_at) 
        VALUES 
        (:user_id, :transaction_code, :amount, :currency, :payment_method, 'pending', :expires_at)
    ");
    
    $stmt->execute([
        'user_id' => $user['id'],
        'transaction_code' => $transaction_code,
        'amount' => $amount,
        'currency' => $currency,
        'payment_method' => $payment_method,
        'expires_at' => $expires_at
    ]);
    
    $transaction_id = $pdo->lastInsertId();
    
    // Return transaction details
    echo json_encode([
        'success' => true,
        'message' => 'Tạo giao dịch thành công',
        'transaction' => [
            'id' => $transaction_id,
            'transaction_code' => $transaction_code,
            'amount' => $amount,
            'currency' => $currency,
            'payment_method' => $payment_method,
            'status' => 'pending',
            'expires_at' => $expires_at,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
}

/**
 * Generate unique transaction code with 'kai' prefix
 * Format: kai + 15 random alphanumeric characters
 */
function generateUniqueTransactionCode($pdo) {
    $max_attempts = 10;
    $attempt = 0;
    
    while ($attempt < $max_attempts) {
        // Generate code: kai + 15 random characters
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = 'kai';
        for ($i = 0; $i < 15; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        // Check if code already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM payment_transactions WHERE transaction_code = ?");
        $stmt->execute([$code]);
        
        if ($stmt->fetchColumn() == 0) {
            return $code;
        }
        
        $attempt++;
    }
    
    throw new Exception('Không thể tạo mã giao dịch duy nhất');
}
