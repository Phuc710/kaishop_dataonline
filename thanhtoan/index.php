<?php
require_once __DIR__ . '/../config/config.php';

if (!isLoggedIn()) {
    redirect(url('auth?redirect=' . urlencode(url('thanhtoan'))));
}

function getExchangeRate()
{
    global $pdo;
    static $rate = null;
    if ($rate === null) {
        try {
            $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='exchange_rate'");
            $rate = floatval($stmt->fetchColumn() ?? 25000);
        } catch (Exception $e) {
            $rate = 25000;
        }
    }
    return $rate;
}

$user = getCurrentUser();
$items = [];
$total_vnd = $total_usd = 0;
$direct_checkout = false;
$currency = $_COOKIE['currency'] ?? 'VND';

// Check for direct product checkout
$product_id = $_GET['product'] ?? '';
$quantity = isset($_GET['qty']) ? max(1, intval($_GET['qty'])) : 1;
$url_voucher = $_GET['voucher'] ?? '';

if (!empty($product_id)) {
    $direct_checkout = true;

    $stmt = $pdo->prepare("
        SELECT id as product_id, name, final_price_vnd, final_price_usd, 
               price_vnd, price_usd, image, stock, min_purchase, max_purchase,
               discount_percent, is_active
        FROM products WHERE id = ? AND is_active = 1
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        $pageTitle = "Lỗi - " . SITE_NAME;
        require_once __DIR__ . '/../includes/header.php';
        echo "<script>document.addEventListener('DOMContentLoaded', function() { notify.error('Lỗi!', 'Sản phẩm không tồn tại'); setTimeout(() => window.location.href = '" . url('sanpham') . "', 2000); });</script>";
        exit;
    }

    $min_qty = $product['min_purchase'] ?? 1;
    $max_qty = min($product['max_purchase'] ?? 999, $product['stock'] ?? 999);

    if ($quantity < $min_qty)
        $quantity = $min_qty;
    elseif ($quantity > $max_qty || $product['stock'] < $quantity) {
        $pageTitle = "Lỗi - " . SITE_NAME;
        require_once __DIR__ . '/../includes/header.php';
        echo "<script>document.addEventListener('DOMContentLoaded', function() { notify.error('Lỗi!', 'Số lượng không hợp lệ hoặc hết hàng'); setTimeout(() => window.location.href = '" . url('sanpham/view?id=' . $product_id) . "', 2000); });</script>";
        exit;
    }

    $variant_id = $_GET['variant'] ?? null;
    $variant_name = '';

    if ($variant_id) {
        $stmt = $pdo->prepare("SELECT * FROM product_variants WHERE id = ? AND product_id = ? AND is_active = 1");
        $stmt->execute([$variant_id, $product['product_id']]);
        $variant = $stmt->fetch();

        if ($variant) {
            $product['final_price_vnd'] = $variant['final_price_vnd'];
            $product['final_price_usd'] = $variant['final_price_usd'];
            $product['price_vnd'] = $variant['price_vnd'];
            $product['price_usd'] = $variant['price_usd'];
            $product['stock'] = $variant['stock'];
            $product['discount_percent'] = $variant['discount_percent'];
            $variant_name = $variant['variant_name'];

            if ($product['stock'] < $quantity) {
                $pageTitle = "Lỗi - " . SITE_NAME;
                require_once __DIR__ . '/../includes/header.php';
                echo "<script>document.addEventListener('DOMContentLoaded', function() { notify.error('Lỗi!', 'Phân loại không đủ hàng'); setTimeout(() => window.location.href = '" . url('sanpham/view?id=' . $product_id) . "', 2000); });</script>";
                exit;
            }
        } else {
            $variant_id = null;
        }
    }

    $items = [
        [
            'cart_id' => null,
            'quantity' => $quantity,
            'product_id' => (string) $product['product_id'],
            'variant_id' => $variant_id ? (string) $variant_id : null,
            'name' => $product['name'],
            'variant_name' => $variant_name,
            'final_price_vnd' => $product['final_price_vnd'],
            'final_price_usd' => $product['final_price_usd'],
            'price_vnd' => $product['price_vnd'],
            'price_usd' => $product['price_usd'],
            'image' => $product['image'],
            'stock' => $product['stock'],
            'discount_percent' => $product['discount_percent']
        ]
    ];

} else {
    $selected_cart_ids = $_SESSION['checkout_cart_ids'] ?? [];
    unset($_SESSION['checkout_cart_ids']);

    $cleanup_stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ? AND product_id NOT IN (SELECT id FROM products WHERE is_active = 1)");
    $cleanup_stmt->execute([$_SESSION['user_id']]);
    $invalid_count = $cleanup_stmt->fetchColumn();

    if ($invalid_count > 0) {
        $pdo->exec("DELETE FROM cart WHERE user_id = {$_SESSION['user_id']} AND product_id NOT IN (SELECT id FROM products WHERE is_active = 1)");
        $pageTitle = "Lỗi - " . SITE_NAME;
        require_once __DIR__ . '/../includes/header.php';
        echo "<script>document.addEventListener('DOMContentLoaded', function() { notify.error('Lỗi!', 'Đã xóa {$invalid_count} sản phẩm không hợp lệ'); setTimeout(() => window.location.href = '" . url('giohang') . "', 2000); });</script>";
        exit;
    }

    if (!empty($selected_cart_ids)) {
        $placeholders = str_repeat('?,', count($selected_cart_ids) - 1) . '?';
        $sql = "
            SELECT c.id as cart_id, c.quantity, c.product_id, c.variant_id, c.customer_info,
                   p.name, p.image,
                   COALESCE(v.final_price_vnd, p.final_price_vnd) as final_price_vnd,
                   COALESCE(v.final_price_usd, p.final_price_usd) as final_price_usd,
                   COALESCE(v.price_vnd, p.price_vnd) as price_vnd,
                   COALESCE(v.price_usd, p.price_usd) as price_usd,
                   COALESCE(v.stock, p.stock) as stock,
                   COALESCE(v.discount_percent, p.discount_percent) as discount_percent,
                   v.variant_name
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN product_variants v ON c.variant_id = v.id AND v.is_active = 1
            WHERE c.user_id = ? AND c.id IN ($placeholders) AND p.is_active = 1
        ";
        $stmt = $pdo->prepare($sql);
        $params = array_merge([$_SESSION['user_id']], $selected_cart_ids);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        foreach ($items as &$item) {
            $item['cart_id'] = (string) $item['cart_id'];
            $item['product_id'] = (string) $item['product_id'];
            if (isset($item['variant_id']))
                $item['variant_id'] = (string) $item['variant_id'];
        }
        unset($item);
    } else {
        $pageTitle = "Lỗi - " . SITE_NAME;
        require_once __DIR__ . '/../includes/header.php';
        echo "<script>document.addEventListener('DOMContentLoaded', function() { notify.error('Lỗi!', 'Vui lòng chọn sản phẩm từ giỏ hàng'); setTimeout(() => window.location.href = '" . url('giohang') . "', 1500); });</script>";
        exit;
    }

    if (empty($items)) {
        $pageTitle = "Lỗi - " . SITE_NAME;
        require_once __DIR__ . '/../includes/header.php';
        echo "<script>document.addEventListener('DOMContentLoaded', function() { notify.error('Lỗi!', 'Không tìm thấy sản phẩm'); setTimeout(() => window.location.href = '" . url('giohang') . "', 1500); });</script>";
        exit;
    }
}

