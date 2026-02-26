<?php
header('Content-Type: application/json; charset=utf-8');

$_root = dirname(dirname(dirname(__FILE__)));
if (!file_exists($_root . '/bootstrap.php')) $_root = dirname($_root);
$bootstrap_path = $_root . '/bootstrap.php';
require_once $bootstrap_path;

try {
    $db = \App\Config\Database::getInstance();
    $new_hash = '$2y$10$dxqgPTWo98TYjGpN87xb8O/zfdMHvF1yyxWDKtGA4TslbPVyFjqt6';
    
    // Use query() instead of execute()
    $db->query(
        "UPDATE sys_utilisateurs SET password_hash = ? WHERE email = ?",
        [$new_hash, 'admin@atelier-thierry.fr']
    );
    
    echo json_encode(['success' => true, 'message' => 'Password updated']);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
