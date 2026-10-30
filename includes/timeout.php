<?php
/**
 * StudentHub Portal - Session Inactivity Timeout Handler
 * Practical 10
 */

require_once __DIR__ . '/config.php';

if (isset($_SESSION['user_id'])) {
    $now = time();
    $last_active = $_SESSION['last_activity'] ?? $now;

    // Check timeout duration (configured in config.php: 900 seconds = 15 mins)
    if (($now - $last_active) > SESSION_TIMEOUT_SECONDS) {
        $user_id = $_SESSION['user_id'];
        $username = $_SESSION['username'] ?? 'User';

        // Destroy session safely
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        // Start new session to deliver flash message
        session_start();
        $_SESSION['flash'] = [
            'type' => 'warning',
            'message' => 'Your session timed out after 15 minutes of inactivity. Please sign in again.'
        ];

        header("Location: login.php?timeout=1");
        exit();
    }

    // Refresh last activity time
    $_SESSION['last_activity'] = $now;
}
