<?php
/**

 * Cách dùng: Truy cập {YOUR_DOMAIN}/sos.php
 */

require_once __DIR__ . '/config/database.php';

try {
    // Tắt maintenance mode
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = '0' WHERE setting_key = 'maintenance_mode'");
    $stmt->execute();

    echo '<!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Tắt Bảo Trì Thành Công</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: "Segoe UI", Tahoma, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .container {
                background: white;
                padding: 3rem;
                border-radius: 16px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                text-align: center;
                max-width: 500px;
                width: 90%;
            }
            .success-icon {
                width: 80px;
                height: 80px;
                background: #10b981;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 1.5rem;
                font-size: 3rem;
                color: white;
            }
            h1 {
                color: #10b981;
                margin-bottom: 1rem;
                font-size: 1.8rem;
            }
            p {
                color: #64748b;
                margin-bottom: 1.5rem;
                line-height: 1.6;
            }
            .btn {
                display: inline-block;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 1rem 2rem;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 600;
                transition: all 0.3s;
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
            }
            .warning {
                background: #fef3c7;
                border: 2px solid #f59e0b;
                padding: 1rem;
                border-radius: 8px;
                margin-top: 1.5rem;
                color: #92400e;
                font-size: 0.9rem;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="success-icon">✓</div>
            <h1>Tắt Bảo Trì Thành Công!</h1>
            <p>Chế độ bảo trì đã được <strong>TẮT</strong>.<br>Bạn có thể đăng nhập bình thường ngay bây giờ.</p>
            <a href="<?= BASE_URL ?>/auth/" class="btn">🔐 Đăng Nhập Ngay</a>
            <div class="warning">
                <strong>⚠️ Lưu ý bảo mật:</strong><br>
                Nên xóa file <code>disable_maintenance.php</code> sau khi đăng nhập để tránh lỗ hổng bảo mật!
            </div>
        </div>
    </body>
    </html>';

} catch (Exception $e) {
    echo '<!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <title>Lỗi</title>
        <style>
            body { font-family: sans-serif; padding: 2rem; background: #1e293b; color: white; }
            .error { background: #ef4444; padding: 1.5rem; border-radius: 8px; }
        </style>
    </head>
    <body>
        <div class="error">
            <h2>❌ Lỗi kết nối database</h2>
            <p>' . htmlspecialchars($e->getMessage()) . '</p>
            <p>Vui lòng kiểm tra cấu hình database trong <code>config/database.php</code></p>
        </div>
    </body>
    </html>';
}
?>