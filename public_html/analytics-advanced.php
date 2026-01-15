<?php
/**
 * Analyse Financière Avancée - KPIs Senior Level
 * Calculs de ratios, BFR, trésorerie, solvabilité, etc.
 */

require_once dirname(dirname(__FILE__)) . '/backend/bootstrap.php';

use App\Config\Database;
use App\Config\InputValidator;
use App\Config\Logger;

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDatabase();
    
    $exercice = $_GET['exercice'] ?? 2024;
    
    // Vérifier que l'exercice existe dans les données
    $checkExercice = $db->fetchAll("SELECT DISTINCT YEAR(ecriture_date) as exercice FROM fin_ecritures_fec ORDER BY exercice DESC");
    $exercicesDisponibles = array_column($checkExercice, 'exercice');
    
    if (!in_array((int)$exercice, $exercicesDisponibles)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => "Exercice $exercice non disponible",
            'exercices_disponibles' => $exercicesDisponibles
        ]);
        exit;
    }
    
    // ============================================
    // 1. CA MENSUEL ET TRIMESTRIEL
    // ============================================
    $caSQL = "
        SELECT 
            SUBSTRING(e.ecriture_date, 1, 7) as date,
            DATE_FORMAT(e.ecriture_date, '%m/%Y') as mois,
            CEILING(ABS(SUM(CASE WHEN e.debit > 0 THEN e.debit ELSE e.credit END))) as ca
        FROM fin_ecritures_fec e
        WHERE YEAR(e.ecriture_date) = $exercice 
          AND SUBSTRING(e.compte_num, 1, 1) = '7'
        GROUP BY SUBSTRING(e.ecriture_date, 1, 7)
        ORDER BY date
    ";
    $caMensuel = $db->query($caSQL)->fetchAll(PDO::FETCH_ASSOC);
    
    // CA Trimestriel
    $caTriSQL = "
        SELECT 
            CONCAT('T', QUARTER(e.ecriture_date)) as trimestre,
            CEILING(ABS(SUM(CASE WHEN e.debit > 0 THEN e.debit ELSE e.credit END))) as ca
        FROM fin_ecritures_fec e
        WHERE YEAR(e.ecriture_date) = $exercice 
          AND SUBSTRING(e.compte_num, 1, 1) = '7'
        GROUP BY QUARTER(e.ecriture_date)
        ORDER BY trimestre
    ";
    $caTri = $db->query($caTriSQL)->fetchAll(PDO::FETCH_ASSOC);
    
    // CA Total (valeur absolue)
    $caTotalResult = $db->query("
        SELECT ABS(SUM(CASE WHEN debit > 0 THEN debit ELSE credit END)) as ca_total
        FROM fin_ecritures_fec 
        WHERE YEAR(ecriture_date) = $exercice AND SUBSTRING(compte_num, 1, 1) = '7'
    ")->fetch(PDO::FETCH_ASSOC);
    $caTotal = (float)($caTotalResult['ca_total'] ?? 0);
    
    // ============================================
    // 2. STRUCTURE DES COÛTS
    // ============================================
    
    // Achats 601
    $achatsResult = $db->query("
        SELECT SUM(ABS(solde)) as montant FROM fin_balance 
        WHERE exercice = $exercice AND SUBSTRING(compte_num, 1, 3) = '601'
    ")->fetch(PDO::FETCH_ASSOC);
    $achats = (float)($achatsResult['montant'] ?? 0);
    
    // Variation stock 603
    $variationStockResult = $db->query("
        SELECT SUM(ABS(solde)) as montant FROM fin_balance 
        WHERE exercice = $exercice AND SUBSTRING(compte_num, 1, 3) = '603'
    ")->fetch(PDO::FETCH_ASSOC);
    $variationStock = (float)($variationStockResult['montant'] ?? 0);
    
    // Coût de la matière = achats + variation stock
    $coutMatiere = $achats + $variationStock;
    
    // Charges de personnel 641 + 645
    $salairesResult = $db->query("
        SELECT SUM(ABS(solde)) as montant FROM fin_balance 
        WHERE exercice = $exercice AND (SUBSTRING(compte_num, 1, 3) = '641' OR SUBSTRING(compte_num, 1, 3) = '645')
    ")->fetch(PDO::FETCH_ASSOC);
    $salaires = (float)($salairesResult['montant'] ?? 0);
    
    // Charges patronales 645
    $cotisationsResult = $db->query("
        SELECT SUM(ABS(solde)) as montant FROM fin_balance 
        WHERE exercice = $exercice AND SUBSTRING(compte_num, 1, 3) = '645'
    ")->fetch(PDO::FETCH_ASSOC);
    $cotisations = (float)($cotisationsResult['montant'] ?? 0);
    
    // Frais de transport 60
    $transportResult = $db->query("
        SELECT SUM(ABS(solde)) as montant FROM fin_balance 
        WHERE exercice = $exercice AND SUBSTRING(compte_num, 1, 3) = '606'
    ")->fetch(PDO::FETCH_ASSOC);
    $transport = (float)($transportResult['montant'] ?? 0);
    
    // Frais bancaires 627
    $fraisBancResult = $db->query("
        SELECT SUM(ABS(solde)) as montant FROM fin_balance 
        WHERE exercice = $exercice AND SUBSTRING(compte_num, 1, 3) = '627'
    ")->fetch(PDO::FETCH_ASSOC);
    $fraisBanc = (float)($fraisBancResult['montant'] ?? 0);
    
    // Autres frais généraux (61x, 62x, 63x, 64x) - excluant salaires et frais banc
    $autresFraisResult = $db->query("
        SELECT SUM(ABS(solde)) as montant FROM fin_balance 
        WHERE exercice = $exercice 
          AND SUBSTRING(compte_num, 1, 2) IN ('61', '62', '63', '64')
          AND SUBSTRING(compte_num, 1, 3) NOT IN ('641', '645', '627')
    ")->fetch(PDO::FETCH_ASSOC);
    $autresFrais = (float)($autresFraisResult['montant'] ?? 0);
    
    // Total charges d'exploitation
    $chargesExploitation = $coutMatiere + $salaires + $transport + $fraisBanc + $autresFrais;
    
    // ============================================
    // 3. TOP CLIENTS (Compte 41xxx UNIQUEMENT - vrais clients débiteurs)
    // Agréger par tiers depuis les écritures FEC pour avoir le détail
    // ============================================
    $clientsSQL = "
        SELECT 
            COALESCE(e.comp_aux_lib, 'Clients sans libellé') as client,
            '41xxx' as compte_num,
            CEILING(SUM(CASE 
                WHEN e.debit > 0 THEN e.debit 
                ELSE -e.credit 
            END)) as montant
        FROM fin_ecritures_fec e
        WHERE YEAR(e.ecriture_date) = $exercice 
          AND SUBSTRING(e.compte_num, 1, 2) = '41'
          AND (e.comp_aux_lib IS NOT NULL OR e.compte_num != '41000000')
        GROUP BY e.comp_aux_lib
        HAVING montant > 0
        ORDER BY montant DESC
        LIMIT 30
    ";
    $topClients = $db->query($clientsSQL)->fetchAll(PDO::FETCH_ASSOC);
    
    // Clients encours (total 41xx pour calculs)
    $clientsEncours = 0;
    foreach ($topClients as $client) {
        $clientsEncours += (float)$client['montant'];
    }
    
    // ============================================
    // 4. TOP FOURNISSEURS (Compte 40xxx UNIQUEMENT - vraies dettes d'achat)
    // Agréger par tiers depuis les écritures FEC pour avoir le détail
    // ============================================
    $fournisseursSQL = "
        SELECT 
            COALESCE(e.comp_aux_lib, 'Fournisseurs sans libellé') as fournisseur,
            '40xxx' as compte_num,
            CEILING(SUM(CASE 
                WHEN e.credit > 0 THEN e.credit 
                ELSE -e.debit 
            END)) as montant
        FROM fin_ecritures_fec e
        WHERE YEAR(e.ecriture_date) = $exercice 
          AND SUBSTRING(e.compte_num, 1, 2) = '40'
          AND (e.comp_aux_lib IS NOT NULL OR e.compte_num != '40000000')
        GROUP BY e.comp_aux_lib
        HAVING montant > 0
        ORDER BY montant DESC
        LIMIT 30
    ";
    $topFournisseurs = $db->query($fournisseursSQL)->fetchAll(PDO::FETCH_ASSOC);
    
    // Fournisseurs encours (total 40xx pour calculs)
    $fournisseursEncours = 0;
    foreach ($topFournisseurs as $fourn) {
        $fournisseursEncours += (float)$fourn['montant'];
    }
    
    // ============================================
    // 5. TRÉSORERIE & BILAN
    // ============================================
    
    // Actif circulant = Stock + Clients + Trésorerie (3, 4, 5)
    $actifCircResult = $db->query("
        SELECT SUM(ABS(solde)) as montant FROM fin_balance 
        WHERE exercice = $exercice AND SUBSTRING(compte_num, 1, 1) IN ('3', '4', '5') AND solde > 0
    ")->fetch(PDO::FETCH_ASSOC);
    $actifCirculant = (float)($actifCircResult['montant'] ?? 0);
    
    // Passif circulant = Dettes à court terme (40, 42, 43, 44, 45)
    $passifCircResult = $db->query("
        SELECT SUM(ABS(solde)) as montant FROM fin_balance 
        WHERE exercice = $exercice 
          AND SUBSTRING(compte_num, 1, 1) = '4'
          AND solde < 0
    ")->fetch(PDO::FETCH_ASSOC);
    $passifCirculant = (float)($passifCircResult['montant'] ?? 0);
    
    // Dettes financières (16x)
    $dettesFinResult = $db->query("
        SELECT SUM(ABS(solde)) as montant FROM fin_balance 
        WHERE exercice = $exercice AND SUBSTRING(compte_num, 1, 2) = '16' AND solde < 0
    ")->fetch(PDO::FETCH_ASSOC);
    $dettesFin = (float)($dettesFinResult['montant'] ?? 0);
    
    // Capitaux propres (10, 11, 12)
    $capPropresResult = $db->query("
        SELECT SUM(ABS(solde)) as montant FROM fin_balance 
        WHERE exercice = $exercice AND SUBSTRING(compte_num, 1, 2) IN ('10', '11', '12') AND solde < 0
    ")->fetch(PDO::FETCH_ASSOC);
    $capPropres = (float)($capPropresResult['montant'] ?? 0);
    
    // Trésorerie nette = (Actif 5 positif) - (Passif financier négatif)
    $tresorerieResult = $db->query("
        SELECT SUM(solde) as montant FROM fin_balance 
        WHERE exercice = $exercice AND SUBSTRING(compte_num, 1, 1) = '5'
    ")->fetch(PDO::FETCH_ASSOC);
    $tresorerie = (float)($tresorerieResult['montant'] ?? 0);
    
    // Besoin en Fonds de Roulement (BFR) = Stock + Clients - Fournisseurs
    $stockResult = $db->query("
        SELECT SUM(ABS(solde)) as montant FROM fin_balance 
        WHERE exercice = $exercice AND SUBSTRING(compte_num, 1, 2) = '31'
    ")->fetch(PDO::FETCH_ASSOC);
    $stock = (float)($stockResult['montant'] ?? 0);
    
    // BFR = Stock + Clients - Fournisseurs
    // Utiliser clientsEncours et fournisseursEncours déjà calculés plus haut
    $bfr = $stock + $clientsEncours - $fournisseursEncours;
    
    // ============================================
    // 6. KPIs RATIOS
    // ============================================
    
    $margeAchats = $caTotal - $coutMatiere;
    $ratioMargeAchats = $caTotal > 0 ? ($margeAchats / $caTotal * 100) : 0;
    
    $margeExploitation = $caTotal - $chargesExploitation;
    $ratioMargeExploitation = $caTotal > 0 ? ($margeExploitation / $caTotal * 100) : 0;
    
    $ratioAchats = $caTotal > 0 ? ($coutMatiere / $caTotal * 100) : 0;
    $ratioSalaires = $caTotal > 0 ? ($salaires / $caTotal * 100) : 0;
    $ratioFrais = $caTotal > 0 ? ($fraisBanc / $caTotal * 100) : 0;
    
    // Ratios de solvabilité
    $endettement = $capPropres != 0 ? ($dettesFin / $capPropres) : 0;
    $ratioLiquidite = $passifCirculant != 0 ? ($actifCirculant / $passifCirculant) : 0;
    $ratioAutonomie = ($capPropres + $dettesFin) != 0 ? ($capPropres / ($capPropres + $dettesFin) * 100) : 0;
    
    // DSO (Jours de vente en stock clients) = (Clients / CA) * 365
    $dso = $caTotal > 0 ? ($clientsEncours / $caTotal * 365) : 0;
    
    // DPO (Jours de paiement fournisseurs) = (Fournisseurs / Achats) * 365
    $dpo = $coutMatiere > 0 ? ($fournisseursEncours / $coutMatiere * 365) : 0;
    
    // Cycle de conversion = DSO + Jours de stock - DPO
    $joursStock = $coutMatiere > 0 ? ($stock / $coutMatiere * 365) : 0;
    $cycleConversion = $dso + $joursStock - $dpo;
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'exercice' => $exercice,
        
        // CA & Ventes
        'ca' => [
            'mensuel' => $caMensuel,
            'trimestriel' => $caTri,
            'total' => ceil($caTotal)
        ],
        
        // Coûts détaillés
        'couts' => [
            'matiere' => ceil($coutMatiere),
            'achats' => ceil($achats),
            'variation_stock' => ceil($variationStock),
            'salaires' => ceil($salaires),
            'cotisations' => ceil($cotisations),
            'transport' => ceil($transport),
            'frais_banc' => ceil($fraisBanc),
            'autres_frais' => ceil($autresFrais),
            'total_exploitation' => ceil($chargesExploitation)
        ],
        
        // Clients & Fournisseurs
        'top_clients' => $topClients,
        'top_fournisseurs' => $topFournisseurs,
        'clients_encours' => ceil($clientsEncours),
        'fournisseurs_encours' => ceil($fournisseursEncours),
        
        // Trésorerie & Bilan
        'tresorerie' => [
            'actif_circulant' => ceil($actifCirculant),
            'passif_circulant' => ceil($passifCirculant),
            'tresorerie_nette' => ceil($tresorerie),
            'dettes_financieres' => ceil($dettesFin),
            'capitaux_propres' => ceil($capPropres),
            'stock' => ceil($stock),
            'bfr' => ceil($bfr)
        ],
        
        // Marges & Ratios
        'marges' => [
            'marge_achats' => ceil($margeAchats),
            'ratio_marge_achats' => round($ratioMargeAchats, 2),
            'marge_exploitation' => ceil($margeExploitation),
            'ratio_marge_exploitation' => round($ratioMargeExploitation, 2)
        ],
        
        // Ratios d'exploitation
        'ratios_exploitation' => [
            'ratio_achats' => round($ratioAchats, 2),
            'ratio_salaires' => round($ratioSalaires, 2),
            'ratio_frais_banc' => round($ratioFrais, 2),
            'ratio_charge_total' => round(($chargesExploitation / $caTotal * 100), 2)
        ],
        
        // Ratios de solvabilité
        'ratios_solvabilite' => [
            'endettement' => round($endettement, 2),
            'ratio_liquidite' => round($ratioLiquidite, 2),
            'ratio_autonomie' => round($ratioAutonomie, 2)
        ],
        
        // Cycles de trésorerie
        'cycles_tresorerie' => [
            'dso_clients' => round($dso, 1),
            'jours_stock' => round($joursStock, 1),
            'dpo_fournisseurs' => round($dpo, 1),
            'cycle_conversion' => round($cycleConversion, 1)
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
