<?php
/**
 * TEST COMPLET NOUVEAU FEC - Parsing correct
 */

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  TEST COMPLET FEC 2024.txt - PARSING CORRECT             ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$fecFile = dirname(__DIR__) . '/fec_2024.txt';

if (!file_exists($fecFile)) {
    echo "❌ Fichier non trouvé\n";
    exit(1);
}

$lines = file($fecFile, FILE_SKIP_EMPTY_LINES);

echo "📊 ÉTAPE 1: Structure\n";
echo "─────────────────────────────────────────────────────\n";

$headerLine = trim($lines[0]);
$separator = "\t"; // TAB confirmé
$headers = str_getcsv($headerLine, $separator);
$headerCount = count($headers);

echo "✓ Lignes: " . count($lines) . "\n";
echo "✓ Colonnes: " . $headerCount . "\n";
echo "✓ En-tête: " . implode(" | ", array_slice(array_map('trim', $headers), 0, 6)) . "...\n";

// Normalise headers pour lookup
$headersNorm = array_map(fn($h) => trim(strtolower($h)), $headers);

echo "\n📈 ÉTAPE 2: Parsing données\n";
echo "─────────────────────────────────────────────────────\n";

$stats = [
    'total' => 0,
    'avec_tiers' => 0,
    'avec_datelet' => 0,
    'avec_lettrage' => 0,
    'tiers_top' => [],
    'montant_total' => 0,
    'journaux' => [],
];

$errors = 0;

for ($i = 1; $i < count($lines); $i++) {
    $line = rtrim($lines[$i], "\r\n");
    if (empty($line)) continue;
    
    $fields = str_getcsv($line, $separator);
    
    if (count($fields) !== $headerCount) {
        $errors++;
        if ($errors <= 3) {
            echo "  DEBUG ligne $i: " . count($fields) . " champs au lieu de " . $headerCount . "\n";
        }
        continue;
    }
    
    // Combine avec headers
    $row = [];
    foreach ($headersNorm as $idx => $colName) {
        $row[$colName] = $fields[$idx] ?? '';
    }
    
    $stats['total']++;
    
    // Tiers
    if (!empty($row['compauxnum'])) {
        $stats['avec_tiers']++;
        $key = $row['compauxnum'];
        if (!isset($stats['tiers_top'][$key])) {
            $stats['tiers_top'][$key] = [
                'lib' => $row['compauxlib'] ?? '',
                'count' => 0,
                'montant' => 0
            ];
        }
        $stats['tiers_top'][$key]['count']++;
    }
    
    // DateLet (CRITIQUE)
    if (!empty($row['datelet'])) {
        $stats['avec_datelet']++;
    }
    
    // EcritureLet (CRITIQUE)
    if (!empty($row['ecriturelet'])) {
        $stats['avec_lettrage']++;
    }
    
    // Montants
    $debit = (float)str_replace(',', '.', $row['debit'] ?? '0');
    $credit = (float)str_replace(',', '.', $row['credit'] ?? '0');
    $stats['montant_total'] += ($debit + $credit);
    
    // Journaux
    if (isset($row['journalcode'])) {
        $stats['journaux'][$row['journalcode']] = 
            ($stats['journaux'][$row['journalcode']] ?? 0) + 1;
    }
}

echo "✓ Écritures parsées: " . number_format($stats['total']) . "\n";
echo "✓ Erreurs parsing: " . $errors . "\n";
echo "✓ Tiers identifiés: " . number_format($stats['avec_tiers']) . 
     " (" . round(100 * $stats['avec_tiers'] / max(1, $stats['total']), 1) . "%)\n";
echo "✓ DateLet présent: " . number_format($stats['avec_datelet']) . 
     " (" . round(100 * $stats['avec_datelet'] / max(1, $stats['total']), 1) . "%)\n";
echo "✓ EcritureLet présent: " . number_format($stats['avec_lettrage']) . 
     " (" . round(100 * $stats['avec_lettrage'] / max(1, $stats['total']), 1) . "%)\n";
echo "✓ Montant total: €" . number_format($stats['montant_total'], 2) . "\n";

// Top tiers
echo "\n👥 TOP 10 TIERS:\n";
uasort($stats['tiers_top'], fn($a, $b) => $b['count'] <=> $a['count']);
$top = 0;
foreach ($stats['tiers_top'] as $num => $data) {
    if ($top++ >= 10) break;
    echo sprintf(
        "  %2d. %s: %d écritures\n",
        $top,
        substr($data['lib'] ?: $num, 0, 35),
        $data['count']
    );
}

// Journaux
echo "\n📋 JOURNAUX:\n";
foreach ($stats['journaux'] as $code => $count) {
    echo sprintf("  - %-3s: %7d écritures\n", $code, $count);
}

// VERDICT
echo "\n═══════════════════════════════════════════════════════\n";
echo "✨ VERDICT NOUVEAU FEC (fec_2024.txt)\n";
echo "═══════════════════════════════════════════════════════\n";

$verdict = [];
$statusOK = true;

if ($headerCount === 18) {
    $verdict[] = "✅ 18 colonnes présentes";
} else {
    $verdict[] = "❌ " . $headerCount . " colonnes (attendu 18)";
    $statusOK = false;
}

if ($errors === 0) {
    $verdict[] = "✅ Aucune erreur parsing";
} else {
    $verdict[] = "⚠️  " . $errors . " lignes mal formées";
}

if ($stats['avec_tiers'] > 0) {
    $verdict[] = "✅ Tiers identifiés";
} else {
    $verdict[] = "❌ Pas de tiers";
    $statusOK = false;
}

if ($stats['avec_datelet'] > 0) {
    $verdict[] = "✅ DateLet présent";
} else {
    $verdict[] = "⚠️  DateLet absent/vide";
}

if ($stats['avec_lettrage'] > 0) {
    $verdict[] = "✅ EcritureLet présent";
} else {
    $verdict[] = "⚠️  EcritureLet absent/vide";
}

foreach ($verdict as $v) {
    echo $v . "\n";
}

echo "\n→ STATUS: " . ($statusOK && $stats['total'] > 10000 ? "✅ PRÊT POUR PHASE 3" : "⚠️  À VÉRIFIER") . "\n";
echo "═══════════════════════════════════════════════════════\n";
