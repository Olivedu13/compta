<?php
/**
 * Import FEC corrigé et vérification KPI
 * Génère une situation comptable réaliste et équilibrée
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$projectRoot = dirname(dirname(__FILE__));
$dbPath = $projectRoot . '/compta.db';
$fecPath = $projectRoot . '/tests/fixtures/fec-corrected-2024.txt';

if (!file_exists($fecPath)) {
    die("❌ FEC not found: $fecPath\n");
}

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║          📥 IMPORT FEC CORRIGÉ ET VÉRIFICATION KPI             ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// 1. Supprimer les écritures existantes de 2024
echo "🗑️  Suppression des écritures 2024...\n";
$stmt = $db->prepare("DELETE FROM ecritures WHERE exercice = 2024");
$deleted = $stmt->rowCount();
echo "   ✅ $deleted écritures supprimées\n\n";

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
            compte_num, compte_lib, debit, credit, libelle_ecriture,
            piece_ref, date_piece
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
echo "⚖️  Vérification équilibre...\n";
$result = $db->query("
    SELECT 
        SUM(debit) as total_debit,
        SUM(credit) as total_credit
    FROM ecritures
    WHERE exercice = 2024
")->fetch(PDO::FETCH_ASSOC);

$totalDebit = (float)($result['total_debit'] ?? 0);
$totalCredit = (float)($result['total_credit'] ?? 0);

echo "   Débits: " . number_format($totalDebit, 2, ',', ' ') . " EUR\n";
echo "   Crédits: " . number_format($totalCredit, 2, ',', ' ') . " EUR\n";

if (abs($totalDebit - $totalCredit) < 0.01) {
    echo "   ✅ Équilibre parfait\n\n";
} else {
    echo "   ❌ DÉSÉQUILIBRE: " . number_format(abs($totalDebit - $totalCredit), 2) . " EUR\n\n";
}

// 4. Afficher la structure par compte
echo "📊 Structure des écritures par compte:\n";
echo "────────────────────────────────────────\n";

$result = $db->query("
    SELECT 
        compte_num,
        SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END) as total_debit,
        SUM(CASE WHEN credit > 0 THEN credit ELSE 0 END) as total_credit
    FROM ecritures
    WHERE exercice = 2024
    GROUP BY compte_num
    ORDER BY compte_num
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($result as $row) {
    $debit = (float)$row['total_debit'];
    $credit = (float)$row['total_credit'];
    $balance = $debit - $credit;
    
    $debitStr = $debit > 0 ? number_format($debit, 2, ',', ' ') : '-';
    $creditStr = $credit > 0 ? number_format($credit, 2, ',', ' ') : '-';
    $balanceStr = number_format($balance, 2, ',', ' ');
    
    echo sprintf("  %s | D: %12s | C: %12s | Balance: %12s\n", 
        $row['compte_num'],
        $debitStr,
        $creditStr,
        $balanceStr
    );
}

// 5. Calculer les KPIs
echo "\n📈 CALCUL DES KPIs:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

function getBalance($db, $compte, $exercice) {
    $stmt = $db->prepare("
        SELECT 
            SUM(debit) as debit,
            SUM(credit) as credit
        FROM ecritures
        WHERE compte_num = ? AND exercice = ?
    ");
    $stmt->execute([$compte, $exercice]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return ($result['debit'] ?? 0) - ($result['credit'] ?? 0);
}

// KPI 1: Stocks
$stockOr = abs(getBalance($db, '311', 2024));
$stockDiamants = abs(getBalance($db, '312', 2024));
$stockBijoux = abs(getBalance($db, '313', 2024));
$stockTotal = $stockOr + $stockDiamants + $stockBijoux;

echo "📦 KPI #1: STOCKS\n";
echo "  Or (311): " . number_format($stockOr, 2, ',', ' ') . " EUR\n";
echo "  Diamants (312): " . number_format($stockDiamants, 2, ',', ' ') . " EUR\n";
echo "  Bijoux (313): " . number_format($stockBijoux, 2, ',', ' ') . " EUR\n";
echo "  TOTAL: " . number_format($stockTotal, 2, ',', ' ') . " EUR ✅\n\n";

// KPI 2: Trésorerie
$banque = getBalance($db, '512', 2024);
$caisse = getBalance($db, '530', 2024);
$tresorerieTotal = $banque + $caisse;

echo "💰 KPI #2: TRÉSORERIE\n";
echo "  Banque (512): " . number_format($banque, 2, ',', ' ') . " EUR\n";
echo "  Caisse (530): " . number_format($caisse, 2, ',', ' ') . " EUR\n";
echo "  TOTAL: " . number_format($tresorerieTotal, 2, ',', ' ') . " EUR ✅\n\n";

// KPI 3: Clients
$clients = getBalance($db, '411', 2024);
echo "👥 KPI #3: CLIENTS\n";
echo "  Clients (411): " . number_format($clients, 2, ',', ' ') . " EUR\n";
if ($clients > 0) {
    echo "  ✅ Créances normales\n\n";
} else if ($clients < 0) {
    echo "  ⚠️ Avances clients: " . number_format(abs($clients), 2, ',', ' ') . " EUR\n\n";
} else {
    echo "  ✅ Équilibré\n\n";
}

// KPI 4: Fournisseurs
$fournisseurs = getBalance($db, '401', 2024);
echo "🏭 KPI #4: FOURNISSEURS\n";
echo "  Fournisseurs (401): " . number_format($fournisseurs, 2, ',', ' ') . " EUR\n";
if ($fournisseurs > 0) {
    echo "  ⚠️ Dettes fournisseurs\n\n";
} else if ($fournisseurs < 0) {
    echo "  ✅ Avances fournisseurs\n\n";
} else {
    echo "  ✅ Équilibré\n\n";
}

// KPI 5: Chiffre d'Affaires
$ca701 = abs(getBalance($db, '701', 2024));
$ca702 = abs(getBalance($db, '702', 2024));
$ca703 = abs(getBalance($db, '703', 2024));
$caTotal = $ca701 + $ca702 + $ca703;

echo "💹 KPI #5: CHIFFRE D'AFFAIRES\n";
echo "  701: " . number_format($ca701, 2, ',', ' ') . " EUR\n";
echo "  702: " . number_format($ca702, 2, ',', ' ') . " EUR\n";
echo "  703: " . number_format($ca703, 2, ',', ' ') . " EUR\n";
echo "  TOTAL: " . number_format($caTotal, 2, ',', ' ') . " EUR ✅\n\n";

// KPI 6: Coûts et Marge
$couts601 = abs(getBalance($db, '601', 2024));
$couts602 = abs(getBalance($db, '602', 2024));
$coutsTotal = $couts601 + $couts602;
$marge = $caTotal - $coutsTotal;
$tauxMarge = $caTotal > 0 ? ($marge / $caTotal) * 100 : 0;

echo "📊 KPI #6: COÛTS ET MARGE\n";
echo "  Coûts 601: " . number_format($couts601, 2, ',', ' ') . " EUR\n";
echo "  Coûts 602: " . number_format($couts602, 2, ',', ' ') . " EUR\n";
echo "  Total coûts: " . number_format($coutsTotal, 2, ',', ' ') . " EUR\n";
echo "  Marge: " . number_format($marge, 2, ',', ' ') . " EUR\n";
echo "  Taux de marge: " . number_format($tauxMarge, 2, ',', ' ') . "% ✅\n\n";

echo "✅ Import et calculs terminés!\n";
