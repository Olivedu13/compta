<?php
/**
 * Endpoint FEC Import - Version optimisée
 * Importe directement sans analyse FecAnalyzer (trop lente)
 */

header('Content-Type: application/json; charset=utf-8');

try {
    // ========================================
    // Initialisation DB
    // ========================================
    $projectRoot = dirname(dirname(dirname(__FILE__)));
    $dbPath = $projectRoot . '/compta.db';
    
    if (!file_exists($dbPath)) {
        http_response_code(500);
        throw new Exception("Base de données non trouvée: $dbPath");
    }
    
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ========================================
    // Validation de la requête
    // ========================================
    if (!isset($_FILES['file'])) {
        http_response_code(400);
        throw new Exception("Fichier requis");
    }
    
    $file = $_FILES['file'];
    $tmpFile = $file['tmp_name'];
    
    if (!file_exists($tmpFile)) {
        http_response_code(400);
        throw new Exception("Fichier temporaire non trouvé");
    }
    
    if (!is_uploaded_file($tmpFile)) {
        http_response_code(400);
        throw new Exception("Fichier non uploadé correctement");
    }
    
    // ========================================
    // Étape 1 : Validation rapide du FEC
    // ========================================
    
    $handle = fopen($tmpFile, 'r');
    if (!$handle) {
        throw new Exception("Impossible d'ouvrir le fichier");
    }
    
    // Récupère le header
    $headers = fgetcsv($handle, 0, "\t");
    if (!$headers || count($headers) < 5) {
        fclose($handle);
        throw new Exception("FEC header invalide - colonnes manquantes");
    }
    
    // Vérifie les colonnes obligatoires
    $requiredCols = ['JournalCode', 'EcritureNum', 'EcritureDate', 'CompteNum', 'Debit', 'Credit'];
    foreach ($requiredCols as $col) {
        if (!in_array($col, $headers)) {
            fclose($handle);
            throw new Exception("Colonne FEC obligatoire manquante: $col");
        }
    }
    
    // ========================================
    // Étape 2 : Import du FEC
    // ========================================
    
    // Prépare l'insertion
    $stmt = $db->prepare('
        INSERT INTO ecritures (
            exercice, journal_code, journal_lib, ecriture_num, ecriture_date,
            compte_num, compte_lib, numero_tiers, lib_tiers,
            debit, credit, libelle_ecriture, piece_ref, date_piece, lettrage_flag
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    
    // Import des lignes
    $db->beginTransaction();
    $count = 0;
    $debit_total = 0.0;
    $credit_total = 0.0;
    $exercice = 2024;
    
    while (($row = fgetcsv($handle, 0, "\t")) !== false) {
        try {
            $data = array_combine($headers, $row);
            if ($data === false) continue;
            
            $debit = (float) str_replace(',', '.', trim($data['Debit'] ?? '0'));
            $credit = (float) str_replace(',', '.', trim($data['Credit'] ?? '0'));
            
            $date_str = trim($data['EcritureDate'] ?? '2024-01-01');
            $exercice = (int) substr($date_str, 0, 4);
            
            $stmt->execute([
                $exercice,
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
            ]);
            
            $debit_total += $debit;
            $credit_total += $credit;
            $count++;
            
        } catch (Exception $e) {
            // Continue même si une ligne échoue
            continue;
        }
    }
    
    $db->commit();
    fclose($handle);
    
    // Vérifie la balance
    $balance_diff = abs($debit_total - $credit_total);
    $is_balanced = $balance_diff < 0.01;
    
    // ========================================
    // Réponse de succès
    // ========================================
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'message' => "$count écritures FEC importées avec succès",
            'count' => $count,
            'exercice' => $exercice,
            'balance_check' => number_format($debit_total, 2, ',', ' ') . ' EUR (débits) = ' . number_format($credit_total, 2, ',', ' ') . ' EUR (crédits)',
            'is_balanced' => $is_balanced ? 'OUI' : 'NON',
            'balance_diff' => number_format($balance_diff, 2, ',', ' ') . ' EUR'
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
?>
