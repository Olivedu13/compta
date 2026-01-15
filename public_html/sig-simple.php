<?php
/**
 * API SIG - Soldes Intermédiaires de Gestion
 * Calcule la cascade SIG depuis le plan comptable PCG 2025
 */

require_once dirname(dirname(__FILE__)) . '/backend/bootstrap.php';

use App\Config\Database;
use App\Config\InputValidator;
use App\Config\Logger;

header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::getInstance();
    
    $exercice = $_GET['exercice'] ?? 2024;
    
    // Récupère tous les balances
    $balances = $db->query("
        SELECT 
            compte_num,
            SUM(debit) as debit,
            SUM(credit) as credit,
            SUM(debit - credit) as solde
        FROM fin_balance
        WHERE exercice = $exercice
        GROUP BY compte_num
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Organise par classe
    $parClasse = [];
    foreach ($balances as $b) {
        $classe = substr($b['compte_num'], 0, 1);
        if (!isset($parClasse[$classe])) {
            $parClasse[$classe] = ['total_debit' => 0, 'total_credit' => 0, 'total_solde' => 0, 'comptes' => []];
        }
        $parClasse[$classe]['total_debit'] += (float)$b['debit'];
        $parClasse[$classe]['total_credit'] += (float)$b['credit'];
        $parClasse[$classe]['total_solde'] += (float)$b['solde'];
        $parClasse[$classe]['comptes'][] = $b;
    }
    
    // Calcule les SIG principaux
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
        'ventes' => $ventes,
        'charges_exploitation' => $charges_exploitation,
        'production' => $resultat,
        'total_actif' => abs((float)($actifs['total_solde'] ?? 0)),
        'total_passif' => abs((float)($passifs['total_solde'] ?? 0))
    ];
    
    // Helper pour formater les SIG
    $formatSIG = function($valeur, $description = '') {
        $estPositif = $valeur >= 0;
        return [
            'valeur_brute' => $valeur,
            'est_positif' => $estPositif,
            'couleur' => $estPositif ? '#4caf50' : '#f44336',
            'symbole' => $estPositif ? '+' : '−',
            'valeur_affichee' => number_format(abs($valeur), 2, ',', ' '),
            'description' => $description
        ];
    };
    
    // Cascade SIG structurée pour Dashboard et SIGPage
    $cascade = [
        'chiffre_affaires' => [
            'formatted' => $formatSIG($ventes, 'Total des ventes (classe 7)'),
            'description' => 'Chiffre d\'Affaires'
        ],
        'charges_exploitation' => [
            'formatted' => $formatSIG(-$charges_exploitation, 'Total des charges (classe 6)'),
            'description' => 'Charges d\'Exploitation'
        ],
        'marge_brute' => [
            'formatted' => $formatSIG($ventes - $charges_exploitation, 'CA - Charges'),
            'description' => 'Marge Brute'
        ],
        'stocks' => [
            'formatted' => $formatSIG((float)($stocks['total_solde'] ?? 0), 'Valorisation stocks'),
            'description' => 'Stocks'
        ],
        'tresorerie' => [
            'formatted' => $formatSIG((float)($tresorerie['total_solde'] ?? 0), 'Banque + Caisse'),
            'description' => 'Trésorerie'
        ]
    ];
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'exercice' => $exercice,
            'sig' => $sig,
            'cascade' => $cascade,
            'par_classe' => $parClasse,
            'waterfall_data' => [
                ['name' => 'Ventes', 'value' => $ventes],
                ['name' => 'Charges', 'value' => -$charges_exploitation],
                ['name' => 'Résultat', 'value' => $resultat]
            ]
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
