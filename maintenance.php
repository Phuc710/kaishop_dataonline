<?php
/**
 * Maintenance Page
 * Displayed when system is under maintenance
 */

// Only show this page if maintenance mode is actually ON
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php'; // Need config for BASE_URL if used, or just use relative path logic

try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key = 'maintenance_mode'");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // If maintenance is OFF, redirect to home
    if (($settings['maintenance_mode'] ?? '0') != '1') {
        header('Location: ' . BASE_URL . '/');
        exit;
    }
} catch (Exception $e) {
    // If can't check, show maintenance page anyway
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Đang Bảo Trì - Kai Shop</title>
    <?php
    // Load favicon helper
    require_once __DIR__ . '/includes/favicon_helper.php';
    echo render_favicon_tags();
    ?>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .maintenance-container {
            text-align: center;
            padding: 2rem;
            max-width: 1000px;
            width: 100%;
        }

        .maintenance-image {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .maintenance-container {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="maintenance-container">
        <img src="<?= BASE_URL ?>/assets/images/baotri.png" alt="Hệ thống đang bảo trì" class="maintenance-image">
    </div>

    <script src="<?= BASE_URL ?>/js/maintenance.js?v=<?= time() ?>"></script>
</body>

</html>