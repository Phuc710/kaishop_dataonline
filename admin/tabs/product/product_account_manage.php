<?php
/**
 * Product Account - Management Dashboard
 * Advanced: Variants support + Stock Pool management
 */

// $product and $product_id already loaded from router
$exchange_rate = floatval($pdo->query("SELECT setting_value FROM settings WHERE setting_key='exchange_rate'")->fetchColumn() ?? 25000);

// Get category info
$category = null;
if ($product['category_id']) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$product['category_id']]);
    $category = $stmt->fetch();
}

// Check variants
$stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY sort_order ASC, id ASC");
$stmt->execute([$product_id]);
$variants = $stmt->fetchAll();
$hasVariants = count($variants) > 0;

// ==================== STATISTICS ====================

// 1. Total Sold
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(oi.quantity), 0) as total_sold
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE CAST(oi.product_id AS CHAR) = ? AND o.status = 'completed'
");
$stmt->execute([strval($product_id)]);
$total_sold = intval($stmt->fetchColumn());

// 2. Total Revenue
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(oi.price_vnd * oi.quantity), 0) as total_revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE CAST(oi.product_id AS CHAR) = ? AND o.status = 'completed'
");
$stmt->execute([strval($product_id)]);
$total_revenue = floatval($stmt->fetchColumn());

// 3. Total Stock
if ($hasVariants) {
    $total_stock = array_sum(array_column($variants, 'stock'));
} else {
    $total_stock = intval($product['stock']);
}

// 4. Total Views
$total_views = intval($product['view_count'] ?? 0);

// 5. Total Reviews
$stmt = $pdo->prepare("
    SELECT COUNT(*) as review_count, COALESCE(AVG(rating), 0) as avg_rating
    FROM reviews
    WHERE CAST(product_id AS CHAR) = ?
");
$stmt->execute([strval($product_id)]);
$review_stats = $stmt->fetch();
$total_reviews = intval($review_stats['review_count']);
$avg_rating = floatval($review_stats['avg_rating']);

// 6. Recent Sales (30 days)
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(oi.quantity), 0) as recent_sold
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE CAST(oi.product_id AS CHAR) = ? 
    AND o.status = 'completed'
    AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stmt->execute([strval($product_id)]);
$recent_sold = intval($stmt->fetchColumn());

// ==================== PAGINATION ====================
$trans_page = max(1, intval($_GET['trans_page'] ?? 1));
$review_page = max(1, intval($_GET['review_page'] ?? 1));
$per_page = 10;
$trans_offset = ($trans_page - 1) * $per_page;
$review_offset = ($review_page - 1) * $per_page;

// ==================== TRANSACTION HISTORY ====================
// Count total transactions
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE CAST(oi.product_id AS CHAR) = ?
");
$stmt->execute([strval($product_id)]);
$total_transactions = intval($stmt->fetchColumn());
$total_trans_pages = ceil($total_transactions / $per_page);

// Get paginated transactions
$stmt = $pdo->prepare("
    SELECT 
        o.id as order_id,
        o.user_id,
        o.created_at,
        o.total_amount_vnd,
        o.status,
        u.username,
        u.email,
        oi.quantity,
        oi.price_vnd,
        oi.variant_id,
        pv.variant_name
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN product_variants pv ON oi.variant_id = pv.id
    WHERE CAST(oi.product_id AS CHAR) = ?
    ORDER BY o.created_at DESC
    LIMIT $per_page OFFSET $trans_offset
");
$stmt->execute([strval($product_id)]);
$transactions = $stmt->fetchAll();

// ==================== REVIEWS ====================
// Count total reviews
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM reviews
    WHERE CAST(product_id AS CHAR) = ?
");
$stmt->execute([strval($product_id)]);
$total_review_count = intval($stmt->fetchColumn());
$total_review_pages = ceil($total_review_count / $per_page);

// Get paginated reviews
$stmt = $pdo->prepare("
    SELECT 
        r.*,
        u.username,
        u.email
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE CAST(r.product_id AS CHAR) = ?
    ORDER BY r.created_at DESC
    LIMIT $per_page OFFSET $review_offset
");
$stmt->execute([strval($product_id)]);
$reviews = $stmt->fetchAll();


// ==================== STOCK POOL ====================
$stock_pool = [];

// Check if product_stock_pool table exists and has variant_id column
try {
    $check = $pdo->query("SHOW COLUMNS FROM product_stock_pool LIKE 'variant_id'");
    $hasVariantColumn = $check->rowCount() > 0;
    
    if ($hasVariantColumn) {
        if ($hasVariants) {
            // Get stock per variant (including sold items)
            foreach ($variants as $v) {
                $stmt = $pdo->prepare("
                    SELECT * FROM product_stock_pool 
                    WHERE product_id = ? AND variant_id = ?
                    ORDER BY is_used ASC, id ASC
                ");
                $stmt->execute([$product_id, $v['id']]);
                $stock_pool[$v['id']] = $stmt->fetchAll();
            }
        } else {
            // Single option (including sold items)
            $stmt = $pdo->prepare("
                SELECT * FROM product_stock_pool 
                WHERE product_id = ? AND variant_id IS NULL
                ORDER BY is_used ASC, id ASC
            ");
            $stmt->execute([$product_id]);
            $stock_pool['single'] = $stmt->fetchAll();
        }
    } else {
        // Old schema - no variant support (including sold items)
        $stmt = $pdo->prepare("
            SELECT * FROM product_stock_pool 
            WHERE product_id = ?
            ORDER BY is_used ASC, id ASC
        ");
        $stmt->execute([$product_id]);
        $stock_pool['single'] = $stmt->fetchAll();
    }
} catch (Exception $e) {
    // Table doesn't exist or error - skip stock pool
    $stock_pool = [];
}

// ==================== INCLUDE UI ====================
include __DIR__ . '/_account_manage_ui.php';
