# 🔒 AUDIT SÉCURITÉ & QUALITÉ - COMPTA BIJOUTERIE

**Date:** 15/01/2026  
**Scope:** Backend PHP + Frontend React  
**Statut:** 🔴 **CRITIQUE** - Corrections urgentes requises

---

## 🚨 FAILLES CRITIQUES (P0 - À TRAITER IMMÉDIATEMENT)

### 1. **Credentials BD Exposés en Dur** ⚠️ GRAVE
**Fichiers affectés:**
- `backend/config/Database.php` (lignes 11-14)
- `public_html/balance-simple.php` (lignes 8-13)
- `public_html/sig-simple.php` (lignes 8-13)
- `public_html/kpis-simple.php` (lignes 8-13)
- `public_html/kpis-detailed.php` (lignes 8-13)
- `public_html/analyse-simple.php` (lignes 8-13)
- `public_html/analytics-advanced.php` (lignes 8-13)
- `public_html/comptes-simple.php` (lignes 8-13)
- `public_html/annees-simple.php` (lignes 8-13)
- `public_html/debug-clients.php` (lignes 8-13)
- `public_html/debug-all-clients.php` (lignes 8-13)

**Risque:** 
- Accès BD non autorisé si code source fuité
- Compromission de données comptables sensibles
- Violation RGPD

**Sévérité:** 🔴 CRITIQUE  
**Solution:** Utiliser variables d'environnement

```php
// ❌ DANGEREUX
private $password = 'Atc13001!74529012!';

// ✅ SÉCURISÉ
private $password = $_ENV['DB_PASS'] ?? getenv('DB_PASS');
```

---

### 2. **Injections SQL** ⚠️ HAUTE
**Fichier:** `public_html/balance-simple.php` (ligne 26-31)

```php
// ❌ DANGEREUX - Injection SQL directe
$result = $db->query("SELECT COUNT(*) as count FROM fin_balance WHERE exercice = $exercice")->fetch();

$balances = $db->query("
    ...WHERE b.exercice = $exercice
    LIMIT $limit OFFSET $offset
")->fetchAll(PDO::FETCH_ASSOC);
```

**Attaque possible:**
```
GET /balance-simple.php?exercice=2024 OR 1=1;--
GET /balance-simple.php?limit=100; DROP TABLE fin_balance;--
```

**Sévérité:** 🔴 CRITIQUE  
**Solution:** Paramètres liés (prepared statements)

---

### 3. **Pas de Validation/Sanitization des Input** ⚠️ HAUTE
**Fichiers affectés:** Tous les fichiers PHP publics

```php
// ❌ DANGEREUX
$exercice = $_GET['exercice'] ?? 2024;
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 100;

// ✅ SÉCURISÉ
$exercice = (int) ($_GET['exercice'] ?? 2024);  // Strict typing
$page = max(1, (int) ($_GET['page'] ?? 1));      // Min value
$limit = min(1000, max(1, (int) ($_GET['limit'] ?? 100)));  // Range
```

**Risque:** Injection SQL, XSS, négation de service

---

### 4. **Pas de Contrôle d'Accès (Authentication/Authorization)** ⚠️ HAUTE
**Fichiers:** TOUS les fichiers API

Actuellement :
- ❌ Aucune authentification
- ❌ Aucune autorisation
- ❌ Aucun rate limiting
- ❌ API ouverte à tous

**Sévérité:** 🔴 CRITIQUE  
**Solution:** Implémenter JWT + middleware auth

---

### 5. **Erreurs Détaillées Exposées au Client** ⚠️ MOYENNE
**Fichier:** `backend/config/Database.php` (ligne 35)

```php
// ❌ DANGEREUX - Expose structure DB à l'attaquant
die(json_encode(['error' => 'Connexion DB échouée: ' . $e->getMessage()]));

// ✅ SÉCURISÉ
Logger::error("DB Error", ['error' => $e->getMessage()]);
die(json_encode(['error' => 'Service indisponible']));
```

---

### 6. **Pas de CSRF Protection** ⚠️ MOYENNE
**POST endpoints:** `/api/analyze/fec`, `/api/import/*`

```php
// ❌ DANGEREUX - Pas de vérification CSRF token
$router->post('/analyze/fec', function() {
    // Accepte les POST de n'importe où
});

// ✅ SÉCURISÉ
if (!hash_equals($_POST['csrf_token'] ?? '', $_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    return json_encode(['error' => 'CSRF token invalid']);
}
```

