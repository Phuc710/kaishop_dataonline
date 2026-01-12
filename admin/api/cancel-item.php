<?php
/**
 * API: Cancel single order item by admin
 * POST: { order_item_id }
 * - Refunds the item amount to user
 * - Updates order total
 * - If all items cancelled, cancel the whole order
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

if (!$order_item_id) {
    echo json_encode(['success' => false, 'message' => 'Missing order_item_id']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Get item info
    $stmt = $pdo->prepare("
        SELECT oi.*, o.user_id, o.order_number, o.status as order_status
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE oi.id = ?
    ");
    $stmt->execute([$order_item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        throw new Exception('Item không tồn tại');
    }
    
    if ($item['order_status'] === 'cancelled') {
        throw new Exception('Đơn hàng đã bị hủy trước đó');
    }
    
    // Calculate refund amount
    $refund_amount = floatval($item['subtotal_vnd'] ?? ($item['price_vnd'] * $item['quantity']));
    
    // Refund to user
    $pdo->prepare("UPDATE users SET balance_vnd = balance_vnd + ? WHERE id = ?")
        ->execute([$refund_amount, $item['user_id']]);
    
    // Mark item as cancelled (set delivery_content to indicate cancelled)
    $pdo->prepare("UPDATE order_items SET delivery_content = '[ĐÃ HỦY]' WHERE id = ?")
        ->execute([$order_item_id]);
    
    // Update order total
    $pdo->prepare("UPDATE orders SET total_amount_vnd = total_amount_vnd - ? WHERE id = ?")
        ->execute([$refund_amount, $item['order_id']]);
    
    // Log refund transaction
    $user = getCurrentUser();
    $stmt = $pdo->prepare("SELECT balance_vnd FROM users WHERE id = ?");
    $stmt->execute([$item['user_id']]);
    $newBalance = $stmt->fetchColumn();
    
    logBalanceTransaction(
        $item['user_id'],
        'refund',
        'VND',
        $refund_amount,
        $newBalance - $refund_amount,
        $newBalance,
        'Hoàn tiền hủy item đơn hàng ' . $item['order_number']
    );
    
    // Check if all items are cancelled
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM order_items 
        WHERE order_id = ? AND (delivery_content IS NULL OR delivery_content = '' OR delivery_content != '[ĐÃ HỦY]')
    ");
    $stmt->execute([$item['order_id']]);
    $remainingItems = $stmt->fetchColumn();
    
    if ($remainingItems == 0) {
        // All items cancelled, cancel the whole order
        $pdo->prepare("UPDATE orders SET status = 'cancelled', cancellation_reason = 'Tất cả item đã bị hủy' WHERE id = ?")
            ->execute([$item['order_id']]);
    } else {
        // Update order status using delivery handler
        $deliveryHandler = new OrderDeliveryHandler($pdo);
        $deliveryHandler->updateOrderStatus($item['order_id']);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã hủy item và hoàn ' . number_format($refund_amount) . 'đ cho khách',
        'refund_amount' => $refund_amount
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
