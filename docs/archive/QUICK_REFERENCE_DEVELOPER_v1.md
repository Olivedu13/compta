# 🎯 QUICK REFERENCE CARD - Checklist Développeur

**Imprimer ou mettre sur le bureau - Pour chaque commit!**

---

## ✅ AVANT CHAQUE COMMIT

### 1️⃣ Sécurité (2 min)

```
□ Pas de $_GET/POST sans InputValidator::as*()
□ Pas de string interpolation en SQL → Paramètres liés avec ?
□ Pas de secrets en dur (API keys, DB pass, JWT secret)
□ Pas de eval() / exec() / system() / passthru()
□ Pas de die() / var_dump() en production
```

**Quick Check:**
```bash
# Chercher patterns dangereux
grep -n "eval\|exec\|system\|passthru" *.php     # Doit être VIDE
grep -n "\$_GET\[\|\$_POST\[" *.php               # Tous avec InputValidator
grep -n "mysql_\|mysqli_" *.php                   # Doit être VIDE (old PHP)
grep -n "hardcode\|password =\|API_KEY =" *.php  # Doit être VIDE
```

### 2️⃣ Code Quality (3 min)

```
□ Fonction < 20 lignes
□ Noms explicites (pas $x, $tmp, $result)
□ Pas de duplication (DRY principle)
□ Indentation 4 espaces (PSR-12)
□ Commentaires pour logique complexe UNIQUEMENT
□ Pas de `?>` à la fin du fichier PHP
```

### 3️⃣ Logging (2 min)

```
□ Opérations sensibles loggées (import, delete, etc.)
□ Erreurs catchées avec Logger::error()
□ Contexte complet dans logs (user ID, params, résultat)
□ Pas de sensibles data en logs (passwords, tokens)
```

### 4️⃣ Testing (1 min)

```
□ Code testé localement AVANT commit
□ Pas de "will test later"
□ Si ajout feature → Ajouter test
□ Tests passent: vendor/bin/phpunit
```

---

## 🚀 PATTERNS À UTILISER

### Pattern 1: Endpoint API Sécurisé

```php
<?php
require_once dirname(dirname(__FILE__)) . '/backend/bootstrap.php';

use App\Config\Database;
use App\Config\InputValidator;
use App\Config\Logger;

header('Content-Type: application/json; charset=utf-8');

try {
    // VALIDATION
    try {
        $year = InputValidator::asYear($_GET['year'] ?? 2024);
        $page = InputValidator::asPage($_GET['page'] ?? 1);
    } catch (InvalidArgumentException $e) {
        http_response_code(400);
        throw new Exception("Invalid input: " . $e->getMessage());
    }
    
    // BUSINESS LOGIC
    $db = Database::getInstance();
    $data = $db->fetchAll(
        "SELECT * FROM table WHERE year = ? ORDER BY id LIMIT ? OFFSET ?",
        [$year, 10, ($page - 1) * 10]
    );
    
    // LOGGING
    Logger::info("Data retrieved", ['year' => $year, 'rows' => count($data)]);
    
    // RESPONSE
    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $data, 'count' => count($data)]);
    
} catch (Exception $e) {
    Logger::error("Request failed", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    
    $message = getenv('APP_ENV') === 'production' 
        ? 'Service unavailable' 
        : $e->getMessage();
    
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $message]);
}
?>
```

### Pattern 2: Validation Input

```php
// ✅ TOUJOURS FAIRE ÇA:

try {
    $exercice = InputValidator::asYear($_GET['exercice'] ?? 2024);
    $account = InputValidator::asAccountNumber($_GET['account'] ?? '');
    $amount = InputValidator::asDecimal($_GET['amount'] ?? '0');
    
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameter: ' . $e->getMessage()]);
    exit;
}

// ❌ JAMAIS FAIRE ÇA:
$exercice = $_GET['exercice'] ?? 2024;  // No validation!
$account = $_GET['account'];             // Could be anything!
```

