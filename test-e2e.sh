#!/bin/bash

##################################################################
# TEST E2E COMPLET - PHASE 6
# Tests tous les endpoints de l'application
# Validation des données en temps réel
##################################################################

set -e

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Statistiques
TESTS_TOTAL=0
TESTS_PASSES=0
TESTS_ECHOUES=0

# Fonctions utilitaires
test_endpoint() {
  local nom="$1"
  local method="$2"
  local endpoint="$3"
  local expected_code="$4"
  
  TESTS_TOTAL=$((TESTS_TOTAL + 1))
  
  echo -e "\n${BLUE}TEST $TESTS_TOTAL: $nom${NC}"
  echo "  Méthode: $method"
  echo "  Endpoint: $endpoint"
  
  if [ "$method" = "GET" ]; then
    response=$(curl -s -w "\n%{http_code}" -X GET "http://localhost$endpoint")
  else
    response=$(curl -s -w "\n%{http_code}" -X POST "http://localhost$endpoint" -H "Content-Type: application/json" -d '{}')
  fi
  
  http_code=$(echo "$response" | tail -n1)
  body=$(echo "$response" | sed '$d')
  
  echo "  Statut HTTP: $http_code (attendu: $expected_code)"
  
  if [ "$http_code" = "$expected_code" ]; then
    echo -e "  ${GREEN}✓ PASSÉ${NC}"
    TESTS_PASSES=$((TESTS_PASSES + 1))
  else
    echo -e "  ${RED}✗ ÉCHOUÉ${NC}"
    TESTS_ECHOUES=$((TESTS_ECHOUES + 1))
  fi
}

test_json_response() {
  local nom="$1"
  local endpoint="$2"
  local field="$3"
  
  TESTS_TOTAL=$((TESTS_TOTAL + 1))
  
  echo -e "\n${BLUE}TEST $TESTS_TOTAL: $nom${NC}"
  echo "  Endpoint: $endpoint"
  echo "  Champ vérifié: $field"
  
  response=$(curl -s "$endpoint")
  
  if echo "$response" | jq -e "$field" > /dev/null 2>&1; then
    value=$(echo "$response" | jq -r "$field")
    echo "  Valeur trouvée: $value"
    echo -e "  ${GREEN}✓ PASSÉ${NC}"
    TESTS_PASSES=$((TESTS_PASSES + 1))
  else
    echo -e "  ${RED}✗ ÉCHOUÉ - Champ non trouvé${NC}"
    TESTS_ECHOUES=$((TESTS_ECHOUES + 1))
  fi
}

# =========================================
# TESTS - SANTÉ DE L'APPLICATION
# =========================================

echo -e "\n${YELLOW}╔════════════════════════════════════════╗${NC}"
echo -e "${YELLOW}║  TESTS E2E - APPLICATION COMPTA        ║${NC}"
echo -e "${YELLOW}╚════════════════════════════════════════╝${NC}"

echo -e "\n${YELLOW}📌 1. SANTÉ DE L'APPLICATION${NC}"
test_endpoint "Health Check" "GET" "/api/health" "200"

# =========================================
# TESTS - PHASE 3 APIs
# =========================================

echo -e "\n${YELLOW}📌 2. PHASE 3 APIs - TIERS${NC}"
test_endpoint "GET /tiers" "GET" "/api/tiers?exercice=2024&limit=5" "200"
test_json_response "Vérifier structure /tiers" "http://localhost/api/tiers?exercice=2024&limit=5" ".success"
test_json_response "Vérifier pagination /tiers" "http://localhost/api/tiers?exercice=2024&limit=5" ".pagination"
test_json_response "Vérifier données tiers" "http://localhost/api/tiers?exercice=2024&limit=5" ".tiers[0]"

echo -e "\n${YELLOW}📌 3. PHASE 3 APIs - TIERS DETAIL${NC}"
test_endpoint "GET /tiers/:numero" "GET" "/api/tiers/08000009?exercice=2024" "200"
test_json_response "Vérifier détail tiers" "http://localhost/api/tiers/08000009?exercice=2024" ".tiers"
test_json_response "Vérifier écritures" "http://localhost/api/tiers/08000009?exercice=2024" ".ecritures"

echo -e "\n${YELLOW}📌 4. PHASE 3 APIs - CASHFLOW${NC}"
test_endpoint "GET /cashflow" "GET" "/api/cashflow?exercice=2024&periode=mois" "200"
test_json_response "Vérifier stats globales" "http://localhost/api/cashflow?exercice=2024&periode=mois" ".stats_globales"
test_json_response "Vérifier par_periode" "http://localhost/api/cashflow?exercice=2024&periode=mois" ".par_periode"
test_json_response "Vérifier par_journal" "http://localhost/api/cashflow?exercice=2024&periode=mois" ".par_journal"

echo -e "\n${YELLOW}📌 5. PHASE 3 APIs - CASHFLOW DETAIL${NC}"
test_endpoint "GET /cashflow/detail/VE" "GET" "/api/cashflow/detail/VE?exercice=2024" "200"
test_json_response "Vérifier stats journal" "http://localhost/api/cashflow/detail/VE?exercice=2024" ".stats"
test_json_response "Vérifier flux_par_jour" "http://localhost/api/cashflow/detail/VE?exercice=2024" ".flux_par_jour"
test_json_response "Vérifier top_comptes" "http://localhost/api/cashflow/detail/VE?exercice=2024" ".top_comptes"

