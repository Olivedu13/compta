#!/bin/bash
set -e

# ========================================
# Build + Deploy SFTP vers compta.sarlatc.com
# ========================================

SFTP_HOST="home210120109.1and1-data.host"
SFTP_USER="acc1249301374"
SFTP_PASS='userCompta!90127452?'
LOCAL="/workspaces/compta"

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

echo -e "${BOLD}╔══════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║   Build & Deploy → compta.sarlatc.com    ║${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════╝${NC}"
echo ""

# ─── 1. INSTALL DEPS ───
echo -e "${YELLOW}[1/4] Installation des dépendances...${NC}"
cd "$LOCAL/frontend"
if [[ ! -d node_modules ]]; then
    npm ci --silent 2>&1
else
    echo "  → node_modules déjà présent"
fi

# ─── 2. BUILD FRONTEND ───
echo -e "${YELLOW}[2/4] Build du frontend (Vite + React)...${NC}"
npm run build 2>&1
BUILD_SIZE=$(du -h "$LOCAL/public_html/assets/index.js" | cut -f1)
echo -e "  → ${GREEN}Build OK${NC} — index.js: ${CYAN}${BUILD_SIZE}${NC}"
echo ""

# ─── 3. UPLOAD SFTP ───
echo -e "${YELLOW}[3/4] Upload SFTP...${NC}"

SFTP_BATCH="/tmp/sftp_deploy_$$.batch"
cat > "$SFTP_BATCH" << 'EOF'
# ─── Structures ───
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
-mkdir api/v1/fec
-mkdir api/auth
-mkdir backend
-mkdir backend/config
-mkdir backend/services
-mkdir backend/validators
-mkdir backend/logs
-mkdir assets

# ─── Nettoyage anciens assets ───
-rm assets/index-B0cMD3EL.js
-rm assets/index-Br9KC5Kv.css
-rm assets/index.js.map
-rm metadata.json

# ─── Fichiers racine ───
put /workspaces/compta/public_html/.htaccess .htaccess
put /workspaces/compta/public_html/.user.ini .user.ini
put /workspaces/compta/public_html/index.html index.html
put /workspaces/compta/public_html/bootstrap.php bootstrap.php
put /workspaces/compta/public_html/api.php api.php
put /workspaces/compta/public_html/depenses.html depenses.html

# ─── API Routing ───
put /workspaces/compta/public_html/api/index.php api/index.php
put /workspaces/compta/public_html/api/simple-import.php api/simple-import.php
put /workspaces/compta/public_html/api/v1/index.php api/v1/index.php

# ─── API v1 endpoints ───
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
put /workspaces/compta/public_html/api/v1/fec/upload.php api/v1/fec/upload.php

# ─── API Auth ───
put /workspaces/compta/public_html/api/auth/verify.php api/auth/verify.php
put /workspaces/compta/public_html/api/auth/setup_password.php api/auth/setup_password.php
put /workspaces/compta/public_html/api/auth/set_pwd.php api/auth/set_pwd.php
put /workspaces/compta/public_html/api/auth/login.php api/auth/login.php
put /workspaces/compta/public_html/api/auth/check_pwd.php api/auth/check_pwd.php

# ─── Backend ───
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

# ─── Assets (build frontend) ───
put /workspaces/compta/public_html/assets/index.js assets/index.js
put /workspaces/compta/public_html/assets/responsive.css assets/responsive.css

quit
EOF

# Installer sshpass si absent
if ! command -v sshpass &> /dev/null; then
    echo -e "  → Installation de sshpass..."
    sudo apt-get install -y sshpass > /dev/null 2>&1
fi

sshpass -p "$SFTP_PASS" sftp -oBatchMode=no -o StrictHostKeyChecking=no "$SFTP_USER@$SFTP_HOST" < "$SFTP_BATCH" 2>&1
RESULT=$?
rm -f "$SFTP_BATCH"

if [[ $RESULT -ne 0 ]]; then
    echo -e "\n${RED}✗ Erreur SFTP (code $RESULT)${NC}"
    exit 1
fi

echo -e "  → ${GREEN}Upload terminé${NC}"
echo ""

# ─── 4. VÉRIFICATION ───
echo -e "${YELLOW}[4/4] Vérification production...${NC}"

check_url() {
    local label="$1" url="$2"
    STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$url" 2>/dev/null)
    if [[ "$STATUS" == "200" ]]; then
        echo -e "  ${GREEN}✓${NC} $label → HTTP $STATUS"
    else
        echo -e "  ${RED}✗${NC} $label → HTTP $STATUS"
    fi
}

check_url "index.html" "https://compta.sarlatc.com/"
check_url "index.js"   "https://compta.sarlatc.com/assets/index.js"
check_url "API years"  "https://compta.sarlatc.com/api/v1/years/list.php"
check_url "API SIG"    "https://compta.sarlatc.com/api/v1/sig/simple.php?exercice=2024"
check_url "API KPIs"   "https://compta.sarlatc.com/api/v1/kpis/financial.php?exercice=2024"

echo ""
echo -e "${GREEN}${BOLD}✓ Déploiement terminé !${NC}"
echo -e "  ${CYAN}https://compta.sarlatc.com${NC}"
