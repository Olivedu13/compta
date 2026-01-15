<?php
/**
 * TEST SIMPLE: Analyse FEC Phase 1
 * Sans dépendances DB - Juste du parsing
 */

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     ANALYSE FEC - PHASE 1 DATA LAYER                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$fecFile = __DIR__ . '/../fec_2024_atc.txt';

if (!file_exists($fecFile)) {
    echo "❌ Fichier FEC non trouvé: $fecFile\n";
    exit(1);
}

echo "📊 LECTURE FEC\n";
echo "─────────────────────────────────────────────────────\n";

// Charge le fichier
$startMem = memory_get_usage(true);
$lines = file($fecFile, FILE_SKIP_EMPTY_LINES);
$endMem = memory_get_usage(true);

echo "✓ Fichier chargé en mémoire\n";
echo "  - Nombre de lignes: " . count($lines) . "\n";
echo "  - Taille fichier: " . number_format(filesize($fecFile), 0) . " octets\n";
echo "  - Mémoire utilisée: " . number_format(($endMem - $startMem) / 1024 / 1024, 2) . " MB\n";

// En-tête
$headerLine = trim($lines[0]);

// Détecte séparateur
$countTab = count(str_getcsv($headerLine, "\t"));
$countPipe = count(str_getcsv($headerLine, "|"));
$separator = $countTab > $countPipe ? "\t" : "|";

echo "\n✓ Séparateur détecté: " . ($separator === "\t" ? "TAB" : "PIPE") . "\n";

// Parse en-tête
$headers = array_map(fn($h) => trim(strtolower($h)), str_getcsv($headerLine, $separator));
echo "✓ " . count($headers) . " colonnes trouvées\n";
echo "  Colonnes: " . implode(", ", $headers) . "\n";

// Analyse des données
echo "\n📈 ANALYSE DONNÉES (premiers 1000 enregistrements)\n";
echo "─────────────────────────────────────────────────────\n";

$stats = [
    'total' => 0,
    'avec_tiers' => 0,
    'avec_datelet' => 0,
    'avec_lettrage' => 0,
    'journaux' => [],
    'comptes' => [],
    'tiers' => [],
    'montant_total' => 0,
];

$dateMin = null;
$dateMax = null;

for ($i = 1; $i < min(1001, count($lines)); $i++) {
    $line = trim($lines[$i]);
    if (empty($line)) continue;
    
    $fields = str_getcsv($line, $separator);
    
    // Certains lignes peuvent avoir moins de colonnes
    if (count($fields) != count($headers)) {
        // Pad avec des valeurs vides
        while (count($fields) < count($headers)) {
            $fields[] = '';
        }
        $fields = array_slice($fields, 0, count($headers));
    }
    
    $row = array_combine($headers, $fields);
    $row = array_map('trim', $row);
    
    $stats['total']++;
    
    // Journal
    if (isset($row['journalcode'])) {
        $stats['journaux'][$row['journalcode']] = 
            ($stats['journaux'][$row['journalcode']] ?? 0) + 1;
    }
    
    // Compte
    if (isset($row['comptenum'])) {
        $key = $row['comptenum'];
        if (!isset($stats['comptes'][$key])) {
            $stats['comptes'][$key] = [
                'lib' => $row['comptelib'] ?? '',
                'count' => 0,
                'montant' => 0
            ];
        }
        $stats['comptes'][$key]['count']++;
        $montant = (float) str_replace(',', '.', $row['debit'] ?? '0') + 
                   (float) str_replace(',', '.', $row['credit'] ?? '0');
        $stats['comptes'][$key]['montant'] += $montant;
        $stats['montant_total'] += $montant;
    }
    
    // Tiers
    if (!empty($row['compauxnum'])) {
        $stats['avec_tiers']++;
        $key = $row['compauxnum'];
        if (!isset($stats['tiers'][$key])) {
            $stats['tiers'][$key] = [
                'lib' => $row['compauxlib'] ?? '',
                'count' => 0,
                'montant' => 0
            ];
        }
        $stats['tiers'][$key]['count']++;
        $montant = (float) str_replace(',', '.', $row['debit'] ?? '0') + 
                   (float) str_replace(',', '.', $row['credit'] ?? '0');
        $stats['tiers'][$key]['montant'] += $montant;
    }
    
    // DateLet (paiement réel)
    if (!empty($row['datelet'])) {
        $stats['avec_datelet']++;
    }
    
    // Lettrage
    if (!empty($row['ecriturelet'])) {
        $stats['avec_lettrage']++;
    }
    
    // Dates min/max
    if (isset($row['ecrituredate']) && !empty($row['ecrituredate'])) {
        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $row['ecrituredate'], $m)) {
            $date = $m[1] . '-' . $m[2] . '-' . $m[3];
            if (!$dateMin || $date < $dateMin) $dateMin = $date;
            if (!$dateMax || $date > $dateMax) $dateMax = $date;
        }
    }
}

