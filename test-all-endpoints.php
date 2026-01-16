<?php
/**
 * Test complet de tous les endpoints KPI
 */

$baseUrl = 'http://localhost'; // À ajuster selon votre setup

$endpoints = [
    '/api/v1/kpis/detailed.php?exercice=2024',
    '/api/v1/analytics/kpis.php?exercice=2024',
    '/api/v1/analytics/analysis.php?exercice=2024',
    '/api/v1/analytics/advanced.php?exercice=2024',
    '/api/v1/cashflow/simple.php?exercice=2024'
];

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║          TEST DES ENDPOINTS KPI                              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

foreach ($endpoints as $endpoint) {
    echo "Testing: $endpoint\n";
    
    // Test avec curl
    $url = "$baseUrl$endpoint";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = @curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false) {
        echo "   ❌ ERREUR: Impossible de se connecter\n";
    } else if ($httpCode >= 200 && $httpCode < 300) {
        $data = json_decode($response, true);
        if (is_array($data)) {
            echo "   ✅ HTTP $httpCode - JSON valide\n";
            if (isset($data['success'])) {
                echo "      Success: " . ($data['success'] ? 'YES' : 'NO') . "\n";
            }
            if (isset($data['data'])) {
                echo "      Data keys: " . implode(', ', array_keys($data['data'])) . "\n";
            }
        } else {
            echo "   ⚠️  HTTP $httpCode - Réponse non-JSON\n";
        }
    } else {
        echo "   ❌ HTTP $httpCode - Erreur serveur\n";
        if (strlen($response) < 200) {
            echo "      Réponse: $response\n";
        }
    }
    echo "\n";
}

