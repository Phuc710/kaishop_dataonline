<?php
/**
 * Google OAuth Login Handler
 * Handles Google Sign-In authentication with Firebase ID tokens
 * 
 * @package KaiShop
 * @subpackage Authentication
 * @version 2.0.0
 */

require_once __DIR__ . '/../config/config.php';

// Set JSON response header
header('Content-Type: application/json; charset=UTF-8');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

/**
 * Send JSON response and exit
 * 
 * @param int $httpCode HTTP status code
 * @param array $data Response data
 */
function sendJsonResponse($httpCode, $data)
{
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send error response
 * 
 * @param int $httpCode HTTP status code
 * @param string $errorCode Error code
 * @param string $errorMessage Error message
 * @param array $additionalData Additional data to include
 */
function sendErrorResponse($httpCode, $errorCode, $errorMessage, $additionalData = [])
{
    sendJsonResponse($httpCode, array_merge([
        'success' => false,
        'error' => [
            'code' => $errorCode,
            'message' => $errorMessage
        ]
    ], $additionalData));
}

/**
 * Send success response
 * 
 * @param array $data Success data
 */
function sendSuccessResponse($data)
{
    sendJsonResponse(200, array_merge([
        'success' => true
    ], $data));
}

// =============================================================================
// Request Validation
// =============================================================================

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse(405, 'METHOD_NOT_ALLOWED', 'Only POST requests are allowed');
}

// Parse request body
$requestBody = file_get_contents('php://input');
$requestData = json_decode($requestBody, true);

if ($requestData === null || json_last_error() !== JSON_ERROR_NONE) {
    sendErrorResponse(400, 'INVALID_JSON', 'Invalid JSON request format', [
        'json_error' => json_last_error_msg()
    ]);
}

// Validate required fields
$requiredFields = ['idToken', 'displayName', 'photoURL'];
$missingFields = array_filter($requiredFields, function ($field) use ($requestData) {
    return !isset($requestData[$field]) || trim($requestData[$field]) === '';
});

if (!empty($missingFields)) {
    sendErrorResponse(400, 'MISSING_REQUIRED_FIELDS', 'Required fields are missing', [
        'missing_fields' => array_values($missingFields)
    ]);
}

// Extract and sanitize input
$firebaseIdToken = trim($requestData['idToken']);
$displayName = trim($requestData['displayName']);
$photoURL = trim($requestData['photoURL']);
$clientEmail = isset($requestData['email']) ? trim($requestData['email']) : null;



// =============================================================================
// Firebase Token Validation
// =============================================================================

$tokenValidation = GoogleFirebaseTokenValidator::validateFirebaseIdToken($firebaseIdToken);

if ($tokenValidation === false) {
    sendErrorResponse(401, 'INVALID_ID_TOKEN', 'Firebase authentication token validation failed');
}

// Extract user data from validated token
$firebaseUid = $tokenValidation['firebase_uid'];
$email = $tokenValidation['email'];
$emailVerified = $tokenValidation['email_verified'];
$name = $displayName ?? $tokenValidation['name'];
$picture = $photoURL ?? $tokenValidation['picture'];

// Validate email exists
if (empty($email)) {
    sendErrorResponse(400, 'EMAIL_REQUIRED', 'Email address is required for authentication');
}

// =============================================================================
// Database Operations
// =============================================================================

