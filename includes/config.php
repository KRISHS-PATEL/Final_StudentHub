<?php
/**
 * StudentHub Portal - Global Configuration & Helper Utilities
 * Subject: Web Development Frameworks (ITUE203) - Practical 15
 * CHARUSAT - Faculty of Technology and Engineering (FTE)
 */

// Report errors during development, log in production
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Global Site Constants
define('APP_NAME', 'StudentHub Portal');
define('APP_VERSION', '1.0.0');
define('APP_INSTITUTE', 'CHARUSAT - FTE');
define('SESSION_TIMEOUT_SECONDS', 900); // 15 minutes (Practical 10)

// Database Configuration (Practical 8)
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'studenthub');
define('DB_PORT', 3306);

// Storage directories
define('BASE_PATH', dirname(__DIR__));
define('DATA_PATH', BASE_PATH . '/data');
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('POSTERS_PATH', UPLOADS_PATH . '/posters');

// Configure self-contained session save path
$session_dir = DATA_PATH . '/sessions';
if (!is_dir($session_dir)) {
    @mkdir($session_dir, 0777, true);
}
if (is_dir($session_dir) && is_writable($session_dir)) {
    @session_save_path($session_dir);
}

// Start secure session if not already active
if (session_status() === PHP_SESSION_NONE) {
    // Practical 10: Secure Cookie settings
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    @session_start();
}

// Generate CSRF Token if not set (Practical 7, 9)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Return CSRF HTML hidden input field
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate CSRF Token
 */
function verify_csrf(?string $token): bool {
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Sanitize text output for XSS protection
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Flash messaging system
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Get Client IP Address
 */
function get_client_ip(): string {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}
