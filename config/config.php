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

// Load SessionManager trước khi khởi tạo session
require_once __DIR__ . '/../includes/SessionManager.php';

// Khởi tạo session an toàn với HTTPOnly và Secure cookies
SessionManager::initialize();

// Cấu hình đường dẫn
define('BASE_PATH', dirname(__DIR__));

// Auto-detect protocol (HTTP/HTTPS) for BASE_URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    ($_SERVER['SERVER_PORT'] ?? 80) == 443 ||
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    ? 'https://' : 'http://';

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$basePath = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false)
    ? '/kaishop'
    : '';

// Get from .env or construct from current request
$envUrl = env('APP_URL', null);
if ($envUrl) {
    // If APP_URL is set in .env, use it but ensure protocol matches current request
    $parsedUrl = parse_url($envUrl);
    $envPath = $parsedUrl['path'] ?? '';
    define('BASE_URL', $protocol . $host . $envPath);
} else {
    // Auto-construct from current request
    define('BASE_URL', $protocol . $host . $basePath);
}

define('SITE_NAME', env('APP_NAME', 'Kai Shop'));

// Cấu hình email (chỉ đọc từ .env)
define('SMTP_HOST', env('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', env('SMTP_PORT', 587));
define('SMTP_USER', env('SMTP_USER', ''));
define('SMTP_PASS', env('SMTP_PASS', ''));
define('EMAIL_FROM', env('EMAIL_FROM', ''));
define('EMAIL_FROM_NAME', env('EMAIL_FROM_NAME', 'KaiShop'));
define('EMAIL_RECIPIENT', env('EMAIL_RECIPIENT', ''));
define('CONTACT_EMAIL', env('CONTACT_EMAIL', ''));

// Cấu hình liên hệ
define('CONTACT_ZALO', env('CONTACT_ZALO', 'https://zalo.me/0812420710'));
define('CONTACT_TELEGRAM', env('CONTACT_TELEGRAM', 'https://t.me/kaishop25'));
define('CONTACT_PHONE', env('CONTACT_PHONE', '0812420710'));
define('CONTACT_FACEBOOK', env('CONTACT_FACEBOOK', ''));

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

// Cấu hình Cloudflare Turnstile (chỉ đọc từ .env)
define('TURNSTILE_SITE_KEY', env('TURNSTILE_SITE_KEY', ''));
define('TURNSTILE_SECRET_KEY', env('TURNSTILE_SECRET_KEY', ''));

// Cấu hình Google Translate
define('GOOGLE_TRANSLATE_ENABLED', env('GOOGLE_TRANSLATE_ENABLED', true));
define('GOOGLE_TRANSLATE_NOTRANSLATE', env('GOOGLE_TRANSLATE_NOTRANSLATE', true)); // Disable auto-translate to prevent ERR_BLOCKED_BY_CLIENT
define('GOOGLE_TRANSLATE_CUSTOMIZATION', env('GOOGLE_TRANSLATE_CUSTOMIZATION', '')); // Disable analytics/logging

// Cấu hình GTranslate Widget
define('GTRANSLATE_ENABLED', env('GTRANSLATE_ENABLED', true));
define('GTRANSLATE_DEFAULT_LANGUAGE', env('GTRANSLATE_DEFAULT_LANGUAGE', 'vi'));
define('GTRANSLATE_DETECT_BROWSER', env('GTRANSLATE_DETECT_BROWSER', true));
define('GTRANSLATE_LANGUAGES', env('GTRANSLATE_LANGUAGES', 'vi,en,ru,th,km,lo,id,fr,de,ja,pt,ko')); // Comma-separated list
define('GTRANSLATE_WRAPPER_SELECTOR', env('GTRANSLATE_WRAPPER_SELECTOR', '.gtranslate_wrapper'));
define('GTRANSLATE_CDN_URL', env('GTRANSLATE_CDN_URL', 'https://cdn.gtranslate.net/widgets/latest/float.js'));

// Cấu hình Firebase (chỉ đọc từ .env)
define('FIREBASE_API_KEY', env('FIREBASE_API_KEY', ''));
define('FIREBASE_AUTH_DOMAIN', env('FIREBASE_AUTH_DOMAIN', ''));
define('FIREBASE_DATABASE_URL', env('FIREBASE_DATABASE_URL', ''));
define('FIREBASE_PROJECT_ID', env('FIREBASE_PROJECT_ID', ''));
define('FIREBASE_STORAGE_BUCKET', env('FIREBASE_STORAGE_BUCKET', ''));
define('FIREBASE_MESSAGING_SENDER_ID', env('FIREBASE_MESSAGING_SENDER_ID', ''));
define('FIREBASE_APP_ID', env('FIREBASE_APP_ID', ''));
define('FIREBASE_MEASUREMENT_ID', env('FIREBASE_MEASUREMENT_ID', ''));

// Database configuration (chỉ đọc từ .env)
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', ''));
define('DB_USER', env('DB_USER', ''));
define('DB_PASS', env('DB_PASS', ''));

define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

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
require_once BASE_PATH . '/includes/AuthenticationLogger.php';
require_once BASE_PATH . '/includes/PasswordResetService.php';

// Load security classes
require_once BASE_PATH . '/includes/LoginRateLimiter.php';
require_once BASE_PATH . '/includes/CSRFProtection.php';

// Check maintenance mode (must be after helpers.php and database connection)
require_once BASE_PATH . '/includes/maintenance_check.php';

// Show maintenance countdown banner for users (5-minute warning)
require_once BASE_PATH . '/includes/maintenance_countdown.php';
