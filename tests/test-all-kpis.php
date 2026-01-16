<?php
/**
 * Test unitaire - Vérification de tous les KPIs
 * Valide chaque calcul KPI contre les données FEC réelles
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$projectRoot = dirname(dirname(__FILE__));
$dbPath = $projectRoot . '/compta.db';

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║             🧪 TEST UNITAIRE - KPIs                           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$exercice = 2024;
$testsPassed = 0;
$testsFailed = 0;

function getCompteBalance($db, $compte, $exercice) {
    $stmt = $db->prepare("
        SELECT 
            SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END) as total_debit,
            SUM(CASE WHEN credit > 0 THEN credit ELSE 0 END) as total_credit
        FROM ecritures
        WHERE compte_num = ? AND exercice = ?
    ");
    $stmt->execute([$compte, $exercice]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return ($result['total_debit'] ?? 0) - ($result['total_credit'] ?? 0);
}

function testKPI($name, $expected, $actual, &$passed, &$failed) {
    $isPass = abs($expected - $actual) < 0.01;
    $icon = $isPass ? '✅' : '❌';
    $status = $isPass ? 'PASS' : 'FAIL';
    
    echo "$icon [$status] $name\n";
    echo "       Attendu: " . number_format($expected, 2, '.', ' ') . " EUR\n";
    echo "       Réel:    " . number_format($actual, 2, '.', ' ') . " EUR\n";
    
    if (!$isPass) {
        echo "       ⚠️  DIFFÉRENCE: " . number_format(abs($expected - $actual), 2, '.', ' ') . " EUR\n";
        $failed++;
    } else {
        $passed++;
    }
    echo "\n";
}

// ================================================================
// KPI #1: STOCKS
// ================================================================
echo "📦 KPI #1: STOCKS\n";
echo "─────────────────────────────────────────────────────────────\n\n";

$stockOr = abs(getCompteBalance($db, '311', $exercice));
$stockDiamants = abs(getCompteBalance($db, '312', $exercice));
$stockBijoux = abs(getCompteBalance($db, '313', $exercice));
$stockTotal = $stockOr + $stockDiamants + $stockBijoux;

testKPI("Stock Or (311)", 0, $stockOr, $testsPassed, $testsFailed);
testKPI("Stock Diamants (312)", 0, $stockDiamants, $testsPassed, $testsFailed);
testKPI("Stock Bijoux (313)", 0, $stockBijoux, $testsPassed, $testsFailed);
testKPI("Stock TOTAL", 0, $stockTotal, $testsPassed, $testsFailed);

// ================================================================
// KPI #2: TRÉSORERIE
// ================================================================
echo "💰 KPI #2: TRÉSORERIE\n";
echo "─────────────────────────────────────────────────────────────\n\n";

$banque = abs(getCompteBalance($db, '512', $exercice));
$caisse = abs(getCompteBalance($db, '530', $exercice));
$tresorerieTotal = $banque + $caisse;

testKPI("Banque (512)", 2500, $banque, $testsPassed, $testsFailed);
testKPI("Caisse (530)", 0, $caisse, $testsPassed, $testsFailed);
testKPI("Trésorerie TOTAL", 2500, $tresorerieTotal, $testsPassed, $testsFailed);

// ================================================================
// KPI #3: CLIENTS
// ================================================================
echo "👥 KPI #3: CLIENTS\n";
echo "─────────────────────────────────────────────────────────────\n\n";

$clients = abs(getCompteBalance($db, '411', $exercice));
testKPI("Clients (411)", 2500, $clients, $testsPassed, $testsFailed);

// ================================================================
// KPI #4: FOURNISSEURS
// ================================================================
echo "🏭 KPI #4: FOURNISSEURS\n";
echo "─────────────────────────────────────────────────────────────\n\n";

$fournisseurs = abs(getCompteBalance($db, '401', $exercice));
testKPI("Fournisseurs (401)", 1500, $fournisseurs, $testsPassed, $testsFailed);

// ================================================================
// KPI #5: DETTES COURT TERME
// ================================================================
echo "📊 KPI #5: DETTES COURT TERME\n";
echo "─────────────────────────────────────────────────────────────\n\n";

$dettesChortTerme = abs(getCompteBalance($db, '164', $exercice));
testKPI("Dettes Court Terme (164)", 0, $dettesChortTerme, $testsPassed, $testsFailed);

// ================================================================
// KPI #6: CHIFFRE D'AFFAIRES
// ================================================================
echo "📈 KPI #6: CHIFFRE D'AFFAIRES\n";
echo "─────────────────────────────────────────────────────────────\n\n";

$ca701 = abs(getCompteBalance($db, '701', $exercice));
$ca702 = abs(getCompteBalance($db, '702', $exercice));
$ca703 = abs(getCompteBalance($db, '703', $exercice));
$chiffreAffaires = $ca701 + $ca702 + $ca703;

echo "Détails:\n";
echo "  - Compte 701: " . number_format($ca701, 2, '.', ' ') . " EUR\n";
echo "  - Compte 702: " . number_format($ca702, 2, '.', ' ') . " EUR\n";
echo "  - Compte 703: " . number_format($ca703, 2, '.', ' ') . " EUR\n";
echo "\n";

testKPI("Chiffre d'Affaires", 2500, $chiffreAffaires, $testsPassed, $testsFailed);

// ================================================================
// KPI #7: VÉRIFICATIONS D'ÉQUILIBRE
// ================================================================
echo "⚖️ KPI #7: VÉRIFICATIONS D'ÉQUILIBRE\n";
echo "─────────────────────────────────────────────────────────────\n\n";

$stmt = $db->query("
    SELECT 
        SUM(debit) as total_debit,
        SUM(credit) as total_credit
    FROM ecritures
    WHERE exercice = $exercice
");
$balance = $stmt->fetch(PDO::FETCH_ASSOC);

testKPI("Balance Débits = Crédits", 
    $balance['total_debit'], 
    $balance['total_credit'], 
    $testsPassed, 
    $testsFailed
);

// ================================================================
// KPI #8: RATIOS
// ================================================================
echo "📊 KPI #8: RATIOS ET CALCULS DÉRIVÉS\n";
echo "─────────────────────────────────────────────────────────────\n\n";

// Taux marge = (CA - Coûts) / CA * 100
// Coûts estimés = achats (compte 6xx)
$stmt = $db->prepare("
    SELECT SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END) as couts
    FROM ecritures
    WHERE compte_num LIKE '6%' AND exercice = ?
");
$stmt->execute([$exercice]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$couts = $result['couts'] ?? 0;

$margeProduction = $chiffreAffaires - $couts;
$tauxMargeProduction = $chiffreAffaires != 0 ? ($margeProduction / $chiffreAffaires) * 100 : 0;

echo "📌 Calculs intermédiaires:\n";
echo "  - CA: " . number_format($chiffreAffaires, 2, '.', ' ') . " EUR\n";
echo "  - Coûts (compte 6): " . number_format($couts, 2, '.', ' ') . " EUR\n";
echo "  - Marge: " . number_format($margeProduction, 2, '.', ' ') . " EUR\n";
echo "  - Taux marge: " . number_format($tauxMargeProduction, 2, ',', ' ') . "%\n\n";

// Taux attendu: (2500 - 1500) / 2500 * 100 = 40%
testKPI("Taux Marge Production", 40, $tauxMargeProduction, $testsPassed, $testsFailed);

// ================================================================
// RÉSUMÉ
// ================================================================
echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║                    📋 RÉSUMÉ DES TESTS                        ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$total = $testsPassed + $testsFailed;
$percentage = $total > 0 ? ($testsPassed / $total) * 100 : 0;

echo "Tests réussis:   ✅ $testsPassed/$total\n";
echo "Tests échoués:   ❌ $testsFailed/$total\n";
echo "Score:           " . number_format($percentage, 1, ',', ' ') . "%\n\n";

if ($testsFailed === 0) {
    echo "🎉 TOUS LES TESTS RÉUSSIS!\n";
} else {
    echo "⚠️  $testsFailed test(s) à corriger.\n";
    echo "   Vérifiez les données FEC et les calculs du SigCalculator.\n";
}

echo "\n";
?>
