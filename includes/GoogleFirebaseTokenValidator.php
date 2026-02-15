<?php
/**
 * Google Firebase Token Validator
 * Validates Firebase ID tokens with comprehensive security checks
 * 
 * @package KaiShop
 * @subpackage Authentication
 * @version 1.0.0
 */

class GoogleFirebaseTokenValidator
{
    /**
     * Firebase issuer prefix
     */
    private const FIREBASE_ISSUER_PREFIX = 'https://securetoken.google.com/';

    /**
     * Google accounts issuer
     */
    private const GOOGLE_ISSUER = 'https://accounts.google.com';

    /**
     * Get Firebase project ID from config
     * 
     * @return string Project ID from .env or default
     */
    private static function getProjectId()
    {
        return defined('FIREBASE_PROJECT_ID') && !empty(FIREBASE_PROJECT_ID)
            ? FIREBASE_PROJECT_ID
            : 'kaishop-id-vn';
    }

    /**
     * Clock skew tolerance in seconds (5 minutes)
     */
    private const CLOCK_SKEW_SECONDS = 300;

    /**
     * Validate Firebase ID Token
     * 
     * @param string $idToken The Firebase ID token to validate
     * @return array|false User data array on success, false on failure
     */
    public static function validateFirebaseIdToken($idToken)
    {
        // Validate token format
        $tokenParts = explode('.', $idToken);
        if (count($tokenParts) !== 3) {
            self::logValidationError('Invalid JWT format', [
                'token_parts_count' => count($tokenParts),
                'expected' => 3
            ]);
            return false;
        }

        // Decode and parse payload
        $payload = self::decodeJwtPayload($tokenParts[1]);
        if ($payload === false) {
            return false;
        }

        // Validate all token claims
        $validationResult = self::validateTokenClaims($payload);
        if (!$validationResult['valid']) {
            self::logValidationError('Token claims validation failed', $validationResult);
            return false;
        }

        // Extract and return user data
        return self::extractUserDataFromPayload($payload);
    }

    /**
     * Decode JWT payload from base64
     * 
     * @param string $payloadBase64 Base64 encoded payload
     * @return array|false Decoded payload array or false on error
     */
    private static function decodeJwtPayload($payloadBase64)
    {
        $decodedString = self::base64UrlDecode($payloadBase64);
        if ($decodedString === false) {
            self::logValidationError('Failed to decode base64 payload');
            return false;
        }

        $payload = json_decode($decodedString, true);

        if ($payload === null || json_last_error() !== JSON_ERROR_NONE) {
            self::logValidationError('Invalid JSON in payload', [
                'json_error' => json_last_error_msg(),
                'json_error_code' => json_last_error()
            ]);
            return false;
        }

        return $payload;
    }

