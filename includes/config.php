<?php
// config.php - Application configuration settings

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Application settings
define('APP_NAME', 'Nhà Hàng Cơm Niêu');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development'); // 'production' or 'development'

// Base paths
define('BASE_URL', 'http://localhost/com-nieu');
define('BASE_PATH', __DIR__ . '/..');

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Session settings
session_set_cookie_params([
    'lifetime' => 86400, // 1 day
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment variables if using .env
if (file_exists(BASE_PATH . '/.env')) {
    $env = parse_ini_file(BASE_PATH . '/.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

// Include other common files
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
