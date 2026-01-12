<?php
/**
 * NẠP TIỀN - Lịch sử giao dịch nạp tiền
 * Bao gồm: Nạp tiền (deposit) + Admin cộng (admin_add)
 */

$exchange_rate = floatval($pdo->query("SELECT setting_value FROM settings WHERE setting_key='exchange_rate'")->fetchColumn() ?? 25000);

// Filters
$filter_type = $_GET['filter_type'] ?? 'all';
$filter_user = trim($_GET['filter_user'] ?? '');

// Build WHERE clause
$where_clauses = ["bt.created_at BETWEEN ? AND ?"];
$params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];

// Type filter
// Only deposit
$where_clauses[] = "bt.type = 'deposit'";

// User filter
if (!empty($filter_user)) {
    $where_clauses[] = "(u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%{$filter_user}%";
    $params[] = "%{$filter_user}%";
}

$where_sql = implode(' AND ', $where_clauses);

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 25;
$offset = ($page - 1) * $per_page;

// Get total stats (for summary)
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN bt.currency='VND' THEN bt.amount ELSE bt.amount * ? END) as total_amount
FROM balance_transactions bt
LEFT JOIN users u ON bt.user_id = u.id
WHERE $where_sql";

$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute(array_merge([$exchange_rate], $params));
$stats = $stats_stmt->fetch();

$total_records = $stats['total'] ?? 0;
$total_amount = $stats['total_amount'] ?? 0;
// $deposit_count = $stats['deposit_count'] ?? 0; // Don't need separate counts anymore since it's only deposit

$total_pages = ceil($total_records / $per_page);

// Get deposit transactions
$deposits_sql = "SELECT bt.*, u.username, u.email,
    CASE WHEN bt.currency='VND' THEN bt.amount ELSE bt.amount * ? END as amount_vnd
FROM balance_transactions bt
LEFT JOIN users u ON bt.user_id = u.id
WHERE $where_sql
ORDER BY bt.created_at DESC
LIMIT ? OFFSET ?";
$deposits_stmt = $pdo->prepare($deposits_sql);
$deposits_stmt->execute(array_merge([$exchange_rate], $params, [$per_page, $offset]));
$deposit_list = $deposits_stmt->fetchAll();

// Build filter URL params
$filter_params = "date_from={$date_from}&date_to={$date_to}&filter_user=" . urlencode($filter_user);

?>

<!-- Filters -->
<div class="card" style="margin-bottom: 1.5rem">
    <form method="GET" style="padding: 1rem">
        <input type="hidden" name="tab" value="finances">
        <input type="hidden" name="sub" value="deposits">

        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; align-items: end">
            <div class="form-group" style="margin: 0">
                <label><i class="fas fa-calendar"></i> Từ ngày</label>
                <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>" required>
            </div>
            <div class="form-group" style="margin: 0">
                <label><i class="fas fa-calendar"></i> Đến ngày</label>
                <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>" required>
            </div>
            <div class="form-group" style="margin: 0">
                <label><i class="fas fa-calendar-alt"></i> Thời gian</label>
                <select name="date_filter" class="form-control" onchange="this.form.submit()">
                    <option value="all" <?= $date_filter == 'all' ? 'selected' : '' ?>>Tùy chọn</option>
                    <option value="today" <?= $date_filter == 'today' ? 'selected' : '' ?>>Hôm nay</option>
                    <option value="week" <?= $date_filter == 'week' ? 'selected' : '' ?>>Tuần này</option>
                    <option value="month" <?= $date_filter == 'month' ? 'selected' : '' ?>>Tháng này</option>
                </select>
            </div>
            <!-- Removed Type Filter as it's only Deposit now -->
            <div class="form-group" style="margin: 0">
                <label><i class="fas fa-user"></i> Người dùng</label>
                <input type="text" name="filter_user" class="form-control" placeholder="Tên hoặc email..."
                    value="<?= e($filter_user) ?>">
            </div>
            <div>
                <button type="submit" class="btn btn-primary" style="width: 100%">
                    <i class="fas fa-search"></i> Lọc
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Total Summary -->
<div class="card" style="margin-bottom: 1.5rem; background: #0f172a; border: 1px solid #10b981; border-radius: 4px">
    <div
        style="padding: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem">
        <div style="display: flex; align-items: center; gap: 1rem">
            <div
                style="width: 50px; height: 50px; background: #10b981; border-radius: 4px; display: flex; align-items: center; justify-content: center">
                <i class="fas fa-coins" style="font-size: 1.5rem; color: white"></i>
            </div>
            <div>
                <div style="color: #10b981; font-size: 1.8rem; font-weight: 800"><?= formatVND($total_amount) ?></div>
                <div style="color: #94a3b8; font-size: 0.9rem">Tổng doanh thu/nạp tiền
                    (<?= number_format($total_records) ?> giao dịch)</div>
            </div>
        </div>
    </div>
