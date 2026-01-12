<?php
// User Activity Logs Tab - View all user actions and system events
?>

<style>
    /* Custom scrollbar for table wrapper */
    .table-wrapper::-webkit-scrollbar {
        height: 8px;
    }

    .table-wrapper::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 4px;
    }

    .table-wrapper::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.5);
        border-radius: 4px;
    }

    .table-wrapper::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.7);
    }

    /* Firefox scrollbar */
    .table-wrapper {
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.5) rgba(255, 255, 255, 0.1);
    }
</style>

<?php

$selected_user_id = $_GET['user_id'] ?? '';
$search = $_GET['search'] ?? '';
$log_type_filter = $_GET['log_type'] ?? '';
$country_filter = $_GET['country'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$date_filter = $_GET['date_filter'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get all users for dropdown
$users_query = "SELECT id, username, email FROM users ORDER BY username ASC";
$users_stmt = $pdo->query($users_query);
$all_users = $users_stmt->fetchAll();

// Get unique countries for filter
$countries_query = "SELECT DISTINCT country FROM system_logs WHERE country IS NOT NULL ORDER BY country";
$countries_stmt = $pdo->query($countries_query);
$countries = $countries_stmt->fetchAll(PDO::FETCH_COLUMN);

// Build WHERE clause
$where = [];
$params = [];

if ($selected_user_id) {
    $where[] = "sl.user_id = ?";
    $params[] = $selected_user_id;
}

if ($search) {
    $where[] = "(sl.description LIKE ? OR sl.action LIKE ? OR sl.ip_address LIKE ? OR u.username LIKE ? OR u.id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($log_type_filter) {
    $where[] = "sl.log_type = ?";
    $params[] = $log_type_filter;
}

if ($country_filter) {
    $where[] = "sl.country = ?";
    $params[] = $country_filter;
}

if ($date_from) {
    $where[] = "DATE(sl.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where[] = "DATE(sl.created_at) <= ?";
    $params[] = $date_to;
}

if ($date_filter == 'today') {
    $where[] = "DATE(sl.created_at) = CURDATE()";
} elseif ($date_filter == 'week') {
    $where[] = "YEARWEEK(sl.created_at, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($date_filter == 'month') {
    $where[] = "MONTH(sl.created_at) = MONTH(CURDATE()) AND YEAR(sl.created_at) = YEAR(CURDATE())";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$count_query = "SELECT COUNT(*) FROM system_logs sl 
                LEFT JOIN users u ON sl.user_id = u.id 
                $where_sql";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

// Get logs
$query = "SELECT sl.*, u.username, u.email 
          FROM system_logs sl
          LEFT JOIN users u ON sl.user_id = u.id
          $where_sql
          ORDER BY sl.created_at DESC
          LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Log type labels
$log_type_labels = [
    'user_login' => ['label' => 'Đăng nhập', 'color' => 'info', 'icon' => 'fa-sign-in-alt'],
    'admin_action' => ['label' => 'Thao tác Admin', 'color' => 'warning', 'icon' => 'fa-user-shield'],
    'payment' => ['label' => 'Thanh toán', 'color' => 'success', 'icon' => 'fa-credit-card'],
    'balance' => ['label' => 'Số dư', 'color' => 'primary', 'icon' => 'fa-wallet'],
    'order' => ['label' => 'Đơn hàng', 'color' => 'info', 'icon' => 'fa-shopping-cart'],
    'product' => ['label' => 'Sản phẩm', 'color' => 'secondary', 'icon' => 'fa-box'],
    'system' => ['label' => 'Hệ thống', 'color' => 'danger', 'icon' => 'fa-cog']
];

// Function to parse user agent
function parseUserAgent($ua)
{
    if (empty($ua))
        return 'Unknown';

    // Browser detection
    if (strpos($ua, 'Chrome') !== false)
        $browser = 'Chrome';
    elseif (strpos($ua, 'Firefox') !== false)
        $browser = 'Firefox';
    elseif (strpos($ua, 'Safari') !== false)
        $browser = 'Safari';
    elseif (strpos($ua, 'Edge') !== false)
        $browser = 'Edge';
    else
        $browser = 'Other';

    // OS detection
    if (strpos($ua, 'Windows') !== false)
        $os = 'Windows';
    elseif (strpos($ua, 'Mac') !== false)
        $os = 'Mac';
    elseif (strpos($ua, 'Linux') !== false)
        $os = 'Linux';
    elseif (strpos($ua, 'Android') !== false)
        $os = 'Android';
    elseif (strpos($ua, 'iOS') !== false)
        $os = 'iOS';
    else
        $os = 'Other';

    return "$browser / $os";
}
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-history"></i> Nhật Ký Hoạt Động</h1>
        <p>Xem lịch sử hoạt động của người dùng</p>
    </div>
    <div style="display:flex;gap:0.5rem">
        <a href="?tab=users" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value">
                    <?= number_format($total) ?>
                </div>
                <div class="stat-label">Tổng Log</div>
            </div>
            <div class="stat-icon primary"><i class="fas fa-list"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value">
                    <?php
                    $login_count = $pdo->query("SELECT COUNT(*) FROM system_logs WHERE log_type='user_login'")->fetchColumn();
                    echo number_format($login_count);
                    ?>
                </div>
                <div class="stat-label">Đăng Nhập</div>
            </div>
            <div class="stat-icon info"><i class="fas fa-sign-in-alt"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value">
                    <?php
                    $admin_count = $pdo->query("SELECT COUNT(*) FROM system_logs WHERE log_type='admin_action'")->fetchColumn();
                    echo number_format($admin_count);
                    ?>
                </div>
                <div class="stat-label">Thao Tác Admin</div>
            </div>
            <div class="stat-icon warning"><i class="fas fa-user-shield"></i></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card">
    <form method="GET" class="form-grid">
        <input type="hidden" name="tab" value="user_activity_logs">

        <div class="form-group">
            <label><i class="fas fa-user"></i> Chọn Người Dùng</label>
            <select name="user_id" class="form-control">
                <option value="">-- Tất cả người dùng --</option>
                <?php foreach ($all_users as $user): ?>
                    <option value="<?= $user['id'] ?>" <?= $selected_user_id == $user['id'] ? 'selected' : '' ?>>
                        <?= e($user['username']) ?> (
                        <?= e($user['email']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label><i class="fas fa-filter"></i> Loại Log</label>
            <select name="log_type" class="form-control">
                <option value="">Tất cả</option>
                <option value="user_login" <?= $log_type_filter == 'user_login' ? 'selected' : '' ?>>Đăng nhập</option>
                <option value="admin_action" <?= $log_type_filter == 'admin_action' ? 'selected' : '' ?>>Thao tác Admin
                </option>
                <option value="payment" <?= $log_type_filter == 'payment' ? 'selected' : '' ?>>Thanh toán</option>
                <option value="balance" <?= $log_type_filter == 'balance' ? 'selected' : '' ?>>Số dư</option>
                <option value="order" <?= $log_type_filter == 'order' ? 'selected' : '' ?>>Đơn hàng</option>
                <option value="product" <?= $log_type_filter == 'product' ? 'selected' : '' ?>>Sản phẩm</option>
                <option value="system" <?= $log_type_filter == 'system' ? 'selected' : '' ?>>Hệ thống</option>
            </select>
        </div>

        <div class="form-group">
            <label><i class="fas fa-globe"></i> Quốc Gia</label>
            <select name="country" class="form-control">
                <option value="">Tất cả</option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?= $country ?>" <?= $country_filter == $country ? 'selected' : '' ?>>
                        <?= $country ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label><i class="fas fa-calendar-alt"></i> Thời Gian</label>
            <select name="date_filter" class="form-control" onchange="this.form.submit()">
                <option value="all" <?= $date_filter == 'all' ? 'selected' : '' ?>>Tất cả</option>
                <option value="today" <?= $date_filter == 'today' ? 'selected' : '' ?>>Hôm nay</option>
                <option value="week" <?= $date_filter == 'week' ? 'selected' : '' ?>>Tuần này</option>
                <option value="month" <?= $date_filter == 'month' ? 'selected' : '' ?>>Tháng này</option>
            </select>
        </div>

        <div class="form-group">
            <label><i class="fas fa-calendar"></i> Từ Ngày</label>
            <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
        </div>

        <div class="form-group">
            <label><i class="fas fa-calendar"></i> Đến Ngày</label>
            <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
        </div>

        <div class="form-group">
            <label><i class="fas fa-search"></i> Tìm Kiếm</label>
            <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>"
                placeholder="Mô tả, IP, username, User ID...">
        </div>

        <div class="form-group" style="display:flex;align-items:flex-end;gap:0.5rem">
            <button type="submit" class="btn btn-primary" style="flex:1">
                <i class="fas fa-filter"></i> Lọc
            </button>
            <a href="?tab=user_activity_logs" class="btn btn-secondary">
                <i class="fas fa-redo"></i>
            </a>
        </div>
    </form>
</div>

<!-- Activity Logs Table -->
<div class="card">
    <div class="table-wrapper" style="overflow-x: auto;">
        <table>
            <thead>
                <tr>

                    <th>Username</th>
                    <th>Loại</th>
                    <th>Hành Động</th>
                    <th>Mô Tả</th>
                    <th>IP / Quốc Gia</th>
                    <th>Thiết Bị</th>
                    <th>Thời Gian</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:3rem;color:#64748b">
                            <i class="fas fa-inbox" style="font-size:3rem;margin-bottom:1rem"></i>
                            <p>Không tìm thấy log nào</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <?php
                        $type_info = $log_type_labels[$log['log_type']] ?? ['label' => $log['log_type'], 'color' => 'secondary', 'icon' => 'fa-question'];
                        ?>
                        <tr>

                            <td>
                                <?php if ($log['username']): ?>
                                    <strong style="color:#f8fafc">
                                        <?= e($log['username']) ?>
                                    </strong><br>
                                    <small style="color:#64748b">
                                        <?= e($log['email']) ?>
                                    </small>
                                <?php else: ?>
                                    <small style="color:#64748b">N/A</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= $type_info['color'] ?>">
                                    <i class="fas <?= $type_info['icon'] ?>"></i>
                                    <?= $type_info['label'] ?>
                                </span>
                            </td>
                            <td>
                                <strong style="color:#f8fafc">
                                    <?= e($log['action']) ?>
                                </strong>
                            </td>
                            <td>
                                <small style="color:#94a3b8;display:block" title="<?= e($log['description']) ?>">
                                    <?= e($log['description'] ?? '-') ?>
                                </small>
                            </td>
                            <td>
                                <div style="color:#94a3b8">
                                    <?= e($log['ip_address'] ?? '-') ?>
                                    <?php if ($log['country']): ?>
                                        <br><small style="color:#64748b">
                                            <i class="fas fa-flag"></i>
                                            <?= e($log['country']) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <small style="color:#64748b">
                                    <?= parseUserAgent($log['user_agent']) ?>
                                </small>
                            </td>
                            <td>
                                <small style="color:#64748b">
                                    <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
                                </small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a
                href="?tab=user_activity_logs&page=<?= $page - 1 ?>&user_id=<?= $selected_user_id ?>&log_type=<?= $log_type_filter ?>&country=<?= $country_filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&search=<?= urlencode($search) ?>">
                <i class="fas fa-chevron-left"></i>
            </a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
            <a href="?tab=user_activity_logs&page=<?= $i ?>&user_id=<?= $selected_user_id ?>&log_type=<?= $log_type_filter ?>&country=<?= $country_filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&search=<?= urlencode($search) ?>"
                class="<?= $i == $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a
                href="?tab=user_activity_logs&page=<?= $page + 1 ?>&user_id=<?= $selected_user_id ?>&log_type=<?= $log_type_filter ?>&country=<?= $country_filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&search=<?= urlencode($search) ?>">
                <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>