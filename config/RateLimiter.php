<?php
/**
 * Rate Limiter Class
 * Prevents abuse by limiting requests per IP
 */

require_once __DIR__ . '/SecurityLogger.php';

class RateLimiter
{
    private $pdo;
    private $enabled;
    private $logger;

    public function __construct($pdo, $logger = null)
    {
        $this->pdo = $pdo;
        $this->enabled = defined('RATE_LIMIT_ENABLED') ? RATE_LIMIT_ENABLED : true;
        $this->logger = $logger;
    }

    /**
     * Check if IP is blocked
     */
    public function isBlocked($ip = null)
    {
        if (!$ip)
            $ip = SecurityLogger::getRealIP();

        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM ip_blocklist 
                WHERE ip = ? AND (
                    is_permanent = 1 OR 
                    expires_at IS NULL OR 
                    expires_at > NOW()
                )
            ");
            $stmt->execute([$ip]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Block an IP
     */
    public function blockIP($ip, $reason = 'Rate limit exceeded', $duration = null, $is_permanent = false)
    {
        if (!$duration && defined('BAN_DURATION')) {
            $duration = BAN_DURATION;
        }

        $expires_at = null;
        if (!$is_permanent && $duration) {
            $expires_at = date('Y-m-d H:i:s', time() + $duration);
        }

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO ip_blocklist (ip, reason, expires_at, is_permanent, violation_count)
                VALUES (?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE 
                    reason = VALUES(reason),
                    expires_at = VALUES(expires_at),
                    is_permanent = VALUES(is_permanent),
                    violation_count = violation_count + 1,
                    blocked_at = NOW()
            ");

            $result = $stmt->execute([$ip, $reason, $expires_at, $is_permanent ? 1 : 0]);

            if ($this->logger) {
                $this->logger->logBlocked('ip_banned', "IP banned: $reason");
            }

            return $result;
        } catch (Exception $e) {
            error_log("RateLimiter blockIP Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unblock an IP
     */
    public function unblockIP($ip)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM ip_blocklist WHERE ip = ?");
            return $stmt->execute([$ip]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check rate limit
     */
    public function checkLimit($endpoint = 'global', $ip = null)
    {
        if (!$this->enabled)
            return true;

        if (!$ip)
            $ip = SecurityLogger::getRealIP();

        // Check if IP is whitelisted
        global $SECURITY_WHITELIST;
        if (isset($SECURITY_WHITELIST) && in_array($ip, $SECURITY_WHITELIST)) {
            return true;
        }

        // Check if already blocked
        if ($this->isBlocked($ip)) {
            if ($this->logger) {
                $this->logger->logBlocked('blocked_ip_retry', 'Blocked IP attempted access');
            }
            return false;
        }

        $max_requests = defined('RATE_LIMIT_REQUESTS') ? RATE_LIMIT_REQUESTS : 100;
        $window = defined('RATE_LIMIT_WINDOW') ? RATE_LIMIT_WINDOW : 60;

        try {
            // Get current rate limit data
            $stmt = $this->pdo->prepare("
                SELECT * FROM rate_limits 
                WHERE ip = ? AND endpoint = ?
            ");
            $stmt->execute([$ip, $endpoint]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            $now = time();

            if (!$data) {
                // First request - insert
                $stmt = $this->pdo->prepare("
                    INSERT INTO rate_limits (ip, endpoint, request_count, first_request, last_request)
                    VALUES (?, ?, 1, NOW(), NOW())
                ");
                $stmt->execute([$ip, $endpoint]);
                return true;
            }

            $first_request_time = strtotime($data['first_request']);
            $elapsed = $now - $first_request_time;

            // Reset window if expired
            if ($elapsed > $window) {
                $stmt = $this->pdo->prepare("
                    UPDATE rate_limits 
                    SET request_count = 1, first_request = NOW(), last_request = NOW()
                    WHERE ip = ? AND endpoint = ?
                ");
                $stmt->execute([$ip, $endpoint]);
                return true;
            }

            // Increment counter
            $new_count = $data['request_count'] + 1;

            if ($new_count > $max_requests) {
                // Rate limit exceeded
                $violations = $data['violations'] + 1;

                $stmt = $this->pdo->prepare("
                    UPDATE rate_limits 
                    SET violations = ?, last_request = NOW()
                    WHERE ip = ? AND endpoint = ?
                ");
                $stmt->execute([$violations, $ip, $endpoint]);

                // Auto-ban if threshold exceeded
                if (defined('AUTO_BAN_ENABLED') && AUTO_BAN_ENABLED) {
                    $threshold = defined('AUTO_BAN_THRESHOLD') ? AUTO_BAN_THRESHOLD : 3;

                    if ($violations >= $threshold) {
                        $permanent = defined('PERMANENT_BAN_THRESHOLD') && $violations >= PERMANENT_BAN_THRESHOLD;
                        $this->blockIP($ip, "Auto-banned after $violations violations", null, $permanent);
                    }
                }

                if ($this->logger) {
                    $this->logger->logSuspicious('rate_limit_exceeded', [
                        'endpoint' => $endpoint,
                        'count' => $new_count,
                        'violations' => $violations
                    ]);
                }

                return false;
            }

            // Update counter
            $stmt = $this->pdo->prepare("
                UPDATE rate_limits 
                SET request_count = ?, last_request = NOW()
                WHERE ip = ? AND endpoint = ?
            ");
            $stmt->execute([$new_count, $ip, $endpoint]);

            return true;

        } catch (Exception $e) {
            error_log("RateLimiter checkLimit Error: " . $e->getMessage());
            return true; // Fail open
        }
    }

    /**
     * Get blocked IPs for admin
     */
    public function getBlockedIPs($limit = 100)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM ip_blocklist 
                ORDER BY blocked_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    // ============================================
    // FINGERPRINT BLOCKING METHODS
    // ============================================

    /**
     * Check if fingerprint is blocked
     */
    public function isFingerprintBlocked($fingerprint = null)
    {
        if (!$fingerprint)
            $fingerprint = SecurityLogger::getFingerprint();

        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM fingerprint_blocklist 
                WHERE fingerprint = ? AND (
                    is_permanent = 1 OR 
                    expires_at IS NULL OR 
                    expires_at > NOW()
                )
            ");
            $stmt->execute([$fingerprint]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Block a fingerprint
     */
    public function blockFingerprint($fingerprint, $reason = 'Manual block', $duration = null, $is_permanent = false, $lastSeenIP = null)
    {
        if (!$duration && defined('BAN_DURATION')) {
            $duration = BAN_DURATION;
        }

        $expires_at = null;
        if (!$is_permanent && $duration) {
            $expires_at = date('Y-m-d H:i:s', time() + $duration);
        }

        if (!$lastSeenIP) {
            $lastSeenIP = SecurityLogger::getRealIP();
        }

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO fingerprint_blocklist (fingerprint, reason, expires_at, is_permanent, violation_count, last_seen_ip)
                VALUES (?, ?, ?, ?, 1, ?)
                ON DUPLICATE KEY UPDATE 
                    reason = VALUES(reason),
                    expires_at = VALUES(expires_at),
                    is_permanent = VALUES(is_permanent),
                    violation_count = violation_count + 1,
                    last_seen_ip = VALUES(last_seen_ip),
                    blocked_at = NOW()
            ");

            $result = $stmt->execute([$fingerprint, $reason, $expires_at, $is_permanent ? 1 : 0, $lastSeenIP]);

            if ($this->logger) {
                $this->logger->logBlocked('fingerprint_banned', "Fingerprint banned: $reason");
            }

            return $result;
        } catch (Exception $e) {
            error_log("RateLimiter blockFingerprint Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unblock a fingerprint
     */
    public function unblockFingerprint($fingerprint)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM fingerprint_blocklist WHERE fingerprint = ?");
            return $stmt->execute([$fingerprint]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get blocked fingerprints for admin
     */
    public function getBlockedFingerprints($limit = 100)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM fingerprint_blocklist 
                ORDER BY blocked_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Check if current request is blocked (IP or Fingerprint)
     */
    public function isRequestBlocked()
    {
        $ip = SecurityLogger::getRealIP();
        $fingerprint = SecurityLogger::getFingerprint();

        // Check IP block
        if ($this->isBlocked($ip)) {
            return ['type' => 'ip', 'value' => $ip];
        }

        // Check fingerprint block
        if ($this->isFingerprintBlocked($fingerprint)) {
            return ['type' => 'fingerprint', 'value' => $fingerprint];
        }

        return false;
    }

    /**
     * Cleanup expired blocks (IP + Fingerprint)
     */
    public function cleanup()
    {
        try {
            // Cleanup IP blocks
            $stmt = $this->pdo->prepare("
                DELETE FROM ip_blocklist 
                WHERE is_permanent = 0 AND expires_at < NOW()
            ");
            $stmt->execute();

            // Cleanup fingerprint blocks
            $stmt = $this->pdo->prepare("
                DELETE FROM fingerprint_blocklist 
                WHERE is_permanent = 0 AND expires_at < NOW()
            ");
            $stmt->execute();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

