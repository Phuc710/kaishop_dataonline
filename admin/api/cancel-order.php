<?php
require_once __DIR__ . '/../../config/config.php';
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
$reason = trim($data['reason'] ?? '');

if (empty($order_id)) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

if (empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập lý do hủy đơn']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Get order info
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND status = 'pending'");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception('Không tìm thấy đơn hàng hoặc đơn không ở trạng thái pending');
    }
    
    // Update order status and add cancellation reason
    $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', cancellation_reason = ? WHERE id = ?");
    $stmt->execute([$reason, $order_id]);
    
    // Refund money to user
    $stmt = $pdo->prepare("UPDATE users SET balance_vnd = balance_vnd + ? WHERE id = ?");
    $stmt->execute([$order['total_amount_vnd'], $order['user_id']]);
    
    // Get user's new balance
    $stmt = $pdo->prepare("SELECT balance_vnd FROM users WHERE id = ?");
    $stmt->execute([$order['user_id']]);
    $new_balance = $stmt->fetchColumn();
    
    // Log refund transaction
    logBalanceTransaction(
        $order['user_id'],
        'refund',
        'VND',
        $order['total_amount_vnd'],
        $new_balance - $order['total_amount_vnd'],
        $new_balance,
        'Hoàn tiền đơn hàng ' . $order['order_number'] . ' - Lý do: ' . $reason
    );
    
    // Restore stock for all items (both products and variants)
    $stmt = $pdo->prepare("SELECT product_id, variant_id, quantity FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();
    
    foreach ($items as $item) {
        // If variant exists, restore variant stock
        if (!empty($item['variant_id'])) {
            $pdo->prepare("UPDATE product_variants SET stock = stock + ? WHERE id = ?")
                ->execute([$item['quantity'], $item['variant_id']]);
        }
        
        // Always restore product stock and sold_count
        $pdo->prepare("UPDATE products SET stock = stock + ?, sold_count = sold_count - ? WHERE id = ?")
            ->execute([$item['quantity'], $item['quantity'], $item['product_id']]);
    }
    
    $pdo->commit();
    
    // Log activity
    logActivity('admin_action', 'cancel_order', "Hủy đơn hàng {$order['order_number']} - Lý do: {$reason}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã hủy đơn hàng và hoàn tiền cho khách'
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
