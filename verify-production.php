<?php
/**
 * Vérification de la production
 * Teste les endpoints sur le serveur déployé
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$server = 'compta.sarlatc.com';
$endpoints = [
    '/api/v1/kpis/detailed.php?exercice=2024',
    '/api/v1/balance/simple.php?exercice=2024',
    '/api/v1/analytics/kpis.php?exercice=2024',
    '/api/v1/analytics/analysis.php?exercice=2024',
    '/api/v1/analytics/advanced.php?exercice=2024',
];

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║          🧪 VÉRIFICATION PRODUCTION                           ║\n";
echo "║          Server: $server                    ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

foreach ($endpoints as $endpoint) {
    $url = "https://$server$endpoint";
    echo "🧪 Endpoint: $endpoint\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        echo "   ✅ HTTP 200 OK\n";
        
        $data = json_decode($response, true);
        if ($data) {
            echo "   ✅ JSON valide\n";
            
            // Affiche un aperçu
            if (is_array($data)) {
                $keys = array_keys($data);
                echo "   📊 Clés: " . implode(', ', array_slice($keys, 0, 5)) . "\n";
            }
        } else {
            echo "   ⚠️  Réponse: " . substr($response, 0, 100) . "...\n";
        }
    } else {
        echo "   ❌ HTTP $http_code\n";
        echo "   Erreur: " . substr($response, 0, 200) . "\n";
    }
    
    echo "\n";
}

echo "✅ Vérification terminée\n\n";
