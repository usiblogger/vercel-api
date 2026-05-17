<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($uri === '/' || $uri === '') {
    readfile(__DIR__ . '/index.html');
    return true;
}
if (preg_match('#^/api/#', $uri)) {
    require __DIR__ . '/api/todos.php';
    return true;
}
return false;
