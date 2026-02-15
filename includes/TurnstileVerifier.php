<?php
/**
 * Cloudflare Turnstile Verifier
 * Verifies Turnstile tokens server-side
 */

class TurnstileVerifier
{
    const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * Verify Turnstile token
     * @param string $token The Turnstile response token
     * @param string $remoteIp User's IP address (optional)
     * @return bool True if verification successful
     */
    public static function verify($token, $remoteIp = null)
    {
        // **LOCALHOST BYPASS** - Skip verification on localhost
        $hostname = $_SERVER['HTTP_HOST'] ?? '';
        if (strpos($hostname, 'localhost') !== false || strpos($hostname, '127.0.0.1') !== false) {
            error_log('[Turnstile] Localhost detected - skipping verification');
            return true; // Always pass on localhost
        }

        // **INPUT VALIDATION** - Check token format
        if (empty($token)) {
            error_log('[Turnstile] Token is empty');
            return false;
        }

        // Validate token length (max 2048 characters per Cloudflare docs)
        if (!is_string($token) || strlen($token) > 2048) {
            error_log('[Turnstile] Invalid token format or length');
            return false;
        }

        // Get IP if not provided (support Cloudflare proxied requests)
        if (!$remoteIp) {
            $remoteIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ??
                $_SERVER['HTTP_X_FORWARDED_FOR'] ??
                $_SERVER['REMOTE_ADDR'] ?? '';
        }

        // Prepare POST data
        $postData = [
            'secret' => TURNSTILE_SECRET_KEY,
            'response' => $token,
            'remoteip' => $remoteIp
        ];

        // Initialize cURL with timeout
        $ch = curl_init(self::VERIFY_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            error_log('[Turnstile] cURL error: ' . curl_error($ch));
            curl_close($ch);
            return false;
        }

        curl_close($ch);

        // Check HTTP status
        if ($httpCode !== 200) {
            error_log('[Turnstile] HTTP error: ' . $httpCode);
            return false;
        }

        // Parse response
        $result = json_decode($response, true);

        if (!$result || json_last_error() !== JSON_ERROR_NONE) {
            error_log('[Turnstile] Invalid JSON response: ' . json_last_error_msg());
            return false;
        }

        // Log for debugging (production should use proper logging)
        error_log('[Turnstile] Verification result: ' . json_encode($result));

        // Check success
        if (!isset($result['success']) || $result['success'] !== true) {
            // Log specific error codes
            if (isset($result['error-codes'])) {
                $errorCodes = implode(', ', $result['error-codes']);
                error_log('[Turnstile] Verification failed - Error codes: ' . $errorCodes);

                // Handle specific errors
                if (in_array('timeout-or-duplicate', $result['error-codes'])) {
                    error_log('[Turnstile] Token expired or already used');
                }
            }
            return false;
        }

        // **HOSTNAME VERIFICATION** - Ensure token was generated for this domain
        if (isset($result['hostname'])) {
            $expectedHostname = $_SERVER['HTTP_HOST'] ?? '';
            if ($result['hostname'] !== $expectedHostname) {
                error_log('[Turnstile] Hostname mismatch - Expected: ' . $expectedHostname . ', Got: ' . $result['hostname']);
                // Still return true but log warning (some setups may have different hostnames)
            }
        }

        // **TOKEN AGE CHECK** - Warn if token is old (close to 5-minute expiry)
        if (isset($result['challenge_ts'])) {
            $challengeTime = strtotime($result['challenge_ts']);
            $ageSeconds = time() - $challengeTime;
            $ageMinutes = $ageSeconds / 60;

            if ($ageMinutes > 4) {
                error_log('[Turnstile] Warning: Token is ' . round($ageMinutes, 1) . ' minutes old (close to expiry)');
            }
        }

        return true;
    }

    /**
     * Get token from POST request
     * @return string|null
     */
    public static function getTokenFromRequest()
    {
        return $_POST['cf-turnstile-response'] ?? null;
    }
}
