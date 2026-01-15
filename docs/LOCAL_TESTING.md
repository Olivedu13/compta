# Guide de Test Local

## 🚀 Démarrage du Frontend React

### Prérequis
- Node.js 18+ installé
- npm 9+ installé
- Dépendances installées: `npm install` (déjà fait)

### Lancer le serveur dev

```bash
cd /workspaces/compta/frontend
npm run dev
```

**Output attendu:**
```
VITE v5.4.21  ready in XXX ms

➜  Local:   http://localhost:5173/
➜  press h to show help
```

### Accéder à l'app

Ouvrir dans le navigateur: **http://localhost:5173**

---

## 🔐 Test du Flux d'Authentification

### Étape 1: Vérifier la Redirection Login

1. Accéder: http://localhost:5173
2. ✅ Devrait rediriger vers http://localhost:5173/login

### Étape 2: Tester le Login

1. **Entrer les identifiants:**
   - Email: `admin@atelier-thierry.fr`
   - Mot de passe: `password123`

2. **Cliquer sur "Se connecter"**

3. ✅ **Attendu:**
   - Spinner de chargement
   - Redirection vers http://localhost:5173/dashboard
   - Affichage du dashboard

### Étape 3: Vérifier le Token

1. **Ouvrir DevTools:** F12 ou Ctrl+Shift+I
2. **Onglet:** Application → LocalStorage → http://localhost:5173

3. ✅ **Devrait voir:**
   - Clé: `token` (contient: `eyJ0...`)
   - Clé: `user` (contient: `{"uid":1,...}`)

### Étape 4: Naviguer entre Pages

**Cliquer sur:**
- Dashboard ✅
- Import ✅
- Rapports SIG ✅
- Balance ✅
- Paramètres ✅

Tous les pages devraient charger sans erreur.

### Étape 5: Tester le Logout

1. **Cliquer sur l'avatar utilisateur** (haut droite)
2. **Cliquer sur "Déconnexion"**
3. ✅ **Attendu:**
   - Redirection vers http://localhost:5173/login
   - localStorage vidé (token + user supprimés)
   - Possible de re-login

### Étape 6: Tester la Protection des Routes

1. **Après logout, ouvrir la console JavaScript:**
   ```javascript
   localStorage.removeItem('token');
   localStorage.removeItem('user');
   ```

2. **Naviguer vers:** http://localhost:5173/dashboard

3. ✅ **Attendu:**
   - Redirection automatique vers /login
   - Message "Veuillez vous connecter" (optionnel)

---

## 🧪 Test Avancé - DevTools

### Network Tab

1. **Ouvrir DevTools → Network**
2. **Login avec:**
   - Email: `admin@atelier-thierry.fr`
   - Password: `password123`

3. **Vérifier les requêtes:**

   **POST /api/auth/login.php**
   ```
   Status: 200
   Payload: 
   {
     "success": true,
     "token": "eyJ0...",
     "user": {...},
     "expiresIn": 86400
   }
   ```

4. **Vérifier les headers:**
   ```
   Request Headers:
   - Content-Type: application/json
   - Authorization: Bearer eyJ0... (pour les requêtes suivantes)
   
   Response Headers:
   - Content-Type: application/json
   ```

### Console Tab

1. **Ouvrir DevTools → Console**

2. **Vérifier le token:**
   ```javascript
   console.log(localStorage.getItem('token'));
   // → "eyJ0hbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
   ```

3. **Vérifier l'user:**
   ```javascript
   console.log(JSON.parse(localStorage.getItem('user')));
   // → {uid: 1, email: "admin@atelier-thierry.fr", ...}
   ```

4. **Décoder le JWT (pour debug):**
   ```javascript
   const token = localStorage.getItem('token');
   const payload = token.split('.')[1];
   const decoded = JSON.parse(atob(payload));
   console.log(decoded);
   ```

---

## 🎯 Utilisateurs de Test

Tester avec les 3 rôles:

### Admin
```
Email:    admin@atelier-thierry.fr
Password: password123
Role:     admin
```
✅ Accès complet à toutes les pages

### Comptable (User)
```
Email:    comptable@atelier-thierry.fr
Password: password123
Role:     user
```
✅ Accès aux pages principales (import, reports, etc.)