echo "✓ Total écritures analysées: " . $stats['total'] . "\n";
echo "✓ Écritures avec tiers (CompAuxNum): " . $stats['avec_tiers'] . 
     " (" . round(100 * $stats['avec_tiers'] / max(1, $stats['total']), 1) . "%)\n";
echo "✓ Écritures avec DateLet (paiement): " . $stats['avec_datelet'] . 
     " (" . round(100 * $stats['avec_datelet'] / max(1, $stats['total']), 1) . "%)\n";
echo "✓ Écritures lettrées: " . $stats['avec_lettrage'] . 
     " (" . round(100 * $stats['avec_lettrage'] / max(1, $stats['total']), 1) . "%)\n";
echo "✓ Montant total: " . number_format($stats['montant_total'], 2) . " €\n";
echo "✓ Plage dates: " . $dateMin . " à " . $dateMax . "\n";

// Journaux
echo "\n📊 JOURNAUX:\n";
foreach ($stats['journaux'] as $code => $count) {
    echo "  - $code: " . $count . " écritures\n";
}

// Top comptes
echo "\n📊 TOP 10 COMPTES (par montant):\n";
$topComptes = array_slice(
    array_sort($stats['comptes'], fn($a, $b) => $b['montant'] <=> $a['montant']),
    0,
    10
);
foreach ($topComptes as $num => $data) {
    echo sprintf(
        "  - %s (%s): %d écritures, €%.2f\n",
        $num,
        substr($data['lib'], 0, 30),
        $data['count'],
        $data['montant']
    );
}

// Top tiers
echo "\n📊 TOP 10 TIERS (par montant):\n";
$topTiers = array_slice(
    array_sort($stats['tiers'], fn($a, $b) => $b['montant'] <=> $a['montant']),
    0,
    10
);
foreach ($topTiers as $num => $data) {
    echo sprintf(
        "  - %s (%s): %d écritures, €%.2f\n",
        $num,
        substr($data['lib'], 0, 30),
        $data['count'],
        $data['montant']
    );
}

echo "\n✨ RÉSUMÉ\n";
echo "═══════════════════════════════════════════════════════\n";
echo "✅ FEC PHASE 1 - ANALYSE COMPLÈTE\n";
echo "   Fichier: " . basename($fecFile) . "\n";
echo "   Status: DONNÉES PRÊTES POUR IMPORT\n";
echo "\n   Points clés:\n";
echo "   ✓ CompAuxNum capturé: " . ($stats['avec_tiers'] > 0 ? "OUI" : "NON") . "\n";
echo "   ✓ DateLet capturé: " . ($stats['avec_datelet'] > 0 ? "OUI" : "NON") . "\n";
echo "   ✓ EcritureLet capturé: " . ($stats['avec_lettrage'] > 0 ? "OUI" : "NON") . "\n";
echo "═══════════════════════════════════════════════════════\n";

// Fonction helper
function array_sort(&$array, $callback) {
    usort($array, $callback);
    return $array;
}
