<?php
/**
 * Product Edit - Router
 * Routes to: product_account_edit, product_source_edit, product_book_edit
 */

$product_id = $_GET['product_id'] ?? '';
if (empty($product_id)) {
    echo '<script>window.location.href = "?tab=products";</script>';
    exit;
}

// Get product to determine type
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo '<script>window.location.href = "?tab=products";</script>';
    exit;
}

// Determine product type (default to account for old products)
$product_type = $product['product_type'] ?? 'account';

// Include appropriate edit file
switch ($product_type) {
    case 'account':
        include __DIR__ . '/product/product_account_edit.php';
        break;
    case 'source':
        include __DIR__ . '/product/product_source_edit.php';
        break;
    case 'book':
        include __DIR__ . '/product/product_book_edit.php';
        break;
    default:
        include __DIR__ . '/product/product_account_edit.php';
        break;
}
