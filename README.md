# StudentHub Portal - Final Integrated Project (Practical 15)

**Subject:** Web Development Frameworks (ITUE203)  
**Semester:** 3rd Semester, 2026-27 (ODD)  
**Institute:** Faculty of Technology and Engineering (FTE), CHARUSAT  

---

## 📌 Project Overview

**StudentHub** is a comprehensive, production-ready, full-stack campus web portal developed as the capstone submission for **Web Development Frameworks (ITUE203)**. It integrates all 15 laboratory assignments into a unified, accessible, secure, and responsive web application.

The portal provides end-to-end functionality for students and university administrators, including campus event discovery, event registrations, student directory management (CRUD with soft delete and CSV export), asynchronous Fetch API interactions, secure session authentication with Remember-Me tokens, server-side poster image uploads, dual CSV/JSON data logging, and interactive administrative analytics with audit traceability.

---

## 🎯 Practical 1 to 15 Implementation Cross-Reference

| Practical | Title / Topic | Implemented Feature in StudentHub | File / Route Reference |
| :---: | :--- | :--- | :--- |
| **1** | Project Initiation, Sitemap, Wireframe & GitHub | Defined 3 user roles (Guest, Student, Admin), visual sitemap, wireframes, and Git structure. | `docs/sitemap_wireframe.md`, `README.md` |
| **2** | Semantic HTML5 & Accessibility (WCAG 2.1 AA) | Semantic tags (`<header>`, `<nav>`, `<main>`, `<article>`, `<aside>`, `<footer>`), skip-to-content link, breadcrumbs, alt text. | `includes/header.php`, `includes/footer.php`, `about.php` |
| **3** | Responsive UI (CSS Grid, Flexbox, Bootstrap 5) | Mobile-first responsive design, CSS Grid dashboard metrics, event cards flex layout. | `assets/css/style.css`, `index.php`, `events.php` |
| **4** | JavaScript DOM & UI Interactivity | Hero image carousel slider, collapsible FAQ accordion, modal popups, notifications, and Light/Dark theme switcher with `localStorage`. | `assets/js/main.js`, `faq.php`, `index.php` |
| **5** | Registration Form Validation & Error Handling | Real-time regex validation (name, email, mobile), password strength meter, confirm password match, and custom Canvas CAPTCHA. | `assets/js/validation.js`, `register.php` |
| **6** | External JSON Rendering using Fetch API | Consuming `events.json`, `students.json`, and `faqs.json` (15+ records each) with search, filter, sorting, and pagination. | `data/*.json`, `events.php`, `faq.php` |
| **7** | PHP Form Processing & CSV/JSON Storage | Server-side validation, sanitization, CSRF protection, file locking (`flock`), storing into `contact_messages.csv`, `feedback.csv`, and `submissions.json`. | `contact.php`, `feedback.php`, `admin_submissions.php` |
| **8** | MySQL Schema Design (3NF), PDO & Prepared Statements | Normalized relational database (`users`, `students`, `events`, `registrations`, `audit_logs`, `remember_tokens`) with dual PDO & MySQLi connection. | `studenthub.sql`, `includes/db.php` |
| **9** | Secure Registration, Duplicate Check & Hashing | Parameterized prepared statement insertion, duplicate email/username check, `password_hash()` (Bcrypt), and registration audit logging. | `register.php`, `includes/auth.php` |
| **10** | Secure Login/Logout, Sessions, RBAC & Timeout | `password_verify()`, `session_regenerate_id()`, role redirection, 15-minute inactivity timeout, and secure hashed Remember-Me tokens. | `login.php`, `logout.php`, `includes/timeout.php` |
| **11** | Student Management CRUD, Search & Filter | Add, edit, search by name/email/ID, filter by course/gender, soft-delete (`status = 'inactive'`), restore, and CSV export. | `admin_students.php`, `export_students.php` |
| **12** | Event Management CRUD with Poster Upload | Admin CRUD for events, poster file upload with strict MIME type and file size validation (< 2MB), unique renaming, and `.htaccess` protection. | `admin_events.php`, `uploads/posters/` |
| **13** | PHP JSON API & Asynchronous Fetch CRUD | RESTful endpoints returning valid JSON (`GET`, `POST`, `PUT`, `DELETE`), consumed via Fetch API without page reload, debounced search, loading states. | `api/events_api.php`, `assets/js/ajax_crud.js`, `ajax_crud.php` |
| **14** | Admin Dashboard, Dynamic Menu, Charts & Audit | Dynamic navbar by role, aggregated statistics cards, user status toggle, interactive Canvas analytics chart, and system audit trail viewer. | `admin_dashboard.php`, `admin_audit.php` |
| **15** | Final Integration, Testing & Deployment | Unified deployable portal, automated test suite (`test_suite.php`), complete SQL dump, and security test report. | `test_suite.php`, `TEST_SECURITY_REPORT.md` |

