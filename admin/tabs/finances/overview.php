<?php
/**
 * TỔNG QUAN TÀI CHÍNH
 * Hiển thị biểu đồ + Tiền Nạp + Tiền Hoàn
 */

$exchange_rate = floatval($pdo->query("SELECT setting_value FROM settings WHERE setting_key='exchange_rate'")->fetchColumn() ?? 25000);

$where_date = "created_at BETWEEN ? AND ?";
$params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];

// ========== TIỀN NẠP (Deposits + Admin Add) ==========
$deposit_stats = $pdo->prepare("SELECT 
    SUM(CASE WHEN currency='VND' THEN amount ELSE amount * ? END) as total_vnd,
    COUNT(*) as count
FROM balance_transactions 
WHERE (type='deposit' OR type='admin_add') AND $where_date");
$deposit_stats->execute(array_merge([$exchange_rate], $params));
$deposits = $deposit_stats->fetch();
$total_deposits = $deposits['total_vnd'] ?? 0;

// ========== TIỀN HOÀN (Hoàn đơn + Admin trừ) ==========
// Hoàn tiền từ balance_transactions (refund = hủy đơn, admin_deduct = admin trừ)
$refund_stats = $pdo->prepare("SELECT 
    SUM(CASE WHEN currency='VND' THEN amount ELSE amount * ? END) as total_vnd,
    COUNT(*) as count,
    SUM(CASE WHEN type='refund' THEN 1 ELSE 0 END) as refund_count,
    SUM(CASE WHEN type='admin_deduct' THEN 1 ELSE 0 END) as deduct_count
FROM balance_transactions 
WHERE (type='refund' OR type='admin_deduct') AND $where_date");
$refund_stats->execute(array_merge([$exchange_rate], $params));
$refund_data = $refund_stats->fetch();

$total_refunds = $refund_data['total_vnd'] ?? 0;
$refund_count = $refund_data['refund_count'] ?? 0;
$deduct_count = $refund_data['deduct_count'] ?? 0;

// ========== BIỂU ĐỒ DATA ==========
// Daily chart (last 30 days) - Tiền Nạp vs Tiền Hoàn
$daily_chart = $pdo->query("SELECT 
    DATE(created_at) as date,
    SUM(CASE WHEN type IN ('deposit', 'admin_add') THEN 
        CASE WHEN currency='VND' THEN amount ELSE amount * {$exchange_rate} END 
    ELSE 0 END) as deposits,
    SUM(CASE WHEN type IN ('refund', 'admin_deduct') THEN 
        CASE WHEN currency='VND' THEN amount ELSE amount * {$exchange_rate} END 
    ELSE 0 END) as refunds
FROM balance_transactions 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY date ASC")->fetchAll();

// Monthly chart (last 12 months)
$monthly_chart = $pdo->query("SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    SUM(CASE WHEN type IN ('deposit', 'admin_add') THEN 
        CASE WHEN currency='VND' THEN amount ELSE amount * {$exchange_rate} END 
    ELSE 0 END) as deposits,
    SUM(CASE WHEN type IN ('refund', 'admin_deduct') THEN 
        CASE WHEN currency='VND' THEN amount ELSE amount * {$exchange_rate} END 
    ELSE 0 END) as refunds
FROM balance_transactions 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY month ASC")->fetchAll();

?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    .chart-container {
        background: #0f172a;
        border-radius: 4px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        border: 1px solid rgba(139, 92, 246, 0.2);
    }

    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid rgba(139, 92, 246, 0.2);
    }

    .chart-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #f8fafc;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .chart-title i {
        color: #8b5cf6;
    }

    canvas {
        max-height: 350px;
    }
</style>

<!-- Date Filter -->
<div class="card" style="margin-bottom: 1.5rem">
    <form method="GET" class="form-grid" style="grid-template-columns: 1fr 1fr 1fr auto; gap: 1rem; padding: 1rem">
        <input type="hidden" name="tab" value="finances">
        <input type="hidden" name="sub" value="overview">
        <div class="form-group">
            <label><i class="fas fa-calendar"></i> Từ ngày</label>
            <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>" required>
        </div>
        <div class="form-group">
            <label><i class="fas fa-calendar"></i> Đến ngày</label>
            <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>" required>
        </div>
        <div class="form-group">
            <label><i class="fas fa-calendar-alt"></i> Thời gian</label>
            <select name="date_filter" class="form-control" onchange="this.form.submit()">
                <option value="all" <?= $date_filter == 'all' ? 'selected' : '' ?>>Tùy chọn</option>
                <option value="today" <?= $date_filter == 'today' ? 'selected' : '' ?>>Hôm nay</option>
                <option value="week" <?= $date_filter == 'week' ? 'selected' : '' ?>>Tuần này</option>
                <option value="month" <?= $date_filter == 'month' ? 'selected' : '' ?>>Tháng này</option>
            </select>
        </div>
        <div class="form-group" style="display: flex; align-items: flex-end">
            <button type="submit" class="btn btn-primary" style="height: fit-content">
                <i class="fas fa-search"></i> Lọc
            </button>
        </div>
    </form>
</div>

<!-- Thống kê chính: Tiền Nạp + Tiền Hoàn -->
<div class="stats-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); margin-bottom: 2rem; gap: 1.5rem">
    <!-- Tiền Nạp (xanh) -->
    <div class="stat-card" style="background: #0f172a; border: 1px solid #10b981; border-radius: 4px">
        <div class="stat-card-header" style="padding: 1.5rem">
            <div>
                <div class="stat-value" style="color: #10b981; font-size: 2.2rem; font-weight: 800">
                    <?= formatVND($total_deposits) ?>
                </div>
                <div class="stat-label"
                    style="font-size: 1.1rem; font-weight: 700; margin-top: 0.75rem; color: #f8fafc">
                    <i class="fas fa-arrow-down" style="color: #10b981"></i> Tiền Nạp Vào
                </div>
                <small style="color: #64748b; display: block; margin-top: 0.5rem">
                    <?= number_format($deposits['count'] ?? 0) ?> giao dịch nạp tiền
                </small>
            </div>
            <div
                style="width: 50px; height: 50px; background: #10b981; border-radius: 4px; display: flex; align-items: center; justify-content: center">
                <i class="fas fa-coins" style="font-size: 2rem; color: white"></i>
            </div>
        </div>
    </div>

    <!-- Tiền Hoàn (đỏ) -->
    <div class="stat-card" style="background: #0f172a; border: 1px solid #ef4444; border-radius: 4px">
        <div class="stat-card-header" style="padding: 1.5rem">
            <div>
                <div class="stat-value" style="color: #ef4444; font-size: 2.2rem; font-weight: 800">
                    <?= formatVND($total_refunds) ?>
                </div>
                <div class="stat-label"
                    style="font-size: 1.1rem; font-weight: 700; margin-top: 0.75rem; color: #f8fafc">
                    <i class="fas fa-arrow-up" style="color: #ef4444"></i> Tiền Hoàn Lại
                </div>
                <small style="color: #64748b; display: block; margin-top: 0.5rem">
                    <?= number_format($refund_count) ?> hoàn đơn + <?= number_format($deduct_count) ?> admin trừ
                </small>
            </div>
            <div
                style="width: 50px; height: 50px; background: #ef4444; border-radius: 4px; display: flex; align-items: center; justify-content: center">
                <i class="fas fa-hand-holding-usd" style="font-size: 2rem; color: white"></i>
            </div>
        </div>
    </div>
</div>

<!-- Biểu đồ 30 ngày - Line Chart -->
<div class="chart-container">
    <div class="chart-header">
        <h3 class="chart-title">
            <i class="fas fa-chart-line"></i> Tiền Nạp vs Tiền Hoàn (30 ngày gần nhất)
        </h3>
    </div>
    <?php if (!empty($daily_chart)): ?>
        <canvas id="dailyChart"></canvas>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new Chart(document.getElementById('dailyChart').getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: <?= json_encode(array_map(fn($d) => date('d/m', strtotime($d['date'])), $daily_chart)) ?>,
                        datasets: [
                            {
                                label: 'Tiền Nạp (VNĐ)',
                                data: <?= json_encode(array_column($daily_chart, 'deposits')) ?>,
                                backgroundColor: 'rgba(16, 185, 129, 0.2)',
                                borderColor: '#10b981',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 4,
                                pointBackgroundColor: '#10b981'
                            },
                            {
                                label: 'Tiền Hoàn (VNĐ)',
                                data: <?= json_encode(array_column($daily_chart, 'refunds')) ?>,
                                backgroundColor: 'rgba(239, 68, 68, 0.2)',
                                borderColor: '#ef4444',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 4,
                                pointBackgroundColor: '#ef4444'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: { color: '#f8fafc', font: { size: 13, weight: '600' } }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                                titleColor: '#f8fafc',
                                bodyColor: '#cbd5e1',
                                padding: 12,
                                callbacks: {
                                    label: function (ctx) {
                                        return ctx.dataset.label + ': ' + new Intl.NumberFormat('vi-VN').format(ctx.parsed.y) + 'đ';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: '#94a3b8',
                                    callback: function (v) { return (v / 1000000).toFixed(1) + 'M'; }
                                },
                                grid: { color: 'rgba(148, 163, 184, 0.1)' }
                            },
                            x: {
                                ticks: { color: '#94a3b8' },
                                grid: { display: false }
                            }
                        }
                    }
                });
            });
        </script>
    <?php else: ?>
        <div style="text-align: center; padding: 3rem; color: #64748b">
            <i class="fas fa-chart-area" style="font-size: 3rem; opacity: 0.3; display: block; margin-bottom: 1rem"></i>
            Chưa có dữ liệu
        </div>
    <?php endif; ?>
