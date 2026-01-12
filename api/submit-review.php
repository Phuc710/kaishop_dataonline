<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = $data['product_id'] ?? null;
$order_id = $data['order_id'] ?? null;
$rating = intval($data['rating'] ?? 0);
$comment = trim($data['comment'] ?? '');
$user_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();
    
    // Validation
    if (empty($product_id)) {
        throw new Exception('Product ID không hợp lệ');
    }
    
    if (empty($order_id)) {
        throw new Exception('Order ID không hợp lệ');
    }
    
    if ($rating < 1 || $rating > 5) {
        throw new Exception('Rating phải từ 1-5 sao');
    }
    
    if (empty($comment) || strlen($comment) < 10) {
        throw new Exception('Bình luận phải có ít nhất 10 ký tự');
    }
    
    // Check if product exists
    $stmt = $pdo->prepare("SELECT id, name FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        throw new Exception('Sản phẩm không tồn tại');
    }
    
    // Verify user owns this order and it contains this product (completed)
    $stmt = $pdo->prepare("
        SELECT o.id, o.order_number
        FROM orders o
        INNER JOIN order_items oi ON o.id = oi.order_id
        WHERE o.id = ?
            AND o.user_id = ? 
            AND oi.product_id = ? 
            AND o.status = 'completed'
        LIMIT 1
    ");
    $stmt->execute([$order_id, $user_id, $product_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception('Đơn hàng không hợp lệ hoặc chưa hoàn thành');
    }
    
    // Check if user already reviewed this product IN THIS ORDER
    $stmt = $pdo->prepare("
        SELECT id FROM reviews 
        WHERE user_id = ? AND product_id = ? AND order_id = ?
    ");
    $stmt->execute([$user_id, $product_id, $order['id']]);
    $existing_review = $stmt->fetch();
    
    if ($existing_review) {
        throw new Exception('Bạn đã đánh giá sản phẩm này trong đơn hàng này rồi');
    }
    
    // Insert review
    $review_id = Snowflake::generateId();
    $stmt = $pdo->prepare("
        INSERT INTO reviews (id, product_id, user_id, order_id, rating, comment, is_verified_purchase, is_approved, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 1, 1, NOW())
    ");
    $stmt->execute([$review_id, $product_id, $user_id, $order['id'], $rating, $comment]);
    
    // Update product rating statistics
    $stmt = $pdo->prepare("
        SELECT 
            AVG(rating) as avg_rating,
            COUNT(*) as total_reviews
        FROM reviews
        WHERE product_id = ? AND is_approved = 1
    ");
    $stmt->execute([$product_id]);
    $stats = $stmt->fetch();
    
    $avg_rating = round($stats['avg_rating'], 1);
    $total_reviews = $stats['total_reviews'];
    
    $stmt = $pdo->prepare("
        UPDATE products 
        SET rating_avg = ?, rating_count = ?
        WHERE id = ?
    ");
    $stmt->execute([$avg_rating, $total_reviews, $product_id]);
    
    // Log activity
    logActivity(
        'product',
        'Review Submitted',
        "User reviewed product: {$product['name']} - Rating: {$rating}/5",
        '',
        $rating,
        $user_id
    );
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Cảm ơn bạn đã đánh giá sản phẩm!',
        'review_id' => $review_id,
        'product_name' => $product['name'],
        'rating' => $rating
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
