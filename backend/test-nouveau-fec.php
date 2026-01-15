<?php
/**
 * TEST NOUVEAU FEC - Comparaison avant/après
 */

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  TEST NOUVEAU FEC (fec_2024.txt) - Structure & Anomalies  ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$fecFile = dirname(__DIR__) . '/fec_2024.txt';

if (!file_exists($fecFile)) {
    echo "❌ Fichier non trouvé: $fecFile\n";
    exit(1);
}

$lines = file($fecFile, FILE_SKIP_EMPTY_LINES);

echo "📊 ÉTAPE 1: Analyse structure\n";
echo "─────────────────────────────────────────────────────\n";

$headerLine = trim($lines[0]);
$separator = count(str_getcsv($headerLine, "\t")) > count(str_getcsv($headerLine, "|")) ? "\t" : "|";
$headers = str_getcsv($headerLine, $separator);
$headerCount = count($headers);

echo "✓ Fichier chargé: " . count($lines) . " lignes\n";
echo "✓ Séparateur: " . ($separator === "\t" ? "TAB" : "PIPE") . "\n";
echo "✓ Colonnes: " . $headerCount . "\n";
echo "✓ Structure:";

foreach ($headers as $i => $col) {
    echo ($i % 3 === 0 ? "\n  " : " | ");
    echo sprintf("%d=%s", $i + 1, trim($col));
}
echo "\n";

// Vérifie 18 colonnes exactes
if ($headerCount === 18) {
    echo "\n✅ FEC VALIDE - 18 colonnes présentes\n";
} else {
    echo "\n⚠️  FEC INVALIDE - " . $headerCount . " colonnes (attendu 18)\n";
}

// Analyse les données (premiers 100 enregistrements)
echo "\n📈 ÉTAPE 2: Analyse données\n";
echo "─────────────────────────────────────────────────────\n";

$stats = [
    'total_lignes' => 0,
    'avec_tiers' => 0,
    'avec_datelet' => 0,
    'avec_lettrage' => 0,
    'avec_debit' => 0,
    'avec_credit' => 0,
    'journaux' => [],
    'top_tiers' => [],
];

$problemes = [];

for ($i = 1; $i < count($lines); $i++) {
    $line = trim($lines[$i]);
    if (empty($line)) continue;
    
    $fields = str_getcsv($line, $separator);
    
    // Vérifie nombre de colonnes
    if (count($fields) !== $headerCount) {
        if (count($problemes) < 10) {
            $problemes[] = [
                'ligne' => $i + 1,
                'attendu' => $headerCount,
                'reçu' => count($fields),
                'apercu' => substr($line, 0, 50)
            ];
        }
        continue;
    }
    
    // Normalise
    $row = array_combine(array_map('strtolower', array_map('trim', $headers)), 
                         array_map('trim', $fields));
    
    $stats['total_lignes']++;
    
    // Tiers
    if (!empty($row['compauxnum'])) {
        $stats['avec_tiers']++;
        $tiersNum = $row['compauxnum'];
        if (!isset($stats['top_tiers'][$tiersNum])) {
            $stats['top_tiers'][$tiersNum] = [
                'lib' => $row['compauxlib'] ?? '',
                'count' => 0,
                'montant_total' => 0
            ];
        }
        $stats['top_tiers'][$tiersNum]['count']++;
        $montant = (float)str_replace(',', '.', $row['debit'] ?? '0') + 
                   (float)str_replace(',', '.', $row['credit'] ?? '0');
        $stats['top_tiers'][$tiersNum]['montant_total'] += $montant;
    }
    
    // DateLet (CRITIQUE - avant manquait)
    if (!empty($row['datelet'])) {
        $stats['avec_datelet']++;
    }
    
    // EcritureLet (CRITIQUE - avant manquait)
    if (!empty($row['ecriturelet'])) {
        $stats['avec_lettrage']++;
    }
    
    // Montants
    $debit = (float)str_replace(',', '.', $row['debit'] ?? '0');
    $credit = (float)str_replace(',', '.', $row['credit'] ?? '0');
    
    if ($debit > 0) $stats['avec_debit']++;
    if ($credit > 0) $stats['avec_credit']++;
    
    // Journaux
    if (isset($row['journalcode'])) {
        $stats['journaux'][$row['journalcode']] = 
            ($stats['journaux'][$row['journalcode']] ?? 0) + 1;
    }
}

