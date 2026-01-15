<?php
require_once dirname(dirname(__FILE__)) . '/backend/bootstrap.php';

use App\Config\Database;
use App\Config\InputValidator;
use App\Config\Logger;

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDatabase();
    
    // Afficher tous les comptes 41xx
    $sql = "SELECT compte_num, solde FROM fin_balance WHERE SUBSTRING(compte_num, 1, 2) = '41' AND solde > 0 ORDER BY ABS(solde) DESC";
    $results = $db->fetchAll($sql);
    
    echo "<h2>Tous les comptes 41xx (solde > 0):</h2>";
    echo "<pre>";
    print_r($results);
    echo "</pre>";
    
    // Vérifier la requête filtrée
    $sqlFiltered = "
        SELECT 
            COALESCE(p.libelle, b.compte_num) as client,
            b.compte_num,
            CEILING(ABS(b.solde)) as montant
        FROM fin_balance b
        LEFT JOIN sys_plan_comptable p ON b.compte_num = p.compte_num
        WHERE b.exercice = 2024
          AND SUBSTRING(b.compte_num, 1, 2) = '41'
          AND b.solde > 0
          AND b.compte_num != '41000000'
        ORDER BY ABS(b.solde) DESC
        LIMIT 10
    ";
    $filtered = $db->query($sqlFiltered)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Requête filtrée (sans 41000000):</h2>";
    echo "<pre>";
    print_r($filtered);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
