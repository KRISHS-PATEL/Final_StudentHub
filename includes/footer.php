  </main>

  <!-- Semantic Footer (Practical 1 & 2) -->
  <footer class="mt-auto">
    <div class="container">
      <div class="row g-4 mb-4">
        <div class="col-lg-4 col-md-6">
          <div class="d-flex align-items-center mb-3">
            <img src="assets/img/logo.svg" alt="StudentHub" style="height: 34px; filter: brightness(1.2);" class="me-2">
          </div>
          <p class="small text-secondary mb-3">
            StudentHub is the unified digital campus ecosystem for Charotar University of Science and Technology (CHARUSAT), Faculty of Technology & Engineering (FTE).
          </p>
          <div class="small text-secondary">
            <i class="bi bi-geo-alt-fill me-2 text-primary"></i>CHARUSAT Campus, Highway, Changa, Gujarat 388421
          </div>
        </div>

        <div class="col-lg-2 col-md-6 col-6">
          <h6 class="text-white fw-bold mb-3">Portals</h6>
          <ul class="list-unstyled small d-flex flex-column gap-2">
            <li><a href="index.php">Home</a></li>
            <li><a href="about.php">About FTE</a></li>
            <li><a href="events.php">Campus Events</a></li>
            <li><a href="faq.php">Help &amp; FAQ</a></li>
            <li><a href="contact.php">Contact Us</a></li>
          </ul>
        </div>

        <div class="col-lg-3 col-md-6 col-6">
          <h6 class="text-white fw-bold mb-3">Student Hub</h6>
          <ul class="list-unstyled small d-flex flex-column gap-2">
            <li><a href="register.php">Student Registration</a></li>
            <li><a href="login.php">Sign In / Dashboard</a></li>
            <li><a href="feedback.php">Submit Feedback</a></li>
            <li><a href="ajax_crud.php">Dynamic Event API</a></li>
            <li><a href="test_suite.php">System Health Self-Test</a></li>
          </ul>
        </div>

        <div class="col-lg-3 col-md-6">
          <h6 class="text-white fw-bold mb-3">Academic Excellence</h6>
          <p class="small text-secondary mb-2">Subject: Web Development Frameworks (ITUE203)</p>
          <p class="small text-secondary mb-3">Semester: 3rd, 2026-27 (ODD)</p>
          <div class="badge bg-primary px-3 py-2 rounded-pill">
            <i class="bi bi-shield-check me-1"></i>Final Integrated Practical 15
          </div>
        </div>
      </div>

      <hr class="border-secondary my-4">

      <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-secondary">
        <div>
          &copy; <?php echo date('Y'); ?> CHARUSAT - Faculty of Technology and Engineering. All Rights Reserved.
        </div>
        <div class="d-flex gap-3 mt-2 mt-md-0">
          <a href="about.php">Privacy &amp; Terms</a>
          <span>&bull;</span>
          <a href="faq.php">Accessibility (WCAG 2.1 AA)</a>
          <span>&bull;</span>
          <a href="contact.php">Support</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- Bootstrap 5 Bundle JS (Includes Popper) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Main Interactivity JS (Practical 4) -->
  <script src="assets/js/main.js"></script>
  <!-- Form Validation & CAPTCHA JS (Practical 5) -->
  <script src="assets/js/validation.js"></script>
</body>
</html>
