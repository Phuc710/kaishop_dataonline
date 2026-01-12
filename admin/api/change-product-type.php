<?php
// API: Change Product Type
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json');

if (!isAdmin()) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = $data['product_id'] ?? '';
$new_type = $data['product_type'] ?? '';

if (empty($product_id) || empty($new_type)) {
    echo json_encode(['success' => false, 'message' => 'Missing product_id or product_type']);
    exit;
}

// Validate product_type
$valid_types = ['account', 'source', 'book'];
if (!in_array($new_type, $valid_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid product_type. Must be: account, source, or book']);
    exit;
}

try {
    // Check product exists
    $stmt = $pdo->prepare("SELECT id, name, product_type FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    $old_type = $product['product_type'] ?? 'account';
    
    // Update product_type
    $pdo->prepare("UPDATE products SET product_type = ? WHERE id = ?")
        ->execute([$new_type, $product_id]);
    
    // If changing TO source/book, set stock = 9999 (unlimited)
    if (in_array($new_type, ['source', 'book'])) {
        $pdo->prepare("UPDATE products SET stock = 9999, min_purchase = 1, max_purchase = 1 WHERE id = ?")
            ->execute([$product_id]);
    }
    
    // If changing FROM source/book TO account, reset stock to 0 (needs stock pool)
    if ($old_type !== 'account' && $new_type === 'account') {
        $pdo->prepare("UPDATE products SET stock = 0 WHERE id = ?")
            ->execute([$product_id]);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Đã đổi loại sản phẩm từ '{$old_type}' sang '{$new_type}'",
        'old_type' => $old_type,
        'new_type' => $new_type
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
