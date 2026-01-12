<?php
// Payment & Revenue Tab - CLEAN VERSION (VND Only)
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

$where_date = "created_at BETWEEN ? AND ?";
$params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];

// Get exchange rate
$exchange_rate = floatval($pdo->query("SELECT setting_value FROM settings WHERE setting_key='exchange_rate'")->fetchColumn() ?? 25000);

// Calculate total revenue VND (orders + balance transactions)
$order_revenue = $pdo->prepare("SELECT 
    SUM(CASE WHEN status='completed' THEN total_amount_vnd ELSE 0 END) as revenue_vnd,
    COUNT(CASE WHEN status='completed' THEN 1 END) as completed_orders
FROM orders WHERE $where_date");
$order_revenue->execute($params);
$order_stats = $order_revenue->fetch();

// Balance transactions (admin +/-)
$balance_stats = $pdo->query("SELECT 
    SUM(CASE WHEN type='admin_add' THEN amount ELSE 0 END) as total_added,
    SUM(CASE WHEN type='admin_deduct' THEN amount ELSE 0 END) as total_deducted
FROM balance_transactions WHERE currency='VND' OR currency='USD'")->fetch();

// Convert USD to VND for balance_stats
$stmt = $pdo->query("SELECT 
    SUM(CASE WHEN type='admin_add' AND currency='USD' THEN amount * {$exchange_rate} ELSE 0 END) as usd_add_vnd,
    SUM(CASE WHEN type='admin_deduct' AND currency='USD' THEN amount * {$exchange_rate} ELSE 0 END) as usd_deduct_vnd
FROM balance_transactions");
$usd_converted = $stmt->fetch();

$total_added_vnd = ($balance_stats['total_added'] ?? 0) + ($usd_converted['usd_add_vnd'] ?? 0);
$total_deducted_vnd = ($balance_stats['total_deducted'] ?? 0) + ($usd_converted['usd_deduct_vnd'] ?? 0);

// Refund stats
$refund_stats = $pdo->prepare("SELECT 
    SUM(total_amount_vnd) as refunded_vnd,
    COUNT(*) as refund_count
FROM orders WHERE status='refunded' AND $where_date");
$refund_stats->execute($params);
$refunds = $refund_stats->fetch();

// Total IN/OUT
$total_in = ($order_stats['revenue_vnd'] ?? 0) + $total_added_vnd;
$total_out = $total_deducted_vnd;
$net_revenue = $total_in - $total_out;

// Daily chart (30 days)
$chart_data = $pdo->query("SELECT 
    DATE(created_at) as date,
    SUM(CASE WHEN status='completed' THEN total_amount_vnd ELSE 0 END) as daily_vnd,
    COUNT(CASE WHEN status='completed' THEN 1 END) as daily_orders
FROM orders 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY date ASC")->fetchAll();
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-chart-line"></i> Doanh Thu Hệ Thống</h1>
        <p>Tổng quan tiền vào/ra (VND)</p>
    </div>
</div>

<!-- Filter -->
<div class="card">
    <form method="GET" class="form-grid" style="grid-template-columns: 1fr 1fr auto; gap: 1rem;">
        <input type="hidden" name="tab" value="payment">
        <div class="form-group">
            <label><i class="fas fa-calendar"></i> Từ ngày</label>
            <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
        </div>
        <div class="form-group">
            <label><i class="fas fa-calendar"></i> Đến ngày</label>
            <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Xem</button>
        </div>
    </form>
</div>

<!-- Main Revenue Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(250px,1fr));margin-bottom:2rem">
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value" style="color:#10b981;font-size:1.5rem"><?= formatVND($total_in) ?></div>
                <div class="stat-label">💰 Tổng Tiền Vào</div>
                <small style="color:#64748b">Orders + Admin Add</small>
            </div>
            <div class="stat-icon success"><i class="fas fa-arrow-down"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value" style="color:#ef4444;font-size:1.5rem"><?= formatVND($total_out) ?></div>
                <div class="stat-label">💸 Tổng Tiền Ra</div>
                <small style="color:#64748b">Admin Deduct</small>
            </div>
            <div class="stat-icon danger"><i class="fas fa-arrow-up"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value" style="color:#8b5cf6;font-size:1.5rem"><?= formatVND($net_revenue) ?></div>
                <div class="stat-label">📊 Doanh Thu Ròng</div>
                <small style="color:#64748b">IN - OUT</small>
            </div>
            <div class="stat-icon primary"><i class="fas fa-chart-line"></i></div>
        </div>
    </div>
</div>

<!-- Breakdown -->
<div class="card" style="margin-bottom:2rem">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> Chi Tiết Tiền Vào/Ra</h3>
    </div>
    <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));padding:1.5rem">
        <div style="padding:1rem;background:rgba(16,185,129,0.1);border-radius:8px;border:1px solid rgba(16,185,129,0.3)">
            <div style="color:#10b981;font-size:1.2rem;font-weight:700"><?= formatVND($order_stats['revenue_vnd'] ?? 0) ?></div>
            <div style="color:#64748b;margin-top:0.5rem">Đơn Hàng (<?= $order_stats['completed_orders'] ?? 0 ?>)</div>
        </div>
        <div style="padding:1rem;background:rgba(16,185,129,0.1);border-radius:8px;border:1px solid rgba(16,185,129,0.3)">
            <div style="color:#10b981;font-size:1.2rem;font-weight:700"><?= formatVND($total_added_vnd) ?></div>
            <div style="color:#64748b;margin-top:0.5rem">Admin Cộng Tiền</div>
        </div>
        <div style="padding:1rem;background:rgba(239,68,68,0.1);border-radius:8px;border:1px solid rgba(239,68,68,0.3)">
            <div style="color:#ef4444;font-size:1.2rem;font-weight:700"><?= formatVND($total_deducted_vnd) ?></div>
            <div style="color:#64748b;margin-top:0.5rem">Admin Trừ Tiền</div>
        </div>
        <div style="padding:1rem;background:rgba(245,158,11,0.1);border-radius:8px;border:1px solid rgba(245,158,11,0.3)">
            <div style="color:#f59e0b;font-size:1.2rem;font-weight:700"><?= formatVND($refunds['refunded_vnd'] ?? 0) ?></div>
            <div style="color:#64748b;margin-top:0.5rem">Hoàn Tiền (<?= $refunds['refund_count'] ?? 0 ?>)</div>
        </div>
    </div>
</div>

<!-- Chart -->
<div class="card" style="margin-bottom:2rem">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-bar"></i> Doanh Thu 30 Ngày</h3>
    </div>
    <div style="padding:1.5rem;overflow-x:auto">
        <div style="min-width:800px;height:300px;position:relative">
            <?php if (!empty($chart_data)): ?>
                <?php
                $max_vnd = max(array_column($chart_data, 'daily_vnd')) ?: 1;
                ?>
                <div style="display:flex;align-items:flex-end;justify-content:space-around;height:100%;gap:0.5rem">
                    <?php foreach ($chart_data as $day): ?>
                        <?php $height = ($day['daily_vnd'] / $max_vnd) * 100; ?>
                        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:0.5rem">
                            <div style="font-size:0.75rem;color:#10b981;font-weight:600">
                                <?= $day['daily_orders'] ?>
                            </div>
                            <div style="width:100%;height:<?= max($height, 5) ?>%;background:linear-gradient(180deg,#8b5cf6,#6d28d9);border-radius:4px 4px 0 0;cursor:pointer" 
                                 title="<?= date('d/m', strtotime($day['date'])) ?>: <?= formatVND($day['daily_vnd']) ?>">
                            </div>
                            <div style="font-size:0.7rem;color:#64748b;transform:rotate(-45deg);white-space:nowrap">
                                <?= date('d/m', strtotime($day['date'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#64748b">
                    <div style="text-align:center">
                        <i class="fas fa-chart-bar" style="font-size:3rem;margin-bottom:1rem;opacity:0.3"></i>
                        <p>Chưa có dữ liệu</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Refund Section -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-undo"></i> Lịch Sử Hoàn Tiền</h3>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Mã Đơn</th>
                    <th>Khách Hàng</th>
                    <th>Số Tiền Hoàn</th>
                    <th>Thời Gian</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $refunded_orders = $pdo->query("SELECT o.*, u.username FROM orders o 
                    LEFT JOIN users u ON o.user_id = u.id 
                    WHERE o.status = 'refunded' 
                    ORDER BY o.created_at DESC 
                    LIMIT 20");
                $refund_list = $refunded_orders->fetchAll();
                ?>
                <?php if (empty($refund_list)): ?>
                    <tr><td colspan="4" style="text-align:center;padding:3rem;color:#64748b">Không có đơn hoàn tiền</td></tr>
                <?php else: ?>
                    <?php foreach ($refund_list as $refund): ?>
                        <tr>
                            <td>
                                <strong style="color:#8b5cf6"><?= $refund['order_number'] ?></strong>
                            </td>
                            <td>
                                <strong style="color:#f8fafc"><?= e($refund['username'] ?? 'Guest') ?></strong>
                            </td>
                            <td>
                                <strong style="color:#f59e0b;font-size:1.1rem"><?= formatVND($refund['total_amount_vnd']) ?></strong>
                            </td>
                            <td>
                                <div style="color:#f8fafc"><?= date('d/m/Y H:i', strtotime($refund['created_at'])) ?></div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
