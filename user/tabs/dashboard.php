<?php
/**
 * Dashboard Tab - Optimized Layout
 */

// Helper function to get exchange rate
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

// Fetch Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$total_orders = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount_vnd), 0) FROM orders WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$total_spent_vnd = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_tickets = $stmt->fetchColumn();

$exchange_rate = getExchangeRate();
$current_currency = $_COOKIE['currency'] ?? 'VND';

// Recent Orders
$stmt = $pdo->prepare("
    SELECT o.*, p.name as product_name, p.image as product_image, oi.quantity
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll();

// Recent Transactions
$stmt = $pdo->prepare("
    SELECT * FROM balance_transactions
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_transactions = $stmt->fetchAll();
?>

<div class="page-header fade-in">
    <div>
        <h1><i class="fas fa-home"></i> Tổng Quan</h1>
        <p>Chào mừng trở lại, <strong style="color: var(--text-main);"><?= e($user['username']) ?></strong> 👋</p>
        <p>ID: <strong style="color: var(--text-main); font-weight: 600; "><?= $user_id ?></strong></p>
    </div>
    <div style="display: flex; gap: 12px;">
        <a href="<?= url('sanpham') ?>" class="btn btn-primary">
            <i class="fas fa-shopping-cart"></i> Mua Sắm
        </a>
        <a href="<?= url('naptien') ?>" class="btn">
            <i class="fas fa-wallet"></i> Nạp Tiền
        </a>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid fade-in">
    <!-- Balance -->
    <div class="stat-card"
        style="background:linear-gradient(135deg,rgba(139,92,246,0.15),rgba(109,40,217,0.05));border-left:4px solid #8b5cf6;box-shadow:0 4px 12px rgba(139,92,246,0.15)">
        <div class="stat-card-header">
            <div>
                <div class="stat-label" style="color:#f8fafc;font-weight:600;margin-bottom:0.5rem">💰 Số Dư Hiện Tại
                </div>
                <div class="stat-value" style="color:#8b5cf6;font-size:2.2rem;font-weight:700">
                    <?php if ($current_currency === 'USD'): ?>
                        $<?= number_format(($user['balance_vnd'] ?? 0) / $exchange_rate, 2) ?>
                    <?php else: ?>
                        <?= number_format($user['balance_vnd'] ?? 0) ?>đ
                    <?php endif; ?>
                </div>
            </div>
            <div class="stat-icon"
                style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);box-shadow:0 4px 12px rgba(139,92,246,0.3)">
                <i class="fas fa-wallet"></i>
            </div>
        </div>
        <div class="stat-change" style="font-weight:700">
            <span
                style="background: linear-gradient(135deg, #10b981, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 800;">
                <i class="fas fa-info-circle" style="color:#8b5cf6; -webkit-text-fill-color: #8b5cf6;"></i>
                Tỷ giá: 1 USD = <?= number_format($exchange_rate) ?>đ
            </span>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="stat-card"
        style="background:linear-gradient(135deg,rgba(16,185,129,0.15),rgba(5,150,105,0.05));border-left:4px solid #10b981;box-shadow:0 4px 12px rgba(16,185,129,0.15)">
        <div class="stat-card-header">
            <div>
                <div class="stat-label" style="color:#f8fafc;font-weight:600;margin-bottom:0.5rem">📦 Tổng Đơn Hàng
                </div>
                <div class="stat-value" style="color:#10b981;font-size:2.2rem;font-weight:700">
                    <?= number_format($total_orders) ?>
                </div>
            </div>
            <div class="stat-icon"
                style="background:linear-gradient(135deg,#10b981,#059669);box-shadow:0 4px 12px rgba(16,185,129,0.3)">
                <i class="fas fa-shopping-bag"></i>
            </div>
        </div>
        <div class="stat-change up" style="color:#22c55e;font-weight:600">
            <i class="fas fa-check-circle"></i>
            <span>Đã hoàn thành</span>
        </div>
    </div>

    <!-- Total Spent -->
    <div class="stat-card"
        style="background:linear-gradient(135deg,rgba(239,68,68,0.15),rgba(220,38,38,0.05));border-left:4px solid #ef4444;box-shadow:0 4px 12px rgba(239,68,68,0.15)">
        <div class="stat-card-header">
            <div>
                <div class="stat-label" style="color:#f8fafc;font-weight:600;margin-bottom:0.5rem">💸 Tổng Chi Tiêu
                </div>
                <div class="stat-value" style="color:#ef4444;font-size:2.2rem;font-weight:700">
                    <?php if ($current_currency === 'USD'): ?>
                        -$<?= number_format($total_spent_vnd / $exchange_rate, 2) ?>
                    <?php else: ?>
                        -<?= number_format($total_spent_vnd) ?>đ
                    <?php endif; ?>
                </div>
            </div>
            <div class="stat-icon"
                style="background:linear-gradient(135deg,#ef4444,#dc2626);box-shadow:0 4px 12px rgba(239,68,68,0.3)">
                <i class="fas fa-arrow-up"></i>
            </div>
        </div>
        <div class="stat-change down" style="color:#ef4444;font-weight:600">
            <i class="fas fa-minus-circle"></i>
            <span>Tiền chi tiêu</span>
        </div>
    </div>

    <!-- Tickets -->
    <div class="stat-card"
        style="background:linear-gradient(135deg,rgba(59,130,246,0.15),rgba(37,99,235,0.05));border-left:4px solid #3b82f6;box-shadow:0 4px 12px rgba(59,130,246,0.15)">
        <div class="stat-card-header">
            <div>
                <div class="stat-label" style="color:#f8fafc;font-weight:600;margin-bottom:0.5rem">🎧 Hỗ Trợ</div>
                <div class="stat-value" style="color:#3b82f6;font-size:1.8rem;font-weight:700"><?= $total_tickets ?>
                </div>
            </div>
            <div class="stat-icon"
                style="background:linear-gradient(135deg,#3b82f6,#2563eb);box-shadow:0 4px 12px rgba(59,130,246,0.3)">
                <i class="fas fa-headset"></i>
            </div>
        </div>
        <div class="stat-change" style="color:#3b82f6;font-weight:600">
            <i class="fas fa-envelope-open-text"></i>
            <span>Ticket đang mở</span>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="card fade-in">
    <div class="card-header">
        <h2 class="card-title">Đơn Hàng Gần Đây</h2>
        <a href="?tab=orders" class="btn btn-sm">
            Xem Tất Cả <i class="fas fa-arrow-right" style="font-size: 10px; margin-left: 4px;"></i>
        </a>
    </div>

    <?php if (empty($recent_orders)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-box-open"></i>
            </div>
            <h3>Chưa Có Đơn Hàng</h3>
            <p>Bạn chưa thực hiện đơn hàng nào gần đây.</p>
            <br>
            <a href="<?= url('sanpham') ?>" class="btn btn-primary">
                Mua Sắm Ngay
            </a>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Mã Đơn</th>
                        <th>Sản Phẩm</th>
                        <th>Số Lượng</th>
                        <th>Tổng Tiền</th>
                        <th>Trạng Thái</th>
                        <th>Ngày Mua</th>
                        <th style="text-align: center;">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $order): ?>
                        <tr class="table-row-custom">
                            <td>
                                <button class="copy-btn" onclick="copyToClipboard('<?= e($order['order_number']) ?>')"
                                    title="Click để copy">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if ($order['product_image']): ?>
                                        <img src="<?= asset('images/uploads/' . $order['product_image']) ?>"
                                            style="width: 32px; height: 32px; border-radius: 6px; object-fit: cover; background: var(--bg-element);">
                                    <?php endif; ?>
                                    <span
                                        style="font-weight: 700; color: var(--text-main); font-size: 0.95rem;"><?= e($order['product_name']) ?></span>
                                </div>
                            </td>
                            <td>x<?= $order['quantity'] ?></td>
                            <td style="font-weight: 700; font-size: 1.1rem; color: var(--text-main);">
                                <?php if ($current_currency === 'USD'): ?>
                                    $<?= number_format(($order['total_amount_usd'] > 0 ? $order['total_amount_usd'] : $order['total_amount_vnd'] / $exchange_rate), 2) ?>
                                <?php else: ?>
                                    <?= number_format(($order['total_amount_usd'] > 0 ? $order['total_amount_usd'] * $exchange_rate : $order['total_amount_vnd'])) ?>đ
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status_map = [
                                    'completed' => ['Hoàn Thành', 'badge-success'],
                                    'pending' => ['Đang Xử Lý', 'badge-warning'],
                                    'cancelled' => ['Đã Hủy', 'badge-danger'],
                                    'refunded' => ['Hoàn Tiền', 'badge-info']
                                ];
                                $s = $status_map[$order['status']] ?? [$order['status'], 'badge'];
                                ?>
                                <span class="badge <?= $s[1] ?>">
                                    <?= $s[0] ?>
                                </span>
                            </td>
                            <td style="color: var(--text-muted); font-size: 13px;">
                                <?= date('d/m/Y', strtotime($order['created_at'])) ?>
                            </td>
                            <td style="text-align: center;">
                                <a href="?tab=orders&view=<?= $order['order_number'] ?>" class="btn btn-sm"
                                    style="padding: 6px 10px;">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Split Layout for Transactions & Actions -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-top: 24px;">

    <!-- Recent Transactions -->
    <div class="card fade-in" style="margin-bottom: 0;">
        <div class="card-header">
            <h2 class="card-title">Giao Dịch Gần Đây</h2>
            <a href="?tab=transactions" class="btn btn-sm">
                Xem Tất Cả
            </a>
        </div>
        <?php if (empty($recent_transactions)): ?>
            <div class="empty-state">
                <p>Chưa có giao dịch nào</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($recent_transactions as $trans): ?>
                    <?php
                    $t_map = [
                        'deposit' => ['Nạp Tiền', 'arrow-down', 'var(--success)'],
                        'purchase' => ['Thanh Toán', 'shopping-cart', 'var(--text-main)'],
                        'refund' => ['Hoàn Tiền', 'undo', 'var(--info)'],
                        'admin_add' => ['Admin Cộng', 'plus', 'var(--success)'],
                        'admin_deduct' => ['Admin Trừ', 'minus', 'var(--danger)']
                    ];
                    $t = $t_map[$trans['type']] ?? ['Giao Dịch', 'circle', 'var(--text-muted)'];

                    // Determine Amount Display based on user's selected currency
                    if ($current_currency === 'USD') {
                        // Ensure amount is in USD
                        $val = ($trans['currency'] === 'USD') ? $trans['amount'] : ($trans['amount'] / $exchange_rate);
                        $amount_display = '$' . number_format($val, 2);
                    } else {
                        // Ensure amount is in VND
                        $val = ($trans['currency'] === 'USD') ? ($trans['amount'] * $exchange_rate) : $trans['amount'];
                        $amount_display = number_format($val) . 'đ';
                    }

                    $is_plus = in_array($trans['type'], ['deposit', 'refund', 'admin_add']);
                    $icon_color = $t[2];
                    $amount_color = $is_plus ? 'var(--success)' : 'var(--danger)';
                    ?>
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--bg-element); border-radius: 16px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div
                                style="width: 36px; height: 36px; border-radius: 50%; background: var(--bg-card); display: flex; align-items: center; justify-content: center; color: <?= $icon_color ?>;">
                                <i class="fas fa-<?= $t[1] ?>"></i>
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 14px;"><?= $t[0] ?></div>
                                <div style="font-size: 12px; color: var(--text-muted);">
                                    <?= date('H:i - d/m', strtotime($trans['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-weight: 700; font-size: 1.1rem; color: <?= $amount_color ?>;">
                                <?= $is_plus ? '+' : '-' ?>         <?= $amount_display ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="card fade-in" style="margin-bottom: 0; height: fit-content;">
        <div class="card-header">
            <h2 class="card-title">Thao Tác</h2>
        </div>
        <div style="display: grid; gap: 12px;">
            <a href="<?= url('sanpham') ?>" class="btn btn-primary" style="justify-content: flex-start;">
                <i class="fas fa-shopping-cart" style="width: 20px;"></i> Mua Sản Phẩm
            </a>
            <a href="<?= url('naptien') ?>" class="btn" style="justify-content: flex-start;">
                <i class="fas fa-wallet" style="width: 20px;"></i> Nạp Tiền
            </a>
            <a href="?tab=transactions" class="btn" style="justify-content: flex-start;">
                <i class="fas fa-history" style="width: 20px;"></i> Lịch Sử Giao Dịch
            </a>
            <a href="?tab=tickets" class="btn" style="justify-content: flex-start;">
                <i class="fas fa-headset" style="width: 20px;"></i> Hỗ Trợ
            </a>
        </div>
    </div>
</div>

<style>
    /* Copy Button - Minimal */
    .copy-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        background: transparent;
        border: 1px solid #333;
        border-radius: 6px;
        color: #888;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .copy-btn:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: #555;
        color: #fff;
    }

    .copy-btn i {
        font-size: 13px;
    }
</style>

<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function () {
            if (typeof notify !== 'undefined') {
                notify.success('Đã copy!', `Mã đơn ${text}`);
            }
        }).catch(function (err) {
            if (typeof notify !== 'undefined') {
                notify.error('Lỗi', 'Không thể copy mã đơn');
            }
        });
    }
</script>