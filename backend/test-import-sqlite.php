<?php
/**
 * TEST IMPORT DIRECT SQLITE - FEC 2024
 * Tests le parsing et l'insertion directement
 */

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  TEST IMPORT DIRECT - FEC 2024.txt → SQLite              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$fecFile = dirname(__DIR__) . '/fec_2024.txt';
$dbFile = dirname(__DIR__) . '/compta.db';

if (!file_exists($fecFile)) {
    echo "❌ Fichier FEC non trouvé\n";
    exit(1);
}

// Connexion SQLite directe
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // Vide la table
    $pdo->exec("DELETE FROM fin_ecritures_fec");
    echo "✓ Table fin_ecritures_fec vidée\n\n";
    
    // ÉTAPE 1: Analyse
    echo "📊 ÉTAPE 1: Analyse FEC\n";
    echo "─────────────────────────────────────────────────────\n";
    
    $lines = file($fecFile, FILE_SKIP_EMPTY_LINES);
    $headerLine = rtrim($lines[0], "\r\n");
    $separator = "\t";
    $headers = str_getcsv($headerLine, $separator);
    $headersNorm = array_map(fn($h) => trim(strtolower($h)), $headers);
    
    echo "✓ Lignes: " . count($lines) . "\n";
    echo "✓ Colonnes: " . count($headers) . "\n";
    
    // ÉTAPE 2: Import
    echo "\n📥 ÉTAPE 2: Import\n";
    echo "─────────────────────────────────────────────────────\n";
    
    $stmt = $pdo->prepare("
        INSERT INTO fin_ecritures_fec (
            journal_code, journal_lib, ecriture_num, ecriture_date,
            compte_num, compte_lib, comp_aux_num, comp_aux_lib,
            piece_ref, piece_date, ecriture_lib,
            debit, credit, ecriture_let, date_let, valid_date,
            montant_devise, id_devise, exercice
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $count = 0;
    $errors = 0;
    $startTime = microtime(true);
    
    // Commence transaction
    $pdo->beginTransaction();
    
    for ($i = 1; $i < count($lines); $i++) {
        $line = rtrim($lines[$i], "\r\n");
        if (empty($line)) continue;
        
        $fields = str_getcsv($line, $separator);
        
        if (count($fields) !== count($headers)) {
            $errors++;
            continue;
        }
        
        // Mappe les colonnes
        $row = [];
        foreach ($headersNorm as $idx => $colName) {
            $row[$colName] = $fields[$idx] ?? '';
        }
        
        // Parse les dates
        $dateStr = $row['ecrituredate'] ?? '';
        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $dateStr, $m)) {
            $eDate = $m[1] . '-' . $m[2] . '-' . $m[3];
        } else {
            $errors++;
            continue;
        }
        
        $pDate = null;
        if (!empty($row['piecedate']) && preg_match('/^(\d{4})(\d{2})(\d{2})$/', $row['piecedate'], $m)) {
            $pDate = $m[1] . '-' . $m[2] . '-' . $m[3];
        }
        
        $dLet = null;
        if (!empty($row['datelet']) && preg_match('/^(\d{4})(\d{2})(\d{2})$/', $row['datelet'], $m)) {
            $dLet = $m[1] . '-' . $m[2] . '-' . $m[3];
        }
        
        $vDate = null;
        if (!empty($row['validdate']) && preg_match('/^(\d{4})(\d{2})(\d{2})$/', $row['validdate'], $m)) {
            $vDate = $m[1] . '-' . $m[2] . '-' . $m[3];
        }
        
        $exercice = (int) substr($eDate, 0, 4);
        $debit = (float) str_replace(',', '.', $row['debit'] ?? '0');
        $credit = (float) str_replace(',', '.', $row['credit'] ?? '0');
        
        try {
            $stmt->execute([
                $row['journalcode'] ?? '',
                $row['journalllib'] ?? '',
                $row['ecriturenum'] ?? '',
                $eDate,
                $row['comptenum'] ?? '',
                $row['comptelib'] ?? '',
                $row['compauxnum'] ?? null,
                $row['compauxlib'] ?? null,
                $row['pieceref'] ?? null,
                $pDate,
                $row['ecriturelib'] ?? '',
                $debit,
                $credit,
                $row['ecriturelet'] ?? null,
                $dLet,
                $vDate,
                str_replace(',', '.', $row['montantdevise'] ?? '0'),
                $row['idevise'] ?? 'EUR',
                $exercice
            ]);
            
            $count++;
            
            // Commit par batch de 500
            if ($count % 500 === 0) {
                echo "  " . number_format($count) . " écritures...\n";
            }
        } catch (Exception $e) {
            $errors++;
        }
    }
    
    $pdo->commit();
    $duration = microtime(true) - $startTime;
    
    echo "✓ Écritures importées: " . number_format($count) . "\n";
    echo "✓ Temps: " . number_format($duration, 2) . "s\n";
    echo "✓ Erreurs: " . $errors . "\n";
    
    // ÉTAPE 3: Validation
    echo "\n✔️  ÉTAPE 3: Validation données\n";
    echo "─────────────────────────────────────────────────────\n";
    
    $countRow = $pdo->query("SELECT COUNT(*) as cnt FROM fin_ecritures_fec")->fetch();
    $totalCount = $countRow['cnt'];
    echo "✓ Total BD: " . number_format($totalCount) . " écritures\n";
    
    $montantRow = $pdo->query("
        SELECT 
            SUM(debit) as debit_total,
            SUM(credit) as credit_total
        FROM fin_ecritures_fec
    ")->fetch();
    $debitTotal = (float)($montantRow['debit_total'] ?? 0);
    $creditTotal = (float)($montantRow['credit_total'] ?? 0);
    echo "✓ Débits: €" . number_format($debitTotal, 2) . "\n";
    echo "✓ Crédits: €" . number_format($creditTotal, 2) . "\n";
    
    $balance = $debitTotal - $creditTotal;
    echo "✓ Balance: €" . number_format($balance, 2) . "\n";
    
    $tiersRow = $pdo->query("
        SELECT COUNT(DISTINCT comp_aux_num) as cnt
        FROM fin_ecritures_fec
        WHERE comp_aux_num IS NOT NULL AND comp_aux_num != ''
    ")->fetch();
    echo "✓ Tiers: " . number_format($tiersRow['cnt']) . "\n";
    
    $lettRow = $pdo->query("
        SELECT COUNT(*) as cnt FROM fin_ecritures_fec
        WHERE ecriture_let IS NOT NULL AND ecriture_let != ''
    ")->fetch();
    echo "✓ Écritures lettrées: " . number_format($lettRow['cnt']) . "\n";
    
    // ÉTAPE 4: Samples
    echo "\n📋 JOURNAUX:\n";
    $journaux = $pdo->query("
        SELECT journal_code, COUNT(*) as cnt
        FROM fin_ecritures_fec
        GROUP BY journal_code
        ORDER BY journal_code
    ")->fetchAll();
    foreach ($journaux as $j) {
        echo sprintf("  %-3s: %7d écritures\n", $j['journal_code'], $j['cnt']);
    }
    
    echo "\n👥 TOP 5 TIERS:\n";
    $tiers = $pdo->query("
        SELECT comp_aux_num, comp_aux_lib, COUNT(*) as cnt
        FROM fin_ecritures_fec
        WHERE comp_aux_num IS NOT NULL AND comp_aux_num != ''
        GROUP BY comp_aux_num
        ORDER BY cnt DESC
        LIMIT 5
    ")->fetchAll();
    $i = 1;
    foreach ($tiers as $t) {
        echo sprintf("  %d. %s: %d\n", $i++, substr($t['comp_aux_lib'] ?: $t['comp_aux_num'], 0, 40), $t['cnt']);
    }
    
    // VERDICT
    echo "\n═══════════════════════════════════════════════════════\n";
    echo "✨ VERDICT\n";
    echo "═══════════════════════════════════════════════════════\n";
    
    $ok = true;
    if ($totalCount > 10000) {
        echo "✅ Données importées (" . number_format($totalCount) . ")\n";
    } else {
        echo "❌ Peu de données (" . number_format($totalCount) . ")\n";
        $ok = false;
    }
    
    if (abs($balance) < 100) {
        echo "✅ Balance correcte (€" . number_format($balance, 2) . ")\n";
    } else {
        echo "❌ Balance incorrecte (€" . number_format($balance, 2) . ")\n";
        $ok = false;
    }
    
    echo "\n→ STATUS: " . ($ok ? "✅ PHASE 3 PRÊTE" : "⚠️  À VÉRIFIER") . "\n";
    echo "═══════════════════════════════════════════════════════\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
