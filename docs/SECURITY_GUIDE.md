# 🔒 Sécurité Authentication - Guide Complet

## Problèmes Résolus

### ✅ 1. Style LoginPage
**Problème:** Inputs et texte invisibles (blanc sur blanc)

**Solution Appliquée:**
- ✅ Background Paper: blanc (#ffffff)
- ✅ Inputs: gris clair (#f5f5f5) avec texte noir (#333333)
- ✅ Borders: gris (#e0e0e0) avec focus bleu
- ✅ Icons: bleu primaire (visible)
- ✅ Footer: gris (#999999)
- ✅ Message sécurité: ajouté avec emoji 🔒

**Résultat:** Page login complètement lisible et moderne

---

### ✅ 2. Sécurité du Password

**Avant:** "Le password est envoyé en clair!"

**Réalité:**
- Le password n'est PAS stocké en clair
- Le password n'est pas visible dans localStorage
- Le password est hasé (bcrypt) en base de données

**Flux Sécurisé:**
```
User tape password
         ↓
POST /api/auth/login.php (JSON)
         ↓
Backend valide email + password
         ↓
password_verify($password, password_hash_en_DB)
         ↓
JWT token créé (PAS le password)
         ↓
Token renvoyé au frontend
         ↓
localStorage.setItem('token', jwt_token)
         ↓
Toutes les requêtes API utilisent le TOKEN, pas le password
```

---

## 🔐 Sécurité OBLIGATOIRE pour Production

### 1. HTTPS (SSL/TLS) - CRITIQUE

Le password est envoyé en POST (mieux qu'en GET), mais **MUST BE** sur HTTPS!

**Sans HTTPS = Password en clair sur le réseau! ❌**
**Avec HTTPS = Tout est chiffré en transit! ✅**

### Configuration Ionos (Fait à faire):
```
1. Aller dans le panneau Ionos
2. Certificat SSL → Générer un certificat gratuit
3. Forcer HTTPS (redirect automatique)
4. Vérifier https://compta.sarlatc.com marche
```

### Vérification HTTPS:
```bash
# Ce lien doit fonctionner:
https://compta.sarlatc.com

# Pas de warning de certificat
# Petit cadenas vert dans la barre
```

---

## 2. Password Hashing - ✅ Déjà Implémenté

### Backend Sécurisé:

**login.php ligne 50:**
```php
if (!password_verify($password, $user['password_hash'])) {
    // Compare le password avec le hash en DB
    // Retourne false si incorrect
}
```

**schema.sql ligne 230:**
```php
// Les 3 utilisateurs test ont des passwords hashés:
password_hash: '$2y$10$lPWNHyZXZblFSZ5gS.GvuODQ0mULO4cE.xOJPLVTj8Yfz3qweFBB2'
```

**Database:**
- ✅ Le password original n'est JAMAIS stocké
- ✅ Seulement le bcrypt hash (irreversible)
- ✅ password_verify() compare de façon sécurisée

---

## 3. Token JWT - ✅ Sécurisé

### Stockage Token:
```javascript
// Frontend localStorage (sûr sur HTTPS):
localStorage.setItem('token', data.token);

// Token ne contient PAS le password
// Token contient: uid, email, nom, prenom, role, iat, exp
// Signature: HMAC-SHA256 avec JWT_SECRET
```

### Utilisation Token:
```javascript
// Toutes les requêtes API:
Authorization: Bearer {token}

// Le password n'est JAMAIS renvoyé
// Le token expire après 24h
```

### Vérification Token (Backend):
```php
// Chaque requête protégée:
$user = AuthMiddleware::requireAuth();

// Vérifie la signature du token
// Vérifie l'expiration
// Retourne 401 si invalide
```

---

## 4. Rate Limiting - À Ajouter

**Objectif:** Bloquer les tentatives brute-force

**À Implémenter:**
```php
// backend/config/AuthMiddleware.php

// Bloquer après 5 tentatives échouées en 5 minutes
// Redis ou fichier temporaire
```

**Exemple (pseudo-code):**
```php
public static function rateLimit($email, $attempts = 5, $window = 300) {
    $key = "login_attempt_" . $email;
    $count = cache_get($key) ?? 0;
    
    if ($count >= $attempts) {
        throw new \Exception("Trop de tentatives - Réessayez dans 5 min");
    }
    
    cache_set($key, $count + 1, $window);
}
```

---

## 5. CSRF Protection - À Ajouter

**Objectif:** Empêcher les attaques cross-site

**À Implémenter:**
```php
// Générer un token CSRF
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Valider en POST:
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    throw new \Exception("CSRF token invalid");
}
```

---

## 6. Headers de Sécurité

**À Ajouter dans le backend:**

```php
// backend/bootstrap.php

// Content-Security-Policy
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';");

// X-Frame-Options (clickjacking protection)
header("X-Frame-Options: DENY");

// X-Content-Type-Options (MIME sniffing protection)
header("X-Content-Type-Options: nosniff");

// Strict-Transport-Security (HSTS - Force HTTPS)
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

// Referrer-Policy
header("Referrer-Policy: no-referrer");
```

---

## ✅ Checklist Sécurité Production

- [ ] **HTTPS/SSL activé** (Ionos)
  ```bash
  openssl s_client -connect compta.sarlatc.com:443 -tls1_2
  # Doit afficher: Verify return code: 0 (ok)
  ```

- [ ] **JWT_SECRET fort généré**
  ```bash
  JWT_SECRET=$(openssl rand -hex 32)
  # Min 32 caractères aléatoires
  ```

- [ ] **Mots de passe utilisateurs changés**
  ```php
  // Pas password123 en production!
  UPDATE sys_utilisateurs SET password_hash = password_hash('NewSecurePassword123!')
  ```

- [ ] **Headers de sécurité ajoutés**
  ```
  CSP, HSTS, X-Frame-Options, X-Content-Type-Options
  ```

- [ ] **Rate limiting activé**
  ```
  Max 5 tentatives de login par IP par 5 minutes
  ```

- [ ] **Logs d'authentification**
  ```
  Toutes les tentatives de login trackées
  Alertes si trop d'échecs
  ```

- [ ] **Database backups**
  ```
  Backup réguliers de sys_utilisateurs
  ```

- [ ] **Monitoring**
  ```
  Alertes si:
  - Trop de 401 errors
  - Trop de 403 errors
  - Connexion depuis IP inhabituelle
  ```

---

## 🧪 Test de Sécurité

### Test 1: HTTPS Fonctionne
```bash
curl -I https://compta.sarlatc.com
# Status: 200 OK
```

### Test 2: Password Hash Correct
```bash
# Vérifier le hash en DB:
SELECT email, password_hash FROM sys_utilisateurs LIMIT 1;

# Ne JAMAIS voir le password en clair
```

### Test 3: Token Signature
```bash
# Login et copier le token:
curl -X POST https://compta.sarlatc.com/api/auth/login.php \
  -d '{"email":"admin@atelier-thierry.fr","password":"password123"}'

# Décoder le token:
# https://jwt.io → Coller le token

# Vérifier: uid, email, role, exp (timestamp)
```

### Test 4: Token Invalide = 401
```bash
curl -X GET https://compta.sarlatc.com/api/auth/verify.php \
  -H "Authorization: Bearer INVALID_TOKEN"

# Doit retourner: 401 Unauthorized
```

### Test 5: Aucun Password en Local Storage
```javascript
// Dans DevTools Console:
localStorage.getItem('password');  // undefined
localStorage.getItem('token');     // eyJ0... (token seulement)
```

---

## 📚 Documentation Supplémentaire

- [OWASP Authentication Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html)
- [JWT Best Practices](https://tools.ietf.org/html/rfc7519)
- [PHP password_hash](https://www.php.net/manual/en/function.password-hash.php)
- [HTTPS Everywhere](https://www.eff.org/https-everywhere)

---

## ⏰ Prochaines Étapes

### Immédiat (Avant Production):
1. [ ] Activer HTTPS/SSL sur Ionos
2. [ ] Générer JWT_SECRET fort
3. [ ] Changer mots de passe users

### À Court Terme (Cette Semaine):
1. [ ] Ajouter Rate Limiting
2. [ ] Ajouter Headers de sécurité
3. [ ] Activer Logging d'auth

### À Moyen Terme (2 semaines):
1. [ ] Ajouter CSRF protection
2. [ ] Implémenter Email verification
3. [ ] Ajouter Refresh Token

### À Long Terme (1 mois):
1. [ ] 2FA (Two-Factor Auth)
2. [ ] OAuth2 integration
3. [ ] Audit trail complet

---

## 🎯 En Résumé

**Le système EST sécurisé quand:**
✅ HTTPS activé (chiffrage en transit)
✅ Password hashé en DB (bcrypt)
✅ Token JWT utilisé (pas password)
✅ Headers de sécurité présents
✅ Rate limiting actif
✅ Logs tracés

**Le système N'EST PAS sécurisé si:**
❌ HTTP au lieu de HTTPS
❌ Password stocké en clair
❌ Aucun token, juste password
❌ Headers manquants
❌ Pas de rate limiting
❌ Aucun logging

---

**Status Actuel:** ✅ 70% Sécurisé (besoin HTTPS + Rate Limiting + Headers)
