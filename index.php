<?php
/**
 * StudentHub Portal - Homepage & Landing
 * Practical 1, 2, 3, 4
 */

$page_title = "Home - Campus Portal";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';

// Fetch quick statistics
$total_students = 20;
$total_events = 15;
$total_registrations = 5;

if ($pdo) {
    try {
        $total_students = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE status = 'active'")->fetchColumn();
        $total_events = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status = 'Open'")->fetchColumn();
        $total_registrations = (int)$pdo->query("SELECT COUNT(*) FROM registrations WHERE status = 'Confirmed'")->fetchColumn();
    } catch (Exception $e) {
        // use defaults
    }
}

// Fetch featured events (Top 3 open events)
$featured_events = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM events WHERE status = 'Open' ORDER BY event_date ASC LIMIT 3");
        $featured_events = $stmt->fetchAll();
    } catch (Exception $e) {
        // fallback to json
    }
}

if (empty($featured_events)) {
    $eventsJson = file_exists(DATA_PATH . '/events.json') ? json_decode(file_get_contents(DATA_PATH . '/events.json'), true) : [];
    $featured_events = array_slice(array_filter($eventsJson, fn($e) => ($e['status'] ?? '') === 'Open'), 0, 3);
}
?>

<!-- Breadcrumb Navigation (Practical 2) -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb py-2 px-3 bg-body-tertiary rounded-3 shadow-sm">
    <li class="breadcrumb-item active" aria-current="page"><i class="bi bi-house-door-fill me-1"></i>Home</li>
  </ol>
</nav>

<!-- Hero Section with Content / Image Slider (Practical 4) -->
<section class="hero-slider" aria-label="Campus Highlights Carousel">
  <div class="hero-slide active">
    <img src="assets/img/hero-1.svg" alt="Welcome to CHARUSAT FTE StudentHub">
  </div>
  <div class="hero-slide">
    <img src="assets/img/hero-2.svg" alt="Explore Technical Hackathons and Cultural Fests">
  </div>
  <div class="hero-slide">
    <img src="assets/img/hero-3.svg" alt="Accessible and Secure Student Ecosystem">
  </div>

  <!-- Slider Controls -->
  <button class="slider-btn prev" aria-label="Previous Slide"><i class="bi bi-chevron-left"></i></button>
  <button class="slider-btn next" aria-label="Next Slide"><i class="bi bi-chevron-right"></i></button>
  <div class="slider-controls" role="tablist" aria-label="Slide Selector"></div>
</section>

<!-- Quick Stats Grid (Practical 3 & 14) -->
<section class="mb-5" aria-label="Portal Metrics">
  <div class="dashboard-grid">
    <div class="stat-card">
      <div>
        <div class="text-muted small fw-semibold text-uppercase">Active Students</div>
        <h2 class="fw-bold mb-0 text-primary mt-1"><?php echo $total_students; ?>+</h2>
        <span class="badge bg-primary-subtle text-primary mt-2">Enrolled Learners</span>
      </div>
      <div class="stat-icon bg-primary-subtle text-primary">
        <i class="bi bi-mortarboard-fill"></i>
      </div>
    </div>

    <div class="stat-card">
      <div>
        <div class="text-muted small fw-semibold text-uppercase">Open Events</div>
        <h2 class="fw-bold mb-0 text-success mt-1"><?php echo $total_events; ?></h2>
        <span class="badge bg-success-subtle text-success mt-2">Active Registrations</span>
      </div>
      <div class="stat-icon bg-success-subtle text-success">
        <i class="bi bi-calendar-check-fill"></i>
      </div>
    </div>

    <div class="stat-card">
      <div>
        <div class="text-muted small fw-semibold text-uppercase">Confirmed Seats</div>
        <h2 class="fw-bold mb-0 text-info mt-1"><?php echo $total_registrations; ?></h2>
        <span class="badge bg-info-subtle text-info mt-2">Participation Rate</span>
      </div>
      <div class="stat-icon bg-info-subtle text-info">
        <i class="bi bi-award-fill"></i>
      </div>
    </div>

    <div class="stat-card">
      <div>
        <div class="text-muted small fw-semibold text-uppercase">Web Frameworks</div>
        <h2 class="fw-bold mb-0 text-warning mt-1">ITUE203</h2>
        <span class="badge bg-warning-subtle text-warning mt-2">Practical 15 Final</span>
      </div>
      <div class="stat-icon bg-warning-subtle text-warning">
        <i class="bi bi-code-slash"></i>
      </div>
    </div>
  </div>
