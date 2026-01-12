<?php
/**
 * Security Logger Class
 * Logs all security-related events and suspicious activities
 */

class SecurityLogger
{
    private $pdo;
    private $enabled;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->enabled = defined('SECURITY_LOG_ENABLED') ? SECURITY_LOG_ENABLED : true;
    }

    /**
     * Get real IP address (works with Cloudflare)
     */
    public static function getRealIP()
    {
        // Cloudflare passes real IP in CF-Connecting-IP header
        if (defined('CLOUDFLARE_ENABLED') && CLOUDFLARE_ENABLED && isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }

        // Check other proxy headers
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (isset($_SERVER[$header]) && filter_var($_SERVER[$header], FILTER_VALIDATE_IP)) {
                return $_SERVER[$header];
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Generate enhanced browser fingerprint
     * Includes multiple data points for stronger tracking
     */
    public static function getFingerprint()
    {
        $data = [
            // Basic headers
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
            $_SERVER['HTTP_ACCEPT'] ?? '',

            // Additional browser data from headers
            $_SERVER['HTTP_DNT'] ?? '', // Do Not Track
            $_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS'] ?? '',

            // Screen/device hints (if available via Client Hints)
            $_SERVER['HTTP_SEC_CH_UA'] ?? '',
            $_SERVER['HTTP_SEC_CH_UA_MOBILE'] ?? '',
            $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] ?? '',

            // Connection type
            $_SERVER['HTTP_CONNECTION'] ?? '',
        ];

        // Add timezone offset if available in cookie
        if (isset($_COOKIE['tz_offset'])) {
            $data[] = $_COOKIE['tz_offset'];
        }

        // Add screen resolution if available in cookie
        if (isset($_COOKIE['screen_res'])) {
            $data[] = $_COOKIE['screen_res'];
        }

        return hash('sha256', implode('|', $data));
    }

    /**
     * Get ISP information (placeholder - requires external API)
     */
    public static function getISP()
    {
        // This would require an external API like ip-api.com or ipinfo.io
        // For now, return null - can be enhanced later
        return null;
    }

    /**
     * Get city and region (placeholder - requires external API)
     */
    public static function getCityRegion()
    {
        // This would require an external API
        // For now, return null - can be enhanced later
        return ['city' => null, 'region' => null];
    }

    /**
     * Get country code from Cloudflare header
     */
    public static function getCountryCode()
    {
        return $_SERVER['HTTP_CF_IPCOUNTRY'] ?? null;
    }

    /**
     * Log security event
     */
    public function log($threat_level = 'low', $attack_type = null, $details = [])
    {
        if (!$this->enabled)
            return false;

        // Don't log everything if LOG_ALL_REQUESTS is false
        if (!LOG_ALL_REQUESTS && $threat_level === 'low' && !$attack_type) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO security_logs 
                (ip, user_agent, request_uri, request_method, user_id, fingerprint, 
                 is_blocked, threat_level, attack_type, country_code, isp, city, region)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $location = self::getCityRegion();

            return $stmt->execute([
                self::getRealIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                substr($_SERVER['REQUEST_URI'] ?? '', 0, 500),
                $_SERVER['REQUEST_METHOD'] ?? 'GET',
                $user_id,
                self::getFingerprint(),
                isset($details['blocked']) ? 1 : 0,
                $threat_level,
                $attack_type,
                self::getCountryCode(),
                self::getISP(),
                $location['city'],
                $location['region']
            ]);
        } catch (Exception $e) {
            error_log("SecurityLogger Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Quick log methods
     */
    public function logSuspicious($attack_type, $details = [])
    {
        return $this->log('medium', $attack_type, $details);
    }

    public function logBlocked($attack_type, $reason = '')
    {
        return $this->log('high', $attack_type, ['blocked' => true, 'reason' => $reason]);
    }

    public function logCritical($attack_type, $details = [])
    {
        return $this->log('critical', $attack_type, $details);
    }

    /**
     * Cleanup old logs
     */
    public function cleanup()
    {
        if (!defined('LOG_RETENTION_DAYS'))
            return;

        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM security_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            return $stmt->execute([LOG_RETENTION_DAYS]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get recent logs for admin
     */
    public function getRecentLogs($limit = 100, $filters = [])
    {
        try {
            $where = [];
            $params = [];

            if (isset($filters['ip'])) {
                $where[] = "ip = ?";
                $params[] = $filters['ip'];
            }

            if (isset($filters['threat_level'])) {
                $where[] = "threat_level = ?";
                $params[] = $filters['threat_level'];
            }

            if (isset($filters['attack_type'])) {
                $where[] = "attack_type = ?";
                $params[] = $filters['attack_type'];
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $stmt = $this->pdo->prepare("
                SELECT * FROM security_logs 
                $whereClause
                ORDER BY created_at DESC 
                LIMIT ?
            ");

            $params[] = $limit;
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get statistics
     */
    public function getStats($days = 7)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_events,
                    COUNT(DISTINCT ip) as unique_ips,
                    SUM(CASE WHEN is_blocked = 1 THEN 1 ELSE 0 END) as blocked_requests,
                    SUM(CASE WHEN threat_level = 'high' OR threat_level = 'critical' THEN 1 ELSE 0 END) as serious_threats
                FROM security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");

            $stmt->execute([$days]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
