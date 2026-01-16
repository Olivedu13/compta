<?php
/**
 * GET /api/v1/kpis/detailed.php
 * Calcule les KPIs détaillés (Indicateurs clés de performance)
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
    
    // Get DB - 5 levels up: kpis -> v1 -> api -> public_html -> compta
    $projectRoot = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
    $dbPath = $projectRoot . '/compta.db';
    
    if (!file_exists($dbPath)) {
        http_response_code(500);
        throw new Exception("Database not found at: " . $dbPath);
    }
    
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all tiers (clients/suppliers) for the exercice
    $stmt = $db->prepare("
        SELECT 
            numero_tiers,
            lib_tiers,
            SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END) as total_debit,
            SUM(CASE WHEN credit > 0 THEN credit ELSE 0 END) as total_credit,
            COUNT(*) as nb_operations
        FROM ecritures
        WHERE exercice = ?
        AND numero_tiers IS NOT NULL
        AND numero_tiers != ''
        GROUP BY numero_tiers, lib_tiers
        ORDER BY total_credit DESC
        LIMIT 20
    ");
    $stmt->execute([$exercice]);
    $topTiersRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process tiers data
    $topTiers = [];
    foreach ($topTiersRaw as $tier) {
        $topTiers[] = [
            'numero' => $tier['numero_tiers'],
            'nom' => $tier['lib_tiers'],
            'debit' => (float)$tier['total_debit'],
            'credit' => (float)$tier['total_credit'],
            'solde' => (float)$tier['total_debit'] - (float)$tier['total_credit'],
            'operations' => (int)$tier['nb_operations']
        ];
    }
    
    // Get account balances by class
    $stmt = $db->prepare("
        SELECT 
            compte_num,
            compte_lib,
            SUM(CAST(debit AS REAL)) as total_debit,
            SUM(CAST(credit AS REAL)) as total_credit
        FROM ecritures
        WHERE exercice = ?
        GROUP BY compte_num, compte_lib
        ORDER BY compte_num
    ");
    $stmt->execute([$exercice]);
    $comptes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize by class
    $parClasse = [];
    $classLabels = [
        '1' => 'Immobilisations',
        '2' => 'Actifs circulants',
        '3' => 'Stocks',
        '4' => 'Tiers',
        '5' => 'Trésorerie',
        '6' => 'Charges',
        '7' => 'Produits'
    ];
    
    foreach ($comptes as $compte) {
        $classe = substr($compte['compte_num'], 0, 1);
        $debit = (float)$compte['total_debit'];
        $credit = (float)$compte['total_credit'];
        $solde = $debit - $credit;
        
        if (!isset($parClasse[$classe])) {
            $parClasse[$classe] = [
                'label' => $classLabels[$classe] ?? "Classe $classe",
                'total_debit' => 0,
                'total_credit' => 0,
                'comptes' => []
            ];
        }
        
        $parClasse[$classe]['total_debit'] += $debit;
        $parClasse[$classe]['total_credit'] += $credit;
        $parClasse[$classe]['comptes'][] = [
            'numero' => $compte['compte_num'],
            'libelle' => $compte['compte_lib'],
            'debit' => $debit,
            'credit' => $credit,
            'solde' => $solde
        ];
    }
    
    // Calculate global stats
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as nb_total,
            SUM(CAST(debit AS REAL)) as total_debit,
            SUM(CAST(credit AS REAL)) as total_credit
        FROM ecritures
        WHERE exercice = ?
    ");
    $stmt->execute([$exercice]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $totalDebit = (float)$stats['total_debit'];
    $totalCredit = (float)$stats['total_credit'];
    
    // Extract specific accounts for dashboard
    $stock = ['or' => 0];
    $tresorerie = ['banque' => 0, 'caisse' => 0];
    $tiers = ['clients' => 0, 'fournisseurs' => 0];
    
    foreach ($comptes as $compte) {
        $num = $compte['compte_num'];
        $solde = (float)$compte['total_debit'] - (float)$compte['total_credit'];
        
        // Stock (31x, 32x, 37x - comptes 3)
        if (substr($num, 0, 2) === '31' || substr($num, 0, 2) === '32' || substr($num, 0, 2) === '37') {
            $stock['or'] += $solde;
        }
        // Trésorerie (51x, 53x - comptes 5)
        if (substr($num, 0, 2) === '51') {
            $tresorerie['banque'] += $solde;
        }
        if (substr($num, 0, 2) === '53') {
            $tresorerie['caisse'] += $solde;
        }
        // Tiers (411, 401 - comptes 4)
        if (substr($num, 0, 3) === '411') {
            $tiersBalance['clients'] += $solde;
        }
        if (substr($num, 0, 3) === '401') {
            $tiersBalance['fournisseurs'] += $solde;
        }
    }
    
    $kpis = [
        'exercice' => $exercice,
        'global' => [
            'total_operations' => (int)$stats['nb_total'],
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'balance' => abs($totalDebit - $totalCredit) < 0.01 ? 'OK' : 'ERREUR'
        ],
        'stock' => $stock,
        'tresorerie' => $tresorerie,
        'tiers' => $tiersBalance,
        'par_classe' => $parClasse,
        'top_tiers' => $topTiers
    ];
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $kpis
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}