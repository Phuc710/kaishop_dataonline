<?php
// Start output buffering để cho phép header() redirect
ob_start();

require_once __DIR__ . '/../config/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(url('403'));
}

// Get active tab
$tab = $_GET['tab'] ?? 'dashboard';

// Get exchange rate from DB
$settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key = 'exchange_rate'");
$settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$exchange_rate = floatval($settings['exchange_rate'] ?? 24000);

// Get unread/open tickets count for notification badge
$ticket_count = intval($pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'open'")->fetchColumn());

// Get pending orders count for badge
$order_count = intval($pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn());

$pageTitle = "Admin - " . SITE_NAME;
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

    <link rel="stylesheet" href="<?= url('admin/assets/css/admin.css') ?>?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= url('admin/assets/css/mobile-responsive.css') ?>?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= url('admin/assets/css/table-scroll.css') ?>?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= url('admin/assets/css/admin-scrollbar.css') ?>?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= asset('css/loading.css') ?>?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= asset('css/notify.css') ?>?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        // Global config from .env
        window.APP_URL = '<?= BASE_URL ?>';
        window.API_URL = '<?= BASE_URL ?>/api';
        window.APP_CONFIG = {
            baseUrl: '<?= BASE_URL ?>',
            siteName: '<?= SITE_NAME ?>'
        };
    </script>
    <script src="<?= asset('js/loading.js') ?>?v=<?= time() ?>"></script>
    <script src="<?= asset('js/notify.js') ?>?v=<?= time() ?>"></script>
    <script src="<?= asset('js/AdminModal.js') ?>?v=<?= time() ?>"></script>
    <script src="<?= asset('js/admin-notifications.js') ?>?v=<?= time() ?>"></script>
    <script src="<?= asset('js/admin-functions.js') ?>?v=<?= time() ?>"></script>
    <script src="<?= asset('js/admin-ticket-notifications.js') ?>?v=<?= time() ?>"></script>

    <!-- CRITICAL: Load collapsed state IMMEDIATELY before render -->
    <script>
        (function () {
            if (window.innerWidth >= 1024 && localStorage.getItem('admin_sidebar_collapsed') === 'true') {
                document.documentElement.classList.add('admin-sidebar-collapsed');
            }
        })();
    </script>

    <style>
        a.dropdown-header:hover {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6 !important;
        }

        /* Apply collapsed state instantly on HTML element */
        html.admin-sidebar-collapsed body .admin-sidebar {
            width: 70px !important;
        }

        html.admin-sidebar-collapsed body .admin-content {
            margin-left: 70px !important;
        }
    </style>
</head>

<body data-exchange-rate="<?= $exchange_rate ?>">
    <div class="admin-layout">
        <!-- Mobile Menu Toggle -->
        <button id="mobile-menu-toggle" class="mobile-menu-toggle">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="sidebar-header">
                <!-- Toggle Button -->
                <button class="admin-sidebar-toggle-btn" id="adminSidebarToggle" title="Thu gọn/Mở rộng">
                    <i class="fas fa-bars"></i>
                    <i class="fas fa-times"></i>
                </button>

                <?php
                // Get current user info from session or DB
                $current_user_id = $_SESSION['user_id'] ?? 0;
                $current_user_stmt = $pdo->prepare("SELECT username, avatar FROM users WHERE id = ?");
                $current_user_stmt->execute([$current_user_id]);
                $current_user = $current_user_stmt->fetch();

                $display_name = $current_user['username'] ?? 'Admin';
                // Remove @gmail.com or anything after @
                $display_name = explode('@', $display_name)[0];

                $user_avatar = !empty($current_user['avatar']) ? $current_user['avatar'] : 'assets/images/default_avatar.png';
                // Fix avatar path if needed (e.g., if it's external or relative)
                if (!filter_var($user_avatar, FILTER_VALIDATE_URL) && strpos($user_avatar, 'http') !== 0) {
                    $user_avatar = asset($user_avatar);
                }
                ?>
            </div>

            <nav class="sidebar-menu">
                <a href="?tab=dashboard" class="menu-item <?= $tab == 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>

                <div class="menu-section"> - Quản Lý - </div>

                <!-- Products Dropdown -->
                <div class="menu-dropdown <?= $tab == 'products' ? 'active' : '' ?>">
                    <a href="#" class="menu-item" onclick="return false;">
                        <i class="fas fa-box"></i>
                        <span>Sản Phẩm</span>
                        <img src="https://media.giphy.com/media/KBlX7iF04rYrtuvSHc/giphy.gif" alt="Products"
                            style="width: 20px; height: 20px; object-fit: contain;">
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </a>
                    <div class="dropdown-content">
                        <a href="?tab=products" class="dropdown-item">
                            <i class="fas fa-list"></i> Danh Sách Tất Cả
                        </a>
                        <a href="?tab=categories" class="dropdown-item">
                            <i class="fas fa-layer-group"></i> Quản Lý Danh Mục
                        </a>
                        <a href="?tab=vouchers" class="dropdown-item">
                            <i class="fas fa-ticket-alt"></i> Voucher
                        </a>
                        <a href="?tab=product_add" class="dropdown-item">
                            <i class="fas fa-plus"></i> Thêm Sản Phẩm Mới
                        </a>
                        <?php
                        // Get categories with products
                        $categories_with_products = $pdo->query("
                        SELECT c.id, c.name, c.icon_type, c.icon_value, COUNT(p.id) as product_count 
                        FROM categories c 
                        LEFT JOIN products p ON c.id = p.category_id 
                        GROUP BY c.id, c.name, c.icon_type, c.icon_value 
                        HAVING product_count > 0
                        ORDER BY c.name
                    ")->fetchAll();
                        if (!empty($categories_with_products)):
                            ?>
                            <div class="dropdown-divider"></div>
                            <a href="?tab=categories" class="dropdown-header"
                                style="cursor:pointer;transition:all 0.3s;display:block;padding:0.5rem 1rem;color:#64748b;font-size:0.75rem;font-weight:700;text-transform:uppercase;text-decoration:none;">
                                <i class="fas fa-layer-group"></i> DANH MỤC (EDIT) (<?= count($categories_with_products) ?>)
                            </a>
                            <?php foreach ($categories_with_products as $cat):
                                $iconValue = $cat['icon_value'] ?? '📦';
                                $iconType = $cat['icon_type'] ?? 'emoji';
                                ?>
                                <div class="category-submenu">
                                    <a href="#" class="dropdown-item category-toggle" onclick="return false;"
                                        data-category="<?= $cat['id'] ?>">
                                        +
                                        <?php if ($iconType === 'image'): ?>
                                            <img src="<?= asset('images/uploads/' . $iconValue) ?>"
                                                style="width:20px;height:20px;border-radius:4px;margin-right:0.5rem">
                                        <?php else: ?>
                                            <span style="font-size:1.2rem;margin-right:0.5rem"><?= $iconValue ?></span>
                                        <?php endif; ?>

                                        <?= e($cat['name']) ?>
                                        <span style="color:#64748b;font-size:0.85rem">(<?= $cat['product_count'] ?>)</span>
                                    </a>

                                    <div class="product-submenu" id="category-products-<?= $cat['id'] ?>">
                                        <?php
                                        $products_in_cat = $pdo->prepare("SELECT id, name FROM products WHERE category_id = ? ORDER BY created_at DESC LIMIT 10");
                                        $products_in_cat->execute([$cat['id']]);
                                        $products = $products_in_cat->fetchAll();
                                        foreach ($products as $prod):
                                            ?>
                                            <a href="?tab=product_manage&product_id=<?= $prod['id'] ?>" class="product-item">
                                                <i class="fas fa-tag"></i>
                                                <?= e(mb_substr($prod['name'], 0, 25)) ?>
                                                <?= mb_strlen($prod['name']) > 25 ? '...' : '' ?>
                                            </a>
                                        <?php endforeach; ?>
                                        <?php if (count($products) >= 10): ?>
                                            <a href="?tab=products&category=<?= $cat['id'] ?>" class="product-item"
                                                style="color:#8b5cf6;font-weight:600">
                                                <i class="fas fa-ellipsis-h"></i> Xem tất cả
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Notifications Dropdown with Subtabs -->
                <div class="menu-dropdown <?= $tab == 'notifications' ? 'active' : '' ?>">
                    <a href="#" class="menu-item" onclick="return false;">
                        <i class="fas fa-bullhorn"></i>
                        <span>Thông Báo</span>
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </a>
                    <div class="dropdown-content">
                        <a href="?tab=notifications&subtab=banner" class="dropdown-item">
                            <i class="fas fa-stream"></i> Banner Chữ Chạy
                        </a>
                        <a href="?tab=notifications&subtab=popup" class="dropdown-item">
                            <i class="fas fa-window-restore"></i> Popup Thông Báo
                        </a>
                        <a href="?tab=notifications&subtab=notices" class="dropdown-item">
                            <i class="fas fa-exclamation-circle"></i> Lưu Ý
                        </a>
                        <a href="?tab=notifications&subtab=tickets" class="dropdown-item">
                            <i class="fas fa-headset"></i> Tickets Hỗ Trợ
                            <span class="ticket-count-badge"
                                style="background:<?= $ticket_count > 0 ? '#ef4444' : 'rgba(139, 92, 246, 0.2)' ?>;color:#fff;display:inline-block;padding:0.1rem 0.2rem;border-radius:12px;font-size:0.75rem;font-weight:700;min-width:20px;text-align:center;margin-left:0.5rem"><?= $ticket_count ?></span>
                        </a>
                    </div>
                </div>

                <a href="?tab=users" class="menu-item <?= $tab == 'users' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    <span>Người Dùng</span>
                </a>


                <div class="menu-section">- Báo Cáo - </div>

                <a href="?tab=finances" class="menu-item <?= $tab == 'finances' ? 'active' : '' ?>">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Doanh Thu</span>
                    <img src="https://media.giphy.com/media/kEhKBVTIMz6c10g3Lz/giphy.gif" alt="Money"
                        style="width: 20px; height: 20px; object-fit: contain;">
                </a>
                <a href="?tab=orders" class="menu-item <?= $tab == 'orders' ? 'active' : '' ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Đơn Hàng</span>
                    <span id="pending-orders-badge"
                        style="background:red;color:white;display:<?= $order_count > 0 ? 'inline-block' : 'none' ?>;padding:0.2rem 0.6rem;border-radius:12px;font-size:0.75rem;font-weight:700;min-width:20px;text-align:center;margin-left:auto"><?= $order_count ?></span>
                </a>

                <div class="menu-section"> - Hệ Thống - </div>
                <!-- History Dropdown with Subtabs -->
                <div
                    class="menu-dropdown <?= in_array($tab, ['user_balance_history', 'user_activity_logs', 'deposit_history']) ? 'active' : '' ?>">
                    <a href="#" class="menu-item" onclick="return false;">
                        <i class="fas fa-history"></i>
                        <span>Lịch Sử</span>
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </a>
                    <div class="dropdown-content">
                        <a href="?tab=deposit_history" class="dropdown-item">
                            <i class="fas fa-money-bill-wave"></i> Lịch Sử Nạp Tiền
                        </a>
                        <a href="?tab=user_balance_history" class="dropdown-item">
                            <i class="fas fa-wallet"></i> Biến Động Số Dư
                        </a>
                        <a href="?tab=user_activity_logs" class="dropdown-item">
                            <i class="fas fa-clipboard-list"></i> Nhật Ký Hoạt Động
                        </a>
                        <a href="?tab=password_reset_logs" class="dropdown-item">
                            <i class="fas fa-shield-alt"></i> Reset Password
                        </a>
                    </div>
                </div>

                <a href="?tab=security" class="menu-item <?= $tab == 'security' ? 'active' : '' ?>">
                    <i class="fas fa-shield-alt"></i>
                    <span>Security</span>
                </a>

                <a href="?tab=settings" class="menu-item <?= $tab == 'settings' ? 'active' : '' ?>">
                    <i class="fas fa-cog settings-spin"></i>
                    <span>Cài Đặt</span>
                </a>

                <div class="menu-section">- Khác -</div>

                <a href="<?= url('') ?>" class="menu-item" target="_blank">
                    <i class="fas fa-home"></i>
                    <span>Về Trang Chủ</span>
                </a>

                <a href="<?= url('dangxuat.php') ?>" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Đăng Xuất</span>
                </a>
            </nav>

            <script>
                // Restore sidebar state from localStorage
                function restoreSidebarState() {
                    const openMenus = JSON.parse(localStorage.getItem('sidebarOpenMenus') || '[]');
                    const openCategories = JSON.parse(localStorage.getItem('sidebarOpenCategories') || '[]');
                    const scrollPos = parseInt(localStorage.getItem('sidebarScrollPos') || '0');

                    // Restore dropdown menus
                    openMenus.forEach(index => {
                        const menu = document.querySelectorAll('.menu-dropdown')[index];
                        if (menu) menu.classList.add('open');
                    });

                    // Restore category submenus
                    openCategories.forEach(categoryId => {
                        const category = document.getElementById('category-products-' + categoryId);
                        if (category) {
                            category.parentElement.classList.add('open');
                        }
                    });

                    // Restore scroll position
                    const sidebar = document.querySelector('.admin-sidebar');
                    if (sidebar && scrollPos > 0) {
                        sidebar.scrollTop = scrollPos;
                    }
                }

                // Save sidebar state to localStorage
                function saveSidebarState() {
                    const openMenus = [];
                    const openCategories = [];

                    // Save dropdown menus state
                    document.querySelectorAll('.menu-dropdown').forEach((menu, index) => {
                        if (menu.classList.contains('open')) {
                            openMenus.push(index);
                        }
                    });

                    // Save category submenus state
                    document.querySelectorAll('.category-submenu.open').forEach(cat => {
                        const categoryId = cat.querySelector('[data-category]')?.getAttribute('data-category');
                        if (categoryId) openCategories.push(categoryId);
                    });

                    // Save scroll position
                    const sidebar = document.querySelector('.admin-sidebar');
                    if (sidebar) {
                        localStorage.setItem('sidebarScrollPos', sidebar.scrollTop.toString());
                    }

                    localStorage.setItem('sidebarOpenMenus', JSON.stringify(openMenus));
                    localStorage.setItem('sidebarOpenCategories', JSON.stringify(openCategories));
                }

                // Dropdown toggle
                document.querySelectorAll('.menu-dropdown > .menu-item').forEach((item, index) => {
                    item.addEventListener('click', (e) => {
                        // Only toggle dropdown, don't navigate
                        if (!e.target.closest('.dropdown-content')) {
                            e.preventDefault();
                            e.stopPropagation();
                            item.parentElement.classList.toggle('open');
                            saveSidebarState();
                            return false;
                        }
                    }, true);
                });

                // Category submenu toggle
                document.addEventListener('click', (e) => {
                    const categoryToggle = e.target.closest('.category-toggle');
                    if (categoryToggle) {
                        e.preventDefault();
                        e.stopPropagation();
                        const parent = categoryToggle.closest('.category-submenu');

                        // Close other open category submenus
                        document.querySelectorAll('.category-submenu.open').forEach(item => {
                            if (item !== parent) item.classList.remove('open');
                        });

                        parent.classList.toggle('open');
                        saveSidebarState();
                    }
                });

                // Save scroll position when scrolling
                const sidebar = document.querySelector('.admin-sidebar');
                if (sidebar) {
                    let scrollTimer;
                    sidebar.addEventListener('scroll', () => {
                        clearTimeout(scrollTimer);
                        scrollTimer = setTimeout(() => {
                            localStorage.setItem('sidebarScrollPos', sidebar.scrollTop.toString());
                        }, 100);
                    });
                }

                // Restore state on page load
                restoreSidebarState();
            </script>
        </div>

        <!-- Main Content -->
        <div class="admin-content" id="admin-content">
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
        // Update pending orders badge
        function updatePendingBadge() {
            fetch('<?= url('admin/api/get-pending-count.php') ?>?v=' + new Date().getTime())
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('pending-orders-badge');
                    if (badge) {
                        if (data.success && data.count > 0) {
                            badge.textContent = data.count;
                            badge.style.display = 'inline-block';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(error => console.error('Error fetching pending count:', error));
        }

        // Update every 30 seconds
        setInterval(updatePendingBadge, 30000);
        // Update on visibility change
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                updatePendingBadge();
            }
        });

        console.log('Admin panel loaded');

        // Mobile Menu Toggle
        const mobileToggle = document.getElementById('mobile-menu-toggle');
        const adminSidebar = document.querySelector('.admin-sidebar');

        if (mobileToggle && adminSidebar) {
            mobileToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                adminSidebar.classList.toggle('mobile-open');
            });

            // Close sidebar when clicking outside
            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 768 &&
                    adminSidebar.classList.contains('mobile-open') &&
                    !adminSidebar.contains(e.target) &&
                    e.target !== mobileToggle &&
                    !mobileToggle.contains(e.target)) {
                    adminSidebar.classList.remove('mobile-open');
                }
            });
        }

        // Desktop Sidebar Toggle - Button functionality only
        const adminToggle = document.getElementById('adminSidebarToggle');
        if (adminToggle && window.innerWidth >= 1024) {
            // Sync body class with HTML class if needed
            if (document.documentElement.classList.contains('admin-sidebar-collapsed')) {
                document.body.classList.add('admin-sidebar-collapsed');
            }

            adminToggle.addEventListener('click', () => {
                // Toggle on both elements
                document.documentElement.classList.toggle('admin-sidebar-collapsed');
                document.body.classList.toggle('admin-sidebar-collapsed');

                const collapsed = document.body.classList.contains('admin-sidebar-collapsed');
                localStorage.setItem('admin_sidebar_collapsed', collapsed);
            });
        }
    </script>

    <style>
        .sidebar-menu {
            margin-top: 10px;
        }

        /* Admin Sidebar Toggle Button */
        .admin-sidebar-toggle-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 36px;
            height: 36px;
            background: rgba(6, 182, 212, 0.15);
            border: 1px solid rgba(6, 182, 212, 0.3);
            border-radius: 8px;
            color: var(--text-primary);
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 10;
            overflow: hidden;
        }

        .admin-sidebar-toggle-btn i {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.2s ease;
            position: absolute;
        }

        .admin-sidebar-toggle-btn i.fa-bars {
            opacity: 1;
            transform: rotate(0deg);
        }

        .admin-sidebar-toggle-btn i.fa-times {
            opacity: 0;
            transform: rotate(90deg);
        }

        .admin-sidebar-toggle-btn:hover {
            background: rgba(6, 182, 212, 0.25);
            border-color: rgba(6, 182, 212, 0.5);
            transform: scale(1.05);
        }

        .admin-sidebar-toggle-btn:active {
            transform: scale(0.95);
        }

        /* Collapsed Admin Sidebar State */
        @media (min-width: 1024px) {
            .admin-sidebar-toggle-btn {
                display: flex !important;
            }

            body.admin-sidebar-collapsed .admin-sidebar {
                width: 70px !important;
            }

            body.admin-sidebar-collapsed .admin-content {
                margin-left: 70px !important;
            }

            .menu-section {
                color: #8b5cf6;
                font-size: 13px;
                font-weight: bolder;

            }

            /* Hide text content when collapsed */
            body.admin-sidebar-collapsed .admin-sidebar .sidebar-header h2,
            body.admin-sidebar-collapsed .admin-sidebar .admin-badge,
            body.admin-sidebar-collapsed .admin-sidebar .user-profile-header,
            body.admin-sidebar-collapsed .admin-sidebar .menu-section,
            body.admin-sidebar-collapsed .admin-sidebar .menu-item span,
            body.admin-sidebar-collapsed .admin-sidebar .dropdown-icon,
            body.admin-sidebar-collapsed .admin-sidebar #pending-orders-badge,
            body.admin-sidebar-collapsed .admin-sidebar .ticket-count-badge,
            body.admin-sidebar-collapsed .admin-sidebar .dropdown-content {
                display: none !important;
            }

            /* Center icons when collapsed */
            body.admin-sidebar-collapsed .admin-sidebar .menu-item {
                justify-content: center;
                padding: 12px 0;
            }

            body.admin-sidebar-collapsed .admin-sidebar .menu-item i {
                margin: 0;
                font-size: 1.3rem;
            }

            /* Adjust header when collapsed */
            body.admin-sidebar-collapsed .admin-sidebar .sidebar-header {
                padding: 1rem 0.5rem;
            }

            /* Move toggle button when collapsed */
            body.admin-sidebar-collapsed .admin-sidebar-toggle-btn {
                right: 50%;
                transform: translateX(50%);
            }

            /* Animate icon change when collapsed */
            body.admin-sidebar-collapsed .admin-sidebar-toggle-btn i.fa-bars {
                opacity: 0;
                transform: rotate(-90deg);
            }

            body.admin-sidebar-collapsed .admin-sidebar-toggle-btn i.fa-times {
                opacity: 1;
                transform: rotate(0deg);
            }
        }

        /* Category Submenu Rotation */
        .category-submenu.open .dropdown-icon {
            transform: rotate(180deg);
        }
    </style>
</body>

</html>
<?php
// Flush output buffer
ob_end_flush();
?>