### Pattern 3: Requête Paramètrée

```php
// ✅ BON - Paramètres liés
$result = $db->fetchAll(
    "SELECT * FROM fec_lines WHERE exercice = ? AND account_num = ? ORDER BY date",
    [$year, $account]
);

// ❌ MAUVAIS - String interpolation (SQL injection!)
$result = $db->fetchAll(
    "SELECT * FROM fec_lines WHERE exercice = $year AND account_num = '$account'"
);

// ❌ MAUVAIS - Weak escaping
$result = $db->fetchAll(
    "SELECT * FROM fec_lines WHERE exercice = " . addslashes($year)
);
```

### Pattern 4: Error Handling

```php
// ✅ BON - Conditional messages
try {
    // code...
} catch (Exception $e) {
    Logger::error("Error context", [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'error' => $e->getMessage()
    ]);
    
    $errorMsg = getenv('APP_ENV') === 'production' 
        ? 'Service temporarily unavailable' 
        : $e->getMessage();
    
    http_response_code(500);
    echo json_encode(['error' => $errorMsg]);
}

// ❌ MAUVAIS - Expose details in production
die('Error: ' . $e->getMessage());  // Customer sees everything!
var_dump($result);                   // Reveals structure!
print_r($db->lastError);             // Shows table names!
```

---

## 📋 INPUT VALIDATORS - Tous disponibles

| Validator | Usage | Returns | Throws |
|-----------|-------|---------|--------|
| `asInt(v, min, max)` | Integer validation | int | InvalidArgumentException |
| `asYear(v)` | Year 1900-2100 | int | InvalidArgumentException |
| `asPage(v)` | Pagination (≥1) | int | InvalidArgumentException |
| `asLimit(v, max)` | Limit per page | int | InvalidArgumentException |
| `asAccountNumber(v)` | PCG format (1-12) | string | InvalidArgumentException |
| `asJournalCode(v)` | Journal code | string | InvalidArgumentException |
| `asDate(v)` | ISO 8601 format | string | InvalidArgumentException |
| `asEmail(v)` | RFC email | string | InvalidArgumentException |
| `asDecimal(v, min, max)` | Currency, supports 100,50 | float | InvalidArgumentException |
| `validateMimeType(actual, allowed)` | MIME type check | bool | InvalidArgumentException |
| `validateFileSize(size, max)` | File size check | bool | InvalidArgumentException |

**Exemples:**
```php
$year = InputValidator::asYear($input);
// → 2024 (si valide)
// → Exception si < 1900 ou > 2100

$limit = InputValidator::asLimit($_GET['limit'] ?? 25, 500);
// → 25 (default)
// → $_GET['limit'] (si 1-500)
// → 500 (si > 500)
// → Exception si négatif ou string
```

---

## 🔐 SECRETS - À JAMAIS EN DUR

❌ Ne jamais mettre en dur:
```php
$password = 'Atc13001!74529012!';           // NO!
$api_key = 'sk_live_abc123xyz';            // NO!
$jwt_secret = 'my_secret_key_12345';       // NO!
```

✅ TOUJOURS utiliser environment:
```php
$password = getenv('DB_PASS');             // .env
$api_key = getenv('EXTERNAL_API_KEY');     // .env
$jwt_secret = getenv('JWT_SECRET');        // .env

// Si var manquante → fallback sécurisé
$pass = getenv('DB_PASS') ?: 'default_insecure_only_for_local_dev';
```

---

## 📊 LOGGING - Quand & Comment

### Toujours Logger:
```php
// 1. Opérations sensibles
Logger::info("FEC file imported", ['file' => $name, 'rows' => 1000, 'user_id' => $uid]);

// 2. Erreurs
Logger::error("Import failed", ['error' => $e->getMessage(), 'user_id' => $uid]);

// 3. Security events
Logger::warning("Invalid login attempt", ['ip' => $_SERVER['REMOTE_ADDR'], 'user' => $username]);

// 4. Accès resources
Logger::info("Report generated", ['user_id' => $uid, 'report_type' => 'balance', 'exercice' => 2024]);
```

