# 🚀 Guide de Déploiement Production sur Ionos

## Résumé Rapide
- **Durée**: ~30 minutes
- **Oui, il faut changer JWT_SECRET après upload** (TRÈS IMPORTANT)
- **Moment exact**: APRÈS upload des fichiers, AVANT de rendre public

---

## 📋 Étape 1: Préparer les fichiers à uploader

### Fichiers nécessaires (7 fichiers total)

```
1. public_html/index.html
2. public_html/assets/index.js (construit avec npm run build)
3. public_html/api/index.php
4. backend/config/Database.php
5. backend/config/JwtManager.php (ou AuthMiddleware.php)
6. backend/api/login.php
7. backend/config/schema.sql
8. .env (voir .env.example)
```

### Vérifier avant upload
```bash
cd /workspaces/compta

# Vérifier que npm run build a réussi
ls -lh public_html/assets/index.js  # Doit être ~1.4MB

# Vérifier les fichiers PHP existants
ls -la backend/config/
ls -la backend/api/

# Vérifier .env existe
ls -la .env
cat .env  # Vérifier les valeurs
```

---

## 📤 Étape 2: Uploader via FTP/SFTP sur Ionos

### Option A: Via FTP (Ionos)

1. **Ouvrir Filezilla ou WinSCP**
   - Hôte: votre-domaine.com (ou ftp.votre-domaine.com)
   - Port: 21 (FTP) ou 22 (SFTP)
   - Identifiants Ionos: Dans votre espace client

2. **Naviguer vers le dossier racine**
   - Ionos organise généralement: `/httpdocs/` ou `/public_html/`

3. **Uploader les fichiers**
   ```
   public_html/
   ├── index.html
   ├── assets/
   │   └── index.js
   └── api/
       └── index.php
   
   backend/
   ├── config/
   │   ├── Database.php
   │   ├── JwtManager.php
   │   └── schema.sql
   └── api/
       └── login.php
   
   .env  (à la racine du domaine)
   ```

### Option B: Via SSH (Ionos - Si disponible)

```bash
# Depuis votre terminal local
scp -r public_html/* user@ionos-server:/httpdocs/
scp -r backend/ user@ionos-server:/
scp .env user@ionos-server:/
```

---

## 🗄️ Étape 3: Exécuter le schéma SQL sur Ionos

### Via PhpMyAdmin (Ionos)

1. **Accéder à PhpMyAdmin**
   - URL: https://votre-domaine.com/phpmyadmin/ (ou via espace client Ionos)

2. **Créer la base de données**
   - Nouvelle base: `compta_bijouterie` (ou votre nom)
   - Caractères: `utf8mb4_unicode_ci`

3. **Importer schema.sql**
   - Sélectionner la base
   - Onglet "Importer"
   - Charger `backend/config/schema.sql`
   - Exécuter

4. **Vérifier les tables créées**
   ```sql
   SHOW TABLES;  -- Doit montrer 3 tables
   SELECT * FROM sys_utilisateurs;  -- Doit montrer 3 utilisateurs
   ```

### Identifiants de test (depuis schema.sql)
```
Email: admin@example.com
Mot de passe: password123

Email: comptable@example.com
Mot de passe: password123

Email: viewer@example.com
Mot de passe: password123
```

---

## 🔐 Étape 4: CHANGER JWT_SECRET (⚠️ CRITICAL)

### ❌ AVANT (fichier .env uploadé)
```env
JWT_SECRET=compta-bijouterie-secret-dev-key  # ❌ Secret temporaire
DB_HOST=votre-host-ionos.com
DB_NAME=compta_bijouterie
DB_USER=votre_user
DB_PASSWORD=votre_password
```

### ✅ APRÈS Étape 4 (à faire sur le serveur Ionos)

#### Option 1: Via SSH (Meilleure option)

```bash
# 1. Se connecter au serveur Ionos
ssh user@ionos-server.com

# 2. Générer un JWT_SECRET sécurisé (32 caractères hex)
openssl rand -hex 32
# Exemple de sortie: a3f2b8c1d4e5f6g7h8i9j0k1l2m3n4o5

# 3. Éditer le fichier .env
nano .env  # ou vim, selon ce que vous préférez

# 4. Remplacer la ligne JWT_SECRET
# Avant: JWT_SECRET=compta-bijouterie-secret-dev-key
# Après: JWT_SECRET=a3f2b8c1d4e5f6g7h8i9j0k1l2m3n4o5

# 5. Sauvegarder et quitter (Ctrl+X, Y, Enter)

# 6. Vérifier le changement
cat .env | grep JWT_SECRET

# 7. Redémarrer PHP (si nécessaire)
# Pour shared hosting Ionos, généralement pas nécessaire
# Mais vous pouvez:
cd /path/to/app && php -r "opcache_reset();"
```

#### Option 2: Via PhpMyAdmin (Alternative si pas SSH)

Si vous n'avez pas accès SSH, vous pouvez créer un petit script PHP:

1. **Créer `update-jwt.php` temporaire**
   ```php
   <?php
   if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_secret'])) {
       $new_secret = $_POST['new_secret'];
       $env_content = file_get_contents('.env');
       $env_content = preg_replace(
           '/JWT_SECRET=.*/i',
           'JWT_SECRET='.$new_secret,
           $env_content
       );
       file_put_contents('.env', $env_content);
       echo "✅ JWT_SECRET mis à jour!";
   }
   ?>
   <form method="POST">
       <input name="new_secret" placeholder="Nouveau JWT_SECRET">
       <button>Mettre à jour</button>
   </form>
   ```

