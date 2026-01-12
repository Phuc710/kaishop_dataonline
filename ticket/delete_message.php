<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only admin can delete messages
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$message_id = $data['message_id'] ?? null;
$ticket_id = $data['ticket_id'] ?? null;

// Debug log
error_log("Delete message - Received message_id: " . var_export($message_id, true));
error_log("Delete message - Received ticket_id: " . var_export($ticket_id, true));

if (!$message_id || !$ticket_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Verify message belongs to ticket - Use string comparison for BigInt
    $stmt = $pdo->prepare("SELECT id, ticket_id FROM ticket_messages WHERE CAST(id AS CHAR) = ? AND CAST(ticket_id AS CHAR) = ?");
    $stmt->execute([strval($message_id), strval($ticket_id)]);
    $found = $stmt->fetch();
    
    if (!$found) {
        error_log("Delete message - Message not found. ID: $message_id, Ticket: $ticket_id");
        echo json_encode(['success' => false, 'message' => 'Message not found or does not belong to this ticket']);
        exit;
    }
    
    error_log("Delete message - Found message, proceeding to delete");
    
    // Delete message - Use CAST for BigInt safety
    $stmt = $pdo->prepare("DELETE FROM ticket_messages WHERE CAST(id AS CHAR) = ?");
    
    if ($stmt->execute([strval($message_id)])) {
        error_log("Delete message - Successfully deleted");
        echo json_encode(['success' => true, 'message' => 'Message deleted successfully']);
    } else {
        error_log("Delete message - Failed to delete");
        echo json_encode(['success' => false, 'message' => 'Failed to delete message']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
