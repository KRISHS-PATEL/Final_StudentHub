-- ============================================================
-- StudentHub Portal - Complete Database Schema and Seed Data
-- Subject: Web Development Frameworks (ITUE203) - Practical 15
-- CHARUSAT - Faculty of Technology and Engineering (FTE)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `studenthub` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `studenthub`;

-- Disable foreign key checks for clean re-creation
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `remember_tokens`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `feedback`;
DROP TABLE IF EXISTS `contact_messages`;
DROP TABLE IF EXISTS `registrations`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- 1. USERS TABLE (Authentication, RBAC, Password Hashing)
-- Practical 8, 9, 10, 14
-- ------------------------------------------------------------
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 2. STUDENTS TABLE (3NF Normalized, Student Directory)
-- Practical 5, 8, 11
-- ------------------------------------------------------------
CREATE TABLE `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `student_id_code` VARCHAR(20) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `phone` VARCHAR(15) NOT NULL,
    `department` VARCHAR(50) NOT NULL,
    `course` VARCHAR(50) NOT NULL,
    `year` VARCHAR(20) NOT NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `address` TEXT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 3. EVENTS TABLE (Events with Poster, Category, Seats)
-- Practical 8, 12, 13
-- ------------------------------------------------------------
CREATE TABLE `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `description` TEXT NOT NULL,
    `event_date` DATE NOT NULL,
    `event_time` TIME NOT NULL,
    `location` VARCHAR(150) NOT NULL,
    `max_seats` INT NOT NULL DEFAULT 60,
    `registered_count` INT NOT NULL DEFAULT 0,
    `poster` VARCHAR(255) DEFAULT 'default-poster.svg',
    `status` ENUM('Open', 'Closed', 'Upcoming') NOT NULL DEFAULT 'Open',
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_events_user` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 4. REGISTRATIONS TABLE (Many-to-Many Bridge Table)
-- Practical 8, 11, 14
-- ------------------------------------------------------------
CREATE TABLE `registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `event_id` INT NOT NULL,
    `status` ENUM('Confirmed', 'Pending', 'Cancelled') NOT NULL DEFAULT 'Confirmed',
    `notes` VARCHAR(255) NULL,
    `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_reg_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reg_event` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    CONSTRAINT `uq_student_event` UNIQUE (`student_id`, `event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 5. AUDIT LOGS TABLE (Traceability, Security & Monitoring)
-- Practical 9, 10, 14
-- ------------------------------------------------------------
CREATE TABLE `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `username` VARCHAR(50) NULL,
    `action` VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 6. CONTACT MESSAGES TABLE (Practical 7 & Contact Page)
-- ------------------------------------------------------------
CREATE TABLE `contact_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `subject` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 7. FEEDBACK TABLE (Practical 7 & Interactive Feedback)
-- ------------------------------------------------------------
CREATE TABLE `feedback` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `student_name` VARCHAR(100) NOT NULL,
    `rating` INT NOT NULL CHECK(`rating` BETWEEN 1 AND 5),
    `category` VARCHAR(50) NOT NULL,
    `comments` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 8. REMEMBER TOKENS TABLE (Practical 10 Remember-Me Security)
-- ------------------------------------------------------------
CREATE TABLE `remember_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    CONSTRAINT `fk_remember_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 9. TASKS TABLE (Practical 13 AJAX Demonstration Support)
-- ------------------------------------------------------------
CREATE TABLE `tasks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `status` ENUM('pending', 'completed') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA INSERTION
-- ============================================================

-- Passwords:
-- Admin: Admin@123  -> $2y$10$74qlTFCPwuDJp4mwbBLJ6uG2gm5uneE8gedc86cw.vKvW0fkvdOne
-- Student: Student@123 -> $2y$10$XrPr9EsmrYAT0R.nqtOxOewD4M6GY2CPvv8cVs/meUAZb47spLfbu

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `status`, `last_login`) VALUES
(1, 'admin', 'admin@studenthub.edu', '$2y$10$74qlTFCPwuDJp4mwbBLJ6uG2gm5uneE8gedc86cw.vKvW0fkvdOne', 'admin', 'active', NOW()),
(2, 'aarav_sharma', 'aarav@studenthub.edu', '$2y$10$XrPr9EsmrYAT0R.nqtOxOewD4M6GY2CPvv8cVs/meUAZb47spLfbu', 'student', 'active', NOW()),
(3, 'priya_patel', 'priya@studenthub.edu', '$2y$10$XrPr9EsmrYAT0R.nqtOxOewD4M6GY2CPvv8cVs/meUAZb47spLfbu', 'student', 'active', NOW()),
(4, 'rohan_mehta', 'rohan@studenthub.edu', '$2y$10$XrPr9EsmrYAT0R.nqtOxOewD4M6GY2CPvv8cVs/meUAZb47spLfbu', 'student', 'active', NOW()),
(5, 'sneha_joshi', 'sneha@studenthub.edu', '$2y$10$XrPr9EsmrYAT0R.nqtOxOewD4M6GY2CPvv8cVs/meUAZb47spLfbu', 'student', 'inactive', NULL);

