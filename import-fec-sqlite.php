<?php
// Import FEC into SQLite

if ($argc < 2) {
    echo "Usage: php import-fec-sqlite.php <fec_file>\n";
    exit(1);
}

$fec_file = $argv[1];

if (!file_exists($fec_file)) {
    echo "❌ File not found: $fec_file\n";
    exit(1);
}

// Connect to SQLite
$db = new PDO('sqlite:compta.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "📥 Importing FEC: $fec_file\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Start transaction
$db->beginTransaction();

$handle = fopen($fec_file, 'r');
$headers = fgetcsv($handle, 0, "\t");

$count = 0;
$errors = 0;

// Map headers
$header_map = array_flip($headers);

while (($row = fgetcsv($handle, 0, "\t")) !== false) {
    try {
        $count++;
        
        // Parse row
        $data = array_combine($headers, $row);
        
        // Extract values
        $journal_code = trim($data['JournalCode'] ?? '');
        $journal_lib = trim($data['JournalLib'] ?? '');
        $ecriture_date = trim($data['EcritureDate'] ?? '');
        $compte_num = trim($data['CompteNum'] ?? '');
        $compte_lib = trim($data['CompteLib'] ?? '');
        $comp_aux_num = trim($data['CompAuxNum'] ?? '');
        $comp_aux_lib = trim($data['CompAuxLib'] ?? '');
        $debit = str_replace(',', '.', trim($data['Debit'] ?? '0'));
        $credit = str_replace(',', '.', trim($data['Credit'] ?? '0'));
        $libelle = trim($data['EcritureLib'] ?? '');
        $piece_ref = trim($data['PieceRef'] ?? '');
        $piece_date = trim($data['PieceDate'] ?? '');
        
        // Insert
        $stmt = $db->prepare('
            INSERT INTO ecritures (
                exercice, journal_code, journal_lib, ecriture_date,
                compte_num, compte_lib, numero_tiers, lib_tiers,
                debit, credit, libelle_ecriture, piece_ref, date_piece
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            2024,
            $journal_code,
            $journal_lib,
            $ecriture_date,
            $compte_num,
            $compte_lib,
            $comp_aux_num,
            $comp_aux_lib,
            $debit,
            $credit,
            $libelle,
            $piece_ref,
            $piece_date
        ]);
        
        if ($count % 1000 == 0) {
            echo "✓ $count écritures importées...\n";
        }
        
    } catch (Exception $e) {
        $errors++;
        if ($errors <= 5) {
            echo "⚠️ Error row $count: " . $e->getMessage() . "\n";
        }
    }
}

fclose($handle);

// Commit
$db->commit();

// Verify
$result = $db->query('
    SELECT 
        COUNT(*) as total,
        ROUND(SUM(debit), 2) as debit_sum,
        ROUND(SUM(credit), 2) as credit_sum,
        ROUND(SUM(debit) - SUM(credit), 2) as balance
    FROM ecritures
')->fetch(PDO::FETCH_ASSOC);

echo "\n═══════════════════════════════════════════════════════════\n";
echo "✅ IMPORT COMPLETE\n";
echo "═══════════════════════════════════════════════════════════\n\n";
echo "📊 Results:\n";
echo "  Total écritures: " . $result['total'] . "\n";
echo "  Total Débit: €" . number_format($result['debit_sum'], 2, ',', ' ') . "\n";
echo "  Total Crédit: €" . number_format($result['credit_sum'], 2, ',', ' ') . "\n";
echo "  Balance: €" . number_format($result['balance'], 2, ',', ' ') . "\n";
echo "  Errors: $errors\n\n";

// Verify FROJO
$frojo = $db->query('
    SELECT COUNT(*) as count, ROUND(SUM(debit), 2) as debit, ROUND(SUM(credit), 2) as credit
    FROM ecritures
    WHERE lib_tiers LIKE "%FROJO%" OR numero_tiers LIKE "%FROJO%"
')->fetch(PDO::FETCH_ASSOC);

echo "🎯 FROJO Client:\n";
echo "  Écritures: " . $frojo['count'] . "\n";
echo "  Débit: €" . number_format($frojo['debit'], 2, ',', ' ') . "\n";
echo "  Crédit: €" . number_format($frojo['credit'], 2, ',', ' ') . "\n";
