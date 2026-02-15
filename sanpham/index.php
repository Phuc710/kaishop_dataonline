<?php
require_once __DIR__ . '/../config/config.php';

$pageTitle = 'Sản Phẩm - ' . SITE_NAME;
$pageDescription = 'Mua tài khoản Netflix, Cursor Pro, Claude 4.5, ChatGPT Plus, Gemini Pro, Tool Free, Sách Free, Sách Hay, Youtube Premium, Spotify, Canva Pro, Gmail cổ, tài khoản mạng xã hội, shop acc uy tín giá rẻ nhất 2025. Giao hàng tự động 24/7, bảo hành 1 đổi 1 trọn đời. Uy tín #1 Việt Nam.';
$pageKeywords = 'mua tài khoản netflix giá rẻ, cursor pro, claudie 4.5, chatgpt plus, Gemini Pro,Tool Free, Sách Free, Sách Hay, youtube premium, spotify premium, canva pro, gmail cổ, tài khoản mạng xã hội, shop acc uy tín';

// Pagination
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Search & Filter - Force Reset on Page Load (non-AJAX)
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    $search = trim($_GET['search'] ?? '');
    $categorySlug = $_GET['category'] ?? '';
    $labelFilter = $_GET['label'] ?? '';
    $minPrice = $_GET['min_price'] ?? '';
    $maxPrice = $_GET['max_price'] ?? '';
    $sort = $_GET['sort'] ?? 'newest';
} else {
    // Standard Load: Force Default State
    $search = '';
    $categorySlug = '';
    $labelFilter = '';
    $minPrice = '';
    $maxPrice = '';
    $sort = 'newest';
}

// Convert category slug to ID if provided
$categoryId = '';
if ($categorySlug !== '') {
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND is_active = 1");
    $stmt->execute([$categorySlug]);
    $categoryId = $stmt->fetchColumn();
    // If slug not found, keep it empty to show all products
    if (!$categoryId) {
        $categorySlug = '';
    }
}

$where = ["p.is_active = 1"];
$where[] = "p.is_hidden = 0";
$params = [];

