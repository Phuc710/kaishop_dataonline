<?php
// User Balance History Tab
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
// User Balance History Tab - View all balance changes per user

$selected_user_id = $_GET['user_id'] ?? '';
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? '';
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

// Build WHERE clause
$where = [];
$params = [];

if ($selected_user_id) {
    $where[] = "bt.user_id = ?";
    $params[] = $selected_user_id;
}

if ($search) {
    // Search by note, username, email, or user ID
    $where[] = "(bt.note LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($type_filter) {
    $where[] = "bt.type = ?";
    $params[] = $type_filter;
}

if ($date_from) {
    $where[] = "DATE(bt.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where[] = "DATE(bt.created_at) <= ?";
    $params[] = $date_to;
}

if ($date_filter == 'today') {
    $where[] = "DATE(bt.created_at) = CURDATE()";
} elseif ($date_filter == 'week') {
    $where[] = "YEARWEEK(bt.created_at, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($date_filter == 'month') {
    $where[] = "MONTH(bt.created_at) = MONTH(CURDATE()) AND YEAR(bt.created_at) = YEAR(CURDATE())";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$count_query = "SELECT COUNT(*) FROM balance_transactions bt 
                LEFT JOIN users u ON bt.user_id = u.id 
                $where_sql";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

// Get transactions
$query = "SELECT bt.*, u.username, u.email 
          FROM balance_transactions bt
          LEFT JOIN users u ON bt.user_id = u.id
          $where_sql
          ORDER BY bt.created_at DESC
          LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Type labels
$type_labels = [
    'deposit' => ['label' => 'Nạp tiền', 'color' => 'success', 'icon' => 'fa-plus-circle'],
    'withdraw' => ['label' => 'Rút tiền', 'color' => 'warning', 'icon' => 'fa-minus-circle'],
    'purchase' => ['label' => 'Mua hàng', 'color' => 'info', 'icon' => 'fa-shopping-cart'],
    'refund' => ['label' => 'Hoàn tiền', 'color' => 'primary', 'icon' => 'fa-undo'],
    'admin_add' => ['label' => 'Admin cộng', 'color' => 'success', 'icon' => 'fa-user-shield'],
    'admin_deduct' => ['label' => 'Admin trừ', 'color' => 'danger', 'icon' => 'fa-user-shield']
];
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-money-bill-wave"></i> Biến Động Số Dư</h1>
        <p>Xem lịch sử thay đổi số dư của người dùng</p>
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
                <div class="stat-label">Tổng Giao Dịch</div>
            </div>
            <div class="stat-icon primary"><i class="fas fa-list"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value">
                    <?php
                    $total_added = $pdo->query("SELECT COUNT(*) FROM balance_transactions WHERE type IN ('admin_add', 'refund')")->fetchColumn();
                    echo number_format($total_added);
                    ?>
                </div>
                <div class="stat-label">Giao Dịch Cộng</div>
            </div>
            <div class="stat-icon success"><i class="fas fa-plus"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value">
                    <?php
                    $total_deducted = $pdo->query("SELECT COUNT(*) FROM balance_transactions WHERE type IN ('purchase', 'withdraw', 'admin_deduct')")->fetchColumn();
                    echo number_format($total_deducted);
                    ?>
                </div>
                <div class="stat-label">Giao Dịch Trừ</div>
            </div>
            <div class="stat-icon danger"><i class="fas fa-minus"></i></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card">
    <form method="GET" class="form-grid">
        <input type="hidden" name="tab" value="user_balance_history">

        <div class="form-group">
            <label><i class="fas fa-user"></i> Chọn Người Dùng</label>
            <select name="user_id" class="form-control" id="userSelect">
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
            <label><i class="fas fa-filter"></i> Loại Giao Dịch</label>
            <select name="type" class="form-control">
                <option value="">Tất cả</option>
                <option value="purchase" <?= $type_filter == 'purchase' ? 'selected' : '' ?>>Mua hàng</option>
                <option value="refund" <?= $type_filter == 'refund' ? 'selected' : '' ?>>Hoàn tiền</option>
                <option value="admin_add" <?= $type_filter == 'admin_add' ? 'selected' : '' ?>>Admin cộng</option>
                <option value="admin_deduct" <?= $type_filter == 'admin_deduct' ? 'selected' : '' ?>>Admin trừ</option>
                <option value="withdraw" <?= $type_filter == 'withdraw' ? 'selected' : '' ?>>Rút tiền</option>
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
                placeholder="Ghi chú, username, email, User ID...">
        </div>

        <div class="form-group" style="display:flex;align-items:flex-end;gap:0.5rem">
            <button type="submit" class="btn btn-primary" style="flex:1">
                <i class="fas fa-filter"></i> Lọc
            </button>
            <a href="?tab=user_balance_history" class="btn btn-secondary">
                <i class="fas fa-redo"></i>
            </a>
        </div>
    </form>
</div>

<!-- Transactions Table -->
<div class="card">
    <div class="table-wrapper" style="overflow-x: auto;">
        <table>
            <thead>
                <tr>

                    <th>Username</th>
                    <th>Số Dư Trước</th>
                    <th>Số Tiền Thay Đổi</th>
                    <th>Số Dư Sau</th>
                    <th>Loại</th>
                    <th>Ghi Chú</th>
                    <th>Thời Gian</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:3rem;color:#64748b">
                            <i class="fas fa-inbox" style="font-size:3rem;margin-bottom:1rem"></i>
                            <p>Không tìm thấy giao dịch nào</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $idx => $trans): ?>
                        <?php
                        $type_info = $type_labels[$trans['type']] ?? ['label' => $trans['type'], 'color' => 'secondary', 'icon' => 'fa-question'];
                        $is_addition = in_array($trans['type'], ['deposit', 'admin_add', 'refund']);
                        ?>
                        <tr>

                            <td>
                                <strong style="color:#f8fafc">
                                    <?= e($trans['username']) ?>
                                </strong><br>
                                <small style="color:#64748b">
                                    <?= e($trans['email']) ?>
                                </small>
                            </td>
                            <td>
                                <div style="font-weight:600;color:#94a3b8">
                                    <?= number_format($trans['balance_before']) ?>
                                    <?= $trans['currency'] ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:700;font-size:1.1rem;color:<?= $is_addition ? '#10b981' : '#ef4444' ?>">
                                    <?= $is_addition ? '+' : '-' ?>
                                    <?= number_format(abs($trans['amount'])) ?>
                                    <?= $trans['currency'] ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:600;color:#10b981">
                                    <?= number_format($trans['balance_after']) ?>
                                    <?= $trans['currency'] ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-<?= $type_info['color'] ?>">
                                    <i class="fas <?= $type_info['icon'] ?>"></i>
                                    <?= $type_info['label'] ?>
                                </span>
                            </td>
                            <td>
                                <small style="color:#94a3b8">
                                    <?= e($trans['note'] ?? '-') ?>
                                </small>
                            </td>
                            <td>
                                <small style="color:#64748b">
                                    <?= date('d/m/Y H:i', strtotime($trans['created_at'])) ?>
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
                href="?tab=user_balance_history&page=<?= $page - 1 ?>&user_id=<?= $selected_user_id ?>&type=<?= $type_filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&search=<?= urlencode($search) ?>">
                <i class="fas fa-chevron-left"></i>
            </a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
            <a href="?tab=user_balance_history&page=<?= $i ?>&user_id=<?= $selected_user_id ?>&type=<?= $type_filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&search=<?= urlencode($search) ?>"
                class="<?= $i == $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a
                href="?tab=user_balance_history&page=<?= $page + 1 ?>&user_id=<?= $selected_user_id ?>&type=<?= $type_filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&search=<?= urlencode($search) ?>">
                <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>