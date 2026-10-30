<?php
/**
 * StudentHub Portal - Student Management CRUD Module
 * Practical 11: Student Management CRUD with Search, Filter & CSV Export
 */

$page_title = "Student Management CRUD";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();
$admin = current_user();

// Handle CREATE Student (Practical 11)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $dept = trim($_POST['department'] ?? 'Information Technology');
        $course = trim($_POST['course'] ?? '');
        $year = trim($_POST['year'] ?? '');
        $gender = trim($_POST['gender'] ?? 'Male');
        $code = trim($_POST['student_id_code'] ?? '');

        if (empty($code)) {
            $code = '24' . strtoupper(substr($course, 0, 2)) . rand(100, 999);
        }

        if (empty($name) || empty($email) || empty($course)) {
            set_flash('danger', 'Name, Email, and Course are required fields.');
        } elseif ($pdo) {
            try {
                // Check duplicate
                $dup = $pdo->prepare("SELECT id FROM students WHERE email = ? OR student_id_code = ?");
                $dup->execute([$email, $code]);
                if ($dup->fetch()) {
                    set_flash('danger', 'A student with that email or Student ID Code already exists.');
                } else {
                    $stmt = $pdo->prepare("INSERT INTO students (student_id_code, full_name, email, phone, department, course, year, gender, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                    $stmt->execute([$code, $name, $email, $phone, $dept, $course, $year, $gender]);

                    log_audit($admin['id'], $admin['username'], "Added student: $name ($code)");
                    set_flash('success', "Student $name ($code) added successfully!");
                }
            } catch (Exception $e) {
                set_flash('danger', 'Insert failed: ' . $e->getMessage());
            }
        }
        header("Location: admin_students.php");
        exit();
    }
}

// Handle UPDATE Student (Practical 11)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_student'])) {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $id = (int)($_POST['student_id'] ?? 0);
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $year = trim($_POST['year'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $status = trim($_POST['status'] ?? 'active');

        if ($pdo && $id > 0) {
            try {
                $up = $pdo->prepare("UPDATE students SET full_name = ?, email = ?, phone = ?, course = ?, year = ?, gender = ?, status = ? WHERE id = ?");
                $up->execute([$name, $email, $phone, $course, $year, $gender, $status, $id]);

                log_audit($admin['id'], $admin['username'], "Updated student profile ID #$id ($name)");
                set_flash('success', "Student record updated successfully!");
            } catch (Exception $e) {
                set_flash('danger', 'Update failed: ' . $e->getMessage());
            }
        }
        header("Location: admin_students.php");
        exit();
    }
}

// Handle SOFT DELETE (Practical 11 Advanced Extension)
if (isset($_GET['soft_delete'])) {
    $id = (int)$_GET['soft_delete'];
    if ($pdo && $id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE students SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$id]);
            log_audit($admin['id'], $admin['username'], "Soft-deleted student ID #$id (marked inactive)");
            set_flash('warning', 'Student status marked as Inactive (Soft Delete applied).');
        } catch (Exception $e) {
            set_flash('danger', 'Operation failed: ' . $e->getMessage());
        }
    }
    header("Location: admin_students.php");
    exit();
}

// Handle RESTORE
if (isset($_GET['restore'])) {
    $id = (int)$_GET['restore'];
    if ($pdo && $id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE students SET status = 'active' WHERE id = ?");
            $stmt->execute([$id]);
            log_audit($admin['id'], $admin['username'], "Restored student ID #$id (marked active)");
            set_flash('success', 'Student record restored to Active status.');
        } catch (Exception $e) {
            set_flash('danger', 'Restore failed: ' . $e->getMessage());
        }
    }
    header("Location: admin_students.php");
    exit();
}

// Handle PERMANENT DELETE
if (isset($_GET['hard_delete'])) {
    $id = (int)$_GET['hard_delete'];
    if ($pdo && $id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$id]);
            log_audit($admin['id'], $admin['username'], "Permanently deleted student record ID #$id");
            set_flash('info', 'Student record permanently removed from database.');
        } catch (Exception $e) {
            set_flash('danger', 'Delete failed: ' . $e->getMessage());
        }
    }
    header("Location: admin_students.php");
    exit();
}

