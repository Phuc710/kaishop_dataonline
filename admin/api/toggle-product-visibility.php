<?php
require_once '../../config/config.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$product_id = $data['product_id'] ?? null;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit;
}

try {
    // Get current visibility status
    $stmt = $pdo->prepare("SELECT is_hidden FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    // Toggle visibility
    $new_status = $product['is_hidden'] ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE products SET is_hidden = ? WHERE id = ?");
    $stmt->execute([$new_status, $product_id]);

    echo json_encode([
        'success' => true,
        'is_hidden' => $new_status,
        'message' => $new_status ? 'Đã ẩn sản phẩm' : 'Đã hiện sản phẩm'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