if ($search !== '') {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($categoryId !== '') {
    $where[] = "p.category_id = ?";
    $params[] = $categoryId;
}
if ($labelFilter !== '') {
    $where[] = "p.label = ?";
    $params[] = $labelFilter;
}

// Price range (Filtered based on base price or final price - usually final price is what matters to user)
if ($minPrice !== '' && is_numeric($minPrice)) {
    $where[] = "p.final_price_vnd >= ?";
    $params[] = (float) $minPrice;
}
if ($maxPrice !== '' && is_numeric($maxPrice)) {
    $where[] = "p.final_price_vnd <= ?";
    $params[] = (float) $maxPrice;
}

$whereClause = implode(' AND ', $where);

// Sorting logic
$orderBy = "p.is_pinned DESC, p.created_at DESC"; // Default
switch ($sort) {
    case 'price_asc':
        $orderBy = "p.is_pinned DESC, p.final_price_vnd ASC";
        break;
    case 'price_desc':
        $orderBy = "p.is_pinned DESC, p.final_price_vnd DESC";
        break;
    case 'name_asc':
        $orderBy = "p.is_pinned DESC, p.name ASC";
        break;
    case 'newest':
    default:
        $orderBy = "p.is_pinned DESC, p.created_at DESC";
        break;
}

// Count total
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $whereClause");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $limit));

// Fetch products - Chỉ lấy data từ variants CÓ HÀNG (stock > 0)
$stmt = $pdo->prepare("
    SELECT p.*,
        (SELECT COUNT(*) FROM product_variants pv WHERE pv.product_id = p.id AND pv.is_active = 1) as variant_count,
        COALESCE((SELECT pv.price_vnd FROM product_variants pv WHERE pv.product_id = p.id AND pv.is_active = 1 AND pv.stock > 0 ORDER BY pv.final_price_vnd ASC LIMIT 1), p.price_vnd) as display_price_vnd,
        COALESCE((SELECT pv.price_usd FROM product_variants pv WHERE pv.product_id = p.id AND pv.is_active = 1 AND pv.stock > 0 ORDER BY pv.final_price_vnd ASC LIMIT 1), p.price_usd) as display_price_usd,
        COALESCE((SELECT pv.final_price_vnd FROM product_variants pv WHERE pv.product_id = p.id AND pv.is_active = 1 AND pv.stock > 0 ORDER BY pv.final_price_vnd ASC LIMIT 1), p.final_price_vnd) as display_final_price_vnd,
        COALESCE((SELECT pv.final_price_usd FROM product_variants pv WHERE pv.product_id = p.id AND pv.is_active = 1 AND pv.stock > 0 ORDER BY pv.final_price_vnd ASC LIMIT 1), p.final_price_usd) as display_final_price_usd,
        COALESCE((SELECT pv.discount_percent FROM product_variants pv WHERE pv.product_id = p.id AND pv.is_active = 1 AND pv.stock > 0 ORDER BY pv.final_price_vnd ASC LIMIT 1), p.discount_percent) as display_discount_percent,
        COALESCE((SELECT SUM(pv.stock) FROM product_variants pv WHERE pv.product_id = p.id AND pv.is_active = 1), p.stock) as display_stock
    FROM products p
    WHERE $whereClause
    ORDER BY $orderBy
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Categories for filter
$categories = $pdo->query("SELECT id, name, slug, icon_value, icon_type, icon_url FROM categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC")->fetchAll();

// Labels for filter
$shop_labels = $pdo->query("SELECT * FROM product_labels ORDER BY name ASC")->fetchAll();

// AJAX Response (return JSON if AJAX request)
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');

    $productsHTML = '';
    foreach ($products as $product) {
        // Use slug if available, fallback to ID
        $productURL = $product['slug']
            ? url('sanpham/' . $product['slug'])
            : url('sanpham/view?id=' . $product['id']);


        // Label HTML - Text-based hardcoded labels
        $labelHTML = '';
        if (!empty($product['label'])) {
            $labelColor = htmlspecialchars($product['label_text_color'] ?? '#ffffff');
            $labelBg = htmlspecialchars($product['label_bg_color'] ?? '#8b5cf6');
            $labelHTML = '<span class="sp-card-label" style="color: ' . $labelColor . '; background: ' . $labelBg . ';">' . htmlspecialchars($product['label']) . '</span>';
        }
        // Rating stars
        $starsHTML = '';
        for ($i = 1; $i <= 5; $i++) {
            $starsHTML .= '<i class="' . ($i <= $product['rating_avg'] ? 'fas' : 'far') . ' fa-star"></i>';
        }

        // Price HTML (with consistent height)
        $hasMultipleVariants = ($product['variant_count'] > 1);
        $pricePrefix = $hasMultipleVariants ? '<span style="font-size:0.75rem;color:#94a3b8;font-weight:400;"></span>' : '';

        $priceHTML = '';
        if ($product['display_discount_percent'] > 0) {
            $priceHTML .= '<div style="display:flex;align-items:center;gap:8px;">';
            $priceHTML .= '<div class="sp-card-price" style="margin-bottom:0;">' . $pricePrefix . formatVND($product['display_final_price_vnd']) . '</div>';
            $priceHTML .= '<div style="font-size:0.75rem;color:#ef4444;font-weight:700;margin-bottom:2px;">-' . $product['display_discount_percent'] . '%</div>';
            $priceHTML .= '</div>';
            $priceHTML .= '<div style="font-size:0.85rem;color:#64748b;text-decoration:line-through;margin-top:2px;">' . formatVND($product['display_price_vnd']) . '</div>';
        } else {
            // Add empty space to match height
            $priceHTML .= '<div class="sp-card-price">' . $pricePrefix . formatVND($product['display_final_price_vnd']) . '</div>';
            $priceHTML .= '<div style="height:21px;margin-top:2px;"></div>';
        }

        // Out of stock overlay (not for source/book)
        $outOfStockHTML = '';
        $isDigitalProduct = in_array($product['product_type'], ['source', 'book']);
        if ($product['display_stock'] <= 0 && !$isDigitalProduct) {
            $outOfStockHTML = '<div class="out-of-stock-overlay">
                <div class="oos-content">
                    <i class="fas fa-box-open oos-icon"></i>
                    <span class="oos-text">Hết hàng</span>
                </div>
            </div>';
        }

        $productsHTML .= '
        <div class="sp-card" data-product-id="' . $product['id'] . '">
            <div class="sp-card-img-wrap" style="position: relative;">
                ' . $labelHTML . '
                ' . $outOfStockHTML . '
                <a href="' . $productURL . '">
                    <img src="' . asset('images/uploads/' . $product['image']) . '" alt="' . htmlspecialchars($product['name']) . '" class="sp-card-img">
                </a>
            </div>
            
            <div class="sp-card-body">
                <h3 class="sp-card-title">
                    <a href="' . $productURL . '">' . htmlspecialchars($product['name']) . '</a>
                </h3>
                
                <div class="sp-card-rating">
                    <span class="stars">' . $starsHTML . '</span>
                    <span>(' . $product['rating_count'] . ')</span>
                </div>
                
                <div class="sp-card-price-row">
                    <div>' . $priceHTML . '</div>
                    <span class="sp-card-stock">' . ($isDigitalProduct ? '∞ Unlimited' : 'Stock ' . $product['display_stock']) . '</span>
                </div>
                
                <div class="sp-card-actions">';

        $requiresInfo = !empty($product['requires_customer_info']);

        if ($product['display_stock'] <= 0 && !$isDigitalProduct) {
            // Out of stock - disabled button
            $productsHTML .= '
                    <button disabled class="sp-btn sp-btn-secondary" style="flex:1; opacity: 0.5; cursor: not-allowed;" title="Hết hàng">
                        <i class="fas fa-ban"></i>
                    </button>';
        } elseif ($hasMultipleVariants || $requiresInfo) {
            $productsHTML .= '
                    <button onclick="window.location.href=\'' . $productURL . '\'" class="sp-btn sp-btn-secondary" style="flex:1;" title="' . ($requiresInfo ? 'Nhập thông tin' : 'Chọn Option') . '">
                        <i class="fas fa-' . ($requiresInfo ? 'user-edit' : 'list') . '"></i>
                    </button>';
        } else {
            $productsHTML .= '
                    <button onclick="quickAddToCart(\'' . $product['id'] . '\', this)" class="sp-btn sp-btn-secondary" style="flex:1;" title="Thêm vào giỏ">
                        <i class="fas fa-cart-plus"></i>
                    </button>';
        }

        $productsHTML .= '
                    <button onclick="window.location.href=\'' . $productURL . '\'" class="sp-btn sp-btn-primary" style="flex:2;">
                        <i class="fas fa-shopping-cart"></i> Mua Ngay
                    </button>
                </div>
            </div>
        </div>';
    }

    echo json_encode([
        'success' => true,
        'html' => $productsHTML,
        'total' => $total,
        'totalPages' => $totalPages,
        'currentPage' => $page
    ]);
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Styles -->
<style>
    .sp-page {
        position: relative;
        padding: 30px 0 60px;
        overflow: hidden;
    }

    /* Container */
    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
    }

    @media (max-width: 768px) {
        .container {
            padding: 0 1rem;
        }
    }

    /* Animated Background */
    .sp-bg-animated {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
        pointer-events: none;
        overflow: hidden;
    }

    .sp-bg-animated::before,
    .sp-bg-animated::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        filter: blur(100px);
        opacity: 0.2;
        animation: float 20s ease-in-out infinite;
    }

    .sp-bg-animated::before {
        width: 600px;
        height: 600px;
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
        top: -200px;
        left: -100px;
        animation-delay: 0s;
    }

    .sp-bg-animated::after {
        width: 500px;
        height: 500px;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        bottom: -150px;
        right: -100px;
        animation-delay: -10s;
    }

    @keyframes float {

        0%,
        100% {
            transform: translate(0, 0) scale(1);
        }

        25% {
            transform: translate(50px, -50px) scale(1.1);
        }

        50% {
            transform: translate(-30px, 30px) scale(0.9);
        }

        75% {
            transform: translate(40px, 20px) scale(1.05);
        }
    }

    /* Header Section */
    .sp-header {
        text-align: center;
        margin-bottom: 20px;
        animation: fadeInDown 0.8s ease;
        position: relative;
    }



    .sp-header h1 {
        font-size: 3rem;
        font-weight: 800;
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 50%, #f97316 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 12px;
        letter-spacing: -1px;
        position: relative;
        display: inline-block;
        animation: gradientShift 3s ease infinite;
        background-size: 200% 200%;
    }

    @keyframes gradientShift {
        0% {
            background-position: 0% 50%;
        }

        50% {
            background-position: 100% 50%;
        }

        100% {
            background-position: 0% 50%;
        }
    }

    .sp-header p {
        font-size: 1.2rem;
        color: #94a3b8;
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }

    /* Filter Section */
    .sp-filter {
        margin-bottom: 40px;
        animation: fadeInUp 0.8s ease 0.2s backwards;
    }

    .sp-filter-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* Gradient Search Box Styles */
    .grid {
        height: 800px;
        width: 800px;
        background-image: linear-gradient(to right, #0f0f10 1px, transparent 1px),
            linear-gradient(to bottom, #0f0f10 1px, transparent 1px);
        background-size: 1rem 1rem;
        background-position: center center;
        position: absolute;
        z-index: -1;
        filter: blur(1px);
    }

    .white,
    .border,
    .darkBorderBg,
    .glow {
        max-height: 70px;
        max-width: 100%;
        height: 100%;
        width: 100%;
        position: absolute;
        overflow: hidden;
        z-index: -1;
        border-radius: 12px;
        filter: blur(3px);
    }

    #search-input-field {
        background-color: #010201;
        border: none;
        width: 100%;
        height: 56px;
        border-radius: 10px;
        color: white;
        padding-inline: 59px;
        font-size: 18px;
        transition: all 0.3s ease;
    }

    #poda {
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    #search-input-field::placeholder {
        color: #c0b9c0;
    }

    #search-input-field:focus {
        outline: none;
    }

    #main-search:focus-within>#input-mask {
        display: none;
    }

    #input-mask {
        pointer-events: none;
        width: 100px;
        height: 20px;
        position: absolute;
        background: linear-gradient(90deg, transparent, black);
        top: 18px;
        left: 70px;
        display: none;
        /* Ẩn hộp đen */
    }

    #pink-mask {
        pointer-events: none;
        width: 30px;
        height: 20px;
        position: absolute;
        background: #cf30aa;
        top: 10px;
        left: 5px;
        filter: blur(20px);
        opacity: 0.8;
        transition: all 2s;
    }

    #main-search:hover>#pink-mask {
        opacity: 0;
    }

    .white {
        max-height: 63px;
        border-radius: 10px;
        filter: blur(2px);
    }

    .white::before {
        content: "";
        z-index: -2;
        text-align: center;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(83deg);
        position: absolute;
        width: 600px;
        height: 600px;
        background-repeat: no-repeat;
        background-position: 0 0;
        filter: brightness(1.4);
        background-image: conic-gradient(rgba(0, 0, 0, 0) 0%,
                #a099d8,
                rgba(0, 0, 0, 0) 8%,
                rgba(0, 0, 0, 0) 50%,
                #dfa2da,
                rgba(0, 0, 0, 0) 58%);
        transition: all 2s;
    }

    .border {
        max-height: 59px;
        border-radius: 11px;
        filter: blur(0.5px);
    }

    .border::before {
        content: "";
        z-index: -2;
        text-align: center;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(70deg);
        position: absolute;
        width: 600px;
        height: 600px;
        filter: brightness(1.3);
        background-repeat: no-repeat;
        background-position: 0 0;
        background-image: conic-gradient(#1c191c,
                #402fb5 5%,
                #1c191c 14%,
                #1c191c 50%,
                #cf30aa 60%,
                #1c191c 64%);
        transition: all 2s;
    }

    .darkBorderBg {
        max-height: 65px;
    }

    .darkBorderBg::before {
        content: "";
        z-index: -2;
        text-align: center;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(82deg);
        position: absolute;
        width: 600px;
        height: 600px;
        background-repeat: no-repeat;
        background-position: 0 0;
        background-image: conic-gradient(rgba(0, 0, 0, 0),
                #18116a,
                rgba(0, 0, 0, 0) 10%,
                rgba(0, 0, 0, 0) 50%,
                #6e1b60,
                rgba(0, 0, 0, 0) 60%);
        transition: all 2s;
    }

    #poda:hover>.darkBorderBg::before {
        transform: translate(-50%, -50%) rotate(-98deg);
    }

    #poda:hover>.glow::before {
        transform: translate(-50%, -50%) rotate(-120deg);
    }

    #poda:hover>.white::before {
        transform: translate(-50%, -50%) rotate(-97deg);
    }

    #poda:hover>.border::before {
        transform: translate(-50%, -50%) rotate(-110deg);
    }

    #poda:focus-within>.darkBorderBg::before {
        transform: translate(-50%, -50%) rotate(442deg);
        transition: all 4s;
    }

    #poda:focus-within>.glow::before {
        transform: translate(-50%, -50%) rotate(420deg);
        transition: all 4s;
    }

    #poda:focus-within>.white::before {
        transform: translate(-50%, -50%) rotate(443deg);
        transition: all 4s;
    }

    #poda:focus-within>.border::before {
        transform: translate(-50%, -50%) rotate(430deg);
        transition: all 4s;
    }

    .glow {
        overflow: hidden;
        filter: blur(30px);
        opacity: 0.4;
        max-height: 130px;
    }

    .glow:before {
        content: "";
        z-index: -2;
        text-align: center;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(60deg);
        position: absolute;
        width: 999px;
        height: 999px;
        background-repeat: no-repeat;
        background-position: 0 0;
        background-image: conic-gradient(#000,
                #402fb5 5%,
                #000 38%,
                #000 50%,
                #cf30aa 60%,
                #000 87%);
        transition: all 2s;
    }

    #filter-icon {
        position: absolute;
        top: 8px;
        right: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2;
        max-height: 40px;
        max-width: 38px;
        height: 100%;
        width: 100%;
        isolation: isolate;
        overflow: hidden;
        border-radius: 10px;
        background: linear-gradient(180deg, #161329, black, #1d1b4b);
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    #filter-icon:hover {
        transform: scale(1.05);
        filter: brightness(1.2);
    }

    .filterBorder {
        height: 42px;
        width: 40px;
        position: absolute;
        overflow: hidden;
        top: 7px;
        right: 7px;
        border-radius: 10px;
    }

    .filterBorder::before {
        content: "";
        text-align: center;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(90deg);
        position: absolute;
        width: 600px;
        height: 600px;
        background-repeat: no-repeat;
        background-position: 0 0;
        filter: brightness(1.35);
        background-image: conic-gradient(rgba(0, 0, 0, 0),
                #3d3a4f,
                rgba(0, 0, 0, 0) 50%,
                rgba(0, 0, 0, 0) 50%,
                #3d3a4f,
                rgba(0, 0, 0, 0) 100%);
        animation: rotate 4s linear infinite;
    }

    #main-search {
        position: relative;
        width: 100%;
    }

    #search-icon {
        position: absolute;
        left: 20px;
        top: 15px;
        z-index: 2;
        pointer-events: none;
    }

    @keyframes rotate {
        100% {
            transform: translate(-50%, -50%) rotate(450deg);
        }
    }

    /* Category Tabs */
    .category-tabs-container {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        justify-content: center;
    }

    .category-tab {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        background: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 12px;
        color: #94a3b8;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        backdrop-filter: blur(8px);
    }

    .category-tab:hover {
        background: rgba(139, 92, 246, 0.1);
        border-color: rgba(139, 92, 246, 0.4);
        color: #f8fafc;
        transform: translateY(-2px);
    }

    .category-tab.active {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(236, 72, 153, 0.2));
        border-color: #8b5cf6;
        color: #fff;
    }

    .cat-icon {
        font-size: 1.2rem;
    }

    .cat-icon-img {
        width: 24px;
        height: 24px;
        object-fit: contain;
    }

    .cat-name {
        font-weight: 500;
        font-size: 0.95rem;
    }


    /* Product Card */
    .sp-card {
        background: rgba(30, 41, 59, 0.6);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        position: relative;
    }

    .sp-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        border-radius: 16px;
        padding: 1px;
        background: linear-gradient(135deg, #ffffff, rgba(236, 72, 153, 0.3));
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        opacity: 0;
        transition: opacity 0.4s ease;
    }

    .sp-card:hover {
        transform: translateY(-10px) scale(1.02);
        border-color: rgba(139, 92, 246, 0.6);
    }

    .sp-card:hover::before {
        opacity: 1;
    }

    .sp-card-img-wrap {
        position: relative;
        height: 200px;
        background: rgba(15, 23, 42, 0.8);
        overflow: hidden;
    }

    .sp-card-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .sp-card:hover .sp-card-img {
        transform: scale(1.05);
    }

    .sp-card-label {
        position: absolute;
        top: 12px;
        right: 12px;
        padding: 7px 16px;
        border-radius: 99px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        z-index: 3;
        /* Subtle glow animation */
        animation: glow-pulse 3s ease-in-out infinite;

        /* Enhanced glassy effect */
        backdrop-filter: blur(12px) saturate(180%);
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
        box-shadow: none;

        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    /* Subtle glow animation - no shaking */
    @keyframes glow-pulse {

        0%,
        100% {
            filter: brightness(1);
        }

        50% {
            filter: brightness(1.15);
        }
    }

    .sp-card:hover .sp-card-label {
        transform: translateY(-2px) scale(1.05);
        animation-play-state: paused;
        filter: brightness(1.1);
    }

    .sp-card-body {
        padding: 16px;
    }

    .sp-card-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #f8fafc;
        margin-bottom: 10px;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .sp-card-title a {
        color: #f8fafc;
        text-decoration: none;
        background: linear-gradient(135deg, #f8fafc, #a78bfa);
        -webkit-background-clip: text;
        -webkit-text-fill-color: #f8fafc;
        background-clip: text;
        transition: all 0.3s ease;
    }

    .sp-card:hover .sp-card-title a {
        -webkit-text-fill-color: transparent;
    }

    .sp-card-rating {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 12px;
        font-size: 13px;
        color: #94a3b8;
    }

    .sp-card-rating .stars {
        display: flex;
        gap: 2px;
        color: #fbbf24;
    }

    .sp-card-price-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 16px;
        padding-bottom: 16px;
        border-bottom: 1px solid rgba(139, 92, 246, 0.15);
        min-height: 80px;
    }

    .sp-card-price-row>div {
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        min-height: 65px;
    }

    .sp-card-price {
        font-size: 1.4rem;
        font-weight: 800;
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        position: relative;
        display: inline-block;
    }

    .sp-card-price::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 0;
        height: 2px;
        background: linear-gradient(135deg, #8b5cf6, #ec4899);
        transition: width 0.4s ease;
    }

    .sp-card:hover .sp-card-price::after {
        width: 100%;
    }

    .sp-card-stock {
        padding: 2px 12px;
        border-radius: 99px;
        font-size: 13px;
        color: #22c55e;
        font-weight: 600;
        align-self: flex-end;
        white-space: nowrap;
    }

    .sp-card-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    /* Buttons */
    .sp-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 700;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        position: relative;
        overflow: hidden;
    }

    .sp-btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .sp-btn:hover::before {
        width: 300px;
        height: 300px;
    }

    .sp-btn-primary {
        background: linear-gradient(135deg, #7C3AED, #EC4899);
        color: #fff;
        border: none;
        box-shadow: none;
    }

    .sp-btn-primary:hover {
        background: linear-gradient(135deg, #7C3AED, #EC4899);
        transform: translateY(-2px);
        box-shadow: none;
    }

    .sp-btn-secondary {
        background: rgba(148, 163, 184, 0.15);
        color: #e2e8f0;
        border: 1px solid rgba(148, 163, 184, 0.3);
    }

    .sp-btn-secondary:hover {
        transform: translateY(-2px);
        box-shadow: none;
    }

    /* Pagination */
    .sp-pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 50px;
        animation: fadeInUp 0.8s ease 0.6s backwards;
    }

    .sp-pagination .sp-btn {
        min-width: 44px;
        height: 44px;
        padding: 0;
    }

    /* Animations */
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Label Select Filter - Refined to match image */
    .sp-filter-select {
        background: #0f172a;
        border: 1px solid rgba(56, 189, 248, 0.3);
        color: #ffffff;
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        width: 100%;
        max-width: 280px;
        cursor: pointer;
        outline: none;
        transition: all 0.2s ease;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 16px;
    }

    .sp-filter-select:hover,
    .sp-filter-select:focus {
        border-color: #38bdf8;
        background: #111827;
        box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
    }

    .sp-filter-select option {
        background: #000000ff;
        color: #ffffffff;
    }

    .sp-card-label-image img {
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
        transition: all 0.3s;
    }

    .sp-card:hover .sp-card-label-image img {
        transform: scale(1.1);
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Category Tabs */
    .category-tabs-container {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .category-tabs-container::-webkit-scrollbar {
        display: none;
    }

    .category-tab {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: rgba(30, 41, 59, 0.6);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 99px;
        color: #94a3b8;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 600;
        white-space: nowrap;
        transition: all 0.3s ease;
    }

    .category-tab:hover {
        background: rgba(139, 92, 246, 0.15);
        border-color: rgba(139, 92, 246, 0.4);
        color: #f8fafc;
        transform: translateY(-2px);
    }

    .category-tab.active {
        background: linear-gradient(135deg, #6366f1, #ec4899);
        border-color: transparent;
        color: #fff;
        box-shadow: none;
    }

    .cat-icon {
        font-size: 1.2rem;
    }

    .cat-icon-img {
        width: 24px;
        height: 24px;
        object-fit: contain;
        border-radius: 4px;
    }

    .cat-name {
        font-weight: 600;
    }

    /* Out of Stock Overlay - New Stable Design */
    .out-of-stock-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.97);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }

    .oos-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        text-align: center;
        padding: 20px;
    }

    .oos-icon {
        font-size: 3rem;
        color: #ffffffff;
    }

    .oos-text {
        font-size: 1.1rem;
        font-weight: 700;
        color: #cbd5e1;
        text-transform: uppercase;
        letter-spacing: 1.5px;
    }

    .oos-subtext {
        font-size: 0.85rem;
        color: #64748b;
    }

    /* Product Grid Layout */
    .sp-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 24px;
        margin-bottom: 40px;
        padding: 0 20px;
    }

    /* Responsive */
    @media (max-width: 1400px) {
        .sp-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
    }

    @media (max-width: 1024px) {
        .sp-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }
    }

    @media (max-width: 768px) {
        .sp-header h1 {
            font-size: 2.4rem;
        }

        .sp-filter-form {
            grid-template-columns: 1fr;
        }

        .sp-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .sp-card-img-wrap {
            height: 240px;
        }

        .sp-card-body {
            padding: 18px;
        }

        .sp-card-title {
            font-size: 1.05rem;
        }

        .sp-card-price {
            font-size: 1.5rem;
        }

        #search-input-field {
            font-size: 14px;
            padding-left: 50px;
        }

        .sp-btn {
            font-size: 13px;
            padding: 10px 16px;
        }
    }

    @media (max-width: 480px) {
        #search-input-field {
            font-size: 14px;
        }

        .sp-header h1 {
            font-size: 2rem;
        }
    }

    /* Advanced Filter Panel */
    .advanced-filters {
        max-height: 0;
        overflow: hidden;
        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        opacity: 0;
        padding: 0 5px;
        margin-top: 0;
    }

    .advanced-filters.active {
        max-height: 500px;
        opacity: 1;
        margin-top: 20px;
        padding: 15px 5px;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(10px);
        padding: 20px;
        border-radius: 16px;
        border: 1px solid rgba(139, 92, 246, 0.2);
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .filter-label {
        font-size: 13px;
        font-weight: 600;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .filter-inputs {
        display: flex;
        gap: 10px;
    }

    .filter-input {
        background: rgba(30, 41, 59, 0.5);
        border: 1px solid rgba(148, 163, 184, 0.2);
        border-radius: 8px;
        padding: 10px;
        color: #f8fafc;
        font-size: 14px;
        width: 100%;
        transition: all 0.3s ease;
    }

    .filter-input:focus {
        border-color: #000000ff;
        box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.2);
        outline: none;
    }

    /* Black Labels for Filters */
    .filter-labels {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 5px;
    }

    .label-pill {
        background: #000;
        color: #fff;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid #333;
        transition: all 0.3s ease;
    }

    .label-pill:hover {
        background: #111;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
    }

    .label-pill.active {
        background: #fff;
        color: #000;
        border-color: #fff;
    }

    /* Filter Icon Animation */
    #filter-icon {
        cursor: pointer;
        transition: all 0.4s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #filter-icon.active {
        transform: rotate(90deg);
    }

    #filter-icon.active svg path {
        stroke: #ffffffff;
    }

    .reset-filters-btn {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.2);
        padding: 10px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        margin-top: auto;
    }

    .reset-filters-btn:hover {
        background: #ef4444;
        color: #fff;
    }
