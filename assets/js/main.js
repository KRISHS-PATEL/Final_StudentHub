/**
 * StudentHub Portal - Main UI & Interactivity Script
 * Covers Practical 4: DOM manipulation, Theme Switcher (localStorage),
 * Image Slider, Accordion, Modal Popups, and Notification Banners.
 */

document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  initHeroSlider();
  initFaqAccordion();
  initModals();
});

/* ------------------------------------------------------------
   1. Light / Dark Theme Switcher with localStorage (Practical 4)
------------------------------------------------------------ */
function initTheme() {
  const themeToggleBtn = document.getElementById('themeToggleBtn');
  const themeIcon = document.getElementById('themeIcon');
  const html = document.documentElement;

  // Retrieve saved preference or default to light
  const savedTheme = localStorage.getItem('studenthub_theme') || 'light';
  applyTheme(savedTheme);

  if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', () => {
      const currentTheme = html.getAttribute('data-bs-theme') || 'light';
      const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
      applyTheme(newTheme);
      localStorage.setItem('studenthub_theme', newTheme);
      showToast(`Switched to ${newTheme.toUpperCase()} mode`, 'info');
    });
  }

  function applyTheme(theme) {
    html.setAttribute('data-bs-theme', theme);
    if (themeIcon) {
      if (theme === 'dark') {
        themeIcon.classList.remove('bi-moon-stars-fill');
        themeIcon.classList.add('bi-sun-fill');
      } else {
        themeIcon.classList.remove('bi-sun-fill');
        themeIcon.classList.add('bi-moon-stars-fill');
      }
    }
  }
}

/* ------------------------------------------------------------
   2. Hero Image / Content Slider (Practical 4)
------------------------------------------------------------ */
function initHeroSlider() {
  const slider = document.querySelector('.hero-slider');
  if (!slider) return;

  const slides = slider.querySelectorAll('.hero-slide');
  const dotsContainer = slider.querySelector('.slider-controls');
  const prevBtn = slider.querySelector('.slider-btn.prev');
  const nextBtn = slider.querySelector('.slider-btn.next');

  if (slides.length <= 1) return;

  let currentSlide = 0;
  let slideInterval = null;

  // Generate dots
  if (dotsContainer) {
    dotsContainer.innerHTML = '';
    slides.forEach((_, idx) => {
      const dot = document.createElement('button');
      dot.className = `slider-dot ${idx === 0 ? 'active' : ''}`;
      dot.setAttribute('aria-label', `Go to slide ${idx + 1}`);
      dot.addEventListener('click', () => goToSlide(idx));
      dotsContainer.appendChild(dot);
    });
  }

  function showSlide(index) {
    slides.forEach((s, i) => {
      s.classList.toggle('active', i === index);
    });
    if (dotsContainer) {
      const dots = dotsContainer.querySelectorAll('.slider-dot');
      dots.forEach((d, i) => d.classList.toggle('active', i === index));
    }
    currentSlide = index;
  }

  function nextSlide() {
    let next = (currentSlide + 1) % slides.length;
    showSlide(next);
  }

  function prevSlide() {
    let prev = (currentSlide - 1 + slides.length) % slides.length;
    showSlide(prev);
  }

  function goToSlide(index) {
    showSlide(index);
    resetAutoPlay();
  }

  if (nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); resetAutoPlay(); });
  if (prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); resetAutoPlay(); });

  function startAutoPlay() {
    slideInterval = setInterval(nextSlide, 5000);
  }

  function stopAutoPlay() {
    if (slideInterval) clearInterval(slideInterval);
  }

  function resetAutoPlay() {
    stopAutoPlay();
    startAutoPlay();
  }

  slider.addEventListener('mouseenter', stopAutoPlay);
  slider.addEventListener('mouseleave', startAutoPlay);

  startAutoPlay();
}

/* ------------------------------------------------------------
   3. Collapsible FAQ Accordion (Practical 4)
------------------------------------------------------------ */
function initFaqAccordion() {
  const faqQuestions = document.querySelectorAll('.faq-question');
  faqQuestions.forEach(btn => {
    btn.addEventListener('click', () => {
      const isExpanded = btn.getAttribute('aria-expanded') === 'true';
      const answer = btn.nextElementSibling;

      // Close all others if desired
      document.querySelectorAll('.faq-question').forEach(otherBtn => {
        if (otherBtn !== btn) {
          otherBtn.setAttribute('aria-expanded', 'false');
          if (otherBtn.nextElementSibling) {
            otherBtn.nextElementSibling.classList.remove('open');
          }
        }
      });

      // Toggle current
      btn.setAttribute('aria-expanded', !isExpanded);
      if (answer) {
        answer.classList.toggle('open', !isExpanded);
      }
    });
  });
}

/* ------------------------------------------------------------
   4. Notification Banners & Toasts (Practical 4)
------------------------------------------------------------ */
function showToast(message, type = 'info') {
  let container = document.querySelector('.toast-container-custom');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container-custom';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = 'custom-toast';
  
  const borderColors = {
    success: '#10b981',
    danger: '#ef4444',
    warning: '#f59e0b',
    info: '#4f46e5'
  };
  toast.style.borderLeftColor = borderColors[type] || borderColors.info;

  toast.innerHTML = `
    <div class="d-flex align-items-center gap-2">
      <span>${escapeHtml(message)}</span>
    </div>
    <button type="button" class="btn-close ms-2" aria-label="Close" style="font-size: 0.8rem;"></button>
  `;

  const closeBtn = toast.querySelector('.btn-close');
  closeBtn.addEventListener('click', () => toast.remove());

  container.appendChild(toast);

  // Auto dismiss after 4 seconds
  setTimeout(() => {
    if (toast.parentElement) {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(100%)';
      toast.style.transition = 'all 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }
  }, 4000);
}

/* ------------------------------------------------------------
   5. Accessible Modal Popups (Practical 4)
------------------------------------------------------------ */
function initModals() {
  document.querySelectorAll('[data-modal-target]').forEach(trigger => {
    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      const targetId = trigger.getAttribute('data-modal-target');
      const modal = document.querySelector(targetId);
      if (modal) {
        const bsModal = bootstrap.Modal.getOrCreateInstance(modal);
        bsModal.show();
      }
    });
  });
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}
