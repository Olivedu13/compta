<?php
/**
 * AUDIT COMPLET DU FEC - Détection des anomalies structurelles
 * Problèmes à identifier et gérer durablement
 */

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  AUDIT FEC - DÉTECTION ANOMALIES STRUCTURELLES           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$fecFile = dirname(__DIR__) . '/fec_2024_atc.txt';
$lines = file($fecFile, FILE_SKIP_EMPTY_LINES);

echo "📊 ÉTAPE 1: Analyse structure\n";
echo "─────────────────────────────────────────────────────\n";

// En-tête
$headerLine = trim($lines[0]);
$separator = count(str_getcsv($headerLine, "\t")) > count(str_getcsv($headerLine, "|")) ? "\t" : "|";
$headers = str_getcsv($headerLine, $separator);
$headerCount = count($headers);

echo "✓ Séparateur: " . ($separator === "\t" ? "TAB" : "PIPE") . "\n";
echo "✓ Nombre de colonnes attendues: " . $headerCount . "\n";

// Analyse chaque ligne
echo "\n🔍 ÉTAPE 2: Scan anomalies par ligne\n";
echo "─────────────────────────────────────────────────────\n";

$anomalies = [
    'colonnes_manquantes' => [],
    'colonnes_extra' => [],
    'dates_invalides' => [],
    'montants_invalides' => [],
    'colonnes_vides_systematiques' => [],
    'valeurs_nulles_imprevues' => [],
];

$columnStats = array_fill(0, $headerCount, [
    'non_vide' => 0,
    'vide' => 0,
    'exemples_vides' => []
]);

$errorLines = [];

for ($i = 1; $i < count($lines) && $i < 200; $i++) {  // Scan 200 lignes
    $line = trim($lines[$i]);
    if (empty($line)) continue;
    
    $fields = str_getcsv($line, $separator);
    
    if (count($fields) != $headerCount) {
        $anomalies['colonnes_manquantes'][] = [
            'ligne' => $i + 1,
            'attendu' => $headerCount,
            'reçu' => count($fields),
            'diff' => count($fields) - $headerCount,
            'apercu' => substr($line, 0, 60)
        ];
    }
    
    // Analyse colonnes vides
    foreach ($fields as $colIdx => $value) {
        $value = trim($value);
        if (empty($value)) {
            $columnStats[$colIdx]['vide']++;
            if (count($columnStats[$colIdx]['exemples_vides']) < 3) {
                $columnStats[$colIdx]['exemples_vides'][] = "ligne " . ($i + 1);
            }
        } else {
            $columnStats[$colIdx]['non_vide']++;
        }
    }
    
    // Valide dates (colonnes 3, 9, 14)
    if (isset($fields[3]) && !empty(trim($fields[3]))) {
        $date = trim($fields[3]);
        if (!preg_match('/^\d{8}$/', $date)) {
            $anomalies['dates_invalides'][] = [
                'ligne' => $i + 1,
                'colonne' => 'EcritureDate',
                'valeur' => $date
            ];
        }
    }
    
    // Valide montants (colonnes 11, 12)
    foreach ([11, 12] as $col) {
        if (isset($fields[$col]) && !empty(trim($fields[$col]))) {
            $val = trim($fields[$col]);
            if (!preg_match('/^[0-9.,\-+]+$/', $val)) {
                $anomalies['montants_invalides'][] = [
                    'ligne' => $i + 1,
                    'colonne' => $col === 11 ? 'Debit' : 'Credit',
                    'valeur' => $val
                ];
            }
        }
    }
}

// Affiche anomalies colonnes
echo "\n📋 ANOMALIES DÉTECTÉES\n";
echo "─────────────────────────────────────────────────────\n";

if (!empty($anomalies['colonnes_manquantes'])) {
    echo "\n⚠️  COLONNES MANQUANTES:\n";
    foreach ($anomalies['colonnes_manquantes'] as $anom) {
        echo sprintf(
            "  Ligne %d: %d colonnes reçues (attendu %d) [%s %s]\n",
            $anom['ligne'],
            $anom['reçu'],
            $anom['attendu'],
            $anom['diff'] > 0 ? '+' . $anom['diff'] : $anom['diff'],
            $anom['apercu']
        );
    }
}

