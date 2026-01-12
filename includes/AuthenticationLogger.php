<?php
/**
 * Authentication Logger
 * Handles comprehensive logging of authentication events
 * 
 * @package KaiShop
 * @subpackage Authentication
 * @version 1.0.0
 */

class AuthenticationLogger {
    /**
     * Database connection
     */
    private $pdo;
    
    /**
     * Constructor
     * 
     * @param PDO $pdo Database connection
     */
    public function __construct($pdo = null) {
        if ($pdo === null) {
            global $pdo;
        }
        $this->pdo = $pdo;
    }
    
    /**
     * Log successful login
     * 
     * @param int $userId User ID
     * @param string $username Username
     * @param string $ipAddress IP address
     * @param string|null $userAgent User agent string
     * @param string $authMethod Authentication method (email, google_oauth, etc.)
     */
    public function logSuccessfulLogin($userId, $username, $ipAddress, $userAgent = null, $authMethod = 'email') {
        $this->writeAuthenticationLog([
            'event_type' => 'LOGIN_SUCCESS',
            'user_id' => $userId,
            'username' => $username,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent ?? $this->getUserAgent(),
            'auth_method' => $authMethod,
            'metadata' => json_encode([
                'timestamp_utc' => gmdate('Y-m-d H:i:s'),
                'server_time' => date('Y-m-d H:i:s')
            ])
        ]);
    }
    
    /**
     * Log failed login attempt
     * 
     * @param string $identifier Username or email used
     * @param string $ipAddress IP address
     * @param string $failureReason Reason for failure
     * @param string|null $userAgent User agent string
     */
    public function logFailedLogin($identifier, $ipAddress, $failureReason, $userAgent = null) {
        $this->writeAuthenticationLog([
            'event_type' => 'LOGIN_FAILED',
            'user_id' => null,
            'username' => $identifier,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent ?? $this->getUserAgent(),
            'auth_method' => 'unknown',
            'metadata' => json_encode([
                'failure_reason' => $failureReason,
                'timestamp_utc' => gmdate('Y-m-d H:i:s')
            ])
        ]);
    }
    
    /**
     * Log user registration
     * 
     * @param int $userId New user ID
     * @param string $username New username
     * @param string $email New email
     * @param string $ipAddress IP address
     * @param string $registrationMethod Registration method
     */
    public function logUserRegistration($userId, $username, $email, $ipAddress, $registrationMethod = 'email') {
        $this->writeAuthenticationLog([
            'event_type' => 'REGISTRATION_SUCCESS',
            'user_id' => $userId,
            'username' => $username,
            'ip_address' => $ipAddress,
            'user_agent' => $this->getUserAgent(),
            'auth_method' => $registrationMethod,
            'metadata' => json_encode([
                'email' => $email,
                'timestamp_utc' => gmdate('Y-m-d H:i:s')
            ])
        ]);
    }
    
    /**
     * Log password reset request
     * 
     * @param string $email Email address
     * @param string $ipAddress IP address
     * @param bool $success Whether token was generated
     */
    public function logPasswordResetRequest($email, $ipAddress, $success = true) {
        $this->writeAuthenticationLog([
            'event_type' => $success ? 'PASSWORD_RESET_REQUESTED' : 'PASSWORD_RESET_FAILED',
            'user_id' => null,
            'username' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $this->getUserAgent(),
            'auth_method' => 'password_reset',
            'metadata' => json_encode([
                'success' => $success,
                'timestamp_utc' => gmdate('Y-m-d H:i:s')
            ])
        ]);
    }
    
    /**
     * Log password change
     * 
     * @param int $userId User ID
     * @param string $ipAddress IP address
     * @param string $changeMethod Method used (reset_token, profile_update, etc.)
     */
    public function logPasswordChange($userId, $ipAddress, $changeMethod = 'reset_token') {
        $this->writeAuthenticationLog([
            'event_type' => 'PASSWORD_CHANGED',
            'user_id' => $userId,
            'username' => null,
            'ip_address' => $ipAddress,
            'user_agent' => $this->getUserAgent(),
            'auth_method' => $changeMethod,
            'metadata' => json_encode([
                'timestamp_utc' => gmdate('Y-m-d H:i:s')
            ])
        ]);
    }
    
    /**
     * Write authentication log to database
     * 
     * @param array $logData Log data array
     */
    private function writeAuthenticationLog($logData) {
        try {
            $logId = Snowflake::generateId();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO system_logs (
                    id,
                    log_type,
                    user_id,
                    action,
                    description,
                    ip_address,
                    user_agent,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $description = $this->buildLogDescription($logData);
            
            $stmt->execute([
                $logId,
                'user_login',
                $logData['user_id'],
                $logData['event_type'],
                $description,
                $logData['ip_address'],
                $logData['user_agent']
            ]);
            
            // Also log to error_log for debugging
            error_log(sprintf(
                '[AuthenticationLogger] %s | User: %s | IP: %s | Method: %s',
                $logData['event_type'],
                $logData['username'] ?? $logData['user_id'] ?? 'unknown',
                $logData['ip_address'],
                $logData['auth_method']
            ));
            
        } catch (Exception $e) {
            // If database logging fails, log to error_log
            error_log(sprintf(
                '[AuthenticationLogger] Failed to write log: %s | Data: %s',
                $e->getMessage(),
                json_encode($logData)
            ));
        }
    }
    
    /**
     * Build log description from log data
     * 
     * @param array $logData Log data
     * @return string Description
     */
    private function buildLogDescription($logData) {
        $descriptions = [
            'LOGIN_SUCCESS' => sprintf(
                'User %s logged in successfully via %s',
                $logData['username'],
                $logData['auth_method']
            ),
            'LOGIN_FAILED' => sprintf(
                'Failed login attempt for %s',
                $logData['username']
            ),
            'REGISTRATION_SUCCESS' => sprintf(
                'New user registered: %s via %s',
                $logData['username'],
                $logData['auth_method']
            ),
            'PASSWORD_RESET_REQUESTED' => sprintf(
                'Password reset requested for %s',
                $logData['username']
            ),
            'PASSWORD_RESET_FAILED' => sprintf(
                'Password reset failed for %s',
                $logData['username']
            ),
            'PASSWORD_CHANGED' => sprintf(
                'Password changed via %s',
                $logData['auth_method']
            )
        ];
        
        $description = $descriptions[$logData['event_type']] ?? 'Unknown authentication event';
        
        // Add metadata if available
        if (!empty($logData['metadata'])) {
            $metadata = json_decode($logData['metadata'], true);
            if ($metadata && isset($metadata['failure_reason'])) {
                $description .= ' - Reason: ' . $metadata['failure_reason'];
            }
        }
        
        return $description;
    }
    
    /**
     * Get user agent from request
     * 
     * @return string User agent string
     */
    private function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
    
    /**
     * Get client IP address
     * 
     * @return string IP address
     */
    public static function getClientIpAddress() {
        // Check for proxy headers
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ipList[0]);
        }
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
}
