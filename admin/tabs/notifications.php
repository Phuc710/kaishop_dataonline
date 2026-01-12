<?php
// Notifications Management Tab with 2 Sub-tabs: Banner & Popup
$success = $error = '';
$current_subtab = $_GET['subtab'] ?? 'banner';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $type = $_POST['type'] ?? 'banner';

    // ============ BANNER ACTIONS ============
    if ($type === 'banner') {
        if ($action === 'add') {
            $message = trim($_POST['message'] ?? '');
            $bg_color = trim($_POST['bg_color'] ?? '#8b5cf6');
            $bg_color_2 = trim($_POST['bg_color_2'] ?? '#7c3aed');
            $text_color = trim($_POST['text_color'] ?? '#ffffff');
            $icon = trim($_POST['icon'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $display_order = intval($_POST['display_order'] ?? 0);
            $speed = intval($_POST['speed'] ?? 50);

            if (!empty($message)) {
                $stmt = $pdo->prepare("INSERT INTO notification_banners (message, bg_color, bg_color_2, text_color, icon, is_active, display_order, speed) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$message, $bg_color, $bg_color_2, $text_color, $icon, $is_active, $display_order, $speed])) {
                    $_SESSION['notif_success'] = 'Thêm banner thành công!';
                    header("Location: ?tab=notifications&subtab=banner");
                    exit;
                }
            }
        } elseif ($action === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $message = trim($_POST['message'] ?? '');
            $bg_color = trim($_POST['bg_color'] ?? '#8b5cf6');
            $bg_color_2 = trim($_POST['bg_color_2'] ?? '#7c3aed');
            $text_color = trim($_POST['text_color'] ?? '#ffffff');
            $icon = trim($_POST['icon'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $display_order = intval($_POST['display_order'] ?? 0);
            $speed = intval($_POST['speed'] ?? 50);

            if ($id && !empty($message)) {
                $stmt = $pdo->prepare("UPDATE notification_banners SET message=?, bg_color=?, bg_color_2=?, text_color=?, icon=?, is_active=?, display_order=?, speed=? WHERE id=?");
                if ($stmt->execute([$message, $bg_color, $bg_color_2, $text_color, $icon, $is_active, $display_order, $speed, $id])) {
                    $_SESSION['notif_success'] = 'Cập nhật banner thành công!';
                    header("Location: ?tab=notifications&subtab=banner");
                    exit;
                }
            }
        } elseif ($action === 'toggle') {
            $id = intval($_POST['id'] ?? 0);
            $is_active = intval($_POST['is_active'] ?? 0);

            if ($id) {
                $stmt = $pdo->prepare("UPDATE notification_banners SET is_active = ? WHERE id = ?");
                if ($stmt->execute([$is_active, $id])) {
                    $_SESSION['notif_success'] = $is_active ? 'Bật banner thành công!' : 'Tắt banner thành công!';
                    header("Location: ?tab=notifications&subtab=banner");
                    exit;
                }
            }
        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM notification_banners WHERE id=?");
                if ($stmt->execute([$id])) {
                    $_SESSION['notif_success'] = 'Xóa banner thành công!';
                    header("Location: ?tab=notifications&subtab=banner");
                    exit;
                }
            }
        }
    }

    // ============ POPUP ACTIONS ============
    elseif ($type === 'popup') {
        if ($action === 'add') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $image = trim($_POST['image'] ?? '');
            $image_width = intval($_POST['image_width'] ?? 800);
            $image_height = intval($_POST['image_height'] ?? 500);
            $content_mode = trim($_POST['content_mode'] ?? 'text');
            $background_code = trim($_POST['background_code'] ?? '');

            if (!empty($title) || !empty($description) || !empty($image)) {
                // Disable all other popups first
                $pdo->exec("UPDATE popup_notifications SET is_active = 0");

                // Insert new popup with is_active = 1
                $stmt = $pdo->prepare("INSERT INTO popup_notifications (title, description, image, image_width, image_height, content_mode, background_code, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                if ($stmt->execute([$title, $description, $image, $image_width, $image_height, $content_mode, $background_code])) {
                    $_SESSION['notif_success'] = 'Thêm popup thành công và đã kích hoạt!';
                    header("Location: ?tab=notifications&subtab=popup");
                    exit;
                }
            }
        } elseif ($action === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $image = trim($_POST['image'] ?? '');
            $image_width = intval($_POST['image_width'] ?? 800);
            $image_height = intval($_POST['image_height'] ?? 500);
            $content_mode = trim($_POST['content_mode'] ?? 'text');
            $background_code = trim($_POST['background_code'] ?? '');

            if ($id && (!empty($title) || !empty($description) || !empty($image))) {
                $stmt = $pdo->prepare("UPDATE popup_notifications SET title=?, description=?, image=?, image_width=?, image_height=?, content_mode=?, background_code=? WHERE id=?");
                if ($stmt->execute([$title, $description, $image, $image_width, $image_height, $content_mode, $background_code, $id])) {
                    $_SESSION['notif_success'] = 'Cập nhật popup thành công!';
                    header("Location: ?tab=notifications&subtab=popup");
                    exit;
                }
            }
        } elseif ($action === 'toggle') {
            $id = intval($_POST['id'] ?? 0);
            $is_active = intval($_POST['is_active'] ?? 0);

            if ($id) {
                if ($is_active) {
                    // Disable all other popups first
                    $pdo->exec("UPDATE popup_notifications SET is_active = 0");
                }

                // Update this popup
                $stmt = $pdo->prepare("UPDATE popup_notifications SET is_active = ? WHERE id = ?");
                if ($stmt->execute([$is_active, $id])) {
                    $_SESSION['notif_success'] = $is_active ? 'Bật popup thành công!' : 'Tắt popup thành công!';
                    header("Location: ?tab=notifications&subtab=popup");
                    exit;
                }
            }
        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id) {
                // Delete image file
                $stmt = $pdo->prepare("SELECT image FROM popup_notifications WHERE id=?");
                $stmt->execute([$id]);
                $popup = $stmt->fetch();
                if ($popup && !empty($popup['image'])) {
                    $img_path = __DIR__ . '/../../' . $popup['image'];
                    if (file_exists($img_path)) {
                        unlink($img_path);
                    }
                }

                $stmt = $pdo->prepare("DELETE FROM popup_notifications WHERE id=?");
                if ($stmt->execute([$id])) {
                    $_SESSION['notif_success'] = 'Xóa popup thành công!';
                    header("Location: ?tab=notifications&subtab=popup");
                    exit;
                }
            }
        }
    }
}

