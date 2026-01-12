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
$product_id = $data['product_id'] ?? '';

if (empty($product_id)) {
    echo json_encode(['success' => false, 'message' => 'Product ID required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Get product info
    $stmt = $pdo->prepare("SELECT name, image FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        throw new Exception('Không tìm thấy sản phẩm');
    }

    // Check if product is in any orders
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM order_items oi
        WHERE oi.product_id = ?
    ");
    $stmt->execute([$product_id]);
    $orderCount = $stmt->fetchColumn();

    if ($orderCount > 0) {
        // Product has orders - set is_active = 0 instead of deleting
        $stmt = $pdo->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
        $stmt->execute([$product_id]);

        $pdo->commit();

        // Log activity
        logActivity('admin_action', 'deactivate_product', "Ẩn sản phẩm (có trong đơn hàng): {$product['name']} (ID: {$product_id})");

        echo json_encode([
            'success' => true,
            'message' => "Đã ẩn sản phẩm '{$product['name']}' (sản phẩm đã có trong {$orderCount} đơn hàng)"
        ]);
        exit;
    }

    // Delete stock accounts (for account type products)
    $stmt = $pdo->prepare("DELETE FROM product_stock_pool WHERE product_id = ?");
    $stmt->execute([$product_id]);

    // Delete product variants
    $stmt = $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?");
    $stmt->execute([$product_id]);

    // Delete reviews
    $stmt = $pdo->prepare("DELETE FROM reviews WHERE product_id = ?");
    $stmt->execute([$product_id]);

    // Delete from cart
    $stmt = $pdo->prepare("DELETE FROM cart WHERE product_id = ?");
    $stmt->execute([$product_id]);

    // Delete product
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);

    // Delete image file
    if (!empty($product['image'])) {
        $image_path = __DIR__ . '/../../assets/images/uploads/' . $product['image'];
        if (file_exists($image_path)) {
            @unlink($image_path);
        }
    }

    $pdo->commit();

    // Log activity
    logActivity('admin_action', 'delete_product', "Xóa sản phẩm: {$product['name']} (ID: {$product_id})");

    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa sản phẩm: ' . $product['name']
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
