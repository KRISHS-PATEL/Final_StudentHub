<?php
/**
 * StudentHub Portal - Student Registration Form
 * Practical 5: Frontend Validation & Custom Canvas CAPTCHA
 * Practical 9: Secure Registration, Duplicate Checks & Password Hashing
 */

$page_title = "Student Registration";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: " . (is_admin() ? 'admin_dashboard.php' : 'student_dashboard.php'));
    exit();
}

$errors = [];
$form_data = [
    'username'   => '',
    'full_name'  => '',
    'email'      => '',
    'phone'      => '',
    'department' => 'Information Technology',
    'course'     => '',
    'year'       => '',
    'gender'     => '',
    'address'    => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token (Practical 9)
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security session expired. Please refresh and try again.";
    }

    $form_data['username']   = trim($_POST['username'] ?? '');
    $form_data['full_name']  = trim($_POST['full_name'] ?? '');
    $form_data['email']      = trim($_POST['email'] ?? '');
    $form_data['phone']      = trim($_POST['phone'] ?? '');
    $form_data['department'] = trim($_POST['department'] ?? 'Information Technology');
    $form_data['course']     = trim($_POST['course'] ?? '');
    $form_data['year']       = trim($_POST['year'] ?? '');
    $form_data['gender']     = trim($_POST['gender'] ?? '');
    $form_data['address']    = trim($_POST['address'] ?? '');
    $password                = $_POST['password'] ?? '';
    $confirm_password        = $_POST['confirm_password'] ?? '';
    $terms                   = isset($_POST['terms']);

    // 2. Server-side Validations (Practical 5 & 9)
    if (empty($form_data['username']) || !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $form_data['username'])) {
        $errors[] = "Username must be between 3 and 30 characters (letters, numbers, underscores).";
    }

    if (empty($form_data['full_name']) || !preg_match('/^[a-zA-Z\s]{2,50}$/', $form_data['full_name'])) {
        $errors[] = "Full Name must contain 2-50 alphabet characters only.";
    }

    if (empty($form_data['email']) || !filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }

    if (empty($form_data['phone']) || !preg_match('/^[6-9]\d{9}$/', $form_data['phone'])) {
        $errors[] = "Mobile number must be a valid 10-digit Indian number starting with 6-9.";
    }

    $valid_courses = ['B.Tech IT', 'B.Tech CSE', 'BCA', 'MCA', 'BSc IT'];
    if (!in_array($form_data['course'], $valid_courses, true)) {
        $errors[] = "Please select a valid academic course from the list.";
    }

    if (empty($form_data['year'])) {
        $errors[] = "Please select your study year.";
    }

    if (empty($form_data['gender']) || !in_array($form_data['gender'], ['Male', 'Female', 'Other'], true)) {
        $errors[] = "Please select your gender.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters in length.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Password and Confirm Password do not match.";
    }

    if (!$terms) {
        $errors[] = "You must agree to the Student Conduct Terms and Conditions.";
    }

    // 3. Database Duplicate Checks (Practical 9)
    if (empty($errors) && $pdo) {
        try {
            $dup_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
            $dup_stmt->execute([$form_data['username'], $form_data['email']]);
            if ($dup_stmt->fetch()) {
                $errors[] = "An account with that username or email address already exists.";
            }

            // Also check students table
            $dup_student = $pdo->prepare("SELECT id FROM students WHERE email = ? LIMIT 1");
            $dup_student->execute([$form_data['email']]);
            if ($dup_student->fetch()) {
                $errors[] = "A student profile with that email is already registered.";
            }
        } catch (Exception $e) {
            $errors[] = "Verification error: " . $e->getMessage();
        }
    }

    // 4. Secure Database Insertion (Practical 9)
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        if ($pdo) {
            try {
                $pdo->beginTransaction();

                // Insert user account
                $user_stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'student', 'active')");
                $user_stmt->execute([$form_data['username'], $form_data['email'], $hashed_password]);
                $user_id = (int)$pdo->lastInsertId();

                // Generate Student ID Code (e.g. 24IT521)
                $prefix = '24' . strtoupper(substr(str_replace(['.', ' '], '', $form_data['course']), 0, 2));
                $code = $prefix . rand(100, 999);

                // Insert linked student profile
                $student_stmt = $pdo->prepare("INSERT INTO students (user_id, student_id_code, full_name, email, phone, department, course, year, gender, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                $student_stmt->execute([
                    $user_id,
                    $code,
                    $form_data['full_name'],
                    $form_data['email'],
                    $form_data['phone'],
                    $form_data['department'],
                    $form_data['course'],
                    $form_data['year'],
                    $form_data['gender'],
                    $form_data['address']
                ]);

                // Record audit log
                log_audit($user_id, $form_data['username'], 'User self-registration successful (Student ID: ' . $code . ')');

                $pdo->commit();

                // Append to CSV for Practical 7 dual-storage
                $csv_file = DATA_PATH . '/students.csv';
                if ($fp = @fopen($csv_file, 'a')) {
                    if (flock($fp, LOCK_EX)) {
                        if (filesize($csv_file) === 0) {
                            fputcsv($fp, ['Student_ID', 'Name', 'Email', 'Phone', 'Course', 'Year', 'Registered_At']);
                        }
                        fputcsv($fp, [$code, $form_data['full_name'], $form_data['email'], $form_data['phone'], $form_data['course'], $form_data['year'], date('Y-m-d H:i:s')]);
                        flock($fp, LOCK_UN);
                    }
                    fclose($fp);
                }

                set_flash('success', 'Registration successful! You may now sign in with your credentials.');
                header("Location: login.php");
                exit();

            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = "Registration database transaction failed: " . $e->getMessage();
            }
        } else {
            // Simulation mode if MySQL is offline
            set_flash('success', 'Registration received in offline mock mode.');
            header("Location: login.php");
            exit();
        }
    }
}
?>

