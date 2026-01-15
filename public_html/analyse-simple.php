<?php
/**
 * ⚠️ DÉPRÉCIÉ - Endpoint migré vers /api/v1/
 * Nouvel endpoint: GET /api/v1/analytics/simple.php
 */
$queryString = http_build_query($_GET);
$newUrl = '/api/v1/analytics/simple.php' . ($queryString ? '?' . $queryString : '');
http_response_code(301);
header('Location: ' . $newUrl);
header('X-Deprecated: true');
header('X-Migration: Endpoint moved to /api/v1/analytics/simple.php');
exit;
use App\Config\Logger;

header('Content-Type: application/json; charset=utf-8');

try {
    try {
        $exercice = InputValidator::asYear($_GET['exercice'] ?? 2024);
    } catch (\InvalidArgumentException $e) {
        http_response_code(400);
        throw new \Exception("Invalid parameter: " . $e->getMessage());
    }
    
    $db = Database::getInstance();
    
    // 1. CA MENSUEL (Classe 7 = Ventes)
    $caSQL = "
        SELECT 
            SUBSTRING(e.ecriture_date, 1, 7) as mois,
            SUM(CASE WHEN e.debit > 0 THEN e.debit ELSE -e.credit END) as ca_mensuel
        FROM fin_ecritures_fec e
        WHERE YEAR(e.ecriture_date) = $exercice 
          AND SUBSTRING(e.compte_num, 1, 1) = '7'
        GROUP BY SUBSTRING(e.ecriture_date, 1, 7)
        ORDER BY mois
    ";
    $caMensuel = $db->query($caSQL)->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. TOP CLIENTS (Classe 4, Compte 41xxx - Clients)
    $clientsSQL = "
        SELECT 
            p.libelle as client,
            b.compte_num,
            ABS(b.solde) as montant
        FROM fin_balance b
        LEFT JOIN sys_plan_comptable p ON b.compte_num = p.compte_num
        WHERE b.exercice = $exercice 
          AND SUBSTRING(b.compte_num, 1, 3) = '411'
          AND b.solde != 0
        ORDER BY ABS(b.solde) DESC
        LIMIT 10
    ";
    $topClients = $db->query($clientsSQL)->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. TOP FOURNISSEURS (Classe 4, Compte 40xxx - Fournisseurs)
    $fournisseursSQL = "
        SELECT 
            p.libelle as fournisseur,
            b.compte_num,
            ABS(b.solde) as montant
        FROM fin_balance b
        LEFT JOIN sys_plan_comptable p ON b.compte_num = p.compte_num
        WHERE b.exercice = $exercice 
          AND SUBSTRING(b.compte_num, 1, 3) = '401'
          AND b.solde != 0
        ORDER BY ABS(b.solde) DESC
        LIMIT 10
    ";
    $topFournisseurs = $db->query($fournisseursSQL)->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. ACHATS (601 - Matières premières)
    $achatsSQL = "
        SELECT 
            SUM(ABS(b.solde)) as montant_achats
        FROM fin_balance b
        WHERE b.exercice = $exercice 
          AND SUBSTRING(b.compte_num, 1, 3) = '601'
    ";
    $achatsResult = $db->query($achatsSQL)->fetch(PDO::FETCH_ASSOC);
    $montantAchats = (float)($achatsResult['montant_achats'] ?? 0);
    
    // 5. MASSES SALARIALES (641 + 645)
    $salaireSQL = "
        SELECT 
            SUM(ABS(b.solde)) as montant_salaires
        FROM fin_balance b
        WHERE b.exercice = $exercice 
          AND (SUBSTRING(b.compte_num, 1, 3) = '641' OR SUBSTRING(b.compte_num, 1, 3) = '645')
    ";
    $salaireResult = $db->query($salaireSQL)->fetch(PDO::FETCH_ASSOC);
    $montantSalaires = (float)($salaireResult['montant_salaires'] ?? 0);
    
    // 6. FRAIS BANCAIRES (627)
    $fraisSQL = "
        SELECT 
            SUM(ABS(b.solde)) as frais_bancaires
        FROM fin_balance b
        WHERE b.exercice = $exercice 
          AND SUBSTRING(b.compte_num, 1, 3) = '627'
    ";
    $fraisResult = $db->query($fraisSQL)->fetch(PDO::FETCH_ASSOC);
    $montantFrais = (float)($fraisResult['frais_bancaires'] ?? 0);
    
    // 7. CA TOTAL
    $caTotalSQL = "
        SELECT 
            SUM(ABS(b.solde)) as ca_total
        FROM fin_balance b
        WHERE b.exercice = $exercice 
          AND SUBSTRING(b.compte_num, 1, 1) = '7'
    ";
    $caTotalResult = $db->query($caTotalSQL)->fetch(PDO::FETCH_ASSOC);
    $caTotal = (float)($caTotalResult['ca_total'] ?? 0);
    
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
}
?>
