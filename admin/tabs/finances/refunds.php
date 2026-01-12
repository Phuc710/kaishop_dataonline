<?php
/**
 * HOÀN TIỀN - Lịch sử hoàn tiền cho user
 * Bao gồm: Hoàn đơn hủy (refund) + Admin trừ (admin_deduct)
 */

$exchange_rate = floatval($pdo->query("SELECT setting_value FROM settings WHERE setting_key='exchange_rate'")->fetchColumn() ?? 25000);

// Filters
$filter_type = $_GET['filter_type'] ?? 'all';
$filter_user = trim($_GET['filter_user'] ?? '');

// Build WHERE clause
$where_clauses = ["bt.created_at BETWEEN ? AND ?"];
$params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];

// Type filter
// Only refund
$where_clauses[] = "bt.type = 'refund'";

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
// $refund_count = $stats['refund_count'] ?? 0;
// $deduct_count = $stats['deduct_count'] ?? 0;
$total_pages = ceil($total_records / $per_page);

// Get refund transactions
$refunds_sql = "SELECT bt.*, u.username, u.email,
    CASE WHEN bt.currency='VND' THEN bt.amount ELSE bt.amount * ? END as amount_vnd
FROM balance_transactions bt
LEFT JOIN users u ON bt.user_id = u.id
WHERE $where_sql
ORDER BY bt.created_at DESC
LIMIT ? OFFSET ?";
$refunds_stmt = $pdo->prepare($refunds_sql);
$refunds_stmt->execute(array_merge([$exchange_rate], $params, [$per_page, $offset]));
$refund_list = $refunds_stmt->fetchAll();

// Build filter URL params
$filter_params = "date_from={$date_from}&date_to={$date_to}&filter_user=" . urlencode($filter_user);

?>

<!-- Filters -->
<div class="card" style="margin-bottom: 1.5rem">
    <form method="GET" style="padding: 1rem">
        <input type="hidden" name="tab" value="finances">
        <input type="hidden" name="sub" value="refunds">

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
            <!-- Removed Type Filter -->
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
<div class="card" style="margin-bottom: 1.5rem; background: #0f172a; border: 1px solid #ef4444; border-radius: 4px">
    <div
        style="padding: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem">
        <div style="display: flex; align-items: center; gap: 1rem">
            <div
                style="width: 50px; height: 50px; background: #ef4444; border-radius: 4px; display: flex; align-items: center; justify-content: center">
                <i class="fas fa-hand-holding-usd" style="font-size: 1.5rem; color: white"></i>
            </div>
            <div>
                <div style="color: #ef4444; font-size: 1.8rem; font-weight: 800"><?= formatVND($total_amount) ?></div>
                <div style="color: #94a3b8; font-size: 0.9rem">Tổng tiền hoàn (<?= number_format($total_records) ?> giao
                    dịch)</div>
            </div>
        </div>
    </div>
</div>

<!-- Refund List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list" style="color: #ef4444"></i> Lịch Sử Hoàn Tiền</h3>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Thời Gian</th>
                    <th>Người Dùng</th>
                    <th>Loại</th>
                    <th>Số Tiền Hoàn</th>
                    <th>Số Dư Trước</th>
                    <th>Số Dư Sau</th>
                    <th>Ghi Chú</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($refund_list)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 3rem; color: #64748b">
                            <i class="fas fa-inbox"
                                style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; display: block"></i>
                            Không có giao dịch nào phù hợp
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($refund_list as $refund): ?>
                        <tr>
                            <td>
                                <div style="color: #f8fafc; font-weight: 600">
                                    <?= date('d/m/Y', strtotime($refund['created_at'])) ?>
                                </div>
                                <small style="color: #64748b"><?= date('H:i:s', strtotime($refund['created_at'])) ?></small>
                            </td>
                            <td>
                                <div><strong style="color: #f8fafc"><?= e($refund['username'] ?? 'N/A') ?></strong></div>
                                <small style="color: #64748b"><?= e($refund['email'] ?? '') ?></small>
                            </td>
                            <td>
                                <?php if ($refund['type'] === 'refund'): ?>
                                    <span class="badge badge-warning"><i class="fas fa-undo"></i> Hoàn Đơn</span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><i class="fas fa-user-shield"></i> Admin Trừ</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong
                                    style="color: #ef4444; font-size: 1.1rem">+<?= formatVND($refund['amount_vnd']) ?></strong>
                            </td>
                            <td style="color: #64748b"><?= formatVND($refund['balance_before']) ?></td>
                            <td><strong style="color: #10b981"><?= formatVND($refund['balance_after']) ?></strong></td>
                            <td>
                                <small
                                    style="color: #94a3b8; max-width: 200px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap"
                                    title="<?= e($refund['note'] ?? '') ?>">
                                    <?= e($refund['note'] ?? '-') ?>
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
                <a href="?tab=finances&sub=refunds&<?= $filter_params ?>&page=<?= $page - 1 ?>"
                    class="btn btn-sm btn-secondary">
                    <i class="fas fa-chevron-left"></i> Trước
                </a>
            <?php endif; ?>
            <span style="color: #64748b; padding: 0 1rem">Trang <?= $page ?> / <?= $total_pages ?></span>
            <?php if ($page < $total_pages): ?>
                <a href="?tab=finances&sub=refunds&<?= $filter_params ?>&page=<?= $page + 1 ?>"
                    class="btn btn-sm btn-secondary">
                    Sau <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>