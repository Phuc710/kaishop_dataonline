<?php
/**
 * Real-time Ban Check API
 * Returns ban status for the current logged-in user
 */

// Suppress all output before JSON
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once __DIR__ . '/../config/config.php';

// Clear any output buffer
ob_end_clean();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['banned' => false]);
    exit;
}

try {
    // Check if user is banned
    $stmt = $pdo->prepare("
        SELECT ub.*, u.is_active
        FROM users u
        LEFT JOIN user_bans ub ON u.id = ub.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $is_banned = false;
    $ban_reason = '';

    if ($result) {
        // Check if account is locked OR explicitly banned
        if ($result['is_active'] == 0 || $result['user_id']) {
            $is_banned = true;
            $ban_reason = $result['reason'] ?? 'Your account has been locked by an administrator';
        }
    }

    echo json_encode([
        'banned' => $is_banned,
        'reason' => $ban_reason,
        'banned_at' => $result['banned_at'] ?? null
    ]);

} catch (Exception $e) {
    error_log("Ban Check Error: " . $e->getMessage());
    echo json_encode(['banned' => false]);
}
