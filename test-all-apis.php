<?php
/**
 * Test complet de TOUS les endpoints API
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$projectRoot = '.';
$dbPath = $projectRoot . '/compta.db';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║          🧪 TEST COMPLET DE TOUS LES ENDPOINTS API           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Test des fichiers PHP
$endpoints = [
    'public_html/api/v1/kpis/detailed.php',
    'public_html/api/v1/analytics/kpis.php',
    'public_html/api/v1/analytics/analysis.php',
    'public_html/api/v1/analytics/advanced.php',
    'public_html/api/v1/balance/simple.php',
    'backend/services/SigCalculator.php'
];

echo "📁 VÉRIFICATION DES FICHIERS:\n";
echo "─────────────────────────────────────────────────────────────\n";

foreach ($endpoints as $file) {
    $exists = file_exists($file);
    $icon = $exists ? '✅' : '❌';
    echo "$icon $file\n";
    
    if ($exists) {
        $lines = count(file($file));
        echo "   └─ $lines lignes\n";
    }
}

echo "\n\n📊 TEST DES FONCTIONS:\n";
echo "─────────────────────────────────────────────────────────────\n";

// Charge le SigCalculator
require_once 'backend/services/SigCalculator.php';

if (class_exists('SigCalculator')) {
    echo "✅ SigCalculator chargé\n";
    
    $sig = new SigCalculator($db);
    $methods = get_class_methods($sig);
    
    echo "\n   Méthodes disponibles:\n";
    foreach ($methods as $method) {
        if (!str_starts_with($method, '_')) {
            echo "   ├─ $method()\n";
        }
    }
} else {
    echo "❌ SigCalculator non trouvé\n";
}

echo "\n\n🧪 TESTS DES CALCULS:\n";
echo "─────────────────────────────────────────────────────────────\n";

try {
    $sig = new SigCalculator($db);
    
    // Test KPIs basiques
    echo "1️⃣ calculKPIs():\n";
    try {
        $kpis = $sig->calculKPIs(2024);
        echo "   ✅ Fonctionne\n";
        if (is_array($kpis)) {
            foreach ($kpis as $key => $val) {
                echo "      - $key: " . ($val !== null ? $val : 'null') . "\n";
            }
        }
    } catch (Exception $e) {
        echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    }
    
    // Test Cashflow
    echo "\n2️⃣ calculCashFlow():\n";
    try {
        $cashflow = $sig->calculCashFlow(2024);
        echo "   ✅ Fonctionne\n";
        if (is_array($cashflow)) {
            foreach ($cashflow as $key => $val) {
                if (!is_array($val)) {
                    echo "      - $key: " . ($val !== null ? $val : 'null') . "\n";
                }
            }
        }
    } catch (Exception $e) {
        echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    }
    
    // Test CA Saisonnalité
    echo "\n3️⃣ analyzeCABySeason():\n";
    try {
        if (method_exists($sig, 'analyzeCABySeason')) {
            $season = $sig->analyzeCABySeason(2024);
            echo "   ✅ Fonctionne\n";
        } else {
            echo "   ❌ Méthode non trouvée\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    }
    
    // Test Top Clients
    echo "\n4️⃣ getTopClients():\n";
    try {
        if (method_exists($sig, 'getTopClients')) {
            $top = $sig->getTopClients(2024, 10);
            echo "   ✅ Fonctionne\n";
            echo "      Clients trouvés: " . count($top) . "\n";
        } else {
            echo "   ❌ Méthode non trouvée\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n\n✅ Test complet terminé\n\n";
