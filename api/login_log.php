<?php
/**
 * API Nhật Ký Đăng Nhập User
 * Endpoint: /api/login_log.php
 * 
 * Chức năng:
 * - Ghi log đăng nhập thành công/thất bại
 * - Lấy lịch sử đăng nhập của user
 * - Thống kê đăng nhập
 * - Xóa log cũ
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

// Helper function để trả về JSON response
function jsonResponse($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Lấy thông tin IP và User Agent
function getClientInfo() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    
    return [
        'ip' => $ip,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
}

// Lấy location từ IP (optional - có thể tích hợp API geo)
function getLocationFromIP($ip) {
    // Có thể tích hợp với ipapi.co, ipinfo.io, etc.
    // Ví dụ đơn giản:
    if ($ip === '::1' || $ip === '127.0.0.1') {
        return 'Local';
    }
    return null; // Hoặc gọi API external
}

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($action) {
        
        // ============================================================
        // GHI LOG ĐĂNG NHẬP
        // ============================================================
        case 'create':
            if ($method !== 'POST') {
                jsonResponse(false, 'Method not allowed', null, 405);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            $userId = $data['user_id'] ?? null;
            $username = $data['username'] ?? '';
            $status = $data['status'] ?? 'success'; // success, failed
            $failReason = $data['fail_reason'] ?? null;
            
            if (empty($userId) && $status === 'success') {
                jsonResponse(false, 'User ID là bắt buộc cho đăng nhập thành công');
            }

            $clientInfo = getClientInfo();
            $logId = Snowflake::generateId();
            
            // Xác định action description
            if ($status === 'success') {
                $description = "User {$username} đăng nhập thành công";
            } else {
                $description = "Đăng nhập thất bại - {$username}" . ($failReason ? ": {$failReason}" : '');
            }

            // Lưu vào system_logs
            $stmt = $pdo->prepare("
                INSERT INTO system_logs (
                    id, log_type, user_id, action, description, 
                    ip_address, user_agent, created_at
                ) VALUES (?, 'user_login', ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $logId,
                $status === 'success' ? $userId : null,
                $status === 'success' ? 'login_success' : 'login_failed',
                $description,
                $clientInfo['ip'],
                $clientInfo['user_agent']
            ]);

            jsonResponse(true, 'Đã ghi log đăng nhập', [
                'log_id' => $logId,
                'status' => $status,
                'ip' => $clientInfo['ip']
            ]);
            break;

        // ============================================================
        // LẤY LỊCH SỬ ĐĂNG NHẬP CỦA USER
        // ============================================================
        case 'history':
            if (!isLoggedIn()) {
                jsonResponse(false, 'Vui lòng đăng nhập', null, 401);
            }

            $userId = $_SESSION['user_id'];
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = (int)($_GET['offset'] ?? 0);
            $statusFilter = $_GET['status'] ?? 'all'; // all, success, failed

            $limit = min($limit, 100); // Max 100 records

            // Build query
            $whereClause = "log_type = 'user_login' AND user_id = ?";
            $params = [$userId];

            if ($statusFilter === 'success') {
                $whereClause .= " AND action = 'login_success'";
            } elseif ($statusFilter === 'failed') {
                $whereClause .= " AND action = 'login_failed'";
            }

            // Đếm tổng số
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM system_logs WHERE {$whereClause}");
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            // Lấy danh sách
            $stmt = $pdo->prepare("
                SELECT 
                    id,
                    action,
                    description,
                    ip_address,
                    user_agent,
                    created_at
                FROM system_logs 
                WHERE {$whereClause}
                ORDER BY created_at DESC
                LIMIT {$limit} OFFSET {$offset}
            ");
            $stmt->execute($params);
            $logs = $stmt->fetchAll();

            // Format data
            foreach ($logs as &$log) {
                $log['status'] = strpos($log['action'], 'success') !== false ? 'success' : 'failed';
                $log['browser'] = getBrowserFromUserAgent($log['user_agent']);
                $log['device'] = getDeviceFromUserAgent($log['user_agent']);
            }

            jsonResponse(true, 'Lấy lịch sử thành công', [
                'logs' => $logs,
                'pagination' => [
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $total
                ]
            ]);
            break;

        // ============================================================
        // THỐNG KÊ ĐĂNG NHẬP
        // ============================================================
        case 'stats':
            if (!isLoggedIn()) {
                jsonResponse(false, 'Vui lòng đăng nhập', null, 401);
            }

            $userId = $_SESSION['user_id'];
            $days = (int)($_GET['days'] ?? 30); // Thống kê n ngày gần nhất
            $days = min($days, 365); // Max 1 năm

            // Tổng số lần đăng nhập
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_logins,
                    SUM(CASE WHEN action = 'login_success' THEN 1 ELSE 0 END) as success_logins,
                    SUM(CASE WHEN action = 'login_failed' THEN 1 ELSE 0 END) as failed_logins
                FROM system_logs 
                WHERE log_type = 'user_login' 
                    AND user_id = ?
                    AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$userId, $days]);
            $stats = $stmt->fetch();

            // Lần đăng nhập gần nhất
            $stmt = $pdo->prepare("
                SELECT created_at, ip_address 
                FROM system_logs 
                WHERE log_type = 'user_login' 
                    AND user_id = ?
                    AND action = 'login_success'
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $lastLogin = $stmt->fetch();

            // IP addresses đã sử dụng
            $stmt = $pdo->prepare("
                SELECT DISTINCT ip_address, COUNT(*) as count
                FROM system_logs 
                WHERE log_type = 'user_login' 
                    AND user_id = ?
                    AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY ip_address
                ORDER BY count DESC
                LIMIT 5
            ");
            $stmt->execute([$userId, $days]);
            $topIPs = $stmt->fetchAll();

            // Thống kê theo ngày (7 ngày gần nhất)
            $stmt = $pdo->prepare("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total,
                    SUM(CASE WHEN action = 'login_success' THEN 1 ELSE 0 END) as success,
                    SUM(CASE WHEN action = 'login_failed' THEN 1 ELSE 0 END) as failed
                FROM system_logs 
                WHERE log_type = 'user_login' 
                    AND user_id = ?
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            $stmt->execute([$userId]);
            $dailyStats = $stmt->fetchAll();

            jsonResponse(true, 'Lấy thống kê thành công', [
                'summary' => [
                    'total_logins' => (int)$stats['total_logins'],
                    'success_logins' => (int)$stats['success_logins'],
                    'failed_logins' => (int)$stats['failed_logins'],
                    'success_rate' => $stats['total_logins'] > 0 
                        ? round(($stats['success_logins'] / $stats['total_logins']) * 100, 2) 
                        : 0,
                    'period_days' => $days
                ],
                'last_login' => $lastLogin,
                'top_ips' => $topIPs,
                'daily_stats' => $dailyStats
            ]);
            break;

        // ============================================================
        // ADMIN: LẤY TẤT CẢ LOG ĐĂNG NHẬP
        // ============================================================
        case 'all':
            requireAdmin();

            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            $userId = $_GET['user_id'] ?? null;
            $status = $_GET['status'] ?? 'all';

            $whereClause = "log_type = 'user_login'";
            $params = [];

            if ($userId) {
                $whereClause .= " AND user_id = ?";
                $params[] = $userId;
            }

            if ($status === 'success') {
                $whereClause .= " AND action = 'login_success'";
            } elseif ($status === 'failed') {
                $whereClause .= " AND action = 'login_failed'";
            }

            // Count
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM system_logs WHERE {$whereClause}");
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            // Get logs
            $stmt = $pdo->prepare("
                SELECT 
                    l.id,
                    l.user_id,
                    u.username,
                    u.email,
                    l.action,
                    l.description,
                    l.ip_address,
                    l.user_agent,
                    l.created_at
                FROM system_logs l
                LEFT JOIN users u ON l.user_id = u.id
                WHERE {$whereClause}
                ORDER BY l.created_at DESC
                LIMIT {$limit} OFFSET {$offset}
            ");
            $stmt->execute($params);
            $logs = $stmt->fetchAll();

            jsonResponse(true, 'Lấy danh sách log thành công', [
                'logs' => $logs,
                'pagination' => [
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset
                ]
            ]);
            break;

        // ============================================================
        // ADMIN: XÓA LOG CŨ
        // ============================================================
        case 'cleanup':
            requireAdmin();

            $days = (int)($_POST['days'] ?? 90); // Xóa log cũ hơn n ngày
            
            $stmt = $pdo->prepare("
                DELETE FROM system_logs 
                WHERE log_type = 'user_login' 
                    AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$days]);
            $deleted = $stmt->rowCount();

            jsonResponse(true, "Đã xóa {$deleted} log cũ hơn {$days} ngày", [
                'deleted_count' => $deleted
            ]);
            break;

        default:
            jsonResponse(false, 'Action không hợp lệ', null, 400);
    }

} catch (PDOException $e) {
    error_log("Login Log API Error: " . $e->getMessage());
    jsonResponse(false, 'Lỗi database: ' . $e->getMessage(), null, 500);
} catch (Exception $e) {
    error_log("Login Log API Error: " . $e->getMessage());
    jsonResponse(false, 'Lỗi: ' . $e->getMessage(), null, 500);
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function getBrowserFromUserAgent($userAgent) {
    if (preg_match('/MSIE|Trident/i', $userAgent)) {
        return 'Internet Explorer';
    } elseif (preg_match('/Edge/i', $userAgent)) {
        return 'Edge';
    } elseif (preg_match('/Chrome/i', $userAgent)) {
        return 'Chrome';
    } elseif (preg_match('/Safari/i', $userAgent)) {
        return 'Safari';
    } elseif (preg_match('/Firefox/i', $userAgent)) {
        return 'Firefox';
    } elseif (preg_match('/Opera/i', $userAgent)) {
        return 'Opera';
    }
    return 'Unknown';
}

function getDeviceFromUserAgent($userAgent) {
    if (preg_match('/mobile|android|iphone|ipad|phone/i', $userAgent)) {
        return 'Mobile';
    } elseif (preg_match('/tablet/i', $userAgent)) {
        return 'Tablet';
    }
    return 'Desktop';
}

function requireAdmin() {
    if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
        jsonResponse(false, 'Bạn không có quyền truy cập', null, 403);
    }
}
