<?php
/**
 * Password Reset Service
 * Handles secure password reset functionality
 * 
 * @package KaiShop
 * @subpackage Authentication
 * @version 1.0.0
 */

class PasswordResetService
{
    /**
     * Database connection
     */
    private $pdo;

    /**
     * Authentication logger
     */
    private $logger;

    /**
     * Token expiry duration in hours
     */
    private const TOKEN_EXPIRY_HOURS = 1;

    /**
     * Token length in bytes (will be doubled in hex)
     */
    private const TOKEN_LENGTH_BYTES = 32;

    /**
     * Minimum password length
     */
    private const MIN_PASSWORD_LENGTH = 6;

    /**
     * Constructor
     * 
     * @param PDO $pdo Database connection
     */
    public function __construct($pdo = null)
    {
        if ($pdo === null) {
            global $pdo;
        }
        $this->pdo = $pdo;
        $this->logger = new AuthenticationLogger($this->pdo);
    }


    /**
     * Check rate limiting for password reset requests
     * Prevents spam by limiting requests per IP and email
     * 
     * @param string $email Email address
     * @param string $ipAddress Client IP address
     * @return array Result with 'allowed' boolean and 'message'
     */
    public function checkRateLimit($email, $ipAddress)
    {
        $maxAttempts = 3;
        $timeWindowMinutes = 15;

        try {
            // Check requests from this IP in the last time window
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as attempt_count
                FROM password_reset_logs
                WHERE ip_address = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ");
            $stmt->execute([$ipAddress, $timeWindowMinutes]);
            $ipAttempts = $stmt->fetchColumn();

            if ($ipAttempts >= $maxAttempts) {
                return [
                    'allowed' => false,
                    'message' => "Too many password reset requests from your IP. Please wait {$timeWindowMinutes} minutes before trying again."
                ];
            }

            // Check requests for this email in the last time window
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as attempt_count
                FROM password_reset_logs
                WHERE email = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ");
            $stmt->execute([$email, $timeWindowMinutes]);
            $emailAttempts = $stmt->fetchColumn();

            if ($emailAttempts >= $maxAttempts) {
                return [
                    'allowed' => false,
                    'message' => "Too many password reset requests for this email. Please wait {$timeWindowMinutes} minutes before trying again."
                ];
            }

            return [
                'allowed' => true,
                'message' => 'Rate limit check passed'
            ];

        } catch (PDOException $e) {
            error_log('[PasswordResetService] Rate limit check error: ' . $e->getMessage());
            // On error, allow the request to proceed (fail open)
            return [
                'allowed' => true,
                'message' => 'Rate limit check bypassed due to error'
            ];
        }
    }

