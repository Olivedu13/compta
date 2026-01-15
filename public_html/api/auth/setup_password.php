<?php
header('Content-Type: application/json; charset=utf-8');

try {
    $bootstrap_path = dirname(dirname(dirname(__FILE__))) . '/bootstrap.php';
    require_once $bootstrap_path;
    
    $db = \App\Config\Database::getInstance();
    $new_hash = '$2y$10$dxqgPTWo98TYjGpN87xb8O/zfdMHvF1yyxWDKtGA4TslbPVyFjqt6';
    
    // Update password for all test users
    $db->execute(
        "UPDATE sys_utilisateurs SET password_hash = ? WHERE email IN (?, ?, ?)",
        [$new_hash, 'admin@atelier-thierry.fr', 'comptable@atelier-thierry.fr', 'directeur@atelier-thierry.fr']
    );
    
    echo json_encode(['success' => true, 'message' => 'Passwords updated']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
