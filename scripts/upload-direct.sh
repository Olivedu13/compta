#!/bin/bash

# ========================================
# Upload direct SFTP vers Ionos
# compta.sarlatc.com → SFTP root = /compta (webroot)
# ========================================

SFTP_HOST="home210120109.1and1-data.host"
SFTP_USER="acc1249301374"
SFTP_PASS='userCompta!90127452?'
LOCAL="/workspaces/compta"

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${YELLOW}=== Upload SFTP Direct vers compta.sarlatc.com ===${NC}"
echo -e "Hôte  : ${CYAN}$SFTP_HOST${NC}"
echo -e "SFTP root = /compta (webroot)"
echo ""

# Build frontend si nécessaire
if [ ! -f "$LOCAL/public_html/assets/index.js" ]; then
    echo -e "${YELLOW}Build frontend...${NC}"
    cd "$LOCAL/frontend" && npm run build
    cd "$LOCAL"
fi

# Batch SFTP — Le SFTP atterrit dans /compta qui EST le webroot
SFTP_BATCH="/tmp/sftp_upload_$$.batch"
cat > "$SFTP_BATCH" << 'EOF'
# ─── Structure API ───
-mkdir api
-mkdir api/v1
-mkdir api/v1/accounting
-mkdir api/v1/analytics
-mkdir api/v1/balance
-mkdir api/v1/cashflow
-mkdir api/v1/kpis
-mkdir api/v1/sig
-mkdir api/v1/years
-mkdir api/v1/expenses
-mkdir api/v1/ai
-mkdir api/auth

# ─── Structure Backend ───
-mkdir backend
-mkdir backend/config
-mkdir backend/services
-mkdir backend/validators
-mkdir backend/logs

# ─── Structure Assets ───
-mkdir assets

# ═══════════════════════════════════════
# FICHIERS RACINE (webroot)
# ═══════════════════════════════════════
put /workspaces/compta/public_html/.htaccess .htaccess
put /workspaces/compta/public_html/.user.ini .user.ini
put /workspaces/compta/public_html/index.html index.html
put /workspaces/compta/public_html/bootstrap.php bootstrap.php

# ═══════════════════════════════════════
# API - Routing & Import
# ═══════════════════════════════════════
put /workspaces/compta/public_html/api/index.php api/index.php
put /workspaces/compta/public_html/api/simple-import.php api/simple-import.php
put /workspaces/compta/public_html/api/v1/index.php api/v1/index.php

# ═══════════════════════════════════════
# API v1 - Tous les endpoints
# ═══════════════════════════════════════
put /workspaces/compta/public_html/api/v1/sig/simple.php api/v1/sig/simple.php
put /workspaces/compta/public_html/api/v1/accounting/sig.php api/v1/accounting/sig.php
put /workspaces/compta/public_html/api/v1/accounting/accounts.php api/v1/accounting/accounts.php
put /workspaces/compta/public_html/api/v1/accounting/ledger.php api/v1/accounting/ledger.php
put /workspaces/compta/public_html/api/v1/accounting/balance.php api/v1/accounting/balance.php
put /workspaces/compta/public_html/api/v1/accounting/years.php api/v1/accounting/years.php
put /workspaces/compta/public_html/api/v1/cashflow/simple.php api/v1/cashflow/simple.php
put /workspaces/compta/public_html/api/v1/balance/simple.php api/v1/balance/simple.php
put /workspaces/compta/public_html/api/v1/kpis/detailed.php api/v1/kpis/detailed.php
put /workspaces/compta/public_html/api/v1/kpis/financial.php api/v1/kpis/financial.php
put /workspaces/compta/public_html/api/v1/expenses/deep-dive.php api/v1/expenses/deep-dive.php
put /workspaces/compta/public_html/api/v1/expenses/lines.php api/v1/expenses/lines.php
put /workspaces/compta/public_html/api/v1/expenses/bank-fees.php api/v1/expenses/bank-fees.php
put /workspaces/compta/public_html/api/v1/ai/analysis.php api/v1/ai/analysis.php
put /workspaces/compta/public_html/api/v1/analytics/advanced.php api/v1/analytics/advanced.php
put /workspaces/compta/public_html/api/v1/analytics/analysis.php api/v1/analytics/analysis.php
put /workspaces/compta/public_html/api/v1/analytics/kpis.php api/v1/analytics/kpis.php
put /workspaces/compta/public_html/api/v1/years/list.php api/v1/years/list.php
-mkdir api/v1/fec
put /workspaces/compta/public_html/api/v1/fec/upload.php api/v1/fec/upload.php

