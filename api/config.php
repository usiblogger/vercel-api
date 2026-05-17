<?php

define('DB_DRIVER', getenv('DB_DRIVER') ?: 'sqlite');
define('STORAGE_DRIVER', getenv('STORAGE_DRIVER') ?: 'local');
define('SUPABASE_URL', getenv('SUPABASE_URL') ?: '');
define('SUPABASE_KEY', getenv('SUPABASE_KEY') ?: '');
define('SUPABASE_SERVICE_KEY', getenv('SUPABASE_SERVICE_KEY') ?: '');
define('SQLITE_PATH', getenv('SQLITE_PATH') ?: __DIR__ . '/../data/todos.db');
define('UPLOAD_DIR', getenv('UPLOAD_DIR') ?: __DIR__ . '/../uploads');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
