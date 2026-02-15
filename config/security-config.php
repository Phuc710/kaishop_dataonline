<?php
/**
 * Security Configuration
 * All security-related settings
 */

// Rate Limiting
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_REQUESTS', 300);     // Max requests per window (tăng từ 100 -> 300)
define('RATE_LIMIT_WINDOW', 60);        // Time window in seconds
define('RATE_LIMIT_API', 1000);         // API requests per hour (tăng từ 500 -> 1000)

// Auto-Ban System
define('AUTO_BAN_ENABLED', true);
define('AUTO_BAN_THRESHOLD', 5);        // Violations before auto-ban (tăng từ 3 -> 5)
define('BAN_DURATION', 1800);           // 30 phút (giảm từ 1 giờ)
define('PERMANENT_BAN_THRESHOLD', 20);  // Violations for permanent ban (tăng từ 10 -> 20)

// WAF (Web Application Firewall)
define('WAF_ENABLED', true);
define('WAF_BLOCK_SQL_INJECTION', true);
define('WAF_BLOCK_XSS', true);
define('WAF_BLOCK_TRAVERSAL', true);
define('WAF_BLOCK_RCE', true);

// Bot Protection
define('BOT_PROTECTION_ENABLED', false);   // TẮT bot detection để tránh block nhầm
define('JS_CHALLENGE_ENABLED', false);     // Set true if not using Cloudflare
define('REQUIRE_USER_AGENT', false);       // Không bắt buộc User-Agent

// Content Protection
define('CONTENT_PROTECTION_ENABLED', true);
define('DISABLE_RIGHT_CLICK', true);
define('DISABLE_IMAGE_DRAG', true);
define('DISABLE_TEXT_SELECT', false);   // Don't disable globally

// Cloudflare Integration
define('CLOUDFLARE_ENABLED', true);
define('TRUST_CLOUDFLARE_IP', true);    // Trust CF-Connecting-IP header

// Logging
define('SECURITY_LOG_ENABLED', true);
define('LOG_ALL_REQUESTS', false);      // Only log suspicious activity if false
define('LOG_RETENTION_DAYS', 30);

// Whitelist IPs (admin IPs, trusted services)
$SECURITY_WHITELIST = [
    // Add your admin IPs here
    // '123.456.789.0',
    '127.0.0.1',      // IPv4 localhost
    '::1',            // IPv6 localhost
    'localhost',       // localhost hostname
    '14.191.221.102',
];

// Bot User-Agent Patterns (to block)
$BOT_PATTERNS = [
    'bot',
    'crawler',
    'spider',
    'scraper',
    'curl',
    'wget',
    'python',
    'java',
    'go-http',
    'scrapy',
    'selenium',
    'phantomjs',
    'headless',
    'automation',
    'httpclient'
];

// Allowed Bots (search engines)
$ALLOWED_BOTS = [
    'googlebot',
    'bingbot',
    'yahoo',
    'duckduckbot',
    'baiduspider',
    'yandexbot',
    'facebookexternalhit'
];

// Dangerous Patterns (for WAF)
$DANGEROUS_PATTERNS = [
    // SQL Injection
    '/(\bUNION\b.*\bSELECT\b|\bSELECT\b.*\bFROM\b.*\bWHERE\b)/i',
    '/(sleep\(|benchmark\(|waitfor\s+delay)/i',
    '/(\bDROP\b|\bDELETE\b|\bUPDATE\b).*\bWHERE\b/i',

    // XSS
    '/(<script|<iframe|javascript:|onerror=|onload=)/i',
    '/(eval\(|expression\(|fromcharcode|alert\()/i',

    // Path Traversal
    '/(\.\.\/|\.\.\\\\|\/etc\/passwd|\/proc\/)/i',

    // RCE
    '/(exec\(|shell_exec|system\(|passthru|`)/i',
    '/(\$_GET|\$_POST|\$_REQUEST|\$_FILES)/i',
];

// CORS Settings
define('CORS_ENABLED', true);
define('CORS_ALLOWED_ORIGINS', '*'); // Change to specific domains in production

// Security Headers
$SECURITY_HEADERS = [
    'X-Frame-Options' => 'SAMEORIGIN',
    'X-Content-Type-Options' => 'nosniff',
    'X-XSS-Protection' => '1; mode=block',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Permissions-Policy' => 'geolocation=(), microphone=(), camera=(), payment=(), usb=()',
    // 'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline';",
];