---

### 7. **File Upload Pas Validé** ⚠️ HAUTE
**Fichier:** `public_html/api/simple-import.php` (ligne 26-34)

```php
// ❌ DANGEREUX - Pas de validation type MIME
$file = $_FILES['file'];
$tmpFile = $file['tmp_name'];  // Peut être n'importe quoi

// ✅ SÉCURISÉ
$allowed_mimes = ['text/plain', 'application/vnd.ms-excel', 'application/gzip'];
if (!in_array($_FILES['file']['type'], $allowed_mimes)) {
    throw new Exception("File type not allowed");
}

// Valider par contenu, pas juste extension
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $tmpFile);
if (!in_array($mime, $allowed_mimes)) {
    throw new Exception("Invalid file content");
}
```

---

### 8. **Pas de Rate Limiting** ⚠️ MOYENNE
**Risque:**
- Brute force sur API
- DDoS
- Extraction de données

---

### 9. **CORS Trop Permissif** ⚠️ MOYENNE
**Fichier:** `public_html/.htaccess` (ligne 12)

```apache
# ❌ DANGEREUX
Header set Access-Control-Allow-Origin "*"

# ✅ SÉCURISÉ
Header set Access-Control-Allow-Origin "https://monsite.fr"
Header set Access-Control-Allow-Credentials "true"
```

---

### 10. **Pas de Content Security Policy** ⚠️ MOYENNE
**Risque:** XSS, injection malveillante

```apache
# ✅ À AJOUTER
Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'"
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
```

---

## 🎯 VIOLATIONS DE PRINCIPES (PATTERNS & CODE QUALITY)

### 1. **Violation DRY (Don't Repeat Yourself)**

**Problème:** Credentials dupliquées dans 11 fichiers

```
Database.php, balance-simple.php, sig-simple.php, kpis-simple.php, 
kpis-detailed.php, analyse-simple.php, analytics-advanced.php, 
comptes-simple.php, annees-simple.php, debug-clients.php, debug-all-clients.php
```

**Impact:** 
- Maintenance difficile
- Risque d'incohérence
- Faille de sécurité amplifiée

**Solution:** ✅ Fait - bootstrap.php + Database::getInstance()

---

### 2. **Violation SOLID - Single Responsibility Principle**

**Fichier:** `public_html/api/index.php` (706 lignes)

```
Router, Database access, Business logic, Error handling - TOUT dans un fichier
```

**Solution proposée:**
- `ApiController.php` pour les endpoints
- `BalanceService.php` pour logique métier
- Séparation concerns

---

### 3. **Violation SOLID - Open/Closed Principle**

**Problème:** Impossible d'étendre sans modifier

Fichiers `*-simple.php` sont dupliqués et modificateurs

**Solution:** Controller pattern avec routes dynamiques

---

### 4. **Absence de Tests Unitaires**

- ❌ Pas de tests PHPUnit
- ❌ Pas de tests Jest pour React
- ❌ Pas de tests d'intégration

**Impact:** Régression non détectées, refactoring impossible

---

### 5. **Gestion d'Erreurs Incohérente**

```php
// ❌ Parfois die(), parfois http_response_code(), parfois json_encode()
die(json_encode(['error' => ...]));  // tue le script
http_response_code(500);              // Retourne code HTTP
echo json_encode(...);                // Retourne JSON

// ✅ Pattern consistent:
try {
    // ...
} catch (Exception $e) {
    Logger::error("Context", ['error' => $e->getMessage()]);
    http_response_code($e->getCode() ?: 500);
    return json_encode(['error' => 'Service unavailable']);
}
```

---

## 📊 AUDIT DE QUALITÉ

### Code Smell 1: Credentials en Dur
- **Ligne 0:** Database.php:11-14
- **Impact:** Faille critique
- **Fix:** 2h (variables env)

### Code Smell 2: Injection SQL
- **Ligne 1:** balance-simple.php:26-31
- **Ligne 2:** sig-simple.php:45-60
- **Ligne 3:** 8 autres fichiers *-simple.php
- **Impact:** Critique
- **Fix:** 4h (paramètres liés)

