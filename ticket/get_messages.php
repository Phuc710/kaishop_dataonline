<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();
$ticket_id = $_GET['ticket_id'] ?? null;
$last_id = $_GET['last_id'] ?? 0;

if (!$ticket_id) {
    echo json_encode(['success' => false, 'message' => 'Missing ticket_id']);
    exit;
}

$where_clause = $is_admin ? "id = ?" : "id = ? AND user_id = ?";
$params = $is_admin ? [$ticket_id] : [$ticket_id, $user_id];

$stmt = $pdo->prepare("SELECT id FROM tickets WHERE $where_clause");
$stmt->execute($params);

if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($last_id > 0) {
    $stmt = $pdo->prepare("
        SELECT tm.*, u.username, u.role, u.avatar
        FROM ticket_messages tm
        LEFT JOIN users u ON tm.user_id = u.id
        WHERE tm.ticket_id = ? AND tm.id > ?
        ORDER BY tm.created_at ASC
    ");
    $stmt->execute([$ticket_id, $last_id]);
    $messages = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'count' => count($messages)
    ]);
} else {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM ticket_messages WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);
    $result = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'count' => (int) $result['count']
    ]);
}
