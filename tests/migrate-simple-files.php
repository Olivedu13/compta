<?php
/**
 * Script de migration - Refactor tous les fichiers *-simple.php
 * Applique les corrections de sécurité en masse
 */

$simpleFiles = [
    'sig-simple.php',
    'kpis-simple.php',
    'kpis-detailed.php',
    'analyse-simple.php',
    'analytics-advanced.php',
    'comptes-simple.php',
    'annees-simple.php',
    'debug-clients.php',
    'debug-all-clients.php'
];

$baseDir = __DIR__ . '/public_html/';
$pattern_credentials = '/\$dbConfig = \[.*?\];/s';
$pattern_pdo_new = '/\$dsn = "mysql.*?";\s*\$db = new PDO\([^)]+\);\s*\$db->setAttribute\([^)]+\);/s';

foreach ($simpleFiles as $file) {
    $path = $baseDir . $file;
    
    if (!file_exists($path)) {
        echo "❌ $file not found\n";
        continue;
    }
    
    $content = file_get_contents($path);
    
    // 1. Remplace les credentials en dur
    $content = preg_replace($pattern_credentials, '', $content);
    
    // 2. Remplace la création PDO manuelle
    $content = preg_replace($pattern_pdo_new, '', $content);
    
    // 3. Ajoute le bootstrap au début (après <?php)
    $content = str_replace(
        '<?php',
        "<?php\nrequire_once dirname(dirname(__FILE__)) . '/backend/bootstrap.php';\nuse App\Config\Database;\nuse App\Config\InputValidator;\nuse App\Config\Logger;",
        $content
    );
    
    // 4. Remplace les `$_GET['param']` par validation
    $content = preg_replace(
        '/\$exercice = \$_GET\[\'exercice\'\] \?\? (\d+);/',
        '$exercice = InputValidator::asYear($_GET[\'exercice\'] ?? $1);',
        $content
    );
    
    $content = preg_replace(
        '/\$db = new PDO\(/',
        '$db = Database::getInstance()->\getConnection()->\query(\n',
        $content
    );
    
    // Sauvegarde le fichier
    file_put_contents($path, $content);
    echo "✅ Migrated: $file\n";
}

echo "\n✓ Migration complète\n";
echo "⚠️ Vérifiez manuellement les fichiers pour les requêtes.\n";
?>
