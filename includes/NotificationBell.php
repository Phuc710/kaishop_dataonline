<?php
/**
 * Notification Bell Component
 * Displays notification bell with badge and popup
 */

class NotificationBell
{
    private $pdo;
    private $user_id;

    public function __construct($pdo, $user_id = null)
    {
        $this->pdo = $pdo;
        $this->user_id = $user_id;
    }

    public function getNotifications()
    {
        if (!$this->user_id) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT * FROM important_notices
            WHERE is_active = 1 AND (target_user_id IS NULL OR target_user_id = ?)
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchAll();
    }

    public function getUnreadCount()
    {
        if (!$this->user_id) {
            return 0;
        }

        try {
            // Try to get unread count using last_read_notifications
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM important_notices
                WHERE is_active = 1 AND (target_user_id IS NULL OR target_user_id = ?)
                AND created_at > COALESCE((
                    SELECT last_read_notifications FROM users WHERE id = ?
                ), '1970-01-01')
            ");
            $stmt->execute([$this->user_id, $this->user_id]);
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            // Fallback: return total count if column doesn't exist
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM important_notices
                WHERE is_active = 1 AND (target_user_id IS NULL OR target_user_id = ?)
            ");
            $stmt->execute([$this->user_id]);
            return $stmt->fetchColumn();
        }
    }

