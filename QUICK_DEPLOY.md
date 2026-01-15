# 🚀 DÉPLOIEMENT DIRECT - Action Immédiate

## Le problème
Le serveur live a une ancienne version de `simple-import.php` qui utilise FecAnalyzer.

## La solution
Remplace les 3 fichiers directement.

## 📋 Fichiers à déployer

### 1. `/public_html/api/simple-import.php` (PRIORITAIRE ⚠️)
```php
<?php
header('Content-Type: application/json; charset=utf-8');
try {
    $projectRoot = dirname(dirname(dirname(__FILE__)));
    $dbPath = $projectRoot . '/compta.db';
    if (!file_exists($dbPath)) {
        http_response_code(500);
        throw new Exception("Base de données non trouvée: $dbPath");
    }
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
    $handle = fopen($tmpFile, 'r');
    if (!$handle) {
        throw new Exception("Impossible d'ouvrir le fichier");
    }
    $headers = fgetcsv($handle, 0, "\t");
    if (!$headers || count($headers) < 5) {
        fclose($handle);
        throw new Exception("FEC header invalide - colonnes manquantes");
    }
    $requiredCols = ['JournalCode', 'EcritureNum', 'EcritureDate', 'CompteNum', 'Debit', 'Credit'];
    foreach ($requiredCols as $col) {
        if (!in_array($col, $headers)) {
            fclose($handle);
            throw new Exception("Colonne FEC obligatoire manquante: $col");
        }
    }
    $stmt = $db->prepare('
        INSERT INTO ecritures (
            exercice, journal_code, journal_lib, ecriture_num, ecriture_date,
            compte_num, compte_lib, numero_tiers, lib_tiers,
            debit, credit, libelle_ecriture, piece_ref, date_piece, lettrage_flag
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
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
            continue;
        }
    }
    $db->commit();
    fclose($handle);
    $balance_diff = abs($debit_total - $credit_total);
    $is_balanced = $balance_diff < 0.01;
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
```

### 2. Via cPanel File Manager
1. Connecte-toi à https://compta.sarlatc.com:2083
2. File Manager → `/homepages/29/d210120109/htdocs/compta/public_html/api/`
3. Supprime `simple-import.php`
4. Upload le nouveau fichier
5. Test: https://compta.sarlatc.com/api/health

### 3. Ou via SSH + SCP
```bash
# SCP le fichier depuis le package
scp deployment-package.tar.gz olive@compta.sarlatc.com:~/
ssh olive@compta.sarlatc.com "cd ~/ && tar -xzf deployment-package.tar.gz && cp -r public_html/* ~/public_html/"
```

## ✅ Test Immédiat
```bash
curl https://compta.sarlatc.com/api/simple-import.php -F "file=@test.txt"
# Doit retourner success: true (pas "Class not found")
```

