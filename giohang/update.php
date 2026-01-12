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
    $quantity = isset($data['quantity']) ? (int)$data['quantity'] : null;
    $customer_info = isset($data['customer_info']) ? trim($data['customer_info']) : null;
    
    // Validate cart_id (can be large snowflake ID)
    if (empty($cart_id) || !is_numeric($cart_id)) {
        throw new Exception('ID giỏ hàng không hợp lệ');
    }
    
    // If only updating customer_info
    if ($quantity === null && $customer_info !== null) {
        $stmt = $pdo->prepare("SELECT id FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            throw new Exception('Không tìm thấy sản phẩm trong giỏ hàng');
        }
        
        $stmt = $pdo->prepare("UPDATE cart SET customer_info = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([$customer_info, $cart_id, $_SESSION['user_id']]);
        
        echo json_encode(['success' => true, 'message' => 'Đã cập nhật thông tin']);
        exit;
    }
    
    if ($quantity !== null && $quantity < 1) {
        throw new Exception('Số lượng phải lớn hơn 0');
    }
    
    $quantity = $quantity ?? 1;
    
    // Check if cart item belongs to user and get product/variant info
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            COALESCE(v.stock, p.stock) as stock,
            COALESCE(v.max_purchase, p.max_purchase) as max_purchase,
            COALESCE(v.min_purchase, p.min_purchase) as min_purchase
        FROM cart c 
        JOIN products p ON c.product_id = p.id
        LEFT JOIN product_variants v ON c.variant_id = v.id AND v.is_active = 1
        WHERE c.id = ? AND c.user_id = ?
    ");
    $stmt->execute([$cart_id, $_SESSION['user_id']]);
    $cart_item = $stmt->fetch();
    
    if (!$cart_item) {
        throw new Exception('Không tìm thấy sản phẩm trong giỏ hàng');
    }
    
    // Validate quantity limits
    $min_qty = max(1, (int)$cart_item['min_purchase']);
    $max_qty = min((int)$cart_item['stock'], (int)$cart_item['max_purchase']);
    
    if ($quantity < $min_qty) {
        throw new Exception("Số lượng tối thiểu là {$min_qty}");
    }
    
    if ($quantity > $max_qty) {
        throw new Exception("Số lượng tối đa là {$max_qty}");
    }
    
    // Update quantity
    $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
    if (!$stmt->execute([$quantity, $cart_id, $_SESSION['user_id']])) {
        throw new Exception('Không thể cập nhật số lượng');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã cập nhật số lượng',
        'quantity' => $quantity
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
