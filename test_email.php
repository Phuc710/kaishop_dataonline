<?php
/**
 * Email Testing Page - KaiShop
 * Test các chức năng gửi email với giao diện đẹp
 */

// Include config (config.php sẽ tự động xử lý session và BASE_PATH)
require_once __DIR__ . '/config/config.php';
require_once BASE_PATH . '/includes/EmailSender.php';

// Xử lý gửi email khi form được submit
$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $error = 'Email không hợp lệ!';
    } else {
        $action = $_POST['action'];

        try {
            switch ($action) {
                case 'welcome':
                    $user = [
                        'username' => $_POST['username'] ?? 'Người dùng mới',
                        'email' => $email
                    ];
                    $success = EmailSender::sendWelcomeEmail($user);
                    $result = $success ? 'Email chào mừng đã được gửi thành công!' : 'Gửi email thất bại!';
                    break;

                case 'reset_password':
                    $token = bin2hex(random_bytes(32)); // Token giả để test
                    $success = EmailSender::sendResetPasswordEmail($email, $token);
                    $result = $success ? 'Email đặt lại mật khẩu đã được gửi thành công!' : 'Gửi email thất bại!';
                    break;

                case 'thank_you':
                    $user = [
                        'username' => $_POST['username'] ?? 'Khách hàng',
                        'email' => $email
                    ];
                    $reason = $_POST['reason'] ?? 'general';
                    $success = EmailSender::sendThankYouEmail($user, $reason);
                    $result = $success ? 'Email cảm ơn đã được gửi thành công!' : 'Gửi email thất bại!';
                    break;

                case 'custom':
                    $subject = $_POST['subject'] ?? 'Test Email';
                    $message = $_POST['message'] ?? 'Đây là email test';
                    $success = EmailSender::send($email, $subject, $message);
                    $result = $success ? 'Email tùy chỉnh đã được gửi thành công!' : 'Gửi email thất bại!';
                    break;

                default:
                    $error = 'Hành động không hợp lệ!';
            }
        } catch (Exception $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email Sender - KaiShop</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #0a0a12 0%, #1a1a2e 100%);
            color: #f1f5f9;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: linear-gradient(90deg, #7c3aed 0%, #d946ef 100%);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(167, 139, 250, 0.3);
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 500;
            animation: slideIn 0.3s ease-out;
        }

        .alert-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: 1px solid #34d399;
        }

        .alert-error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border: 1px solid #f87171;
        }

        .test-section {
            background: #12121e;
            border: 1px solid #2a2a3f;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 24px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .test-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(167, 139, 250, 0.2);
        }

        .test-section h2 {
            color: #a78bfa;
            margin-bottom: 20px;
            font-size: 1.5em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .icon {
            font-size: 1.3em;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #94a3b8;
            font-weight: 500;
        }

        input[type="email"],
        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 12px 16px;
            background: #1a1a2e;
            border: 1px solid #2a2a3f;
            border-radius: 8px;
            color: #f1f5f9;
            font-size: 15px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input[type="email"]:focus,
        input[type="text"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #a78bfa;
            box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.1);
        }

        textarea {
            min-height: 100px;
            resize: vertical;
            font-family: inherit;
        }

        .btn {
            padding: 14px 32px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(90deg, #7c3aed 0%, #d946ef 100%);
            color: #fff;
            box-shadow: 0 4px 16px rgba(167, 139, 250, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(167, 139, 250, 0.5);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .info-box {
            background: #1a1a2e;
            border-left: 4px solid #a78bfa;
            padding: 16px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .info-box p {
            color: #94a3b8;
            line-height: 1.6;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2em;
            }

            .test-section {
                padding: 20px;
            }

            .btn-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>📧 Email Sender Test</h1>
            <p>Kiểm tra các chức năng gửi email của KaiShop</p>
        </div>

        <?php if ($result): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($result) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Email chào mừng -->
        <div class="test-section">
            <h2><span class="icon">🚀</span> Email Chào Mừng</h2>
            <form method="POST">
                <input type="hidden" name="action" value="welcome">
                <div class="form-group">
                    <label>Email nhận:</label>
                    <input type="email" name="email" placeholder="example@gmail.com" required>
                </div>
                <div class="form-group">
                    <label>Tên người dùng:</label>
                    <input type="text" name="username" placeholder="Nguyễn Văn A" value="Người dùng mới">
                </div>
                <button type="submit" class="btn btn-primary">
                    <span>📨</span> Gửi Email Chào Mừng
                </button>
            </form>
            <div class="info-box">
                <p>Email chào mừng được gửi khi người dùng đăng ký tài khoản mới.</p>
            </div>
        </div>

        <!-- Email đặt lại mật khẩu -->
        <div class="test-section">
            <h2><span class="icon">🔐</span> Email Đặt Lại Mật Khẩu</h2>
            <form method="POST">
                <input type="hidden" name="action" value="reset_password">
                <div class="form-group">
                    <label>Email nhận:</label>
                    <input type="email" name="email" placeholder="example@gmail.com" required>
                </div>
                <button type="submit" class="btn btn-primary">
                    <span>🔑</span> Gửi Email Reset Password
                </button>
            </form>
            <div class="info-box">
                <p>Email chứa link đặt lại mật khẩu (token có hiệu lực 60 phút).</p>
            </div>
        </div>

        <!-- Email cảm ơn -->
        <div class="test-section">
            <h2><span class="icon">💜</span> Email Cảm Ơn</h2>
            <form method="POST">
                <input type="hidden" name="action" value="thank_you">
                <div class="form-group">
                    <label>Email nhận:</label>
                    <input type="email" name="email" placeholder="example@gmail.com" required>
                </div>
                <div class="form-group">
                    <label>Tên người dùng:</label>
                    <input type="text" name="username" placeholder="Khách hàng" value="Khách hàng">
                </div>
                <div class="form-group">
                    <label>Lý do cảm ơn:</label>
                    <select name="reason">
                        <option value="general">Chung chung</option>
                        <option value="purchase">Mua hàng</option>
                        <option value="registration">Đăng ký</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <span>💌</span> Gửi Email Cảm Ơn
                </button>
            </form>
            <div class="info-box">
                <p>Email cảm ơn khách hàng sau khi mua hàng hoặc đăng ký.</p>
            </div>
        </div>

        <!-- Email tùy chỉnh -->
        <div class="test-section">
            <h2><span class="icon">✉️</span> Email Tùy Chỉnh</h2>
            <form method="POST">
                <input type="hidden" name="action" value="custom">
                <div class="form-group">
                    <label>Email nhận:</label>
                    <input type="email" name="email" placeholder="example@gmail.com" required>
                </div>
                <div class="form-group">
                    <label>Tiêu đề:</label>
                    <input type="text" name="subject" placeholder="Tiêu đề email" value="Test Email từ KaiShop">
                </div>
                <div class="form-group">
                    <label>Nội dung (HTML hoặc text):</label>
                    <textarea name="message"
                        placeholder="Nội dung email..."><h2>Xin chào!</h2><p>Đây là email test từ KaiShop.</p></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <span>📧</span> Gửi Email Tùy Chỉnh
                </button>
            </form>
            <div class="info-box">
                <p>Gửi email với nội dung tùy chỉnh (hỗ trợ HTML).</p>
            </div>
        </div>
    </div>
</body>

</html>