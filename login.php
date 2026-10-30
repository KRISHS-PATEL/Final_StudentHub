<?php
/**
 * StudentHub Portal - Secure Login & Authentication
 * Practical 10: Sessions, RBAC, Timeout, and Remember-Me Token
 */

$page_title = "Sign In";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// If already authenticated, redirect based on role
if (is_logged_in()) {
    header("Location: " . (is_admin() ? 'admin_dashboard.php' : 'student_dashboard.php'));
    exit();
}

$error = '';
$username_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = "Invalid session token. Please try again.";
    } else {
        $username_val = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (empty($username_val) || empty($password)) {
            $error = "Please enter both username/email and password.";
        } else {
            $result = login_user($username_val, $password, $remember);

            if ($result['success']) {
                set_flash('success', $result['message']);
                if ($result['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: student_dashboard.php");
                }
                exit();
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>

<!-- Breadcrumbs (Practical 2) -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb py-2 px-3 bg-body-tertiary rounded-3 shadow-sm">
    <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house-door me-1"></i>Home</a></li>
    <li class="breadcrumb-item active" aria-current="page">Portal Login</li>
  </ol>
</nav>

<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-5">
      <div class="card-header bg-primary text-white p-4 text-center">
        <div class="display-6 mb-2"><i class="bi bi-shield-lock-fill"></i></div>
        <h1 class="h4 fw-bold mb-1">StudentHub Authentication</h1>
        <p class="mb-0 opacity-75 small">Practical 10: Secure Sessions &amp; Role-Based Access</p>
      </div>

      <div class="card-body p-4 p-md-5 bg-body">
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo e($error); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <!-- Demo Credentials Helper Callout -->
        <div class="alert alert-info py-2 px-3 small rounded-3 mb-4">
          <div class="fw-bold mb-1"><i class="bi bi-key-fill me-1"></i>Demo Accounts for Evaluation:</div>
          <div><strong>Admin:</strong> <code>admin</code> | <strong>Pass:</strong> <code>Admin@123</code></div>
          <div><strong>Student:</strong> <code>aarav_sharma</code> | <strong>Pass:</strong> <code>Student@123</code></div>
        </div>

        <form method="POST" action="login.php" class="needs-validation">
          <?php echo csrf_field(); ?>

          <div class="mb-3">
            <label for="username" class="form-label fw-semibold">Username or University Email</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-person"></i></span>
              <input type="text" id="username" name="username" class="form-control" 
                     placeholder="e.g. admin or aarav_sharma" value="<?php echo e($username_val); ?>" required autofocus>
            </div>
          </div>

          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label for="password" class="form-label fw-semibold mb-0">Password</label>
            </div>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input type="password" id="password" name="password" class="form-control" 
                     placeholder="Enter your account password" required>
            </div>
          </div>

          <!-- Remember-Me Checkbox (Practical 10) -->
          <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" id="remember" name="remember">
            <label class="form-check-label small" for="remember">
              Remember me on this computer (Secure 30-day hashed token)
            </label>
          </div>

          <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary btn-lg fw-semibold shadow-sm">
              <i class="bi bi-box-arrow-in-right me-2"></i>Sign In to Portal
            </button>
          </div>

          <div class="text-center small text-muted">
            New to CHARUSAT StudentHub? <a href="register.php" class="fw-semibold">Create an Account</a>
          </div>
        </form>
      </div>

      <div class="card-footer bg-body-tertiary text-center py-3 small text-muted border-top">
        <i class="bi bi-lock-fill text-success me-1"></i>Protected by Bcrypt Hashing, Session Timeout &amp; CSRF Defense
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
