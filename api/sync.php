<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/supabase.php';

$method = $_SERVER['REQUEST_METHOD'];
$direction = $_GET['direction'] ?? $_POST['direction'] ?? '';

if (PHP_SAPI === 'cli') {
    $args = getopt('', ['direction::']);
    $direction = $args['direction'] ?? $direction;
}

if (!$direction || !in_array($direction, ['to-supabase', 'to-sqlite'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Specify direction=to-supabase or direction=to-sqlite']);
    exit;
}

try {
    if ($direction === 'to-supabase') {
        if (DB_DRIVER === 'supabase') {
            echo json_encode(['error' => 'Already using Supabase']);
            exit;
        }
        $todos = getAllTodosRaw();
        if (empty($todos)) { echo json_encode(['synced' => [], 'errors' => []]); exit; }
        foreach ($todos as &$t) { $t['updated_at'] = date('c', strtotime($t['updated_at'])); $t['created_at'] = date('c', strtotime($t['created_at'])); }
        $ok = supabaseRequest('POST', 'todos', $todos, 'application/json');
        echo json_encode(['synced' => count($todos) . ' todos', 'errors' => $ok ? [] : ['Supabase insert failed']]);
    } else {
        if (DB_DRIVER === 'sqlite') {
            echo json_encode(['error' => 'Already using SQLite']);
            exit;
        }
        $todos = getAllTodosSupabaseRaw();
        if (empty($todos)) { echo json_encode(['synced' => [], 'errors' => []]); exit; }
        upsertTodos($todos);
        echo json_encode(['synced' => count($todos) . ' todos', 'errors' => []]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
