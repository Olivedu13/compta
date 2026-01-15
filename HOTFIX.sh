#!/bin/bash
# 🔥 HOTFIX - Déployer simple-import.php immédiatement

set -e

REMOTE_USER="olive"
REMOTE_HOST="compta.sarlatc.com"
REMOTE_PATH="/homepages/29/d210120109/htdocs/compta/public_html/api"

echo "╔════════════════════════════════════════╗"
echo "║  🔥 HOTFIX - simple-import.php       ║"
echo "╚════════════════════════════════════════╝"
echo ""

echo "📤 Copie simple-import.php vers le serveur..."
scp /workspaces/compta/simple-import-STANDALONE.php "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/simple-import.php"

echo ""
echo "✅ Hotfix déployé!"
echo ""
echo "Test:"
echo "  curl -s https://compta.sarlatc.com/api/health"
echo ""

