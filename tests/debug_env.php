<?php
header('Content-Type: application/json; charset=utf-8');

$bootstrap_path = dirname(dirname(dirname(__FILE__))) . '/bootstrap.php';
require_once $bootstrap_path;

// Check what variables are loaded
$vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'JWT_SECRET'];
$result = [];

foreach ($vars as $var) {
    $val = getenv($var);
    $result[$var] = $val ?: 'NOT SET';
}

echo json_encode(['env_vars' => $result, 'bootstrap_path' => $bootstrap_path]);