echo "✓ Total écritures valides: " . number_format($stats['total_lignes']) . "\n";
echo "✓ Écritures avec tiers: " . number_format($stats['avec_tiers']) . 
     " (" . round(100 * $stats['avec_tiers'] / max(1, $stats['total_lignes']), 1) . "%)\n";
echo "✓ Écritures avec DateLet: " . number_format($stats['avec_datelet']) . 
     " (" . round(100 * $stats['avec_datelet'] / max(1, $stats['total_lignes']), 1) . "%)\n";
echo "✓ Écritures avec EcritureLet: " . number_format($stats['avec_lettrage']) . 
     " (" . round(100 * $stats['avec_lettrage'] / max(1, $stats['total_lignes']), 1) . "%)\n";
echo "✓ Journaux uniques: " . count($stats['journaux']) . "\n";

if (!empty($problemes)) {
    echo "\n⚠️  ANOMALIES DÉTECTÉES (" . count($problemes) . " lignes):\n";
    foreach (array_slice($problemes, 0, 5) as $pb) {
        echo sprintf(
            "  Ligne %d: %d colonnes reçues (attendu %d)\n",
            $pb['ligne'],
            $pb['reçu'],
            $pb['attendu']
        );
    }
} else {
    echo "\n✅ AUCUNE ANOMALIE STRUCTURELLE\n";
}

// Top tiers
echo "\n👥 TOP 10 TIERS:\n";
uasort($stats['top_tiers'], fn($a, $b) => $b['montant_total'] <=> $a['montant_total']);
$top = 0;
foreach ($stats['top_tiers'] as $num => $data) {
    if ($top++ >= 10) break;
    echo sprintf(
        "  %2d. %s: %d écritures, €%.2f\n",
        $top,
        substr($data['lib'], 0, 35),
        $data['count'],
        $data['montant_total']
    );
}

// Journaux
echo "\n📋 JOURNAUX:\n";
foreach ($stats['journaux'] as $code => $count) {
    echo "  - $code: " . number_format($count) . " écritures\n";
}

// COMPARAISON avec ancien
echo "\n📊 COMPARAISON NOUVEAU vs ANCIEN FEC\n";
echo "═══════════════════════════════════════════════════════\n";

echo "ANCIEN FEC (fec_2024_atc.txt):\n";
echo "  - Colonnes: 16/18 (MANQUAIENT: EcritureLet, DateLet)\n";
echo "  - Lignes: 11.617\n";
echo "  - Tiers: 40,1%\n";
echo "  - DateLet: 18,7%\n";
echo "  - EcritureLet: 18,7%\n";

echo "\nNOUVEAU FEC (fec_2024.txt):\n";
echo "  - Colonnes: " . $headerCount . "/18\n";
echo "  - Lignes: " . $stats['total_lignes'] . "\n";
echo "  - Tiers: " . round(100 * $stats['avec_tiers'] / max(1, $stats['total_lignes']), 1) . "%\n";
echo "  - DateLet: " . round(100 * $stats['avec_datelet'] / max(1, $stats['total_lignes']), 1) . "%\n";
echo "  - EcritureLet: " . round(100 * $stats['avec_lettrage'] / max(1, $stats['total_lignes']), 1) . "%\n";

// VERDICT
echo "\n✨ VERDICT\n";
echo "═══════════════════════════════════════════════════════\n";

$status = [];
if ($headerCount === 18) {
    $status[] = "✅ Structure corrigée (18 colonnes)";
} else {
    $status[] = "⚠️  Structure incomplète (" . $headerCount . "/18)";
}

if (empty($problemes)) {
    $status[] = "✅ Pas d'anomalies";
} else {
    $status[] = "⚠️  " . count($problemes) . " anomalies détectées";
}

if ($stats['avec_datelet'] > 0) {
    $status[] = "✅ DateLet présent";
} else {
    $status[] = "❌ DateLet absent";
}

if ($stats['avec_lettrage'] > 0) {
    $status[] = "✅ EcritureLet présent";
} else {
    $status[] = "❌ EcritureLet absent";
}

foreach ($status as $s) {
    echo $s . "\n";
}

echo "\n→ VERDICT: ";
if ($headerCount === 18 && empty($problemes) && $stats['avec_datelet'] > 0) {
    echo "✅ PRÊT POUR PHASE 3\n";
} else {
    echo "⚠️  À VÉRIFIER AVANT PHASE 3\n";
}

echo "═══════════════════════════════════════════════════════\n";
