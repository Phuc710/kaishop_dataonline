<?php
/**
 * Auto-expire Transactions Cron Job
 * Run this script every minute to automatically expire old pending transactions
 * 
 * Setup cron job (Linux):
 * * * * * * php /path/to/expire-transactions.php
 * 
 * Or use Windows Task Scheduler for Windows servers
 */

require_once __DIR__ . '/../config/config.php';

try {
    // Find all pending transactions that have expired
    $stmt = $pdo->prepare("
        UPDATE payment_transactions 
        SET status = 'expired' 
        WHERE status = 'pending' 
        AND expires_at < NOW()
    ");
    
    $stmt->execute();
    $expired_count = $stmt->rowCount();
    
    // Log result
    $log_file = __DIR__ . '/../logs/cron.log';
    $log_dir = dirname($log_file);
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents(
        $log_file, 
        date('[Y-m-d H:i:s] ') . "Expired {$expired_count} transaction(s)\n", 
        FILE_APPEND
    );
    
    echo "Expired {$expired_count} transaction(s)\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
