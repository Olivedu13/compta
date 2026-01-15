# 📋 ROADMAP SÉCURITÉ - 3 PHASES

**État:** Après audit complet  
**Date de démarrage:** 15/01/2026  
**Priorité:** CRITIQUE (avant production)

---

## PHASE 1️⃣ - 24 HEURES (URGENT - Dès maintenant)

**Objectif:** Corriger les failles P0 les plus critiques

### ✅ Déjà Fait
- [x] Créer `.env` pour environment variables
- [x] Refactoriser `bootstrap.php` pour charger `.env`
- [x] Créer `InputValidator.php` pour validation centralisée
- [x] Refactoriser `Database.php` (credentials from env)
- [x] Refactoriser `balance-simple.php` (template secure)

### 🔄 À Faire (Immédiat)

#### Task 1.1: Refactoriser 9 fichiers *-simple.php
```
Temps estimé: 4-6 heures

Fichiers:
[ ] sig-simple.php
[ ] kpis-simple.php
[ ] kpis-detailed.php
[ ] analyse-simple.php
[ ] analytics-advanced.php
[ ] comptes-simple.php
[ ] annees-simple.php
[ ] debug-clients.php
[ ] debug-all-clients.php

Pattern: Appliquer exact même transformation que balance-simple.php
- Ajouter: require_once bootstrap.php
- Retirer: $dbConfig hardcoded
- Remplacer: SQL queries → Parameterized
- Ajouter: InputValidator pour tous les params
- Standardiser: Error handling
```

**Vérification après chaque fichier:**
```bash
# Chercher les patterns dangereux
grep -n "mysql_\|\$_GET\[\|eval\|system" file.php  # Doit être vide
grep -n "?>" file.php  # Ne pas finir par ?>
```

#### Task 1.2: File Upload Validation (simple-import.php)
```
Temps estimé: 2 heures

Ajouter validations:
1. MIME type check (finfo)
2. File size limit (64MB max)
3. Content verification (not just extension)

Code pattern:

use App\Config\InputValidator;

try {
    $tmpFile = $file['tmp_name'];
    $filename = $file['name'];
    
    // 1. Check file exists
    if (!is_uploaded_file($tmpFile)) {
        throw new Exception("Invalid upload");
    }
    
    // 2. Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmpFile);
    finfo_close($finfo);
    
    InputValidator::validateMimeType(
        $mime, 
        ['text/plain', 'application/gzip', 'application/octet-stream']
    );
    
    // 3. Check file size
    $filesize = filesize($tmpFile);
    InputValidator::validateFileSize($filesize, 67108864); // 64MB
    
    // 4. Process (FecAnalyzer already validates content)
    $analyzer = new FecAnalyzer();
    $result = $analyzer->analyze($tmpFile);
    
} catch (InvalidArgumentException $e) {
    Logger::warning("Invalid file upload", ['error' => $e->getMessage()]);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file']);
}
```

#### Task 1.3: Configuration des Headers Sécurité (.htaccess)
```
Temps estimé: 30 minutes

Ajouter au début de /public_html/.htaccess:

# Security Headers
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"

# CORS restrictif (temporaire, sera remplacé par JWT)
Header set Access-Control-Allow-Origin "https://yourdomain.com"
Header set Access-Control-Allow-Credentials "true"
Header set Access-Control-Allow-Methods "GET, POST, OPTIONS"
Header set Access-Control-Allow-Headers "Content-Type, Authorization"

# Compression
mod_deflate enablements...
```

### 📊 Résultat Attendu Phase 1
- ✅ 0 failles SQL injection
- ✅ 0 credentials en dur
- ✅ 0 input non-validés
- ✅ 0 uploads non-vérifiés
- ✅ 10 fichiers PHP sécurisés + refactorisés

### ⏰ Timeline Phase 1
```
| Lundi  | Mardi  | Mercredi |
|--------|--------|----------|
| Task 1.1 (6h) | Task 1.1 (suite 2h) | Task 1.2 (2h) |
|              | Task 1.2 (1h)       | Task 1.3 (1h) |
|              | Testing (1h)        | Testing (1h)  |
```

---

## PHASE 2️⃣ - 48 HEURES (IMPORTANT)

**Objectif:** Ajouter authentication et authorization

### ✅ À Faire

#### Task 2.1: JWT Authentication Middleware
```
Temps estimé: 6-8 heures

Structure:
1. Endpoint /api/auth/login
   - Accepte username + password
   - Valide contre sys_utilisateurs
   - Retourne JWT token

2. Middleware JWT
   - Valide Authorization: Bearer {token}
   - Vérifie signature
   - Charge user context

3. Protéger tous les endpoints
   - GET /api/* → JWT requis
   - POST /api/* → JWT requis
   - CORS seulement pour domaine autorisé

Code Template:

<?php
require_once dirname(dirname(__FILE__)) . '/backend/bootstrap.php';

use App\Config\Database;
use App\Config\Logger;
use Firebase\JWT\JWT;

header('Content-Type: application/json');

try {
    // 1. Extract token from Authorization header
    $headers = getallheaders();
    $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
    
    if (!$token) {
        throw new Exception("Missing token");
    }
    
    // 2. Verify JWT
    $secret = getenv('JWT_SECRET');
    $decoded = JWT::decode($token, $secret, ['HS256']);
    
    // 3. Load user context
    $db = Database::getInstance();
    $user = $db->fetchOne(
        "SELECT * FROM sys_utilisateurs WHERE id = ? AND actif = 1",
        [$decoded->uid]
    );
    
    if (!$user) {
        throw new Exception("User not found");
    }
    
    // 4. Process request with $user context
    $data = json_decode(file_get_contents('php://input'), true);
    
    Logger::info("API call", ['user' => $user['username'], 'endpoint' => $_SERVER['REQUEST_URI']]);
    
    // ... rest of endpoint ...
    
} catch (Exception $e) {
    Logger::error("Auth failed", ['error' => $e->getMessage()]);
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
}
?>
```

