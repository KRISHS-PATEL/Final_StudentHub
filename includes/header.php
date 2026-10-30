<?php
/**
 * StudentHub Portal - Reusable Semantic Header & Dynamic Navigation
 * Covers Practical 1, 2, 3, 4, 10, 14
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/timeout.php';

$user = current_user();
$current_page = basename($_SERVER['PHP_SELF']);
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($page_title) ? e($page_title) . ' | ' . APP_NAME : APP_NAME . ' - CHARUSAT FTE'; ?></title>
  <meta name="description" content="CHARUSAT FTE Unified StudentHub Portal integrating academic, event and administrative workflows.">
  <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- Custom Stylesheet -->
  <link href="assets/css/style.css" rel="stylesheet">

  <!-- Immediate Theme Application to prevent FOIT/FOUC -->
  <script>
    (function() {
      const saved = localStorage.getItem('studenthub_theme') || 'light';
      document.documentElement.setAttribute('data-bs-theme', saved);
    })();
  </script>
</head>
<body>
  <!-- Practical 2: Accessibility Skip to Content Link -->
  <a href="#main-content" class="skip-link">Skip to main content</a>

  <!-- Practical 1 & 14: Dynamic Role-Based Semantic Navigation -->
  <header>
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top" aria-label="Main Navigation">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
          <img src="assets/img/logo.svg" alt="StudentHub Portal Logo" class="me-2" style="height: 38px;">
        </a>

        <!-- Mobile Menu Toggle Button (Practical 4) -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain"
                aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
          <!-- Left Navigation Links -->
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
              <a class="nav-link <?php echo $current_page === 'index.php' ? 'active text-primary fw-bold' : ''; ?>" href="index.php">
                <i class="bi bi-house-door me-1"></i>Home
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo $current_page === 'about.php' ? 'active text-primary fw-bold' : ''; ?>" href="about.php">
                <i class="bi bi-info-circle me-1"></i>About
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo $current_page === 'events.php' ? 'active text-primary fw-bold' : ''; ?>" href="events.php">
                <i class="bi bi-calendar-event me-1"></i>Events
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo $current_page === 'faq.php' ? 'active text-primary fw-bold' : ''; ?>" href="faq.php">
                <i class="bi bi-question-circle me-1"></i>FAQ
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo $current_page === 'contact.php' ? 'active text-primary fw-bold' : ''; ?>" href="contact.php">
                <i class="bi bi-envelope me-1"></i>Contact
              </a>
            </li>

            <!-- Dynamic Role-Based Menu: Admin (Practical 14) -->
            <?php if ($user && $user['role'] === 'admin'): ?>
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle text-warning fw-semibold" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="bi bi-shield-lock me-1"></i>Admin Hub
                </a>
                <ul class="dropdown-menu shadow" aria-labelledby="adminDropdown">
                  <li><a class="dropdown-item" href="admin_dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</a></li>
                  <li><a class="dropdown-item" href="admin_students.php"><i class="bi bi-people me-2"></i>Manage Students (CRUD)</a></li>
                  <li><a class="dropdown-item" href="admin_events.php"><i class="bi bi-calendar-plus me-2"></i>Manage Events (Posters)</a></li>
                  <li><a class="dropdown-item" href="ajax_crud.php"><i class="bi bi-lightning-charge me-2"></i>AJAX Events (Fetch API)</a></li>
                  <li><a class="dropdown-item" href="admin_submissions.php"><i class="bi bi-file-earmark-spreadsheet me-2"></i>CSV & JSON Submissions</a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item" href="admin_audit.php"><i class="bi bi-journal-text me-2"></i>System Audit Logs</a></li>
                </ul>
              </li>
            <?php endif; ?>

            <!-- Dynamic Role-Based Menu: Student -->
            <?php if ($user && $user['role'] === 'student'): ?>
              <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'student_dashboard.php' ? 'active text-primary fw-bold' : ''; ?>" href="student_dashboard.php">
                  <i class="bi bi-mortarboard me-1"></i>My Portal
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'feedback.php' ? 'active text-primary fw-bold' : ''; ?>" href="feedback.php">
                  <i class="bi bi-star me-1"></i>Feedback
                </a>
              </li>
            <?php endif; ?>
          </ul>

          <!-- Right Navigation: Theme Switcher & Auth Actions -->
          <div class="d-flex align-items-center gap-2">
            <!-- Light / Dark Theme Switcher Button (Practical 4) -->
            <button id="themeToggleBtn" class="btn btn-outline-secondary btn-sm rounded-circle p-2" type="button" aria-label="Toggle Light and Dark Mode" title="Toggle Theme">
              <i id="themeIcon" class="bi bi-moon-stars-fill"></i>
            </button>

            <?php if ($user): ?>
              <!-- User Profile Dropdown -->
              <div class="dropdown">
                <button class="btn btn-outline-primary btn-sm dropdown-toggle d-flex align-items-center gap-2" type="button" id="userMenuBtn" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="bi bi-person-circle"></i>
                  <span><?php echo e($user['username']); ?></span>
                  <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?> small"><?php echo ucfirst(e($user['role'])); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userMenuBtn">
                  <li><h6 class="dropdown-header">Signed in as <?php echo e($user['email']); ?></h6></li>
                  <li><a class="dropdown-item" href="<?php echo $user['role'] === 'admin' ? 'admin_dashboard.php' : 'student_dashboard.php'; ?>"><i class="bi bi-grid me-2"></i>Dashboard</a></li>
                  <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person-gear me-2"></i>My Profile</a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                </ul>
              </div>
            <?php else: ?>
              <!-- Guest Links -->
              <a href="login.php" class="btn btn-outline-primary btn-sm px-3">
                <i class="bi bi-box-arrow-in-right me-1"></i>Login
              </a>
              <a href="register.php" class="btn btn-primary btn-sm px-3">
                <i class="bi bi-person-plus me-1"></i>Register
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </nav>
  </header>

  <!-- Flash Notification Banner (Practical 4) -->
  <div class="container mt-3">
    <?php if ($flash): ?>
      <div class="alert alert-<?php echo e($flash['type']); ?> alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-<?php echo $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
        <?php echo e($flash['message']); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Database Connection Alert (Friendly Notification if MySQL not started) -->
    <?php if (isset($db_connected) && !$db_connected && in_array($current_page, ['login.php', 'register.php', 'admin_dashboard.php', 'admin_students.php', 'admin_events.php'])): ?>
      <div class="alert alert-warning alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-database-exclamation me-2"></i>
        <strong>Database Notice:</strong> MySQL service is currently not connected (<?php echo e($db_error); ?>). Please ensure XAMPP MySQL is started and <code>studenthub.sql</code> is imported.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
  </div>

  <!-- Semantic Main Content Container -->
  <main id="main-content" class="container py-4">
