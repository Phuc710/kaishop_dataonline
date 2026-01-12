<?php
/**
 * Product Labels API - CRUD Operations
 * Quản lý nhãn sản phẩm
 */

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

// Check admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            handleList();
            break;
        case 'get':
            handleGet();
            break;
        case 'create':
            handleCreate();
            break;
        case 'update':
            handleUpdate();
            break;
        case 'delete':
            handleDelete();
            break;
        case 'upload':
            handleUpload();
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// List all labels
function handleList() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT * FROM product_labels 
        ORDER BY display_order ASC, id DESC
    ");
    
    $labels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'labels' => $labels
    ]);
}

// Get single label
function handleGet() {
    global $pdo;
    
    $id = $_GET['id'] ?? 0;
    
    $stmt = $pdo->prepare("SELECT * FROM product_labels WHERE id = ?");
    $stmt->execute([$id]);
    $label = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$label) {
        throw new Exception('Label not found');
    }
    
    echo json_encode([
        'success' => true,
        'label' => $label
    ]);
}

// Create new label
function handleCreate() {
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = trim($data['name'] ?? '');
    $image = trim($data['image'] ?? '');
    $display_order = intval($data['display_order'] ?? 0);
    $is_active = intval($data['is_active'] ?? 1);
    
    if (empty($name)) {
        throw new Exception('Tên nhãn không được để trống');
    }
    
    if (empty($image)) {
        throw new Exception('Vui lòng upload ảnh nhãn');
    }
    
    // Check duplicate name
    $stmt = $pdo->prepare("SELECT id FROM product_labels WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        throw new Exception('Tên nhãn đã tồn tại');
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO product_labels (name, image, display_order, is_active)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([$name, $image, $display_order, $is_active]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Tạo nhãn thành công',
        'id' => $pdo->lastInsertId()
    ]);
}

// Update label
function handleUpdate() {
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($data['id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $image = trim($data['image'] ?? '');
    $display_order = intval($data['display_order'] ?? 0);
    $is_active = intval($data['is_active'] ?? 1);
    
    if (!$id) {
        throw new Exception('ID không hợp lệ');
    }
    
    if (empty($name)) {
        throw new Exception('Tên nhãn không được để trống');
    }
    
    if (empty($image)) {
        throw new Exception('Vui lòng upload ảnh nhãn');
    }
    
    // Check duplicate name (exclude current)
    $stmt = $pdo->prepare("SELECT id FROM product_labels WHERE name = ? AND id != ?");
    $stmt->execute([$name, $id]);
    if ($stmt->fetch()) {
        throw new Exception('Tên nhãn đã tồn tại');
    }
    
    $stmt = $pdo->prepare("
        UPDATE product_labels 
        SET name = ?, image = ?, display_order = ?, is_active = ?
        WHERE id = ?
    ");
    
    $stmt->execute([$name, $image, $display_order, $is_active, $id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật nhãn thành công'
    ]);
}

// Delete label
function handleDelete() {
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['id'] ?? 0);
    
    if (!$id) {
        throw new Exception('ID không hợp lệ');
    }
    
    // Check if label is being used
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE label_id = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        throw new Exception("Không thể xóa nhãn này vì đang có {$count} sản phẩm sử dụng");
    }
    
    // Get image path to delete file
    $stmt = $pdo->prepare("SELECT image FROM product_labels WHERE id = ?");
    $stmt->execute([$id]);
    $label = $stmt->fetch();
    
    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM product_labels WHERE id = ?");
    $stmt->execute([$id]);
    
    // Delete image file if exists
    if ($label && !empty($label['image'])) {
        $imagePath = __DIR__ . '/../../' . ltrim($label['image'], '/');
        if (file_exists($imagePath)) {
            @unlink($imagePath);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Xóa nhãn thành công'
    ]);
}

// Upload label image
function handleUpload() {
    if (!isset($_FILES['image'])) {
        throw new Exception('Không có file được upload');
    }
    
    $file = $_FILES['image'];
    
    // Validate
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP)');
    }
    
    if ($file['size'] > 2 * 1024 * 1024) { // 2MB
        throw new Exception('Kích thước file không được vượt quá 2MB');
    }
    
    // Create upload directory
    $uploadDir = __DIR__ . '/../../uploads/labels/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'label_' . time() . '_' . uniqid() . '.' . $extension;
    $uploadPath = $uploadDir . $filename;
    
    // Move file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Lỗi khi upload file');
    }
    
    // Return URL
    $imageUrl = '/kaishop/uploads/labels/' . $filename;
    
    echo json_encode([
        'success' => true,
        'message' => 'Upload thành công',
        'url' => $imageUrl
    ]);
}
?>