</div>

<!-- Biểu đồ 12 tháng - Bar Chart -->
<div class="chart-container">
    <div class="chart-header">
        <h3 class="chart-title">
            <i class="fas fa-chart-bar"></i> Thống Kê 12 Tháng
        </h3>
    </div>
    <?php if (!empty($monthly_chart)): ?>
        <canvas id="monthlyChart"></canvas>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new Chart(document.getElementById('monthlyChart').getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode(array_map(fn($m) => date('M Y', strtotime($m['month'] . '-01')), $monthly_chart)) ?>,
                        datasets: [
                            {
                                label: 'Tiền Nạp',
                                data: <?= json_encode(array_column($monthly_chart, 'deposits')) ?>,
                                backgroundColor: 'rgba(16, 185, 129, 0.8)',
                                borderColor: '#10b981',
                                borderWidth: 2,
                                borderRadius: 8
                            },
                            {
                                label: 'Tiền Hoàn',
                                data: <?= json_encode(array_column($monthly_chart, 'refunds')) ?>,
                                backgroundColor: 'rgba(239, 68, 68, 0.8)',
                                borderColor: '#ef4444',
                                borderWidth: 2,
                                borderRadius: 8
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: { color: '#f8fafc', font: { size: 13, weight: '600' } }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                                titleColor: '#f8fafc',
                                bodyColor: '#cbd5e1',
                                padding: 12,
                                callbacks: {
                                    label: function (ctx) {
                                        return ctx.dataset.label + ': ' + new Intl.NumberFormat('vi-VN').format(ctx.parsed.y) + 'đ';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: '#94a3b8',
                                    callback: function (v) { return (v / 1000000).toFixed(1) + 'M'; }
                                },
                                grid: { color: 'rgba(148, 163, 184, 0.1)' }
                            },
                            x: {
                                ticks: { color: '#94a3b8' },
                                grid: { display: false }
                            }
                        }
                    }
                });
            });
        </script>
    <?php else: ?>
        <div style="text-align: center; padding: 3rem; color: #64748b">
            <i class="fas fa-chart-bar" style="font-size: 3rem; opacity: 0.3; display: block; margin-bottom: 1rem"></i>
            Chưa có dữ liệu
        </div>
    <?php endif; ?>
