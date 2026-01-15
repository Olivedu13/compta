# Guide d'Upload sur Ionos

## 📋 Prérequis

- Accès FTP/SFTP à Ionos
- Schema.sql exécuté sur la base de données
- Assets buildés localement (`npm run build` exécuté)

---

## 🚀 Étapes d'Upload

### Étape 1: Fichiers Critiques (JwtManager & AuthMiddleware)

```
Local Path: /workspaces/compta/backend/config/JwtManager.php
Ionos Path: /backend/config/JwtManager.php
Permission: 644
```

```
Local Path: /workspaces/compta/backend/config/AuthMiddleware.php
Ionos Path: /backend/config/AuthMiddleware.php
Permission: 644
```

### Étape 2: Endpoints API Authentification

```
Local Path: /workspaces/compta/public_html/api/auth/login.php
Ionos Path: /public_html/api/auth/login.php
Permission: 644
```

```
Local Path: /workspaces/compta/public_html/api/auth/verify.php
Ionos Path: /public_html/api/auth/verify.php
Permission: 644
```

### Étape 3: Assets React Buildés

```
Local Path: /workspaces/compta/public_html/assets/index.js
Ionos Path: /public_html/assets/index.js
Permission: 644
```

### Étape 4: Fichier .env (SECRET!)

⚠️ **IMPORTANT:** Uploader le .env avec les credentials production!

```
Local Path: /workspaces/compta/.env
Ionos Path: Hors du public_html (ex: /var/www/.env ou /)
Permission: 600 (lecture seule par le serveur)
```

---

## 📝 Vérification sur Ionos

Après upload, vérifier via SSH:

```bash
# Vérifier les permissions
ls -la /var/www/backend/config/JwtManager.php
ls -la /var/www/public_html/api/auth/login.php

# Vérifier l'accès
curl -X POST https://compta.sarlatc.com/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@atelier-thierry.fr","password":"password123"}'

# Réponse attendue:
# {"success":true,"token":"eyJ0...","user":{...},"expiresIn":86400}
```

---

## 🔒 Configuration .env Production

S'assurer que .env contient:

```bash
# JWT (À générer avec: openssl rand -hex 32)
JWT_SECRET=<valeur-très-longue-aléatoire>

# Base de données (credentials production)
DB_HOST=...
DB_USER=...
DB_PASS=...

# CORS
CORS_ORIGIN=compta.sarlatc.com

# Environnement
APP_ENV=production
```

---

## 🧪 Test Post-Upload

1. **Test Endpoint Login:**
```bash
curl -X POST https://compta.sarlatc.com/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@atelier-thierry.fr","password":"password123"}'
```

2. **Test via Navigateur:**
   - Ouvrir: https://compta.sarlatc.com
   - Vérifier redirection vers /login
   - Login avec admin@atelier-thierry.fr / password123
   - Vérifier redirection vers /dashboard
   - Vérifier token dans DevTools → localStorage

3. **Test Pages Protégées:**
   - Accéder /dashboard
   - Accéder /import
   - Accéder /balance
   - Accéder /sig
   - Vérifier que logout fonctionne

---

## 🐛 Troubleshooting

### Erreur: "Cannot POST /api/auth/login.php"
- Vérifier que le fichier est uploadé
- Vérifier les permissions (644)
- Vérifier CORS_ORIGIN dans .env

### Erreur: "JWT_SECRET undefined"
- Vérifier que .env est présent ET accessible par PHP
- Vérifier que JWT_SECRET a une valeur

### Erreur: "Database connection failed"
- Vérifier credentials dans .env
- Vérifier que la base est accessible depuis Ionos

### Token n'est pas stocké
- Ouvrir DevTools → Application → localStorage
- Vérifier que "token" et "user" sont présents

---

## ✅ Checklist Final

- [ ] JwtManager.php uploadé
- [ ] AuthMiddleware.php uploadé
- [ ] /api/auth/login.php uploadé
- [ ] /api/auth/verify.php uploadé
- [ ] /public_html/assets/index.js uploadé
- [ ] .env uploadé avec credentials production
- [ ] JWT_SECRET configuré (strong & random)
- [ ] DB migrations exécutées (schema.sql)
- [ ] Test POST /api/auth/login OK
- [ ] Test login via navigateur OK
- [ ] Logout fonctionne OK

---

## 📞 Support

Pour toute erreur, consulter:
- Logs PHP: `/var/www/backend/logs/`
- Logs Ionos: Panneau de contrôle → Logs
- DevTools browser: F12 → Console + Network
