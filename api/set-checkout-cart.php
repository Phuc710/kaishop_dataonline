<?php
// API: Lưu cart_ids vào session để checkout
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

if (!isLoggedIn()) {
    die(json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']));
}

$data = json_decode(file_get_contents('php://input'), true);
$cart_ids = $data['cart_ids'] ?? [];

if (empty($cart_ids) || !is_array($cart_ids)) {
    die(json_encode(['success' => false, 'message' => 'Vui lòng chọn sản phẩm']));
}

// Validate all selected cart items
$placeholders = implode(',', array_fill(0, count($cart_ids), '?'));
$stmt = $pdo->prepare("
    SELECT 
        c.id,
        c.quantity,
        c.customer_info,
        p.id as product_id,
        p.name,
        p.requires_customer_info,
        COALESCE(v.stock, p.stock) as stock,
        COALESCE(v.min_purchase, p.min_purchase) as min_purchase,
        COALESCE(v.max_purchase, p.max_purchase) as max_purchase,
        v.variant_name,
        p.is_active
    FROM cart c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN product_variants v ON c.variant_id = v.id
    WHERE c.id IN ($placeholders) AND c.user_id = ?
");

$params = array_merge(array_map('strval', $cart_ids), [$_SESSION['user_id']]);
$stmt->execute($params);
$cart_items = $stmt->fetchAll();

// Check if all cart IDs were found
if (count($cart_items) !== count($cart_ids)) {
    die(json_encode(['success' => false, 'message' => 'Một số sản phẩm không tồn tại trong giỏ hàng']));
}

// Validate each item
$errors = [];

foreach ($cart_items as $item) {
    $product_name = $item['name'];
    if (!empty($item['variant_name'])) {
        $product_name .= ', ' . $item['variant_name'];
    }
    
    // Check if product is active
    if (!$item['is_active']) {
        $errors[] = "Sản phẩm '$product_name' đã ngưng kinh doanh. Vui lòng xóa khỏi giỏ hàng.";
        continue;
    }
    
    // Check stock
    $stock = (int)$item['stock'];
    $quantity = (int)$item['quantity'];
    
    if ($stock <= 0) {
        $errors[] = "Sản phẩm '$product_name' đã hết hàng. Vui lòng xóa khỏi giỏ hàng.";
        continue;
    }
    
    if ($quantity > $stock) {
        $errors[] = "Sản phẩm '$product_name' chỉ còn $stock sản phẩm. Vui lòng giảm số lượng.";
        continue;
    }
    
    // Check min/max purchase
    $min = (int)($item['min_purchase'] ?? 1);
    $max = (int)($item['max_purchase'] ?? 999);
    
    if ($quantity < $min) {
        $errors[] = "Sản phẩm '$product_name' yêu cầu mua tối thiểu $min sản phẩm.";
        continue;
    }
    
    if ($quantity > $max) {
        $errors[] = "Sản phẩm '$product_name' chỉ được mua tối đa $max sản phẩm.";
        continue;
    }
    
    // Check required customer info
    if ($item['requires_customer_info'] && empty(trim($item['customer_info']))) {
        $errors[] = "Sản phẩm '$product_name' yêu cầu nhập thông tin khách hàng.";
        continue;
    }
}

// If there are errors, return them
if (!empty($errors)) {
    die(json_encode([
        'success' => false, 
        'message' => 'Có lỗi với một số sản phẩm trong giỏ hàng',
        'errors' => $errors
    ]));
}

// All validation passed, save to session
$_SESSION['checkout_cart_ids'] = array_map('strval', $cart_ids);
echo json_encode(['success' => true]);

