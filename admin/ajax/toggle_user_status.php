<?php
/**
 * AJAX Endpoint: Toggle User Status (Lock/Unlock)
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user_id = $input['user_id'] ?? null;
$status = $input['status'] ?? null;

if (!$user_id || !isset($status)) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $result = $stmt->execute([$status, $user_id]);

    if ($result) {
        $action = $status ? 'unlocked' : 'locked';
        echo json_encode([
            'success' => true,
            'message' => "User {$action} successfully"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
    }
} catch (Exception $e) {
    error_log("Toggle User Status Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
