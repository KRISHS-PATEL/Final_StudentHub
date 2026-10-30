<?php
/**
 * StudentHub Portal - Event Management CRUD with File Upload
 * Practical 12: Event CRUD with Poster Upload & File Validation
 */

$page_title = "Event Management & Posters";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();
$admin = current_user();

// Handle CREATE Event with Poster Upload (Practical 12)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'Technical');
        $desc = trim($_POST['description'] ?? '');
        $date = trim($_POST['event_date'] ?? '');
        $time = trim($_POST['event_time'] ?? '10:00:00');
        $location = trim($_POST['location'] ?? 'Campus Lab');
        $max_seats = (int)($_POST['max_seats'] ?? 50);
        $status = trim($_POST['status'] ?? 'Open');
        $poster_filename = 'default-poster.svg';

        $upload_error = '';

        // Validate & Process Poster Upload
        if (!empty($_FILES['poster']['name']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['poster'];
            $fileSize = $file['size'];
            $fileTmp = $file['tmp_name'];
            $originalName = $file['name'];
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

            // 1. File extension validation
            if (!in_array($ext, $allowed_exts, true)) {
                $upload_error = "Invalid poster file format! Only JPG, JPEG, PNG, WEBP, and GIF are permitted.";
            }
            // 2. File size limit validation (Max 2MB)
            elseif ($fileSize > 2097152) {
                $upload_error = "Poster file is too large! Maximum allowed upload size is 2MB.";
            } else {
                // 3. MIME type inspection
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $fileTmp);
                finfo_close($finfo);

                if (!in_array($mime, $allowed_mimes, true)) {
                    $upload_error = "File content does not match genuine image structure ($mime).";
                } else {
                    // 4. Safe image renaming (Practical 12 Advanced Extension)
                    $poster_filename = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
                    $destination = POSTERS_PATH . '/' . $poster_filename;

                    if (!move_uploaded_file($fileTmp, $destination)) {
                        $upload_error = "Failed to store uploaded poster file on server.";
                    }
                }
            }
        }

        if (!empty($upload_error)) {
            set_flash('danger', $upload_error);
        } elseif (empty($title) || empty($date)) {
            set_flash('danger', 'Event Title and Date are required.');
        } elseif ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO events (title, category, description, event_date, event_time, location, max_seats, poster, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $category, $desc, $date, $time, $location, $max_seats, $poster_filename, $status, $admin['id']]);

                log_audit($admin['id'], $admin['username'], "Created event with poster: $title (Poster: $poster_filename)");
                set_flash('success', "Event \"$title\" created successfully with validated poster upload!");
            } catch (Exception $e) {
                set_flash('danger', 'Database insertion failed: ' . $e->getMessage());
            }
        }
        header("Location: admin_events.php");
        exit();
    }
}

// Handle UPDATE Event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_event'])) {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $id = (int)($_POST['event_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $date = trim($_POST['event_date'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $status = trim($_POST['status'] ?? 'Open');

        if ($pdo && $id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE events SET title = ?, category = ?, description = ?, event_date = ?, location = ?, status = ? WHERE id = ?");
                $stmt->execute([$title, $category, $desc, $date, $location, $status, $id]);

                log_audit($admin['id'], $admin['username'], "Updated event #$id ($title)");
                set_flash('success', "Event updated successfully!");
            } catch (Exception $e) {
                set_flash('danger', 'Update failed: ' . $e->getMessage());
            }
        }
        header("Location: admin_events.php");
        exit();
    }
}

// Handle DELETE Event
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($pdo && $id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$id]);

            log_audit($admin['id'], $admin['username'], "Deleted event #$id");
            set_flash('info', 'Event record and linked registrations deleted.');
        } catch (Exception $e) {
            set_flash('danger', 'Delete failed: ' . $e->getMessage());
        }
    }
    header("Location: admin_events.php");
    exit();
}

