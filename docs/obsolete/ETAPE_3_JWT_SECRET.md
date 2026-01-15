# 🔐 ÉTAPE 3: Changer JWT_SECRET (Explication Détaillée)

## 🎯 Qu'est-ce que JWT_SECRET?

**JWT_SECRET** = La clé secrète qui signe vos tokens d'authentification.

- **Dev**: JWT_SECRET = connu/public (c'est ok, c'est du dev)
- **Production**: JWT_SECRET = DOIT être unique et sécurisé ⚠️

### Pourquoi changer?

```
Scénario DANGEREUX (sans changement):
1. Quelqu'un voit le JWT_SECRET dev sur GitHub
2. Il génère un faux token valide
3. Il accède à l'application en tant qu'admin
4. Disaster! 😱

Scénario SÉCURISÉ (avec changement):
1. JWT_SECRET dev publié? Pas grave!
2. Ionos a JWT_SECRET UNIQUE et secret
3. Faux token ne sera pas valide
4. App sécurisée! 🔐
```

---

## 📋 État Actuel

**Fichier .env sur Ionos ACTUELLEMENT:**
```env
JWT_SECRET=changez_moi_en_production_min_32_caracteres_aleatoires
```

**C'est un placeholder!** On doit le changer par une vraie clé sécurisée.

---

## 🚀 COMMENT FAIRE (2 options)

### ✅ OPTION 1: Via SSH (Recommandé - 3 min)

#### Étape A: Se connecter SSH à Ionos

```bash
ssh acc1249301374@home210120109.1and1-data.host
# Entrer mot de passe: userCompta!90127452?
```

#### Étape B: Générer une nouvelle clé sécurisée

Une fois connecté, exécuter:

```bash
openssl rand -hex 32
```

**Sortie attendue** (copier ça!):
```
a3f2b8c1d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0
```

**✅ Copier cette valeur exactement!**

#### Étape C: Éditer le fichier .env

```bash
nano .env
```

**Dans nano:**
```
# Chercher la ligne:
JWT_SECRET=changez_moi_en_production_min_32_caracteres_aleatoires

# La remplacer par:
JWT_SECRET=a3f2b8c1d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0
```

**Sauvegarder:**
- Appuyer sur: `Ctrl + X`
- Répondre: `Y` (yes)
- Appuyer sur: `Entrée` (valider)

**Vérifier:**
```bash
cat .env | grep JWT_SECRET
# Doit afficher: JWT_SECRET=a3f2b8c1d4e5f6...
```

#### Étape D: Fin SSH

```bash
exit
# ou Ctrl+D
```

---

### ✅ OPTION 2: Via FTP (Alternative - 5 min)

**Si tu n'as pas accès SSH, cette option marche aussi:**

#### Étape A: Générer la clé localement

Sur TON ordinateur (terminal/cmd):
```bash
openssl rand -hex 32
```

(Copier la sortie)

#### Étape B: Télécharger .env depuis Ionos

Via FTP (Filezilla/WinSCP):
1. Se connecter Ionos FTP
2. Naviguer à la racine
3. Télécharger le fichier `.env` (clic droit → Télécharger)

#### Étape C: Éditer .env localement

Ouvrir avec **Notepad++** ou **VS Code**:

```env
# Avant:
JWT_SECRET=changez_moi_en_production_min_32_caracteres_aleatoires

# Après (coller la clé générée):
JWT_SECRET=a3f2b8c1d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0
```

**Sauvegarder** le fichier.

#### Étape D: Re-uploader .env

Via FTP:
1. Sélectionner le `.env` modifié
2. Clic droit → Uploader
3. Remplacer le fichier existant

---

## ✅ VÉRIFIER QUE C'EST BON

**Sur le serveur** (via SSH):
```bash
# Vérifier la nouvelle clé
cat .env | grep JWT_SECRET
# Doit afficher: JWT_SECRET=a3f2b8c1d4e5f6... (pas l'ancien placeholder)

# Vérifier que .env est à la racine
pwd
# Doit afficher: /kunden/... ou similaire
```

**Puis tester l'app:**
```bash
# Depuis ton ordinateur, test login:
curl -X POST https://compta.sarlatc.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password123"}'

# Doit retourner: {"success":true,"token":"..."}
```

---

## 🎯 RÉSUMÉ RAPIDE

```
1. SSH vers Ionos (ou FTP)
   └─ acc1249301374@home210120109.1and1-data.host
      ou mot de passe: userCompta!90127452?

2. Générer clé: openssl rand -hex 32

3. Éditer .env:
   └─ Remplacer JWT_SECRET = <nouvelle_clé>
      Sauvegarder

4. Vérifier: cat .env | grep JWT_SECRET

5. Tester: curl https://compta.sarlatc.com/api/auth/login
```

---

## ⚠️ ATTENTION

**NE PAS:**
- ❌ Utiliser le même JWT_SECRET dev/prod
- ❌ Partager JWT_SECRET par email/chat
- ❌ Committer .env dans Git
- ❌ Laisser placeholder sur production

**À FAIRE:**
- ✅ Générer avec `openssl rand -hex 32`
- ✅ Sauvegarder la clé dans gestionnaire de mots de passe
- ✅ Changer régulièrement (ex: tous les 6 mois)
- ✅ Tester après changement

---

## 🆘 PROBLÈMES COURANTS

### "Pas d'accès SSH"
→ Utiliser Option 2 (FTP)

### "nano: command not found"
→ Essayer: `vi .env` ou `vim .env` (même éditeur, touches différentes)

### "Permission denied" en éditant .env
→ Vérifier permissions: `ls -la .env` (doit être 644)
→ Si besoin: `chmod 644 .env`

### "Command not found: openssl"
→ Demander à Ionos support (généralement installé)
→ Alternative: Générer clé sur ton ordinateur avec openssl

### Login ne marche pas après changement JWT_SECRET
→ Les tokens ANCIENS ne sont pas valides
→ Déconnecter: Effacer localStorage
→ Se reconnecter: Nouveau token avec nouvelle clé

---

## 📞 AIDE

Besoin d'aide SSH?
- SSH = "accès terminal" au serveur Ionos
- Pas besoin de GUI (pas de clics)
- Juste des commandes texte

Identifier tes identifiants:
```
Hôte SSH: home210120109.1and1-data.host
User: acc1249301374
Password: userCompta!90127452?
```

**Sur ton ordinateur (Mac/Linux/Windows avec Git Bash):**
```bash
ssh acc1249301374@home210120109.1and1-data.host
```

---

## ✅ QUAND C'EST BON

L'étape 3 est terminée quand:
- ✅ JWT_SECRET est changé sur Ionos
- ✅ Pas le placeholder "changez_moi_..."
- ✅ Une vraie clé aléatoire (32 caractères hex)
- ✅ Login fonctionne sur https://compta.sarlatc.com/

**Après ça, l'app est prête pour production! 🚀**

---

**Dis-moi quand c'est fait et je peux vérifier si tout fonctionne!**