</div>

<!-- Deposit List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list" style="color: #10b981"></i> Lịch Sử Nạp Tiền</h3>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Thời Gian</th>
                    <th>Người Dùng</th>
                    <th>Loại</th>
                    <th>Số Tiền</th>
                    <th>Số Dư Trước</th>
                    <th>Số Dư Sau</th>
                    <th>Ghi Chú</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($deposit_list)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 3rem; color: #64748b">
                            <i class="fas fa-inbox"
                                style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; display: block"></i>
                            Không có giao dịch nào phù hợp
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($deposit_list as $deposit): ?>
                        <tr>
                            <td>
                                <div style="color: #f8fafc; font-weight: 600">
                                    <?= date('d/m/Y', strtotime($deposit['created_at'])) ?>
                                </div>
                                <small style="color: #64748b"><?= date('H:i:s', strtotime($deposit['created_at'])) ?></small>
                            </td>
                            <td>
                                <div><strong style="color: #f8fafc"><?= e($deposit['username'] ?? 'N/A') ?></strong></div>
                                <small style="color: #64748b"><?= e($deposit['email'] ?? '') ?></small>
                            </td>
                            <td>
                                <?php if ($deposit['type'] === 'deposit'): ?>
                                    <span class="badge badge-success"><i class="fas fa-credit-card"></i> Nạp Tiền</span>
                                <?php else: ?>
                                    <span class="badge badge-warning"><i class="fas fa-user-shield"></i> Admin</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong
                                    style="color: #10b981; font-size: 1.1rem">+<?= formatVND($deposit['amount_vnd']) ?></strong>
                            </td>
                            <td style="color: #64748b"><?= formatVND($deposit['balance_before']) ?></td>
                            <td><strong style="color: #10b981"><?= formatVND($deposit['balance_after']) ?></strong></td>
                            <td>
                                <small
                                    style="color: #94a3b8; max-width: 200px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap"
                                    title="<?= e($deposit['note'] ?? '') ?>">
                                    <?= e($deposit['note'] ?? '-') ?>
                                </small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div
            style="padding: 1rem; border-top: 1px solid rgba(139, 92, 246, 0.1); display: flex; justify-content: center; align-items: center; gap: 0.5rem">
            <?php if ($page > 1): ?>
                <a href="?tab=finances&sub=deposits&<?= $filter_params ?>&page=<?= $page - 1 ?>"
                    class="btn btn-sm btn-secondary">
                    <i class="fas fa-chevron-left"></i> Trước
                </a>
            <?php endif; ?>
            <span style="color: #64748b; padding: 0 1rem">Trang <?= $page ?> / <?= $total_pages ?></span>
            <?php if ($page < $total_pages): ?>
                <a href="?tab=finances&sub=deposits&<?= $filter_params ?>&page=<?= $page + 1 ?>"
                    class="btn btn-sm btn-secondary">
                    Sau <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>