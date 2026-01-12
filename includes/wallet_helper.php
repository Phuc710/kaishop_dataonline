<?php
/**
 * Single Wallet System
 * Lưu balance bằng VND làm chuẩn, convert sang USD khi hiển thị
 */

/**
 * Get exchange rate from database
 */
function getExchangeRate() {
    global $pdo;
    static $rate = null;
    
    if ($rate === null) {
        try {
            $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='exchange_rate'");
            $rate = floatval($stmt->fetchColumn() ?? 25000);
        } catch (Exception $e) {
            $rate = 25000;
        }
    }
    
    return $rate;
}

/**
 * Get user currency preference
 */
function getUserCurrency() {
    return $_COOKIE['currency'] ?? 'VND';
}

/**
 * Format balance for display
 * @param float $balanceVND Balance stored in VND
 * @param string $currency Display currency
 */
function formatBalance($balanceVND, $currency = 'VND') {
    $balanceVND = floatval($balanceVND);
    
    if ($currency === 'USD') {
        $usd = $balanceVND / getExchangeRate();
        return '$' . number_format($usd, 2, '.', ',');
    }
    
    return number_format($balanceVND, 0, ',', '.') . 'đ';
}

/**
 * Format amount for display
 */
function formatAmount($amountVND, $currency = 'VND') {
    $amountVND = floatval($amountVND);
    
    if ($currency === 'USD') {
        $usd = $amountVND / getExchangeRate();
        return '$' . number_format($usd, 2, '.', ',');
    }
    
    return number_format($amountVND, 0, ',', '.') . 'đ';
}

/**
 * Get balance value in specified currency
 */
function getBalanceValue($balanceVND, $currency = 'VND') {
    $balanceVND = floatval($balanceVND);
    
    if ($currency === 'USD') {
        return $balanceVND / getExchangeRate();
    }
    
    return $balanceVND;
}

/**
 * Convert to VND (storage format)
 */
function convertToVND($amount, $fromCurrency = 'VND') {
    $amount = floatval($amount);
    
    if ($fromCurrency === 'USD') {
        return $amount * getExchangeRate();
    }
    
    return $amount;
}

/**
 * Update user balance (amount in VND)
 */
function updateUserBalance($userId, $amountVND, $pdo) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET balance_vnd = balance_vnd + ? WHERE id = ?");
        return $stmt->execute([$amountVND, $userId]);
    } catch (Exception $e) {
        error_log("Update balance error: " . $e->getMessage());
        return false;
    }
}
