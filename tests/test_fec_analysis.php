<?php
/**
 * Script de test pour FecAnalyzer
 * Teste l'analyse du FEC réel fourni par l'utilisateur
 */

define('APP_ROOT', dirname(__FILE__));
define('BACKEND_ROOT', APP_ROOT . '/backend');

// Autoloader simple
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

echo "=== TEST FECANALYZER ===" . PHP_EOL;

// Test les séparateurs manuellement d'abord
$line1 = file_get_contents('fec_2024_atc.txt', false, null, 0, 200);  // Première ligne
echo "First line (200 chars): " . substr($line1, 0, 100) . "..." . PHP_EOL;

$seps = ["\t" => 'TAB', '|' => 'PIPE', ',' => 'COMMA', ';' => 'SEMI'];
foreach ($seps as $sep => $name) {
    $fields = str_getcsv(trim(explode(PHP_EOL, $line1)[0]), $sep);
    echo "  $name: " . count($fields) . " fields" . PHP_EOL;
}

echo PHP_EOL . "Checking FecAnalyzer: " . (file_exists(BACKEND_ROOT . '/services/FecAnalyzer.php') ? '✓' : '✗') . PHP_EOL;

try {
    $analyzer = new App\Services\FecAnalyzer();
    echo "Instantiation: ✓" . PHP_EOL;
    
    // Analyse du fichier
    $result = $analyzer->analyze('fec_2024_atc.txt');
    
    echo PHP_EOL . "=== RÉSULTATS ===" . PHP_EOL;
    echo "Format:" . PHP_EOL;
    echo "  Séparateur: " . ($result['format']['detected_separator'] === "\t" ? 'TAB' : $result['format']['detected_separator']) . PHP_EOL;
    echo "  Encoding: " . $result['format']['encoding'] . PHP_EOL;
    
    if (isset($result['headers']['headers']) && is_array($result['headers']['headers'])) {
        echo "  Colonnes détectées: " . count($result['headers']['headers']) . PHP_EOL;
    }
    
    echo PHP_EOL . "Statistiques:" . PHP_EOL;
    if (isset($result['data_statistics'])) {
        $stats = $result['data_statistics'];
        $quality = $result['data_quality'] ?? [];
        echo "  Lignes: " . $stats['total_rows'] . PHP_EOL;
        if (isset($quality['journals_list'])) {
            echo "  Journaux: " . count($quality['journals_list']) . PHP_EOL;
        }
        if (isset($quality['accounts_list'])) {
            echo "  Comptes: " . count($quality['accounts_list']) . PHP_EOL;
        }
        if (!empty($stats['date_range']['min'])) {
            echo "  Périodes: " . $stats['date_range']['min'] . " à " . $stats['date_range']['max'] . PHP_EOL;
        }
    }
    
    echo PHP_EOL . "Balance:" . PHP_EOL;
    if (isset($result['data_statistics'])) {
        $stats = $result['data_statistics'];
        $difference = abs($stats['total_debit'] - $stats['total_credit']);
        echo "  Débits: " . number_format($stats['total_debit'], 2, ',', ' ') . " EUR" . PHP_EOL;
        echo "  Crédits: " . number_format($stats['total_credit'], 2, ',', ' ') . " EUR" . PHP_EOL;
        echo "  Différence: " . number_format($difference, 8, ',', ' ') . " EUR" . PHP_EOL;
        echo "  Équilibre: " . ($difference < 0.01 ? '✓ OUI' : '✗ NON') . PHP_EOL;
    }
    
    echo PHP_EOL . "Anomalies:" . PHP_EOL;
    if (isset($result['anomalies'])) {
        echo "  Critiques: " . count($result['anomalies']['critical']) . PHP_EOL;
        echo "  Avertissements: " . count($result['anomalies']['warnings']) . PHP_EOL;
        
        if (count($result['anomalies']['critical']) > 0) {
            echo PHP_EOL . "  Critiques détails:" . PHP_EOL;
            foreach (array_slice($result['anomalies']['critical'], 0, 5) as $anom) {
                echo "    - " . $anom . PHP_EOL;
            }
            if (count($result['anomalies']['critical']) > 5) {
                echo "    ... et " . (count($result['anomalies']['critical']) - 5) . " autres" . PHP_EOL;
            }
        }
    }
    
    echo PHP_EOL . "✓ Prêt pour import: " . ($result['ready_for_import'] ? 'OUI' : 'NON') . PHP_EOL;
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . PHP_EOL;
    echo "Trace: " . $e->getTraceAsString() . PHP_EOL;
}
?>
