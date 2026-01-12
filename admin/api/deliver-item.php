<?php
/**
 * API: Deliver single order item by admin
 * POST: { order_item_id, admin_response }
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/OrderDeliveryHandler.php';

header('Content-Type: application/json');

// Check admin
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// Handle ID as string to prevent precision loss
$order_item_id = isset($data['order_item_id']) ? strval($data['order_item_id']) : null;
$admin_response = trim($data['admin_response'] ?? '');

if (!$order_item_id) {
    echo json_encode(['success' => false, 'message' => 'Missing order_item_id', 'debug' => $raw]);
    exit;
}

if (empty($admin_response)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập nội dung giao hàng']);
    exit;
}

try {
    $deliveryHandler = new OrderDeliveryHandler($pdo);
    $result = $deliveryHandler->deliverSingleItem($order_item_id, $admin_response);
    
    if (isset($result['success']) && $result['success'] === false) {
        echo json_encode($result);
    } else {
        // Get updated order info
        $stmt = $pdo->prepare("
            SELECT oi.order_id, o.status, o.order_number 
            FROM order_items oi 
            JOIN orders o ON oi.order_id = o.id 
            WHERE oi.id = ?
        ");
        $stmt->execute([$order_item_id]);
        $orderInfo = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã giao hàng cho item thành công',
            'item_id' => $order_item_id,
            'order_status' => $orderInfo['status'] ?? 'pending',
            'order_number' => $orderInfo['order_number'] ?? '',
            'delivery_content' => $admin_response
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
