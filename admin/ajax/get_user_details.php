<?php
/**
 * AJAX Endpoint: Get User Details
 * Returns comprehensive user information including IP history, fingerprints, and security logs
 */

// Suppress all output before JSON
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/SecurityLogger.php';
require_once __DIR__ . '/../../includes/wallet_helper.php';

// Clear any output buffer
ob_end_clean();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

try {
    global $pdo; // Ensure $pdo is available for getExchangeRate()

    // Get user basic info
    $stmt = $pdo->prepare("
        SELECT id, username, email, role, balance_vnd, balance_usd, is_active, created_at, last_login
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // Get current IP and fingerprint from latest security log
    $stmt = $pdo->prepare("
        SELECT ip, fingerprint
        FROM security_logs
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $latest_log = $stmt->fetch(PDO::FETCH_ASSOC);

    $user['current_ip'] = $latest_log['ip'] ?? null;
    $user['current_fingerprint'] = $latest_log['fingerprint'] ?? null;

    // Get IP history (unique IPs with count and last seen)
    $stmt = $pdo->prepare("
        SELECT 
            ip,
            country_code,
            MAX(created_at) as last_seen,
            COUNT(*) as count
        FROM security_logs
        WHERE user_id = ?
        GROUP BY ip, country_code
        ORDER BY last_seen DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $user['ip_history'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count total unique IPs
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT ip) as total
        FROM security_logs
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $user['total_ips'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Count total unique fingerprints
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT fingerprint) as total
        FROM security_logs
        WHERE user_id = ? AND fingerprint IS NOT NULL
    ");
    $stmt->execute([$user_id]);
    $user['total_fingerprints'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get recent security logs
    $stmt = $pdo->prepare("
        SELECT ip, fingerprint, threat_level, attack_type, is_blocked, created_at
        FROM security_logs
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $user['security_logs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get exchange rate using helper function
    $exchange_rate = getExchangeRate();

    echo json_encode([
        'success' => true,
        'user' => $user,
        'exchange_rate' => $exchange_rate
    ]);

} catch (Exception $e) {
    error_log("Get User Details Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
