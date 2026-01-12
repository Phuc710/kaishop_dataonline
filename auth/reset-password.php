<?php
require_once '../config/config.php';

$token = $_GET['token'] ?? '';
$error = null;
$validToken = false;

if (empty($token)) {
    $error = "Liên kết không hợp lệ.";
} else {
    // Verify Token
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = "Liên kết đã hết hạn hoặc không hợp lệ.";
    } else {
        $validToken = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        setFlash('error', 'Mật khẩu phải có ít nhất 6 ký tự');
    } elseif ($password !== $confirm_password) {
        setFlash('error', 'Mật khẩu nhập lại không khớp');
    } else {
        // Update Password
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $updateStmt->execute([$hashed, $user['id']]);

        setFlash('success', 'Mật khẩu đã được thay đổi thành công! Vui lòng đăng nhập.');
        redirect(url('auth'));
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - <?= SITE_NAME ?></title>
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

        h2 {
            color: var(--text-main);
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 20px;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-align: center;
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
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(139, 92, 246, 0.4);
        }

        .alert {
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            border: 1px solid transparent;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.2);
        }
    </style>
</head>

<body>
    <div class="auth-card">
        <?php if ($error): ?>
            <h2>Lỗi</h2>
            <div class="alert alert-error">
                <?= $error ?>
            </div>
            <a href="forgot-password" class="btn-submit"
                style="display:block; text-align:center; text-decoration:none;">Quay lại</a>
        <?php else: ?>
            <h2>Đặt lại mật khẩu</h2>

            <?php $flash = getFlash(); ?>
            <?php if ($flash && $flash['type'] === 'error'): ?>
                <div class="alert alert-error">
                    <?= $flash['message'] ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Mật khẩu mới</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" placeholder="Nhập mật khẩu mới" required minlength="6">
                    </div>
                </div>

                <div class="form-group">
                    <label>Nhập lại mật khẩu</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu" required
                            minlength="6">
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Lưu mật khẩu
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>