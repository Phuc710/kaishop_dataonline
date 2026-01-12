<?php
/**
 * Martin Shop - Main Index File & Router
 */

// Load configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Snowflake.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/EmailSender.php';

// Get request URI
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = dirname($_SERVER['SCRIPT_NAME']);
$base_path = rtrim($script_name, '/');

// Remove base path from request URI (only from the beginning)
$url_path = parse_url($request_uri, PHP_URL_PATH);
if (strpos($url_path, $base_path) === 0) {
    $path = substr($url_path, strlen($base_path));
} else {
    $path = $url_path;
}
$path = trim($path, '/');

// Get query parameters
$query_string = parse_url($request_uri, PHP_URL_QUERY);
parse_str($query_string ?? '', $params);

// Simple routing
$routes = [
    '' => 'trangchu/index.php',
    'trangchu' => 'trangchu/index.php',

    'dangnhap' => 'auth/index.php',
    'dangky' => 'auth/index.php?tab=register',
    'auth' => 'auth/index.php',
    'auth/forgot-password' => 'auth/forgot-password.php',
    'dangxuat' => 'dangxuat.php',

    'sanpham' => 'sanpham/index.php',
    'sanpham/view' => 'sanpham/view.php',

    'chinhsach' => 'chinhsach/index.php',

    'giohang' => 'giohang/index.php',
    'giohang/add' => 'giohang/add.php',
    'giohang/remove' => 'giohang/remove.php',
    'giohang/update' => 'giohang/update.php',
    'giohang/count' => 'giohang/count.php',

    'thanhtoan' => 'thanhtoan/index.php',
    'thanhtoan/process' => 'thanhtoan/process.php',

    'naptien' => 'naptien/index.php',

    'user' => 'user/index.php',
    'user/profile' => 'user/profile.php',
    'user/orders' => 'user/orders.php',
    'user/tickets' => 'user/tickets.php',
    'user/settings' => 'user/settings.php',

    'ticket' => 'ticket/index.php',
    'ticket/create' => 'ticket/create.php',
    'ticket/view' => 'ticket/view.php',

    'admin' => 'admin/index.php',
    'admin/dashboard' => 'admin/dashboard.php',
    'admin/products' => 'admin/products.php',
    'admin/products/add' => 'admin/products/add.php',
    'admin/products/edit' => 'admin/products/edit.php',
    'admin/products/delete' => 'admin/products/delete.php',
    'admin/users' => 'admin/users.php',
    'admin/orders' => 'admin/orders.php',
    'admin/notifications' => 'admin/notifications.php',
    'admin/settings' => 'admin/settings.php',

    'api/products/stock' => 'api/stock.php',
    'api/upload' => 'api/upload_image.php',
];

// Check if route exists
if (isset($routes[$path])) {
    $file = __DIR__ . '/' . $routes[$path];
    if (file_exists($file)) {
        require_once $file;
    } else {
        http_response_code(404);
        require_once __DIR__ . '/404.php';
    }
} else {
    // Check for product slug route: sanpham/{slug}
    if (preg_match('/^sanpham\/([a-z0-9_\-]+)$/', $path, $matches)) {
        $_GET['slug'] = $matches[1];
        require_once __DIR__ . '/sanpham/view.php';
    } else {
        // Try to find file directly
        $file = __DIR__ . '/' . $path . '.php';
        if (file_exists($file)) {
            require_once $file;
        } else {
            http_response_code(404);
            require_once __DIR__ . '/404.php';
        }
    }
}
?>