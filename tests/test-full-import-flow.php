<?php
/**
 * Test complet du flux d'import FEC avec suppression
 * Simule: Upload du FEC → Suppression des écritures 2024 → Import des nouvelles écritures
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$projectRoot = dirname(dirname(__FILE__));
$dbPath = $projectRoot . '/compta.db';

if (!file_exists($dbPath)) {
    echo "❌ Base de données non trouvée: $dbPath\n";
    exit(1);
}

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== TEST COMPLET D'IMPORT FEC AVEC SUPPRESSION ===\n\n";

try {
    $fecFile = $projectRoot . '/tests/fixtures/test-import-2024.txt';
    
    if (!file_exists($fecFile)) {
        echo "❌ Fichier FEC test non trouvé: $fecFile\n";
        exit(1);
    }
    
    // 1. Compte initial
    $result = $db->query("SELECT COUNT(*) as count FROM ecritures WHERE exercice = 2024");
    $beforeCount = $result->fetch(PDO::FETCH_ASSOC)['count'];
    echo "📊 État initial:\n";
    echo "   - Écritures 2024 en base: $beforeCount\n\n";
    
    // 2. Simule l'import (copie la logique du simple-import.php)
    echo "🔄 Simulation d'import FEC 2024 en 3 étapes:\n\n";
    
    // Étape 1: Ouvre le FEC et détecte l'exercice
    echo "   Étape 1: Détection de l'exercice du FEC\n";
    $handle = fopen($fecFile, 'r');
    $headers = fgetcsv($handle, 0, "\t");
    $firstRow = fgetcsv($handle, 0, "\t");
    rewind($handle);
    
    $firstData = array_combine($headers, $firstRow);
    $dateStr = trim($firstData['EcritureDate']);
    $exercice = (int) substr($dateStr, 0, 4);
    echo "      ✓ Exercice détecté: $exercice\n\n";
    
    // Étape 2: SUPPRIME les écritures existantes
    echo "   Étape 2: Suppression des écritures existantes de $exercice\n";
    $deleteStmt = $db->prepare("DELETE FROM ecritures WHERE exercice = ?");
    $db->beginTransaction();
    $deleteStmt->execute([$exercice]);
    $deletedCount = $deleteStmt->rowCount();
    $db->commit();
    echo "      ✓ Écritures supprimées: $deletedCount\n\n";
    
    // Étape 3: Import les nouvelles écritures
    echo "   Étape 3: Import des nouvelles écritures du FEC\n";
    
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
    
    // Skip header
    fgetcsv($handle, 0, "\t");
    
    while (($row = fgetcsv($handle, 0, "\t")) !== false) {
        try {
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
            
        } catch (Exception $e) {
            echo "      ⚠️  Erreur ligne: " . $e->getMessage() . "\n";
            continue;
        }
    }
    
    $db->commit();
    fclose($handle);
    
    echo "      ✓ Écritures importées: $importCount\n";
    echo "      ✓ Débits: " . number_format($debitTotal, 2, '.', ' ') . " EUR\n";
    echo "      ✓ Crédits: " . number_format($creditTotal, 2, '.', ' ') . " EUR\n\n";
    
    // 3. Vérifie le résultat
    echo "✅ Résultat final:\n";
    $result = $db->query("SELECT COUNT(*) as count FROM ecritures WHERE exercice = 2024");
    $afterCount = $result->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   - Écritures 2024 après import: $afterCount\n";
    
    echo "\n📊 Comparaison:\n";
    echo "   - Avant: $beforeCount écritures\n";
    echo "   - Supprimées: $deletedCount écritures\n";
    echo "   - Importées: $importCount écritures\n";
    echo "   - Après: $afterCount écritures\n";
    
    if ($afterCount === $importCount) {
        echo "\n✅ SUCCÈS COMPLET!\n";
        echo "   Les $beforeCount écritures de 2024 ont été supprimées.\n";
        echo "   Les $importCount nouvelles écritures ont été importées.\n";
        echo "   Aucune duplication!\n";
    } else {
        echo "\n❌ INCOHÉRENCE!\n";
        echo "   Attendu: $importCount écritures\n";
        echo "   Trouvé: $afterCount écritures\n";
        exit(1);
    }
    
    // 4. Affiche les détails des écritures
    echo "\n📋 Détails des écritures importées:\n";
    $result = $db->query("SELECT journal_code, COUNT(*) as count, SUM(debit) as debit, SUM(credit) as credit FROM ecritures WHERE exercice = 2024 GROUP BY journal_code");
    foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "   - {$row['journal_code']}: {$row['count']} écritures | D:{$row['debit']} C:{$row['credit']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
