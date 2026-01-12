<?php
/**
 * WAF (Web Application Firewall) Class
 * Protects against common web attacks
 */

require_once __DIR__ . '/SecurityLogger.php';

class WAF {
    private $logger;
    private $enabled;
    
    public function __construct($logger = null) {
        $this->logger = $logger;
        $this->enabled = defined('WAF_ENABLED') ? WAF_ENABLED : true;
    }
    
    /**
     * Check all inputs for threats
     */
    public function checkRequest() {
        if (!$this->enabled) return true;
        
        // Check for SQL Injection
        if (defined('WAF_BLOCK_SQL_INJECTION') && WAF_BLOCK_SQL_INJECTION) {
            if ($this->detectSQLInjection()) {
                $this->blockRequest('sql_injection');
                return false;
            }
        }
        
        // Check for XSS
        if (defined('WAF_BLOCK_XSS') && WAF_BLOCK_XSS) {
            if ($this->detectXSS()) {
                $this->blockRequest('xss_attempt');
                return false;
            }
        }
        
        // Check for Path Traversal
        if (defined('WAF_BLOCK_TRAVERSAL') && WAF_BLOCK_TRAVERSAL) {
            if ($this->detectPathTraversal()) {
                $this->blockRequest('path_traversal');
                return false;
            }
        }
        
        // Check for RCE attempts
        if (defined('WAF_BLOCK_RCE') && WAF_BLOCK_RCE) {
            if ($this->detectRCE()) {
                $this->blockRequest('rce_attempt');
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Detect SQL Injection patterns
     */
    private function detectSQLInjection() {
        $patterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bSELECT\b.*\bFROM\b.*\bWHERE\b)/i',
            '/(sleep\(|benchmark\(|waitfor\s+delay)/i',
            '/(\bDROP\b|\bDELETE\b|\bTRUNCATE\b|\bINSERT\b|\bUPDATE\b).*\b(table|database|WHERE)/i',
            '/(--|#|\/\*|\*\/|;)/i', // SQL comment chars
            '/(\bOR\b|\bAND\b).*=.*\1/i', // OR 1=1, AND 1=1
        ];
        
        return $this->scanInputs($patterns);
    }
    
    /**
     * Detect XSS patterns
     */
    private function detectXSS() {
        $patterns = [
            '/(<script|<\/script>|javascript:|onerror=|onload=|onclick=)/i',
            '/(eval\(|expression\(|fromcharcode|alert\(|confirm\(|prompt\()/i',
            '/(<iframe|<object|<embed|<applet)/i',
            '/(document\.|window\.|location\.|cookie)/i',
        ];
        
        return $this->scanInputs($patterns);
    }
    
    /**
     * Detect Path Traversal
     */
    private function detectPathTraversal() {
        $patterns = [
            '/(\.\.\/|\.\.\\\\)/i',
            '/(\/etc\/passwd|\/proc\/|\/var\/log)/i',
            '/(php:\/\/|file:\/\/|glob:\/\/|data:\/\/)/i',
        ];
        
        return $this->scanInputs($patterns);
    }
    
    /**
     * Detect RCE (Remote Code Execution)
     */
    private function detectRCE() {
        $patterns = [
            '/(exec\(|shell_exec|system\(|passthru|popen|proc_open)/i',
            '/(`|base64_decode|eval|assert|preg_replace.*\/e)/i',
            '/(\$_GET|\$_POST|\$_REQUEST|\$_FILES|\$_COOKIE|\$_SERVER)/i',
            '/(phpinfo|file_get_contents|file_put_contents|fopen|fwrite)/i',
        ];
        
        return $this->scanInputs($patterns);
    }
    
    /**
     * Scan all inputs against patterns
     */
    private function scanInputs($patterns) {
        $inputs = array_merge(
            $_GET,
            $_POST,
            $_COOKIE,
            ['uri' => $_SERVER['REQUEST_URI'] ?? '']
        );
        
        foreach ($inputs as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    if ($this->logger) {
                        $this->logger->logSuspicious('pattern_match', [
                            'pattern' => $pattern,
                            'input_key' => $key,
                            'value' => substr($value, 0, 100)
                        ]);
                    }
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Block request and exit
     */
    private function blockRequest($attack_type) {
        if ($this->logger) {
            $this->logger->logBlocked($attack_type, 'WAF blocked request');
        }
        
        http_response_code(403);
        header('Content-Type: application/json');
        die(json_encode([
            'error' => 'Forbidden',
            'message' => 'Your request has been blocked by security filters.',
            'code' => 403
        ]));
    }
    
    /**
     * Sanitize input (use this for outputs)
     */
    public static function sanitize($data, $type = 'html') {
        if (is_array($data)) {
            return array_map(function($item) use ($type) {
                return self::sanitize($item, $type);
            }, $data);
        }
        
        switch ($type) {
            case 'html':
                return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
            case 'url':
                return urlencode($data);
            case 'sql':
                return addslashes($data);
            case 'js':
                return json_encode($data);
            default:
                return $data;
        }
    }
    
    /**
     * Check if bot (based on User-Agent)
     */
    public function isBot() {
        if (!defined('BOT_PROTECTION_ENABLED') || !BOT_PROTECTION_ENABLED) {
            return false;
        }
        
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        
        if (empty($user_agent)) {
            if (defined('REQUIRE_USER_AGENT') && REQUIRE_USER_AGENT) {
                return true; // No user agent = bot
            }
            return false;
        }
        
        // Check allowed bots first
        global $ALLOWED_BOTS;
        if (isset($ALLOWED_BOTS)) {
            foreach ($ALLOWED_BOTS as $bot) {
                if (stripos($user_agent, $bot) !== false) {
                    return false; // Allowed bot
                }
            }
        }
        
        // Check bot patterns
        global $BOT_PATTERNS;
        if (isset($BOT_PATTERNS)) {
            foreach ($BOT_PATTERNS as $pattern) {
                if (stripos($user_agent, $pattern) !== false) {
                    if ($this->logger) {
                        $this->logger->logSuspicious('bot_detected', [
                            'user_agent' => $user_agent,
                            'pattern' => $pattern
                        ]);
                    }
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Validate file upload
     */
    public function validateUpload($file) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'Invalid upload'];
        }
        
        // Check file size (10MB max)
        $max_size = 10 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            return ['valid' => false, 'error' => 'File too large'];
        }
        
        // Validate MIME type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime, $allowed_types)) {
            if ($this->logger) {
                $this->logger->logSuspicious('invalid_upload', [
                    'mime' => $mime,
                    'filename' => $file['name']
                ]);
            }
            return ['valid' => false, 'error' => 'Invalid file type'];
        }
        
        // Check file extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($ext, $allowed_ext)) {
            return ['valid' => false, 'error' => 'Invalid file extension'];
        }
        
        // Check for PHP tag in image files (shell upload attempt)
        $content = file_get_contents($file['tmp_name']);
        if (preg_match('/<\?php|<\?=|<script/i', $content)) {
            if ($this->logger) {
                $this->logger->logCritical('shell_upload_attempt', [
                    'filename' => $file['name']
                ]);
            }
            return ['valid' => false, 'error' => 'Malicious file detected'];
        }
        
        return ['valid' => true];
    }
}
