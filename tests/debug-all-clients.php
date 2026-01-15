<?php
require_once dirname(dirname(__FILE__)) . '/backend/bootstrap.php';

use App\Config\Database;
use App\Config\InputValidator;
use App\Config\Logger;

header('Content-Type: application/json');

try {
    $db = getDatabase();
    
    $exercice = 2024;
    
    // Voir TOUS les tiers 41xxx
    $sql = "
        SELECT 
            COALESCE(e.comp_aux_lib, 'Clients sans libellé') as client,
            SUBSTRING(e.compte_num, 1, 2) as classe,
            CEILING(SUM(CASE 
                WHEN e.debit > 0 THEN e.debit 
                ELSE -e.credit 
            END)) as montant
        FROM fin_ecritures_fec e
        WHERE YEAR(e.ecriture_date) = $exercice 
          AND SUBSTRING(e.compte_num, 1, 2) = '41'
        GROUP BY e.comp_aux_lib
        HAVING montant > 0
        ORDER BY montant DESC
        LIMIT 30
    ";
    
    $results = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'total_count' => count($results),
        'clients' => $results
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
