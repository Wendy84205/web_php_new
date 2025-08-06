<?php
// init.php - Initialize application

// Load configuration
require_once __DIR__ . '/config.php';

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400, // 1 day
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// SỬA LẠI ĐƯỜNG DẪN NÀY:
// Include common functions
require_once __DIR__ . '/functions.php';  // Thêm functions.php
require_once __DIR__ . '/auth.php';       // Sửa đường dẫn đúng


// Check for maintenance mode
try {
    if (getSetting('maintenance_mode', '0') === '1' && !isAdmin()) {
        http_response_code(503);
        header('Retry-After: 3600'); // Retry after 1 hour
        require __DIR__ . '/../views/maintenance.php';
        exit();
    }
} catch (Exception $e) {
    error_log("Maintenance mode check error: " . $e->getMessage());
}

// CSRF protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate required PHP extensions
$requiredExtensions = ['pdo_mysql', 'gd', 'mbstring', 'openssl'];
$missingExtensions = array_filter($requiredExtensions, fn($ext) => !extension_loaded($ext));

if ($missingExtensions) {
    header('Content-Type: text/plain; charset=UTF-8');
    http_response_code(500);
    exit("FATAL: Missing required PHP extensions:\n- " . implode("\n- ", $missingExtensions));
}

// Set default timezone
date_default_timezone_set(ini_get('date.timezone') ?: 'Asia/Ho_Chi_Minh');

// Error handling configuration
if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// Custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $errorTypes = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_USER_ERROR => 'User Error',
    ];
    
    $type = $errorTypes[$errno] ?? 'Unknown Error';
    $message = "[$type] $errstr in $errfile on line $errline";
    
    error_log($message);
    
    if (APP_ENV === 'production' && in_array($errno, [E_ERROR, E_USER_ERROR, E_PARSE])) {
        http_response_code(500);
        require __DIR__ . '/../views/500.php';
        exit();
    }
    
    // In development, let PHP handle error display
    return false;
});

// Exception handler
set_exception_handler(function($exception) {
    error_log("Uncaught Exception: " . $exception->getMessage() . 
              " in " . $exception->getFile() . 
              " on line " . $exception->getLine());
    
    if (APP_ENV === 'production') {
        http_response_code(500);
        require __DIR__ . '/../views/500.php';
    } else {
        echo "<h1>Uncaught Exception</h1>";
        echo "<p><strong>Type:</strong> " . get_class($exception) . "</p>";
        echo "<p><strong>Message:</strong> " . $exception->getMessage() . "</p>";
        echo "<p><strong>File:</strong> " . $exception->getFile() . "</p>";
        echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
        echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
    }
    exit(1);
});

// Fatal error handler
register_shutdown_function(function() {
    $error = error_get_last();
    $fatalErrors = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    
    if ($error && in_array($error['type'], $fatalErrors)) {
        error_log("Fatal error: " . $error['message'] . 
                  " in " . $error['file'] . 
                  " on line " . $error['line']);
        
        if (APP_ENV === 'production') {
            http_response_code(500);
            require __DIR__ . '/../views/500.php';
        } else {
            echo "<h1>Fatal Error</h1>";
            echo "<p><strong>Type:</strong> " . $error['type'] . "</p>";
            echo "<p><strong>Message:</strong> " . $error['message'] . "</p>";
            echo "<p><strong>File:</strong> " . $error['file'] . "</p>";
            echo "<p><strong>Line:</strong> " . $error['line'] . "</p>";
        }
        exit(1);
    }
});

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
if (isset($_SERVER['HTTPS'])) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}