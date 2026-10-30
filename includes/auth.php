<?php
/**
 * StudentHub Portal - Authentication & RBAC Engine
 * Practical 9, 10
 */

require_once __DIR__ . '/db.php';

/**
 * Check if a user is currently authenticated
 */
function is_logged_in(): bool {
    if (!empty($_SESSION['user_id'])) {
        return true;
    }
    // Attempt Remember-Me auto-login
    return check_remember_me();
}

/**
 * Get the currently logged-in user details
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'       => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'email'    => $_SESSION['email'] ?? '',
        'role'     => $_SESSION['role'] ?? 'guest',
        'status'   => $_SESSION['status'] ?? 'active'
    ];
}

/**
 * Check if the authenticated user is an Admin
 */
function is_admin(): bool {
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin';
}

/**
 * Guard: Require login or redirect
 */
function require_login(string $redirect_to = 'login.php'): void {
    if (!is_logged_in()) {
        set_flash('warning', 'Please sign in to access that page.');
        header("Location: $redirect_to");
        exit();
    }
}

/**
 * Guard: Require Admin role
 */
function require_admin(): void {
    require_login();
    if (!is_admin()) {
        log_audit($_SESSION['user_id'] ?? null, $_SESSION['username'] ?? 'Unknown', 'Unauthorized access attempt to admin area blocked');
        set_flash('danger', 'Access denied. Administrator privileges required.');
        header("Location: student_dashboard.php");
        exit();
    }
}

/**
 * Guard: Require Student role
 */
function require_student(): void {
    require_login();
    if (is_admin()) {
        // Admins can view or be redirected
        header("Location: admin_dashboard.php");
        exit();
    }
}

/**
 * Authenticate User
 */
function login_user(string $identity, string $password, bool $remember = false): array {
    global $pdo;
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection unavailable.'];
    }

    try {
        $stmt = $pdo->prepare("SELECT id, username, email, password, role, status FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$identity, $identity]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid username/email or password.'];
        }

        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Your account is deactivated. Please contact an administrator.'];
        }

        if (!password_verify($password, $user['password'])) {
            log_audit($user['id'], $user['username'], 'Failed login attempt (password mismatch)');
            return ['success' => false, 'message' => 'Invalid username/email or password.'];
        }

        // Session ID regeneration against session fixation (Practical 10)
        session_regenerate_id(true);

        $_SESSION['user_id']       = (int)$user['id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['email']         = $user['email'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['status']        = $user['status'];
        $_SESSION['last_activity'] = time();
        $_SESSION['session_id_display'] = session_id();

        // Update last_login timestamp
        $update_stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $update_stmt->execute([$user['id']]);

        // Audit Log
        log_audit($user['id'], $user['username'], 'Successful login (Role: ' . $user['role'] . ')');

        // Remember-Me Token Handler (Practical 10)
        if ($remember) {
            issue_remember_token((int)$user['id']);
        }

        return [
            'success' => true,
            'role'    => $user['role'],
            'message' => 'Welcome back, ' . htmlspecialchars($user['username']) . '!'
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Authentication error: ' . $e->getMessage()];
    }
}

/**
 * Issue a secure Remember-Me cookie and store hash in DB
 */
function issue_remember_token(int $user_id): void {
    global $pdo;
    if (!$pdo) return;

    $raw_token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $raw_token);
    $expires = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30 days

    try {
        $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $token_hash, $expires]);

        // Store user_id|raw_token in cookie
        $cookie_val = $user_id . ':' . $raw_token;
        setcookie('studenthub_remember', $cookie_val, [
            'expires'  => time() + (86400 * 30),
            'path'     => '/',
            'httponly' => true,
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'samesite' => 'Lax'
        ]);
    } catch (Exception $e) {
        error_log("Failed to issue remember token: " . $e->getMessage());
    }
}

/**
 * Check Remember-Me cookie and auto-login
 */
function check_remember_me(): bool {
    global $pdo;
    if (empty($_COOKIE['studenthub_remember']) || !$pdo) {
        return false;
    }

    $parts = explode(':', $_COOKIE['studenthub_remember'], 2);
    if (count($parts) !== 2) return false;

    [$user_id, $raw_token] = $parts;
    $token_hash = hash('sha256', $raw_token);

    try {
        $stmt = $pdo->prepare("SELECT t.id, u.id as uid, u.username, u.email, u.role, u.status 
                               FROM remember_tokens t 
                               JOIN users u ON t.user_id = u.id 
                               WHERE t.user_id = ? AND t.token_hash = ? AND t.expires_at > NOW() AND u.status = 'active'
                               LIMIT 1");
        $stmt->execute([(int)$user_id, $token_hash]);
        $user = $stmt->fetch();

        if ($user) {
            session_regenerate_id(true);
            $_SESSION['user_id']       = (int)$user['uid'];
            $_SESSION['username']      = $user['username'];
            $_SESSION['email']         = $user['email'];
            $_SESSION['role']          = $user['role'];
            $_SESSION['status']        = $user['status'];
            $_SESSION['last_activity'] = time();
            $_SESSION['session_id_display'] = session_id();

            log_audit($user['uid'], $user['username'], 'Automatic login via Remember-Me token');
            return true;
        }
    } catch (Exception $e) {
        error_log("Remember-me check error: " . $e->getMessage());
    }
    return false;
}

/**
 * Terminate Session & Remember Me Cookie
 */
function logout_user(): void {
    global $pdo;
    $user = current_user();
    if ($user) {
        log_audit($user['id'], $user['username'], 'User logged out');
        if (!empty($_COOKIE['studenthub_remember']) && $pdo) {
            $parts = explode(':', $_COOKIE['studenthub_remember'], 2);
            if (count($parts) === 2) {
                $token_hash = hash('sha256', $parts[1]);
                try {
                    $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ? AND token_hash = ?");
                    $stmt->execute([(int)$parts[0], $token_hash]);
                } catch (Exception $e) {
                    // Ignore
                }
            }
        }
    }

    // Clear cookie
    if (isset($_COOKIE['studenthub_remember'])) {
        setcookie('studenthub_remember', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'samesite' => 'Lax'
        ]);
        unset($_COOKIE['studenthub_remember']);
    }

    // Destroy session
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
