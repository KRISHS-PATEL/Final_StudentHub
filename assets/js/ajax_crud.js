/**
 * StudentHub Portal - Asynchronous Fetch API CRUD Engine
 * Covers Practical 13:
 * PHP JSON API consumption without full page reload,
 * Async/Await Fetch calls, GET/POST/PUT/DELETE,
 * Debounced search, loading states, error handling,
 * and localStorage offline caching.
 */

const API_BASE = 'api/events_api.php';
let activeEvents = [];
let debounceTimer = null;

document.addEventListener('DOMContentLoaded', () => {
  const ajaxContainer = document.getElementById('ajaxCrudContainer');
  if (ajaxContainer) {
    loadEventsAsync();
    setupEventListeners();
  }
});

function setupEventListeners() {
  const searchInput = document.getElementById('ajaxSearchInput');
  const categoryFilter = document.getElementById('ajaxCategoryFilter');
  const addForm = document.getElementById('ajaxAddEventForm');

  if (searchInput) {
    // Debounced search (300ms delay)
    searchInput.addEventListener('input', (e) => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        filterAndRenderEvents();
      }, 300);
    });
  }

  if (categoryFilter) {
    categoryFilter.addEventListener('change', () => {
      filterAndRenderEvents();
    });
  }

  if (addForm) {
    addForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      await handleCreateEvent(addForm);
    });
  }
}

/**
 * Fetch events from PHP JSON API endpoint
 */
