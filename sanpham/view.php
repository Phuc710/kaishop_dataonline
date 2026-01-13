<?php
require_once __DIR__ . '/../config/config.php';

// Support both ID and slug
$id = $_GET['id'] ?? null;
$slug = $_GET['slug'] ?? null;

// If path info exists (e.g., /sanpham/product-slug), extract slug
$pathInfo = $_SERVER['PATH_INFO'] ?? '';
if ($pathInfo && !$slug) {
    $slug = trim($pathInfo, '/');
}

// Try to find product by slug first, then by ID
if ($slug) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE slug = ? AND is_active = 1");
    $stmt->execute([$slug]);
    $product = $stmt->fetch();
} elseif ($id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
} else {
    $product = null;
}

if (!$product) {
    http_response_code(404);
    require_once __DIR__ . '/../404.php';
    exit;
}

// Update ID for later use
$id = $product['id'];

// Cập nhật view count
$pdo->prepare("UPDATE products SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);

// Product Variants
$stmt = $pdo->prepare("
    SELECT * FROM product_variants 
    WHERE product_id = ? AND is_active = 1 
    ORDER BY sort_order ASC, id ASC
");
$stmt->execute([$id]);
$variants = $stmt->fetchAll();

// Reviews
$stmt = $pdo->prepare("
    SELECT r.*, u.username, u.avatar 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = ? AND r.is_approved = 1 
    ORDER BY r.created_at DESC LIMIT 10
");
$stmt->execute([$id]);
$reviews = $stmt->fetchAll();

// Comments
$stmt = $pdo->prepare("
    SELECT c.*, u.username, u.avatar 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.product_id = ? AND c.is_approved = 1 AND c.parent_id IS NULL
    ORDER BY c.created_at DESC LIMIT 20
");
$stmt->execute([$id]);
$comments = $stmt->fetchAll();

$images = json_decode($product['images'] ?? '[]', true) ?: [];

$reviewCount = count($reviews);
$commentCount = count($comments);
$hasVariants = count($variants) > 0;

// Find first variant with stock > 0 (not just first variant)
$firstAvailableVariant = null;
if ($hasVariants) {
    foreach ($variants as $v) {
        if ($v['stock'] > 0) {
            $firstAvailableVariant = $v;
            break;
        }
    }
    // If no variant has stock, use first variant anyway
    if (!$firstAvailableVariant && isset($variants[0])) {
        $firstAvailableVariant = $variants[0];
    }
}

// Calculate actual stock and max order from first AVAILABLE variant
$actualStock = $firstAvailableVariant ? $firstAvailableVariant['stock'] : $product['stock'];
$actualMaxPurchase = $firstAvailableVariant ? $firstAvailableVariant['max_purchase'] : $product['max_purchase'];
$maxOrder = min($actualStock, $actualMaxPurchase);

// Get exchange rate for JS
$exchange_rate = 25000;
try {
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='exchange_rate'");
    $exchange_rate = floatval($stmt->fetchColumn() ?? 25000);
} catch (Exception $e) {
}
?>
<?php
// Dùng header chung (HeaderComponent sẽ render <head> + header + mở <body>)
$pageTitle = $product['name'] . ' - ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
?>
<!-- Product View Styles -->
<link rel="stylesheet" href="<?= asset('css/product-view.css') ?>">
<link rel="stylesheet" href="<?= asset('css/product-variants.css') ?>">

<!-- Animated Background -->
<div class="view-bg-animated"></div>

<section class="product-detail">
    <div class="container">
        <div class="product-detail-grid">
            <!-- Column 1: Gallery -->
            <div class="product-gallery">
                <div class="pd-main-wrap">
                    <img src="<?= asset('images/uploads/' . $product['image']) ?>" alt="<?= e($product['name']) ?>"
                        class="pd-main-img" id="mainImage">
                </div>
                <?php if (!empty($images)): ?>
                    <div class="pd-thumbs">
                        <button class="pd-thumb is-active" type="button" onclick="changeImage(this.dataset.src, this)"
                            data-src="<?= asset('images/uploads/' . $product['image']) ?>">
                            <img src="<?= asset('images/uploads/' . $product['image']) ?>" class="pd-thumb-img" alt="thumb">
                        </button>
                        <?php foreach ($images as $img): ?>
                            <button class="pd-thumb" type="button" onclick="changeImage(this.dataset.src, this)"
                                data-src="<?= asset('images/uploads/' . $img) ?>">
                                <img src="<?= asset('images/uploads/' . $img) ?>" class="pd-thumb-img" alt="thumb">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Column 2: Combined Product Info + Purchase -->
            <div class="product-purchase-section glass-card">
                <!-- Label + Stock Header -->
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                    <!-- Label (Left) -->
                    <?php if ($product['label']): ?>
                        <span class="product-label"
                            style="color: <?= e($product['label_text_color'] ?? '#ffffff') ?>; background: <?= e($product['label_bg_color'] ?? '#8b5cf6') ?>;">
                            <?= e($product['label']) ?>
                        </span>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>

                    <!-- Stock Badge (Right) -->
                    <div style="text-align:right;">
                        <div style="font-size:0.7rem;color:#64748b;margin-bottom:2px;">In Stock</div>
                        <div style="color:#10b981;font-weight:700;font-size:1.1rem;">
                            <?php if (in_array($product['product_type'], ['source', 'book'])): ?>
                                <span style="font-size:1.5rem;">∞</span> <span style="font-size:0.85rem;">Unlimited</span>
                            <?php else: ?>
                                <span class="stock-count" id="stockCount"><?= $actualStock ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Product Name (Nổi bật) -->
                <h1 style="font-size:1.8rem;margin:0 0 20px 0;line-height:1.3;font-weight:800;color:#f8fafc;">
                    <?= e($product['name']) ?>
                </h1>

                <!-- Price (Nổi bật) - Only show for simple products -->
                <?php if (!$hasVariants): ?>
                    <div style="margin-bottom:20px;">
                        <div style="display:flex;align-items:center;gap:12px;margin-bottom:4px;">
                            <?php if (isset($_SESSION['currency']) && $_SESSION['currency'] === 'USD'): ?>
                                <div class="price" id="displayPrice" style="font-size:2rem;font-weight:800;color:#fbbf24;">
                                    <?= formatUSD($product['final_price_usd']) ?>
                                </div>
                            <?php else: ?>
                                <div class="price" id="displayPrice" style="font-size:2rem;font-weight:800;color:#fbbf24;">
                                    <?= formatVND($product['final_price_vnd']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($product['discount_percent'] > 0): ?>
                                <span
                                    style="padding:4px 10px;background:#ef4444;color:#fff;border-radius:8px;font-size:0.75rem;font-weight:700;">-<?= $product['discount_percent'] ?>%</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($product['discount_percent'] > 0): ?>
                            <div style="font-size:0.95rem;color:#64748b;text-decoration:line-through;">
                                <?php if (isset($_SESSION['currency']) && $_SESSION['currency'] === 'USD'): ?>
                                    <?= formatUSD($product['price_usd']) ?>
                                <?php else: ?>
                                    <?= formatVND($product['price_vnd']) ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Stats -->
                <div
                    style="display:flex;gap:20px;margin-bottom:20px;font-size:0.85rem;color:#94a3b8;padding-bottom:16px;border-bottom:1px solid rgba(139,92,246,0.15);">
                    <span><i class="fas fa-star" style="color:#fbbf24;"></i> <?= $product['rating_count'] ?></span>
                    <span><i class="fas fa-shopping-cart"></i> <?= $product['sold_count'] ?></span>
                    <span><i class="fas fa-eye"></i> <?= $product['view_count'] ?></span>
                </div>

                <?php if ($hasVariants): ?>
                    <!-- Dynamic Price Display for Variants -->
                    <div style="margin-bottom:20px;">
                        <div id="variantPriceDisplay">
                            <?php
                            $firstVariant = $firstAvailableVariant ?? $variants[0];
                            $showOriginalPrice = isset($firstVariant['discount_percent']) && $firstVariant['discount_percent'] > 0;
                            ?>
                            <div style="display:flex;align-items:center;gap:12px;margin-bottom:4px;">
                                <div class="price" id="displayPrice" style="font-size:2rem;font-weight:800;color:#fbbf24;">
                                    <?php if (isset($_SESSION['currency']) && $_SESSION['currency'] === 'USD'): ?>
                                        <?= formatUSD($firstVariant['final_price_usd'] ?? $firstVariant['price_usd']) ?>
                                    <?php else: ?>
                                        <?= formatVND($firstVariant['final_price_vnd'] ?? $firstVariant['price_vnd']) ?>
                                    <?php endif; ?>
                                </div>
                                <?php if ($showOriginalPrice): ?>
                                    <span id="discountBadge"
                                        style="padding:4px 10px;background:#ef4444;color:#fff;border-radius:8px;font-size:0.75rem;font-weight:700;">-<?= $firstVariant['discount_percent'] ?>%</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($showOriginalPrice): ?>
                                <div id="originalPrice" style="font-size:0.95rem;color:#64748b;text-decoration:line-through;">
                                    <?php if (isset($_SESSION['currency']) && $_SESSION['currency'] === 'USD'): ?>
                                        <?= formatUSD($firstVariant['price_usd']) ?>
                                    <?php else: ?>
                                        <?= formatVND($firstVariant['price_vnd']) ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Product Variants -->
                    <div class="variant-selector">
                        <div class="variant-title">
                            <i class="fas fa-layer-group"></i> Chọn gói
                        </div>
                        <div class="variant-options">
                            <?php foreach ($variants as $index => $variant):
                                // Check this variant if it's the first available one
                                $isChecked = $firstAvailableVariant && $variant['id'] == $firstAvailableVariant['id'];
                                ?>
                                <label class="variant-label <?= $variant['stock'] <= 0 ? 'out-of-stock' : '' ?>">
                                    <input type="radio" name="variant" class="variant-input" value="<?= $variant['id'] ?>"
                                        data-price-vnd="<?= $variant['final_price_vnd'] ?? $variant['price_vnd'] ?>"
                                        data-price-usd="<?= $variant['final_price_usd'] ?? $variant['price_usd'] ?>"
                                        data-discount="<?= $variant['discount_percent'] ?? 0 ?>"
                                        data-original-vnd="<?= $variant['price_vnd'] ?>"
                                        data-original-usd="<?= $variant['price_usd'] ?>" data-stock="<?= $variant['stock'] ?>"
                                        data-max-purchase="<?= $variant['max_purchase'] ?? 2 ?>"
                                        data-min-purchase="<?= $variant['min_purchase'] ?? 1 ?>"
                                        data-requires-customer-info="<?= $variant['requires_customer_info'] ?? 0 ?>"
                                        data-customer-info-label="<?= htmlspecialchars($variant['customer_info_label'] ?? '') ?>"
                                        <?= $isChecked ? 'checked' : '' ?>         <?= $variant['stock'] <= 0 ? 'disabled' : '' ?>
                                        onchange="updateVariantPrice(this)" />
                                    <div class="variant-left">
                                        <span class="variant-custom"></span>
                                        <span class="variant-text"><?= e($variant['variant_name']) ?></span>
                                    </div>
                                    <div class="variant-price">
                                        <div style="text-align:right;">
                                            <?php if (isset($variant['discount_percent']) && $variant['discount_percent'] > 0): ?>
                                                <!-- Original Price + Discount Badge -->
                                                <div
                                                    style="display:flex;align-items:center;justify-content:flex-end;gap:8px;margin-bottom:4px;">
                                                    <span style="font-size:0.75rem;color:#64748b;text-decoration:line-through;">
                                                        <?php if (isset($_SESSION['currency']) && $_SESSION['currency'] === 'USD'): ?>
                                                            <?= formatUSD($variant['price_usd']) ?>
                                                        <?php else: ?>
                                                            <?= formatVND($variant['price_vnd']) ?>
                                                        <?php endif; ?>
                                                    </span>
                                                    <span
                                                        style="padding:2px 6px;background:#ef4444;color:#fff;border-radius:4px;font-size:0.65rem;font-weight:700;">-<?= $variant['discount_percent'] ?>%</span>
                                                </div>
                                            <?php endif; ?>
                                            <!-- Sale Price (Always Green) -->
                                            <div style="color:#10b981;font-weight:700;font-size:0.95rem;">
                                                <?php if (isset($_SESSION['currency']) && $_SESSION['currency'] === 'USD'): ?>
                                                    <?= formatUSD($variant['final_price_usd'] ?? $variant['price_usd']) ?>
                                                <?php else: ?>
                                                    <?= formatVND($variant['final_price_vnd'] ?? $variant['price_vnd']) ?>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($variant['stock'] <= 0): ?>
                                                <span style="font-size:0.7rem;color:#ef4444;margin-top:2px;display:block;">(Hết
                                                    hàng)</span>
                                            <?php elseif ($variant['stock'] < 10): ?>
                                                <span style="font-size:0.7rem;color:#64748b;margin-top:2px;display:block;">(Còn
                                                    <?= $variant['stock'] ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Quantity Selector -->
                <div style="margin-bottom:18px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                        <label style="color:#cbd5e1;font-weight:600;font-size:0.85rem;margin:0;">
                            <i class="fas fa-shopping-cart"></i> Số lượng
                        </label>
                        <small style="color:#64748b;font-size:0.75rem;">
                            Maximum order: <strong style="color:#f8fafc;"><?= $maxOrder ?></strong>
                        </small>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <button onclick="changeQty(-1)" class="btn btn-secondary"
                            style="width:40px;height:40px;padding:0;font-size:1rem;">-</button>
                        <input type="number" id="quantity" value="1" min="1" max="<?= $maxOrder ?>"
                            style="width:70px;height:40px;text-align:center;background:rgba(15,23,42,0.6);border:1px solid rgba(139,92,246,0.3);border-radius:8px;color:#f8fafc;font-size:0.95rem;font-weight:700;"
                            readonly>
                        <button onclick="changeQty(1)" class="btn btn-secondary"
                            style="width:40px;height:40px;padding:0;font-size:1rem;">+</button>
                    </div>
                </div>

                <!-- Voucher Box -->
                <div class="voucher-box" style="margin-bottom:18px;">
                    <div class="voucher-title" style="font-size:0.85rem;">
                        <i class="fas fa-tag"></i>
                        <span>Mã giảm giá</span>
                    </div>
                    <div class="voucher-input-group">
                        <input type="text" id="voucherCode" placeholder="Nhập mã voucher" style="font-size:0.85rem;" />
                        <button onclick="applyVoucher()" class="btn btn-secondary btn-voucher">
                            <i class="fas fa-check"></i>
                        </button>
                    </div>
                    <div class="voucher-applied" id="voucherApplied" style="font-size:0.8rem;">
                        <i class="fas fa-check-circle"></i>
                        <span id="voucherMessage"></span>
                    </div>
                </div>

                <!-- Customer Info Requirement -->
                <?php if (isset($product['requires_customer_info']) && $product['requires_customer_info'] == 1): ?>
                    <div id="customer-info-section"
                        style="margin-bottom:1.5rem;padding:1rem;background:rgba(139,92,246,0.1);border:1px solid rgba(139,92,246,0.3);border-radius:12px;">
                        <label style="display:block;color:#f8fafc;font-weight:600;margin-bottom:0.5rem;font-size:0.95rem;">
                            <i class="fas fa-user-edit" style="color:#8b5cf6;"></i>
                            <span
                                id="customer-info-label-text"><?= e($product['customer_info_label'] ?? 'Thông tin cần thiết') ?></span>
                            *
                        </label>
                        <textarea id="customer-info-input" class="form-control" rows="3"
                            placeholder="<?= e($product['customer_info_label'] ?? 'Nhập thông tin...') ?>"
                            style="background:rgba(15,23,42,0.8);width:100%; border:1px solid rgba(139,92,246,0.3);color:#f8fafc;padding:0.75rem;border-radius:8px;font-size:0.9rem;resize:vertical;"
                            required></textarea>
                        <small style="display:block;color:#94a3b8;margin-top:0.5rem;font-size:0.8rem;">
                            <i class="fas fa-info-circle"></i>
                            Đợi Admin xử lý đơn hàng này.
                        </small>
                    </div>
                <?php elseif ($hasVariants): ?>
                    <!-- Dynamic Customer Info Section for Variants -->
                    <div id="customer-info-section"
                        style="display:none;margin-bottom:1.5rem;padding:1rem;background:rgba(139,92,246,0.1);border:1px solid rgba(139,92,246,0.3);border-radius:12px;">
                        <label style="display:block;color:#f8fafc;font-weight:600;margin-bottom:0.5rem;font-size:0.95rem;">
                            <i class="fas fa-user-edit" style="color:#8b5cf6;"></i>
                            <span id="customer-info-label-text">Thông tin cần thiết</span> *
                        </label>
                        <textarea id="customer-info-input" class="form-control" rows="3" placeholder="Nhập thông tin..."
                            style="background:rgba(15,23,42,0.8);width:100%; border:1px solid rgba(139,92,246,0.3);color:#f8fafc;padding:0.75rem;border-radius:8px;font-size:0.9rem;resize:vertical;"></textarea>
                        <small style="display:block;color:#94a3b8;margin-top:0.5rem;font-size:0.8rem;">
                            <i class="fas fa-info-circle"></i>
                            Đợi Admin xử lý đơn hàng này.
                        </small>
                    </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <?php
                // Check if all variants are out of stock (or product without variants is out of stock)
                $allOutOfStock = false;
                if ($hasVariants) {
                    $allOutOfStock = !$firstAvailableVariant; // No available variant found
                } else {
                    $allOutOfStock = $product['stock'] <= 0;
                }
                ?>

                <?php if ($allOutOfStock): ?>
                    <!-- Out of Stock Message -->
                    <div
                        style="background: rgba(15, 23, 42, 0.97); border: 1px solid rgba(71, 85, 105, 0.3); border-radius: 16px; padding: 32px; text-align: center;">
                        <i class="fas fa-box-open"
                            style="font-size: 3rem; color: #475569; margin-bottom: 16px; display: block;"></i>
                        <div
                            style="font-size: 1.1rem; font-weight: 700; color: #cbd5e1; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 8px;">
                            Tạm hết hàng
                        </div>
                        <div style="font-size: 0.85rem; color: #64748b;">
                            Sản phẩm sẽ sớm có lại, vui lòng quay lại sau!
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Normal Action Buttons -->
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <button onclick="buyNowDetail()" class="btn btn-primary"
                            style="width:100%;padding:12px;font-size:0.95rem;font-weight:700;">
                            Mua Ngay
                        </button>
                        <button onclick="addToCartDetail()" class="btn btn-secondary"
                            style="width:100%;padding:12px;font-size:0.95rem;">
                            <i class="fas fa-cart-plus"></i> Thêm Giỏ Hàng
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bottom Section: Description/Reviews (Full Width) -->
        <div class="product-bottom-section">
            <div class="product-extra glass-card">
                <div class="pd-tabs">
                    <button type="button" class="pd-tab is-active" data-tab="description">
                        <i class="fas fa-info-circle"></i> Mô tả
                    </button>
                    <button type="button" class="pd-tab" data-tab="reviews">
                        <i class="fas fa-star"></i>
                        Đánh giá
                        <?php if ($reviewCount): ?>
                            <span class="count-pill"><?= $reviewCount ?></span>
                        <?php endif; ?>
                    </button>
                </div>

                <div class="pd-tab-panels">
                    <!-- Tab: Mô tả -->
                    <div class="pd-tab-panel is-active" id="tab-description">
                        <div class="product-description">
                            <p><?= nl2br(e($product['description'])) ?></p>
                        </div>
                    </div>

                    <!-- Tab: Đánh giá -->
                    <div class="pd-tab-panel" id="tab-reviews">
                        <?php if ($reviewCount > 0): ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item"
                                    style="padding:18px 0;border-bottom:1px solid rgba(139,92,246,0.15);transition:background 0.3s ease;">
                                    <div style="display:flex;gap:15px;">
                                        <img class="review-avatar" src="<?= getUserAvatar($review) ?>"
                                            style="width:46px;height:46px;border-radius:50%;border:2px solid rgba(139,92,246,0.3);">
                                        <div class="review-content" style="flex:1;">
                                            <strong class="review-username"
                                                style="color:#f8fafc;font-weight:700;"><?= e($review['username']) ?></strong>
                                            <div class="review-stars" style="color:#fbbf24;margin:6px 0;">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="<?= $i <= $review['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <p class="review-comment"
                                                style="color:#94a3b8;line-height:1.6;margin:8px 0;font-size:14px;">
                                                <?= e($review['comment']) ?>
                                            </p>
                                            <small class="review-time"
                                                style="color:#64748b;font-size:12px;"><?= timeAgo($review['created_at']) ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align:center;padding:32px 10px;color:#94a3b8;">
                                <i class="fas fa-star" style="font-size:2.4rem;opacity:0.3;margin-bottom:10px;"></i>
                                <p>Chưa có đánh giá nào cho sản phẩm này</p>
                                <small style="color:#64748b;">Mua hàng và trở thành người đánh giá đầu tiên!</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
</section>

<script src="<?= asset('js/script.js') ?>"></script>
<script>
    const productId = '<?= $product['id'] ?>';
    const exchangeRate = <?= $exchange_rate ?>;
    let maxStock = <?= $actualStock ?>;
    const maxPurchase = <?= $actualMaxPurchase ?>;
    let maxOrder = Math.min(maxStock, maxPurchase);
    let appliedVoucher = null;
    let currentFinalPrice = null; // Store final price after voucher

    function changeImage(src, el) {
        const main = document.getElementById('mainImage');
        main.src = src;
        // active thumb highlight
        if (el) {
            document.querySelectorAll('.pd-thumb').forEach(t => t.classList.remove('is-active'));
            el.classList.add('is-active');
        }
    }

    function changeQty(delta) {
        const input = document.getElementById('quantity');
        let val = parseInt(input.value) + delta;
        // Min is always 1, max is min(stock, admin max_purchase)
        val = Math.max(1, Math.min(maxOrder, val));
        input.value = val;
        input.max = maxOrder; // Update max attribute
    }

    // Update price and stock when variant changes
    function updateVariantPrice(radio) {
        const priceVnd = parseFloat(radio.dataset.priceVnd);
        const priceUsd = parseFloat(radio.dataset.priceUsd);
        const originalVnd = parseFloat(radio.dataset.originalVnd);
        const originalUsd = parseFloat(radio.dataset.originalUsd);
        const discount = parseInt(radio.dataset.discount);
        const stock = parseInt(radio.dataset.stock);
        const variantMaxPurchase = parseInt(radio.dataset.maxPurchase || maxPurchase);
        const requiresCustomerInfo = parseInt(radio.dataset.requiresCustomerInfo || 0);
        const customerInfoLabel = radio.dataset.customerInfoLabel || 'Thông tin cần thiết';
        const currency = "<?= $_SESSION['currency'] ?? 'VND' ?>";

        // Update max order based on variant stock and max_purchase
        maxStock = stock;
        maxOrder = Math.min(maxStock, variantMaxPurchase);

        // Reset voucher khi đổi variant
        appliedVoucher = null;
        currentFinalPrice = null;
        const voucherApplied = document.getElementById('voucherApplied');
        const voucherCode = document.getElementById('voucherCode');
        if (voucherApplied) voucherApplied.classList.remove('show');
        if (voucherCode) voucherCode.value = '';

        // Update customer info section visibility
        const customerInfoSection = document.getElementById('customer-info-section');
        const customerInfoLabelText = document.getElementById('customer-info-label-text');
        const customerInfoInput = document.getElementById('customer-info-input');

        if (customerInfoSection) {
            if (requiresCustomerInfo === 1) {
                customerInfoSection.style.display = 'block';
                if (customerInfoLabelText) customerInfoLabelText.textContent = customerInfoLabel;
                if (customerInfoInput) {
                    customerInfoInput.placeholder = customerInfoLabel;
                    customerInfoInput.required = true;
                }
            } else {
                customerInfoSection.style.display = 'none';
                if (customerInfoInput) {
                    customerInfoInput.required = false;
                    customerInfoInput.value = '';
                }
            }
        }

        // Update display price with discount support
        const priceDisplay = document.getElementById('variantPriceDisplay');

        let priceHTML = '';
        if (discount > 0) {
            const origPrice = currency === 'USD' ? '$' + originalUsd.toFixed(2) : new Intl.NumberFormat('vi-VN').format(originalVnd) + 'đ';
            const finalPrice = currency === 'USD' ? '$' + priceUsd.toFixed(2) : new Intl.NumberFormat('vi-VN').format(priceVnd) + 'đ';

            priceHTML = `
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:4px;">
                    <div class="price" id="displayPrice" style="font-size:2rem;font-weight:800;color:#fbbf24;">${finalPrice}</div>
                    <span style="padding:4px 10px;background:#ef4444;color:#fff;border-radius:8px;font-size:0.75rem;font-weight:700;">-${discount}%</span>
                </div>
                <div style="font-size:0.95rem;color:#64748b;text-decoration:line-through;">${origPrice}</div>
            `;
        } else {
            const finalPrice = currency === 'USD' ? '$' + priceUsd.toFixed(2) : new Intl.NumberFormat('vi-VN').format(priceVnd) + 'đ';
            priceHTML = `
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:4px;">
                    <div class="price" id="displayPrice" style="font-size:2rem;font-weight:800;color:#fbbf24;">${finalPrice}</div>
                </div>
            `;
        }

        priceDisplay.innerHTML = priceHTML;

        // Update stock count
        document.getElementById('stockCount').textContent = stock;

        // Update max order display
        const maxOrderDisplay = document.querySelector('small strong');
        if (maxOrderDisplay) {
            maxOrderDisplay.textContent = maxOrder;
        }

        // Reset quantity to 1
        const qtyInput = document.getElementById('quantity');
        qtyInput.value = 1;
        qtyInput.max = maxOrder;
    }

    // Voucher functionality
    function applyVoucher() {
        const code = document.getElementById('voucherCode').value.trim().toUpperCase();
        if (!code) {
            notify.warning('Lỗi!', 'Vui lòng nhập mã voucher');
            return;
        }

        // Check if user is logged in
        if (!<?= isLoggedIn() ? 'true' : 'false' ?>) {
            notify.warning('Lỗi!', 'Bạn cần đăng nhập để sử dụng voucher');
            return;
        }

        const qty = parseInt(document.getElementById('quantity').value);

        // Get current price (from selected variant or default product)
        let currentPricePerUnit = <?= $product['final_price_vnd'] ?>;
        const selectedVariant = document.querySelector('input[name="variant"]:checked');
        if (selectedVariant) {
            currentPricePerUnit = parseFloat(selectedVariant.dataset.priceVnd);
        }

        const totalAmount = currentPricePerUnit * qty;

        fetch('<?= url('api/voucher') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'validate',
                code: code,
                total_amount: totalAmount,
                product_id: '<?= $product['id'] ?>'
            })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    appliedVoucher = data.voucher;
                    const voucherApplied = document.getElementById('voucherApplied');
                    const voucherMessage = document.getElementById('voucherMessage');

                    voucherMessage.textContent = `Đã áp dụng: ${data.voucher.code} - Giảm ${data.formatted.discount_amount}`;
                    voucherApplied.classList.add('show');

                    // Update displayed price
                    const displayPrice = document.getElementById('displayPrice');
                    const finalAmountTotal = data.calculation.final_amount; // Tổng tiền sau giảm
                    const discountAmountTotal = data.calculation.discount_amount; // Tổng tiền giảm

                    // Tính giá mỗi sản phẩm sau khi giảm
                    const pricePerUnitAfterDiscount = finalAmountTotal / qty;

                    // Store the final price for use in buyNowDetail
                    currentFinalPrice = pricePerUnitAfterDiscount;

                    // Giá gốc là currentPricePerUnit đã tính ở trên
                    const originalPricePerUnit = currentPricePerUnit;

                    // Format prices
                    const formattedOriginal = new Intl.NumberFormat('vi-VN').format(originalPricePerUnit) + 'đ';
                    const formattedFinal = new Intl.NumberFormat('vi-VN').format(pricePerUnitAfterDiscount) + 'đ';

                    // Calculate voucher display text
                    let voucherText = '';
                    const currency = "<?= $_COOKIE['currency'] ?? 'VND' ?>";

                    if (data.voucher.discount_type === 'percentage') {
                        voucherText = `-${data.voucher.discount_value}%`;
                    } else {
                        const discountValue = data.voucher.discount_value;
                        if (currency === 'USD') {
                            // Tính tương đương USD
                            const usdAmount = (discountValue / exchangeRate).toFixed(2);
                            voucherText = `-$${usdAmount}`;
                        } else {
                            voucherText = `-${new Intl.NumberFormat('vi-VN').format(discountValue)}đ`;
                        }
                    }

                    // Update price display - two rows
                    displayPrice.style.cssText = '';
                    displayPrice.innerHTML = `
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:4px;">
                    <span style="font-size:2rem;font-weight:900;color:#10b981;">${formattedFinal}</span>
                    <span style="font-size:1.1rem;color:#ef4444;font-weight:800;">${voucherText}</span>
                </div>
                <div style="font-size:1rem;color:#64748b;text-decoration:line-through;">${formattedOriginal}</div>
            `;

                    notify.success('Thành công!', data.message);
                } else {
                    notify.error('Lỗi!', data.message);
                }
            })
            .catch(err => {
                notify.error('Lỗi!', 'Không thể kết nối đến server');
            });
    }

    function addToCartDetail() {
        const qty = parseInt(document.getElementById('quantity').value);

        // Validate quantity
        if (!qty || qty < 1) {
            notify.error('Lỗi!', 'Vui lòng chọn số lượng hợp lệ');
            return;
        }

        // Check and validate customer info if required
        const customerInfoInput = document.getElementById('customer-info-input');
        let customerInfo = null;
        if (customerInfoInput && customerInfoInput.required) {
            customerInfo = customerInfoInput.value.trim();
            if (!customerInfo) {
                notify.warning('Thiếu thông tin', 'Vui lòng nhập thông tin yêu cầu');
                customerInfoInput.focus();
                return;
            }
        } else if (customerInfoInput) {
            // If not required, still get the value if provided
            customerInfo = customerInfoInput.value.trim() || null;
        }

        // Get selected variant if any
        const selectedVariant = document.querySelector('input[name="variant"]:checked');
        const variant_id = selectedVariant ? selectedVariant.value : null;

        // Prepare request data
        const requestData = {
            product_id: productId,
            quantity: qty
        };

        // Add variant_id if selected
        if (variant_id) {
            requestData.variant_id = variant_id;
        }

        // Add customer info if provided
        if (customerInfo) {
            requestData.customer_info = customerInfo;
        }

        fetch(window.APP_URL + '/giohang/add.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestData)
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    notify.success('Thành công!', data.message);
                    if (typeof updateCartCount === 'function') {
                        updateCartCount();
                    }
                } else {
                    notify.error('Lỗi!', data.message);
                }
            })
            .catch(err => {
                console.error('Add to cart error:', err);
                notify.error('Lỗi!', 'Không thể thêm vào giỏ hàng');
            });
    }

    function buyNowDetail() {
        if (!<?= isLoggedIn() ? 'true' : 'false' ?>) {
            window.location.href = '<?= url('auth') ?>?redirect=' + encodeURIComponent(window.location.href);
            return;
        }

        // Check and validate customer info if required
        const customerInfoInput = document.getElementById('customer-info-input');
        let customerInfo = null;
        if (customerInfoInput && customerInfoInput.required) {
            customerInfo = customerInfoInput.value.trim();
            if (!customerInfo) {
                notify.warning('Thiếu thông tin', 'Vui lòng nhập thông tin yêu cầu');
                customerInfoInput.focus();
                return;
            }
        } else if (customerInfoInput) {
            // If not required, still get the value if provided
            customerInfo = customerInfoInput.value.trim() || null;
        }

        const qty = parseInt(document.getElementById('quantity').value);
        const selectedVariant = document.querySelector('input[name="variant"]:checked');
        const variant_id = selectedVariant ? selectedVariant.value : null;
        const voucher_code = appliedVoucher ? appliedVoucher.code : null;

        // Get current price - use final price if voucher applied, otherwise original price
        let pricePerUnit;
        const currency = '<?= $_COOKIE['currency'] ?? 'VND' ?>';

        if (currency === 'USD') {
            if (currentFinalPrice !== null) {
                // Use voucher-discounted price (converted to USD)
                pricePerUnit = currentFinalPrice / exchangeRate;
            } else {
                // Use original price (USD)
                pricePerUnit = <?= $product['final_price_usd'] ?? ($product['final_price_vnd'] / $exchange_rate) ?>;
                if (selectedVariant) {
                    pricePerUnit = parseFloat(selectedVariant.dataset.priceUsd);
                }
            }
        } else {
            if (currentFinalPrice !== null) {
                // Use voucher-discounted price (VND)
                pricePerUnit = currentFinalPrice;
            } else {
                // Use original price (VND)
                pricePerUnit = <?= $product['final_price_vnd'] ?>;
                if (selectedVariant) {
                    pricePerUnit = parseFloat(selectedVariant.dataset.priceVnd);
                }
            }
        }

        const totalAmount = pricePerUnit * qty;

        // Show confirmation popup
        showPaymentConfirmation(totalAmount, currency, qty, variant_id, voucher_code, customerInfo);
    }

    function submitComment(e) {
        e.preventDefault();
        const text = document.getElementById('commentText').value.trim();
        if (!text) {
            notify.warning('Ở!', 'Vui lòng nhập nội dung bình luận');
            return;
        }

        fetch(window.APP_URL + '/api/comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, content: text })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    notify.success('Thành công!', 'Đã gửi bình luận! Đang tải lại...');
                    document.getElementById('commentText').value = '';
                    // Smooth reload to show new comment
                    if (window.smoothReloadWithProgress) {
                        smoothReloadWithProgress(1000);
                    }
                } else {
                    notify.error('Lỗi!', data.message || 'Không thể gửi bình luận');
                }
            })
            .catch(err => {
                notify.error('Lỗi!', 'Không thể kết nối đến server');
            });
    }

    // Tabs: mô tả / đánh giá
    document.addEventListener('DOMContentLoaded', function () {
        const tabs = document.querySelectorAll('.pd-tab');
        const panels = document.querySelectorAll('.pd-tab-panel');

        tabs.forEach(tab => {
            tab.addEventListener('click', function () {
                const target = this.dataset.tab;
                tabs.forEach(t => t.classList.remove('is-active'));
                this.classList.add('is-active');

                panels.forEach(p => {
                    if (p.id === 'tab-' + target) {
                        p.classList.add('is-active');
                    } else {
                        p.classList.remove('is-active');
                    }
                });
            });
        });

        // Initialize customer info section for first selected variant
        const selectedVariant = document.querySelector('input[name="variant"]:checked');
        if (selectedVariant) {
            updateVariantPrice(selectedVariant);
        }

        // Hover effects for review items
        document.querySelectorAll('.review-item').forEach(item => {
            item.addEventListener('mouseenter', function () {
                this.style.padding = '18px';
                this.style.margin = '0 -18px';
                this.style.borderRadius = '12px';
            });
            item.addEventListener('mouseleave', function () {
                this.style.padding = '18px 0';
                this.style.margin = '0';
            });
        });

        // Focus effect for textarea
        const textarea = document.getElementById('commentText');
        if (textarea) {
            textarea.addEventListener('focus', function () {
                this.style.borderColor = '#8b5cf6';
                this.style.boxShadow = '0 0 0 3px rgba(139, 92, 246, 0.15)';
            });
            textarea.addEventListener('blur', function () {
                this.style.borderColor = '#ffffff';
                this.style.boxShadow = 'none';
            });
        }
    });

    // Payment confirmation popup
    function showPaymentConfirmation(totalAmount, currency, qty, variant_id, voucher_code) {
        const formattedAmount = currency === 'VND'
            ? new Intl.NumberFormat('vi-VN').format(totalAmount) + 'đ'
            : '$' + totalAmount.toFixed(2);

        // Show voucher info if applied
        let voucherHTML = '';
        if (appliedVoucher) {
            const discountText = appliedVoucher.discount_type === 'percentage'
                ? `-${appliedVoucher.discount_value}%`
                : (currency === 'VND'
                    ? `-${new Intl.NumberFormat('vi-VN').format(appliedVoucher.discount_value)}đ`
                    : `-$${appliedVoucher.discount_value}`);

            voucherHTML = `
            <div class="popup-voucher-info">
                <i class="fas fa-tag"></i>
                <span>Mã giảm giá: <strong>${appliedVoucher.code}</strong></span>
                <span class="voucher-discount">${discountText}</span>
            </div>
        `;
        }

        const popup = document.createElement('div');
        popup.id = 'paymentPopup';
        popup.innerHTML = `
        <div class="popup-overlay" onclick="closePaymentPopup()"></div>
        <div class="popup-container">
            <div class="popup-header">
                <h3><i class="fas fa-shopping-bag"></i> Thanh Toán</h3>
                <button onclick="closePaymentPopup()" class="popup-close">&times;</button>
            </div>
            <div class="popup-body">
                <div class="popup-product-info">
                    <img src="<?= asset('images/uploads/' . $product['image']) ?>" alt="<?= e($product['name']) ?>">
                    <div>
                        <h4><?= e($product['name']) ?></h4>
                        <p>Số lượng: <strong>${qty}</strong></p>
                    </div>
                </div>
                ${voucherHTML}
                <div class="popup-amount">
                    <span>Tổng thanh toán:</span>
                    <strong class="popup-price">${formattedAmount}</strong>
                </div>
                <div class="popup-note">
                    <i class="fas fa-shield-check"></i>
                    <div>
                        <strong style="display:block;margin-bottom:4px;color:#60a5fa;">Hỗ trợ 24/7</strong>
                        <span>Nếu gặp lỗi, vui lòng mở Ticket để được hỗ trợ</span>
                    </div>
                </div>
            </div>
            <div class="popup-footer">
                <button onclick="closePaymentPopup()" class="btn btn-secondary">Hủy</button>
                <button onclick="confirmPayment(${qty}, ${variant_id ? `'${variant_id}'` : 'null'}, ${voucher_code ? `'${voucher_code}'` : 'null'})" class="btn btn-primary">
                    <i class="fas fa-check"></i> Xác Nhận
                </button>
            </div>
        </div>
    `;
        document.body.appendChild(popup);
        setTimeout(() => popup.classList.add('show'), 10);
    }

    function closePaymentPopup() {
        const popup = document.getElementById('paymentPopup');
        if (popup) {
            popup.classList.remove('show');
            setTimeout(() => popup.remove(), 300);
        }
    }

    function confirmPayment(qty, variant_id, voucher_code) {
        const popup = document.getElementById('paymentPopup');
        const confirmBtn = popup.querySelector('.btn-primary');
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<span class="loading-icon-inline"></span> Đang xử lý...';

        // Get customer info if exists
        const customerInfoInput = document.getElementById('customer-info-input');
        const customerInfo = customerInfoInput ? customerInfoInput.value.trim() : null;

        fetch(window.APP_URL + '/api/buy-now.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                product_id: productId,
                quantity: qty,
                variant_id: variant_id,
                voucher_code: voucher_code,
                customer_info: customerInfo
            })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closePaymentPopup();
                    showSuccessPopup(data);
                } else {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="fas fa-check"></i> Xác Nhận';

                    // Check if insufficient balance
                    if (data.insufficient_balance) {
                        closePaymentPopup();

                        // Calculate shortage amount
                        const shortage = data.required - data.balance;
                        const formattedShortage = data.currency === 'VND'
                            ? new Intl.NumberFormat('vi-VN').format(shortage) + 'đ'
                            : '$' + shortage.toFixed(2);

                        notify.show({
                            type: 'error',
                            title: 'Số dư không đủ!',
                            message: ` Vui lòng nạp thêm tiền: ${formattedShortage}.`,
                            showConfirm: true,
                            confirmText: 'Nạp',
                            cancelText: 'Đóng',
                            duration: 0,
                            onConfirm: () => {
                                // Redirect to deposit page with pre-filled amount
                                window.location.href = window.APP_URL + `/naptien?amount=${shortage}&currency=${data.currency}`;
                            },
                            onCancel: () => {
                                // Do nothing, just close
                            }
                        });
                    } else {
                        notify.error('Lỗi!', data.message);
                    }
                }
            })
            .catch(err => {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-check"></i> Xác Nhận';
                notify.error('Lỗi!', 'Không thể kết nối đến server');
            });
    }
