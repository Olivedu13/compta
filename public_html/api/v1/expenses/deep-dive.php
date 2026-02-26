<?php
/**
 * GET /api/v1/expenses/deep-dive.php
 * Analyse Granulaire des Charges (Classe 6) — Big Four Standard
 * Segmentation fournisseur, détection doublons, alertes variations
 * Self-contained
 * 
 * Params:
 * - exercice (required): Année comptable
 */

header('Content-Type: application/json; charset=utf-8');

try {
    $exercice = isset($_GET['exercice']) ? (int)$_GET['exercice'] : null;
    
    if (!$exercice || $exercice < 1900 || $exercice > 2100) {
        http_response_code(400);
        throw new Exception('Parameter exercice is required and must be a valid year');
    }
    
    // Find project root (works locally with public_html/ and on Ionos flat webroot)
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
    
    $labels_categories = [
        '60' => 'Achats (matières, marchandises)',
        '61' => 'Services extérieurs',
        '62' => 'Autres services extérieurs',
        '63' => 'Impôts, taxes et versements',
        '64' => 'Charges de personnel',
        '65' => 'Autres charges de gestion',
        '66' => 'Charges financières',
        '67' => 'Charges exceptionnelles',
        '68' => 'Dotations amortissements/provisions',
    ];
    
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
    
    if ($useEcritures) {
        // =============================================
        // MODE ECRITURES: données détaillées depuis la table ecritures
        // =============================================
        $result = buildFromEcritures($db, $exercice, $labels_categories);
    } else {
        // =============================================
        // MODE RAPPORT: données agrégées depuis la table reports (import frontend)
        // =============================================
        $result = buildFromReport($db, $exercice, $labels_categories);
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $result,
        'source' => $useEcritures ? 'ecritures' : 'report'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// =============================================
// FUNCTION: Build from ecritures table (full SQL)
// =============================================
function buildFromEcritures($db, $exercice, $labels_categories) {
    // 1. CHARGES PAR CATÉGORIE
    $stmt = $db->prepare("
        SELECT 
            SUBSTR(compte_num, 1, 2) as categorie,
            SUM(CAST(debit AS REAL)) as total_debit,
            SUM(CAST(credit AS REAL)) as total_credit,
            COUNT(*) as nb_ecritures
        FROM ecritures
        WHERE exercice = ? AND SUBSTR(compte_num, 1, 1) = '6'
        GROUP BY SUBSTR(compte_num, 1, 2)
        ORDER BY SUM(CAST(debit AS REAL) - CAST(credit AS REAL)) DESC
    ");
    $stmt->execute([$exercice]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $par_categorie = [];
    $total_charges = 0;
    foreach ($categories as $c) {
        $montant = (float)$c['total_debit'] - (float)$c['total_credit'];
        $total_charges += $montant;
        $par_categorie[] = [
            'code' => $c['categorie'],
            'label' => $labels_categories[$c['categorie']] ?? "Catégorie {$c['categorie']}",
            'montant' => round($montant, 2),
            'nb_ecritures' => (int)$c['nb_ecritures'],
        ];
    }
    foreach ($par_categorie as &$cat) {
        $cat['pct_total'] = $total_charges > 0 ? round(($cat['montant'] / $total_charges) * 100, 1) : 0;
    }
    unset($cat);
    
    // 2. CHARGES PAR COMPTE
    $stmt = $db->prepare("
        SELECT compte_num, compte_lib,
            SUM(CAST(debit AS REAL) - CAST(credit AS REAL)) as montant,
            COUNT(*) as nb_ecritures,
            MIN(ecriture_date) as premiere_ecriture,
            MAX(ecriture_date) as derniere_ecriture
        FROM ecritures
        WHERE exercice = ? AND SUBSTR(compte_num, 1, 1) = '6'
        GROUP BY compte_num, compte_lib
        ORDER BY SUM(CAST(debit AS REAL) - CAST(credit AS REAL)) DESC LIMIT 30
    ");
    $stmt->execute([$exercice]);
    $par_compte = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $cd) {
        $par_compte[] = [
            'compte_num' => $cd['compte_num'],
            'compte_lib' => $cd['compte_lib'],
            'montant' => round((float)$cd['montant'], 2),
            'nb_ecritures' => (int)$cd['nb_ecritures'],
            'pct_total' => $total_charges > 0 ? round(((float)$cd['montant'] / $total_charges) * 100, 1) : 0,
            'premiere' => $cd['premiere_ecriture'],
            'derniere' => $cd['derniere_ecriture'],
        ];
    }
    
    // 3. FOURNISSEURS
    $stmt = $db->prepare("
        SELECT 
            COALESCE(numero_tiers, 'SANS_TIERS') as fournisseur_num,
            COALESCE(lib_tiers, 'Sans tiers identifié') as fournisseur_nom,
            SUM(CAST(debit AS REAL) - CAST(credit AS REAL)) as montant,
            COUNT(*) as nb_factures,
            COUNT(DISTINCT piece_ref) as nb_pieces,
            MIN(ecriture_date) as premiere_facture,
            MAX(ecriture_date) as derniere_facture,
            GROUP_CONCAT(DISTINCT SUBSTR(compte_num, 1, 2)) as comptes_utilises
        FROM ecritures
        WHERE exercice = ? AND SUBSTR(compte_num, 1, 1) = '6'
        GROUP BY COALESCE(numero_tiers, 'SANS_TIERS'), COALESCE(lib_tiers, 'Sans tiers identifié')
        HAVING SUM(CAST(debit AS REAL) - CAST(credit AS REAL)) > 0
        ORDER BY SUM(CAST(debit AS REAL) - CAST(credit AS REAL)) DESC LIMIT 50
    ");
    $stmt->execute([$exercice]);
    $par_fournisseur = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) {
        $par_fournisseur[] = [
            'numero' => $f['fournisseur_num'], 'nom' => $f['fournisseur_nom'],
            'montant' => round((float)$f['montant'], 2),
            'pct_total' => $total_charges > 0 ? round(((float)$f['montant'] / $total_charges) * 100, 1) : 0,
            'nb_factures' => (int)$f['nb_factures'], 'nb_pieces' => (int)$f['nb_pieces'],
            'premiere_facture' => $f['premiere_facture'], 'derniere_facture' => $f['derniere_facture'],
            'comptes' => $f['comptes_utilises'],
        ];
    }
    
    // 4. ÉVOLUTION MENSUELLE
    $stmt = $db->prepare("
        SELECT SUBSTR(ecriture_date, 1, 7) as mois, SUBSTR(compte_num, 1, 2) as categorie,
            SUM(CAST(debit AS REAL) - CAST(credit AS REAL)) as montant, COUNT(*) as nb_ecritures
        FROM ecritures
        WHERE exercice = ? AND SUBSTR(compte_num, 1, 1) = '6'
        GROUP BY SUBSTR(ecriture_date, 1, 7), SUBSTR(compte_num, 1, 2)
        ORDER BY mois, categorie
    ");
    $stmt->execute([$exercice]);
    $evolution_mensuelle = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $e) {
        $mois = $e['mois'];
        if (!isset($evolution_mensuelle[$mois])) {
            $evolution_mensuelle[$mois] = ['mois' => $mois, 'total' => 0, 'categories' => []];
        }
        $montant = (float)$e['montant'];
        $evolution_mensuelle[$mois]['total'] += $montant;
        $evolution_mensuelle[$mois]['categories'][$e['categorie']] = round($montant, 2);
    }
    $evolution_mensuelle = array_values($evolution_mensuelle);
    
    // 5. VARIATIONS ATYPIQUES
    $variations_atypiques = detectVariations($evolution_mensuelle, $labels_categories);
    
    // 6. DOUBLONS
    $stmt = $db->prepare("
        SELECT piece_ref, COALESCE(lib_tiers, 'N/A') as fournisseur,
            CAST(debit AS REAL) as montant, ecriture_date, compte_num
        FROM ecritures
        WHERE exercice = ? AND SUBSTR(compte_num, 1, 1) = '6'
          AND piece_ref IS NOT NULL AND piece_ref != '' AND CAST(debit AS REAL) > 0
        ORDER BY piece_ref, ecriture_date
    ");
    $stmt->execute([$exercice]);
    $doublons_par_piece = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) {
        $key = $f['piece_ref'] . '|' . $f['montant'];
        $doublons_par_piece[$key][] = $f;
    }
    $doublons = [];
    foreach ($doublons_par_piece as $entries) {
        if (count($entries) >= 2) {
            $doublons[] = [
                'piece_ref' => $entries[0]['piece_ref'], 'fournisseur' => $entries[0]['fournisseur'],
                'montant' => (float)$entries[0]['montant'], 'nb_occurrences' => count($entries),
                'dates' => array_map(function($e) { return $e['ecriture_date']; }, $entries),
                'risque' => count($entries) > 2 ? 'ÉLEVÉ' : 'MOYEN',
            ];
        }
    }
    usort($doublons, function($a, $b) { return $b['montant'] * $b['nb_occurrences'] <=> $a['montant'] * $a['nb_occurrences']; });
    $doublons = array_slice($doublons, 0, 20);
    
    // 7. FIXES / VARIABLES
    $charges_fixes_codes = ['63', '64', '66', '68'];
    $charges_variables_codes = ['60', '61', '62'];
    $montant_fixes = 0; $montant_variables = 0;
    foreach ($par_categorie as $cat) {
        if (in_array($cat['code'], $charges_fixes_codes)) $montant_fixes += $cat['montant'];
        elseif (in_array($cat['code'], $charges_variables_codes)) $montant_variables += $cat['montant'];
    }
    
    return [
        'exercice' => $exercice,
        'synthese' => [
            'total_charges' => round($total_charges, 2),
            'charges_fixes' => round($montant_fixes, 2), 'charges_variables' => round($montant_variables, 2),
            'pct_fixes' => $total_charges > 0 ? round(($montant_fixes / $total_charges) * 100, 1) : 0,
            'pct_variables' => $total_charges > 0 ? round(($montant_variables / $total_charges) * 100, 1) : 0,
            'nb_fournisseurs' => count($par_fournisseur),
            'nb_doublons_detectes' => count($doublons),
            'nb_variations_atypiques' => count($variations_atypiques),
        ],
        'par_categorie' => $par_categorie, 'par_compte' => $par_compte,
        'par_fournisseur' => $par_fournisseur, 'evolution_mensuelle' => $evolution_mensuelle,
        'variations_atypiques' => $variations_atypiques, 'doublons_factures' => $doublons,
        'structure' => ['fixes' => round($montant_fixes, 2), 'variables' => round($montant_variables, 2)],
    ];
}

// =============================================
// FUNCTION: Build from reports JSON (fallback)
// =============================================
function buildFromReport($db, $exercice, $labels_categories) {
    $stmt = $db->prepare("SELECT data_json FROM reports WHERE year = ?");
    $stmt->execute([$exercice]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        throw new Exception("Aucune donnée trouvée pour l'exercice $exercice");
    }
    
    $report = json_decode($row['data_json'], true);
    if (!$report) {
        throw new Exception("Données corrompues pour l'exercice $exercice");
    }
    
    $details = $report['details'] ?? [];
    
    // Map report sections to compte categories
    $sectionToCategories = [
        'purchases' => '60',
        'external' => ['61', '62'],
        'taxes' => '63',
        'personnel' => '64',
        'management' => ['65', '68'],
        'debt' => '66',
    ];
    
    // Extract all expense accounts from the details
    $allAccounts = [];
    $expenseSections = ['purchases', 'external', 'personnel', 'debt', 'taxes', 'management'];
    foreach ($expenseSections as $section) {
        if (isset($details[$section])) {
            foreach ($details[$section] as $item) {
                $code2 = substr($item['code'], 0, 2);
                if ($code2[0] === '6') { // Only class 6
                    $allAccounts[] = [
                        'code' => $item['code'],
                        'libelle' => $item['libelle'],
                        'solde' => (float)$item['solde'],
                        'categorie' => $code2,
                    ];
                }
            }
        }
    }
    
    // 1. PAR CATÉGORIE: Group by 2-digit prefix
    $catTotals = [];
    foreach ($allAccounts as $acc) {
        $cat = $acc['categorie'];
        if (!isset($catTotals[$cat])) {
            $catTotals[$cat] = ['montant' => 0, 'nb_comptes' => 0];
        }
        $catTotals[$cat]['montant'] += $acc['solde'];
        $catTotals[$cat]['nb_comptes']++;
    }
    
    $total_charges = 0;
    $par_categorie = [];
    arsort($catTotals); // Sort by amount
    foreach ($catTotals as $code => $data) {
        $total_charges += $data['montant'];
        $par_categorie[] = [
            'code' => $code,
            'label' => $labels_categories[$code] ?? "Catégorie $code",
            'montant' => round($data['montant'], 2),
            'nb_ecritures' => $data['nb_comptes'], // nb comptes as proxy
        ];
    }
    // Sort by montant desc
    usort($par_categorie, function($a, $b) { return $b['montant'] <=> $a['montant']; });
    foreach ($par_categorie as &$cat) {
        $cat['pct_total'] = $total_charges > 0 ? round(($cat['montant'] / $total_charges) * 100, 1) : 0;
    }
    unset($cat);
    
    // 2. PAR COMPTE: Sort accounts by amount
    usort($allAccounts, function($a, $b) { return $b['solde'] <=> $a['solde']; });
    $par_compte = [];
    foreach (array_slice($allAccounts, 0, 30) as $acc) {
        $par_compte[] = [
            'compte_num' => $acc['code'],
            'compte_lib' => $acc['libelle'],
            'montant' => round($acc['solde'], 2),
            'nb_ecritures' => 0, // Not available from report
            'pct_total' => $total_charges > 0 ? round(($acc['solde'] / $total_charges) * 100, 1) : 0,
            'premiere' => null, 'derniere' => null,
        ];
    }
    
    // 3. FOURNISSEURS: from topSuppliers if available
    $par_fournisseur = [];
    if (!empty($report['topSuppliers'])) {
        foreach ($report['topSuppliers'] as $s) {
            $par_fournisseur[] = [
                'numero' => $s['code'] ?? 'N/A', 'nom' => $s['label'] ?? $s['libelle'] ?? 'N/A',
                'montant' => round((float)($s['value'] ?? $s['solde'] ?? 0), 2),
                'pct_total' => 0, 'nb_factures' => 0, 'nb_pieces' => 0,
                'premiere_facture' => null, 'derniere_facture' => null, 'comptes' => '',
            ];
        }
    }
    
    // 4. ÉVOLUTION MENSUELLE: from monthlyBreakdown
    $evolution_mensuelle = [];
    $monthly = $report['monthlyBreakdown'] ?? [];
    foreach ($monthly as $mois_num => $data) {
        $mois_str = sprintf('%04d-%02d', $exercice, (int)$mois_num);
        $expenses = abs((float)($data['expenses'] ?? 0));
        $evolution_mensuelle[] = [
            'mois' => $mois_str,
            'total' => round($expenses, 2),
            'categories' => [], // No category breakdown in monthly data
        ];
    }
    // Sort by month
    usort($evolution_mensuelle, function($a, $b) { return strcmp($a['mois'], $b['mois']); });
    
    // 5. VARIATIONS ATYPIQUES
    $variations_atypiques = detectVariations($evolution_mensuelle, $labels_categories);
    
    // 6. FIXES / VARIABLES
    $charges_fixes_codes = ['63', '64', '66', '68'];
    $charges_variables_codes = ['60', '61', '62'];
    $montant_fixes = 0; $montant_variables = 0;
    foreach ($par_categorie as $cat) {
        if (in_array($cat['code'], $charges_fixes_codes)) $montant_fixes += $cat['montant'];
        elseif (in_array($cat['code'], $charges_variables_codes)) $montant_variables += $cat['montant'];
    }
    
    return [
        'exercice' => $exercice,
        'synthese' => [
            'total_charges' => round($total_charges, 2),
            'charges_fixes' => round($montant_fixes, 2), 'charges_variables' => round($montant_variables, 2),
            'pct_fixes' => $total_charges > 0 ? round(($montant_fixes / $total_charges) * 100, 1) : 0,
            'pct_variables' => $total_charges > 0 ? round(($montant_variables / $total_charges) * 100, 1) : 0,
            'nb_fournisseurs' => count($par_fournisseur),
            'nb_doublons_detectes' => 0,
            'nb_variations_atypiques' => count($variations_atypiques),
        ],
        'par_categorie' => $par_categorie, 'par_compte' => $par_compte,
        'par_fournisseur' => $par_fournisseur, 'evolution_mensuelle' => $evolution_mensuelle,
        'variations_atypiques' => $variations_atypiques, 'doublons_factures' => [],
        'structure' => ['fixes' => round($montant_fixes, 2), 'variables' => round($montant_variables, 2)],
        'note' => 'Données issues du rapport agrégé. Import FEC détaillé non disponible pour cet exercice.',
    ];
}

// =============================================
// FUNCTION: Detect month-over-month variations
// =============================================
function detectVariations($evolution_mensuelle, $labels_categories) {
    $variations = [];
    $mois_precedent = null;
    foreach ($evolution_mensuelle as $m) {
        if ($mois_precedent !== null) {
            $diff = $m['total'] - $mois_precedent['total'];
            $pct = $mois_precedent['total'] > 0 ? round(($diff / $mois_precedent['total']) * 100, 1) : 0;
            if (abs($pct) > 50 && abs($diff) > 500) {
                $variations[] = [
                    'mois' => $m['mois'], 'mois_precedent' => $mois_precedent['mois'],
                    'montant_actuel' => round($m['total'], 2), 'montant_precedent' => round($mois_precedent['total'], 2),
                    'variation_euros' => round($diff, 2), 'variation_pct' => $pct,
                    'type' => $diff > 0 ? 'HAUSSE' : 'BAISSE',
                    'severity' => abs($pct) > 100 ? 'critical' : 'warning',
                ];
            }
            foreach ($m['categories'] ?? [] as $cat => $montant) {
                $montant_prec = $mois_precedent['categories'][$cat] ?? 0;
                if ($montant_prec > 0) {
                    $diff_cat = $montant - $montant_prec;
                    $pct_cat = round(($diff_cat / $montant_prec) * 100, 1);
                    if (abs($pct_cat) > 80 && abs($diff_cat) > 300) {
                        $variations[] = [
                            'mois' => $m['mois'], 'categorie' => $labels_categories[$cat] ?? "Cat. $cat",
                            'montant_actuel' => round($montant, 2), 'montant_precedent' => round($montant_prec, 2),
                            'variation_euros' => round($diff_cat, 2), 'variation_pct' => $pct_cat,
                            'type' => $diff_cat > 0 ? 'HAUSSE' : 'BAISSE',
                            'severity' => abs($pct_cat) > 150 ? 'critical' : 'warning',
                        ];
                    }
                }
            }
        }
        $mois_precedent = $m;
    }
    return $variations;
}