    /**
     * Initiate password reset process
     * 
     * @param string $email Email address
     * @param string $ipAddress Client IP address
     * @return array Result array with 'success' and 'message'
     */
    public function initiatePasswordReset($email, $ipAddress)
    {
        // Validate email format
        if (!$this->isValidEmailFormat($email)) {
            return $this->createResponse(false, 'INVALID_EMAIL_FORMAT', 'Invalid email address format');
        }

        // Find user by email
        $user = $this->findUserByEmail($email);

        // Security: Always return success to prevent email enumeration
        // Even if email doesn't exist, we return success
        if (!$user) {
            $this->logger->logPasswordResetRequest($email, $ipAddress, false);
            // Log attempt for rate limiting
            $this->logResetAttempt($email, $ipAddress, false);
            return $this->createResponse(
                true,
                'REQUEST_PROCESSED',
                'If the email exists in our system, password reset instructions have been sent'
            );
        }

        // Check if user uses OAuth (no password)
        if ($this->isOAuthOnlyUser($user)) {
            // Log attempt for rate limiting
            $this->logResetAttempt($email, $ipAddress, false);
            return $this->createResponse(
                false,
                'OAUTH_ACCOUNT',
                'This account uses Google Sign-In. Please use Google to log in.'
            );
        }

        // Generate secure reset token
        $resetToken = $this->generateSecureToken();
        $tokenExpiry = $this->calculateTokenExpiry();

        // Store token in database
        $tokenStored = $this->storeResetToken($user['id'], $resetToken, $tokenExpiry);
        if (!$tokenStored) {
            error_log('[PasswordResetService] Failed to store reset token for user: ' . $user['id']);
            // Log attempt for rate limiting
            $this->logResetAttempt($email, $ipAddress, false);
            return $this->createResponse(false, 'TOKEN_STORAGE_FAILED', 'Unable to process request. Please try again.');
        }

        // Send reset email
        $emailSent = $this->sendResetEmail($email, $resetToken);
        if (!$emailSent) {
            error_log('[PasswordResetService] Failed to send reset email to: ' . $email);

            // DEBUG: Log the reset link for testing without email
            $resetLink = url("auth/reset-password?token=$resetToken");
            error_log('[PasswordResetService] DEBUG - Reset link for ' . $email . ': ' . $resetLink);

            // Log attempt for rate limiting
            $this->logResetAttempt($email, $ipAddress, false);
            return $this->createResponse(false, 'EMAIL_SEND_FAILED', 'Unable to send email. Please try again later.');
        }

        // Log successful reset request
        $this->logger->logPasswordResetRequest($email, $ipAddress, true);
        // Log attempt for rate limiting
        $this->logResetAttempt($email, $ipAddress, true);

        return $this->createResponse(
            true,
            'REQUEST_SENT',
            'Password reset instructions have been sent to your email. Please check your inbox.'
        );
    }

