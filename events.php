<?php
/**
 * StudentHub Portal - Campus Events Catalogue
 * Practical 6: Fetch API, Search, Filter, Sorting, Pagination
 * Practical 12: Event status, Posters, and Registration
 */

$page_title = "Campus Events Directory";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';

// Parameters for server-side fallback
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$sort = trim($_GET['sort'] ?? 'date_asc');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 6;
$offset = ($page - 1) * $limit;

$all_events = [];
$total_items = 0;

if ($pdo) {
    try {
        $sql = "SELECT * FROM events WHERE 1=1";
        $count_sql = "SELECT COUNT(*) FROM events WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (title LIKE ? OR description LIKE ? OR location LIKE ?)";
            $count_sql .= " AND (title LIKE ? OR description LIKE ? OR location LIKE ?)";
            $w = "%$search%";
            $params[] = $w; $params[] = $w; $params[] = $w;
        }

        if (!empty($category) && $category !== 'all') {
            $sql .= " AND category = ?";
            $count_sql .= " AND category = ?";
            $params[] = $category;
        }

        // Count total matching
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_items = (int)$count_stmt->fetchColumn();

        // Sort
        if ($sort === 'title_asc') {
            $sql .= " ORDER BY title ASC";
        } elseif ($sort === 'seats_desc') {
            $sql .= " ORDER BY max_seats DESC";
        } else {
            $sql .= " ORDER BY event_date ASC, id DESC";
        }

        $sql .= " LIMIT $limit OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $all_events = $stmt->fetchAll();
    } catch (Exception $e) {
        // fallback
    }
}

// Fallback to JSON file if database is empty
if (empty($all_events)) {
    $jsonFile = DATA_PATH . '/events.json';
    if (file_exists($jsonFile)) {
        $json_data = json_decode(file_get_contents($jsonFile), true) ?: [];
        if (!empty($search)) {
            $json_data = array_values(array_filter($json_data, fn($e) => stripos($e['title'], $search) !== false || stripos($e['description'], $search) !== false));
        }
        if (!empty($category) && $category !== 'all') {
            $json_data = array_values(array_filter($json_data, fn($e) => $e['category'] === $category));
        }
        $total_items = count($json_data);
        $all_events = array_slice($json_data, $offset, $limit);
    }
}

$total_pages = ceil($total_items / $limit);
?>

<!-- Breadcrumbs (Practical 2) -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb py-2 px-3 bg-body-tertiary rounded-3 shadow-sm">
    <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house-door me-1"></i>Home</a></li>
    <li class="breadcrumb-item active" aria-current="page">Events Catalogue</li>
  </ol>
</nav>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h1 class="fw-bold mb-1">Campus Events &amp; Activities</h1>
    <p class="text-muted mb-0">Browse technical workshops, cultural celebrations, and academic competitions.</p>
  </div>
  <?php if (is_admin()): ?>
    <a href="admin_events.php" class="btn btn-primary rounded-pill px-3">
      <i class="bi bi-plus-circle me-1"></i>Add New Event
    </a>
  <?php endif; ?>
</div>

<!-- Search, Filter & Sort Form (Practical 6) -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-body">
  <div class="card-body p-3 p-md-4">
    <form method="GET" action="events.php" class="row g-3 align-items-center">
      <div class="col-md-5">
        <label for="search" class="form-label small fw-semibold text-muted">Search Events</label>
        <div class="input-group">
          <span class="input-group-text bg-body border-end-0"><i class="bi bi-search"></i></span>
          <input type="text" id="search" name="search" class="form-control border-start-0" 
                 placeholder="Search by title, topic, or venue..." value="<?php echo e($search); ?>">
        </div>
      </div>

      <div class="col-md-3">
        <label for="category" class="form-label small fw-semibold text-muted">Filter by Category</label>
        <select id="category" name="category" class="form-select">
          <option value="all">All Categories</option>
          <option value="Technical" <?php echo $category === 'Technical' ? 'selected' : ''; ?>>Technical</option>
          <option value="Workshop" <?php echo $category === 'Workshop' ? 'selected' : ''; ?>>Workshop</option>
          <option value="Seminar" <?php echo $category === 'Seminar' ? 'selected' : ''; ?>>Seminar</option>
          <option value="Cultural" <?php echo $category === 'Cultural' ? 'selected' : ''; ?>>Cultural</option>
          <option value="Sports" <?php echo $category === 'Sports' ? 'selected' : ''; ?>>Sports</option>
        </select>
      </div>

      <div class="col-md-2">
        <label for="sort" class="form-label small fw-semibold text-muted">Sort By</label>
        <select id="sort" name="sort" class="form-select">
          <option value="date_asc" <?php echo $sort === 'date_asc' ? 'selected' : ''; ?>>Date (Soonest)</option>
          <option value="title_asc" <?php echo $sort === 'title_asc' ? 'selected' : ''; ?>>Title (A-Z)</option>
          <option value="seats_desc" <?php echo $sort === 'seats_desc' ? 'selected' : ''; ?>>Max Seats</option>
        </select>
      </div>

      <div class="col-md-2 d-flex align-items-end gap-2 mt-md-auto">
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter me-1"></i>Filter</button>
        <?php if (!empty($search) || !empty($category)): ?>
          <a href="events.php" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- Events Grid (Practical 3 & 6) -->