# =========================================
# TESTS - DONNÉES INTÉGRITÉ
# =========================================

echo -e "\n${YELLOW}📌 6. INTÉGRITÉ DES DONNÉES${NC}"

# Vérifier balance parfaite
BALANCE=$(curl -s "http://localhost/api/cashflow?exercice=2024&periode=mois" | jq '.stats_globales.flux_net_total')
if [ "$BALANCE" = "0" ] || [ "$BALANCE" = "0.0" ]; then
  echo -e "  ${GREEN}✓ Balance parfaite (€0.00)${NC}"
  TESTS_PASSES=$((TESTS_PASSES + 1))
else
  echo -e "  ${RED}✗ Balance déséquilibrée: $BALANCE${NC}"
  TESTS_ECHOUES=$((TESTS_ECHOUES + 1))
fi
TESTS_TOTAL=$((TESTS_TOTAL + 1))

# Vérifier nombre tiers
NB_TIERS=$(curl -s "http://localhost/api/tiers?exercice=2024&limit=1000" | jq '.pagination.total')
if [ "$NB_TIERS" -gt 0 ]; then
  echo -e "  ${GREEN}✓ Tiers trouvés: $NB_TIERS${NC}"
  TESTS_PASSES=$((TESTS_PASSES + 1))
else
  echo -e "  ${RED}✗ Aucun tiers trouvé${NC}"
  TESTS_ECHOUES=$((TESTS_ECHOUES + 1))
fi
TESTS_TOTAL=$((TESTS_TOTAL + 1))

# Vérifier nombre écritures
NB_ECRITURES=$(curl -s "http://localhost/api/cashflow?exercice=2024&periode=mois" | jq '.par_periode | map(.nb_ecritures) | add')
if [ "$NB_ECRITURES" -gt 0 ]; then
  echo -e "  ${GREEN}✓ Écritures totales: $NB_ECRITURES${NC}"
  TESTS_PASSES=$((TESTS_PASSES + 1))
else
  echo -e "  ${RED}✗ Aucune écriture trouvée${NC}"
  TESTS_ECHOUES=$((TESTS_ECHOUES + 1))
fi
TESTS_TOTAL=$((TESTS_TOTAL + 1))

# =========================================
# TESTS - PERFORMANCE
# =========================================

echo -e "\n${YELLOW}📌 7. PERFORMANCE${NC}"

# Test temps réponse /tiers
TIME_TIERS=$(curl -s -w "%{time_total}" -o /dev/null "http://localhost/api/tiers?exercice=2024&limit=100")
echo -e "  GET /tiers: ${TIME_TIERS}s"
if (( $(echo "$TIME_TIERS < 1" | bc -l) )); then
  echo -e "  ${GREEN}✓ Performance acceptable${NC}"
  TESTS_PASSES=$((TESTS_PASSES + 1))
else
  echo -e "  ${YELLOW}⚠ Performance lente (>1s)${NC}"
  TESTS_ECHOUES=$((TESTS_ECHOUES + 1))
fi
TESTS_TOTAL=$((TESTS_TOTAL + 1))

# Test temps réponse /cashflow
TIME_CASHFLOW=$(curl -s -w "%{time_total}" -o /dev/null "http://localhost/api/cashflow?exercice=2024&periode=mois")
echo -e "  GET /cashflow: ${TIME_CASHFLOW}s"
if (( $(echo "$TIME_CASHFLOW < 2" | bc -l) )); then
  echo -e "  ${GREEN}✓ Performance acceptable${NC}"
  TESTS_PASSES=$((TESTS_PASSES + 1))
else
  echo -e "  ${YELLOW}⚠ Performance lente (>2s)${NC}"
  TESTS_ECHOUES=$((TESTS_ECHOUES + 1))
fi
TESTS_TOTAL=$((TESTS_TOTAL + 1))

# =========================================
# RÉSUMÉ
# =========================================

echo -e "\n${YELLOW}╔════════════════════════════════════════╗${NC}"
echo -e "${YELLOW}║  RÉSUMÉ DES TESTS                      ║${NC}"
echo -e "${YELLOW}╚════════════════════════════════════════╝${NC}"

TAUX_SUCCES=$((TESTS_PASSES * 100 / TESTS_TOTAL))

echo -e "\n  Total tests: ${BLUE}$TESTS_TOTAL${NC}"
echo -e "  Passés: ${GREEN}$TESTS_PASSES${NC}"
echo -e "  Échoués: ${RED}$TESTS_ECHOUES${NC}"
echo -e "  Taux de succès: ${BLUE}${TAUX_SUCCES}%${NC}"

if [ $TESTS_ECHOUES -eq 0 ]; then
  echo -e "\n${GREEN}✓ TOUS LES TESTS PASSÉS!${NC}"
  exit 0
else
  echo -e "\n${RED}✗ TESTS ÉCHOUÉS${NC}"
  exit 1
fi
