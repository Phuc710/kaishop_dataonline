<?php
/**
 * Admin Notifications API
 * Check for new tickets and other admin notifications
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// Must be admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'check_tickets':
        // Get count of open tickets
        $open_tickets = intval($pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'open'")->fetchColumn());
        
        // Get latest ticket (for notification)
        $latest_ticket = $pdo->query("
            SELECT t.*, u.username 
            FROM tickets t 
            LEFT JOIN users u ON t.user_id = u.id 
            WHERE t.status = 'open' 
            ORDER BY t.created_at DESC 
            LIMIT 1
        ")->fetch();
        
        // Get recently created tickets (last 5 minutes)
        $recent_tickets = $pdo->query("
            SELECT t.*, u.username 
            FROM tickets t 
            LEFT JOIN users u ON t.user_id = u.id 
            WHERE t.status = 'open' 
            AND t.created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ORDER BY t.created_at DESC
        ")->fetchAll();
        
        echo json_encode([
            'success' => true,
            'count' => $open_tickets,
            'latest_ticket' => $latest_ticket,
            'recent_tickets' => $recent_tickets,
            'has_new' => count($recent_tickets) > 0
        ]);
        break;
        
    case 'mark_notified':
        // Mark that admin has seen the notification
        // Can store in session or database
        $_SESSION['last_ticket_check'] = time();
        echo json_encode(['success' => true]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
