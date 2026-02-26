<?php
/**
 * GET /api/v1/years/list.php
 * Liste toutes les années disponibles
 * Self-contained - No dependencies
 */

header('Content-Type: application/json; charset=utf-8');

try {
    // Get DB - 5 levels up: years -> v1 -> api -> public_html -> compta
    // Find project root (works locally with public_html/ and on Ionos flat webroot)
    $projectRoot = dirname(dirname(dirname(__DIR__)));
    if (!file_exists($projectRoot . '/compta.db')) {
        $projectRoot = dirname($projectRoot);
    }
    $dbPath = $projectRoot . '/compta.db';
    
    if (!file_exists($dbPath)) {
        http_response_code(500);
        throw new Exception("Database not found");
    }
    
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Merge years from both ecritures (backend imports) and reports (frontend imports)
    $years = [];
    
    // From ecritures table
    $stmt = $db->query("SELECT DISTINCT exercice FROM ecritures ORDER BY exercice DESC");
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $y) {
        $years[] = (int)$y;
    }
    
    // From reports table (frontend saves year + data_json via api.php)
    try {
        $stmt2 = $db->query("SELECT DISTINCT year FROM reports ORDER BY year DESC");
        foreach ($stmt2->fetchAll(PDO::FETCH_COLUMN) as $y) {
            if (!in_array((int)$y, $years)) $years[] = (int)$y;
        }
    } catch (Exception $e) {
        // reports table may not exist yet, ignore
    }
    
    rsort($years);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $years
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
