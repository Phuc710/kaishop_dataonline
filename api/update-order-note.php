<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $order_id = $data['order_id'] ?? null;
    $note = $data['note'] ?? '';
    
    if (!$order_id) {
        throw new Exception('Thiếu mã đơn hàng');
    }
    
    // Check if order belongs to user and is pending
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception('Đơn hàng không tồn tại');
    }
    
    if ($order['status'] !== 'pending') {
        throw new Exception('Chỉ có thể chỉnh sửa thông tin khi đơn hàng đang chờ xử lý');
    }
    
    // Update note
    $stmt = $pdo->prepare("UPDATE orders SET note = ? WHERE id = ?");
    $stmt->execute([$note, $order_id]);
    
    echo json_encode(['success' => true, 'message' => 'Cập nhật thông tin thành công']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