-- 20+ Realistic Students (Practical 11 seed data)
INSERT INTO `students` (`id`, `user_id`, `student_id_code`, `full_name`, `email`, `phone`, `department`, `course`, `year`, `gender`, `address`, `status`) VALUES
(1, 2, '24IT001', 'Aarav Sharma', 'aarav@studenthub.edu', '9876543210', 'Information Technology', 'B.Tech IT', '3rd Year', 'Male', 'Block A, Charusat Campus, Changa', 'active'),
(2, 3, '24IT002', 'Priya Patel', 'priya@studenthub.edu', '9876543211', 'Information Technology', 'B.Tech IT', '3rd Year', 'Female', 'Hostel 3, Charusat, Anand', 'active'),
(3, 4, '24CE015', 'Rohan Mehta', 'rohan@studenthub.edu', '9876543212', 'Computer Engineering', 'B.Tech CSE', '2nd Year', 'Male', 'Station Road, Nadiad', 'active'),
(4, 5, '24IT028', 'Sneha Joshi', 'sneha@studenthub.edu', '9876543213', 'Information Technology', 'B.Tech IT', '3rd Year', 'Female', 'Vidyanagar, Anand', 'inactive'),
(5, NULL, '24BCA01', 'Vikram Desai', 'vikram.desai@gmail.com', '9876543214', 'Computer Applications', 'BCA', '1st Year', 'Male', 'Akshar Marg, Rajkot', 'active'),
(6, NULL, '24BCA02', 'Ananya Shah', 'ananya.shah@gmail.com', '9876543215', 'Computer Applications', 'BCA', '2nd Year', 'Female', 'Satellite, Ahmedabad', 'active'),
(7, NULL, '23MCA11', 'Kunal Trivedi', 'kunal.t@gmail.com', '9876543216', 'Computer Applications', 'MCA', '2nd Year', 'Male', 'Subhash Bridge, Vadodara', 'active'),
(8, NULL, '24CE042', 'Diya Varma', 'diya.v@gmail.com', '9876543217', 'Computer Engineering', 'B.Tech CSE', '3rd Year', 'Female', 'College Road, Nadiad', 'active'),
(9, NULL, '24IT055', 'Devansh Soni', 'devansh.s@gmail.com', '9876543218', 'Information Technology', 'B.Tech IT', '4th Year', 'Male', 'V.V. Nagar, Anand', 'active'),
(10, NULL, '24BSC09', 'Tanvi Parekh', 'tanvi.p@gmail.com', '9876543219', 'Information Technology', 'BSc IT', '2nd Year', 'Female', 'Race Course, Vadodara', 'active'),
(11, NULL, '24CE078', 'Harsh Solanki', 'harsh.s@gmail.com', '9876543220', 'Computer Engineering', 'B.Tech CSE', '1st Year', 'Male', 'Bhavnagar Highway, Changa', 'active'),
(12, NULL, '24IT089', 'Bhavya Pandya', 'bhavya.p@gmail.com', '9876543221', 'Information Technology', 'B.Tech IT', '2nd Year', 'Female', 'Bodakdev, Ahmedabad', 'active'),
(13, NULL, '23MCA25', 'Nikhil Raval', 'nikhil.r@gmail.com', '9876543222', 'Computer Applications', 'MCA', '1st Year', 'Male', 'Manjalpur, Vadodara', 'active'),
(14, NULL, '24CE099', 'Isha Dave', 'isha.d@gmail.com', '9876543223', 'Computer Engineering', 'B.Tech CSE', '4th Year', 'Female', 'GIDC, Vitthal Udyognagar', 'active'),
(15, NULL, '24BCA33', 'Manav Chauhan', 'manav.c@gmail.com', '9876543224', 'Computer Applications', 'BCA', '3rd Year', 'Male', 'Bopal, Ahmedabad', 'active'),
(16, NULL, '24BSC18', 'Riddhi Jani', 'riddhi.j@gmail.com', '9876543225', 'Information Technology', 'BSc IT', '3rd Year', 'Female', 'Station Road, Anand', 'active'),
(17, NULL, '24IT102', 'Siddharth Bhatt', 'sid.bhatt@gmail.com', '9876543226', 'Information Technology', 'B.Tech IT', '1st Year', 'Male', 'Ghatlodia, Ahmedabad', 'active'),
(18, NULL, '24CE115', 'Kavya Shukla', 'kavya.s@gmail.com', '9876543227', 'Computer Engineering', 'B.Tech CSE', '2nd Year', 'Female', 'Gotri Road, Vadodara', 'active'),
(19, NULL, '24IT120', 'Yash Parmar', 'yash.parmar@gmail.com', '9876543228', 'Information Technology', 'B.Tech IT', '3rd Year', 'Male', 'Campus Town, Changa', 'active'),
(20, NULL, '23MCA40', 'Meera Rajput', 'meera.r@gmail.com', '9876543229', 'Computer Applications', 'MCA', '2nd Year', 'Female', 'Karelibaug, Vadodara', 'active'),
(21, NULL, '24CE132', 'Arjun Panchal', 'arjun.p@gmail.com', '9876543230', 'Computer Engineering', 'B.Tech CSE', '4th Year', 'Male', 'Sayajigunj, Vadodara', 'active'),
(22, NULL, '24BCA45', 'Khushi Macwan', 'khushi.m@gmail.com', '9876543231', 'Computer Applications', 'BCA', '1st Year', 'Female', 'Gamdi, Anand', 'active');