</div>

<!-- Pie Chart - Tỷ lệ -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem">
    <div class="chart-container">
        <div class="chart-header">
            <h3 class="chart-title">
                <i class="fas fa-chart-pie"></i> Tỷ Lệ Tiền Nạp / Hoàn
            </h3>
        </div>
        <?php if ($total_deposits > 0 || $total_refunds > 0): ?>
            <canvas id="ratioChart" style="max-height: 280px"></canvas>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    new Chart(document.getElementById('ratioChart').getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: ['Tiền Nạp', 'Tiền Hoàn'],
                            datasets: [{
                                data: [<?= $total_deposits ?>, <?= $total_refunds ?>],
                                backgroundColor: ['#10b981', '#ef4444'],
                                borderColor: '#1e293b',
                                borderWidth: 4,
                                hoverOffset: 10
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { color: '#f8fafc', font: { size: 13 }, padding: 20 }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                                    callbacks: {
                                        label: function (ctx) {
                                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                            const pct = ((ctx.parsed / total) * 100).toFixed(1);
                                            return ctx.label + ': ' + new Intl.NumberFormat('vi-VN').format(ctx.parsed) + 'đ (' + pct + '%)';
                                        }
                                    }
                                }
                            }
                        }
                    });
                });
            </script>
        <?php else: ?>
            <div style="text-align: center; padding: 2rem; color: #64748b">Chưa có dữ liệu</div>
        <?php endif; ?>
    </div>

    <!-- Summary Card -->
    <div class="chart-container">
        <div class="chart-header">
            <h3 class="chart-title">
                <i class="fas fa-info-circle"></i> Tóm Tắt
            </h3>
        </div>
        <div style="display: flex; flex-direction: column; gap: 1rem">
            <div
                style="padding: 1.25rem; background: rgba(16, 185, 129, 0.1); border-radius: 12px; border-left: 4px solid #10b981">
                <div style="color: #10b981; font-size: 1.5rem; font-weight: 800"><?= formatVND($total_deposits) ?></div>
                <div style="color: #94a3b8; margin-top: 0.5rem">💰 Tổng tiền nạp vào hệ thống</div>
            </div>
            <div
                style="padding: 1.25rem; background: rgba(239, 68, 68, 0.1); border-radius: 12px; border-left: 4px solid #ef4444">
                <div style="color: #ef4444; font-size: 1.5rem; font-weight: 800"><?= formatVND($total_refunds) ?></div>
                <div style="color: #94a3b8; margin-top: 0.5rem">💸 Tổng tiền hoàn cho user</div>
            </div>
            <div
                style="padding: 1.25rem; background: rgba(139, 92, 246, 0.1); border-radius: 12px; border-left: 4px solid #8b5cf6">
                <?php $net = $total_deposits - $total_refunds; ?>
                <div style="color: <?= $net >= 0 ? '#8b5cf6' : '#ef4444' ?>; font-size: 1.5rem; font-weight: 800">
                    <?= $net >= 0 ? '+' : '' ?><?= formatVND($net) ?>
                </div>
                <div style="color: #94a3b8; margin-top: 0.5rem">📊 Chênh lệch (Nạp - Hoàn)</div>
            </div>
        </div>
    </div>
</div>