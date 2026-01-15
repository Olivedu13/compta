<?php
/**
 * Script de debug pour l'import FEC
 * Teste le parsing du fichier FEC ligne par ligne
 */

// Teste avec les données fournies
$testData = <<<'EOD'
JournalCode	JournalLib	EcritureNum	EcritureDate	CompteNum	CompteLib	CompAuxNum	CompAuxLib	PieceRef	PieceDate	EcritureLib	Debit	Credit	EcritureLet	DateLet	ValidDate	Montantdevise	Idevise
AN	a nouveau	AN000001	20240101	41100000	CLIENTS	01200000	CLIENT DIVERS	SAN1568	20111222	25/02/12 ROURE CIRCUIT P	0,00	4000,00			20241231		
AN	a nouveau	AN000001	20240101	41100000	CLIENTS	01200000	CLIENT DIVERS	SAN1569	20120514	QUINTANA CIRCUIT P	0,00	4000,00			20241231		
AN	a nouveau	AN000001	20240101	40100000	FOURNISSEURS	08000063	CLUB DES JOAILLIERS	SAN3399	20120620	0636 CLUB DES JOAILL	0,00	0,00			20241231		
EOD;

$lines = explode("\n", $testData);
echo "=== ANALYSE FEC ===\n\n";

// Parse l'en-tête
$headerLine = $lines[0];
echo "En-tête brut: " . var_export($headerLine, true) . "\n\n";

$separator = detectSeparator($headerLine);
echo "Séparateur détecté: " . ($separator === "\t" ? "TAB" : "PIPE") . "\n\n";

$headers = str_getcsv($headerLine, $separator);
echo "Colonnes extraites (" . count($headers) . "):\n";
foreach ($headers as $i => $h) {
    echo "  [$i] '" . trim($h) . "'\n";
}
echo "\n";

// Normalise les headers
$normalizedHeaders = array_map(function($h) {
    return preg_replace('/[_\s]/', '', strtolower(trim($h)));
}, $headers);

echo "Colonnes normalisées:\n";
foreach ($normalizedHeaders as $i => $h) {
    echo "  [$i] '" . $h . "'\n";
}
echo "\n";

// Parse les 3 lignes de données
for ($i = 1; $i < min(4, count($lines)); $i++) {
    echo "--- LIGNE " . $i . " ---\n";
    $line = $lines[$i];
    
    if (empty(trim($line))) {
        echo "Ligne vide\n\n";
        continue;
    }
    
    $fields = str_getcsv($line, $separator);
    echo "Champs extraits (" . count($fields) . "):\n";
    
    $row = [];
    foreach ($normalizedHeaders as $idx => $header) {
        $value = isset($fields[$idx]) ? trim($fields[$idx]) : '';
        $row[$header] = $value;
        echo "  '" . $header . "' = '" . $value . "'\n";
    }
    
    // Test de validation
    echo "\nValidation:\n";
    
    // Test montants
    $debit = (float) str_replace(',', '.', $row['debit']);
    $credit = (float) str_replace(',', '.', $row['credit']);
    echo "  Debit: '" . $row['debit'] . "' -> " . $debit . "\n";
    echo "  Credit: '" . $row['credit'] . "' -> " . $credit . "\n";
    
    // Test date
    $eDate = parseFecDate($row['ecrituredate']);
    echo "  EcritureDate: '" . $row['ecrituredate'] . "' -> " . ($eDate ? $eDate->format('Y-m-d') : 'INVALIDE') . "\n";
    
    // Test champs obligatoires
    $required = ['journalcode', 'ecriturenum', 'ecrituredate', 'comptenum', 'debit', 'credit'];
    echo "  Champs obligatoires: ";
    $allOk = true;
    foreach ($required as $field) {
        $ok = isset($row[$field]) && $row[$field] !== '';
        echo ($ok ? "✓" : "✗") . $field . " ";
        if (!$ok) $allOk = false;
    }
    echo ($allOk ? " OK" : " ERREUR") . "\n";
    
    echo "\n";
}

function detectSeparator($firstLine) {
    $tabCount = substr_count($firstLine, "\t");
    $pipeCount = substr_count($firstLine, "|");
    return $tabCount > $pipeCount ? "\t" : "|";
}

function parseFecDate($dateStr) {
    $dateStr = trim((string) $dateStr);
    if (empty($dateStr)) return null;
    
    if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $dateStr, $matches)) {
        $year = $matches[1];
        $month = $matches[2];
        $day = $matches[3];
        
        $date = \DateTime::createFromFormat('Y-m-d', "$year-$month-$day");
        if (!$date) return null;
        return $date;
    }
    
    return null;
}
?>
