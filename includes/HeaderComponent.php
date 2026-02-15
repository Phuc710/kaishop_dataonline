<?php

class HeaderComponent
{
    private $pdo;
    private $siteName;
    private $siteLogo;
    private $tabLogo;
    private $cartCount;
    private $currentUser;
    private $labels = [];

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->siteName = get_setting('site_name', SITE_NAME);
        $this->loadSettings();
        $this->loadCartCount();
        $this->loadCurrentUser();
        $this->loadLabels();
    }

    /**
     * Get exchange rate from database
     */
    private function getExchangeRate()
    {
        static $rate = null;

        if ($rate === null) {
            $rate = floatval(get_setting('exchange_rate', 25000));
        }

        return $rate;
    }

    private function loadSettings()
    {
        $this->siteLogo = get_setting('header_logo', 'images/kaishop.gif');
        $this->tabLogo = get_setting('tab_logo', 'images/kaishop.gif');
    }


    private function loadCartCount()
    {
        $this->cartCount = 0;
        if (isLoggedIn()) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $this->cartCount = $stmt->fetchColumn();
        }
    }

    private function loadCurrentUser()
    {
        $this->currentUser = getCurrentUser();
    }

    private function loadLabels()
    {
        try {
            // Fetch labels with image
            $stmt = $this->pdo->query("SELECT id, name, image_url FROM product_labels ORDER BY name ASC");
            $this->labels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->labels = [];
        }
    }

    public function render()
    {
        $currentCurrency = $_COOKIE['currency'] ?? 'VND';
        $vnFlagPath = BASE_PATH . '/assets/images/vn.png';
        $vnFlagExists = file_exists($vnFlagPath);
        ?>
        <!DOCTYPE html>
        <html lang="vi">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
            <?php if (defined('GOOGLE_TRANSLATE_NOTRANSLATE') && GOOGLE_TRANSLATE_NOTRANSLATE): ?>
                <meta name="google" content="notranslate">
            <?php endif; ?>
            <?php if (defined('GOOGLE_TRANSLATE_CUSTOMIZATION')): ?>
                <meta name="google-translate-customization" content="<?= GOOGLE_TRANSLATE_CUSTOMIZATION ?>">
            <?php endif; ?>
            <title><?= $GLOBALS['pageTitle'] ?? $this->siteName ?></title>
            <meta name="description"
                content="<?= $GLOBALS['pageDescription'] ?? 'KaiShop - Website bán tài khoản uy tín #1 Việt Nam, giao dịch tự động 24/7, bảo hành rõ ràng, hỗ trợ nhanh chóng.' ?>">
            <?php if (isset($GLOBALS['pageKeywords'])): ?>
                <meta name="keywords" content="<?= $GLOBALS['pageKeywords'] ?>">
            <?php endif; ?>

            <?php
            // Load favicon helper
            require_once __DIR__ . '/favicon_helper.php';
            require_once __DIR__ . '/HolidayModeManager.php';
            echo render_favicon_tags();
            ?>

            <!-- Google Font -->
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
                rel="stylesheet">

            <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
            <link rel="stylesheet" href="<?= asset('css/light-theme.css') ?>?v=<?= time() ?>">
            <link rel="stylesheet" href="<?= asset('css/light-mode-enhancements.css') ?>?v=<?= time() ?>">
            <link rel="stylesheet" href="<?= asset('css/notify.css') ?>">
            <link rel="stylesheet" href="<?= asset('css/loading.css') ?>">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <script src="<?= asset('js/theme-switcher.js') ?>"></script>
            <script>
                // Global configuration from .env
                window.APP_URL = '<?= BASE_URL ?>';
                window.API_URL = '<?= BASE_URL ?>/api';
                window.APP_CONFIG = {
                    baseUrl: '<?= BASE_URL ?>',
                    siteName: '<?= SITE_NAME ?>'
                };
            </script>
            <script src="<?= asset('js/loading.js') ?>?v=<?= time() ?>"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script src="<?= asset('js/notify.js') ?>?v=<?= time() ?>"></script>
            <script src="<?= asset('js/cart-helper.js') ?>?v=<?= time() ?>"></script>
            <?php $this->renderStyles(); ?>

            <!-- Open Graph Tags for Social Sharing -->
            <meta property="og:title" content="<?= $pageTitle ?? 'KaiShop - Mua Tài Khoản ChatGPT, Gemini, Canva Giá Rẻ' ?>">
            <meta property="og:description"
                content="<?= $pageDescription ?? 'Mua tài khoản ChatGPT Plus, Gemini Pro, Canva Pro giá rẻ. Giao dịch tự động 24/7!' ?>">
            <meta property="og:image" content="<?= BASE_URL ?>/assets/images/og-image.png">
            <meta property="og:url" content="<?= BASE_URL . $_SERVER['REQUEST_URI'] ?>">
            <meta property="og:type" content="website">
            <meta property="og:site_name" content="KaiShop">

            <!-- Twitter Card Tags -->
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="<?= $pageTitle ?? 'KaiShop - Mua Tài Khoản ChatGPT, Gemini, Canva Giá Rẻ' ?>">
            <meta name="twitter:description"
                content="<?= $pageDescription ?? 'Mua tài khoản ChatGPT Plus, Gemini Pro, Canva Pro giá rẻ. Giao dịch tự động 24/7!' ?>">
            <meta name="twitter:image" content="<?= BASE_URL ?>/assets/images/og-image.png">

            <!-- Holiday Mode Assets -->
            <?php if (HolidayModeManager::isActive()): ?>
                <?php
                $currentMode = HolidayModeManager::getCurrentMode();
                $cssFile = '';
                switch ($currentMode) {
                    case 'halloween':
                        $cssFile = 'halloween.css';
                        break;
                    case 'noel':
                        $cssFile = 'noel.css';
                        break;
                    case 'tet':
                        $cssFile = 'tet.css';
                        break;
                }
                if ($cssFile):
                    ?>
                    <link rel="stylesheet" href="<?= asset('css/' . $cssFile) ?>?v=<?= time() ?>">
                <?php endif; ?>
                <script src="<?= asset('js/holiday-effects.js') ?>?v=<?= time() ?>"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        if (typeof HolidayEffects !== 'undefined') {
                            HolidayEffects.init('<?= $currentMode ?>');
                        }
                    });
                </script>
            <?php endif; ?>
        </head>

        <body class="<?= HolidayModeManager::getBodyClass() ?>">
            <header class="kai-header">
                <nav class="kai-nav">
                    <!-- Mobile Menu Toggler -->
                    <button class="kai-mobile-menu-btn" id="mobileMenuBtn">
                        <i class="fas fa-bars"></i>
                    </button>

                    <!-- Logo Section -->
                    <a href="<?= url('') ?>" class="kai-logo">
                        <img src="<?= asset($this->siteLogo) ?>" alt="<?= $this->siteName ?>" class="kai-logo-img">
                    </a>

                    <!-- Desktop Menu -->
                    <ul class="kai-menu desktop-only">
                        <li><a href="<?= url('') ?>" class="kai-link">Trang Chủ</a></li>
                        <li class="kai-dropdown-trigger"
                            style="position: relative; height: 100%; display: flex; align-items: center;">
                            <a href="<?= url('sanpham') ?>" class="kai-link"
                                style="display: flex; align-items: center; gap: 5px;">
                                Sản Phẩm <img src="https://media.giphy.com/media/KBlX7iF04rYrtuvSHc/giphy.gif" alt="Products"
                                    style="width: 20px; height: 20px; object-fit: contain;">
                            </a>
                        </li>
                        <li><a href="<?= url('naptien') ?>" class="kai-link">Nạp Tiền</a></li>
                        <?php if (isLoggedIn()): ?>
                            <li class="kai-history-menu"
                                style="position: relative; height: 100%; display: flex; align-items: center;">
                                <a href="javascript:void(0)" class="kai-link"
                                    style="display:flex; align-items:center; gap:6px; cursor:default;">
                                    Lịch Sử <i class="fas fa-chevron-down kai-dropdown-icon" style="font-size: 12px;"></i>
                                </a>
                                <div class="kai-dropdown">
                                    <a href="<?= url('user?tab=deposit_history') ?>" class="kai-dropdown-item">
                                        <i class="fas fa-wallet"></i> Nạp Tiền
                                    </a>
                                    <a href="<?= url('user?tab=transactions') ?>" class="kai-dropdown-item">
                                        <i class="fas fa-exchange-alt"></i> Biến Động Số Dư
                                    </a>
                                    <a href="<?= url('user?tab=orders') ?>" class="kai-dropdown-item">
                                        <i class="fas fa-shopping-cart"></i> Đơn Hàng
                                    </a>
                                </div>
                            </li>
                        <?php endif; ?>
                    </ul>

                    <!-- Actions End -->
                    <ul class="kai-actions">
                        <!-- Theme Toggle -->
                        <?php if (!HolidayModeManager::isActive()): ?>
                            <li class="desktop-only">
                                <button class="kai-btn kai-btn-icon" onclick="toggleTheme()" aria-label="Toggle Theme">
                                    <img id="theme-icon" src="<?= asset('images/moon.png') ?>" alt="Theme"
                                        style="width: 20px; height: 20px; object-fit: contain;">
                                </button>
                            </li>
                        <?php endif; ?>

                        <!-- Currency Switcher -->
                        <li class="desktop-only">
                            <button id="currencySwitcher" class="kai-btn kai-btn-icon kai-currency-btn notranslate"
                                title="Chuyển tiền tệ">
                                <?php if ($currentCurrency === 'VND'): ?>
                                    <?php if ($vnFlagExists): ?>
                                        <img id="currencyFlag" src="<?= asset('images/vn.png') ?>" alt="VND">
                                    <?php else: ?>
                                        <span id="currencyFlag" class="currency-flag">🇻🇳</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span id="currencyFlag" class="currency-flag">🌍</span>
                                <?php endif; ?>
                                <span id="currencyText"><?= $currentCurrency ?></span>
                            </button>
                        </li>

                        <?php if (isLoggedIn()): ?>
                            <!-- Cart -->
                            <li>
                                <a href="<?= url('giohang') ?>" class="kai-btn kai-btn-icon kai-cart-btn">
                                    <i class="fas fa-shopping-cart"></i>
                                    <?php if ($this->cartCount > 0): ?>
                                        <span class="kai-badge"><?= $this->cartCount ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>

                            <!-- User Menu -->
                            <li class="kai-user-menu">
                                <button class="kai-btn kai-btn-user">
                                    <?php
                                    $userIcon = getUserAvatar($this->currentUser);
                                    $userRole = $this->currentUser['role'] ?? 'user';
                                    $frameImg = ($userRole === 'admin') ? 'khung_admin.webp' : 'khung_user.gif';
                                    ?>
                                    <div class="kai-avatar-wrapper">
                                        <img src="<?= $userIcon ?>" alt="User" class="kai-user-icon">
                                        <img src="<?= asset('images/' . $frameImg) ?>" alt="Frame" class="kai-avatar-frame">
                                    </div>
                                    <div class="kai-user-info desktop-only">
                                        <span class="kai-username"><?= e($this->currentUser['username'] ?? 'User') ?></span>
                                        <span class="kai-wallet">
                                            <i class="fas fa-wallet"></i>
                                            <?php
                                            $currency = $_COOKIE['currency'] ?? 'VND';
                                            $balanceVND = $this->currentUser['balance_vnd'] ?? 0;
                                            if ($currency === 'USD'):
                                                $exchangeRate = $this->getExchangeRate();
                                                $balanceUSD = $balanceVND / $exchangeRate;
                                                ?>
                                                $<?= number_format($balanceUSD, 2) ?>
                                            <?php else: ?>
                                                <?= number_format($balanceVND) ?>đ
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <i class="fas fa-chevron-down kai-dropdown-icon"></i>
                                </button>
                                <div class="kai-dropdown">
                                    <?php if ($this->currentUser['role'] === 'admin'): ?>
                                        <a href="<?= url('admin/index.php') ?>" class="kai-dropdown-item">
                                            <i class="fas fa-user-shield"></i> Quản Trị
                                        </a>
                                        <div class="kai-dropdown-divider"></div>
                                    <?php endif; ?>
                                    <a href="<?= url('user') ?>" class="kai-dropdown-item">
                                        <i class="fas fa-user"></i> Tài Khoản
                                    </a>
                                    <a href="<?= url('user?tab=settings') ?>" class="kai-dropdown-item">
                                        <i class="fas fa-cog"></i> Cài Đặt
                                    </a>
                                    <div class="kai-dropdown-divider"></div>
                                    <a href="<?= url('dangxuat.php') ?>" class="kai-dropdown-item kai-logout">
                                        Đăng Xuất
                                    </a>
                                </div>
                            </li>
                            <!-- Notifications -->
                            <li>
                                <?php
                                // Include notification bell
                                require_once __DIR__ . '/NotificationBell.php';
                                $notificationBell = new NotificationBell($this->pdo, $this->currentUser['id'] ?? null);
                                echo $notificationBell->render();
                                ?>
                            </li>
                        <?php else: ?>
                            <li class="desktop-only"><a href="<?= url('auth') ?>" class="kai-btn kai-btn-primary"> Đăng Nhập👻</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>

                <!-- Mobile Drawer -->
                <div class="kai-mobile-drawer" id="mobileDrawer">
                    <div class="drawer-header">
                        <div class="drawer-header-brand">
                            <img src="<?= asset($this->siteLogo) ?>" alt="<?= $this->siteName ?>" class="drawer-logo">
                        </div>
                        <button class="drawer-close" id="drawerClose"><i class="fas fa-times"></i></button>
                    </div>

                    <div class="drawer-content">
                        <?php if (isLoggedIn()): ?>
                            <!-- User Profile Card -->
                            <div class="drawer-profile-card">
                                <div class="drawer-profile-main">
                                    <div class="drawer-avatar-wrapper">
                                        <?php
                                        $userRoleId = $this->currentUser['role'] ?? 'user';
                                        $frameImgId = ($userRoleId === 'admin') ? 'khung_admin.webp' : 'khung_user.gif';
                                        ?>
                                        <img src="<?= getUserAvatar($this->currentUser) ?>" alt="Avatar" class="drawer-avatar">
                                        <img src="<?= asset('images/' . $frameImgId) ?>" alt="Frame" class="drawer-avatar-frame">
                                    </div>
                                    <div class="drawer-user-details">
                                        <h4 class="drawer-username"><?= e($this->currentUser['username']) ?></h4>
                                        <div class="drawer-user-badge"><?= ucfirst($userRoleId) ?></div>
                                    </div>
                                </div>
                                <div class="drawer-balance-card">
                                    <span class="balance-label">Số dư hiện tại</span>
                                    <div class="balance-amount">
                                        <i class="fas fa-wallet"></i>
                                        <?php
                                        if ($currentCurrency === 'USD'):
                                            $exchangeRate = $this->getExchangeRate();
                                            $balanceUSD = ($this->currentUser['balance_vnd'] ?? 0) / $exchangeRate;
                                            ?>
                                            $<?= number_format($balanceUSD, 2) ?>
                                        <?php else: ?>
                                            <?= number_format($this->currentUser['balance_vnd'] ?? 0) ?>đ
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Menu Sections -->
                        <div class="drawer-sections">
                            <!-- Navigation Section -->
                            <div class="drawer-section">
                                <span class="section-label">Điều hướng</span>
                                <ul class="drawer-menu">
                                    <li><a href="<?= url('') ?>"><i class="fas fa-home"></i> Trang Chủ</a></li>
                                    <li><a href="<?= url('sanpham') ?>"><i class="fas fa-shopping-bag"></i> Sản Phẩm</a></li>
                                    <li><a href="<?= url('naptien') ?>"><i class="fas fa-wallet"></i> Nạp Tiền</a></li>
                                </ul>
                            </div>

                            <?php if (isLoggedIn()): ?>
                                <!-- Account Section -->
                                <div class="drawer-section">
                                    <span class="section-label">Tài khoản</span>
                                    <ul class="drawer-menu">
                                        <?php if ($this->currentUser['role'] === 'admin'): ?>
                                            <li><a href="<?= url('admin/index.php') ?>" class="admin-link"><i
                                                        class="fas fa-user-shield"></i> Quản Trị</a></li>
                                        <?php endif; ?>
                                        <li><a href="<?= url('user') ?>"><i class="fas fa-user-circle"></i> Trang cá nhân</a></li>
                                        <li><a href="<?= url('user?tab=settings') ?>"><i class="fas fa-cog"></i> Cài đặt</a></li>
                                    </ul>
                                </div>

                                <!-- History Section -->
                                <div class="drawer-section">
                                    <span class="section-label">Lịch sử giao dịch</span>
                                    <ul class="drawer-menu">
                                        <li><a href="<?= url('user?tab=deposit_history') ?>"><i class="fas fa-history"></i> Lịch Sử
                                                Nạp</a></li>
                                        <li><a href="<?= url('user?tab=transactions') ?>"><i class="fas fa-exchange-alt"></i> Biến
                                                Động Số Dư</a></li>
                                        <li><a href="<?= url('user?tab=orders') ?>"><i class="fas fa-shopping-cart"></i> Đơn
                                                Hàng</a></li>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <!-- Quick Actions -->
                            <div class="drawer-section">
                                <span class="section-label">Tiện ích nhanh</span>
                                <div class="drawer-quick-actions">
                                    <?php if (!HolidayModeManager::isActive()): ?>
                                        <button onclick="toggleTheme()" class="quick-action-btn theme-toggle">
                                            <div class="btn-content">
                                                <img id="mobile-theme-icon" src="<?= asset('images/moon.png') ?>" alt="Theme">
                                                <span id="mobile-theme-text">Tối</span>
                                            </div>
                                        </button>
                                    <?php endif; ?>

                                    <button id="mobileCurrencySwitcher" class="quick-action-btn currency-switcher notranslate">
                                        <div class="btn-content">
                                            <?php if ($currentCurrency === 'VND'): ?>
                                                <?php if ($vnFlagExists): ?>
                                                    <img src="<?= asset('images/vn.png') ?>" alt="VND">
                                                <?php else: ?>
                                                    <span class="currency-flag">🇻🇳</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="currency-flag">🌍</span>
                                            <?php endif; ?>
                                            <span><?= $currentCurrency ?></span>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Logout / Auth -->
                            <div class="drawer-auth-section">
                                <?php if (isLoggedIn()): ?>
                                    <a href="<?= url('dangxuat.php') ?>" class="drawer-logout-btn">
                                        <i class="fas fa-sign-out-alt"></i> Đăng Xuất
                                    </a>
                                <?php else: ?>
                                    <a href="<?= url('auth') ?>" class="drawer-login-btn">
                                        Đăng Nhập <i class="fas fa-ghost"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="kai-mobile-overlay" id="mobileOverlay"></div>
            </header>

            <!-- GTranslate Widget -->
            <?php if (defined('GTRANSLATE_ENABLED') && GTRANSLATE_ENABLED): ?>
                <div
                    class="<?= defined('GTRANSLATE_WRAPPER_SELECTOR') ? ltrim(GTRANSLATE_WRAPPER_SELECTOR, '.') : 'gtranslate_wrapper' ?>">
                </div>
                <script>
                    window.gtranslateSettings = {
                        "default_language": "<?= defined('GTRANSLATE_DEFAULT_LANGUAGE') ? GTRANSLATE_DEFAULT_LANGUAGE : 'vi' ?>",
                        "detect_browser_language": <?= defined('GTRANSLATE_DETECT_BROWSER') && GTRANSLATE_DETECT_BROWSER ? 'true' : 'false' ?>,
                        "languages": <?= json_encode(defined('GTRANSLATE_LANGUAGES') ? explode(',', GTRANSLATE_LANGUAGES) : ['vi', 'en']) ?>,
                        "wrapper_selector": "<?= defined('GTRANSLATE_WRAPPER_SELECTOR') ? GTRANSLATE_WRAPPER_SELECTOR : '.gtranslate_wrapper' ?>"
                    }
                </script>
                <script
                    src="<?= defined('GTRANSLATE_CDN_URL') ? GTRANSLATE_CDN_URL : 'https://cdn.gtranslate.net/widgets/latest/float.js' ?>"
                    defer></script>
            <?php endif; ?>
            <style>
                /* Hide Google Translate Toolbar & Tooltip */
                .goog-te-banner-frame.skiptranslate {
                    display: none !important;
                }

                body {
                    top: 0px !important;
                }

                .goog-tooltip {
                    display: none !important;
                }

                .goog-tooltip-hover {
                    display: none !important;
                }

                .goog-text-highlight {
                    background-color: transparent !important;
                    box-shadow: none !important;
                }

                /* FORCE GTranslate to bottom-right */
                .gtranslate_wrapper,
                .gt_float_switcher {
                    position: fixed !important;
                    right: 20px !important;
                    left: auto !important;
                    bottom: 20px !important;
                    top: auto !important;
                    z-index: 999999 !important;
                }

                /* Override GTranslate Default Styles */
                .gt_float_switcher {
                    font-family: inherit !important;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
                    border-radius: 30px !important;
                    overflow: hidden !important;
                    padding: 0 !important;
                    background: #ffffff !important;
                    color: #0f172a !important;
                    border: 1px solid rgba(0, 0, 0, 0.05) !important;
                    transition: all 0.3s ease !important;
                    transform: scale(0.85) !important;
                    transform-origin: bottom right !important;
                }

                .gt_float_switcher:hover {
                    transform: scale(0.9) !important;
                    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
                }

                .gt_float_switcher .gt_selected {
                    background: transparent !important;
                    color: #0f172a !important;
                    padding: 8px 12px !important;
                    display: flex !important;
                    align-items: center !important;
                    gap: 6px !important;
                    font-weight: 600 !important;
                    font-size: 13px !important;
                }

                .gt_float_switcher img {
                    width: 20px !important;
                    height: 20px !important;
                    border-radius: 50% !important;
                    margin: 0 !important;
                    box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.05);
                }

                /* Dropdown list styling */
                .gt_float_switcher .gt_options {
                    background: #ffffff !important;
                    border-radius: 12px !important;
                    bottom: 115% !important;
                    width: 140px !important;
                    padding: 8px !important;
                    border: 1px solid rgba(0, 0, 0, 0.55) !important;
                    max-height: 300px !important;
                    overflow-y: auto !important;
                    color: #0f172a !important;
                }

                .gt_float_switcher .gt_options a {
                    color: #334155 !important;
                    padding: 8px 12px !important;
                    font-size: 13px !important;
                    border-radius: 8px !important;
                    transition: all 0.2s !important;
                    margin-bottom: 2px !important;
                    display: flex !important;
                    align-items: center !important;
                    gap: 8px !important;
                    text-decoration: none !important;
                }

                .gt_float_switcher .gt_options a:hover {
                    background: #f1f5f9 !important;
                    color: #0f172a !important;
                }

                /* Scrollbar */
                .gt_float_switcher .gt_options::-webkit-scrollbar {
                    width: 4px;
                }

                .gt_float_switcher .gt_options::-webkit-scrollbar-track {
                    background: transparent;
                }

                .gt_float_switcher .gt_options::-webkit-scrollbar-thumb {
                    background: #334155;
                    border-radius: 4px;
                }

                .gt_float_switcher .gt_options::-webkit-scrollbar-thumb:hover {
                    background: #1e293b;
                }
            </style>

            <?php $this->renderFlashMessages(); ?>
            <?php $this->renderScripts($vnFlagExists); ?>
            <?php
    }

    private function renderStyles()
    {
        ?>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                /* ============================================
                           Custom Cursor Styles
                           ============================================ */

                /* Default cursor for entire page */
                * {
                    cursor: url('<?= BASE_URL ?>/assets/images/cursor/default.cur'), auto;
                }

                /* Pointer cursor for interactive elements */
                a,
                button,
                input[type="button"],
                input[type="submit"],
                input[type="reset"],
                input[type="checkbox"],
                input[type="radio"],
                select,
                label,
                .btn,
                .kai-btn,
                .kai-link,
                .clickable,
                [role="button"],
                [onclick] {
                    cursor: url('<?= BASE_URL ?>/assets/images/cursor/link.cur'), pointer !important;
                }

                /* Text cursor for text inputs */
                input[type="text"],
                input[type="email"],
                input[type="password"],
                input[type="search"],
                input[type="tel"],
                input[type="url"],
                input[type="number"],
                textarea,
                [contenteditable="true"] {
                    cursor: url('<?= BASE_URL ?>/assets/images/cursor/Text-Select.cur'), text !important;
                }

                body {
                    font-family: "Poppins", -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: #0a0e27;
                    background-size: cover;
                    background-attachment: fixed;
                    background-position: center;
                    min-height: 100vh;
                    color: #f9fafb;
                }

                .kai-header {
                    position: sticky;
                    top: 0;
                    z-index: 1000;
                    background: rgba(2, 6, 23, 0.95);
                    backdrop-filter: blur(16px);
                    border-bottom: 1px solid rgba(139, 92, 246, 0.15);
                    box-shadow: none;
                }

                .kai-menu.desktop-only {
                    margin-left: 3rem;
                }

                .kai-nav {
                    max-width: 1400px;
                    margin: 0 auto;
                    padding: 1rem 2.5rem;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    gap: 1rem;
                }

                /* Logo */
                .kai-logo {
                    display: flex;
                    align-items: center;
                    text-decoration: none;
                    z-index: 10;
                    flex-shrink: 0;
                    position: relative;
                }

                .kai-logo-img {
                    position: absolute;
                    height: 110px;
                    width: auto;
                    min-width: 100px;
                    object-fit: contain;
                    transition: transform 0.3s ease;
                }


                /* Menu */
                .kai-menu {
                    display: flex;
                    list-style: none;
                    gap: 2rem;
                    align-items: center;
                    margin: 0;
                    flex: 1;
                    justify-content: center;
                }

                .kai-link {
                    color: #cbd5e1;
                    text-decoration: none;
                    font-weight: 500;
                    font-size: 15px;
                    position: relative;
                    padding: 0.5rem 0;
                    transition: color 0.3s ease;
                }

                .kai-link::before {
                    content: '';
                    position: absolute;
                    bottom: 0;
                    left: 50%;
                    transform: translateX(-50%) scaleX(0);
                    width: 100%;
                    height: 2px;
                    border-radius: 999px;
                    background: linear-gradient(90deg, #0606d4ff, #0890b2ff);
                    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                }

                .kai-link:hover {
                    color: #ffffff;
                }

                .kai-link:hover::before {
                    transform: translateX(-50%) scaleX(1);
                }

                /* Actions */
                .kai-actions {
                    display: flex;
                    list-style: none;
                    gap: 0.75rem;
                    align-items: center;
                    z-index: 2;
                }

                .kai-btn {
                    padding: 0.5rem 1rem;
                    border-radius: 12px;
                    text-decoration: none;
                    font-weight: 600;
                    font-size: 14px;
                    transition: background 0.2s ease, transform 0.2s ease;
                    /* Specific transitions */
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 0.5rem;
                    border: none;
                    cursor: pointer;
                    position: relative;
                    height: 40px;
                    white-space: nowrap;
                }

                .kai-btn-primary {
                    background: linear-gradient(135deg, #0606d4ff 0%, #0890b2ff 100%);
                    color: white;
                    font-weight: 600;
                    letter-spacing: 0.3px;
                    font-size: 14px;
                    padding: 0.6rem 1.2rem;
                    overflow: hidden;
                    border-radius: 8px;
                }

                .kai-btn-primary i {
                    transition: transform 0.3s ease;
                }

                .kai-btn-primary:hover {
                    background: linear-gradient(135deg, #0505b8ff 0%, #0678a0ff 100%);
                    transform: translateY(-1px);
                }

                .kai-btn-primary:hover i {
                    transform: translateX(4px);
                }



                .kai-btn-secondary {
                    background: rgba(139, 92, 246, 0.15);
                    color: #a78bfa;
                }

                .kai-btn-secondary:hover {
                    background: rgba(139, 92, 246, 0.25);
                    border-color: rgba(139, 92, 246, 0.5);
                }

                .kai-btn-icon {
                    background: rgba(139, 92, 246, 0.1);
                    padding: 0 1rem;
                    border: none;
                    color: #cbd5e1;
                }

                .kai-btn-icon:hover {
                    background: rgba(139, 92, 246, 0.2);
                    border-color: rgba(139, 92, 246, 0.4);
                }

                .kai-currency-btn img {
                    width: 20px;
                    height: 20px;
                    object-fit: contain;
                    border-radius: 4px;
                }

                .currency-flag {
                    font-size: 1.2rem;
                    line-height: 1;
                }

                /* Cart Button */
                .kai-cart-btn {
                    position: relative;
                }

                .kai-badge {
                    position: absolute;
                    top: -5px;
                    right: -5px;
                    background: linear-gradient(135deg, #ef4444, #dc2626);
                    color: white;
                    border-radius: 50%;
                    width: 18px;
                    height: 18px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 10px;
                    font-weight: 700;
                }

                /* User Menu */
                .kai-user-menu {
                    position: relative;
                }

                .kai-btn-user {
                    background: rgba(124, 58, 237, 0.2);
                    border: none;
                    color: #e2e8f0;
                    padding: 0 0.8rem;
                }

                .kai-btn-user:hover {
                    background: rgba(124, 58, 237, 0.3);
                    border-color: rgba(124, 58, 237, 0.6);
                }

                .kai-avatar-wrapper {
                    position: relative;
                    width: 40px;
                    height: 40px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-right: 4px;
                }

                .kai-user-icon {
                    width: 26px;
                    height: 26px;
                    border-radius: 50%;
                    object-fit: cover;
                    z-index: 1;
                    position: relative;
                }

                .kai-avatar-frame {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    object-fit: contain;
                    z-index: 2;
                    pointer-events: none;
                }

                .kai-user-info {
                    display: flex;
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 0px;
                    margin-left: 8px;
                }

                .kai-username {
                    max-width: 100px;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                    font-size: 13px;
                    line-height: 1.2;
                }

                .kai-wallet {
                    font-size: 11px;
                    color: #10b981;
                    font-weight: 700;
                    display: flex;
                    align-items: center;
                    gap: 4px;
                }

                /* Dropdown Menu */
                .kai-dropdown {
                    position: absolute;
                    top: 100%;
                    right: 0;
                    min-width: 200px;
                    background: rgba(15, 23, 42, 0.98);
                    border: none;
                    border-radius: 12px;
                    padding: 0.5rem 0;
                    opacity: 0;
                    visibility: hidden;
                    transform: translateY(10px);
                    transition: all 0.25s ease;
                    z-index: 1000;
                    margin-top: 0.5rem;
                    backdrop-filter: blur(20px);
                }

                .kai-user-menu.active .kai-dropdown {
                    opacity: 1;
                    visibility: visible;
                    transform: translateY(0);
                }

                .kai-dropdown-item {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 0.75rem 1.2rem;
                    color: #cbd5e1;
                    text-decoration: none;
                    font-size: 14px;
                    font-weight: 500;
                    transition: all 0.2s ease;
                }

                .kai-dropdown-item:hover {
                    background: rgba(139, 92, 246, 0.15);
                    color: #f8fafc;
                }

                .kai-dropdown-item i {
                    width: 18px;
                    font-size: 14px;
                    color: #a78bfa;
                }

                .kai-dropdown-divider {
                    height: 1px;
                    background: rgba(148, 163, 184, 0.15);
                    margin: 0.4rem 0;
                }

                .kai-logout {
                    color: #ef4444 !important;
                }

                .kai-logout:hover {
                    background: rgba(239, 68, 68, 0.15);
                    color: #f87171 !important;
                }

                /* Dropdown Icon */
                .kai-dropdown-icon {
                    font-size: 12px;
                    color: #ffffffff;
                    margin-left: 6px;
                    transition: transform 0.3s ease;
                }

                .kai-user-menu.active .kai-dropdown-icon {
                    transform: rotate(180deg);
                }

                /* History Dropdown Styling */
                .kai-history-menu .kai-dropdown {
                    min-width: 220px;
                    left: 50%;
                    right: auto;
                    top: 100%;
                    transform: translateX(-50%) translateY(10px);
                    padding-top: 0.5rem;
                    /* Add some padding so mouse doesn't leave when moving down */
                    margin-top: 0;
                    /* Align perfectly */
                }

                /* Hover Effect */
                .kai-history-menu:hover .kai-dropdown {
                    opacity: 1;
                    visibility: visible;
                    transform: translateX(-50%) translateY(0);
                }

                .kai-history-menu:hover .kai-dropdown-icon {
                    transform: rotate(180deg);
                }

                .kai-history-menu:hover .kai-link {
                    color: #ffffff;
                }

                .kai-history-menu:hover .kai-link::before {
                    transform: translateX(-50%) scaleX(1);
                }

                /* Generic Dropdown Trigger Hover Effect (For Products etc) */
                .kai-dropdown-trigger .kai-dropdown {
                    min-width: 220px;
                    left: 50%;
                    right: auto;
                    top: 100%;
                    transform: translateX(-50%) translateY(10px);
                    padding-top: 0.5rem;
                    margin-top: 0;
                }

                .kai-dropdown-trigger:hover .kai-dropdown {
                    opacity: 1;
                    visibility: visible;
                    transform: translateX(-50%) translateY(0);
                }

                .kai-dropdown-trigger:hover .kai-dropdown-icon {
                    transform: rotate(180deg);
                }

                /* Mobile Specific */
                .kai-mobile-menu-btn {
                    display: none;
                    background: rgba(139, 92, 246, 0.15);
                    border: 1px solid rgba(139, 92, 246, 0.5);
                    border-radius: 10px;
                    color: #ffffffff;
                    font-size: 1.3rem;
                    cursor: pointer;
                    padding: 0.5rem 0.7rem;
                    z-index: 2000;
                    transition: all 0.3s ease;
                }

                .kai-mobile-menu-btn:hover {
                    background: rgba(139, 92, 246, 0.25);
                    border-color: rgba(139, 92, 246, 0.5);
                }

                .mobile-only {
                    display: none;
                }

                /* Mobile Drawer Premium Refactor */
                .kai-mobile-drawer {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 320px;
                    height: 100vh;
                    background: rgba(2, 6, 23, 0.85);
                    backdrop-filter: blur(25px) saturate(180%);
                    -webkit-backdrop-filter: blur(25px) saturate(180%);
                    z-index: 9000;
                    transform: translateX(-100%);
                    transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
                    border-right: 1px solid rgba(255, 255, 255, 0.1);
                    display: flex;
                    flex-direction: column;
                    box-shadow: 20px 0 50px rgba(0, 0, 0, 0.3);
                }

                .kai-mobile-drawer.active {
                    transform: translateX(0);
                }

                .drawer-header {
                    padding: 24px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
                }

                .drawer-header-brand {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }

                .drawer-logo {

                    position: absolute;
                    left: 90px;
                    top: 7%;
                    transform: translateY(-50%);
                    width: 100px;
                    height: auto;
                    filter: drop-shadow(0 0 8px rgba(139, 92, 246, 0.3));
                }


                .drawer-close {
                    width: 40px;
                    height: 40px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: rgba(255, 255, 255, 0.05);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    border-radius: 99px;
                    color: white;
                    transition: all 0.3s ease;
                }

                .drawer-close:active {
                    transform: scale(0.9);
                    background: rgba(255, 255, 255, 0.1);
                }

                .drawer-content {
                    padding: 24px;
                    flex: 1;
                    overflow-y: auto;
                    scrollbar-width: none;
                }

                .drawer-content::-webkit-scrollbar {
                    display: none;
                }

                /* Profile Card */
                .drawer-profile-card {
                    background: linear-gradient(135deg, rgb(87 87 87 / 20%), rgba(236, 72, 153, 0.1));
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    border-radius: 24px;
                    padding: 20px;
                    margin-bottom: 24px;
                    position: relative;
                    overflow: hidden;
                }

                .drawer-profile-card::before {
                    content: '';
                    position: absolute;
                    top: -50%;
                    left: -50%;
                    width: 200%;
                    height: 200%;
                    background: radial-gradient(circle, rgba(255, 255, 255, 0.05) 0%, transparent 70%);
                    pointer-events: none;
                }

                .drawer-profile-main {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    margin-bottom: 18px;
                }

                .drawer-avatar-wrapper {
                    position: relative;
                    width: 56px;
                    height: 56px;
                }

                .drawer-avatar {
                    width: 100%;
                    height: 100%;
                    border-radius: 99px;
                    object-fit: cover;
                    border: 2px solid rgba(255, 255, 255, 0.2);
                }

                .drawer-avatar-frame {
                    position: absolute;
                    inset: -5px;
                    width: calc(100% + 10px);
                    height: calc(100% + 10px);
                    pointer-events: none;
                }

                .drawer-username {
                    font-size: 1.1rem;
                    font-weight: 700;
                    color: white;
                    margin: 0;
                }

                .drawer-user-badge {
                    display: inline-block;
                    font-size: 10px;
                    font-weight: 700;
                    text-transform: uppercase;
                    padding: 2px 8px;
                    background: rgb(255 255 255 / 86%);
                    border-radius: 99px;
                    color: #000000;
                    margin-top: 4px;
                }

                .drawer-balance-card {
                    background: rgba(0, 0, 0, 0.2);
                    border-radius: 16px;
                    padding: 12px 16px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .balance-label {
                    font-size: 12px;
                    color: #94a3b8;
                }

                .balance-amount {
                    font-size: 1.1rem;
                    font-weight: 800;
                    color: #10b981;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                }

                /* Menu Sections */
                .drawer-sections {
                    display: flex;
                    flex-direction: column;
                    gap: 24px;
                }

                .section-label {
                    display: inline-block;
                    color: #ffc300;
                    font-weight: 700;
                    font-size: 0.75rem;
                    letter-spacing: 2px;
                    text-transform: uppercase;
                    margin-bottom: 12px;
                }

                .drawer-menu {
                    list-style: none;
                    display: flex;
                    flex-direction: column;
                    gap: 6px;
                }

                .drawer-menu a {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 14px 16px;
                    color: #cbd5e1;
                    text-decoration: none;
                    font-weight: 600;
                    font-size: 14px;
                    border-radius: 99px;
                    transition: all 0.3s ease;
                    background: rgba(255, 255, 255, 0.02);
                }

                .drawer-menu a i {
                    font-size: 16px;
                    width: 20px;
                    text-align: center;
                    color: #a78bfa;
                    transition: transform 0.3s ease;
                }

                .drawer-menu a:active {
                    background: rgba(139, 92, 246, 0.15);
                    color: white;
                    transform: scale(0.98);
                }

                .drawer-menu a:active i {
                    transform: scale(1.2);
                }

                .admin-link {
                    color: #fbbf24 !important;
                    background: rgba(251, 191, 36, 0.05) !important;
                }


                .quick-action-btn {
                    background: rgba(255, 255, 255, 0.05);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    border-radius: 99px;
                    padding: 12px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    width: 100%;
                }

                .quick-action-btn:active {
                    background: rgba(255, 255, 255, 0.1);
                    transform: scale(0.95);
                }

                .btn-content {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    color: white;
                    font-weight: 600;
                    font-size: 13px;
                }

                .quick-action-btn img {
                    width: 20px;
                    height: 20px;
                    object-fit: contain;
                }

                /* Auth Section */
                .drawer-auth-section {
                    margin-top: 10px;
                    padding-bottom: 40px;
                }

                .drawer-logout-btn {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    width: 100%;
                    padding: 16px;
                    background: rgba(239, 68, 68, 0.1);
                    border: 1px solid rgba(239, 68, 68, 0.2);
                    border-radius: 99px;
                    color: #ef4444;
                    font-weight: 700;
                    text-decoration: none;
                    transition: all 0.3s ease;
                }

                .drawer-logout-btn:active {
                    background: rgba(239, 68, 68, 0.2);
                    transform: scale(0.98);
                }

                .drawer-login-btn {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 10px;
                    width: 100%;
                    padding: 16px;
                    background: linear-gradient(135deg, #0606d4, #0890b2);
                    border-radius: 99px;
                    color: white;
                    font-weight: 700;
                    text-decoration: none;
                    box-shadow: 0 4px 15px rgba(6, 6, 212, 0.3);
                }

                .drawer-login-btn i {
                    font-size: 1.1rem;
                }

                /* Mobile Overlay */
                .kai-mobile-overlay {
                    position: fixed;
                    inset: 0;
                    z-index: 8900;
                    opacity: 0;
                    visibility: hidden;
                    transition: all 0.4s ease;
                    background: rgba(0, 0, 0, 0.4);
                    backdrop-filter: blur(4px);
                }

                .kai-mobile-overlay.active {
                    opacity: 1;
                    visibility: visible;
                }

                /* Responsive Media Queries */
                @media (max-width: 968px) {
                    .desktop-only {
                        display: none !important;
                    }

                    .mobile-only {
                        display: flex !important;
                        margin-left: auto;
                    }

                    .kai-mobile-menu-btn {
                        display: block;
                    }

                    .kai-nav {
                        padding: 0.8rem 1rem;
                        justify-content: space-between;
                    }

                    /* Center Logo on mobile */
                    .kai-logo {
                        position: absolute;
                        left: 50%;
                        transform: translateX(-50%);
                        z-index: 10;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }

                    .kai-logo-img {
                        height: 100px;
                        min-width: 50px;
                        width: auto;
                        position: static;
                        margin: 0;
                    }

                    .kai-actions {
                        gap: 10px;
                    }

                    /* Hide user menu and notification on mobile - move to drawer */
                    .kai-user-menu,
                    .kai-actions>li:has(.kai-notification-bell) {
                        display: none !important;
                    }

                    /* Only show cart on mobile */
                    .kai-cart-btn {
                        padding: 0;
                        width: 40px;
                        height: 40px;
                        justify-content: center;
                        background: rgba(139, 92, 246, 0.1);
                        border-radius: 99px;
                    }

                    /* Holiday Mode Overrides for Drawer Close */
                    body.holiday-mode-noel .drawer-close,
                    body.holiday-mode-tet .drawer-close,
                    body.holiday-mode-halloween .drawer-close {
                        color: #ffffff !important;
                        background: rgb(0 0 0 / 15%) !important;
                        border-color: rgba(255, 255, 255, 0.3) !important;
                    }
            </style>
            <?php
    }

    private function renderFlashMessages()
    {
        if (hasFlash()) {
            $flash = getFlash();
            ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        notify.<?= $flash['type'] === 'success' ? 'success' : 'error' ?>(
                            '<?= $flash['type'] === 'success' ? 'Thành công!' : 'Lỗi!' ?>',
                            '<?= addslashes($flash['message']) ?>'
                        );
                    });
                </script>
                <?php
        }
    }

    private function renderScripts($vnFlagExists)
    {
        ?>
            <script>
                // Currency Switcher Logic (Shared)
                function switchCurrency() {
                    const currentText = document.getElementById('currencyText') || document.getElementById('mobileCurrencySwitcher');
                    const isVND = document.cookie.includes('currency=VND') || (!document.cookie.includes('currency=USD'));
                    const newCurrency = isVND ? 'USD' : 'VND';

                    document.cookie = `currency=${newCurrency}; path=/; max-age=31536000`;

                    if (window.notify) {
                        location.reload();
                    }
                }

                const currencySwitcher = document.getElementById('currencySwitcher');
                if (currencySwitcher) currencySwitcher.addEventListener('click', switchCurrency);

                const mobileCurrencySwitcher = document.getElementById('mobileCurrencySwitcher');
                if (mobileCurrencySwitcher) mobileCurrencySwitcher.addEventListener('click', switchCurrency);

                // Toggle user dropdown on click
                const userMenuBtn = document.querySelector('.kai-btn-user');
                const userMenu = document.querySelector('.kai-user-menu');

                if (userMenuBtn && userMenu) {
                    userMenuBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        userMenu.classList.toggle('active');
                    });

                    // Close dropdown when clicking outside
                    document.addEventListener('click', function (e) {
                        if (!userMenu.contains(e.target)) {
                            userMenu.classList.remove('active');
                        }
                    });
                }

                // History Menu Logic
                const historyMenuBtn = document.querySelector('.kai-history-menu button');
                const historyMenu = document.querySelector('.kai-history-menu');

                if (historyMenuBtn && historyMenu) {
                    historyMenuBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        historyMenu.classList.toggle('active');
                        // Close user menu if open
                        if (userMenu) userMenu.classList.remove('active');
                    });

                    // Close dropdown when clicking outside
                    document.addEventListener('click', function (e) {
                        if (!historyMenu.contains(e.target)) {
                            historyMenu.classList.remove('active');
                        }
                    });
                }

                // Update User Menu click to close History Menu
                if (userMenuBtn && userMenu) {
                    userMenuBtn.addEventListener('click', function (e) {
                        if (historyMenu) historyMenu.classList.remove('active');
                    });
                }

                // Mobile Menu Logic
                const mobileMenuBtn = document.getElementById('mobileMenuBtn');
                const mobileDrawer = document.getElementById('mobileDrawer');
                const mobileOverlay = document.getElementById('mobileOverlay');
                const drawerClose = document.getElementById('drawerClose');

                function toggleDrawer() {
                    mobileDrawer.classList.toggle('active');
                    mobileOverlay.classList.toggle('active');
                    document.body.style.overflow = mobileDrawer.classList.contains('active') ? 'hidden' : '';
                }

                if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', toggleDrawer);
                if (drawerClose) drawerClose.addEventListener('click', toggleDrawer);
                if (mobileOverlay) mobileOverlay.addEventListener('click', toggleDrawer);

                // Theme logic is handled by theme-switcher.js

            </script>
            <?php
    }
}

