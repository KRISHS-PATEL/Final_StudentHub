# StudentHub Portal - Sitemap, Architecture & Wireframes
**Subject:** Web Development Frameworks (ITUE203) - Practical 1  
**Institute:** CHARUSAT - Faculty of Technology and Engineering (FTE)

---

## 1. Portal Scope & User Roles

The **StudentHub Portal** serves three primary user personas:

| Role | Access Level | Description & Core Responsibilities |
| :--- | :--- | :--- |
| **Guest / Visitor** | Public | Explores university announcements, browses public events, reads FAQs, submits contact messages, registers/logs in. |
| **Student** | Authenticated | Views personalized dashboard, manages profile, browses & registers for hackathons and workshops, cancels registrations, submits 5-star portal reviews. |
| **Administrator** | Elevated / Moderation | Full system monitoring, manages student directory (CRUD + Soft Delete + CSV export), manages events (CRUD + validated poster uploads), moderates users (Active/Inactive status toggle), inspects system audit trail. |

---

## 2. Complete Visual Sitemap

```mermaid
graph TD
    Home["index.php (Home / Landing)"]
    About["about.php (About FTE &amp; Portal)"]
    Events["events.php (Events Catalogue)"]
    FAQ["faq.php (Collapsible FAQ)"]
    Contact["contact.php (Contact Form &amp; Dual Storage)"]
    Login["login.php (Authentication &amp; RBAC)"]
    Register["register.php (Registration &amp; CAPTCHA)"]

    Home --> About
    Home --> Events
    Home --> FAQ
    Home --> Contact
    Home --> Login
    Home --> Register

    Login -->|Role: student| StudentDash["student_dashboard.php (Student Portal)"]
    Login -->|Role: admin| AdminDash["admin_dashboard.php (Admin Hub)"]

    StudentDash --> MyEvents["Enrolled Events List"]
    StudentDash --> Profile["profile.php (Profile &amp; Password)"]
    StudentDash --> Feedback["feedback.php (Student Reviews)"]
    StudentDash --> EvReg["event_register.php (Confirm Registration)"]

    AdminDash --> AdminStudents["admin_students.php (Student CRUD + Soft Delete)"]
    AdminDash --> AdminEvents["admin_events.php (Event CRUD + Poster Upload)"]
    AdminDash --> AjaxCrud["ajax_crud.php (Fetch API CRUD)"]
    AdminDash --> AdminSubmissions["admin_submissions.php (CSV &amp; JSON Viewer)"]
    AdminDash --> AdminAudit["admin_audit.php (System Audit Logs)"]
    AdminStudents --> ExportCSV["export_students.php (Download CSV)"]
```

---

## 3. Relational Entity-Relationship (ER) Model (3NF)

```mermaid
erDiagram
    USERS ||--o| STUDENTS : "links 1:1"
    USERS ||--o{ AUDIT_LOGS : "logs actions"
    USERS ||--o{ REMEMBER_TOKENS : "has sessions"
    USERS ||--o{ EVENTS : "creates (admin)"
    STUDENTS ||--o{ REGISTRATIONS : "enrolls in"
    EVENTS ||--o{ REGISTRATIONS : "has participants"
    USERS ||--o{ FEEDBACK : "submits"

    USERS {
        int id PK
        string username UK
        string email UK
        string password
        string role
        string status
        datetime last_login
        timestamp created_at
    }

    STUDENTS {
        int id PK
        int user_id FK
        string student_id_code UK
        string full_name
        string email UK
        string phone
        string department
        string course
        string year
        string gender
        string status
    }

    EVENTS {
        int id PK
        string title
        string category
        text description
        date event_date
        time event_time
        string location
        int max_seats
        int registered_count
        string poster
        string status
    }

    REGISTRATIONS {
        int id PK
        int student_id FK
        int event_id FK
        string status
        timestamp registered_at
    }

    AUDIT_LOGS {
        int id PK
        int user_id FK
        string username
        string action
        string ip_address
        string user_agent
        timestamp created_at
    }
```

---

## 4. UI Wireframe Layouts

### A. Desktop Portal Header & Hero
```
+-----------------------------------------------------------------------------------+
| [Logo] StudentHub       Home   About   Events   FAQ   Contact   [Admin/Student]   [Theme]|
+-----------------------------------------------------------------------------------+
|  [========================== HERO SLIDER CAROUSEL =============================]  |
|  "One Unified Campus Portal - CHARUSAT Faculty of Technology & Engineering"       |
|  [< Prev]                                                                [Next >] |
+-----------------------------------------------------------------------------------+
|  [ 20+ Active Students ]   [ 15 Open Events ]   [ 5 Confirmed Seats ]   [ ITUE203 ] |
+-----------------------------------------------------------------------------------+
```

### B. Mobile Responsive Navigation
```
+---------------------------------------+
| [Logo] StudentHub          [Theme] [=]|
+---------------------------------------+
| [=] Collapsible Hamburger Menu:       |
|   - Home                              |
|   - About FTE                         |
|   - Campus Events                     |
|   - FAQ & Support                     |
|   - Sign In / Register                |
+---------------------------------------+
```