async function loadEventsAsync() {
  const container = document.getElementById('ajaxEventsGrid');
  const spinner = document.getElementById('ajaxLoadingSpinner');

  if (spinner) spinner.style.display = 'block';
  if (container) container.innerHTML = '';

  try {
    const response = await fetch(API_BASE, {
      method: 'GET',
      headers: { 'Accept': 'application/json' }
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();
    if (result.status === 'success') {
      activeEvents = result.data;
      // Cache in localStorage for offline-like resilience (Practical 6)
      localStorage.setItem('cached_studenthub_events', JSON.stringify(activeEvents));
      renderEventsList(activeEvents);
    } else {
      showToast(result.message || 'Failed to load events.', 'danger');
    }
  } catch (error) {
    console.warn('Network error loading events, falling back to cache:', error);
    // Offline Cache Fallback
    const cached = localStorage.getItem('cached_studenthub_events');
    if (cached) {
      activeEvents = JSON.parse(cached);
      renderEventsList(activeEvents);
      showToast('Loaded events from offline cache.', 'warning');
    } else {
      if (container) {
        container.innerHTML = `<div class="col-12 text-center text-danger py-4">Unable to load events. Please check server or database connection.</div>`;
      }
    }
  } finally {
    if (spinner) spinner.style.display = 'none';
  }
}

/**
 * Filter events by search term and category
 */
function filterAndRenderEvents() {
  const query = (document.getElementById('ajaxSearchInput')?.value || '').toLowerCase().trim();
  const category = document.getElementById('ajaxCategoryFilter')?.value || 'all';

  const filtered = activeEvents.filter(ev => {
    const matchesQuery = !query || ev.title.toLowerCase().includes(query) || (ev.description && ev.description.toLowerCase().includes(query)) || (ev.location && ev.location.toLowerCase().includes(query));
    const matchesCategory = category === 'all' || ev.category === category;
    return matchesQuery && matchesCategory;
  });

  renderEventsList(filtered);
}

/**
 * Render events into responsive cards
 */
function renderEventsList(events) {
  const grid = document.getElementById('ajaxEventsGrid');
  if (!grid) return;

  if (events.length === 0) {
    grid.innerHTML = `
      <div class="col-12 text-center py-5">
        <p class="text-muted fs-5">No events found matching your criteria.</p>
      </div>`;
    return;
  }

  grid.innerHTML = events.map(ev => {
    const badgeColor = ev.status === 'Open' ? 'bg-success' : (ev.status === 'Closed' ? 'bg-danger' : 'bg-secondary');
    const posterSrc = ev.poster ? (ev.poster.startsWith('sample') ? 'uploads/posters/' + ev.poster : 'assets/img/' + ev.poster) : 'assets/img/default-poster.svg';

    return `
      <div class="col-md-6 col-lg-4 mb-4" id="event-card-${ev.id}">
        <div class="card h-100 shadow-sm border-0">
          <div style="height: 160px; overflow: hidden; background: #1e293b; position: relative;">
            <img src="${posterSrc}" class="w-100 h-100" style="object-fit: cover;" alt="${escapeHtml(ev.title)}" onerror="this.src='assets/img/default-poster.svg'">
            <span class="badge ${badgeColor} position-absolute top-0 end-0 m-2">${escapeHtml(ev.status)}</span>
            <span class="badge bg-primary position-absolute bottom-0 start-0 m-2">${escapeHtml(ev.category)}</span>
          </div>
          <div class="card-body d-flex flex-column">
            <h5 class="card-title fw-bold text-primary mb-2">${escapeHtml(ev.title)}</h5>
            <p class="card-text text-muted small flex-grow-1">${escapeHtml(ev.description.substring(0, 110))}...</p>
            <div class="small text-secondary mb-3">
              <div><i class="bi bi-calendar-event me-2"></i><strong>Date:</strong> ${escapeHtml(ev.event_date)}</div>
              <div><i class="bi bi-geo-alt me-2"></i><strong>Venue:</strong> ${escapeHtml(ev.location)}</div>
              <div><i class="bi bi-people me-2"></i><strong>Seats:</strong> ${ev.registered_count || 0} / ${ev.max_seats}</div>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-outline-primary btn-sm flex-fill" onclick="openEditEventModal(${ev.id})">
                <i class="bi bi-pencil me-1"></i>Edit
              </button>
              <button class="btn btn-outline-danger btn-sm" onclick="handleDeleteEvent(${ev.id})" title="Delete Event">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

/**
 * Handle Event Creation (POST)
 */
async function handleCreateEvent(form) {
  const formData = new FormData(form);
  const payload = {
    title: formData.get('title'),
    category: formData.get('category'),
    description: formData.get('description'),
    event_date: formData.get('event_date'),
    event_time: formData.get('event_time'),
    location: formData.get('location'),
    max_seats: parseInt(formData.get('max_seats')) || 50,
    status: formData.get('status') || 'Open'
  };

  try {
    const res = await fetch(API_BASE, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    const data = await res.json();
    if (data.status === 'success') {
      showToast(data.message || 'Event created successfully!', 'success');
      form.reset();
      const modalEl = document.getElementById('addEventModal');
      if (modalEl) {
        bootstrap.Modal.getInstance(modalEl)?.hide();
      }
      loadEventsAsync();
    } else {
      showToast(data.message || 'Error adding event', 'danger');
    }
  } catch (err) {
    showToast('Network error while creating event.', 'danger');
  }
}

/**
 * Handle Event Update (PUT)
 */
async function handleUpdateEvent(id, updatedData) {
  try {
    const res = await fetch(API_BASE, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ id, ...updatedData })
    });

    const data = await res.json();
    if (data.status === 'success') {
      showToast('Event updated successfully!', 'success');
      loadEventsAsync();
    } else {
      showToast(data.message || 'Failed to update event.', 'danger');
    }
  } catch (err) {
    showToast('Network error while updating event.', 'danger');
  }
}

/**
 * Handle Event Deletion (DELETE)
 */
async function handleDeleteEvent(id) {
  if (!confirm('Are you sure you want to delete this event? This action is immediate via AJAX.')) {
    return;
  }

  const card = document.getElementById(`event-card-${id}`);
  if (card) {
    card.style.opacity = '0.5';
  }

  try {
    const res = await fetch(API_BASE, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ id })
    });

    const data = await res.json();
    if (data.status === 'success') {
      showToast('Event deleted successfully!', 'success');
      if (card) {
        card.style.transition = 'all 0.4s ease';
        card.style.transform = 'scale(0.8)';
        card.style.opacity = '0';
        setTimeout(() => card.remove(), 400);
      }
      activeEvents = activeEvents.filter(e => e.id !== id);
    } else {
      if (card) card.style.opacity = '1';
      showToast(data.message || 'Failed to delete event.', 'danger');
    }
  } catch (err) {
    if (card) card.style.opacity = '1';
    showToast('Network error while deleting event.', 'danger');
  }
}

function openEditEventModal(id) {
  const ev = activeEvents.find(e => e.id === id);
  if (!ev) return;

  const newTitle = prompt('Edit Event Title:', ev.title);
  if (newTitle && newTitle.trim() !== '') {
    handleUpdateEvent(id, {
      title: newTitle.trim(),
      category: ev.category,
      description: ev.description,
      location: ev.location,
      status: ev.status
    });
  }
}
