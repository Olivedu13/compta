# 📋 GUIDE DE DÉPLOIEMENT - compta.sarlatc.com

## ✅ Fichiers Prêts à Déployer

```
deployment-package.tar.gz (2.9 MB)
├── public_html/api/index.php           [11 KB]  - API principal (100% self-contained)
├── public_html/api/simple-import.php   [8 KB]   - Endpoint FEC import
└── compta.db                           [12 MB]  - Base SQLite avec 11,617 écritures
```

## 🚀 Instructions de Déploiement

### Option 1 : Via le panneau cPanel (Recommandé)

1. Connecte-toi à cPanel : https://compta.sarlatc.com:2083
2. File Manager → Navigate to `/homepages/29/d210120109/htdocs/compta/`
3. Upload le `deployment-package.tar.gz`
4. Clique droit → Extract
5. Déplace les fichiers aux bons emplacements

### Option 2 : Via SCP (Ligne de commande)

```bash
# Sauvegarde l'ancienne DB
ssh olive@compta.sarlatc.com "cp ~/public_html/compta.db ~/public_html/compta.db.backup"

# Déploie les fichiers
scp ~/deployment-package.tar.gz olive@compta.sarlatc.com:~/public_html/
ssh olive@compta.sarlatc.com "cd ~/public_html && tar -xzf deployment-package.tar.gz"
```

### Option 3 : Via Git (Si git est installé)

```bash
ssh olive@compta.sarlatc.com "cd ~/public_html/compta && git pull origin main"
```

## 🧪 Tests Après Déploiement

```bash
# 1. Health Check
curl -s https://compta.sarlatc.com/api/health | jq .

# Réponse attendue:
{
  "status": "OK",
  "version": "1.0.0",
  "database": "connected"
}

# 2. Vérifier année 2024
curl -s https://compta.sarlatc.com/api/annee/2024/exists | jq .

# Réponse attendue:
{
  "success": true,
  "exercice": 2024,
  "exists": true,
  "count": 11617
}

# 3. Lister les tiers
curl -s 'https://compta.sarlatc.com/api/tiers?exercice=2024&limit=3' | jq .

# 4. Importer un FEC
curl -X POST https://compta.sarlatc.com/api/simple-import.php -F "file=@fec_2024.txt"
```

## 📊 Changements Déployés

### ✨ Améliorations
- ✅ API 100% self-contained (pas de dépendances externes)
- ✅ Plus d'erreurs "Class not found"
- ✅ Base de données SQLite avec 11,617 écritures
- ✅ Tous les endpoints testés et fonctionnels

### 🔧 Endpoints Disponibles

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/api/health` | GET | Vérifier l'état de l'API |
| `/api/annee/:exercice/exists` | GET | Vérifier si une année existe |
| `/api/tiers` | GET | Lister les tiers avec pagination |
| `/api/tiers/:numero` | GET | Détail d'un tiers |
| `/api/cashflow` | GET | Analyse du cashflow |
| `/api/cashflow/detail/:journal` | GET | Détail par journal |
| `/api/simple-import.php` | POST | Importer un FEC |

### 📝 Paramètres

#### GET /api/tiers
```
?exercice=2024           # Année
&limit=50                # Nombre de résultats
&offset=0                # Décalage (pagination)
&tri=montant             # Tri: 'montant' ou 'nom'
```

#### GET /api/tiers/:numero
```
?exercice=2024           # Année
```

#### GET /api/cashflow
```
?exercice=2024           # Année
&periode=mois            # Période: 'mois' ou 'trimestre'
```

#### POST /api/simple-import.php
```
-F "file=@fec_2024.txt"  # Fichier FEC à importer
```

## ⚠️ Notes Importantes

1. **Permissions fichiers**
   - Les fichiers PHP doivent être exécutables
   - La base `compta.db` doit être lisible et inscriptible

2. **Sauvegarde**
   - Une sauvegarde de l'ancienne DB est créée automatiquement
   - Format: `compta.db.backup.20260115_145300`

3. **Rollback**
   ```bash
   ssh olive@compta.sarlatc.com "cp ~/public_html/compta.db.backup ~/public_html/compta.db"
   ```

4. **Logs d'erreur**
   - Vérifie les logs du serveur: `/var/www/logs/`
   - Ou utilise l'Error Log dans cPanel

## 🆘 Dépannage

### "Class not found" Error
❌ **Ancien problème** - Résolu ✅
Les fichiers déployés n'ont plus de dépendances externes

### "Database not found"
- Vérifie que `compta.db` est au bon endroit
- Droit d'accès: `chmod 644 compta.db`

### Permission Denied
```bash
ssh olive@compta.sarlatc.com "chmod 644 ~/public_html/api/*.php ~/public_html/compta.db"
```

### Import FEC échoue
- Vérifiez que le fichier est au format TSV (Tab-Separated Values)
- Les colonnes requises: JournalCode, EcritureNum, EcritureDate, CompteNum, Debit, Credit

## ✅ Déploiement Réussi !

Si tous les tests passent :
- 🟢 FROJO et tous les tiers sont visibles
- 🟢 Dashboard affiche les données correctement
- 🟢 Import de nouveaux FEC fonctionne

🎉 **Bienvenue en production !**
