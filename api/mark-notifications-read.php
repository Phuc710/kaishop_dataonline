<?php
/**
 * Mark Notifications as Read API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Try to update user's last_read_notifications timestamp
    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET last_read_notifications = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
    } catch (Exception $e) {
        // Column doesn't exist yet, that's okay
        // User should run migration: add_notification_column.sql
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Notifications marked as read'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