-- 15 Realistic Events (Practical 6, 8, 12 seed data)
INSERT INTO `events` (`id`, `title`, `category`, `description`, `event_date`, `event_time`, `location`, `max_seats`, `registered_count`, `poster`, `status`, `created_by`) VALUES
(1, 'Web Dev Hackathon 2026', 'Technical', 'A 24-hour non-stop rapid prototyping hackathon focusing on Full Stack PHP, APIs, and modern responsive UI engineering.', '2026-11-15', '09:00:00', 'Lab 3 & 4, IT Building', 60, 2, 'sample-tech.png', 'Open', 1),
(2, 'AI & Cloud Computing Seminar', 'Seminar', 'Distinguished industry speakers discussing modern LLMs, autonomous agent architectures, and cloud deployments.', '2026-11-20', '10:30:00', 'Main Auditorium, FTE Block', 120, 1, 'sample-ai.png', 'Open', 1),
(3, 'Cyber Security & Ethical Hacking Workshop', 'Workshop', 'Hands-on practical session covering SQL Injection defense, XSS, CSRF tokens, and modern security audit frameworks.', '2026-12-05', '14:00:00', 'Seminar Hall A', 45, 1, 'sample-tech.png', 'Open', 1),
(4, 'SPOURAL Cultural Fest 2026', 'Cultural', 'Annual university cultural extravaganza featuring music, drama, fine arts, dance, and literary competitions.', '2026-12-18', '17:00:00', 'University Central Grounds', 500, 0, 'sample-cultural.png', 'Upcoming', 1),
(5, 'Inter-College Badminton Championship', 'Sports', 'Annual indoor badminton tournament for singles and doubles across all engineering branches.', '2026-11-28', '08:00:00', 'University Sports Complex', 32, 0, 'default-poster.svg', 'Open', 1),
(6, 'Full Stack JavaScript & Node.js Bootcamp', 'Workshop', 'Fast-paced masterclass on asynchronous JavaScript, Fetch API, DOM manipulation, and REST API integration.', '2026-11-22', '10:00:00', 'Computer Lab 7', 50, 0, 'sample-tech.png', 'Open', 1),
(7, 'UI/UX Design Sprint with Figma', 'Workshop', 'Interactive workshop on wireframing, WCAG accessibility, color contrast, and prototyping mobile-first portals.', '2026-11-25', '11:00:00', 'Design Studio, Architecture Block', 40, 0, 'sample-tech.png', 'Open', 1),
(8, 'Campus Placement Preparation Talk', 'Seminar', 'Alumni panel sharing interview tips, resume building, aptitude strategies, and technical viva guidance.', '2026-12-01', '15:00:00', 'Auditorium 2', 150, 0, 'default-poster.svg', 'Open', 1),
(9, 'IoT & Embedded Robotics Arena', 'Technical', 'Showcase and competition of smart microcontrollers, Arduino sensors, and automated robotic arms.', '2026-12-10', '09:30:00', 'Robotics Lab, Depstar', 40, 0, 'sample-tech.png', 'Upcoming', 1),
(10, 'Database Performance & SQL Tuning', 'Technical', 'Deep dive into 3NF normalization, MySQL indexing, query execution plans, and transaction integrity.', '2026-12-12', '13:30:00', 'Lab 2, CSPIT', 45, 0, 'sample-tech.png', 'Open', 1),
(11, 'Annual Tech Quiz League', 'Technical', 'Fast-paced buzzer quiz covering web frameworks, networking protocols, algorithms, and tech trivia.', '2026-12-15', '16:00:00', 'Room 302, IT Dept', 60, 0, 'default-poster.svg', 'Open', 1),
(12, 'Code Combat Competitive Programming', 'Technical', 'Solve data structures and algorithmic challenges on time-constrained platforms with live leaderboards.', '2026-12-22', '10:00:00', 'Online / Virtual Lab', 100, 0, 'sample-tech.png', 'Upcoming', 1),
(13, 'Photography & Visual Media Walk', 'Cultural', 'Hands-on campus photo walk guided by professional photographers focusing on lighting and composition.', '2026-12-24', '07:30:00', 'Central Garden, Campus', 30, 0, 'sample-cultural.png', 'Open', 1),
(14, 'Startup Pitch & Entrepreneurship Summit', 'Seminar', 'Student innovators pitch early startup ideas to venture angel networks and faculty incubator mentors.', '2027-01-08', '10:00:00', 'Incubation Center Hall', 80, 0, 'default-poster.svg', 'Upcoming', 1),
(15, 'Open Source Contribution Day', 'Workshop', 'Guided contribution drive to open-source GitHub projects with Git version control best practices.', '2027-01-15', '09:00:00', 'Open Source Lab', 50, 0, 'sample-tech.png', 'Upcoming', 1);

