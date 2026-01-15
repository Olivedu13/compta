#!/bin/bash

echo "╔════════════════════════════════════════════════════════════╗"
echo "║  TEST COMPLET API TIERS + CASHFLOW                       ║"
echo "╚════════════════════════════════════════════════════════════╝"

BASE_URL="http://localhost:8080/api"
EXERCICE="2024"

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

test_api() {
    local method=$1
    local endpoint=$2
    local description=$3
    
    echo -e "\n${GREEN}✓${NC} $method $endpoint"
    echo "  $description"
    
    local url="$BASE_URL$endpoint"
    local response=$(curl -s "$url")
    
    if echo "$response" | jq empty 2>/dev/null; then
        local status=$(echo "$response" | jq -r '.success // .status // "N/A"' 2>/dev/null)
        if [[ "$status" == "OK" ]] || [[ "$status" == "true" ]]; then
            echo "  ${GREEN}Success${NC}"
            echo "$response"
        else
            echo "  ${RED}Error: $(echo $response | jq -r '.error' 2>/dev/null)${NC}"
        fi
    else
        echo "  ${RED}Invalid JSON response${NC}"
        echo "$response" | head -5
    fi
}

echo ""
echo "═══════════════════════════════════════════════════════"
echo "API TIERS"
echo "═══════════════════════════════════════════════════════"

test_api "GET" "/tiers?exercice=$EXERCICE&limit=3" \
    "Récupère les 3 premiers tiers triés par montant"

test_api "GET" "/tiers?exercice=$EXERCICE&limit=3&tri=nom" \
    "Récupère 3 tiers triés par nom"

test_api "GET" "/tiers/08000009?exercice=$EXERCICE" \
    "Détail du tiers 08000009 (GOLDMAN DIAMONDS) avec ses 272 écritures"

echo ""
echo "═══════════════════════════════════════════════════════"
echo "API CASHFLOW"
echo "═══════════════════════════════════════════════════════"

test_api "GET" "/cashflow?exercice=$EXERCICE&periode=mois" \
    "Analyse cashflow par mois avec flux global par journal"

test_api "GET" "/cashflow/detail/VE?exercice=$EXERCICE" \
    "Détail cashflow du journal VE (Ventes) avec top comptes"

test_api "GET" "/cashflow/detail/AC?exercice=$EXERCICE" \
    "Détail cashflow du journal AC (Achats)"

echo ""
echo "═══════════════════════════════════════════════════════"
echo "✅ TESTS TERMINÉS"
echo "═══════════════════════════════════════════════════════"
