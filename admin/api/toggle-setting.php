<?php
/**
 * Toggle System Settings API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';

// Check admin permission
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $setting_key = $data['setting_key'] ?? '';
    $setting_value = $data['setting_value'] ?? '';
    
    if (empty($setting_key)) {
        throw new Exception('Setting key is required');
    }
    
    // Valid settings
    $validSettings = [
        'allow_registration',
        'enable_cart',
        'maintenance_mode',
        'enable_voucher',
        'enable_product_reviews'
    ];
    
    if (!in_array($setting_key, $validSettings)) {
        throw new Exception('Invalid setting key');
    }
    
    // Update or insert setting
    $stmt = $pdo->prepare("
        INSERT INTO settings (setting_key, setting_value, description)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    
    $descriptions = [
        'allow_registration' => 'Cho phép đăng ký tài khoản mới',
        'enable_cart' => 'Bật giỏ hàng',
        'maintenance_mode' => 'Chế độ bảo trì',
        'enable_voucher' => 'Bật mã giảm giá (Voucher)',
        'enable_product_reviews' => 'Bật bình luận sản phẩm'
    ];
    
    $stmt->execute([
        $setting_key,
        $setting_value,
        $descriptions[$setting_key]
    ]);
    
    // Log the action
    $stmt = $pdo->prepare("
        INSERT INTO system_logs (log_type, action, description, user_id, ip_address)
        VALUES ('setting', 'update', ?, ?, ?)
    ");
    $stmt->execute([
        "Thay đổi cài đặt: {$descriptions[$setting_key]} = " . ($setting_value == '1' ? 'Bật' : 'Tắt'),
        $_SESSION['user_id'],
        $_SERVER['REMOTE_ADDR']
    ]);
    
    // Special handling for maintenance mode
    if ($setting_key === 'maintenance_mode' && $setting_value == '1') {
        // Count non-admin users to be kicked
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role != 'admin'");
        $stmt->execute();
        $user_count = $stmt->fetchColumn();
        
        // Log the maintenance activation
        $stmt = $pdo->prepare("
            INSERT INTO system_logs (log_type, action, description, user_id, ip_address)
            VALUES ('system', 'maintenance_on', ?, ?, ?)
        ");
        $stmt->execute([
            "Maintenance mode activated by admin. {$user_count} users will be kicked.",
            $_SESSION['user_id'],
            $_SERVER['REMOTE_ADDR']
        ]);
    } elseif ($setting_key === 'maintenance_mode' && $setting_value == '0') {
        // Log the maintenance deactivation
        $stmt = $pdo->prepare("
            INSERT INTO system_logs (log_type, action, description, user_id, ip_address)
            VALUES ('system', 'maintenance_off', 'Maintenance mode deactivated by admin. System restored.', ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $_SERVER['REMOTE_ADDR']
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Cài đặt đã được cập nhật',
        'setting_key' => $setting_key,
        'setting_value' => $setting_value
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
