#!/bin/bash
# Script de déploiement vers compta.sarlatc.com

set -e

echo "╔════════════════════════════════════════════════════════╗"
echo "║  DÉPLOIEMENT VERS compta.sarlatc.com                   ║"
echo "╚════════════════════════════════════════════════════════╝"

# Configuration
REMOTE_HOST="${REMOTE_HOST:-compta.sarlatc.com}"
REMOTE_USER="${REMOTE_USER:-olive}"
REMOTE_PATH="/homepages/29/d210120109/htdocs/compta"

echo ""
echo "📍 Configuration:"
echo "   Host: $REMOTE_HOST"
echo "   User: $REMOTE_USER"
echo "   Path: $REMOTE_PATH"
echo ""

# Fichiers à déployer
FILES=(
    "public_html/api/index.php"
    "public_html/api/simple-import.php"
    "compta.db"
)

echo "📦 Fichiers à déployer:"
for file in "${FILES[@]}"; do
    if [ -f "/workspaces/compta/$file" ]; then
        size=$(du -h "/workspaces/compta/$file" | cut -f1)
        echo "   ✓ $file ($size)"
    else
        echo "   ✗ $file (NOT FOUND)"
        exit 1
    fi
done

echo ""
echo "🔄 Déploiement en cours..."
echo ""

# Sauvegarde distance
echo "1️⃣  Sauvegarde de la base de données distante..."
ssh "$REMOTE_USER@$REMOTE_HOST" "cp $REMOTE_PATH/compta.db $REMOTE_PATH/compta.db.backup.$(date +%Y%m%d_%H%M%S)" 2>/dev/null || echo "   ⚠️  Sauvegarde skippée (pas de base existante)"

# Deploy index.php
echo "2️⃣  Déploiement de /api/index.php..."
scp /workspaces/compta/public_html/api/index.php "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/public_html/api/"

# Deploy simple-import.php
echo "3️⃣  Déploiement de /api/simple-import.php..."
scp /workspaces/compta/public_html/api/simple-import.php "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/public_html/api/"

# Deploy compta.db
echo "4️⃣  Déploiement de compta.db..."
scp /workspaces/compta/compta.db "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/"

echo ""
echo "✅ Déploiement terminé!"
echo ""
echo "🧪 Tests:"
echo ""
echo "1. Health check:"
echo "   curl -s https://$REMOTE_HOST/api/health | jq ."
echo ""
echo "2. Check annee 2024:"
echo "   curl -s https://$REMOTE_HOST/api/annee/2024/exists | jq ."
echo ""
echo "3. Lister les tiers:"
echo "   curl -s 'https://$REMOTE_HOST/api/tiers?exercice=2024&limit=3' | jq ."
echo ""
