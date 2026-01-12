<?php
// Orders Management Tab
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $order_id = $_POST['order_id'] ?? '';

    if ($action === 'update_status' && $order_id) {
        $new_status = $_POST['status'] ?? '';
        $stmt = $pdo->prepare("UPDATE orders SET status=? WHERE id=?");
        if ($stmt->execute([$new_status, $order_id])) {
            $success = 'Cập nhật trạng thái thành công!';
        }
    }
}

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$currency_filter = $_GET['currency'] ?? '';
$date_filter = $_GET['date_filter'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where = [];
$params = [];

if ($search) {
    $where[] = "(o.order_number LIKE ? OR u.username LIKE ? OR u.id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status_filter) {
    $where[] = "o.status = ?";
    $params[] = $status_filter;
}
if ($currency_filter) {
    $where[] = "o.currency = ?";
    $params[] = $currency_filter;
}
if ($date_filter == 'today') {
    $where[] = "DATE(o.created_at) = CURDATE()";
} elseif ($date_filter == 'week') {
    $where[] = "YEARWEEK(o.created_at, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($date_filter == 'month') {
    $where[] = "MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$count_query = "SELECT COUNT(*) FROM orders o LEFT JOIN users u ON o.user_id = u.id $where_sql";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

$query = "SELECT o.*, u.username, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.id $where_sql ORDER BY o.created_at DESC LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$completed_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='completed'")->fetchColumn();
$cancelled_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='cancelled'")->fetchColumn();
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-shopping-cart"></i> Quản Lý Đơn Hàng</h1>
        <p>Tổng: <?= $total ?> đơn hàng</p>
    </div>
</div>

<?php if ($success): ?>
    <script>
        if (window.notify) {
            notify.success('Thành công!', '<?= $success ?>');
        }
    </script>
<?php endif; ?>

<?php if ($error): ?>
    <script>
        if (window.notify) {
            notify.error('Lỗi!', '<?= $error ?>');
        }
    </script>
<?php endif; ?>

<div class="stats-grid" style="gap:1.5rem">
    <div class="stat-card"
        style="background:linear-gradient(135deg,rgba(139,92,246,0.15),rgba(109,40,217,0.05));border-left:4px solid #8b5cf6;box-shadow:0 4px 12px rgba(139,92,246,0.15)">
        <div class="stat-card-header">
            <div>
                <div class="stat-value" style="color:#8b5cf6;font-size:2rem;font-weight:700">
                    <?= number_format($total) ?>
                </div>
                <div class="stat-label" style="color:#f8fafc;font-weight:600">🛒 Tổng Đơn</div>
            </div>
            <div class="stat-icon"
                style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);box-shadow:0 4px 12px rgba(139,92,246,0.3)"><i
                    class="fas fa-shopping-bag"></i></div>
        </div>
    </div>
    <div class="stat-card"
        style="background:linear-gradient(135deg,rgba(16,185,129,0.15),rgba(5,150,105,0.05));border-left:4px solid #10b981;box-shadow:0 4px 12px rgba(16,185,129,0.15)">
        <div class="stat-card-header">
            <div>
                <div class="stat-value" style="color:#10b981;font-size:2rem;font-weight:700"><?= $completed_orders ?>
                </div>
                <div class="stat-label" style="color:#f8fafc;font-weight:600">✅ Hoàn Thành</div>
            </div>
            <div class="stat-icon"
                style="background:linear-gradient(135deg,#10b981,#059669);box-shadow:0 4px 12px rgba(16,185,129,0.3)"><i
                    class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="stat-card"
        style="background:linear-gradient(135deg,rgba(245,158,11,0.15),rgba(217,119,6,0.05));border-left:4px solid #f59e0b;box-shadow:0 4px 12px rgba(245,158,11,0.15)">
        <div class="stat-card-header">
            <div>
                <div class="stat-value" style="color:#f59e0b;font-size:2rem;font-weight:700"><?= $pending_orders ?>
                </div>
                <div class="stat-label" style="color:#f8fafc;font-weight:600">⏰ Đang Xử Lý</div>
            </div>
            <div class="stat-icon"
                style="background:linear-gradient(135deg,#f59e0b,#d97706);box-shadow:0 4px 12px rgba(245,158,11,0.3)"><i
                    class="fas fa-clock"></i></div>
        </div>
    </div>
    <div class="stat-card"
        style="background:linear-gradient(135deg,rgba(239,68,68,0.15),rgba(220,38,38,0.05));border-left:4px solid #ef4444;box-shadow:0 4px 12px rgba(239,68,68,0.15)">
        <div class="stat-card-header">
            <div>
                <div class="stat-value" style="color:#ef4444;font-size:2rem;font-weight:700"><?= $cancelled_orders ?>
                </div>
                <div class="stat-label" style="color:#f8fafc;font-weight:600">❌ Đã Hủy</div>
            </div>
            <div class="stat-icon"
                style="background:linear-gradient(135deg,#ef4444,#dc2626);box-shadow:0 4px 12px rgba(239,68,68,0.3)"><i
                    class="fas fa-times-circle"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <form method="GET" class="form-grid">
        <input type="hidden" name="tab" value="orders">
        <div class="form-group">
            <label><i class="fas fa-search"></i> Tìm kiếm</label>
            <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>"
                placeholder="Mã đơn, username, User ID...">
        </div>
        <div class="form-group">
            <label><i class="fas fa-info-circle"></i> Trạng thái</label>
            <select name="status" class="form-control">
                <option value="">Tất cả</option>
                <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                <option value="refunded" <?= $status_filter == 'refunded' ? 'selected' : '' ?>>Hoàn tiền</option>
            </select>
        </div>
        <div class="form-group">
            <label><i class="fas fa-money-bill"></i> Loại tiền</label>
            <select name="currency" class="form-control">
                <option value="">Tất cả</option>
                <option value="VND" <?= $currency_filter == 'VND' ? 'selected' : '' ?>>VND</option>
                <option value="USD" <?= $currency_filter == 'USD' ? 'selected' : '' ?>>USD</option>
            </select>
            </select>
        </div>
        <div class="form-group">
            <label><i class="fas fa-calendar-alt"></i> Thời gian</label>
            <select name="date_filter" class="form-control" onchange="this.form.submit()">
                <option value="all" <?= $date_filter == 'all' ? 'selected' : '' ?>>Tất cả</option>
                <option value="today" <?= $date_filter == 'today' ? 'selected' : '' ?>>Hôm nay</option>
                <option value="week" <?= $date_filter == 'week' ? 'selected' : '' ?>>Tuần này</option>
                <option value="month" <?= $date_filter == 'month' ? 'selected' : '' ?>>Tháng này</option>
            </select>
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end;gap:0.5rem">
            <button type="submit" class="btn btn-primary" style="flex:1"><i class="fas fa-filter"></i> Lọc</button>
            <a href="?tab=orders" class="btn btn-secondary"><i class="fas fa-redo"></i></a>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Mã Đơn</th>
                    <th>Khách Hàng</th>
                    <th>Tổng Tiền</th>
                    <th>Loại Tiền</th>
                    <th>Trạng Thái</th>
                    <th>Ngày Tạo</th>
                    <th>Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;padding:3rem;color:#64748b"><i class="fas fa-inbox"
                                style="font-size:3rem;margin-bottom:1rem"></i>
                            <p>Không có đơn hàng</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <strong style="color:#8b5cf6"><?= $order['order_number'] ?></strong>
                                <br><small style="color:#64748b">#<?= substr($order['id'], -8) ?></small>
                            </td>
                            <td>
                                <strong style="color:#f8fafc"><?= e($order['username']) ?></strong>
                                <br><small style="color:#64748b"><?= e($order['email']) ?></small>
                            </td>
                            <td>
                                <strong
                                    style="color:<?= in_array($order['status'], ['cancelled', 'refunded']) ? '#ef4444' : '#10b981' ?>;font-size:1.1rem;<?= in_array($order['status'], ['cancelled', 'refunded']) ? 'text-decoration:line-through;opacity:0.7;' : '' ?>">
                                    <?= $order['currency'] == 'VND' ? formatVND($order['total_amount_vnd']) : formatUSD($order['total_amount_usd']) ?>
                                </strong>
                            </td>
                            <td>
                                <span
                                    class="badge <?= $order['currency'] == 'VND' ? 'badge-primary' : 'badge-info' ?>"><?= $order['currency'] ?></span>
                            </td>
                            <td>
                                <?php
                                $status_badges = [
                                    'pending' => ['warning', 'Đang xử lý', '#f59e0b', 'rgba(245,158,11,0.15)'],
                                    'completed' => ['success', 'Hoàn thành', '#10b981', 'rgba(16,185,129,0.15)'],
                                    'cancelled' => ['danger', 'Đã hủy', '#ef4444', 'rgba(239,68,68,0.15)'],
                                    'refunded' => ['info', 'Hoàn tiền', '#06b6d4', 'rgba(6,182,212,0.15)']
                                ];
                                $badge = $status_badges[$order['status']] ?? ['primary', $order['status'], '#8b5cf6', 'rgba(139,92,246,0.15)'];
                                ?>
                                <span class="badge"
                                    style="background:<?= $badge[3] ?>;color:<?= $badge[2] ?>;font-weight:600;padding:0.4rem 0.8rem;border-radius:6px;text-transform:uppercase;font-size:0.75rem;letter-spacing:0.5px">
                                    <i
                                        class="fas fa-<?= $badge[0] === 'success' ? 'check-circle' : ($badge[0] === 'warning' ? 'clock' : ($badge[0] === 'danger' ? 'times-circle' : 'info-circle')) ?>"></i>
                                    <?= $badge[1] ?>
                                </span>
                            </td>
                            <td>
                                <div style="color:#f8fafc"><?= date('d/m/Y', strtotime($order['created_at'])) ?></div>
                                <small style="color:#64748b"><?= date('H:i', strtotime($order['created_at'])) ?></small>
                            </td>
                            <td>
                                <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
                                    <?php
                                    // Convert IDs to strings to prevent JavaScript precision loss
                                    $orderData = $order;
                                    $orderData['id'] = strval($order['id']);
                                    $orderData['user_id'] = strval($order['user_id']);
                                    if (isset($orderData['voucher_id'])) {
                                        $orderData['voucher_id'] = strval($orderData['voucher_id']);
                                    }
                                    ?>
                                    <button onclick='viewOrder(<?= json_encode($orderData) ?>)' class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($order['status'] == 'pending'): ?>
                                        <button onclick='updateStatus("<?= $order['id'] ?>", "completed")'
                                            class="btn btn-sm btn-success">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button onclick='updateStatus("<?= $order['id'] ?>", "cancelled")'
                                            class="btn btn-sm btn-danger">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a
                href="?tab=orders&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>&currency=<?= $currency_filter ?>"><i
                    class="fas fa-chevron-left"></i></a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
            <a href="?tab=orders&page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>&currency=<?= $currency_filter ?>"
                class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
            <a
                href="?tab=orders&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>&currency=<?= $currency_filter ?>"><i
                    class="fas fa-chevron-right"></i></a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div id="viewModal"
    style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.8);z-index:9999;padding:2rem;overflow-y:auto">
    <div
        style="max-width:800px;margin:0 auto;background:linear-gradient(135deg,#1e293b,#0f172a);padding:2rem;border-radius:16px;border:1px solid rgba(139,92,246,0.3)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
            <h2 style="color:#f8fafc"><i class="fas fa-file-invoice"></i> Chi Tiết Đơn Hàng</h2>
            <button onclick="closeViewModal()" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></button>
        </div>
        <div id="orderDetails"></div>
    </div>
</div>

<script src="<?= url('admin/assets/js/order-functions.js') ?>?v=<?= time() ?>"></script>