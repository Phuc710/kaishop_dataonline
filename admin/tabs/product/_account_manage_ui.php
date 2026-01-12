<link rel="stylesheet" href="<?= asset('css/product_add.css') ?>?v=<?= time() ?>">
<link rel="stylesheet" href="<?= asset('css/product_manage.css') ?>?v=<?= time() ?>">

<!-- Header -->
<div class="page-header"
    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 1rem;">
            <i class="fas fa-user-shield"></i>
            Account: <?= htmlspecialchars($product['name']) ?>
        </h1>
        <p style="color: var(--text-muted); margin: 0.5rem 0 0 0;">
            <span
                style="background: <?= $product['label_bg_color'] ?>; color: <?= $product['label_text_color'] ?>; padding: 0.25rem 0.75rem; border-radius: 6px; font-weight: 700;">
                <?= htmlspecialchars($product['label'] ?? 'NORMAL') ?>
            </span>
            <span style="margin-left: 1rem;">ID: #<?= substr($product_id, -8) ?></span>
            <span class="status-badge" style="margin-left: 0.5rem; background: #1792e4ff;">
                <i class="fas fa-user-shield"></i> Account
            </span>
            <?php if ($hasVariants): ?>
                <span class="status-badge" style="margin-left: 0.5rem; background: #f59e0b;">
                    <i class="fas fa-layer-group"></i> Multi Variants
                </span>
            <?php endif; ?>
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="?tab=product_edit&product_id=<?= $product_id ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit Full
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
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-value" style="color: #10b981;"><?= number_format($total_sold) ?></div>
        <div class="stat-label">
            <i class="fas fa-box"></i> Đã Bán
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
            <i class="fas fa-money-bill-wave"></i> USD: $<?= number_format($total_revenue / $exchange_rate, 2) ?>
        </small>
    </div>

    <!-- Total Stock -->
    <div class="stat-card">
        <div class="stat-icon" style="color: #f59e0b;">
            <i class="fas fa-warehouse"></i>
        </div>
        <div class="stat-value" style="color: #f59e0b;"><?= number_format($total_stock) ?></div>
        <div class="stat-label">
            <i class="fas fa-boxes"></i> Tồn Kho
        </div>
        <?php if ($product['product_type'] === 'account'): ?>
            <button type="button" class="btn btn-sm btn-primary" onclick="openStockManagerLocal()"
                style="margin-top: 0.5rem; width: 100%;">
                <i class="fas fa-cog"></i> Quản Lý Kho
            </button>
        <?php endif; ?>
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
        <div class="stat-icon" style="color: #ef4444;">
            <i class="fas fa-star"></i>
        </div>
        <div class="stat-value" style="color: #ef4444;"><?= number_format($total_reviews) ?></div>
        <div class="stat-label">
            <i class="fas fa-comment-dots"></i> Đánh Giá
        </div>
        <?php if ($total_reviews > 0): ?>
            <div style="margin-top: 0.5rem;">
                <span class="rating-stars">
                    <?php
                    $fullStars = floor($avg_rating);
                    $halfStar = ($avg_rating - $fullStars) >= 0.5;
                    for ($i = 0; $i < $fullStars; $i++)
                        echo '★';
                    if ($halfStar)
                        echo '☆';
                    ?>
                </span>
                <span style="color: var(--text-muted); margin-left: 0.5rem;"><?= number_format($avg_rating, 1) ?>/5</span>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Product Info Card -->
