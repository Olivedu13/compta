<?php
/**
 * Diagnostic complet des KPIs
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$db = new PDO('sqlite:compta.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║          🔍 DIAGNOSTIC COMPLET DES KPIs                      ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// 1. Vérifier la structure DB
echo "1️⃣ STRUCTURE DE LA BASE DE DONNÉES:\n";
echo "─────────────────────────────────────────────────────────────\n";

$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
echo "   Tables: " . implode(", ", $tables) . "\n\n";

// 2. Vérifier les données
echo "2️⃣ DONNÉES DISPONIBLES:\n";
echo "─────────────────────────────────────────────────────────────\n";

$count = $db->query("SELECT COUNT(*) FROM ecritures WHERE exercice = 2024")->fetchColumn();
echo "   Écritures 2024: $count\n";

$years = $db->query("SELECT DISTINCT exercice FROM ecritures ORDER BY exercice")->fetchAll(PDO::FETCH_COLUMN);
echo "   Années disponibles: " . implode(", ", $years) . "\n\n";

// 3. Vérifier les comptes disponibles
echo "3️⃣ COMPTES UTILISÉS (2024):\n";
echo "─────────────────────────────────────────────────────────────\n";

$accounts = $db->query("
    SELECT 
        compte_num,
        COUNT(*) as nb,
        SUM(debit) as debit,
        SUM(credit) as credit
    FROM ecritures
    WHERE exercice = 2024
    GROUP BY compte_num
    ORDER BY compte_num
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($accounts as $acc) {
    printf("   %s: D=%.2f C=%.2f (%d écr.)\n", 
        $acc['compte_num'], 
        $acc['debit'], 
        $acc['credit'], 
        $acc['nb']
    );
}

// 4. Vérifier si des tables d'analyse existent
echo "\n4️⃣ TABLES D'ANALYSE:\n";
echo "─────────────────────────────────────────────────────────────\n";

$analysis_tables = ['fin_balance', 'client_sales', 'monthly_sales', 'product_sales'];
foreach ($analysis_tables as $table) {
    try {
        $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "   ✅ $table: $count lignes\n";
    } catch (Exception $e) {
        echo "   ❌ $table: n'existe pas\n";
    }
}

// 5. Analyser les endpoints
echo "\n5️⃣ ENDPOINTS DISPONIBLES:\n";
echo "─────────────────────────────────────────────────────────────\n";

$api_files = [
    'public_html/api/v1/kpis/detailed.php' => 'KPIs détaillés',
    'public_html/api/v1/analytics/kpis.php' => 'Analytics KPIs',
    'public_html/api/v1/analytics/analysis.php' => 'Analyses',
    'public_html/api/v1/analytics/advanced.php' => 'Analyses avancées',
    'public_html/api/v1/balance/simple.php' => 'Balance simple'
];

foreach ($api_files as $file => $desc) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $isErrorHandler = strpos($content, 'catch') !== false || strpos($content, 'try') !== false;
        echo "   ✅ $file ($desc)\n";
        
        // Détecte les fonctions/classes
        preg_match_all('/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $func) {
                echo "      └─ $func()\n";
            }
        }
    } else {
        echo "   ❌ $file (n'existe pas)\n";
    }
}

// 6. Test des calculs basiques
echo "\n6️⃣ CALCULS DE BASE:\n";
echo "─────────────────────────────────────────────────────────────\n";

// Stocks
$stocks = $db->query("
    SELECT SUM(debit) as total FROM ecritures 
    WHERE exercice = 2024 AND compte_num IN ('311', '312', '313')
")->fetch(PDO::FETCH_ASSOC);
echo "   Stocks (31X): " . number_format($stocks['total'] ?? 0, 2, ',', ' ') . " EUR\n";

// CA
$ca = $db->query("
    SELECT SUM(credit) as total FROM ecritures 
    WHERE exercice = 2024 AND compte_num IN ('701', '702', '703')
")->fetch(PDO::FETCH_ASSOC);
echo "   CA (70X): " . number_format($ca['total'] ?? 0, 2, ',', ' ') . " EUR\n";

// Clients
$clients = $db->query("
    SELECT (SUM(CASE WHEN compte_num='411' THEN debit ELSE 0 END) - 
            SUM(CASE WHEN compte_num='411' THEN credit ELSE 0 END)) as total 
    FROM ecritures 
    WHERE exercice = 2024
")->fetch(PDO::FETCH_ASSOC);
echo "   Clients (411): " . number_format($clients['total'] ?? 0, 2, ',', ' ') . " EUR\n";

echo "\n✅ Diagnostic terminé\n\n";
