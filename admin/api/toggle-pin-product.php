<?php
/**
 * Toggle Product Pin Status API
 */
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = $data['product_id'] ?? '';

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Product ID required']);
    exit;
}

try {
    // Get current pin status
    $stmt = $pdo->prepare("SELECT is_pinned FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    // Toggle pin status
    $new_status = $product['is_pinned'] ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE products SET is_pinned = ? WHERE id = ?");
    $stmt->execute([$new_status, $product_id]);
    
    echo json_encode([
        'success' => true,
        'is_pinned' => $new_status,
        'message' => $new_status ? 'Đã ghim sản phẩm' : 'Đã bỏ ghim sản phẩm'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