// Get success/error message from session
$success = $error = '';
if (isset($_SESSION['notif_success'])) {
    $success = $_SESSION['notif_success'];
    unset($_SESSION['notif_success']);
}
if (isset($_SESSION['notif_error'])) {
    $error = $_SESSION['notif_error'];
    unset($_SESSION['notif_error']);
}

// Get data based on current subtab
$notifications = $pdo->query("SELECT * FROM notification_banners ORDER BY display_order ASC")->fetchAll();
$popups = $pdo->query("SELECT * FROM popup_notifications ORDER BY display_order ASC")->fetchAll();
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-bullhorn"></i> Quản Lý Thông Báo</h1>
        <p>Quản lý thông báo, lưu ý và hỗ trợ khách hàng</p>
    </div>
</div>



<?php if ($success): ?>
    <script>
        if (window.notify) {
            notify.success('Thành công!', <?= json_encode($success) ?>);
        }
    </script>
<?php endif; ?>

<?php if ($error): ?>
    <script>
        if (window.notify) {
            notify.error('Lỗi!', <?= json_encode($error) ?>);
        }
    </script>
<?php endif; ?>

<?php
// Load subtab content
if ($current_subtab === 'tickets') {
    require __DIR__ . '/notifications/tickets.php';
} elseif ($current_subtab === 'notices') {
    // Handle notices actions directly here
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notices_action'])) {
        $action = $_POST['notices_action'];

        switch ($action) {
            case 'create':
                try {
                    $title = trim($_POST['title']);
                    $content = trim($_POST['content']);
                    $type = $_POST['type'];
                    $target_user_id = !empty($_POST['target_user_id']) ? $_POST['target_user_id'] : null;

                    // Create notice in database
                    $stmt = $pdo->prepare("INSERT INTO important_notices (title, content, type, target_user_id) VALUES (?, ?, ?, ?)");
                    if ($stmt->execute([$title, $content, $type, $target_user_id])) {
                        $noticeId = $pdo->lastInsertId();
                        $_SESSION['notif_success'] = 'Đã tạo thông báo thành công!';
                    } else {
                        $_SESSION['notif_error'] = 'Có lỗi xảy ra khi tạo thông báo!';
                    }
                } catch (PDOException $e) {
                    $_SESSION['notif_error'] = 'Lỗi database: ' . $e->getMessage();
                }
                header('Location: ?tab=notifications&subtab=notices');
                exit;

            case 'toggle_status':
                try {
                    $id = $_POST['id'];
                    $stmt = $pdo->prepare("UPDATE important_notices SET is_active = NOT is_active WHERE id = ?");
                    $stmt->execute([$id]);

                    // If activating notice, send bell notification
                    $stmt = $pdo->prepare("SELECT is_active FROM important_notices WHERE id = ?");
                    $stmt->execute([$id]);
                    $isActive = $stmt->fetchColumn();

                    $_SESSION['notif_success'] = $isActive ? 'Đã kích hoạt thông báo!' : 'Đã tắt thông báo!';
                } catch (PDOException $e) {
                    $_SESSION['notif_error'] = 'Lỗi: ' . $e->getMessage();
                }
                header('Location: ?tab=notifications&subtab=notices');
                exit;

            case 'delete':
                try {
                    $id = $_POST['id'];
                    $stmt = $pdo->prepare("DELETE FROM important_notices WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $_SESSION['notif_success'] = 'Đã xóa lưu ý thành công!';
                    } else {
                        $_SESSION['notif_error'] = 'Không thể xóa lưu ý!';
                    }
                } catch (PDOException $e) {
                    $_SESSION['notif_error'] = 'Lỗi database: ' . $e->getMessage();
                }
                header('Location: ?tab=notifications&subtab=notices');
                exit;
        }
    }

    // Get notices data
    $stmt = $pdo->query("SELECT n.*, u.username FROM important_notices n LEFT JOIN users u ON n.target_user_id = u.id ORDER BY n.created_at DESC");
    $notices = $stmt->fetchAll();
    $all_users = $pdo->query("SELECT id, username FROM users ORDER BY username ASC")->fetchAll();

    // Include notices template
    include __DIR__ . '/notifications/notices.php';
} else {
    require __DIR__ . '/notifications/' . $current_subtab . '.php';
}
?>