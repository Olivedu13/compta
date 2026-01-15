# 🎯 LES ÉTAPES À FAIRE (PAR TOI, SUR IONOS)

**État actuel**: Tout est prêt localement ✅

---

## 📋 RÉSUMÉ DE CE QUI EST DÉJÀ FAIT

✅ **Build production**: `public_html/assets/index.js` (1.4M)  
✅ **API files**: 4 fichiers PHP en place  
✅ **Config files**: 6 fichiers backend en place  
✅ **.env**: Créé avec valeurs actuelles  
✅ **Schema SQL**: Prêt à exécuter  
✅ **Documentation**: Complète et organisée  

---

## 🚀 CE QUE TU DOIS FAIRE (3 étapes simples)

### **ÉTAPE 1: Upload vers Ionos (15 min)**

Ces fichiers **DOIVENT** être uploadés via FTP/SFTP Ionos:

```
À copier vers Ionos:

1. public_html/index.html
2. public_html/assets/index.js       ← Le build React
3. public_html/api/index.php
4. public_html/api/auth/login.php
5. public_html/api/auth/verify.php
6. backend/config/*.php              ← Tous les fichiers config
7. backend/config/schema.sql
8. .env                              ← À copier AVEC les valeurs actuelles
```

**Via FTP (Filezilla/WinSCP):**
- Ouvrir FTP client
- Connexion Ionos (identifiants dans espace client)
- Naviguer vers dossier racine (généralement `/httpdocs/`)
- Uploader les fichiers cidessus dans la même structure
- Vérifier les permissions: 644 (fichiers), 755 (dossiers)

**Via SSH (si tu as accès SSH):**
```bash
scp -r public_html/* user@ionos-server:/httpdocs/
scp -r backend/ user@ionos-server:/
scp .env user@ionos-server:/
```

✅ **Après**: Les fichiers sont sur Ionos, mais app ne fonctionne PAS encore

---

### **ÉTAPE 2: Base de Données (10 min)**

**Via PhpMyAdmin Ionos:**

1. Accéder: `https://compta.sarlatc.com/phpmyadmin/` (ou via espace client Ionos)

2. **Créer base de données**:
   - Bouton: "Nouvelle base"
   - Nom: `dbs15168768` (voir DB_NAME dans .env)
   - Collatif: `utf8mb4_unicode_ci`
   - Créer

3. **Importer schema.sql**:
   - Sélectionner la base créée
   - Onglet: "Importer"
   - Charger fichier: `backend/config/schema.sql`
   - Exécuter

4. **Vérifier**:
   ```sql
   SHOW TABLES;
   -- Doit afficher 3 tables:
   -- fin_ecritures_fec
   -- sys_plan_comptable
   -- sys_utilisateurs
   
   SELECT * FROM sys_utilisateurs;
   -- Doit afficher 3 users:
   -- admin@example.com
   -- comptable@example.com
   -- viewer@example.com
   ```

✅ **Après**: Base de données prête avec données de test

---

### **ÉTAPE 3: Changer JWT_SECRET (⭐ TRÈS IMPORTANT!) (5 min)**

**Via SSH Ionos** (meilleure méthode):

```bash
# 1. Se connecter SSH
ssh user@ionos-server.com

# 2. Générer NOUVELLE clé sécurisée
openssl rand -hex 32
# Copier la sortie, exemple: a3f2b8c1d4e5f6g7h8i9j0k1l2m3n4o5

# 3. Éditer .env
nano .env

# 4. Chercher cette ligne:
# JWT_SECRET=changez_moi_en_production_min_32_caracteres_aleatoires

# 5. Remplacer par:
# JWT_SECRET=a3f2b8c1d4e5f6g7h8i9j0k1l2m3n4o5

# 6. Sauvegarder:
# Ctrl+X, puis Y, puis Entrée

# 7. Vérifier:
cat .env | grep JWT_SECRET
```

**Via FTP (si pas SSH):**
1. Télécharger `.env` depuis Ionos
2. Éditer avec Notepad++/VS Code:
   - Chercher: `JWT_SECRET=changez_moi_en_production_min_32_caracteres_aleatoires`
   - Remplacer par: `JWT_SECRET=<valeur_générée_openssl>`
3. Re-uploader `.env`

⚠️ **IMPORTANT**: 
- Générer une NOUVELLE clé pour CHAQUE déploiement
- Ne PAS utiliser la même clé dev/prod
- Sauvegarder la clé dans un gestionnaire de mots de passe
- NE PAS la partager par email/chat

