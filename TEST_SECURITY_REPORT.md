# StudentHub Portal - Test & Security Report
**Course:** Web Development Frameworks (ITUE203) - Practical 15  
**Institute:** CHARUSAT - Faculty of Technology and Engineering (FTE)  
**Academic Term:** 3rd Semester, 2026-27 (ODD)

---

## 1. Executive Summary

This **Test & Security Report** provides holistic verification of the unified **StudentHub Portal** across all 15 laboratory assignments. Testing covers functional end-to-end integration, database integrity, file storage, security defense layers (OWASP Top 10 mitigations), and accessibility compliance (WCAG 2.1 Level AA).

---

## 2. Functional Testing Matrix

| Test ID | Module / Practical | Test Description & Inputs | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-01** | Practical 1 & 2 | Semantic HTML5 navigation linking all 10+ pages | Clean transitions, zero broken links, consistent header/footer | Verified across all pages | **PASS** |
| **TC-02** | Practical 3 | Responsive Grid/Flexbox layout on 320px, 768px, 1200px | Navigation collapses to hamburger; grid reflows seamlessly | Verified on Mobile, Tablet & Desktop | **PASS** |
| **TC-03** | Practical 4 | Theme Switcher (Dark/Light Mode) | HTML theme attribute updates; preference remembered in `localStorage` | Instant toggle; persists on refresh | **PASS** |
| **TC-04** | Practical 4 | Hero Slider & FAQ Accordion | Auto-sliding carousel with pause-on-hover; collapsible FAQ items expand/contract | Smooth DOM transitions and ARIA updates | **PASS** |
| **TC-05** | Practical 5 | Registration Real-time Validation | Name regex (`^[a-zA-Z\s]+$`), 10-digit mobile, email format, password strength bar | Real-time feedback with green/red states | **PASS** |
| **TC-06** | Practical 5 | Canvas CAPTCHA Generation & Verify | Random 6-char string rendered with distortion; mismatched code blocked | Correctly blocks invalid CAPTCHA entries | **PASS** |
| **TC-07** | Practical 6 | External JSON Rendering & Filtering | Fetching `events.json`, `students.json`, `faqs.json` with search & pagination | Instant search filter and page navigation | **PASS** |
| **TC-08** | Practical 7 | PHP Form Processing & Dual File Storage | Contact & feedback POST requests stored into CSV and JSON files using `flock()` | Records safely appended to disk | **PASS** |
| **TC-09** | Practical 8 & 9 | MySQL Insertion & Duplicate Check | Registering existing username or email rejected; valid user inserted | Duplicate caught; clean error messages | **PASS** |
| **TC-10** | Practical 9 & 10 | Password Hashing & Login Verification | `password_hash()` storing Bcrypt hashes; verified via `password_verify()` | Seamless authentication with correct pass; invalid rejected | **PASS** |
| **TC-11** | Practical 10 | RBAC Dynamic Redirection | Admin directed to `admin_dashboard.php`; student directed to `student_dashboard.php` | Role checked and redirected immediately | **PASS** |
| **TC-12** | Practical 10 | Session Timeout Defense | Inactivity exceeding 15 minutes terminates session | Session destroyed; redirected to login with timeout notice | **PASS** |
| **TC-13** | Practical 10 | Remember-Me Token Security | Cryptographic token hashed in `remember_tokens` table; auto-logs in via cookie | Authenticates user across browser restarts | **PASS** |
| **TC-14** | Practical 11 | Student CRUD & Soft Delete | Insert, Read, Update, and Soft-Delete (`status = 'inactive'`) with restore option | Database updated and UI synchronized | **PASS** |
| **TC-15** | Practical 11 | CSV Export Engine | Admin downloads filtered student records via `export_students.php` | Valid UTF-8 CSV file downloaded | **PASS** |
| **TC-16** | Practical 12 | Event Poster File Validation | Non-image extensions (e.g. `.php`, `.exe`) or files > 2MB rejected | MIME and size checked; safe renaming applied | **PASS** |
| **TC-17** | Practical 13 | Asynchronous AJAX CRUD | GET/POST/PUT/DELETE event operations via Fetch API without page reload | Fast async UI updates with toast alerts | **PASS** |
| **TC-18** | Practical 14 | Admin Dashboard & Audit Trail | Dynamic statistics cards, registration analytics chart, user status toggle | Traceability verified in `audit_logs` | **PASS** |

---

## 3. Security Verification Checklist

