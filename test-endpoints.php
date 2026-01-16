<?php
/**
 * Test tous les endpoints API
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║        🧪 TEST DES ENDPOINTS API                              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$db = new PDO('sqlite:compta.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Helper function
function queryAPI($file, $exercice = 2024) {
    if (!file_exists($file)) {
        return "❌ Fichier n'existe pas: $file";
    }
    
    // Simule GET
    $_GET['exercice'] = $exercice;
    
    // Charge le fichier
    ob_start();
    try {
        // Set up simple database wrapper
        function getDatabase() {
            return $GLOBALS['db'];
        }
        
        include $file;
        $output = ob_get_clean();
        return json_decode($output, true) ?? $output;
    } catch (Exception $e) {
        ob_end_clean();
        return "❌ Erreur: " . $e->getMessage();
    }
}

// Tests
$tests = [
    'public_html/api/v1/kpis/detailed.php' => 'KPIs détaillés',
    'public_html/api/v1/balance/simple.php' => 'Balance simple',
    'public_html/api/v1/analytics/kpis.php' => 'Analytics KPIs',
    'public_html/api/v1/analytics/analysis.php' => 'Analyse complète',
    'public_html/api/v1/analytics/advanced.php' => 'Analyses avancées',
];

foreach ($tests as $file => $desc) {
    echo "🧪 TEST: $desc\n";
    echo "   Fichier: $file\n";
    
    try {
        if (!file_exists($file)) {
            echo "   ❌ Fichier n'existe pas\n";
        } else {
            echo "   ✅ Fichier existe (" . filesize($file) . " bytes)\n";
            
            // Vérifie si le fichier a des erreurs évidentes
            $content = file_get_contents($file);
            
            // Compte les fonctions
            preg_match_all('/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $content, $funcs);
            if (!empty($funcs[1])) {
                echo "   📌 Fonctions: " . implode(", ", array_unique($funcs[1])) . "\n";
            }
            
            // Détecte les tables utilisées
            preg_match_all('/FROM\s+([a-zA-Z_][a-zA-Z0-9_]*)/i', $content, $tables);
            if (!empty($tables[1])) {
                $unique_tables = array_unique($tables[1]);
                echo "   📊 Tables: " . implode(", ", $unique_tables) . "\n";
                
                foreach ($unique_tables as $table) {
                    try {
                        $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                        echo "      └─ $table: ✅ " . $count . " lignes\n";
                    } catch (Exception $e) {
                        echo "      └─ $table: ❌ N'existe pas\n";
                    }
                }
            }
        }
    } catch (Exception $e) {
        echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "✅ Test terminé\n\n";
