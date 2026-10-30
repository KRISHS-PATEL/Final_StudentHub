<?php
/**
 * StudentHub Portal - Student Feedback & Ratings
 * Practical 7: PHP Form Processing & Multi-Format Storage
 */

$page_title = "Student Feedback & Rating";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$errors = [];
$success = false;

$student_name = '';
$category = 'Portal Usability';
$rating = 5;
$comments = '';

if (is_logged_in()) {
    $u = current_user();
    $student_name = $u['username'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Session expired. Please try submitting again.";
    }

    $student_name = trim(filter_input(INPUT_POST, 'student_name', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $rating = (int)($_POST['rating'] ?? 5);
    $category = trim(filter_input(INPUT_POST, 'category', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'Portal Usability');
    $comments = trim(filter_input(INPUT_POST, 'comments', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

    if (empty($student_name) || strlen($student_name) < 2) {
        $errors[] = "Please provide your student name.";
    }

    if ($rating < 1 || $rating > 5) {
        $errors[] = "Rating must be between 1 and 5 stars.";
    }

    if (empty($comments) || strlen($comments) < 5) {
        $errors[] = "Comments must be at least 5 characters.";
    }

    if (empty($errors)) {
        $timestamp = date('Y-m-d H:i:s');

        // 1. Append to CSV
        $csv_file = DATA_PATH . '/feedback.csv';
        if ($fp = @fopen($csv_file, 'a')) {
            if (flock($fp, LOCK_EX)) {
                if (filesize($csv_file) === 0) {
                    fputcsv($fp, ['Student_Name', 'Rating', 'Category', 'Comments', 'Submitted_At']);
                }
                fputcsv($fp, [$student_name, $rating, $category, $comments, $timestamp]);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }

        // 2. Append to JSON
        $json_file = DATA_PATH . '/submissions.json';
        $submissions = file_exists($json_file) ? json_decode(file_get_contents($json_file), true) : [];
        if (!is_array($submissions)) $submissions = [];

        $submissions[] = [
            'type' => 'feedback',
            'student_name' => $student_name,
            'rating' => $rating,
            'category' => $category,
            'comments' => $comments,
            'timestamp' => $timestamp
        ];
        @file_put_contents($json_file, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

        // 3. Insert into DB
        if ($pdo) {
            try {
                $uid = $_SESSION['user_id'] ?? null;
                $stmt = $pdo->prepare("INSERT INTO feedback (user_id, student_name, rating, category, comments) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$uid, $student_name, $rating, $category, $comments]);
                log_audit($uid, $student_name, "Submitted $rating-star feedback ($category)");
            } catch (Exception $e) {
                // Ignore DB error if CSV saved
            }
        }

        $success = true;
        $comments = '';
    }
}
?>

<!-- Breadcrumbs (Practical 2) -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb py-2 px-3 bg-body-tertiary rounded-3 shadow-sm">
    <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house-door me-1"></i>Home</a></li>
    <li class="breadcrumb-item active" aria-current="page">Student Feedback</li>
  </ol>
</nav>

<div class="row justify-content-center mb-5">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm rounded-4 bg-body">
      <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 text-center">
        <div class="text-warning display-6 mb-2"><i class="bi bi-star-half"></i></div>
        <h1 class="h3 fw-bold mb-1 text-primary">Student Experience &amp; Feedback</h1>
        <p class="text-muted small mb-0">Help us continuously improve portal usability, accessibility, and features.</p>
      </div>

      <div class="card-body p-4 p-md-5">
        <?php if ($success): ?>
          <div class="alert alert-success alert-dismissible fade show shadow-sm text-center py-4" role="alert">
            <i class="bi bi-heart-fill text-danger fs-3 d-block mb-2"></i>
            <h4 class="alert-heading fs-5 fw-bold mb-1">Thank You for Your Feedback!</h4>
            <p class="mb-0 small">Your valuable feedback has been recorded safely in <code>data/feedback.csv</code> and the central database.</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <h5 class="alert-heading fs-6 fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Errors:</h5>
            <ul class="mb-0 ps-3 small">
              <?php foreach ($errors as $err): ?>
                <li><?php echo e($err); ?></li>
              <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <form method="POST" action="feedback.php">
          <?php echo csrf_field(); ?>

          <div class="mb-3">
            <label for="student_name" class="form-label fw-semibold">Your Name <span class="text-danger">*</span></label>
            <input type="text" id="student_name" name="student_name" class="form-control" placeholder="Aarav Sharma" value="<?php echo e($student_name); ?>" required>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label for="category" class="form-label fw-semibold">Feedback Category</label>
              <select id="category" name="category" class="form-select">
                <option value="Portal Usability" <?php echo $category === 'Portal Usability' ? 'selected' : ''; ?>>Portal Usability</option>
                <option value="Events System" <?php echo $category === 'Events System' ? 'selected' : ''; ?>>Events &amp; Registrations</option>
                <option value="Dark Mode & UI" <?php echo $category === 'Dark Mode & UI' ? 'selected' : ''; ?>>Dark Mode &amp; UI Styling</option>
                <option value="Accessibility" <?php echo $category === 'Accessibility' ? 'selected' : ''; ?>>WCAG 2.1 AA Accessibility</option>
                <option value="Other Suggestions" <?php echo $category === 'Other Suggestions' ? 'selected' : ''; ?>>Other Suggestions</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold d-block">Overall Rating <span class="text-danger">*</span></label>
              <div class="d-flex gap-3 pt-2">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="rating" id="rating<?php echo $i; ?>" value="<?php echo $i; ?>" <?php echo $rating === $i ? 'checked' : ''; ?>>
                    <label class="form-check-label fw-bold text-warning" for="rating<?php echo $i; ?>">
                      <?php echo $i; ?> &#9733;
                    </label>
                  </div>
                <?php endfor; ?>
              </div>
            </div>
          </div>

          <div class="mb-4">
            <label for="comments" class="form-label fw-semibold">Your Comments / Suggestions <span class="text-danger">*</span></label>
            <textarea id="comments" name="comments" rows="4" class="form-control" placeholder="What features did you like most? What can we improve?" required><?php echo e($comments); ?></textarea>
          </div>

          <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg fw-semibold shadow-sm">
              <i class="bi bi-star-fill me-2"></i>Submit Feedback
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
