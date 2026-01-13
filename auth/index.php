<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/favicon_helper.php';
require_once __DIR__ . '/../includes/HolidayModeManager.php';

// Check maintenance mode and fetch tab_logo
$tab_logo = 'images/kaishop.gif'; // Default
try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('maintenance_mode', 'tab_logo')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Maintenance Check
    if (($settings['maintenance_mode'] ?? '0') == '1') {
        header('Location: /kaishop/maintenance');
        exit;
    }

    // Tab Logo
    if (!empty($settings['tab_logo'])) {
        $tab_logo = $settings['tab_logo'];
    }
} catch (Exception $e) {
    // If error, continue
}

if (isLoggedIn()) {
    redirect(url(''));
}

// Function để ghi log đăng nhập
function logUserLogin($userId, $username, $status, $failReason = null)
{
    global $pdo;

    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }

        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $logId = generateShortId();

        if ($status === 'success') {
            $description = "User {$username} đăng nhập thành công";
            $action = 'login_success';
        } else {
            $description = "Đăng nhập thất bại - {$username}" . ($failReason ? ": {$failReason}" : '');
            $action = 'login_failed';
        }

        $stmt = $pdo->prepare("
            INSERT INTO system_logs (
                id, log_type, user_id, action, description, 
                ip_address, user_agent, created_at
            ) VALUES (?, 'user_login', ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $logId,
            $status === 'success' ? $userId : null,
            $action,
            $description,
            $ip,
            $userAgent
        ]);
    } catch (Exception $e) {
        error_log("Error logging user login: " . $e->getMessage());
    }
}

/**
 * Verify reCAPTCHA Enterprise token (same as google-login.php)
 */
function verifyRecaptchaToken($token)
{
    // Skip verification on localhost or if secret key is not configured
    if ($token === 'localhost_skip' || empty(RECAPTCHA_SECRET_KEY)) {
        error_log('[reCAPTCHA] Skipping verification (localhost or no secret key)');
        return ['success' => true, 'score' => 1.0, 'skipped' => true];
    }

    try {
        // Prepare API request
        $url = 'https://recaptchaenterprise.googleapis.com/v1/projects/' . RECAPTCHA_PROJECT_ID . '/assessments?key=' . RECAPTCHA_SECRET_KEY;

        $payload = [
            'event' => [
                'token' => $token,
                'siteKey' => RECAPTCHA_SITE_KEY,
                'expectedAction' => 'login'
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log('[reCAPTCHA] API request failed with HTTP code: ' . $httpCode);
            return ['success' => false, 'error' => 'API request failed'];
        }

        $result = json_decode($response, true);

        if (!isset($result['tokenProperties']['valid']) || !$result['tokenProperties']['valid']) {
            error_log('[reCAPTCHA] Invalid token');
            return ['success' => false, 'error' => 'Token không hợp lệ'];
        }

        if (!isset($result['tokenProperties']['action']) || $result['tokenProperties']['action'] !== 'login') {
            error_log('[reCAPTCHA] Action mismatch');
            return ['success' => false, 'error' => 'Action không khớp'];
        }

        $score = $result['riskAnalysis']['score'] ?? 0;

        if ($score < RECAPTCHA_MIN_SCORE) {
            error_log('[reCAPTCHA] Score too low: ' . $score);
            return ['success' => false, 'error' => 'Điểm xác thực quá thấp', 'score' => $score];
        }

        error_log('[reCAPTCHA] Verification successful, score: ' . $score);
        return ['success' => true, 'score' => $score];

    } catch (Exception $e) {
        error_log('[reCAPTCHA] Exception: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Lỗi hệ thống'];
    }
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $recaptchaToken = $_POST['recaptcha_token'] ?? '';

    if (empty($username) || empty($password)) {
        setFlash('error', 'Vui lòng nhập đầy đủ thông tin');
        logUserLogin(null, $username, 'failed', 'Thông tin không đầy đủ');
    } else {
        // Verify reCAPTCHA token
        if (empty($recaptchaToken)) {
            setFlash('error', 'Vui lòng xác thực bảo mật');
            logUserLogin(null, $username, 'failed', 'Missing reCAPTCHA token');
        } else {
            // Verify Cloudflare Turnstile
            $turnstileToken = TurnstileVerifier::getTokenFromRequest();
            if (!TurnstileVerifier::verify($turnstileToken)) {
                setFlash('error', 'Xác thực thất bại. Vui lòng thử lại.');
                logUserLogin(null, $username, 'failed', 'BOT L verification failed');
            } else {
                // Verify reCAPTCHA token (optional dual layer)
                $recaptchaResult = verifyRecaptchaToken($recaptchaToken);
                if (!$recaptchaResult['success']) {
                    setFlash('error', 'Xác thực bảo mật thất bại. Vui lòng thử lại.');
                    logUserLogin(null, $username, 'failed', 'reCAPTCHA verification failed: ' . $recaptchaResult['error']);
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1");
                    $stmt->execute([$username, $username]);
                    $user = $stmt->fetch();

                    if ($user && verifyPassword($password, $user['password'])) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];

                        $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

                        // Ghi log đăng nhập thành công
                        logUserLogin($user['id'], $user['username'], 'success');

                        setFlash('success', 'Đăng nhập thành công!');
                        redirect(url(''));
                    } else {
                        // SECURITY: Generic error message - không tiết lộ là sai username hay password
                        logUserLogin(null, $username, 'failed', 'Invalid credentials');
                        setFlash('error', 'Tài khoản hoặc mật khẩu không đúng');
                    }
                }
            }
        }
    }
}

