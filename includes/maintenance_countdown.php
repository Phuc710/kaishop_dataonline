<?php
/**
 * Maintenance Countdown Banner for Users
 * Shows 5-minute countdown when maintenance mode is scheduled
 * Auto-kicks users after countdown ends
 */

// Skip for admin panel
if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
    return;
}

// Skip for maintenance page itself
if (strpos($_SERVER['PHP_SELF'], '/maintenance.php') !== false) {
    return;
}

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

    if ($maintenance_mode == '1' && $start_time > 0) {
        $current_time = time();
        $elapsed = $current_time - $start_time;
        $countdown_duration = 300; // 5 minutes = 300 seconds
        $remaining = $countdown_duration - $elapsed;

        // If countdown ended, kick non-admin users
        if ($remaining <= 0) {
            if (!isAdmin()) {
                // Force logout and redirect to maintenance
                if (isLoggedIn()) {
                    session_destroy();
                    session_start();
                }
                header('Location: /kaishop/maintenance');
                exit;
            }
        } else {
            // Show countdown banner for ALL users (including admin for testing)
            $end_timestamp = $start_time + $countdown_duration;
            $is_admin = isAdmin();
            ?>
            <!-- Maintenance Banner - Bottom Center -->
            <div id="maintenance-banner" style="
                    position: fixed;
                    bottom: 30px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: linear-gradient(135deg, #e8444473, #991b1b95);
                    color: white;
                    padding: 1rem 1.5rem;
                    border-radius: 16px;
                    border: 2px solid #dd3a3aff;
                    box-shadow: 0 8px 25px rgba(220, 38, 38, 0.6);
                    font-family: 'Segoe UI', sans-serif;
                    z-index: 999999;
                    backdrop-filter: blur(10px);
                    max-width: 400px;
                    text-align: center;
                ">
                <div style="margin-bottom: 0.5rem;">
                    <div style="font-weight: 800; font-size: 1rem; margin-bottom: 0.3rem;">
                        🚨 HỆ THỐNG BẢO TRÌ 🚨
                    </div>
                    <div style="font-size: 1rem; opacity: 0.9;">
                        Hệ thống sẽ bảo trì sau <strong id="countdown-display" style="
                                color: #ffffff;
                                font-weight: 900;
                                font-size: 1.2rem;
                                font-family: 'Segoe UI', sans-serif;
                            ">5:00</strong> nữa
                    </div>
                </div>
                <?php if ($is_admin): ?>
                    <div style="font-size: 0.75rem; color: #fbbf24; margin-top: 0.5rem;">
                        Hoàn thiện thanh toán trước khi hết thời gian
                    </div>
                <?php endif; ?>
            </div>

            <style>
                /* No animations - static banner */
            </style>

            <script>
                (function () {
                    const endTime = <?= $end_timestamp ?>;
                    const countdownDisplay = document.getElementById('countdown-display');

                    function updateCountdown() {
                        const now = Math.floor(Date.now() / 1000);
                        const remaining = endTime - now;

                        if (remaining <= 0) {
                            // Time's up - redirect to maintenance (unless admin)
                            <?php if (!$is_admin): ?>
                                window.location.href = '/kaishop/maintenance';
                                return;
                            <?php else: ?>
                                // Admin - just show 00:00
                                countdownDisplay.textContent = '00:00';
                                countdownDisplay.style.background = 'rgba(251, 191, 36, 0.3)';
                                return;
                            <?php endif; ?>
                        }

                        const minutes = Math.floor(remaining / 60);
                        const seconds = remaining % 60;

                        countdownDisplay.textContent =
                            String(minutes).padStart(2, '0') + ':' +
                            String(seconds).padStart(2, '0');

                        // Update every second
                        setTimeout(updateCountdown, 1000);
                    }

                    // Start countdown
                    updateCountdown();

                    // Also check server-side every 10 seconds
                    setInterval(() => {
                        fetch('/kaishop/api/check-maintenance')
                            .then(r => r.json())
                            .then(data => {
                                if (data.should_kick) {
                                    window.location.href = '/kaishop/maintenance';
                                }
                            })
                            .catch(e => console.error('Maintenance check failed:', e));
                    }, 10000);
                })();
            </script>
            <?php
        }
    }
} catch (Exception $e) {
    // Silent fail
    error_log("Maintenance countdown error: " . $e->getMessage());
}
?>