<div class="form-section" style="margin-bottom: 2rem;">
    <div class="form-section-header">
        <i class="fas fa-info-circle"></i>
        <h3>Thông Tin Sản Phẩm</h3>
    </div>
    <div class="product-info-grid">
        <!-- Image -->
        <div class="product-image-container">
            <img src="<?= url('assets/images/uploads/' . $product['image']) ?>"
                alt="<?= htmlspecialchars($product['name']) ?>">
            <span class="category-badge">
                <?php if ($category): ?>
                    <?php if (isset($category['icon']) && preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $category['icon'])): ?>
                        <img src="<?= url('assets/images/uploads/' . $category['icon']) ?>">
                    <?php elseif (isset($category['icon'])): ?>
                        <span style="font-size: 1.2rem;"><?= $category['icon'] ?></span>
                    <?php else: ?>
                        <i class="fas fa-folder"></i>
                    <?php endif; ?>
                    <?= htmlspecialchars($category['name']) ?>
                <?php else: ?>
                    <i class="fas fa-folder"></i> Uncategorized
                <?php endif; ?>
            </span>
        </div>

        <!-- Details -->
        <div class="product-details">
            <div class="detail-item">
                <strong><i class="fas fa-tag"></i> Tên</strong>
                <p><?= htmlspecialchars($product['name']) ?></p>
            </div>

            <div class="detail-item">
                <strong><i class="fas fa-align-left"></i> Mô Tả</strong>
                <p><?= nl2br(htmlspecialchars($product['description'] ?? 'Không có mô tả')) ?></p>
            </div>

            <div class="detail-item">
                <strong><i class="fas fa-dollar-sign"></i> Giá</strong>
                <?php if (!$hasVariants): ?>
                    <div class="price-display">
                        <span class="price-main"><?= number_format($product['final_price_vnd']) ?>đ</span>
                        <?php if ($product['discount_percent'] > 0): ?>
                            <span class="price-old"><?= number_format($product['price_vnd']) ?>đ</span>
                            <span class="discount-badge">-<?= $product['discount_percent'] ?>%</span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #f59e0b; font-weight: 600;">Nhiều giá (xem variants bên dưới)</p>
                <?php endif; ?>
            </div>

            <?php if ($product['product_type'] === 'account' && $product['requires_customer_info']): ?>
                <div class="detail-item">
                    <strong><i class="fas fa-user-circle"></i> Yêu Cầu Thông Tin</strong>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span class="status-badge" style="background: #8b5cf6;">✓ Có</span>
                        <span><?= htmlspecialchars($product['customer_info_label'] ?? '') ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Variants Info (if exists) -->
<?php if ($hasVariants): ?>
    <div class="form-section" style="margin-bottom: 2rem;">
        <div class="form-section-header">
            <i class="fas fa-layer-group"></i>
            <h3>Variants (<?= count($variants) ?>)</h3>
        </div>
        <div style="padding: 1.5rem;">
            <div style="display: grid; gap: 1rem;">
                <?php foreach ($variants as $v): ?>
                    <div
                        style="background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 12px; padding: 1rem;">
                        <div style="display: grid; grid-template-columns: 1fr auto auto auto; gap: 1rem; align-items: center;">
                            <div>
                                <strong style="color: var(--text-primary); font-size: 1.1rem;">
                                    <i class="fas fa-star"></i> <?= htmlspecialchars($v['variant_name']) ?>
                                </strong>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 1.5rem; font-weight: 700; color: #10b981;">
                                    <?= number_format($v['final_price_vnd']) ?>đ</div>
                                <?php if ($v['discount_percent'] > 0): ?>
                                    <div style="font-size: 0.85rem; color: var(--text-muted); text-decoration: line-through;">
                                        <?= number_format($v['price_vnd']) ?>đ</div>
                                <?php endif; ?>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 0.85rem; color: var(--text-muted);">Tồn Kho</div>
                                <div style="font-size: 1.5rem; font-weight: 700; color: #f59e0b;">
                                    <?= number_format($v['stock']) ?></div>
                            </div>
                            <?php if ($product['product_type'] === 'account'): ?>
                                <button type="button" class="btn btn-sm btn-primary"
                                    onclick="openStockManagerLocal('<?= $v['id'] ?>')">
                                    <i class="fas fa-cog"></i> Quản Lý
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

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
                        <th>Variant</th>
                        <th>Số Lượng</th>
                        <th>Giá</th>
                        <th>Thời Gian</th>
                        <th>Trạng Thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td>
                                <strong style="color: #8b5cf6;">#<?= substr($t['order_id'], -8) ?></strong>
                            </td>
                            <td>
                                <div><strong><?= htmlspecialchars($t['username'] ?? 'Guest') ?></strong></div>
                                <div style="font-size: 0.85rem; color: var(--text-muted);">
                                    <?= htmlspecialchars($t['email'] ?? 'N/A') ?></div>
                            </td>
                            <td>
                                <?php if ($t['variant_name']): ?>
                                    <span style="color: #f59e0b;"><?= htmlspecialchars($t['variant_name']) ?></span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <span style="font-weight: 700; color: #10b981;">x<?= $t['quantity'] ?></span>
                            </td>
                            <td>
                                <strong><?= number_format($t['price_vnd']) ?>đ</strong>
                            </td>
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
                            style="padding:0.5rem 1rem;background:rgba(139,92,246,0.2);border:1px solid rgba(139,92,246,0.3);border-radius:8px;color:#8b5cf6;text-decoration:none;font-weight:600;transition:all 0.2s;">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $trans_page - 2);
                    $end = min($total_trans_pages, $trans_page + 2);
                    for ($i = $start; $i <= $end; $i++):
                        ?>
                        <a href="?tab=product_manage&product_id=<?= $product_id ?>&trans_page=<?= $i ?>&review_page=<?= $review_page ?>"
                            style="padding:0.5rem 1rem;background:<?= $i == $trans_page ? '#8b5cf6' : 'rgba(139,92,246,0.2)' ?>;border:1px solid rgba(139,92,246,0.3);border-radius:8px;color:<?= $i == $trans_page ? '#fff' : '#8b5cf6' ?>;text-decoration:none;font-weight:600;min-width:40px;text-align:center;transition:all 0.2s;">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($trans_page < $total_trans_pages): ?>
                        <a href="?tab=product_manage&product_id=<?= $product_id ?>&trans_page=<?= $trans_page + 1 ?>&review_page=<?= $review_page ?>"
                            style="padding:0.5rem 1rem;background:rgba(139,92,246,0.2);border:1px solid rgba(139,92,246,0.3);border-radius:8px;color:#8b5cf6;text-decoration:none;font-weight:600;transition:all 0.2s;">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>

                    <span style="padding:0.5rem 1rem;color:var(--text-muted);font-size:0.9rem;">
                        Trang <?= $trans_page ?>/<?= $total_trans_pages ?> · Tổng <?= $total_transactions ?> giao dịch
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

