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
    
    // =============================================
    // 1. CHARGES PAR CATÉGORIE (sous-classes 60-68)
    // =============================================
    $stmt = $db->prepare("
        SELECT 
            SUBSTR(compte_num, 1, 2) as categorie,
            compte_lib,
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
    
    // Ajouter % du total
    foreach ($par_categorie as &$cat) {
        $cat['pct_total'] = $total_charges > 0 ? round(($cat['montant'] / $total_charges) * 100, 1) : 0;
    }
    unset($cat);
    
    // =============================================
    // 2. CHARGES PAR COMPTE DÉTAILLÉ (Top 30)
    // =============================================
    $stmt = $db->prepare("
        SELECT 
            compte_num,
            compte_lib,
            SUM(CAST(debit AS REAL) - CAST(credit AS REAL)) as montant,
            COUNT(*) as nb_ecritures,
            MIN(ecriture_date) as premiere_ecriture,
            MAX(ecriture_date) as derniere_ecriture
        FROM ecritures
        WHERE exercice = ? AND SUBSTR(compte_num, 1, 1) = '6'
        GROUP BY compte_num, compte_lib
        ORDER BY SUM(CAST(debit AS REAL) - CAST(credit AS REAL)) DESC
        LIMIT 30
    ");
    $stmt->execute([$exercice]);
    $comptes_detail = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $par_compte = [];
    foreach ($comptes_detail as $cd) {
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
    
    // =============================================
    // 3. CHARGES PAR FOURNISSEUR (segmentation)
    // =============================================
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
        ORDER BY SUM(CAST(debit AS REAL) - CAST(credit AS REAL)) DESC
        LIMIT 50
    ");
    $stmt->execute([$exercice]);
    $fournisseurs_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $par_fournisseur = [];
    foreach ($fournisseurs_raw as $f) {
        $par_fournisseur[] = [
            'numero' => $f['fournisseur_num'],
            'nom' => $f['fournisseur_nom'],
            'montant' => round((float)$f['montant'], 2),
            'pct_total' => $total_charges > 0 ? round(((float)$f['montant'] / $total_charges) * 100, 1) : 0,
            'nb_factures' => (int)$f['nb_factures'],
            'nb_pieces' => (int)$f['nb_pieces'],
            'premiere_facture' => $f['premiere_facture'],
            'derniere_facture' => $f['derniere_facture'],
            'comptes' => $f['comptes_utilises'],
        ];
    }
    
    // =============================================
    // 4. ÉVOLUTION MENSUELLE DES CHARGES
    // =============================================
    $stmt = $db->prepare("
        SELECT 
            SUBSTR(ecriture_date, 1, 7) as mois,
            SUBSTR(compte_num, 1, 2) as categorie,
            SUM(CAST(debit AS REAL) - CAST(credit AS REAL)) as montant,
            COUNT(*) as nb_ecritures
        FROM ecritures
        WHERE exercice = ? AND SUBSTR(compte_num, 1, 1) = '6'
        GROUP BY SUBSTR(ecriture_date, 1, 7), SUBSTR(compte_num, 1, 2)
        ORDER BY mois, categorie
    ");
    $stmt->execute([$exercice]);
    $evolution_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Regrouper par mois
    $evolution_mensuelle = [];
    foreach ($evolution_raw as $e) {
        $mois = $e['mois'];
        if (!isset($evolution_mensuelle[$mois])) {
            $evolution_mensuelle[$mois] = ['mois' => $mois, 'total' => 0, 'categories' => []];
        }
        $montant = (float)$e['montant'];
        $evolution_mensuelle[$mois]['total'] += $montant;
        $evolution_mensuelle[$mois]['categories'][$e['categorie']] = round($montant, 2);
    }
    $evolution_mensuelle = array_values($evolution_mensuelle);
    
    // =============================================
    // 5. DÉTECTION DES VARIATIONS ATYPIQUES (mois/mois)
    // =============================================
    $variations_atypiques = [];
    $mois_precedent = null;
    
    foreach ($evolution_mensuelle as $idx => $m) {
        if ($mois_precedent !== null) {
            $diff = $m['total'] - $mois_precedent['total'];
            $pct = $mois_precedent['total'] > 0 ? round(($diff / $mois_precedent['total']) * 100, 1) : 0;
            
            // Variation > 50% = atypique
            if (abs($pct) > 50 && abs($diff) > 500) {
                $variations_atypiques[] = [
                    'mois' => $m['mois'],
                    'mois_precedent' => $mois_precedent['mois'],
                    'montant_actuel' => round($m['total'], 2),
                    'montant_precedent' => round($mois_precedent['total'], 2),
                    'variation_euros' => round($diff, 2),
                    'variation_pct' => $pct,
                    'type' => $diff > 0 ? 'HAUSSE' : 'BAISSE',
                    'severity' => abs($pct) > 100 ? 'critical' : 'warning',
                ];
            }
            
            // Vérifier aussi par catégorie
            foreach ($m['categories'] as $cat => $montant) {
                $montant_prec = $mois_precedent['categories'][$cat] ?? 0;
                if ($montant_prec > 0) {
                    $diff_cat = $montant - $montant_prec;
                    $pct_cat = round(($diff_cat / $montant_prec) * 100, 1);
                    
                    if (abs($pct_cat) > 80 && abs($diff_cat) > 300) {
                        $variations_atypiques[] = [
                            'mois' => $m['mois'],
                            'categorie' => $labels_categories[$cat] ?? "Cat. $cat",
                            'montant_actuel' => round($montant, 2),
                            'montant_precedent' => round($montant_prec, 2),
                            'variation_euros' => round($diff_cat, 2),
                            'variation_pct' => $pct_cat,
                            'type' => $diff_cat > 0 ? 'HAUSSE' : 'BAISSE',
                            'severity' => abs($pct_cat) > 150 ? 'critical' : 'warning',
                        ];
                    }
                }
            }
        }
        $mois_precedent = $m;
    }
    
    // =============================================
    // 6. DÉTECTION DE DOUBLONS DE FACTURES
    // =============================================
    $stmt = $db->prepare("
        SELECT 
            piece_ref,
            COALESCE(lib_tiers, 'N/A') as fournisseur,
            CAST(debit AS REAL) as montant,
            ecriture_date,
            compte_num,
            COUNT(*) OVER (PARTITION BY piece_ref, CAST(debit AS REAL)) as nb_similaires
        FROM ecritures
        WHERE exercice = ? 
          AND SUBSTR(compte_num, 1, 1) = '6'
          AND piece_ref IS NOT NULL AND piece_ref != ''
          AND CAST(debit AS REAL) > 0
        ORDER BY piece_ref, ecriture_date
    ");
    $stmt->execute([$exercice]);
    $factures_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Détecter les doublons : même pièce + même montant
    $doublons_par_piece = [];
    foreach ($factures_raw as $f) {
        $key = $f['piece_ref'] . '|' . $f['montant'];
        if (!isset($doublons_par_piece[$key])) {
            $doublons_par_piece[$key] = [];
        }
        $doublons_par_piece[$key][] = $f;
    }
    
    $doublons = [];
    foreach ($doublons_par_piece as $key => $entries) {
        if (count($entries) >= 2) {
            $doublons[] = [
                'piece_ref' => $entries[0]['piece_ref'],
                'fournisseur' => $entries[0]['fournisseur'],
                'montant' => (float)$entries[0]['montant'],
                'nb_occurrences' => count($entries),
                'dates' => array_map(function($e) { return $e['ecriture_date']; }, $entries),
                'risque' => count($entries) > 2 ? 'ÉLEVÉ' : 'MOYEN',
            ];
        }
    }
    
    // Trier par montant décroissant
    usort($doublons, function($a, $b) {
        return $b['montant'] * $b['nb_occurrences'] <=> $a['montant'] * $a['nb_occurrences'];
    });
    $doublons = array_slice($doublons, 0, 20);
    
    // =============================================
    // 7. CHARGES FIXES vs VARIABLES (estimation)
    // =============================================
    $charges_fixes_codes = ['63', '64', '66', '68']; // Impôts, personnel, financières, dotations
    $charges_variables_codes = ['60', '61', '62']; // Achats, services
    
    $montant_fixes = 0;
    $montant_variables = 0;
    foreach ($par_categorie as $cat) {
        if (in_array($cat['code'], $charges_fixes_codes)) {
            $montant_fixes += $cat['montant'];
        } elseif (in_array($cat['code'], $charges_variables_codes)) {
            $montant_variables += $cat['montant'];
        }
    }
    
    // =============================================
    // COMPILATION RÉSULTAT
    // =============================================
    
    $result = [
        'exercice' => $exercice,
        'synthese' => [
            'total_charges' => round($total_charges, 2),
            'charges_fixes' => round($montant_fixes, 2),
            'charges_variables' => round($montant_variables, 2),
            'pct_fixes' => $total_charges > 0 ? round(($montant_fixes / $total_charges) * 100, 1) : 0,
            'pct_variables' => $total_charges > 0 ? round(($montant_variables / $total_charges) * 100, 1) : 0,
            'nb_fournisseurs' => count($par_fournisseur),
            'nb_doublons_detectes' => count($doublons),
            'nb_variations_atypiques' => count($variations_atypiques),
        ],
        'par_categorie' => $par_categorie,
        'par_compte' => $par_compte,
        'par_fournisseur' => $par_fournisseur,
        'evolution_mensuelle' => $evolution_mensuelle,
        'variations_atypiques' => $variations_atypiques,
        'doublons_factures' => $doublons,
        'structure' => [
            'fixes' => round($montant_fixes, 2),
            'variables' => round($montant_variables, 2),
        ],
    ];
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $result
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
