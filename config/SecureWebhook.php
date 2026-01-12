<?php
/**
 * Secure Webhook Handler - SePay Only
 * Simplified version chỉ cho SePay
 */

class SecureWebhook {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Verify SePay webhook
     * Kiểm tra transaction code format và data validity
     */
    public function verifySePay($data) {
        // Check required fields
        if (!isset($data['content']) || !isset($data['amount'])) {
            return false;
        }
        
        // Verify content contains valid transaction code (kai + 15 chars)
        if (!preg_match('/kai[A-Z0-9]{15}/i', $data['content'])) {
            return false;
        }
        
        // Verify amount is positive
        if (floatval($data['amount']) <= 0) {
            return false;
        }
        
        // Verify transfer type is 'in' (incoming only)
        if (isset($data['transfer_type']) && $data['transfer_type'] !== 'in') {
            return false;
        }
        
        return true;
    }
    
    /**
     * Log webhook request
     */
    public function logWebhook($type, $data, $verified = false) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO webhook_logs (type, data, verified, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt->execute([
                $type,
                json_encode($data),
                $verified ? 1 : 0,
                $ip,
                $userAgent
            ]);
        } catch (Exception $e) {
            error_log("Webhook log error: " . $e->getMessage());
        }
    }
}

