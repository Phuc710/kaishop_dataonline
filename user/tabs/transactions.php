<?php
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

// Get filter type and pagination - Default to all
$filter_type = $_GET['type'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;

$exchange_rate = getExchangeRate();
$current_currency = $_COOKIE['currency'] ?? 'VND';

// Build query from balance_transactions
$where_clause = "user_id = ?";
$params = [$user_id];

if ($filter_type === 'deposit') {
    $where_clause .= " AND (type = 'deposit' OR type = 'admin_add' OR type = 'refund')";
} elseif ($filter_type === 'deduct') {
    $where_clause .= " AND (type = 'admin_deduct' OR type = 'purchase')";
} elseif ($filter_type === 'refund') {
    $where_clause .= " AND type = 'refund'";
}

// Get total count for pagination
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM balance_transactions WHERE $where_clause");
$count_stmt->execute($params);
$total_transactions = $count_stmt->fetchColumn();
$total_pages = ceil($total_transactions / $per_page);
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare("
    SELECT * FROM balance_transactions
    WHERE $where_clause
    ORDER BY created_at DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Get counts for each type
$stmt = $pdo->prepare("SELECT type, COUNT(*) as count FROM balance_transactions WHERE user_id = ? GROUP BY type");
$stmt->execute([$user_id]);
$type_counts = [];
foreach ($stmt->fetchAll() as $row) {
    $type_counts[$row['type']] = $row['count'];
}
$total_count = array_sum($type_counts);

// Calculate totals from ALL balance transactions
$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN (type = 'deposit' OR type = 'admin_add' OR type = 'refund') THEN 
            CASE WHEN currency='VND' THEN amount ELSE amount * ? END 
        ELSE 0 END) as total_deposit,
        SUM(CASE WHEN (type = 'admin_deduct' OR type = 'purchase') THEN 
            CASE WHEN currency='VND' THEN amount ELSE amount * ? END 
        ELSE 0 END) as total_deduct
    FROM balance_transactions
    WHERE user_id = ?
");
$stmt->execute([$exchange_rate, $exchange_rate, $user_id]);
$totals = $stmt->fetch();
$total_deposit = $totals['total_deposit'] ?? 0;
$total_deposit = $totals['total_deposit'] ?? 0;
$total_spent = $totals['total_deduct'] ?? 0;

// Get Ticket Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_tickets = $stmt->fetchColumn();

// Note: Purchase transactions are already logged in balance_transactions
// No need to separately query orders table
?>

<div class="page-header fade-in">
    <div>
        <h1><i class="fas fa-exchange-alt"></i> Lịch Sử Giao Dịch</h1>
        <p>Theo dõi tất cả các giao dịch của bạn</p>
    </div>
    <div style="display: flex; gap: 1rem;">
        <a href="<?= url('naptien') ?>" class="btn btn-success">
            <i class="fas fa-plus-circle"></i> Nạp Tiền
        </a>
    </div>
</div>

<!-- Transaction Stats -->
<div class="stats-grid fade-in" style="gap:1.5rem">
    <div class="stat-card"
        style="background:linear-gradient(135deg,rgba(16,185,129,0.15),rgba(5,150,105,0.05));border-left:4px solid #10b981;box-shadow:0 4px 12px rgba(16,185,129,0.15)">
        <div class="stat-card-header">
            <div>
                <div class="stat-label" style="color:#f8fafc;font-weight:600;margin-bottom:0.5rem">💰 Tổng Nạp</div>
                <div class="stat-value" style="color:#10b981;font-size:1.8rem;font-weight:700">
                    <?php if ($current_currency === 'USD'): ?>
                        +$<?= number_format($total_deposit / $exchange_rate, 2) ?>
                    <?php else: ?>
                        +<?= number_format($total_deposit) ?> đ
                    <?php endif; ?>
                </div>
            </div>
            <div class="stat-icon"
                style="background:linear-gradient(135deg,#10b981,#059669);box-shadow:0 4px 12px rgba(16,185,129,0.3)">
                <i class="fas fa-arrow-down"></i>
            </div>
        </div>
        <div class="stat-change up" style="color:#10b981;font-weight:600">
            <i class="fas fa-plus"></i>
            <span>Tiền nạp vào</span>
        </div>
    </div>

    <div class="stat-card"
        style="background:linear-gradient(135deg,rgba(239,68,68,0.15),rgba(220,38,38,0.05));border-left:4px solid #ef4444;box-shadow:0 4px 12px rgba(239,68,68,0.15)">
        <div class="stat-card-header">
            <div>
                <div class="stat-label" style="color:#f8fafc;font-weight:600;margin-bottom:0.5rem">💸 Tổng Chi Tiêu
                </div>
                <div class="stat-value" style="color:#ef4444;font-size:1.8rem;font-weight:700">
                    <?php if ($current_currency === 'USD'): ?>
                        -$<?= number_format($total_spent / $exchange_rate, 2) ?>
                    <?php else: ?>
                        -<?= number_format($total_spent) ?> đ
                    <?php endif; ?>
                </div>
            </div>
            <div class="stat-icon"
                style="background:linear-gradient(135deg,#ef4444,#dc2626);box-shadow:0 4px 12px rgba(239,68,68,0.3)">
                <i class="fas fa-arrow-up"></i>
            </div>
        </div>
        <div class="stat-change down" style="color:#ef4444;font-weight:600">
            <i class="fas fa-minus"></i>
            <span>Tiền chi tiêu</span>
        </div>
    </div>

    <div class="stat-card balance-highlight"
        style="background:linear-gradient(135deg,rgba(139,92,246,0.15),rgba(109,40,217,0.05));border:2px solid rgba(139,92,246,0.3);box-shadow:0 4px 16px rgba(139,92,246,0.2)">
        <div class="stat-card-header">
            <div>
                <div class="stat-label" style="color:#f8fafc;font-weight:600;margin-bottom:0.5rem">👛 Số Dư Hiện Tại
                </div>
                <div class="stat-value"
                    style="color:#8b5cf6;font-size:1.8rem;font-weight:700;text-shadow:0 2px 4px rgba(0,0,0,0.1)">
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
        <div class="stat-change" style="color:#8b5cf6;font-weight:600">
            <i class="fas fa-info-circle"></i>
            <span>Số dư khả dụng</span>
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

<!-- Filter Tabs -->
<div class="card fade-in" style="margin-bottom: 2rem;">
    <div class="filter-tabs-container">
        <a href="?tab=transactions&type=all"
            class="btn <?= $filter_type == 'all' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
            <i class="fas fa-list"></i> Tất Cả (<?= $total_count ?>)
        </a>
        <a href="?tab=transactions&type=deposit"
            class="btn <?= $filter_type == 'deposit' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
            <i class="fas fa-arrow-down"></i> Nạp Tiền
            (<?= ($type_counts['deposit'] ?? 0) + ($type_counts['admin_add'] ?? 0) + ($type_counts['refund'] ?? 0) ?>)
        </a>
        <a href="?tab=transactions&type=deduct"
            class="btn <?= $filter_type == 'deduct' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
            <i class="fas fa-minus-circle"></i> Trừ Tiền
            (<?= ($type_counts['admin_deduct'] ?? 0) + ($type_counts['purchase'] ?? 0) ?>)
        </a>
        <a href="?tab=transactions&type=refund"
            class="btn <?= $filter_type == 'refund' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
            <i class="fas fa-undo"></i> Hoàn Tiền (<?= $type_counts['refund'] ?? 0 ?>)
        </a>
    </div>
</div>

<!-- Transactions Table -->
<?php if (empty($transactions)): ?>
    <div class="card fade-in">
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-receipt"></i>
            </div>
            <h3>Chưa Có Giao Dịch
                <?php
                if ($filter_type == 'deposit')
                    echo 'Nạp Tiền';
                elseif ($filter_type == 'deduct')
                    echo 'Trừ Tiền';
                elseif ($filter_type == 'refund')
                    echo 'Hoàn Tiền';
                else
                    echo '';
                ?>
            </h3>
            <p>Bạn chưa có giao dịch
                <?php
                if ($filter_type == 'deposit')
                    echo 'nạp tiền';
                elseif ($filter_type == 'deduct')
                    echo 'trừ tiền';
                elseif ($filter_type == 'refund')
                    echo 'hoàn tiền';
                else
                    echo '';
                ?> nào
            </p>
            <a href="<?= url('naptien') ?>" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Nạp Tiền Ngay
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card fade-in">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th class="text-center">Mã GD</th>
                        <th class="text-center">Loại</th>
                        <th class="text-center">Số Dư Trước</th>
                        <th class="text-center">Thay Đổi</th>
                        <th class="text-center">Số Dư Sau</th>
                        <th class="text-center">Ghi Chú</th>
                        <th class="text-center">Thời Gian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $trans): ?>
                        <tr>
                            <td class="text-center">
                                <code
                                    style="background: rgba(139,92,246,0.15); color: #8b5cf6; padding: 0.4rem 0.6rem; border-radius: 6px; font-weight: 600; font-size: 0.8rem;">
                                                                                                                #<?= substr($trans['id'], -8) ?>
                                                                                                            </code>
                            </td>
                            <td class="text-center">
                                <?php
                                $type_config = [
                                    'admin_add' => ['Admin Nạp', 'plus-circle', '#10b981', 'rgba(16,185,129,0.15)'],
                                    'deposit' => ['Nạp Tiền', 'plus-circle', '#10b981', 'rgba(16,185,129,0.15)'],
                                    'admin_deduct' => ['Admin Trừ', 'minus-circle', '#ef4444', 'rgba(239,68,68,0.15)'],
                                    'purchase' => ['Mua Hàng', 'shopping-cart', '#8b5cf6', 'rgba(139,92,246,0.15)'],
                                    'refund' => ['Hoàn Tiền', 'undo', '#f59e0b', 'rgba(245,158,11,0.15)']
                                ];

                                $config = $type_config[$trans['type']] ?? [ucfirst($trans['type']), 'circle', '#64748b', 'rgba(100,116,139,0.15)'];
                                ?>
                                <span class="badge" style="background: <?= $config[3] ?>; color: <?= $config[2] ?>;">
                                    <i class="fas fa-<?= $config[1] ?>"></i> <?= $config[0] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php
                                // Calculate Balance Before
                                $bal_before_val = $trans['balance_before'] ?? 0;
                                if ($current_currency === 'USD') {
                                    $bal_before_display = '$' . number_format($bal_before_val / $exchange_rate, 2);
                                } else {
                                    $bal_before_display = number_format($bal_before_val) . 'đ';
                                }
                                ?>
                                <div style="font-weight: 700; color: #10b981; font-size: 1.05rem;">
                                    <?= $bal_before_display ?>
                                </div>
                            </td>
                            <td style="padding: 16px 24px; text-align: center;">
                                <div style="font-weight: 700; font-size: 1.05rem;">
                                    <?php
                                    // Deposit types: admin_add, deposit, refund
                                    $is_deposit = in_array($trans['type'], ['admin_add', 'deposit', 'refund']);
                                    $amount_color = $is_deposit ? '#10b981' : '#ef4444';
                                    $sign = $is_deposit ? '+' : '-';

                                    // Calculate Change Amount based on current currency
                                    if ($current_currency === 'USD') {
                                        $amount_val = ($trans['currency'] === 'USD') ? $trans['amount'] : ($trans['amount'] / $exchange_rate);
                                        $amount_display = '$' . number_format($amount_val, 2);
                                    } else {
                                        $amount_val = ($trans['currency'] === 'USD') ? ($trans['amount'] * $exchange_rate) : $trans['amount'];
                                        $amount_display = number_format($amount_val) . 'đ';
                                    }
                                    ?>
                                    <span
                                        style="color: <?= $amount_color ?>; display: flex; align-items: center; justify-content: center; gap: 0.25rem; font-weight: 700;">
                                        <?= $sign ?>         <?= $amount_display ?>
                                    </span>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php
                                // Calculate Balance After
                                $bal_after_val = $trans['balance_after'] ?? 0;
                                if ($current_currency === 'USD') {
                                    $bal_after_display = '$' . number_format($bal_after_val / $exchange_rate, 2);
                                } else {
                                    $bal_after_display = number_format($bal_after_val) . 'đ';
                                }
                                ?>
                                <div style="font-weight: 700; color: #10b981; font-size: 1.05rem;">
                                    <?= $bal_after_display ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <small style="color: var(--text-main); line-height: 1.6;">
                                    <?= e($trans['note'] ?? 'Không có ghi chú') ?>
                                </small>
                            </td>
                            <td class="text-center">
                                <div style="color: var(--text-main); font-size: 14px; font-weight: 600;">
                                    <?= date('d/m/Y', strtotime($trans['created_at'])) ?>
                                </div>
                                <small style="color: var(--text-muted); font-size: 13px;">
                                    <?= date('H:i:s', strtotime($trans['created_at'])) ?>
                                </small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination-container fade-in" style="margin-top: 2rem;">
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?tab=transactions&type=<?= $filter_type ?>&page=<?= $page - 1 ?>" class="page-btn">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php else: ?>
                    <span class="page-btn disabled">
                        <i class="fas fa-chevron-left"></i>
                    </span>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                if ($start_page > 1): ?>
                    <a href="?tab=transactions&type=<?= $filter_type ?>&page=1" class="page-btn">1</a>
                    <?php if ($start_page > 2): ?>
                        <span class="page-dots">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="?tab=transactions&type=<?= $filter_type ?>&page=<?= $i ?>"
                        class="page-btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <span class="page-dots">...</span>
                    <?php endif; ?>
                    <a href="?tab=transactions&type=<?= $filter_type ?>&page=<?= $total_pages ?>"
                        class="page-btn"><?= $total_pages ?></a>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?tab=transactions&type=<?= $filter_type ?>&page=<?= $page + 1 ?>" class="page-btn">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="page-btn disabled">
                        <i class="fas fa-chevron-right"></i>
                    </span>
                <?php endif; ?>
            </div>
            <div class="pagination-info">
                <span>Trang <?= $page ?> / <?= $total_pages ?></span>
                <span class="separator">•</span>
                <span>Tổng <?= $total_transactions ?> giao dịch</span>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<style>
    /* ========================================
   PAGINATION STYLES
   ======================================== */
    .pagination-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
        padding: 1.5rem;
        background: rgba(30, 41, 59, 0.4);
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, 0.1);
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

    @media (max-width: 640px) {
        .pagination {
            flex-wrap: wrap;
            justify-content: center;
        }

        .pagination-info {
            font-size: 0.8rem;
        }
    }
</style>

<script>
    // Currency toggle functionality
    document.getElementById('currencyToggle').addEventListener('click', function () {
        const currentCurrency = document.getElementById('currencyText').textContent;
        const newCurrency = currentCurrency === 'VND' ? 'USD' : 'VND';

        // Set cookie
        document.cookie = `currency=${newCurrency}; path=/; max-age=31536000`;

        // Update display text
        document.getElementById('currencyText').textContent = newCurrency;

        // Show notification with reload option
        if (window.notify) {
            // Smooth reload after short delay
            if (window.smoothReloadWithProgress) {
                smoothReloadWithProgress(300);
            } else {
                setTimeout(() => location.reload(), 300);
            }
        }
    });
</script>