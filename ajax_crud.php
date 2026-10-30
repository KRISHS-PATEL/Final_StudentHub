<?php
/**
 * StudentHub Portal - AJAX & Fetch-Based Asynchronous CRUD
 * Practical 13: Asynchronous Event CRUD without Full Page Reload
 */

$page_title = "AJAX Event CRUD (Fetch API)";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';
?>

<!-- Breadcrumbs (Practical 2) -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb py-2 px-3 bg-body-tertiary rounded-3 shadow-sm">
    <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house-door me-1"></i>Home</a></li>
    <li class="breadcrumb-item active" aria-current="page">AJAX Fetch API CRUD</li>
  </ol>
</nav>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <span class="badge bg-success-subtle text-success mb-1"><i class="bi bi-lightning-charge me-1"></i>No Page Reloads</span>
    <h1 class="h3 fw-bold mb-1">Interactive AJAX Event Management</h1>
    <p class="text-muted mb-0">Practical 13: JavaScript Fetch API communicating asynchronously with PHP JSON endpoints.</p>
  </div>
  <button type="button" class="btn btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addEventModal">
    <i class="bi bi-plus-lg me-1"></i>Add Event (Async POST)
  </button>
</div>

<!-- AJAX Container for JS Controller -->
<div id="ajaxCrudContainer">
  <!-- Search & Filter Controls -->
  <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body">
    <div class="card-body p-3 p-md-4">
      <div class="row g-3 align-items-center">
        <div class="col-md-7">
          <label for="ajaxSearchInput" class="form-label small fw-semibold text-muted">Instant Search (Debounced 300ms)</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="ajaxSearchInput" class="form-control" placeholder="Type to search events asynchronously...">
          </div>
        </div>

        <div class="col-md-5">
          <label for="ajaxCategoryFilter" class="form-label small fw-semibold text-muted">Category Filter</label>
          <select id="ajaxCategoryFilter" class="form-select">
            <option value="all">All Event Categories</option>
            <option value="Technical">Technical</option>
            <option value="Workshop">Workshop</option>
            <option value="Seminar">Seminar</option>
            <option value="Cultural">Cultural</option>
            <option value="Sports">Sports</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Loading Spinner -->
  <div id="ajaxLoadingSpinner" class="text-center py-5" style="display: none;">
    <div class="spinner-border text-primary" role="status">
      <span class="visually-hidden">Loading events via Fetch API...</span>
    </div>
    <p class="text-muted small mt-2">Querying PHP JSON API...</p>
  </div>

  <!-- Events Dynamic Grid -->
  <div class="row" id="ajaxEventsGrid">
    <!-- Populated dynamically by assets/js/ajax_crud.js -->
  </div>
</div>

<!-- Add Event Modal (Async POST) -->
<div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="ajaxAddEventForm">
        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="addModalLabel">Add Event (Asynchronous POST)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Event Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Cloud AI Workshop" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold">Category</label>
              <select name="category" class="form-select">
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
              <label class="form-label fw-semibold">Time</label>
              <input type="time" name="event_time" class="form-control" value="10:00">
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-8">
              <label class="form-label fw-semibold">Location / Venue</label>
              <input type="text" name="location" class="form-control" placeholder="e.g. Auditorium 1" required>
            </div>
            <div class="col-4">
              <label class="form-label fw-semibold">Max Seats</label>
              <input type="number" name="max_seats" class="form-control" value="60" min="1">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" rows="3" class="form-control" placeholder="Event overview..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Publish via API</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Load AJAX CRUD Script -->
<script src="assets/js/ajax_crud.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
