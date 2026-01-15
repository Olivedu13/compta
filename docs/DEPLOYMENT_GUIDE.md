# Guide de Déploiement - Authentification JWT + Pages React

## Phase 1: Test Local (Développement)

### 1.1 Lancer le Frontend en mode dev
```bash
cd /workspaces/compta/frontend
npm run dev
```
L'app sera accessible à: `http://localhost:5173`

### 1.2 Flux de Test
1. Ouvrir `http://localhost:5173`
2. Vérifier la redirection vers `/login`
3. Tester login avec les identifiants test:
   - Email: `admin@atelier-thierry.fr`
   - Mot de passe: `password123`
4. Vérifier le token dans DevTools → Application → localStorage
5. Vérifier la redirection vers `/dashboard`
6. Explorer les pages: Dashboard, Import, Balance, SIG
7. Tester Logout (bouton en haut à droite)

### 1.3 Données de Test
```
Utilisateurs actifs (role: admin, user, viewer)
- admin@atelier-thierry.fr / password123
- comptable@atelier-thierry.fr / password123
- viewer@atelier-thierry.fr / password123
```

---

## Phase 2: Build Production

### 2.1 Compiler les assets React
```bash
cd /workspaces/compta/frontend
npm run build
```

Cela génère les fichiers optimisés dans:
- `public_html/assets/index-xxx.js`
- `public_html/assets/index-xxx.css`

### 2.2 Vérifier la structure
```bash
ls -la /workspaces/compta/public_html/assets/
```

---

## Phase 3: Préparation Ionos

### 3.1 Structure à créer sur Ionos

```
public_html/
├── index.html                    (existe - peut rester)
├── api/
│   ├── index.php                 (existe)
│   ├── simple-import.php         (existe)
│   └── auth/
│       ├── login.php             (À uploader - NOUVEAU)
│       └── verify.php            (À uploader - NOUVEAU)
├── assets/
│   ├── index-xxx.js              (À uploader - build)
│   ├── index-xxx.css             (À uploader - build)
│   └── index.js                  (existe)
└── [autres pages HTML]           (À conserver si nécessaire)

backend/config/
├── Database.php                  (existe)
├── JwtManager.php                (À uploader - NOUVEAU)
├── AuthMiddleware.php            (À uploader - NOUVEAU)
├── Logger.php                    (existe)
├── Router.php                    (existe)
└── schema.sql                    (utilisé pour la DB)
```

### 3.2 Fichiers à Uploader

**NOUVEAUX:**
- `backend/config/JwtManager.php`
- `backend/config/AuthMiddleware.php`
- `public_html/api/auth/login.php`
- `public_html/api/auth/verify.php`
- `frontend/src/pages/LoginPage.jsx` (compilé dans assets)

**À REMPLACER:**
- `public_html/assets/*` (avec la version buildée)

**À NETTOYER (optionnel - mais conseillé):**
- `public_html/debug*.php` (fichiers debug, plus utilisés)
- `public_html/*-simple.php` (anciens fichiers, remplacés par React)

### 3.3 Configuration .env sur Ionos

Vérifier que `.env` contient:
```
JWT_SECRET=<clé-très-longue-32-caractères-min>
CORS_ORIGIN=compta.sarlatc.com
DB_HOST=...
DB_USER=...
DB_PASS=...
APP_ENV=production
```

**⚠️ ATTENTION:** Générer une clé JWT_SECRET forte!
```bash
openssl rand -hex 32
# Copier le résultat dans .env
```

---

## Phase 4: Vérification sur Production

### 4.1 Tester les endpoints
```bash
# Test login endpoint
curl -X POST https://compta.sarlatc.com/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@atelier-thierry.fr","password":"password123"}'

# Réponse attendue:
# {"success":true,"token":"eyJ0...","user":{...},"expiresIn":86400}
```

### 4.2 Tester depuis le navigateur
1. Ouvrir `https://compta.sarlatc.com`
2. Vérifier login possible
3. Vérifier accès aux pages protégées
4. Vérifier logout fonctionne

---

## Troubleshooting

### Problème: "Cannot POST /api/auth/login.php"
**Solution:** Vérifier que le fichier existe sur le serveur
```bash
ls -la /var/www/public_html/api/auth/
```

### Problème: "JWT token invalid"
**Solution:** Vérifier JWT_SECRET dans .env
```bash
grep JWT_SECRET /workspaces/compta/.env
```

### Problème: "Database connection failed"
**Solution:** Vérifier les credentials dans .env
```bash
grep DB_ /workspaces/compta/.env
```

### Problème: Token n'est pas stocké
**Solution:** Vérifier localStorage dans DevTools
```javascript
// Dans la console browser:
localStorage.getItem('token')
localStorage.getItem('user')
```

---

## Fichiers à Nettoyer (Optionnel)

Ces fichiers sont remplacés par les pages React et peuvent être supprimés:
- `public_html/debug-all-clients.php`
- `public_html/debug-clients.php`
- `public_html/analyse-simple.php`
- `public_html/balance-simple.php`
- `public_html/comptes-simple.php`
- `public_html/kpis-simple.php`
- `public_html/annees-simple.php`
- `public_html/sig-simple.php`

Pour les nettoyer:
```bash
cd /workspaces/compta/public_html
rm -f debug*.php *-simple.php
```

---

## Checklist Final

- [ ] DB schema.sql exécuté (utilisateurs test présents)
- [ ] Frontend buildé: `npm run build`
- [ ] Assets générés dans `public_html/assets/`
- [ ] Fichiers PHP uploadés sur Ionos
- [ ] JWT_SECRET configuré (.env)
- [ ] .env présent sur le serveur Ionos
- [ ] Test login depuis navigateur OK
- [ ] Token stocké dans localStorage OK
- [ ] Pages protégées accessibles OK
- [ ] Logout fonctionne OK

---

## Support

**Erreurs d'authentification:**
- Vérifier les credentials dans schema.sql (lines 230-236)
- Vérifier JWT_SECRET en production

**Erreurs API:**
- Vérifier les logs: `backend/logs/`
- Vérifier les headers CORS

**Erreurs Frontend:**
- Ouvrir DevTools → Console → Vérifier les erreurs
- Vérifier Network → Requests vers `/api/auth/`