    /**
     * Validate all claims in the token payload
     * 
     * @param array $payload Token payload
     * @return array Validation result with 'valid' boolean and 'errors' array
     */
    private static function validateTokenClaims($payload)
    {
        $currentTimestamp = time();
        $validationResult = [
            'valid' => true,
            'errors' => [],
            'warnings' => []
        ];

        // Validate issuer claim
        if (!self::validateIssuerClaim($payload['iss'] ?? '')) {
            $validationResult['valid'] = false;
            $validationResult['errors'][] = sprintf(
                'Invalid issuer: %s (expected one of: %s)',
                $payload['iss'] ?? 'missing',
                implode(', ', self::getValidIssuers())
            );
        }

        // Validate audience claim (must match project ID)
        if (!self::validateAudienceClaim($payload['aud'] ?? '')) {
            $validationResult['valid'] = false;
            $validationResult['errors'][] = sprintf(
                'Invalid audience: %s (expected: %s)',
                $payload['aud'] ?? 'missing',
                self::getProjectId()
            );
        }

        // Validate expiration time
        if (!isset($payload['exp'])) {
            $validationResult['valid'] = false;
            $validationResult['errors'][] = 'Missing expiration time (exp)';
        } elseif ($payload['exp'] < $currentTimestamp) {
            $validationResult['valid'] = false;
            $validationResult['errors'][] = sprintf(
                'Token expired at %s (current time: %s)',
                date('Y-m-d H:i:s', $payload['exp']),
                date('Y-m-d H:i:s', $currentTimestamp)
            );
        }

        // Validate issued at time
        if (isset($payload['iat'])) {
            $maxIssuedAt = $currentTimestamp + self::CLOCK_SKEW_SECONDS;
            if ($payload['iat'] > $maxIssuedAt) {
                $validationResult['valid'] = false;
                $validationResult['errors'][] = sprintf(
                    'Token issued in the future (iat: %s, max allowed: %s)',
                    date('Y-m-d H:i:s', $payload['iat']),
                    date('Y-m-d H:i:s', $maxIssuedAt)
                );
            }
        }

        // Validate auth time
        if (isset($payload['auth_time'])) {
            $maxAuthTime = $currentTimestamp + self::CLOCK_SKEW_SECONDS;
            if ($payload['auth_time'] > $maxAuthTime) {
                $validationResult['valid'] = false;
                $validationResult['errors'][] = sprintf(
                    'Auth time in the future (auth_time: %s, max allowed: %s)',
                    date('Y-m-d H:i:s', $payload['auth_time']),
                    date('Y-m-d H:i:s', $maxAuthTime)
                );
            }
        }

        // Validate subject (user ID) exists
        if (empty($payload['sub'])) {
            $validationResult['valid'] = false;
            $validationResult['errors'][] = 'Missing subject claim (sub)';
        }

        // Validate email exists
        if (empty($payload['email'])) {
            $validationResult['valid'] = false;
            $validationResult['errors'][] = 'Missing email claim';
        }

        return $validationResult;
    }

    /**
     * Validate issuer claim
     * 
     * @param string $issuer Issuer from token
     * @return bool True if valid
     */
    private static function validateIssuerClaim($issuer)
    {
        return in_array($issuer, self::getValidIssuers(), true);
    }

    /**
     * Get list of valid issuers
     * 
     * @return array Valid issuer URLs
     */
    private static function getValidIssuers()
    {
        $projectId = self::getProjectId();
        return [
            self::FIREBASE_ISSUER_PREFIX . $projectId,
            self::GOOGLE_ISSUER,
            'accounts.google.com' // Alternative format
        ];
    }

    /**
     * Validate audience claim
     * 
     * @param string $audience Audience from token
     * @return bool True if valid
     */
    private static function validateAudienceClaim($audience)
    {
        // For Firebase ID tokens, audience must match project ID
        return $audience === self::getProjectId();
    }

    /**
     * Extract user data from validated payload
     * 
     * @param array $payload Validated token payload
     * @return array User data
     */
    private static function extractUserDataFromPayload($payload)
    {
        return [
            'firebase_uid' => $payload['sub'] ?? null,
            'email' => $payload['email'] ?? null,
            'email_verified' => $payload['email_verified'] ?? false,
            'name' => $payload['name'] ?? null,
            'picture' => $payload['picture'] ?? null,
            'auth_time' => $payload['auth_time'] ?? null,
            'issued_at' => $payload['iat'] ?? null,
            'expires_at' => $payload['exp'] ?? null
        ];
    }

    /**
     * Decode base64url encoded string
     * 
     * @param string $data Base64url encoded data
     * @return string|false Decoded string or false on error
     */
    private static function base64UrlDecode($data)
    {
        // Add padding if needed
        $remainder = strlen($data) % 4;
        if ($remainder > 0) {
            $paddingLength = 4 - $remainder;
            $data .= str_repeat('=', $paddingLength);
        }

        // Convert base64url to base64
        $base64 = strtr($data, '-_', '+/');

        // Decode
        $decoded = base64_decode($base64, true);

        if ($decoded === false) {
            self::logValidationError('Base64 decode failed');
        }

        return $decoded;
    }

    /**
     * Log validation errors with context
     * 
     * @param string $message Error message
     * @param array $context Additional context
     */
    private static function logValidationError($message, $context = [])
    {
        $logMessage = sprintf(
            '[GoogleFirebaseTokenValidator] %s',
            $message
        );

        if (!empty($context)) {
            $logMessage .= ' | Context: ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        }

        error_log($logMessage);
    }
}
