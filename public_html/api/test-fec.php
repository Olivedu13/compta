<?php
/**
 * Test FEC - Debug complet de l'import
 */

header('Content-Type: application/json; charset=utf-8');

if (!isset($_FILES['file'])) {
    echo json_encode(['error' => 'Fichier requis']);
    exit;
}

$file = $_FILES['file'];
$filePath = $file['tmp_name'];

if (!file_exists($filePath)) {
    echo json_encode(['error' => 'Fichier non trouvé']);
    exit;
}

$debug = [];

// 1. Lit le fichier
$lines = file($filePath, FILE_SKIP_EMPTY_LINES);
$debug['total_lines'] = count($lines);

// 2. Détecte séparateur
$separator = "\t";
$tabCount = substr_count($lines[0], "\t");
$pipeCount = substr_count($lines[0], "|");
$separator = $tabCount > $pipeCount ? "\t" : "|";
$debug['separator'] = $separator === "\t" ? "TAB" : "PIPE";

// 3. Cherche l'en-tête
$headerLineIdx = -1;
foreach ($lines as $i => $line) {
    $line = trim($line);
    if (empty($line)) continue;
    
    $normalized = preg_replace('/[^a-z0-9\t|]/', '', strtolower($line));
    
    if (strpos($normalized, 'journalcode') === 0) {
        $fields = str_getcsv($line, $separator);
        if (count($fields) === 18) {
            $headerLineIdx = $i;
            break;
        }
    }
}

$debug['header_found'] = $headerLineIdx !== -1;
$debug['header_line_index'] = $headerLineIdx;

if ($headerLineIdx === -1) {
    echo json_encode(['error' => 'En-tête non trouvé', 'debug' => $debug]);
    exit;
}

// 4. Parse l'en-tête
$headerLine = trim($lines[$headerLineIdx]);
$headers = str_getcsv($headerLine, $separator);
$headers = array_map(function($h) { return trim(strtolower($h)); }, $headers);

$debug['headers'] = $headers;
$debug['header_count'] = count($headers);

// 5. Parse les 5 premières lignes de données
$dataLines = [];
$lineCount = 0;
for ($i = $headerLineIdx + 1; $i < count($lines) && $lineCount < 5; $i++) {
    $line = trim($lines[$i]);
    if (empty($line)) continue;
    
    $fields = str_getcsv($line, $separator);
    
    $row = [];
    foreach ($headers as $idx => $header) {
        $row[$header] = isset($fields[$idx]) ? trim($fields[$idx]) : '';
    }
    
    // Teste la validation
    $validation = [];
    
    // Teste les champs obligatoires
    $required = ['journalcode', 'ecriturenum', 'ecrituredate', 'comptenum', 'debit', 'credit'];
    foreach ($required as $field) {
        $validation[$field] = [
            'value' => $row[$field] ?? 'MISSING',
            'empty' => empty($row[$field] ?? '')
        ];
    }
    
    // Teste la conversion des montants
    $debit = str_replace(',', '.', $row['debit'] ?? '0');
    $credit = str_replace(',', '.', $row['credit'] ?? '0');
    $validation['debit_converted'] = (float)$debit;
    $validation['credit_converted'] = (float)$credit;
    
    // Teste la date
    $dateStr = $row['ecrituredate'] ?? '';
    $dateOk = preg_match('/^(\d{4})(\d{2})(\d{2})$/', $dateStr, $matches);
    $validation['date_format_ok'] = $dateOk ? true : false;
    
    $dataLines[] = [
        'line_index' => $i,
        'field_count' => count($fields),
        'values' => $row,
        'validation' => $validation
    ];
    
    $lineCount++;
}

$debug['data_lines_parsed'] = count($dataLines);
$debug['data_lines'] = $dataLines;

echo json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if (file_exists($filePath)) {
    unlink($filePath);
}
?>