    /**
     * Log password reset attempt for rate limiting
     * 
     * @param string $email Email address
     * @param string $ipAddress IP address
     * @param bool $success Whether the attempt was successful
     * @return void
     */
    private function logResetAttempt($email, $ipAddress, $success)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO password_reset_logs (email, ip_address, success, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$email, $ipAddress, $success ? 1 : 0]);
        } catch (PDOException $e) {
            error_log('[PasswordResetService] Failed to log reset attempt: ' . $e->getMessage());
            // Don't throw error, just log it
        }
    }

    /**
     * Validate reset token and update password
     * 
     * @param string $token Reset token
     * @param string $newPassword New password
     * @param string $confirmPassword Password confirmation
     * @param string $ipAddress Client IP address
     * @return array Result array
     */
    public function resetPasswordWithToken($token, $newPassword, $confirmPassword, $ipAddress)
    {
        // Validate passwords match
        if ($newPassword !== $confirmPassword) {
            return $this->createResponse(false, 'PASSWORDS_MISMATCH', 'Passwords do not match');
        }

        // Validate password strength
        $passwordValidation = $this->validatePasswordStrength($newPassword);
        if (!$passwordValidation['valid']) {
            return $this->createResponse(false, 'WEAK_PASSWORD', $passwordValidation['message']);
        }

        // Find and validate token
        $tokenData = $this->findValidResetToken($token);
        if (!$tokenData) {
            return $this->createResponse(false, 'INVALID_TOKEN', 'Invalid or expired password reset link');
        }

        // Check if token has expired
        if ($this->isTokenExpired($tokenData['reset_expires'])) {
            $this->invalidateToken($token);
            return $this->createResponse(false, 'TOKEN_EXPIRED', 'Password reset link has expired. Please request a new one.');
        }

        // Update password
        $passwordUpdated = $this->updateUserPassword($tokenData['id'], $newPassword);
        if (!$passwordUpdated) {
            return $this->createResponse(false, 'PASSWORD_UPDATE_FAILED', 'Failed to update password. Please try again.');
        }

        // Invalidate token after successful use
        $this->invalidateToken($token);

        // Log password change
        $this->logger->logPasswordChange($tokenData['id'], $ipAddress, 'reset_token');

        return $this->createResponse(true, 'PASSWORD_RESET_SUCCESS', 'Password has been reset successfully. You can now log in with your new password.');
    }

    /**
     * Validate email format
     * 
     * @param string $email Email address
     * @return bool True if valid
     */
    private function isValidEmailFormat($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Find user by email
     * 
     * @param string $email Email address
     * @return array|false User data or false
     */
    private function findUserByEmail($email)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, username, email, password, is_active
                FROM users
                WHERE email = ?
                LIMIT 1
            ");
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[PasswordResetService] Database error finding user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user only uses OAuth (no password set)
     * 
     * @param array $user User data
     * @return bool True if OAuth only
     */
    private function isOAuthOnlyUser($user)
    {
        return empty($user['password']) || $user['password'] === null;
    }

    /**
     * Generate secure random token
     * 
     * @return string Hex token
     */
    private function generateSecureToken()
    {
        return bin2hex(random_bytes(self::TOKEN_LENGTH_BYTES));
    }

    /**
     * Calculate token expiry timestamp
     * 
     * @return string MySQL datetime
     */
    private function calculateTokenExpiry()
    {
        return date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_EXPIRY_HOURS . ' hours'));
    }

    /**
     * Store reset token in database
     * 
     * @param int $userId User ID
     * @param string $token Reset token
     * @param string $expiry Expiry datetime
     * @return bool Success
     */
    private function storeResetToken($userId, $token, $expiry)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users
                SET reset_token = ?,
                    reset_expires = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$token, $expiry, $userId]);
        } catch (PDOException $e) {
            error_log('[PasswordResetService] Database error storing token: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send password reset email
     * 
     * @param string $email Email address
     * @param string $token Reset token
     * @return bool Success
     */
    private function sendResetEmail($email, $token)
    {
        try {
            return EmailSender::sendResetPasswordEmail($email, $token);
        } catch (Exception $e) {
            error_log('[PasswordResetService] Email send error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find valid reset token
     * 
     * @param string $token Reset token
     * @return array|false Token data or false
     */
    private function findValidResetToken($token)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, username, email, reset_token, reset_expires
                FROM users
                WHERE reset_token = ?
                AND reset_token IS NOT NULL
                LIMIT 1
            ");
            $stmt->execute([$token]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[PasswordResetService] Database error finding token: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if token has expired
     * 
     * @param string $expiryDatetime Expiry datetime
     * @return bool True if expired
     */
    private function isTokenExpired($expiryDatetime)
    {
        if (empty($expiryDatetime)) {
            return true;
        }

        $expiryTimestamp = strtotime($expiryDatetime);
        $currentTimestamp = time();

        return $expiryTimestamp < $currentTimestamp;
    }

    /**
     * Update user password
     * 
     * @param int $userId User ID
     * @param string $newPassword New password
     * @return bool Success
     */
    private function updateUserPassword($userId, $newPassword)
    {
        try {
            $hashedPassword = hashPassword($newPassword);

            $stmt = $this->pdo->prepare("
                UPDATE users
                SET password = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            return $stmt->execute([$hashedPassword, $userId]);
        } catch (PDOException $e) {
            error_log('[PasswordResetService] Database error updating password: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Invalidate reset token
     * 
     * @param string $token Token to invalidate
     * @return bool Success
     */
    private function invalidateToken($token)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users
                SET reset_token = NULL,
                    reset_expires = NULL,
                    updated_at = NOW()
                WHERE reset_token = ?
            ");
            return $stmt->execute([$token]);
        } catch (PDOException $e) {
            error_log('[PasswordResetService] Database error invalidating token: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate password strength
     * 
     * @param string $password Password to validate
     * @return array Validation result
     */
    private function validatePasswordStrength($password)
    {
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            return [
                'valid' => false,
                'message' => sprintf('Password must be at least %d characters long', self::MIN_PASSWORD_LENGTH)
            ];
        }

        // Add more password strength rules here if needed
        // Example: require uppercase, lowercase, numbers, special chars

        return [
            'valid' => true,
            'message' => 'Password is acceptable'
        ];
    }

    /**
     * Create response array
     * 
     * @param bool $success Success status
     * @param string $code Response code
     * @param string $message Response message
     * @return array Response
     */
    private function createResponse($success, $code, $message)
    {
        return [
            'success' => $success,
            'code' => $code,
            'message' => $message
        ];
    }
}