</script>

<!-- Include popup chung -->
<?php include __DIR__ . '/../includes/success-popup.php'; ?>

<style>
    #paymentPopup,
    #successPopup {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    #paymentPopup.show,
    #successPopup.show {
        opacity: 1;
    }

    .popup-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(4px);
    }

    .popup-container {
        position: relative;
        background: rgba(15, 23, 42, 0.98);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 16px;
        max-width: 500px;
        width: 90%;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from {
            transform: translateY(30px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .popup-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 24px;
        border-bottom: 1px solid rgba(139, 92, 246, 0.2);
    }

    .popup-header h3 {
        color: #f8fafc;
        font-size: 1.3rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
    }

    .popup-close {
        background: none;
        border: none;
        color: #94a3b8;
        font-size: 2rem;
        cursor: pointer;
        transition: color 0.3s ease;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .popup-close:hover {
        color: #f8fafc;
    }

    .popup-body {
        padding: 24px;
    }

    .popup-product-info {
        display: flex;
        gap: 16px;
        align-items: center;
        margin-bottom: 20px;
        padding: 16px;
        background: rgba(30, 41, 59, 0.5);
        border-radius: 12px;
    }

    .popup-product-info img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid rgba(139, 92, 246, 0.2);
    }

    .popup-product-info h4 {
        color: #f8fafc;
        font-size: 1rem;
        font-weight: 600;
        margin: 0 0 8px 0;
    }

    .popup-product-info p {
        color: #94a3b8;
        font-size: 0.9rem;
        margin: 0;
    }

    .popup-amount {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(236, 72, 153, 0.15));
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 12px;
        margin-bottom: 16px;
    }

    .popup-amount span {
        color: #cbd5e1;
        font-size: 0.95rem;
    }

    .popup-price {
        color: #fbbf24;
        font-size: 1.5rem;
        font-weight: 800;
    }

    .popup-voucher-info {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid rgba(16, 185, 129, 0.3);
        border-radius: 10px;
        margin-bottom: 16px;
    }

    .popup-voucher-info i {
        color: #10b981;
        font-size: 1.1rem;
    }

    .popup-voucher-info span {
        color: #cbd5e1;
        font-size: 0.9rem;
        flex: 1;
    }

    .popup-voucher-info strong {
        color: #10b981;
        font-weight: 700;
    }

    .voucher-discount {
        color: #10b981;
        font-weight: 700;
        font-size: 1rem;
        margin-left: auto;
    }

    .popup-note {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px 16px;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(99, 102, 241, 0.1));
        border: 1px solid rgba(59, 130, 246, 0.25);
        border-radius: 10px;
        color: #cbd5e1;
        font-size: 0.85rem;
        line-height: 1.5;
    }

    .popup-note i {
        color: #60a5fa;
        font-size: 1.3rem;
        margin-top: 2px;
        flex-shrink: 0;
    }

    .popup-note strong {
        color: #60a5fa;
        font-size: 0.9rem;
    }

    .popup-footer {
        display: flex;
        gap: 12px;
        padding: 20px 24px;
        border-top: 1px solid rgba(139, 92, 246, 0.2);
    }

    .popup-footer .btn {
        flex: 1;
    }

    /* Success Popup */
    .success-container {
        text-align: center;
        padding: 40px 30px;
    }

    .success-animation {
        margin: 0 auto 20px;
        width: 150px;
        height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .success-animation img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .success-details {
        background: rgba(30, 41, 59, 0.5);
        border-radius: 12px;
        padding: 20px;
        margin: 20px 0;
        text-align: left;
    }

    .success-details p {
        color: #cbd5e1;
        font-size: 0.95rem;
        margin: 12px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .success-details i {
        color: #8b5cf6;
        width: 20px;
    }

    .success-actions {
        display: flex;
        gap: 12px;
        margin-top: 24px;
    }

    .success-actions .btn {
        flex: 1;
    }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>