<?php
session_start();
header('Content-Type: application/json');

// Check admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['success' => false, 'error' => 'Không có quyền']));
}

// Check file
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    die(json_encode(['success' => false, 'error' => 'Không có file']));
}

$file = $_FILES['image'];

// Check extension
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
    die(json_encode(['success' => false, 'error' => 'File không hợp lệ']));
}

// Create directory
$upload_dir = __DIR__ . '/../uploads/popups/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate filename
$filename = 'popup_' . time() . '.' . $ext;
$filepath = $upload_dir . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode([
        'success' => true,
        'path' => 'uploads/popups/' . $filename,
        'width' => 800,
        'height' => 500
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Không thể lưu file']);
}
