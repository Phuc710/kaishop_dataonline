<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Delete user notifications
    $stmt = $pdo->prepare("DELETE FROM user_notifications WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Delete ticket messages
    $stmt = $pdo->prepare("DELETE tm FROM ticket_messages tm 
                           INNER JOIN tickets t ON tm.ticket_id = t.id 
                           WHERE t.user_id = ?");
    $stmt->execute([$user_id]);
    
    // Delete tickets
    $stmt = $pdo->prepare("DELETE FROM tickets WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Delete order items for user's orders
    $stmt = $pdo->prepare("DELETE oi FROM order_items oi 
                           INNER JOIN orders o ON oi.order_id = o.id 
                           WHERE o.user_id = ?");
    $stmt->execute([$user_id]);
    
    // Delete orders
    $stmt = $pdo->prepare("DELETE FROM orders WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Delete cart items
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Delete balance transactions
    $stmt = $pdo->prepare("DELETE FROM balance_transactions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Delete user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    
    // Commit transaction
    $pdo->commit();
    
    // Destroy session
    session_destroy();
    
    echo json_encode(['success' => true, 'message' => 'Tài khoản đã được xóa vĩnh viễn']);
    
} catch (Exception $e) {
    // Rollback on error
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
?>
