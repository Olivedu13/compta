<?php
/**
 * ⚠️ DÉPRÉCIÉ - Endpoint migré vers /api/v1/
 * Nouvel endpoint: GET /api/v1/kpis/simple.php
 */
$queryString = http_build_query($_GET);
$newUrl = '/api/v1/kpis/simple.php' . ($queryString ? '?' . $queryString : '');
http_response_code(301);
header('Location: ' . $newUrl);
header('X-Deprecated: true');
header('X-Migration: Endpoint moved to /api/v1/kpis/simple.php');
exit;

// Code legacy conservé pour référence:
/*
/**
 * API KPIs - PDO direct
 */

require_once dirname(dirname(__FILE__)) . '/backend/bootstrap.php';

use App\Config\Database;
use App\Config\InputValidator;
use App\Config\Logger;

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDatabase();
    
    $exercice = $_GET['exercice'] ?? 2024;
    
    // Récupère les grands comptes pour SIG
    $sql = "
        SELECT 
            SUBSTRING(compte_num, 1, 1) as classe,
            COUNT(*) as nb_comptes,
            SUM(debit) as total_debit,
            SUM(credit) as total_credit,
            SUM(debit - credit) as total_solde
        FROM fin_balance b
        WHERE b.exercice = $exercice
        GROUP BY SUBSTRING(compte_num, 1, 1)
        ORDER BY classe
    ";
    
    $kpis = $db->fetchAll($sql);
    
    // Calcule aussi les totaux généraux
    $totals = $db->fetchOne("
        SELECT 
            SUM(debit) as total_debit,
            SUM(credit) as total_credit,
            SUM(debit - credit) as total_solde
        FROM fin_balance
        WHERE exercice = $exercice
    ");
    
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
}
?>
