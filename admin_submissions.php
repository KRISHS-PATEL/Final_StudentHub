<?php
/**
 * StudentHub Portal - CSV & JSON File Submissions Viewer
 * Practical 7: PHP Form Processing with Server-Side Validation and CSV/JSON File Storage
 * Intermediate Extension: Display stored CSV/JSON records on a webpage
 */

$page_title = "Stored CSV & JSON Submissions";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();

// Read Contact CSV
$contact_csv = [];
$contact_file = DATA_PATH . '/contact_messages.csv';
if (file_exists($contact_file) && ($handle = fopen($contact_file, 'r')) !== false) {
    $header = fgetcsv($handle);
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) >= 4) {
            $contact_csv[] = $row;
        }
    }
    fclose($handle);
}

// Read Feedback CSV
$feedback_csv = [];
$feedback_file = DATA_PATH . '/feedback.csv';
if (file_exists($feedback_file) && ($handle = fopen($feedback_file, 'r')) !== false) {
    $header_f = fgetcsv($handle);
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) >= 4) {
            $feedback_csv[] = $row;
        }
    }
    fclose($handle);
}

// Read Submissions JSON
$submissions_json = [];
$json_file = DATA_PATH . '/submissions.json';
if (file_exists($json_file)) {
    $submissions_json = json_decode(file_get_contents($json_file), true) ?: [];
}
?>

<!-- Breadcrumbs (Practical 2) -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb py-2 px-3 bg-body-tertiary rounded-3 shadow-sm">
    <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house-door me-1"></i>Home</a></li>
    <li class="breadcrumb-item"><a href="admin_dashboard.php">Admin Hub</a></li>
    <li class="breadcrumb-item active" aria-current="page">Stored Submissions (CSV &amp; JSON)</li>
  </ol>
</nav>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1"><i class="bi bi-file-earmark-spreadsheet-fill text-primary me-2"></i>File-Based Data Storage Viewer</h1>
    <p class="text-muted mb-0">Practical 7: Real-time verification of sanitized form submissions stored in CSV and JSON formats.</p>
  </div>
  <a href="admin_dashboard.php" class="btn btn-outline-secondary rounded-pill px-3">
    <i class="bi bi-arrow-left me-1"></i>Admin Dashboard
  </a>
</div>

<!-- Tabs for CSV vs JSON -->
<ul class="nav nav-pills mb-4" id="submissionTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active rounded-pill px-4" id="contact-tab" data-bs-toggle="pill" data-bs-target="#contact-panel" type="button" role="tab">
      <i class="bi bi-envelope-paper me-1"></i>Contact CSV (<?php echo count($contact_csv); ?>)
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link rounded-pill px-4" id="feedback-tab" data-bs-toggle="pill" data-bs-target="#feedback-panel" type="button" role="tab">
      <i class="bi bi-chat-heart me-1"></i>Feedback CSV (<?php echo count($feedback_csv); ?>)
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link rounded-pill px-4" id="json-tab" data-bs-toggle="pill" data-bs-target="#json-panel" type="button" role="tab">
      <i class="bi bi-filetype-json me-1"></i>Unified JSON File (<?php echo count($submissions_json); ?>)
    </button>
  </li>
</ul>

<div class="tab-content" id="submissionTabsContent">
  <!-- Contact CSV Panel -->
  <div class="tab-pane fade show active" id="contact-panel" role="tabpanel">
    <div class="card border-0 shadow-sm rounded-4 bg-body mb-5">
      <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
        <div>
          <h2 class="h5 fw-bold mb-0 text-primary">data/contact_messages.csv</h2>
          <span class="small text-muted">File path: <code><?php echo e($contact_file); ?></code></span>
        </div>
        <span class="badge bg-success-subtle text-success">File Locking (flock) Safe</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th scope="col" class="ps-4">Sender Name</th>
                <th scope="col">Email</th>
                <th scope="col">Subject</th>
                <th scope="col">Message Content</th>
                <th scope="col" class="pe-4">Date Stored</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($contact_csv)): ?>
                <tr><td colspan="5" class="text-center py-4 text-muted">No records currently stored in contact CSV.</td></tr>
              <?php else: ?>
                <?php foreach ($contact_csv as $row): ?>
                  <tr>
                    <td class="ps-4 fw-semibold"><?php echo e($row[0] ?? ''); ?></td>
                    <td class="small"><?php echo e($row[1] ?? ''); ?></td>
                    <td class="small fw-semibold text-primary"><?php echo e($row[2] ?? ''); ?></td>
                    <td class="small text-muted"><?php echo e($row[3] ?? ''); ?></td>
                    <td class="pe-4 small text-muted"><?php echo e($row[4] ?? 'N/A'); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Feedback CSV Panel -->
  <div class="tab-pane fade" id="feedback-panel" role="tabpanel">
    <div class="card border-0 shadow-sm rounded-4 bg-body mb-5">
      <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
        <div>
          <h2 class="h5 fw-bold mb-0 text-primary">data/feedback.csv</h2>
          <span class="small text-muted">File path: <code><?php echo e($feedback_file); ?></code></span>
        </div>
        <span class="badge bg-primary-subtle text-primary">5-Star Feedback Store</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th scope="col" class="ps-4">Student Name</th>
                <th scope="col">Rating</th>
                <th scope="col">Category</th>
                <th scope="col">Comments</th>
                <th scope="col" class="pe-4">Timestamp</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($feedback_csv)): ?>
                <tr><td colspan="5" class="text-center py-4 text-muted">No records in feedback CSV.</td></tr>
              <?php else: ?>
                <?php foreach ($feedback_csv as $row): ?>
                  <tr>
                    <td class="ps-4 fw-semibold"><?php echo e($row[0] ?? ''); ?></td>
                    <td>
                      <span class="text-warning">
                        <?php 
                          $r = (int)($row[1] ?? 5);
                          for ($i = 0; $i < $r; $i++) echo '<i class="bi bi-star-fill"></i>';
                        ?>
                      </span>
                    </td>
                    <td><span class="badge bg-info-subtle text-info"><?php echo e($row[2] ?? ''); ?></span></td>
                    <td class="small text-muted"><?php echo e($row[3] ?? ''); ?></td>
                    <td class="pe-4 small text-muted"><?php echo e($row[4] ?? 'N/A'); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- JSON File Panel -->
  <div class="tab-pane fade" id="json-panel" role="tabpanel">
    <div class="card border-0 shadow-sm rounded-4 bg-body mb-5">
      <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
        <div>
          <h2 class="h5 fw-bold mb-0 text-primary">data/submissions.json</h2>
          <span class="small text-muted">File path: <code><?php echo e($json_file); ?></code></span>
        </div>
        <span class="badge bg-secondary">JSON Object Array</span>
      </div>
      <div class="card-body p-4">
        <pre class="bg-dark text-light p-3 rounded-3" style="max-height: 400px; overflow-y: auto;"><code><?php echo e(json_encode($submissions_json, JSON_PRETTY_PRINT)); ?></code></pre>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
