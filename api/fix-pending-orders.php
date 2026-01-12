<?php
/**
 * Fix pending orders: Auto-deliver items that don't have delivery_content yet
 * Run once to fix old orders
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/OrderDeliveryHandler.php';

header('Content-Type: application/json');

// Only admin can run this
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Admin only']);
    exit;
}

try {
    $deliveryHandler = new OrderDeliveryHandler($pdo);
    
    // Get all orders with pending items (items without delivery_content)
    $stmt = $pdo->query("
        SELECT DISTINCT o.id, o.order_number 
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        WHERE (oi.delivery_content IS NULL OR oi.delivery_content = '')
        AND o.status != 'cancelled'
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $fixed = [];
    $errors = [];
    
    foreach ($orders as $order) {
        try {
            // Try to auto-deliver
            $result = $deliveryHandler->deliverAutoItems($order['id']);
            
            // Update order status
            $newStatus = $deliveryHandler->updateOrderStatus($order['id']);
            
            $fixed[] = [
                'order_number' => $order['order_number'],
                'new_status' => $newStatus,
                'deliveries' => count($result)
            ];
        } catch (Exception $e) {
            $errors[] = [
                'order_number' => $order['order_number'],
                'error' => $e->getMessage()
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Fixed ' . count($fixed) . ' orders',
        'fixed' => $fixed,
        'errors' => $errors
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
