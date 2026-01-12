<?php
// Password Reset Logs Tab
global $pdo;

$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$date_filter = $_GET['date_filter'] ?? 'all';
$success_filter = $_GET['success'] ?? 'all'; // all, success, failed
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where = [];
$params = [];

if ($search) {
    $where[] = "(email LIKE ? OR ip_address LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($date_from) {
    $where[] = "DATE(created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where[] = "DATE(created_at) <= ?";
    $params[] = $date_to;
}

if ($date_filter == 'today') {
    $where[] = "DATE(created_at) = CURDATE()";
} elseif ($date_filter == 'week') {
    $where[] = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($date_filter == 'month') {
    $where[] = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
}


$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$count_query = "SELECT COUNT(*) FROM password_reset_logs $where_sql";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

// Get logs
$query = "SELECT * FROM password_reset_logs 
          $where_sql 
          ORDER BY created_at DESC 
          LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Stats
$total_attempts = $pdo->query("SELECT COUNT(*) FROM password_reset_logs")->fetchColumn();
$successful = $pdo->query("SELECT COUNT(*) FROM password_reset_logs WHERE success = 1")->fetchColumn();
$failed = $pdo->query("SELECT COUNT(*) FROM password_reset_logs WHERE success = 0")->fetchColumn();
$today_attempts = $pdo->query("SELECT COUNT(*) FROM password_reset_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// Top IPs (potential abuse)
$top_ips_query = "SELECT ip_address, COUNT(*) as count 
                  FROM password_reset_logs 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  GROUP BY ip_address 
                  ORDER BY count DESC 
                  LIMIT 5";
$top_ips = $pdo->query($top_ips_query)->fetchAll();

// Top emails (potential abuse)
$top_emails_query = "SELECT email, COUNT(*) as count 
                     FROM password_reset_logs 
                     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                     GROUP BY email 
                     ORDER BY count DESC 
                     LIMIT 5";
$top_emails = $pdo->query($top_emails_query)->fetchAll();
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-shield-alt"></i> Password Reset Logs</h1>
        <p>Theo dõi các yêu cầu đặt lại mật khẩu và phát hiện spam</p>
    </div>
</div>

<!-- Stats Cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:2rem">
    <div class="card"
        style="background:linear-gradient(135deg,rgba(139,92,246,0.1),rgba(139,92,246,0.05));border:1px solid rgba(139,92,246,0.2)">
        <div style="display:flex;align-items:center;gap:1rem">
            <div
                style="width:48px;height:48px;background:rgba(139,92,246,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center">
                <i class="fas fa-list" style="font-size:1.5rem;color:#8b5cf6"></i>
            </div>
            <div>
                <div style="color:#94a3b8;font-size:0.85rem">Tổng Yêu Cầu</div>
                <div style="color:#f8fafc;font-size:1.75rem;font-weight:700"><?= number_format($total_attempts) ?></div>
            </div>
        </div>
    </div>

    <div class="card"
        style="background:linear-gradient(135deg,rgba(16,185,129,0.1),rgba(16,185,129,0.05));border:1px solid rgba(16,185,129,0.2)">
        <div style="display:flex;align-items:center;gap:1rem">
            <div
                style="width:48px;height:48px;background:rgba(16,185,129,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center">
                <i class="fas fa-check-circle" style="font-size:1.5rem;color:#10b981"></i>
            </div>
            <div>
                <div style="color:#94a3b8;font-size:0.85rem">Thành Công</div>
                <div style="color:#f8fafc;font-size:1.75rem;font-weight:700"><?= number_format($successful) ?></div>
            </div>
        </div>
    </div>

    <div class="card"
        style="background:linear-gradient(135deg,rgba(239,68,68,0.1),rgba(239,68,68,0.05));border:1px solid rgba(239,68,68,0.2)">
        <div style="display:flex;align-items:center;gap:1rem">
            <div
                style="width:48px;height:48px;background:rgba(239,68,68,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center">
                <i class="fas fa-times-circle" style="font-size:1.5rem;color:#ef4444"></i>
            </div>
            <div>
                <div style="color:#94a3b8;font-size:0.85rem">Thất Bại</div>
                <div style="color:#f8fafc;font-size:1.75rem;font-weight:700"><?= number_format($failed) ?></div>
            </div>
        </div>
    </div>

    <div class="card"
        style="background:linear-gradient(135deg,rgba(245,158,11,0.1),rgba(245,158,11,0.05));border:1px solid rgba(245,158,11,0.2)">
        <div style="display:flex;align-items:center;gap:1rem">
            <div
                style="width:48px;height:48px;background:rgba(245,158,11,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center">
                <i class="fas fa-calendar-day" style="font-size:1.5rem;color:#f59e0b"></i>
            </div>
            <div>
                <div style="color:#94a3b8;font-size:0.85rem">Hôm Nay</div>
                <div style="color:#f8fafc;font-size:1.75rem;font-weight:700"><?= number_format($today_attempts) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Potential Abuse Detection -->
<?php if (!empty($top_ips) || !empty($top_emails)): ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem">
        <!-- Top IPs -->
        <div class="card">
            <h3 style="color:#f8fafc;margin-bottom:1rem;display:flex;align-items:center;gap:0.5rem">
                <i class="fas fa-exclamation-triangle" style="color:#f59e0b"></i>
                Top IPs (24h) - Nghi Ngờ Spam
            </h3>
            <div style="display:flex;flex-direction:column;gap:0.75rem">
                <?php foreach ($top_ips as $ip): ?>
                    <div
                        style="display:flex;justify-content:space-between;align-items:center;padding:0.75rem;background:rgba(30,41,59,0.5);border-radius:8px">
                        <code style="color:#8b5cf6;font-size:0.9rem"><?= htmlspecialchars($ip['ip_address']) ?></code>
                        <span class="badge badge-<?= $ip['count'] >= 5 ? 'danger' : 'warning' ?>">
                            <?= $ip['count'] ?> lần
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Top Emails -->
        <div class="card">
            <h3 style="color:#f8fafc;margin-bottom:1rem;display:flex;align-items:center;gap:0.5rem">
                <i class="fas fa-exclamation-triangle" style="color:#f59e0b"></i>
                Top Emails (24h) - Nghi Ngờ Spam
            </h3>
            <div style="display:flex;flex-direction:column;gap:0.75rem">
                <?php foreach ($top_emails as $email): ?>
                    <div
                        style="display:flex;justify-content:space-between;align-items:center;padding:0.75rem;background:rgba(30,41,59,0.5);border-radius:8px">
                        <span style="color:#94a3b8;font-size:0.9rem"><?= htmlspecialchars($email['email']) ?></span>
                        <span class="badge badge-<?= $email['count'] >= 5 ? 'danger' : 'warning' ?>">
                            <?= $email['count'] ?> lần
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>


<!-- Search Bar -->
<div class="card">
    <form method="GET" style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap">
        <input type="hidden" name="tab" value="password_reset">
        <input type="hidden" name="success" value="<?= htmlspecialchars($success_filter) ?>">
        <div style="flex:1;min-width:200px;position:relative">
            <i class="fas fa-search"
                style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:#64748b"></i>
            <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>"
                style="padding-left:3rem;border-radius:12px">
        </div>
        <div style="display:flex;gap:0.5rem;align-items:center">
            <label style="color:#94a3b8;font-size:0.9rem;white-space:nowrap">Thời gian:</label>
            <select name="date_filter" class="form-control" style="border-radius:12px;width:120px"
                onchange="this.form.submit()">
                <option value="all" <?= $date_filter == 'all' ? 'selected' : '' ?>>Tất cả</option>
                <option value="today" <?= $date_filter == 'today' ? 'selected' : '' ?>>Hôm nay</option>
                <option value="week" <?= $date_filter == 'week' ? 'selected' : '' ?>>Tuần này</option>
                <option value="month" <?= $date_filter == 'month' ? 'selected' : '' ?>>Tháng này</option>
            </select>
        </div>
        <div style="display:flex;gap:0.5rem;align-items:center">
            <label style="color:#94a3b8;font-size:0.9rem;white-space:nowrap">Từ ngày:</label>
            <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" class="form-control"
                style="border-radius:12px;width:140px">
        </div>
        <div style="display:flex;gap:0.5rem;align-items:center">
            <label style="color:#94a3b8;font-size:0.9rem;white-space:nowrap">Đến ngày:</label>
            <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" class="form-control"
                style="border-radius:12px;width:140px">
        </div>
        <button type="submit" class="btn btn-primary" style="padding:0.75rem 2rem;border-radius:12px">
            <i class="fas fa-search"></i> Tìm
        </button>
        <?php if ($search || $date_from || $date_to): ?>
            <a href="?tab=password_reset&success=<?= $success_filter ?>" class="btn btn-secondary"
                style="padding:0.75rem 1rem;border-radius:12px">
                <i class="fas fa-times"></i>
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Logs Table -->
<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Thời Gian</th>
                    <th>Email</th>
                    <th>IP Address</th>
                    <th>Trạng Thái</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center;padding:3rem;color:#64748b">
                            <i class="fas fa-inbox" style="font-size:3rem;margin-bottom:1rem;display:block"></i>
                            <p style="margin:0">
                                <?php if ($search): ?>
                                    Không tìm thấy kết quả nào cho "<?= htmlspecialchars($search) ?>"
                                <?php else: ?>
                                    Chưa có log password reset nào
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <code style="color:#64748b;font-size:0.85rem">#<?= $log['id'] ?></code>
                            </td>
                            <td>
                                <div style="color:#f8fafc;font-weight:600"><?= date('d/m/Y', strtotime($log['created_at'])) ?>
                                </div>
                                <small style="color:#64748b"><?= date('H:i:s', strtotime($log['created_at'])) ?></small>
                            </td>
                            <td>
                                <div style="color:#8b5cf6;font-weight:500"><?= htmlspecialchars($log['email']) ?></div>
                            </td>
                            <td>
                                <code style="color:#64748b;font-size:0.9rem"><?= htmlspecialchars($log['ip_address']) ?></code>
                            </td>
                            <td>
                                <?php if ($log['success']): ?>
                                    <span class="badge badge-success">
                                        <i class="fas fa-check-circle"></i> Thành Công
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-danger">
                                        <i class="fas fa-times-circle"></i> Thất Bại
                                    </span>
                                <?php endif; ?>
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
        <?php
        $pagination_params = "success=$success_filter";
        if ($search)
            $pagination_params .= "&search=" . urlencode($search);
        if ($date_from)
            $pagination_params .= "&date_from=" . urlencode($date_from);
        if ($date_to)
            $pagination_params .= "&date_to=" . urlencode($date_to);
        ?>
        <?php if ($page > 1): ?>
            <a href="?tab=password_reset&page=<?= $page - 1 ?>&<?= $pagination_params ?>"><i
                    class="fas fa-chevron-left"></i></a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
            <a href="?tab=password_reset&page=<?= $i ?>&<?= $pagination_params ?>"
                class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
            <a href="?tab=password_reset&page=<?= $page + 1 ?>&<?= $pagination_params ?>"><i
                    class="fas fa-chevron-right"></i></a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<style>
    .tab-link:hover {
        background: rgba(139, 92, 246, 0.1);
        border-radius: 8px 8px 0 0;
    }
</style>