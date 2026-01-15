<?php
/**
 * TEST IMPORT RÉEL - FEC 2024
 * Teste l'import complet et valide les données en base
 */

// Détermine le répertoire racine (parent du backend)
$backendDir = dirname(__FILE__);
$rootDir = dirname($backendDir);

// Inclut les fichiers de configuration
require_once $backendDir . '/config/Database.php';
require_once $backendDir . '/config/Logger.php';
require_once $backendDir . '/services/ImportService.php';

use App\Config\Database;
use App\Config\Logger;
use App\Services\ImportService;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  TEST IMPORT RÉEL - FEC 2024.txt → Base de Données       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

try {
    // Initialise les services
    $db = Database::getInstance();
    $import = new ImportService();
    $fecFile = $rootDir . '/fec_2024.txt';
    
    if (!file_exists($fecFile)) {
        echo "❌ Fichier FEC non trouvé: $fecFile\n";
        exit(1);
    }
    
    // ÉTAPE 1: Analyse préalable
    echo "📊 ÉTAPE 1: Analyse FEC\n";
    echo "─────────────────────────────────────────────────────\n";
    
    $analysis = $import->analyzeFEC($fecFile);
    echo "✓ Écritures détectées: " . number_format($analysis['total_rows']) . "\n";
    echo "✓ Période: " . $analysis['date_min'] . " à " . $analysis['date_max'] . "\n";
    echo "✓ Journaux: " . implode(', ', $analysis['journals']) . "\n";
    
    // ÉTAPE 2: Vide la table fin_ecritures (pour partir de 0)
    echo "\n🗑️  ÉTAPE 2: Préparation base de données\n";
    echo "─────────────────────────────────────────────────────\n";
    
    $db->query("TRUNCATE TABLE fin_ecritures");
    echo "✓ Table fin_ecritures vidée\n";
    
    // ÉTAPE 3: Import réel
    echo "\n📥 ÉTAPE 3: Import FEC en cours...\n";
    echo "─────────────────────────────────────────────────────\n";
    
    $startTime = microtime(true);
    $result = $import->importFEC($fecFile);
    $duration = microtime(true) - $startTime;
    
    echo "✓ Écritures importées: " . number_format($result['count']) . "\n";
    echo "✓ Temps: " . number_format($duration, 2) . "s\n";
    
    if (!empty($result['errors'])) {
        echo "⚠️  Erreurs: " . count($result['errors']) . "\n";
        foreach (array_slice($result['errors'], 0, 5) as $err) {
            echo "   - " . substr($err, 0, 80) . "...\n";
        }
    }
    
    // ÉTAPE 4: Validation données
    echo "\n✔️  ÉTAPE 4: Validation données importées\n";
    echo "─────────────────────────────────────────────────────\n";
    
    // Total écritures
    $countRow = $db->queryRow("SELECT COUNT(*) as cnt FROM fin_ecritures");
    $totalCount = $countRow['cnt'];
    echo "✓ Total BD: " . number_format($totalCount) . " écritures\n";
    
    // Montant total
    $montantRow = $db->queryRow("
        SELECT 
            SUM(debit) as debit_total,
            SUM(credit) as credit_total
        FROM fin_ecritures
    ");
    $debitTotal = (float)($montantRow['debit_total'] ?? 0);
    $creditTotal = (float)($montantRow['credit_total'] ?? 0);
    echo "✓ Débits: €" . number_format($debitTotal, 2) . "\n";
    echo "✓ Crédits: €" . number_format($creditTotal, 2) . "\n";
    
    $balance = $debitTotal - $creditTotal;
    echo "✓ Balance: €" . number_format($balance, 2) . " (doit être ≈0)\n";
    
    // Tiers
    $tiersRow = $db->queryRow("
        SELECT COUNT(DISTINCT comp_aux_num) as tiers_count
        FROM fin_ecritures
        WHERE comp_aux_num IS NOT NULL AND comp_aux_num != ''
    ");
    echo "✓ Tiers identifiés: " . number_format($tiersRow['tiers_count']) . "\n";
    
    // Lettrage
    $lettRow = $db->queryRow("
        SELECT 
            COUNT(*) as lettres,
            COUNT(DISTINCT ecriture_let) as codes_lettrage
        FROM fin_ecritures
        WHERE ecriture_let IS NOT NULL AND ecriture_let != ''
    ");
    echo "✓ Écritures lettrées: " . number_format($lettRow['lettres']) . "\n";
    echo "✓ Codes de lettrage: " . number_format($lettRow['codes_lettrage']) . "\n";
    
    // Dates lettrage
    $dateLetRow = $db->queryRow("
        SELECT COUNT(*) as date_let_count
        FROM fin_ecritures
        WHERE date_let IS NOT NULL
    ");
    echo "✓ Dates de lettrage: " . number_format($dateLetRow['date_let_count']) . "\n";
    
    // Journaux
    echo "\n📋 JOURNAUX:\n";
    $journaux = $db->query("
        SELECT journal_code, COUNT(*) as cnt
        FROM fin_ecritures
        GROUP BY journal_code
        ORDER BY journal_code
    ");
    foreach ($journaux as $j) {
        echo sprintf("  %-3s: %7d écritures\n", $j['journal_code'], $j['cnt']);
    }
    
    // Top tiers
    echo "\n👥 TOP 10 TIERS:\n";
    $tiers = $db->query("
        SELECT comp_aux_num, comp_aux_lib, COUNT(*) as cnt
        FROM fin_ecritures
        WHERE comp_aux_num IS NOT NULL AND comp_aux_num != ''
        GROUP BY comp_aux_num
        ORDER BY cnt DESC
        LIMIT 10
    ");
    $i = 1;
    foreach ($tiers as $t) {
        echo sprintf(
            "  %2d. %s: %d écritures\n",
            $i++,
            substr($t['comp_aux_lib'] ?: $t['comp_aux_num'], 0, 35),
            $t['cnt']
        );
    }
    
    // Comptes
    echo "\n💰 TOP 10 COMPTES:\n";
    $comptes = $db->query("
        SELECT compte_num, compte_lib, COUNT(*) as cnt, SUM(debit+credit) as montant
        FROM fin_ecritures
        GROUP BY compte_num
        ORDER BY cnt DESC
        LIMIT 10
    ");
    $i = 1;
    foreach ($comptes as $c) {
        echo sprintf(
            "  %2d. %s: %d écritures (€%s)\n",
            $i++,
            substr($c['compte_lib'] ?: $c['compte_num'], 0, 25),
            $c['cnt'],
            number_format($c['montant'], 0)
        );
    }
    
    // VERDICT FINAL
    echo "\n═══════════════════════════════════════════════════════\n";
    echo "✨ VERDICT FINAL\n";
    echo "═══════════════════════════════════════════════════════\n";
    
    $verdict = [];
    $ok = true;
    
    if ($result['success']) {
        $verdict[] = "✅ Import réussi";
    } else {
        $verdict[] = "❌ Échec import";
        $ok = false;
    }
    
    if ($totalCount > 10000) {
        $verdict[] = "✅ Données importées (" . number_format($totalCount) . ")";
    } else {
        $verdict[] = "⚠️  Peu de données (" . number_format($totalCount) . ")";
    }
    
    if (abs($balance) < 100) { // Tolérance 100€
        $verdict[] = "✅ Balance correcte (€" . number_format($balance, 2) . ")";
    } else {
        $verdict[] = "❌ Balance incorrecte (€" . number_format($balance, 2) . ")";
        $ok = false;
    }
    
    if ($tiersRow['tiers_count'] > 100) {
        $verdict[] = "✅ Tiers présents";
    } else {
        $verdict[] = "⚠️  Peu de tiers";
    }
    
    if ($lettRow['lettres'] > 0) {
        $verdict[] = "✅ Lettrage présent";
    } else {
        $verdict[] = "⚠️  Lettrage absent";
    }
    
    foreach ($verdict as $v) {
        echo $v . "\n";
    }
    
    echo "\n→ STATUS: " . ($ok ? "✅ PHASE 3 POSSIBLE" : "❌ À CORRIGER") . "\n";
    echo "═══════════════════════════════════════════════════════\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
    exit(1);
}
