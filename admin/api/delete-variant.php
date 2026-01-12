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
$variant_id = $data['variant_id'] ?? '';
$product_id = $data['product_id'] ?? '';

if (empty($variant_id) || empty($product_id)) {
    echo json_encode(['success' => false, 'message' => 'Variant ID and Product ID required']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Get variant info
    $stmt = $pdo->prepare("SELECT * FROM product_variants WHERE id = ? AND product_id = ?");
    $stmt->execute([$variant_id, $product_id]);
    $variant = $stmt->fetch();
    
    if (!$variant) {
        throw new Exception('Không tìm thấy variant');
    }
    
    // Check how many variants this product has
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_variants WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $variantCount = $stmt->fetchColumn();
    
    if ($variantCount <= 2) {
        throw new Exception('Không thể xóa! Sản phẩm nhiều option phải có tối thiểu 2 option.');
    }
    
    // Check if variant is in any orders
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE oi.variant_id = ? AND o.status IN ('pending', 'completed')
    ");
    $stmt->execute([$variant_id]);
    $orderCount = $stmt->fetchColumn();
    
    if ($orderCount > 0) {
        throw new Exception("Không thể xóa option đã có trong {$orderCount} đơn hàng.");
    }
    
    // Delete variant
    $stmt = $pdo->prepare("DELETE FROM product_variants WHERE id = ?");
    $stmt->execute([$variant_id]);
    
    $pdo->commit();
    
    // Log activity
    logActivity('admin_action', 'delete_variant', "Xóa variant: {$variant['variant_name']} (Product: {$product_id})");
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa option: ' . $variant['variant_name']
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
