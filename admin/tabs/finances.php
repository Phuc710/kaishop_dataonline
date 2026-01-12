<?php
/**
 * HỆ THỐNG TÀI CHÍNH
 * Quản lý Tiền Nạp và Tiền Hoàn
 */

// Get sub-tab
$sub_tab = $_GET['sub'] ?? 'overview';

// Date filter
$date_filter = $_GET['date_filter'] ?? 'all';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

if ($date_filter == 'today') {
    $date_from = date('Y-m-d');
    $date_to = date('Y-m-d');
} elseif ($date_filter == 'week') {
    // Current week (Monday to Sunday)
    $date_from = date('Y-m-d', strtotime('monday this week'));
    $date_to = date('Y-m-d', strtotime('sunday this week'));
} elseif ($date_filter == 'month') {
    $date_from = date('Y-m-01');
    $date_to = date('Y-m-t'); // Last day of month
}

?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-money-bill-wave"></i> Hệ Thống Tài Chính</h1>
        <p>Quản lý tiền nạp và tiền hoàn cho người dùng</p>
    </div>
</div>

<!-- Tab Navigation -->
<div class="card" style="margin-bottom: 1.5rem">
    <div
        style="display: flex; gap: 0.5rem; padding: 1rem; border-bottom: 2px solid rgba(139, 92, 246, 0.1); overflow-x: auto">
        <?php
        $tabs = [
            'overview' => ['icon' => 'chart-line', 'label' => 'Tổng Quan'],
            'deposits' => ['icon' => 'arrow-down', 'label' => 'Nạp Tiền'],
            'refunds' => ['icon' => 'undo', 'label' => 'Hoàn Tiền'],
            'adjustments' => ['icon' => 'sliders-h', 'label' => 'Admin +/-']
        ];

        foreach ($tabs as $key => $tab):
            $active = ($sub_tab === $key) ? 'background: #8b5cf6; color: #fff; border: 1px solid #8b5cf6;' : 'background: #0f172a; color: #8b5cf6; border: 1px solid rgba(139, 92, 246, 0.2);';
            ?>
            <a href="?tab=finances&sub=<?= $key ?>&date_filter=<?= $date_filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>"
                style="padding: 0.6rem 1.2rem; border-radius: 4px; text-decoration: none; font-weight: 600; white-space: nowrap; <?= $active ?>">
                <i class="fas fa-<?= $tab['icon'] ?>"></i> <?= $tab['label'] ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php
// Load sub-tab content
$sub_tab_file = __DIR__ . "/finances/{$sub_tab}.php";
if (file_exists($sub_tab_file)) {
    include $sub_tab_file;
} else {
    echo '<div class="card"><div style="padding: 3rem; text-align: center; color: #64748b">
        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3"></i>
        <p>Tab không tồn tại</p>
    </div></div>';
}
?>