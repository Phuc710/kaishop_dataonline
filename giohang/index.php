<?php
require_once __DIR__ . '/../config/config.php';

if (!isLoggedIn()) {
    redirect(url('auth?redirect=' . urlencode(url('giohang'))));
}

// Auto cleanup: remove cart items with invalid/inactive products
$pdo->exec("DELETE FROM cart WHERE user_id = {$_SESSION['user_id']} AND product_id NOT IN (SELECT id FROM products WHERE is_active = 1)");

$stmt = $pdo->prepare("
    SELECT 
        c.*,
        p.name,
        p.image,
        COALESCE(v.requires_customer_info, p.requires_customer_info) as requires_customer_info,
        COALESCE(v.customer_info_label, p.customer_info_label) as customer_info_label,
        COALESCE(v.final_price_vnd, p.final_price_vnd) as price_vnd,
        COALESCE(v.final_price_usd, p.final_price_usd) as price_usd,
        COALESCE(v.stock, p.stock) as stock,
        COALESCE(v.min_purchase, p.min_purchase) as min_purchase,
        COALESCE(v.max_purchase, p.max_purchase) as max_purchase,
        v.variant_name
    FROM cart c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN product_variants v ON c.variant_id = v.id AND v.is_active = 1
    WHERE c.user_id = ? AND p.is_active = 1
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

$currency = $_COOKIE['currency'] ?? 'VND';
$total_vnd = $total_usd = 0;
foreach ($cart_items as $item) {
    $total_vnd += $item['price_vnd'] * $item['quantity'];
    $total_usd += $item['price_usd'] * $item['quantity'];
}

$pageTitle = "Giỏ Hàng - " . SITE_NAME;
$pageDescription = 'Giỏ hàng của bạn tại KaiShop. Xem lại sản phẩm, cập nhật số lượng và thanh toán nhanh chóng, an toàn. Hỗ trợ nhiều hình thức thanh toán.';
$pageKeywords = 'giỏ hàng, thanh toán online, mua hàng trực tuyến, đặt hàng kaishop';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .cart-page {
        padding: 30px 0 60px;
        min-height: 100vh;
        position: relative;
    }

    /* Animated Background */
    .cart-bg-animated {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
        pointer-events: none;
        overflow: hidden;
    }

    .cart-bg-animated::before,
    .cart-bg-animated::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        filter: blur(100px);
        opacity: 0.2;
        animation: float 20s ease-in-out infinite;
    }

    .cart-bg-animated::before {
        width: 600px;
        height: 600px;
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
        top: -200px;
        right: -150px;
        animation-delay: 0s;
    }

    .cart-bg-animated::after {
        width: 520px;
        height: 520px;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        bottom: -200px;
        left: -150px;
        animation-delay: -12s;
    }

    @keyframes float {

        0%,
        100% {
            transform: translate(0, 0) scale(1);
        }

        25% {
            transform: translate(60px, -60px) scale(1.05);
        }

        50% {
            transform: translate(-40px, 40px) scale(0.95);
        }

        75% {
            transform: translate(50px, 25px) scale(1.02);
        }
    }

    .cart-header {
        text-align: center;
        margin-bottom: 50px;
    }

    .cart-header h1 {
        font-size: 2rem;
        font-weight: 800;
        margin: 0 0 10px 0;
        background: linear-gradient(135deg, #a78bfa, #ec4899);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .cart-header p {
        color: var(--text-muted);
        font-size: 1rem;
    }

    .cart-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
        margin-top: 30px;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 40px;
        /* Increased padding */
        width: 100%;
        box-sizing: border-box;
    }

    .cart-item {
        background: var(--bg-card);
        backdrop-filter: blur(20px);
        border: 1px solid var(--cart-border);
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
        display: flex;
        gap: 24px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .cart-item:hover {
        border-color: rgba(139, 92, 246, 0.5);
        transform: translateY(-2px);
    }

    .cart-item-img {
        width: 140px;
        height: 140px;
        object-fit: cover;
        border-radius: 12px;
        flex-shrink: 0;
    }

    .cart-item-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .cart-item-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-main);
        margin: 0;
        line-height: 1.3;
    }

    .cart-item-price {
        font-size: 1.4rem;
        font-weight: 800;
        background: linear-gradient(135deg, #10b981, #059669);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .cart-qty-controls {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: auto;
    }

    .qty-box {
        display: flex;
        align-items: center;
        gap: 0;
        background: var(--bg-element);
        border-radius: 10px;
        padding: 4px;
        border: 1px solid var(--cart-border);
    }

    .qty-btn {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--border-light);
        border: none;
        color: var(--primary-light);
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .qty-btn:hover {
        background: var(--primary);
        color: #fff;
    }

    .qty-input {
        width: 40px;
        height: 32px;
        text-align: center;
        background: transparent;
        border: none;
        color: var(--text-main);
        font-size: 1rem;
        font-weight: 700;
    }

    .btn-remove {
        padding: 8px 16px;
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        border-radius: 12px;
        color: #ef4444;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-remove:hover {
        background: rgba(239, 68, 68, 0.9);
        border-color: #ef4444;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    .cart-item-total {
        text-align: right;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        align-items: flex-end;
    }

    .cart-item-total-price {
        font-size: 1.8rem;
        font-weight: 800;
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .cart-summary {
        background: var(--bg-card);
        backdrop-filter: blur(20px);
        border: 1px solid var(--cart-border);
        border-radius: 16px;
        padding: 30px;
        height: fit-content;
        position: sticky;
        top: 110px;
    }

    .cart-summary h2 {
        font-size: 1.5rem;
        font-weight: 800;
        margin: 0 0 24px 0;
        color: var(--text-main);
    }

    .btn-checkout {
        width: 100%;
        padding: 13px 24px;
        margin-top: 24px;
        background: linear-gradient(135deg, #0600ff 0%, #5f00b9 100%);
        border: none;
        border-radius: 99px;
        color: #fff;
        font-size: 1.1rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        text-decoration: none;
        position: relative;
    }

    .btn-checkout:hover {
        transform: translateY(-3px) scale(1.02);
    }

    .btn-checkout:active {
        transform: translateY(-1px) scale(0.98);
    }

    .btn-checkout .fa-arrow-right {
        transition: transform 0.3s ease;
    }

    .btn-checkout:hover .fa-arrow-right {
        transform: translateX(5px);
    }

    .btn-continue {
        width: 100%;
        padding: 13px 24px;
        margin-top: 12px;
        background: transparent;
        border: 2px solid rgba(139, 92, 246, 0.5);
        border-radius: 99px;
        color: #a78bfa;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-decoration: none;
        text-transform: uppercase;
    }

    .btn-continue:hover {
        background: rgba(139, 92, 246, 0.1);
        border-color: #8b5cf6;
        color: #fff;
        transform: translateY(-2px);
    }

    .empty-cart {
        background: var(--bg-card);
        backdrop-filter: blur(20px);
        border: 1px solid var(--cart-border);
        border-radius: 16px;
        text-align: center;
        padding: 80px 40px;
    }

    .empty-cart>i {
        font-size: 5rem;
        color: #ffffff;
        margin-bottom: 30px;
        display: block;
    }

    .empty-cart h2 {
        font-size: 1.6rem;
        color: var(--text-main);
        margin: 0 0 16px 0;
    }

    .empty-cart a {
        justify-self: center;
        align-self: center;
    }

    .empty-cart p {
        color: #94a3b8;
        font-size: 1rem;
        margin: 0 0 30px 0;
    }

    .empty-cart .btn-checkout {
        width: auto;
        display: inline-flex;
        min-width: 250px;
        margin: 0 auto;
        padding: 12px 30px;
    }

    .empty-cart .btn-checkout i {
        font-size: 1.5rem;
        color: #fff;
        margin: 0;
        display: inline-block;
    }

    @media (max-width: 1024px) {
        .cart-grid {
            grid-template-columns: 1fr;
        }

        .cart-summary {
            position: static;
        }
    }

    @media (max-width: 768px) {
        .cart-item {
            flex-direction: column;
        }

        .cart-item-img {
            width: 100%;
            height: 200px;
        }

        .cart-item-total {
            align-items: flex-start;
            flex-direction: row;
            justify-content: space-between;
        }

        .cart-header h1 {
            font-size: 1.6rem;
        }

        .cart-item-title {
            font-size: 1rem;
        }

        .cart-item-price {
            font-size: 1.2rem;
        }

        .cart-item-total-price {
            font-size: 1.4rem;
        }

        .btn-checkout {
            font-size: 1rem;
            padding: 10px 20px;
        }

        .btn-checkout .fa-arrow-right {
            display: none;
        }
    }

    /* Animated Checkbox */
    .checkbox-wrapper-12 {
        position: relative;
        margin-right: 16px;
    }

    .checkbox-wrapper-12>svg {
        position: absolute;
        top: -130%;
        left: -170%;
        width: 110px;
        pointer-events: none;
    }

    .checkbox-wrapper-12 input[type="checkbox"] {
        -webkit-appearance: none;
        appearance: none;
        cursor: pointer;
        margin: 0;
        position: absolute;
        top: 0;
        left: 0;
        width: 24px;
        height: 24px;
        border: 2px solid rgba(139, 92, 246, 0.4);
        border-radius: 50%;
        background: transparent;
    }

    .checkbox-wrapper-12 input[type="checkbox"]:focus {
        outline: 0;
    }

    .checkbox-wrapper-12 .cbx {
        width: 24px;
        height: 24px;
        position: relative;
    }

    .checkbox-wrapper-12 .cbx label {
        width: 24px;
        height: 24px;
        background: none;
        border-radius: 50%;
        position: absolute;
        top: 0;
        left: 0;
        pointer-events: none;
    }

    .checkbox-wrapper-12 .cbx svg {
        position: absolute;
        top: 5px;
        left: 4px;
        z-index: 1;
        pointer-events: none;
    }

    .checkbox-wrapper-12 .cbx svg path {
        stroke: #fff;
        stroke-width: 3;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-dasharray: 19;
        stroke-dashoffset: 19;
        transition: stroke-dashoffset 0.3s ease;
        transition-delay: 0.2s;
    }

    .checkbox-wrapper-12 .cbx input:checked+label {
        animation: splash-12 0.6s ease forwards;
    }

    .checkbox-wrapper-12 .cbx input:checked+label+svg path {
        stroke-dashoffset: 0;
    }

    @keyframes splash-12 {
        40% {
            background: #8b5cf6;
            box-shadow: 0 -18px 0 -8px #8b5cf6, 16px -8px 0 -8px #8b5cf6, 16px 8px 0 -8px #8b5cf6, 0 18px 0 -8px #8b5cf6, -16px 8px 0 -8px #8b5cf6, -16px -8px 0 -8px #8b5cf6;
        }

        100% {
            background: #8b5cf6;
            box-shadow: 0 -36px 0 -10px transparent, 32px -16px 0 -10px transparent, 32px 16px 0 -10px transparent, 0 36px 0 -10px transparent, -32px 16px 0 -10px transparent, -32px -16px 0 -10px transparent;
        }
    }

    /* Local Variables for Cart Page Visibility */
    .cart-page {
        --cart-border: rgba(255, 255, 255, 0.2);
    }

    /* Summary Box Styling */
    .summary-box {

        margin-bottom: 24px;
        padding: 20px;
        background: rgba(139, 92, 246, 0.1);
        border-radius: 12px;
    }

    .summary-label {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-bottom: 8px;
    }

    .summary-total {
        font-size: 2.2rem;
        font-weight: 900;
        /* Extra Bold */
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1.2;
    }

    /* --- LIGHT MODE OVERRIDES --- */
    [data-theme="light"] .cart-page {
        --cart-border: #cbd5e1;
    }

    [data-theme="light"] .cart-item-total-price {
        background: none !important;
        -webkit-background-clip: border-box !important;
        background-clip: border-box !important;
        -webkit-text-fill-color: #f66700ff !important;
        color: #f67700ff !important;
    }

    [data-theme="light"] .cart-item-price {
        background: none !important;
        -webkit-background-clip: border-box !important;
        background-clip: border-box !important;
        -webkit-text-fill-color: #059669 !important;
        color: #059669 !important;
    }

    [data-theme="light"] .summary-box {
        background: #f8fafc !important;
        border-color: #e2e8f0 !important;
    }


    .customer-info-box {
        margin: 12px 0;
        padding: 16px;
        background: rgba(251, 191, 36, 0.05);
        border: 1px solid rgba(251, 191, 36, 0.2);
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .customer-info-box:focus-within {
        background: rgba(251, 191, 36, 0.1);
        border-color: rgba(251, 191, 36, 0.5);
        box-shadow: 0 4px 12px rgba(251, 191, 36, 0.1);
    }

    .customer-info-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
        color: #fbbf24;
        margin-bottom: 10px;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .customer-info-input {
        width: 100%;
        padding: 12px 16px;
        background: var(--bg-element);
        border: 1px solid rgba(251, 191, 36, 0.2);
        border-radius: 10px;
        color: var(--text-main);
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .customer-info-input:focus {
        outline: none;
        background: var(--bg-hover);
        border-color: #fbbf24;
    }
</style>

<!-- Animated Background -->
<div class="cart-bg-animated"></div>

<div class="cart-page">
    <div class="container">
        <div class="cart-header">
            <h1><i class="fas fa-shopping-cart"></i> GIỎ HÀNG</h1>
        </div>

        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <h2>Giỏ hàng trống</h2>
                <p>Chưa có sản phẩm nào trong giỏ hàng của bạn</p>
                <a href="<?= url('sanpham') ?>" class="btn-checkout">
                    <span>Khám Phá Sản Phẩm</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        <?php else: ?>
            <div class="cart-grid">
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item" data-cart-id="<?= $item['id'] ?>"
                            data-price="<?= $currency === 'USD' ? $item['price_usd'] : $item['price_vnd'] ?>">
                            <!-- Checkbox -->
                            <div class="checkbox-wrapper-12">
                                <div class="cbx">
                                    <input type="checkbox" id="cbx-<?= $item['id'] ?>" class="cart-checkbox"
                                        data-cart-id="<?= $item['id'] ?>" onchange="saveCheckboxState()">
                                    <label for="cbx-<?= $item['id'] ?>"></label>
                                    <svg fill="none" viewBox="0 0 15 14" height="14" width="15">
                                        <path d="M2 8.36364L6.23077 12L13 2"></path>
                                    </svg>
                                </div>
                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg">
                                    <defs>
                                        <filter id="goo-<?= $item['id'] ?>">
                                            <feGaussianBlur result="blur" stdDeviation="4" in="SourceGraphic"></feGaussianBlur>
                                            <feColorMatrix result="goo" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 22 -7"
                                                mode="matrix" in="blur"></feColorMatrix>
                                            <feBlend in2="goo" in="SourceGraphic"></feBlend>
                                        </filter>
                                    </defs>
                                </svg>
                            </div>

                            <img src="<?= asset('images/uploads/' . $item['image']) ?>" alt="<?= e($item['name']) ?>"
                                class="cart-item-img">

                            <div class="cart-item-info">
                                <h3 class="cart-item-title"><?= e($item['name']) ?></h3>
                                <?php if (!empty($item['variant_name'])): ?>
                                    <div style="font-size: 0.85rem; color: #94a3b8; margin-top: 4px;">
                                        <i class="fas fa-tag"></i> <?= e($item['variant_name']) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($item['requires_customer_info'])): ?>
                                    <div class="customer-info-box">
                                        <label class="customer-info-label">
                                            <i class="fas fa-user-edit"></i>
                                            <?= e($item['customer_info_label'] ?: 'Thông tin yêu cầu') ?>
                                            <span style="color: #ef4444;">*</span>
                                        </label>
                                        <textarea class="customer-info-input" data-cart-id="<?= $item['id'] ?>" rows="3"
                                            placeholder="VD: Email, Facebook, SĐT..." style="resize:vertical;"
                                            onchange="updateCustomerInfo('<?= $item['id'] ?>', this.value)"><?= e($item['customer_info'] ?? '') ?></textarea>
                                    </div>
                                <?php endif; ?>

                                <!-- PRICE DISPLAY UPDATE -->
                                <div class="cart-item-price">
                                    <?php if ($currency === 'USD'): ?>
                                        $<?= number_format($item['price_usd'], 2) ?>
                                    <?php else: ?>
                                        <?= formatVND($item['price_vnd']) ?>
                                    <?php endif; ?>
                                </div>

                                <div class="cart-qty-controls">
                                    <div class="qty-box">
                                        <button onclick="updateQty('<?= $item['id'] ?>', -1)" class="qty-btn">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" value="<?= $item['quantity'] ?>"
                                            min="<?= $item['min_purchase'] ?? 1 ?>"
                                            max="<?= min($item['max_purchase'] ?? 999, $item['stock'] ?? 999) ?>"
                                            class="qty-input" id="qty-<?= $item['id'] ?>" data-cart-id="<?= $item['id'] ?>"
                                            readonly>
                                        <button onclick="updateQty('<?= $item['id'] ?>', 1)" class="qty-btn">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>

                                    <button onclick="removeItem('<?= $item['id'] ?>')" class="btn-remove">
                                        <i class="fas fa-trash"></i> Xóa
                                    </button>
                                </div>
                            </div>

                            <div class="cart-item-total">
                                <div class="cart-item-total-price">
                                    <?php if ($currency === 'USD'): ?>
                                        $<?= number_format($item['price_usd'] * $item['quantity'], 2) ?>
                                    <?php else: ?>
                                        <?= formatVND($item['price_vnd'] * $item['quantity']) ?>
                                    <?php endif; ?>
                                </div>
                                <small style="color:var(--text-disabled);font-size:0.85rem;">
                                    <?php if ($item['stock'] >= 9000): ?>
                                        ∞ Unlimited
                                    <?php else: ?>
                                        Còn <?= $item['stock'] ?> trong kho
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <h2><i class="fas fa-receipt"></i> Thanh Toán</h2>

                    <div class="summary-box">
                        <div class="summary-label">Tổng thành tiền</div>
                        <div class="summary-total" id="summary-total-display">
                            <?php if ($currency === 'USD'): ?>
                                $<?= number_format($total_usd, 2) ?>
                            <?php else: ?>
                                <?= formatVND($total_vnd) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="margin-bottom:20px;font-size:0.85rem;color:var(--text-muted);">
                        <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                            <span>Số sản phẩm chọn:</span>
                            <span id="selected-count"><?= count($cart_items) ?></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;">
                            <span>Tổng số lượng:</span>
                            <span id="total-qty"><?= array_sum(array_column($cart_items, 'quantity')) ?></span>
                        </div>
                    </div>

                    <button onclick="proceedToCheckout()" class="btn-checkout">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Thanh Toán</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                    <a href="<?= url('sanpham') ?>" class="btn-continue">
                        <i class="fas fa-shopping-bag"></i> Tiếp Tục Mua Sắm
                    </a>

                    <div
                        style="margin-top:24px;padding-top:24px;border-top:1px solid rgba(139,92,246,0.15);text-align:center;">
                        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">
                            <i class="fas fa-shield-alt" style="color:#10b981;"></i>
                            Thanh toán an toàn & bảo mật
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    const currentCurrency = '<?= $currency ?>'; // Define currency for JS

    function updateQty(cartId, delta) {
        cartId = String(cartId);
        const input = document.getElementById('qty-' + cartId);
        if (!input) return;

        const currentQty = parseInt(input.value) || 1;
        const newQty = currentQty + delta;
        const min = parseInt(input.getAttribute('min')) || 1;
        const max = parseInt(input.getAttribute('max')) || 999;

        if (newQty < min || newQty > max) return;

        // Update UI
        input.value = newQty;

        // Update total price
        const cartItem = document.querySelector(`[data-cart-id="${cartId}"]`);
        const pricePerUnit = parseFloat(cartItem.dataset.price || 0);
        const totalPriceEl = cartItem.querySelector('.cart-item-total-price');
        if (totalPriceEl && pricePerUnit) {
            const newTotal = pricePerUnit * newQty;
            if (currentCurrency === 'USD') {
                totalPriceEl.textContent = '$' + newTotal.toFixed(2);
            } else {
                totalPriceEl.textContent = new Intl.NumberFormat('vi-VN', {
                    style: 'currency',
                    currency: 'VND'
                }).format(newTotal);
            }
        }

        // Update cart summary
        updateCartSummary();

        // Send to server
        fetch('<?= url('giohang/update.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cart_id: cartId, quantity: newQty })
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    input.value = currentQty;
                    updateCartSummary();
                }
            })
            .catch(() => {
                input.value = currentQty;
                updateCartSummary();
            });
    }

    function updateCartSummary() {
        let total = 0;
        let selectedCount = 0;
        let totalQty = 0;

        document.querySelectorAll('.cart-item').forEach(item => {
            const checkbox = item.querySelector('.cart-checkbox');
            const isChecked = checkbox && checkbox.checked;

            if (isChecked) {
                const qty = parseInt(item.querySelector('.qty-input')?.value || 0);
                const price = parseFloat(item.dataset.price || 0);
                total += qty * price;
                selectedCount++;
                totalQty += qty;
            }
        });

        const summaryTotal = document.querySelector('.summary-total');
        const selectedCountEl = document.getElementById('selected-count');
        const totalQtyEl = document.getElementById('total-qty');

        let formatted;
        if (currentCurrency === 'USD') {
            formatted = '$' + total.toFixed(2);
        } else {
            formatted = new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(total);
        }

        if (summaryTotal) summaryTotal.textContent = formatted;
        if (selectedCountEl) selectedCountEl.textContent = selectedCount;
        if (totalQtyEl) totalQtyEl.textContent = totalQty;
    }

    async function removeItem(cartId) {
        cartId = String(cartId);

        const confirmed = await notify.confirm({
            title: 'Xác nhận xóa?',
            message: 'Bạn có chắc muốn xóa sản phẩm này?',
            type: 'warning',
            confirmText: 'Xóa',
            cancelText: 'Hủy'
        });

        if (!confirmed) return;

        const cartItem = document.querySelector(`[data-cart-id="${cartId}"]`);
        if (!cartItem) return;

        cartItem.style.transition = 'all 0.5s ease';
        cartItem.style.transform = 'translateX(-100%)';
        cartItem.style.opacity = '0';

        const response = await fetch('<?= url('giohang/remove.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cart_id: cartId })
        });

        const data = await response.json();

        if (data.success) {
            // Remove from saved state
            const savedState = JSON.parse(localStorage.getItem('cartCheckboxState') || '{}');
            delete savedState[cartId];
            localStorage.setItem('cartCheckboxState', JSON.stringify(savedState));

            // Remove from DOM with animation
            setTimeout(() => {
                cartItem.remove();

                // Check if cart is empty
                const remainingItems = document.querySelectorAll('.cart-item');
                if (remainingItems.length === 0) {
                    // Show empty cart message or redirect
                    window.location.href = '<?= url('giohang') ?>';
                }
            }, 500);
        } else {
            cartItem.style.transform = 'translateX(0)';
            cartItem.style.opacity = '1';
        }
    }

    // Save checkbox state to localStorage
    function saveCheckboxState() {
        const checkboxes = document.querySelectorAll('.cart-checkbox');
        const state = {};

        checkboxes.forEach(checkbox => {
            const cartId = checkbox.dataset.cartId;
            state[cartId] = checkbox.checked;
        });

        localStorage.setItem('cartCheckboxState', JSON.stringify(state));
        updateCartSummary();
    }

    // Restore checkbox state from localStorage
    function restoreCheckboxState() {
        const savedState = localStorage.getItem('cartCheckboxState');

        if (savedState) {
            const state = JSON.parse(savedState);
            const checkboxes = document.querySelectorAll('.cart-checkbox');

            checkboxes.forEach(checkbox => {
                const cartId = checkbox.dataset.cartId;
                if (state.hasOwnProperty(cartId)) {
                    checkbox.checked = state[cartId];
                } else {
                    // Nếu item mới, mặc định checked
                    checkbox.checked = true;
                }
            });
        } else {
            // Lần đầu tiên, check tất cả
            document.querySelectorAll('.cart-checkbox').forEach(cb => {
                cb.checked = true;
            });
        }

        updateCartSummary();
    }

    function proceedToCheckout() {
        const selectedItems = [];
        document.querySelectorAll('.cart-checkbox:checked').forEach(cb => selectedItems.push(cb.dataset.cartId));

        if (selectedItems.length === 0) {
            return notify.error('Thông báo', 'Vui lòng chọn ít nhất 1 sản phẩm để thanh toán');
        }

        fetch('<?= url('api/set-checkout-cart.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cart_ids: selectedItems })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '<?= url('thanhtoan') ?>';
                } else {
                    // Display detailed errors
                    if (data.errors && data.errors.length > 0) {
                        // Create error list HTML
                        const errorList = data.errors.map(err => `<div style="padding: 8px 0; border-bottom: 1px solid rgba(239, 68, 68, 0.2); text-align: left;"><i class="fas fa-exclamation-circle" style="color: #ef4444; margin-right: 8px;"></i>${err}</div>`).join('');
                        const errorHtml = `<div style="max-height: 300px; overflow-y: auto; margin-top: 12px;">${errorList}</div>`;

                        notify.error('Không thể thanh toán', errorHtml);
                    } else {
                        notify.error('Lỗi', data.message || 'Không thể tiếp tục thanh toán');
                    }
                }
            })
            .catch(() => notify.error('Lỗi', 'Không thể kết nối server'));
    }


    // Update customer info
    async function updateCustomerInfo(cartId, value) {
        cartId = String(cartId);

        try {
            const response = await fetch('<?= url('giohang/update.php') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cart_id: cartId, customer_info: value })
            });

            const data = await response.json();

            if (data.success) {
                // Visual feedback
                const input = document.querySelector(`.customer-info-input[data-cart-id="${cartId}"]`);
                if (input) {
                    input.style.borderColor = '#10b981';
                    setTimeout(() => {
                        input.style.borderColor = 'rgba(251, 191, 36, 0.3)';
                    }, 1500);
                }
            }
        } catch (error) {
            console.error('Update customer info error:', error);
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function () {
        restoreCheckboxState();
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>