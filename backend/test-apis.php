<?php
/**
 * TEST DES APIs TIERS ET CASHFLOW
 */

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  TEST APIs - TIERS + CASHFLOW                            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$baseUrl = "http://localhost/api";
$exercice = 2024;

// Test 1: GET /api/tiers
echo "📋 TEST 1: GET /api/tiers\n";
echo "─────────────────────────────────────────────────────\n";

$url = "$baseUrl/tiers?exercice=$exercice&limit=10&tri=montant";
echo "URL: $url\n";

$response = @file_get_contents($url);
if ($response === false) {
    echo "❌ Connexion échouée - serveur pas lancé\n";
    echo "Lancez: php -S localhost:80 -t /workspaces/compta/public_html\n";
    exit(1);
}

$data = json_decode($response, true);
if ($data['success'] ?? false) {
    echo "✓ Récupéré " . count($data['tiers']) . " tiers\n";
    echo "✓ Total: " . $data['pagination']['total'] . " tiers\n";
    
    if (!empty($data['tiers'])) {
        echo "\nTop 3 tiers:\n";
        foreach (array_slice($data['tiers'], 0, 3) as $i => $t) {
            echo sprintf(
                "  %d. %s: %d écritures, €%.2f (solde: €%.2f)\n",
                $i + 1,
                substr($t['libelle'], 0, 40),
                $t['nb_ecritures'],
                $t['total_montant'],
                $t['solde']
            );
        }
    }
} else {
    echo "❌ Erreur: " . ($data['error'] ?? 'Inconnue') . "\n";
    print_r($data);
}

// Test 2: GET /api/tiers/:numero
echo "\n📋 TEST 2: GET /api/tiers/:numero\n";
echo "─────────────────────────────────────────────────────\n";

if (!empty($data['tiers'][0])) {
    $tierNum = $data['tiers'][0]['numero'];
    $url = "$baseUrl/tiers/$tierNum?exercice=$exercice";
    echo "URL: $url\n";
    
    $response = @file_get_contents($url);
    $tierData = json_decode($response, true);
    
    if ($tierData['success'] ?? false) {
        $t = $tierData['tiers'];
        echo "✓ Tiers: " . $t['libelle'] . "\n";
        echo "  - Solde: €" . number_format($t['solde'], 2) . "\n";
        echo "  - Écritures: " . $t['nb_ecritures'] . "\n";
        echo "  - Lettrage: " . $t['nb_ecritures'] . " lettrées\n";
        echo "  - Période: " . $t['date_premiere'] . " à " . $t['date_derniere'] . "\n";
    } else {
        echo "❌ Erreur: " . ($tierData['error'] ?? 'Inconnue') . "\n";
    }
}

// Test 3: GET /api/cashflow
echo "\n📊 TEST 3: GET /api/cashflow\n";
echo "─────────────────────────────────────────────────────\n";

$url = "$baseUrl/cashflow?exercice=$exercice&periode=mois";
echo "URL: $url\n";

$response = @file_get_contents($url);
$cashData = json_decode($response, true);

if ($cashData['success'] ?? false) {
    $g = $cashData['stats_globales'];
    echo "✓ Flux global:\n";
    echo "  - Entrées: €" . number_format($g['total_entrees'], 2) . "\n";
    echo "  - Sorties: €" . number_format($g['total_sorties'], 2) . "\n";
    echo "  - Flux net: €" . number_format($g['flux_net_total'], 2) . "\n";
    
    echo "\n✓ Par mois:\n";
    foreach (array_slice($cashData['par_periode'], 0, 5) as $p) {
        echo sprintf(
            "  %s: Entrées €%.2f | Sorties €%.2f | Net €%.2f\n",
            $p['periode'],
            $p['entrees'],
            $p['sorties'],
            $p['flux_net']
        );
    }
    
    echo "\n✓ Par journal:\n";
    foreach ($cashData['par_journal'] as $j) {
        echo sprintf(
            "  %s: Net €%.2f (%d écritures)\n",
            $j['journal'],
            $j['flux_net'],
            $j['nb_ecritures']
        );
    }
} else {
    echo "❌ Erreur: " . ($cashData['error'] ?? 'Inconnue') . "\n";
    print_r($cashData);
}

// Test 4: GET /api/cashflow/detail/:journal
echo "\n📊 TEST 4: GET /api/cashflow/detail/:journal\n";
echo "─────────────────────────────────────────────────────\n";

if (!empty($cashData['par_journal'][0])) {
    $journal = $cashData['par_journal'][0]['journal'];
    $url = "$baseUrl/cashflow/detail/$journal?exercice=$exercice";
    echo "URL: $url\n";
    
    $response = @file_get_contents($url);
    $detailData = json_decode($response, true);
    
    if ($detailData['success'] ?? false) {
        $s = $detailData['stats'];
        echo "✓ Journal: " . $journal . "\n";
        echo "  - Solde: €" . number_format($s['solde'], 2) . "\n";
        echo "  - Écritures: " . $s['nb_ecritures'] . "\n";
        echo "  - Jours actifs: " . $s['nb_jours_actifs'] . "\n";
        echo "  - Période: " . $s['date_debut'] . " à " . $s['date_fin'] . "\n";
        
        echo "\n✓ Top 3 comptes:\n";
        foreach (array_slice($detailData['top_comptes'], 0, 3) as $c) {
            echo sprintf(
                "  %s (%s): €%.2f\n",
                $c['compte'],
                substr($c['libelle'], 0, 30),
                $c['solde']
            );
        }
    } else {
        echo "❌ Erreur: " . ($detailData['error'] ?? 'Inconnue') . "\n";
    }
}

echo "\n═══════════════════════════════════════════════════════\n";
echo "✅ TESTS API TERMINÉS\n";
echo "═══════════════════════════════════════════════════════\n";
