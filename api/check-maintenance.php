<?php
/**
 * Check Maintenance Status API
 * Returns whether user should be kicked due to maintenance
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

try {
    // Get maintenance settings
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('maintenance_mode', 'maintenance_start_time')");
    $stmt->execute();
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    $maintenance_mode = $settings['maintenance_mode'] ?? '0';
    $start_time = intval($settings['maintenance_start_time'] ?? 0);

    $should_kick = false;
    $remaining = 0;

    if ($maintenance_mode == '1' && $start_time > 0) {
        $current_time = time();
        $elapsed = $current_time - $start_time;
        $countdown_duration = 300; // 5 minutes
        $remaining = $countdown_duration - $elapsed;

        // Check if countdown ended
        if ($remaining <= 0) {
            // Check if user is not admin
            if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
                $should_kick = true;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'should_kick' => $should_kick,
        'remaining' => max(0, $remaining),
        'maintenance_active' => $maintenance_mode == '1'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>