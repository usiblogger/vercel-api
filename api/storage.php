<?php

function guessMime(string $path): string {
    $map = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml',
        'pdf' => 'application/pdf', 'txt' => 'text/plain', 'csv' => 'text/csv',
        'json' => 'application/json', 'mp4' => 'video/mp4', 'mp3' => 'audio/mpeg',
        'zip' => 'application/zip', 'gz' => 'application/gzip',
        'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return $map[$ext] ?? 'application/octet-stream';
}

function fileUrl(string $name): string {
    if (STORAGE_DRIVER === 'supabase') return supabaseFileUrl($name);
    return '/uploads/' . rawurlencode($name);
}

function uploadFile(string $name, string $tmpPath, string $mime = ''): bool {
    if (STORAGE_DRIVER === 'supabase') return supabaseUploadFile($name, $tmpPath, $mime);
    $dir = UPLOAD_DIR;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return copy($tmpPath, $dir . '/' . $name);
}

function downloadFile(string $name): ?string {
    if (STORAGE_DRIVER === 'supabase') return supabaseDownloadFile($name);
    $path = UPLOAD_DIR . '/' . $name;
    return file_exists($path) ? $path : null;
}

function listFiles(): array {
    if (STORAGE_DRIVER === 'supabase') return supabaseListFiles();
    $dir = UPLOAD_DIR;
    if (!is_dir($dir)) return [];
    $files = array_diff(scandir($dir), ['.', '..', '.gitkeep']);
    $result = [];
    foreach ($files as $f) {
        $p = $dir . '/' . $f;
        $result[] = [
            'name' => $f,
            'size' => filesize($p),
            'mime' => guessMime($f),
            'created_at' => date('c', filemtime($p)),
            'url' => fileUrl($f),
        ];
    }
    return $result;
}

function deleteFile(string $name): bool {
    if (STORAGE_DRIVER === 'supabase') return supabaseDeleteFile($name);
    $path = UPLOAD_DIR . '/' . $name;
    return file_exists($path) ? unlink($path) : false;
}