// Handle Register
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        setFlash('error', 'Vui lòng điền đầy đủ thông tin');
    } elseif (strlen($username) < 3) {
        setFlash('error', 'Tên đăng nhập phải có ít nhất 3 ký tự');
    } elseif (!isValidEmail($email)) {
        setFlash('error', 'Email không hợp lệ');
    } elseif (strlen($password) < 6) {
        setFlash('error', 'Mật khẩu phải có ít nhất 6 ký tự');
    } elseif ($password !== $confirm_password) {
        setFlash('error', 'Mật khẩu xác nhận không khớp');
    } else {
        // Verify Cloudflare Turnstile
        $turnstileToken = TurnstileVerifier::getTokenFromRequest();
        if (!TurnstileVerifier::verify($turnstileToken)) {
            setFlash('error', 'Xác thực bảo mật Turnstile thất bại. Vui lòng thử lại.');
        } else {
            // Check existing
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                setFlash('error', 'Tên đăng nhập đã tồn tại');
            } else {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    setFlash('error', 'Email đã được sử dụng');
                } else {
                    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                    $role = ($count == 0) ? 'admin' : 'user';

                    $id = generateShortId();
                    $hashed_password = hashPassword($password);

                    $stmt = $pdo->prepare("INSERT INTO users (id, username, email, password, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");

                    if ($stmt->execute([$id, $username, $email, $hashed_password, $role])) {
                        // Send welcome email
                        EmailSender::sendWelcomeEmail([
                            'username' => $username,
                            'email' => $email
                        ]);

                        $_SESSION['user_id'] = $id;
                        $_SESSION['username'] = $username;
                        $_SESSION['role'] = $role;

                        setFlash('success', $role === 'admin' ? 'Chúc mừng! Bạn là Admin đầu tiên!' : 'Đăng ký thành công!');
                        redirect(url(''));
                    } else {
                        setFlash('error', 'Có lỗi xảy ra, vui lòng thử lại');
                    }
                }
            }
        }
    }
}

