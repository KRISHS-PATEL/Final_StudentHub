<?php
/**
 * StudentHub Portal - Administrator Dashboard
 * Practical 14: Admin Dashboard with RBAC, Dynamic Menu, Analytics Chart & Audit Log
 */

$page_title = "Admin Dashboard";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();
$admin = current_user();

// Audit log entry for viewing admin dashboard (Practical 14)
log_audit($admin['id'], $admin['username'], 'Viewed Admin Dashboard');

// User status toggle action (Practical 14 Intermediate Extension)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_user_status'])) {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $target_user_id = (int)($_POST['target_user_id'] ?? 0);
        $current_status = $_POST['current_status'] ?? 'active';
        $new_status = ($current_status === 'active') ? 'inactive' : 'active';

        if ($pdo && $target_user_id > 0) {
            // Prevent self-deactivation
            if ($target_user_id === $admin['id']) {
                set_flash('danger', 'You cannot deactivate your own administrative account.');
            } else {
                $up = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                $up->execute([$new_status, $target_user_id]);
                log_audit($admin['id'], $admin['username'], "Toggled user ID #$target_user_id status to $new_status");
                set_flash('success', "User account status updated to $new_status.");
            }
        }
        header("Location: admin_dashboard.php");
        exit();
    }
}

// Aggregation Queries (Practical 14)
$user_count = 5;
$student_count = 20;
$event_count = 15;
$reg_count = 5;
$recent_users = [];
$recent_logs = [];