</style>

<!-- Animated Background -->
<div class="sp-bg-animated"></div>

<section class="sp-page">
    <div class="container">
        <!-- Header -->
        <div class="sp-header">
            <h1>All sản phẩm KaiShop</h1>
        </div>

        <!-- Filter -->
        <div class="sp-filter">
            <form method="GET" class="sp-filter-form" id="product-filter-form">
                <!-- Gradient Search Box -->
                <div id="poda">
                    <div class="glow"></div>
                    <div class="darkBorderBg"></div>
                    <div class="darkBorderBg"></div>
                    <div class="darkBorderBg"></div>
                    <div class="white"></div>
                    <div class="border"></div>

                    <div id="main-search">
                        <input placeholder="Search..." type="text" name="search" id="search-input-field" class="input"
                            value="<?= e($search) ?>" />
                        <div id="input-mask"></div>
                        <div id="pink-mask"></div>
                        <div class="filterBorder"></div>
                        <div id="filter-icon" onclick="toggleAdvancedFilters()">
                            <svg preserveAspectRatio="none" height="27" width="27" viewBox="4.8 4.56 14.832 15.408"
                                fill="none">
                                <path
                                    d="M8.16 6.65002H15.83C16.47 6.65002 16.99 7.17002 16.99 7.81002V9.09002C16.99 9.56002 16.7 10.14 16.41 10.43L13.91 12.64C13.56 12.93 13.33 13.51 13.33 13.98V16.48C13.33 16.83 13.1 17.29 12.81 17.47L12 17.98C11.24 18.45 10.2 17.92 10.2 16.99V13.91C10.2 13.5 9.97 12.98 9.73 12.69L7.52 10.36C7.23 10.08 7 9.55002 7 9.20002V7.87002C7 7.17002 7.52 6.65002 8.16 6.65002Z"
                                    stroke="#d6d6e6" stroke-width="1" stroke-miterlimit="10" stroke-linecap="round"
                                    stroke-linejoin="round"></path>
                            </svg>
                        </div>
                        <div id="search-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" viewBox="0 0 24 24" stroke-width="2"
                                stroke-linejoin="round" stroke-linecap="round" height="24" fill="none"
                                class="feather feather-search">
                                <circle stroke="url(#search)" r="8" cy="11" cx="11"></circle>
                                <line stroke="url(#searchl)" y2="16.65" y1="22" x2="16.65" x1="22"></line>
                                <defs>
                                    <linearGradient gradientTransform="rotate(50)" id="search">
                                        <stop stop-color="#f8e7f8" offset="0%"></stop>
                                        <stop stop-color="#b6a9b7" offset="50%"></stop>
                                    </linearGradient>
                                    <linearGradient id="searchl">
                                        <stop stop-color="#b6a9b7" offset="0%"></stop>
                                        <stop stop-color="#837484" offset="50%"></stop>
                                    </linearGradient>
                                </defs>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Advanced Filters Panel -->
                <div class="advanced-filters" id="advanced-filters-panel">
                    <div class="filter-grid">
                        <!-- Price Filtering -->
                        <div class="filter-group">
                            <label class="filter-label">Khoảng giá (VND)</label>
                            <div class="filter-inputs">
                                <input type="number" name="min_price" id="min_price" placeholder="Từ"
                                    class="filter-input"
                                    value="<?= isset($_GET['min_price']) ? e($_GET['min_price']) : '' ?>">
                                <input type="number" name="max_price" id="max_price" placeholder="Đến"
                                    class="filter-input"
                                    value="<?= isset($_GET['max_price']) ? e($_GET['max_price']) : '' ?>">
                            </div>
                        </div>

                        <!-- Sorting -->
                        <div class="filter-group">
                            <label class="filter-label">Sắp xếp theo</label>
                            <select name="sort" id="sort-select" class="filter-input">
                                <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                                <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Giá: Thấp đến Cao
                                </option>
                                <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Giá: Cao đến Thấp
                                </option>
                            </select>
                        </div>

                        <!-- Labels Selection (Changed to Select) -->
                        <div class="filter-group">
                            <label class="filter-label">Loại sản phẩm</label>
                            <select name="label" id="label-select" class="filter-input">
                                <option value="">Tất cả loại</option>
                                <option value="Free" <?= $labelFilter == 'Free' ? 'selected' : '' ?>>Free</option>
                                <option value="Source" <?= $labelFilter == 'Source' ? 'selected' : '' ?>>Source</option>
                                <option value="Account" <?= $labelFilter == 'Account' ? 'selected' : '' ?>>Account</option>
                                <option value="Other" <?= $labelFilter == 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>

                        <!-- Actions -->
                        <div class="filter-group" style="flex-direction: row; gap: 10px; align-items: flex-end;">
                            <button type="button" class="reset-filters-btn" onclick="resetAllFilters()"
                                style="flex: 1; height: 44px; border-radius: 8px; margin-top: 0;">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>


                <input type="hidden" name="category" id="category-input" value="<?= $categorySlug ?>">
                <div class="category-tabs-container" style="margin-top: 20px;">
                    <div onclick="selectCategory('')" class="category-tab active" id="cat-tab-all">
                        Tất cả
                    </div>
                    <?php foreach ($categories as $c):
                        $iconValue = $c['icon_value'] ?? '📦';
                        $iconType = $c['icon_type'] ?? 'emoji';
                        ?>
                        <div onclick="selectCategory('<?= $c['slug'] ?>')"
                            class="category-tab <?= $categorySlug == $c['slug'] ? 'active' : '' ?>"
                            id="cat-tab-<?= $c['slug'] ?>">
                            <?php if ($iconType === 'image'): ?>
                                <img src="<?= asset('images/uploads/' . $iconValue) ?>" alt="" class="cat-icon-img">
                            <?php else: ?>
                                <span class="cat-icon"><?= $iconValue ?></span>
                            <?php endif; ?>
                            <span class="cat-name"><?= e($c['name']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>

        <!-- Product Grid -->
        <div class="sp-grid">
            <?php foreach ($products as $product):
                $isDigitalProduct = in_array($product['product_type'], ['source', 'book']);
                ?>
                <div class="sp-card" data-product-id="<?= $product['id'] ?>">
                    <div class="sp-card-img-wrap" style="position: relative;">
                        <?php
                        $labelHTML = '';
                        if (!empty($product['label'])) {
                            $labelColor = htmlspecialchars($product['label_text_color'] ?? '#ffffff');
                            $labelBg = htmlspecialchars($product['label_bg_color'] ?? '#8b5cf6');
                            $labelHTML = '<span class="sp-card-label" style="color: ' . $labelColor . '; background: ' . $labelBg . ';">' . htmlspecialchars($product['label']) . '</span>';
                        }
                        echo $labelHTML;
                        ?>
                        <?php if ($product['display_stock'] <= 0 && !$isDigitalProduct): ?>
                            <div class="out-of-stock-overlay">
                                <div class="oos-content">
                                    <i class="fas fa-box-open oos-icon"></i>
                                    <span class="oos-text">Hết hàng</span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <a
                            href="<?= $product['slug'] ? url('sanpham/' . $product['slug']) : url('sanpham/view?id=' . $product['id']) ?>">
                            <img src="<?= asset('images/uploads/' . $product['image']) ?>" alt="<?= e($product['name']) ?>"
                                class="sp-card-img">
                        </a>
                    </div>

                    <div class="sp-card-body">
                        <h3 class="sp-card-title">
                            <a
                                href="<?= $product['slug'] ? url('sanpham/' . $product['slug']) : url('sanpham/view?id=' . $product['id']) ?>"><?= e($product['name']) ?></a>
                        </h3>

                        <div class="sp-card-rating">
                            <span class="stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?= $i <= $product['rating_avg'] ? 'fas' : 'far' ?> fa-star"></i>
                                <?php endfor; ?>
                            </span>
                            <span>(<?= $product['rating_count'] ?>)</span>
                        </div>

                        <div class="sp-card-price-row">
                            <div>
                                <?php
                                $hasMultipleVariants = ($product['variant_count'] > 1);
                                $pricePrefix = $hasMultipleVariants ? '<span style="font-size:0.75rem;color:#94a3b8;font-weight:400;"></span>' : '';
                                ?>
                                <?php if ($product['display_discount_percent'] > 0): ?>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div class="sp-card-price" style="margin-bottom:0;">
                                            <?= $pricePrefix ?>         <?= formatVND($product['display_final_price_vnd']) ?>
                                        </div>
                                        <div style="font-size:0.75rem;color:#ef4444;font-weight:700;margin-bottom:2px;">
                                            -<?= $product['display_discount_percent'] ?>%
                                        </div>
                                    </div>
                                    <div style="font-size:0.85rem;color:#64748b;text-decoration:line-through;margin-top:2px;">
                                        <?= formatVND($product['display_price_vnd']) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="sp-card-price">
                                        <?= $pricePrefix ?>         <?= formatVND($product['display_final_price_vnd']) ?>
                                    </div>
                                    <!-- Empty space to match discount height -->
                                    <div style="height:21px;margin-top:2px;"></div>
                                <?php endif; ?>
                            </div>
                            <span
                                class="sp-card-stock"><?= $isDigitalProduct ? '∞ Unlimited' : 'Stock ' . $product['display_stock'] ?></span>
                        </div>

                        <?php $requiresInfo = !empty($product['requires_customer_info']); ?>
                        <div class="sp-card-actions">
                            <?php if ($product['display_stock'] <= 0 && !$isDigitalProduct): ?>
                                <!-- Out of stock - only show detail button -->
                                <button disabled class="sp-btn sp-btn-secondary"
                                    style="flex:1; opacity: 0.5; cursor: not-allowed;" title="Hết hàng">
                                    <i class="fas fa-ban"></i>
                                </button>
                            <?php elseif ($hasMultipleVariants || $requiresInfo): ?>
                                <!-- For products with multiple variants OR requires customer info, redirect to detail page -->
                                <button
                                    onclick="window.location.href='<?= $product['slug'] ? url('sanpham/' . $product['slug']) : url('sanpham/view?id=' . $product['id']) ?>'"
                                    class="sp-btn sp-btn-secondary" style="flex:1;"
                                    title="<?= $requiresInfo ? 'Nhập thông tin' : 'Chọn Option' ?>">
                                    <i class="fas fa-<?= $requiresInfo ? 'user-edit' : 'list' ?>"></i>
                                </button>
                            <?php else: ?>
                                <!-- For products without variants or single variant, allow quick add -->
                                <button onclick="quickAddToCart('<?= $product['id'] ?>', this)" class="sp-btn sp-btn-secondary"
                                    style="flex:1;" title="Thêm vào giỏ">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            <?php endif; ?>
                            <button
                                onclick="window.location.href='<?= $product['slug'] ? url('sanpham/' . $product['slug']) : url('sanpham/view?id=' . $product['id']) ?>'"
                                class="sp-btn sp-btn-primary" style="flex:2;"> Mua Ngay
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="sp-pagination">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a class="sp-btn <?= $p == $page ? 'sp-btn-primary' : 'sp-btn-secondary' ?>"
                        href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<script src="<?= asset('js/script.js') ?>"></script>

<script>
    // AJAX Search without page reload
    const searchInput = document.getElementById('search-input-field');
    const categoryInput = document.getElementById('category-input');
    const filterForm = document.getElementById('product-filter-form');
    const productGrid = document.querySelector('.sp-grid');

    let searchTimeout;

    // Fetch products via AJAX
    async function fetchProducts() {
        const formData = new FormData(filterForm);
        // Explicitly update category value in params if needed, though FormData should catch it from hidden input
        const params = new URLSearchParams(formData);

        try {
            // Show global loading overlay
            Loading.show('Đang tải sản phẩm...');

            const response = await fetch(`?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                // Update grid
                if (data.html.trim() === '') {
                    productGrid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #94a3b8;"><i class="fas fa-search" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.3;"></i><h3 style="margin: 0; font-size: 1.2rem;">Không tìm thấy sản phẩm</h3><p style="margin-top: 10px; opacity: 0.7;">Thử tìm kiếm với từ khóa khác</p></div>';
                } else {
                    productGrid.innerHTML = data.html;

                    // Animate cards
                    const cards = productGrid.querySelectorAll('.sp-card');
                    cards.forEach((card, index) => {
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(20px)';
                        setTimeout(() => {
                            card.style.transition = 'all 0.5s ease';
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0)';
                        }, 50 * index);
                    });
                }

                // Update URL without reload
                const url = new URL(window.location);
                if (searchInput.value) {
                    url.searchParams.set('search', searchInput.value);
                } else {
                    url.searchParams.delete('search');
                }
                if (categoryInput && categoryInput.value) {
                    url.searchParams.set('category', categoryInput.value);
                } else {
                    url.searchParams.delete('category');
                }
                const labelSelect = document.getElementById('label-select');
                if (labelSelect && labelSelect.value) {
                    url.searchParams.set('label', labelSelect.value);
                } else {
                    url.searchParams.delete('label');
                }

                // Sync Price Range to URL
                const minPrice = document.getElementById('min_price');
                const maxPrice = document.getElementById('max_price');
                if (minPrice && minPrice.value) url.searchParams.set('min_price', minPrice.value);
                else url.searchParams.delete('min_price');
                if (maxPrice && maxPrice.value) url.searchParams.set('max_price', maxPrice.value);
                else url.searchParams.delete('max_price');

                window.history.pushState({}, '', url);
            }

            // Hide loading overlay
            Loading.hide();

        } catch (error) {
            console.error('Search error:', error);
            Loading.hide();
            productGrid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #ef4444;"><i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 20px;"></i><h3 style="margin: 0; font-size: 1.2rem;">Đã xảy ra lỗi</h3><p style="margin-top: 10px;">Vui lòng thử lại</p></div>';
        }
    }

    // Search input with debounce
    searchInput?.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            fetchProducts();
        }, 600); // 600ms debounce
    });

    // Price input with debounce
    ['min_price', 'max_price'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                fetchProducts();
            }, 800);
        });
    });

    // Sort & Label select change
    ['sort-select', 'label-select'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', fetchProducts);
    });

    // Toggle Advanced Filters
    function toggleAdvancedFilters() {
        const panel = document.getElementById('advanced-filters-panel');
        const icon = document.getElementById('filter-icon');
        panel.classList.toggle('active');
        icon.classList.toggle('active');
    }

    // Reset all filters
    function resetAllFilters() {
        // Reset inputs
        const minPrice = document.getElementById('min_price');
        const maxPrice = document.getElementById('max_price');
        const sortSelect = document.getElementById('sort-select');
        const labelSelect = document.getElementById('label-select');

        if (minPrice) minPrice.value = '';
        if (maxPrice) maxPrice.value = '';
        if (sortSelect) sortSelect.value = 'newest';
        if (labelSelect) labelSelect.value = '';
        if (searchInput) searchInput.value = '';

        // Fetch
        fetchProducts();
    }

    // Select Category Function
    function selectCategory(id) {
        // Update hidden input
        const catInput = document.getElementById('category-input');
        if (catInput) {
            catInput.value = id;
        }

        // Update active visual state
        document.querySelectorAll('.category-tab').forEach(el => {
            el.classList.remove('active');
        });

        // Find active tab and add class
        const activeTab = id === '' ? document.getElementById('cat-tab-all') : document.getElementById('cat-tab-' + id);
        if (activeTab) {
            activeTab.classList.add('active');
        }

        // Trigger search/fetch
        fetchProducts();
    }

    // Smooth animations on load & Auto Reset URL
    document.addEventListener('DOMContentLoaded', function () {
        // Clean URL if it's a standard load with parameters (matching PHP forced reset)
        const isAjax = <?= json_encode($isAjax) ?>;
        if (!isAjax && window.location.search) {
            const cleanUrl = window.location.pathname;
            window.history.replaceState({}, '', cleanUrl);
        }

        const cards = document.querySelectorAll('.sp-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100 * index);
        });
    });
</script>

<!-- Code Protection -->
<script src="<?= asset('js/code-protection.js') ?>"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>