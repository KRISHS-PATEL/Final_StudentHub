<?php
/**
 * StudentHub Portal - Student Dashboard
 * Practical 10: Role-Based Access Control & Sessions
 */

$page_title = "Student Dashboard";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$user = current_user();

// Handle Cancellation of Event Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_registration'])) {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $reg_id = (int)($_POST['registration_id'] ?? 0);
        $event_id = (int)($_POST['event_id'] ?? 0);

        if ($pdo && $reg_id > 0) {
            try {
                $del = $pdo->prepare("DELETE FROM registrations WHERE id = ?");
                $del->execute([$reg_id]);
                // Decrement event counter
                $dec = $pdo->prepare("UPDATE events SET registered_count = GREATEST(0, registered_count - 1) WHERE id = ?");
                $dec->execute([$event_id]);

                log_audit($user['id'], $user['username'], "Cancelled registration #$reg_id for event #$event_id");
                set_flash('info', 'Your event registration has been cancelled.');
                header("Location: student_dashboard.php");
                exit();
            } catch (Exception $e) {
                set_flash('danger', 'Error cancelling registration: ' . $e->getMessage());
            }
        }
    }
}

// Fetch student profile details
$student_profile = null;
$enrolled_events = [];

if ($pdo) {
    try {
        $sp_stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ? OR email = ? LIMIT 1");
        $sp_stmt->execute([$user['id'], $user['email']]);
        $student_profile = $sp_stmt->fetch();

        if ($student_profile) {
            $reg_stmt = $pdo->prepare("
                SELECT r.id as reg_id, r.status as reg_status, r.registered_at, 
                       e.id as event_id, e.title, e.category, e.event_date, e.event_time, e.location, e.status as event_status, e.poster
                FROM registrations r
                JOIN events e ON r.event_id = e.id
                WHERE r.student_id = ?
                ORDER BY e.event_date ASC
            ");
            $reg_stmt->execute([$student_profile['id']]);
            $enrolled_events = $reg_stmt->fetchAll();
        }
    } catch (Exception $e) {
        // fallback
    }
}
?>

<!-- Breadcrumbs (Practical 2) -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb py-2 px-3 bg-body-tertiary rounded-3 shadow-sm">
    <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house-door me-1"></i>Home</a></li>
    <li class="breadcrumb-item active" aria-current="page">Student Dashboard</li>
  </ol>
</nav>

<!-- Welcome Greeting -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1">
      Welcome, <?php echo e($student_profile['full_name'] ?? $user['username']); ?>!
    </h1>
    <p class="text-muted mb-0">Student Portal &bull; Department of <?php echo e($student_profile['department'] ?? 'Information Technology'); ?></p>
  </div>
  <div class="d-flex gap-2">
    <a href="events.php" class="btn btn-primary rounded-pill px-3">
      <i class="bi bi-calendar-plus me-1"></i>Browse Events
    </a>
    <a href="profile.php" class="btn btn-outline-secondary rounded-pill px-3">
      <i class="bi bi-person-gear me-1"></i>Edit Profile
    </a>
  </div>
</div>

<!-- Practical 10 Inspection Banner (Viva / Screenshot proof) -->
<div class="alert alert-light border shadow-sm rounded-4 mb-4">
  <div class="row align-items-center">
    <div class="col-md-7">
      <div class="small fw-semibold text-primary"><i class="bi bi-shield-check me-1"></i>Practical 10 Authentication Verification Proof:</div>
      <div class="small text-muted">
        Role: <span class="badge bg-primary"><?php echo ucfirst(e($user['role'])); ?></span> | 
        Status: <span class="badge bg-success"><?php echo ucfirst(e($user['status'])); ?></span> | 
        Session Timeout: <strong>15 Minutes Inactive</strong>
      </div>
    </div>
    <div class="col-md-5 text-md-end mt-2 mt-md-0">
      <span class="small text-secondary">Regenerated Session ID:</span>
      <code class="d-block small text-truncate" style="max-width: 280px; margin-left: auto;"><?php echo session_id(); ?></code>
    </div>
  </div>
</div>

<!-- Metric Widgets (Practical 3 CSS Grid) -->
<div class="dashboard-grid mb-4">
  <div class="stat-card">
    <div>
      <div class="text-muted small fw-semibold text-uppercase">Enrolled Events</div>
      <h2 class="fw-bold mb-0 text-primary mt-1"><?php echo count($enrolled_events); ?></h2>
      <span class="badge bg-primary-subtle text-primary mt-2">Active Registrations</span>
    </div>
    <div class="stat-icon bg-primary-subtle text-primary">
      <i class="bi bi-ticket-perforated-fill"></i>
    </div>
  </div>

  <div class="stat-card">
    <div>
      <div class="text-muted small fw-semibold text-uppercase">Student ID</div>
      <h2 class="fw-bold mb-0 text-success mt-1"><?php echo e($student_profile['student_id_code'] ?? '24IT001'); ?></h2>
      <span class="badge bg-success-subtle text-success mt-2"><?php echo e($student_profile['course'] ?? 'B.Tech IT'); ?></span>
    </div>
    <div class="stat-icon bg-success-subtle text-success">
      <i class="bi bi-card-text"></i>
    </div>
  </div>

  <div class="stat-card">
    <div>
      <div class="text-muted small fw-semibold text-uppercase">Academic Year</div>
      <h2 class="fw-bold mb-0 text-info mt-1"><?php echo e($student_profile['year'] ?? '3rd Year'); ?></h2>
      <span class="badge bg-info-subtle text-info mt-2">CHARUSAT FTE</span>
    </div>
    <div class="stat-icon bg-info-subtle text-info">
      <i class="bi bi-award-fill"></i>
    </div>
  </div>

  <div class="stat-card">
    <div>
      <div class="text-muted small fw-semibold text-uppercase">Feedback Status</div>
      <h2 class="fw-bold mb-0 text-warning mt-1"><i class="bi bi-star-fill text-warning"></i></h2>
      <a href="feedback.php" class="badge bg-warning-subtle text-warning text-decoration-none mt-2">Submit Review</a>
    </div>
    <div class="stat-icon bg-warning-subtle text-warning">
      <i class="bi bi-chat-left-heart-fill"></i>
    </div>
  </div>
</div>

<!-- Enrolled Events Table (Practical 8 & 11) -->
<div class="card border-0 shadow-sm rounded-4 mb-5 bg-body">
  <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
    <div>
      <h2 class="h5 fw-bold mb-1 text-primary"><i class="bi bi-calendar-check me-2"></i>My Enrolled Events</h2>
      <p class="text-muted small mb-0">Real-time status of your event participations and lab sessions.</p>
    </div>
    <a href="events.php" class="btn btn-outline-primary btn-sm rounded-pill">
      <i class="bi bi-search me-1"></i>Find More
    </a>
  </div>

  <div class="card-body p-4">
    <?php if (empty($enrolled_events)): ?>
      <div class="text-center py-5">
        <div class="display-6 text-muted mb-3"><i class="bi bi-calendar-plus"></i></div>
        <h3 class="fs-5 fw-bold">No Event Registrations Yet</h3>
        <p class="text-muted small">You have not registered for any upcoming campus events yet.</p>
        <a href="events.php" class="btn btn-primary btn-sm rounded-pill px-4">Browse Campus Events</a>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th scope="col">Event Title</th>
              <th scope="col">Category</th>
              <th scope="col">Date &amp; Time</th>
              <th scope="col">Venue</th>
              <th scope="col">Registration Status</th>
              <th scope="col" class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($enrolled_events as $reg): ?>
              <tr>
                <td>
                  <span class="fw-bold text-primary"><?php echo e($reg['title']); ?></span>
                </td>
                <td>
                  <span class="badge bg-primary-subtle text-primary"><?php echo e($reg['category']); ?></span>
                </td>
                <td class="small">
                  <div><i class="bi bi-calendar3 me-1 text-muted"></i><?php echo e($reg['event_date']); ?></div>
                  <div class="text-muted"><?php echo e($reg['event_time'] ?? '10:00 AM'); ?></div>
                </td>
                <td class="small"><?php echo e($reg['location']); ?></td>
                <td>
                  <span class="badge bg-success-subtle text-success">
                    <i class="bi bi-check-circle-fill me-1"></i><?php echo e($reg['reg_status']); ?>

                  </span>
                </td>
                <td class="text-end">
                  <form method="POST" action="student_dashboard.php" onsubmit="return confirm('Are you sure you want to cancel registration for <?php echo e($reg['title']); ?>?');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="registration_id" value="<?php echo $reg['reg_id']; ?>">
                    <input type="hidden" name="event_id" value="<?php echo $reg['event_id']; ?>">
                    <button type="submit" name="cancel_registration" class="btn btn-outline-danger btn-sm rounded-pill px-3" title="Cancel Registration">
                      <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
