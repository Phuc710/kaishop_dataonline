<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

try {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Dữ liệu không hợp lệ');
    }
    
    // Handle large IDs as string to prevent precision loss
    $cart_id = $data['cart_id'] ?? '';
    
    // Validate cart_id (can be large snowflake ID)
    if (empty($cart_id) || !is_numeric($cart_id)) {
        throw new Exception('ID giỏ hàng không hợp lệ');
    }
    
    // Check if cart item belongs to user
    $stmt = $pdo->prepare("SELECT id FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Không tìm thấy sản phẩm trong giỏ hàng');
    }
    
    // Delete cart item
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    if (!$stmt->execute([$cart_id, $_SESSION['user_id']])) {
        throw new Exception('Không thể xóa sản phẩm');
    }
    
    // Get updated cart count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = (int)$stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa sản phẩm khỏi giỏ hàng',
        'cart_count' => $cart_count
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
