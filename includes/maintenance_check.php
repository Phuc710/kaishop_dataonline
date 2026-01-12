<?php
/**
 * Maintenance Mode Middleware
 * Check if maintenance mode is enabled and kick ALL non-admin users
 */

// Skip for admin panel
if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
    return;
}

// Skip for maintenance page itself
if (strpos($_SERVER['PHP_SELF'], '/maintenance.php') !== false) {
    return;
}

// Skip for logout page (allow logout during maintenance)
if (strpos($_SERVER['PHP_SELF'], '/dangxuat.php') !== false) {
    return;
}

try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'");
    $stmt->execute();
    $maintenance_mode = $stmt->fetchColumn();
    
    if ($maintenance_mode == '1') {
        // Check if user is admin
        if (!isLoggedIn() || !isAdmin()) {
            // Log and destroy session for logged-in non-admin users
            if (isLoggedIn()) {
                try {
                    $user_id = $_SESSION['user_id'];
                    
                    // Log the kick
                    $stmt = $pdo->prepare("
                        INSERT INTO system_logs (log_type, action, description, user_id, ip_address)
                        VALUES ('system', 'kicked', 'User kicked due to maintenance mode', ?, ?)
                    ");
                    $stmt->execute([$user_id, $_SERVER['REMOTE_ADDR']]);
                } catch (Exception $e) {
                    // Ignore logging errors
                }
                
                // Destroy session
                session_destroy();
                session_start(); // Restart for flash message
            }
            
            // Redirect to maintenance page
            header('Location: /kaishop/maintenance');
            exit;
        }
    }
} catch (Exception $e) {
    // If error checking maintenance mode, skip and continue
    error_log("Maintenance check error: " . $e->getMessage());
}
?>