$activeTab = $_GET['tab'] ?? 'login';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auth - <?= SITE_NAME ?></title>

    <?php echo render_favicon_tags(); ?>

    <!-- Holiday Mode Assets -->
    <?php if (HolidayModeManager::isActive()): ?>
        <?php
        $currentMode = HolidayModeManager::getCurrentMode();
        $cssFile = '';
        switch ($currentMode) {
            case 'halloween':
                $cssFile = 'halloween.css';
                break;
            case 'noel':
                $cssFile = 'noel.css';
                break;
            case 'tet':
                $cssFile = 'tet.css';
                break;
        }
        if ($cssFile):
        ?>
            <link rel="stylesheet" href="<?= asset('css/' . $cssFile) ?>?v=<?= time() ?>">
        <?php endif; ?>
        <script src="<?= asset('js/holiday-effects.js') ?>?v=<?= time() ?>"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof HolidayEffects !== 'undefined') {
                    HolidayEffects.init('<?= $currentMode ?>');
                }
            });
        </script>
    <?php endif; ?>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= url('auth/index.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/light-theme.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/loading.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/notify.css') ?>">
    <script src="<?= url('assets/js/loading.js') ?>"></script>
    <script src="<?= url('assets/js/notify.js') ?>"></script>
    <script>
        window.APP_CONFIG = {
            baseUrl: '<?= BASE_URL ?>',
            siteName: '<?= SITE_NAME ?>'
        };
    </script>
    <script src="<?= url('assets/js/theme-switcher.js') ?>?v=<?= time() ?>"></script>

    <!-- Firebase SDK -->
    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
        import { getAuth, signInWithPopup, GoogleAuthProvider } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';

        // Firebase Configuration
        const firebaseConfig = {
            apiKey: "AIzaSyC8CQbJAylfIkqE-tdM2e2vsHcr5iuZmlw",
            authDomain: "kaishop-b0f1d.firebaseapp.com",
            databaseURL: "https://kaishop-b0f1d-default-rtdb.asia-southeast1.firebasedatabase.app",
            projectId: "kaishop-b0f1d",
            storageBucket: "kaishop-b0f1d.firebasestorage.app",
            messagingSenderId: "800821561970",
            appId: "1:800821561970:web:1c2385260b24a3e12890eb",
            measurementId: "G-KS30HF01HE"
        };

        // Initialize Firebase
        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);

        // Make available globally
        window.firebaseAuth = auth;
        window.GoogleAuthProvider = GoogleAuthProvider;
        window.signInWithPopup = signInWithPopup;
    </script>

    <!-- reCAPTCHA Enterprise -->
    <script
        src="https://www.google.com/recaptcha/enterprise.js?render=6Lf2cSosAAAAAI0UuvpT-i9XE9Qw5sxpK3GNEn6m"></script>

    <!-- Cloudflare Turnstile -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

</head>

