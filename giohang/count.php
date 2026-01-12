<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['count' => 0]);
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
echo json_encode(['count' => (int)$stmt->fetchColumn()]);
?>
