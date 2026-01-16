<?php
/**
 * Test de duplication d'import
 * Importe 2 fois le même FEC pour vérifier que la suppression fonctionne
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$projectRoot = dirname(dirname(__FILE__));
$dbPath = $projectRoot . '/compta.db';

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== TEST DE DUPLICATION D'IMPORT ===\n";
echo "Objectif: Importer 2 fois le même FEC et vérifier qu'il n'y a pas de duplication\n\n";

$fecFile = $projectRoot . '/tests/fixtures/test-import-2024.txt';

function importFec($db, $fecFile) {
    $handle = fopen($fecFile, 'r');
    $headers = fgetcsv($handle, 0, "\t");
    $firstRow = fgetcsv($handle, 0, "\t");
    rewind($handle);
    
    $firstData = array_combine($headers, $firstRow);
    $exercice = (int) substr(trim($firstData['EcritureDate']), 0, 4);
    
    // SUPPRIME
    $deleteStmt = $db->prepare("DELETE FROM ecritures WHERE exercice = ?");
    $db->beginTransaction();
    $deleteStmt->execute([$exercice]);
    $deletedCount = $deleteStmt->rowCount();
    $db->commit();
    
    // IMPORTE
    $stmt = $db->prepare('
        INSERT INTO ecritures (
            exercice, journal_code, journal_lib, ecriture_num, ecriture_date,
            compte_num, compte_lib, numero_tiers, lib_tiers,
            debit, credit, libelle_ecriture, piece_ref, date_piece, lettrage_flag
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    
    $db->beginTransaction();
    $importCount = 0;
    
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
        $importCount++;
    }
    
    $db->commit();
    fclose($handle);
    
    return [
        'exercice' => $exercice,
        'deleted' => $deletedCount,
        'imported' => $importCount
    ];
}

// PREMIER IMPORT
echo "1️⃣  Premier import:\n";
$result1 = importFec($db, $fecFile);
$countAfter1 = $db->query("SELECT COUNT(*) as count FROM ecritures WHERE exercice = 2024")->fetch(PDO::FETCH_ASSOC)['count'];
echo "   - Supprimées: {$result1['deleted']}\n";
echo "   - Importées: {$result1['imported']}\n";
echo "   - Total en base: $countAfter1\n\n";

// DEUXIÈME IMPORT (même fichier)
echo "2️⃣  Deuxième import (même fichier):\n";
$result2 = importFec($db, $fecFile);
$countAfter2 = $db->query("SELECT COUNT(*) as count FROM ecritures WHERE exercice = 2024")->fetch(PDO::FETCH_ASSOC)['count'];
echo "   - Supprimées: {$result2['deleted']}\n";
echo "   - Importées: {$result2['imported']}\n";
echo "   - Total en base: $countAfter2\n\n";

// VÉRIFICATION
echo "✅ Résultat:\n";
if ($countAfter1 === $result1['imported'] && $countAfter2 === $result2['imported']) {
    echo "   ✓ Pas de duplication!\n";
    echo "   ✓ Après 1er import: $countAfter1 écritures\n";
    echo "   ✓ Après 2e import: $countAfter2 écritures (identique)\n";
    echo "   ✓ Les {$result2['deleted']} anciennes écritures ont bien été supprimées.\n";
    echo "\n✅ TEST RÉUSSI!\n";
    echo "   La suppression fonctionne correctement.\n";
    echo "   Aucune duplication même avec un import identique.\n";
} else {
    echo "   ❌ DUPLICATION DÉTECTÉE!\n";
    echo "   Après 1er import: $countAfter1 écritures\n";
    echo "   Après 2e import: $countAfter2 écritures\n";
    exit(1);
}