<body class="<?= HolidayModeManager::getBodyClass() ?>">
    <div class="auth-container">

        <!-- Auth Card -->
        <div class="auth-card">
            <a href="<?= url('') ?>" class="back-link-in-card">
                <i class="fas fa-arrow-left"></i>
                Về trang chủ
            </a>
            <!-- Flash Messages -->
            <?php $flash = getFlash(); ?>
            <?php if ($flash): ?>
                <?php if ($flash['type'] === 'error'): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= $flash['message'] ?>
                    </div>
                <?php endif; ?>
                <?php if ($flash['type'] === 'success'): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= $flash['message'] ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Login Tab -->
            <div id="login-tab" class="tab-content <?= $activeTab === 'login' ? 'active' : '' ?>">
                <div class="auth-header">
                    <h1>Chào mừng trở lại 👋</h1>
                    <p class="subtitle">Vui lòng đăng nhập để sử dụng dịch vụ</p>
                </div>

                <button type="button" class="btn-google" id="googleLoginBtn">
                    <svg class="google-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                        <path fill="#FFC107"
                            d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z" />
                        <path fill="#FF3D00"
                            d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z" />
                        <path fill="#4CAF50"
                            d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z" />
                        <path fill="#1976D2"
                            d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z" />
                    </svg>
                    Đăng nhập bằng Google
                </button>

                <div class="divider">
                    <span class="marquee-wrapper"><span class="marquee-text" style="color: #fff;">Miền chính:
                            Kaishop.id.vn</span></span>
                </div>

                <form method="POST" id="loginForm">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="recaptcha_token" id="recaptcha_token">

                    <div class="form-group">
                        <label>Tài Khoản</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" name="username" placeholder="Nhập email hoặc tài khoản" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Mật Khẩu</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="password" id="loginPassword" placeholder="••••••••" required>
                            <i class="fas fa-eye-slash password-toggle"
                                onclick="togglePassword('loginPassword', this)"></i>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="rememberMe" name="remember">
                            <label for="rememberMe">Lưu đăng nhập</label>
                        </div>
                        <a href="forgot-password" class="forgot-link">Quên mật khẩu?</a>
                    </div>

                    <!-- Cloudflare Turnstile Widget -->
                    <div class="cf-turnstile" data-sitekey="<?= TURNSTILE_SITE_KEY ?>" data-theme="dark"></div>

                    <button type="button" class="btn-submit" onclick="submitLoginForm()">
                        <i class="fas fa-arrow-right"></i> Đăng Nhập
                    </button>
                </form>

                <div class="auth-footer">
                    Chưa có tài khoản? <a href="?tab=register">Đăng Ký Ngay</a>
                </div>
            </div>

            <!-- Register Tab -->
            <div id="register-tab" class="tab-content <?= $activeTab === 'register' ? 'active' : '' ?>">
                <div class="auth-header">
                    <h1>Cùng bắt đầu nào 😎</h1>
                    <p class="subtitle">Bắt đầu sử dụng dịch vụ bằng cách đăng ký tài khoản mới</p>
                </div>

                <button type="button" class="btn-google" id="googleRegisterBtn">
                    <svg class="google-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                        <path fill="#FFC107"
                            d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z" />
                        <path fill="#FF3D00"
                            d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z" />
                        <path fill="#4CAF50"
                            d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z" />
                        <path fill="#1976D2"
                            d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z" />
                    </svg>
                    Đăng ký bằng Google
                </button>

                <div class="divider">
                    <span class="marquee-wrapper"><span class="marquee-text">Miễn chính: Kaishop.id.vn</span></span>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="register">

                    <div class="form-group">
                        <label>Địa chỉ Email</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" name="email" placeholder="Nhập địa chỉ email" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tên Đăng Nhập</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" name="username" placeholder="Nhập tên tài khoản" required minlength="3">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Mật Khẩu</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="password" id="registerPassword" placeholder="••••••••" required
                                minlength="6">
                            <i class="fas fa-eye-slash password-toggle"
                                onclick="togglePassword('registerPassword', this)"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Nhập Lại Mật Khẩu</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="confirm_password" id="confirmPassword" placeholder="••••••••"
                                required minlength="6">
                            <i class="fas fa-eye-slash password-toggle"
                                onclick="togglePassword('confirmPassword', this)"></i>
                        </div>
                    </div>

                    <div class="terms-wrapper">
                        <input type="checkbox" id="agreeTerms" required>
                        <label for="agreeTerms">
                            Tôi đồng ý với <a href="#">Chính sách & Điều khoản</a>
                        </label>
                    </div>

                    <!-- Cloudflare Turnstile Widget -->
                    <div class="cf-turnstile" data-sitekey="<?= TURNSTILE_SITE_KEY ?>" data-theme="dark"></div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-user-plus"></i> Đăng Ký
                    </button>
                </form>

                <div class="auth-footer">
                    Đã có tài khoản? <a href="?tab=login">Đăng Nhập Ngay</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password Toggle Function
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }

        function switchTab(tab) {
            // Update URL
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.history.pushState({}, '', url);

            // Update tabs
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

            if (tab === 'login') {
                document.querySelectorAll('.tab')[0].classList.add('active');
                document.getElementById('login-tab').classList.add('active');
            } else {
                document.querySelectorAll('.tab')[1].classList.add('active');
                document.getElementById('register-tab').classList.add('active');
            }
        }

        <?php if (hasFlash()): ?>             <?php $flash = getFlash(); ?>         document.addEventListener('DOMContentLoaded', function () {             notify.<?= $flash['type'] === 'success' ? 'success' : 'error' ?>(                 '<?= $flash['type'] === 'success' ? 'Thành công!' : 'Lỗi!' ?>',                 '<?= addslashes($flash['message']) ?>'             );         });
        <?php endif; ?>

        // Google Sign-In Handler with reCAPTCHA
        async function handleGoogleSignIn() {
            const btn = event.target.closest('.btn-google');
            if (!btn) return;

            try {
                btn.disabled = true;
                btn.innerHTML = '<span class="loading-icon-inline"></span> Đang xử lý...';

                // Execute reCAPTCHA (skip on localhost)
                let recaptchaToken = null;
                if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                    recaptchaToken = 'localhost_skip';
                } else {
                    try {
                        await grecaptcha.enterprise.ready();
                        recaptchaToken = await grecaptcha.enterprise.execute('6Lf2cSosAAAAAI0UuvpT-i9XE9Qw5sxpK3GNEn6m', { action: 'login' });
                    } catch (recaptchaError) {
                        console.error('reCAPTCHA Error:', recaptchaError);
                        throw new Error('Xác thực bảo mật thất bại. Vui lòng thử lại.');
                    }
                }

                // Proceed with Google Sign-In
                const provider = new window.GoogleAuthProvider();
                const result = await window.signInWithPopup(window.firebaseAuth, provider);
                const user = result.user;

                // Prepare payload with full Google profile + reCAPTCHA token
                const payload = {
                    email: user.email,
                    displayName: user.displayName,
                    photoURL: user.photoURL,
                    idToken: await user.getIdToken(),
                    recaptchaToken: recaptchaToken
                };

                // Send token to backend
                const response = await fetch('<?= url('auth/google-login.php') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.success) {
                    notify.success('Thành công!', 'Đăng nhập Google thành công!');
                    setTimeout(() => {
                        window.location.href = '<?= url('') ?>';
                    }, 500);
                } else {
                    throw new Error(data.message || 'Lỗi đăng nhập');
                }
            } catch (error) {
                console.error('Google Sign-In Error:', error);

                let errorMessage = 'Đăng nhập Google thất bại';

                if (error.code === 'auth/popup-blocked') {
                    errorMessage = 'Trình duyệt chặn popup. Vui lòng cho phép popup và thử lại.';
                } else if (error.code === 'auth/popup-closed-by-user') {
                    errorMessage = 'Bạn đã đóng popup đăng nhập.';
                } else if (error.code === 'auth/network-request-failed') {
                    errorMessage = 'Lỗi kết nối mạng. Vui lòng kiểm tra internet.';
                } else if (error.message) {
                    errorMessage = error.message;
                }

                notify.error('Lỗi!', errorMessage);

                // Reset button
                btn.disabled = false;
                const isLogin = btn.id === 'googleLoginBtn';
                btn.innerHTML = `
                    <svg class="google-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                        <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"/>
                        <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"/>
                        <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"/>
                        <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"/>
                    </svg>
                    ${isLogin ? 'Đăng nhập bằng Google' : 'Đăng ký bằng Google'}
                `;
            }
        }

        // Attach event listeners
        document.addEventListener('DOMContentLoaded', function () {
            const googleLoginBtn = document.getElementById('googleLoginBtn');
            const googleRegisterBtn = document.getElementById('googleRegisterBtn');

            if (googleLoginBtn) {
                googleLoginBtn.addEventListener('click', handleGoogleSignIn);
            }
            if (googleRegisterBtn) {
                googleRegisterBtn.addEventListener('click', handleGoogleSignIn);
            }
        });

        // Normal Login Form with reCAPTCHA
        function submitLoginForm() {
            // Skip reCAPTCHA on localhost
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                document.getElementById('recaptcha_token').value = 'localhost_skip';
                document.getElementById('loginForm').submit();
                return;
            }

            grecaptcha.enterprise.ready(async () => {
                try {
                    const token = await grecaptcha.enterprise.execute(
                        '6Lf2cSosAAAAAI0UuvpT-i9XE9Qw5sxpK3GNEn6m',
                        { action: 'login' }
                    );

                    // Set token and submit form
                    document.getElementById('recaptcha_token').value = token;
                    document.getElementById('loginForm').submit();
                } catch (error) {
                    console.error('reCAPTCHA Error:', error);
                    notify.error('Lỗi!', 'Xác thực bảo mật thất bại. Vui lòng thử lại.');
                }
            });
        }
    </script>
</body>

</html>