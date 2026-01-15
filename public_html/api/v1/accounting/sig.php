<?php
/**
 * GET /api/v1/accounting/sig
 * Calcule les Soldes Intermédiaires de Gestion (SIG)
 * 
 * Params:
 * - exercice (required): Année comptable (ex: 2024)
 */

use App\Config\InputValidator;
use App\Config\Logger;

try {
    $exercice = InputValidator::asYear($_GET['exercice'] ?? null);
    
    if (!$exercice) {
        http_response_code(400);
        throw new Exception('Parameter exercice is required');
    }
    
    $db = getDatabase();
    
    // Récupère les balances groupées par compte
    $balances = $db->fetchAll(
        "SELECT 
            compte_num,
            SUM(debit) as debit,
            SUM(credit) as credit,
            SUM(debit - credit) as solde
        FROM fin_balance
        WHERE exercice = ?
        GROUP BY compte_num",
        [$exercice]
    );
    
    // Organise par classe
    $parClasse = [];
    foreach ($balances as $b) {
        $classe = substr($b['compte_num'], 0, 1);
        if (!isset($parClasse[$classe])) {
            $parClasse[$classe] = [
                'total_debit' => 0, 
                'total_credit' => 0, 
                'total_solde' => 0, 
                'comptes' => []
            ];
        }
        $parClasse[$classe]['total_debit'] += (float)$b['debit'];
        $parClasse[$classe]['total_credit'] += (float)$b['credit'];
        $parClasse[$classe]['total_solde'] += (float)$b['solde'];
        $parClasse[$classe]['comptes'][] = $b;
    }
    
    // Récupère les soldes par classe
    $actifs = $parClasse['1'] ?? ['total_solde' => 0];
    $passifs = $parClasse['2'] ?? ['total_solde' => 0];
    $stocks = $parClasse['3'] ?? ['total_solde' => 0];
    $tiers = $parClasse['4'] ?? ['total_solde' => 0];
    $tresorerie = $parClasse['5'] ?? ['total_solde' => 0];
    $charges = $parClasse['6'] ?? ['total_solde' => 0];
    $produits = $parClasse['7'] ?? ['total_solde' => 0];
    
    // Calculs SIG
    $ventes = abs((float)($produits['total_solde'] ?? 0));
    $charges_exploitation = abs((float)($charges['total_solde'] ?? 0));
    $resultat = $ventes - $charges_exploitation;
    
    $sig = [
        'exercice' => $exercice,
        'ventes' => $ventes,
        'charges_exploitation' => $charges_exploitation,
        'resultat_exploitation' => $resultat,
        'actif_total' => abs((float)($actifs['total_solde'] ?? 0)),
        'passif_total' => abs((float)($passifs['total_solde'] ?? 0)),
        'tresorerie' => (float)($tresorerie['total_solde'] ?? 0)
    ];
    
    Logger::info('SIG calculated', ['exercice' => $exercice]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $sig,
        'classes' => $parClasse
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    Logger::error('Failed to calculate SIG', ['error' => $e->getMessage()]);
}
