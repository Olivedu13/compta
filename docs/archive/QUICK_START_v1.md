# ⚡ Quick Start - 5 Minutes

## 🚀 Démarrage Immédiat (Développement)

### 1️⃣ Lancer le Frontend
```bash
cd /workspaces/compta/frontend
npm run dev
```
👉 Ouvrir: **http://localhost:5173**

### 2️⃣ Identifiants Test
```
Email:    admin@atelier-thierry.fr
Password: password123
```

### 3️⃣ Tester
- ✅ Login → ✅ Dashboard → ✅ Logout

---

## 🔧 Après DB Exécuté

1. **DB OK?** → Utilisateurs test présents ✅
2. **npm run dev?** → Frontend démarre ✅
3. **Login fonctionne?** → Token généré ✅

Si OUI → Prêt pour Ionos!

---

## 📦 Pour Production (Ionos)

### Build
```bash
npm run build  # Génère assets
```

### Upload
```
Files à uploader:
- backend/config/JwtManager.php
- backend/config/AuthMiddleware.php
- public_html/api/auth/login.php
- public_html/api/auth/verify.php
- public_html/assets/index.js
- .env (avec JWT_SECRET fort!)
```

### Test
```bash
curl -X POST https://compta.sarlatc.com/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@atelier-thierry.fr","password":"password123"}'
```

---

## 📚 Docs Complètes

- **Test Local:** `LOCAL_TESTING.md`
- **Déploiement:** `DEPLOYMENT_GUIDE.md`
- **Ionos:** `IONOS_UPLOAD.md`
- **API:** `API_DOCUMENTATION.md`

---

## 🆘 Problèmes?

| Problème | Solution |
|----------|----------|
| Port 5173 en use | `kill -9 $(lsof -t -i:5173)` |
| Login échoue | Vérifier schema.sql exécuté + credentials |
| Token manquant | Vérifier localStorage (DevTools) |
| CORS error | Vérifier CORS_ORIGIN dans .env |

---

## ✨ C'est Tout!

**La plateforme est prête pour production.** 🎊

Prochaine étape → Ionos upload!
