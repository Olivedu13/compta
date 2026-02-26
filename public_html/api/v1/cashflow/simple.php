<?php
/**
 * GET /api/v1/cashflow/simple.php
 * Retourne les données de cashflow par période et journal
 * Self-contained - No dependencies
 * 
 * Params:
 * - exercice (required): Année comptable
 * - periode (optional): mois|trimestre|semaine (default: mois)
 */

header('Content-Type: application/json; charset=utf-8');

try {
    // Validate params
    $exercice = isset($_GET['exercice']) ? (int)$_GET['exercice'] : null;
    $periode = $_GET['periode'] ?? 'mois';
    
    if (!$exercice || $exercice < 1900 || $exercice > 2100) {
        http_response_code(400);
        throw new Exception('Parameter exercice is required and must be a valid year');
    }
    
    // Get DB - 5 levels up: cashflow -> v1 -> api -> public_html -> compta
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
    
    // Get all journals
    $stmt = $db->prepare("
        SELECT DISTINCT journal_code FROM ecritures WHERE exercice = ? ORDER BY journal_code
    ");
    $stmt->execute([$exercice]);
    $journals = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Par journal - totals (mouvements bruts par journal)
    $parJournal = [];
    foreach ($journals as $journal) {
        $stmt = $db->prepare("
            SELECT 
                journal_lib,
                SUM(CAST(debit AS REAL)) as total_debit,
                SUM(CAST(credit AS REAL)) as total_credit,
                COUNT(*) as nb_ecritures
            FROM ecritures
            WHERE exercice = ? AND journal_code = ?
            GROUP BY journal_code
        ");
        $stmt->execute([$exercice, $journal]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $parJournal[] = [
            'journal' => $journal,
            'journal_lib' => $row['journal_lib'] ?? '',
            'total_debit' => (float)($row['total_debit'] ?? 0),
            'total_credit' => (float)($row['total_credit'] ?? 0),
            'flux_net' => (float)(($row['total_debit'] ?? 0) - ($row['total_credit'] ?? 0)),
            'nb_ecritures' => (int)($row['nb_ecritures'] ?? 0)
        ];
    }
    
    // Cashflow réel = mouvements sur comptes de trésorerie (classe 5)
    // Débit classe 5 = encaissement, Crédit classe 5 = décaissement
    $parPeriode = [];
    for ($month = 1; $month <= 12; $month++) {
        $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
        
        // Flux trésorerie (comptes 5xx)
        $stmt = $db->prepare("
            SELECT 
                SUM(CAST(debit AS REAL)) as encaissements,
                SUM(CAST(credit AS REAL)) as decaissements,
                COUNT(*) as nb_ecritures
            FROM ecritures
            WHERE exercice = ? AND strftime('%m', ecriture_date) = ?
              AND SUBSTR(compte_num, 1, 1) = '5'
        ");
        $stmt->execute([$exercice, $monthStr]);
        $tresorerie = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Produits encaissés (classe 7)
        $stmt = $db->prepare("
            SELECT SUM(CAST(credit AS REAL)) - SUM(CAST(debit AS REAL)) as produits
            FROM ecritures
            WHERE exercice = ? AND strftime('%m', ecriture_date) = ?
              AND SUBSTR(compte_num, 1, 1) = '7'
        ");
        $stmt->execute([$exercice, $monthStr]);
        $prodRow = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Charges décaissées (classe 6)  
        $stmt = $db->prepare("
            SELECT SUM(CAST(debit AS REAL)) - SUM(CAST(credit AS REAL)) as charges
            FROM ecritures
            WHERE exercice = ? AND strftime('%m', ecriture_date) = ?
              AND SUBSTR(compte_num, 1, 1) = '6'
        ");
        $stmt->execute([$exercice, $monthStr]);
        $chargesRow = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $enc = (float)($tresorerie['encaissements'] ?? 0);
        $dec = (float)($tresorerie['decaissements'] ?? 0);
        
        if ($enc > 0 || $dec > 0) {
            $parPeriode[] = [
                'periode' => $monthStr . '/' . $exercice,
                'encaissements' => $enc,
                'decaissements' => $dec,
                'flux_net' => $enc - $dec,
                'produits_periode' => (float)($prodRow['produits'] ?? 0),
                'charges_periode' => (float)($chargesRow['charges'] ?? 0),
                'resultat_periode' => (float)($prodRow['produits'] ?? 0) - (float)($chargesRow['charges'] ?? 0),
                'nb_ecritures' => (int)($tresorerie['nb_ecritures'] ?? 0)
            ];
        }
    }
    
    // Stats globales trésorerie (comptes 5xx uniquement)
    $stmt = $db->prepare("
        SELECT 
            SUM(CAST(debit AS REAL)) as total_encaissements,
            SUM(CAST(credit AS REAL)) as total_decaissements,
            COUNT(*) as nb_ecritures
        FROM ecritures
        WHERE exercice = ? AND SUBSTR(compte_num, 1, 1) = '5'
    ");
    $stmt->execute([$exercice]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Solde trésorerie fin de période
    $totalEnc = (float)($stats['total_encaissements'] ?? 0);
    $totalDec = (float)($stats['total_decaissements'] ?? 0);
    
    $cashflow = [
        'exercice' => $exercice,
        'stats_globales' => [
            'total_encaissements' => $totalEnc,
            'total_decaissements' => $totalDec,
            'flux_net_tresorerie' => $totalEnc - $totalDec,
            'nb_mouvements_tresorerie' => (int)($stats['nb_ecritures'] ?? 0)
        ],
        'par_journal' => $parJournal,
        'par_periode' => $parPeriode
    ];
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $cashflow
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
