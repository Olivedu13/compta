<?php
/**
 * GET /api/v1/expenses/bank-fees.php
 * Analyse complète des frais bancaires toutes classes confondues
 * 
 * Comptes concernés: 627xxx (Services bancaires), 661xxx (Intérêts), 
 * 665xxx (Escomptes), 666xxx (Pertes de change), 668xxx (Autres charges fin.)
 * 
 * Params:
 * - exercice (required): Année comptable
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $exercice = isset($_GET['exercice']) ? (int)$_GET['exercice'] : null;
    
    if (!$exercice || $exercice < 1900 || $exercice > 2100) {
        http_response_code(400);
        throw new Exception('Parameter exercice is required and must be a valid year');
    }
    
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
    
    // Bank fee account prefixes (class 62 + class 66)
    $bankPrefixes = ['627', '661', '665', '666', '668'];
    $placeholders = implode(' OR ', array_map(fn($p) => "compte_num LIKE '$p%'", $bankPrefixes));
    
    $whereBase = "WHERE exercice = ? AND ($placeholders)";
    
    // === Condition d'exclusion des TOTAUX BANCAIRES ===
    // Les banques enregistrent des lignes récapitulatives (arrêtés de compte,
    // résultats trimestriels) qui REPRENNENT les frais déjà comptés en détail.
    // On les exclut de tous les calculs pour éviter le double comptage.
    $excludePatterns = "
        AND NOT (
            UPPER(libelle_ecriture) LIKE '%ARRET%'
            OR UPPER(libelle_ecriture) LIKE '%RESULTAT ARRET%'
            OR UPPER(libelle_ecriture) LIKE 'INTERETS/FRAIS%'
            OR UPPER(libelle_ecriture) LIKE 'INTERETS FRAIS%'
            OR UPPER(libelle_ecriture) LIKE 'INT ARRET%'
        )
    ";
    $whereClean = $whereBase . $excludePatterns;
    
    // 1. Total global (HORS totaux bancaires)
    $totStmt = $db->prepare("
        SELECT 
            SUM(CAST(debit AS REAL)) as total_debit,
            SUM(CAST(credit AS REAL)) as total_credit,
            COUNT(*) as nb_ecritures
        FROM ecritures $whereClean
    ");
    $totStmt->execute([$exercice]);
    $totals = $totStmt->fetch(PDO::FETCH_ASSOC);
    $totalDebit = round((float)($totals['total_debit'] ?? 0), 2);
    $totalCredit = round((float)($totals['total_credit'] ?? 0), 2);
    $totalSolde = round($totalDebit - $totalCredit, 2);
    $nbEcritures = (int)($totals['nb_ecritures'] ?? 0);
    
    if ($nbEcritures === 0) {
        // Fallback: check reports
        echo json_encode([
            'success' => true,
            'data' => [
                'exercice' => $exercice,
                'total' => 0,
                'nb_ecritures' => 0,
                'par_type' => [],
                'par_compte' => [],
                'par_mois' => [],
                'par_banque' => [],
            ],
            'source' => 'none'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 2. Par type (sous-compte unique groupé)
    $typeStmt = $db->prepare("
        SELECT 
            compte_num,
            compte_lib,
            SUM(CAST(debit AS REAL)) as total_debit,
            SUM(CAST(credit AS REAL)) as total_credit,
            COUNT(*) as nb
        FROM ecritures $whereClean
        GROUP BY compte_num
        ORDER BY (SUM(CAST(debit AS REAL)) - SUM(CAST(credit AS REAL))) DESC
    ");
    $typeStmt->execute([$exercice]);
    $parCompte = [];
    while ($row = $typeStmt->fetch(PDO::FETCH_ASSOC)) {
        $d = round((float)$row['total_debit'], 2);
        $c = round((float)$row['total_credit'], 2);
        $parCompte[] = [
            'compte' => $row['compte_num'],
            'label' => $row['compte_lib'] ?: $row['compte_num'],
            'montant' => round($d - $c, 2),
            'debit' => $d,
            'credit' => $c,
            'nb' => (int)$row['nb'],
            'pct' => $totalSolde > 0 ? round(($d - $c) / $totalSolde * 100, 1) : 0,
        ];
    }
    
    // 3. Par catégorie (groupé par préfixe 3 chiffres)
    $catLabels = [
        '627' => 'Services bancaires',
        '661' => 'Intérêts & agios',
        '665' => 'Escomptes accordés',
        '666' => 'Pertes de change',
        '668' => 'Autres charges financières',
    ];
    $catStmt = $db->prepare("
        SELECT 
            SUBSTR(compte_num, 1, 3) as prefix,
            SUM(CAST(debit AS REAL)) as total_debit,
            SUM(CAST(credit AS REAL)) as total_credit,
            COUNT(*) as nb
        FROM ecritures $whereClean
        GROUP BY SUBSTR(compte_num, 1, 3)
        ORDER BY (SUM(CAST(debit AS REAL)) - SUM(CAST(credit AS REAL))) DESC
    ");
    $catStmt->execute([$exercice]);
    $parType = [];
    while ($row = $catStmt->fetch(PDO::FETCH_ASSOC)) {
        $d = round((float)$row['total_debit'], 2);
        $c = round((float)$row['total_credit'], 2);
        $montant = round($d - $c, 2);
        $parType[] = [
            'code' => $row['prefix'],
            'label' => $catLabels[$row['prefix']] ?? "Compte {$row['prefix']}",
            'montant' => $montant,
            'nb' => (int)$row['nb'],
            'pct' => $totalSolde > 0 ? round($montant / $totalSolde * 100, 1) : 0,
        ];
    }
    
    // 4. Par mois
    $monthStmt = $db->prepare("
        SELECT 
            SUBSTR(ecriture_date, 1, 7) as mois,
            SUM(CAST(debit AS REAL)) as total_debit,
            SUM(CAST(credit AS REAL)) as total_credit,
            COUNT(*) as nb
        FROM ecritures $whereClean
        GROUP BY SUBSTR(ecriture_date, 1, 7)
        ORDER BY mois
    ");
    $monthStmt->execute([$exercice]);
    $parMois = [];
    while ($row = $monthStmt->fetch(PDO::FETCH_ASSOC)) {
        $d = round((float)$row['total_debit'], 2);
        $c = round((float)$row['total_credit'], 2);
        $parMois[] = [
            'mois' => $row['mois'],
            'montant' => round($d - $c, 2),
            'nb' => (int)$row['nb'],
        ];
    }
    
    // 5. Par banque (journal)
    $bankStmt = $db->prepare("
        SELECT 
            journal_code,
            journal_lib,
            SUM(CAST(debit AS REAL)) as total_debit,
            SUM(CAST(credit AS REAL)) as total_credit,
            COUNT(*) as nb
        FROM ecritures $whereClean
        GROUP BY journal_code
        ORDER BY (SUM(CAST(debit AS REAL)) - SUM(CAST(credit AS REAL))) DESC
    ");
    $bankStmt->execute([$exercice]);
    $parBanque = [];
    while ($row = $bankStmt->fetch(PDO::FETCH_ASSOC)) {
        $d = round((float)$row['total_debit'], 2);
        $c = round((float)$row['total_credit'], 2);
        $parBanque[] = [
            'code' => $row['journal_code'],
            'label' => $row['journal_lib'] ?: $row['journal_code'],
            'montant' => round($d - $c, 2),
            'nb' => (int)$row['nb'],
            'pct' => $totalSolde > 0 ? round(($d - $c) / $totalSolde * 100, 1) : 0,
        ];
    }
    
    // 6. Récupérer les lignes de TOTAUX BANCAIRES exclues (pour affichage info)
    //    Ce sont les lignes qui font double emploi avec le détail.
    $arreteStmt = $db->prepare("
        SELECT 
            compte_num,
            compte_lib,
            journal_code,
            ecriture_date,
            libelle_ecriture,
            CAST(debit AS REAL) as debit,
            CAST(credit AS REAL) as credit
        FROM ecritures $whereBase
          AND (UPPER(libelle_ecriture) LIKE '%ARRET%' 
               OR UPPER(libelle_ecriture) LIKE '%RESULTAT ARRET%'
               OR UPPER(libelle_ecriture) LIKE 'INTERETS/FRAIS%'
               OR UPPER(libelle_ecriture) LIKE 'INTERETS FRAIS%'
               OR UPPER(libelle_ecriture) LIKE 'INT ARRET%')
        ORDER BY ecriture_date
    ");
    $arreteStmt->execute([$exercice]);
    $arreteLignes = $arreteStmt->fetchAll(PDO::FETCH_ASSOC);

    $totalArrete = 0;
    $arreteDetail = [];
    foreach ($arreteLignes as $row) {
        $d = round((float)$row['debit'], 2);
        $c = round((float)$row['credit'], 2);
        $solde = round($d - $c, 2);
        $totalArrete += $solde;
        $arreteDetail[] = [
            'date' => $row['ecriture_date'],
            'journal' => $row['journal_code'],
            'compte' => $row['compte_num'],
            'compte_lib' => $row['compte_lib'],
            'libelle' => $row['libelle_ecriture'],
            'montant' => $solde,
        ];
    }
    $totalArrete = round($totalArrete, 2);

    echo json_encode([
        'success' => true,
        'data' => [
            'exercice' => $exercice,
            'total' => $totalSolde,
            'total_arrete_compte' => $totalArrete,
            'total_brut' => round($totalSolde + $totalArrete, 2),
            'nb_ecritures' => $nbEcritures,
            'cout_moyen_mensuel' => round($totalSolde / max(12, 1), 2),
            'par_type' => $parType,
            'par_compte' => $parCompte,
            'par_mois' => $parMois,
            'par_banque' => $parBanque,
            'arrete_compte' => [
                'total' => $totalArrete,
                'nb' => count($arreteDetail),
                'detail' => $arreteDetail,
                'note' => 'Totaux trimestriels calculés par les banques (arrêtés de compte, intérêts/frais). Exclus car ils font double emploi avec les écritures détaillées.',
            ],
        ],
        'source' => 'ecritures'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    if (http_response_code() === 200) http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