<div class="row g-4 mb-5">
  <?php if (empty($all_events)): ?>
    <div class="col-12 text-center py-5">
      <div class="display-6 text-muted mb-3"><i class="bi bi-calendar-x"></i></div>
      <h3 class="fs-4 fw-bold">No Events Found</h3>
      <p class="text-muted">No campus events match your current search criteria. Try resetting filters.</p>
      <a href="events.php" class="btn btn-primary rounded-pill px-4">View All Events</a>
    </div>
  <?php else: ?>
    <?php foreach ($all_events as $event): ?>
      <?php 
        $poster = !empty($event['poster']) ? $event['poster'] : 'default-poster.svg';
        $poster_src = (strpos($poster, 'sample') === 0) ? 'uploads/posters/' . $poster : 'assets/img/' . $poster;
        $status_class = ($event['status'] === 'Open') ? 'bg-success' : (($event['status'] === 'Closed') ? 'bg-danger' : 'bg-warning text-dark');
        $reg_count = $event['registered_count'] ?? 0;
        $max_seats = $event['max_seats'] ?? 50;
        $is_full = $reg_count >= $max_seats;
      ?>
      <div class="col-md-6 col-lg-4">
        <div class="event-card h-100">
          <div class="event-poster-container">
            <img src="<?php echo e($poster_src); ?>" alt="<?php echo e($event['title']); ?>" class="event-poster-img" onerror="this.src='assets/img/default-poster.svg'">
            <span class="event-badge <?php echo $status_class; ?>"><?php echo e($event['status']); ?></span>
            <span class="badge bg-dark bg-opacity-75 position-absolute bottom-0 start-0 m-2"><?php echo e($event['category']); ?></span>
          </div>

          <div class="event-body">
            <h3 class="fs-5 fw-bold text-primary mb-2"><?php echo e($event['title']); ?></h3>
            <p class="text-muted small mb-3 flex-grow-1">
              <?php echo e(substr($event['description'], 0, 110)) . '...'; ?>
            </p>

            <div class="small text-secondary mb-3">
              <div><i class="bi bi-calendar3 me-2"></i><strong>Date:</strong> <?php echo e($event['event_date']); ?> (<?php echo e($event['event_time'] ?? '10:00 AM'); ?>)</div>
              <div><i class="bi bi-geo-alt me-2"></i><strong>Venue:</strong> <?php echo e($event['location']); ?></div>
              <div class="d-flex justify-content-between align-items-center mt-2">
                <span><i class="bi bi-people me-1"></i>Seats: <strong><?php echo $reg_count; ?></strong> / <?php echo $max_seats; ?></span>
                <span class="badge <?php echo $is_full ? 'bg-danger' : 'bg-success-subtle text-success'; ?>">
                  <?php echo $is_full ? 'Housefull' : ($max_seats - $reg_count) . ' Left'; ?>
                </span>
              </div>
            </div>

            <div class="pt-2 border-top d-flex gap-2">
              <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" data-bs-toggle="modal" data-bs-target="#eventModal<?php echo $event['id']; ?>">
                <i class="bi bi-info-circle me-1"></i>Details
              </button>

              <?php if (is_logged_in() && !is_admin()): ?>
                <?php if ($event['status'] === 'Open' && !$is_full): ?>
                  <form action="event_register.php" method="POST" class="flex-fill">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                      <i class="bi bi-check2-circle me-1"></i>Register
                    </button>
                  </form>
                <?php else: ?>
                  <button class="btn btn-secondary btn-sm flex-fill" disabled>
                    <?php echo $is_full ? 'Full' : 'Closed'; ?>
                  </button>
                <?php endif; ?>
              <?php elseif (!is_logged_in()): ?>
                <a href="login.php" class="btn btn-outline-primary btn-sm flex-fill">
                  <i class="bi bi-box-arrow-in-right me-1"></i>Sign in to Register
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Event Details Modal (Practical 4 Modal Popup) -->
      <div class="modal fade" id="eventModal<?php echo $event['id']; ?>" tabindex="-1" aria-labelledby="modalLabel<?php echo $event['id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title fw-bold" id="modalLabel<?php echo $event['id']; ?>"><?php echo e($event['title']); ?></h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <img src="<?php echo e($poster_src); ?>" class="img-fluid rounded-3 mb-3 w-100" style="max-height: 220px; object-fit: cover;" alt="<?php echo e($event['title']); ?>">
              <span class="badge bg-primary mb-2"><?php echo e($event['category']); ?></span>
              <span class="badge <?php echo $status_class; ?> mb-2"><?php echo e($event['status']); ?></span>
              <p class="mt-2 text-muted"><?php echo nl2br(e($event['description'])); ?></p>
              <hr>
              <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                <li><i class="bi bi-calendar-event me-2 text-primary"></i><strong>Date &amp; Time:</strong> <?php echo e($event['event_date']); ?> at <?php echo e($event['event_time'] ?? '10:00 AM'); ?></li>
                <li><i class="bi bi-geo-alt me-2 text-primary"></i><strong>Venue:</strong> <?php echo e($event['location']); ?></li>
                <li><i class="bi bi-people me-2 text-primary"></i><strong>Seat Capacity:</strong> <?php echo $max_seats; ?> maximum (<?php echo $reg_count; ?> enrolled)</li>
              </ul>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <?php if (is_logged_in() && !is_admin() && $event['status'] === 'Open' && !$is_full): ?>
                <form action="event_register.php" method="POST">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                  <button type="submit" class="btn btn-primary">Confirm Registration</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Pagination (Practical 6) -->
<?php if ($total_pages > 1): ?>
  <nav aria-label="Events pagination" class="d-flex justify-content-center mb-5">
    <ul class="pagination shadow-sm">
      <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>" aria-label="Previous">
          <span aria-hidden="true">&laquo;</span>
        </a>
      </li>
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
          <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>"><?php echo $i; ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>" aria-label="Next">
          <span aria-hidden="true">&raquo;</span>
        </a>
      </li>
    </ul>
  </nav>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
