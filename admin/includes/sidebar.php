<div class="admin-sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-crown"></i> <?= SITE_NAME ?></h2>
        <span class="admin-badge">ADMIN</span>
    </div>

    <nav class="sidebar-menu">
        <a href="<?= url('admin/?tab=dashboard') ?>"
            class="menu-item <?= strpos($_SERVER['PHP_SELF'], 'index.php') !== false || strpos($_SERVER['PHP_SELF'], 'dashboard') !== false ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i>
            <span>Dashboard</span>
        </a>

        <div class="menu-section">Quản Lý</div>

        <a href="<?= url('admin/?tab=products') ?>"
            class="menu-item <?= strpos($_SERVER['PHP_SELF'], 'products') !== false ? 'active' : '' ?>">
            <i class="fas fa-box"></i>
            <span>Sản Phẩm</span>
        </a>

        <a href="<?= url('admin/?tab=categories') ?>"
            class="menu-item <?= isset($_GET['tab']) && $_GET['tab'] === 'categories' ? 'active' : '' ?>">
            <i class="fas fa-layer-group"></i>
            <span>Danh Mục</span>
        </a>

        <a href="<?= url('admin/?tab=users') ?>" class="menu-item">
            <i class="fas fa-users"></i>
            <span>Người Dùng</span>
        </a>

        <a href="<?= url('admin/?tab=orders') ?>" class="menu-item">
            <i class="fas fa-shopping-cart"></i>
            <span>Đơn Hàng</span>
            <span class="badge-pending" id="pendingOrdersBadge" style="display:none"></span>
        </a>

        <a href="<?= url('admin/?tab=notifications') ?>" class="menu-item">
            <i class="fas fa-bullhorn"></i>
            <span>Thông Báo</span>
        </a>

        <a href="<?= url('admin/?tab=settings') ?>" class="menu-item">
            <i class="fas fa-cog"></i>
            <span>Cài Đặt</span>
        </a>

        <div class="menu-section">Khác</div>

        <a href="<?= url('') ?>" class="menu-item" target="_blank">
            <i class="fas fa-home"></i>
            <span>Về Trang Chủ</span>
        </a>

        <a href="<?= url('auth/logout') ?>" class="menu-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Đăng Xuất</span>
        </a>
    </nav>
</div>

<style>
    .admin-layout {
        display: flex;
        min-height: 100vh
    }

    .admin-sidebar {
        width: 260px;
        background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        border-right: 1px solid rgba(139, 92, 246, 0.2);
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        overflow-y: auto
    }

    .sidebar-header {
        padding: 1.5rem;
        border-bottom: 1px solid rgba(139, 92, 246, 0.2)
    }

    .sidebar-header h2 {
        color: #f8fafc;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem
    }

    .admin-badge {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 700
    }

    .sidebar-menu {
        padding: 1rem 0
    }

    .menu-section {
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 0.75rem 1.5rem;
        letter-spacing: 0.05em
    }

    .menu-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1.5rem;
        color: #94a3b8;
        text-decoration: none;
        transition: all 0.3s;
        border-left: 3px solid transparent;
        position: relative
    }

    .menu-item:hover {
        color: #f8fafc
    }

    .menu-item.active {
        background: rgba(139, 92, 246, 0.2);
        color: #f8fafc;
        border-left-color: #8b5cf6
    }

    .menu-item i {
        width: 20px;
        font-size: 1.1rem
    }

    .badge-pending {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        padding: 0.2rem 0.5rem;
        border-radius: 10px;
        font-size: 0.7rem;
        font-weight: 700;
        margin-left: auto;
        min-width: 20px;
        text-align: center;
        animation: pulse 2s infinite;
        box-shadow: 0 0 10px rgba(239, 68, 68, 0.5)
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
            opacity: 1
        }

        50% {
            transform: scale(1.05);
            opacity: 0.9
        }
    }

    .admin-content {
        margin-left: 260px;
        flex: 1;
        padding: 2rem;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        min-height: 100vh
    }
</style>

<script>
    // Load pending orders count
    async function loadPendingCount() {
        try {
            const response = await fetch(`${window.APP_URL}/admin/api/get-pending-count.php`);
            const data = await response.json();

            const badge = document.getElementById('pendingOrdersBadge');

            if (data.success && data.count > 0) {
                badge.textContent = data.count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        } catch (error) {
            console.error('Error loading pending count:', error);
        }
    }

    // Load on page load
    loadPendingCount();

    // Refresh every 30 seconds
    setInterval(loadPendingCount, 30000);

    // Also refresh when coming back to tab
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            loadPendingCount();
        }
    });
</script>