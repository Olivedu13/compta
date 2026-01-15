<?php
/**
 * SETUP SQLite pour tester l'import
 */

$dbFile = dirname(__DIR__) . '/compta.db';

// Crée/ouvre la base SQLite
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Creating SQLite database: $dbFile\n";

// Lecture du schéma SQL
$schema = file_get_contents(dirname(__FILE__) . '/config/schema.sql');

// Convertit le schéma MySQL en SQLite
$sqlite_schema = preg_replace_callback(
    [
        '/ENGINE=InnoDB.*?COLLATE=[^;]*;/is',
        '/COMMENT=[^,;]*/is',
        '/DECIMAL\((\d+,\d+)\)/is',
        '/AUTO_INCREMENT/is',
        '/YEAR/is',
        '/KEY\s+([^(]*)\(/is',
    ],
    function ($matches) {
        if (str_contains($matches[0], 'ENGINE=')) return ';';
        if (str_contains($matches[0], 'COMMENT=')) return '';
        if (str_contains($matches[0], 'DECIMAL')) return 'REAL';
        if (str_contains($matches[0], 'AUTO_INCREMENT')) return '';
        if (str_contains($matches[0], 'YEAR')) return 'INTEGER';
        if (str_contains($matches[0], 'KEY')) return 'KEY ' . $matches[1] . ' (';
        return $matches[0];
    },
    $schema
);

// Exécute les statements
$statements = array_filter(
    array_map('trim', explode(';', $sqlite_schema)),
    fn($s) => !empty($s) && !str_starts_with($s, '--')
);

foreach ($statements as $sql) {
    if (!empty($sql)) {
        try {
            $pdo->exec($sql);
            echo "✓ " . substr($sql, 0, 60) . "...\n";
        } catch (Exception $e) {
            echo "⚠ " . substr($sql, 0, 60) . "... (" . $e->getMessage() . ")\n";
        }
    }
}

echo "\n✅ Base SQLite créée\n";
