<?php
/**
 * Get user avatar URL
 * Returns Google avatar if available, otherwise default image
 * 
 * @param array|null $user User array with 'avatar' and 'role' keys  
 * @param string|null $avatar Avatar string
 * @param string|null $role User role
 * @return string Avatar URL
 */
function getUserAvatar($user = null, $avatar = null, $role = null)
{
    // Extract from user array if provided
    if (is_array($user)) {
        $avatar = $avatar ?? ($user['avatar'] ?? null);
        $role = $role ?? ($user['role'] ?? 'user');
    }

    // If avatar is a URL (Google avatar), return it
    if (!empty($avatar) && filter_var($avatar, FILTER_VALIDATE_URL)) {
        return $avatar;
    }

    // If avatar is a local filename (uploaded), return local URL
    if (!empty($avatar) && !filter_var($avatar, FILTER_VALIDATE_URL)) {
        return asset('images/uploads/' . $avatar);
    }

    // Default avatar based on role
    $defaultAvatar = ($role === 'admin') ? 'admin.png' : 'user.png';
    return asset('images/' . $defaultAvatar);
}

/**
 * Helper Functions
 * Các hàm tiện ích sử dụng trong toàn hệ thống
 */

/**
 * Kiểm tra user đã đăng nhập chưa
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/**
 * Kiểm tra user có phải admin không
 */
function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Redirect đến URL
 */
function redirect($url)
{
    header("Location: " . $url);
    exit;
}

/**
 * Lấy thông tin user hiện tại
 */
function getCurrentUser()
{
    global $pdo;
    if (!isLoggedIn()) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
| * Format giá tiền VND
| */
function formatVND($amount)
{
    $currency = $_COOKIE['currency'] ?? 'VND';
    if ($currency === 'USD') {
        // Chuyển sang USD nếu chọn USD
        $usd = $amount / EXCHANGE_RATE;
        $formatted = number_format($usd, 2, '.', ',');
        // Loại bỏ .00 nếu là số nguyên
        $formatted = rtrim(rtrim($formatted, '0'), '.');
        return '$' . $formatted;
    }
    return number_format($amount, 0, ',', '.') . 'đ';
}

/**
 * Format tiền tệ theo ngôn ngữ
 */
function formatCurrency($amount)
{
    return formatVND($amount);
}

/**
 * Format giá tiền USD
 */
function formatUSD($amount)
{
    // Bỏ .00 nếu là số nguyên, giữ số thập phân nếu có
    $formatted = number_format($amount, 2, '.', ',');
    // Loại bỏ .00 thừa
    $formatted = rtrim(rtrim($formatted, '0'), '.');
    return '$' . $formatted;
}

/**
 * Format giá theo loại tiền tệ
 */
function formatPrice($amount, $currency = 'VND')
{
    if ($currency === 'USD') {
        return formatUSD($amount);
    }
    return formatVND($amount);
}

/**
 * Chuyển đổi VND sang USD
 */
function vndToUsd($amount)
{
    return $amount / EXCHANGE_RATE;
}

/**
 * Chuyển đổi USD sang VND
 */
function usdToVnd($amount)
{
    return $amount * EXCHANGE_RATE;
}

/**
 * Escape HTML
 */
function e($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Tạo slug từ chuỗi tiếng Việt
 */
function createSlug($string)
{
    $unicode = [
        'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
        'd' => 'đ',
        'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
        'i' => 'í|ì|ỉ|ĩ|ị',
        'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
        'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
        'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
    ];

    $string = strtolower($string);
    foreach ($unicode as $nonUnicode => $uni) {
        $string = preg_replace("/($uni)/i", $nonUnicode, $string);
    }

    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    $string = trim($string, '-');

    return $string;
}

/**
 * Lấy IP address của client
 */
function getClientIP()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $_SERVER['REMOTE_ADDR'];
}

/**
 * Detect country từ IP address
 */
function getCountryFromIP($ip = null)
{
    if ($ip === null) {
        $ip = getClientIP();
    }

    // Convert ::1 to 127.0.0.1
    if ($ip === '::1') {
        $ip = '127.0.0.1';
    }

    // Nếu là localhost/private IP
    if (in_array($ip, ['127.0.0.1']) || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
        return 'VN'; // Default localhost = VN
    }

    // Dùng API miễn phí ip-api.com (giới hạn 45 requests/minute)
    try {
        $url = "http://ip-api.com/json/{$ip}?fields=countryCode";
        $context = stream_context_create(['http' => ['timeout' => 2]]); // Timeout 2s
        $response = @file_get_contents($url, false, $context);

        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['countryCode'])) {
                return $data['countryCode']; // VN, US, CN, etc.
            }
        }
    } catch (Exception $e) {
        // Nếu lỗi thì trả về null
    }

    return null;
}

/**
 * Format thời gian
 */
