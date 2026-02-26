<?php
/**
 * GET /api/v1/kpis/financial.php
 * KPIs Financiers Complets — Standard Big Four
 * BFR, CAF, Solvabilité, DSO/DPO, Seuil de Rentabilité, Ratios
 * Self-contained - No dependencies
 * 
 * Params:
 * - exercice (required): Année comptable (ex: 2024)
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
    // REQUÊTES DE BASE
    // =============================================
    
    // Soldes par racine 2 et 3 caractères
    $stmt = $db->prepare("
        SELECT 
            SUBSTR(compte_num, 1, 1) as classe,
            SUBSTR(compte_num, 1, 2) as racine2,
            SUBSTR(compte_num, 1, 3) as racine3,
            SUM(CAST(debit AS REAL)) as total_debit,
            SUM(CAST(credit AS REAL)) as total_credit
        FROM ecritures
        WHERE exercice = ?
        GROUP BY SUBSTR(compte_num, 1, 1), SUBSTR(compte_num, 1, 2), SUBSTR(compte_num, 1, 3)
    ");
    $stmt->execute([$exercice]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $soldes1 = []; $soldes2 = []; $soldes3 = [];
    $debits2 = []; $credits2 = [];
    $debits3 = []; $credits3 = [];
    
    foreach ($rows as $r) {
        $c1 = $r['classe']; $r2 = $r['racine2']; $r3 = $r['racine3'];
        $d = (float)$r['total_debit']; $c = (float)$r['total_credit'];
        $s = $d - $c;
        
        if (!isset($soldes1[$c1])) $soldes1[$c1] = 0;
        $soldes1[$c1] += $s;
        
        if (!isset($soldes2[$r2])) $soldes2[$r2] = 0;
        $soldes2[$r2] += $s;
        if (!isset($debits2[$r2])) $debits2[$r2] = 0;
        $debits2[$r2] += $d;
        if (!isset($credits2[$r2])) $credits2[$r2] = 0;
        $credits2[$r2] += $c;
        
        if (!isset($soldes3[$r3])) $soldes3[$r3] = 0;
        $soldes3[$r3] += $s;
        if (!isset($debits3[$r3])) $debits3[$r3] = 0;
        $debits3[$r3] += $d;
        if (!isset($credits3[$r3])) $credits3[$r3] = 0;
        $credits3[$r3] += $c;
    }
    
    // =============================================
    // BILAN SIMPLIFIÉ
    // =============================================
    
    // ACTIF IMMOBILISÉ (classe 2) - Amortissements (28/29)
    $immobilisations_brutes = ($soldes2['20'] ?? 0) + ($soldes2['21'] ?? 0) + ($soldes2['22'] ?? 0) + 
                               ($soldes2['23'] ?? 0) + ($soldes2['24'] ?? 0) + ($soldes2['25'] ?? 0) + 
                               ($soldes2['26'] ?? 0) + ($soldes2['27'] ?? 0);
    $amortissements = ($soldes2['28'] ?? 0) + ($soldes2['29'] ?? 0);
    $actif_immobilise = $immobilisations_brutes + $amortissements; // 28/29 sont créditeurs (négatifs)
    
    // STOCKS (classe 3)
    $stocks = ($soldes2['31'] ?? 0) + ($soldes2['32'] ?? 0) + ($soldes2['33'] ?? 0) + 
              ($soldes2['34'] ?? 0) + ($soldes2['35'] ?? 0) + ($soldes2['37'] ?? 0);
    $depreciation_stocks = ($soldes2['39'] ?? 0);
    $stocks_net = $stocks + $depreciation_stocks;
    
    // CRÉANCES CLIENTS (411)
    $creances_clients = $soldes3['411'] ?? 0;
    
    // AUTRES CRÉANCES (classes 40 hors 401, 44, 46)
    $autres_creances = ($soldes2['40'] ?? 0) - ($soldes3['401'] ?? 0) + ($soldes2['44'] ?? 0) + ($soldes2['46'] ?? 0);
    
    // TRÉSORERIE ACTIVE (classe 5)
    $tresorerie_active = ($soldes2['51'] ?? 0) + ($soldes2['52'] ?? 0) + ($soldes2['53'] ?? 0) + ($soldes2['54'] ?? 0);
    
    // ACTIF CIRCULANT
    $actif_circulant = $stocks_net + $creances_clients + $autres_creances + $tresorerie_active;
    
    // TOTAL ACTIF
    $total_actif = $actif_immobilise + $actif_circulant;
    
    // CAPITAUX PROPRES (classe 1 : 10+11+12+13+14+15)
    // Attention : les capitaux propres sont au crédit (solde négatif en comptabilité)
    $capitaux_propres = -(($soldes2['10'] ?? 0) + ($soldes2['11'] ?? 0) + ($soldes2['12'] ?? 0) + 
                          ($soldes2['13'] ?? 0) + ($soldes2['14'] ?? 0) + ($soldes2['15'] ?? 0));
    
    // DETTES FINANCIÈRES (16+17)
    $dettes_financieres = -(($soldes2['16'] ?? 0) + ($soldes2['17'] ?? 0));
    
    // DETTES FOURNISSEURS (401)
    $dettes_fournisseurs = -($soldes3['401'] ?? 0);
    
    // DETTES FISCALES ET SOCIALES (43+44)
    $dettes_fiscales = -(($soldes2['43'] ?? 0) + ($soldes2['44'] ?? 0));
    
    // PASSIF CIRCULANT
    $passif_circulant = $dettes_fournisseurs + $dettes_fiscales;
    
    // TRÉSORERIE PASSIVE (emprunts CT, 519)
    $tresorerie_passive = -($soldes3['519'] ?? 0);
    
    // =============================================
    // KPIs FINANCIERS
    // =============================================
    
    // --- CA et SIG de base ---
    $ca_net = -($soldes2['70'] ?? 0);
    $achats = ($soldes3['601'] ?? 0) + ($soldes3['602'] ?? 0) + ($soldes3['607'] ?? 0);
    $services_ext = ($soldes2['61'] ?? 0) + ($soldes2['62'] ?? 0);
    $charges_personnel = $soldes2['64'] ?? 0;
    $impots_taxes = $soldes2['63'] ?? 0;
    $dotations = ($soldes3['681'] ?? 0) + ($soldes3['686'] ?? 0) + ($soldes3['687'] ?? 0);
    $charges_financieres = $soldes2['66'] ?? 0;
    $resultat_net = -($soldes1['7'] ?? 0) - ($soldes1['6'] ?? 0);
    
    // --- 1. BFR (Besoin en Fonds de Roulement) ---
    // BFR = Actif Circulant (hors trésorerie) - Passif Circulant (hors trésorerie)
    $actif_circulant_ht = $stocks_net + $creances_clients + $autres_creances;
    $bfr = $actif_circulant_ht - $passif_circulant;
    $bfr_jours = $ca_net > 0 ? round(($bfr / $ca_net) * 365, 1) : 0;
    
    // --- 2. FONDS DE ROULEMENT ---
    // FR = Capitaux permanents (CP + Dettes LT) - Actif immobilisé
    $fonds_roulement = ($capitaux_propres + $dettes_financieres) - $actif_immobilise;
    
    // --- 3. TRÉSORERIE NETTE ---
    // TN = FR - BFR ou Trésorerie Active - Trésorerie Passive
    $tresorerie_nette = $fonds_roulement - $bfr;
    $tresorerie_nette_verification = $tresorerie_active - $tresorerie_passive;
    
    // --- 4. DSO (Days Sales Outstanding) = Créances Clients / CA * 365 ---
    $dso = $ca_net > 0 ? round(($creances_clients / $ca_net) * 365, 1) : 0;
    
    // --- 5. DPO (Days Payable Outstanding) = Dettes Fournisseurs / Achats * 365 ---
    $total_achats = $achats + $services_ext;
    $dpo = $total_achats > 0 ? round(($dettes_fournisseurs / $total_achats) * 365, 1) : 0;
    
    // --- 6. JOURS DE STOCK = Stock / Achats matières * 365 ---
    $jours_stock = $achats > 0 ? round(($stocks_net / $achats) * 365, 1) : 0;
    
    // --- 7. CYCLE DE CONVERSION DE TRÉSORERIE ---
    $cycle_conversion = $dso + $jours_stock - $dpo;
    
    // --- 8. CAF (Capacité d'Autofinancement) ---
    $reprises_provisions = -(($soldes3['781'] ?? 0) + ($soldes3['786'] ?? 0) + ($soldes3['787'] ?? 0));
    $caf = $resultat_net + $dotations - $reprises_provisions;
    
    // --- 9. RATIO DE SOLVABILITÉ GÉNÉRALE ---
    // = Total Actif / Total Dettes
    $total_dettes = $dettes_financieres + $passif_circulant + $tresorerie_passive;
    $ratio_solvabilite = $total_dettes > 0 ? round($total_actif / $total_dettes, 2) : 999;
    
    // --- 10. RATIO DE LIQUIDITÉ GÉNÉRALE ---
    // = Actif circulant / Passif circulant
    $ratio_liquidite = $passif_circulant > 0 ? round($actif_circulant / $passif_circulant, 2) : 999;
    
    // --- 11. RATIO DE LIQUIDITÉ IMMÉDIATE ---
    // = Trésorerie active / Passif circulant
    $ratio_liquidite_immediate = $passif_circulant > 0 ? round($tresorerie_active / $passif_circulant, 2) : 999;
    
    // --- 12. RATIO D'AUTONOMIE FINANCIÈRE ---
    // = Capitaux propres / Total bilan
    $ratio_autonomie = $total_actif > 0 ? round(($capitaux_propres / $total_actif) * 100, 1) : 0;
    
    // --- 13. RATIO D'ENDETTEMENT ---
    // = Dettes financières / Capitaux propres
    $ratio_endettement = $capitaux_propres > 0 ? round($dettes_financieres / $capitaux_propres, 2) : 999;
    
    // --- 14. SEUIL DE RENTABILITÉ ---
    // Charges fixes = Personnel + impôts + dotations + charges financières
    $charges_fixes = $charges_personnel + $impots_taxes + $dotations + $charges_financieres;
    // Charges variables = Achats matières + services ext
    $charges_variables = $achats + $services_ext;
    // Taux de marge sur coûts variables
    $taux_mscv = $ca_net > 0 ? ($ca_net - $charges_variables) / $ca_net : 0;
    $marge_sur_cout_variable = $ca_net - $charges_variables;
    // Seuil de rentabilité = Charges fixes / Taux MSCV
    $seuil_rentabilite = $taux_mscv > 0 ? round($charges_fixes / $taux_mscv, 2) : 0;
    // Point mort (en jours)
    $point_mort_jours = $ca_net > 0 ? round(($seuil_rentabilite / $ca_net) * 365, 0) : 365;
    // Marge de sécurité
    $marge_securite = $ca_net - $seuil_rentabilite;
    $indice_securite = $ca_net > 0 ? round(($marge_securite / $ca_net) * 100, 1) : 0;
    
    // --- 15. EBE et taux ---
    $ebe = $resultat_net + $dotations + $charges_financieres + $impots_taxes;
    $taux_ebe = $ca_net > 0 ? round(($ebe / $ca_net) * 100, 2) : 0;
    
    // --- 16. Données de lettrage (exploitation FEC) ---
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN lettrage_flag = 1 THEN 1 ELSE 0 END) as lettrees,
            SUM(CASE WHEN piece_ref IS NOT NULL AND piece_ref != '' THEN 1 ELSE 0 END) as avec_piece,
            SUM(CASE WHEN date_limite_reglement IS NOT NULL AND date_limite_reglement != '' THEN 1 ELSE 0 END) as avec_echeance,
            SUM(CASE WHEN date_lettrage IS NOT NULL AND date_lettrage != '' THEN 1 ELSE 0 END) as avec_date_lettrage
        FROM ecritures WHERE exercice = ?
    ");
    $stmt->execute([$exercice]);
    $lettrage_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $taux_lettrage = (int)$lettrage_stats['total'] > 0 
        ? round(((int)$lettrage_stats['lettrees'] / (int)$lettrage_stats['total']) * 100, 1) 
        : 0;
    
    // --- 17. Créances échues non lettrées (risque) ---
    $stmt = $db->prepare("
        SELECT 
            SUM(CAST(debit AS REAL) - CAST(credit AS REAL)) as montant_risque,
            COUNT(*) as nb_ecritures
        FROM ecritures 
        WHERE exercice = ? 
          AND SUBSTR(compte_num, 1, 3) = '411'
          AND lettrage_flag = 0
          AND date_limite_reglement IS NOT NULL 
          AND date_limite_reglement < DATE('now')
    ");
    $stmt->execute([$exercice]);
    $creances_echues = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // --- 18. Doublons potentiels de pièces ---
    $stmt = $db->prepare("
        SELECT piece_ref, COUNT(*) as nb, 
               SUM(CAST(debit AS REAL)) as sum_debit
        FROM ecritures 
        WHERE exercice = ? 
          AND piece_ref IS NOT NULL AND piece_ref != ''
        GROUP BY piece_ref
        HAVING COUNT(*) > 4
        ORDER BY COUNT(*) DESC
        LIMIT 20
    ");
    $stmt->execute([$exercice]);
    $doublons_potentiels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // =============================================
    // COMPILATION RÉSULTAT
    // =============================================
    
    $kpis = [
        'exercice' => $exercice,
        
        // BILAN SYNTHÉTIQUE
        'bilan' => [
            'actif_immobilise' => round($actif_immobilise, 2),
            'stocks' => round($stocks_net, 2),
            'creances_clients' => round($creances_clients, 2),
            'autres_creances' => round($autres_creances, 2),
            'tresorerie_active' => round($tresorerie_active, 2),
            'actif_circulant' => round($actif_circulant, 2),
            'total_actif' => round($total_actif, 2),
            'capitaux_propres' => round($capitaux_propres, 2),
            'dettes_financieres' => round($dettes_financieres, 2),
            'dettes_fournisseurs' => round($dettes_fournisseurs, 2),
            'dettes_fiscales' => round($dettes_fiscales, 2),
            'passif_circulant' => round($passif_circulant, 2),
            'tresorerie_passive' => round($tresorerie_passive, 2),
        ],
        
        // EQUILIBRE FINANCIER
        'equilibre' => [
            'fonds_roulement' => round($fonds_roulement, 2),
            'bfr' => round($bfr, 2),
            'bfr_jours' => $bfr_jours,
            'tresorerie_nette' => round($tresorerie_nette, 2),
            'tresorerie_verification' => round($tresorerie_nette_verification, 2),
            'coherence' => abs($tresorerie_nette - $tresorerie_nette_verification) < 1 ? 'OK' : 'ECART',
        ],
        
        // CYCLE D'EXPLOITATION
        'cycles' => [
            'dso_clients' => $dso,
            'dpo_fournisseurs' => $dpo,
            'jours_stock' => $jours_stock,
            'cycle_conversion' => round($cycle_conversion, 1),
            'interpretation' => $cycle_conversion > 0 
                ? "L'entreprise finance {$cycle_conversion} jours de son cycle d'exploitation"
                : "L'entreprise bénéficie d'un cycle de trésorerie favorable"
        ],
        
        // SOLVABILITÉ ET LIQUIDITÉ
        'solvabilite' => [
            'ratio_solvabilite' => $ratio_solvabilite,
            'ratio_liquidite' => $ratio_liquidite,
            'ratio_liquidite_immediate' => $ratio_liquidite_immediate,
            'ratio_autonomie' => $ratio_autonomie,
            'ratio_endettement' => $ratio_endettement,
            'sante' => ($ratio_solvabilite >= 1.5 && $ratio_liquidite >= 1.2) ? 'SAIN' : 
                       (($ratio_solvabilite >= 1 && $ratio_liquidite >= 1) ? 'VIGILANCE' : 'ALERTE'),
        ],
        
        // SEUIL DE RENTABILITÉ
        'seuil_rentabilite' => [
            'charges_fixes' => round($charges_fixes, 2),
            'charges_variables' => round($charges_variables, 2),
            'marge_cout_variable' => round($marge_sur_cout_variable, 2),
            'taux_mscv' => round($taux_mscv * 100, 2),
            'seuil' => round($seuil_rentabilite, 2),
            'point_mort_jours' => $point_mort_jours,
            'marge_securite' => round($marge_securite, 2),
            'indice_securite' => $indice_securite,
            'atteint' => $ca_net >= $seuil_rentabilite,
        ],
        
        // CAF
        'caf' => [
            'montant' => round($caf, 2),
            'taux_caf' => $ca_net > 0 ? round(($caf / $ca_net) * 100, 2) : 0,
        ],
        
        // PROFITABILITÉ
        'profitabilite' => [
            'ca_net' => round($ca_net, 2),
            'ebe' => round($ebe, 2),
            'taux_ebe' => $taux_ebe,
            'resultat_net' => round($resultat_net, 2),
            'taux_marge_nette' => $ca_net > 0 ? round(($resultat_net / $ca_net) * 100, 2) : 0,
            'roi' => $total_actif > 0 ? round(($resultat_net / $total_actif) * 100, 2) : 0,
            'roe' => $capitaux_propres > 0 ? round(($resultat_net / $capitaux_propres) * 100, 2) : 0,
        ],
        
        // QUALITÉ DES DONNÉES FEC
        'qualite_fec' => [
            'total_ecritures' => (int)$lettrage_stats['total'],
            'ecritures_lettrees' => (int)$lettrage_stats['lettrees'],
            'taux_lettrage' => $taux_lettrage,
            'ecritures_avec_piece' => (int)$lettrage_stats['avec_piece'],
            'ecritures_avec_echeance' => (int)$lettrage_stats['avec_echeance'],
            'ecritures_avec_date_lettrage' => (int)$lettrage_stats['avec_date_lettrage'],
        ],
        
        // RISQUES
        'risques' => [
            'creances_echues' => [
                'montant' => round((float)($creances_echues['montant_risque'] ?? 0), 2),
                'nb_ecritures' => (int)($creances_echues['nb_ecritures'] ?? 0),
            ],
            'doublons_potentiels' => array_map(function($d) {
                return [
                    'piece_ref' => $d['piece_ref'],
                    'nb_lignes' => (int)$d['nb'],
                    'montant' => round((float)$d['sum_debit'], 2),
                ];
            }, $doublons_potentiels),
        ],
        
        // ALERTES AUTOMATISÉES
        'alertes' => [],
    ];
    
    // =============================================
    // MOTEUR D'ALERTES
    // =============================================
    $alertes = [];
    
    if ($ratio_solvabilite < 1) {
        $alertes[] = ['level' => 'critical', 'icon' => '🔴', 'title' => 'Insolvabilité', 'message' => "Ratio de solvabilité = {$ratio_solvabilite}x — Risque de cessation de paiement"];
    }
    if ($ratio_liquidite < 1) {
        $alertes[] = ['level' => 'critical', 'icon' => '🔴', 'title' => 'Liquidité insuffisante', 'message' => "Ratio de liquidité = {$ratio_liquidite}x — Incapacité à honorer les dettes CT"];
    }
    if ($tresorerie_nette < 0) {
        $alertes[] = ['level' => 'critical', 'icon' => '🔴', 'title' => 'Trésorerie négative', 'message' => "TN = " . number_format($tresorerie_nette, 0, ',', ' ') . " € — Tension de trésorerie"];
    }
    if ($bfr > $fonds_roulement && $fonds_roulement > 0) {
        $alertes[] = ['level' => 'warning', 'icon' => '🟠', 'title' => 'BFR > Fonds de Roulement', 'message' => "BFR ({$bfr_jours}j) dépasse le FR — Besoin de financement CT"];
    }
    if ($dso > 60) {
        $alertes[] = ['level' => 'warning', 'icon' => '🟠', 'title' => 'Délai client excessif', 'message' => "DSO = {$dso} jours — Suivi des impayés recommandé"];
    }
    if ($dpo < 30 && $dpo > 0) {
        $alertes[] = ['level' => 'info', 'icon' => '🔵', 'title' => 'Paiement fournisseurs rapide', 'message' => "DPO = {$dpo} jours — Levier d'optimisation trésorerie possible"];
    }
    if ($cycle_conversion > 90) {
        $alertes[] = ['level' => 'warning', 'icon' => '🟠', 'title' => 'Cycle de conversion long', 'message' => "Cycle = " . round($cycle_conversion, 0) . " jours — Risque de trésorerie"];
    }
    if (!$kpis['seuil_rentabilite']['atteint']) {
        $alertes[] = ['level' => 'critical', 'icon' => '🔴', 'title' => 'Sous le seuil de rentabilité', 'message' => "CA (" . number_format($ca_net, 0, ',', ' ') . " €) < Seuil (" . number_format($seuil_rentabilite, 0, ',', ' ') . " €)"];
    }
    if ($ratio_autonomie < 20) {
        $alertes[] = ['level' => 'warning', 'icon' => '🟠', 'title' => 'Autonomie financière faible', 'message' => "Ratio = {$ratio_autonomie}% — Dépendance élevée aux créanciers"];
    }
    if ((float)($creances_echues['montant_risque'] ?? 0) > 0) {
        $alertes[] = ['level' => 'warning', 'icon' => '🟠', 'title' => 'Créances échues non lettrées', 
            'message' => number_format((float)$creances_echues['montant_risque'], 0, ',', ' ') . " € de créances en retard"];
    }
    if ($taux_lettrage < 50 && (int)$lettrage_stats['total'] > 100) {
        $alertes[] = ['level' => 'info', 'icon' => '🔵', 'title' => 'Taux de lettrage bas', 'message' => "Seulement {$taux_lettrage}% des écritures lettrées — Risque de rapprochement"];
    }
    if (count($doublons_potentiels) > 0) {
        $alertes[] = ['level' => 'info', 'icon' => '🔵', 'title' => 'Doublons de pièces potentiels', 'message' => count($doublons_potentiels) . " pièces avec plus de 4 lignes — À vérifier"];
    }
    if ($resultat_net < 0) {
        $alertes[] = ['level' => 'critical', 'icon' => '🔴', 'title' => 'Résultat Net déficitaire', 'message' => "Perte de " . number_format(abs($resultat_net), 0, ',', ' ') . " €"];
    }
    
    $kpis['alertes'] = $alertes;
    
    // Scoring santé globale (0-100)
    $score = 100;
    foreach ($alertes as $a) {
        if ($a['level'] === 'critical') $score -= 20;
        if ($a['level'] === 'warning') $score -= 10;
        if ($a['level'] === 'info') $score -= 3;
    }
    $kpis['score_sante'] = max(0, min(100, $score));
    $kpis['grade'] = $score >= 80 ? 'A' : ($score >= 60 ? 'B' : ($score >= 40 ? 'C' : 'D'));
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $kpis
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