    public function render()
    {
        $notifications = $this->getNotifications();
        $unreadCount = $this->getUnreadCount();

        ob_start();
        ?>
        <div class="notification-bell-container">
            <button class="notification-bell" id="notificationBell" onclick="toggleNotifications()">
                <img src="https://media.giphy.com/media/btnLQHNxdBTRAdxup9/giphy.gif" alt="Notification"
                    style="width: 24px; height: 24px; object-fit: contain;">
                <?php if ($unreadCount > 0): ?>
                    <span class="notification-badge"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
                <?php endif; ?>
            </button>

            <div class="notification-popup" id="notificationPopup" style="display: none;">
                <div class="notification-header">
                    <h4>Thông Báo</h4>
                    <?php if ($unreadCount > 0): ?>
                        <button class="mark-all-read" onclick="markAllAsRead()">
                            <i class="fas fa-check-double"></i> Đánh dấu đã đọc
                        </button>
                    <?php endif; ?>
                </div>

                <div class="notification-list">
                    <?php if (empty($notifications)): ?>
                        <div class="notification-empty">
                            <i class="fas fa-bell-slash"></i>
                            <p>Không có thông báo nào</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <?php
                            $type_icons = [
                                'warning' => 'exclamation-triangle',
                                'danger' => 'exclamation-circle',
                                'info' => 'info-circle',
                                'success' => 'check-circle'
                            ];
                            $icon = $type_icons[$notification['type']] ?? 'info-circle';
                            ?>
                            <div class="notification-item" data-id="<?= $notification['id'] ?>">
                                <div class="notification-icon notification-<?= $notification['type'] ?>">
                                    <i class="fas fa-<?= $icon ?>"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-title"><?= e($notification['title']) ?></div>
                                    <div class="notification-message"><?= e($notification['content']) ?></div>
                                    <div class="notification-time">
                                        <i class="fas fa-clock"></i>
                                        <?= $this->timeAgo($notification['created_at']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <style>
            .notification-bell-container {
                position: relative;
            }

            .notification-bell {
                position: relative;
                background: none;
                border: none;
                color: #f97316;
                font-size: 1.2rem;
                padding: 0.5rem;
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.2s ease;
            }


            .notification-badge {
                position: absolute;
                top: -2px;
                right: -2px;
                background: #ef4444 !important;
                color: #ffffff !important;
                font-size: 0.7rem;
                font-weight: 700;
                padding: 0.15rem 0.4rem;
                border-radius: 10px;
                min-width: 18px;
                text-align: center;
                line-height: 1;
                box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
            }

            .notification-popup {
                position: absolute;
                top: 100%;
                right: 0;
                width: 350px;
                max-height: 500px;
                background: rgba(15, 23, 42, 0.98);
                backdrop-filter: blur(20px);
                border: 1px solid rgba(148, 163, 184, 0.3);
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
                z-index: 1000;
                margin-top: 8px;
            }

            .notification-header {
                padding: 1rem;
                border-bottom: 1px solid rgba(148, 163, 184, 0.1);
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .notification-header h4 {
                margin: 0;
                color: #f9fafb;
                font-size: 1.1rem;
                font-weight: 700;
            }

            .mark-all-read {
                background: none;
                border: none;
                color: #94a3b8;
                font-size: 0.8rem;
                cursor: pointer;
                padding: 0.25rem 0.5rem;
                border-radius: 6px;
                transition: all 0.2s ease;
            }

            .mark-all-read:hover {
                background: rgba(148, 163, 184, 0.1);
                color: #f9fafb;
            }

            .notification-list {
                max-height: 350px;
                overflow-y: auto;
            }

            .notification-empty {
                padding: 2rem;
                text-align: center;
                color: #94a3b8;
            }

            .notification-empty i {
                font-size: 2rem;
                margin-bottom: 0.5rem;
                display: block;
            }

            .notification-item {
                padding: 1rem;
                border-bottom: 1px solid rgba(148, 163, 184, 0.1);
                display: flex;
                gap: 0.75rem;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .notification-item:hover {
                background: rgba(139, 92, 246, 0.05);
            }

            .notification-item:last-child {
                border-bottom: none;
            }

            .notification-icon {
                width: 40px;
                height: 40px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.2rem;
                flex-shrink: 0;
            }

            .notification-warning {
                background: rgba(251, 146, 60, 0.2);
                color: #fb923c;
            }

            .notification-danger {
                background: rgba(239, 68, 68, 0.2);
                color: #ef4444;
            }

            .notification-info {
                background: rgba(59, 130, 246, 0.2);
                color: #3b82f6;
            }

            .notification-success {
                background: rgba(34, 197, 94, 0.2);
                color: #22c55e;
            }

            .notification-content {
                flex: 1;
                min-width: 0;
            }

            .notification-title {
                font-weight: 600;
                color: #f9fafb;
                font-size: 0.9rem;
                margin-bottom: 0.25rem;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .notification-message {
                color: #94a3b8;
                font-size: 0.8rem;
                line-height: 1.4;
                margin-bottom: 0.5rem;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .notification-time {
                color: #64748b;
                font-size: 0.75rem;
                display: flex;
                align-items: center;
                gap: 0.25rem;
            }

            .notification-footer {
                padding: 0.75rem 1rem;
                border-top: 1px solid rgba(148, 163, 184, 0.1);
                text-align: center;
            }

            .view-all-notifications {
                color: #8b5cf6;
                text-decoration: none;
                font-size: 0.9rem;
                font-weight: 600;
            }

            .view-all-notifications:hover {
                text-decoration: underline;
            }

            /* Scrollbar */
            .notification-list::-webkit-scrollbar {
                width: 6px;
            }

            .notification-list::-webkit-scrollbar-track {
                background: rgba(15, 23, 42, 0.5);
            }

            .notification-list::-webkit-scrollbar-thumb {
                background: rgba(139, 92, 246, 0.5);
                border-radius: 3px;
            }

            /* Mobile responsive */
            @media (max-width: 768px) {
                .notification-popup {
                    width: calc(100vw - 40px);
                    max-width: 350px;
                    right: 0;
                    left: auto;
                }
            }

            @media (max-width: 480px) {
                .notification-popup {
                    width: calc(100vw - 30px);
                    max-width: 320px;
                    right: -10px;
                }
            }
        </style>

        <script>
            function toggleNotifications() {
                const popup = document.getElementById('notificationPopup');
                const isVisible = popup.style.display !== 'none';

                if (isVisible) {
                    popup.style.display = 'none';
                } else {
                    popup.style.display = 'block';
                    // Mark as read when opened
                    setTimeout(() => {
                        markAllAsRead();
                    }, 1000);
                }
            }

            function markAllAsRead() {
                // Use relative path from root
                const apiPath = window.location.origin + '/kaishop/api/mark-notifications-read.php';

                fetch(apiPath, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({})
                }).then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove badge
                            const badge = document.querySelector('.notification-badge');
                            if (badge) {
                                badge.remove();
                            }

                            // Remove mark all read button
                            const markAllBtn = document.querySelector('.mark-all-read');
                            if (markAllBtn) {
                                markAllBtn.remove();
                            }
                        }
                    }).catch(error => {
                        console.log('Mark as read failed:', error);
                    });
            }

            // Close popup when clicking outside
            document.addEventListener('click', function (e) {
                const container = document.querySelector('.notification-bell-container');
                const popup = document.getElementById('notificationPopup');

                if (container && !container.contains(e.target)) {
                    popup.style.display = 'none';
                }
            });
        </script>
        <?php
        return ob_get_clean();
    }

    private function timeAgo($datetime)
    {
        $time = time() - strtotime($datetime);

        if ($time < 60)
            return 'Vừa xong';
        if ($time < 3600)
            return floor($time / 60) . ' phút trước';
        if ($time < 86400)
            return floor($time / 3600) . ' giờ trước';
        if ($time < 2592000)
            return floor($time / 86400) . ' ngày trước';

        return date('d/m/Y', strtotime($datetime));
    }
}
?>