function timeAgo($datetime)
{
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60)
        return 'Vừa xong';
    if ($diff < 3600)
        return floor($diff / 60) . ' phút trước';
    if ($diff < 86400)
        return floor($diff / 3600) . ' giờ trước';
    if ($diff < 604800)
        return floor($diff / 86400) . ' ngày trước';

    return date('d/m/Y H:i', $timestamp);
}

/**
 * Validate email
 */
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate số điện thoại Việt Nam
 */
function isValidPhone($phone)
{
    return preg_match('/^(0|\+84)[0-9]{9,10}$/', $phone);
}

/**
 * Hash password
 */
function hashPassword($password)
{
    return password_hash($password, HASH_ALGO, ['cost' => HASH_COST]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

/**
 * Tạo token ngẫu nhiên
 */
function generateToken($length = 32)
{
    return bin2hex(random_bytes($length));
}

/**
 * Upload file ảnh
 */
function uploadImage($file, $folder = 'products')
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return false;
    }

    $filename = uniqid() . '_' . time() . '.' . $extension;
    $uploadPath = UPLOAD_DIR . $folder . '/';

    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }

    $destination = $uploadPath . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $folder . '/' . $filename;
    }

    return false;
}

/**
 * Xóa file ảnh
 */
function deleteImage($path)
{
    $fullPath = UPLOAD_DIR . $path;
    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}

/**
 * Lấy URL đầy đủ
 */
function url($path = '')
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Lấy URL asset
 */
function asset($path = '')
{
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

/**
 * Tạo CSRF token
 */
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Flash message
 */
function setFlash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get flash message
 */
function getFlash()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Kiểm tra có flash message không
 */
function hasFlash()
{
    return isset($_SESSION['flash']);
}

/**
 * Generate Snowflake ID
 */
function generateSnowflakeId()
{
    return Snowflake::generateId();
}

/**
 * Generate Short ID (8 digits)
 */
function generateShortId()
{
    return str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
}

/**
 * Get Real IP Address (works with proxy/cloudflare/load balancer)
 */
function getRealIpAddress()
{
    $ip = '';

    // Check for shared internet/proxy
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    // Check for proxy
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Can be comma separated list, get first IP
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    }
    // Check for Cloudflare
    elseif (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    // Check for other proxy headers
    elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    }
    // Default to REMOTE_ADDR
    else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    // Validate IP
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = '0.0.0.0';
    }

    // Convert ::1 to 127.0.0.1 for localhost
    if ($ip === '::1') {
        $ip = '127.0.0.1';
    }

    return $ip;
}

/**
 * Log system activity
 */
function logActivity($log_type, $action, $description = '', $old_value = '', $new_value = '', $user_id = null, $admin_id = null)
{
    global $pdo;

    $log_id = generateShortId();
    $ip = getRealIpAddress();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $country = getCountryFromIP($ip); // Detect country

    if (!$user_id && isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
    }

    if (!$admin_id && isAdmin()) {
        $admin_id = $_SESSION['user_id'];
    }

    $stmt = $pdo->prepare("INSERT INTO system_logs (id, log_type, user_id, admin_id, action, description, old_value, new_value, ip_address, country, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$log_id, $log_type, $user_id, $admin_id, $action, $description, $old_value, $new_value, $ip, $country, $user_agent]);

    return $log_id;
}

/**
 * Log balance transaction
 */
function logBalanceTransaction($user_id, $type, $currency, $amount, $balance_before, $balance_after, $note = '', $admin_id = null)
{
    global $pdo;

    $tx_id = generateShortId();
    $ip = getRealIpAddress();

    if (!$admin_id && isAdmin()) {
        $admin_id = $_SESSION['user_id'];
    }

    $stmt = $pdo->prepare("INSERT INTO balance_transactions (id, user_id, admin_id, type, currency, amount, balance_before, balance_after, note, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$tx_id, $user_id, $admin_id, $type, $currency, $amount, $balance_before, $balance_after, $note, $ip]);

    // Also log to system logs
    logActivity('balance', "Balance {$type}", "User: {$user_id} | Amount: {$amount} {$currency} | Note: {$note}", $balance_before, $balance_after, $user_id, $admin_id);

    return $tx_id;
}

/**
 * Get user login history
 */
function getUserLoginHistory($user_id, $limit = 50)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM system_logs WHERE log_type='user_login' AND user_id=? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

/**
 * Get user balance transactions
 */
function getUserBalanceTransactions($user_id, $limit = 50)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM balance_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

/**
 * Get system setting
 */
function get_setting($key, $default = null)
{
    global $pdo;
    static $settings_cache = null;

    if ($settings_cache === null) {
        $settings_cache = [];
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings_cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            // Silently fail or log
        }
    }

    return $settings_cache[$key] ?? $default;
}
?>