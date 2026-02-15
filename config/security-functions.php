<?php
/**
 * Security Configuration
 * CSRF Token, Rate Limiting, and Request Verification
 */

// CSRF Token Management
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }

    // Regenerate token every 1 hour
    if (time() - ($_SESSION['csrf_token_time'] ?? 0) > 3600) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }

    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token)
{
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

// Request Signature Generation
function generateRequestSignature($data, $secret = null)
{
    if ($secret === null) {
        $secret = defined('API_SECRET_KEY') ? API_SECRET_KEY : 'your-secret-key-change-this';
    }

    // Sort data for consistent signature
    if (is_array($data)) {
        ksort($data);
        $string = http_build_query($data);
    } else {
        $string = $data;
    }

    return hash_hmac('sha256', $string, $secret);
}

function verifyRequestSignature($data, $signature, $secret = null)
{
    $expectedSignature = generateRequestSignature($data, $secret);
    return hash_equals($expectedSignature, $signature);
}

// Rate Limiting (File-based for simplicity, use Redis in production)
class RateLimiter
{
    private $storageDir;

    public function __construct($storageDir = null)
    {
        $this->storageDir = $storageDir ?? sys_get_temp_dir() . '/rate_limit';
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    public function checkLimit($key, $maxAttempts = 10, $decayMinutes = 1)
    {
        $key = md5($key);
        $file = $this->storageDir . '/' . $key;

        // Clean old files
        $this->cleanup();

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            $attempts = $data['attempts'] ?? 0;
            $resetTime = $data['reset_time'] ?? 0;

            // Check if reset time has passed
            if (time() > $resetTime) {
                $attempts = 0;
            }

            if ($attempts >= $maxAttempts) {
                return false;
            }

            // Increment attempts
            $data['attempts'] = $attempts + 1;
            $data['reset_time'] = $resetTime ?: (time() + ($decayMinutes * 60));
            file_put_contents($file, json_encode($data));
        } else {
            // First attempt
            $data = [
                'attempts' => 1,
                'reset_time' => time() + ($decayMinutes * 60)
            ];
            file_put_contents($file, json_encode($data));
        }

        return true;
    }

    public function getRemainingAttempts($key, $maxAttempts = 10)
    {
        $key = md5($key);
        $file = $this->storageDir . '/' . $key;

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            $attempts = $data['attempts'] ?? 0;
            $resetTime = $data['reset_time'] ?? 0;

            if (time() > $resetTime) {
                return $maxAttempts;
            }

            return max(0, $maxAttempts - $attempts);
        }

        return $maxAttempts;
    }

    private function cleanup()
    {
        $files = glob($this->storageDir . '/*');
        foreach ($files as $file) {
            if (is_file($file) && time() - filemtime($file) > 3600) {
                unlink($file);
            }
        }
    }
}

// IP Validation
function isValidIP($ip)
{
    return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

function getClientIP()
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    // Check for proxy
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }

    return isValidIP($ip) ? $ip : '0.0.0.0';
}

// Webhook Signature Verification
function verifyWebhookSignature($payload, $signature, $secret)
{
    $expectedSignature = hash_hmac('sha256', $payload, $secret);
    return hash_equals($expectedSignature, $signature);
}

// Nonce Management (Replay Attack Prevention)
function generateNonce()
{
    return bin2hex(random_bytes(16));
}

function verifyNonce($nonce, $maxAge = 300)
{
    $nonceFile = sys_get_temp_dir() . '/nonces/' . md5($nonce);

    // Create nonces directory if not exists
    $nonceDir = sys_get_temp_dir() . '/nonces';
    if (!is_dir($nonceDir)) {
        mkdir($nonceDir, 0755, true);
    }

    // Check if nonce exists
    if (file_exists($nonceFile)) {
        return false; // Nonce already used
    }

    // Store nonce
    file_put_contents($nonceFile, time());

    // Cleanup old nonces
    $files = glob($nonceDir . '/*');
    foreach ($files as $file) {
        if (is_file($file) && time() - filemtime($file) > $maxAge) {
            unlink($file);
        }
    }

    return true;
}

// Request Timestamp Validation
function verifyTimestamp($timestamp, $maxAge = 300)
{
    $now = time();
    $diff = abs($now - $timestamp);
    return $diff <= $maxAge;
}

// API Response Helper
function apiResponse($success, $message, $data = [], $httpCode = 200)
{
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ]);
    exit;
}

// Security Headers
function setSecurityHeaders()
{
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');

    // XSS Protection
    header('X-XSS-Protection: 1; mode=block');

    // Prevent MIME sniffing
    header('X-Content-Type-Options: nosniff');

    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Content Security Policy (adjust as needed)
    // header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\' https://cdn.gtranslate.net https://www.gstatic.com; style-src \'self\' \'unsafe-inline\' https://fonts.googleapis.com; font-src \'self\' https://fonts.gstatic.com;');
}

// Call security headers on every page
setSecurityHeaders();
