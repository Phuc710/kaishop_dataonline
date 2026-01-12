<?php
/**
 * AJAX Endpoint: Instant Ban User
 * Blocks account, IP, fingerprint, and forces immediate logout
 */

session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/SecurityLogger.php';
require_once __DIR__ . '/../../config/RateLimiter.php';

header('Content-Type: application/json');

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user_id = $input['user_id'] ?? null;
$reason = $input['reason'] ?? 'Banned by admin';
$ban_account = $input['ban_account'] ?? true;
$ban_ip = $input['ban_ip'] ?? true;
$ban_fingerprint = $input['ban_fingerprint'] ?? true;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Get user's current IP and fingerprint from latest security log
    $stmt = $pdo->prepare("
        SELECT ip, fingerprint
        FROM security_logs
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $latest_log = $stmt->fetch(PDO::FETCH_ASSOC);

    $user_ip = $latest_log['ip'] ?? null;
    $user_fingerprint = $latest_log['fingerprint'] ?? null;

    $actions = [];

    // 1. Lock the user account (if selected)
    if ($ban_account) {
        $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        $stmt->execute([$user_id]);
        $actions['account_locked'] = true;
    } else {
        $actions['account_locked'] = false;
    }

    // 2. Block IP if available and selected
    $logger = new SecurityLogger($pdo);
    $rateLimiter = new RateLimiter($pdo, $logger);

    if ($ban_ip && $user_ip) {
        $rateLimiter->blockIP($user_ip, $reason, null, true); // Permanent block
        $actions['ip_blocked'] = true;
    } else {
        $actions['ip_blocked'] = false;
    }

    // 3. Block fingerprint if available and selected
    if ($ban_fingerprint && $user_fingerprint) {
        $rateLimiter->blockFingerprint($user_fingerprint, $reason, null, true); // Permanent block
        $actions['fingerprint_blocked'] = true;
    } else {
        $actions['fingerprint_blocked'] = false;
    }

    // 4. Destroy all user sessions (force logout) - only if account is locked
    if ($ban_account) {
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $actions['sessions_destroyed'] = true;
    } else {
        $actions['sessions_destroyed'] = false;
    }

    // 5. Create a ban notification in user_bans table - only if account is locked
    if ($ban_account) {
        $stmt = $pdo->prepare("
            INSERT INTO user_bans (user_id, banned_at, banned_by, reason)
            VALUES (?, NOW(), ?, ?)
            ON DUPLICATE KEY UPDATE banned_at = NOW(), banned_by = ?, reason = ?
        ");
        $stmt->execute([$user_id, $_SESSION['user_id'], $reason, $_SESSION['user_id'], $reason]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Ban actions executed successfully',
        'details' => $actions
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Instant Ban Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
