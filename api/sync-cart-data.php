<?php
/**
 * API: Sync Cart Data
 * Đồng bộ và kiểm tra thay đổi giá, stock, tên sản phẩm trong giỏ hàng
 */
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Get cart items with current product data
    $stmt = $pdo->prepare("
        SELECT 
            c.id as cart_id,
            c.product_id,
            c.variant_id,
            c.quantity,
            p.name as product_name,
            p.image,
            p.is_active as product_active,
            p.stock as product_stock,
            p.final_price_vnd as product_price_vnd,
            p.final_price_usd as product_price_usd,
            p.discount_percent as product_discount,
            v.variant_name,
            v.is_active as variant_active,
            v.stock as variant_stock,
            v.final_price_vnd as variant_price_vnd,
            v.final_price_usd as variant_price_usd,
            v.discount_percent as variant_discount
        FROM cart c
        JOIN products p ON c.product_id = p.id
        LEFT JOIN product_variants v ON c.variant_id = v.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $changes = [];
    $out_of_stock = [];
    $insufficient_stock = [];
    $inactive_items = [];
    $removed_items = [];
    
    foreach ($cart_items as $item) {
        $cart_id = $item['cart_id'];
        $has_variant = !empty($item['variant_id']);
        
        // Determine item name
        $item_name = $item['product_name'];
        if ($has_variant && !empty($item['variant_name'])) {
            $item_name .= ' - ' . $item['variant_name'];
        }
        
        // Check if active
        if (!$item['product_active']) {
            $inactive_items[] = [
                'cart_id' => $cart_id,
                'name' => $item_name,
                'reason' => 'Sản phẩm đã bị vô hiệu hóa'
            ];
            // Remove from cart
            $pdo->prepare("DELETE FROM cart WHERE id = ?")->execute([$cart_id]);
            $removed_items[] = $item_name;
            continue;
        }
        
        if ($has_variant && !$item['variant_active']) {
            $inactive_items[] = [
                'cart_id' => $cart_id,
                'name' => $item_name,
                'reason' => 'Phân loại sản phẩm đã bị vô hiệu hóa'
            ];
            // Remove from cart
            $pdo->prepare("DELETE FROM cart WHERE id = ?")->execute([$cart_id]);
            $removed_items[] = $item_name;
            continue;
        }
        
        // Determine current stock and price
        if ($has_variant && $item['variant_stock'] !== null) {
            $current_stock = (int)$item['variant_stock'];
            $current_price_vnd = (int)$item['variant_price_vnd'];
            $current_price_usd = (float)$item['variant_price_usd'];
        } else {
            $current_stock = (int)$item['product_stock'];
            $current_price_vnd = (int)$item['product_price_vnd'];
            $current_price_usd = (float)$item['product_price_usd'];
        }
        
        $requested_qty = (int)$item['quantity'];
        
        // Check stock
        if ($current_stock <= 0) {
            $out_of_stock[] = [
                'cart_id' => $cart_id,
                'name' => $item_name,
                'requested' => $requested_qty
            ];
        } elseif ($current_stock < $requested_qty) {
            $insufficient_stock[] = [
                'cart_id' => $cart_id,
                'name' => $item_name,
                'requested' => $requested_qty,
                'available' => $current_stock
            ];
            
            // Auto-adjust quantity to available stock
            $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?")
                ->execute([$current_stock, $cart_id]);
                
            $changes[] = [
                'cart_id' => $cart_id,
                'name' => $item_name,
                'type' => 'quantity_adjusted',
                'old_value' => $requested_qty,
                'new_value' => $current_stock,
                'message' => "Số lượng đã được điều chỉnh từ {$requested_qty} xuống {$current_stock} (kho chỉ còn {$current_stock})"
            ];
        }
    }
    
    // Return summary
    $has_changes = !empty($changes) || !empty($out_of_stock) || !empty($insufficient_stock) || !empty($inactive_items);
    
    echo json_encode([
        'success' => true,
        'has_changes' => $has_changes,
        'changes' => $changes,
        'out_of_stock' => $out_of_stock,
        'insufficient_stock' => $insufficient_stock,
        'inactive_items' => $inactive_items,
        'removed_items' => $removed_items,
        'message' => $has_changes ? 'Giỏ hàng đã được cập nhật' : 'Giỏ hàng đã được đồng bộ'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi đồng bộ giỏ hàng: ' . $e->getMessage()
    ]);
}