| Security Control | OWASP Vulnerability | Mitigation Technique Implemented | Verification Result |
| :--- | :--- | :--- | :--- |
| **SQL Injection Prevention** | A03:2021 - Injection | 100% Parameterized queries using PDO and MySQLi prepared statements (`?` placeholders). No string concatenation in SQL queries. | **VERIFIED SECURE** |
| **Cross-Site Scripting (XSS)** | A03:2021 - Injection | All user input is sanitized before output using `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')`. | **VERIFIED SECURE** |
| **Cross-Site Request Forgery (CSRF)** | A01:2021 - Broken Access Control | Cryptographically secure tokens (`random_bytes(32)`) generated in `$_SESSION['csrf_token']` and validated on all POST form submissions. | **VERIFIED SECURE** |
| **Password Storage Security** | A02:2021 - Cryptographic Failures | Passwords hashed using industry-standard Bcrypt algorithm via `password_hash($pass, PASSWORD_DEFAULT)`. No plain passwords stored. | **VERIFIED SECURE** |
| **Session Fixation Prevention** | A07:2021 - Auth Failures | `session_regenerate_id(true)` executed immediately upon successful user authentication. | **VERIFIED SECURE** |
| **Session Inactivity Timeout** | A07:2021 - Auth Failures | Inactivity timer tracks `$_SESSION['last_activity']` and forces auto-logout after 15 minutes of user idle time. | **VERIFIED SECURE** |
| **Remember-Me Cookie Security** | A02:2021 - Cryptographic Failures | Cookie stores random selector:token; database stores only SHA-256 hash. HTTP-Only, Secure, and SameSite=Lax flags enabled. | **VERIFIED SECURE** |
| **Malicious File Upload Defense** | A04:2021 - Insecure Design | Server-side MIME-type inspection via `finfo_file()`, extension whitelist (`jpg`, `jpeg`, `png`, `webp`, `gif`), 2MB limit, random renaming, and `.htaccess` execution prevention. | **VERIFIED SECURE** |
| **Role-Based Access Control (RBAC)** | A01:2021 - Broken Access Control | Strict server-side route guards (`require_login()`, `require_admin()`, `require_student()`). Unauthorized attempts blocked and logged to `audit_logs`. | **VERIFIED SECURE** |
| **Race Conditions in File Writes** | Concurrency Issues | `flock($fp, LOCK_EX)` applied during all file append operations to CSV and JSON files. | **VERIFIED SECURE** |

---

## 4. Accessibility Checklist (WCAG 2.1 AA Compliance)

| Guideline | Requirement | StudentHub Implementation | Status |
| :--- | :--- | :--- | :---: |
| **1.1.1 Non-text Content** | All images have meaningful alternative text | Descriptive `alt` attributes on all logo, posters, and icon SVG images. | **PASS** |
| **1.3.1 Info and Relationships** | Information, structure, and relationships are conveyable | Semantic tags used throughout: `<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<aside>`, `<footer>`. | **PASS** |
| **1.4.3 Contrast (Minimum)** | Contrast ratio of at least 4.5:1 for normal text | Tested and passed in both Light and Dark themes. | **PASS** |
| **2.1.1 Keyboard** | All functionality is operable through a keyboard interface | All inputs, buttons, navigation links, and modals are accessible via `Tab` and `Enter`. | **PASS** |
| **2.4.1 Bypass Blocks** | Mechanism to bypass blocks of repeated content | Visible "Skip to main content" link at the top of every page (`.skip-link`). | **PASS** |
| **2.4.7 Focus Visible** | Any keyboard operable user interface has a visible focus indicator | Explicit focus rings enabled via `:focus-visible` styling (`outline: 3px solid #38bdf8`). | **PASS** |
| **3.3.1 Error Identification** | If an input error is detected, the item is described in text | Clear inline feedback messages linked to form controls (`.invalid-feedback`). | **PASS** |
| **4.1.2 Name, Role, Value** | Controls have accessible names and roles | Proper ARIA attributes: `aria-expanded`, `aria-controls`, `aria-label`, and `role="alert"`. | **PASS** |

---

## 5. Browser & Platform Testing

| Platform / Browser | Version Tested | Rendering Engine | Test Result |
| :--- | :--- | :--- | :---: |
| **Google Chrome (Desktop)** | Modern (v120+) | Blink / V8 | 100% Functional |
| **Microsoft Edge (Desktop)** | Modern (v120+) | Blink / V8 | 100% Functional |
| **Mozilla Firefox (Desktop)** | Modern (v120+) | Gecko / SpiderMonkey | 100% Functional |
| **Apple Safari (WebKit)** | Modern (v17+) | WebKit | 100% Functional |
| **Mobile Chrome / Safari** | Mobile Viewports (320px - 480px) | Blink / WebKit | Fully Responsive |
