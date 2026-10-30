<?php
/**
 * StudentHub Portal - System Audit Logs Viewer
 * Practical 14: Administrative Monitoring & Traceability
 */

$page_title = "Audit Logs & Security Trail";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();

$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$logs = [];
$total_count = 0;

if ($pdo) {
    try {
        $sql = "SELECT * FROM audit_logs WHERE 1=1";
        $cnt_sql = "SELECT COUNT(*) FROM audit_logs WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (action LIKE ? OR username LIKE ? OR ip_address LIKE ?)";
            $cnt_sql .= " AND (action LIKE ? OR username LIKE ? OR ip_address LIKE ?)";
            $w = "%$search%";
            $params[] = $w; $params[] = $w; $params[] = $w;
        }

        $cnt_stmt = $pdo->prepare($cnt_sql);
        $cnt_stmt->execute($params);
        $total_count = (int)$cnt_stmt->fetchColumn();

        $sql .= " ORDER BY id DESC LIMIT $limit OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();
    } catch (Exception $e) {
        // fallback
    }
}

$total_pages = ceil($total_count / $limit);
?>

<!-- Breadcrumbs (Practical 2) -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb py-2 px-3 bg-body-tertiary rounded-3 shadow-sm">
    <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house-door me-1"></i>Home</a></li>
    <li class="breadcrumb-item"><a href="admin_dashboard.php">Admin Hub</a></li>
    <li class="breadcrumb-item active" aria-current="page">System Audit Logs</li>
  </ol>
</nav>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1"><i class="bi bi-journal-text text-primary me-2"></i>System Security &amp; Audit Logs</h1>
    <p class="text-muted mb-0">Practical 14: Traceability of all logins, registrations, CRUD operations, and admin actions.</p>
  </div>
  <a href="admin_dashboard.php" class="btn btn-outline-secondary rounded-pill px-3">
    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
  </a>
</div>

<!-- Search Form -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-body">
  <div class="card-body p-3">
    <form method="GET" action="admin_audit.php" class="row g-2 align-items-center">
      <div class="col-md-10">
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" name="search" class="form-control" placeholder="Search by username, action description, or IP address..." value="<?php echo e($search); ?>">
        </div>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-primary w-100">Search</button>
        <?php if (!empty($search)): ?>
          <a href="admin_audit.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- Logs Table -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-body">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 small">
        <thead class="table-light">
          <tr>
            <th scope="col" class="ps-4">Log ID</th>
            <th scope="col">Timestamp</th>
            <th scope="col">User</th>
            <th scope="col">Action Details</th>
            <th scope="col">Client IP</th>
            <th scope="col" class="pe-4">User Agent</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($logs)): ?>
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bi bi-journal-x fs-2 d-block mb-2"></i>No audit log entries found.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($logs as $log): ?>
              <tr>
                <td class="ps-4 fw-bold text-muted">#<?php echo $log['id']; ?></td>
                <td class="text-nowrap"><?php echo e($log['created_at']); ?></td>
                <td>
                  <span class="fw-semibold text-primary"><?php echo e($log['username'] ?? 'Anonymous/Guest'); ?></span>
                  <?php if (!empty($log['user_id'])): ?>
                    <span class="badge bg-light text-muted border ms-1">UID: <?php echo $log['user_id']; ?></span>
                  <?php endif; ?>
                </td>
                <td><span class="badge bg-primary-subtle text-primary text-wrap text-start"><?php echo e($log['action']); ?></span></td>
                <td><code><?php echo e($log['ip_address'] ?? '127.0.0.1'); ?></code></td>
                <td class="pe-4 text-muted small text-truncate" style="max-width: 200px;" title="<?php echo e($log['user_agent']); ?>">
                  <?php echo e($log['user_agent'] ?? 'Unknown'); ?>

                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
  <nav aria-label="Audit logs pagination" class="d-flex justify-content-center mb-5">
    <ul class="pagination shadow-sm">
      <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">&laquo;</a>
      </li>
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
          <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">&raquo;</a>
      </li>
    </ul>
  </nav>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
