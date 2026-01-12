<?php
/**
 * Lịch Sử Nạp Tiền - Sepay Webhook Logs
 * Hiển thị tất cả giao dịch nạp tiền từ Sepay
 */

// Filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$date_filter = $_GET['date_filter'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where = [];
$params = [];

// Only show processed transactions
$where[] = "swl.processed = 1";

if ($search) {
    $where[] = "(swl.content LIKE ? OR swl.account_number LIKE ? OR swl.transaction_id LIKE ? OR u.username LIKE ? OR u.id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($date_from) {
    $where[] = "DATE(swl.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where[] = "DATE(swl.created_at) <= ?";
    $params[] = $date_to;
}

if ($date_filter == 'today') {
    $where[] = "DATE(swl.created_at) = CURDATE()";
} elseif ($date_filter == 'week') {
    $where[] = "YEARWEEK(swl.created_at, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($date_filter == 'month') {
    $where[] = "MONTH(swl.created_at) = MONTH(CURDATE()) AND YEAR(swl.created_at) = YEAR(CURDATE())";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$count_query = "SELECT COUNT(*) FROM sepay_webhook_logs swl 
LEFT JOIN payment_transactions pt ON (LOWER(swl.content) LIKE CONCAT('%', pt.transaction_code, '%'))
LEFT JOIN users u ON pt.user_id = u.id
$where_sql";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

// Get stats
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN swl.processed = 1 THEN 1 ELSE 0 END) as processed,
    SUM(CASE WHEN swl.processed = 0 THEN 1 ELSE 0 END) as pending,
    SUM(swl.amount) as total_amount,
    SUM(CASE WHEN swl.processed = 1 THEN swl.amount ELSE 0 END) as processed_amount
FROM sepay_webhook_logs swl 
LEFT JOIN payment_transactions pt ON (LOWER(swl.content) LIKE CONCAT('%', pt.transaction_code, '%'))
LEFT JOIN users u ON pt.user_id = u.id
$where_sql";
$stats_stmt = $pdo->prepare($stats_query);
$stats_stmt->execute($params);
$stats = $stats_stmt->fetch();

// Get logs with user info (parse transaction code from content to find user)
$query = "SELECT swl.*, 
    pt.user_id, pt.transaction_code,
    u.username, u.email, u.phone
FROM sepay_webhook_logs swl
LEFT JOIN payment_transactions pt ON (
    LOWER(swl.content) LIKE CONCAT('%', pt.transaction_code, '%')
)
LEFT JOIN users u ON pt.user_id = u.id
$where_sql
ORDER BY swl.created_at DESC
LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-money-bill-wave"></i> Lịch Sử Nạp Tiền (Sepay)</h1>
        <p>Webhook logs từ cổng thanh toán Sepay</p>
    </div>
    <div style="display:flex;gap:0.5rem">
        <a href="?tab=user_balance_history" class="btn btn-secondary">
            <i class="fas fa-exchange-alt"></i> Biến Động Số Dư
        </a>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value"><?= number_format($stats['total'] ?? 0) ?></div>
                <div class="stat-label">Tổng Giao Dịch</div>
            </div>
            <div class="stat-icon primary"><i class="fas fa-receipt"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value" style="color:#10b981"><?= number_format($stats['processed'] ?? 0) ?></div>
                <div class="stat-label">Đã Xử Lý</div>
            </div>
            <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value" style="color:#10b981;font-size:1.5rem">
                    <?= formatVND($stats['processed_amount'] ?? 0) ?>
                </div>
                <div class="stat-label">Tổng Tiền Nạp</div>
            </div>
            <div class="stat-icon success"><i class="fas fa-coins"></i></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card">
    <form method="GET" class="form-grid">
        <input type="hidden" name="tab" value="deposit_history">



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
                placeholder="Nội dung, STK, Mã GD, Username, User ID...">
        </div>

        <div class="form-group" style="display:flex;align-items:flex-end;gap:0.5rem">
            <button type="submit" class="btn btn-primary" style="flex:1">
                <i class="fas fa-filter"></i> Lọc
            </button>
            <a href="?tab=deposit_history" class="btn btn-secondary">
                <i class="fas fa-redo"></i>
            </a>
        </div>
    </form>
</div>