// Filters
$category_filter = trim($_GET['category'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$events = [];
if ($pdo) {
    try {
        $sql = "SELECT * FROM events WHERE 1=1";
        $params = [];

        if (!empty($category_filter) && $category_filter !== 'all') {
            $sql .= " AND category = ?";
            $params[] = $category_filter;
        }

        if (!empty($status_filter) && $status_filter !== 'all') {
            $sql .= " AND status = ?";
            $params[] = $status_filter;
        }

        $sql .= " ORDER BY event_date ASC, id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll();
    } catch (Exception $e) {
        // fallback
    }
}

if (empty($events)) {
    $eventsJson = file_exists(DATA_PATH . '/events.json') ? json_decode(file_get_contents(DATA_PATH . '/events.json'), true) : [];
    $events = $eventsJson;
}
?>

<!-- Breadcrumbs (Practical 2) -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb py-2 px-3 bg-body-tertiary rounded-3 shadow-sm">
    <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house-door me-1"></i>Home</a></li>
    <li class="breadcrumb-item"><a href="admin_dashboard.php">Admin Hub</a></li>
    <li class="breadcrumb-item active" aria-current="page">Event Management</li>
  </ol>
</nav>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1"><i class="bi bi-calendar2-range text-primary me-2"></i>Event Management &amp; Poster Upload</h1>
    <p class="text-muted mb-0">Practical 12: Admin CRUD, poster file validation (MIME/size), and status controls.</p>
  </div>
  <button type="button" class="btn btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#createEventModal">
    <i class="bi bi-plus-circle-fill me-1"></i>Create New Event
  </button>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-body">
  <div class="card-body p-3">
    <form method="GET" action="admin_events.php" class="row g-3 align-items-center">
      <div class="col-md-5">
        <label for="cat" class="form-label small fw-semibold text-muted">Category Filter</label>
        <select id="cat" name="category" class="form-select">
          <option value="all">All Categories</option>
          <option value="Technical" <?php echo $category_filter === 'Technical' ? 'selected' : ''; ?>>Technical</option>
          <option value="Workshop" <?php echo $category_filter === 'Workshop' ? 'selected' : ''; ?>>Workshop</option>
          <option value="Seminar" <?php echo $category_filter === 'Seminar' ? 'selected' : ''; ?>>Seminar</option>
          <option value="Cultural" <?php echo $category_filter === 'Cultural' ? 'selected' : ''; ?>>Cultural</option>
          <option value="Sports" <?php echo $category_filter === 'Sports' ? 'selected' : ''; ?>>Sports</option>
        </select>
      </div>

      <div class="col-md-5">
        <label for="st" class="form-label small fw-semibold text-muted">Status Filter</label>
        <select id="st" name="status" class="form-select">
          <option value="all">All Statuses</option>
          <option value="Open" <?php echo $status_filter === 'Open' ? 'selected' : ''; ?>>Open for Registration</option>
          <option value="Closed" <?php echo $status_filter === 'Closed' ? 'selected' : ''; ?>>Closed / Full</option>
          <option value="Upcoming" <?php echo $status_filter === 'Upcoming' ? 'selected' : ''; ?>>Upcoming</option>
        </select>
      </div>

      <div class="col-md-2 d-flex align-items-end mt-md-auto">
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter me-1"></i>Filter</button>
      </div>
    </form>
  </div>
</div>

<!-- Events Table -->
<div class="card border-0 shadow-sm rounded-4 mb-5 bg-body">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th scope="col" class="ps-4">Poster</th>
            <th scope="col">Event Details</th>
            <th scope="col">Category</th>
            <th scope="col">Date / Venue</th>
            <th scope="col">Seats</th>
            <th scope="col">Status</th>
            <th scope="col" class="text-end pe-4">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($events as $ev): ?>
            <?php 
              $poster = !empty($ev['poster']) ? $ev['poster'] : 'default-poster.svg';
              $poster_src = (strpos($poster, 'sample') === 0) ? 'uploads/posters/' . $poster : (file_exists(POSTERS_PATH . '/' . $poster) ? 'uploads/posters/' . $poster : 'assets/img/' . $poster);
              $badge_color = ($ev['status'] === 'Open') ? 'bg-success' : (($ev['status'] === 'Closed') ? 'bg-danger' : 'bg-warning text-dark');
            ?>
            <tr>
              <td class="ps-4">
                <img src="<?php echo e($poster_src); ?>" alt="Poster" class="rounded-3 shadow-sm" style="width: 60px; height: 42px; object-fit: cover;" onerror="this.src='assets/img/default-poster.svg'">
              </td>
              <td>
                <span class="fw-bold text-primary"><?php echo e($ev['title']); ?></span>
                <div class="small text-muted"><?php echo e(substr($ev['description'] ?? '', 0, 75)) . '...'; ?></div>
              </td>
              <td><span class="badge bg-primary-subtle text-primary"><?php echo e($ev['category']); ?></span></td>
              <td class="small">
                <div><i class="bi bi-calendar3 me-1 text-muted"></i><?php echo e($ev['event_date']); ?></div>
                <div class="text-muted"><?php echo e($ev['location']); ?></div>
              </td>
              <td class="small">
                <strong><?php echo $ev['registered_count'] ?? 0; ?></strong> / <?php echo $ev['max_seats']; ?>
              </td>
              <td><span class="badge <?php echo $badge_color; ?>"><?php echo e($ev['status']); ?></span></td>
              <td class="text-end pe-4">
                <div class="d-flex justify-content-end gap-1">
                  <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editEventModal<?php echo $ev['id']; ?>" title="Edit Event">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <a href="admin_events.php?delete=<?php echo $ev['id']; ?>" class="btn btn-outline-danger btn-sm" 
                     onclick="return confirm('Delete event <?php echo e($ev['title']); ?> and its registration records?');" title="Delete Event">
                    <i class="bi bi-trash"></i>
                  </a>
                </div>
              </td>
            </tr>

            <!-- Edit Event Modal -->
            <div class="modal fade" id="editEventModal<?php echo $ev['id']; ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="POST" action="admin_events.php">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="event_id" value="<?php echo $ev['id']; ?>">
                    <div class="modal-header">
                      <h5 class="modal-title fw-bold">Edit Event: <?php echo e($ev['title']); ?></h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="mb-3">
                        <label class="form-label fw-semibold">Event Title</label>
                        <input type="text" name="title" class="form-control" value="<?php echo e($ev['title']); ?>" required>
                      </div>
                      <div class="row g-2 mb-3">
                        <div class="col-6">
                          <label class="form-label fw-semibold">Category</label>
                          <select name="category" class="form-select">
                            <option value="Technical" <?php echo $ev['category'] === 'Technical' ? 'selected' : ''; ?>>Technical</option>
                            <option value="Workshop" <?php echo $ev['category'] === 'Workshop' ? 'selected' : ''; ?>>Workshop</option>
                            <option value="Seminar" <?php echo $ev['category'] === 'Seminar' ? 'selected' : ''; ?>>Seminar</option>
                            <option value="Cultural" <?php echo $ev['category'] === 'Cultural' ? 'selected' : ''; ?>>Cultural</option>
                            <option value="Sports" <?php echo $ev['category'] === 'Sports' ? 'selected' : ''; ?>>Sports</option>
                          </select>
                        </div>
                        <div class="col-6">
                          <label class="form-label fw-semibold">Status</label>
                          <select name="status" class="form-select">
                            <option value="Open" <?php echo $ev['status'] === 'Open' ? 'selected' : ''; ?>>Open</option>
                            <option value="Closed" <?php echo $ev['status'] === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                            <option value="Upcoming" <?php echo $ev['status'] === 'Upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                          </select>
                        </div>
                      </div>
                      <div class="row g-2 mb-3">
                        <div class="col-6">
                          <label class="form-label fw-semibold">Date</label>
                          <input type="date" name="event_date" class="form-control" value="<?php echo e($ev['event_date']); ?>" required>
                        </div>
                        <div class="col-6">
                          <label class="form-label fw-semibold">Venue</label>
                          <input type="text" name="location" class="form-control" value="<?php echo e($ev['location']); ?>" required>
                        </div>
                      </div>
                      <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo e($ev['description']); ?></textarea>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" name="edit_event" class="btn btn-primary">Update Event</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Create Event Modal (Practical 12) -->
<div class="modal fade" id="createEventModal" tabindex="-1" aria-labelledby="createEventLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <!-- File upload form with multipart/form-data -->
      <form method="POST" action="admin_events.php" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="createEventLabel">Create Campus Event</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Event Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" placeholder="e.g. AI Hackathon 2026" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
              <select name="category" class="form-select" required>
                <option value="Technical">Technical</option>
                <option value="Workshop">Workshop</option>
                <option value="Seminar">Seminar</option>
                <option value="Cultural">Cultural</option>
                <option value="Sports">Sports</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold">Status</label>
              <select name="status" class="form-select">
                <option value="Open">Open</option>
                <option value="Upcoming">Upcoming</option>
                <option value="Closed">Closed</option>
              </select>
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold">Event Date <span class="text-danger">*</span></label>
              <input type="date" name="event_date" class="form-control" required>
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold">Event Time</label>
              <input type="time" name="event_time" class="form-control" value="10:00">
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-8">
              <label class="form-label fw-semibold">Location / Venue <span class="text-danger">*</span></label>
              <input type="text" name="location" class="form-control" placeholder="e.g. Lab 3, IT Block" required>
            </div>
            <div class="col-4">
              <label class="form-label fw-semibold">Max Seats</label>
              <input type="number" name="max_seats" class="form-control" value="60" min="1" max="1000">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Event Description</label>
            <textarea name="description" rows="3" class="form-control" placeholder="Outline event objectives and agenda..."></textarea>
          </div>

          <!-- Poster File Upload (Practical 12) -->
          <div class="mb-3">
            <label for="posterInput" class="form-label fw-semibold">
              Event Poster Image (Max 2MB, JPG/PNG/WEBP/GIF)
            </label>
            <input type="file" id="posterInput" name="poster" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
            <div class="form-text small">Server-side MIME check, extension validation, and safe renaming will be applied.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="add_event" class="btn btn-primary"><i class="bi bi-cloud-arrow-up me-1"></i>Publish Event</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
