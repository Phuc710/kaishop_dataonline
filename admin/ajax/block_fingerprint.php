<?php
/**
 * AJAX Endpoint: Block Fingerprint
 */

session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/RateLimiter.php';
require_once __DIR__ . '/../../config/SecurityLogger.php';

header('Content-Type: application/json');

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$fingerprint = $input['fingerprint'] ?? null;
$reason = $input['reason'] ?? 'Blocked by admin';
$permanent = $input['permanent'] ?? false;
$duration = $input['duration'] ?? 3600;

if (!$fingerprint) {
    echo json_encode(['success' => false, 'message' => 'Fingerprint required']);
    exit;
}

try {
    $logger = new SecurityLogger($pdo);
    $rateLimiter = new RateLimiter($pdo, $logger);

    // Don't allow blocking admin's own fingerprint
    $currentFingerprint = SecurityLogger::getFingerprint();
    if ($fingerprint === $currentFingerprint) {
        echo json_encode(['success' => false, 'message' => 'Cannot block your own fingerprint']);
        exit;
    }

    $result = $rateLimiter->blockFingerprint($fingerprint, $reason, $permanent ? null : $duration, $permanent);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Fingerprint blocked successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to block fingerprint']);
    }
} catch (Exception $e) {
    error_log("Block Fingerprint Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
