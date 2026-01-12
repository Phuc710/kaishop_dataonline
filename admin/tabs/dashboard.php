<?php
// Dashboard Tab - REBUILT VERSION
$exchange_rate = floatval($pdo->query("SELECT setting_value FROM settings WHERE setting_key='exchange_rate'")->fetchColumn() ?? 25000);

// Quick stats
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='completed'")->fetchColumn();
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();

// Tiền nạp từ system_logs (payment + balance logs)
$total_deposits = $pdo->query("SELECT SUM(CAST(REGEXP_REPLACE(description, '[^0-9]', '') AS UNSIGNED))
    FROM system_logs
    WHERE log_type IN ('payment', 'balance')
    AND action LIKE '%Cộng%'")->fetchColumn() ?? 0;

// Tiền hoàn từ system_logs (refund + admin_deduct)
$total_refunds = $pdo->query("SELECT SUM(CAST(REGEXP_REPLACE(description, '[^0-9]', '') AS UNSIGNED))
    FROM system_logs
    WHERE log_type IN ('payment', 'balance')
    AND action LIKE '%Trừ%'")->fetchColumn() ?? 0;

// Monthly data for chart - TIỀN NẠP
$months_list = [];
for ($i = 11; $i >= 0; $i--) {
    $months_list[] = date('Y-m', strtotime("-$i months"));
}

// Get deposits by month from system_logs
$deposits_query = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
           SUM(CAST(REGEXP_REPLACE(description, '[^0-9]', '') AS UNSIGNED)) as total,
           COUNT(*) as count
    FROM system_logs
    WHERE log_type IN ('payment', 'balance')
    AND action LIKE '%Cộng%'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
")->fetchAll(PDO::FETCH_ASSOC);

// Convert to map
$deposits_map = [];
$deposits_count_map = [];
foreach ($deposits_query as $row) {
    $deposits_map[$row['month']] = $row['total'];
    $deposits_count_map[$row['month']] = $row['count'];
}

// Combine data
$monthly_data = [];
foreach ($months_list as $month) {
    $monthly_data[] = [
        'month' => $month,
        'deposits' => $deposits_map[$month] ?? 0,
        'count' => $deposits_count_map[$month] ?? 0
    ];
}
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-chart-line"></i> Tổng Quan Hệ Thống</h1>
        <p>Dashboard - Biểu đồ doanh thu</p>
    </div>
</div>

<!-- Quick Stats -->
<div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.5rem;margin-bottom:2rem">
    <div class="stat-card" style="background:linear-gradient(135deg,rgba(139,92,246,0.15),rgba(109,40,217,0.05));border-left:4px solid #8b5cf6;box-shadow:0 4px 12px rgba(139,92,246,0.15);padding:1.5rem;border-radius:12px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start">
            <div>
                <div style="color:#8b5cf6;font-size:2.5rem;font-weight:700;margin-bottom:0.5rem"><?= number_format($total_users) ?></div>
                <div style="color:#cbd5e1;font-weight:600;font-size:0.95rem">👥 Tổng Người Dùng</div>
            </div>
            <div style="width:60px;height:60px;background:linear-gradient(135deg,#8b5cf6,#6d28d9);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(139,92,246,0.3)">
                <i class="fas fa-users" style="font-size:1.5rem;color:white"></i>
            </div>
        </div>
    </div>
    
    <div class="stat-card" style="background:linear-gradient(135deg,rgba(16,185,129,0.15),rgba(5,150,105,0.05));border-left:4px solid #10b981;box-shadow:0 4px 12px rgba(16,185,129,0.15);padding:1.5rem;border-radius:12px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start">
            <div>
                <div style="color:#10b981;font-size:2.5rem;font-weight:700;margin-bottom:0.5rem"><?= number_format($total_products) ?></div>
                <div style="color:#cbd5e1;font-weight:600;font-size:0.95rem">📦 Tổng Sản Phẩm</div>
            </div>
            <div style="width:60px;height:60px;background:linear-gradient(135deg,#10b981,#059669);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(16,185,129,0.3)">
                <i class="fas fa-box" style="font-size:1.5rem;color:white"></i>
            </div>
        </div>
    </div>
    
    <div class="stat-card" style="background:linear-gradient(135deg,rgba(245,158,11,0.15),rgba(217,119,6,0.05));border-left:4px solid #f59e0b;box-shadow:0 4px 12px rgba(245,158,11,0.15);padding:1.5rem;border-radius:12px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start">
            <div>
                <div style="color:#f59e0b;font-size:2.5rem;font-weight:700;margin-bottom:0.5rem"><?= number_format($total_orders) ?></div>
                <div style="color:#cbd5e1;font-weight:600;font-size:0.95rem">✅ Đơn Hoàn Thành</div>
                <?php if ($pending_orders > 0): ?>
                    <div style="margin-top:0.5rem">
                        <span style="background:rgba(239,68,68,0.2);color:#ef4444;padding:0.25rem 0.6rem;border-radius:8px;font-size:0.8rem;font-weight:700">
                            <i class="fas fa-clock"></i> <?= $pending_orders ?> đang chờ
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            <div style="width:60px;height:60px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(245,158,11,0.3)">
                <i class="fas fa-shopping-cart" style="font-size:1.5rem;color:white"></i>
            </div>
        </div>
    </div>
    
    <div class="stat-card" style="background:linear-gradient(135deg,rgba(16,185,129,0.15),rgba(5,150,105,0.05));border-left:4px solid #10b981;box-shadow:0 4px 12px rgba(16,185,129,0.15);padding:1.5rem;border-radius:12px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start">
            <div>
                <div style="color:#10b981;font-size:1.8rem;font-weight:700;margin-bottom:0.5rem"><?= formatVND($total_deposits) ?></div>
                <div style="color:#cbd5e1;font-weight:600;font-size:0.95rem">💰 Tổng Tiền Nạp</div>
                <div style="margin-top:0.5rem;font-size:0.85rem;color:#64748b">
                    Hoàn/Trừ: <span style="color:#ef4444;font-weight:600"><?= formatVND($total_refunds) ?></span>
                </div>
            </div>
            <div style="width:60px;height:60px;background:linear-gradient(135deg,#10b981,#059669);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(16,185,129,0.3)">
                <i class="fas fa-coins" style="font-size:1.5rem;color:white"></i>
            </div>
        </div>
    </div>
</div>


<!-- Recent Orders -->
<?php
$recent_orders = $pdo->query("SELECT o.*, u.username, u.email FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 10")->fetchAll();
?>
<div class="card" style="background:rgba(30,41,59,0.6);backdrop-filter:blur(10px);border:1px solid rgba(139,92,246,0.2);border-radius:16px;overflow:hidden">
    <div class="card-header" style="background:rgba(139,92,246,0.1);padding:1.5rem;border-bottom:1px solid rgba(139,92,246,0.2);display:flex;justify-content:space-between;align-items:center">
        <h3 style="color:#f8fafc;font-size:1.3rem;font-weight:700;display:flex;align-items:center;gap:0.75rem;margin:0">
            <i class="fas fa-shopping-bag" style="color:#8b5cf6"></i> Đơn Hàng Gần Đây
        </h3>
        <a href="?tab=orders" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:white;padding:0.6rem 1.2rem;border-radius:8px;text-decoration:none;font-weight:600;font-size:0.9rem;transition:all 0.3s;display:inline-flex;align-items:center;gap:0.5rem"
           onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 16px rgba(139,92,246,0.4)'"
           onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='none'">
            Xem tất cả <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:rgba(139,92,246,0.05);border-bottom:2px solid rgba(139,92,246,0.2)">
                    <th style="padding:1rem;text-align:left;color:#8b5cf6;font-weight:700;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.5px">Mã Đơn</th>
                    <th style="padding:1rem;text-align:left;color:#8b5cf6;font-weight:700;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.5px">Khách Hàng</th>
                    <th style="padding:1rem;text-align:left;color:#8b5cf6;font-weight:700;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.5px">Số Tiền</th>
                    <th style="padding:1rem;text-align:center;color:#8b5cf6;font-weight:700;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.5px">Trạng Thái</th>
                    <th style="padding:1rem;text-align:left;color:#8b5cf6;font-weight:700;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.5px">Thời Gian</th>
                    <th style="padding:1rem;text-align:center;color:#8b5cf6;font-weight:700;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.5px">Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_orders)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:3rem;color:#64748b">
                            <i class="fas fa-inbox" style="font-size:3rem;opacity:0.3;margin-bottom:1rem;display:block"></i>
                            <span style="font-size:1.1rem">Chưa có đơn hàng nào</span>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                        <tr style="border-bottom:1px solid rgba(139,92,246,0.1);transition:all 0.3s"
                            onmouseover="this.style.background='rgba(139,92,246,0.05)'"
                            onmouseout="this.style.background='transparent'">
                            <td style="padding:1rem">
                                <code style="background:rgba(139,92,246,0.2);color:#c4b5fd;padding:0.5rem 0.8rem;border-radius:8px;font-weight:700;font-size:0.85rem;font-family:'Courier New',monospace"><?= $order['order_number'] ?></code>
                            </td>
                            <td style="padding:1rem">
                                <div>
                                    <div style="color:#f8fafc;font-weight:600;font-size:0.95rem"><?= e($order['username'] ?? 'Guest') ?></div>
                                    <?php if (!empty($order['email'])): ?>
                                        <div style="color:#64748b;font-size:0.8rem;margin-top:0.2rem"><?= e($order['email']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="padding:1rem">
                                <div style="display:flex;flex-direction:column;gap:0.2rem">
                                    <span style="color:#10b981;font-weight:700;font-size:1rem"><?= formatVND($order['total_amount_vnd']) ?></span>
                                    <?php if ($order['currency'] == 'USD' && $order['total_amount_usd'] > 0): ?>
                                        <span style="color:#64748b;font-size:0.8rem">≈ $<?= number_format($order['total_amount_usd'], 2) ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="padding:1rem;text-align:center">
                                <?php
                                $status_config = [
                                    'pending' => ['🕐', 'Chờ Xử Lý', '#f59e0b', 'rgba(245,158,11,0.2)'],
                                    'completed' => ['✅', 'Hoàn Thành', '#10b981', 'rgba(16,185,129,0.2)'],
                                    'cancelled' => ['❌', 'Đã Hủy', '#ef4444', 'rgba(239,68,68,0.2)'],
                                    'refunded' => ['💸', 'Hoàn Tiền', '#06b6d4', 'rgba(6,182,212,0.2)']
                                ];
                                $status = $status_config[$order['status']] ?? ['📦', ucfirst($order['status']), '#8b5cf6', 'rgba(139,92,246,0.2)'];
                                ?>
                                <span style="background:<?= $status[3] ?>;color:<?= $status[2] ?>;padding:0.5rem 1rem;border-radius:20px;font-weight:700;font-size:0.85rem;display:inline-flex;align-items:center;gap:0.5rem;white-space:nowrap">
                                    <?= $status[0] ?> <?= $status[1] ?>
                                </span>
                            </td>
                            <td style="padding:1rem">
                                <div style="display:flex;flex-direction:column;gap:0.2rem">
                                    <span style="color:#f8fafc;font-weight:600;font-size:0.9rem"><?= date('d/m/Y', strtotime($order['created_at'])) ?></span>
                                    <span style="color:#64748b;font-size:0.8rem"><?= date('H:i:s', strtotime($order['created_at'])) ?></span>
                                </div>
                            </td>
                            <td style="padding:1rem;text-align:center">
                                <a href="?tab=orders&view=<?= $order['id'] ?>" style="background:rgba(139,92,246,0.2);color:#c4b5fd;padding:0.5rem 1rem;border-radius:8px;text-decoration:none;font-weight:600;font-size:0.85rem;transition:all 0.3s;display:inline-flex;align-items:center;gap:0.5rem"
                                   onmouseover="this.style.background='rgba(139,92,246,0.3)';this.style.color='#e9d5ff'"
                                   onmouseout="this.style.background='rgba(139,92,246,0.2)';this.style.color='#c4b5fd'">
                                    <i class="fas fa-eye"></i> Chi tiết
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