<!-- Reviews Section -->
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
                            style="padding:0.5rem 1rem;background:rgba(139,92,246,0.2);border:1px solid rgba(139,92,246,0.3);border-radius:8px;color:#8b5cf6;text-decoration:none;font-weight:600;transition:all 0.2s;">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $review_page - 2);
                    $end = min($total_review_pages, $review_page + 2);
                    for ($i = $start; $i <= $end; $i++):
                        ?>
                        <a href="?tab=product_manage&product_id=<?= $product_id ?>&trans_page=<?= $trans_page ?>&review_page=<?= $i ?>"
                            style="padding:0.5rem 1rem;background:<?= $i == $review_page ? '#8b5cf6' : 'rgba(139,92,246,0.2)' ?>;border:1px solid rgba(139,92,246,0.3);border-radius:8px;color:<?= $i == $review_page ? '#fff' : '#8b5cf6' ?>;text-decoration:none;font-weight:600;min-width:40px;text-align:center;transition:all 0.2s;">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($review_page < $total_review_pages): ?>
                        <a href="?tab=product_manage&product_id=<?= $product_id ?>&trans_page=<?= $trans_page ?>&review_page=<?= $review_page + 1 ?>"
                            style="padding:0.5rem 1rem;background:rgba(139,92,246,0.2);border:1px solid rgba(139,92,246,0.3);border-radius:8px;color:#8b5cf6;text-decoration:none;font-weight:600;transition:all 0.2s;">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>

                    <span style="padding:0.5rem 1rem;color:var(--text-muted);font-size:0.9rem;">
                        Trang <?= $review_page ?>/<?= $total_review_pages ?> · Tổng <?= $total_review_count ?> đánh giá
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

<!-- Stock Manager Modal (For Account Products) -->
<?php if ($product['product_type'] === 'account'): ?>
    <?php include __DIR__ . '/_stock_manager_modal.php'; ?>
    <script src="<?= asset('js/stock_manager.js') ?>?v=<?= time() ?>"></script>
    <script>
        // Initialize stock data
        const stockPoolData = <?= json_encode($stock_pool) ?>;

        function openStockManagerLocal(variantId = null) {
            const mode = variantId ? 'variant' : 'single';
            const key = variantId || 'single';

            // Initialize with data (even if empty array)
            const data = stockPoolData[key] || [];
            initStockManager(data);

            // Call the global openStockManager from stock_manager.js
            if (typeof window.openStockManager === 'function') {
                window.openStockManager(mode, variantId);
            } else {
                console.error('openStockManager function not found');
                alert('Lỗi: Không thể mở quản lý kho');
            }
        }
    </script>
<?php endif; ?>