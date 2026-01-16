<?php
/**
 * GET /api/v1/sig-simple.php
 * Calcule les Soldes Intermédiaires de Gestion (SIG)
 * Self-contained - No dependencies
 * 
 * Params:
 * - exercice (required): Année comptable (ex: 2024)
 */

header('Content-Type: application/json; charset=utf-8');

try {
    // Validate exercice
    $exercice = isset($_GET['exercice']) ? (int)$_GET['exercice'] : null;
    
    if (!$exercice || $exercice < 1900 || $exercice > 2100) {
        http_response_code(400);
        throw new Exception('Parameter exercice is required and must be a valid year');
    }
    
    // Get DB
    $projectRoot = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
    $dbPath = $projectRoot . '/compta.db';
    if (!file_exists($dbPath)) {
        throw new Exception("Database not found");
    }
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query all ecritures for the exercice
    $stmt = $db->prepare("
        SELECT 
            compte_num,
            SUM(CAST(debit AS REAL) - CAST(credit AS REAL)) as total_montant
        FROM ecritures
        WHERE exercice = ?
        GROUP BY compte_num
    ");
    $stmt->execute([$exercice]);
    $ecritures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize by class
    $parClasse = [];
    foreach ($ecritures as $e) {
        $compte = $e['compte_num'];
        $classe = substr($compte, 0, 1);
        
        if (!isset($parClasse[$classe])) {
            $parClasse[$classe] = ['total' => 0, 'comptes' => []];
        }
        
        $montant = (float)$e['total_montant'];
        $parClasse[$classe]['total'] += $montant;
        $parClasse[$classe]['comptes'][$compte] = $montant;
    }
    
    // Get totals by class
    $classe1 = $parClasse['1']['total'] ?? 0;  // Assets
    $classe2 = $parClasse['2']['total'] ?? 0;  // Liabilities
    $classe3 = $parClasse['3']['total'] ?? 0;  // Inventory
    $classe4 = $parClasse['4']['total'] ?? 0;  // Receivables
    $classe5 = $parClasse['5']['total'] ?? 0;  // Cash
    $classe6 = $parClasse['6']['total'] ?? 0;  // Expenses
    $classe7 = $parClasse['7']['total'] ?? 0;  // Income
    
    // Get total records for validation
    $stmt = $db->prepare("SELECT COUNT(*) as cnt, SUM(CAST(debit AS REAL) - CAST(credit AS REAL)) as total FROM ecritures WHERE exercice = ?");
    $stmt->execute([$exercice]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $sig = [
        'exercice' => $exercice,
        'ca_brut' => abs($classe7),
        'charges' => abs($classe6),
        'resultat_net' => abs($classe7) - abs($classe6),
        'nb_ecritures' => (int)$stats['cnt'],
        'balance' => abs($classe1 + $classe2 + $classe3 + $classe4 + $classe5 + $classe6 + $classe7) < 0.01 ? 'OK' : 'WARN'
    ];
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $sig
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}