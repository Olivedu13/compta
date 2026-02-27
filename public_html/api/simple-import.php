<?php
/**
 * Endpoint FEC Import — Version idempotente
 * 
 * 1. Lit TOUT le fichier en mémoire
 * 2. Détecte TOUS les exercices présents
 * 3. Supprime TOUTES les données de ces exercices (ecritures + fin_balance + reports)
 * 4. Insère toutes les lignes en une seule transaction
 * 5. Reconstruit fin_balance pour chaque exercice
 * 
 * Résultat : idempotent — reimporter le même fichier donne EXACTEMENT le même résultat.
 */

header('Content-Type: application/json; charset=utf-8');

try {
    // ========================================
    // 0. Initialisation DB
    // ========================================
    $projectRoot = dirname(dirname(__DIR__));
    if (!file_exists($projectRoot . '/compta.db')) $projectRoot = dirname($projectRoot);
    if (!file_exists($projectRoot . '/compta.db')) $projectRoot = dirname(dirname(dirname(__DIR__)));
    if (!file_exists($projectRoot . '/compta.db')) $projectRoot = $_SERVER['DOCUMENT_ROOT'] ?: dirname(dirname(__DIR__));
    $dbPath = $projectRoot . '/compta.db';

    if (!file_exists($dbPath)) {
        http_response_code(500);
        throw new Exception("Base de données non trouvée: $dbPath");
    }

    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("PRAGMA journal_mode = WAL");

    // ========================================
    // 1. Validation du fichier uploadé
    // ========================================
    if (!isset($_FILES['file'])) {
        http_response_code(400);
        throw new Exception("Fichier requis");
    }

    $tmpFile = $_FILES['file']['tmp_name'];
    if (!$tmpFile || !file_exists($tmpFile) || !is_uploaded_file($tmpFile)) {
        http_response_code(400);
        throw new Exception("Fichier non uploadé correctement");
    }

    // ========================================
    // 2. Lecture complète du FEC en mémoire
    // ========================================
    $content = file_get_contents($tmpFile);
    
    // Supprimer BOM UTF-8 si présent
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        $content = substr($content, 3);
    }
    
    // Normaliser les fins de ligne
    $content = str_replace("\r\n", "\n", $content);
    $content = str_replace("\r", "\n", $content);
    
    $rawLines = explode("\n", $content);
    unset($content);
    
    if (count($rawLines) < 2) {
        throw new Exception("Fichier FEC vide ou invalide");
    }

    // Parse headers (première ligne)
    $headers = str_getcsv($rawLines[0], "\t");
    if (!$headers || count($headers) < 5) {
        throw new Exception("FEC header invalide - colonnes manquantes");
    }
    
    // Strip whitespace/BOM from header names
    $headers = array_map('trim', $headers);

    // Vérifier colonnes obligatoires
    $requiredCols = ['JournalCode', 'EcritureNum', 'EcritureDate', 'CompteNum', 'Debit', 'Credit'];
    $missing = array_diff($requiredCols, $headers);
    if (!empty($missing)) {
        throw new Exception("Colonnes FEC manquantes: " . implode(', ', $missing));
    }

    // ========================================
    // 3. Parse toutes les lignes → tableau structuré
    // ========================================
    $allRows = [];
    $exercices = [];
    $debit_total = 0.0;
    $credit_total = 0.0;
    $skipped = 0;

    $nbHeaders = count($headers);

    for ($i = 1; $i < count($rawLines); $i++) {
        $line = trim($rawLines[$i]);
        if ($line === '') continue;

        $fields = str_getcsv($line, "\t");
        
        if (count($fields) !== $nbHeaders) {
            if (count($fields) < $nbHeaders) {
                $fields = array_pad($fields, $nbHeaders, '');
            } else {
                $skipped++;
                continue;
            }
        }

        $data = array_combine($headers, $fields);
        if ($data === false) { $skipped++; continue; }

        $debit = (float) str_replace(',', '.', trim($data['Debit'] ?? '0'));
        $credit = (float) str_replace(',', '.', trim($data['Credit'] ?? '0'));

        $date_str = trim($data['EcritureDate'] ?? '');
        $year = 0;
        if (strlen($date_str) >= 4) {
            $year = (int) substr($date_str, 0, 4);
        }
        if ($year < 1900 || $year > 2100) {
            $year = 2024;
        }

        $exercices[$year] = true;
        $debit_total += $debit;
        $credit_total += $credit;

        $allRows[] = [
            $year,
            trim($data['JournalCode'] ?? ''),
            trim($data['JournalLib'] ?? ''),
            trim($data['EcritureNum'] ?? ''),
            $date_str,
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
        ];
    }

    unset($rawLines);

    if (empty($allRows)) {
        throw new Exception("Aucune écriture valide trouvée dans le fichier FEC");
    }

    $exercicesList = array_keys($exercices);
    sort($exercicesList);

    // ========================================
    // 4. TRANSACTION UNIQUE : DELETE + INSERT + REBUILD
    // ========================================
    $db->beginTransaction();

    try {
        // 4a. Supprimer TOUTES les données des exercices concernés
        $deletedEcritures = 0;
        foreach ($exercicesList as $yr) {
            $stmt = $db->prepare("DELETE FROM ecritures WHERE exercice = ?");
            $stmt->execute([$yr]);
            $deletedEcritures += $stmt->rowCount();

            $db->prepare("DELETE FROM fin_balance WHERE exercice = ?")->execute([$yr]);

            try { $db->prepare("DELETE FROM reports WHERE year = ?")->execute([$yr]); } catch (Exception $e) {}
        }

        // 4b. Insérer TOUTES les lignes
        $insertStmt = $db->prepare('
            INSERT INTO ecritures (
                exercice, journal_code, journal_lib, ecriture_num, ecriture_date,
                compte_num, compte_lib, numero_tiers, lib_tiers,
                debit, credit, libelle_ecriture, piece_ref, date_piece, lettrage_flag
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');

        $insertCount = 0;
        $insertErrors = 0;
        foreach ($allRows as $row) {
            try {
                $insertStmt->execute($row);
                $insertCount++;
            } catch (Exception $e) {
                $insertErrors++;
            }
        }

        // 4c. Reconstruire fin_balance pour chaque exercice
        foreach ($exercicesList as $yr) {
            $db->exec("
                INSERT INTO fin_balance (exercice, compte_num, debit, credit, solde)
                SELECT 
                    exercice,
                    compte_num,
                    SUM(CAST(debit AS REAL)),
                    SUM(CAST(credit AS REAL)),
                    SUM(CAST(debit AS REAL)) - SUM(CAST(credit AS REAL))
                FROM ecritures
                WHERE exercice = $yr
                GROUP BY exercice, compte_num
            ");
        }

        $db->commit();

    } catch (Exception $txError) {
        $db->rollBack();
        throw new Exception("Erreur import (transaction annulée): " . $txError->getMessage());
    }

    unset($allRows);

    // ========================================
    // 5. Réponse
    // ========================================
    $balance_diff = abs($debit_total - $credit_total);
    $is_balanced = $balance_diff < 0.01;
    $primaryExercice = count($exercicesList) === 1 ? $exercicesList[0] : max($exercicesList);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'message' => "$insertCount écritures FEC importées avec succès",
            'count' => $insertCount,
            'exercice' => $primaryExercice,
            'exercices' => $exercicesList,
            'deleted_before' => $deletedEcritures,
            'skipped' => $skipped,
            'errors' => $insertErrors,
            'balance_check' => number_format($debit_total, 2, ',', ' ') . ' € (D) = ' . number_format($credit_total, 2, ',', ' ') . ' € (C)',
            'is_balanced' => $is_balanced ? 'OUI' : 'NON',
            'balance_diff' => round($balance_diff, 2)
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} finally {
    if (isset($tmpFile) && file_exists($tmpFile)) {
        @unlink($tmpFile);
    }
}