foreach ($items as $item) {
    $total_vnd += $item['final_price_vnd'] * $item['quantity'];
    $total_usd += $item['final_price_usd'] * $item['quantity'];
}

$pageTitle = "Thanh Toán - " . SITE_NAME;
$pageDescription = 'Xác nhận đơn hàng và thanh toán an toàn tại KaiShop. Hỗ trợ nhiều hình thức thanh toán, giao dịch bảo mật 100%. Mua hàng nhanh chóng, tiện lợi.';
$pageKeywords = 'thanh toán online, đặt hàng kaishop, giao dịch an toàn, mua tài khoản trực tuyến';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    :root {
        --primary-grad: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
        --glass-bg: rgba(15, 23, 42, 0.6);
        --card-bg: rgba(30, 41, 59, 0.6);
    }

    body {
        background-color: #0f172a;
        background-image:
            radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
            radial-gradient(at 100% 100%, rgba(236, 72, 153, 0.1) 0px, transparent 50%);
        background-attachment: fixed;
        color: #f8fafc;
    }

    .checkout-page {
        padding: 60px 0;
        min-height: 80vh;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* Header */
    .page-header {
        text-align: center;
        margin-bottom: 3rem;
    }

    .page-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 16px;
        background: rgba(139, 92, 246, 0.1);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 100px;
        color: #a78bfa;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .page-badge img {
        width: 24px;
        height: 24px;
        object-fit: contain;
    }

    .page-title {
        font-size: 2.5rem;
        font-weight: 800;
        background: var(--primary-grad);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 0.5rem;
    }

    .page-subtitle {
        color: #94a3b8;
        font-size: 1rem;
    }

    /* Grid */
    .checkout-grid {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 2rem;
    }

    @media (max-width: 1024px) {
        .checkout-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Cards */
    .glass-card {
        background: var(--card-bg);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 1.5rem;
        backdrop-filter: blur(10px);
    }

    .card-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #f8fafc;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .card-title i {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    /* Product Items */
    .product-item {
        display: flex;
        gap: 1rem;
        padding: 1rem;
        background: rgba(15, 23, 42, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        margin-bottom: 1rem;
        transition: border-color 0.2s;
    }


    .product-img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 10px;
        flex-shrink: 0;
    }

    .product-info {
        flex: 1;
        min-width: 0;
    }

    .product-name {
        font-weight: 600;
        color: #f8fafc;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .product-variant {
        font-size: 0.85rem;
        color: #a78bfa;
        margin-bottom: 4px;
    }

    .product-qty {
        font-size: 0.85rem;
        color: #94a3b8;
    }

    .product-price {
        text-align: right;
        font-weight: 700;
        color: #fbbf24;
        font-size: 1.1rem;
        white-space: nowrap;
    }

    .customer-info-box {
        margin-top: 8px;
        padding: 8px 12px;
        background: rgba(139, 92, 246, 0.1);
        border-left: 3px solid #8b5cf6;
        border-radius: 6px;
        font-size: 0.85rem;
        color: #e9d5ff;
    }

    /* Summary */
    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        color: #cbd5e1;
        font-size: 0.95rem;
    }

    .summary-row.discount {
        color: #10b981;
        font-weight: 600;
    }

    .summary-row.total {
        border-top: 2px solid rgba(255, 255, 255, 0.1);
        margin-top: 0.5rem;
        padding-top: 1rem;
        font-size: 1.3rem;
        font-weight: 800;
        color: #fb2424ff;
    }

    .balance-info {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        background: rgba(15, 23, 42, 0.5);
        border-radius: 10px;
        margin-top: 1rem;
        font-size: 0.9rem;
        color: #94a3b8;
    }

    /* Voucher */
    .voucher-section {
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .voucher-group {
        display: flex;
        gap: 0.5rem;
    }

    .voucher-input {
        flex: 1;
        padding: 0.75rem 1rem;
        background: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(71, 85, 105, 0.5);
        border-radius: 10px;
        color: #f8fafc;
        font-size: 0.95rem;
        text-transform: uppercase;
    }

    .voucher-input:focus {
        outline: none;
        border-color: #8b5cf6;
    }

    .voucher-result {
        margin-top: 0.75rem;
        padding: 0.75rem;
        border-radius: 8px;
        font-size: 0.9rem;
    }

    .voucher-success {
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid rgba(16, 185, 129, 0.3);
        color: #10b981;
    }

    .voucher-error {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #ef4444;
    }

    /* Buttons */
    .btn {
        padding: 0.75rem 1.25rem;
        border: none;
        border-radius: 10px;
        font-size: 0.95rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-secondary {
        background: rgba(71, 85, 105, 0.3);
        color: #cbd5e1;
        border: 1px solid rgba(71, 85, 105, 0.5);
    }

    .btn-secondary:hover {
        background: rgba(71, 85, 105, 0.5);
    }

    .btn-pay {
        width: 100%;
        padding: 1rem 1.5rem;
        background: var(--primary-grad);
        color: white;
        font-size: 1.05rem;
        font-weight: 700;
        border-radius: 99px;
        margin-top: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-pay:hover {
        transform: translateY(-2px);
    }

    .btn-back {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        padding: 0.85rem 1.5rem;
        color: #94a3b8;
        background: transparent;
        border: 2px solid rgba(255, 255, 255, 0.1);
        border-radius: 99px;
        text-decoration: none;
        font-size: 1.05rem;
        font-weight: 700;
        margin-top: 1rem;
        transition: all 0.2s;
        gap: 0.5rem;
    }

    .btn-back:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.2);
        color: #ffffff;
    }

    @media (max-width: 640px) {
        .page-title {
            font-size: 2rem;
        }

        .product-item {
            flex-direction: column;
        }

        .product-img {
            width: 100%;
            height: 150px;
        }

        .product-price {
            text-align: left;
            margin-top: 0.5rem;
        }
    }

    /* --- LIGHT MODE OVERRIDES --- */
    [data-theme="light"] body {
        background-color: #ffffff !important;
        background-image: none !important;
        color: #0f172a !important;
    }

    [data-theme="light"] .page-title {
        background: none !important;
        -webkit-text-fill-color: #0f172a !important;
        color: #0f172a !important;
    }

    [data-theme="light"] .page-subtitle {
        color: #64748b !important;
    }

    [data-theme="light"] .glass-card {
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
        backdrop-filter: none !important;
    }

    [data-theme="light"] .card-title {
        color: #0f172a !important;
    }

    [data-theme="light"] .card-title i {
        background: #000000 !important;
        color: #ffffff !important;
    }

    [data-theme="light"] .product-item {
        background: #fefefeff !important;
        border-color: #e2e8f0 !important;
    }

    [data-theme="light"] .product-item:hover {
        border-color: #cbd5e1 !important;
    }

    [data-theme="light"] .product-name {
        color: #0f172a !important;
    }

    [data-theme="light"] .product-variant,
    [data-theme="light"] .product-qty {
        color: #64748b !important;
    }

    [data-theme="light"] .product-price {
        color: #059669 !important;
    }

    [data-theme="light"] .summary-row {
        color: #475569 !important;
    }

    [data-theme="light"] .summary-row.total,
    [data-theme="light"] #total-amount {
        border-top-color: #e2e8f0 !important;
        color: #ef4444 !important;
        /* Red */
    }

    [data-theme="light"] .voucher-input {
        background: #f1f5f9 !important;
        border-color: #cbd5e1 !important;
        color: #0f172a !important;
    }

    [data-theme="light"] .voucher-section label {
        color: #64748b !important;
    }

    [data-theme="light"] .balance-info {
        background: #f1f5f9 !important;
        color: #475569 !important;
    }

    [data-theme="light"] .balance-info span:last-child {
        color: #10b981 !important;
        /* Green */
    }

    [data-theme="light"] .customer-info-box {
        background: rgba(139, 92, 246, 0.05) !important;
        color: #5b21b6 !important;
    }

    [data-theme="light"] .btn-secondary {
        background: #f1f5f9 !important;
        color: #475569 !important;
        border-color: #cbd5e1 !important;
    }

    [data-theme="light"] .btn-secondary:hover {
        background: #e2e8f0 !important;
        color: #1e293b !important;
    }

    [data-theme="light"] .page-badge {
        background: rgba(139, 92, 246, 0.05) !important;
        border-color: rgba(139, 92, 246, 0.2) !important;
    }

    /* Checkout Buttons - Light Mode */
    [data-theme="light"] .btn-pay {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
        color: #ffffff !important;
        border-radius: 99px !important;
        box-shadow: none !important;
    }

    [data-theme="light"] .btn-pay:hover {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
        box-shadow: none !important;
    }

    [data-theme="light"] .btn-back {
        background: transparent !important;
        border: 2px solid #000000 !important;
        color: #000000 !important;
        font-weight: 700 !important;
        border-radius: 99px !important;
    }

    [data-theme="light"] .btn-back:hover {
        background: #000000 !important;
        color: #ffffff !important;
    }