2. **Accéder à**: `https://votre-domaine.com/update-jwt.php`
3. **Entrer la nouvelle clé** (générée avec `openssl rand -hex 32`)
4. **Supprimer le fichier après** (`unlink('update-jwt.php');`)

---

## ✅ Étape 5: Tester les endpoints

### Test 1: Vérifier le frontend
```bash
curl -I https://votre-domaine.com/
# Doit retourner: HTTP/2 200 OK
```

### Test 2: Tester la connexion
```bash
# Depuis Postman ou curl
curl -X POST https://votre-domaine.com/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password123"
  }'

# Résultat attendu:
# {
#   "success": true,
#   "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
#   "user": {...}
# }
```

### Test 3: Utiliser le token
```bash
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGc..."

curl -X GET https://votre-domaine.com/api/verify \
  -H "Authorization: Bearer $TOKEN"

# Résultat attendu:
# {
#   "success": true,
#   "message": "Token valide"
# }
```

---

## 🔒 Étape 6: Sécurisation Production

### Activer HTTPS/SSL
```bash
# Sur Ionos, généralement automatique
# Vérifier: https://votre-domaine.com/ charge bien en HTTPS
# Redirection HTTP → HTTPS:
```

**Dans `public_html/.htaccess`** (créer si n'existe pas):
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

### Protéger les fichiers sensibles

**Dans `backend/.htaccess`**:
```apache
# Bloquer l'accès direct au dossier backend/
<FilesMatch "\.php$">
    Deny from all
</FilesMatch>
```

### Changer les mots de passe de test

**Via PhpMyAdmin**:
```sql
-- Modifier le mot de passe admin
UPDATE sys_utilisateurs 
SET mot_de_passe = '$2y$10$...' 
WHERE email = 'admin@example.com';
-- ($2y$10$... = bcrypt de votre nouveau mot de passe)
```

Ou via SQL:
```bash
# Générer un hash bcrypt (en CLI PHP):
php -r "echo password_hash('nouveau_mot_de_passe_securise', PASSWORD_BCRYPT);"
```

---

## 📊 Checklist de Vérification

- [ ] Fichiers uploadés (7 fichiers)
- [ ] Base de données créée
- [ ] schema.sql exécuté
- [ ] Tables créées (3 tables)
- [ ] .env mis à jour avec infos Ionos
- [ ] JWT_SECRET changé avec valeur sécurisée
- [ ] Frontend charge en HTTPS
- [ ] Test login réussit
- [ ] Token JWT valide
- [ ] HTTPS fonctionne
- [ ] Mots de passe de test changés
- [ ] .htaccess pour redirection HTTP→HTTPS

---

## ⚠️ RÉSUMÉ: JWT_SECRET - Quand Changer?

### Timeline
```
1. npm run build (local)          → .env.example copié → .env avec JWT_SECRET temporaire
2. Upload fichiers vers Ionos    → JWT_SECRET toujours temporaire ❌
3. ⭐ SSH dans Ionos             → GÉNÉRER NOUVEAU JWT_SECRET ✅
4. Mettre à jour .env sur Ionos  → JWT_SECRET = nouvelle valeur sécurisée
5. Redémarrer PHP (si besoin)    → Appliquer le changement
6. Tester login endpoint         → Vérifier que nouveau JWT_SECRET fonctionne ✅
7. Rendre public                 → Application prête! 🚀
```

### Importance
- ❌ NE PAS garder le même JWT_SECRET dev/prod
- ❌ NE PAS partager JWT_SECRET en plain text
- ✅ Générer avec `openssl rand -hex 32`
- ✅ Stocker uniquement dans .env (pas dans git)
- ✅ Changer après chaque upload

---

## 🆘 Troubleshooting

### Erreur: "Cannot connect to database"
```
Cause: Identifiants DB incorrects dans .env
Vérifier: DB_HOST, DB_NAME, DB_USER, DB_PASSWORD dans espace client Ionos
```

### Erreur: "Invalid JWT token"
```
Cause: JWT_SECRET différent entre ancien et nouveau
Solution: Déconnecter tous les utilisateurs (localStorage vide)
Ou: Générer nouveau token avec nouveau JWT_SECRET
```

### Erreur: "403 Forbidden"
```
Cause: Permissions fichiers incorrectes
Solution: chmod 644 fichiers, chmod 755 dossiers (via FTP)
```

### Erreur: "500 Internal Server Error"
```
Cause: Erreur PHP, généralement permissions ou path PHP incorrect
Vérifier: Logs d'erreur Ionos (espace client → Logs)
```

---

## 📚 Fichiers de Référence

- [API_DOCUMENTATION.md](docs/API_DOCUMENTATION.md) - Endpoints complets
- [SECURITY_GUIDE.md](docs/SECURITY_GUIDE.md) - Sécurité en détail
- [QUICK_START.md](docs/QUICK_START.md) - Démarrage rapide dev

---

**Vous êtes prêt pour le déploiement! 🚀**
