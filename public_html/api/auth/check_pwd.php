<?php
header('Content-Type: application/json; charset=utf-8');

$_root = dirname(dirname(dirname(__FILE__)));
if (!file_exists($_root . '/bootstrap.php')) $_root = dirname($_root);
$bootstrap_path = $_root . '/bootstrap.php';
require_once $bootstrap_path;

try {
    $db = \App\Config\Database::getInstance();
    
    $user = $db->fetchOne("SELECT email, password_hash FROM sys_utilisateurs WHERE email = ?", 
        ['admin@atelier-thierry.fr']);
    
    echo json_encode(['user' => $user]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
