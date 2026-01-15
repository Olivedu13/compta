<?php
// Test parsing ligne réelle du FEC 2024.txt

$line = "AN\ta nouveau\tAN000001\t20240101\t41100000\tCLIENTS\t01200000\tCLIENT DIVERS\tSAN1568\t20111222\t25/02/12 ROURE CIRCUIT P\t0,00\t4000,00\t\t\t20241231\t\t";

echo "Test str_getcsv avec TAB vrai:\n";
$fields = str_getcsv($line, "\t");

echo "Champs reçus: " . count($fields) . "\n\n";

for ($i = 0; $i < count($fields); $i++) {
    printf("[%2d] = '%s'\n", $i+1, $fields[$i]);
}

echo "\n✅ RÉSULTAT: " . (count($fields) === 18 ? "18 COLONNES OK!" : "❌ Problème " . count($fields)) . "\n";
