<?php
/**
 * Security Middleware
 * Execute security checks on every request
 * Include this file in config.php
 */

// Load security classes
require_once __DIR__ . '/../config/security-config.php';
require_once __DIR__ . '/../config/SecurityLogger.php';
require_once __DIR__ . '/../config/RateLimiter.php';
require_once __DIR__ . '/../config/WAF.php';

// Initialize security components
$securityLogger = new SecurityLogger($pdo);
$rateLimiter = new RateLimiter($pdo, $securityLogger);
$waf = new WAF($securityLogger);

// ============================================================================
// BYPASS ALL SECURITY FOR LOCALHOST (Development)
// ============================================================================
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
$localhostIPs = ['127.0.0.1', '::1', 'localhost'];

if (in_array($clientIP, $localhostIPs)) {
    // Skip all security checks for localhost
    goto skip_security;
}
// ============================================================================

// Set CORS headers first (before any other headers)
if (CORS_ENABLED) {
    header('Access-Control-Allow-Origin: ' . CORS_ALLOWED_ORIGINS);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');

    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// Set security headers
if (isset($SECURITY_HEADERS)) {
    foreach ($SECURITY_HEADERS as $header => $value) {
        header("$header: $value");
    }
}

// 1. Whitelist: webhook, OAuth, admin panel, and blocked page
$endpoint = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$whitelistPatterns = [
    '/api/sepay-webhook.php',
    '/auth/google-login.php',
    '/admin/',
    '/admin/index.php',
    '/admin/api/',
    '/admin/tabs/',
    '/blocked.php'
];

foreach ($whitelistPatterns as $pattern) {
    if ($endpoint === $pattern || strpos($endpoint, $pattern) !== false) {
        // Bypass all security for whitelisted routes
        goto skip_security;
    }
}

// 2. Check if Fingerprint is blocked
$fingerprint = SecurityLogger::getFingerprint();
$blocked = $rateLimiter->isFingerprintBlocked($fingerprint);
if ($blocked) {
    // Extract ban reason and redirect to blocked page with reason
    $ban_reason = isset($blocked['reason']) ? urlencode($blocked['reason']) : urlencode('Security violation detected');
    header('Location: ' . BASE_URL . '/blocked.php?reason=' . $ban_reason);
    exit;
}


// 3. Check rate limit
if (!$rateLimiter->checkLimit($endpoint)) {
    http_response_code(429);
    header('Content-Type: application/json');
    header('Retry-After: 60');
    die(json_encode([
        'error' => 'Too Many Requests',
        'message' => 'Rate limit exceeded. Please slow down.',
        'retry_after' => 60,
        'code' => 429
    ]));
}

// 3. Check for bots
if ($waf->isBot()) {
    http_response_code(403);
    header('Content-Type: application/json');
    die(json_encode([
        'error' => 'Forbidden',
        'message' => 'Automated access is not allowed.',
        'code' => 403
    ]));
}

// 4. Run WAF checks
if (!$waf->checkRequest()) {
    // WAF will handle the response
    exit;
}

// 5. Periodic cleanup (1% chance)
if (rand(1, 100) === 1) {
    $rateLimiter->cleanup();
    $securityLogger->cleanup();
}

// Label for webhook bypass
skip_security:
