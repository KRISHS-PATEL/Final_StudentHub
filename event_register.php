<?php
/**
 * StudentHub Portal - Event Registration Handler
 * Practical 8, 9, 10
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: events.php");
    exit();
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Invalid session security token. Please try again.');
    header("Location: events.php");
    exit();
}

$user = current_user();
$event_id = (int)($_POST['event_id'] ?? 0);

if ($event_id <= 0) {
    set_flash('danger', 'Invalid event selected.');
    header("Location: events.php");
    exit();
}

if (!$pdo) {
    set_flash('warning', 'Database is currently offline. Simulating registration confirmation.');
    header("Location: student_dashboard.php");
    exit();
}

try {
    // 1. Get student ID linked to this user
    $s_stmt = $pdo->prepare("SELECT id, full_name FROM students WHERE user_id = ? OR email = ? LIMIT 1");
    $s_stmt->execute([$user['id'], $user['email']]);
    $student = $s_stmt->fetch();

    if (!$student) {
        // Automatically create student profile if missing
        $code = '24IT' . rand(100, 999);
        $ins = $pdo->prepare("INSERT INTO students (user_id, student_id_code, full_name, email, phone, department, course, year, gender) VALUES (?, ?, ?, ?, '9876543210', 'Information Technology', 'B.Tech IT', '3rd Year', 'Male')");
        $ins->execute([$user['id'], $code, $user['username'], $user['email']]);
        $student_id = (int)$pdo->lastInsertId();
    } else {
        $student_id = (int)$student['id'];
    }

    // 2. Check if event is open and has available seats
    $ev_stmt = $pdo->prepare("SELECT id, title, max_seats, registered_count, status FROM events WHERE id = ?");
    $ev_stmt->execute([$event_id]);
    $event = $ev_stmt->fetch();

    if (!$event) {
        set_flash('danger', 'The specified event does not exist.');
        header("Location: events.php");
        exit();
    }

    if ($event['status'] !== 'Open') {
        set_flash('warning', 'Registration for this event is currently ' . $event['status'] . '.');
        header("Location: events.php");
        exit();
    }

    if ($event['registered_count'] >= $event['max_seats']) {
        set_flash('danger', 'Sorry, this event has already reached full capacity.');
        header("Location: events.php");
        exit();
    }

    // 3. Check duplicate registration
    $dup_stmt = $pdo->prepare("SELECT id FROM registrations WHERE student_id = ? AND event_id = ?");
    $dup_stmt->execute([$student_id, $event_id]);
    if ($dup_stmt->fetch()) {
        set_flash('info', 'You are already registered for "' . $event['title'] . '".');
        header("Location: student_dashboard.php");
        exit();
    }

    // 4. Insert registration
    $reg_stmt = $pdo->prepare("INSERT INTO registrations (student_id, event_id, status) VALUES (?, ?, 'Confirmed')");
    $reg_stmt->execute([$student_id, $event_id]);

    // 5. Update event seat count
    $up_stmt = $pdo->prepare("UPDATE events SET registered_count = registered_count + 1 WHERE id = ?");
    $up_stmt->execute([$event_id]);

    // 6. Audit Log
    log_audit($user['id'], $user['username'], 'Registered for event: ' . $event['title']);

    set_flash('success', 'Congratulations! You have successfully registered for "' . $event['title'] . '".');
    header("Location: student_dashboard.php");
    exit();

} catch (Exception $e) {
    set_flash('danger', 'Registration error: ' . $e->getMessage());
    header("Location: events.php");
    exit();
}
