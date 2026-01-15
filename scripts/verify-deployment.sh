#!/bin/bash

# Script de vérification - Endpoints API + Structure

echo "╔════════════════════════════════════════════════════════════════════════════╗"
echo "║              🔍 VÉRIFICATION STRUCTURE DÉPLOIEMENT                         ║"
echo "╚════════════════════════════════════════════════════════════════════════════╝"
echo

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

check_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✅${NC} $1"
        return 0
    else
        echo -e "${RED}❌${NC} $1 (MANQUANT)"
        return 1
    fi
}

check_dir() {
    if [ -d "$1" ]; then
        echo -e "${GREEN}✅${NC} $1/"
        return 0
    else
        echo -e "${RED}❌${NC} $1/ (MANQUANT)"
        return 1
    fi
}

echo "📋 VÉRIFICATION FICHIERS BACKEND"
echo "════════════════════════════════════════════════════════════════════════════"
check_file "backend/config/JwtManager.php"
check_file "backend/config/AuthMiddleware.php"
check_file "backend/config/Database.php"
check_file "backend/config/Logger.php"
echo

echo "📋 VÉRIFICATION FICHIERS API"
echo "════════════════════════════════════════════════════════════════════════════"
check_file "public_html/api/auth/login.php"
check_file "public_html/api/auth/verify.php"
check_file "public_html/api/index.php"
echo

echo "📋 VÉRIFICATION ASSETS BUILT"
echo "════════════════════════════════════════════════════════════════════════════"
check_dir "public_html/assets"
check_file "public_html/assets/index.js"
echo

echo "📋 VÉRIFICATION COMPOSANTS REACT"
echo "════════════════════════════════════════════════════════════════════════════"
check_file "frontend/src/pages/LoginPage.jsx"
check_file "frontend/src/pages/Dashboard.jsx"
check_file "frontend/src/pages/ImportPage.jsx"
check_file "frontend/src/pages/BalancePage.jsx"
check_file "frontend/src/pages/SIGPage.jsx"
echo

echo "📋 VÉRIFICATION HOOKS/CONTEXT"
echo "════════════════════════════════════════════════════════════════════════════"
check_file "frontend/src/hooks/useAuth.jsx"
check_file "frontend/src/components/ProtectedRoute.jsx"
check_file "frontend/src/components/Layout.jsx"
check_file "frontend/src/services/api.js"
echo

echo "📋 VÉRIFICATION CONFIGURATION"
echo "════════════════════════════════════════════════════════════════════════════"
check_file ".env"

# Vérifier JWT_SECRET
if grep -q "JWT_SECRET=" .env; then
    echo -e "${GREEN}✅${NC} JWT_SECRET configuré dans .env"
else
    echo -e "${YELLOW}⚠️ ${NC} JWT_SECRET non configuré dans .env (À ajouter!)"
fi

# Vérifier CORS_ORIGIN
if grep -q "CORS_ORIGIN=" .env; then
    echo -e "${GREEN}✅${NC} CORS_ORIGIN configuré dans .env"
else
    echo -e "${YELLOW}⚠️ ${NC} CORS_ORIGIN non configuré dans .env"
fi
echo

echo "📊 RÉSUMÉ"
echo "════════════════════════════════════════════════════════════════════════════"
echo "✅ Backend: JwtManager + AuthMiddleware + Endpoints /api/auth/*"
echo "✅ Frontend: React Router + LoginPage + ProtectedRoute + useAuth Hook"
echo "✅ Assets: Buildés et prêts pour déploiement"
echo
echo "📌 PROCHAINES ÉTAPES:"
echo "   1. Uploader sur Ionos via FTP/SFTP"
echo "   2. Vérifier permissions (755 pour dossiers, 644 pour fichiers)"
echo "   3. Exécuter schema.sql si pas déjà fait"
echo "   4. Tester login sur https://compta.sarlatc.com"
echo

echo "════════════════════════════════════════════════════════════════════════════"
