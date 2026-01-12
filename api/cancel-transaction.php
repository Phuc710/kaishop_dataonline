<?php
/**
 * Cancel Transaction API
 * Allows user to cancel a pending transaction
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
if (!isset($input['transaction_code'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu mã giao dịch'
    ]);
    exit;
}

$transaction_code = $input['transaction_code'];
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
    
    // Check if transaction is already completed
    if ($transaction['status'] === 'completed') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Không thể hủy giao dịch đã hoàn thành'
        ]);
        exit;
    }
    
    // Cancel transaction
    $stmt = $pdo->prepare("
        UPDATE payment_transactions 
        SET status = 'expired' 
        WHERE id = ?
    ");
    $stmt->execute([$transaction['id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã hủy giao dịch thành công'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
}
