<?php
/**
 * Cart Count API
 * Lấy số lượng sản phẩm trong giỏ hàng
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'count' => 0,
        'message' => 'Chưa đăng nhập'
    ]);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Get cart count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    
    $count = $result['count'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'count' => 0,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