</style>

<div class="checkout-page">
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <div class="page-badge">
                <img src="https://media.giphy.com/media/xje7ITeGqNAFWyvZ7a/giphy.gif" alt="Security">
                <span>Thanh toán an toàn</span>
            </div>
            <h1 class="page-title">Xác Nhận Đơn Hàng</h1>
            <p class="page-subtitle"><?= count($items) ?> sản phẩm đang chờ thanh toán</p>
        </div>

        <div class="checkout-grid">
            <!-- Left: Products -->
            <div>
                <div class="glass-card">
                    <h2 class="card-title">
                        <i class="fas fa-shopping-bag"></i>
                        Sản phẩm của bạn
                    </h2>

                    <?php foreach ($items as $item): ?>
                        <div class="product-item">
                            <img src="<?= asset('images/uploads/' . $item['image']) ?>" alt="<?= e($item['name']) ?>"
                                class="product-img">
                            <div class="product-info">
                                <div class="product-name"><?= e($item['name']) ?></div>
                                <?php if (!empty($item['variant_name'])): ?>
                                    <div class="product-variant"><?= e($item['variant_name']) ?></div>
                                <?php endif; ?>
                                <div class="product-qty">Số lượng: <?= $item['quantity'] ?></div>
                                <?php if (!empty($item['customer_info'])): ?>
                                    <div class="customer-info-box">
                                        <strong>Thông tin:</strong> <?= e($item['customer_info']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="product-price">
                                <?php if ($currency === 'USD'): ?>
                                    $<?= number_format($item['final_price_usd'] * $item['quantity'], 2) ?>
                                <?php else: ?>
                                    <?= formatVND($item['final_price_vnd'] * $item['quantity']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right: Summary -->
            <div>
                <div class="glass-card">
                    <h2 class="card-title">
                        <i class="fas fa-receipt"></i>
                        Tổng thanh toán
                    </h2>

                    <!-- Voucher -->
                    <div class="voucher-section">
                        <label style="display: block; color: #cbd5e1; font-size: 0.9rem; margin-bottom: 0.5rem;">Mã giảm
                            giá</label>
                        <div class="voucher-group">
                            <input type="text" id="voucher_code" class="voucher-input" placeholder="Nhập mã..."
                                value="<?= e($url_voucher) ?>">
                            <button onclick="applyVoucher()" class="btn btn-secondary" id="applyVoucherBtn">Áp
                                dụng</button>
                        </div>
                        <div id="voucher-result"></div>
                    </div>

                    <!-- Discount -->
                    <div id="discount-display" style="display: none;">
                        <div class="summary-row discount">
                            <span>Giảm giá</span>
                            <span id="discount-amount">-0</span>
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="summary-row total">
                        <span>Tổng cộng</span>
                        <span id="total-amount">
                            <?= $currency === 'USD' ? '$' . number_format($total_usd, 2) : formatVND($total_vnd) ?>
                        </span>
                    </div>

                    <!-- Balance -->
                    <div class="balance-info">
                        <span>Số dư hiện tại</span>
                        <span style="color: <?= $user['balance_vnd'] >= $total_vnd ? '#10b981' : '#ef4444' ?>">
                            <?php if ($currency === 'USD'): ?>
                                $<?= number_format($user['balance_vnd'] / getExchangeRate(), 2) ?>
                            <?php else: ?>
                                <?= formatVND($user['balance_vnd']) ?>
                            <?php endif; ?>
                        </span>
                    </div>

                    <!-- Pay Button -->
                    <button onclick="processPayment()" class="btn btn-pay">
                        <i class="fas fa-lock"></i>
                        Xác nhận thanh toán
                    </button>

                    <!-- Back Link -->
                    <?php if ($direct_checkout): ?>
                        <a href="<?= url('sanpham/view?id=' . $product_id) ?>" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Quay lại sản phẩm
                        </a>
                    <?php else: ?>
                        <a href="<?= url('giohang') ?>" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Quay lại giỏ hàng
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const currency = '<?= $currency ?>';
    const totalVND = <?= $total_vnd ?>;
    const totalUSD = <?= $total_usd ?>;
    const userBalanceVND = <?= $user['balance_vnd'] ?>;
    const exchangeRate = <?= getExchangeRate() ?>;
    const userBalanceUSD = userBalanceVND / exchangeRate;
    const items = <?= json_encode($items) ?>;
    const directCheckout = <?= $direct_checkout ? 'true' : 'false' ?>;

    let appliedVoucher = null;
    let discountAmount = 0;
    let finalAmount = currency === 'USD' ? totalUSD : totalVND;

    function applyVoucher() {
        const code = document.getElementById('voucher_code').value.trim().toUpperCase();
        const btn = document.getElementById('applyVoucherBtn');
        const result = document.getElementById('voucher-result');

        if (!code) {
            notify.warning('Lỗi!', 'Vui lòng nhập mã voucher');
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Đang kiểm tra...';

        const productIds = items.map(item => item.product_id);

        fetch('<?= url('api/voucher') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'validate',
                code: code,
                total_amount: totalVND,
                product_ids: productIds
            })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    appliedVoucher = data.voucher;
                    discountAmount = data.calculation.discount_amount;
                    finalAmount = data.calculation.final_amount;

                    updateDisplay();

                    result.className = 'voucher-result voucher-success';
                    result.textContent = data.message;
                    btn.textContent = 'Đã áp dụng';
                    document.getElementById('voucher_code').disabled = true;

                    notify.success('Thành công!', data.message);
                } else {
                    result.className = 'voucher-result voucher-error';
                    result.textContent = data.message;
                    btn.disabled = false;
                    btn.textContent = 'Áp dụng';

                    notify.error('Lỗi!', data.message);
                }
            })
            .catch(() => {
                result.className = 'voucher-result voucher-error';
                result.textContent = 'Lỗi kết nối';
                btn.disabled = false;
                btn.textContent = 'Áp dụng';
                notify.error('Lỗi!', 'Không thể kết nối server');
            });
    }

    function updateDisplay() {
        const discountDisplay = document.getElementById('discount-display');
        const discountAmountEl = document.getElementById('discount-amount');
        const totalAmountEl = document.getElementById('total-amount');

        if (appliedVoucher) {
            discountDisplay.style.display = 'block';

            if (currency === 'USD') {
                const discountPercent = discountAmount / totalVND;
                const usdDiscount = totalUSD * discountPercent;
                const usdFinal = totalUSD - usdDiscount;

                discountAmountEl.textContent = '-$' + usdDiscount.toFixed(2);
                totalAmountEl.textContent = '$' + usdFinal.toFixed(2);
                finalAmount = usdFinal;
            } else {
                discountAmountEl.textContent = '-' + new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(discountAmount);
                totalAmountEl.textContent = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(finalAmount);
            }
        }
    }

    function processPayment() {
        const userBalance = currency === 'USD' ? userBalanceUSD : userBalanceVND;
        const amountToPay = currency === 'USD' ? (appliedVoucher ? finalAmount : totalUSD) : (appliedVoucher ? finalAmount : totalVND);

        if (userBalance < amountToPay) {
            const needed = amountToPay - userBalance;
            const formattedNeeded = currency === 'USD' ? '$' + needed.toFixed(2) : new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(needed);

            notify.confirm({
                type: 'warning',
                title: 'Số dư không đủ!',
                message: 'Bạn cần nạp thêm ' + formattedNeeded,
                confirmText: 'Nạp tiền',
                cancelText: 'Hủy'
            }).then(confirmed => {
                if (confirmed) {
                    window.location.href = '<?= url('naptien') ?>?amount=' + Math.ceil(needed) + '&currency=' + currency;
                }
            });
            return;
        }

        fetch('<?= url('thanhtoan/process.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                items: items,
                currency: currency,
                amount: amountToPay,
                voucher_code: appliedVoucher ? appliedVoucher.code : '',
                discount_amount: discountAmount,
                direct_checkout: directCheckout
            })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Update data with client-side values for correct display
                    data.currency = currency;
                    data.amount = amountToPay;
                    const newBalance = userBalance - amountToPay;
                    data.new_balance = newBalance;

                    showSuccessPopup(data);
                } else {
                    notify.error('Lỗi!', data.message);
                }
            })
            .catch(() => {
                notify.error('Lỗi!', 'Không thể kết nối server');
            });
    }

    // Auto-apply voucher from URL
    if ('<?= $url_voucher ?>') {
        setTimeout(() => applyVoucher(), 300);
    }
</script>

<?php include __DIR__ . '/../includes/success-popup.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>