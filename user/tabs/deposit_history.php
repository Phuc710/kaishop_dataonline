<?php
/**
 * Lịch Sử Nạp Tiền - User Panel
 * Hiển thị lịch sử nạp tiền của user từ Sepay
 */

// Filters
$search = $_GET['search'] ?? '';
$date_filter = $_GET['date_filter'] ?? 'all';

$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where = ["user_id = ? AND type = 'deposit'"];
$params = [$user_id];

if ($search) {
    $where[] = "(note LIKE ? OR amount LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($date_filter == 'today') {
    $where[] = "DATE(created_at) = CURDATE()";
} elseif ($date_filter == 'week') {
    $where[] = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($date_filter == 'month') {
    $where[] = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

// Count total deposits (Global for Stats)
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) FROM balance_transactions 
    WHERE user_id = ? AND type = 'deposit'
");
$count_stmt->execute([$user_id]);
$total_global = $count_stmt->fetchColumn();

// Count for pagination (Filtered)
$count_filtered_stmt = $pdo->prepare("SELECT COUNT(*) FROM balance_transactions $where_sql");
$count_filtered_stmt->execute($params);
$total = $count_filtered_stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

// Get stats for this user (Global)
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_count,
        SUM(CASE WHEN currency = 'VND' THEN amount ELSE amount * ? END) as total_amount
    FROM balance_transactions 
    WHERE user_id = ? AND type = 'deposit'
");
$stats_stmt->execute([$exchange_rate, $user_id]);
$stats = $stats_stmt->fetch();

// Get deposit list (Filtered)
// Add exchange rate parameter at the beginning because it's used in the select clause
$query_params = array_merge([$exchange_rate], $params, [$per_page, $offset]);

$deposits_stmt = $pdo->prepare("
    SELECT bt.*,
        CASE WHEN bt.currency = 'VND' THEN bt.amount ELSE bt.amount * ? END as amount_vnd
    FROM balance_transactions bt
    $where_sql
    ORDER BY bt.created_at DESC
    LIMIT ? OFFSET ?
");
$deposits_stmt->execute($query_params);
$deposits = $deposits_stmt->fetchAll();
?>

<div class="page-header fade-in">
    <div>
        <h1><i class="fas fa-money-bill-wave"></i> Lịch Sử Nạp Tiền</h1>
        <p>Danh sách các giao dịch nạp tiền vào tài khoản</p>
    </div>
    <a href="<?= url('naptien') ?>" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.5rem;">
        <i class="fas fa-plus-circle"></i> Nạp Tiền
    </a>
</div>

<!-- Stats -->
<div class="stats-grid fade-in" style="gap:1.5rem; margin-bottom: 2rem;">
    <!-- Tổng Giao Dịch -->
    <div class="stat-card"
        style="background:linear-gradient(135deg,rgba(59,130,246,0.15),rgba(37,99,235,0.05));border-left:4px solid #3b82f6;box-shadow:0 4px 12px rgba(59,130,246,0.15)">
        <div class="stat-card-header">
            <div>
                <div class="stat-label" style="color:#f8fafc;font-weight:600;margin-bottom:0.5rem">🔢 Tổng Giao Dịch
                </div>
                <div class="stat-value" style="color:#3b82f6;font-size:1.8rem;font-weight:700">
                    <?= number_format($stats['total_count'] ?? 0) ?>
                </div>
            </div>
            <div class="stat-icon"
                style="background:linear-gradient(135deg,#3b82f6,#2563eb);box-shadow:0 4px 12px rgba(59,130,246,0.3)">
                <i class="fas fa-receipt"></i>
            </div>
        </div>
        <div class="stat-change" style="color:#3b82f6;font-weight:600">
            <i class="fas fa-info-circle"></i>
            <span>Số lần nạp thành công</span>
        </div>
    </div>

    <!-- Tổng Đã Nạp -->
    <div class="stat-card"
        style="background:linear-gradient(135deg,rgba(16,185,129,0.15),rgba(5,150,105,0.05));border-left:4px solid #10b981;box-shadow:0 4px 12px rgba(16,185,129,0.15)">
        <div class="stat-card-header">
            <div>
                <div class="stat-label" style="color:#f8fafc;font-weight:600;margin-bottom:0.5rem">💰 Tổng Đã Nạp</div>
                <div class="stat-value" style="color:#10b981;font-size:1.8rem;font-weight:700">
                    <?= formatVND($stats['total_amount'] ?? 0) ?>
                </div>
            </div>
            <div class="stat-icon"
                style="background:linear-gradient(135deg,#10b981,#059669);box-shadow:0 4px 12px rgba(16,185,129,0.3)">
                <i class="fas fa-coins"></i>
            </div>
        </div>
        <div class="stat-change up" style="color:#10b981;font-weight:600">
            <i class="fas fa-arrow-up"></i>
            <span>Tổng tiền đã nạp vào</span>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="card fade-in">
    <form method="GET" class="filter-form">
        <input type="hidden" name="tab" value="deposit_history">

        <div class="filter-group">
            <label><i class="fas fa-calendar-alt"></i> Thời Gian</label>
            <select name="date_filter" class="form-control" onchange="this.form.submit()">
                <option value="all" <?= $date_filter == 'all' ? 'selected' : '' ?>>Tất cả</option>
                <option value="today" <?= $date_filter == 'today' ? 'selected' : '' ?>>Hôm nay</option>
                <option value="week" <?= $date_filter == 'week' ? 'selected' : '' ?>>Tuần này</option>
                <option value="month" <?= $date_filter == 'month' ? 'selected' : '' ?>>Tháng này</option>
            </select>
        </div>

        <div class="filter-group flex-grow">
            <label><i class="fas fa-search"></i> Tìm Kiếm</label>
            <div class="search-input-wrapper">
                <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>"
                    placeholder="Tìm theo nội dung, số tiền...">
                <button type="submit" class="btn btn-primary search-btn">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Deposit List -->
<div class="card fade-in" style="padding: 0; overflow: hidden;">
    <div class="card-header" style="border-bottom: 1px solid var(--border); padding: 1.5rem;">
        <h3 class="card-title" style="display:flex;align-items:center;gap:0.5rem;margin:0;font-size:1.2rem">
            <i class="fas fa-list"></i> Danh Sách Giao Dịch
        </h3>
    </div>

    <?php if (empty($deposits)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-inbox"></i>
            </div>
            <h3>Chưa Có Giao Dịch</h3>
            <p>Không tìm thấy giao dịch nào phù hợp</p>
            <?php if ($search || $date_filter != 'all'): ?>
                <a href="?tab=deposit_history" class="btn btn-secondary" style="margin-top:1rem">
                    <i class="fas fa-redo"></i> Xóa Bộ Lọc
                </a>
            <?php else: ?>
                <a href="<?= url('naptien') ?>" class="btn btn-primary" style="margin-top:1rem">
                    <i class="fas fa-plus-circle"></i> Nạp Tiền Ngay
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="admin-style-table">
                <thead>
                    <tr>
                        <th class="text-center">Thời gian</th>
                        <th class="text-center">Ngân hàng</th>
                        <th class="text-center">Nội dung chuyển khoản</th>
                        <th class="text-center">Số tiền nạp</th>
                        <th class="text-center">Thực nhận</th>
                        <th class="text-center">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deposits as $deposit): ?>
                        <tr>
                            <td class="text-center">
                                <div style="color:var(--text-main);font-weight:700;font-size:0.95rem">
                                    <?= date('d/m/Y', strtotime($deposit['created_at'])) ?>
                                </div>
                                <small style="color:var(--text-muted);font-weight:600">
                                    <?= date('H:i:s', strtotime($deposit['created_at'])) ?>
                                </small>
                            </td>
                            <td class="text-center">
                                <?php
                                // Extract bank name from note (format: "... | Bank: BANK_NAME")
                                $bank_name = 'MB Bank'; // Default
                                if (isset($deposit['note']) && strpos($deposit['note'], '| Bank: ') !== false) {
                                    $parts = explode('| Bank: ', $deposit['note']);
                                    if (isset($parts[1])) {
                                        $bank_name = trim($parts[1]);
                                    }
                                }
                                ?>
                                <span class="badge badge-primary"
                                    style="font-weight:700"><?= htmlspecialchars($bank_name) ?></span>
                            </td>
                            <td class="text-center">
                                <?php
                                // Extract only the transaction code part (before | Bank:)
                                $display_note = $deposit['note'] ?? '-';
                                if (strpos($display_note, '| Bank: ') !== false) {
                                    $display_note = explode('| Bank: ', $display_note)[0];
                                }
                                ?>
                                <div style="color:var(--text-main);font-weight:700" title="<?= e($deposit['note'] ?? '') ?>">
                                    <?= e($display_note) ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <span style="color:var(--text-main);font-weight:700">
                                    <?= formatVND($deposit['amount_vnd']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span style="color:#10b981;font-weight:700">
                                    <?= formatVND($deposit['amount_vnd']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="status-badge status-success">Thành công</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div class="pagination-container fade-in" style="margin-top: 2rem;">
        <div class="pagination">
            <?php
            $query_params = "tab=deposit_history&search=" . urlencode($search) . "&date_filter=" . urlencode($date_filter);
            ?>

            <?php if ($page > 1): ?>
                <a href="?<?= $query_params ?>&page=<?= $page - 1 ?>" class="page-btn">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php else: ?>
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);

            if ($start > 1): ?>
                <a href="?<?= $query_params ?>&page=1" class="page-btn">1</a>
                <?php if ($start > 2): ?> <span class="page-dots">...</span> <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start; $i <= $end; $i++): ?>
                <a href="?<?= $query_params ?>&page=<?= $i ?>" class="page-btn <?= $i == $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($end < $total_pages): ?>
                <?php if ($end < $total_pages - 1): ?> <span class="page-dots">...</span> <?php endif; ?>
                <a href="?<?= $query_params ?>&page=<?= $total_pages ?>" class="page-btn"><?= $total_pages ?></a>
            <?php endif; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?<?= $query_params ?>&page=<?= $page + 1 ?>" class="page-btn">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            <?php endif; ?>
        </div>
        <div class="pagination-info">
            <span>Trang <?= $page ?> / <?= $total_pages ?></span>
            <span class="separator">•</span>
            <span>Tổng <?= $total ?> giao dịch</span>
        </div>
    </div>
<?php endif; ?>

<style>
    /* Filter Styles */
    .filter-form {
        display: flex;
        gap: 1.5rem;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .filter-group label {
        color: var(--text-muted);
        font-size: 0.85rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .flex-grow {
        flex: 1;
        min-width: 200px;
    }

    .search-input-wrapper {
        display: flex;
        gap: 0.5rem;
    }

    .search-btn {
        padding: 0 1rem;
        border-radius: var(--radius-input);
    }

    /* Admin Table Style Replication (Cyan Theme) */
    .admin-style-table {
        width: 100%;
        border-collapse: collapse;
    }

    .admin-style-table thead {
        background: rgba(6, 182, 212, 0.1);
    }

    .admin-style-table th {
        color: var(--text-main);
        padding: 1rem;
        text-align: center;
        font-weight: 700;
        border-bottom: 1px solid var(--border);
        white-space: nowrap;
    }

    .admin-style-table th.text-center {
        text-align: center;
    }

    .admin-style-table td {
        padding: 1rem;
        border-bottom: 1px solid rgba(6, 182, 212, 0.05);
        color: var(--text-muted);
    }

    .admin-style-table td.text-center {
        text-align: center;
    }

    /* Status Badge Styling */
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.5rem;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.875rem;
        transition: all 0.3s ease;
    }

    .status-badge.status-success {
        background: linear-gradient(135deg, #02bf80d7, #059669);
        color: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.4);
        border-radius: 99px;
    }

    .admin-style-table tr:hover {
        background: rgba(6, 182, 212, 0.05);
    }

    .admin-style-table tr:last-child td {
        border-bottom: none;
    }

    [data-theme="light"] .admin-style-table thead {
        background: #f8fafc;
    }

    [data-theme="light"] .admin-style-table th {
        color: #1e293b;
        border-bottom: 1px solid #e2e8f0;
        font-weight: 700;
    }

    [data-theme="light"] .admin-style-table tr:hover {
        background: #f8fafc;
    }

    [data-theme="light"] .admin-style-table td {
        color: #334155;
        border-bottom: 1px solid #e2e8f0;
    }

    /* Keep pagination styles */
    .pagination-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
        padding: 1.5rem;
        background: var(--card-bg);
        border-radius: 16px;
        border: 1px solid var(--border);
    }

    .pagination {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        height: 40px;
        padding: 0 12px;
        background: rgba(30, 41, 59, 0.6);
        border: 1.5px solid rgba(139, 92, 246, 0.2);
        border-radius: 10px;
        color: #cbd5e1;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .page-btn:hover:not(.disabled):not(.active) {
        background: rgba(139, 92, 246, 0.15);
        border-color: rgba(139, 92, 246, 0.4);
        color: #a78bfa;
        transform: translateY(-2px);
    }

    .page-btn.active {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        border-color: #8b5cf6;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
        transform: translateY(-2px);
    }

    .page-btn.disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    .page-dots {
        color: #64748b;
        font-weight: 700;
        padding: 0 0.5rem;
    }

    .pagination-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #94a3b8;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .pagination-info .separator {
        color: #475569;
    }

    /* Light Theme Pagination */
    [data-theme="light"] .pagination-container {
        background: #ffffff;
        border-color: #e2e8f0;
    }

    [data-theme="light"] .page-btn {
        background: #ffffff;
        border-color: #e2e8f0;
        color: #475569;
    }

    [data-theme="light"] .page-btn:hover:not(.disabled):not(.active) {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #0f172a;
    }
</style>