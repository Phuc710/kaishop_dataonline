<?php
/**
 * Check Transaction Status API
 * Check if transaction has been completed
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

// Get transaction code
$transaction_code = $_GET['code'] ?? '';

if (empty($transaction_code)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu mã giao dịch'
    ]);
    exit;
}

$user = getCurrentUser();

try {
    // Get transaction
    $stmt = $pdo->prepare("
        SELECT * FROM payment_transactions 
        WHERE transaction_code = ? AND user_id = ?
    ");
    $stmt->execute([$transaction_code, $user['id']]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy giao dịch'
        ]);
        exit;
    }
    
    // Check if expired
    if ($transaction['status'] === 'pending' && strtotime($transaction['expires_at']) < time()) {
        // Update status to expired
        $stmt = $pdo->prepare("UPDATE payment_transactions SET status = 'expired' WHERE id = ?");
        $stmt->execute([$transaction['id']]);
        $transaction['status'] = 'expired';
    }
    
    echo json_encode([
        'success' => true,
        'status' => $transaction['status'],
        'transaction' => $transaction
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
}
