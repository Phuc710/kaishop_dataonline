<?php
/**
 * Google reCAPTCHA Enterprise Verifier
 * Handles verification of reCAPTCHA Enterprise tokens
 * 
 * @package KaiShop
 * @subpackage Security
 * @version 1.0.0
 */

class GoogleRecaptchaVerifier {
    /**
     * reCAPTCHA Enterprise API endpoint
     */
    private const API_ENDPOINT = 'https://recaptchaenterprise.googleapis.com/v1/projects/%s/assessments?key=%s';
    
    /**
     * Minimum acceptable score (0.0 - 1.0)
     */
    private const MIN_SCORE_THRESHOLD = 0.5;
    
    /**
     * Expected action name
     */
    private const EXPECTED_ACTION = 'login';
    
    /**
     * Request timeout in seconds
     */
    private const REQUEST_TIMEOUT = 10;
    
    /**
     * Verify reCAPTCHA Enterprise token
     * 
     * @param string $token reCAPTCHA response token
     * @param string $expectedAction Expected action name (default: 'login')
     * @return array Verification result with 'success', 'score', and optional 'error'
     */
    public static function verify($token, $expectedAction = self::EXPECTED_ACTION) {
        // Skip verification on localhost
        if (self::isLocalhostEnvironment()) {
            return self::createSuccessResponse(1.0, true);
        }
        
        // Validate token is provided
        if (empty($token)) {
            return self::createErrorResponse('MISSING_TOKEN', 'reCAPTCHA token is required');
        }
        
        // Check if reCAPTCHA is configured
        if (!self::isRecaptchaConfigured()) {
            self::logError('reCAPTCHA not configured', [
                'has_secret_key' => defined('RECAPTCHA_SECRET_KEY'),
                'has_project_id' => defined('RECAPTCHA_PROJECT_ID')
            ]);
            return self::createErrorResponse('NOT_CONFIGURED', 'reCAPTCHA is not configured');
        }
        
        // Call reCAPTCHA API
        $apiResponse = self::callRecaptchaApi($token, $expectedAction);
        
        if (!$apiResponse['success']) {
            return $apiResponse;
        }
        
        // Validate response
        return self::validateApiResponse($apiResponse['data'], $expectedAction);
    }
    
