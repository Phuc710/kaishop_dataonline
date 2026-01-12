<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$product_ids = $data['product_ids'] ?? [];

if (empty($product_ids) || !is_array($product_ids)) {
    echo json_encode([]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($product_ids), '?'));
$stmt = $pdo->prepare("SELECT id, stock FROM products WHERE id IN ($placeholders)");
$stmt->execute($product_ids);
$results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

echo json_encode($results);
?>