</section>

<!-- Featured Events Section (Practical 3 & 6) -->
<section class="mb-5" aria-labelledby="featured-events-heading">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 id="featured-events-heading" class="fw-bold mb-1">Featured Campus Events</h2>
      <p class="text-muted mb-0">Upcoming competitions, workshops, and symposiums at CHARUSAT FTE</p>
    </div>
    <a href="events.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
      View All Events <i class="bi bi-arrow-right ms-1"></i>
    </a>
  </div>

  <div class="events-grid">
    <?php foreach ($featured_events as $event): ?>
      <?php 
        $poster = !empty($event['poster']) ? $event['poster'] : 'default-poster.svg';
        $poster_src = (strpos($poster, 'sample') === 0) ? 'uploads/posters/' . $poster : 'assets/img/' . $poster;
      ?>
      <article class="event-card">
        <div class="event-poster-container">
          <img src="<?php echo e($poster_src); ?>" alt="<?php echo e($event['title']); ?>" class="event-poster-img" onerror="this.src='assets/img/default-poster.svg'">
          <span class="event-badge bg-primary text-white"><?php echo e($event['category']); ?></span>
        </div>
        <div class="event-body">
          <h3 class="fs-5 fw-bold text-primary mb-2"><?php echo e($event['title']); ?></h3>
          <p class="text-muted small mb-3 flex-grow-1">
            <?php echo e(substr($event['description'], 0, 120)) . '...'; ?>
          </p>
          <div class="small text-secondary mb-3">
            <div><i class="bi bi-calendar3 me-2"></i><?php echo e($event['event_date']); ?></div>
            <div><i class="bi bi-geo-alt me-2"></i><?php echo e($event['location']); ?></div>
          </div>
          <div class="d-flex justify-content-between align-items-center pt-2 border-top">
            <span class="badge bg-success-subtle text-success">
              <i class="bi bi-person-check me-1"></i><?php echo $event['registered_count'] ?? 0; ?> Registered
            </span>
            <a href="events.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-primary rounded-pill px-3">
              Details &amp; Register
            </a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<!-- Call-to-Action Banner -->
<section class="p-5 rounded-4 bg-primary text-white text-center shadow-lg mb-5" aria-label="Portal Introduction">
  <div class="max-w-2xl mx-auto">
    <h2 class="fw-bold mb-3">Ready to Join StudentHub?</h2>
    <p class="lead mb-4 opacity-90">
      Access your personalized student dashboard, enroll in academic hackathons, explore campus notices, and participate in technical activities.
    </p>
    <div class="d-flex justify-content-center gap-3 flex-wrap">
      <?php if (!is_logged_in()): ?>
        <a href="register.php" class="btn btn-light btn-lg text-primary fw-semibold px-4 shadow-sm">
          <i class="bi bi-person-plus-fill me-2"></i>Create Free Account
        </a>
        <a href="login.php" class="btn btn-outline-light btn-lg px-4">
          <i class="bi bi-box-arrow-in-right me-2"></i>Student Sign In
        </a>
      <?php else: ?>
        <a href="<?php echo is_admin() ? 'admin_dashboard.php' : 'student_dashboard.php'; ?>" class="btn btn-light btn-lg text-primary fw-semibold px-4 shadow-sm">
          <i class="bi bi-speedometer2 me-2"></i>Go to My Dashboard
        </a>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
