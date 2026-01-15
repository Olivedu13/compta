<?php
/**
 * Script de test d'import FEC
 * Teste l'import complet du fichier fec_2024_atc.txt
 * Vérifie la capture de CompAuxNum, DateLet, EcritureLet, etc.
 */

// Simple logger pour test
class TestLogger {
    public static function info($msg, $data = []) {
        echo $msg . "\n";
        if ($data) {
            print_r($data);
        }
    }
    public static function warning($msg, $data = []) {
        echo "⚠️  $msg\n";
    }
    public static function error($msg, $data = []) {
        echo "❌ $msg\n";
    }
}

// Configuration test
$fecFile = __DIR__ . '/../../fec_2024_atc.txt';
$test_start = microtime(true);

TestLogger::info("╔════════════════════════════════════════════════════════════╗");
TestLogger::info("║     TEST IMPORT FEC - PHASE 1 BACKEND DATA LAYER        ║");
TestLogger::info("╚════════════════════════════════════════════════════════════╝");

if (!file_exists($fecFile)) {
    TestLogger::error("❌ Fichier FEC non trouvé: $fecFile");
    exit(1);
}

// Étape 1: Analyse préalable du FEC
TestLogger::info("\n📊 ÉTAPE 1: Analyse du fichier FEC");
TestLogger::info("─────────────────────────────────────────────────────");

$lines = file($fecFile, FILE_SKIP_EMPTY_LINES);
Logger::info("✓ Fichier chargé en mémoire");
Logger::info("  - Nombre de lignes: " . count($lines));
Logger::info("  - Taille fichier: " . number_format(filesize($fecFile), 0) . " octets");

// Détecte le séparateur
$headerLine = trim($lines[0]);
$headerTab = str_getcsv($headerLine, "\t");
$headerPipe = str_getcsv($headerLine, "|");
$separator = count($headerTab) > count($headerPipe) ? "\t" : "|";
Logger::info("✓ Séparateur détecté: " . ($separator === "\t" ? "TAB" : "PIPE"));

// Parse l'en-tête
$headers = array_map(fn($h) => trim(strtolower($h)), str_getcsv(trim($lines[0]), $separator));
Logger::info("✓ En-tête parsé avec " . count($headers) . " colonnes");
Logger::info("  Colonnes: " . implode(", ", $headers));

// Vérifie les colonnes clés pour Phase 1
$required_cols = ['journalcode', 'ecrituredate', 'comptenum', 'compauxnum', 'compauxlib', 'datelet', 'ecriturelet', 'debit', 'credit'];
$missing = [];
foreach ($required_cols as $col) {
    if (!in_array($col, $headers)) {
        $missing[] = $col;
    }
}

if (!empty($missing)) {
    Logger::warning("⚠️  Colonnes requises manquantes: " . implode(", ", $missing));
} else {
    Logger::info("✓ Toutes les colonnes requises présentes");
}

// Étape 2: Analyse des données (premier batch)
Logger::info("\n📈 ÉTAPE 2: Analyse des données (premiers 100 enregistrements)");
Logger::info("─────────────────────────────────────────────────────");

$sampleData = [];
$tiers_avec_nom = 0;
$tiers_date_let = 0;
$tiers_letres = 0;
$journaux_uniques = [];
$comptes_uniques = [];
$tiers_uniques = [];

for ($i = 1; $i < min(101, count($lines)); $i++) {
    $line = trim($lines[$i]);
    if (empty($line)) continue;
    
    $fields = str_getcsv($line, $separator);
    $row = array_combine($headers, $fields);
    
    // Normalise
    $row = array_map('trim', $row);
    
    if ($i <= 5) {
        $sampleData[] = $row;
    }
    
    // Collecte des statistiques
    $journaux_uniques[$row['journalcode']] = true;
    $comptes_uniques[$row['comptenum']] = $row['comptelib'] ?? '';
    
    if (!empty($row['compauxnum'])) {
        $tiers_uniques[$row['compauxnum']] = $row['compauxlib'] ?? '';
        $tiers_avec_nom++;
    }
    
    if (!empty($row['datelet'])) {
        $tiers_date_let++;
    }
    
    if (!empty($row['ecriturelet'])) {
        $tiers_letres++;
    }
}

Logger::info("✓ Journaux uniques: " . implode(", ", array_keys($journaux_uniques)));
Logger::info("✓ Comptes uniques (premier batch): " . count($comptes_uniques));
Logger::info("✓ Tiers avec nom (CompAuxLib): " . $tiers_avec_nom . "%");
Logger::info("✓ Écritures avec DateLet: " . $tiers_date_let . "%");
Logger::info("✓ Écritures lettrées: " . $tiers_letres . "%");

