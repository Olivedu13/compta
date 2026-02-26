<?php
/**
 * GET /api/v1/analytics/analysis
 * Analyse complète: CA mensuel, top clients/fournisseurs, structure coûts
 * 
 * Params:
 * - exercice (required): Année comptable (ex: 2024)
 */

// Find project root (works locally with public_html/ and on Ionos flat webroot)
$_projectRoot = dirname(dirname(dirname(__DIR__)));
if (!file_exists($_projectRoot . '/backend/bootstrap.php')) {
    $_projectRoot = dirname($_projectRoot);
}
require_once $_projectRoot . '/backend/bootstrap.php';

use App\Config\InputValidator;
use App\Config\Logger;

try {
    $exercice = InputValidator::asYear($_GET['exercice'] ?? null);
    
    if (!$exercice) {
        http_response_code(400);
        throw new Exception('Parameter exercice is required');
    }
    
    $db = getDatabase();
    
    // 1. CA MENSUEL (Classe 7 = Ventes)
    $caMensuel = $db->fetchAll(
        "SELECT 
            SUBSTRING(e.ecriture_date, 1, 7) as mois,
            SUM(CASE WHEN e.debit > 0 THEN e.debit ELSE -e.credit END) as ca_mensuel
        FROM fin_ecritures_fec e
        WHERE strftime('%Y', e.ecriture_date) = ?
          AND SUBSTRING(e.compte_num, 1, 1) = '7'
        GROUP BY SUBSTRING(e.ecriture_date, 1, 7)
        ORDER BY mois",
        [(string)$exercice]
    );
    
    // 2. TOP CLIENTS (411xxx)
    $topClients = $db->fetchAll(
        "SELECT 
            p.compte_lib as client,
            b.compte_num,
            ABS(b.solde) as montant
        FROM fin_balance b
        LEFT JOIN sys_plan_comptable p ON b.compte_num = p.compte_num
        WHERE b.exercice = ?
          AND SUBSTRING(b.compte_num, 1, 3) = '411'
          AND b.solde != 0
        ORDER BY ABS(b.solde) DESC
        LIMIT 10",
        [$exercice]
    );
    
    // 3. TOP FOURNISSEURS (401xxx)
    $topFournisseurs = $db->fetchAll(
        "SELECT 
            p.compte_lib as fournisseur,
            b.compte_num,
            ABS(b.solde) as montant
        FROM fin_balance b
        LEFT JOIN sys_plan_comptable p ON b.compte_num = p.compte_num
        WHERE b.exercice = ?
          AND SUBSTRING(b.compte_num, 1, 3) = '401'
          AND b.solde != 0
        ORDER BY ABS(b.solde) DESC
        LIMIT 10",
        [$exercice]
    );
    
    // 4. ACHATS (601 - Matières premières)
    $achatsResult = $db->fetchOne(
        "SELECT SUM(ABS(b.solde)) as montant_achats
        FROM fin_balance b
        WHERE b.exercice = ?
          AND SUBSTRING(b.compte_num, 1, 3) = '601'",
        [$exercice]
    );
    $montantAchats = (float)($achatsResult['montant_achats'] ?? 0);
    
    // 5. MASSES SALARIALES (641 + 645)
    $salaireResult = $db->fetchOne(
        "SELECT SUM(ABS(b.solde)) as montant_salaires
        FROM fin_balance b
        WHERE b.exercice = ?
          AND (SUBSTRING(b.compte_num, 1, 3) = '641' 
            OR SUBSTRING(b.compte_num, 1, 3) = '645')",
        [$exercice]
    );
    $montantSalaires = (float)($salaireResult['montant_salaires'] ?? 0);
    
    // 6. FRAIS BANCAIRES (627)
    $fraisResult = $db->fetchOne(
        "SELECT SUM(ABS(b.solde)) as frais_bancaires
        FROM fin_balance b
        WHERE b.exercice = ?
          AND SUBSTRING(b.compte_num, 1, 3) = '627'",
        [$exercice]
    );
    $montantFrais = (float)($fraisResult['frais_bancaires'] ?? 0);
    
    // 7. CA TOTAL
    $caTotalResult = $db->fetchOne(
        "SELECT SUM(ABS(b.solde)) as ca_total
        FROM fin_balance b
        WHERE b.exercice = ?
          AND SUBSTRING(b.compte_num, 1, 1) = '7'",
        [$exercice]
    );
    $caTotal = (float)($caTotalResult['ca_total'] ?? 0);
    
    Logger::info('Analysis retrieved', ['exercice' => $exercice]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'exercice' => $exercice,
        'ca_mensuel' => $caMensuel,
        'top_clients' => $topClients,
        'top_fournisseurs' => $topFournisseurs,
        'structure_couts' => [
            'achats' => $montantAchats,
            'salaires' => $montantSalaires,
            'frais_bancaires' => $montantFrais
        ],
        'ca_total' => $caTotal
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    Logger::error('Failed to fetch analysis', ['error' => $e->getMessage()]);
}
