<?php
/**
 * Blocked Page - Matrix Rain Design
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
            overflow: hidden;
            position: relative;
        }

        /* Matrix Rain Background */
        .matrix-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            display: flex;
            z-index: 1;
            overflow: hidden;
        }

        .matrix-pattern {
            position: relative;
            width: 1000px;
            height: 100%;
            flex-shrink: 0;
        }

        .matrix-column {
            position: absolute;
            top: -100%;
            width: 20px;
            height: 100%;
            font-size: 16px;
            line-height: 18px;
            font-weight: bold;
            animation: fall linear infinite;
            white-space: nowrap;
        }

        .matrix-column::before {
            content: "アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲンABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            position: absolute;
            top: 0;
            left: 0;
            background: linear-gradient(to bottom, #ffffff 0%, #ffffff 5%, #00ff41 10%, #00ff41 20%, #00dd33 30%, #00bb22 40%, #009911 50%, #007700 60%, #005500 70%, #003300 80%, rgba(0, 255, 65, 0.5) 90%, transparent 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            writing-mode: vertical-lr;
            letter-spacing: 1px;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .matrix-column:nth-child(1) {
            left: 0px;
            animation-delay: -2.5s;
            animation-duration: 3s;
        }

        .matrix-column:nth-child(2) {
            left: 25px;
            animation-delay: -3.2s;
            animation-duration: 4s;
        }

        .matrix-column:nth-child(3) {
            left: 50px;
            animation-delay: -1.8s;
            animation-duration: 2.5s;
        }

        .matrix-column:nth-child(4) {
            left: 75px;
            animation-delay: -2.9s;
            animation-duration: 3.5s;
        }

        .matrix-column:nth-child(5) {
            left: 100px;
            animation-delay: -1.5s;
            animation-duration: 3s;
        }

        .matrix-column:nth-child(6) {
            left: 125px;
            animation-delay: -3.8s;
            animation-duration: 4.5s;
        }

        .matrix-column:nth-child(7) {
            left: 150px;
            animation-delay: -2.1s;
            animation-duration: 2.8s;
        }

        .matrix-column:nth-child(8) {
            left: 175px;
            animation-delay: -2.7s;
            animation-duration: 3.2s;
        }

        .matrix-column:nth-child(9) {
            left: 200px;
            animation-delay: -3.4s;
            animation-duration: 3.8s;
        }

        .matrix-column:nth-child(10) {
            left: 225px;
            animation-delay: -1.9s;
            animation-duration: 2.7s;
        }

        .matrix-column:nth-child(11) {
            left: 250px;
            animation-delay: -3.6s;
            animation-duration: 4.2s;
        }

        .matrix-column:nth-child(12) {
            left: 275px;
            animation-delay: -2.3s;
            animation-duration: 3.1s;
        }

        .matrix-column:nth-child(13) {
            left: 300px;
            animation-delay: -3.1s;
            animation-duration: 3.6s;
        }

        .matrix-column:nth-child(14) {
            left: 325px;
            animation-delay: -2.6s;
            animation-duration: 2.9s;
        }

        .matrix-column:nth-child(15) {
            left: 350px;
            animation-delay: -3.7s;
            animation-duration: 4.1s;
        }

        .matrix-column:nth-child(16) {
            left: 375px;
            animation-delay: -2.8s;
            animation-duration: 3.3s;
        }

        .matrix-column:nth-child(17) {
            left: 400px;
            animation-delay: -3.3s;
            animation-duration: 3.7s;
        }

        .matrix-column:nth-child(18) {
            left: 425px;
            animation-delay: -2.2s;
            animation-duration: 2.6s;
        }

        .matrix-column:nth-child(19) {
            left: 450px;
            animation-delay: -3.9s;
            animation-duration: 4.3s;
        }

        .matrix-column:nth-child(20) {
            left: 475px;
            animation-delay: -2.4s;
            animation-duration: 3.4s;
        }

        .matrix-column:nth-child(21) {
            left: 500px;
            animation-delay: -1.7s;
            animation-duration: 2.4s;
        }

        .matrix-column:nth-child(22) {
            left: 525px;
            animation-delay: -3.5s;
            animation-duration: 3.9s;
        }

        .matrix-column:nth-child(23) {
            left: 550px;
            animation-delay: -2s;
            animation-duration: 3s;
        }

        .matrix-column:nth-child(24) {
            left: 575px;
            animation-delay: -4s;
            animation-duration: 4.4s;
        }

        .matrix-column:nth-child(25) {
            left: 600px;
            animation-delay: -1.6s;
            animation-duration: 2.3s;
        }

        .matrix-column:nth-child(26) {
            left: 625px;
            animation-delay: -3s;
            animation-duration: 3.5s;
        }

        .matrix-column:nth-child(27) {
            left: 650px;
            animation-delay: -3.8s;
            animation-duration: 4s;
        }

        .matrix-column:nth-child(28) {
            left: 675px;
            animation-delay: -2.5s;
            animation-duration: 2.8s;
        }

        .matrix-column:nth-child(29) {
            left: 700px;
            animation-delay: -3.2s;
            animation-duration: 3.6s;
        }

        .matrix-column:nth-child(30) {
            left: 725px;
            animation-delay: -2.7s;
            animation-duration: 3.2s;
        }

        .matrix-column:nth-child(31) {
            left: 750px;
            animation-delay: -1.8s;
            animation-duration: 2.7s;
        }

        .matrix-column:nth-child(32) {
            left: 775px;
            animation-delay: -3.6s;
            animation-duration: 4.1s;
        }

        .matrix-column:nth-child(33) {
            left: 800px;
            animation-delay: -2.1s;
            animation-duration: 3.1s;
        }

        .matrix-column:nth-child(34) {
            left: 825px;
            animation-delay: -3.4s;
            animation-duration: 3.7s;
        }

        .matrix-column:nth-child(35) {
            left: 850px;
            animation-delay: -2.8s;
            animation-duration: 2.9s;
        }

        .matrix-column:nth-child(36) {
            left: 875px;
            animation-delay: -3.7s;
            animation-duration: 4.2s;
        }

        .matrix-column:nth-child(37) {
            left: 900px;
            animation-delay: -2.3s;
            animation-duration: 3.3s;
        }

        .matrix-column:nth-child(38) {
            left: 925px;
            animation-delay: -1.9s;
            animation-duration: 2.5s;
        }

        .matrix-column:nth-child(39) {
            left: 950px;
            animation-delay: -3.5s;
            animation-duration: 3.8s;
        }

        .matrix-column:nth-child(40) {
            left: 975px;
            animation-delay: -2.6s;
            animation-duration: 3.4s;
        }

        .matrix-column:nth-child(odd)::before {
            content: "アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲン123456789";
        }

        .matrix-column:nth-child(even)::before {
            content: "ガギグゲゴザジズゼゾダヂヅデドバビブベボパピプペポヴァィゥェォャュョッABCDEFGHIJKLMNOPQRSTUVWXYZ";
        }

        .matrix-column:nth-child(3n)::before {
            content: "アカサタナハマヤラワイキシチニヒミリウクスツヌフムユルエケセテネヘメレオコソトノホモヨロヲン0987654321";
        }

        .matrix-column:nth-child(4n)::before {
            content: "ンヲロヨモホノトソコオレメヘネテセケエルユムフヌツスクウリミヒニチシキイワラヤマハナタサカア";
        }

        .matrix-column:nth-child(5n)::before {
            content: "ガザダバパギジヂビピグズヅブプゲゼデベペゴゾドボポヴァィゥェォャュョッ!@#$%^&*()_+-=[]{}|;:,.<>?";
        }

        @keyframes fall {
            0% {
                transform: translateY(-10%);
                opacity: 1;
            }

            100% {
                transform: translateY(200%);
                opacity: 0;
            }
        }

        /* Content Container */
        .container {
            position: relative;
            z-index: 10;
            text-align: center;
            padding: 2.5rem;
            max-width: 500px;
            background: rgba(0, 0, 0, 0.85);
            border-radius: 8px;
            border: 1px solid #00ff41;
            box-shadow: 0 0 30px rgba(0, 255, 65, 0.3);
        }

        .icon {
            font-size: 5rem;
            color: #ef4444;
            margin-bottom: 1.5rem;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.7;
                transform: scale(1.05);
            }
        }

        h1 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            color: #ef4444;
            text-transform: uppercase;
            font-weight: 800;
            text-shadow: 0 0 10px rgba(239, 68, 68, 0.5);
        }

        .subtitle {
            font-size: 1.1rem;
            color: #00ff41;
            margin-bottom: 2rem;
            text-shadow: 0 0 5px rgba(0, 255, 65, 0.5);
        }

        .reason-box {
            background: rgba(17, 17, 17, 0.8);
            border: 1px dashed #ef4444;
            padding: 1.5rem;
            margin: 1.5rem 0;
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.2);
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
            transition: all 0.3s ease;
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.4);
        }

        .contact-btn:hover {
            background: #dc2626;
            box-shadow: 0 0 25px rgba(239, 68, 68, 0.6);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .matrix-column {
                font-size: 14px;
                line-height: 16px;
                width: 18px;
            }
        }

        @media (max-width: 480px) {
            .matrix-column {
                font-size: 12px;
                line-height: 14px;
                width: 15px;
            }

            .container {
                margin: 1rem;
                padding: 1.5rem;
            }

            h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>

<body>
    <!-- Matrix Rain Background -->
    <div class="matrix-container">
        <?php for ($pattern = 0; $pattern < 5; $pattern++): ?>
            <div class="matrix-pattern">
                <?php for ($col = 0; $col < 40; $col++): ?>
                    <div class="matrix-column"></div>
                <?php endfor; ?>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Content -->
    <div class="container">
        <div class="icon">
            <i class="fa-solid fa-ban"></i>
        </div>

        <h1>Access Blocked</h1>
        <p class="subtitle">System access has been restricted</p>

        <div class="reason-box">
            <div class="reason-title">Ban Reason</div>
            <div class="reason-text"><?= $reason ?></div>
        </div>

        <a href="<?= defined('CONTACT_TELEGRAM') ? CONTACT_TELEGRAM : 'https://t.me/kaishop25' ?>" target="_blank"
            class="contact-btn">
            <i class="fa-brands fa-telegram"></i>
            Contact Support
        </a>
    </div>
</body>

</html>