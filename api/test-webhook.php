<?php
/**
 * Webhook Test Script - Simulate Sepay Webhook
 * Usage: php test-webhook.php [transaction_code] [amount]
 * 
 * Example: php test-webhook.php kaiSMVZDH11RBKYP15 20000
 */

// Get parameters
$transaction_code = $argv[1] ?? 'kaiTEST123456789AB';
$amount = $argv[2] ?? 20000;

// Build test payload
$payload = [
    'id' => 'TEST_' . time(),
    'amount' => (int) $amount,
    'content' => $transaction_code,
    'account_number' => '09696969690',
    'transfer_type' => 'in',
    'transaction_date' => date('Y-m-d H:i:s'),
    'gate' => 'MB',
    'bank_brand_name' => 'MB Bank'
];

echo "=== WEBHOOK TEST ===\n";
echo "Transaction Code: $transaction_code\n";
echo "Amount: " . number_format($amount) . " VND\n";
echo "Payload: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

// Send to webhook
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost/kaishop/api/sepay-webhook.php',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10
]);

$start = microtime(true);
$response = curl_exec($ch);
$time = round((microtime(true) - $start) * 1000);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "=== RESPONSE ===\n";
echo "HTTP Code: $httpCode\n";
echo "Time: {$time}ms\n";
echo "Response: $response\n";

// Parse and display result
$result = json_decode($response, true);
if ($result && $result['success']) {
    echo "\n✅ SUCCESS!\n";
} else {
    echo "\n❌ FAILED: " . ($result['message'] ?? 'Unknown error') . "\n";
}
