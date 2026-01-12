<?php
ob_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/HolidayModeManager.php';

if (!isLoggedIn()) {
    redirect(url('auth'));
}

$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    redirect(url('auth'));
}

// Get active tab
$tab = $_GET['tab'] ?? 'dashboard';

// Get exchange rate and settings
$settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key = 'exchange_rate'");
$settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$exchange_rate = floatval($settings['exchange_rate'] ?? 24000);

$pageTitle = "Tài Khoản - " . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <?php
    // Load favicon helper
    require_once __DIR__ . '/../includes/favicon_helper.php';
    echo render_favicon_tags();
    ?>

    <link rel="stylesheet" href="<?= url('user/assets/css/user.css') ?>?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= asset('css/loading.css') ?>?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= asset('css/notify.css') ?>?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="<?= asset('js/loading.js') ?>?v=<?= time() ?>"></script>
    <script src="<?= asset('js/notify.js') ?>?v=<?= time() ?>"></script>
    <script>
        window.APP_CONFIG = {
            baseUrl: '<?= BASE_URL ?>',
            siteName: '<?= SITE_NAME ?>'
        };
    </script>
    <script src="<?= asset('js/theme-switcher.js') ?>?v=<?= time() ?>"></script>

    <!-- CRITICAL: Load collapsed state BEFORE page renders -->
    <script>
        (function () {
            if (window.innerWidth >= 1024 && localStorage.getItem('sidebar_collapsed') === 'true') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        })();
    </script>
    <style>
        /* Apply collapsed state instantly on HTML element */
        html.sidebar-collapsed body .user-sidebar {
            width: 80px !important;
        }

        html.sidebar-collapsed body .user-content {
            margin-left: 80px !important;
        }
    </style>
</head>


