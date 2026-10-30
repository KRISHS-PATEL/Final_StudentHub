<?php
/**
 * StudentHub Portal - About Us Page
 * Practical 1, 2
 */

$page_title = "About FTE & StudentHub";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumbs (Practical 2) -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb py-2 px-3 bg-body-tertiary rounded-3 shadow-sm">
    <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house-door me-1"></i>Home</a></li>
    <li class="breadcrumb-item active" aria-current="page">About Us</li>
  </ol>
</nav>

<div class="row g-4 mb-5">
  <div class="col-lg-8">
    <article class="bg-body p-4 p-md-5 rounded-4 border shadow-sm">
      <header class="mb-4 border-bottom pb-3">
        <span class="badge bg-primary-subtle text-primary mb-2">Academic Portal Architecture</span>
        <h1 class="fw-bold mb-2">About StudentHub Portal</h1>
        <p class="text-muted">Charotar University of Science and Technology (CHARUSAT) &bull; Faculty of Technology &amp; Engineering</p>
      </header>

      <section class="mb-4">
        <h2 class="fs-4 fw-bold text-primary mb-3">Project Overview</h2>
        <p>
          <strong>StudentHub</strong> is an integrated campus portal engineered as the comprehensive capstone for <strong>Web Development Frameworks (ITUE203)</strong>. The portal unites all 15 semester-long laboratory assignments into an enterprise-ready, accessible, and secure digital platform.
        </p>
        <p>
          Students can discover campus hackathons, manage course registrations, submit academic inquiries, and interact asynchronously with modern PHP JSON APIs without requiring full page reloads.
        </p>
      </section>

      <section class="mb-4">
        <h2 class="fs-4 fw-bold text-primary mb-3">Course Learning Outcomes (CO Mapping)</h2>
        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th scope="col" style="width: 15%;">Course Outcome</th>
                <th scope="col">Description &amp; Implementation in StudentHub</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><span class="badge bg-primary">CO1</span></td>
                <td>Architect semantic HTML5 structures, forms, requirement wireframes, and secure relational database models.</td>
              </tr>
              <tr>
                <td><span class="badge bg-info text-dark">CO2</span></td>
                <td>Construct mobile-first responsive web interfaces using CSS Grid, Flexbox, and Bootstrap 5 components.</td>
              </tr>
              <tr>
                <td><span class="badge bg-success">CO3</span></td>
                <td>Apply client-side DOM manipulation, event handling, real-time regex form validation, and canvas rendering.</td>
              </tr>
              <tr>
                <td><span class="badge bg-warning text-dark">CO4</span></td>
                <td>Consume external JSON datasets via Fetch API, build search/filter logic, and engineer asynchronous CRUD operations.</td>
              </tr>
              <tr>
                <td><span class="badge bg-danger">CO5</span></td>
                <td>Implement secure server-side PHP processing, password hashing (Bcrypt), session management, RBAC, and file uploads.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section>
        <h2 class="fs-4 fw-bold text-primary mb-3">Accessibility &amp; Security Compliance</h2>
        <p>
          StudentHub is designed with strict adherence to <strong>WCAG 2.1 AA Accessibility</strong> guidelines, featuring skip-to-content links, ARIA landmark roles, semantic containers (<code>header</code>, <code>nav</code>, <code>main</code>, <code>article</code>, <code>aside</code>, <code>footer</code>), and keyboard accessibility.
        </p>
        <p>
          Security controls include prepared PDO/MySQLi statements to eliminate SQL injection, <code>htmlspecialchars()</code> sanitization against XSS, cryptographic CSRF tokens on all POST submissions, and server-side MIME type verification for uploaded files.
        </p>
      </section>
    </article>
  </div>

  <div class="col-lg-4">
    <aside>
      <div class="bg-body p-4 rounded-4 border shadow-sm mb-4">
        <h3 class="fs-5 fw-bold mb-3"><i class="bi bi-mortarboard-fill text-primary me-2"></i>Faculty Details</h3>
        <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
          <li><strong>Institute:</strong> FTE, CHARUSAT</li>
          <li><strong>Department:</strong> Information Technology</li>
          <li><strong>Subject Code:</strong> ITUE203</li>
          <li><strong>Academic Term:</strong> 3rd Sem, 2026-27 (ODD)</li>
          <li><strong>Location:</strong> Changa, Gujarat, India</li>
        </ul>
      </div>

      <div class="bg-body p-4 rounded-4 border shadow-sm mb-4">
        <h3 class="fs-5 fw-bold mb-3"><i class="bi bi-stack text-success me-2"></i>Technology Stack</h3>
        <div class="d-flex flex-wrap gap-2">
          <span class="badge bg-secondary">HTML5 Semantic</span>
          <span class="badge bg-secondary">CSS3 / Grid / Flexbox</span>
          <span class="badge bg-primary">Bootstrap 5.3</span>
          <span class="badge bg-warning text-dark">JavaScript ES6+</span>
          <span class="badge bg-info text-dark">Fetch API</span>
          <span class="badge bg-danger">PHP 8.2 (PDO/MySQLi)</span>
          <span class="badge bg-dark">MySQL 3NF</span>
          <span class="badge bg-secondary">Canvas CAPTCHA</span>
        </div>
      </div>

      <div class="bg-primary text-white p-4 rounded-4 shadow-sm">
        <h3 class="fs-5 fw-bold mb-2">Need Academic Help?</h3>
        <p class="small opacity-90 mb-3">Have questions about lab assignments, event schedules, or portal accounts?</p>
        <a href="contact.php" class="btn btn-light btn-sm text-primary fw-semibold w-100">Contact Faculty / Admins</a>
      </div>
    </aside>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
