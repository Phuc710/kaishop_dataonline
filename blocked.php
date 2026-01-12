<?php
/**
 * Blocked Page - Basic Design
 * Displayed when user is blocked (IP/Fingerprint/Account)
 */

require_once __DIR__ . '/config/config.php';

// Get ban reason from URL parameter
$reason = isset($_GET['reason']) ? htmlspecialchars($_GET['reason']) : 'Violation of usage policy';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Blocked - Kai Shop</title>
    <?php echo render_favicon_tags(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #000;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .container {
            text-align: center;
            padding: 2.5rem;
            max-width: 500px;
            background: #000;
            border-radius: 8px;
        }

        .icon {
            font-size: 5rem;
            color: #ef4444;
            margin-bottom: 1.5rem;
        }

        h1 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            color: #ef4444;
            text-transform: uppercase;
            font-weight: 800;
        }

        .subtitle {
            font-size: 1.1rem;
            color: #ccc;
            margin-bottom: 2rem;
        }

        .reason-box {
            background: #111;
            border: 1px dashed #ef4444;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }

        .reason-title {
            color: #ef4444;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 0.5rem;
        }

        .reason-text {
            color: #fff;
            font-size: 1.2rem;
            line-height: 1.5;
            font-weight: 600;
        }

        .contact-btn {
            display: inline-block;
            text-decoration: none;
            background: #ef4444;
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 4px;
            font-weight: 700;
            font-size: 1rem;
            margin-top: 1rem;
        }

        @media (max-width: 480px) {
            .container {
                margin: 1rem;
                padding: 1.5rem;
            }
            h1 { font-size: 1.8rem; }
        }
    </style>
</head>

<body>
    <div class="container">

        <h1>Access Blocked</h1>
        <p class="subtitle">System access has been restricted</p>

        <div class="reason-box">
            <div class="reason-title">Ban Reason</div>
            <div class="reason-text"><?= $reason ?></div>
        </div>

        <a href="https://t.me/kaishop25" target="_blank" class="contact-btn">
            <i class="fa-solid fa-right-to-bracket"></i>
            Contact Support
        </a>
    </div>
</body>

</html>