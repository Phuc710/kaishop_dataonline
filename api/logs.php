<?php
require_once '../config/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Export logs to CSV for download
if ($action === 'export_csv') {
    try {
        // Get all logs
        $query = "SELECT l.*, u.username, a.username as admin_username
FROM system_logs l
LEFT JOIN users u ON l.user_id = u.id
LEFT JOIN users a ON l.admin_id = a.id
ORDER BY l.created_at DESC";
        $stmt = $pdo->query($query);
        $logs = $stmt->fetchAll();

        if (empty($logs)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Không có log nào để export']);
            exit;
        }

        // Set headers for CSV download
        $filename = 'logs_backup_' . date('Y-m-d_H-i-s') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output CSV
        $output = fopen('php://output', 'w');

        // Add BOM for UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Write header
        fputcsv($output, [
            'ID',
            'Log Type',
            'Action',
            'Description',
            'User ID',
            'Username',
            'Admin ID',
            'Admin Username',
            'IP Address',
            'Country',
            'User Agent',
            'Old Value',
            'New Value',
            'Created At'
        ]);

        // Write data
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['log_type'],
                $log['action'],
                $log['description'],
                $log['user_id'] ?? '',
                $log['username'] ?? '',
                $log['admin_id'] ?? '',
                $log['admin_username'] ?? '',
                $log['ip_address'] ?? '',
                $log['country'] ?? '',
                $log['user_agent'] ?? '',
                $log['old_value'] ?? '',
                $log['new_value'] ?? '',
                $log['created_at']
            ]);
        }

        fclose($output);
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        exit;
    }
}

// Delete all logs (called after user confirms download)
if ($action === 'delete_all_logs') {
    header('Content-Type: application/json');
    try {
        // Count logs before deletion
        $count_stmt = $pdo->query("SELECT COUNT(*) FROM system_logs");
        $total_logs = $count_stmt->fetchColumn();

        if ($total_logs == 0) {
            echo json_encode(['success' => false, 'message' => 'Không có log nào để xóa']);
            exit;
        }

        // Delete all logs
        $delete_stmt = $pdo->prepare("DELETE FROM system_logs");
        $delete_stmt->execute();

        // Log this action
        $log_stmt = $pdo->prepare(
            "INSERT INTO system_logs (log_type, action, description, admin_id, ip_address, created_at)
VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $log_stmt->execute([
            'admin_action',
            'Xóa toàn bộ log',
            "Đã xóa $total_logs logs sau khi export",
            $_SESSION['user_id'],
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);

        echo json_encode([
            'success' => true,
            'message' => "Đã xóa $total_logs logs thành công",
            'deleted_count' => $total_logs
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'export_and_delete_all') {
    try {
        // Get all logs
        $query = "SELECT l.*, u.username, a.username as admin_username
FROM system_logs l
LEFT JOIN users u ON l.user_id = u.id
LEFT JOIN users a ON l.admin_id = a.id
ORDER BY l.created_at DESC";
        $stmt = $pdo->query($query);
        $logs = $stmt->fetchAll();

        if (empty($logs)) {
            echo json_encode(['success' => false, 'message' => 'Không có log nào để xóa']);
            exit;
        }

        $total_logs = count($logs);

        // Create export directory if not exists
        $export_dir = __DIR__ . '/../admin/exports';
        if (!file_exists($export_dir)) {
            if (!mkdir($export_dir, 0777, true)) {
                throw new Exception('Không thể tạo thư mục exports');
            }
        }

        // Check if directory is writable
        if (!is_writable($export_dir)) {
            throw new Exception('Thư mục exports không có quyền ghi');
        }

        // Generate filename with timestamp
        $filename = 'logs_backup_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = $export_dir . '/' . $filename;

        // Create CSV file
        $file = fopen($filepath, 'w');
        if (!$file) {
            throw new Exception('Không thể tạo file CSV');
        }

        // Add BOM for UTF-8
        fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Write header
        fputcsv($file, [
            'ID',
            'Log Type',
            'Action',
            'Description',
            'User ID',
            'Username',
            'Admin ID',
            'Admin Username',
            'IP Address',
            'Country',
            'User Agent',
            'Old Value',
            'New Value',
            'Created At'
        ]);

        // Write data
        foreach ($logs as $log) {
            fputcsv($file, [
                $log['id'],
                $log['log_type'],
                $log['action'],
                $log['description'],
                $log['user_id'] ?? '',
                $log['username'] ?? '',
                $log['admin_id'] ?? '',
                $log['admin_username'] ?? '',
                $log['ip_address'] ?? '',
                $log['country'] ?? '',
                $log['user_agent'] ?? '',
                $log['old_value'] ?? '',
                $log['new_value'] ?? '',
                $log['created_at']
            ]);
        }

        fclose($file);

        // Verify file was created
        if (!file_exists($filepath)) {
            throw new Exception('File CSV không được tạo thành công');
        }

        // Delete all logs
        $delete_stmt = $pdo->prepare("DELETE FROM system_logs");
        $delete_stmt->execute();

        // Log this action (create new entry after deletion)
        $log_stmt = $pdo->prepare(
            "INSERT INTO system_logs (log_type, action, description, admin_id, ip_address, created_at)
VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $log_stmt->execute([
            'admin_action',
            'Xóa toàn bộ log',
            "Đã xóa $total_logs logs và export ra file $filename",
            $_SESSION['user_id'],
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);

        echo json_encode([
            'success' => true,
            'message' => "Đã export và xóa $total_logs logs thành công",
            'filename' => $filename,
            'filepath' => 'admin/exports/' . $filename,
            'deleted_count' => $total_logs
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
}