#### Task 2.2: CSRF Token Protection
```
Temps estimé: 2-3 heures

Implémenter session-based CSRF tokens:
- Générer token au login
- Valider sur tous POST/PUT/DELETE
- Rejeter si invalide

// Dans login endpoint
session_start();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Dans form HTML
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

// Avant de traiter POST
if (!hash_equals($_POST['csrf_token'] ?? '', $_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    die(json_encode(['error' => 'CSRF token invalid']));
}
```

#### Task 2.3: Role-Based Access Control (RBAC)
```
Temps estimé: 3-4 heures

Ajouter roles à sys_utilisateurs:
- admin: Accès complet
- comptable: Lecture/Écriture données
- viewer: Lecture seule

Middleware check:

// Dans chaque endpoint
$requiredRole = 'comptable';
if (!in_array($user['role'], ['admin', $requiredRole])) {
    http_response_code(403);
    echo json_encode(['error' => 'Insufficient permissions']);
    exit;
}
```

### 📊 Résultat Attendu Phase 2
- ✅ Tous les endpoints protégés par JWT
- ✅ Roles/permissions implémentés
- ✅ CSRF tokens sur tous les forms
- ✅ Accès non-authentifié → 401
- ✅ Accès non-autorisé → 403

---

## PHASE 3️⃣ - 1 SEMAINE (ENHANCEMENT)

**Objectif:** Optimisations et hardening final

### ✅ À Faire

#### Task 3.1: Security Headers Avancés (CSP)
```
Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-...'; style-src 'self'
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

#### Task 3.2: Rate Limiting
```
Limiter requêtes par IP:
- Login: 5 tentatives / 15 minutes
- API: 100 requêtes / minute par user
- File upload: 10 fichiers / heure
```

#### Task 3.3: Tests Unitaires
```
Coverage minimum: 70%
- InputValidator tests
- FecAnalyzer tests
- Database connection tests
- Error handling tests

Exécuter:
vendor/bin/phpunit tests/ --coverage-html coverage/
```

#### Task 3.4: Tests d'Intégration
```
- Vérifier workflow complet FEC
- Tester toutes routes avec JWT
- Vérifier CORS
- Vérifier logging
```

#### Task 3.5: Performance Optimization
```
Identifier:
- Indexes manquants en DB
- N+1 query patterns
- Requêtes lentes (> 100ms)

Optimiser:
- Ajouter indexes
- Cacher résultats courants
- Paginer gros datasets
```

#### Task 3.6: Documentation Finale
```
- API documentation (OpenAPI/Swagger)
- Setup guide pour devs
- Troubleshooting guide
- Deployment checklist
```

### 📊 Résultat Attendu Phase 3
- ✅ 100% tests coverage critiques
- ✅ 0 requêtes > 500ms
- ✅ Rate limiting actif
- ✅ Documentation complète
- ✅ Prêt pour production

---

## 🎯 OBJECTIFS MESURABLES

### Avant (État Actuel)
```
❌ Failles critiques: 5 (P0)
❌ SQL Injections: 11 fichiers
❌ Credentials exposés: 11 fichiers
❌ Validation: 0%
❌ Authentication: Aucune
❌ Tests: Aucuns
❌ Documentation sécurité: Aucune
```

### Après Phase 1 ✅
```
✅ Failles critiques: 1 restante (JWT requis)
✅ SQL Injections: 0
✅ Credentials exposés: 0
✅ Validation: 100%
✅ Authentication: À faire Phase 2
✅ Tests: À faire Phase 3
✅ Documentation sécurité: Complète
```

### Après Phase 3 ✅✅✅
```
✅ Failles P0: 0
✅ Failles P1/P2: Mitigées
✅ Authentication: JWT + RBAC
✅ Authorization: Role-based
✅ Tests: 70%+ coverage
✅ Performance: < 200ms avg
✅ Documentation: Complète
✅ Prêt Production: OUI
```

---

## 📞 ESCALADE

**En cas de blocage:**

| Problème | Action |
|----------|--------|
| Erreur PHP | Vérifier logs: `tail -f backend/logs/$(date +%Y-%m-%d).log` |
| DB query fail | Tester query directement: `mysql -h $DB_HOST ...` |
| JWT invalide | Vérifier secret `echo $JWT_SECRET` |
| Test échoue | Debugger: `php -l file.php` + `phpstan` |
| Performance issue | Profiler: `xdebug` + Blackfire.io |

---

## 📅 CHECKLIST FINALE

**À faire avant passage PRODUCTION:**

- [ ] Phase 1 complétée (tous 9 fichiers refactorisés)
- [ ] Phase 2 complétée (JWT + RBAC)
- [ ] Phase 3 complétée (optimisations)
- [ ] Tous les logs sains (grep ERROR logs/ = rien)
- [ ] Tests passant (phpunit = 0 failures)
- [ ] .env configuré (production-ready)
- [ ] DB backups fonctionnels
- [ ] Monitoring activé
- [ ] Rollback plan écrit
- [ ] Équipe formée aux nouveaux patterns

**Signature des responsables:**
```
Responsable PHP: _______________  Date: ______
Responsable DB:  _______________  Date: ______
Responsable Sec: _______________  Date: ______
```