### Viewer
```
Email:    viewer@atelier-thierry.fr
Password: password123
Role:     viewer
```
✅ Accès lecture seule (dashboards, reports)

---

## 📊 Test des Pages

### Dashboard
- Vérifier que les KPI se chargent
- Vérifier les graphiques s'affichent
- Tester le sélecteur d'année

### Import
- Vérifier la zone de drop de fichiers
- Tester l'upload d'un fichier FEC
- Vérifier les messages de succès/erreur

### Reports SIG
- Vérifier l'affichage des cascades SIG
- Tester le filtrage par compte

### Balance
- Vérifier le tableau de balance se charge
- Tester le tri par colonne
- Vérifier les montants

### Paramètres
- Vérifier que la page s'affiche
- Tester les changements de configuration

---

## 🐛 Problèmes Courants

### Problème: "Cannot GET /"
**Solution:** Vérifier que npm run dev est lancé
```bash
npm run dev
# Devrait afficher: Local: http://localhost:5173/
```

### Problème: "Module not found: @mui/material"
**Solution:** Installer les dépendances
```bash
cd frontend
npm install
```

### Problème: Login échoue avec 401
**Solutions:**
1. Vérifier les identifiants (admin@atelier-thierry.fr / password123)
2. Vérifier que la DB a les utilisateurs test (schema.sql exécuté)
3. Vérifier les logs: `backend/logs/`

### Problème: Token n'est pas envoyé à l'API
**Solution:** Vérifier l'intercepteur axios
```javascript
// Dans frontend/src/services/api.js
// Devrait voir:
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});
```

### Problème: Pages blanches après login
**Solutions:**
1. Ouvrir la console (F12) et chercher les erreurs
2. Vérifier les onglets Network pour les requêtes
3. Vérifier que `/api/` est accessible
4. Redémarrer le serveur dev

### Problème: "CORS error"
**Solutions:**
1. Vérifier que CORS_ORIGIN dans .env est correct
2. Vérifier les headers CORS dans les endpoints PHP
3. Pour dev, CORS devrait être permissif (ou désactivé)

---

## ✅ Checklist de Test

- [ ] npm run dev lance sans erreurs
- [ ] http://localhost:5173 charge la page
- [ ] Redirection vers /login OK
- [ ] Login avec admin@atelier-thierry.fr OK
- [ ] Token généré et stocké en localStorage
- [ ] Redirection vers /dashboard après login
- [ ] Dashboard affiche les données
- [ ] Navigation entre pages fonctionne
- [ ] Logout fonctionne et nettoie localStorage
- [ ] Redirection vers /login après logout
- [ ] Impossible d'accéder /dashboard après logout
- [ ] Test avec user comptable OK
- [ ] Test avec user viewer OK
- [ ] Console DevTools sans erreurs
- [ ] Network tab affiche les bonnes requêtes

---

## 🚀 Prêt pour Production?

Avant de déployer sur Ionos:

- [ ] Tous les tests locaux OK
- [ ] npm run build réussit
- [ ] Assets générés sans erreurs
- [ ] Console sans warnings/errors
- [ ] Logout puis login fonctionne
- [ ] Page refresh mantient la session (token stocké)

Si tout est OK → Prêt pour Ionos!

---

## 📞 Debug Avancé

### Activer le logging (Frontend)

Dans `frontend/src/services/api.js`:
```javascript
api.interceptors.response.use(
  response => {
    console.log('[API] Success:', response.config.url, response.data);
    return response;
  },
  error => {
    console.error('[API] Error:', error.config.url, error.response?.data);
    return Promise.reject(error);
  }
);
```

### Vérifier les Cookies vs LocalStorage

Le système utilise **localStorage** (pas de cookies HttpOnly).

Pour vérifier:
```javascript
// Dans la console:
console.log('Token:', localStorage.getItem('token'));
console.log('User:', localStorage.getItem('user'));
console.log('All LocalStorage:', localStorage);
```

### Redémarrer le Serveur

Si rien ne fonctionne:
```bash
# Arrêter (Ctrl+C)
# Puis:
npm run dev
```

---

## 📚 Documentation Utile

- [Déploiement Ionos](./IONOS_UPLOAD.md)
- [API Documentation](./API_DOCUMENTATION.md)
- [Deployment Guide](./DEPLOYMENT_GUIDE.md)
