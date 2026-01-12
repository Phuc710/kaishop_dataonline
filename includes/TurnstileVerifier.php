<?php
/**
 * Cloudflare Turnstile Verifier
 * Verifies Turnstile tokens server-side
 */

class TurnstileVerifier {
    const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    
    /**
     * Verify Turnstile token
     * @param string $token The Turnstile response token
     * @param string $remoteIp User's IP address (optional)
     * @return bool True if verification successful
     */
    public static function verify($token, $remoteIp = null) {
        // **LOCALHOST BYPASS** - Skip verification on localhost
        $hostname = $_SERVER['HTTP_HOST'] ?? '';
        if (strpos($hostname, 'localhost') !== false || strpos($hostname, '127.0.0.1') !== false) {
            error_log('[Turnstile] Localhost detected - skipping verification');
            return true; // Always pass on localhost
        }
        
        if (empty($token)) {
            error_log('[Turnstile] Token is empty');
            return false;
        }
        
        // Get IP if not provided
        if (!$remoteIp) {
            $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        // Prepare POST data
        $postData = [
            'secret' => TURNSTILE_SECRET_KEY,
            'response' => $token,
            'remoteip' => $remoteIp
        ];
        
        // Initialize cURL
        $ch = curl_init(self::VERIFY_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
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
        
        if (!$result) {
            error_log('[Turnstile] Invalid JSON response');
            return false;
        }
        
        // Log for debugging
        error_log('[Turnstile] Verification result: ' . json_encode($result));
        
        // Check success
        if (isset($result['success']) && $result['success'] === true) {
            return true;
        }
        
        // Log error codes if failed
        if (isset($result['error-codes'])) {
            error_log('[Turnstile] Error codes: ' . implode(', ', $result['error-codes']));
        }
        
        return false;
    }
    
    /**
     * Get token from POST request
     * @return string|null
     */
    public static function getTokenFromRequest() {
        return $_POST['cf-turnstile-response'] ?? null;
    }
}