# ═══════════════════════════════════════
# API Auth
# ═══════════════════════════════════════
put /workspaces/compta/public_html/api/auth/verify.php api/auth/verify.php
put /workspaces/compta/public_html/api/auth/setup_password.php api/auth/setup_password.php
put /workspaces/compta/public_html/api/auth/set_pwd.php api/auth/set_pwd.php
put /workspaces/compta/public_html/api/auth/login.php api/auth/login.php
put /workspaces/compta/public_html/api/auth/check_pwd.php api/auth/check_pwd.php

# ═══════════════════════════════════════
# Backend complet
# ═══════════════════════════════════════
put /workspaces/compta/backend/bootstrap.php backend/bootstrap.php
put /workspaces/compta/backend/config/Database.php backend/config/Database.php
put /workspaces/compta/backend/config/Router.php backend/config/Router.php
put /workspaces/compta/backend/config/Logger.php backend/config/Logger.php
put /workspaces/compta/backend/config/schema.sql backend/config/schema.sql
put /workspaces/compta/backend/config/schema_sqlite.sql backend/config/schema_sqlite.sql
put /workspaces/compta/backend/config/JwtManager.php backend/config/JwtManager.php
put /workspaces/compta/backend/config/AuthMiddleware.php backend/config/AuthMiddleware.php
put /workspaces/compta/backend/config/InputValidator.php backend/config/InputValidator.php
put /workspaces/compta/backend/services/ImportService.php backend/services/ImportService.php
put /workspaces/compta/backend/services/SigCalculator.php backend/services/SigCalculator.php
put /workspaces/compta/backend/services/FecAnalyzer.php backend/services/FecAnalyzer.php
put /workspaces/compta/backend/services/CashflowAnalyzer.php backend/services/CashflowAnalyzer.php
put /workspaces/compta/backend/validators/FECValidator.php backend/validators/FECValidator.php

# ═══════════════════════════════════════
# Frontend build (assets)
# ═══════════════════════════════════════
put /workspaces/compta/public_html/assets/index.js assets/index.js
put /workspaces/compta/public_html/assets/responsive.css assets/responsive.css

# ═══════════════════════════════════════
# Pages standalone
# ═══════════════════════════════════════
put /workspaces/compta/public_html/depenses.html depenses.html

# ═══════════════════════════════════════
# Base de données (NE PAS écraser le serveur)
# ═══════════════════════════════════════
# put /workspaces/compta/compta.db compta.db

# ═══════════════════════════════════════
# Sécurité : remplacer api.php qui fuite les clés API
# ═══════════════════════════════════════
put /workspaces/compta/public_html/api.php api.php

# ═══════════════════════════════════════
# Nettoyage fichiers obsolètes/dangereux
# ═══════════════════════════════════════
-rm metadata.json

quit
EOF

echo -e "${YELLOW}Upload en cours...${NC}"

if command -v sshpass &> /dev/null; then
    sshpass -p "$SFTP_PASS" sftp -oBatchMode=no -o StrictHostKeyChecking=no "$SFTP_USER@$SFTP_HOST" < "$SFTP_BATCH" 2>&1
    RESULT=$?
else
    echo -e "${RED}sshpass non disponible — installation...${NC}"
    sudo apt-get install -y sshpass > /dev/null 2>&1
    sshpass -p "$SFTP_PASS" sftp -oBatchMode=no -o StrictHostKeyChecking=no "$SFTP_USER@$SFTP_HOST" < "$SFTP_BATCH" 2>&1
    RESULT=$?
fi

rm -f "$SFTP_BATCH"

if [ $RESULT -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✓ Upload réussi!${NC}"
    echo ""
    echo -e "${CYAN}Vérification des endpoints :${NC}"
    echo "  curl https://compta.sarlatc.com/api/v1/years/list.php"
    echo "  curl https://compta.sarlatc.com/api/v1/sig/simple.php?exercice=2024"
    echo "  curl https://compta.sarlatc.com/api/v1/kpis/financial.php?exercice=2024"
else
    echo -e "\n${RED}✗ Erreur SFTP: code $RESULT${NC}"
fi