// Affiche un exemple d'enregistrement complet
if (!empty($sampleData)) {
    Logger::info("\n📋 EXEMPLE D'ENREGISTREMENT:");
    Logger::info("─────────────────────────────────────────────────────");
    $sample = $sampleData[0];
    foreach ($sample as $key => $val) {
        Logger::info("  $key: " . ($val ? substr($val, 0, 50) : '(vide)'));
    }
}

// Étape 3: Import réel
Logger::info("\n💾 ÉTAPE 3: Import FEC dans la base de données");
Logger::info("─────────────────────────────────────────────────────");

try {
    $importService = new ImportService();
    $result = $importService->importFEC($fecFile);
    
    Logger::info("✓ Import réussi!");
    Logger::info("  - Écritures importées: " . $result['count']);
    Logger::info("  - Erreurs: " . $result['errors']);
    Logger::info("  - Comptes créés: " . $result['accounts_created']);
    Logger::info("  - Message: " . $result['message']);
    
} catch (\Exception $e) {
    Logger::error("❌ Erreur lors de l'import: " . $e->getMessage());
    exit(1);
}

// Étape 4: Vérification des données importées
Logger::info("\n🔍 ÉTAPE 4: Vérification des données importées");
Logger::info("─────────────────────────────────────────────────────");

try {
    $db = Database::getInstance();
    
    // Statistiques brutes
    $totalRows = $db->query(
        "SELECT COUNT(*) as cnt FROM fin_ecritures_fec",
        []
    )[0]['cnt'];
    Logger::info("✓ Total écritures dans DB: " . $totalRows);
    
    // Vérification CompAuxNum
    $auxNum = $db->query(
        "SELECT COUNT(*) as cnt FROM fin_ecritures_fec WHERE comp_aux_num IS NOT NULL AND comp_aux_num != ''",
        []
    )[0]['cnt'];
    Logger::info("✓ Écritures avec CompAuxNum: " . $auxNum . " (" . round(100 * $auxNum / $totalRows, 1) . "%)");
    
    // Vérification DateLet
    $dateLet = $db->query(
        "SELECT COUNT(*) as cnt FROM fin_ecritures_fec WHERE date_let IS NOT NULL",
        []
    )[0]['cnt'];
    Logger::info("✓ Écritures avec DateLet: " . $dateLet . " (" . round(100 * $dateLet / $totalRows, 1) . "%)");
    
    // Vérification EcritureLet
    $ecritLet = $db->query(
        "SELECT COUNT(*) as cnt FROM fin_ecritures_fec WHERE ecriture_let IS NOT NULL AND ecriture_let != ''",
        []
    )[0]['cnt'];
    Logger::info("✓ Écritures lettrées: " . $ecritLet . " (" . round(100 * $ecritLet / $totalRows, 1) . "%)");
    
    // Top 10 tiers
    Logger::info("\n📊 TOP 10 TIERS (par montant):");
    $topTiers = $db->query("
        SELECT 
            comp_aux_num,
            comp_aux_lib,
            COUNT(*) as nb_ecritures,
            ROUND(SUM(COALESCE(debit, 0)) + SUM(COALESCE(credit, 0)), 2) as montant_total
        FROM fin_ecritures_fec
        WHERE comp_aux_num IS NOT NULL AND comp_aux_num != ''
        GROUP BY comp_aux_num, comp_aux_lib
        ORDER BY montant_total DESC
        LIMIT 10
    ", []);
    
    foreach ($topTiers as $tier) {
        Logger::info(sprintf(
            "  %s (%s): %d écritures, montant: €%.2f",
            $tier['comp_aux_lib'] ?? $tier['comp_aux_num'],
            $tier['comp_aux_num'],
            $tier['nb_ecritures'],
            $tier['montant_total']
        ));
    }
    
    // Distributions des dates
    Logger::info("\n📅 DISTRIBUTION DATES:");
    $dateStats = $db->query("
        SELECT 
            COUNT(*) as total,
            MIN(ecriture_date) as date_min,
            MAX(ecriture_date) as date_max,
            COUNT(DISTINCT ecriture_date) as dates_uniques
        FROM fin_ecritures_fec
    ", [])[0];
    Logger::info("  Plage de dates: " . $dateStats['date_min'] . " à " . $dateStats['date_max']);
    Logger::info("  Dates uniques: " . $dateStats['dates_uniques']);
    
} catch (\Exception $e) {
    Logger::error("❌ Erreur vérification: " . $e->getMessage());
    exit(1);
}

// Performance
$test_end = microtime(true);
$duration = round(($test_end - $test_start) * 1000, 2);

Logger::info("\n✨ RÉSUMÉ");
Logger::info("═══════════════════════════════════════════════════════");
Logger::info("✅ Import FEC PHASE 1 terminé avec succès!");
Logger::info("   Durée: " . $duration . "ms");
Logger::info("   Status: PRÊT POUR PHASE 2 (Calculations)");
Logger::info("═══════════════════════════════════════════════════════\n");
