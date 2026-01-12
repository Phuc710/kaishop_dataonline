<?php
/**
 * Product Management - Router
 * Routes to: product_account_manage, product_source_manage, product_book_manage
 */

$product_id = $_GET['product_id'] ?? '';

if (empty($product_id)) {
    echo '<div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> Không tìm thấy sản phẩm!</div>';
    exit;
}

// Get product to determine type
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo '<div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> Sản phẩm không tồn tại!</div>';
    exit;
}

// Determine product type (default to account for old products)
$product_type = $product['product_type'] ?? 'account';

// Include appropriate manage file
switch ($product_type) {
    case 'account':
        include __DIR__ . '/product/product_account_manage.php';
        break;
    case 'source':
        include __DIR__ . '/product/product_source_manage.php';
        break;
    case 'book':
        include __DIR__ . '/product/product_book_manage.php';
        break;
    default:
        include __DIR__ . '/product/product_account_manage.php';
        break;
}
