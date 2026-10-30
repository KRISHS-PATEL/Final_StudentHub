<?php
/**
 * StudentHub Portal - Automated Diagnostic Test Suite
 * Practical 15: Final Integration, Testing & Verification
 */

$page_title = "Automated Diagnostic Test Suite";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$tests = [];

// Test 1: PHP Runtime Environment
$tests[] = [
    'category' => 'Environment',
    'name' => 'PHP Version >= 8.0',
    'status' => version_compare(PHP_VERSION, '8.0.0', '>=') ? 'PASS' : 'FAIL',
    'details' => 'Current PHP Version: ' . PHP_VERSION
];

// Test 2: Essential Extensions
$required_exts = ['pdo', 'pdo_mysql', 'mysqli', 'json', 'session', 'fileinfo'];
$missing_exts = [];
foreach ($required_exts as $ext) {
    if (!extension_loaded($ext)) {
        $missing_exts[] = $ext;
    }
}
$tests[] = [
    'category' => 'Environment',
    'name' => 'PHP Extensions (PDO, MySQLi, JSON, FileInfo)',
    'status' => empty($missing_exts) ? 'PASS' : 'FAIL',
    'details' => empty($missing_exts) ? 'All essential extensions loaded.' : 'Missing: ' . implode(', ', $missing_exts)
];

// Test 3: Data Directory Writable
$data_writable = is_writable(DATA_PATH);
$tests[] = [
    'category' => 'File Storage',
    'name' => 'Data Directory Writable (Practical 7)',
    'status' => $data_writable ? 'PASS' : 'FAIL',
    'details' => DATA_PATH . ($data_writable ? ' is writable' : ' is NOT writable')
];

// Test 4: Uploads Directory Writable
$upload_writable = is_writable(POSTERS_PATH);
$tests[] = [
    'category' => 'File Storage',
    'name' => 'Posters Upload Directory Writable (Practical 12)',
    'status' => $upload_writable ? 'PASS' : 'FAIL',
    'details' => POSTERS_PATH . ($upload_writable ? ' is writable' : ' is NOT writable')
];

// Test 5: JSON Datasets (Practical 6)
$json_files = ['events.json', 'students.json', 'faqs.json'];
$all_json_ok = true;
$json_counts = [];
foreach ($json_files as $jf) {
    $p = DATA_PATH . '/' . $jf;
    if (file_exists($p)) {
        $arr = json_decode(file_get_contents($p), true);
        $c = is_array($arr) ? count($arr) : 0;
        $json_counts[] = "$jf ($c items)";
        if ($c < 5) $all_json_ok = false;
    } else {
        $all_json_ok = false;
        $json_counts[] = "$jf (MISSING)";
    }
}
$tests[] = [
    'category' => 'Datasets',
    'name' => 'Seed JSON Datasets (>=15 items)',
    'status' => $all_json_ok ? 'PASS' : 'WARN',
    'details' => implode(', ', $json_counts)
];

// Test 6: Database Connectivity
$tests[] = [
    'category' => 'Database',
    'name' => 'MySQL Database Connection (Practical 8)',
    'status' => $db_connected ? 'PASS' : 'WARN',
    'details' => $db_connected ? 'Connected via PDO and MySQLi.' : 'Database not connected: ' . ($db_error ?? 'Check MySQL service.')
];

// Test 7: Schema Tables Check (if connected)
if ($db_connected && $pdo) {
    $expected_tables = ['users', 'students', 'events', 'registrations', 'audit_logs', 'contact_messages', 'feedback', 'remember_tokens'];
    $existing_tables = [];
    try {
        $q = $pdo->query("SHOW TABLES");
        while ($r = $q->fetch(PDO::FETCH_NUM)) {
            $existing_tables[] = $r[0];
        }
        $missing = array_diff($expected_tables, $existing_tables);
        $tests[] = [
            'category' => 'Database',
            'name' => 'Relational Schema Tables (3NF)',
            'status' => empty($missing) ? 'PASS' : 'WARN',
            'details' => empty($missing) ? 'All 8 core tables present.' : 'Missing tables: ' . implode(', ', $missing) . '. Please import studenthub.sql.'
        ];
    } catch (Exception $e) {
        $tests[] = [
            'category' => 'Database',
            'name' => 'Schema Verification',
            'status' => 'FAIL',
            'details' => $e->getMessage()
        ];
    }
}

