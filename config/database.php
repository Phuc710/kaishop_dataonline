<?php
/**
 * Cấu hình Database
 * Kai Shop - Website bán tài khoản
 */

// Load env function if not already loaded
if (!function_exists('env')) {
    function env($key, $default = null)
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}


try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}
