<?php
/**
 * Product Book - Management Dashboard
 * Simple: No variants, No stock management, Just ebook link + stats
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

// 3. Total Views
$total_views = intval($product['view_count'] ?? 0);

// 4. Total Reviews
$stmt = $pdo->prepare("
    SELECT COUNT(*) as review_count, COALESCE(AVG(rating), 0) as avg_rating
    FROM reviews
    WHERE CAST(product_id AS CHAR) = ?
");
$stmt->execute([strval($product_id)]);
$review_stats = $stmt->fetch();
$total_reviews = intval($review_stats['review_count']);
$avg_rating = floatval($review_stats['avg_rating']);

// 5. Recent Sales (30 days)
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
        oi.price_vnd
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    LEFT JOIN users u ON o.user_id = u.id
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


// ==================== INCLUDE UI ====================
?>

<link rel="stylesheet" href="<?= asset('css/product_add.css') ?>?v=<?= time() ?>">
<link rel="stylesheet" href="<?= asset('css/product_manage.css') ?>?v=<?= time() ?>">

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(59, 130, 246, 0.05));
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 12px;
        padding: 1.5rem;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(180deg, #8b5cf6, #3b82f6);
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #8b5cf6;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        color: var(--text-muted);
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-icon {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: 50px;
        height: 50px;
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(59, 130, 246, 0.1));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .transaction-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }

    .transaction-table thead {
        background: rgba(139, 92, 246, 0.1);
    }

    .transaction-table th {
        padding: 1rem;
        text-align: left;
        color: var(--text-primary);
        font-weight: 600;
        border-bottom: 2px solid rgba(139, 92, 246, 0.3);
    }

    .transaction-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid rgba(139, 92, 246, 0.1);
    }

    .transaction-table tbody tr:hover {
        background: rgba(139, 92, 246, 0.05);
    }

    .review-card {
        background: rgba(30, 41, 59, 0.6);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1rem;
    }

    .rating-stars {
        color: #f59e0b;
        font-size: 1.2rem;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .status-completed {
        background: #10b981;
        color: #fff;
    }

    .status-pending {
        background: #f59e0b;
        color: #000;
    }

    .status-cancelled {
        background: #ef4444;
        color: #fff;
    }
</style>

<!-- Header -->
<div class="page-header"
    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 1rem;">
            <i class="fas fa-book"></i>
            Sách: <?= htmlspecialchars($product['name']) ?>
        </h1>
        <p style="color: var(--text-muted); margin: 0.5rem 0 0 0;">
            <span
                style="background: <?= $product['label_bg_color'] ?>; color: <?= $product['label_text_color'] ?>; padding: 0.25rem 0.75rem; border-radius: 6px; font-weight: 700;">
                <?= htmlspecialchars($product['label'] ?? 'NORMAL') ?>
            </span>
            <span style="margin-left: 1rem;">ID: #<?= substr($product_id, -8) ?></span>
            <span class="status-badge" style="margin-left: 0.5rem; background: #f59e0b;">
                <i class="fas fa-book"></i> Sách
            </span>
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="?tab=product_edit&product_id=<?= $product_id ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit
        </a>
        <a href="?tab=products" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<!-- Statistics Dashboard -->
<div class="stats-grid">
    <!-- Total Sold -->
    <div class="stat-card">
        <div class="stat-icon" style="color: #10b981;">
            <i class="fas fa-book-reader"></i>
        </div>
        <div class="stat-value" style="color: #10b981;"><?= number_format($total_sold) ?></div>
        <div class="stat-label">
            <i class="fas fa-shopping-cart"></i> Đã Bán
        </div>
    </div>

    <!-- Total Revenue -->
    <div class="stat-card">
        <div class="stat-icon" style="color: #3b82f6;">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-value" style="color: #3b82f6;"><?= number_format($total_revenue) ?>đ</div>
        <div class="stat-label">
            <i class="fas fa-chart-line"></i> Doanh Thu
        </div>
        <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">
            <i class="fas fa-money-bill-wave"></i> ~$<?= number_format($total_revenue / $exchange_rate, 2) ?>
        </small>
    </div>

    <!-- Total Views -->
    <div class="stat-card">
        <div class="stat-icon" style="color: #8b5cf6;">
            <i class="fas fa-eye"></i>
        </div>
        <div class="stat-value" style="color: #8b5cf6;"><?= number_format($total_views) ?></div>
        <div class="stat-label">
            <i class="fas fa-chart-bar"></i> Lượt Xem
        </div>
    </div>

    <!-- Total Reviews -->
    <div class="stat-card">
        <div class="stat-icon" style="color: #f59e0b;">
            <i class="fas fa-star"></i>
        </div>
        <div class="stat-value" style="color: #f59e0b;"><?= number_format($total_reviews) ?></div>
        <div class="stat-label">
            <i class="fas fa-comment-dots"></i> Đánh Giá
        </div>
        <?php if ($total_reviews > 0): ?>
            <div style="margin-top: 0.5rem;">
                <span class="rating-stars">
                    <?php
                    $fullStars = floor($avg_rating);
                    for ($i = 0; $i < $fullStars; $i++)
                        echo '★';
                    for ($i = $fullStars; $i < 5; $i++)
                        echo '☆';
                    ?>
                </span>
                <span style="color: var(--text-muted); margin-left: 0.5rem;"><?= number_format($avg_rating, 1) ?>/5</span>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Product Info -->
<div class="form-section" style="margin-bottom: 2rem;">
    <div class="form-section-header">
        <i class="fas fa-info-circle"></i>
        <h3>Thông Tin Sản Phẩm</h3>
    </div>
    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 2rem; padding: 1.5rem;">
        <!-- Image -->
        <div style="text-align: center;">
            <img src="<?= asset('images/uploads/' . $product['image']) ?>"
                alt="<?= htmlspecialchars($product['name']) ?>"
                style="width: 100%; border-radius: 12px; border: 2px solid #ffffff;">
            <div style="margin-top: 1rem;">
                <span
                    style="background: rgba(59, 130, 246, 0.2); padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600;">
                    <?php if ($category): ?>
                        <?php
                        $iconValue = $category['icon_value'] ?? '📦';
                        $iconType = $category['icon_type'] ?? 'emoji';

                        if ($iconType === 'image' && !empty($category['icon_url'])):
                            ?>
                            <img src="<?= $category['icon_url'] ?>"
                                style="width: 20px; height: 20px; vertical-align: middle; margin-right: 0.5rem;">
                        <?php elseif ($iconType === 'fontawesome'): ?>
                            <i class="<?= $iconValue ?>" style="margin-right: 0.5rem;"></i>
                        <?php else: ?>
                            <span style="font-size: 1.2rem; margin-right: 0.5rem;"><?= $iconValue ?></span>
                        <?php endif; ?>
                        <?= htmlspecialchars($category['name']) ?>
                    <?php else: ?>
                        <i class="fas fa-folder"></i> Uncategorized
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <!-- Details -->
        <div style="display: grid; gap: 1rem;">
            <div>
                <strong style="color: var(--text-primary);"><i class="fas fa-tag"></i> Tên:</strong>
                <p style="color: var(--text-secondary); margin: 0.5rem 0 0 0;"><?= htmlspecialchars($product['name']) ?>
                </p>
            </div>
            <div>
                <strong style="color: var(--text-primary);"><i class="fas fa-align-left"></i> Mô Tả:</strong>
                <p style="color: var(--text-secondary); margin: 0.5rem 0 0 0;">
                    <?= nl2br(htmlspecialchars($product['description'] ?? 'Không có mô tả')) ?>
                </p>
            </div>
            <div>
                <strong style="color: var(--text-primary);"><i class="fas fa-cloud-download-alt"></i> Link
                    Ebook:</strong>
                <div
                    style="background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; padding: 1rem; margin-top: 0.5rem; font-family: monospace; font-size: 0.9rem;">
                    <?= nl2br(htmlspecialchars($product['delivery_content'] ?? 'Chưa có link')) ?>
                </div>
            </div>
            <div>
                <strong style="color: var(--text-primary);"><i class="fas fa-dollar-sign"></i> Giá:</strong>
                <p style="margin: 0.5rem 0 0 0;">
                    <span
                        style="font-size: 1.8rem; font-weight: 700; color: #10b981;"><?= number_format($product['final_price_vnd']) ?>đ</span>
                    <?php if ($product['discount_percent'] > 0): ?>
                        <span
                            style="text-decoration: line-through; color: var(--text-muted); margin-left: 1rem; font-size: 1.2rem;"><?= number_format($product['price_vnd']) ?>đ</span>
                        <span class="status-badge status-completed"
                            style="margin-left: 0.5rem;">-<?= $product['discount_percent'] ?>%</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Transaction History -->
<div class="form-section" style="margin-bottom: 2rem;">
    <div class="form-section-header">
        <i class="fas fa-history"></i>
        <h3>Lịch Sử Giao Dịch (<?= $total_transactions ?>)</h3>
    </div>
    <div style="padding: 1.5rem; overflow-x: auto;">
        <?php if (count($transactions) > 0): ?>
            <table class="transaction-table">
                <thead>
                    <tr>
                        <th>Đơn Hàng</th>
                        <th>Khách Hàng</th>
                        <th>Số Lượng</th>
                        <th>Giá</th>
                        <th>Thời Gian</th>
                        <th>Trạng Thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><strong style="color: #8b5cf6;">#<?= substr($t['order_id'], -8) ?></strong></td>
                            <td>
                                <div><strong><?= htmlspecialchars($t['username'] ?? 'Guest') ?></strong></div>
                                <div style="font-size: 0.85rem; color: var(--text-muted);">
                                    <?= htmlspecialchars($t['email'] ?? 'N/A') ?>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <span style="font-weight: 700; color: #10b981;">x<?= $t['quantity'] ?></span>
                            </td>
                            <td><strong><?= number_format($t['price_vnd']) ?>đ</strong></td>
                            <td style="font-size: 0.85rem; color: var(--text-muted);">
                                <?= date('d/m/Y H:i', strtotime($t['created_at'])) ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $t['status'] ?>">
                                    <?= $t['status'] ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($total_trans_pages > 1): ?>
                <div
                    style="display:flex;justify-content:center;align-items:center;gap:0.5rem;margin-top:1.5rem;flex-wrap:wrap;">
                    <?php if ($trans_page > 1): ?>
                        <a href="?tab=product_manage&product_id=<?= $product_id ?>&trans_page=<?= $trans_page - 1 ?>&review_page=<?= $review_page ?>"
                            style="padding:0.5rem 1rem;background:rgba(139,92,246,0.2);border:1px solid rgba(139,92,246,0.3);border-radius:8px;color:#8b5cf6;text-decoration:none;font-weight:600;">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $trans_page - 2);
                    $end = min($total_trans_pages, $trans_page + 2);
                    for ($i = $start; $i <= $end; $i++):
                        ?>
                        <a href="?tab=product_manage&product_id=<?= $product_id ?>&trans_page=<?= $i ?>&review_page=<?= $review_page ?>"
                            style="padding:0.5rem 1rem;background:<?= $i == $trans_page ? '#8b5cf6' : 'rgba(139,92,246,0.2)' ?>;border:1px solid rgba(139,92,246,0.3);border-radius:8px;color:<?= $i == $trans_page ? '#fff' : '#8b5cf6' ?>;text-decoration:none;font-weight:600;min-width:40px;text-align:center;">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($trans_page < $total_trans_pages): ?>
                        <a href="?tab=product_manage&product_id=<?= $product_id ?>&trans_page=<?= $trans_page + 1 ?>&review_page=<?= $review_page ?>"
                            style="padding:0.5rem 1rem;background:rgba(139,92,246,0.2);border:1px solid rgba(139,92,246,0.3);border-radius:8px;color:#8b5cf6;text-decoration:none;font-weight:600;">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>

                    <span style="padding:0.5rem 1rem;color:var(--text-muted);font-size:0.9rem;">
                        Trang <?= $trans_page ?>/<?= $total_trans_pages ?> · Tổng <?= $total_transactions ?>
                    </span>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                <i class="fas fa-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                <p style="margin-top: 1rem;">Chưa có giao dịch nào</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Reviews -->
<div class="form-section">
    <div class="form-section-header">
        <i class="fas fa-star"></i>
        <h3>Đánh Giá (<?= $total_review_count ?>)</h3>
    </div>
    <div style="padding: 1.5rem;">
        <?php if (count($reviews) > 0): ?>
            <?php foreach ($reviews as $r): ?>
                <div class="review-card">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <div>
                            <strong style="color: var(--text-primary);">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($r['username'] ?? 'Anonymous') ?>
                            </strong>
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                                <?= htmlspecialchars($r['email'] ?? '') ?>
                            </div>
                        </div>
                        <div class="rating-stars">
                            <?php for ($i = 0; $i < $r['rating']; $i++)
                                echo '★'; ?>
                            <?php for ($i = $r['rating']; $i < 5; $i++)
                                echo '☆'; ?>
                        </div>
                    </div>
                    <p style="color: var(--text-secondary); margin: 0 0 1rem 0;">
                        <?= nl2br(htmlspecialchars($r['comment'] ?? '')) ?>
                    </p>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">
                        <i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($total_review_pages > 1): ?>
                <div
                    style="display:flex;justify-content:center;align-items:center;gap:0.5rem;margin-top:1.5rem;flex-wrap:wrap;">
                    <?php if ($review_page > 1): ?>
                        <a href="?tab=product_manage&product_id=<?= $product_id ?>&trans_page=<?= $trans_page ?>&review_page=<?= $review_page - 1 ?>"
                            style="padding:0.5rem 1rem;background:rgba(139,92,246,0.2);border:1px solid rgba(139,92,246,0.3);border-radius:8px;color:#8b5cf6;text-decoration:none;font-weight:600;">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $review_page - 2);
                    $end = min($total_review_pages, $review_page + 2);
                    for ($i = $start; $i <= $end; $i++):
                        ?>
                        <a href="?tab=product_manage&product_id=<?= $product_id ?>&trans_page=<?= $trans_page ?>&review_page=<?= $i ?>"
                            style="padding:0.5rem 1rem;background:<?= $i == $review_page ? '#8b5cf6' : 'rgba(139,92,246,0.2)' ?>;border:1px solid rgba(139,92,246,0.3);border-radius:8px;color:<?= $i == $review_page ? '#fff' : '#8b5cf6' ?>;text-decoration:none;font-weight:600;min-width:40px;text-align:center;">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($review_page < $total_review_pages): ?>
                        <a href="?tab=product_manage&product_id=<?= $product_id ?>&trans_page=<?= $trans_page ?>&review_page=<?= $review_page + 1 ?>"
                            style="padding:0.5rem 1rem;background:rgba(139,92,246,0.2);border:1px solid rgba(139,92,246,0.3);border-radius:8px;color:#8b5cf6;text-decoration:none;font-weight:600;">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>

                    <span style="padding:0.5rem 1rem;color:var(--text-muted);font-size:0.9rem;">
                        Trang <?= $review_page ?>/<?= $total_review_pages ?> · Tổng <?= $total_review_count ?>
                    </span>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                <i class="fas fa-comments" style="font-size: 3rem; opacity: 0.3;"></i>
                <p style="margin-top: 1rem;">Chưa có đánh giá nào</p>
            </div>
        <?php endif; ?>
    </div>
</div>