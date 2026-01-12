<?php
// Load Environment Variables from .env file
function loadEnv($path)
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if (strlen($value) > 1 && ($value[0] === '"' || $value[0] === "'") && $value[strlen($value) - 1] === $value[0]) {
                $value = substr($value, 1, -1);
            }

            // Set as environment variable
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

// Load .env file
loadEnv(dirname(__DIR__) . '/.env');

// Helper function to get env variable with default
function env($key, $default = null)
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// Cấu hình timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // Đặt thành 1 nếu dùng HTTPS
session_start();

// Cấu hình đường dẫn
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', env('APP_URL', 'http://localhost/kaishop'));
//define('BASE_URL', env('APP_URL', 'http://localhost/kaishop'));

define('SITE_NAME', env('APP_NAME', 'Kai Shop'));

// Cấu hình email
define('SMTP_HOST', env('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', env('SMTP_PORT', 587));
define('SMTP_USER', env('SMTP_USER', 'kaishop365@gmail.com'));
define('SMTP_PASS', env('SMTP_PASS', 'onkqhepgezpafkts'));
define('EMAIL_FROM', env('EMAIL_FROM', 'kaishop365@gmail.com'));
define('EMAIL_FROM_NAME', env('EMAIL_FROM_NAME', 'KaiShop'));
define('EMAIL_RECIPIENT', env('EMAIL_RECIPIENT', 'kaishop365@gmail.com'));
define('CONTACT_EMAIL', env('CONTACT_EMAIL', 'kaishop365@gmail.com'));

// Cấu hình tiền tệ
define('CURRENCY_VND', 'đ');
define('CURRENCY_USD', '$');
define('DEFAULT_CURRENCY', env('DEFAULT_CURRENCY', 'VND'));
define('EXCHANGE_RATE', env('EXCHANGE_RATE', 24000));

// Cấu hình upload
define('UPLOAD_DIR', BASE_PATH . '/assets/images/uploads/');
define('MAX_FILE_SIZE', env('MAX_FILE_SIZE', 5242880));
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Cấu hình phân trang
define('ITEMS_PER_PAGE', 12);

// Cấu hình bảo mật
define('HASH_ALGO', PASSWORD_BCRYPT);
define('HASH_COST', 12);

// Cấu hình reCAPTCHA Enterprise
define('RECAPTCHA_SITE_KEY', '6LcAwyskAAAAAAkMWVmZnHOfc8JwdDerw+f3wgOX');
define('RECAPTCHA_SECRET_KEY', env('RECAPTCHA_SECRET_KEY', '6LcAwyskAAAAAAkN-v8tl_ASoL.Jb7nMzoTIuwcRy4NLL'));
define('RECAPTCHA_PROJECT_ID', env('RECAPTCHA_PROJECT_ID', 'kaishop-b0f1d'));
define('RECAPTCHA_MIN_SCORE', 1.0); // Minimum score for legitimate users (0.0 - 1.0)

// Cấu hình Cloudflare Turnstile
define('TURNSTILE_SITE_KEY', env('TURNSTILE_SITE_KEY', '0x4AAAAAACGm-YQj1qFJ3dp1N'));
define('TURNSTILE_SECRET_KEY', env('TURNSTILE_SECRET_KEY', '0x4AAAAAACGm-diUL1gOzD-I_3QwtsSqzw4'));

// Load database config
require_once __DIR__ . '/database.php';

// ==================== SECURITY SYSTEM ====================
// Load security middleware (rate limiting, WAF, bot detection)
require_once BASE_PATH . '/includes/security_middleware.php';
// =========================================================

// Load helper classes
require_once BASE_PATH . '/includes/Snowflake.php';
require_once BASE_PATH . '/includes/EmailSender.php';
require_once BASE_PATH . '/includes/TurnstileVerifier.php';
require_once BASE_PATH . '/includes/ImageUploader.php';
require_once BASE_PATH . '/includes/HeaderComponent.php';
require_once BASE_PATH . '/includes/FooterComponent.php';
require_once BASE_PATH . '/includes/favicon_helper.php';
require_once BASE_PATH . '/includes/helpers.php';

// Load authentication service classes
require_once BASE_PATH . '/includes/GoogleFirebaseTokenValidator.php';
require_once BASE_PATH . '/includes/GoogleRecaptchaVerifier.php';
require_once BASE_PATH . '/includes/AuthenticationLogger.php';
require_once BASE_PATH . '/includes/PasswordResetService.php';

// Check maintenance mode (must be after helpers.php and database connection)
require_once BASE_PATH . '/includes/maintenance_check.php';

// Show maintenance countdown banner for users (5-minute warning)
require_once BASE_PATH . '/includes/maintenance_countdown.php';
?>