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
    
    // Get DB - 6 levels up: cashflow -> v1 -> api -> public_html -> compta
    $projectRoot = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
    $dbPath = $projectRoot . '/compta.db';
    
    if (!file_exists($dbPath)) {
        http_response_code(500);
        throw new Exception("Database not found");
    }
    
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all journals
    $stmt = $db->prepare("
        SELECT DISTINCT journal FROM ecritures WHERE exercice = ? ORDER BY journal
    ");
    $stmt->execute([$exercice]);
    $journals = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Par journal - totals
    $parJournal = [];
    foreach ($journals as $journal) {
        $stmt = $db->prepare("
            SELECT 
                SUM(CAST(debit AS REAL)) as total_debit,
                SUM(CAST(credit AS REAL)) as total_credit,
                COUNT(*) as nb_ecritures
            FROM ecritures
            WHERE exercice = ? AND journal = ?
        ");
        $stmt->execute([$exercice, $journal]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $parJournal[] = [
            'journal' => $journal,
            'entrees' => (float)($row['total_debit'] ?? 0),
            'sorties' => (float)($row['total_credit'] ?? 0),
            'flux_net' => (float)(($row['total_debit'] ?? 0) - ($row['total_credit'] ?? 0)),
            'nb_ecritures' => (int)($row['nb_ecritures'] ?? 0)
        ];
    }
    
    // Par période (par mois pour l'année)
    $parPeriode = [];
    for ($month = 1; $month <= 12; $month++) {
        $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
        
        $stmt = $db->prepare("
            SELECT 
                SUM(CAST(debit AS REAL)) as total_debit,
                SUM(CAST(credit AS REAL)) as total_credit,
                COUNT(*) as nb_ecritures
            FROM ecritures
            WHERE exercice = ? AND strftime('%m', date_piece) = ?
        ");
        $stmt->execute([$exercice, $monthStr]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (($row['total_debit'] ?? 0) > 0 || ($row['total_credit'] ?? 0) > 0) {
            $parPeriode[] = [
                'periode' => $monthStr . '/' . $exercice,
                'entrees' => (float)($row['total_debit'] ?? 0),
                'sorties' => (float)($row['total_credit'] ?? 0),
                'flux_net' => (float)(($row['total_debit'] ?? 0) - ($row['total_credit'] ?? 0)),
                'nb_ecritures' => (int)($row['nb_ecritures'] ?? 0)
            ];
        }
    }
    
    // Stats globales
    $stmt = $db->prepare("
        SELECT 
            SUM(CAST(debit AS REAL)) as total_debit,
            SUM(CAST(credit AS REAL)) as total_credit,
            COUNT(*) as nb_ecritures
        FROM ecritures
        WHERE exercice = ?
    ");
    $stmt->execute([$exercice]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $cashflow = [
        'exercice' => $exercice,
        'stats_globales' => [
            'total_entrees' => (float)($stats['total_debit'] ?? 0),
            'total_sorties' => (float)($stats['total_credit'] ?? 0),
            'flux_net_total' => (float)(($stats['total_debit'] ?? 0) - ($stats['total_credit'] ?? 0))
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
