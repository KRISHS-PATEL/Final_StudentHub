<?php
/**
 * StudentHub Portal - Events RESTful JSON API Endpoint
 * Practical 13: PHP JSON API & AJAX/Fetch-Based CRUD without Page Reload
 * Practical 6: External JSON rendering and Fallback
 */

header('Content-Type: application/json; charset=UTF-8');
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        handleGet();
        break;
    case 'POST':
        handlePost($input);
        break;
    case 'PUT':
        handlePut($input);
        break;
    case 'DELETE':
        handleDelete($input);
        break;
    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
        break;
}

function handleGet() {
    global $pdo;
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category = isset($_GET['category']) ? trim($_GET['category']) : '';

    if ($pdo) {
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
                $stmt->execute([$id]);
                $event = $stmt->fetch();
                if ($event) {
                    echo json_encode(["status" => "success", "data" => $event]);
                } else {
                    http_response_code(404);
                    echo json_encode(["status" => "error", "message" => "Event not found"]);
                }
                return;
            }

            $sql = "SELECT * FROM events WHERE 1=1";
            $params = [];

            if (!empty($search)) {
                $sql .= " AND (title LIKE ? OR description LIKE ? OR location LIKE ?)";
                $wildcard = "%$search%";
                $params[] = $wildcard;
                $params[] = $wildcard;
                $params[] = $wildcard;
            }
            if (!empty($category) && $category !== 'all') {
                $sql .= " AND category = ?";
                $params[] = $category;
            }

            $sql .= " ORDER BY event_date ASC, id DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $events = $stmt->fetchAll();

            echo json_encode(["status" => "success", "count" => count($events), "data" => $events]);
            return;
        } catch (Exception $e) {
            // Fallback to JSON file if DB query fails
        }
    }

    // Fallback to data/events.json
    $jsonFile = DATA_PATH . '/events.json';
    if (file_exists($jsonFile)) {
        $events = json_decode(file_get_contents($jsonFile), true) ?: [];
        if (!empty($search)) {
            $events = array_values(array_filter($events, function($e) use ($search) {
                return stripos($e['title'], $search) !== false || stripos($e['description'], $search) !== false;
            }));
        }
        if (!empty($category) && $category !== 'all') {
            $events = array_values(array_filter($events, function($e) use ($category) {
                return $e['category'] === $category;
            }));
        }
        echo json_encode(["status" => "success", "count" => count($events), "data" => $events, "source" => "json_fallback"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "No events data source available"]);
    }
}

function handlePost($input) {
    global $pdo;
    if (empty($input['title']) || empty($input['event_date'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Title and Event Date are required"]);
        return;
    }

    $title = trim($input['title']);
    $category = trim($input['category'] ?? 'Technical');
    $description = trim($input['description'] ?? '');
    $event_date = trim($input['event_date']);
    $event_time = trim($input['event_time'] ?? '10:00:00');
    $location = trim($input['location'] ?? 'Campus Lab');
    $max_seats = (int)($input['max_seats'] ?? 60);
    $status = trim($input['status'] ?? 'Open');

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO events (title, category, description, event_date, event_time, location, max_seats, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $category, $description, $event_date, $event_time, $location, $max_seats, $status]);
            $newId = (int)$pdo->lastInsertId();

            log_audit($_SESSION['user_id'] ?? null, $_SESSION['username'] ?? 'API', "Created event via API: $title");

            http_response_code(201);
            echo json_encode(["status" => "success", "message" => "Event created successfully!", "id" => $newId]);
            return;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
            return;
        }
    }

    // Fallback JSON append
    $jsonFile = DATA_PATH . '/events.json';
    $events = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];
    $newId = count($events) ? (max(array_column($events, 'id')) + 1) : 1;
    $newEvent = [
        "id" => $newId,
        "title" => $title,
        "category" => $category,
        "description" => $description,
        "event_date" => $event_date,
        "event_time" => $event_time,
        "location" => $location,
        "max_seats" => $max_seats,
        "registered_count" => 0,
        "status" => $status,
        "poster" => "default-poster.svg"
    ];
    $events[] = $newEvent;
    file_put_contents($jsonFile, json_encode($events, JSON_PRETTY_PRINT));
    http_response_code(201);
    echo json_encode(["status" => "success", "message" => "Event created in JSON store!", "id" => $newId]);
}

function handlePut($input) {
    global $pdo;
    if (empty($input['id'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Event ID is required for update"]);
        return;
    }

    $id = (int)$input['id'];
    $title = trim($input['title'] ?? '');
    $category = trim($input['category'] ?? '');
    $description = trim($input['description'] ?? '');
    $location = trim($input['location'] ?? '');
    $status = trim($input['status'] ?? '');

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE events SET title = COALESCE(NULLIF(?, ''), title), category = COALESCE(NULLIF(?, ''), category), description = COALESCE(NULLIF(?, ''), description), location = COALESCE(NULLIF(?, ''), location), status = COALESCE(NULLIF(?, ''), status) WHERE id = ?");
            $stmt->execute([$title, $category, $description, $location, $status, $id]);

            log_audit($_SESSION['user_id'] ?? null, $_SESSION['username'] ?? 'API', "Updated event #$id via API");

            echo json_encode(["status" => "success", "message" => "Event updated successfully!"]);
            return;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Update failed: " . $e->getMessage()]);
            return;
        }
    }

    // JSON update fallback
    $jsonFile = DATA_PATH . '/events.json';
    if (file_exists($jsonFile)) {
        $events = json_decode(file_get_contents($jsonFile), true) ?: [];
        foreach ($events as &$e) {
            if ($e['id'] === $id) {
                if ($title) $e['title'] = $title;
                if ($category) $e['category'] = $category;
                if ($description) $e['description'] = $description;
                if ($location) $e['location'] = $location;
                if ($status) $e['status'] = $status;
                break;
            }
        }
        file_put_contents($jsonFile, json_encode($events, JSON_PRETTY_PRINT));
        echo json_encode(["status" => "success", "message" => "Event updated in JSON store!"]);
    }
}

function handleDelete($input) {
    global $pdo;
    $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Valid Event ID is required"]);
        return;
    }

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$id]);

            log_audit($_SESSION['user_id'] ?? null, $_SESSION['username'] ?? 'API', "Deleted event #$id via API");

            echo json_encode(["status" => "success", "message" => "Event deleted successfully!"]);
            return;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Delete failed: " . $e->getMessage()]);
            return;
        }
    }

    // JSON delete fallback
    $jsonFile = DATA_PATH . '/events.json';
    if (file_exists($jsonFile)) {
        $events = json_decode(file_get_contents($jsonFile), true) ?: [];
        $events = array_values(array_filter($events, fn($e) => $e['id'] !== $id));
        file_put_contents($jsonFile, json_encode($events, JSON_PRETTY_PRINT));
        echo json_encode(["status" => "success", "message" => "Event deleted from JSON store!"]);
    }
}