### Code Smell 3: Pas de Validation Input
- **Ligne 0:** Tous les `$_GET['param']` sans cast
- **Impact:** Haute
- **Fix:** 3h

### Code Smell 4: Duplication de Code
- **11 fichiers*-simple.php** quasi-identiques
- **Impact:** Maintenance impossible
- **Fix:** 6h (refactor vers Controller)

---

## ⚡ PERFORMANCE

### Issue 1: N+1 Queries

**Fichier:** `public_html/api/index.php` (ligne 140)

```php
// ❌ Si 1000 comptes, 1000 requêtes!
$balances = $db->fetchAll("SELECT * FROM fin_balance WHERE exercice = ?", [$exercice]);
foreach ($balances as $b) {
    $plan = $db->fetchOne("SELECT libelle FROM sys_plan_comptable WHERE compte_num = ?", [$b['compte_num']]);
    // ...
}

// ✅ Une seule requête
$balances = $db->fetchAll("
    SELECT b.*, p.libelle
    FROM fin_balance b
    LEFT JOIN sys_plan_comptable p ON b.compte_num = p.compte_num
    WHERE b.exercice = ?
", [$exercice]);
```

---

### Issue 2: Pas d'Indexation Appropriée

**Schema:** `backend/config/schema.sql`

```sql
-- ❌ Pas d'index sur colonnes fréquemment searchées
CREATE TABLE fin_balance (
    ...
    exercice YEAR,
    compte_num VARCHAR(12),
    ...
);

-- ✅ À AJOUTER
CREATE INDEX idx_balance_exercice_compte ON fin_balance(exercice, compte_num);
CREATE INDEX idx_ecritures_compte_date ON fin_ecritures_fec(compte_num, ecriture_date);
```

---

### Issue 3: Pas de Pagination

**Fichier:** `public_html/analytics-advanced.php`

```php
// ❌ Charge TOUTES les lignes
$result = $db->query("SELECT * FROM fin_ecritures_fec WHERE exercice = 2024");
$data = $result->fetchAll();  // Potentiellement 100K+ lignes en mémoire!

// ✅ Pagination
$limit = 100;
$offset = 0;
$result = $db->query("SELECT * FROM fin_ecritures_fec WHERE exercice = ? LIMIT ? OFFSET ?", [$exercice, $limit, $offset]);
```

---

## 📋 RÉSUMÉ DES RISQUES CRITIQUES

| Risque | Sévérité | Impact | Effort Fix |
|--------|----------|--------|-----------|
| **Credentials en dur** | 🔴 P0 | BD compromise | 2h |
| **Injections SQL** | 🔴 P0 | Extraction données | 4h |
| **Pas d'auth** | 🔴 P0 | Accès libre API | 6h |
| **File upload non validé** | 🔴 P0 | RCE possible | 2h |
| **Pas de validation input** | 🟠 P1 | Injection/XSS | 3h |
| **Errors exposées** | 🟠 P1 | Info leak | 1h |
| **Pas de CSRF** | 🟠 P1 | Attaques CSRF | 2h |
| **CORS trop permissif** | 🟡 P2 | XSS amplifiée | 30min |
| **Pas de CSP** | 🟡 P2 | XSS | 30min |
| **N+1 Queries** | 🟡 P2 | Performance | 2h |
| **Pas de tests** | 🟡 P2 | Régression | 20h |

---

## ✅ PLAN D'ACTION

### PHASE 1 (Urgence 24h)
1. [P0] Credentials → env variables
2. [P0] SQL Injection → prepared statements  
3. [P0] Input validation → strict casting
4. [P0] File upload validation → MIME check

### PHASE 2 (48h)
1. [P0] Authentication → JWT middleware
2. [P0] Authorization → role-based access
3. [P1] Error handling standardisé
4. [P1] CSRF tokens

### PHASE 3 (1 semaine)
1. [P1] Security headers (CSP, CORS, etc.)
2. [P2] Refactor *-simple.php → Controllers
3. [P2] Tests unitaires PHPUnit
4. [P2] DB indexes + query optimization

---

## 📝 NOTES

- **Bon:** bootstrap.php, FecAnalyzer bien structuré, paramètres liés dans API moderne
- **À améliorer:** Fichiers *-simple.php ne devraient PAS exister
- **Recommandation:** Utiliser uniquement API moderne (/api/index.php) en production