<!-- Logs Table -->
<div class="card">
    <div class="table-wrapper" style="overflow-x: auto;">
        <table>
            <thead>
                <tr>

                    <th>Người Nạp</th>
                    <th>Mã GD Sepay</th>
                    <th>Số Tiền</th>
                    <th>Nội Dung CK</th>
                    <th>STK / Ngân Hàng</th>
                    <th>Thời Gian</th>
                    <th>Thao Tác</th>

                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:3rem;color:#64748b">
                            <i class="fas fa-inbox" style="font-size:3rem;margin-bottom:1rem;display:block;opacity:0.3"></i>
                            <p>Không có giao dịch nào</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>

                            <td>
                                <?php if ($log['user_id']): ?>
                                    <a href="?tab=users&search=<?= urlencode($log['username']) ?>" style="text-decoration:none">
                                        <div style="color:#f8fafc;font-weight:600"><?= e($log['username']) ?></div>
                                        <small style="color:#64748b">ID: <?= $log['user_id'] ?></small>
                                    </a>
                                <?php else: ?>
                                    <small style="color:#64748b">-</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code
                                    style="background:rgba(139,92,246,0.2);color:#c4b5fd;padding:0.3rem 0.6rem;border-radius:6px;font-size:0.8rem">
                                    <?= e($log['transaction_id'] ?? 'N/A') ?>
                                </code>
                            </td>
                            <td>
                                <strong style="color:#10b981;font-size:1.1rem">
                                    +<?= formatVND($log['amount']) ?>
                                </strong>
                            </td>
                            <td>
                                <div title="<?= e($log['content']) ?>">
                                    <?= e($log['content'] ?? '-') ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($log['phone']): ?>
                                    <div style="color:#f8fafc;font-weight:600"><?= e($log['phone']) ?></div>
                                    <small style="color:#64748b"><?= e($log['gate'] ?? 'Ngân hàng') ?></small>
                                <?php else: ?>
                                    <div style="color:#64748b">-</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="color:#f8fafc"><?= date('d/m/Y', strtotime($log['created_at'])) ?></div>
                                <small style="color:#64748b"><?= date('H:i:s', strtotime($log['created_at'])) ?></small>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info"
                                    onclick="showRawJson(<?= $log['id'] ?>, <?= htmlspecialchars(json_encode($log['raw_data']), ENT_QUOTES) ?>)"
                                    title="Xem Raw JSON">
                                    <i class="fas fa-code"></i>
                                </button>
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
                href="?tab=deposit_history&page=<?= $page - 1 ?>&status=<?= $status_filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&search=<?= urlencode($search) ?>">
                <i class="fas fa-chevron-left"></i>
            </a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
            <a href="?tab=deposit_history&page=<?= $i ?>&status=<?= $status_filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&search=<?= urlencode($search) ?>"
                class="<?= $i == $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a
                href="?tab=deposit_history&page=<?= $page + 1 ?>&status=<?= $status_filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&search=<?= urlencode($search) ?>">
                <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- JSON Modal -->
<div id="jsonModal"
    style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.8);z-index:9999;padding:2rem;overflow:auto">
    <div
        style="max-width:800px;margin:0 auto;background:var(--card-bg);border-radius:16px;border:1px solid var(--border)">
        <div
            style="padding:1rem 1.5rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
            <h3 style="margin:0;color:#f8fafc"><i class="fas fa-code"></i> Raw Webhook Data <span id="jsonLogId"
                    style="color:#64748b"></span></h3>
            <button onclick="closeJsonModal()"
                style="background:none;border:none;color:#64748b;cursor:pointer;font-size:1.5rem">&times;</button>
        </div>
        <div style="padding:1.5rem">
            <pre id="jsonContent"
                style="background:rgba(15,23,42,0.8);border-radius:12px;padding:1rem;overflow-x:auto;color:#10b981;font-family:monospace;font-size:0.85rem;margin:0;max-height:60vh;overflow-y:auto"></pre>
        </div>
        <div
            style="padding:1rem 1.5rem;border-top:1px solid var(--border);display:flex;gap:0.5rem;justify-content:flex-end">
            <button onclick="copyJson()" class="btn btn-primary btn-sm">
                <i class="fas fa-copy"></i> Copy JSON
            </button>
            <button onclick="closeJsonModal()" class="btn btn-secondary btn-sm">Đóng</button>
        </div>
    </div>
</div>

<script>
    let currentJsonData = '';

    function showRawJson(logId, rawData) {
        try {
            const parsed = JSON.parse(rawData);
            currentJsonData = JSON.stringify(parsed, null, 2);
        } catch (e) {
            currentJsonData = rawData || 'No data';
        }

        document.getElementById('jsonLogId').textContent = '#' + logId;
        document.getElementById('jsonContent').textContent = currentJsonData;
        document.getElementById('jsonModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeJsonModal() {
        document.getElementById('jsonModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    function copyJson() {
        navigator.clipboard.writeText(currentJsonData).then(() => {
            Notify.success('Đã copy JSON!');
        }).catch(() => {
            Notify.error('Không thể copy');
        });
    }

    // Close on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeJsonModal();
    });

    // Close on backdrop click
    document.getElementById('jsonModal').addEventListener('click', function (e) {
        if (e.target === this) closeJsonModal();
    });
</script>