<!-- Breadcrumbs (Practical 2) -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb py-2 px-3 bg-body-tertiary rounded-3 shadow-sm">
    <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house-door me-1"></i>Home</a></li>
    <li class="breadcrumb-item active" aria-current="page">Student Registration</li>
  </ol>
</nav>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-5">
      <div class="card-header bg-primary text-white p-4 text-center">
        <h1 class="h3 fw-bold mb-1">Student Registration Portal</h1>
        <p class="mb-0 opacity-75 small">CHARUSAT Faculty of Technology &amp; Engineering &bull; ITUE203 Practical 5 &amp; 9</p>
      </div>

      <div class="card-body p-4 p-md-5 bg-body">
        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <h5 class="alert-heading fs-6 fw-bold mb-2"><i class="bi bi-exclamation-octagon-fill me-2"></i>Registration Errors:</h5>
            <ul class="mb-0 ps-3 small">
              <?php foreach ($errors as $err): ?>
                <li><?php echo e($err); ?></li>
              <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <!-- Registration Form with Client-Side & Server-Side Validation -->
        <form id="registrationForm" method="POST" action="register.php" novalidate>
          <?php echo csrf_field(); ?>

          <h5 class="text-primary fw-bold border-bottom pb-2 mb-3">1. Account Credentials</h5>
          
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label for="username" class="form-label fw-semibold">Choose Username <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-at"></i></span>
                <input type="text" id="username" name="username" class="form-control" 
                       placeholder="e.g. aarav_sharma" value="<?php echo e($form_data['username']); ?>" required>
              </div>
              <div class="form-text small">3-30 letters, numbers, or underscores.</div>
            </div>

            <div class="col-md-6">
              <label for="email" class="form-label fw-semibold">University Email <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" id="email" name="email" class="form-control" 
                       placeholder="student@charusat.edu" value="<?php echo e($form_data['email']); ?>" required>
              </div>
              <div class="form-text small">Used for portal login and communications.</div>
            </div>

            <div class="col-md-6">
              <label for="password" class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" id="password" name="password" class="form-control" 
                       placeholder="Minimum 8 characters" required>
              </div>
              <!-- Real-time Password Strength Meter (Practical 5) -->
              <div class="strength-meter">
                <div id="passwordStrengthBar" class="strength-bar"></div>
              </div>
              <div id="passwordStrengthText" class="small fw-semibold text-muted"></div>
            </div>

            <div class="col-md-6">
              <label for="confirm_password" class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                       placeholder="Re-type your password" required>
              </div>
            </div>
          </div>

          <h5 class="text-primary fw-bold border-bottom pb-2 mb-3">2. Student Profile Details</h5>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label for="full_name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
              <input type="text" id="full_name" name="full_name" class="form-control" 
                     placeholder="Firstname Lastname" value="<?php echo e($form_data['full_name']); ?>" required>
            </div>

            <div class="col-md-6">
              <label for="phone" class="form-label fw-semibold">Mobile Number <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text">+91</span>
                <input type="tel" id="phone" name="phone" class="form-control" 
                       placeholder="10-digit number" value="<?php echo e($form_data['phone']); ?>" required>
              </div>
            </div>

            <div class="col-md-6">
              <label for="department" class="form-label fw-semibold">Department</label>
              <select id="department" name="department" class="form-select">
                <option value="Information Technology" <?php echo $form_data['department'] === 'Information Technology' ? 'selected' : ''; ?>>Information Technology (IT)</option>
                <option value="Computer Engineering" <?php echo $form_data['department'] === 'Computer Engineering' ? 'selected' : ''; ?>>Computer Engineering (CE)</option>
                <option value="Computer Applications" <?php echo $form_data['department'] === 'Computer Applications' ? 'selected' : ''; ?>>Computer Applications (FCA)</option>
              </select>
            </div>

            <div class="col-md-6">
              <label for="course" class="form-label fw-semibold">Course / Degree <span class="text-danger">*</span></label>
              <select id="course" name="course" class="form-select" required>
                <option value="">Select Course</option>
                <option value="B.Tech IT" <?php echo $form_data['course'] === 'B.Tech IT' ? 'selected' : ''; ?>>B.Tech IT</option>
                <option value="B.Tech CSE" <?php echo $form_data['course'] === 'B.Tech CSE' ? 'selected' : ''; ?>>B.Tech CSE</option>
                <option value="BCA" <?php echo $form_data['course'] === 'BCA' ? 'selected' : ''; ?>>BCA</option>
                <option value="MCA" <?php echo $form_data['course'] === 'MCA' ? 'selected' : ''; ?>>MCA</option>
                <option value="BSc IT" <?php echo $form_data['course'] === 'BSc IT' ? 'selected' : ''; ?>>BSc IT</option>
              </select>
            </div>

            <div class="col-md-6">
              <label for="year" class="form-label fw-semibold">Academic Year <span class="text-danger">*</span></label>
              <select id="year" name="year" class="form-select" required>
                <option value="">Select Year</option>
                <option value="1st Year" <?php echo $form_data['year'] === '1st Year' ? 'selected' : ''; ?>>1st Year</option>
                <option value="2nd Year" <?php echo $form_data['year'] === '2nd Year' ? 'selected' : ''; ?>>2nd Year</option>
                <option value="3rd Year" <?php echo $form_data['year'] === '3rd Year' ? 'selected' : ''; ?>>3rd Year</option>
                <option value="4th Year" <?php echo $form_data['year'] === '4th Year' ? 'selected' : ''; ?>>4th Year</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold d-block">Gender <span class="text-danger">*</span></label>
              <div class="d-flex gap-4 pt-1">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="gender" id="genderMale" value="Male" <?php echo $form_data['gender'] === 'Male' ? 'checked' : ''; ?> required>
                  <label class="form-check-label" for="genderMale">Male</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="gender" id="genderFemale" value="Female" <?php echo $form_data['gender'] === 'Female' ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="genderFemale">Female</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="gender" id="genderOther" value="Other" <?php echo $form_data['gender'] === 'Other' ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="genderOther">Other</label>
                </div>
              </div>
            </div>

            <div class="col-12">
              <label for="address" class="form-label fw-semibold">Permanent / Hostel Address</label>
              <textarea id="address" name="address" rows="2" class="form-control" placeholder="Hostel/Local address in Changa/Anand/Nadiad..."><?php echo e($form_data['address']); ?></textarea>
            </div>
          </div>

          <h5 class="text-primary fw-bold border-bottom pb-2 mb-3">3. Verification &amp; Acceptance</h5>

          <!-- Custom Canvas CAPTCHA (Practical 5) -->
          <div class="row g-3 mb-4 align-items-center">
            <div class="col-md-6">
              <label for="captcha_input" class="form-label fw-semibold">Security Verification <span class="text-danger">*</span></label>
              <div class="captcha-container">
                <canvas id="captchaCanvas" width="160" height="42" class="shadow-sm"></canvas>
                <button type="button" id="refreshCaptchaBtn" class="btn btn-outline-secondary btn-sm" title="Get New Security Code">
                  <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
              </div>
              <input type="text" id="captcha_input" name="captcha_input" class="form-control" placeholder="Enter characters shown above" required autocomplete="off">
            </div>

            <div class="col-md-6 pt-md-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                <label class="form-check-label small" for="terms">
                  I accept the <a href="about.php" target="_blank">Student Code of Conduct</a> and agree to portal terms and conditions. <span class="text-danger">*</span>
                </label>
              </div>
            </div>
          </div>

          <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary btn-lg fw-semibold shadow-sm">
              <i class="bi bi-person-check-fill me-2"></i>Complete Student Registration
            </button>
          </div>

          <div class="text-center mt-3 small text-muted">
            Already have an active account? <a href="login.php" class="fw-semibold">Sign In Here</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
