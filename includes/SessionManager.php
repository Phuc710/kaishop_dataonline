<?php
/**
 * Session Manager - Quản lý session an toàn
 * Cấu hình HTTPOnly, Secure cookies và session timeout
 */

class SessionManager
{
    // Session timeout: 24 giờ (86400 giây)
    private const SESSION_LIFETIME = 86400;

    // Session regeneration interval: 30 phút
    private const REGENERATE_INTERVAL = 1800;

    /**
     * Khởi tạo session với cấu hình an toàn
     */
    public static function initialize()
    {
        // Nếu session đã start thì return
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Cấu hình session cookie parameters
        $cookieParams = [
            'lifetime' => self::SESSION_LIFETIME,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? '',
            'secure' => self::isSecureConnection(), // Chỉ gửi qua HTTPS
            'httponly' => true, // Không thể truy cập từ JavaScript
            'samesite' => 'Lax' // CSRF protection
        ];

        session_set_cookie_params($cookieParams);

        // Đặt session name (tránh dùng PHPSESSID mặc định)
        session_name('KAISHOP_SESSION');

        // Start session
        session_start();

        // Kiểm tra session timeout
        self::checkTimeout();

        // Kiểm tra session hijacking
        self::validateFingerprint();

        // Regenerate session ID định kỳ
        self::regenerateIfNeeded();
    }

    /**
     * Kiểm tra kết nối có phải HTTPS không
     */
    private static function isSecureConnection(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            ($_SERVER['SERVER_PORT'] ?? 80) == 443 ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }

    /**
     * Kiểm tra session timeout
     */
    private static function checkTimeout()
    {
        if (isset($_SESSION['LAST_ACTIVITY'])) {
            $elapsed = time() - $_SESSION['LAST_ACTIVITY'];

            if ($elapsed > self::SESSION_LIFETIME) {
                // Session timeout - destroy và redirect
                self::destroy();
                return;
            }
        }

        // Update last activity time
        $_SESSION['LAST_ACTIVITY'] = time();
    }

    /**
     * Validate session fingerprint để phát hiện session hijacking
     */
    private static function validateFingerprint()
    {
        $currentFingerprint = self::generateFingerprint();

        if (!isset($_SESSION['FINGERPRINT'])) {
            // First time - set fingerprint
            $_SESSION['FINGERPRINT'] = $currentFingerprint;
        } else if ($_SESSION['FINGERPRINT'] !== $currentFingerprint) {
            // Fingerprint mismatch - possible session hijacking
            error_log('[Security] Session hijacking detected - Fingerprint mismatch');
            self::destroy();
            return;
        }
    }

    /**
     * Generate session fingerprint từ User-Agent và IP
     */
    private static function generateFingerprint(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = self::getRealIP();

        // Chỉ lấy 3 octets đầu của IP để tránh false positive khi IP thay đổi nhẹ
        $ipParts = explode('.', $ip);
        $ipPrefix = implode('.', array_slice($ipParts, 0, 3));

        return hash('sha256', $userAgent . $ipPrefix);
    }

    /**
     * Lấy IP thực của user
     */
    private static function getRealIP(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Regenerate session ID nếu cần (mỗi 30 phút)
     */
    private static function regenerateIfNeeded()
    {
        if (!isset($_SESSION['CREATED_AT'])) {
            $_SESSION['CREATED_AT'] = time();
        } else {
            $elapsed = time() - $_SESSION['CREATED_AT'];

            if ($elapsed > self::REGENERATE_INTERVAL) {
                self::regenerate();
            }
        }
    }

    /**
     * Regenerate session ID (gọi sau khi login thành công)
     */
    public static function regenerate()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION['CREATED_AT'] = time();
            $_SESSION['FINGERPRINT'] = self::generateFingerprint();
        }
    }

    /**
     * Destroy session hoàn toàn
     */
    public static function destroy()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Clear session data
            $_SESSION = [];

            // Delete session cookie
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            // Destroy session
            session_destroy();
        }
    }

    /**
     * Set session data sau khi login thành công
     */
    public static function setUserSession(array $userData)
    {
        // Regenerate session ID để tránh session fixation
        self::regenerate();

        // Set user data
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['username'] = $userData['username'];
        $_SESSION['email'] = $userData['email'];
        $_SESSION['role'] = $userData['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
    }

    /**
     * Kiểm tra user đã login chưa
     */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_id']);
    }

    /**
     * Lấy user ID hiện tại
     */
    public static function getUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Lấy user role hiện tại
     */
    public static function getUserRole(): ?string
    {
        return $_SESSION['role'] ?? null;
    }
}
