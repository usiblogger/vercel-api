<?php

define('DB_DRIVER', getenv('DB_DRIVER') ?: 'sqlite');
define('SUPABASE_URL', getenv('SUPABASE_URL') ?: '');
define('SUPABASE_KEY', getenv('SUPABASE_KEY') ?: '');
define('SQLITE_PATH', getenv('SQLITE_PATH') ?: __DIR__ . '/../data/todos.db');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