if (!empty($anomalies['dates_invalides'])) {
    echo "\n⚠️  DATES INVALIDES:\n";
    foreach (array_slice($anomalies['dates_invalides'], 0, 5) as $anom) {
        echo sprintf(
            "  Ligne %d %s: '%s' (attendu AAAAMMJJ)\n",
            $anom['ligne'],
            $anom['colonne'],
            $anom['valeur']
        );
    }
    if (count($anomalies['dates_invalides']) > 5) {
        echo "  ... et " . (count($anomalies['dates_invalides']) - 5) . " autres\n";
    }
}

if (!empty($anomalies['montants_invalides'])) {
    echo "\n⚠️  MONTANTS INVALIDES:\n";
    foreach (array_slice($anomalies['montants_invalides'], 0, 5) as $anom) {
        echo sprintf(
            "  Ligne %d %s: '%s' (format invalide)\n",
            $anom['ligne'],
            $anom['colonne'],
            $anom['valeur']
        );
    }
}

// Colonnes systématiquement vides
echo "\n📊 COLONNES PAR DENSITÉ (premiers 200 enregistrements):\n";
echo "─────────────────────────────────────────────────────\n";

$colCount = 0;
foreach ($columnStats as $colIdx => $stats) {
    $total = $stats['vide'] + $stats['non_vide'];
    if ($total === 0) continue;
    
    $pctVide = round(100 * $stats['vide'] / $total, 1);
    $colName = $headers[$colIdx] ?? "Col$colIdx";
    
    $status = $pctVide === 0 ? "✓ TOUJOURS" : 
              ($pctVide === 100 ? "✗ JAMAIS" : "⚠ " . $pctVide . "%");
    
    printf(
        "  [%-15s] %s remplies (%.1f%% remplies)\n",
        substr($colName, 0, 15),
        $status,
        100 - $pctVide
    );
    
    if ($pctVide >= 50) {
        $colCount++;
    }
}

echo "\n🔴 COLONNES À RISQUE (< 50% remplies): " . $colCount . " colonnes\n";

// Analyse par compte
echo "\n📊 ÉTAPE 3: Analyse par compte (tous les enregistrements)\n";
echo "─────────────────────────────────────────────────────\n";

$accounts = [];
$totalLines = 0;
$compteNumIdx = -1;

for ($i = 0; $i < count($headers); $i++) {
    if (trim(strtolower($headers[$i])) === 'comptenum') {
        $compteNumIdx = $i;
        break;
    }
}

for ($i = 1; $i < count($lines); $i++) {
    $line = trim($lines[$i]);
    if (empty($line)) continue;
    
    $fields = str_getcsv($line, $separator);
    $totalLines++;
    
    if (isset($fields[$compteNumIdx])) {
        $compte = trim($fields[$compteNumIdx]);
        if (!isset($accounts[$compte])) {
            $accounts[$compte] = ['count' => 0, 'line_nums' => []];
        }
        $accounts[$compte]['count']++;
    }
}

echo "✓ Total écritures analysées: " . $totalLines . "\n";
echo "✓ Comptes uniques: " . count($accounts) . "\n";

// Comptes à structure étrange
echo "\n📋 COMPTES AVEC STRUCTURE ANORMALE:\n";
$anomalyCount = 0;
foreach ($accounts as $compte => $data) {
    // Comptes avec lettres (au lieu de chiffres)
    if (preg_match('/[^0-9]/', $compte)) {
        echo sprintf(
            "  - '%s': %d écritures (contient des lettres)\n",
            $compte,
            $data['count']
        );
        $anomalyCount++;
    }
    // Comptes avec longueur anormale
    if (strlen($compte) > 15 || strlen($compte) < 2) {
        echo sprintf(
            "  - '%s': %d écritures (longueur anormale: %d)\n",
            $compte,
            $data['count'],
            strlen($compte)
        );
        $anomalyCount++;
    }
}
if ($anomalyCount === 0) {
    echo "  ✓ Aucun compte anormal détecté\n";
}

echo "\n═══════════════════════════════════════════════════════\n";
echo "✅ AUDIT TERMINÉ\n";
echo "\nRECOMMANDATIONS:\n";
echo "1. Gestion colonnes manquantes: PADDING avec valeurs vides\n";
echo "2. Gestion colonnes vides: Accepter comme NULL dans DB\n";
echo "3. Dates: Valider format AAAAMMJJ, rejeter si invalide\n";
echo "4. Montants: Normaliser format (virgule → point)\n";
echo "5. Comptes: Valider format numérique uniquement\n";
echo "═══════════════════════════════════════════════════════\n";
