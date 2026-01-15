# API Documentation - Endpoints d'Authentification

## 📌 Base URL

- **Dev:** `http://localhost:5173/api/`
- **Production:** `https://compta.sarlatc.com/api/`

---

## 🔐 POST /auth/login.php

Authentifier un utilisateur et obtenir un JWT token.

### Request

```http
POST /api/auth/login.php HTTP/1.1
Content-Type: application/json

{
  "email": "admin@atelier-thierry.fr",
  "password": "password123"
}
```

### Parameters

| Param | Type | Required | Description |
|-------|------|----------|-------------|
| email | string | Yes | Email utilisateur |
| password | string | Yes | Mot de passe utilisateur |

### Response - Success (200)

```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "uid": 1,
    "email": "admin@atelier-thierry.fr",
    "nom": "Admin",
    "prenom": "System",
    "role": "admin"
  },
  "expiresIn": 86400
}
```

### Response - Errors

**400 Bad Request - Input Invalid**
```json
{
  "success": false,
  "message": "Email ou mot de passe invalide"
}
```

**401 Unauthorized - Invalid Credentials**
```json
{
  "success": false,
  "message": "Utilisateur non trouvé ou mot de passe incorrect"
}
```

**500 Server Error**
```json
{
  "success": false,
  "message": "Erreur serveur"
}
```

### Curl Example

```bash
curl -X POST https://compta.sarlatc.com/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@atelier-thierry.fr",
    "password": "password123"
  }'
```

---

## ✅ GET /auth/verify.php

Vérifier que le JWT token actuel est valide.

### Request

```http
GET /api/auth/verify.php HTTP/1.1
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### Headers

| Header | Required | Description |
|--------|----------|-------------|
| Authorization | Yes | Bearer token (Bearer {JWT}) |

### Response - Success (200)

```json
{
  "success": true,
  "user": {
    "uid": 1,
    "email": "admin@atelier-thierry.fr",
    "nom": "Admin",
    "prenom": "System",
    "role": "admin",
    "iat": 1705330800,
    "exp": 1705417200
  }
}
```

### Response - Errors

**401 Unauthorized - Missing Token**
```json
{
  "success": false,
  "message": "Token manquant"
}
```

**401 Unauthorized - Invalid Token**
```json
{
  "success": false,
  "message": "Token invalide ou expiré"
}
```

### Curl Example

```bash
curl -X GET https://compta.sarlatc.com/api/auth/verify.php \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
```

---

## 🔑 JWT Token Structure

Le token retourné est un JWT (JSON Web Token) HS256.

### Payload

```json
{
  "uid": 1,
  "email": "admin@atelier-thierry.fr",
  "nom": "Admin",
  "prenom": "System",
  "role": "admin",
  "iat": 1705330800,
  "exp": 1705417200
}
```

### Fields

| Field | Type | Description |
|-------|------|-------------|
| uid | integer | User ID |
| email | string | Email utilisateur |
| nom | string | Nom utilisateur |
| prenom | string | Prénom utilisateur |
| role | string | Rôle (admin, user, viewer) |
| iat | integer | Issued At (timestamp) |
| exp | integer | Expiration (timestamp - 24h) |

### Token Expiration

- **Durée:** 86400 secondes (24 heures)
- **Renouvellement:** Login requis après expiration
- **Erreur:** 401 si expiré

---

## 🔑 Utilisateurs Test

Tous les mots de passe: `password123`

| Email | Role | ID |
|-------|------|-----|
| admin@atelier-thierry.fr | admin | 1 |
| comptable@atelier-thierry.fr | user | 2 |
| viewer@atelier-thierry.fr | viewer | 3 |

---

## 🛡️ Authentification des Requêtes

Toutes les requêtes protégées doivent inclure le token JWT:

```http
Authorization: Bearer {token}
```

**Exemple avec axios (Frontend):**

```javascript
import axios from 'axios';

const token = localStorage.getItem('token');
const config = {
  headers: {
    'Authorization': `Bearer ${token}`
  }
};

axios.get('/api/dashboard', config)
  .then(response => console.log(response.data))
  .catch(error => console.error(error));
```

---

## ⚙️ Configuration

### JWT_SECRET

Le JWT_SECRET doit être:
- **Minimum:** 32 caractères aléatoires
- **Type:** Chaîne hexadécimale
- **Génération:** `openssl rand -hex 32`

**Changement en production:**

```bash
# Générer une nouvelle clé
JWT_SECRET=$(openssl rand -hex 32)
echo "Nouvelle clé: $JWT_SECRET"

# Mettre à jour .env
sed -i "s/JWT_SECRET=.*/JWT_SECRET=$JWT_SECRET/" .env
```

### CORS

Les requêtes cross-origin sont autorisées pour:
- **Dev:** `localhost:5173`
- **Production:** `compta.sarlatc.com`

Configure dans `.env`:
```
CORS_ORIGIN=compta.sarlatc.com
```

---

## 🔒 Middleware d'Authentification

Pour protéger un endpoint PHP:

```php
<?php
require_once '../../backend/config/AuthMiddleware.php';

// Vérifier l'authentification
$user = AuthMiddleware::requireAuth();

// Utiliser $user
echo json_encode([
    'success' => true,
    'message' => "Bienvenue {$user->prenom}",
    'user' => $user
]);
```

### Vérifier les Rôles

```php
// Vérifier que l'utilisateur est admin
AuthMiddleware::requireRole($user, ['admin']);

// Vérifier que l'utilisateur est user ou admin
AuthMiddleware::requireRole($user, ['admin', 'user']);
```

---

## 📊 Cycle de Vie de l'Authentification

```
1. Login
   POST /api/auth/login.php
   ↓
2. Token Reçu
   Stocké en localStorage
   ↓
3. Requêtes Protégées
   Header Authorization: Bearer {token}
   ↓
4. Vérification
   JwtManager::verifyToken()
   ↓
5. Accès Autorisé
   Opération Effectuée
   ↓
6. Logout
   localStorage.clear()
```

---

## 🧪 Test des Endpoints

### Test Login

```bash
# Succès
curl -X POST http://localhost:5173/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@atelier-thierry.fr","password":"password123"}'

# Erreur: Mauvais mot de passe
curl -X POST http://localhost:5173/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@atelier-thierry.fr","password":"wrongpassword"}'
```

### Test Verify

```bash
# Récupérer le token
TOKEN=$(curl -s -X POST http://localhost:5173/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@atelier-thierry.fr","password":"password123"}' | jq -r .token)

# Vérifier le token
curl -X GET http://localhost:5173/api/auth/verify.php \
  -H "Authorization: Bearer $TOKEN"
```

---

## 🚨 Codes d'Erreur HTTP

| Code | Signification | Action |
|------|---|---|
| 200 | OK | Succès |
| 400 | Bad Request | Données invalides - Corriger la requête |
| 401 | Unauthorized | Token absent/invalide/expiré - Re-login requis |
| 403 | Forbidden | Permissions insuffisantes - Vérifier le rôle |
| 500 | Server Error | Erreur serveur - Consulter les logs |

---

## 📝 Headers de Réponse

Toutes les réponses incluent:

```
Content-Type: application/json
Access-Control-Allow-Origin: compta.sarlatc.com
Access-Control-Allow-Methods: GET, POST, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
```

---

## 🔗 Liens Utiles

- [JWT Documentation](https://jwt.io)
- [PHP password_hash](https://www.php.net/manual/en/function.password-hash.php)
- [CORS Specification](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS)
