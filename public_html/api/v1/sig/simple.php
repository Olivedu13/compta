<?php
/**
 * GET /api/v1/sig/simple.php
 * Calcule les Soldes Intermédiaires de Gestion (SIG) - Cascade PCG 2025
 * Standard Big Four - Expertise bijouterie
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
        http_response_code(500);
        throw new Exception("Database not found at: " . $dbPath);
    }
    
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // =============================================
    // REQUÊTE : Soldes par racine de compte (2+3 premiers caractères)
    // =============================================
    $stmt = $db->prepare("
        SELECT 
            SUBSTR(compte_num, 1, 2) as racine2,
            SUBSTR(compte_num, 1, 3) as racine3,
            SUM(CAST(debit AS REAL)) as total_debit,
            SUM(CAST(credit AS REAL)) as total_credit
        FROM ecritures
        WHERE exercice = ?
        GROUP BY SUBSTR(compte_num, 1, 2), SUBSTR(compte_num, 1, 3)
    ");
    $stmt->execute([$exercice]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Indexer les soldes par racine 2 et 3
    $soldes2 = [];
    $soldes3 = [];
    
    foreach ($rows as $r) {
        $rac2 = $r['racine2'];
        $rac3 = $r['racine3'];
        $solde = (float)$r['total_debit'] - (float)$r['total_credit'];
        
        if (!isset($soldes2[$rac2])) $soldes2[$rac2] = 0;
        $soldes2[$rac2] += $solde;
        
        if (!isset($soldes3[$rac3])) $soldes3[$rac3] = 0;
        $soldes3[$rac3] += $solde;
    }
    
    // =============================================
    // EXCLUSION DES TOTAUX BANCAIRES (doublons)
    // Les banques enregistrent des récapitulatifs trimestriels (arrêtés de
    // compte, résultats, intérêts/frais) qui reprennent les frais déjà
    // comptés individuellement → double comptage sur comptes 627/661.
    // =============================================
    // EXCLUSION DES DOUBLONS BANCAIRES — uniquement comptes 627
    // Les arrêtés trimestriels reprennent les frais déjà comptés unitairement
    // On n'exclut PAS les comptes 661 car ce sont des intérêts d'emprunt légitimes
    $exclStmt = $db->prepare("
        SELECT 
            SUBSTR(compte_num, 1, 2) as racine2,
            SUBSTR(compte_num, 1, 3) as racine3,
            SUM(CAST(debit AS REAL)) as total_debit,
            SUM(CAST(credit AS REAL)) as total_credit
        FROM ecritures
        WHERE exercice = ?
          AND compte_num LIKE '627%'
          AND (UPPER(libelle_ecriture) LIKE '%ARRET%' 
               OR UPPER(libelle_ecriture) LIKE '%RESULTAT ARRET%'
               OR UPPER(libelle_ecriture) LIKE 'INTERETS/FRAIS%'
               OR UPPER(libelle_ecriture) LIKE 'INTERETS FRAIS%'
               OR UPPER(libelle_ecriture) LIKE 'INT ARRET%')
        GROUP BY SUBSTR(compte_num, 1, 2), SUBSTR(compte_num, 1, 3)
    ");
    $exclStmt->execute([$exercice]);
    $totalExclu = 0;
    while ($row = $exclStmt->fetch(PDO::FETCH_ASSOC)) {
        $solde = (float)$row['total_debit'] - (float)$row['total_credit'];
        $totalExclu += $solde;
        if (isset($soldes2[$row['racine2']])) $soldes2[$row['racine2']] -= $solde;
        if (isset($soldes3[$row['racine3']])) $soldes3[$row['racine3']] -= $solde;
    }

    // RECLASSIFICATION : 627 (frais bancaires) de Services Ext. (62) → Financier (66)
    // Le compte 627 est comptablement en 62, mais analytiquement c'est une charge financière
    $frais_bancaires_627 = $soldes3['627'] ?? 0; // Déjà net des doublons exclus ci-dessus

    // =============================================
    // CASCADE SIG PCG 2025 — Bijouterie
    // Convention: Produits = crédit > débit → solde négatif (on inverse)
    //             Charges = débit > crédit → solde positif
    // =============================================
    
    // 1. CHIFFRE D'AFFAIRES NET (comptes 70)
    $ca_net = -($soldes2['70'] ?? 0);
    
    // 2. PRODUCTION DE L'EXERCICE = 70 + 71 + 72
    $production = -(($soldes2['70'] ?? 0) + ($soldes2['71'] ?? 0) + ($soldes2['72'] ?? 0));
    
    // 3. CONSOMMATIONS MATIÈRES = 601 + 602 ± 603
    $achats_mp = ($soldes3['601'] ?? 0) + ($soldes3['602'] ?? 0) + ($soldes3['603'] ?? 0);
    $achats_marchandises = $soldes3['607'] ?? 0;
    $variation_stock = $soldes3['603'] ?? 0;
    
    // 4. MARGE COMMERCIALE
    $ventes_marchandises = -($soldes3['707'] ?? 0);
    $marge_commerciale = $ventes_marchandises - $achats_marchandises + $variation_stock;
    
    // 5. MARGE DE PRODUCTION
    $marge_production = $production - $achats_mp;
    
    // 6. VALEUR AJOUTÉE = Marge Prod. - Services ext. (61+62 HORS 627)
    // 627 (frais bancaires) reclassé en charges financières
    $services_ext = ($soldes2['61'] ?? 0) + ($soldes2['62'] ?? 0) - $frais_bancaires_627;
    $valeur_ajoutee = $marge_production - $services_ext;
    
    // 7. EBE (EBITDA) = VA + Subventions (74) - Impôts (63) - Personnel (64)
    $subventions = -($soldes2['74'] ?? 0);
    $impots_taxes = $soldes2['63'] ?? 0;
    $charges_personnel = $soldes2['64'] ?? 0;
    $ebe = $valeur_ajoutee + $subventions - $impots_taxes - $charges_personnel;
    
    // 8. RÉSULTAT D'EXPLOITATION = EBE + Autres prod. (75+78+79) - Autres charges (65+68)
    $autres_produits_exploit = -(($soldes2['75'] ?? 0) + ($soldes2['78'] ?? 0) + ($soldes2['79'] ?? 0));
    $autres_charges_exploit = ($soldes2['65'] ?? 0) + ($soldes2['68'] ?? 0);
    $resultat_exploitation = $ebe + $autres_produits_exploit - $autres_charges_exploit;
    
    // 9. RÉSULTAT FINANCIER = Produits fin. (76) - Charges fin. (66 + 627)
    // 627 reclassé en charges financières
    $produits_financiers = -($soldes2['76'] ?? 0);
    $charges_financieres = ($soldes2['66'] ?? 0) + $frais_bancaires_627;
    $resultat_financier = $produits_financiers - $charges_financieres;
    
    // 10. RCAI
    $rcai = $resultat_exploitation + $resultat_financier;
    
    // 11. RÉSULTAT EXCEPTIONNEL = 77 - 67
    $resultat_exceptionnel = -($soldes2['77'] ?? 0) - ($soldes2['67'] ?? 0);
    
    // 12. IS (695)
    $impot_benefices = $soldes3['695'] ?? 0;
    
    // 13. RÉSULTAT NET
    $resultat_net = $rcai + $resultat_exceptionnel - $impot_benefices;
    
    // 14. CAF = RN + Dotations (681/686/687) - Reprises (781/786/787) + VNC (675) - Produits cession (775)
    $dotations_amort = ($soldes3['681'] ?? 0) + ($soldes3['686'] ?? 0) + ($soldes3['687'] ?? 0);
    $reprises = -(($soldes3['781'] ?? 0) + ($soldes3['786'] ?? 0) + ($soldes3['787'] ?? 0));
    $vnc_cessions = $soldes3['675'] ?? 0;
    $produits_cessions = -($soldes3['775'] ?? 0);
    $caf = $resultat_net + $dotations_amort - $reprises + $vnc_cessions - $produits_cessions;
    
    // =============================================
    // RATIOS
    // =============================================
    $taux_marge_brute = $ca_net > 0 ? round(($marge_production / $ca_net) * 100, 2) : 0;
    $taux_va = $ca_net > 0 ? round(($valeur_ajoutee / $ca_net) * 100, 2) : 0;
    $taux_ebe = $ca_net > 0 ? round(($ebe / $ca_net) * 100, 2) : 0;
    $taux_marge_nette = $ca_net > 0 ? round(($resultat_net / $ca_net) * 100, 2) : 0;
    $part_personnel_va = $valeur_ajoutee > 0 ? round(($charges_personnel / $valeur_ajoutee) * 100, 2) : 0;
    
    // Validation
    $stmt = $db->prepare("SELECT COUNT(*) as cnt, SUM(CAST(debit AS REAL)) as sd, SUM(CAST(credit AS REAL)) as sc FROM ecritures WHERE exercice = ?");
    $stmt->execute([$exercice]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $ecart = abs((float)$stats['sd'] - (float)$stats['sc']);
    
    // Waterfall
    $waterfall = [
        ['name' => 'CA Net', 'value' => round($ca_net, 2), 'color' => '#2196f3'],
        ['name' => 'Production', 'value' => round($production, 2), 'color' => '#42a5f5'],
        ['name' => '- Conso. Matières', 'value' => round(-$achats_mp, 2), 'color' => '#ef5350'],
        ['name' => '= Marge Prod.', 'value' => round($marge_production, 2), 'color' => '#66bb6a'],
        ['name' => '- Services Ext.', 'value' => round(-$services_ext, 2), 'color' => '#ef5350'],
        ['name' => '= Valeur Ajoutée', 'value' => round($valeur_ajoutee, 2), 'color' => '#4caf50'],
        ['name' => '- Personnel', 'value' => round(-$charges_personnel, 2), 'color' => '#ff7043'],
        ['name' => '- Impôts & Taxes', 'value' => round(-$impots_taxes, 2), 'color' => '#ff7043'],
        ['name' => '= EBE (EBITDA)', 'value' => round($ebe, 2), 'color' => '#26a69a'],
        ['name' => '- Dotations', 'value' => round(-$autres_charges_exploit, 2), 'color' => '#ff7043'],
        ['name' => '= Rés. Exploit.', 'value' => round($resultat_exploitation, 2), 'color' => '#5c6bc0'],
        ['name' => '+/- Rés. Financier', 'value' => round($resultat_financier, 2), 'color' => $resultat_financier >= 0 ? '#66bb6a' : '#ef5350'],
        ['name' => '= RCAI', 'value' => round($rcai, 2), 'color' => '#7e57c2'],
        ['name' => '= Résultat Net', 'value' => round($resultat_net, 2), 'color' => $resultat_net >= 0 ? '#2e7d32' : '#c62828']
    ];
    
    // Cascade détaillée
    $mkCascade = function($val, $desc, $color) {
        return [
            'valeur' => round($val, 2),
            'description' => $desc,
            'formatted' => [
                'valeur_affichee' => number_format(abs($val), 2, ',', ' '),
                'est_positif' => $val >= 0,
                'couleur' => $color
            ]
        ];
    };
    
    $cascade = [
        'ca_net' => $mkCascade($ca_net, 'Chiffre d\'Affaires Net (comptes 70)', '#2196f3'),
        'production' => $mkCascade($production, 'Production de l\'exercice (70+71+72)', '#42a5f5'),
        'marge_production' => $mkCascade($marge_production, 'Marge de Production = Production - (601+602±603)', $marge_production >= 0 ? '#4caf50' : '#f44336'),
        'valeur_ajoutee' => $mkCascade($valeur_ajoutee, 'Valeur Ajoutée = Marge Prod. - (61+62)', $valeur_ajoutee >= 0 ? '#4caf50' : '#f44336'),
        'ebe' => $mkCascade($ebe, 'EBE (EBITDA) = VA + 74 - 63 - 64', $ebe >= 0 ? '#26a69a' : '#f44336'),
        'resultat_exploitation' => $mkCascade($resultat_exploitation, 'Résultat d\'Exploitation = EBE - Dotations', $resultat_exploitation >= 0 ? '#5c6bc0' : '#f44336'),
        'resultat_financier' => $mkCascade($resultat_financier, 'Résultat Financier = 76 - 66', $resultat_financier >= 0 ? '#66bb6a' : '#ef5350'),
        'rcai' => $mkCascade($rcai, 'Résultat Courant Avant Impôts = RE + RF', $rcai >= 0 ? '#7e57c2' : '#f44336'),
        'resultat_net' => $mkCascade($resultat_net, 'Résultat Net = RCAI + Exceptionnel - IS', $resultat_net >= 0 ? '#2e7d32' : '#c62828'),
        'caf' => $mkCascade($caf, 'CAF = RN + Dotations - Reprises + VNC - Prod. Cessions', $caf >= 0 ? '#00838f' : '#c62828')
    ];
    
    $sig = [
        'exercice' => $exercice,
        'ca_net' => round($ca_net, 2),
        'ca_brut' => round($ca_net, 2),
        'production' => round($production, 2),
        'marge_production' => round($marge_production, 2),
        'marge_commerciale' => round($marge_commerciale, 2),
        'valeur_ajoutee' => round($valeur_ajoutee, 2),
        'ebe' => round($ebe, 2),
        'resultat_exploitation' => round($resultat_exploitation, 2),
        'resultat_financier' => round($resultat_financier, 2),
        'rcai' => round($rcai, 2),
        'resultat_exceptionnel' => round($resultat_exceptionnel, 2),
        'resultat_net' => round($resultat_net, 2),
        'caf' => round($caf, 2),
        'charges' => round(($soldes2['60'] ?? 0) + ($soldes2['61'] ?? 0) + ($soldes2['62'] ?? 0) + ($soldes2['63'] ?? 0) + ($soldes2['64'] ?? 0) + ($soldes2['65'] ?? 0) + ($soldes2['66'] ?? 0) + ($soldes2['67'] ?? 0) + ($soldes2['68'] ?? 0), 2),
        'ratios' => [
            'taux_marge_brute' => $taux_marge_brute,
            'taux_valeur_ajoutee' => $taux_va,
            'taux_ebe' => $taux_ebe,
            'taux_marge_nette' => $taux_marge_nette,
            'part_personnel_va' => $part_personnel_va,
        ],
        'detail_charges' => [
            'achats_matieres' => round($achats_mp, 2),
            'achats_marchandises' => round($achats_marchandises, 2),
            'services_exterieurs' => round($services_ext, 2),
            'impots_taxes' => round($impots_taxes, 2),
            'charges_personnel' => round($charges_personnel, 2),
            'charges_financieres' => round($charges_financieres, 2),
            'dotations_amortissements' => round($dotations_amort, 2),
        ],
        'nb_ecritures' => (int)$stats['cnt'],
        'balance' => $ecart < 0.01 ? 'OK' : 'WARN',
        'ecart_balance' => round($ecart, 2),
        'cascade' => $cascade,
        'waterfall_data' => $waterfall
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
