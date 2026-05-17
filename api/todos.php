<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/supabase.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$id = null;

if (preg_match('#^/api/todos(?:/(\d+))?#', $path, $m)) {
    $id = isset($m[1]) ? (int)$m[1] : null;
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

try {
    switch ($method) {
        case 'GET':
            if ($id) {
                $todo = getTodo($id);
                if (!$todo) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
                echo json_encode(['data' => $todo]);
            } else {
                $filter = $_GET['filter'] ?? 'all';
                echo json_encode(['data' => listTodos($filter)]);
            }
            break;

        case 'POST':
            $body = json_decode(file_get_contents('php://input'), true);
            if (empty($body['title'])) { http_response_code(400); echo json_encode(['error' => 'Title required']); exit; }
            $todo = createTodo($body['title']);
            http_response_code(201);
            echo json_encode(['data' => $todo]);
            break;

        case 'PUT':
            if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID required']); exit; }
            $body = json_decode(file_get_contents('php://input'), true);
            $todo = updateTodo($id, $body['title'] ?? null, isset($body['completed']) ? (int)$body['completed'] : null);
            if (!$todo) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
            echo json_encode(['data' => $todo]);
            break;

        case 'DELETE':
            if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID required']); exit; }
            if (!deleteTodo($id)) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
            echo json_encode(['ok' => true]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
