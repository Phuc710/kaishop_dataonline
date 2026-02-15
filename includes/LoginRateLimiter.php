<?php
/**
 * Login Rate Limiter - Chống brute-force attacks
 * Progressive delays và account lockout
 */

class LoginRateLimiter
{
    private $pdo;

    // Ngưỡng và thời gian delay
    private const DELAY_THRESHOLD_1 = 3;  // 3 lần sai → delay 5s
    private const DELAY_THRESHOLD_2 = 5;  // 5 lần sai → delay 15s
    private const LOCKOUT_THRESHOLD = 10; // 10 lần sai → khóa 30 phút

    private const DELAY_TIME_1 = 5;       // 5 giây
    private const DELAY_TIME_2 = 15;      // 15 giây
    private const LOCKOUT_TIME = 1800;    // 30 phút (1800 giây)

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Kiểm tra và áp dụng rate limit trước khi login
     * @return array ['allowed' => bool, 'message' => string, 'delay' => int]
     */
    public function checkLimit(string $username): array
    {
        $ip = $this->getRealIP();
        $fingerprint = $this->getFingerprint();

        // Lấy thông tin attempt hiện tại
        $attempt = $this->getAttempt($username, $ip);

        if (!$attempt) {
            // Chưa có attempt nào - cho phép
            return ['allowed' => true, 'message' => '', 'delay' => 0];
        }

        // Kiểm tra lockout
        if ($attempt['locked_until'] && strtotime($attempt['locked_until']) > time()) {
            $remainingTime = strtotime($attempt['locked_until']) - time();
            $minutes = ceil($remainingTime / 60);

            return [
                'allowed' => false,
                'message' => "Tài khoản tạm thời bị khóa. Vui lòng thử lại sau {$minutes} phút.",
                'delay' => 0,
                'locked_until' => $attempt['locked_until']
            ];
        }

        // Kiểm tra số lần thử
        $count = $attempt['attempt_count'];

        if ($count >= self::LOCKOUT_THRESHOLD) {
            // Khóa tài khoản
            $this->lockAccount($username, $ip);

            return [
                'allowed' => false,
                'message' => 'Quá nhiều lần đăng nhập thất bại. Tài khoản đã bị khóa 30 phút.',
                'delay' => 0
            ];
        } elseif ($count >= self::DELAY_THRESHOLD_2) {
            // Delay 15 giây
            return [
                'allowed' => true,
                'message' => 'Vui lòng đợi 15 giây trước khi thử lại.',
                'delay' => self::DELAY_TIME_2
            ];
        } elseif ($count >= self::DELAY_THRESHOLD_1) {
            // Delay 5 giây
            return [
                'allowed' => true,
                'message' => 'Vui lòng đợi 5 giây trước khi thử lại.',
                'delay' => self::DELAY_TIME_1
            ];
        }

        return ['allowed' => true, 'message' => '', 'delay' => 0];
    }

    /**
     * Ghi nhận failed login attempt
     */
    public function recordFailedAttempt(string $username)
    {
        $ip = $this->getRealIP();
        $fingerprint = $this->getFingerprint();

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO login_attempts (username, ip_address, fingerprint, attempt_count, last_attempt)
                VALUES (?, ?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE
                    attempt_count = attempt_count + 1,
                    last_attempt = NOW(),
                    fingerprint = VALUES(fingerprint)
            ");

            $stmt->execute([$username, $ip, $fingerprint]);

            // Log vào security_logs
            $this->logSecurityEvent($username, $ip, 'failed_login');

        } catch (Exception $e) {
            error_log('[LoginRateLimiter] Error recording failed attempt: ' . $e->getMessage());
        }
    }

    /**
     * Reset attempts sau khi login thành công
     */
    public function resetAttempts(string $username)
    {
        $ip = $this->getRealIP();

        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM login_attempts 
                WHERE username = ? AND ip_address = ?
            ");
            $stmt->execute([$username, $ip]);

            // Log successful login
            $this->logSecurityEvent($username, $ip, 'successful_login');

        } catch (Exception $e) {
            error_log('[LoginRateLimiter] Error resetting attempts: ' . $e->getMessage());
        }
    }

    /**
     * Khóa tài khoản
     */
    private function lockAccount(string $username, string $ip)
    {
        $lockedUntil = date('Y-m-d H:i:s', time() + self::LOCKOUT_TIME);

        try {
            $stmt = $this->pdo->prepare("
                UPDATE login_attempts 
                SET locked_until = ?
                WHERE username = ? AND ip_address = ?
            ");
            $stmt->execute([$lockedUntil, $username, $ip]);

            // Log lockout event
            $this->logSecurityEvent($username, $ip, 'account_locked');

        } catch (Exception $e) {
            error_log('[LoginRateLimiter] Error locking account: ' . $e->getMessage());
        }
    }

    /**
     * Lấy thông tin attempt hiện tại
     */
    private function getAttempt(string $username, string $ip): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM login_attempts 
                WHERE username = ? AND ip_address = ?
            ");
            $stmt->execute([$username, $ip]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;

        } catch (Exception $e) {
            error_log('[LoginRateLimiter] Error getting attempt: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Lấy IP thực của user
     */
    private function getRealIP(): string
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
     * Tạo browser fingerprint
     */
    private function getFingerprint(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';

        return hash('sha256', $userAgent . $acceptLanguage . $acceptEncoding);
    }

    /**
     * Log security event
     */
    private function logSecurityEvent(string $username, string $ip, string $eventType)
    {
        try {
            // Kiểm tra xem bảng security_logs có tồn tại không
            $stmt = $this->pdo->prepare("
                INSERT INTO security_logs (ip, user_agent, request_uri, request_method, attack_type, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'POST';

            $stmt->execute([
                $ip,
                $userAgent,
                $requestUri,
                $requestMethod,
                $eventType
            ]);

        } catch (Exception $e) {
            // Nếu bảng không tồn tại hoặc lỗi, chỉ log vào error_log
            error_log("[LoginRateLimiter] Security event: {$eventType} - Username: {$username}, IP: {$ip}");
        }
    }

    /**
     * Cleanup old attempts (gọi định kỳ)
     */
    public function cleanup()
    {
        try {
            // Xóa các attempts cũ hơn 24 giờ và không bị lock
            $stmt = $this->pdo->prepare("
                DELETE FROM login_attempts 
                WHERE last_attempt < DATE_SUB(NOW(), INTERVAL 24 HOUR)
                AND (locked_until IS NULL OR locked_until < NOW())
            ");
            $stmt->execute();

        } catch (Exception $e) {
            error_log('[LoginRateLimiter] Error cleaning up: ' . $e->getMessage());
        }
    }
}