-- Registrations (Practical 8, 14 seed data)
INSERT INTO `registrations` (`student_id`, `event_id`, `status`, `notes`) VALUES
(1, 1, 'Confirmed', 'Team Lead - Web Titans'),
(2, 1, 'Confirmed', 'Front-end Specialist'),
(2, 2, 'Confirmed', 'Interested in AI LLMs'),
(3, 3, 'Confirmed', 'Cyber security enthusiast'),
(1, 3, 'Pending', 'Waiting for lab allocation');

-- Update registered counts to match
UPDATE `events` SET `registered_count` = 2 WHERE `id` = 1;
UPDATE `events` SET `registered_count` = 1 WHERE `id` = 2;
UPDATE `events` SET `registered_count` = 2 WHERE `id` = 3;

-- Audit Logs (Practical 9, 10, 14 seed data)
INSERT INTO `audit_logs` (`user_id`, `username`, `action`, `ip_address`, `user_agent`) VALUES
(1, 'admin', 'System initialization and schema setup', '127.0.0.1', 'Mozilla/5.0 Windows NT 10.0'),
(1, 'admin', 'Created seed event: Web Dev Hackathon 2026', '127.0.0.1', 'Mozilla/5.0 Windows NT 10.0'),
(2, 'aarav_sharma', 'User registered successfully', '127.0.0.1', 'Mozilla/5.0 Windows NT 10.0'),
(2, 'aarav_sharma', 'Registered for event: Web Dev Hackathon 2026', '127.0.0.1', 'Mozilla/5.0 Windows NT 10.0'),
(3, 'priya_patel', 'User logged in', '127.0.0.1', 'Mozilla/5.0 Windows NT 10.0'),
(1, 'admin', 'Viewed Admin Dashboard', '127.0.0.1', 'Mozilla/5.0 Windows NT 10.0');

-- Feedback seed data (Practical 7)
INSERT INTO `feedback` (`user_id`, `student_name`, `rating`, `category`, `comments`) VALUES
(2, 'Aarav Sharma', 5, 'Portal Usability', 'The unified StudentHub portal is extremely intuitive and responsive! Loved the dark mode.'),
(3, 'Priya Patel', 5, 'Events System', 'Event registration with instant AJAX feedback worked smoothly during the hackathon sign-up.'),
(4, 'Rohan Mehta', 4, 'Search & Filter', 'Search and filter is very fast. The poster upload validation is also clear.');

-- Contact messages seed data (Practical 7)
INSERT INTO `contact_messages` (`name`, `email`, `subject`, `message`) VALUES
('Pooja Shah', 'pooja.s@gmail.com', 'Hackathon Team Size Inquiry', 'Can a team have members from both Information Technology and Computer Engineering?'),
('Dr. M. Parmar', 'martin.parmar@charusat.ac.in', 'Lab Booking Confirmation', 'Lab 3 has been confirmed for the upcoming Web Dev Hackathon 2026.');

-- Tasks seed data (Practical 13)
INSERT INTO `tasks` (`title`, `status`) VALUES
('Complete Practical 15 final integration', 'completed'),
('Test CSRF token defense on all POST forms', 'completed'),
('Verify WCAG 2.1 AA keyboard accessibility', 'completed'),
('Export students directory to CSV', 'pending');
