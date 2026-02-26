#!/bin/bash
# Script de déploiement complet vers compta.sarlatc.com
# Déploie: API endpoints, frontend build, base de données

set -e

echo "╔════════════════════════════════════════════════════════╗"
echo "║  DÉPLOIEMENT COMPLET → compta.sarlatc.com             ║"
echo "╚════════════════════════════════════════════════════════╝"

# Configuration
REMOTE_HOST="${REMOTE_HOST:-compta.sarlatc.com}"
REMOTE_USER="${REMOTE_USER:-olive}"
REMOTE_PATH="/homepages/29/d210120109/htdocs/compta"
LOCAL="/workspaces/compta"
SSH_OPTS="-o StrictHostKeyChecking=no -o ConnectTimeout=10"

echo ""
echo "📍 Configuration:"
echo "   Host: $REMOTE_HOST"
echo "   User: $REMOTE_USER"
echo "   Path: $REMOTE_PATH"
echo ""

# =============================================
# 1. BUILD FRONTEND
# =============================================
echo "1️⃣  Build du frontend..."
cd "$LOCAL/frontend"
npx vite build --mode production 2>&1 | tail -3
echo "   ✅ Build terminé"
echo ""

# =============================================
# 2. VÉRIFICATION DES FICHIERS
# =============================================
echo "2️⃣  Vérification des fichiers..."

# Core API
API_FILES=(
    "public_html/api/index.php"
    "public_html/api/simple-import.php"
    "public_html/api/v1/sig/simple.php"
    "public_html/api/v1/kpis/detailed.php"
    "public_html/api/v1/kpis/financial.php"
    "public_html/api/v1/balance/simple.php"
    "public_html/api/v1/cashflow/simple.php"
    "public_html/api/v1/analytics/advanced.php"
    "public_html/api/v1/analytics/analysis.php"
    "public_html/api/v1/analytics/kpis.php"
    "public_html/api/v1/years/list.php"
    "public_html/api/v1/expenses/deep-dive.php"
    "public_html/api/v1/ai/analysis.php"
)

MISSING=0
for file in "${API_FILES[@]}"; do
    if [ -f "$LOCAL/$file" ]; then
        echo "   ✓ $file"
    else
        echo "   ✗ $file (MANQUANT)"
        MISSING=1
    fi
done

# Frontend build
if [ -f "$LOCAL/public_html/assets/index.js" ]; then
    size=$(du -h "$LOCAL/public_html/assets/index.js" | cut -f1)
    echo "   ✓ public_html/assets/index.js ($size)"
else
    echo "   ✗ public_html/assets/index.js (MANQUANT — build frontend échoué?)"
    MISSING=1
fi

if [ -f "$LOCAL/public_html/index.html" ]; then
    echo "   ✓ public_html/index.html"
else
    echo "   ✗ public_html/index.html (MANQUANT)"
    MISSING=1
fi

if [ "$MISSING" = "1" ]; then
    echo ""
    echo "❌ Fichiers manquants — déploiement annulé"
    exit 1
fi

echo ""

# =============================================
# 3. CONNEXION SSH + BACKUP
# =============================================
echo "3️⃣  Sauvegarde base distante..."
ssh $SSH_OPTS "$REMOTE_USER@$REMOTE_HOST" \
    "cp $REMOTE_PATH/compta.db $REMOTE_PATH/compta.db.backup.\$(date +%Y%m%d_%H%M%S)" \
    2>/dev/null || echo "   ⚠️  Sauvegarde skippée (pas de base existante ou pas d'accès)"
echo ""

# =============================================
# 4. CRÉATION DES RÉPERTOIRES DISTANTS
# =============================================
echo "4️⃣  Création des répertoires distants..."
ssh $SSH_OPTS "$REMOTE_USER@$REMOTE_HOST" "
    mkdir -p $REMOTE_PATH/public_html/api/v1/sig
    mkdir -p $REMOTE_PATH/public_html/api/v1/kpis
    mkdir -p $REMOTE_PATH/public_html/api/v1/balance
    mkdir -p $REMOTE_PATH/public_html/api/v1/cashflow
    mkdir -p $REMOTE_PATH/public_html/api/v1/analytics
    mkdir -p $REMOTE_PATH/public_html/api/v1/years
    mkdir -p $REMOTE_PATH/public_html/api/v1/expenses
    mkdir -p $REMOTE_PATH/public_html/api/v1/ai
    mkdir -p $REMOTE_PATH/public_html/assets
" 2>/dev/null || echo "   ⚠️  Création répertoires — vérifier manuellement"
echo ""

# =============================================
# 5. DÉPLOIEMENT API
# =============================================
echo "5️⃣  Déploiement des endpoints API..."
for file in "${API_FILES[@]}"; do
    echo "   → $file"
    scp $SSH_OPTS "$LOCAL/$file" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/$file"
done
echo "   ✅ API déployée"
echo ""

# =============================================
# 6. DÉPLOIEMENT FRONTEND
# =============================================
echo "6️⃣  Déploiement du frontend..."
# Index HTML
scp $SSH_OPTS "$LOCAL/public_html/index.html" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/public_html/"
echo "   → index.html"

# Assets (JS/CSS build)
scp $SSH_OPTS "$LOCAL/public_html/assets/"* "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/public_html/assets/" 2>/dev/null || true
echo "   → assets/*"

# .htaccess si présent
if [ -f "$LOCAL/public_html/.htaccess" ]; then
    scp $SSH_OPTS "$LOCAL/public_html/.htaccess" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/public_html/"
    echo "   → .htaccess"
fi

echo "   ✅ Frontend déployé"
echo ""

# =============================================
# 7. DÉPLOIEMENT BDD (optionnel)
# =============================================
read -p "📂 Déployer la base de données locale? (o/N) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Oo]$ ]]; then
    echo "7️⃣  Déploiement de compta.db..."
    scp $SSH_OPTS "$LOCAL/compta.db" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/"
    echo "   ✅ Base de données déployée"
else
    echo "7️⃣  Base de données — skippée"
fi
echo ""

# =============================================
# 8. VÉRIFICATION
# =============================================
echo "╔════════════════════════════════════════════════════════╗"
echo "║  ✅ DÉPLOIEMENT TERMINÉ                                ║"
echo "╚════════════════════════════════════════════════════════╝"
echo ""
echo "🧪 Tests de vérification :"
echo ""
echo "  curl -s https://$REMOTE_HOST/api/v1/years/list.php | jq ."
echo "  curl -s 'https://$REMOTE_HOST/api/v1/sig/simple.php?exercice=2024' | jq .data.ca_net"
echo "  curl -s 'https://$REMOTE_HOST/api/v1/kpis/financial.php?exercice=2024' | jq .data.score_sante"
echo "  curl -s 'https://$REMOTE_HOST/api/v1/expenses/deep-dive.php?exercice=2024' | jq .data.categories"
echo "  curl -s 'https://$REMOTE_HOST/api/v1/ai/analysis.php?exercice=2024' | jq .data.alertes"
echo ""
echo "🌐 Application : https://$REMOTE_HOST/"
echo ""
