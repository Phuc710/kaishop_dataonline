<?php
/**
 * Authentication API with Cloudflare Turnstile Verification
 * Handles user login with CAPTCHA protection
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Load configuration
require_once dirname(__DIR__) . '/config/config.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$token = $input['token'] ?? '';

// Initialize LoginRateLimiter
$rateLimiter = new LoginRateLimiter($pdo);

// Validate input
if (empty($username) || empty($password) || empty($token)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin đăng nhập'
    ]);
    exit;
}

// Check rate limit BEFORE verifying credentials
$limitCheck = $rateLimiter->checkLimit($username);
if (!$limitCheck['allowed']) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => $limitCheck['message'],
        'locked_until' => $limitCheck['locked_until'] ?? null
    ]);
    exit;
}

// Apply delay if needed
if ($limitCheck['delay'] > 0) {
    sleep($limitCheck['delay']);
}

// Verify Turnstile token
if (!TurnstileVerifier::verify($token)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Xác minh Turnstile thất bại. Vui lòng thử lại.'
    ]);
    exit;
}

try {
    // Query user from database
    $stmt = $conn->prepare("
        SELECT id, username, password, email, role, status, created_at 
        FROM users 
        WHERE username = ? OR email = ?
    ");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // User not found - record failed attempt
        $rateLimiter->recordFailedAttempt($username);

        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Sai tên đăng nhập hoặc mật khẩu'
        ]);
        exit;
    }

    $user = $result->fetch_assoc();

    // Check if account is active
    if ($user['status'] !== 'active') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Tài khoản đã bị khóa hoặc chưa được kích hoạt'
        ]);
        exit;
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        // Password incorrect - record failed attempt
        $rateLimiter->recordFailedAttempt($username);

        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Sai tên đăng nhập hoặc mật khẩu'
        ]);
        exit;
    }

    // Login successful - Reset failed attempts
    $rateLimiter->resetAttempts($username);

    // Create secure session using SessionManager
    SessionManager::setUserSession([
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role']
    ]);

    // Update last login time
    $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $updateStmt->bind_param("i", $user['id']);
    $updateStmt->execute();

    // Determine redirect based on role
    $redirect = '/trangchu';
    if ($user['role'] === 'admin') {
        $redirect = '/admin';
    } elseif ($user['role'] === 'user') {
        $redirect = '/user';
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Đăng nhập thành công',
        'redirect' => BASE_URL . $redirect,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);

} catch (Exception $e) {
    error_log('[Auth] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Đã xảy ra lỗi. Vui lòng thử lại sau.'
    ]);
}
