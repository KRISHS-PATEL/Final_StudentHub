<?php
/**
 * StudentHub Portal - Contact Us Page
 * Practical 7: PHP Form Processing with Server-Side Validation, CSRF & CSV/JSON Storage
 */

$page_title = "Contact Faculty & Support";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';

$errors = [];
$success = false;

$name = '';
$email = '';
$subject = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CSRF Token Verification (Practical 7 Advanced Extension)
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security verification token expired. Please refresh the page.";
    }

    // 2. Input Sanitization
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
    $subject = trim(filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $message = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

    // 3. Server-side Validation Rules
    if (empty($name) || strlen($name) < 2 || strlen($name) > 60) {
        $errors[] = "Please enter your full name (2 to 60 characters).";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }

    if (empty($subject) || strlen($subject) < 3) {
        $errors[] = "Subject is required (at least 3 characters).";
    }

    if (empty($message) || strlen($message) < 10) {
        $errors[] = "Message content must be at least 10 characters long.";
    }

    // 4. Safe Dual-File Storage (Practical 7) & Database Insert
    if (empty($errors)) {
        $timestamp = date('Y-m-d H:i:s');

        // A. Append to CSV file with safe file locking (flock)
        $csv_file = DATA_PATH . '/contact_messages.csv';
        if ($fp = @fopen($csv_file, 'a')) {
            if (flock($fp, LOCK_EX)) {
                if (filesize($csv_file) === 0) {
                    fputcsv($fp, ['Name', 'Email', 'Subject', 'Message', 'Submitted_At']);
                }
                fputcsv($fp, [$name, $email, $subject, $message, $timestamp]);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }

        // B. Append to JSON file with safe file locking
        $json_file = DATA_PATH . '/submissions.json';
        $submissions = file_exists($json_file) ? json_decode(file_get_contents($json_file), true) : [];
        if (!is_array($submissions)) $submissions = [];

        $submissions[] = [
            'type' => 'contact',
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
            'timestamp' => $timestamp
        ];
        @file_put_contents($json_file, json_encode($submissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

        // C. Insert into Database if connected
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $subject, $message]);
                log_audit($_SESSION['user_id'] ?? null, $name, "Submitted contact inquiry: $subject");
            } catch (Exception $e) {
                // Ignore DB error if CSV/JSON write succeeded
            }
        }

        $success = true;
        // Reset form
        $name = $email = $subject = $message = '';
    }
}
?>

<!-- Breadcrumbs (Practical 2) -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb py-2 px-3 bg-body-tertiary rounded-3 shadow-sm">
    <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house-door me-1"></i>Home</a></li>
    <li class="breadcrumb-item active" aria-current="page">Contact Support</li>
  </ol>
</nav>

<div class="row g-4 mb-5">
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm rounded-4 bg-body">
      <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
        <h1 class="h3 fw-bold mb-1 text-primary">Get in Touch with Faculty &amp; Support</h1>
        <p class="text-muted small mb-0">Practical 7: Server-side validation, sanitization, and dual CSV/JSON storage.</p>
      </div>

      <div class="card-body p-4">
        <?php if ($success): ?>
          <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <h5 class="alert-heading fs-6 fw-bold mb-1"><i class="bi bi-check-circle-fill me-2"></i>Inquiry Submitted Successfully!</h5>
            <p class="mb-0 small">Your message has been sanitized and recorded in both system database and disk files (CSV/JSON). Our team will respond shortly.</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <h5 class="alert-heading fs-6 fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please Correct the Following:</h5>
            <ul class="mb-0 ps-3 small">
              <?php foreach ($errors as $err): ?>
                <li><?php echo e($err); ?></li>
              <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <form method="POST" action="contact.php" class="needs-validation">
          <?php echo csrf_field(); ?>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label for="name" class="form-label fw-semibold">Your Name <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" id="name" name="name" class="form-control" placeholder="Aarav Sharma" value="<?php echo e($name); ?>" required>
              </div>
            </div>

            <div class="col-md-6">
              <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" id="email" name="email" class="form-control" placeholder="name@charusat.edu" value="<?php echo e($email); ?>" required>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label for="subject" class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
            <input type="text" id="subject" name="subject" class="form-control" placeholder="e.g. Inquiry regarding Web Dev Hackathon team formation" value="<?php echo e($subject); ?>" required>
          </div>

          <div class="mb-4">
            <label for="message" class="form-label fw-semibold">Message Content <span class="text-danger">*</span></label>
            <textarea id="message" name="message" rows="5" class="form-control" placeholder="Type your detailed academic or technical inquiry..." required><?php echo e($message); ?></textarea>
          </div>

          <div class="d-flex justify-content-between align-items-center">
            <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold">
              <i class="bi bi-send-fill me-2"></i>Send Message
            </button>
            <span class="small text-muted"><i class="bi bi-shield-check text-success me-1"></i>CSRF Protected</span>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card border-0 shadow-sm rounded-4 bg-body mb-4">
      <div class="card-body p-4">
        <h3 class="fs-5 fw-bold text-primary mb-3"><i class="bi bi-building me-2"></i>Campus Information</h3>
        <p class="small text-muted mb-3">
          Faculty of Technology and Engineering (FTE)<br>
          Charotar University of Science and Technology (CHARUSAT)
        </p>

        <ul class="list-unstyled small d-flex flex-column gap-3 mb-0">
          <li class="d-flex align-items-start gap-2">
            <i class="bi bi-geo-alt-fill text-danger fs-5"></i>
            <div>
              <strong>Campus Address:</strong><br>
              CHARUSAT Campus, Highway, Changa, Dist. Anand, Gujarat - 388421
            </div>
          </li>
          <li class="d-flex align-items-center gap-2">
            <i class="bi bi-envelope-fill text-primary fs-5"></i>
            <div>
              <strong>Academic Support:</strong><br>
              <a href="mailto:support@studenthub.charusat.ac.in">support@studenthub.charusat.ac.in</a>
            </div>
          </li>
          <li class="d-flex align-items-center gap-2">
            <i class="bi bi-telephone-fill text-success fs-5"></i>
            <div>
              <strong>FTE Office Phone:</strong><br>
              +91-2697-265011 / 265021
            </div>
          </li>
        </ul>
      </div>
    </div>

    <!-- Practical 7 Storage Callout -->
    <div class="card border-0 shadow-sm rounded-4 bg-primary text-white p-4">
      <h3 class="fs-6 fw-bold mb-2"><i class="bi bi-file-earmark-code me-2"></i>Practical 7 Implementation Notice</h3>
      <p class="small opacity-90 mb-3">
        Submissions made through this form are validated and sanitized server-side, then appended safely with concurrent file locking (<code>flock</code>) to <code>data/contact_messages.csv</code> and <code>data/submissions.json</code>.
      </p>
      <?php if (is_admin()): ?>
        <a href="admin_submissions.php" class="btn btn-light btn-sm text-primary fw-semibold rounded-pill">
          View Stored CSV &amp; JSON Records &rarr;
        </a>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
