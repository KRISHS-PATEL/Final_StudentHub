<?php
/**
 * StudentHub Portal - Students RESTful JSON API Endpoint
 * Practical 13: Asynchronous CRUD Integration
 */

header('Content-Type: application/json; charset=UTF-8');
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        handleGetStudents();
        break;
    case 'POST':
        handlePostStudent($input);
        break;
    case 'DELETE':
        handleDeleteStudent($input);
        break;
    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
        break;
}

function handleGetStudents() {
    global $pdo;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $course = isset($_GET['course']) ? trim($_GET['course']) : '';

    if ($pdo) {
        try {
            $sql = "SELECT * FROM students WHERE 1=1";
            $params = [];

            if (!empty($search)) {
                $sql .= " AND (full_name LIKE ? OR email LIKE ? OR student_id_code LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if (!empty($course)) {
                $sql .= " AND course = ?";
                $params[] = $course;
            }

            $sql .= " ORDER BY id DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $students = $stmt->fetchAll();

            echo json_encode(["status" => "success", "count" => count($students), "data" => $students]);
            return;
        } catch (Exception $e) {
            // fallback
        }
    }

    $jsonFile = DATA_PATH . '/students.json';
    if (file_exists($jsonFile)) {
        $students = json_decode(file_get_contents($jsonFile), true) ?: [];
        if (!empty($search)) {
            $students = array_values(array_filter($students, fn($s) => stripos($s['full_name'], $search) !== false || stripos($s['email'], $search) !== false));
        }
        if (!empty($course)) {
            $students = array_values(array_filter($students, fn($s) => $s['course'] === $course));
        }
        echo json_encode(["status" => "success", "count" => count($students), "data" => $students, "source" => "json_fallback"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "No students data source available"]);
    }
}

function handlePostStudent($input) {
    global $pdo;
    if (empty($input['full_name']) || empty($input['email']) || empty($input['course'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Name, Email, and Course are required"]);
        return;
    }

    $name = trim($input['full_name']);
    $email = trim($input['email']);
    $phone = trim($input['phone'] ?? '9876543210');
    $dept = trim($input['department'] ?? 'Information Technology');
    $course = trim($input['course']);
    $year = trim($input['year'] ?? '1st Year');
    $gender = trim($input['gender'] ?? 'Male');
    $code = '24' . strtoupper(substr($course, 0, 2)) . rand(100, 999);

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO students (student_id_code, full_name, email, phone, department, course, year, gender, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$code, $name, $email, $phone, $dept, $course, $year, $gender]);
            echo json_encode(["status" => "success", "message" => "Student record created!", "id" => $pdo->lastInsertId()]);
            return;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "DB error: " . $e->getMessage()]);
            return;
        }
    }

    echo json_encode(["status" => "success", "message" => "Simulated student creation."]);
}

function handleDeleteStudent($input) {
    global $pdo;
    $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Valid Student ID is required"]);
        return;
    }

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE students SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(["status" => "success", "message" => "Student soft-deleted successfully!"]);
            return;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
            return;
        }
    }

    echo json_encode(["status" => "success", "message" => "Simulated soft-delete."]);
}
