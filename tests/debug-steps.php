<?php
header('Content-Type: application/json');

echo json_encode([
    'step' => 'start',
    'time' => time()
]);

flush();

// Test 1: Can we read input?
$input = json_decode(file_get_contents('php://input'), true);
echo json_encode(['step' => 'input_read', 'email' => $input['email'] ?? null]);
flush();

// Test 2: Can we load bootstrap?
require_once dirname(dirname(__FILE__)) . '/bootstrap.php';
echo json_encode(['step' => 'bootstrap_loaded']);
flush();

// Test 3: Can we get DB?
use App\Config\Database;
$db = Database::getInstance();
echo json_encode(['step' => 'db_connected']);
flush();

// Test 4: Can we query?
$user = $db->fetchOne("SELECT * FROM sys_utilisateurs WHERE email = ?", [$input['email']]);
echo json_encode(['step' => 'query_done', 'found_user' => $user ? true : false]);
flush();

?>
