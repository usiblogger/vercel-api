<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/supabase.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$name = null;

if (preg_match('#^/api/files(?:/(.+))?#', $path, $m)) {
    $name = $m[1] ?? null;
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

try {
    switch ($method) {
        case 'GET':
            if ($name) {
                $local = downloadFile($name);
                if (!$local) { http_response_code(404); echo json_encode(['error' => 'File not found']); exit; }
                $mime = guessMime($name);
                header('Content-Type: ' . $mime);
                header('Content-Disposition: inline; filename="' . $name . '"');
                readfile($local);
                if (STORAGE_DRIVER === 'supabase') unlink($local);
            } else {
                echo json_encode(['data' => listFiles()]);
            }
            break;

        case 'POST':
            if (!isset($_FILES['file'])) {
                http_response_code(400);
                echo json_encode(['error' => 'No file uploaded']);
                exit;
            }
            $f = $_FILES['file'];
            $original = basename($f['name']);
            $ext = pathinfo($original, PATHINFO_EXTENSION);
            $safeName = uniqid() . ($ext ? '.' . $ext : '');
            $ok = uploadFile($safeName, $f['tmp_name']);
            if (!$ok) { http_response_code(500); echo json_encode(['error' => 'Upload failed']); exit; }
            http_response_code(201);
            echo json_encode(['data' => [
                'name' => $safeName,
                'original' => $original,
                'size' => $f['size'],
                'mime' => guessMime($safeName),
                'url' => fileUrl($safeName),
            ]]);
            break;

        case 'DELETE':
            if (!$name) { http_response_code(400); echo json_encode(['error' => 'Filename required']); exit; }
            $name = basename($name);
            if (!deleteFile($name)) { http_response_code(404); echo json_encode(['error' => 'File not found']); exit; }
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
