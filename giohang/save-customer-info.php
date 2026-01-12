<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['cart_id'])) {
        echo json_encode(['success' => false, 'message' => 'Thiếu cart_id']);
        exit;
    }
    
    $cart_id = $input['cart_id'];
    $customer_info = isset($input['customer_info']) ? trim($input['customer_info']) : null;
    
    // Verify cart belongs to user
    $stmt = $pdo->prepare("SELECT id FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Cart item không tồn tại']);
        exit;
    }
    
    // Update customer info
    $stmt = $pdo->prepare("UPDATE cart SET customer_info = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$customer_info, $cart_id, $_SESSION['user_id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã lưu thông tin'
    ]);
    
} catch (Exception $e) {
    error_log("Save customer info error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
