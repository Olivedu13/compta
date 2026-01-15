<?php
/**
 * GET /api/v1/years/list
 * Liste toutes les années disponibles
 */

header('Content-Type: application/json; charset=utf-8');

try {
    $projectRoot = dirname(dirname(dirname(dirname(__FILE__))));
    $dbPath = $projectRoot . '/compta.db';
    
    if (!file_exists($dbPath)) {
        throw new Exception("Database not found");
    }
    
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $db->query("
        SELECT DISTINCT exercice 
        FROM ecritures 
        ORDER BY exercice DESC
    ");
    $years = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => array_column($years, 'exercice')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

use App\Config\Database;
use App\Config\Logger;

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDatabase();
    
    // Récupère toutes les années disponibles
    $annees = $db->fetchAll("
        SELECT DISTINCT exercice 
        FROM fin_balance 
        ORDER BY exercice DESC
    ");
    
    $result = array_column($annees, 'exercice');
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $result
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
