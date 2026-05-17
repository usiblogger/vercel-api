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

function supabaseStorageKey(): string {
    return SUPABASE_SERVICE_KEY ?: SUPABASE_KEY;
}

function supabaseStorageRequest(string $method, string $path, array $headers = [], string $body = ''): ?array {
    $key = supabaseStorageKey();
    if (!SUPABASE_URL || !$key) return null;
    $url = rtrim(SUPABASE_URL, '/') . '/storage/v1/' . ltrim($path, '/');
    $ch = curl_init();
    $defaultHeaders = [
        'apikey: ' . $key,
        'Authorization: Bearer ' . $key,
    ];
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers),
        CURLOPT_TIMEOUT => 30,
    ]);
    if ($body) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    $res = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (in_array($http, [200, 201, 202, 204])) {
        $decoded = json_decode($res, true);
        return is_array($decoded) ? $decoded : [];
    }
    return null;
}

function ensureBucket(string $bucket = 'todo-files'): bool {
    $buckets = supabaseStorageRequest('GET', 'bucket');
    if ($buckets === null) return false;
    foreach ($buckets as $b) {
        if (($b['name'] ?? '') === $bucket) return true;
    }
    $res = supabaseStorageRequest('POST', 'bucket', ['Content-Type: application/json'], json_encode([
        'name' => $bucket,
        'public' => true,
    ]));
    return $res !== null;
}

function supabaseFileUrl(string $name, string $bucket = 'todo-files'): string {
    return rtrim(SUPABASE_URL, '/') . "/storage/v1/object/public/$bucket/" . rawurlencode($name);
}

function supabaseUploadFile(string $name, string $tmpPath, string $mime = '', string $bucket = 'todo-files'): bool {
    ensureBucket($bucket);
    if (!$mime) $mime = guessMime($name);
    $data = file_get_contents($tmpPath);
    if ($data === false) return false;
    $res = supabaseStorageRequest('POST', "object/$bucket/" . rawurlencode($name), [
        'Content-Type: ' . $mime,
        'x-upsert: true',
    ], $data);
    return $res !== null;
}

function supabaseDownloadFile(string $name, string $bucket = 'todo-files'): ?string {
    $url = rtrim(SUPABASE_URL, '/') . "/storage/v1/object/public/$bucket/" . rawurlencode($name);
    $data = @file_get_contents($url);
    if ($data === false) return null;
    $tmp = sys_get_temp_dir() . '/' . uniqid('sb_') . '_' . $name;
    file_put_contents($tmp, $data);
    return $tmp;
}

function supabaseListFiles(string $bucket = 'todo-files'): array {
    ensureBucket($bucket);
    $res = supabaseStorageRequest('POST', "object/list/$bucket", ['Content-Type: application/json'], json_encode([
        'prefix' => '',
        'sortBy' => ['column' => 'created_at', 'order' => 'desc'],
    ]));
    if (!$res) return [];
    $result = [];
    foreach ($res as $f) {
        $name = $f['name'] ?? '';
        if (!$name) continue;
        $result[] = [
            'name' => $name,
            'size' => $f['metadata']['size'] ?? 0,
            'mime' => $f['metadata']['mimetype'] ?? guessMime($name),
            'created_at' => $f['created_at'] ?? '',
            'url' => supabaseFileUrl($name, $bucket),
        ];
    }
    return $result;
}

function supabaseDeleteFile(string $name, string $bucket = 'todo-files'): bool {
    $res = supabaseStorageRequest('DELETE', "object/$bucket", ['Content-Type: application/json'], json_encode([
        'prefixes' => [$name],
    ]));
    return $res !== null;
}
