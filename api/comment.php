<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = $data['product_id'] ?? 0;
$content = trim($data['content'] ?? '');

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Nội dung không được trống']);
    exit;
}

$id = Snowflake::generateId();
$stmt = $pdo->prepare("INSERT INTO comments (id, product_id, user_id, content, is_approved) VALUES (?, ?, ?, ?, 1)");
if ($stmt->execute([$id, $product_id, $_SESSION['user_id'], $content])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
?>
