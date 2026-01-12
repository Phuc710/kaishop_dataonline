<?php
require_once __DIR__ . '/../config/config.php';

if (!isLoggedIn()) {
    redirect(url('auth'));
}

$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();
$ticket_id = $_GET['id'] ?? $_GET['ticket-invoice-id'] ?? null;

if (!$ticket_id) {
    redirect('/user/tickets');
}

// Get ticket info
$where_clause = $is_admin ? "t.id = ?" : "t.id = ? AND t.user_id = ?";
$params = $is_admin ? [$ticket_id] : [$ticket_id, $user_id];

$stmt = $pdo->prepare("SELECT t.*, u.username, u.email, u.role, o.id as order_id, o.order_number 
                        FROM tickets t 
                        LEFT JOIN users u ON t.user_id = u.id 
                        LEFT JOIN orders o ON t.order_id = o.id 
                        WHERE $where_clause");
$stmt->execute($params);
$ticket = $stmt->fetch();

// Check if ticket has attachment image
$has_attachment = !empty($ticket['attachment']) && file_exists(__DIR__ . '/../' . $ticket['attachment']);

if (!$ticket) {
    redirect('/user/tickets');
}

// Get ticket messages
$stmt = $pdo->prepare("SELECT tm.*, u.username, u.role, u.avatar 
                        FROM ticket_messages tm 
                        LEFT JOIN users u ON tm.user_id = u.id 
                        WHERE tm.ticket_id = ? 
                        ORDER BY tm.created_at ASC");
$stmt->execute([$ticket_id]);
$messages = $stmt->fetchAll();

$pageTitle = "Ticket #" . $ticket['ticket_number'] . " - " . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <?php
    // Load favicon helper
    require_once __DIR__ . '/../includes/favicon_helper.php';
    echo render_favicon_tags();
    ?>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/loading.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/notify.css') ?>">
    <script>
        // Global configuration
        window.APP_CONFIG = {
            baseUrl: '<?= BASE_URL ?>',
            siteName: '<?= SITE_NAME ?>'
        };
    </script>
    <script src="<?= asset('js/loading.js') ?>"></script>
    <script src="<?= asset('js/notify.js') ?>"></script>
    <script src="<?= asset('js/ticket-chat.js') ?>"></script>
    <style>
        :root {
            --bg-main: #020617;
            --bg-card: rgba(30, 41, 59, 0.5);
            --bg-card-solid: #0f172a;
            --bg-card-hover: rgba(30, 41, 59, 0.8);
            --primary: #8b5cf6;
            --primary-light: #a78bfa;
            --secondary: #ec4899;
            --accent: #f59e0b;
            --success: #10b981;
            --danger: #ef4444;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --text-dim: #64748b;
            --border-color: rgba(148, 163, 184, 0.15);
            --radius-sm: 8px;
            --radius-md: 16px;
            --radius-lg: 24px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Poppins", -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-main);
            color: var(--text-main);
            min-height: 100vh;
            padding: 1rem;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .ticket-nav {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.15));
            border: 1px solid rgba(99, 102, 241, 0.4);
            border-radius: 10px;
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.8125rem;
            transition: all 0.2s;
            height: 40px;
            box-sizing: border-box;
        }

        .btn-back:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.25), rgba(139, 92, 246, 0.25));
            border-color: var(--primary);
            transform: translateY(-1px);
        }

        .page-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
        }


        .ticket-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            height: calc(100vh - 100px);
        }

        .ticket-layout.admin-view,
        .ticket-layout.user-view {
            grid-template-columns: 320px 1fr;
        }

        .ticket-card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .ticket-card:first-child {
            overflow-y: auto;
        }

        .ticket-card:first-child::-webkit-scrollbar {
            width: 6px;
        }

        .ticket-card:first-child::-webkit-scrollbar-track {
            background: transparent;
        }

        .ticket-card:first-child::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.3);
            border-radius: 10px;
        }

        .ticket-card:first-child::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.5);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .ticket-header {
            padding: 1.5rem;
        }

        .ticket-title-section {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .ticket-main-info h2 {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            line-height: 1.4;
        }

        .info-grid {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 8px;
            font-size: 0.875rem;
        }

        .info-item i {
            width: 20px;
            text-align: center;
            color: var(--primary-light);
        }

        .info-label {
            color: var(--text-muted);
            min-width: 80px;
        }

        .info-value {
            color: var(--text-primary);
            font-weight: 500;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.875rem;
            border-radius: 6px;
            font-size: 0.8125rem;
            font-weight: 600;
        }

        .badge-number {
            background: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        .badge-status-open {
            background: rgba(34, 197, 94, 0.15);
            color: var(--success);
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .badge-status-answered {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .badge-status-closed {
            background: rgba(107, 114, 128, 0.15);
            color: #9ca3af;
            border: 1px solid rgba(107, 114, 128, 0.3);
        }

        .user-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.875rem;
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.25);
            border-radius: 20px;
            font-size: 0.875rem;
            color: var(--text-primary);
        }

        .user-pill i {
            color: var(--primary-light);
        }



        .request-content {
            background: rgba(99, 102, 241, 0.08);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 8px;
            padding: 1rem;
            max-height: 300px;
            overflow-y: auto;
        }

        .request-content::-webkit-scrollbar {
            width: 6px;
        }

        .request-content::-webkit-scrollbar-track {
            background: rgba(99, 102, 241, 0.1);
            border-radius: 10px;
        }

        .request-content::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.4);
            border-radius: 10px;
        }

        .request-content::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.6);
        }

        .request-content p {
            color: var(--text-primary);
            line-height: 1.7;
            font-size: 0.9375rem;
            margin: 0;
        }

        .request-content strong {
            color: #fff;
            font-weight: 700;
        }

        .attachment-preview {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .attachment-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
        }

        .attachment-label i {
            color: var(--primary-light);
        }

        .btn-view-image {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-view-image:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.4);
        }

        .btn-view-image i {
            font-size: 1rem;
        }

        /* Image Lightbox */
        .image-lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.95);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s;
        }

        .image-lightbox.show {
            display: flex;
        }

        .lightbox-image {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            border-radius: 8px;
        }

        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            color: white;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .lightbox-close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }



        .ticket-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .control-select {
            padding: 0.625rem 1rem;
            background: var(--bg-card-solid);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-main);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            min-width: 120px;
            margin-left: auto;
            height: 40px;
            box-sizing: border-box;
        }

        .control-select:hover {
            background: var(--bg-card-hover);
            border-color: var(--primary);
        }

        .control-select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .control-select option {
            background: var(--bg-card-solid);
            color: var(--text-main);
            padding: 0.5rem;
        }

        /* Original Message */
        .original-message {
            padding: 1.5rem;
            padding-top: 0;
            flex: 1;
        }

        .original-message-content {
            background: rgba(99, 102, 241, 0.08);
            padding: 1rem;
            border-radius: 8px;
            border-left: 3px solid var(--primary);
            color: var(--text-secondary);
            line-height: 1.6;
            font-size: 0.875rem;
        }

        /* Chat Section */
        .chat-section {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .chat-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-header-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
        }


        .info-button {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.875rem;
            background: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 6px;
            color: var(--primary-light);
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .info-button:hover {
            background: rgba(99, 102, 241, 0.25);
            border-color: var(--primary);
            transform: translateY(-1px);
        }

        /* Popup Modal */
        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s;
        }

        .popup-overlay.show {
            display: flex;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .popup-modal {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .popup-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .popup-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .popup-close {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s;
        }

        .popup-close:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }

        .popup-body {
            padding: 1.5rem;
        }

        .created-date {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.8125rem;
            color: var(--text-muted);
        }

        .created-date i {
            color: var(--primary-light);
        }

        /* Status Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 0.875rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-status-open {
            background: linear-gradient(135deg, #0097a5ff, #1fe49cff);
            color: white;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
        }

        .badge-status-answered {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.4);
        }

        .badge-status-closed {
            background: rgba(100, 116, 139, 0.3);
            color: var(--text-muted);
        }

        /* Panel Toggle Button */
        .panel-toggle-btn {
            display: none;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.15));
            border: 1px solid rgba(99, 102, 241, 0.4);
            border-radius: 10px;
            color: var(--text-main);
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            height: 40px;
            box-sizing: border-box;
        }

        .panel-toggle-btn:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.25), rgba(139, 92, 246, 0.25));
            border-color: var(--primary);
            transform: translateY(-1px);
        }

        .ticket-info-panel {
            transition: all 0.3s ease;
        }

        .ticket-info-panel.collapsed {
            display: none;
        }

        .panel-close-btn {
            display: none;
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-color);
            border-radius: 50%;
            color: var(--text-main);
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .panel-close-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: #ef4444;
            color: #ef4444;
        }

        .chat-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .chat-header-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .chat-header-left i {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .chat-header-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .chat-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
            margin: 0;
        }


        .messages-container {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            scroll-behavior: smooth;
        }

        .messages-container::-webkit-scrollbar {
            width: 8px;
        }

        .messages-container::-webkit-scrollbar-track {
            background: rgba(148, 163, 184, 0.1);
            border-radius: 10px;
        }

        .messages-container::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }

        /* ========================================
           CHAT MESSAGE LAYOUT - FACEBOOK STYLE
           ======================================== */

        .message-item {
            display: flex;
            align-items: flex-end;
            margin-bottom: 0.75rem;
            gap: 0.5rem;
            position: relative;
        }

        .message-item.user-message {
            justify-content: flex-start;
        }

        .message-item.admin-message {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 32px;
            height: 32px;
            min-width: 32px;
            border-radius: 50%;
            overflow: hidden;
            background: var(--bg-card-solid);
            border: 2px solid var(--border-color);
            flex-shrink: 0;
        }

        .message-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .message-content {
            display: flex;
            flex-direction: column;
            max-width: 65%;
            position: relative;
        }

        .user-message .message-content {
            align-items: flex-start;
        }

        .admin-message .message-content {
            align-items: flex-end;
        }

        .message-header {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            margin-bottom: 0.25rem;
        }

        .message-author {
            font-weight: 700;
            color: #ffffff;
            font-size: 0.8125rem;
        }

        .message-admin-badge {
            background: linear-gradient(135deg, var(--danger), #f87171);
            color: white;
            padding: 0.125rem 0.5rem;
            border-radius: 10px;
            font-size: 0.5625rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .message-time {
            font-size: 0.625rem;
            color: var(--text-dim);
            position: absolute;
            bottom: -1rem;
            left: 0;
            opacity: 0;
            transition: opacity 0.15s ease;
            white-space: nowrap;
        }

        .admin-message .message-time {
            left: auto;
            right: 0;
        }

        .message-item:hover .message-time {
            opacity: 1;
        }

        .message-bubble {
            padding: 0.625rem 1rem;
            border-radius: 18px;
            color: white;
            word-wrap: break-word;
            word-break: break-word;
            line-height: 1.5;
            font-size: 0.875rem;
            max-width: 100%;
        }

        .user-message .message-bubble {
            background: rgba(71, 85, 105, 0.6);
            border-bottom-left-radius: 4px;
        }

        .admin-message .message-bubble {
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            border-bottom-right-radius: 4px;
        }

        .message-bubble a {
            color: #fff;
            text-decoration: underline;
            word-break: break-all;
            font-weight: 500;
        }

        .message-bubble a:hover {
            color: #e0e0e0;
        }

        .message-bubble img {
            max-width: 100%;
            border-radius: 8px;
            margin-top: 0.5rem;
            cursor: pointer;
        }

        .message-actions {
            display: flex;
            gap: 0.25rem;
            align-items: center;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        /* Show actions on hover */
        .message-item:hover .message-actions {
            opacity: 1;
        }

        /* Admin message - actions bên trái bubble */
        .admin-message .message-actions {
            order: -1;
        }

        .message-action-btn {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.3);
            border: none;
            color: rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.7rem;
        }

        .message-action-btn:hover {
            background: rgba(0, 0, 0, 0.5);
            color: white;
            transform: scale(1.15);
        }

        .message-action-btn.delete:hover {
            background: #ef4444;
            color: white;
        }

        .empty-messages {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-dim);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
        }

        .empty-messages i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.3;
            color: var(--text-muted);
        }

        .empty-messages p {
            font-size: 0.875rem;
        }

        .reply-form {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            background: var(--bg-card-solid);
        }

        .input-hint {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-size: 0.8125rem;
            color: var(--text-muted);
        }

        .input-hint i {
            color: #a78bfa;
        }

        .keyboard-hints {
            display: flex;
            gap: 1rem;
        }

        .keyboard-hints kbd {
            padding: 0.125rem 0.375rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 0.75rem;
            font-family: monospace;
        }

        .reply-input-wrapper {
            display: flex;
            gap: 0.75rem;
            align-items: flex-end;
        }

        .reply-textarea {
            flex: 1;
            padding: 0.75rem 1rem;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--text-main);
            font-size: 0.875rem;
            resize: none;
            transition: all 0.2s;
            font-family: inherit;
            min-height: 44px;
            max-height: 120px;
        }

        .reply-textarea:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--bg-card-hover);
        }

        .reply-textarea::placeholder {
            color: var(--text-dim);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(139, 92, 246, 0.4);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-close-ticket {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1rem;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(220, 38, 38, 0.15));
            border: 1px solid rgba(239, 68, 68, 0.4);
            border-radius: 10px;
            color: #ef4444;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-left: auto;
            height: 40px;
            box-sizing: border-box;
        }

        .btn-close-ticket:hover {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.25), rgba(220, 38, 38, 0.25));
            border-color: #ef4444;
            transform: translateY(-1px);
        }

        /* Image Paste Preview */
        .image-paste-preview {
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .closed-notice {
            text-align: center;
            padding: 1.5rem;
            background: rgba(245, 158, 11, 0.08);
            border-top: 1px solid rgba(245, 158, 11, 0.2);
            color: var(--warning);
            font-weight: 500;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        /* Loading Spinner */
        .loading-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-light);
            font-size: 0.875rem;
        }

        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 1024px) {

            .ticket-layout,
            .ticket-layout.admin-view,
            .ticket-layout.user-view {
                grid-template-columns: 1fr;
                height: auto;
            }

            .panel-toggle-btn {
                display: inline-flex;
            }

            .ticket-info-panel {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 200;
                border-radius: 0;
                overflow-y: auto;
                background: var(--bg-main);
                flex-direction: column;
            }

            .ticket-info-panel.show {
                display: flex;
            }

            .ticket-info-panel.show .panel-close-btn {
                display: flex;
            }

            .ticket-info-panel .ticket-header {
                position: sticky;
                top: 0;
                background: var(--bg-card-solid);
                border-bottom: 1px solid var(--border-color);
                z-index: 10;
            }

            .ticket-info-panel .original-message {
                padding: 1.5rem;
            }

            .chat-section {
                height: calc(100vh - 60px);
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 0;
            }

            .container {
                max-width: 100%;
                padding: 0;
            }

            .ticket-nav {
                padding: 0.75rem;
                margin-bottom: 0;
                gap: 0.5rem;
                background: var(--bg-card-solid);
                border-bottom: 1px solid var(--border-color);
            }

            .page-title {
                display: none;
            }

            .btn-back {
                padding: 0.5rem 0.75rem;
                font-size: 0.8125rem;
            }

            .btn-close-ticket {
                margin-left: auto;
                padding: 0.5rem 0.75rem;
                font-size: 0.75rem;
            }

            .ticket-card {
                border-radius: 0;
                border-left: none;
                border-right: none;
            }

            .chat-header {
                padding: 0.75rem 1rem;
            }

            .chat-title {
                font-size: 0.9375rem;
            }

            .chat-header-info i {
                display: none;
            }

            .badge {
                font-size: 0.625rem;
                padding: 0.25rem 0.5rem;
            }

            .created-date {
                font-size: 0.6875rem;
            }

            .messages-container {
                padding: 0.75rem;
                padding-bottom: 1.5rem;
            }

            .message-item {
                margin-bottom: 1rem;
            }

            .message-avatar {
                width: 28px;
                height: 28px;
                min-width: 28px;
            }

            .message-content {
                max-width: 75%;
            }

            .message-header {
                margin-bottom: 0.125rem;
            }

            .message-author {
                font-size: 0.6875rem;
            }

            .message-admin-badge {
                font-size: 0.5rem;
                padding: 0.0625rem 0.375rem;
            }

            .message-bubble {
                padding: 0.5rem 0.75rem;
                font-size: 0.8125rem;
                border-radius: 14px;
            }

            .user-message .message-bubble {
                border-bottom-left-radius: 4px;
            }

            .admin-message .message-bubble {
                border-bottom-right-radius: 4px;
            }

            .message-time {
                font-size: 0.5625rem;
                bottom: -0.875rem;
            }

            .chat-section {
                height: calc(100vh - 60px);
            }

            .reply-form {
                padding: 0.75rem 1rem;
            }

            .input-hint {
                display: none;
            }

            .reply-input-wrapper {
                gap: 0.5rem;
            }

            .reply-textarea {
                padding: 0.625rem 0.75rem;
                font-size: 0.875rem;
                border-radius: 20px;
            }

            .btn-submit {
                padding: 0.625rem 1rem;
                border-radius: 20px;
            }

            .btn-submit i {
                margin: 0;
            }
        }

        @media (max-width: 480px) {
            .ticket-nav {
                padding: 0.5rem 0.75rem;
            }

            .btn-back span {
                display: none;
            }

            .message-avatar {
                width: 24px;
                height: 24px;
                min-width: 24px;
            }

            .message-content {
                max-width: 80%;
            }

            .message-bubble {
                padding: 0.5rem 0.625rem;
                font-size: 0.8125rem;
            }

            .reply-form {
                padding: 0.5rem 0.75rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Navigation -->
        <div class="ticket-nav">
            <a href="<?= $is_admin ? url('admin/index.php?tab=notifications&subtab=tickets') : url('user?tab=tickets') ?>"
                class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Quay lại
            </a>
            <h1 class="page-title">
                <span class="badge badge-number" style="margin-left: auto;">
                    Mã: #<?= htmlspecialchars($ticket['ticket_number']) ?>
                </span>
            </h1>
            <?php if ($is_admin): ?>
                <button class="panel-toggle-btn" onclick="toggleInfoPanel()">
                    <i class="fas fa-info-circle"></i>
                    Chi tiết
                </button>
                <select id="adminStatusSelect" class="control-select">
                    <option value="open" <?= $ticket['status'] == 'open' || $ticket['status'] == 'answered' ? 'selected' : '' ?>>
                        Open</option>
                    <option value="closed" <?= $ticket['status'] == 'closed' ? 'selected' : '' ?>>Close</option>
                </select>
            <?php endif; ?>
            <?php if (!$is_admin): ?>
                <button class="panel-toggle-btn" onclick="toggleInfoPanel()">
                    <i class="fas fa-info-circle"></i>
                    Chi tiết
                </button>
            <?php endif; ?>
            <?php if (!$is_admin && $ticket['status'] != 'closed'): ?>
                <button onclick="closeTicketConfirm()" class="btn-close-ticket">
                    <i class="fas fa-times-circle"></i>
                    Đóng
                </button>
            <?php endif; ?>
        </div>

        <!-- Main Layout -->
        <div class="ticket-layout <?= $is_admin ? 'admin-view' : 'user-view' ?>">
            <!-- Info Panel - Both Admin and User -->
            <div class="ticket-card ticket-info-panel">
                <div class="ticket-header"
                    style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div class="section-title"
                            style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 0.75rem;">
                            TIÊU ĐỀ
                            TICKET</div>
                        <h2 style="font-size: 1.125rem; color: var(--text-main); font-weight: 700; margin: 0;">
                            <?= htmlspecialchars($ticket['subject']) ?>
                        </h2>
                    </div>
                    <button class="panel-close-btn" onclick="toggleInfoPanel()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="original-message">
                    <div
                        style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 0.75rem;">
                        NỘI DUNG YÊU CẦU</div>
                    <div class="request-content">
                        <p style="margin: 0;"><?= nl2br(htmlspecialchars($ticket['message'])) ?></p>
                    </div>

                    <?php if ($has_attachment): ?>
                        <div class="attachment-preview">
                            <div class="attachment-label">
                                <i class="fas fa-paperclip"></i>
                                ẢNH ĐÍNH KÈM
                            </div>
                            <button class="btn-view-image" onclick="openLightbox('<?= url($ticket['attachment']) ?>')">
                                Xem ảnh
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Chat Column -->
            <div class="ticket-card chat-section">
                <div class="chat-header">
                    <div class="chat-header-left">
                        <i class="fas fa-headset"></i>
                        <h3 class="chat-title">Hộp Chat Hỗ Trợ 24/7</h3>
                    </div>
                    <div class="chat-header-right">
                        <span class="badge badge-status-<?= $ticket['status'] ?>">
                            <?php
                            $status_text = [
                                'open' => 'Đang mở',
                                'answered' => 'Đã trả lời',
                                'closed' => 'Đã đóng'
                            ];
                            echo $status_text[$ticket['status']] ?? $ticket['status'];
                            ?>
                        </span>
                        <div class="created-date">
                            <i class="fas fa-clock"></i>
                            <?= date('d/m/Y • H:i', strtotime($ticket['created_at'])) ?>
                        </div>
                    </div>
                </div>

                <div id="messagesList" class="messages-container">
                    <?php if (empty($messages)): ?>
                        <div class="empty-messages">
                            <i class="fas fa-comment-slash"></i>
                            <p>Chưa có tin nhắn nào trong cuộc hội thoại này</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg):
                            $message_user_is_admin = ($msg['role'] == 'admin' || $msg['is_admin']);
                            $display_name = $message_user_is_admin ? '' : htmlspecialchars($msg['username']);
                            $message_class = $message_user_is_admin ? 'admin-message' : 'user-message';
                            ?>
                            <div class="message-item <?= $message_class ?>" data-message-id="<?= $msg['id'] ?>"
                                data-user-id="<?= $msg['user_id'] ?>">

                                <!-- Avatar -->
                                <div class="message-avatar">
                                    <img src="<?= getUserAvatar(['avatar' => $msg['avatar'], 'role' => $msg['role']]) ?>"
                                        alt="Avatar">
                                </div>

                                <!-- Content -->
                                <div class="message-content">
                                    <div class="message-header">
                                        <?php if (!$message_user_is_admin): ?>
                                            <span class="message-author"><?= $display_name ?></span>
                                        <?php else: ?>
                                            <span class="message-admin-badge">ADMIN</span>
                                        <?php endif; ?>
                                    </div>

                                    <div style="display: flex; align-items: center; gap: 0.25rem;">
                                        <div class="message-bubble">
                                            <?php if (!empty($msg['message'])): ?>
                                                <?php
                                                // Auto-linkify URLs
                                                $text = htmlspecialchars($msg['message']);
                                                $text = preg_replace(
                                                    '/(https?:\/\/[^\s]+)/',
                                                    '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
                                                    $text
                                                );
                                                echo nl2br($text);
                                                ?>
                                            <?php endif; ?>

                                            <?php if (!empty($msg['image'])): ?>
                                                <img src="<?= url($msg['image']) ?>" alt="Image"
                                                    onclick="openLightbox('<?= url($msg['image']) ?>')"
                                                    style="display: block; margin-top: <?= !empty($msg['message']) ? '0.5rem' : '0' ?>;">
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($is_admin): ?>
                                            <div class="message-actions">
                                                <button class="message-action-btn delete"
                                                    onclick="deleteMessage('<?= $msg['id'] ?>')" title="Xóa tin nhắn">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="message-time"><?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Reply Form -->
                <?php if ($ticket['status'] != 'closed'): ?>

                    <form id="replyForm" class="reply-form" data-no-loading>
                        <div class="input-hint">
                            <span>
                                <i class="fas fa-heart"></i>
                                Nhấn tin lịch sự, tôn trọng nhân viên hỗ trợ 💜
                            </span>
                            <div class="keyboard-hints">
                                <span><kbd>Enter</kbd> để gửi</span>
                            </div>
                        </div>
                        <div class="reply-input-wrapper">
                            <textarea id="replyMessage" name="message" class="reply-textarea"
                                placeholder="Nhập tin nhắn của bạn..." rows="1" required></textarea>
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-paper-plane"></i>
                                Gửi
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="closed-notice">
                        <i class="fas fa-lock"></i>
                        Ticket đã đóng - Không thể gửi tin nhắn
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- User Info Popup -->
    <div id="userInfoPopup" class="popup-overlay" onclick="closeUserInfo(event)">
        <div class="popup-modal" onclick="event.stopPropagation()">
            <div class="popup-header">
                <div class="popup-title">
                    <i class="fas fa-user-circle"></i>
                    Thông tin User
                </div>
                <button class="popup-close" onclick="closeUserInfo()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="popup-body">
                <div class="info-grid">
                    <div class="info-item">
                        <i class="fas fa-user"></i>
                        <span class="info-label">User:</span>
                        <span class="user-pill">
                            <i class="fas fa-user-circle"></i>
                            <?= htmlspecialchars($ticket['username']) ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-id-badge"></i>
                        <span class="info-label">ID:</span>
                        <span class="info-value"><?= htmlspecialchars($ticket['user_id']) ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-shield-alt"></i>
                        <span class="info-label">Role:</span>
                        <span class="badge badge-status-open"
                            style="text-transform: capitalize;"><?= htmlspecialchars($ticket['role']) ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <span class="info-label">Ngày tạo:</span>
                        <span class="info-value"><?= date('d/m/Y H:i:s', strtotime($ticket['created_at'])) ?></span>
                    </div>
                    <?php if ($ticket['email']): ?>
                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?= htmlspecialchars($ticket['email']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // User Info Popup Functions
        function toggleUserInfo() {
            const popup = document.getElementById('userInfoPopup');
            popup.classList.toggle('show');
        }

        function closeUserInfo(event) {
            if (event && event.target.id !== 'userInfoPopup') return;
            document.getElementById('userInfoPopup').classList.remove('show');
        }

        // Close popup with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeUserInfo();
            }
        });

        const chatClient = new TicketChatClient(
            '<?= $ticket_id ?>',
            '<?= $user_id ?>',
            <?= $is_admin ? 'true' : 'false' ?>,
            '<?= url('ticket') ?>'
        );

        window.ticketChat = chatClient;
        chatClient.init();
        chatClient.scrollToBottom();

        <?php if ($is_admin): ?>
            // Admin status update - NO CONFIRM, just update directly
            document.getElementById('adminStatusSelect')?.addEventListener('change', async function () {
                const newStatus = this.value;
                const originalValue = '<?= $ticket['status'] ?>';

                // Disable select while updating
                this.disabled = true;

                try {
                    const response = await fetch('<?= url('api/tickets.php') ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'update_status',
                            ticket_id: '<?= $ticket_id ?>',
                            status: newStatus
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        if (window.notify) {
                            notify.success('✅ Cập nhật!', 'Trạng thái đã thay đổi', 1500);
                        }
                        setTimeout(() => window.location.reload(), 800);
                    } else {
                        if (window.notify) {
                            notify.error('Lỗi!', result.message || 'Không thể cập nhật');
                        }
                        this.value = originalValue;
                        this.disabled = false;
                    }
                } catch (error) {
                    if (window.notify) {
                        notify.error('Lỗi!', 'Không thể kết nối máy chủ');
                    }
                    this.value = originalValue;
                    this.disabled = false;
                }
            });
        <?php endif; ?>

        // Chat client handles message submission automatically

        // Image Lightbox Functions
        function openLightbox(src) {
            const lightbox = document.getElementById('imageLightbox');
            const lightboxImg = document.getElementById('lightboxImage');
            if (lightbox && lightboxImg) {
                lightboxImg.src = src;
                lightbox.classList.add('show');
            }
        }

        function closeLightbox() {
            const lightbox = document.getElementById('imageLightbox');
            if (lightbox) {
                lightbox.classList.remove('show');
            }
        }

        // Close lightbox on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeLightbox();
            }
        });

        // Delete message function (Admin only)
        window.deleteMessage = async function (messageId) {
            // Show confirmation
            if (window.notify && notify.show) {
                notify.show({
                    type: 'warning',
                    title: '⚠️ Xác nhận xóa',
                    message: 'Bạn có chắc muốn xóa tin nhắn này? Người dùng sẽ không thấy tin nhắn này nữa.',
                    showConfirm: true,
                    confirmText: 'Xóa',
                    cancelText: 'Hủy',
                    onConfirm: async () => {
                        await performDelete(messageId);
                    }
                });
            } else {
                if (!confirm('Xóa tin nhắn này? Người dùng sẽ không thấy tin nhắn này nữa.')) {
                    return;
                }
                await performDelete(messageId);
            }
        };

        // Perform delete operation
        async function performDelete(messageId) {
            // Ensure messageId is a string
            const msgId = String(messageId);
            console.log('Deleting message ID:', msgId, 'from ticket:', '<?= $ticket_id ?>');

            try {
                const response = await fetch('<?= url('ticket/delete_message.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        message_id: msgId,
                        ticket_id: '<?= $ticket_id ?>'
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Show success notification
                    if (window.notify) {
                        notify.success('✅ Đã xóa!');
                    }

                    // Remove message from UI
                    const messageEl = document.querySelector(`[data-message-id="${messageId}"]`);
                    if (messageEl) {
                        messageEl.style.opacity = '0';
                        messageEl.style.transform = 'scale(0.9)';
                        setTimeout(() => messageEl.remove(), 300);
                    }
                } else {
                    if (window.notify) {
                        notify.error('❌ Lỗi!', result.message || 'Không thể xóa tin nhắn');
                    } else {
                        alert('Lỗi: ' + (result.message || 'Không thể xóa tin nhắn'));
                    }
                }
            } catch (error) {
                console.error('Delete error:', error);
                if (window.notify) {
                    notify.error('❌ Lỗi kết nối!', 'Không thể kết nối đến máy chủ. Vui lòng thử lại.');
                } else {
                    alert('Lỗi: Không thể kết nối đến máy chủ');
                }
            }
        }

        // Close ticket function (User only)
        window.closeTicketConfirm = function () {
            if (window.notify && notify.confirm) {
                notify.confirm({
                    title: 'Đóng ticket này?',
                    message: 'Bạn sẽ không thể mở lại sau khi đóng.',
                    confirmText: 'Đóng',
                    cancelText: 'Hủy'
                }).then(async (confirmed) => {
                    if (confirmed) {
                        await performCloseTicket();
                    }
                });
            } else if (confirm('Đóng ticket này? Bạn sẽ không thể mở lại sau khi đóng.')) {
                performCloseTicket();
            }
        };

        async function performCloseTicket() {
            try {
                const response = await fetch('<?= url('api/tickets.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'close',
                        ticket_id: '<?= $ticket_id ?>'
                    })
                });

                const result = await response.json();

                if (result.success) {
                    if (window.notify) {
                        notify.success('Đóng ticket!', 'Ticket đã được đóng', 1500);
                    }
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    if (window.notify) {
                        notify.error('Lỗi!', result.message || 'Không thể đóng ticket');
                    }
                }
            } catch (error) {
                console.error('Close ticket error:', error);
                if (window.notify) {
                    notify.error('Lỗi!', 'Không thể kết nối máy chủ');
                }
            }
        };

        // Toggle ticket info panel (for mobile)
        window.toggleInfoPanel = function () {
            const panel = document.querySelector('.ticket-info-panel');
            if (panel) {
                panel.classList.toggle('show');
                const btn = document.querySelector('.panel-toggle-btn');
                if (btn) {
                    const icon = btn.querySelector('i');
                    if (panel.classList.contains('show')) {
                        icon.className = 'fas fa-times';
                        btn.innerHTML = '<i class="fas fa-times"></i> Đóng';
                    } else {
                        icon.className = 'fas fa-info-circle';
                        btn.innerHTML = '<i class="fas fa-info-circle"></i> Chi tiết';
                    }
                }
            }
        };
    </script>

    <!-- Image Lightbox -->
    <div id="imageLightbox" class="image-lightbox" onclick="closeLightbox()">
        <button class="lightbox-close" onclick="closeLightbox()">
            <i class="fas fa-times"></i>
        </button>
        <img id="lightboxImage" class="lightbox-image" alt="Preview" onclick="event.stopPropagation()">
    </div>
</body>

</html>