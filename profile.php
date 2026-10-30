<?php
/**
 * StudentHub Portal - User Profile & Security Settings
 * Practical 10 & 14
 */

$page_title = "User Profile & Security";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$user = current_user();

$errors = [];
$success_msg = '';

// Handle Profile Details Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Session security check failed. Please refresh.";
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($full_name)) {
            $errors[] = "Full Name cannot be left blank.";
        } elseif ($pdo) {
            try {
                $up = $pdo->prepare("UPDATE students SET full_name = ?, phone = ?, address = ? WHERE user_id = ? OR email = ?");
                $up->execute([$full_name, $phone, $address, $user['id'], $user['email']]);

                log_audit($user['id'], $user['username'], "Updated personal profile information");
                set_flash('success', 'Profile information updated successfully!');
                header("Location: profile.php");
                exit();
            } catch (Exception $e) {
                $errors[] = "Profile update failed: " . $e->getMessage();
            }
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Session security check failed.";
    } else {
        $old_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_new_password'] ?? '';

        if (strlen($new_pass) < 8) {
            $errors[] = "New password must be at least 8 characters long.";
        } elseif ($new_pass !== $confirm_pass) {
            $errors[] = "New password and confirmation do not match.";
        } elseif ($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$user['id']]);
                $db_pass = $stmt->fetchColumn();

                if (!password_verify($old_pass, $db_pass)) {
                    $errors[] = "Your current password was entered incorrectly.";
                } else {
                    $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                    $up_pass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $up_pass->execute([$new_hash, $user['id']]);

                    log_audit($user['id'], $user['username'], "Changed account password successfully");
                    set_flash('success', 'Account password changed successfully!');
                    header("Location: profile.php");
                    exit();
                }
            } catch (Exception $e) {
                $errors[] = "Password update failed: " . $e->getMessage();
            }
        }
    }
}

// Fetch student profile details
$student = null;
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ? OR email = ? LIMIT 1");
        $stmt->execute([$user['id'], $user['email']]);
        $student = $stmt->fetch();
    } catch (Exception $e) {
        // fallback
    }
}
?>

<!-- Breadcrumbs (Practical 2) -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb py-2 px-3 bg-body-tertiary rounded-3 shadow-sm">
    <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house-door me-1"></i>Home</a></li>
    <li class="breadcrumb-item"><a href="<?php echo is_admin() ? 'admin_dashboard.php' : 'student_dashboard.php'; ?>">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Profile Settings</li>
  </ol>
</nav>

<div class="row g-4 mb-5">
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm rounded-4 text-center p-4 bg-body">
      <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2.5rem;">
        <i class="bi bi-person-fill"></i>
      </div>
      <h2 class="h5 fw-bold mb-1"><?php echo e($student['full_name'] ?? $user['username']); ?></h2>
      <p class="text-muted small mb-2"><?php echo e($user['email']); ?></p>
      <div>
        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?> px-3 py-1 rounded-pill">
          Role: <?php echo ucfirst(e($user['role'])); ?>
        </span>
      </div>

      <hr class="my-4">

      <ul class="list-unstyled text-start small d-flex flex-column gap-2 mb-0">
        <li><strong>Username:</strong> <?php echo e($user['username']); ?></li>
        <?php if ($student): ?>
          <li><strong>Student ID:</strong> <?php echo e($student['student_id_code']); ?></li>
          <li><strong>Department:</strong> <?php echo e($student['department']); ?></li>
          <li><strong>Course:</strong> <?php echo e($student['course']); ?></li>
          <li><strong>Year:</strong> <?php echo e($student['year']); ?></li>
        <?php endif; ?>
        <li><strong>Account Status:</strong> <span class="badge bg-success">Active</span></li>
      </ul>
    </div>
  </div>

  <div class="col-lg-8">
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <ul class="mb-0 ps-3 small">
          <?php foreach ($errors as $e): ?>
            <li><?php echo e($e); ?></li>
          <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Edit Profile Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-body mb-4">
      <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
        <h3 class="h5 fw-bold mb-0 text-primary"><i class="bi bi-person-lines-fill me-2"></i>Personal Information</h3>
      </div>
      <div class="card-body p-4">
        <form method="POST" action="profile.php">
          <?php echo csrf_field(); ?>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Full Name</label>
              <input type="text" name="full_name" class="form-control" value="<?php echo e($student['full_name'] ?? $user['username']); ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Mobile Number</label>
              <input type="tel" name="phone" class="form-control" value="<?php echo e($student['phone'] ?? ''); ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Permanent / Campus Address</label>
            <textarea name="address" rows="2" class="form-control"><?php echo e($student['address'] ?? ''); ?></textarea>
          </div>
          <button type="submit" name="update_profile" class="btn btn-primary px-4 rounded-pill">
            <i class="bi bi-check-circle me-1"></i>Save Profile Changes
          </button>
        </form>
      </div>
    </div>

    <!-- Security & Password Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-body">
      <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
        <h3 class="h5 fw-bold mb-0 text-primary"><i class="bi bi-shield-lock-fill me-2"></i>Update Password</h3>
        <p class="text-muted small mb-0">Practical 9 &amp; 10: Bcrypt hash replacement</p>
      </div>
      <div class="card-body p-4">
        <form method="POST" action="profile.php">
          <?php echo csrf_field(); ?>
          <div class="mb-3">
            <label class="form-label fw-semibold">Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label fw-semibold">New Password (Min 8 chars)</label>
              <input type="password" name="new_password" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Confirm New Password</label>
              <input type="password" name="confirm_new_password" class="form-control" required>
            </div>
          </div>
          <button type="submit" name="change_password" class="btn btn-outline-primary px-4 rounded-pill">
            <i class="bi bi-key-fill me-1"></i>Update Password
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
