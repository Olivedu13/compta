<?php
require_once dirname(dirname(__FILE__)) . '/bootstrap.php';

use App\Config\AuthMiddleware;
use App\Config\Logger;

header('Content-Type: application/json; charset=utf-8');

try {
    // Verify authentication
    $user = AuthMiddleware::requireAuth();
    
    Logger::info("Session verified", ['user_id' => $user->uid]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
    
} catch (\Exception $e) {
    Logger::error("Session verification failed", ['error' => $e->getMessage()]);
    
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
