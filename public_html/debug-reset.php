<?php
/**
 * Script de debug et reset de la base
 * À utiliser pour nettoyer avant les tests
 */

header('Content-Type: application/json; charset=utf-8');

define('APP_ROOT', dirname(dirname(dirname(__FILE__))));
define('BACKEND_ROOT', APP_ROOT . '/backend');

spl_autoload_register(function($class) {
    $class = str_replace('App\\', '', $class);
    $path = str_replace('\\', '/', $class);
    $parts = explode('/', $path);
    if (count($parts) > 0) {
        $parts[0] = strtolower($parts[0]);
    }
    $path = implode('/', $parts);
    $filePath = BACKEND_ROOT . '/' . $path . '.php';
    if (file_exists($filePath)) {
        require_once $filePath;
    }
});

use App\Config\Database;
use App\Config\Logger;

Logger::init();
$db = Database::getInstance();

$action = $_GET['action'] ?? 'info';
$exercice = (int) ($_GET['exercice'] ?? 2024);

try {
    if ($action === 'info') {
        // Affiche les stats
        $stats = [
            'fin_ecritures_fec' => $db->fetchOne("SELECT COUNT(*) as cnt FROM fin_ecritures_fec")['cnt'] ?? 0,
            'fin_balance' => $db->fetchOne("SELECT COUNT(*) as cnt FROM fin_balance")['cnt'] ?? 0,
            'sys_plan_comptable' => $db->fetchOne("SELECT COUNT(*) as cnt FROM sys_plan_comptable")['cnt'] ?? 0,
        ];
        
        // Exercices présents
        $exercices = $db->fetchAll(
            "SELECT DISTINCT exercice FROM fin_ecritures_fec ORDER BY exercice DESC"
        );
        
        // Premiers comptes
        $comptes_sample = $db->fetchAll(
            "SELECT DISTINCT compte_num FROM fin_ecritures_fec WHERE exercice = ? LIMIT 10",
            [$exercice]
        );
        
        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'exercices' => $exercices,
            'sample_comptes' => $comptes_sample
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
    } elseif ($action === 'reset') {
        // Nettoie tout
        $db->query("DELETE FROM fin_balance");
        $db->query("DELETE FROM fin_ecritures_fec");
        $db->query("DELETE FROM sys_plan_comptable WHERE classe_racine IS NOT NULL");
        
        echo json_encode([
            'success' => true,
            'message' => 'Base complètement nettoyée'
        ], JSON_PRETTY_PRINT);
        
    } elseif ($action === 'reset-year') {
        // Nettoie une année
        $db->query("DELETE FROM fin_balance WHERE exercice = ?", [$exercice]);
        $db->query("DELETE FROM fin_ecritures_fec WHERE exercice = ?", [$exercice]);
        
        echo json_encode([
            'success' => true,
            'message' => "Année $exercice nettoyée"
        ], JSON_PRETTY_PRINT);
        
    } elseif ($action === 'recalc-balance') {
        // Recalcule la balance pour un exercice
        $db->query("DELETE FROM fin_balance WHERE exercice = ?", [$exercice]);
        
        $sql = "
            INSERT INTO fin_balance (exercice, compte_num, debit, credit, solde)
            SELECT 
                exercice,
                compte_num,
                SUM(debit) as debit,
                SUM(credit) as credit,
                SUM(debit) - SUM(credit) as solde
            FROM fin_ecritures_fec
            WHERE exercice = ?
            GROUP BY compte_num
        ";
        
        $db->query($sql, [$exercice]);
        
        $count = $db->fetchOne("SELECT COUNT(*) as cnt FROM fin_balance WHERE exercice = ?", [$exercice])['cnt'];
        
        echo json_encode([
            'success' => true,
            'message' => "Balance recalculée pour $exercice",
            'balance_lines' => $count
        ], JSON_PRETTY_PRINT);
        
    } elseif ($action === 'sample-data') {
        // Affiche un échantillon de données
        $data = $db->fetchAll(
            "SELECT compte_num, journal_code, debit, credit, ecriture_date 
             FROM fin_ecritures_fec 
             WHERE exercice = ?
             LIMIT 5",
            [$exercice]
        );
        
        echo json_encode([
            'success' => true,
            'sample' => $data,
            'count' => count($data)
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
