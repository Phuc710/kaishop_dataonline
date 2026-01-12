<?php
/**
 * API: Check Selected Cart Items Stock
 * Kiểm tra tồn kho real-time của các sản phẩm được chọn từ giỏ hàng
 */
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

try {
    // Get cart_ids from POST request
    $input = json_decode(file_get_contents('php://input'), true);
    $cart_ids = $input['cart_ids'] ?? [];
    
    if (empty($cart_ids)) {
        echo json_encode([
            'success' => false,
            'message' => 'Không có sản phẩm nào được chọn'
        ]);
        exit;
    }
    
    // Ensure cart_ids are strings (for Snowflake IDs)
    $cart_ids = array_map('strval', $cart_ids);
    
    $user_id = $_SESSION['user_id'];
    
    // Build query with IN clause
    $placeholders = str_repeat('?,', count($cart_ids) - 1) . '?';
    
    // Get selected cart items with real-time stock and prices
    $stmt = $pdo->prepare("
        SELECT 
            c.id as cart_id,
            c.product_id,
            c.variant_id,
            c.quantity as requested_quantity,
            p.name as product_name,
            p.is_active,
            p.final_price_vnd as product_price_vnd,
            p.final_price_usd as product_price_usd,
            p.stock as product_stock,
            v.stock as variant_stock,
            v.is_active as variant_active,
            COALESCE(v.final_price_vnd, p.final_price_vnd) as current_price_vnd,
            COALESCE(v.final_price_usd, p.final_price_usd) as current_price_usd,
            v.variant_name
        FROM cart c
        JOIN products p ON c.product_id = p.id
        LEFT JOIN product_variants v ON c.variant_id = v.id AND v.is_active = 1
        WHERE c.user_id = ? AND c.id IN ($placeholders)
    ");
    
    $params = array_merge([$user_id], $cart_ids);
    $stmt->execute($params);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $out_of_stock = [];
    $insufficient_stock = [];
    $inactive_products = [];
    $price_changes = [];
    
    foreach ($cart_items as $item) {
        $item_name = $item['product_name'];
        if (!empty($item['variant_name'])) {
            $item_name .= ' - ' . $item['variant_name'];
        }
        
        // Determine actual stock to check (variant takes priority)
        $current_stock = 0;
        $is_variant = !empty($item['variant_id']);
        
        if ($is_variant && $item['variant_stock'] !== null) {
            // Has variant and variant stock exists
            $current_stock = (int)$item['variant_stock'];
            $is_active = $item['is_active'] && $item['variant_active'];
        } else {
            // No variant or variant stock is null, use product stock
            $current_stock = (int)$item['product_stock'];
            $is_active = $item['is_active'];
        }
        
        // Check if product/variant is active
        if (!$is_active) {
            $inactive_products[] = [
                'cart_id' => $item['cart_id'],
                'name' => $item_name
            ];
            continue;
        }
        
        $requested_qty = (int)$item['requested_quantity'];
        
        // Check if out of stock
        if ($current_stock <= 0) {
            $out_of_stock[] = [
                'cart_id' => $item['cart_id'],
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'],
                'name' => $item_name,
                'requested' => $requested_qty,
                'available' => 0
            ];
        }
        // Check if insufficient stock
        elseif ($current_stock < $requested_qty) {
            $insufficient_stock[] = [
                'cart_id' => $item['cart_id'],
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'],
                'name' => $item_name,
                'requested' => $requested_qty,
                'available' => $current_stock
            ];
        }
    }
    
    // Return results
    $has_issues = !empty($out_of_stock) || !empty($insufficient_stock) || !empty($inactive_products);
    
    echo json_encode([
        'success' => true,
        'valid' => !$has_issues,
        'out_of_stock' => $out_of_stock,
        'insufficient_stock' => $insufficient_stock,
        'inactive_products' => $inactive_products,
        'price_changes' => $price_changes,
        'total_items' => count($cart_items)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi kiểm tra tồn kho: ' . $e->getMessage()
    ]);
}
