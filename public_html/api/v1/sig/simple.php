<?php
/**
 * API Legacy v1 - SIG Simple Endpoint (REDIRECTED)
 * Forward vers la nouvelle API v2
 */

header('Content-Type: application/json; charset=utf-8');

try {
    $exercice = $_GET['exercice'] ?? 2024;
    
    // Connect to SQLite directly (no dependencies)
    $projectRoot = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
    $dbPath = $projectRoot . '/compta.db';
    
    if (!file_exists($dbPath)) {
        http_response_code(500);
        throw new Exception("Database not found");
    }
    
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all transactions for the exercice
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as nb_ecritures,
            ROUND(SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END), 2) as total_debit,
            ROUND(SUM(CASE WHEN credit > 0 THEN credit ELSE 0 END), 2) as total_credit
        FROM ecritures
        WHERE exercice = ?
    ");
    $stmt->execute([$exercice]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate simple SIG
    $ca_brut = (float) $result['total_credit'] ?? 0;
    $charges = (float) $result['total_debit'] ?? 0;
    $resultat_net = $ca_brut - $charges;
    
    $response = [
        'success' => true,
        'data' => [
            'exercice' => (int) $exercice,
            'ca_brut' => $ca_brut,
            'charges' => $charges,
            'resultat_net' => $resultat_net,
            'nb_ecritures' => $result['nb_ecritures'],
            'balance' => abs($ca_brut - $charges) < 0.01 ? 'OK' : 'ERREUR'
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
