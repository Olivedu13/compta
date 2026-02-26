<?php
/**
 * GET /api/v1/expenses/lines.php
 * Retourne toutes les lignes de charges (classe 6) — écritures brutes
 * 
 * Params:
 * - exercice (required): Année comptable
 * - page (optional): Numéro de page (défaut: 1)
 * - per_page (optional): Lignes par page (défaut: 200, max: 1000)
 * - compte (optional): Filtre par numéro de compte (préfixe)
 * - journal (optional): Filtre par code journal
 * - search (optional): Recherche texte libre (libellé écriture)
 * - sort (optional): Champ de tri (date|compte|montant|journal) défaut: date
 * - order (optional): asc|desc (défaut: desc)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $exercice = isset($_GET['exercice']) ? (int)$_GET['exercice'] : null;
    
    if (!$exercice || $exercice < 1900 || $exercice > 2100) {
        http_response_code(400);
        throw new Exception('Parameter exercice is required and must be a valid year');
    }
    
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(1000, max(10, (int)($_GET['per_page'] ?? 200)));
    $compteFilter = $_GET['compte'] ?? null;
    $journalFilter = $_GET['journal'] ?? null;
    $searchFilter = $_GET['search'] ?? null;
    $sortField = $_GET['sort'] ?? 'date';
    $sortOrder = strtolower($_GET['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

    // Find database
    $projectRoot = dirname(dirname(dirname(__DIR__)));
    if (!file_exists($projectRoot . '/compta.db')) {
        $projectRoot = dirname($projectRoot);
    }
    $dbPath = $projectRoot . '/compta.db';
    
    if (!file_exists($dbPath)) {
        throw new Exception("Database not found");
    }
    
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Build WHERE clause
    $where = "WHERE exercice = ? AND SUBSTR(compte_num, 1, 1) = '6'";
    $params = [$exercice];
    
    if ($compteFilter) {
        $where .= " AND compte_num LIKE ?";
        $params[] = $compteFilter . '%';
    }
    
    if ($journalFilter) {
        $where .= " AND journal_code = ?";
        $params[] = $journalFilter;
    }
    
    if ($searchFilter) {
        $where .= " AND (libelle_ecriture LIKE ? OR compte_lib LIKE ? OR piece_ref LIKE ?)";
        $params[] = '%' . $searchFilter . '%';
        $params[] = '%' . $searchFilter . '%';
        $params[] = '%' . $searchFilter . '%';
    }
    
    // Sort mapping
    $sortMap = [
        'date' => 'ecriture_date',
        'compte' => 'compte_num',
        'montant' => '(CAST(debit AS REAL) - CAST(credit AS REAL))',
        'journal' => 'journal_code',
        'libelle' => 'libelle_ecriture'
    ];
    $sortCol = $sortMap[$sortField] ?? 'ecriture_date';
    
    // Count total
    $countStmt = $db->prepare("SELECT COUNT(*) FROM ecritures $where");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    
    // Fetch lines
    $offset = ($page - 1) * $perPage;
    // Use exact column names from the DB schema
    $selectStr = 'ecriture_date, journal_code, journal_lib, ecriture_num, compte_num, compte_lib, numero_tiers, lib_tiers, piece_ref, date_piece, libelle_ecriture, CAST(debit AS REAL) as debit, CAST(credit AS REAL) as credit, lettrage_flag';

    $sql = "
        SELECT $selectStr
        FROM ecritures
        $where
        ORDER BY $sortCol $sortOrder, ecriture_date DESC
        LIMIT $perPage OFFSET $offset
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format lines
    $formatted = [];
    foreach ($lines as $l) {
        $debit = round((float)$l['debit'], 2);
        $credit = round((float)$l['credit'], 2);
        $formatted[] = [
            'date' => $l['ecriture_date'] ?? '',
            'journal' => $l['journal_code'] ?? '',
            'journal_lib' => $l['journal_lib'] ?? '',
            'num' => $l['ecriture_num'] ?? '',
            'compte' => $l['compte_num'] ?? '',
            'compte_lib' => $l['compte_lib'] ?? '',
            'aux_num' => $l['numero_tiers'] ?? '',
            'aux_lib' => $l['lib_tiers'] ?? '',
            'piece' => $l['piece_ref'] ?? '',
            'piece_date' => $l['date_piece'] ?? '',
            'libelle' => $l['libelle_ecriture'] ?? '',
            'debit' => $debit,
            'credit' => $credit,
            'montant' => round($debit - $credit, 2),
            'lettrage' => $l['lettrage_flag'] ?? '',
        ];
    }
    
    // Get available journals for filter
    $jStmt = $db->prepare("
        SELECT DISTINCT journal_code, journal_lib
        FROM ecritures
        WHERE exercice = ? AND SUBSTR(compte_num, 1, 1) = '6'
        ORDER BY journal_code
    ");
    $jStmt->execute([$exercice]);
    $journals = $jStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get available account prefixes for filter
    $aStmt = $db->prepare("
        SELECT DISTINCT SUBSTR(compte_num, 1, 2) as prefix, 
               MIN(compte_lib) as label,
               COUNT(*) as nb
        FROM ecritures
        WHERE exercice = ? AND SUBSTR(compte_num, 1, 1) = '6'
        GROUP BY SUBSTR(compte_num, 1, 2)
        ORDER BY prefix
    ");
    $aStmt->execute([$exercice]);
    $accountPrefixes = $aStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Totals for current filter
    $totStmt = $db->prepare("
        SELECT 
            SUM(CAST(debit AS REAL)) as total_debit,
            SUM(CAST(credit AS REAL)) as total_credit,
            COUNT(*) as nb_ecritures
        FROM ecritures $where
    ");
    $totStmt->execute($params);
    $totals = $totStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'exercice' => $exercice,
            'lines' => $formatted,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
            ],
            'totals' => [
                'debit' => round((float)$totals['total_debit'], 2),
                'credit' => round((float)$totals['total_credit'], 2),
                'solde' => round((float)$totals['total_debit'] - (float)$totals['total_credit'], 2),
                'nb_ecritures' => (int)$totals['nb_ecritures'],
            ],
            'filters' => [
                'journals' => $journals,
                'account_prefixes' => $accountPrefixes,
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    if (http_response_code() === 200) http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
