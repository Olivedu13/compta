<?php
/**
 * GET /api/v1/analytics/kpis
 * Récupère les indicateurs clés de performance (KPIs)
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
    
    // KPIs par classe de compte
    $kpis = $db->fetchAll("
        SELECT 
            SUBSTRING(compte_num, 1, 1) as classe,
            COUNT(*) as nb_comptes,
            SUM(debit) as total_debit,
            SUM(credit) as total_credit,
            SUM(debit - credit) as total_solde
        FROM fin_balance
        WHERE exercice = ?
        GROUP BY SUBSTRING(compte_num, 1, 1)
        ORDER BY classe
    ", [$exercice]);
    
    // Totaux généraux
    $totals = $db->fetchOne(
        "SELECT 
            SUM(debit) as total_debit,
            SUM(credit) as total_credit,
            SUM(debit - credit) as total_solde
        FROM fin_balance
        WHERE exercice = ?",
        [$exercice]
    );
    
    Logger::info('KPIs retrieved', ['exercice' => $exercice]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $kpis,
        'totals' => $totals,
        'exercice' => $exercice
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    Logger::error('Failed to fetch KPIs', ['error' => $e->getMessage()]);
}
