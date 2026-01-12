<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/OrderDeliveryHandler.php';
header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$order_id = $data['order_id'] ?? '';

if (empty($order_id)) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

try {
    $response = $data['response'] ?? '';

    // First check if order exists and is pending
    $check_stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
    $check_stmt->execute([$order_id]);
    $current_order = $check_stmt->fetch();
    
    if (!$current_order) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy đơn hàng'
        ]);
        exit;
    }
    
    if ($current_order['status'] !== 'pending') {
        echo json_encode([
            'success' => false,
            'message' => "Đơn hàng đang ở trạng thái: {$current_order['status']}. Chỉ có thể hoàn thành đơn hàng pending."
        ]);
        exit;
    }

    $pdo->beginTransaction();

    // Update status
    $stmt = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ? AND status = 'pending'");
    $stmt->execute([$order_id]);
    
    if ($stmt->rowCount() > 0) {
        // Update response if provided (legacy)
        if (!empty($response)) {
            $stmt_items = $pdo->prepare("UPDATE order_items SET account_data = ? WHERE order_id = ?");
            $stmt_items->execute([$response, $order_id]);
        }

        $pdo->commit();
        
        // Auto-deliver products based on type
        $deliveryHandler = new OrderDeliveryHandler($pdo);
        $deliveries = $deliveryHandler->deliverOrder($order_id);

        // Log activity
        logActivity('admin_action', 'complete_order', "Hoàn thành đơn hàng ID: $order_id");
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã hoàn thành đơn hàng và giao sản phẩm',
            'deliveries' => $deliveries
        ]);
    } else {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Không thể cập nhật đơn hàng (có thể đã hoàn thành rồi)'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
