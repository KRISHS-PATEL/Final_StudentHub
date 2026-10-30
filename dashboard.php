<?php
/**
 * StudentHub Portal - Dashboard Gateway Router
 * Practical 10: Role-Based Redirection
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

if (is_admin()) {
    header("Location: admin_dashboard.php");
} else {
    header("Location: student_dashboard.php");
}
exit();
