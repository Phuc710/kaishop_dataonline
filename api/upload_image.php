<?php
/**
 * API Upload Image
 * Hỗ trợ GIF, JPG, PNG, WEBP với auto-resize
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// Check auth
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Check method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check file
if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

// Get folder type
$folder = $_POST['folder'] ?? 'products';

// Validate folder
$allowedFolders = ['products', 'logos', 'banners'];
if (!in_array($folder, $allowedFolders)) {
    $folder = 'products';
}

try {
    // Upload image
    $uploader = new ImageUploader();
    $result = $uploader->upload($_FILES['image'], $folder);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'filename' => $result['filename'],
            'path' => $result['path'],
            'url' => asset('images/uploads/' . $result['path']),
            'size' => ImageUploader::formatFileSize($result['size']),
            'type' => $result['type']
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
