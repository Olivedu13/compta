<?php
/**
 * Import du FEC test complet 2024 et test des KPIs
 * Utilise la logique de simple-import.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$projectRoot = dirname(dirname(__FILE__));
$dbPath = $projectRoot . '/compta.db';
$fecFile = $projectRoot . '/tests/fixtures/fec-complete-test-2024.txt';

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║        📥 IMPORT FEC COMPLET + TEST DES KPIs                 ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

if (!file_exists($fecFile)) {
    die("❌ Fichier FEC non trouvé: $fecFile\n");
}

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ================================================================
// ÉTAPE 1: IMPORTE LE FEC (logique de simple-import.php)
// ================================================================

echo "1️⃣  Ouverture du FEC...\n";
$handle = fopen($fecFile, 'r');
$headers = fgetcsv($handle, 0, "\t");

// Détecte l'exercice
$firstRow = fgetcsv($handle, 0, "\t");
rewind($handle);
fgetcsv($handle, 0, "\t");
$firstData = array_combine($headers, $firstRow);
$exercice = (int) substr(trim($firstData['EcritureDate']), 0, 4);

echo "✓ Exercice détecté: $exercice\n\n";

// SUPPRIME les écritures existantes (FIX import)
echo "2️⃣  Suppression des écritures existantes de $exercice...\n";
$deleteStmt = $db->prepare("DELETE FROM ecritures WHERE exercice = ?");
$db->beginTransaction();
$deleteStmt->execute([$exercice]);
$deletedCount = $deleteStmt->rowCount();
$db->commit();

echo "✓ Écritures supprimées: $deletedCount\n\n";

// IMPORTE les nouvelles écritures
echo "3️⃣  Import des nouvelles écritures...\n";

$stmt = $db->prepare('
    INSERT INTO ecritures (
        exercice, journal_code, journal_lib, ecriture_num, ecriture_date,
        compte_num, compte_lib, numero_tiers, lib_tiers,
        debit, credit, libelle_ecriture, piece_ref, date_piece, lettrage_flag
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
');

$db->beginTransaction();
$importCount = 0;
$debitTotal = 0.0;
$creditTotal = 0.0;

rewind($handle);
fgetcsv($handle, 0, "\t");

while (($row = fgetcsv($handle, 0, "\t")) !== false) {
    $data = array_combine($headers, $row);
    if ($data === false) continue;
    
    $debit = (float) str_replace(',', '.', trim($data['Debit'] ?? '0'));
    $credit = (float) str_replace(',', '.', trim($data['Credit'] ?? '0'));
    $dateStr = trim($data['EcritureDate'] ?? '2024-01-01');
    $exercice = (int) substr($dateStr, 0, 4);
    
    $stmt->execute([
        $exercice,
        trim($data['JournalCode'] ?? ''),
        trim($data['JournalLib'] ?? ''),
        trim($data['EcritureNum'] ?? ''),
        $dateStr,
        trim($data['CompteNum'] ?? ''),
        trim($data['CompteLib'] ?? ''),
        trim($data['CompAuxNum'] ?? ''),
        trim($data['CompAuxLib'] ?? ''),
        $debit,
        $credit,
        trim($data['EcritureLib'] ?? ''),
        trim($data['PieceRef'] ?? ''),
        trim($data['PieceDate'] ?? ''),
        !empty(trim($data['EcritureLet'] ?? '')) ? 1 : 0
    ]);
    
    $debitTotal += $debit;
    $creditTotal += $credit;
    $importCount++;
}

$db->commit();
fclose($handle);

echo "✓ Écritures importées: $importCount\n";
echo "✓ Débits: " . number_format($debitTotal, 2, '.', ' ') . " EUR\n";
echo "✓ Crédits: " . number_format($creditTotal, 2, '.', ' ') . " EUR\n";
echo "✓ Équilibre: " . (abs($debitTotal - $creditTotal) < 0.01 ? '✅' : '❌') . "\n\n";

// ================================================================
// ÉTAPE 2: TESTE LES KPIs
// ================================================================

echo "4️⃣  Tests des KPIs...\n\n";

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

$testsPassed = 0;
$testsFailed = 0;

// KPI: Stocks
$stockOr = abs(getCompteBalance($db, '311', 2024));
$stockDiamants = abs(getCompteBalance($db, '312', 2024));
$stockBijoux = abs(getCompteBalance($db, '313', 2024));

echo "📦 Stocks:\n";
echo "  Or (311): " . number_format($stockOr, 2, '.', ' ') . " EUR (attendu: 10 000)\n";
echo "  Diamants (312): " . number_format($stockDiamants, 2, '.', ' ') . " EUR (attendu: 5 000)\n";
echo "  Bijoux (313): " . number_format($stockBijoux, 2, '.', ' ') . " EUR (attendu: 2 000)\n";
echo "  Total: " . number_format($stockOr + $stockDiamants + $stockBijoux, 2, '.', ' ') . " EUR (attendu: 17 000)\n\n";

// KPI: Trésorerie
$banque = abs(getCompteBalance($db, '512', 2024));
$caisse = abs(getCompteBalance($db, '530', 2024));

echo "💰 Trésorerie:\n";
echo "  Banque (512): " . number_format($banque, 2, '.', ' ') . " EUR\n";
echo "  Caisse (530): " . number_format($caisse, 2, '.', ' ') . " EUR\n";
echo "  Total: " . number_format($banque + $caisse, 2, '.', ' ') . " EUR (attendu: 10 500)\n\n";

// KPI: Tiers
$clients = abs(getCompteBalance($db, '411', 2024));
$fournisseurs = abs(getCompteBalance($db, '401', 2024));

echo "👥 Tiers:\n";
echo "  Clients (411): " . number_format($clients, 2, '.', ' ') . " EUR (attendu: 0)\n";
echo "  Fournisseurs (401): " . number_format($fournisseurs, 2, '.', ' ') . " EUR (attendu: 8 000)\n\n";

// KPI: Chiffre d'Affaires
$ca = abs(getCompteBalance($db, '701', 2024)) + 
      abs(getCompteBalance($db, '702', 2024)) + 
      abs(getCompteBalance($db, '703', 2024));

echo "📈 Chiffre d'Affaires:\n";
echo "  701: " . number_format(abs(getCompteBalance($db, '701', 2024)), 2, '.', ' ') . " EUR\n";
echo "  702: " . number_format(abs(getCompteBalance($db, '702', 2024)), 2, '.', ' ') . " EUR\n";
echo "  703: " . number_format(abs(getCompteBalance($db, '703', 2024)), 2, '.', ' ') . " EUR\n";
echo "  Total CA: " . number_format($ca, 2, '.', ' ') . " EUR (attendu: 10 000)\n\n";

// KPI: Coûts et Marge
$couts = abs(getCompteBalance($db, '601', 2024)) + abs(getCompteBalance($db, '602', 2024));
$marge = $ca - $couts;
$tauxMarge = $ca > 0 ? ($marge / $ca) * 100 : 0;

echo "📊 Marge & Taux:\n";
echo "  Coûts (601+602): " . number_format($couts, 2, '.', ' ') . " EUR (attendu: 3 000)\n";
echo "  Marge: " . number_format($marge, 2, '.', ' ') . " EUR (attendu: 7 000)\n";
echo "  Taux marge: " . number_format($tauxMarge, 2, ',', ' ') . "% (attendu: 70%)\n\n";

echo "\n✅ Import complet et tests affichés.\n";
echo "Vérifiez que les valeurs correspondent aux attentes.\n";
echo "\n";
?>
