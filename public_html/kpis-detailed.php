<?php
/**
 * ⚠️ DÉPRÉCIÉ - Endpoint migré vers /api/v1/
 * Nouvel endpoint: GET /api/v1/kpis/detailed.php
 */
$queryString = http_build_query($_GET);
$newUrl = '/api/v1/kpis/detailed.php' . ($queryString ? '?' . $queryString : '');
http_response_code(301);
header('Location: ' . $newUrl);
header('X-Deprecated: true');
header('X-Migration: Endpoint moved to /api/v1/kpis/detailed.php');
exit;

// Code legacy conservé pour référence:
/*
/**
 * KPIs détaillés avec vrais comptes
 */

require_once dirname(dirname(__FILE__)) . '/backend/bootstrap.php';

use App\Config\Database;
use App\Config\InputValidator;
use App\Config\Logger;

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDatabase();
    
    $exercice = $_GET['exercice'] ?? 2024;
    
    // Récupère comptes détaillés et leurs libellés
    $sql = "
        SELECT 
            b.compte_num,
            SUBSTRING(b.compte_num, 1, 1) as classe,
            SUBSTRING(b.compte_num, 1, 3) as sous_classe,
            b.solde,
            b.debit,
            b.credit,
            p.libelle as compte_libelle
        FROM fin_balance b
        LEFT JOIN sys_plan_comptable p ON b.compte_num = p.compte_num
        WHERE b.exercice = $exercice
        ORDER BY b.compte_num
    ";
    
    $comptes = $db->fetchAll($sql);
    
    // Organise les KPIs
    $kpis = [
        'stock' => ['or' => 0],
        'tresorerie' => ['banque' => 0, 'caisse' => 0, 'valeurs' => 0],
        'tiers' => ['clients' => 0, 'fournisseurs' => 0],
        'classes' => []
    ];
    
    // Catégorise les comptes
    foreach ($comptes as $compte) {
        $classe = $compte['classe'];
        $sous_classe = $compte['sous_classe'];
        $solde = (float)$compte['solde'];
        $libelle = $compte['compte_libelle'] ?: 'N/A';
        
        // Accumule par classe
        if (!isset($kpis['classes'][$classe])) {
            $kpis['classes'][$classe] = [];
        }
        $kpis['classes'][$classe][] = [
            'compte_num' => $compte['compte_num'],
            'libelle' => $libelle,
            'solde' => $solde
        ];
        
        // Stock
        if ($classe === '3') {
            $kpis['stock']['or'] += $solde;
        }
        
        // Trésorerie
        if ($classe === '5') {
            // Banques (51xxx)
            if ($sous_classe === '512' || $sous_classe === '513') {
                $kpis['tresorerie']['banque'] += $solde;
            }
            // Caisse (53xxx)
            elseif ($sous_classe === '530') {
                $kpis['tresorerie']['caisse'] += $solde;
            }
            // Valeurs (chèques, 51100000, 51910000)
            elseif ($sous_classe === '511' || $sous_classe === '519') {
                $kpis['tresorerie']['valeurs'] += $solde;
            }
        }
        
        // Tiers
        if ($classe === '4') {
            // Clients (41xxx)
            if ($sous_classe === '411') {
                $kpis['tiers']['clients'] += $solde;
            }
            // Fournisseurs (40xxx)
            elseif ($sous_classe === '401') {
                $kpis['tiers']['fournisseurs'] += $solde;
            }
        }
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'kpis' => $kpis,
        'exercice' => $exercice
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
