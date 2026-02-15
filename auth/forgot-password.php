<?php
/**
 * Forgot Password Handler
 * Handles password reset requests
 * 
 * @package KaiShop
 * @subpackage Authentication
 * @version 2.0.0
 */

require_once '../config/config.php';

// Redirect if logged in
if (isset($_SESSION['user_id'])) {
    redirect(url(''));
    exit;
}

// Initialize password reset service
$passwordResetService = new PasswordResetService($pdo);
$ipAddress = AuthenticationLogger::getClientIpAddress();

// Track if email was successfully sent (for cooldown trigger)
$emailSentSuccessfully = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        setFlash('error', 'Please enter your email address');
    } else {
        // Anti-spam: Check rate limiting
        $rateLimitCheck = $passwordResetService->checkRateLimit($email, $ipAddress);

        if (!$rateLimitCheck['allowed']) {
            setFlash('error', $rateLimitCheck['message']);
        } else {
            // Process password reset request
            $result = $passwordResetService->initiatePasswordReset($email, $ipAddress);

            if ($result['success']) {
                setFlash('success', $result['message']);
                // Only trigger cooldown if email was actually sent
                if ($result['code'] === 'REQUEST_SENT') {
                    $emailSentSuccessfully = true;
                }
            } else {
                setFlash('error', $result['message']);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - <?= SITE_NAME ?></title>

    <?php
    // Load favicon helper
    require_once __DIR__ . '/../includes/favicon_helper.php';
    echo render_favicon_tags();
    ?>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* KaiShop Color Palette */
            --bg-body: #020617;
            --bg-card: rgba(30, 41, 59, 0.5);
            --bg-card-solid: #0f172a;
            --bg-element: rgba(30, 41, 59, 0.8);
            --bg-hover: rgba(30, 41, 59, 0.95);

            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --text-disabled: #64748b;

            --border: rgba(148, 163, 184, 0.15);
            --border-light: rgba(148, 163, 184, 0.1);
            --border-hover: #ffffff;

            --primary: #8b5cf6;
            --primary-hover: #7c3aed;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            /* Home page background - Dark slate with grid pattern */
            background: #020617;
            height: 100vh;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Home page grid pattern overlay */
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(to right, rgba(148, 163, 184, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(148, 163, 184, 0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
            z-index: 0;
        }

        /* Home page gradient glow effects */
        body::after {
            content: "";
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 50% 20%, rgba(139, 92, 246, 0.15) 0%, transparent 60%),
                radial-gradient(ellipse 50% 40% at 80% 80%, rgba(236, 72, 153, 0.1) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
            background: var(--bg-card);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
            margin: 20px;
            z-index: 1;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            text-decoration: none;
            margin-bottom: 24px;
            font-size: 14px;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: var(--primary);
        }

        h2 {
            color: var(--text-main);
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 30px;

        }

        p.subtitle {
            color: var(--text-muted);
            margin-bottom: 32px;
            font-size: 14px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            margin-left: 4px;
        }

        .input-wrapper {
            position: relative;
        }

        input {
            width: 100%;
            padding: 14px 16px 14px 45px;
            background: var(--bg-element);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text-main);
            font-size: 15px;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--bg-card-solid);
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1);
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-disabled);
            font-size: 16px;
            transition: color 0.3s;
        }

        input:focus+.input-icon {
            color: var(--primary);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            overflow: hidden;
            position: relative;
        }

        .btn-submit i {
            transition: transform 0.3s ease;
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(139, 92, 246, 0.4);
        }

        .btn-submit:hover:not(:disabled) i {
            transform: translateX(4px);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-submit.loading {
            pointer-events: none;
        }

        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Cooldown timer */
        .cooldown-timer {
            display: none;
            margin-top: 12px;
            padding: 10px;
            background: rgba(139, 92, 246, 0.1);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 8px;
            color: var(--text-muted);
            font-size: 13px;
            text-align: center;
        }

        .cooldown-timer.active {
            display: block;
        }

        .cooldown-timer strong {
            color: var(--primary);
            font-weight: 600;
        }

        .alert {
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid transparent;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.2);
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
            border-color: rgba(34, 197, 94, 0.2);
        }
    </style>
</head>

<body>
    <div class="auth-card">
        <a href="<?= url('auth') ?>" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Quay lại đăng nhập
        </a>

        <h2>Đặt lại mật khẩu 🔐</h2>

        <?php $flash = getFlash(); ?>
        <?php if ($flash): ?>
            <?php if ($flash['type'] === 'error'): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
            <?php endif; ?>
            <?php if ($flash['type'] === 'success'): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST" id="resetForm">
            <div class="form-group">
                <label>Địa chỉ Email đã đăng ký</label>
                <div class="input-wrapper">
                    <input type="email" name="email" id="emailInput" placeholder="yourname@example.com" required
                        autofocus>
                    <i class="fas fa-envelope input-icon"></i>
                </div>
                <small style="color: var(--text-muted); font-size: 12px; margin-top: 4px; display: block;">
                    💡 Email phải trùng với email bạn đã dùng để đăng ký tài khoản
                </small>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                <span id="btnText">Xác nhận</span>
            </button>

            <div class="cooldown-timer" id="cooldownTimer">
                ⏱️ Vui lòng đợi <strong id="countdown">60</strong> giây trước khi gửi lại
            </div>
        </form>

        <div style="text-align: center; margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--border);">
            <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 8px;">Hỗ trợ 24/7</p>
            <a href="<?= CONTACT_TELEGRAM ?>" target="_blank"
                style="color: var(--primary); text-decoration: none; font-weight: 600; font-size: 14px;">
                <i class="fab fa-telegram" style="font-size: 14px;"></i> Telegram
            </a>
        </div>
    </div>

    <script>
        const form = document.getElementById('resetForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const emailInput = document.getElementById('emailInput');
        const cooldownTimer = document.getElementById('cooldownTimer');
        const countdownEl = document.getElementById('countdown');

        const COOLDOWN_SECONDS = 60;
        const STORAGE_KEY = 'password_reset_cooldown';

        // Check for existing cooldown on page load
        function checkCooldown() {
            const cooldownEnd = localStorage.getItem(STORAGE_KEY);
            if (cooldownEnd) {
                const remaining = Math.ceil((parseInt(cooldownEnd) - Date.now()) / 1000);
                if (remaining > 0) {
                    startCooldown(remaining);
                } else {
                    localStorage.removeItem(STORAGE_KEY);
                }
            }
        }

        // Start cooldown timer
        function startCooldown(seconds) {
            submitBtn.disabled = true;
            emailInput.disabled = true;
            emailInput.removeAttribute('required'); // Remove required to prevent validation error
            cooldownTimer.classList.add('active');

            let remaining = seconds;
            countdownEl.textContent = remaining;

            const interval = setInterval(() => {
                remaining--;
                countdownEl.textContent = remaining;

                if (remaining <= 0) {
                    clearInterval(interval);
                    submitBtn.disabled = false;
                    emailInput.disabled = false;
                    emailInput.setAttribute('required', ''); // Re-add required
                    cooldownTimer.classList.remove('active');
                    localStorage.removeItem(STORAGE_KEY);
                }
            }, 1000);
        }

        // Handle form submission
        form.addEventListener('submit', function (e) {
            // Check if already in cooldown
            const cooldownEnd = localStorage.getItem(STORAGE_KEY);
            if (cooldownEnd && Date.now() < parseInt(cooldownEnd)) {
                e.preventDefault();
                return;
            }

            // Show loading state
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            emailInput.readOnly = true; // Use readOnly so value is sent to server
            btnText.innerHTML = '<span class="spinner"></span>Đang xử lý...';

        });

        <?php if ($emailSentSuccessfully): ?>
            // Email was sent successfully, start cooldown
            const cooldownEndTime = Date.now() + (COOLDOWN_SECONDS * 1000);
            localStorage.setItem(STORAGE_KEY, cooldownEndTime.toString());
            startCooldown(COOLDOWN_SECONDS);
        <?php endif; ?>

        // Check cooldown on page load
        checkCooldown();
    </script>
</body>

</html>