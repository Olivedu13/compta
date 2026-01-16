#!/bin/bash

# TEST COMPLET DES 5 ENDPOINTS

echo "═══════════════════════════════════════════════════════════════"
echo "TEST ENDPOINTS API - 16 JANVIER 2026"
echo "═══════════════════════════════════════════════════════════════"
echo ""

PROJECT_ROOT="/workspaces/compta"
DB_PATH="$PROJECT_ROOT/compta.db"
API_BASE="$PROJECT_ROOT/public_html/api/v1"

# Tester avec PHP CLI en simulant un appel HTTP

test_endpoint() {
    local name="$1"
    local file="$2"
    local params="$3"
    
    echo "🧪 Testant: $name"
    echo "   Fichier: $file"
    
    # Utiliser PHP CLI pour simuler l'appel
    php -d xdebug.mode=off << EOFPHP
<?php
\$_GET['exercice'] = 2024;
$params

// Supprimer les headers pour ne pas avoir d'erreur
if (!function_exists('header_remove')) {
    function header_remove() {}
}
@ini_set('display_errors', 0);

// Capturer la réponse
ob_start();
include('$file');
\$response = ob_get_clean();

// Parser JSON
\$json = json_decode(\$response, true);

if (\$json === null) {
    echo "   ❌ ERREUR: Réponse non JSON\n";
    echo "   " . substr(\$response, 0, 200) . "\n";
    exit(1);
}

if (!\$json['success']) {
    echo "   ❌ ERREUR: " . (\$json['error'] ?? 'Inconnue') . "\n";
    exit(1);
}

// Vérifier la structure
\$data = \$json['data'] ?? [];

echo "   ✅ Succès\n";
echo "   Exercice: " . (\$data['exercice'] ?? 'N/A') . "\n";

// Afficher les clés principales
\$keys = array_keys(\$data);
echo "   Clés: " . implode(", ", array_slice(\$keys, 0, 5)) . "\n";
?>
EOFPHP
    
    echo ""
}

# Tests
test_endpoint "1. KPIs Détaillés" "$API_BASE/kpis/detailed.php"
test_endpoint "2. Balance Simple" "$API_BASE/balance/simple.php"
test_endpoint "3. Analytics KPIs" "$API_BASE/analytics/kpis.php"
test_endpoint "4. Analytics Analysis" "$API_BASE/analytics/analysis.php"
test_endpoint "5. Analytics Advanced" "$API_BASE/analytics/advanced.php"

echo "═══════════════════════════════════════════════════════════════"
echo "RÉSUMÉ"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "Vérifier:"
echo "1. ✅ Tous les endpoints retournent success: true"
echo "2. ✅ Les données contiennent les champs attendus"
echo "3. ✅ Pas de doublons entre endpoints"
echo ""