---

## 🔑 Default Credentials for Evaluation

| Role | Username | University Email | Password | Access Rights |
| :--- | :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin@studenthub.edu` | `Admin@123` | Full administrative control, student CRUD, event creation with posters, user status toggles, audit logs. |
| **Student (Demo 1)** | `aarav_sharma` | `aarav@studenthub.edu` | `Student@123` | Student portal, browse events, register/cancel registrations, submit feedback. |
| **Student (Demo 2)** | `priya_patel` | `priya@studenthub.edu` | `Student@123` | Enrolled in hackathon and AI workshop. |
| **Student (Demo 3)** | `rohan_mehta` | `rohan@studenthub.edu` | `Student@123` | Enrolled in cyber security workshop. |

*(Note: Passwords are encrypted in the database using PHP's standard Bcrypt algorithm.)*

---

## 🚀 Setup & Local Deployment Instructions

### Method 1: Using XAMPP (Recommended)

1. **Start XAMPP Control Panel:**
   - Start the **Apache** service.
   - Start the **MySQL** service.

2. **Copy the Project to `htdocs`:**
   Copy the `studenthub` folder into your XAMPP web directory:
   ```powershell
   Copy-Item -Path "C:\Users\kimse\.gemini\antigravity\scratch\studenthub" -Destination "C:\xampp\htdocs\studenthub" -Recurse -Force
   ```

3. **Import Database Schema:**
   - Open your browser and navigate to **phpMyAdmin**: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
   - Click on the **Import** tab.
   - Choose the file: `studenthub/studenthub.sql` and click **Import** (or **Go**).
   - *Alternatively, import via MySQL Command Line:*
     ```bash
     C:\xampp\mysql\bin\mysql.exe -u root -p < studenthub.sql
     ```

4. **Launch the Portal:**
   Open your browser and visit:
   ```
   http://localhost/studenthub/
   ```

---

### Method 2: Using PHP Built-In Web Server

If running standalone without Apache:
1. Ensure MySQL is running on port 3306 (or import `studenthub.sql`).
2. Open terminal in the `studenthub` root folder and start PHP server:
   ```powershell
   C:\xampp\php\php.exe -S 127.0.0.1:8000 -t .
   ```
3. Open your browser and navigate to:
   ```
   http://127.0.0.1:8000/
   ```

---

## 🧪 Automated Testing & Diagnostics

StudentHub includes an automated built-in diagnostic test runner. To verify system health:
1. Log in or visit:
   ```
   http://localhost/studenthub/test_suite.php
   ```
2. The suite executes automated checks on:
   - PHP runtime environment & extension availability (`pdo`, `mysqli`, `fileinfo`, `json`).
   - File write permissions on `data/` and `uploads/posters/`.
   - Presence and integrity of JSON datasets.
   - Database connection and schema table existence.
   - Bcrypt hashing and CSRF token generation.

---

## 📂 Project Directory Structure

```
studenthub/
├── .gitignore                      # Git ignored files
├── README.md                       # Complete documentation & setup instructions
├── TEST_SECURITY_REPORT.md         # Comprehensive test & security checklist
├── studenthub.sql                  # Complete 3NF MySQL schema and rich seed data
├── docs/
│   └── sitemap_wireframe.md        # Practical 1 sitemap, wireframes & ER diagram
├── assets/
│   ├── css/
│   │   └── style.css               # Unified responsive stylesheet (Grid, Flexbox, Themes)
│   ├── js/
│   │   ├── main.js                 # Theme switcher, hero slider, FAQ accordion, modals
│   │   ├── validation.js           # Real-time regex validation & Canvas CAPTCHA
│   │   └── ajax_crud.js            # Asynchronous Fetch API CRUD controller
│   └── img/
│       ├── logo.svg                # StudentHub official SVG logo
│       ├── hero-1.svg              # Slide 1 banner
│       ├── hero-2.svg              # Slide 2 banner
│       ├── hero-3.svg              # Slide 3 banner
│       └── default-poster.svg      # Fallback event poster
├── data/
│   ├── events.json                 # 15+ external event records (Practical 6)
│   ├── students.json               # 20+ external student profiles (Practical 6)
│   ├── faqs.json                   # 15+ categorized FAQ records (Practical 6)
│   ├── contact_messages.csv        # Stored contact inquiries (Practical 7)
│   ├── feedback.csv                # Stored student ratings & reviews (Practical 7)
│   └── submissions.json            # Unified JSON disk log (Practical 7)
├── uploads/
│   ├── .htaccess                   # Execution prevention security rule
│   └── posters/                    # Validated event posters directory
│       ├── .htaccess
│       ├── sample-tech.png
│       ├── sample-ai.png
│       └── sample-cultural.png
├── includes/
│   ├── config.php                  # Global constants, session params, CSRF & flash helpers
│   ├── db.php                      # Dual PDO & MySQLi connection with audit helper
│   ├── auth.php                    # Bcrypt login, RBAC guards, Remember-Me engine
│   ├── timeout.php                 # 15-minute inactivity session timeout handler
│   ├── header.php                  # Reusable semantic header & dynamic role-based navbar
│   └── footer.php                  # Semantic footer with institute credits & scripts
├── api/
│   ├── events_api.php              # RESTful JSON API for events (GET/POST/PUT/DELETE)
│   └── students_api.php            # RESTful JSON API for students
├── index.php                       # Homepage with hero slider & stats (Practical 1-4)
├── about.php                       # About FTE and course outcome mapping (Practical 1-2)
├── events.php                      # Events directory with search, filter, pagination (Practical 6)
├── event_register.php              # Student event registration handler (Practical 8, 9)
├── register.php                    # Student registration with validation & CAPTCHA (Practical 5, 9)
├── login.php                       # Authentication with sessions, RBAC, Remember-Me (Practical 10)
├── logout.php                      # Secure logout destroying session & cookies (Practical 10)
├── dashboard.php                   # Gateway router to role-specific dashboard (Practical 10)
├── student_dashboard.php           # Student portal with enrolled events (Practical 10)
├── admin_dashboard.php             # Admin panel with charts, metrics & user toggle (Practical 14)
├── admin_students.php              # Student CRUD, soft delete, search & filter (Practical 11)
├── admin_events.php                # Event CRUD with poster upload & validation (Practical 12)
├── admin_audit.php                 # Searchable system security audit logs (Practical 14)
├── admin_submissions.php           # CSV & JSON file storage viewer (Practical 7)
├── ajax_crud.php                   # Asynchronous Fetch API CRUD demo (Practical 13)
├── profile.php                     # User profile & Bcrypt password manager
├── contact.php                     # Contact form with CSV/JSON/DB storage (Practical 7)
├── feedback.php                    # 5-star student review form (Practical 7)
├── faq.php                         # Collapsible FAQ accordion with JSON search (Practical 4, 6)
├── export_students.php             # Export student directory to downloadable CSV (Practical 11)
└── test_suite.php                  # Automated system diagnostic runner (Practical 15)
```

---

## 🔒 Security Summary

- **SQL Injection Defense:** All queries use PDO / MySQLi prepared statements with bound parameters.
- **XSS Prevention:** Output escaping with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- **CSRF Tokens:** Synchronizer token pattern validated on all POST forms.
- **Password Security:** Salted hashes generated with `password_hash($pass, PASSWORD_DEFAULT)`.
- **Session Protection:** `session_regenerate_id(true)` upon login; strict inactivity timeout; HTTP-Only and SameSite cookie policies.
- **Upload Hardening:** MIME verification, extension whitelisting, file size limits, safe renaming, and `.htaccess` disabling script execution.

---

## 👥 Authors & Academic Credits

- **Subject:** Web Development Frameworks (ITUE203)
- **Institution:** Charotar University of Science and Technology (CHARUSAT)
- **Faculty:** Faculty of Technology & Engineering (FTE)
- **Course Coordinator:** Dr. Martin Parmar & Faculty Team
