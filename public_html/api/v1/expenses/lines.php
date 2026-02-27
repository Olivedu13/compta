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
    
    // Check if we have meaningful ecritures for this exercice
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM ecritures WHERE exercice = ? AND SUBSTR(compte_num, 1, 1) = '6'");
    $checkStmt->execute([$exercice]);
    $ecrituresCount = (int)$checkStmt->fetchColumn();
    
    // Check if report exists with richer data
    $hasReport = false;
    try {
        $rCheck = $db->prepare("SELECT COUNT(*) FROM reports WHERE year = ?");
        $rCheck->execute([$exercice]);
        $hasReport = (int)$rCheck->fetchColumn() > 0;
    } catch (Exception $e) {}
    
    // Use ecritures only if we have a meaningful number (>= 10), otherwise fallback to report
    $useEcritures = $ecrituresCount >= 10 || ($ecrituresCount > 0 && !$hasReport);
    
    if (!$useEcritures) {
        // =============================================
        // MODE RAPPORT: reconstruct lines from report JSON
        // =============================================
        $rStmt = $db->prepare("SELECT data_json FROM reports WHERE year = ?");
        $rStmt->execute([$exercice]);
        $rRow = $rStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rRow) {
            echo json_encode(['success' => true, 'data' => [
                'exercice' => $exercice, 'lines' => [],
                'pagination' => ['page' => 1, 'per_page' => $perPage, 'total' => 0, 'total_pages' => 0],
                'totals' => ['debit' => 0, 'credit' => 0, 'solde' => 0, 'nb_ecritures' => 0],
                'filters' => ['journals' => [], 'account_prefixes' => []],
                'source' => 'none'
            ]], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $report = json_decode($rRow['data_json'], true);
        $details = $report['details'] ?? [];
        
        // Extract all class 6 accounts from report details
        $allLines = [];
        $expenseSections = ['purchases', 'external', 'personnel', 'debt', 'taxes', 'management'];
        foreach ($expenseSections as $section) {
            foreach ($details[$section] ?? [] as $item) {
                $code = $item['code'] ?? '';
                if (substr($code, 0, 1) === '6') {
                    $solde = (float)($item['solde'] ?? 0);
                    $allLines[] = [
                        'date' => $exercice . '-12-31',
                        'journal' => 'FEC', 'journal_lib' => 'Import FEC',
                        'num' => '', 'compte' => $code,
                        'compte_lib' => $item['libelle'] ?? '',
                        'aux_num' => '', 'aux_lib' => '',
                        'piece' => '', 'piece_date' => '',
                        'libelle' => $item['libelle'] ?? '',
                        'debit' => $solde > 0 ? round($solde, 2) : 0,
                        'credit' => $solde < 0 ? round(abs($solde), 2) : 0,
                        'montant' => round($solde, 2),
                        'lettrage' => '',
                    ];
                }
            }
        }
        
        // Apply filters
        if ($compteFilter) {
            $comptes = array_filter(array_map('trim', explode(',', $compteFilter)));
            $allLines = array_values(array_filter($allLines, function($l) use ($comptes) {
                foreach ($comptes as $c) { if (strpos($l['compte'], $c) === 0) return true; }
                return false;
            }));
        }
        if ($searchFilter) {
            $search = strtolower($searchFilter);
            $allLines = array_values(array_filter($allLines, function($l) use ($search) {
                return strpos(strtolower($l['libelle']), $search) !== false
                    || strpos(strtolower($l['compte_lib']), $search) !== false
                    || strpos(strtolower($l['compte']), $search) !== false;
            }));
        }
        
        // Sort
        usort($allLines, function($a, $b) use ($sortField, $sortOrder) {
            $map = ['date' => 'date', 'compte' => 'compte', 'montant' => 'montant', 'libelle' => 'libelle',
                    'journal' => 'journal', 'compte_lib' => 'compte_lib', 'piece' => 'piece',
                    'debit' => 'debit', 'credit' => 'credit'];
            $key = $map[$sortField] ?? 'montant';
            if (in_array($key, ['montant', 'debit', 'credit'])) {
                $cmp = ($a[$key] <=> $b[$key]);
            } else {
                $cmp = strcmp($a[$key] ?? '', $b[$key] ?? '');
            }
            return $sortOrder === 'ASC' ? $cmp : -$cmp;
        });
        
        $total = count($allLines);
        $totalDebit = array_sum(array_column($allLines, 'debit'));
        $totalCredit = array_sum(array_column($allLines, 'credit'));
        
        // Paginate
        $offset = ($page - 1) * $perPage;
        $pagedLines = array_slice($allLines, $offset, $perPage);
        
        // Account prefixes
        $prefixes = [];
        foreach ($allLines as $l) {
            $p = substr($l['compte'], 0, 2);
            if (!isset($prefixes[$p])) $prefixes[$p] = ['prefix' => $p, 'label' => $l['compte_lib'], 'nb' => 0];
            $prefixes[$p]['nb']++;
        }
        ksort($prefixes);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'exercice' => $exercice, 'lines' => $pagedLines,
                'pagination' => [
                    'page' => $page, 'per_page' => $perPage,
                    'total' => $total, 'total_pages' => (int)ceil($total / $perPage),
                ],
                'totals' => [
                    'debit' => round($totalDebit, 2), 'credit' => round($totalCredit, 2),
                    'solde' => round($totalDebit - $totalCredit, 2), 'nb_ecritures' => $total,
                ],
                'filters' => ['journals' => [], 'account_prefixes' => array_values($prefixes)],
                'source' => 'report',
                'note' => 'Données agrégées par compte — le détail ligne par ligne n\'est pas disponible pour cet exercice.'
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // =============================================
    // MODE ECRITURES: full detail from ecritures table
    // =============================================
    // any_class=1: bypass class 6 filter (for counterpart accounts like 421)
    $anyClass = !empty($_GET['any_class']);
    if ($anyClass) {
        $where = "WHERE exercice = ?";
    } else {
        $where = "WHERE exercice = ? AND SUBSTR(compte_num, 1, 1) = '6'";
    }
    $params = [$exercice];
    
    if ($compteFilter) {
        // Support comma-separated account prefixes (e.g. 627,661,665)
        $comptes = array_filter(array_map('trim', explode(',', $compteFilter)));
        if (count($comptes) === 1) {
            $where .= " AND compte_num LIKE ?";
            $params[] = $comptes[0] . '%';
        } elseif (count($comptes) > 1) {
            $clauses = [];
            foreach ($comptes as $c) { $clauses[] = "compte_num LIKE ?"; $params[] = $c . '%'; }
            $where .= " AND (" . implode(' OR ', $clauses) . ")";
        }
    }
    
    if ($journalFilter) {
        $where .= " AND journal_code = ?";
        $params[] = $journalFilter;
    }
    
    if ($searchFilter) {
        $where .= " AND (libelle_ecriture LIKE ? OR compte_lib LIKE ? OR piece_ref LIKE ? OR lib_tiers LIKE ?)";
        $params[] = '%' . $searchFilter . '%';
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
        'libelle' => 'libelle_ecriture',
        'compte_lib' => 'compte_lib',
        'piece' => 'piece_ref',
        'debit' => 'CAST(debit AS REAL)',
        'credit' => 'CAST(credit AS REAL)'
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
        $rawDate = $l['ecriture_date'] ?? '';
        $rawPieceDate = $l['date_piece'] ?? '';
        $formatted[] = [
            'date' => preg_replace('/^(\d{4})(\d{2})(\d{2})$/', '$1-$2-$3', $rawDate),
            'journal' => $l['journal_code'] ?? '',
            'journal_lib' => $l['journal_lib'] ?? '',
            'num' => $l['ecriture_num'] ?? '',
            'compte' => $l['compte_num'] ?? '',
            'compte_lib' => $l['compte_lib'] ?? '',
            'aux_num' => $l['numero_tiers'] ?? '',
            'aux_lib' => $l['lib_tiers'] ?? '',
            'piece' => $l['piece_ref'] ?? '',
            'piece_date' => preg_replace('/^(\d{4})(\d{2})(\d{2})$/', '$1-$2-$3', $rawPieceDate),
            'libelle' => $l['libelle_ecriture'] ?? '',
            'debit' => $debit,
            'credit' => $credit,
            'montant' => round($debit - $credit, 2),
            'lettrage' => $l['lettrage_flag'] ?? '',
        ];
    }
    
    // Get available journals for filter
    $classFilter = $anyClass ? '' : "AND SUBSTR(compte_num, 1, 1) = '6'";
    $jStmt = $db->prepare("
        SELECT DISTINCT journal_code, journal_lib
        FROM ecritures
        WHERE exercice = ? $classFilter
        ORDER BY journal_code
    ");
    $jStmt->execute([$exercice]);
    $journals = $jStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get available account prefixes for filter
    if ($compteFilter && strpos($compteFilter, ',') !== false) {
        // Multi-prefix: show full account numbers (8 digits) for sub-account filter
        $comptes = array_filter(array_map('trim', explode(',', $compteFilter)));
        $likeClauses = [];
        foreach ($comptes as $c) { $likeClauses[] = "compte_num LIKE '" . SQLite3::escapeString($c) . "%'"; }
        $prefixFilter = "AND (" . implode(' OR ', $likeClauses) . ")";
        $prefixLen = 8;
    } elseif ($compteFilter) {
        $prefixLen = max(strlen($compteFilter) + 1, 3);
        $prefixFilter = $anyClass ? "AND compte_num LIKE '" . substr($compteFilter, 0, 3) . "%'" : "AND SUBSTR(compte_num, 1, 1) = '6'";
    } else {
        $prefixLen = 2;
        $prefixFilter = $anyClass ? '' : "AND SUBSTR(compte_num, 1, 1) = '6'";
    }
    $aStmt = $db->prepare("
        SELECT DISTINCT SUBSTR(compte_num, 1, $prefixLen) as prefix, 
               MIN(compte_lib) as label,
               COUNT(*) as nb
        FROM ecritures
        WHERE exercice = ? $prefixFilter
        GROUP BY SUBSTR(compte_num, 1, $prefixLen)
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
