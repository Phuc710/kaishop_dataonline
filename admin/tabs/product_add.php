<?php
/**
 * Product Add - Router
 * Routes to: product_account, product_source, product_book
 */

$product_type = $_GET['type'] ?? 'account';

// Validate type
if (!in_array($product_type, ['account', 'source', 'book'])) {
    $product_type = 'account';
}
?>

<link rel="stylesheet" href="../assets/css/product_add.css">

<div class="product-type-selector">
    <div class="selector-header">
        <h2 class="title-gradient">
            <i class="fas fa-plus-circle"></i>
            Thêm Sản Phẩm Mới
        </h2>
        <p class="subtitle">
            Chọn loại sản phẩm bên dưới để bắt đầu thiết lập thông tin
        </p>
    </div>

    <div class="type-nav">
        <a href="?tab=product_add&type=account" class="type-card <?= $product_type === 'account' ? 'active' : '' ?>">
            <div class="type-glow"></div>
            <div class="type-icon">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="type-info">
                <h3>Tài Khoản</h3>
            </div>
        </a>

        <a href="?tab=product_add&type=source" class="type-card <?= $product_type === 'source' ? 'active' : '' ?>">
            <div class="type-glow"></div>
            <div class="type-icon">
                <i class="fas fa-code"></i>
            </div>
            <div class="type-info">
                <h3>Source Code</h3>
            </div>
        </a>

        <a href="?tab=product_add&type=book" class="type-card <?= $product_type === 'book' ? 'active' : '' ?>">
            <div class="type-glow"></div>
            <div class="type-icon">
                <i class="fas fa-book"></i>
            </div>
            <div class="type-info">
                <h3>Sách / Tài liệu</h3>
            </div>
        </a>
    </div>
</div>

<div class="product-form-container">
    <?php
    // Include the appropriate product type file
    switch ($product_type) {
        case 'account':
            include __DIR__ . '/product/product_account.php';
            break;
        case 'source':
            include __DIR__ . '/product/product_source.php';
            break;
        case 'book':
            include __DIR__ . '/product/product_book.php';
            break;
    }
    ?>
</div>

<style>
    .product-type-selector {
        background: rgba(15, 23, 42, 0.4);
        border: 1px solid rgba(148, 163, 184, 0.1);
        border-radius: 32px;
        padding: 4rem 2rem;
        margin-bottom: 3rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }

    /* Background accent glow */
    .product-type-selector::before {
        content: '';
        position: absolute;
        top: -100px;
        right: -100px;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(139, 92, 246, 0.15), transparent 70%);
        z-index: 0;
    }

    .selector-header {
        text-align: center;
        margin-bottom: 4rem;
        position: relative;
        z-index: 1;
    }

    .title-gradient {
        font-family: 'Outfit', sans-serif;
        font-size: 2.2rem !important;
        font-weight: 800;
        margin: 0 !important;
        background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }

    .title-gradient i {
        background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        filter: drop-shadow(0 0 8px rgba(245, 158, 11, 0.3));
    }

    .selector-header .subtitle {
        color: #94a3b8 !important;
        font-size: 1.1rem !important;
        margin-top: 0.75rem !important;
        font-weight: 400;
    }

    .type-nav {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
        max-width: 1100px;
        margin: 0 auto;
        position: relative;
        z-index: 1;
    }

    .type-card {
        background: rgba(30, 41, 59, 0.3);
        border: 1px solid rgba(148, 163, 184, 0.1);
        border-radius: 28px;
        padding: 2.5rem 1.5rem;
        text-decoration: none;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 1.5rem;
        position: relative;
        overflow: hidden;
    }

    .type-glow {
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at top right, rgba(139, 92, 246, 0.12), transparent 60%);
        opacity: 0;
        transition: opacity 0.4s ease;
    }


    .type-icon {
        width: 80px;
        height: 80px;
        background: rgba(15, 23, 42, 0.5);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: #94a3b8;
        border: 1px solid rgba(148, 163, 184, 0.1);
        transition: all 0.4s ease;
    }

    .type-card:hover .type-icon {
        background: rgba(139, 92, 246, 0.15);
        border-color: rgba(139, 92, 246, 0.3);
        color: #a78bfa;
        transform: scale(1.1) rotate(5deg);
    }

    /* Active State */
    .type-card.active {
        background: rgba(139, 92, 246, 0.08);
        border-color: #8b5cf6;
        box-shadow: 0 0 30px rgba(139, 92, 246, 0.15);
    }

    .type-card.active .type-icon {
        background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
        color: #fff;
        border: none;
        box-shadow: 0 10px 20px rgba(139, 92, 246, 0.4);
    }

    .type-info h3 {
        color: #f8fafc;
        font-size: 1.4rem;
        font-weight: 700;
        margin-bottom: 0.4rem;
    }

    .type-info span {
        color: #64748b;
        font-size: 0.9rem;
        font-weight: 400;
    }

    .type-card.active .type-info h3 {
        color: #fff;
    }

    .type-card.active .type-info span {
        color: #a78bfa;
        font-weight: 500;
    }

    /* Form Container Animation */
    .product-form-container {
        animation: slideUpFade 0.7s cubic-bezier(0.22, 1, 0.36, 1);
    }

    @keyframes slideUpFade {
        from {
            opacity: 0;
            transform: translateY(40px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive Design */
    @media (max-width: 992px) {
        .type-nav {
            grid-template-columns: 1fr;
            max-width: 500px;
        }

        .type-card {
            flex-direction: row;
            text-align: left;
            padding: 1.5rem;
            gap: 1.5rem;
        }

        .type-icon {
            width: 60px;
            height: 60px;
            font-size: 1.8rem;
            flex-shrink: 0;
        }
    }
</style>