✅ **Après**: JWT_SECRET changé, app est sécurisée

---

## ✅ VÉRIFIER QUE TOUT FONCTIONNE

### Test 1: Frontend charge
```bash
curl -I https://compta.sarlatc.com/
# Doit retourner: HTTP/2 200 OK
```

### Test 2: API Login fonctionne
```bash
curl -X POST https://compta.sarlatc.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password123"}'

# Doit retourner:
# {
#   "success": true,
#   "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
#   "user": {...}
# }
```

### Test 3: Token JWT valide
```bash
# Copier le token du Test 2
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGc..."

curl -X GET https://compta.sarlatc.com/api/auth/verify \
  -H "Authorization: Bearer $TOKEN"

# Doit retourner:
# {"success":true,"message":"Token valide"}
```

### Test 4: Frontend (navigateur)
- Accéder: `https://compta.sarlatc.com/`
- Login: `admin@example.com` / `password123`
- Dashboard doit charger ✅

---

## 🎯 RÉSUMÉ: AVANT/APRÈS

### ❌ AVANT (Maintenant)
```
- Projet en développement local
- JWT_SECRET = dev/test
- Pas de base de données
- Frontend pas buildé
```

### ✅ APRÈS (Après 3 étapes)
```
- Projet en production sur Ionos
- JWT_SECRET = unique et sécurisé
- Base de données + données de test
- Frontend buildé + optimisé
- API fonctionnelle
- Prêt pour utilisateurs! 🚀
```

---

## 📚 RÉFÉRENCES

- **Aide complète**: [IONOS_PRODUCTION.md](IONOS_PRODUCTION.md)
- **Checklist détaillée**: [DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md)
- **Documentation API**: [docs/API_DOCUMENTATION.md](docs/API_DOCUMENTATION.md)
- **Troubleshooting**: [IONOS_PRODUCTION.md#-troubleshooting](IONOS_PRODUCTION.md#-troubleshooting)

---

## ❓ QUESTIONS FRÉQUENTES

**Q: Quels identifiants Ionos il me faut?**  
R: Identifiants FTP/SFTP + accès PhpMyAdmin. Voir espace client Ionos.

**Q: .env contient mes identifiants, c'est pas grave?**  
R: OUI c'est grave, mais:
- Fichier `.env` ne doit PAS être public (ajouter `.htaccess`)
- Les identifiants sont déjà dans espace client Ionos
- JWT_SECRET doit être changé régulièrement

**Q: Je dois changer le mot de passe `password123`?**  
R: Oui, en production. Via PhpMyAdmin:
```sql
UPDATE sys_utilisateurs 
SET mot_de_passe = '$2y$10$...'  -- bcrypt hash
WHERE email = 'admin@example.com';
```

**Q: Comment générer bcrypt en CLI?**  
R: 
```bash
php -r "echo password_hash('nouveau_mdp', PASSWORD_BCRYPT);"
```

**Q: Après tout ça, l'app va arrêter de fonctionner localement?**  
R: Non, `.env` local n'est pas affecté. Dev continue de marcher.

---

## 🚦 CHECKLIST RAPIDE

- [ ] Fichiers uploadés via FTP (étape 1)
- [ ] Base de données créée (étape 2)
- [ ] schema.sql exécuté (étape 2)
- [ ] Tables vérifiées (étape 2)
- [ ] JWT_SECRET changé via SSH (étape 3)
- [ ] Test 1: Frontend charge ✅
- [ ] Test 2: Login API fonctionne ✅
- [ ] Test 3: Token JWT valide ✅
- [ ] Test 4: Frontend login réussit ✅
- [ ] Prêt pour production! 🚀

---

## ⏸️ PAUSE - ATTENTE DE TES ACTIONS

J'ai fait tout ce que je pouvais faire localement. Maintenant **C'EST À TOI**:

1. **Tu dois uploaders les fichiers** (via FTP ou SSH)
2. **Tu dois créer la base de données** (via PhpMyAdmin)
3. **Tu dois changer JWT_SECRET** (via SSH ou FTP)

**Après que tu aies fait ces 3 étapes**, dis-moi et je peux:
- Vérifier les erreurs si quelque chose ne fonctionne pas
- Déboguer les problèmes d'authentification
- Modifier du code si besoin

**Bonne chance! 🚀**