try {
    $ipAddress = AuthenticationLogger::getClientIpAddress();
    $logger = new AuthenticationLogger($pdo);

    // Check if user exists
    $stmt = $pdo->prepare("
        SELECT id, username, email, role, is_active, password
        FROM users
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        // =====================================================================
        // Existing User Login
        // =====================================================================

        // Check if account is active
        if (!$existingUser['is_active']) {
            sendErrorResponse(403, 'ACCOUNT_INACTIVE', 'Your account has been deactivated. Please contact support.');
        }

        // Update user profile with latest Google data
        $updateStmt = $pdo->prepare("
            UPDATE users
            SET 
                last_login = NOW(),
                full_name = COALESCE(NULLIF(?, ''), full_name),
                avatar = COALESCE(NULLIF(?, ''), avatar),
                updated_at = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([$name, $picture, $existingUser['id']]);

        // Create session
        $_SESSION['user_id'] = $existingUser['id'];
        $_SESSION['username'] = $existingUser['username'];
        $_SESSION['role'] = $existingUser['role'];
        $_SESSION['login_method'] = 'google_oauth';
        $_SESSION['login_time'] = time();

        // Log successful login
        $logger->logSuccessfulLogin(
            $existingUser['id'],
            $existingUser['username'],
            $ipAddress,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            'google_oauth_existing'
        );

        // Send success response
        sendSuccessResponse([
            'message' => 'Login successful',
            'is_existing_user' => true,
            'user' => [
                'id' => $existingUser['id'],
                'username' => $existingUser['username'],
                'email' => $email,
                'role' => $existingUser['role']
            ]
        ]);

    } else {
        // =====================================================================
        // New User Registration
        // =====================================================================

        // Generate unique username from email
        $baseUsername = preg_replace('/[^a-z0-9_]/i', '', explode('@', $email)[0]);
        $username = $baseUsername;
        $counter = 1;

        // Ensure username is unique
        while (true) {
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $checkStmt->execute([$username]);

            if (!$checkStmt->fetch()) {
                break; // Username is available
            }

            $username = $baseUsername . $counter;
            $counter++;

            // Prevent infinite loop
            if ($counter > 999) {
                sendErrorResponse(500, 'USERNAME_GENERATION_FAILED', 'Unable to generate unique username');
            }
        }

        // Determine user role (first user is admin)
        $userCountStmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $userCount = $userCountStmt->fetch(PDO::FETCH_ASSOC)['total'];
        $userRole = ($userCount == 0) ? 'admin' : 'user';

        // Generate user ID
        $newUserId = generateShortId();

        // Create user account
        $insertStmt = $pdo->prepare("
            INSERT INTO users (
                id,
                username,
                email,
                password,
                full_name,
                avatar,
                role,
                is_active,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, NULL, ?, ?, ?, 1, NOW(), NOW())
        ");

        $insertResult = $insertStmt->execute([
            $newUserId,
            $username,
            $email,
            $name,
            $picture,
            $userRole
        ]);

        if (!$insertResult) {
            sendErrorResponse(500, 'USER_CREATION_FAILED', 'Failed to create user account');
        }

        // Send welcome email
        try {
            EmailSender::sendWelcomeEmail([
                'username' => $username,
                'email' => $email
            ]);
        } catch (Exception $emailError) {
            // Log but don't fail registration if email fails
            error_log('[Google Login] Welcome email failed: ' . $emailError->getMessage());
        }

        // Create session
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $userRole;
        $_SESSION['login_method'] = 'google_oauth';
        $_SESSION['login_time'] = time();

        // Log registration
        $logger->logUserRegistration(
            $newUserId,
            $username,
            $email,
            $ipAddress,
            'google_oauth'
        );

        // Send success response
        sendSuccessResponse([
            'message' => 'Account created successfully',
            'is_new_user' => true,
            'user' => [
                'id' => $newUserId,
                'username' => $username,
                'email' => $email,
                'role' => $userRole
            ]
        ]);
    }

} catch (PDOException $dbError) {
    error_log('[Google Login] Database error: ' . $dbError->getMessage());
    sendErrorResponse(500, 'DATABASE_ERROR', 'A database error occurred. Please try again later.');

} catch (Exception $generalError) {
    error_log('[Google Login] Unexpected error: ' . $generalError->getMessage());
    sendErrorResponse(500, 'INTERNAL_ERROR', 'An unexpected error occurred. Please try again later.');
}
