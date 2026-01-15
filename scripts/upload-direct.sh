#!/bin/bash

# ========================================
# Upload direct SFTP vers Ionos
# ========================================

SFTP_HOST="home210120109.1and1-data.host"
SFTP_USER="acc1249301374"
SFTP_PASS='userCompta!90127452?'

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}=== Upload SFTP Direct ===${NC}"
echo "Hôte: $SFTP_HOST"
echo "User: $SFTP_USER"
echo ""

# Batch SFTP
SFTP_BATCH="/tmp/sftp_upload_$$.batch"
cat > "$SFTP_BATCH" << 'EOF'
# Aller dans le dossier compta existant
cd compta

# Create directories
-mkdir public_html/api/v1
-mkdir public_html/api/v1/accounting
-mkdir public_html/api/v1/analytics
-mkdir public_html/api/v1/balance
-mkdir public_html/api/v1/sig
-mkdir public_html/api/v1/years

# Public HTML - fichiers utiles
put /workspaces/compta/public_html/.htaccess public_html/.htaccess
put /workspaces/compta/public_html/.user.ini public_html/.user.ini
put /workspaces/compta/public_html/index.html public_html/index.html
put /workspaces/compta/public_html/api/index.php public_html/api/index.php
put /workspaces/compta/simple-import-STANDALONE.php public_html/api/simple-import.php
put /workspaces/compta/public_html/api/v1/index.php public_html/api/v1/index.php
put /workspaces/compta/public_html/api/v1/sig-simple.php public_html/api/v1/sig-simple.php
put /workspaces/compta/public_html/api/v1/sig/simple.php public_html/api/v1/sig/simple.php
put /workspaces/compta/public_html/api/v1/accounting/sig.php public_html/api/v1/accounting/sig.php
put /workspaces/compta/compta.db compta.db
put /workspaces/compta/public_html/assets/index.js public_html/assets/index.js
put /workspaces/compta/public_html/annees-simple.php public_html/annees-simple.php
put /workspaces/compta/public_html/balance-simple.php public_html/balance-simple.php
put /workspaces/compta/public_html/kpis-simple.php public_html/kpis-simple.php
put /workspaces/compta/public_html/kpis-detailed.php public_html/kpis-detailed.php
put /workspaces/compta/public_html/sig-simple.php public_html/sig-simple.php
put /workspaces/compta/public_html/comptes-simple.php public_html/comptes-simple.php
put /workspaces/compta/public_html/analyse-simple.php public_html/analyse-simple.php
put /workspaces/compta/public_html/analytics-advanced.php public_html/analytics-advanced.php

# Backend config
put /workspaces/compta/backend/config/Database.php backend/config/Database.php
put /workspaces/compta/backend/config/Router.php backend/config/Router.php
put /workspaces/compta/backend/config/Logger.php backend/config/Logger.php
put /workspaces/compta/backend/config/schema.sql backend/config/schema.sql

put /workspaces/compta/backend/config/Logger.php backend/config/Logger.php

# Backend services
put /workspaces/compta/backend/services/ImportService.php backend/services/ImportService.php
put /workspaces/compta/backend/services/SigCalculator.php backend/services/SigCalculator.php

# Supprimer les fichiers de debug/test du serveur
rm public_html/cleanup.php
rm public_html/data-status.php
rm public_html/debug-import-fec.php
rm public_html/debug-reset.php
rm public_html/api-simple.php
rm public_html/comptes-list.php
rm public_html/test-import.php
rm public_html/test-sig-format.php
rm public_html/test-simple.php
rm public_html/api/debug-api.php
rm public_html/api/debug-data.php
rm public_html/api/debug-fec.php
rm public_html/api/debug-import.php
rm public_html/api/debug-parse-fec.php
rm public_html/api/tail-errors.php
rm public_html/api/test-fec.php
rm public_html/api/balance-simple.php
rm public_html/api/kpis-simple.php

quit
EOF

echo -e "${YELLOW}Upload en cours...${NC}"

# Essayer sshpass s'il existe
if command -v sshpass &> /dev/null; then
    sshpass -p "$SFTP_PASS" sftp -o StrictHostKeyChecking=no "$SFTP_USER@$SFTP_HOST" < "$SFTP_BATCH"
else
    # Sinon utiliser expect
    if command -v expect &> /dev/null; then
        expect << EXPECTEOF
set timeout 30
spawn sftp -o StrictHostKeyChecking=no "$SFTP_USER@$SFTP_HOST"
expect "assword:"
send "$SFTP_PASS\r"
expect sftp>
while {[gets stdin line] >= 0} {
    send "$line\r"
    expect sftp>
}
EXPECTEOF
    else
        # Dernier recours: sftp interactif
        echo -e "${RED}sshpass/expect non disponibles${NC}"
        echo "Entrez le mot de passe quand demandé:"
        sftp -o StrictHostKeyChecking=no "$SFTP_USER@$SFTP_HOST" < "$SFTP_BATCH"
    fi
fi

RESULT=$?
rm -f "$SFTP_BATCH"

if [ $RESULT -eq 0 ]; then
    echo -e "\n${GREEN}✓ Upload réussi!${NC}"
else
    echo -e "\n${RED}Erreur: $RESULT${NC}"
fi