    /**
     * Call reCAPTCHA Enterprise API
     * 
     * @param string $token reCAPTCHA token
     * @param string $expectedAction Expected action
     * @return array API call result
     */
    private static function callRecaptchaApi($token, $expectedAction) {
        $apiUrl = sprintf(
            self::API_ENDPOINT,
            RECAPTCHA_PROJECT_ID,
            RECAPTCHA_SECRET_KEY
        );
        
        $requestPayload = [
            'event' => [
                'token' => $token,
                'siteKey' => RECAPTCHA_SITE_KEY,
                'expectedAction' => $expectedAction
            ]
        ];
        
        // Initialize cURL
        $curl = curl_init($apiUrl);
        if ($curl === false) {
            return self::createErrorResponse('CURL_INIT_FAILED', 'Failed to initialize HTTP client');
        }
        
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($requestPayload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => self::REQUEST_TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        // Execute request
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        
        // Check for cURL errors
        if ($response === false || !empty($curlError)) {
            self::logError('API request failed', [
                'curl_error' => $curlError,
                'http_code' => $httpCode
            ]);
            return self::createErrorResponse('API_REQUEST_FAILED', 'Failed to verify reCAPTCHA');
        }
        
        // Check HTTP status
        if ($httpCode !== 200) {
            self::logError('API returned non-200 status', [
                'http_code' => $httpCode,
                'response' => substr($response, 0, 500)
            ]);
            return self::createErrorResponse('API_ERROR', 'reCAPTCHA verification failed');
        }
        
        // Parse JSON response
        $data = json_decode($response, true);
        if ($data === null || json_last_error() !== JSON_ERROR_NONE) {
            self::logError('Invalid JSON response', [
                'json_error' => json_last_error_msg()
            ]);
            return self::createErrorResponse('INVALID_RESPONSE', 'Invalid API response');
        }
        
        return [
            'success' => true,
            'data' => $data
        ];
    }
    
    /**
     * Validate reCAPTCHA API response
     * 
     * @param array $responseData API response data
     * @param string $expectedAction Expected action
     * @return array Validation result
     */
    private static function validateApiResponse($responseData, $expectedAction) {
        // Check token properties
        if (!isset($responseData['tokenProperties'])) {
            return self::createErrorResponse('MISSING_TOKEN_PROPERTIES', 'Invalid response format');
        }
        
        $tokenProps = $responseData['tokenProperties'];
        
        // Validate token is valid
        if (!isset($tokenProps['valid']) || $tokenProps['valid'] !== true) {
            self::logError('Token is invalid', ['token_properties' => $tokenProps]);
            return self::createErrorResponse('INVALID_TOKEN', 'Token validation failed');
        }
        
        // Validate action matches
        if (!isset($tokenProps['action']) || $tokenProps['action'] !== $expectedAction) {
            self::logError('Action mismatch', [
                'expected' => $expectedAction,
                'actual' => $tokenProps['action'] ?? 'missing'
            ]);
            return self::createErrorResponse('ACTION_MISMATCH', 'Invalid action');
        }
        
        // Get risk score
        $score = $responseData['riskAnalysis']['score'] ?? 0.0;
        
        // Check if score meets threshold
        if ($score < self::MIN_SCORE_THRESHOLD) {
            self::logError('Score below threshold', [
                'score' => $score,
                'threshold' => self::MIN_SCORE_THRESHOLD
            ]);
            return self::createErrorResponse('LOW_SCORE', 'Security score too low', $score);
        }
        
        // Success
        self::logSuccess('Verification successful', ['score' => $score]);
        return self::createSuccessResponse($score, false);
    }
    
    /**
     * Check if running on localhost
     * 
     * @return bool True if localhost
     */
    private static function isLocalhostEnvironment() {
        $hostname = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        return (
            strpos($hostname, 'localhost') !== false ||
            strpos($hostname, '127.0.0.1') !== false ||
            strpos($hostname, '::1') !== false
        );
    }
    
    /**
     * Check if reCAPTCHA is properly configured
     * 
     * @return bool True if configured
     */
    private static function isRecaptchaConfigured() {
        return (
            defined('RECAPTCHA_SECRET_KEY') && !empty(RECAPTCHA_SECRET_KEY) &&
            defined('RECAPTCHA_PROJECT_ID') && !empty(RECAPTCHA_PROJECT_ID) &&
            defined('RECAPTCHA_SITE_KEY') && !empty(RECAPTCHA_SITE_KEY)
        );
    }
    
    /**
     * Create success response
     * 
     * @param float $score Risk score
     * @param bool $skipped Whether verification was skipped
     * @return array Success response
     */
    private static function createSuccessResponse($score, $skipped) {
        return [
            'success' => true,
            'score' => $score,
            'skipped' => $skipped
        ];
    }
    
    /**
     * Create error response
     * 
     * @param string $code Error code
     * @param string $message Error message
     * @param float|null $score Optional score
     * @return array Error response
     */
    private static function createErrorResponse($code, $message, $score = null) {
        $response = [
            'success' => false,
            'error' => $message,
            'error_code' => $code
        ];
        
        if ($score !== null) {
            $response['score'] = $score;
        }
        
        return $response;
    }
    
    /**
     * Log error message
     * 
     * @param string $message Error message
     * @param array $context Additional context
     */
    private static function logError($message, $context = []) {
        $logMessage = sprintf('[GoogleRecaptchaVerifier] ERROR: %s', $message);
        if (!empty($context)) {
            $logMessage .= ' | ' . json_encode($context);
        }
        error_log($logMessage);
    }
    
    /**
     * Log success message
     * 
     * @param string $message Success message
     * @param array $context Additional context
     */
    private static function logSuccess($message, $context = []) {
        $logMessage = sprintf('[GoogleRecaptchaVerifier] SUCCESS: %s', $message);
        if (!empty($context)) {
            $logMessage .= ' | ' . json_encode($context);
        }
        error_log($logMessage);
    }
}
