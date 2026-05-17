<?php

function supabaseRequest(string $method, string $path, array $data = [], ?string $accept = null): ?array {
    if (!SUPABASE_URL || !SUPABASE_KEY) return null;
    $url = rtrim(SUPABASE_URL, '/') . '/rest/v1/' . ltrim($path, '/');
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json',
    ];
    if ($accept) $headers[] = 'Accept: ' . $accept;
    if ($method === 'GET') $headers[] = 'Prefer: count=exact';

    $ch = curl_init();
    $query = ($method === 'GET' && $data) ? '?' . http_build_query($data) : '';
    curl_setopt_array($ch, [
        CURLOPT_URL => $url . $query,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15,
    ]);
    if ($method !== 'GET' && $data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $res = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http >= 200 && $http < 300) {
        $decoded = json_decode($res, true);
        return is_array($decoded) ? $decoded : null;
    }
    return null;
}

function listTodosSupabase(string $filter = 'all'): array {
    $params = ['select' => '*', 'order' => 'created_at.desc'];
    if ($filter === 'active') $params['completed'] = 'eq.0';
    elseif ($filter === 'completed') $params['completed'] = 'eq.1';
    return supabaseRequest('GET', 'todos', $params) ?? [];
}

function getTodoSupabase(int $id): ?array {
    $res = supabaseRequest('GET', 'todos', ['select' => '*', 'id' => "eq.$id"]);
    return $res[0] ?? null;
}

function createTodoSupabase(string $title): array {
    $res = supabaseRequest('POST', 'todos', [['title' => $title, 'completed' => 0]], 'application/json');
    return $res[0] ?? ['title' => $title, 'completed' => 0];
}

function updateTodoSupabase(int $id, ?string $title = null, ?int $completed = null): ?array {
    $data = [];
    if ($title !== null) $data['title'] = $title;
    if ($completed !== null) $data['completed'] = $completed;
    $data['updated_at'] = date('c');
    supabaseRequest('PATCH', "todos?id=eq.$id", $data);
    return getTodoSupabase($id);
}

function deleteTodoSupabase(int $id): bool {
    $res = supabaseRequest('DELETE', "todos?id=eq.$id");
    return $res !== null;
}

function getAllTodosSupabaseRaw(): array {
    return supabaseRequest('GET', 'todos', ['select' => '*', 'order' => 'id.asc']) ?? [];
}
