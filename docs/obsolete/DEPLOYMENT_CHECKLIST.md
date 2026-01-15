# ✅ Checklist Production - Ionos

## 📦 Préparation locale (à faire AVANT upload)

- [ ] **Build Frontend**
  ```bash
  cd frontend && npm run build
  # Vérifier: public_html/assets/index.js créé (~1.4MB)
  ```

- [ ] **Vérifier structure**
  ```bash
  ls -la public_html/assets/index.js
  ls -la backend/config/schema.sql
  ls -la backend/api/login.php
  ```

- [ ] **Vérifier .env local**
  - [ ] JWT_SECRET défini (temporaire OK pour upload)
  - [ ] DB_HOST, DB_USER, DB_PASSWORD OK
  - [ ] CORS_ORIGINS pour localhost

- [ ] **Tester localement**
  ```bash
  # Vérifier login fonctionne
  npm run dev
  # Tester: admin@example.com / password123
  ```

---

## 📤 Upload vers Ionos

- [ ] **Préparer les fichiers** (7 fichiers minimum)
  ```
  1. public_html/index.html
  2. public_html/assets/index.js
  3. public_html/api/index.php
  4. backend/config/Database.php
  5. backend/api/login.php
  6. backend/config/schema.sql
  7. .env (valeurs Ionos)
  ```

- [ ] **Upload via FTP/SFTP**
  - [ ] Accéder FTP Ionos (Filezilla/WinSCP)
  - [ ] Vérifier dossier cible: `/httpdocs/` ou `/public_html/`
  - [ ] Upload tous les fichiers
  - [ ] Vérifier permissions: 644 (fichiers), 755 (dossiers)

- [ ] **Vérifier après upload**
  ```bash
  # Via FTP: Voir les fichiers dans le dossier
  # Via SSH:
  ls -la /httpdocs/public_html/
  ls -la /httpdocs/backend/
  ```

---

## 🗄️ Base de Données - Ionos

- [ ] **PhpMyAdmin Ionos**
  - [ ] Accéder: https://votre-domaine.com/phpmyadmin/
  - [ ] Créer base: `compta_bijouterie` (UTF-8)

- [ ] **Importer schema.sql**
  - [ ] Onglet "Importer"
  - [ ] Charger: `backend/config/schema.sql`
  - [ ] Exécuter

- [ ] **Vérifier tables**
  ```sql
  SHOW TABLES;
  -- Doit afficher: sys_utilisateurs, sys_plan_comptable, fin_ecritures_fec
  
  SELECT * FROM sys_utilisateurs;
  -- Doit afficher: 3 users (admin, comptable, viewer)
  ```

---

## 🔐 Configuration JWT_SECRET (TRÈS IMPORTANT!)

### ⚠️ CETTE ÉTAPE EST CRITIQUE - À FAIRE ABSOLUMENT!

- [ ] **Via SSH (Recommandé)**
  ```bash
  ssh user@ionos-server.com
  
  # Générer nouvelle clé (copier la sortie)
  openssl rand -hex 32
  # Exemple: a3f2b8c1d4e5f6g7h8i9j0k1l2m3n4o5
  
  # Éditer .env
  nano .env
  # Chercher: JWT_SECRET=compta-bijouterie-secret-dev-key
  # Remplacer par: JWT_SECRET=a3f2b8c1d4e5f6g7h8i9j0k1l2m3n4o5
  
  # Sauvegarder: Ctrl+X, Y, Enter
  # Vérifier: cat .env | grep JWT_SECRET
  ```

- [ ] **Via FTP (Alternative)**
  - [ ] Télécharger `.env` local
  - [ ] Éditer avec éditeur texte
  - [ ] Remplacer: `JWT_SECRET=compta-bijouterie-secret-dev-key`
  - [ ] Par: `JWT_SECRET=<nouvelle_clé_générée>`
  - [ ] Re-uploader `.env`

- [ ] **Documenter la nouvelle clé**
  - [ ] Sauvegarder JWT_SECRET dans gestionnaire de mots de passe
  - [ ] Format: `JWT_SECRET_IONOS_<date>: <valeur>`
  - [ ] NE PAS partager en texte brut

