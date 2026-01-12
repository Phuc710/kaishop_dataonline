<?php
/**
 * 404 Error Page - Not Found
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/favicon_helper.php';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - <?= SITE_NAME ?></title>

    <?php echo render_favicon_tags(); ?>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #000;
            height: 100vh;
            overflow: hidden;
            position: relative;
        }

        .gif-fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .btn-home {
            position: fixed;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 40px;
            background: rgba(169, 16, 16, 0.58);
            backdrop-filter: blur(10px);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: 18px;
            box-shadow: 0 10px 40px rgba(25, 0, 83, 0.5);
            transition: all 0.3s ease;
            animation: fadeInUp 1s ease 0.5s backwards;
        }

        .btn-home:hover {
            transform: translateX(-50%) translateY(-5px);
            box-shadow: 0 15px 50px rgba(241, 70, 70, 0.7);
            background: rgba(253, 77, 77, 0.48);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        @media (max-width: 768px) {
            .btn-home {
                bottom: 30px;
                padding: 14px 30px;
                font-size: 16px;
            }
        }
    </style>
</head>

<body>
    <img src="<?= asset('images/404.png') ?>" alt="404" class="gif-fullscreen">
    <a href="<?= url('') ?>" style="cursor:pointer;" class="btn-home">
        <i class="fas fa-home"></i>
        Về Trang Chủ
    </a>
</body>

</html>