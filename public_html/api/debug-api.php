<?php
/**
 * Debug API - Test complet
 */

header('Content-Type: application/json; charset=utf-8');

define('APP_ROOT', dirname(dirname(dirname(__FILE__))));
define('BACKEND_ROOT', APP_ROOT . '/backend');

spl_autoload_register(function($class) {
    $class = str_replace('App\\', '', $class);
    $path = str_replace('\\', '/', $class);
    $parts = explode('/', $path);
    if (count($parts) > 0) $parts[0] = strtolower($parts[0]);
    $path = implode('/', $parts);
    $filePath = BACKEND_ROOT . '/' . $path . '.php';
    if (file_exists($filePath)) require_once $filePath;
});

use App\Config\Database;

try {
    $db = Database::getInstance();
    
    $result = [
        'status' => 'OK',
        'database' => 'connected',
        'tables' => []
    ];
    
    // Vérifie les tables
    $tables = ['fin_ecritures_fec', 'fin_balance', 'sys_plan_comptable', 'sys_journaux'];
    foreach ($tables as $table) {
        $count = $db->fetchOne("SELECT COUNT(*) as cnt FROM $table");
        $result['tables'][$table] = $count['cnt'] ?? 0;
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'ERROR',
        'error' => $e->getMessage()
    ]);
}
?>
