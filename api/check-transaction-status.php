<?php
/**
 * Check Transaction Status API
 * Used by polling to detect when payment is processed
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$transaction_code = $_GET['code'] ?? '';

if (!$transaction_code) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing transaction code']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT status, completed_at, amount 
        FROM payment_transactions 
        WHERE transaction_code = ? AND user_id = ?
    ");
    $stmt->execute([$transaction_code, getCurrentUser()['id']]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        echo json_encode(['error' => 'Transaction not found']);
        exit;
    }

    echo json_encode([
        'status' => $transaction['status'],
        'completed_at' => $transaction['completed_at'],
        'amount' => $transaction['amount']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