if ($pdo) {
    try {
        $user_count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $student_count = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
        $event_count = (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
        $reg_count = (int)$pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn();

        // Recent users
        $u_stmt = $pdo->query("SELECT id, username, email, role, status, created_at, last_login FROM users ORDER BY id DESC LIMIT 5");
        $recent_users = $u_stmt->fetchAll();

        // Recent audit logs
        $l_stmt = $pdo->query("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 6");
        $recent_logs = $l_stmt->fetchAll();
    } catch (Exception $e) {
        // fallback
    }
}
?>

<!-- Breadcrumbs (Practical 2) -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb py-2 px-3 bg-body-tertiary rounded-3 shadow-sm">
    <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house-door me-1"></i>Home</a></li>
    <li class="breadcrumb-item active" aria-current="page">Admin Dashboard</li>
  </ol>
</nav>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1"><i class="bi bi-speedometer2 text-primary me-2"></i>System Administration Dashboard</h1>
    <p class="text-muted mb-0">Practical 14: Centralized monitoring, user moderation, analytics, and audit logs.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="admin_students.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
      <i class="bi bi-people me-1"></i>Students (CRUD)
    </a>
    <a href="admin_events.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
      <i class="bi bi-calendar-plus me-1"></i>Events (Posters)
    </a>
    <a href="admin_audit.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
      <i class="bi bi-journal-text me-1"></i>Audit Logs
    </a>
  </div>
</div>

<!-- Aggregated Metrics Cards (Practical 14) -->
<div class="dashboard-grid mb-4">
  <div class="stat-card border-primary">
    <div>
      <div class="text-muted small fw-semibold text-uppercase">Total System Users</div>
      <h2 class="fw-bold mb-0 text-primary mt-1"><?php echo $user_count; ?></h2>
      <span class="badge bg-primary-subtle text-primary mt-2">Admins &amp; Students</span>
    </div>
    <div class="stat-icon bg-primary-subtle text-primary">
      <i class="bi bi-person-lines-fill"></i>
    </div>
  </div>

  <div class="stat-card border-success">
    <div>
      <div class="text-muted small fw-semibold text-uppercase">Enrolled Students</div>
      <h2 class="fw-bold mb-0 text-success mt-1"><?php echo $student_count; ?></h2>
      <span class="badge bg-success-subtle text-success mt-2">Directory Records</span>
    </div>
    <div class="stat-icon bg-success-subtle text-success">
      <i class="bi bi-mortarboard-fill"></i>
    </div>
  </div>

  <div class="stat-card border-info">
    <div>
      <div class="text-muted small fw-semibold text-uppercase">Campus Events</div>
      <h2 class="fw-bold mb-0 text-info mt-1"><?php echo $event_count; ?></h2>
      <span class="badge bg-info-subtle text-info mt-2">Active Catalogue</span>
    </div>
    <div class="stat-icon bg-info-subtle text-info">
      <i class="bi bi-calendar-event-fill"></i>
    </div>
  </div>

  <div class="stat-card border-warning">
    <div>
      <div class="text-muted small fw-semibold text-uppercase">Total Registrations</div>
      <h2 class="fw-bold mb-0 text-warning mt-1"><?php echo $reg_count; ?></h2>
      <span class="badge bg-warning-subtle text-warning mt-2">Event Bookings</span>
    </div>
    <div class="stat-icon bg-warning-subtle text-warning">
      <i class="bi bi-award-fill"></i>
    </div>
  </div>
</div>

<!-- Analytics Chart & Quick Actions Row (Practical 14 Advanced Extension) -->
<div class="row g-4 mb-5">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm rounded-4 h-100 bg-body">
      <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
        <div>
          <h2 class="h5 fw-bold mb-0 text-primary"><i class="bi bi-bar-chart-line-fill me-2"></i>Event Registrations Analytics</h2>
          <p class="text-muted small mb-0">Monthly participation trend across FTE departments</p>
        </div>
        <span class="badge bg-primary-subtle text-primary">Academic Year 2026-27</span>
      </div>
      <div class="card-body p-4">
        <!-- Interactive Canvas-based Bar & Line Chart -->
        <canvas id="registrationChart" width="600" height="260" style="width: 100%; height: 260px;"></canvas>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card border-0 shadow-sm rounded-4 h-100 bg-body">
      <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
        <h2 class="h5 fw-bold mb-0 text-primary"><i class="bi bi-gear-fill me-2"></i>Administrative Actions</h2>
        <p class="text-muted small mb-0">Management shortcuts</p>
      </div>
      <div class="card-body p-4 d-flex flex-column gap-3">
        <a href="admin_students.php" class="btn btn-outline-primary d-flex align-items-center justify-content-between p-3 rounded-3 text-start">
          <div>
            <div class="fw-bold"><i class="bi bi-people-fill me-2"></i>Student Management</div>
            <div class="small text-muted">Add, edit, soft-delete, search &amp; filter students</div>
          </div>
          <i class="bi bi-chevron-right"></i>
        </a>

        <a href="admin_events.php" class="btn btn-outline-primary d-flex align-items-center justify-content-between p-3 rounded-3 text-start">
          <div>
            <div class="fw-bold"><i class="bi bi-calendar-plus-fill me-2"></i>Event CRUD &amp; Posters</div>
            <div class="small text-muted">Upload posters with MIME/size validation</div>
          </div>
          <i class="bi bi-chevron-right"></i>
        </a>

        <a href="ajax_crud.php" class="btn btn-outline-success d-flex align-items-center justify-content-between p-3 rounded-3 text-start">
          <div>
            <div class="fw-bold"><i class="bi bi-lightning-charge-fill me-2"></i>AJAX API Live CRUD</div>
            <div class="small text-muted">Manage events without page reloads</div>
          </div>
          <i class="bi bi-chevron-right"></i>
        </a>

        <a href="admin_submissions.php" class="btn btn-outline-info d-flex align-items-center justify-content-between p-3 rounded-3 text-start">
          <div>
            <div class="fw-bold"><i class="bi bi-file-earmark-spreadsheet me-2"></i>CSV &amp; JSON Storage</div>
            <div class="small text-muted">View feedback &amp; contact messages from disk</div>
          </div>
          <i class="bi bi-chevron-right"></i>
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Moderation & User Status Toggle (Practical 14 Intermediate Extension) -->
<div class="card border-0 shadow-sm rounded-4 mb-5 bg-body">
  <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
    <div>
      <h2 class="h5 fw-bold mb-1 text-primary"><i class="bi bi-person-check-fill me-2"></i>User Moderation &amp; Status Control</h2>
      <p class="text-muted small mb-0">Active / Inactive role status toggling with instant audit tracking</p>
    </div>
    <span class="badge bg-secondary">RBAC Enforced</span>
  </div>

  <div class="card-body p-4">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th scope="col">User</th>
            <th scope="col">Email</th>
            <th scope="col">Role</th>
            <th scope="col">Last Login</th>
            <th scope="col">Status</th>
            <th scope="col" class="text-end">Toggle Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent_users as $u): ?>
            <tr>
              <td>
                <span class="fw-bold"><?php echo e($u['username']); ?></span>
              </td>
              <td class="small"><?php echo e($u['email']); ?></td>
              <td>
                <span class="badge bg-<?php echo $u['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                  <?php echo ucfirst(e($u['role'])); ?>
                </span>
              </td>
              <td class="small text-muted">
                <?php echo $u['last_login'] ? e($u['last_login']) : 'Never'; ?>
              </td>
              <td>
                <span class="badge bg-<?php echo $u['status'] === 'active' ? 'success' : 'secondary'; ?>">
                  <?php echo ucfirst(e($u['status'])); ?>
                </span>
              </td>
              <td class="text-end">
                <?php if ($u['id'] !== $admin['id']): ?>
                  <form method="POST" action="admin_dashboard.php" style="display:inline;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="target_user_id" value="<?php echo $u['id']; ?>">
                    <input type="hidden" name="current_status" value="<?php echo e($u['status']); ?>">
                    <button type="submit" name="toggle_user_status" class="btn btn-sm <?php echo $u['status'] === 'active' ? 'btn-outline-warning' : 'btn-outline-success'; ?> rounded-pill px-3">
                      <i class="bi bi-power me-1"></i><?php echo $u['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                    </button>
                  </form>
                <?php else: ?>
                  <span class="badge bg-light text-muted border">Self (Admin)</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Recent System Audit Logs (Practical 14) -->
<div class="card border-0 shadow-sm rounded-4 mb-5 bg-body">
  <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
    <div>
      <h2 class="h5 fw-bold mb-1 text-primary"><i class="bi bi-journal-text me-2"></i>Recent Security &amp; Audit Trail</h2>
      <p class="text-muted small mb-0">Traceable actions, IP addresses, and operational timestamps</p>
    </div>
    <a href="admin_audit.php" class="btn btn-outline-primary btn-sm rounded-pill">
      View Complete Log <i class="bi bi-arrow-right ms-1"></i>
    </a>
  </div>

  <div class="card-body p-4">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 small">
        <thead class="table-light">
          <tr>
            <th scope="col">Time</th>
            <th scope="col">User</th>
            <th scope="col">Action Performed</th>
            <th scope="col">IP Address</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recent_logs)): ?>
            <tr><td colspan="4" class="text-center text-muted py-3">No audit logs recorded yet.</td></tr>
          <?php else: ?>
            <?php foreach ($recent_logs as $log): ?>
              <tr>
                <td class="text-muted"><?php echo e($log['created_at']); ?></td>
                <td class="fw-semibold text-primary"><?php echo e($log['username'] ?? 'System'); ?></td>
                <td><?php echo e($log['action']); ?></td>
                <td><code><?php echo e($log['ip_address']); ?></code></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Chart Rendering Script (Canvas-based, no external CDN dependencies required) -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const canvas = document.getElementById('registrationChart');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');

  // Chart data: Monthly registrations
  const months = ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan'];
  const values = [12, 28, 45, 68, 92, 115, 84];

  // Draw background grid lines
  const w = canvas.width;
  const h = canvas.height;
  const padding = 40;
  const maxVal = 140;

  ctx.clearRect(0, 0, w, h);

  // Horizontal grid lines
  ctx.strokeStyle = '#e2e8f0';
  ctx.lineWidth = 1;
  for (let i = 0; i <= 4; i++) {
    const y = padding + (i * (h - 2 * padding) / 4);
    ctx.beginPath();
    ctx.moveTo(padding, y);
    ctx.lineTo(w - padding, y);
    ctx.stroke();

    ctx.fillStyle = '#94a3b8';
    ctx.font = '11px sans-serif';
    ctx.fillText(Math.round(maxVal - (i * maxVal / 4)), 10, y + 4);
  }

  // Draw bars
  const barWidth = 32;
  const step = (w - 2 * padding) / months.length;

  months.forEach((m, idx) => {
    const x = padding + idx * step + (step - barWidth) / 2;
    const barHeight = (values[idx] / maxVal) * (h - 2 * padding);
    const y = h - padding - barHeight;

    // Bar Gradient
    const grad = ctx.createLinearGradient(0, y, 0, h - padding);
    grad.addColorStop(0, '#4f46e5');
    grad.addColorStop(1, '#818cf8');
    ctx.fillStyle = grad;

    // Rounded top bar
    ctx.beginPath();
    ctx.roundRect(x, y, barWidth, barHeight, [6, 6, 0, 0]);
    ctx.fill();

    // Month Label
    ctx.fillStyle = '#64748b';
    ctx.font = 'bold 12px sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText(m, x + barWidth / 2, h - padding + 18);

    // Value label
    ctx.fillStyle = '#1e293b';
    ctx.font = '11px sans-serif';
    ctx.fillText(values[idx], x + barWidth / 2, y - 6);
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
