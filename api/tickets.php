<?php
/**
 * TICKET API - REST
 * Hỗ trợ: Tạo ticket, Join room, Gửi message, Đóng ticket, Online status
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Check authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'UNAUTHORIZED',
        'message' => 'Vui lòng đăng nhập'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? null;

/**
 * =================================================================
 * API ENDPOINTS
 * =================================================================
 */

try {
    switch ($action) {

        
        case 'create':
            $subject = trim($input['subject'] ?? '');
            $message = trim($input['message'] ?? '');
            $order_id = $input['order_id'] ?? null;
            $priority = $input['priority'] ?? 'medium';

            if (empty($subject) || empty($message)) {
                throw new Exception('Subject và message là bắt buộc');
            }

            if (!in_array($priority, ['low', 'medium', 'high', 'urgent'])) {
                $priority = 'medium';
            }

            // Generate hex8 ID (8 character hexadecimal)
            $ticket_id = strtolower(bin2hex(random_bytes(4)));
            $ticket_number = 'TKT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            // Insert ticket
            $stmt = $pdo->prepare("
                INSERT INTO tickets (id, user_id, order_id, ticket_number, subject, message, priority, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'open', NOW(), NOW())
            ");

            $stmt->execute([$ticket_id, $user_id, $order_id, $ticket_number, $subject, $message, $priority]);

            echo json_encode([
                'success' => true,
                'action' => 'CREATE',
                'data' => [
                    'ticket_id' => $ticket_id,
                    'ticket_number' => $ticket_number,
                    'status' => 'open',
                    'created_at' => date('Y-m-d H:i:s')
                ],
                'message' => 'Ticket đã được tạo thành công'
            ]);
            break;

        /**
         * JOIN ROOM
         * POST /api/ticket-realtime.php
         * Body: { action: "join_room", ticket_id }
         */
        case 'join_room':
            $ticket_id = $input['ticket_id'] ?? null;

            if (!$ticket_id) {
                throw new Exception('Ticket ID là bắt buộc');
            }

            // Verify access
            if (!$is_admin) {
                $stmt = $pdo->prepare("SELECT user_id FROM tickets WHERE id = ?");
                $stmt->execute([$ticket_id]);
                $ticket = $stmt->fetch();

                if (!$ticket || $ticket['user_id'] != $user_id) {
                    throw new Exception('Không có quyền truy cập ticket này');
                }
            }

            // Update online status
            $stmt = $pdo->prepare("
                INSERT INTO ticket_online_users (ticket_id, user_id, role, joined_at, last_seen) 
                VALUES (?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                    last_seen = NOW(),
                    is_online = 1
            ");

            $stmt->execute([$ticket_id, $user_id, $is_admin ? 'ADMIN' : 'USER']);

            // Get current online users
            $stmt = $pdo->prepare("
                SELECT tou.*, u.username, u.role 
                FROM ticket_online_users tou
                LEFT JOIN users u ON tou.user_id = u.id
                WHERE tou.ticket_id = ? AND tou.is_online = 1 AND tou.last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ");
            $stmt->execute([$ticket_id]);
            $online_users = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'action' => 'JOIN_ROOM',
                'data' => [
                    'ticket_id' => $ticket_id,
                    'user_id' => $user_id,
                    'role' => $is_admin ? 'ADMIN' : 'USER',
                    'online_users' => $online_users,
                    'online_count' => count($online_users)
                ],
                'message' => 'Đã tham gia phòng chat'
            ]);
            break;

        /**
         * LEAVE ROOM
         * POST /api/ticket-realtime.php
         * Body: { action: "leave_room", ticket_id }
         */
        case 'leave_room':
            $ticket_id = $input['ticket_id'] ?? null;

            if (!$ticket_id) {
                throw new Exception('Ticket ID là bắt buộc');
            }

            $stmt = $pdo->prepare("
                UPDATE ticket_online_users 
                SET is_online = 0, last_seen = NOW() 
                WHERE ticket_id = ? AND user_id = ?
            ");

            $stmt->execute([$ticket_id, $user_id]);

            echo json_encode([
                'success' => true,
                'action' => 'LEAVE_ROOM',
                'data' => [
                    'ticket_id' => $ticket_id,
                    'user_id' => $user_id
                ],
                'message' => 'Đã rời khỏi phòng chat'
            ]);
            break;

        /**
         * HEARTBEAT - Keep alive
         * POST /api/ticket-realtime.php
         * Body: { action: "heartbeat", ticket_id }
         */
        case 'heartbeat':
            $ticket_id = $input['ticket_id'] ?? null;

            if (!$ticket_id) {
                throw new Exception('Ticket ID là bắt buộc');
            }

            $stmt = $pdo->prepare("
                UPDATE ticket_online_users 
                SET last_seen = NOW() 
                WHERE ticket_id = ? AND user_id = ? AND is_online = 1
            ");

            $stmt->execute([$ticket_id, $user_id]);

            echo json_encode([
                'success' => true,
                'action' => 'HEARTBEAT',
                'timestamp' => time()
            ]);
            break;

        /**
         * GET ONLINE USERS
         * GET /api/ticket-realtime.php?action=online_users&ticket_id=xxx
         */
        case 'online_users':
            $ticket_id = $input['ticket_id'] ?? $_GET['ticket_id'] ?? null;

            if (!$ticket_id) {
                throw new Exception('Ticket ID là bắt buộc');
            }

            $stmt = $pdo->prepare("
                SELECT tou.*, u.username, u.role 
                FROM ticket_online_users tou
                LEFT JOIN users u ON tou.user_id = u.id
                WHERE tou.ticket_id = ? AND tou.is_online = 1 AND tou.last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ");
            $stmt->execute([$ticket_id]);
            $online_users = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'action' => 'ONLINE_USERS',
                'data' => [
                    'online_users' => $online_users,
                    'online_count' => count($online_users),
                    'has_admin' => !empty(array_filter($online_users, fn($u) => $u['role'] === 'admin'))
                ]
            ]);
            break;

        /**
         * SEND MESSAGE
         * POST /api/ticket-realtime.php
         * Body: { action: "send_message", ticket_id, message }
         */
        case 'send_message':
            $ticket_id = $input['ticket_id'] ?? null;
            $message = trim($input['message'] ?? '');

            if (!$ticket_id || empty($message)) {
                throw new Exception('Ticket ID và message là bắt buộc');
            }

            // Verify access
            if (!$is_admin) {
                $stmt = $pdo->prepare("SELECT user_id, status FROM tickets WHERE id = ?");
                $stmt->execute([$ticket_id]);
                $ticket = $stmt->fetch();

                if (!$ticket) {
                    throw new Exception('Ticket không tồn tại');
                }

                if ($ticket['user_id'] != $user_id) {
                    throw new Exception('Không có quyền gửi tin nhắn');
                }

                if ($ticket['status'] === 'closed') {
                    throw new Exception('Ticket đã đóng');
                }
            }

            $snowflake = new Snowflake();
            $message_id = $snowflake->generateId();

            // Insert message
            $stmt = $pdo->prepare("
                INSERT INTO ticket_messages (id, ticket_id, user_id, message, is_admin, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([$message_id, $ticket_id, $user_id, $message, $is_admin ? 1 : 0]);

            // Update ticket status
            $new_status = $is_admin ? 'answered' : 'open';
            $stmt = $pdo->prepare("UPDATE tickets SET updated_at = NOW(), status = ? WHERE id = ?");
            $stmt->execute([$new_status, $ticket_id]);

            // Get message with user info
            $stmt = $pdo->prepare("
                SELECT tm.*, u.username, u.role 
                FROM ticket_messages tm
                LEFT JOIN users u ON tm.user_id = u.id
                WHERE tm.id = ?
            ");
            $stmt->execute([$message_id]);
            $message_data = $stmt->fetch();

            echo json_encode([
                'success' => true,
                'action' => 'SEND_MESSAGE',
                'data' => [
                    'message_id' => $message_id,
                    'message' => $message_data,
                    'ticket_status' => $new_status
                ],
                'message' => 'Tin nhắn đã được gửi'
            ]);
            break;

        /**
         * GET MESSAGES
         * GET /api/ticket-realtime.php?action=messages&ticket_id=xxx&after=timestamp
         */
        case 'messages':
            $ticket_id = $input['ticket_id'] ?? $_GET['ticket_id'] ?? null;
            $after = $input['after'] ?? $_GET['after'] ?? null;

            if (!$ticket_id) {
                throw new Exception('Ticket ID là bắt buộc');
            }

            // Verify access
            if (!$is_admin) {
                $stmt = $pdo->prepare("SELECT user_id FROM tickets WHERE id = ?");
                $stmt->execute([$ticket_id]);
                $ticket = $stmt->fetch();

                if (!$ticket || $ticket['user_id'] != $user_id) {
                    throw new Exception('Không có quyền truy cập');
                }
            }

            $sql = "
                SELECT tm.*, u.username, u.role 
                FROM ticket_messages tm
                LEFT JOIN users u ON tm.user_id = u.id
                WHERE tm.ticket_id = ?
            ";
            $params = [$ticket_id];

            if ($after) {
                $sql .= " AND tm.created_at > ?";
                $params[] = date('Y-m-d H:i:s', $after);
            }

            $sql .= " ORDER BY tm.created_at ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $messages = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'action' => 'MESSAGES',
                'data' => [
                    'messages' => $messages,
                    'count' => count($messages)
                ]
            ]);
            break;

        /**
         * UPDATE STATUS (ADMIN only)
         * POST /api/ticket-realtime.php
         * Body: { action: "update_status", ticket_id, status }
         */
        case 'update_status':
            if (!$is_admin) {
                throw new Exception('Chỉ ADMIN mới có thể cập nhật trạng thái');
            }

            $ticket_id = $input['ticket_id'] ?? null;
            $status = $input['status'] ?? null;

            if (!$ticket_id || !$status) {
                throw new Exception('Ticket ID và status là bắt buộc');
            }

            if (!in_array($status, ['open', 'answered', 'closed'])) {
                throw new Exception('Status không hợp lệ');
            }

            $stmt = $pdo->prepare("UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $ticket_id]);

            echo json_encode([
                'success' => true,
                'action' => 'UPDATE_STATUS',
                'data' => [
                    'ticket_id' => $ticket_id,
                    'status' => $status
                ],
                'message' => 'Đã cập nhật trạng thái'
            ]);
            break;

        /**
         * REPLY TO TICKET (ADMIN)
         * POST /api/tickets.php
         * Body: { action: "reply", ticket_id, message }
         */
        case 'reply':
            if (!$is_admin) {
                throw new Exception('Chỉ ADMIN mới có thể reply ticket');
            }

            $ticket_id = $input['ticket_id'] ?? null;
            $message = trim($input['message'] ?? '');

            if (!$ticket_id || empty($message)) {
                throw new Exception('Ticket ID và message là bắt buộc');
            }

            $snowflake = new Snowflake();
            $message_id = $snowflake->generateId();

            // Insert message
            $stmt = $pdo->prepare("
                INSERT INTO ticket_messages (id, ticket_id, user_id, message, is_admin, created_at) 
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$message_id, $ticket_id, $user_id, $message]);

            // Update ticket status to answered
            $stmt = $pdo->prepare("UPDATE tickets SET status = 'open', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$ticket_id]);

            echo json_encode([
                'success' => true,
                'message' => 'Đã gửi trả lời thành công'
            ]);
            break;

        /**
         * CLOSE TICKET
         * POST /api/ticket-realtime.php
         * Body: { action: "close", ticket_id }
         */
        case 'close':
            $ticket_id = $input['ticket_id'] ?? null;

            if (!$ticket_id) {
                throw new Exception('Ticket ID là bắt buộc');
            }

            // Only admin or ticket owner can close
            if (!$is_admin) {
                $stmt = $pdo->prepare("SELECT user_id FROM tickets WHERE id = ?");
                $stmt->execute([$ticket_id]);
                $ticket = $stmt->fetch();

                if (!$ticket || $ticket['user_id'] != $user_id) {
                    throw new Exception('Không có quyền đóng ticket này');
                }
            }

            $stmt = $pdo->prepare("UPDATE tickets SET status = 'closed', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$ticket_id]);

            // Set all users offline
            $stmt = $pdo->prepare("UPDATE ticket_online_users SET is_online = 0 WHERE ticket_id = ?");
            $stmt->execute([$ticket_id]);

            echo json_encode([
                'success' => true,
                'action' => 'CLOSE',
                'data' => [
                    'ticket_id' => $ticket_id,
                    'status' => 'closed'
                ],
                'message' => 'Ticket đã được đóng'
            ]);
            break;

        /**
         * GET TICKET INFO
         * GET /api/ticket-realtime.php?action=info&ticket_id=xxx
         */
        case 'info':
            $ticket_id = $input['ticket_id'] ?? $_GET['ticket_id'] ?? null;

            if (!$ticket_id) {
                throw new Exception('Ticket ID là bắt buộc');
            }

            // Verify access
            $where = $is_admin ? "t.id = ?" : "t.id = ? AND t.user_id = ?";
            $params = $is_admin ? [$ticket_id] : [$ticket_id, $user_id];

            $stmt = $pdo->prepare("
                SELECT t.*, u.username, u.email, u.role, o.order_number,
                       (SELECT COUNT(*) FROM ticket_messages WHERE ticket_id = t.id) as message_count
                FROM tickets t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN orders o ON t.order_id = o.id
                WHERE $where
            ");
            $stmt->execute($params);
            $ticket = $stmt->fetch();

            if (!$ticket) {
                throw new Exception('Ticket không tồn tại');
            }

            echo json_encode([
                'success' => true,
                'action' => 'INFO',
                'data' => $ticket
            ]);
            break;

        default:
            throw new Exception('Action không hợp lệ');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'API_ERROR',
        'message' => $e->getMessage()
    ]);
}
