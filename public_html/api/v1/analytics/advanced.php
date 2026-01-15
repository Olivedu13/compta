<?php
/**
 * GET /api/v1/analytics/advanced.php
 * Analyse avancée des données comptables
 * Self-contained - No dependencies
 * 
 * Params:
 * - exercice (required): Année comptable (ex: 2024)
 */

header('Content-Type: application/json; charset=utf-8');

try {
    // Validate exercice
    $exercice = isset($_GET['exercice']) ? (int)$_GET['exercice'] : null;
    
    if (!$exercice || $exercice < 1900 || $exercice > 2100) {
        http_response_code(400);
        throw new Exception('Parameter exercice is required and must be a valid year');
    }
    
    // Get DB - 5 levels up: analytics -> v1 -> api -> public_html -> compta
    $projectRoot = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
    $dbPath = $projectRoot . '/compta.db';
    
    if (!file_exists($dbPath)) {
        http_response_code(500);
        throw new Exception("Database not found");
    }
    
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Evolution par mois
    $stmt = $db->prepare("
        SELECT 
            strftime('%Y-%m', ecriture_date) as mois,
            SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END) as total_debit,
            SUM(CASE WHEN credit > 0 THEN credit ELSE 0 END) as total_credit,
            COUNT(*) as nb_operations
        FROM ecritures
        WHERE exercice = ?
        GROUP BY strftime('%Y-%m', ecriture_date)
        ORDER BY mois
    ");
    $stmt->execute([$exercice]);
    $evolutionMois = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Top journaux
    $stmt = $db->prepare("
        SELECT 
            journal_code,
            journal_lib,
            COUNT(*) as nb_operations,
            SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END) as total_debit,
            SUM(CASE WHEN credit > 0 THEN credit ELSE 0 END) as total_credit
        FROM ecritures
        WHERE exercice = ?
        GROUP BY journal_code, journal_lib
        ORDER BY nb_operations DESC
    ");
    $stmt->execute([$exercice]);
    $journaux = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Comptes les plus actifs
    $stmt = $db->prepare("
        SELECT 
            compte_num,
            compte_lib,
            COUNT(*) as nb_operations,
            SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END) as total_debit,
            SUM(CASE WHEN credit > 0 THEN credit ELSE 0 END) as total_credit
        FROM ecritures
        WHERE exercice = ?
        GROUP BY compte_num, compte_lib
        ORDER BY nb_operations DESC
        LIMIT 30
    ");
    $stmt->execute([$exercice]);
    $comptesActifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Tiers par montant (mouvements les plus importants)
    $stmt = $db->prepare("
        SELECT 
            numero_tiers,
            lib_tiers,
            COUNT(*) as nb_operations,
            SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END) as total_debit,
            SUM(CASE WHEN credit > 0 THEN credit ELSE 0 END) as total_credit,
            ROUND(SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END) - SUM(CASE WHEN credit > 0 THEN credit ELSE 0 END), 2) as solde
        FROM ecritures
        WHERE exercice = ?
        AND numero_tiers IS NOT NULL
        AND numero_tiers != ''
        GROUP BY numero_tiers, lib_tiers
        ORDER BY (total_debit + total_credit) DESC
        LIMIT 30
    ");
    $stmt->execute([$exercice]);
    $tiersActifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 5. Distribution par classe
    $stmt = $db->prepare("
        SELECT 
            SUBSTR(compte_num, 1, 1) as classe,
            COUNT(*) as nb_operations,
            SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END) as total_debit,
            SUM(CASE WHEN credit > 0 THEN credit ELSE 0 END) as total_credit
        FROM ecritures
        WHERE exercice = ?
        GROUP BY SUBSTR(compte_num, 1, 1)
        ORDER BY classe
    ");
    $stmt->execute([$exercice]);
    $classeResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $classLabels = [
        '1' => 'Immobilisations',
        '2' => 'Actifs circulants',
        '3' => 'Stocks',
        '4' => 'Tiers',
        '5' => 'Trésorerie',
        '6' => 'Charges',
        '7' => 'Produits'
    ];
    
    $parClasse = [];
    foreach ($classeResults as $row) {
        $classe = $row['classe'];
        $parClasse[] = [
            'classe' => $classe,
            'libelle' => $classLabels[$classe] ?? "Classe $classe",
            'nb_operations' => (int)$row['nb_operations'],
            'total_debit' => (float)$row['total_debit'],
            'total_credit' => (float)$row['total_credit'],
            'solde' => (float)$row['total_debit'] - (float)$row['total_credit']
        ];
    }
    
    // 6. Statistiques globales
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_operations,
            COUNT(DISTINCT journal_code) as nb_journaux,
            COUNT(DISTINCT compte_num) as nb_comptes,
            COUNT(DISTINCT numero_tiers) as nb_tiers,
            MIN(ecriture_date) as date_premiere,
            MAX(ecriture_date) as date_derniere,
            ROUND(AVG(CASE WHEN debit > 0 THEN debit WHEN credit > 0 THEN credit ELSE 0 END), 2) as montant_moyen
        FROM ecritures
        WHERE exercice = ?
    ");
    $stmt->execute([$exercice]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $data = [
        'exercice' => $exercice,
        'stats_globales' => [
            'total_operations' => (int)$stats['total_operations'],
            'nb_journaux' => (int)$stats['nb_journaux'],
            'nb_comptes' => (int)$stats['nb_comptes'],
            'nb_tiers' => (int)$stats['nb_tiers'],
            'periode' => [
                'debut' => $stats['date_premiere'],
                'fin' => $stats['date_derniere']
            ],
            'montant_moyen' => (float)$stats['montant_moyen']
        ],
        'evolution_mensuelle' => array_map(function($m) {
            return [
                'mois' => $m['mois'],
                'debit' => (float)$m['total_debit'],
                'credit' => (float)$m['total_credit'],
                'operations' => (int)$m['nb_operations']
            ];
        }, $evolutionMois),
        'journaux' => array_map(function($j) {
            return [
                'code' => $j['journal_code'],
                'libelle' => $j['journal_lib'],
                'operations' => (int)$j['nb_operations'],
                'debit' => (float)$j['total_debit'],
                'credit' => (float)$j['total_credit'],
                'solde' => (float)$j['total_debit'] - (float)$j['total_credit']
            ];
        }, $journaux),
        'comptes_actifs' => array_map(function($c) {
            return [
                'numero' => $c['compte_num'],
                'libelle' => $c['compte_lib'],
                'operations' => (int)$c['nb_operations'],
                'debit' => (float)$c['total_debit'],
                'credit' => (float)$c['total_credit'],
                'solde' => (float)$c['total_debit'] - (float)$c['total_credit']
            ];
        }, $comptesActifs),
        'tiers_actifs' => array_map(function($t) {
            return [
                'numero' => $t['numero_tiers'],
                'libelle' => $t['lib_tiers'],
                'operations' => (int)$t['nb_operations'],
                'debit' => (float)$t['total_debit'],
                'credit' => (float)$t['total_credit'],
                'solde' => (float)$t['solde']
            ];
        }, $tiersActifs),
        'distribution_classes' => $parClasse
    ];
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}