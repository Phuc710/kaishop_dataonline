<?php
// Users Management Tab

/**
 * Get exchange rate from database
 */
function getExchangeRate()
{
    global $pdo;
    static $rate = null;

    if ($rate === null) {
        try {
            $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='exchange_rate'");
            $rate = floatval($stmt->fetchColumn() ?? 25000);
        } catch (Exception $e) {
            $rate = 25000;
        }
    }

    return $rate;
}

$success = $error = '';

// Get success message from session (after redirect)
if (isset($_SESSION['balance_success'])) {
    $success = $_SESSION['balance_success'];
    unset($_SESSION['balance_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? '';

    if ($action === 'update_balance' && $user_id) {
        $amount = floatval($_POST['amount'] ?? 0);
        $currency = $_POST['currency'] ?? 'VND';
        $note = trim($_POST['note'] ?? '');
        $type = $_POST['type'] ?? 'admin_add';

        // Validation
        if ($amount <= 0) {
            $error = "Số tiền phải lớn hơn 0!";
        } elseif (empty($note)) {
            $error = "Vui lòng nhập ghi chú/lý do!";
        } else {
            try {
                // Get exchange rate from settings
                $exchange_rate = floatval($pdo->query("SELECT setting_value FROM settings WHERE setting_key='exchange_rate'")->fetchColumn() ?? 25000);

                // Get current balance
                $user = $pdo->prepare("SELECT balance_vnd, username FROM users WHERE id=?");
                $user->execute([$user_id]);
                $userData = $user->fetch();

                if (!$userData) {
                    $error = 'Không tìm thấy người dùng!';
                } else {
                    $old_balance_vnd = $userData['balance_vnd'];

                    // Convert to VND if currency is USD (1 ví duy nhất là VND)
                    $amount_vnd = ($currency == 'USD') ? ($amount * $exchange_rate) : $amount;

                    // Validate VND must be multiple of 1,000
                    if (fmod($amount_vnd, 1000) != 0) {
                        $error = "Số tiền VND phải chia hết cho 1,000! (Ví dụ: 10,000 hoặc 23,300)";
                    }

                    // Calculate new balance
                    if ($type == 'admin_add') {
                        $new_balance_vnd = $old_balance_vnd + $amount_vnd;
                    } else {
                        // Check if user has enough balance to deduct
                        if ($old_balance_vnd < $amount_vnd) {
                            $error = "Không đủ số dư! Số dư hiện tại: " . number_format($old_balance_vnd) . "đ, số tiền trừ: " . number_format($amount_vnd) . "đ";
                        } else {
                            $new_balance_vnd = $old_balance_vnd - $amount_vnd;
                        }
                    }

                    if (!$error) {
                        // Update balance_vnd only (1 wallet system)
                        $stmt = $pdo->prepare("UPDATE users SET balance_vnd=? WHERE id=?");
                        $stmt->execute([$new_balance_vnd, $user_id]);

                        // Log transaction with original currency
                        logBalanceTransaction($user_id, $type, $currency, $amount, $old_balance_vnd, $new_balance_vnd, $note);

                        // Log admin action
                        $action_text = $type == 'admin_add' ? 'Cộng' : 'Trừ';
                        $display_amount = $currency == 'USD' ? ('$' . number_format($amount, 2)) : (number_format($amount) . ' VND');
                        logActivity('admin_action', 'Update User Balance', "Admin {$action_text} {$display_amount} cho {$userData['username']} | Note: {$note}", number_format($old_balance_vnd) . "đ", number_format($new_balance_vnd) . "đ", $user_id);

                        $success = "Cập nhật số dư thành công: {$action_text} {$display_amount}";

                        // Redirect để tránh form resubmission
                        $_SESSION['balance_success'] = $success;
                        header("Location: ?tab=users");
                        exit;
                    }
                }
            } catch (Exception $e) {
                $error = "Lỗi: " . $e->getMessage();
            }
        }
    } elseif ($action === 'change_role' && $user_id) {
        $role = $_POST['role'] ?? 'user';
        $username = $_POST['username'] ?? '';
        $stmt = $pdo->prepare("UPDATE users SET role=? WHERE id=?");
        if ($stmt->execute([$role, $user_id])) {
            $success = 'Thay đổi quyền thành công!';
            $_SESSION['balance_success'] = $success;
            header("Location: ?tab=users");
            exit;
        }
    } elseif ($action === 'toggle_status' && $user_id) {
        $is_active = intval($_POST['is_active'] ?? 1);
        $stmt = $pdo->prepare("UPDATE users SET is_active=? WHERE id=?");
        if ($stmt->execute([$is_active, $user_id])) {
            $success = $is_active ? 'Kích hoạt thành công!' : 'Khóa tài khoản thành công!';
            $_SESSION['balance_success'] = $success;
            header("Location: ?tab=users");
            exit;
        }
    }
}

$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date_filter'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where = [];
$params = [];

if ($search) {
    $where[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ? OR id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($role_filter) {
    $where[] = "role = ?";
    $params[] = $role_filter;
}
if ($status_filter) {
    $where[] = "is_active = ?";
    $params[] = $status_filter == 'active' ? 1 : 0;
}
if ($date_filter == 'today') {
    $where[] = "DATE(u.created_at) = CURDATE()";
} elseif ($date_filter == 'week') {
    $where[] = "YEARWEEK(u.created_at, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($date_filter == 'month') {
    $where[] = "MONTH(u.created_at) = MONTH(CURDATE()) AND YEAR(u.created_at) = YEAR(CURDATE())";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$count_query = "SELECT COUNT(*) FROM users $where_sql";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as total_orders,
          (SELECT SUM(total_amount_vnd) FROM orders WHERE user_id = u.id AND status = 'completed') as total_spent_vnd
          FROM users u $where_sql ORDER BY u.created_at DESC LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-users"></i> Quản Lý Người Dùng</h1>
        <p>Tổng: <?= $total ?> người dùng</p>
    </div>
</div>

<?php if ($success): ?>
    <script>
        if (window.notify) {
            notify.success('<?= addslashes($success) ?>');
        }
    </script>
<?php endif; ?>

<?php if ($error): ?>
    <script>
        if (window.notify) {
            notify.error('<?= addslashes($error) ?>');
        }
    </script>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value"><?= $total ?></div>
                <div class="stat-label">Tổng Người Dùng</div>
            </div>
            <div class="stat-icon primary"><i class="fas fa-users"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value">
                    <?= $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn() ?>
                </div>
                <div class="stat-label">Admin</div>
            </div>
            <div class="stat-icon warning"><i class="fas fa-user-shield"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value">
                    <?= $pdo->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn() ?>
                </div>
                <div class="stat-label">Hoạt Động</div>
            </div>
            <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value">
                    <?= $pdo->query("SELECT COUNT(*) FROM users WHERE is_active=0")->fetchColumn() ?>
                </div>
                <div class="stat-label">Bị Khóa</div>
            </div>
            <div class="stat-icon danger"><i class="fas fa-ban"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <form method="GET" class="form-grid">
        <input type="hidden" name="tab" value="users">
        <div class="form-group">
            <label><i class="fas fa-search"></i> Tìm kiếm</label>
            <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>"
                placeholder="Username, email, tên, User ID...">
        </div>
        <div class="form-group">
            <label><i class="fas fa-user-tag"></i> Vai trò</label>
            <select name="role" class="form-control">
                <option value="">Tất cả</option>
                <option value="user" <?= $role_filter == 'user' ? 'selected' : '' ?>>User</option>
                <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>
        <div class="form-group">
            <label><i class="fas fa-check"></i> Trạng thái</label>
            <select name="status" class="form-control">
                <option value="">Tất cả</option>
                <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Hoạt động</option>
                <option value="inactive" <?= $status_filter == 'inactive' ? 'selected' : '' ?>>Bị khóa</option>
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
            <a href="?tab=users" class="btn btn-secondary"><i class="fas fa-redo"></i></a>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Người dùng</th>
                    <th>Email</th>
                    <th>Số dư</th>
                    <th>Vai trò</th>
                    <th>Đơn hàng</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:3rem;color:#64748b"><i class="fas fa-users"
                                style="font-size:3rem;margin-bottom:1rem"></i>
                            <p>Không tìm thấy người dùng</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><small>#<?= substr($user['id'], -8) ?></small></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:0.75rem">
                                    <img src="<?= getUserAvatar($user) ?>" alt="Avatar" class="avatar"
                                        style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                    <div>
                                        <strong style="color:#f8fafc"><?= e($user['username']) ?></strong>
                                        <br><small style="color:#64748b"><?= e($user['full_name'] ?? '') ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($user['email']) ?></td>
                            <td>
                                <div style="font-weight:700;color:#10b981;font-size:1.05rem">
                                    <?= formatVND($user['balance_vnd']) ?>
                                </div>
                                <small style="color:#64748b;margin-top:0.25rem;display:block">
                                    ≈ $<?= number_format($user['balance_vnd'] / getExchangeRate(), 2) ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($user['role'] == 'admin'): ?>
                                    <span class="badge badge-warning"><i class="fas fa-crown"></i> Admin</span>
                                <?php else: ?>
                                    <span class="badge badge-info">User</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color:#f8fafc"><?= $user['total_orders'] ?? 0 ?></strong> đơn
                                <br><small style="color:#64748b"><?= formatVND($user['total_spent_vnd'] ?? 0) ?></small>
                            </td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><i class="fas fa-ban"></i> Locked</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
                                    <button class="btn btn-sm btn-primary" data-user-id="<?= $user['id'] ?>"
                                        data-username="<?= htmlspecialchars($user['username']) ?>"
                                        data-balance="<?= $user['balance_vnd'] ?>"
                                        onclick="editBalance({id: '<?= $user['id'] ?>', username: '<?= addslashes($user['username']) ?>', balance_vnd: <?= $user['balance_vnd'] ?>})">
                                        <i class="fas fa-wallet"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning"
                                        onclick="changeRole('<?= $user['id'] ?>', '<?= $user['role'] ?>')">
                                        <i class="fas fa-user-tag"></i>
                                    </button>
                                    <button class="btn btn-sm btn-info" onclick="viewUserDetails('<?= $user['id'] ?>')">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger"
                                        onclick="instantBanUser('<?= $user['id'] ?>', '<?= addslashes($user['username']) ?>')">
                                        <i class="fas fa-hammer"></i>
                                    </button>
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
                href="?tab=users&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&role=<?= $role_filter ?>&status=<?= $status_filter ?>"><i
                    class="fas fa-chevron-left"></i></a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
            <a href="?tab=users&page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= $role_filter ?>&status=<?= $status_filter ?>"
                class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
            <a
                href="?tab=users&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&role=<?= $role_filter ?>&status=<?= $status_filter ?>"><i
                    class="fas fa-chevron-right"></i></a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Balance Modal -->
<div id="balanceModal"
    style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.8);z-index:9999;padding:2rem;overflow-y:auto">
    <div
        style="max-width:600px;margin:2rem auto;background:linear-gradient(135deg,#1e293b,#0f172a);padding:2rem;border-radius:16px;border:1px solid rgba(139,92,246,0.3)">
        <h2 style="color:#f8fafc;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem">
            <i class="fas fa-wallet"></i> Cập Nhật Số Dư
        </h2>
        <form method="POST" id="balanceForm">
            <input type="hidden" name="action" value="update_balance">
            <input type="hidden" name="user_id" id="balance-user-id">

            <div class="balance-info-grid"
                style="background:rgba(139,92,246,0.1);padding:1.5rem;border-radius:12px;margin-bottom:1.5rem;border:1px solid rgba(139,92,246,0.2);gap:2rem">
                <!-- Left: User Info -->
                <div>
                    <div style="color:#94a3b8;font-size:0.9rem;margin-bottom:0.5rem">Người dùng:</div>
                    <div style="color:#f8fafc;font-weight:600;font-size:1.2rem" id="balance-username"></div>
                    <div style="color:#64748b;font-size:0.85rem;margin-top:0.25rem" id="balance-user-id-display"></div>
                </div>
                <!-- Right: Current Balance -->
                <div style="text-align:right">
                    <div style="color:#94a3b8;font-size:0.9rem;margin-bottom:0.5rem">Số dư hiện tại:</div>
                    <div style="color:#10b981;font-weight:700;font-size:1.1rem" id="balance-current"></div>
                </div>
            </div>

            <!-- Transaction Type -->
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:0.5rem;color:#f8fafc;margin-bottom:0.75rem">
                    <i class="fas fa-coins"></i> Loại Giao Dịch
                </label>
                <div style="display:flex;gap:1rem">
                    <label class="transaction-type-option" data-type="add"
                        style="flex:1;background:rgba(16,185,129,0.1);border:2px solid rgba(16,185,129,0.3);padding:1rem;border-radius:8px;cursor:pointer;transition:all 0.3s;user-select:none">
                        <input type="radio" name="type" value="admin_add" required checked style="display:none">
                        <div style="display:flex;align-items:center;gap:0.75rem">
                            <i class="fas fa-plus-circle" style="font-size:1.5rem;color:#10b981"></i>
                            <div>
                                <div style="color:#f8fafc;font-weight:600">Cộng Tiền</div>
                                <small style="color:#64748b">Deposit</small>
                            </div>
                        </div>
                    </label>
                    <label class="transaction-type-option" data-type="deduct"
                        style="flex:1;background:rgba(239,68,68,0.1);border:2px solid rgba(239,68,68,0.3);padding:1rem;border-radius:8px;cursor:pointer;transition:all 0.3s;user-select:none">
                        <input type="radio" name="type" value="admin_deduct" required style="display:none">
                        <div style="display:flex;align-items:center;gap:0.75rem">
                            <i class="fas fa-minus-circle" style="font-size:1.5rem;color:#ef4444"></i>
                            <div>
                                <div style="color:#f8fafc;font-weight:600">Trừ Tiền</div>
                                <small style="color:#64748b">Deduct</small>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Currency Selection (VND or USD, auto-convert to VND) -->
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:0.5rem;color:#f8fafc;margin-bottom:0.75rem">
                    <i class="fas fa-money-bill"></i> Đơn Vị Nhập (Tự động chuyển đổi)
                </label>
                <div style="display:flex;gap:1rem">
                    <label class="currency-select-option" data-currency="VND"
                        style="flex:1;background:rgba(139,92,246,0.1);border:2px solid rgba(139,92,246,0.3);padding:1rem;border-radius:8px;cursor:pointer;transition:all 0.3s;user-select:none">
                        <input type="radio" name="currency" value="VND" required checked style="display:none">
                        <div style="display:flex;align-items:center;gap:0.75rem">
                            <i class="fas fa-dong-sign" style="font-size:1.5rem;color:#8b5cf6"></i>
                            <div>
                                <div style="color:#f8fafc;font-weight:600">VND</div>
                                <small style="color:#64748b">Việt Nam Đồng</small>
                            </div>
                        </div>
                    </label>
                    <label class="currency-select-option" data-currency="USD"
                        style="flex:1;background:rgba(59,130,246,0.1);border:2px solid rgba(59,130,246,0.3);padding:1rem;border-radius:8px;cursor:pointer;transition:all 0.3s;user-select:none">
                        <input type="radio" name="currency" value="USD" required style="display:none">
                        <div style="display:flex;align-items:center;gap:0.75rem">
                            <i class="fas fa-dollar-sign" style="font-size:1.5rem;color:#3b82f6"></i>
                            <div>
                                <div style="color:#f8fafc;font-weight:600">USD</div>
                                <small style="color:#64748b">US Dollar</small>
                            </div>
                        </div>
                    </label>
                </div>
                <small style="color:#64748b;display:block;margin-top:0.5rem">
                    <i class="fas fa-info-circle"></i> Hệ thống 1 ví VND duy nhất. USD sẽ tự động convert sang VND.
                </small>
            </div>

            <!-- Amount Input -->
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:0.5rem;color:#f8fafc;margin-bottom:0.5rem">
                    <i class="fas fa-money-bill-wave"></i> Số Tiền
                </label>
                <input type="number" name="amount" id="amount" class="form-control" min="1" step="any"
                    placeholder="VD: 10000 (VND) hoặc 10 (USD)" required
                    style="background:#0f172a;color:#f8fafc;border:1px solid rgba(139,92,246,0.3);padding:1rem;border-radius:8px;width:100%;font-size:1.2rem;font-weight:600">
                <small style="color:#64748b;display:block;margin-top:0.5rem" id="amount-hint">VND: Phải chia hết cho
                    1,000đ | USD: Số bất kỳ (auto convert)</small>
            </div>

            <div class="form-group">
                <label style="display:flex;align-items:center;gap:0.5rem;color:#f8fafc;margin-bottom:0.5rem">
                    <i class="fas fa-sticky-note"></i> Ghi Chú / Lý Do *
                </label>
                <textarea name="note" class="form-control" rows="3" required
                    placeholder="VD: Khách nạp tiền bằng bank nhưng hệ thống chưa cập nhật..."
                    style="background:#0f172a;color:#f8fafc;border:1px solid rgba(139,92,246,0.3);padding:0.75rem;border-radius:8px;width:100%;resize:vertical"></textarea>
                <small style="color:#64748b;display:block;margin-top:0.25rem">Ghi rõ lý do để theo dõi nhật ký</small>
            </div>

            <div style="display:flex;gap:1rem;margin-top:1.5rem">
                <button type="submit" class="btn btn-primary" style="flex:1" id="submitBalanceBtn">
                    <i class="fas fa-save"></i> Cập Nhật
                </button>
                <button type="button" onclick="closeBalanceModal()" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Hủy
                </button>
            </div>
        </form>
    </div>
</div>


<style>
    .transaction-type-option,
    .currency-select-option {
        position: relative;
    }

    .transaction-type-option:hover,
    .currency-select-option:hover {
        transform: translateY(-2px);
    }

    .transaction-type-option:has(input:checked),
    .currency-select-option:has(input:checked) {
        border-width: 3px !important;
        transform: scale(1.05);
    }

    /* Color-specific active states */
    .transaction-type-option[data-type="add"]:has(input:checked) {
        border-color: #10b981 !important;
        box-shadow: 0 0 20px rgba(16, 185, 129, 0.4) !important;
    }

    .transaction-type-option[data-type="deduct"]:has(input:checked) {
        border-color: #ef4444 !important;
        box-shadow: 0 0 20px rgba(239, 68, 68, 0.4) !important;
    }

    .currency-select-option[data-currency="VND"]:has(input:checked) {
        border-color: #8b5cf6 !important;
        box-shadow: 0 0 20px rgba(139, 92, 246, 0.4) !important;
    }

    .currency-select-option[data-currency="USD"]:has(input:checked) {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 20px rgba(59, 130, 246, 0.4) !important;
    }

    /* Animation */
    .transaction-type-option,
    .currency-select-option {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
</style>

<!-- User Details Modal -->
<div id="userDetailsModal"
    style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:99999;justify-content:center;align-items:center;padding:2rem">
    <div
        style="width:100%;max-width:1200px;max-height:90vh;overflow-y:auto;background:linear-gradient(135deg, #1e293b 0%, #0f172a 100%);border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,0.5);border:1px solid rgba(139,92,246,0.3);position:relative;z-index:100000;display:flex;flex-direction:column">
        <!-- Modal Header -->
        <div
            style="padding:1.5rem 2rem;border-bottom:1px solid rgba(148,163,184,0.2);display:flex;justify-content:space-between;align-items:center">
            <h2 style="margin:0;color:#f8fafc;display:flex;align-items:center;gap:0.75rem">
                <i class="fas fa-user-shield" style="color:#8b5cf6"></i>
                <span id="userDetailsUsername">User Details</span>
            </h2>
            <button onclick="closeUserDetails()"
                style="background:transparent;border:none;color:#94a3b8;font-size:1.5rem;cursor:pointer;padding:0.5rem;transition:color 0.3s;z-index:100001;position:relative">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Modal Content -->
        <div id="userDetailsContent" style="padding:2rem">
            <div style="text-align:center;padding:3rem;color:#94a3b8">
                <i class="fas fa-spinner fa-spin" style="font-size:2rem"></i>
                <p style="margin-top:1rem">Loading user details...</p>
            </div>
        </div>
    </div>
</div>

<script>
    function viewUserDetails(userId) {
        // Show modal with loading state
        const modal = document.getElementById('userDetailsModal');
        const content = document.getElementById('userDetailsContent');

        // Use flex to ensure centering works with the new CSS
        modal.style.display = 'flex';

        content.innerHTML = '<div style="text-align:center;padding:3rem"><span class="loading-icon" style="font-size:2rem;margin-bottom:1rem"></span><p style="color:#94a3b8">Loading user details...</p></div>';
        document.body.style.overflow = 'hidden';

        // Fetch user details via AJAX
        fetch(`/kaishop/admin/ajax/get_user_details.php?user_id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayUserDetails(data.user, data.exchange_rate);
                } else {
                    document.getElementById('userDetailsContent').innerHTML = `
                        <div style="text-align:center;padding:3rem;color:#ef4444">
                            <i class="fas fa-exclamation-triangle" style="font-size:2rem"></i>
                            <p style="margin-top:1rem">${data.message || 'Failed to load user details'}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('userDetailsContent').innerHTML = `
                    <div style="text-align:center;padding:3rem;color:#ef4444">
                        <i class="fas fa-exclamation-triangle" style="font-size:2rem"></i>
                        <p style="margin-top:1rem">Error loading user details</p>
                    </div>
                `;
            });
    }

    function displayUserDetails(user, exchangeRate = 24000) {
        document.getElementById('userDetailsUsername').textContent = user.username;

        const content = `
            <div style="display:grid;gap:1.5rem">
                <!-- User Info Card -->
                <div style="background:rgba(15,23,42,0.6);border-radius:12px;padding:1.5rem;border:1px solid rgba(148,163,184,0.2)">
                    <h3 style="margin:0 0 1rem 0;color:#f8fafc;display:flex;align-items:center;gap:0.5rem">
                        <i class="fas fa-user" style="color:#8b5cf6"></i> User Information
                    </h3>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem">
                        <div>
                            <small style="color:#94a3b8">User ID</small>
                            <div style="color:#f8fafc;font-weight:600">#${user.id}</div>
                        </div>
                        <div>
                            <small style="color:#94a3b8">Email</small>
                            <div style="color:#f8fafc;font-weight:600">${user.email || '-'}</div>
                        </div>
                        <div>
                            <small style="color:#94a3b8">Role</small>
                            <div><span class="badge ${user.role === 'admin' ? 'badge-danger' : 'badge-primary'}">${user.role}</span></div>
                        </div>
                        <div>
                            <small style="color:#94a3b8">Status</small>
                            <div><span class="badge ${user.is_active ? 'badge-success' : 'badge-danger'}">${user.is_active ? 'Active' : 'Locked'}</span></div>
                        </div>
                        <div>
                            <small style="color:#94a3b8">Balance</small>
                            <div>
                                <span style="color:#10b981;font-weight:600;font-size:1.1rem">${Number(user.balance_vnd).toLocaleString('vi-VN')} ₫</span>
                                <span style="color:#64748b;font-size:0.9rem;margin-left:0.5rem">(≈ $${(user.balance_vnd / exchangeRate).toFixed(2)})</span>
                            </div>
                        </div>
                        <div>
                            <small style="color:#94a3b8">Registered</small>
                            <div style="color:#f8fafc">${new Date(user.created_at).toLocaleString('vi-VN')}</div>
                        </div>
                        <div>
                            <small style="color:#94a3b8">Last Login</small>
                            <div style="color:#f8fafc">${user.last_login ? new Date(user.last_login).toLocaleString('vi-VN') : '-'}</div>
                        </div>
                    </div>
                </div>

                <!-- Security Info Card -->
                <div style="background:rgba(15,23,42,0.6);border-radius:12px;padding:1.5rem;border:1px solid rgba(148,163,184,0.2)">
                    <h3 style="margin:0 0 1rem 0;color:#f8fafc;display:flex;align-items:center;gap:0.5rem">
                        <i class="fas fa-shield-alt" style="color:#10b981"></i> Security Information
                    </h3>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1rem">
                        <div>
                            <small style="color:#94a3b8">Current IP</small>
                            <div style="color:#3b82f6;font-family:monospace;font-weight:600">${user.current_ip || '<span style="color:#64748b;font-style:italic">Not recorded</span>'}</div>
                        </div>
                        <div>
                            <small style="color:#94a3b8">Current Fingerprint</small>
                            <div style="color:#8b5cf6;font-family:monospace;font-size:0.85rem;word-break:break-all;background:rgba(139,92,246,0.1);padding:0.25rem 0.5rem;border-radius:4px;display:inline-block">
                                ${user.current_fingerprint || '<span style="color:#64748b;font-style:italic">Not recorded (Localhost?)</span>'}
                            </div>
                        </div>
                        <div>
                            <small style="color:#94a3b8">Total IPs Used</small>
                            <div style="color:#f8fafc;font-weight:600">${user.total_ips || 0}</div>
                        </div>
                        <div>
                            <small style="color:#94a3b8">Total Fingerprints</small>
                            <div style="color:#f8fafc;font-weight:600">${user.total_fingerprints || 0}</div>
                        </div>
                    </div>
                </div>

                <!-- IP History -->
                ${user.ip_history && user.ip_history.length > 0 ? `
                <div style="background:rgba(15,23,42,0.6);border-radius:12px;padding:1.5rem;border:1px solid rgba(148,163,184,0.2)">
                    <h3 style="margin:0 0 1rem 0;color:#f8fafc;display:flex;align-items:center;gap:0.5rem">
                        <i class="fas fa-network-wired" style="color:#3b82f6"></i> IP History (Last 10)
                    </h3>
                    <div style="overflow-x:auto">
                        <table style="width:100%;border-collapse:collapse">
                            <thead>
                                <tr style="border-bottom:1px solid rgba(148,163,184,0.2)">
                                    <th style="padding:0.75rem;text-align:left;color:#94a3b8;font-weight:600">IP Address</th>
                                    <th style="padding:0.75rem;text-align:left;color:#94a3b8;font-weight:600">Country</th>
                                    <th style="padding:0.75rem;text-align:left;color:#94a3b8;font-weight:600">Last Seen</th>
                                    <th style="padding:0.75rem;text-align:left;color:#94a3b8;font-weight:600">Times Used</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${user.ip_history.map(ip => `
                                    <tr style="border-bottom:1px solid rgba(148,163,184,0.1)">
                                        <td style="padding:0.75rem"><code style="color:#3b82f6;font-weight:600">${ip.ip}</code></td>
                                        <td style="padding:0.75rem">${ip.country_code || '-'}</td>
                                        <td style="padding:0.75rem;color:#f8fafc">${new Date(ip.last_seen).toLocaleString('vi-VN')}</td>
                                        <td style="padding:0.75rem"><span class="badge badge-info">${ip.count}</span></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
                ` : ''}

                <!-- Recent Security Logs -->
                ${user.security_logs && user.security_logs.length > 0 ? `
                <div style="background:rgba(15,23,42,0.6);border-radius:12px;padding:1.5rem;border:1px solid rgba(148,163,184,0.2)">
                    <h3 style="margin:0 0 1rem 0;color:#f8fafc;display:flex;align-items:center;gap:0.5rem">
                        <i class="fas fa-history" style="color:#f59e0b"></i> Recent Security Logs (Last 10)
                    </h3>
                    <div style="overflow-x:auto">
                        <table style="width:100%;border-collapse:collapse">
                            <thead>
                                <tr style="border-bottom:1px solid rgba(148,163,184,0.2)">
                                    <th style="padding:0.75rem;text-align:left;color:#94a3b8;font-weight:600">Time</th>
                                    <th style="padding:0.75rem;text-align:left;color:#94a3b8;font-weight:600">IP</th>
                                    <th style="padding:0.75rem;text-align:left;color:#94a3b8;font-weight:600">Threat</th>
                                    <th style="padding:0.75rem;text-align:left;color:#94a3b8;font-weight:600">Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${user.security_logs.map(log => {
            const threatColors = { low: '#10b981', medium: '#f59e0b', high: '#ef4444', critical: '#dc2626' };
            return `
                                    <tr style="border-bottom:1px solid rgba(148,163,184,0.1)">
                                        <td style="padding:0.75rem;color:#f8fafc">${new Date(log.created_at).toLocaleString('vi-VN')}</td>
                                        <td style="padding:0.75rem"><code style="color:#3b82f6">${log.ip}</code></td>
                                        <td style="padding:0.75rem"><span style="color:${threatColors[log.threat_level] || '#94a3b8'};font-weight:600;text-transform:uppercase">${log.threat_level}</span></td>
                                        <td style="padding:0.75rem;color:#94a3b8">${log.attack_type || '-'}</td>
                                    </tr>
                                `}).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
                ` : ''}
            </div>
        `;

        document.getElementById('userDetailsContent').innerHTML = content;
    }

    function closeUserDetails() {
        document.getElementById('userDetailsModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function toggleUserStatus(userId, status) {
        fetch('/kaishop/admin/ajax/toggle_user_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, status: status })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    notify.success('Success', data.message || 'User status updated');
                    closeUserDetails();
                    location.reload();
                } else {
                    notify.error('Error', data.message || 'Failed to update user status');
                }
            });
    }

    function blockUserFingerprint(fingerprint, username) {
        fetch('/kaishop/admin/ajax/block_fingerprint.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                fingerprint: fingerprint,
                reason: `Blocked from user details: ${username}`,
                permanent: false,
                duration: 3600
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    notify.success('Success', 'Fingerprint blocked successfully');
                    closeUserDetails();
                } else {
                    notify.error('Error', data.message || 'Failed to block fingerprint');
                }
            });
    }

    // Close modal on ESC key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && document.getElementById('userDetailsModal').style.display === 'block') {
            closeUserDetails();
        }
    });

    // Close modal on outside click
    document.getElementById('userDetailsModal')?.addEventListener('click', function (e) {
        if (e.target === this) {
            closeUserDetails();
        }
    });

    // INSTANT BAN USER - Shows modal with checkboxes for selective banning
    function instantBanUser(userId, username) {
        // Create ban modal
        const modalHTML = `
            <div id="banOptionsModal" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:999999;display:flex;align-items:center;justify-content:center">
                <div style="background:linear-gradient(135deg, #1e293b 0%, #0f172a 100%);border-radius:16px;padding:2rem;max-width:500px;width:90%;border:1px solid rgba(239,68,68,0.3);box-shadow:0 20px 60px rgba(0,0,0,0.5)">
                    <h2 style="margin:0 0 1.5rem 0;color:#f8fafc;display:flex;align-items:center;gap:0.75rem">
                        <i class="fas fa-hammer" style="color:#ef4444"></i>
                        Ban User: ${username}
                    </h2>
                    
                    <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;padding:1rem;margin-bottom:1.5rem">
                        <p style="margin:0;color:#fca5a5;font-size:0.9rem">
                            <i class="fas fa-exclamation-triangle"></i> Chọn các hành động ban bên dưới
                        </p>
                    </div>

                    <div style="margin-bottom:1.5rem">
                        <label style="display:flex;align-items:center;gap:0.75rem;padding:1rem;background:rgba(15,23,42,0.6);border:1px solid rgba(148,163,184,0.2);border-radius:8px;cursor:pointer;margin-bottom:0.75rem;transition:all 0.3s" class="ban-option">
                            <input type="checkbox" id="banAccount" checked style="width:18px;height:18px;cursor:pointer">
                            <div style="flex:1">
                                <div style="color:#f8fafc;font-weight:600;margin-bottom:0.25rem">
                                    <i class="fas fa-user-lock" style="color:#ef4444"></i> Lock Account
                                </div>
                                <small style="color:#94a3b8">Khóa tài khoản người dùng</small>
                            </div>
                        </label>

                        <label style="display:flex;align-items:center;gap:0.75rem;padding:1rem;background:rgba(15,23,42,0.6);border:1px solid rgba(148,163,184,0.2);border-radius:8px;cursor:pointer;margin-bottom:0.75rem;transition:all 0.3s" class="ban-option">
                            <input type="checkbox" id="banIP" checked style="width:18px;height:18px;cursor:pointer">
                            <div style="flex:1">
                                <div style="color:#f8fafc;font-weight:600;margin-bottom:0.25rem">
                                    <i class="fas fa-network-wired" style="color:#3b82f6"></i> Block IP Address
                                </div>
                                <small style="color:#94a3b8">Chặn địa chỉ IP (permanent)</small>
                            </div>
                        </label>

                        <label style="display:flex;align-items:center;gap:0.75rem;padding:1rem;background:rgba(15,23,42,0.6);border:1px solid rgba(148,163,184,0.2);border-radius:8px;cursor:pointer;transition:all 0.3s" class="ban-option">
                            <input type="checkbox" id="banFingerprint" checked style="width:18px;height:18px;cursor:pointer">
                            <div style="flex:1">
                                <div style="color:#f8fafc;font-weight:600;margin-bottom:0.25rem">
                                    <i class="fas fa-fingerprint" style="color:#8b5cf6"></i> Block Fingerprint
                                </div>
                                <small style="color:#94a3b8">Chặn browser fingerprint (permanent)</small>
                            </div>
                        </label>
                    </div>

                    <div style="margin-bottom:1.5rem">
                        <label style="color:#f8fafc;margin-bottom:0.5rem;display:block;font-weight:600">
                            <i class="fas fa-comment-alt"></i> Lý do ban *
                        </label>
                        <textarea id="banReason" rows="3" placeholder="VD: Spam, Scam, Violation of terms..." style="width:100%;background:#0f172a;color:#f8fafc;border:1px solid rgba(148,163,184,0.3);border-radius:8px;padding:0.75rem;resize:vertical" required>Violation of terms of service</textarea>
                    </div>

                    <div style="display:flex;gap:1rem">
                        <button onclick="executeBan('${userId}', '${username}')" style="flex:1;background:#ef4444;color:#fff;border:none;padding:0.75rem 1.5rem;border-radius:8px;font-weight:600;cursor:pointer;transition:all 0.3s">
                            <i class="fas fa-hammer"></i> Execute Ban
                        </button>
                        <button onclick="closeBanModal()" style="background:rgba(148,163,184,0.2);color:#f8fafc;border:none;padding:0.75rem 1.5rem;border-radius:8px;font-weight:600;cursor:pointer;transition:all 0.3s">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Add modal to page
        const modalDiv = document.createElement('div');
        modalDiv.innerHTML = modalHTML;
        document.body.appendChild(modalDiv);

        // Add hover effects
        document.querySelectorAll('.ban-option').forEach(option => {
            option.addEventListener('mouseenter', function () {
                this.style.borderColor = 'rgba(139,92,246,0.5)';
                this.style.background = 'rgba(139,92,246,0.1)';
            });
            option.addEventListener('mouseleave', function () {
                this.style.borderColor = 'rgba(148,163,184,0.2)';
                this.style.background = 'rgba(15,23,42,0.6)';
            });
        });
    }

    window.closeBanModal = function () {
        const modal = document.getElementById('banOptionsModal');
        if (modal) modal.remove();
    }

    window.executeBan = function (userId, username) {
        const banAccount = document.getElementById('banAccount').checked;
        const banIP = document.getElementById('banIP').checked;
        const banFingerprint = document.getElementById('banFingerprint').checked;
        const reason = document.getElementById('banReason').value.trim();

        if (!reason) {
            notify.error('Error', 'Vui lòng nhập lý do ban!');
            return;
        }

        if (!banAccount && !banIP && !banFingerprint) {
            notify.error('Error', 'Vui lòng chọn ít nhất 1 hành động ban!');
            return;
        }

        // Close modal
        closeBanModal();

        // Show loading
        notify.info('Processing', 'Đang thực hiện ban...', { duration: 0 });

        fetch('/kaishop/admin/ajax/instant_ban_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: userId,
                reason: reason,
                ban_account: banAccount,
                ban_ip: banIP,
                ban_fingerprint: banFingerprint
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let message = `User ${username} đã bị ban!\n\n`;
                    if (data.details.account_locked) message += '✓ Account locked\n';
                    if (data.details.ip_blocked) message += '✓ IP blocked\n';
                    if (data.details.fingerprint_blocked) message += '✓ Fingerprint blocked\n';

                    notify.success('✅ Banned!', message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    notify.error('Error', data.message || 'Failed to ban user');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                notify.error('Error', 'Failed to ban user');
            });
    }
</script>

<!-- All JavaScript functions moved to admin-functions.js for AJAX compatibility -->