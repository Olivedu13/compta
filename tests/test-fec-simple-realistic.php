<?php
/**
 * Import FEC simple et réaliste + vérification KPI complète
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$projectRoot = dirname(dirname(__FILE__));
$dbPath = $projectRoot . '/compta.db';
$fecPath = $projectRoot . '/tests/fixtures/fec-simple-realistic-2024.txt';

if (!file_exists($fecPath)) {
    die("❌ FEC not found: $fecPath\n");
}

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║          📥 IMPORT FEC SIMPLE RÉALISTE                        ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// 1. Supprimer les écritures existantes de 2024
$db->exec("DELETE FROM ecritures WHERE exercice = 2024");

// 2. Importer le FEC
echo "📥 Importation du FEC...\n";
$handle = fopen($fecPath, 'r');
$headers = fgetcsv($handle, 0, "\t");

$imported = 0;
while (($line = fgetcsv($handle, 0, "\t")) !== false) {
    if (empty($line[0])) continue;
    
    $data = array_combine($headers, $line);
    
    $stmt = $db->prepare("
        INSERT INTO ecritures (
            exercice, journal_code, ecriture_num, ecriture_date,
            compte_num, compte_lib, debit, credit, libelle_ecriture, piece_ref, date_piece
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        2024,
        $data['JournalCode'],
        $data['EcritureNum'],
        $data['EcritureDate'],
        $data['CompteNum'],
        $data['CompteLib'],
        (float)($data['Debit'] ?? 0),
        (float)($data['Credit'] ?? 0),
        $data['EcritureLib'],
        $data['PieceRef'] ?? '',
        $data['PieceDate'] ?? ''
    ]);
    
    $imported++;
}
fclose($handle);

echo "   ✅ $imported écritures importées\n\n";

// 3. Vérifier l'équilibre
$result = $db->query("
    SELECT 
        SUM(debit) as total_debit,
        SUM(credit) as total_credit
    FROM ecritures
    WHERE exercice = 2024
")->fetch(PDO::FETCH_ASSOC);

$totalDebit = (float)($result['total_debit'] ?? 0);
$totalCredit = (float)($result['total_credit'] ?? 0);

echo "⚖️  Équilibre: " . number_format($totalDebit, 2, ',', ' ') . " = " . number_format($totalCredit, 2, ',', ' ');
if (abs($totalDebit - $totalCredit) < 0.01) {
    echo " ✅\n\n";
} else {
    echo " ❌\n\n";
}

// 4. Fonction pour calculer le balance
function getBalance($db, $compte, $exercice) {
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

// 5. KPIs
echo "📊 VÉRIFICATION DES KPIs:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$kpis = [];

// Stocks
$kpis['stocks'] = [
    '311' => abs(getBalance($db, '311', 2024)),
    '312' => abs(getBalance($db, '312', 2024)),
    '313' => abs(getBalance($db, '313', 2024))
];
$kpis['stocks']['TOTAL'] = $kpis['stocks']['311'] + $kpis['stocks']['312'] + $kpis['stocks']['313'];

echo "📦 STOCKS (actif immobilisé)\n";
echo "   Or (311):       " . number_format($kpis['stocks']['311'], 2, ',', ' ') . " EUR (attendu: 10 000)\n";
echo "   Diamants (312): " . number_format($kpis['stocks']['312'], 2, ',', ' ') . " EUR (attendu: 5 000)\n";
echo "   Bijoux (313):   " . number_format($kpis['stocks']['313'], 2, ',', ' ') . " EUR (attendu: 2 000)\n";
echo "   TOTAL:          " . number_format($kpis['stocks']['TOTAL'], 2, ',', ' ') . " EUR (attendu: 17 000)\n";

$check1 = abs($kpis['stocks']['311'] - 10000) < 0.01 && 
          abs($kpis['stocks']['312'] - 5000) < 0.01 && 
          abs($kpis['stocks']['313'] - 2000) < 0.01;
echo "   Status: " . ($check1 ? "✅ PASS\n\n" : "❌ FAIL\n\n");

// Trésorerie
$kpis['tresorerie'] = [
    '512' => getBalance($db, '512', 2024),
    '530' => getBalance($db, '530', 2024)
];
$kpis['tresorerie']['TOTAL'] = $kpis['tresorerie']['512'] + $kpis['tresorerie']['530'];

echo "💰 TRÉSORERIE (actif courant)\n";
echo "   Banque (512):  " . number_format($kpis['tresorerie']['512'], 2, ',', ' ') . " EUR (attendu: 5 000)\n";
echo "   Caisse (530):  " . number_format($kpis['tresorerie']['530'], 2, ',', ' ') . " EUR (attendu: 0)\n";
echo "   TOTAL:         " . number_format($kpis['tresorerie']['TOTAL'], 2, ',', ' ') . " EUR (attendu: 5 000)\n";

$check2 = abs($kpis['tresorerie']['512'] - 5000) < 0.01 && 
          abs($kpis['tresorerie']['530'] - 0) < 0.01;
echo "   Status: " . ($check2 ? "✅ PASS\n\n" : "❌ FAIL\n\n");

// Clients
$kpis['clients'] = abs(getBalance($db, '411', 2024));

echo "👥 CLIENTS (créances)\n";
echo "   411:  " . number_format($kpis['clients'], 2, ',', ' ') . " EUR (attendu: 2 500)\n";

$check3 = abs($kpis['clients'] - 2500) < 0.01;
echo "   Status: " . ($check3 ? "✅ PASS\n\n" : "❌ FAIL\n\n");

// Fournisseurs
$kpis['fournisseurs'] = abs(getBalance($db, '401', 2024));

echo "🏭 FOURNISSEURS (dettes)\n";
echo "   401:  " . number_format($kpis['fournisseurs'], 2, ',', ' ') . " EUR (attendu: 0)\n";

$check4 = abs($kpis['fournisseurs'] - 0) < 0.01;
echo "   Status: " . ($check4 ? "✅ PASS\n\n" : "❌ FAIL\n\n");

// Chiffre d'Affaires
$kpis['ca'] = abs(getBalance($db, '701', 2024));

echo "💹 CHIFFRE D'AFFAIRES\n";
echo "   701: " . number_format($kpis['ca'], 2, ',', ' ') . " EUR (attendu: 10 000)\n";

$check5 = abs($kpis['ca'] - 10000) < 0.01;
echo "   Status: " . ($check5 ? "✅ PASS\n\n" : "❌ FAIL\n\n");

// Coûts et Marge
$kpis['couts'] = abs(getBalance($db, '601', 2024)) + abs(getBalance($db, '602', 2024));
$kpis['marge'] = $kpis['ca'] - $kpis['couts'];
$kpis['taux_marge'] = $kpis['ca'] > 0 ? ($kpis['marge'] / $kpis['ca']) * 100 : 0;

echo "📊 COÛTS ET MARGE\n";
echo "   Coûts (601+602): " . number_format($kpis['couts'], 2, ',', ' ') . " EUR (attendu: 3 000)\n";
echo "   Marge brute:     " . number_format($kpis['marge'], 2, ',', ' ') . " EUR (attendu: 7 000)\n";
echo "   Taux de marge:   " . number_format($kpis['taux_marge'], 2, ',', ' ') . "% (attendu: 70%)\n";

$check6 = abs($kpis['couts'] - 3000) < 0.01 && 
          abs($kpis['marge'] - 7000) < 0.01 && 
          abs($kpis['taux_marge'] - 70) < 0.01;
echo "   Status: " . ($check6 ? "✅ PASS\n\n" : "❌ FAIL\n\n");

// Équilibre
echo "⚖️  ÉQUILIBRE COMPTABLE\n";
echo "   Débits:  " . number_format($totalDebit, 2, ',', ' ') . " EUR\n";
echo "   Crédits: " . number_format($totalCredit, 2, ',', ' ') . " EUR\n";

$check7 = abs($totalDebit - $totalCredit) < 0.01;
echo "   Status: " . ($check7 ? "✅ PASS\n\n" : "❌ FAIL\n\n");

// RÉSUMÉ
$checks = [$check1, $check2, $check3, $check4, $check5, $check6, $check7];
$passed = count(array_filter($checks));
$total = count($checks);

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                    📋 RÉSUMÉ FINAL                           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";
echo "✅ Tests réussis: $passed/$total\n";
echo "❌ Tests échoués: " . ($total - $passed) . "/$total\n";
echo "📊 Score: " . number_format(($passed/$total)*100, 1, ',', ' ') . "%\n\n";

if ($passed == $total) {
    echo "🎉 TOUS LES KPIs SONT CORRECTS!\n";
} else {
    echo "⚠️  Certains KPIs nécessitent ajustements\n";
}
