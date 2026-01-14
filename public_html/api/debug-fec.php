<?php
/**
 * Debug FEC - Analyse détaillée du fichier uploadé
 */

header('Content-Type: application/json; charset=utf-8');

// Chemins
define('APP_ROOT', dirname(dirname(dirname(__FILE__))));
define('BACKEND_ROOT', APP_ROOT . '/backend');

// Autoloader
spl_autoload_register(function($class) {
    $class = str_replace('App\\', '', $class);
    $path = str_replace('\\', '/', $class);
    $parts = explode('/', $path);
    if (count($parts) > 0) {
        $parts[0] = strtolower($parts[0]);
    }
    $path = implode('/', $parts);
    $filePath = BACKEND_ROOT . '/' . $path . '.php';
    if (file_exists($filePath)) {
        require_once $filePath;
    }
});

use App\Config\Logger;

Logger::init();

if (!isset($_FILES['file'])) {
    echo json_encode(['error' => 'Fichier requis']);
    exit;
}

$file = $_FILES['file'];
$filePath = $file['tmp_name'];

$debug = [
    'file_name' => $file['name'],
    'file_size' => filesize($filePath),
    'file_exists' => file_exists($filePath),
];

// Lit le fichier
$lines = file($filePath, FILE_SKIP_EMPTY_LINES);
$debug['total_lines'] = count($lines);
$debug['first_10_lines'] = array_slice($lines, 0, 10);

// Détecte séparateur
$separator = "\t";
if (!empty($lines)) {
    $tabCount = substr_count($lines[0], "\t");
    $pipeCount = substr_count($lines[0], "|");
    $separator = $tabCount > $pipeCount ? "\t" : "|";
}
$debug['separator'] = $separator === "\t" ? "TAB" : "PIPE";
$debug['tab_count_line0'] = substr_count($lines[0], "\t");
$debug['pipe_count_line0'] = substr_count($lines[0], "|");

// Cherche l'en-tête
$headerLineIdx = -1;
$fecHeaders = ['journalcode', 'ecriturenum', 'comptenum', 'debit', 'credit'];

for ($i = 0; $i < min(count($lines), 50); $i++) {
    $line = trim($lines[$i]);
    if (empty($line)) continue;
    
    $fields = str_getcsv($line, $separator);
    $fieldsNorm = array_map(function($f) { 
        return trim(strtolower(preg_replace('/[^a-z0-9_]/', '', $f))); 
    }, $fields);
    
    $debug['line_' . $i] = [
        'content' => substr($line, 0, 80),
        'field_count' => count($fields),
        'normalized_fields' => $fieldsNorm
    ];
    
    $matches = 0;
    foreach ($fecHeaders as $header) {
        if (in_array($header, $fieldsNorm)) {
            $matches++;
        }
    }
    
    if ($matches >= 3 && count($fields) >= 15) {
        $headerLineIdx = $i;
        $debug['header_found_at_line'] = $i;
        $debug['header_fields'] = $fieldsNorm;
        break;
    }
}

if ($headerLineIdx === -1) {
    $debug['header_found'] = false;
    $debug['error'] = 'En-tête FEC non trouvé';
} else {
    $debug['header_found'] = true;
    
    // Scanne les comptes
    $headerLine = trim($lines[$headerLineIdx]);
    $headers = str_getcsv($headerLine, $separator);
    $headers = array_map(function($h) { return trim(strtolower($h)); }, $headers);
    
    $compteNumIdx = -1;
    foreach ($headers as $idx => $header) {
        $norm = preg_replace('/[_\s]/', '', strtolower($header));
        if ($norm === 'comptenum') {
            $compteNumIdx = $idx;
            break;
        }
    }
    
    $debug['compte_num_column_index'] = $compteNumIdx;
    
    if ($compteNumIdx !== -1) {
        $comptes = [];
        $dataLineCount = 0;
        
        for ($i = $headerLineIdx + 1; $i < count($lines) && $dataLineCount < 100; $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;
            
            $fields = str_getcsv($line, $separator);
            if (isset($fields[$compteNumIdx])) {
                $compteNum = trim($fields[$compteNumIdx]);
                if (!empty($compteNum)) {
                    if (!isset($comptes[$compteNum])) {
                        $comptes[$compteNum] = 1;
                    } else {
                        $comptes[$compteNum]++;
                    }
                }
            }
            $dataLineCount++;
        }
        
        $debug['data_lines_scanned'] = $dataLineCount;
        $debug['unique_comptes'] = count($comptes);
        $debug['sample_comptes'] = array_slice($comptes, 0, 10, true);
    }
}

echo json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if (file_exists($filePath)) {
    unlink($filePath);
}
?>
