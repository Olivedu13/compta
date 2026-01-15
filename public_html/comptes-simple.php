<?php
/**
 * Liste des comptes avec libellés - ROOT
 */

require_once dirname(dirname(__FILE__)) . '/backend/bootstrap.php';

use App\Config\Database;
use App\Config\InputValidator;
use App\Config\Logger;

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDatabase();
    
    $exercice = $_GET['exercice'] ?? 2024;
    
    // Récupère les comptes avec leurs soldes ET libellés si disponibles
    $sql = "
        SELECT 
            b.compte_num,
            SUBSTRING(b.compte_num, 1, 1) as classe,
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
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'count' => count($comptes),
        'comptes' => $comptes,
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
