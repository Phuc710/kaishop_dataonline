<?php
/**
 * CSRF Protection - Cross-Site Request Forgery Protection
 * Generate và validate CSRF tokens cho forms
 */

class CSRFProtection
{
    // Token lifetime: 1 giờ
    private const TOKEN_LIFETIME = 3600;

    /**
     * Generate CSRF token mới
     */
    public static function generateToken(): string
    {
        // Khởi tạo session nếu chưa có
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Generate random token
        $token = bin2hex(random_bytes(32));

        // Lưu vào session với timestamp
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();

        return $token;
    }

    /**
     * Lấy token hiện tại hoặc tạo mới nếu chưa có/hết hạn
     */
    public static function getToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Kiểm tra token có tồn tại và còn hạn không
        if (
            isset($_SESSION['csrf_token']) &&
            isset($_SESSION['csrf_token_time']) &&
            (time() - $_SESSION['csrf_token_time']) < self::TOKEN_LIFETIME
        ) {
            return $_SESSION['csrf_token'];
        }

        // Token không tồn tại hoặc hết hạn - tạo mới
        return self::generateToken();
    }

    /**
     * Validate CSRF token
     */
    public static function validateToken(?string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Kiểm tra token có được gửi lên không
        if (empty($token)) {
            error_log('[CSRF] Token not provided');
            return false;
        }

        // Kiểm tra token có trong session không
        if (!isset($_SESSION['csrf_token'])) {
            error_log('[CSRF] No token in session');
            return false;
        }

        // Kiểm tra token có hết hạn không
        if (!isset($_SESSION['csrf_token_time']) || (time() - $_SESSION['csrf_token_time']) > self::TOKEN_LIFETIME) {
            error_log('[CSRF] Token expired');
            return false;
        }

        // So sánh token (timing-safe comparison)
        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            error_log('[CSRF] Token mismatch');
            return false;
        }

        return true;
    }

    /**
     * Validate token từ request (POST/GET)
     */
    public static function validateRequest(): bool
    {
        // Lấy token từ POST hoặc GET
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;

        return self::validateToken($token);
    }

    /**
     * Generate hidden input field với CSRF token
     */
    public static function field(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Regenerate token (sau khi submit thành công)
     */
    public static function regenerateToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Xóa token cũ
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);

        // Tạo token mới
        return self::generateToken();
    }

    /**
     * Middleware để check CSRF cho POST/PUT/DELETE requests
     */
    public static function middleware()
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Chỉ check cho POST, PUT, DELETE
        if (!in_array($method, ['POST', 'PUT', 'DELETE'])) {
            return;
        }

        // Whitelist một số endpoints không cần CSRF (API, webhooks)
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $whitelistPatterns = [
            '/api/sepay-webhook.php',
            '/auth/google-login.php',
            '/api/auth_turnstile.php', // API login có Turnstile protection
        ];

        foreach ($whitelistPatterns as $pattern) {
            if (strpos($requestUri, $pattern) !== false) {
                return; // Skip CSRF check
            }
        }

        // Validate CSRF token
        if (!self::validateRequest()) {
            http_response_code(403);
            header('Content-Type: application/json');
            die(json_encode([
                'success' => false,
                'error' => 'CSRF token validation failed',
                'message' => 'Invalid or expired security token. Please refresh the page and try again.'
            ]));
        }
    }

    /**
     * Get token cho AJAX requests
     */
    public static function getTokenForAjax(): array
    {
        return [
            'csrf_token' => self::getToken(),
            'csrf_token_time' => $_SESSION['csrf_token_time'] ?? time()
        ];
    }
}
