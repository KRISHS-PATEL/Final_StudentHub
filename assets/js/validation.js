/**
 * StudentHub Portal - Registration & Form Validation Script
 * Covers Practical 5:
 * HTML5 inputs, Regular Expressions, Password Strength Meter,
 * Real-time Validation (keyup/input), User-Friendly UX Error Messaging,
 * and Custom Canvas-Based CAPTCHA with visual reload.
 */

document.addEventListener('DOMContentLoaded', () => {
  const regForm = document.getElementById('registrationForm');
  if (regForm) {
    initRegistrationValidation(regForm);
  }

  // Generic forms with class .needs-js-validation
  document.querySelectorAll('.needs-js-validation').forEach(form => {
    initGenericValidation(form);
  });
});

let generatedCaptchaCode = "";

/* ------------------------------------------------------------
   Custom Canvas-Based CAPTCHA Generator (Practical 5)
------------------------------------------------------------ */
function generateCaptcha() {
  const canvas = document.getElementById('captchaCanvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
  let captcha = '';
  for (let i = 0; i < 6; i++) {
    captcha += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  generatedCaptchaCode = captcha;

  // Clear canvas
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  // Background tint
  ctx.fillStyle = '#f1f5f9';
  ctx.fillRect(0, 0, canvas.width, canvas.height);

  // Random noise lines
  for (let i = 0; i < 5; i++) {
    ctx.strokeStyle = `rgba(${Math.random() * 200}, ${Math.random() * 200}, ${Math.random() * 200}, 0.5)`;
    ctx.lineWidth = 1 + Math.random() * 2;
    ctx.beginPath();
    ctx.moveTo(Math.random() * canvas.width, Math.random() * canvas.height);
    ctx.lineTo(Math.random() * canvas.width, Math.random() * canvas.height);
    ctx.stroke();
  }

  // Draw characters with slight rotation
  ctx.font = 'bold 22px "Courier New", monospace';
  for (let i = 0; i < captcha.length; i++) {
    ctx.save();
    ctx.fillStyle = `rgb(${20 + Math.random() * 100}, ${20 + Math.random() * 100}, ${100 + Math.random() * 120})`;
    const x = 20 + i * 22;
    const y = 28 + (Math.random() * 6 - 3);
    const angle = (Math.random() * 20 - 10) * (Math.PI / 180);
    ctx.translate(x, y);
    ctx.rotate(angle);
    ctx.fillText(captcha[i], 0, 0);
    ctx.restore();
  }
}

/* ------------------------------------------------------------
   Password Strength Evaluator (Practical 5)
------------------------------------------------------------ */
function checkPasswordStrength(password) {
  let score = 0;
  if (!password) return { score: 0, text: 'None', className: '' };

  if (password.length >= 8) score++;
  if (/[A-Z]/.test(password)) score++;
  if (/[a-z]/.test(password)) score++;
  if (/[0-9]/.test(password)) score++;
  if (/[^A-Za-z0-9]/.test(password)) score++;

  if (score <= 2) {
    return { score: 1, text: 'Weak (Need numbers & uppercase)', className: 'strength-weak' };
  } else if (score <= 4) {
    return { score: 2, text: 'Medium (Add special symbols)', className: 'strength-medium' };
  } else {
    return { score: 3, text: 'Strong Password', className: 'strength-strong' };
  }
}

/* ------------------------------------------------------------
   Registration Form Validation Logic
------------------------------------------------------------ */
function initRegistrationValidation(form) {
  generateCaptcha();
  const refreshBtn = document.getElementById('refreshCaptchaBtn');
  if (refreshBtn) {
    refreshBtn.addEventListener('click', (e) => {
      e.preventDefault();
      generateCaptcha();
      const captchaInput = document.getElementById('captcha_input');
      if (captchaInput) {
        captchaInput.value = '';
        setFieldState(captchaInput, false, 'New CAPTCHA generated.');
      }
    });
  }

  const fields = {
    username: {
      el: form.querySelector('#username'),
      validate: (val) => /^[a-zA-Z0-9_]{3,30}$/.test(val),
      msg: 'Username must be 3-30 characters (alphanumeric & underscores only).'
    },
    full_name: {
      el: form.querySelector('#full_name'),
      validate: (val) => /^[a-zA-Z\s]{2,50}$/.test(val.trim()),
      msg: 'Full Name must contain 2-50 letters only.'
    },
    email: {
      el: form.querySelector('#email'),
      validate: (val) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val.trim()),
      msg: 'Enter a valid email address (e.g. name@charusat.edu).'
    },
    phone: {
      el: form.querySelector('#phone'),
      validate: (val) => /^[6-9]\d{9}$/.test(val.trim()),
      msg: 'Enter a valid 10-digit Indian mobile number starting with 6-9.'
    },
    course: {
      el: form.querySelector('#course'),
      validate: (val) => val !== '',
      msg: 'Please select your academic course.'
    },
    year: {
      el: form.querySelector('#year'),
      validate: (val) => val !== '',
      msg: 'Please select your study year.'
    },
    gender: {
      el: form.querySelector('input[name="gender"]:checked') || form.querySelector('input[name="gender"]'),
      validate: () => !!form.querySelector('input[name="gender"]:checked'),
      msg: 'Please select your gender.'
    },
    password: {
      el: form.querySelector('#password'),
      validate: (val) => val.length >= 8,
      msg: 'Password must be at least 8 characters long.'
    },
    confirm_password: {
      el: form.querySelector('#confirm_password'),
      validate: (val) => {
        const pass = form.querySelector('#password').value;
        return val.length > 0 && val === pass;
      },
      msg: 'Passwords do not match.'
    },
    captcha: {
      el: form.querySelector('#captcha_input'),
      validate: (val) => val.trim().toLowerCase() === generatedCaptchaCode.toLowerCase(),
      msg: 'CAPTCHA code did not match. Please try again.'
    },
    terms: {
      el: form.querySelector('#terms'),
      validate: (val, el) => el && el.checked,
      msg: 'You must accept the Student Code of Conduct & Terms.'
    }
  };

  // Real-time password strength listener
  const passEl = form.querySelector('#password');
  const strengthBar = document.getElementById('passwordStrengthBar');
  const strengthText = document.getElementById('passwordStrengthText');

  if (passEl && strengthBar && strengthText) {
    passEl.addEventListener('input', () => {
      const val = passEl.value;
      const res = checkPasswordStrength(val);
      strengthBar.className = 'strength-bar ' + res.className;
      strengthText.textContent = val ? res.text : '';
    });
  }

  // Real-time input listeners (keyup / change)
  Object.keys(fields).forEach(key => {
    const item = fields[key];
    if (!item.el) return;

    const eventType = item.el.tagName === 'SELECT' || item.el.type === 'checkbox' || item.el.type === 'radio' ? 'change' : 'input';
    item.el.addEventListener(eventType, () => {
      validateSingleField(key, fields);
    });
  });

  // Form submission handler
  form.addEventListener('submit', (e) => {
    let isValid = true;
    Object.keys(fields).forEach(key => {
      const fieldValid = validateSingleField(key, fields);
      if (!fieldValid) {
        isValid = false;
      }
    });

    if (!isValid) {
      e.preventDefault();
      // Scroll to first invalid element
      const firstInvalid = form.querySelector('.is-invalid');
      if (firstInvalid) {
        firstInvalid.focus();
      }
    }
  });

  function validateSingleField(key, fieldsList) {
    const item = fieldsList[key];
    if (!item.el) return true;
    const val = item.el.value;
    const valid = item.validate(val, item.el);
    setFieldState(item.el, valid, item.msg);
    return valid;
  }
}

function setFieldState(element, isValid, errorMsg) {
  if (element.type === 'radio') {
    const container = element.closest('.form-group') || element.parentElement;
    const feedback = container.querySelector('.invalid-feedback') || createFeedback(container);
    if (!isValid) {
      feedback.textContent = errorMsg;
      feedback.style.display = 'block';
    } else {
      feedback.style.display = 'none';
    }
    return;
  }

  element.classList.toggle('is-valid', isValid);
  element.classList.toggle('is-invalid', !isValid);

  let feedback = element.nextElementSibling;
  while (feedback && !feedback.classList.contains('invalid-feedback')) {
    feedback = feedback.nextElementSibling;
  }

  if (!feedback) {
    feedback = document.createElement('div');
    feedback.className = 'invalid-feedback';
    element.parentElement.appendChild(feedback);
  }

  if (!isValid) {
    feedback.textContent = errorMsg;
  }
}

function createFeedback(container) {
  const f = document.createElement('div');
  f.className = 'invalid-feedback';
  container.appendChild(f);
  return f;
}

function initGenericValidation(form) {
  form.addEventListener('submit', (e) => {
    if (!form.checkValidity()) {
      e.preventDefault();
      e.stopPropagation();
    }
    form.classList.add('was-validated');
  }, false);
}
