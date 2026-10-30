<?php
/**
 * StudentHub Portal - Logout Handler
 * Practical 10
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

logout_user();

session_start();
set_flash('info', 'You have been securely signed out. Have a productive day!');
header("Location: login.php");
exit();