<body data-exchange-rate="<?= $exchange_rate ?>">
    <div class="user-layout">
        <!-- Sidebar -->
        <div class="user-sidebar" id="userSidebar">
            <div class="sidebar-header">
                <!-- Toggle Button -->
                <button class="sidebar-toggle-btn" id="sidebarToggle" title="Thu gọn/Mở rộng">
                    <i class="fas fa-bars"></i>
                    <i class="fas fa-times"></i>
                </button>

                <div class="user-avatar-section">
                    <?php
                    $frameImgSidebar = ($user['role'] === 'admin') ? 'khung_admin.webp' : 'khung_user.gif';
                    ?>
                    <div class="user-avatar-wrapper"
                        style="position: relative; width: 96px; height: 96px; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center;">
                        <img src="<?= getUserAvatar($user) ?>" alt="Avatar" class="user-avatar-img"
                            style="width: 70px; height: 70px; object-fit: cover; border-radius: 50%; z-index: 1;">
                        <img src="<?= asset('images/' . $frameImgSidebar) ?>" alt="Frame" class="user-avatar-frame"
                            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain; z-index: 2; pointer-events: none;">
                    </div>
                    <div class="user-info">
                        <h3><?= e($user['username']) ?></h3>
                        <span class="user-role <?= $user['role'] === 'admin' ? 'admin' : 'member' ?>">
                            <i class="fas fa-<?= $user['role'] === 'admin' ? 'crown' : 'star' ?>"></i>
                            <?= $user['role'] === 'admin' ? 'ADMIN' : 'MEMBER' ?>
                        </span>
                    </div>
                </div>
            </div>

            <nav class="sidebar-menu">
                <a href="?tab=dashboard" class="menu-item <?= $tab == 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>Tổng Quan</span>
                </a>
                <?php if (!HolidayModeManager::isActive()): ?>
                    <div id="themeToggle" class="menu-item" style="cursor: pointer;">
                        <img src="<?= asset('images/moon.png') ?>" alt="Theme"
                            style="width: 20px; height: 20px; object-fit: contain;">
                        <span>Chế Độ Tối</span>
                    </div>
                <?php endif; ?>

                <div id="currencyToggleSidebar" class="menu-item" style="cursor: pointer;">
                    <i class="fas fa-coins"></i>
                    <span>Tỷ Giá:
                        <?= (!isset($_COOKIE['currency']) || $_COOKIE['currency'] == 'VND') ? 'VND' : 'USD' ?></span>
                </div>

                <hr>
                <div class="menu-section">Quản Lý</div>

                <a href="?tab=orders" class="menu-item <?= $tab == 'orders' ? 'active' : '' ?>">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Sản phẩm</span>
                    <img src="https://media.giphy.com/media/KBlX7iF04rYrtuvSHc/giphy.gif" alt="Products"
                        style="width: 20px; height: 20px; object-fit: contain;">
                </a>

                <a href="?tab=transactions" class="menu-item <?= $tab == 'transactions' ? 'active' : '' ?>">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Giao Dịch</span>

                </a>

                <a href="?tab=deposit_history" class="menu-item <?= $tab == 'deposit_history' ? 'active' : '' ?>">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Lịch Sử Nạp Tiền</span>
                </a>

                <div class="menu-section">Hỗ Trợ</div>

                <a href="?tab=tickets" class="menu-item <?= $tab == 'tickets' ? 'active' : '' ?>">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Tickets Hỗ Trợ</span>
                </a>

                <a href="https://zalo.me/0812420710" target="_blank" class="menu-item">
                    <i class="fas fa-phone"></i>
                    <span>Liên hệ Zalo</span>
                </a>
                <a href="https://t.me/kaishop25" target="_blank" class="menu-item">
                    <i class="fab fa-telegram"></i>
                    <span>Nhóm Telegram</span>
                </a>



                <div class="menu-section">Cài Đặt</div>

                <a href="?tab=security" class="menu-item <?= $tab == 'security' ? 'active' : '' ?>">
                    <i class="fas fa-shield-alt"></i>
                    <span>Bảo Mật</span>
                </a>
                <a href="<?= url('chinhsach') ?>" class="menu-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>Chính Sách</span>
                </a>
                <a href="?tab=settings" class="menu-item <?= $tab == 'settings' ? 'active' : '' ?>">
                    <i class="fas fa-cog settings-spin"></i>
                    <span>Cài Đặt</span>
                </a>

                <div class="menu-section">Khác</div>

                <a href="<?= url('') ?>" class="menu-item">
                    <i class="fas fa-home"></i>
                    <span>Về Trang Chủ</span>
                </a>

                <?php if ($user['role'] === 'admin'): ?>
                    <a href="<?= url('admin') ?>" class="menu-item">
                        <i class="fas fa-crown"></i>
                        <span>Quản Trị</span>
                    </a>
                <?php endif; ?>

                <a href="<?= url('dangxuat.php') ?>" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Đăng Xuất</span>
                </a>
            </nav>
        </div>

        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Mobile Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Main Content -->
        <div class="user-content" id="userContent">
            <?php
            // Include tab content
            $tab_file = __DIR__ . "/tabs/{$tab}.php";
            if (file_exists($tab_file)) {
                include $tab_file;
            } else {
                include __DIR__ . '/tabs/dashboard.php';
            }
            ?>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const userSidebar = document.getElementById('userSidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function toggleMenu() {
            userSidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('active');
            document.body.classList.toggle('sidebar-open');
        }

        function closeMenu() {
            userSidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        }

        mobileMenuToggle.addEventListener('click', toggleMenu);
        sidebarOverlay.addEventListener('click', closeMenu);

        // Close sidebar when clicking a menu item on mobile
        const menuItems = document.querySelectorAll('.menu-item');
        menuItems.forEach(item => {
            item.addEventListener('click', () => {
                if (window.innerWidth <= 1024) {
                    closeMenu();
                }
            });
        });

        // Currency Toggle Sidebar
        const currencyBtn = document.getElementById('currencyToggleSidebar');
        if (currencyBtn) {
            currencyBtn.addEventListener('click', function () {
                const current = '<?= $_COOKIE['currency'] ?? 'VND' ?>';
                const next = current === 'VND' ? 'USD' : 'VND';
                document.cookie = `currency=${next}; path=/; max-age=2592000`;
                location.reload();
            });
        }

        // Desktop Sidebar Toggle - Button functionality only
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle && window.innerWidth >= 1024) {
            // Sync body class with HTML class if needed
            if (document.documentElement.classList.contains('sidebar-collapsed')) {
                document.body.classList.add('sidebar-collapsed');
            }

            sidebarToggle.addEventListener('click', () => {
                // Toggle on both elements
                document.documentElement.classList.toggle('sidebar-collapsed');
                document.body.classList.toggle('sidebar-collapsed');

                const collapsed = document.body.classList.contains('sidebar-collapsed');
                localStorage.setItem('sidebar_collapsed', collapsed);
            });
        }
    </script>

    <!-- GTranslate Widget -->
    <div class="gtranslate_wrapper"></div>
    <script>
        window.gtranslateSettings = {
            "default_language": "vi",
            "detect_browser_language": true,
            "languages": ["vi", "en", "ru", "th", "km", "lo", "id", "fr", "de", "ja", "pt", "ko"],
            "wrapper_selector": ".gtranslate_wrapper"
        }
    </script>
    <script src="https://cdn.gtranslate.net/widgets/latest/float.js" defer></script>
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

        /* Sidebar Toggle Button */
        .sidebar-toggle-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 36px;
            height: 36px;
            background: rgba(139, 92, 246, 0.15);
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 8px;
            color: var(--text-main);
            cursor: pointer;
            display: none;
            /* Hidden by default, show on desktop */
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 10;
            overflow: hidden;
        }

        .sidebar-toggle-btn i {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.2s ease;
            position: absolute;
        }

        .sidebar-toggle-btn i.fa-bars {
            opacity: 1;
            transform: rotate(0deg);
        }

        .sidebar-toggle-btn i.fa-times {
            opacity: 0;
            transform: rotate(90deg);
        }

        .sidebar-toggle-btn:hover {
            background: rgba(139, 92, 246, 0.25);
            border-color: rgba(139, 92, 246, 0.5);
            transform: scale(1.05);
        }

        .sidebar-toggle-btn:active {
            transform: scale(0.95);
        }

        /* Collapsed Sidebar State */
        @media (min-width: 1024px) {
            .sidebar-toggle-btn {
                display: flex !important;
            }

            body.sidebar-collapsed .user-sidebar {
                width: 80px !important;
            }

            body.sidebar-collapsed .user-content {
                margin-left: 80px !important;
            }

            /* Smoothly hide text content when collapsed */
            .user-sidebar .user-info h3,
            .user-sidebar .user-role,
            .user-sidebar .menu-section,
            .user-sidebar .menu-item span,
            .user-sidebar hr,
            .user-sidebar .user-avatar-wrapper,
            .user-sidebar .user-info {
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                opacity: 1;
                transform: translateX(0);
                max-height: 120px;
                /* Arbitrary large height */
                overflow: hidden;
            }

            body.sidebar-collapsed .user-sidebar .user-info h3,
            body.sidebar-collapsed .user-sidebar .user-role,
            body.sidebar-collapsed .user-sidebar .menu-section,
            body.sidebar-collapsed .user-sidebar .menu-item span,
            body.sidebar-collapsed .user-sidebar hr,
            body.sidebar-collapsed .user-sidebar .user-avatar-wrapper,
            body.sidebar-collapsed .user-sidebar .user-info {
                opacity: 0 !important;
                transform: translateX(-10px);
                max-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                /* pointer-events: none; */
                visibility: hidden;
                transition: opacity 0.2s ease, transform 0.2s ease, max-height 0.3s ease, margin 0.3s ease, padding 0.3s ease, visibility 0.3s;
            }

            /* Specific fix for the avatar to scale down */
            body.sidebar-collapsed .user-sidebar .user-avatar-wrapper {
                transform: scale(0);
                width: 0 !important;
                height: 0 !important;
                border: none;
            }

            /* Fix header spacing */
            body.sidebar-collapsed .user-sidebar .sidebar-header {
                margin-bottom: 0;
            }

            body.sidebar-collapsed .user-sidebar .menu-item span {
                width: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                opacity: 0 !important;
                display: none;
            }

            /* Center icons when collapsed - Strict centering */
            body.sidebar-collapsed .user-sidebar .menu-item {
                justify-content: center !important;
                padding: 12px 0 !important;
                width: auto !important;
                border-radius: 8px;
            }

            body.sidebar-collapsed .user-sidebar .menu-item i {
                margin: 0 !important;
                font-size: 1.3rem !important;
                flex-shrink: 0 !important;
                width: auto !important;
            }

            /* Active state adjustments for collapsed */
            body.sidebar-collapsed .user-sidebar .menu-item.active {
                border-left: none !important;
                background: linear-gradient(135deg, rgba(139, 92, 246, 0.25) 0%, rgba(168, 85, 247, 0.2) 100%) !important;
                color: #555df7ff !important;
                border: 1.5px solid rgba(139, 92, 246, 0.5) !important;
                border-radius: 10px !important;
                box-shadow: 0 0 20px rgba(139, 92, 246, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
            }

            body.sidebar-collapsed .user-sidebar .menu-item.active i {
                color: #555df7ff !important;
                filter: drop-shadow(0 0 8px rgba(168, 85, 247, 0.4));
            }

            /* Adjust header when collapsed */
            body.sidebar-collapsed .user-sidebar .sidebar-header {
                padding-bottom: 0 !important;
                margin-bottom: 26px !important;
                display: flex;
                justify-content: center;
            }

            body.sidebar-collapsed .user-sidebar .user-avatar-section {
                padding: 0 !important;
                margin: 0 !important;
                height: 0 !important;
                overflow: hidden !important;
            }

            /* Remove ANY conflicting avatar overriding */
            /* Remove ANY conflicting avatar overriding */
            body.sidebar-collapsed .user-sidebar .user-avatar-wrapper {
                width: 0 !important;
                height: 0 !important;
                padding: 0 !important;
                border: none !important;
                margin: 0 !important;
                transform: scale(0);
            }

            /* Move toggle button when collapsed */
            body.sidebar-collapsed .sidebar-toggle-btn {
                right: 50%;
                transform: translateX(50%);
            }

            body.sidebar-collapsed .sidebar-toggle-btn:hover {
                transform: translateX(50%) scale(1.05);
            }

            /* Animate icon change when collapsed */
            body.sidebar-collapsed .sidebar-toggle-btn i.fa-bars {
                opacity: 0;
                transform: rotate(-90deg);
            }

            body.sidebar-collapsed .sidebar-toggle-btn i.fa-times {
                opacity: 1;
                transform: rotate(0deg);
            }
        }

        /* Settings Gear Spin Animation */
        @keyframes settings-spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .settings-spin {
            animation: settings-spin 4s linear infinite;
        }

        /* Hide specific icon when collapsed to show GIF only */
        body.sidebar-collapsed .user-sidebar .menu-item[href="?tab=orders"] i.fa-shopping-bag {
            display: none !important;
        }
    </style>
</body>

</html>
<?php
ob_end_flush();
?>