<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();
$ticket_id = $_POST['ticket_id'] ?? null;
$message = trim($_POST['message'] ?? '');
$image_path = null;

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/../assets/images/ticket_messages/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file = $_FILES['image'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Định dạng ảnh không hợp lệ']);
        exit;
    }

    if ($file['size'] > 5242880) {
        echo json_encode(['success' => false, 'message' => 'Ảnh quá lớn (tối đa 5MB)']);
        exit;
    }

    $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $filepath = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $image_path = 'assets/images/ticket_messages/' . $filename;
    }
}

if (!$ticket_id || (empty($message) && !$image_path)) {
    echo json_encode(['success' => false, 'message' => 'Cần có tin nhắn hoặc ảnh']);
    exit;
}

$where_clause = $is_admin ? "id = ?" : "id = ? AND user_id = ?";
$params = $is_admin ? [$ticket_id] : [$ticket_id, $user_id];

$stmt = $pdo->prepare("SELECT id, status FROM tickets WHERE $where_clause");
$stmt->execute($params);
$ticket = $stmt->fetch();

if (!$ticket) {
    echo json_encode(['success' => false, 'message' => 'Ticket không tồn tại']);
    exit;
}

if ($ticket['status'] === 'closed') {
    echo json_encode(['success' => false, 'message' => 'Ticket đã đóng']);
    exit;
}

try {
    $message_id = Snowflake::generateId();

    $stmt = $pdo->prepare("INSERT INTO ticket_messages (id, ticket_id, user_id, message, image, is_admin, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$message_id, $ticket_id, $user_id, $message, $image_path, $is_admin ? 1 : 0]);

    $new_status = $is_admin ? 'answered' : 'open';
    $stmt = $pdo->prepare("UPDATE tickets SET updated_at = NOW(), status = ? WHERE id = ?");
    $stmt->execute([$new_status, $ticket_id]);

    $stmt = $pdo->prepare("SELECT tm.*, u.username, u.role, u.avatar FROM ticket_messages tm LEFT JOIN users u ON tm.user_id = u.id WHERE tm.id = ?");
    $stmt->execute([$message_id]);
    $new_message = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'message_id' => $message_id,
        'data' => $new_message
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi server']);
}
