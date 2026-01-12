<?php
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$order_id = $_GET['order_id'] ?? '';

if (empty($order_id)) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

try {
    // Get order items with product info
    $stmt = $pdo->prepare("
        SELECT 
            oi.*,
            COALESCE(oi.product_image, p.image) as product_image,
            COALESCE(oi.product_name, p.name) as product_name,
            p.requires_customer_info
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
        ORDER BY oi.id
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert IDs to string to prevent JS precision loss
    foreach ($items as &$item) {
        $item['id'] = strval($item['id']);
        $item['order_id'] = strval($item['order_id']);
        $item['product_id'] = strval($item['product_id']);
    }
    
    // Debug logging
    error_log("Order ID: " . $order_id);
    error_log("Items found: " . count($items));
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'debug' => [
            'order_id' => $order_id,
            'items_count' => count($items)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in get-order-details.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
