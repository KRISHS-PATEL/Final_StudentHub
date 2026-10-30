<?php
/**
 * StudentHub Portal - CSV Export Engine
 * Practical 11 Intermediate Extension: Export Student Directory to CSV
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();

$search = trim($_GET['search'] ?? '');
$course = trim($_GET['course'] ?? '');

$filename = 'students_export_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Microsoft Excel compatibility
fputs($output, "\xEF\xBB\xBF");

// Column Headers
fputcsv($output, ['ID', 'Student_Code', 'Full_Name', 'Email', 'Phone', 'Department', 'Course', 'Year', 'Gender', 'Status', 'Registered_At']);

if ($pdo) {
    try {
        $sql = "SELECT id, student_id_code, full_name, email, phone, department, course, year, gender, status, created_at FROM students WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (full_name LIKE ? OR email LIKE ? OR student_id_code LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($course) && $course !== 'all') {
            $sql .= " AND course = ?";
            $params[] = $course;
        }

        $sql .= " ORDER BY id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['id'],
                $row['student_id_code'],
                $row['full_name'],
                $row['email'],
                $row['phone'],
                $row['department'],
                $row['course'],
                $row['year'],
                $row['gender'],
                $row['status'],
                $row['created_at']
            ]);
        }
    } catch (Exception $e) {
        fputcsv($output, ['Error generating export: ' . $e->getMessage()]);
    }
} else {
    // Fallback export from JSON file
    $jsonFile = DATA_PATH . '/students.json';
    if (file_exists($jsonFile)) {
        $students = json_decode(file_get_contents($jsonFile), true) ?: [];
        foreach ($students as $s) {
            fputcsv($output, [
                $s['id'],
                $s['student_id_code'] ?? '24IT000',
                $s['full_name'],
                $s['email'],
                $s['phone'],
                $s['department'] ?? 'Information Technology',
                $s['course'],
                $s['year'],
                $s['gender'],
                $s['status'],
                date('Y-m-d H:i:s')
            ]);
        }
    }
}

fclose($output);
exit();
