<?php
/**
 * Diagnostic FEC - Détecte format réel (guillemets, échappement, etc.)
 */

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  DIAGNOSTIC NOUVEAU FEC - Format réel                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$fecFile = dirname(__DIR__) . '/fec_2024.txt';
$lines = file($fecFile, FILE_SKIP_EMPTY_LINES);

echo "📋 ANALYSE FORMAT\n";
echo "─────────────────────────────────────────────────────\n";

// En-tête
$header = trim($lines[0]);
echo "En-tête:\n" . substr($header, 0, 150) . "...\n\n";

// Première ligne de données
$line1 = trim($lines[1]);
echo "Ligne 1 (brute):\n" . $line1 . "\n\n";

// Compte les TAB
$tabCount = substr_count($line1, "\t");
$quoteCount = substr_count($line1, '"');

echo "Caractères spéciaux:\n";
echo "  - TAB: " . $tabCount . "\n";
echo "  - Guillemets: " . $quoteCount . "\n\n";

// Teste str_getcsv
$fieldsTab = str_getcsv($line1, "\t");
echo "Parsage TAB simple:\n";
echo "  - Champs reçus: " . count($fieldsTab) . "\n";
echo "  - Attendu: 18\n";
if (count($fieldsTab) !== 18) {
    echo "  - ⚠️  PROBLÈME: Guillemets ou échappement manquants\n";
}

echo "\n";
for ($i = 0; $i < count($fieldsTab); $i++) {
    printf("  [%2d] %s\n", $i + 1, substr($fieldsTab[$i], 0, 40));
}

// SOLUTION: Détecte si guillemets présents
echo "\n\nTEST AVEC GUILLEMETS:\n";
echo "─────────────────────────────────────────────────────\n";

// Entoure champs avec espaces de guillemets
function fixCsvLine($line, $separator = "\t") {
    $fields = explode($separator, trim($line));
    
    foreach ($fields as &$field) {
        $field = trim($field);
        // Si contient espace ET n'est pas déjà entre guillemets
        if (strpos($field, ' ') !== false && 
            !(strpos($field, '"') === 0 && strrpos($field, '"') === strlen($field) - 1)) {
            $field = '"' . str_replace('"', '""', $field) . '"';
        }
    }
    
    return implode($separator, $fields);
}

$line1Fixed = fixCsvLine($line1);
$fieldsFixed = str_getcsv($line1Fixed, "\t");

echo "Après correction guillemets:\n";
echo "  - Champs reçus: " . count($fieldsFixed) . "\n";
echo "  - Status: " . (count($fieldsFixed) === 18 ? "✅ OK" : "❌ TOUJOURS PROBLÈME") . "\n";

if (count($fieldsFixed) === 18) {
    echo "\n✅ SOLUTION: Passer par fixCsvLine() avant str_getcsv()\n";
} else {
    echo "\n⚠️  Autre problème détecté\n";
}

echo "\n═══════════════════════════════════════════════════════\n";
echo "VERDICT:\n";
if (count($fieldsTab) === 18) {
    echo "✅ FEC se parse correctement avec str_getcsv()\n";
} else if (count($fieldsFixed) === 18) {
    echo "⚠️  FEC nécessite prétraitement guillemets\n";
} else {
    echo "❌ FEC a un problème d'encodage plus profond\n";
}
echo "═══════════════════════════════════════════════════════\n";