### Ne JAMAIS Logger:
```php
// ❌ Secrets
Logger::info("DB connected", ['pass' => $password]);

// ❌ Sensibles data
Logger::info("User data", ['ssn' => $customer_ssn, 'email' => $email]);

// ❌ Infos de debug non-pertinentes
Logger::info("Entering function", ['var_dump' => var_export($everything, true)]);
```

---

## 🧪 TESTING - Quick Commands

```bash
# Syntax check
php -l backend/config/Database.php

# Static analysis
phpstan analyse backend/

# Code style
phpcs --standard=PSR12 backend/

# Unit tests
phpunit tests/

# Run specific test
phpunit tests/InputValidatorTest.php

# With coverage
phpunit --coverage-html coverage/ tests/
```

---

## 🐛 DEBUGGING - Outils

```bash
# View today's log
tail -100 backend/logs/$(date +%Y-%m-%d).log

# Watch log in real-time
tail -f backend/logs/$(date +%Y-%m-%d).log

# Search error
grep ERROR backend/logs/*.log

# Count errors per day
wc -l backend/logs/*.log | sort -rn

# Extract specific error
grep "SQL error" backend/logs/2024-01-15.log | head -10

# Pretty-print JSON logs
cat backend/logs/2024-01-15.log | jq '.'
```

---

## 📝 GIT COMMIT WORKFLOW

```bash
# 1. Check your changes
git status
git diff backend/file.php

# 2. Run security check
bash backend/check-security.sh

# 3. Run tests
vendor/bin/phpunit tests/

# 4. Add changes
git add backend/file.php
git add tests/file_test.php

# 5. Commit with clear message
git commit -m "feat: Add FEC import validation

- Implement InputValidator
- Add MIME type checking
- Add file size limits
- Update simple-import.php

Fixes #45"

# 6. Push & create PR
git push origin feature/fec-validation
# → Open PR on GitHub
```

---

## 🆘 COMMON MISTAKES

| ❌ Mistake | ✅ Fix | Category |
|-----------|--------|----------|
| `$_GET['id']` direct en SQL | `InputValidator::asInt($_GET['id'] ?? 0)` | Security |
| `die()` avec erreur complète | `json_encode(['error' => 'Service unavailable'])` | Security |
| Fonction 100 lignes | Scinder en petites fonctions | Quality |
| Variable `$x`, `$tmp` | Noms explicites `$accountNumber` | Quality |
| Pas de tests | Ajouter test avant commit | Testing |
| Credentials en .php | Utiliser .env + getenv() | Security |
| `mysql_*` functions | Utiliser PDO via Database class | Architecture |
| Pas de logging | Logger opérations sensibles | Operations |
| N+1 queries en boucle | Une requête avec JOIN | Performance |
| `LIMIT` directement | `InputValidator::asLimit()` | Security |

---

## 🎓 RESOURCES

- [PHP Security](https://www.php.net/manual/en/security.php)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PSR Standards](https://www.php-fig.org/psr/)
- Project docs: [BONNES_PRATIQUES_EQUIPE.md](./BONNES_PRATIQUES_EQUIPE.md)

---

## 🔗 FILES REFERENCE

| Document | Purpose |
|----------|---------|
| [bootstrap.php](./backend/bootstrap.php) | Central initialization |
| [InputValidator.php](./backend/config/InputValidator.php) | Validation utilities |
| [Database.php](./backend/config/Database.php) | DB singleton |
| [Logger.php](./backend/config/Logger.php) | Logging service |
| [balance-simple.php](./public_html/balance-simple.php) | Secure endpoint template |
| [.env](./.env) | Environment configuration |
| [.htaccess](./public_html/.htaccess) | Apache security config |

---

**Dernière mise à jour:** 15/01/2026  
**Pour questions:** Voir BONNES_PRATIQUES_EQUIPE.md

