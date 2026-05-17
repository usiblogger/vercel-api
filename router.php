<?php
require_once __DIR__ . '/api/storage.php';
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($uri === '/' || $uri === '') {
    readfile(__DIR__ . '/index.html');
    return true;
}
if (preg_match('#^/api/todos#', $uri)) {
    require __DIR__ . '/api/todos.php';
    return true;
}
if (preg_match('#^/api/pos#', $uri)) {
    require __DIR__ . '/api/pos.php';
    return true;
}
if (preg_match('#^/api/files#', $uri)) {
    require __DIR__ . '/api/files.php';
    return true;
}
if (preg_match('#^/uploads/(.+)#', $uri, $m)) {
    $path = __DIR__ . '/uploads/' . basename($m[1]);
    if (file_exists($path)) {
        $mime = guessMime($path);
        header('Content-Type: ' . $mime);
        readfile($path);
        return true;
    }
    return false;
}
return false;