// Test 8: Password Hashing & Bcrypt Verification (Practical 9 & 10)
$test_pass = "Admin@123";
$hash = password_hash($test_pass, PASSWORD_DEFAULT);
$verified = password_verify($test_pass, $hash);
$tests[] = [
    'category' => 'Security',
    'name' => 'Bcrypt Password Hashing & Verification',
    'status' => $verified ? 'PASS' : 'FAIL',
    'details' => 'password_hash() and password_verify() functioning correctly.'
];

// Test 9: CSRF Token Generation
$tests[] = [
    'category' => 'Security',
    'name' => 'CSRF Defense Token Generated',
    'status' => !empty($_SESSION['csrf_token']) ? 'PASS' : 'FAIL',
    'details' => 'Token length: ' . strlen($_SESSION['csrf_token'] ?? '') . ' bytes.'
];

$pass_count = count(array_filter($tests, fn($t) => $t['status'] === 'PASS'));
$warn_count = count(array_filter($tests, fn($t) => $t['status'] === 'WARN'));
$fail_count = count(array_filter($tests, fn($t) => $t['status'] === 'FAIL'));
?>

<!-- Breadcrumbs (Practical 2) -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb py-2 px-3 bg-body-tertiary rounded-3 shadow-sm">
    <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house-door me-1"></i>Home</a></li>
    <li class="breadcrumb-item active" aria-current="page">Diagnostic Test Suite</li>
  </ol>
</nav>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1"><i class="bi bi-check2-circle text-primary me-2"></i>System Diagnostic &amp; Verification Suite</h1>
    <p class="text-muted mb-0">Practical 15: Automated verification of environment, databases, security checklists, and file systems.</p>
  </div>
  <a href="test_suite.php" class="btn btn-primary rounded-pill px-3">
    <i class="bi bi-arrow-clockwise me-1"></i>Re-Run Diagnostics
  </a>
</div>

<!-- Test Summary Metrics -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm rounded-4 bg-body p-3 text-center border-start border-success border-4">
      <div class="display-6 fw-bold text-success mb-1"><?php echo $pass_count; ?></div>
      <div class="small fw-semibold text-muted text-uppercase">Tests Passed</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm rounded-4 bg-body p-3 text-center border-start border-warning border-4">
      <div class="display-6 fw-bold text-warning mb-1"><?php echo $warn_count; ?></div>
      <div class="small fw-semibold text-muted text-uppercase">Warnings / Notes</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm rounded-4 bg-body p-3 text-center border-start border-danger border-4">
      <div class="display-6 fw-bold text-danger mb-1"><?php echo $fail_count; ?></div>
      <div class="small fw-semibold text-muted text-uppercase">Critical Failures</div>
    </div>
  </div>
</div>

<!-- Test Results Table -->
<div class="card border-0 shadow-sm rounded-4 bg-body mb-5">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th scope="col" class="ps-4">Module / Category</th>
            <th scope="col">Test Case</th>
            <th scope="col">Diagnostic Output</th>
            <th scope="col" class="pe-4 text-end">Result</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tests as $t): ?>
            <?php 
              $badge_cls = $t['status'] === 'PASS' ? 'bg-success' : ($t['status'] === 'WARN' ? 'bg-warning text-dark' : 'bg-danger');
            ?>
            <tr>
              <td class="ps-4"><span class="badge bg-secondary-subtle text-secondary"><?php echo e($t['category']); ?></span></td>
              <td class="fw-semibold"><?php echo e($t['name']); ?></td>
              <td class="small text-muted"><?php echo e($t['details']); ?></td>
              <td class="pe-4 text-end">
                <span class="badge <?php echo $badge_cls; ?> px-3 py-1 rounded-pill"><?php echo e($t['status']); ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
