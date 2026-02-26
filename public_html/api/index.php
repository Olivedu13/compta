<?php
/**
 * API REST - Self-contained (No dependencies)
 * Tous les endpoints en PDO direct
 */

header('Content-Type: application/json; charset=utf-8');

// ========================================
// Database Connection
// ========================================

$projectRoot = dirname(dirname(dirname(__FILE__)));
if (!file_exists($projectRoot . '/compta.db')) $projectRoot = dirname($projectRoot);
$dbPath = $projectRoot . '/compta.db';

if (!file_exists($dbPath)) {
    http_response_code(500);
    die(json_encode(['error' => 'Database not found'], JSON_UNESCAPED_UNICODE));
}

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['error' => 'DB error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
}

// ========================================
// Parse request
// ========================================

$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['path'] ?? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove /api prefix and trailing slash
$path = preg_replace('|^.*/api/?|', '', $path);
$path = trim($path, '/');

// Extract parameters
$parts = explode('/', $path);
$endpoint = $parts[0] ?? '';
$param1 = $parts[1] ?? '';
$param2 = $parts[2] ?? '';

try {
    // ========================================
    // GET /api/health
    // ========================================
    if ($method === 'GET' && $endpoint === 'health') {
        $dbStatus = true;
        try {
            $db->query("SELECT 1");
        } catch (Exception $e) {
            $dbStatus = false;
        }
        
        echo json_encode([
            'status' => 'OK',
            'version' => '1.0.0',
            'database' => $dbStatus ? 'connected' : 'disconnected'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // ========================================
    // GET /api/annee/2026/exists
    // ========================================
    if ($method === 'GET' && $endpoint === 'annee' && $param2 === 'exists') {
        $exercice = (int) $param1;
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM ecritures WHERE exercice = ?");
        $stmt->execute([$exercice]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'exercice' => $exercice,
            'exists' => $count > 0,
            'count' => $count
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // ========================================
    // GET /api/tiers
    // ========================================
    if ($method === 'GET' && $endpoint === 'tiers' && !$param1) {
        $exercice = $_GET['exercice'] ?? 2024;
        $limit = $_GET['limit'] ?? 50;
        $offset = $_GET['offset'] ?? 0;
        $tri = $_GET['tri'] ?? 'montant';
        
        // Validate sort
        $orderBy = ($tri === 'nom') ? 'numero_tiers, lib_tiers' : 'ABS(solde) DESC, numero_tiers';
        
        $stmt = $db->prepare("
            SELECT 
                numero_tiers as numero,
                lib_tiers as libelle,
                COALESCE(SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END), 0) as total_debit,
                COALESCE(SUM(CASE WHEN credit > 0 THEN credit ELSE 0 END), 0) as total_credit,
                ROUND(COALESCE(SUM(debit - credit), 0), 2) as solde,
                COUNT(*) as nb_ecritures
            FROM ecritures
            WHERE exercice = ? AND numero_tiers != ''
            GROUP BY numero_tiers
            ORDER BY $orderBy
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$exercice, $limit, $offset]);
        $tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Total
        $countStmt = $db->prepare("SELECT COUNT(DISTINCT numero_tiers) as total FROM ecritures WHERE exercice = ? AND numero_tiers != ''");
        $countStmt->execute([$exercice]);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo json_encode([
            'success' => true,
            'tiers' => $tiers,
            'pagination' => [
                'limit' => (int) $limit,
                'offset' => (int) $offset,
                'total' => (int) $total,
                'pages' => ceil($total / $limit)
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // ========================================
    // GET /api/tiers/NUMERO
    // ========================================
    if ($method === 'GET' && $endpoint === 'tiers' && $param1) {
        $numero = $param1;
        $exercice = $_GET['exercice'] ?? 2024;
        
        // Récupère les écritures du tiers
        $stmt = $db->prepare("
            SELECT 
                id,
                ecriture_date as date,
                journal_code as journal,
                compte_num as compte,
                libelle_ecriture as libelle,
                debit,
                credit,
                ROUND(debit - credit, 2) as solde,
                piece_ref as ref
            FROM ecritures
            WHERE exercice = ? AND numero_tiers = ?
            ORDER BY ecriture_date DESC
        ");
        $stmt->execute([$exercice, $numero]);
        $ecritures = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($ecritures)) {
            echo json_encode([
                'error' => 'Tiers not found',
                'numero' => $numero
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Agrégé
        $debit_total = array_sum(array_column($ecritures, 'debit'));
        $credit_total = array_sum(array_column($ecritures, 'credit'));
        
        echo json_encode([
            'success' => true,
            'tiers' => [
                'numero' => $numero,
                'total_debit' => round($debit_total, 2),
                'total_credit' => round($credit_total, 2),
                'solde' => round($debit_total - $credit_total, 2),
                'nb_ecritures' => count($ecritures)
            ],
            'ecritures' => $ecritures
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // ========================================
    // GET /api/cashflow
    // ========================================
    if ($method === 'GET' && $endpoint === 'cashflow' && !$param1) {
        $exercice = $_GET['exercice'] ?? 2024;
        $periode = $_GET['periode'] ?? 'mois';
        
        // Par mois
        $stmt = $db->prepare("
            SELECT 
                strftime('%Y-%m', ecriture_date) as periode,
                ROUND(SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END), 2) as entrees,
                ROUND(SUM(CASE WHEN credit > 0 THEN credit ELSE 0 END), 2) as sorties,
                COUNT(DISTINCT journal_code) as nb_journaux,
                COUNT(*) as nb_ecritures
            FROM ecritures
            WHERE exercice = ?
            GROUP BY strftime('%Y-%m', ecriture_date)
            ORDER BY periode
        ");
        $stmt->execute([$exercice]);
        $par_periode = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Par journal
        $stmt = $db->prepare("
            SELECT 
                journal_code as journal,
                ROUND(SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END), 2) as entrees,
                ROUND(SUM(CASE WHEN credit > 0 THEN credit ELSE 0 END), 2) as sorties,
                COUNT(*) as nb_ecritures
            FROM ecritures
            WHERE exercice = ?
            GROUP BY journal_code
            ORDER BY entrees DESC
        ");
        $stmt->execute([$exercice]);
        $par_journal = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Totaux
        $stmt = $db->prepare("
            SELECT 
                ROUND(SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END), 2) as total_entrees,
                ROUND(SUM(CASE WHEN credit > 0 THEN credit ELSE 0 END), 2) as total_sorties
            FROM ecritures
            WHERE exercice = ?
        ");
        $stmt->execute([$exercice]);
        $totals = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'stats_globales' => [
                'total_entrees' => (float) $totals['total_entrees'],
                'total_sorties' => (float) $totals['total_sorties'],
                'flux_net_total' => (float) $totals['total_entrees'] - (float) $totals['total_sorties']
            ],
            'par_periode' => $par_periode,
            'par_journal' => $par_journal
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // ========================================
    // GET /api/cashflow/detail/VE
    // ========================================
    if ($method === 'GET' && $endpoint === 'cashflow' && $param1 === 'detail' && $param2) {
        $journal = $param2;
        $exercice = $_GET['exercice'] ?? 2024;
        
        // Stats du journal
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as nb_ecritures,
                COUNT(DISTINCT ecriture_date) as nb_jours_actifs,
                ROUND(SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END), 2) as total_debit,
                ROUND(SUM(CASE WHEN credit > 0 THEN credit ELSE 0 END), 2) as total_credit
            FROM ecritures
            WHERE exercice = ? AND journal_code = ?
        ");
        $stmt->execute([$exercice, $journal]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['solde'] = (float) $stats['total_debit'] - (float) $stats['total_credit'];
        
        // Top comptes
        $stmt = $db->prepare("
            SELECT 
                compte_num as compte,
                COUNT(*) as nb_ecritures,
                ROUND(SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END), 2) as debit,
                ROUND(SUM(CASE WHEN credit > 0 THEN credit ELSE 0 END), 2) as credit
            FROM ecritures
            WHERE exercice = ? AND journal_code = ?
            GROUP BY compte_num
            ORDER BY (debit + credit) DESC
            LIMIT 10
        ");
        $stmt->execute([$exercice, $journal]);
        $top_comptes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($top_comptes as &$c) {
            $c['solde'] = (float) $c['debit'] - (float) $c['credit'];
            $c['libelle'] = '';
        }
        
        echo json_encode([
            'success' => true,
            'journal' => $journal,
            'stats' => $stats,
            'top_comptes' => $top_comptes
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // ========================================
    // Not found
    // ========================================
    http_response_code(404);
    echo json_encode([
        'error' => 'Endpoint not found',
        'endpoint' => $endpoint,
        'method' => $method
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur serveur',
        'debug' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ], JSON_UNESCAPED_UNICODE);
}