// Build Search and Filter Queries (Practical 11)
$search = trim($_GET['search'] ?? '');
$course_filter = trim($_GET['course_filter'] ?? '');
$status_filter = trim($_GET['status_filter'] ?? '');
$gender_filter = trim($_GET['gender_filter'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$students = [];
$total_students_count = 0;

if ($pdo) {
    try {
        $sql = "SELECT * FROM students WHERE 1=1";
        $count_sql = "SELECT COUNT(*) FROM students WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (full_name LIKE ? OR email LIKE ? OR student_id_code LIKE ? OR phone LIKE ?)";
            $count_sql .= " AND (full_name LIKE ? OR email LIKE ? OR student_id_code LIKE ? OR phone LIKE ?)";
            $w = "%$search%";
            $params[] = $w; $params[] = $w; $params[] = $w; $params[] = $w;
        }

        if (!empty($course_filter) && $course_filter !== 'all') {
            $sql .= " AND course = ?";
            $count_sql .= " AND course = ?";
            $params[] = $course_filter;
        }

        if (!empty($status_filter) && $status_filter !== 'all') {
            $sql .= " AND status = ?";
            $count_sql .= " AND status = ?";
            $params[] = $status_filter;
        }

        if (!empty($gender_filter) && $gender_filter !== 'all') {
            $sql .= " AND gender = ?";
            $count_sql .= " AND gender = ?";
            $params[] = $gender_filter;
        }

        $cnt_stmt = $pdo->prepare($count_sql);
        $cnt_stmt->execute($params);
        $total_students_count = (int)$cnt_stmt->fetchColumn();

        $sql .= " ORDER BY id DESC LIMIT $limit OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $students = $stmt->fetchAll();
    } catch (Exception $e) {
        // fallback
    }
}

// Fallback to students.json if DB unavailable
if (empty($students)) {
    $jsonFile = DATA_PATH . '/students.json';
    if (file_exists($jsonFile)) {
        $jData = json_decode(file_get_contents($jsonFile), true) ?: [];
        if (!empty($search)) {
            $jData = array_values(array_filter($jData, fn($s) => stripos($s['full_name'], $search) !== false || stripos($s['email'], $search) !== false));
        }
        $total_students_count = count($jData);
        $students = array_slice($jData, $offset, $limit);
    }
}

$total_pages = ceil($total_students_count / $limit);
?>

<!-- Breadcrumbs (Practical 2) -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb py-2 px-3 bg-body-tertiary rounded-3 shadow-sm">
    <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house-door me-1"></i>Home</a></li>
    <li class="breadcrumb-item"><a href="admin_dashboard.php">Admin Hub</a></li>
    <li class="breadcrumb-item active" aria-current="page">Student CRUD</li>
  </ol>
</nav>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-1"><i class="bi bi-people-fill text-primary me-2"></i>Student Management CRUD</h1>
    <p class="text-muted mb-0">Practical 11: Add, view, edit, search, filter, soft delete, and export students to CSV.</p>
  </div>
  <div class="d-flex gap-2">
    <!-- Export CSV Button (Practical 11 Intermediate Extension) -->
    <a href="export_students.php?search=<?php echo urlencode($search); ?>&course=<?php echo urlencode($course_filter); ?>" class="btn btn-outline-success rounded-pill px-3">
      <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export to CSV
    </a>
    <button type="button" class="btn btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addStudentModal">
      <i class="bi bi-person-plus-fill me-1"></i>Add New Student
    </button>
  </div>
</div>

<!-- Search & Filter Card (Practical 11) -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-body">
  <div class="card-body p-3 p-md-4">
    <form method="GET" action="admin_students.php" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label for="search" class="form-label small fw-semibold text-muted">Search Student</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" id="search" name="search" class="form-control" 
                 placeholder="Search by name, email, ID, or phone..." value="<?php echo e($search); ?>">
        </div>
      </div>

      <div class="col-md-3">
        <label for="course_filter" class="form-label small fw-semibold text-muted">Filter by Course</label>
        <select id="course_filter" name="course_filter" class="form-select">
          <option value="all">All Courses</option>
          <option value="B.Tech IT" <?php echo $course_filter === 'B.Tech IT' ? 'selected' : ''; ?>>B.Tech IT</option>
          <option value="B.Tech CSE" <?php echo $course_filter === 'B.Tech CSE' ? 'selected' : ''; ?>>B.Tech CSE</option>
          <option value="BCA" <?php echo $course_filter === 'BCA' ? 'selected' : ''; ?>>BCA</option>
          <option value="MCA" <?php echo $course_filter === 'MCA' ? 'selected' : ''; ?>>MCA</option>
          <option value="BSc IT" <?php echo $course_filter === 'BSc IT' ? 'selected' : ''; ?>>BSc IT</option>
        </select>
      </div>

      <div class="col-md-2">
        <label for="status_filter" class="form-label small fw-semibold text-muted">Status</label>
        <select id="status_filter" name="status_filter" class="form-select">
          <option value="all">All Status</option>
          <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
          <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive (Soft-Deleted)</option>
        </select>
      </div>

      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-filter me-1"></i>Filter</button>
        <?php if (!empty($search) || !empty($course_filter) || !empty($status_filter)): ?>
          <a href="admin_students.php" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- Students Records Table (Practical 11) -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-body">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th scope="col" class="ps-4">Student ID</th>
            <th scope="col">Full Name</th>
            <th scope="col">Email &amp; Mobile</th>
            <th scope="col">Course / Year</th>
            <th scope="col">Gender</th>
            <th scope="col">Status</th>
            <th scope="col" class="text-end pe-4">CRUD Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($students)): ?>
            <tr>
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="bi bi-person-x fs-2 d-block mb-2"></i>No student records found matching the search criteria.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($students as $s): ?>
              <tr>
                <td class="ps-4 fw-bold text-primary"><?php echo e($s['student_id_code']); ?></td>
                <td>
                  <span class="fw-semibold"><?php echo e($s['full_name']); ?></span>
                  <div class="small text-muted"><?php echo e($s['department'] ?? 'Information Technology'); ?></div>
                </td>
                <td class="small">
                  <div><i class="bi bi-envelope me-1 text-muted"></i><?php echo e($s['email']); ?></div>
                  <div><i class="bi bi-telephone me-1 text-muted"></i><?php echo e($s['phone']); ?></div>
                </td>
                <td class="small">
                  <span class="badge bg-primary-subtle text-primary"><?php echo e($s['course']); ?></span>
                  <div class="text-muted mt-1"><?php echo e($s['year']); ?></div>
                </td>
                <td class="small"><?php echo e($s['gender']); ?></td>
                <td>
                  <span class="badge bg-<?php echo ($s['status'] === 'active') ? 'success' : 'secondary'; ?>">
                    <?php echo ucfirst(e($s['status'])); ?>
                  </span>
                </td>
                <td class="text-end pe-4">
                  <div class="d-flex justify-content-end gap-1">
                    <!-- Edit Button -->
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $s['id']; ?>" title="Edit Student">
                      <i class="bi bi-pencil"></i>
                    </button>

                    <!-- Soft Delete / Restore -->
                    <?php if ($s['status'] === 'active'): ?>
                      <a href="admin_students.php?soft_delete=<?php echo $s['id']; ?>" class="btn btn-outline-warning btn-sm" 
                         onclick="return confirm('Soft-delete student <?php echo e($s['full_name']); ?>? Status will change to Inactive.');" title="Soft Delete">
                        <i class="bi bi-slash-circle"></i>
                      </a>
                    <?php else: ?>
                      <a href="admin_students.php?restore=<?php echo $s['id']; ?>" class="btn btn-outline-success btn-sm" title="Restore to Active">
                        <i class="bi bi-arrow-counterclockwise"></i>
                      </a>
                    <?php endif; ?>

                    <!-- Permanent Delete with Confirmation -->
                    <a href="admin_students.php?hard_delete=<?php echo $s['id']; ?>" class="btn btn-outline-danger btn-sm" 
                       onclick="return confirm('WARNING: Permanently delete student <?php echo e($s['full_name']); ?>? This CANNOT be undone!');" title="Permanent Delete">
                      <i class="bi bi-trash"></i>
                    </a>
                  </div>
                </td>
              </tr>

              <!-- Edit Student Modal (Practical 11) -->
              <div class="modal fade" id="editModal<?php echo $s['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <form method="POST" action="admin_students.php">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="student_id" value="<?php echo $s['id']; ?>">
                      <div class="modal-header">
                        <h5 class="modal-title fw-bold">Edit Student: <?php echo e($s['full_name']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <div class="mb-3">
                          <label class="form-label fw-semibold">Full Name</label>
                          <input type="text" name="full_name" class="form-control" value="<?php echo e($s['full_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                          <label class="form-label fw-semibold">Email</label>
                          <input type="email" name="email" class="form-control" value="<?php echo e($s['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                          <label class="form-label fw-semibold">Phone</label>
                          <input type="tel" name="phone" class="form-control" value="<?php echo e($s['phone']); ?>" required>
                        </div>
                        <div class="row g-2 mb-3">
                          <div class="col-6">
                            <label class="form-label fw-semibold">Course</label>
                            <select name="course" class="form-select" required>
                              <option value="B.Tech IT" <?php echo $s['course'] === 'B.Tech IT' ? 'selected' : ''; ?>>B.Tech IT</option>
                              <option value="B.Tech CSE" <?php echo $s['course'] === 'B.Tech CSE' ? 'selected' : ''; ?>>B.Tech CSE</option>
                              <option value="BCA" <?php echo $s['course'] === 'BCA' ? 'selected' : ''; ?>>BCA</option>
                              <option value="MCA" <?php echo $s['course'] === 'MCA' ? 'selected' : ''; ?>>MCA</option>
                              <option value="BSc IT" <?php echo $s['course'] === 'BSc IT' ? 'selected' : ''; ?>>BSc IT</option>
                            </select>
                          </div>
                          <div class="col-6">
                            <label class="form-label fw-semibold">Year</label>
                            <select name="year" class="form-select" required>
                              <option value="1st Year" <?php echo $s['year'] === '1st Year' ? 'selected' : ''; ?>>1st Year</option>
                              <option value="2nd Year" <?php echo $s['year'] === '2nd Year' ? 'selected' : ''; ?>>2nd Year</option>
                              <option value="3rd Year" <?php echo $s['year'] === '3rd Year' ? 'selected' : ''; ?>>3rd Year</option>
                              <option value="4th Year" <?php echo $s['year'] === '4th Year' ? 'selected' : ''; ?>>4th Year</option>
                            </select>
                          </div>
                        </div>
                        <div class="row g-2">
                          <div class="col-6">
                            <label class="form-label fw-semibold">Gender</label>
                            <select name="gender" class="form-select">
                              <option value="Male" <?php echo $s['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                              <option value="Female" <?php echo $s['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                              <option value="Other" <?php echo $s['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                          </div>
                          <div class="col-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                              <option value="active" <?php echo $s['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                              <option value="inactive" <?php echo $s['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive (Soft Delete)</option>
                            </select>
                          </div>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_student" class="btn btn-primary">Save Changes</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Pagination (Practical 11) -->
<?php if ($total_pages > 1): ?>
  <nav aria-label="Student directory pagination" class="d-flex justify-content-center mb-5">
    <ul class="pagination shadow-sm">
      <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&course_filter=<?php echo urlencode($course_filter); ?>&status_filter=<?php echo urlencode($status_filter); ?>">&laquo;</a>
      </li>
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
          <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&course_filter=<?php echo urlencode($course_filter); ?>&status_filter=<?php echo urlencode($status_filter); ?>"><?php echo $i; ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&course_filter=<?php echo urlencode($course_filter); ?>&status_filter=<?php echo urlencode($status_filter); ?>">&raquo;</a>
      </li>
    </ul>
  </nav>
<?php endif; ?>

<!-- Add Student Modal (Practical 11) -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="admin_students.php">
        <?php echo csrf_field(); ?>
        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="addStudentLabel">Add New Student Record</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="full_name" class="form-control" placeholder="e.g. Aarav Sharma" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" placeholder="student@charusat.edu" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold">Student ID Code</label>
              <input type="text" name="student_id_code" class="form-control" placeholder="e.g. 24IT501 (Auto if blank)">
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold">Phone Number</label>
              <input type="tel" name="phone" class="form-control" placeholder="10-digit mobile" required>
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold">Department</label>
              <select name="department" class="form-select">
                <option value="Information Technology">Information Technology</option>
                <option value="Computer Engineering">Computer Engineering</option>
                <option value="Computer Applications">Computer Applications</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold">Course <span class="text-danger">*</span></label>
              <select name="course" class="form-select" required>
                <option value="B.Tech IT">B.Tech IT</option>
                <option value="B.Tech CSE">B.Tech CSE</option>
                <option value="BCA">BCA</option>
                <option value="MCA">MCA</option>
                <option value="BSc IT">BSc IT</option>
              </select>
            </div>
          </div>
          <div class="row g-2">
            <div class="col-6">
              <label class="form-label fw-semibold">Academic Year</label>
              <select name="year" class="form-select">
                <option value="1st Year">1st Year</option>
                <option value="2nd Year">2nd Year</option>
                <option value="3rd Year">3rd Year</option>
                <option value="4th Year">4th Year</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold">Gender</label>
              <select name="gender" class="form-select">
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="add_student" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Save Student</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