---

## ✅ Tests après Configuration

- [ ] **Test 1: Frontend chargé**
  ```bash
  curl -I https://votre-domaine.com/
  # Doit retourner: HTTP/2 200 OK
  ```

- [ ] **Test 2: Login endpoint**
  ```bash
  curl -X POST https://votre-domaine.com/api/login \
    -H "Content-Type: application/json" \
    -d '{"email":"admin@example.com","password":"password123"}'
  # Doit retourner: {"success":true,"token":"..."}
  ```

- [ ] **Test 3: Vérifier token**
  ```bash
  # Copier le token de Test 2
  TOKEN="eyJ0eXAiOiJKV1QiLCJhbGc..."
  
  curl -X GET https://votre-domaine.com/api/verify \
    -H "Authorization: Bearer $TOKEN"
  # Doit retourner: {"success":true,"message":"Token valide"}
  ```

- [ ] **Test 4: Login via Frontend**
  - [ ] Accéder: https://votre-domaine.com/
  - [ ] Entrer: admin@example.com / password123
  - [ ] Vérifier: Dashboard charge avec données

---

## 🔒 Sécurisation

- [ ] **HTTPS/SSL activé**
  - [ ] https://votre-domaine.com/ fonctionne
  - [ ] Certificat SSL valide (généralement auto avec Ionos)

- [ ] **Redirection HTTP → HTTPS**
  - [ ] Créer `public_html/.htaccess`:
  ```apache
  <IfModule mod_rewrite.c>
      RewriteEngine On
      RewriteCond %{HTTPS} off
      RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
  </IfModule>
  ```

- [ ] **Protéger backend/**
  - [ ] Créer `backend/.htaccess`:
  ```apache
  <FilesMatch "\.php$">
      Deny from all
  </FilesMatch>
  ```

- [ ] **Changer mots de passe test**
  - [ ] Générer nouveau mot de passe sécurisé
  - [ ] Mettre à jour dans DB (bcrypt)
  - [ ] Tester login avec nouveau mot de passe

---

## 📊 Vérifications finales

- [ ] **Logs d'erreur**
  - [ ] Vérifier via FTP: `backend/logs/`
  - [ ] Via SSH: `tail -f backend/logs/app.log`
  - [ ] Aucune erreur critique

- [ ] **Performance**
  - [ ] Frontend charge en < 2 secondes
  - [ ] API login répond en < 500ms
  - [ ] Dashboard réactif

- [ ] **Fonctionnalité complète**
  - [ ] Login/Logout fonctionne
  - [ ] Routes protégées accessibles
  - [ ] Import FEC fonctionne
  - [ ] Dashboard affiche données

- [ ] **Backup initial**
  - [ ] Télécharger `.env` (sauvegarder JWT_SECRET)
  - [ ] Exporter base de données
  - [ ] Conserver fichiers uploadés

---

## 🚀 Prêt pour Production!

Quand tout est coché, l'application est prête:
- ✅ Frontend chargé en HTTPS
- ✅ JWT_SECRET sécurisé et changé
- ✅ Base de données fonctionnelle
- ✅ Authentification opérationnelle
- ✅ Sécurité en place

**Dates de vérification:**
- Date déploiement: ___________
- Dernier test: ___________
- Notes: ___________

---

## 🆘 Problèmes Courants

**"Cannot read property 'token'"**
→ JWT_SECRET pas changé ou mauvaise valeur dans .env

**"500 Internal Server Error"**
→ Vérifier logs: `backend/logs/` → PHP error logs Ionos

**"403 Forbidden"**
→ Vérifier permissions FTP: 644 fichiers, 755 dossiers

**"Unexpected token" (JSON)**
→ Vérifier CORS_ORIGINS dans .env

**Token expire instantanément**
→ Vérifier JWT_EXPIRY = 86400 (secondes, pas heures)

---

**Voir aussi:** [IONOS_PRODUCTION.md](IONOS_PRODUCTION.md) pour procédure complète
