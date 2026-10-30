<?php
/**
 * StudentHub Portal - Dual PDO & MySQLi Database Connection
 * Practical 8, 9, 11, 14
 * Supports both PDO and MySQLi with Prepared Statements
 */

require_once __DIR__ . '/config.php';

$pdo = null;
$conn = null;
$db_connected = false;
$db_error = null;

try {
    // 1. PDO Connection (Object-Oriented, Prepared Statements)
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // 2. MySQLi Connection (Dual-mode for Practical 8/9/11/12 requirements)
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        throw new Exception("MySQLi Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    $db_connected = true;
} catch (Throwable $ex) {
    $db_connected = false;
    $db_error = $ex->getMessage();
}

/**
 * Audit Logging Helper (Practical 9, 10, 14)
 * Records actions into audit_logs table
 */
function log_audit(?int $user_id, ?string $username, string $action): void {
    global $pdo;
    if (!$pdo) return;
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, username, action, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $ip = get_client_ip();
        $agent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 250);
        $stmt->execute([$user_id, $username, $action, $ip, $agent]);
    } catch (Exception $e) {
        error_log("Audit log failure: " . $e->getMessage());
    }
}
