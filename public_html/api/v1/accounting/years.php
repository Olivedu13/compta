<?php
/**
 * GET /api/v1/accounting/years
 * Liste les années disponibles
 */

use App\Config\Database;
use App\Config\Logger;

try {
    $db = getDatabase();
    
    $annees = $db->fetchAll(
        "SELECT DISTINCT exercice FROM fin_balance ORDER BY exercice DESC"
    );
    
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
    Logger::error('Failed to fetch years', ['error' => $e->getMessage()]);
}
