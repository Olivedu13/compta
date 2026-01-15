<?php
/**
 * GET /api/v1/years/list
 * Liste toutes les années disponibles
 * 
 * @method GET
 * @return {success: boolean, data: array, error?: string}
 */

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/backend/bootstrap.php';

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
