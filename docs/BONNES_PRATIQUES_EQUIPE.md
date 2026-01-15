# 👨‍💼 RECOMMANDATIONS POUR L'ÉQUIPE - BONNES PRATIQUES

**Version:** 1.0  
**Audience:** Développeurs PHP/React  
**Date:** 15/01/2026

---

## 🎯 PRINCIPES FONDAMENTAUX

### 1. Sécurité en Priorité

✅ **À FAIRE:**
- Tous les `$_GET`, `$_POST`, `$_REQUEST` → `InputValidator::as*()` 
- Toutes les requêtes SQL → Paramètres liés avec `?`
- Toutes les erreurs → Logger + message générique au client

```php
// ✅ BON
try {
    $exercice = InputValidator::asYear($_GET['exercice'] ?? 2024);
    $result = $db->fetchAll(
        "SELECT * FROM table WHERE exercice = ?",
        [$exercice]
    );
} catch (InvalidArgumentException $e) {
    Logger::error("Validation failed", ['error' => $e->getMessage()]);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameter']);
}
```

❌ **À ÉVITER:**
```php
// ❌ DANGEREUX
$exercice = $_GET['exercice'] ?? 2024;
$db->query("SELECT * FROM table WHERE exercice = $exercice");
die(json_encode(['error' => $e->getMessage()]));  // Info leak!
```

---

### 2. Utiliser les Services Centralisés

✅ **Architecture Recommandée:**

```
RequestValidator (InputValidator)
        ↓
BusinessLogic (Service classes)
        ↓
DataAccess (Database queries)
        ↓
Logger (Audit trail)
```

✅ **Pattern à Suivre:**

```php
<?php
require_once dirname(dirname(__FILE__)) . '/backend/bootstrap.php';

use App\Config\Database;
use App\Config\InputValidator;
use App\Config\Logger;

header('Content-Type: application/json');

try {
    // 1. Validation
    $param = InputValidator::asType($_GET['param'] ?? default);
    
    // 2. Business Logic (mettre dans Service si complexe)
    $db = Database::getInstance();
    $result = $db->fetchAll(
        "SELECT * FROM table WHERE column = ?",
        [$param]
    );
    
    // 3. Logging
    Logger::info("Operation succeeded", ['rows' => count($result)]);
    
    // 4. Response
    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $result]);
    
} catch (Exception $e) {
    Logger::error("Operation failed", ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['error' => 'Service unavailable']);
}
?>
```

---

### 3. DRY - Don't Repeat Yourself

✅ **À FAIRE:**
- Créer des Services pour la logique métier répétée
- Utiliser l'héritage/traits pour patterns communs
- Centraliser les configurations

❌ **À ÉVITER:**
```php
// ❌ 11 fichiers identiques
$dbConfig = [
    'host' => 'db5019387279.hosting-data.io',
    ...
];
```

✅ **À LA PLACE:**
```php
// ✅ Une fois, dans bootstrap.php
getenv('DB_HOST');  // Chaque fichier peut l'utiliser
```

---

### 4. Code Structure - SOLID Principles

**S**ingle Responsibility Principle:
- 1 classe = 1 responsabilité
- `FecAnalyzer` = analyse FEC uniquement
- `InputValidator` = validation uniquement

**O**pen/Closed:
- Classes ouvertes à l'extension
- Fermées à la modification
- Utiliser des interfaces pour les contrats

**L**iskov Substitution:
- Les sous-classes doivent pouvoir remplacer les parents
- Ne pas changer le contrat de la méthode

**I**nterface Segregation:
- Interfaces spécifiques > Interfaces génériques
- Implémenter seulement ce qui est nécessaire

**D**ependency Injection:
- Injecter les dépendances en constructeur
- Pas de `new` en dur dans la classe

```php
// ❌ MAUVAIS
class ReportGenerator {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();  // Tight coupling
    }
}

// ✅ BON
class ReportGenerator {
    private $db;
    
    public function __construct(Database $db) {  // Dependency injection
        $this->db = $db;
    }
}
```

---

## 📝 CHECKLIST AVANT DE COMMITTER

### Sécurité
- [ ] Aucun secret en dur (credentials, API keys)
- [ ] Tous les inputs validés
- [ ] Toutes les requêtes SQL paramétrées
- [ ] Aucune exposition d'erreur (production)
- [ ] Logging des opérations sensibles

### Qualité
- [ ] Code lisible (noms explicites)
- [ ] Pas de duplication
- [ ] Fonctions < 20 lignes
- [ ] Commentaires pour la logique complexe
- [ ] Tests unitaires écrits

### Performance
- [ ] Pas de N+1 queries
- [ ] Indexes DB appropriés
- [ ] Pagination pour gros datasets
- [ ] Compression GZIP activée

### Documentation
- [ ] Doc des paramètres de fonction
- [ ] Exemples d'utilisation
- [ ] Contrats d'interface clairs

---

## 🧪 TESTING

### Unitaires (PHPUnit)

```php
<?php
namespace Tests;

use App\Services\FecAnalyzer;
use PHPUnit\Framework\TestCase;

class FecAnalyzerTest extends TestCase {
    public function testAnalyzeFecFormat() {
        $analyzer = new FecAnalyzer();
        $result = $analyzer->analyze('sample.txt');
        
        $this->assertTrue($result['is_balanced']);
        $this->assertEquals(18, count($result['columns']));
    }
}
```

### Intégration (API Testing)

```bash
# Test endpoint
curl -X POST http://localhost:8000/api/analyze/fec \
  -F "file=@test.txt" \
  -H "Content-Type: multipart/form-data"

# Vérifier réponse
jq '.success' response.json
```

---

## 🔄 WORKFLOW GIT

```bash
# 1. Créer branche feature
git checkout -b feature/nom-feature

# 2. Faire les changements
# ... code modifications ...

# 3. Tester
npm test          # Frontend
vendor/bin/phpunit  # Backend

# 4. Vérifier sécurité
grep -r "mysql_" .           # ❌ Fonctions dépréciées
grep -r "\$_GET\[" .         # Validation requise
grep -r "eval\|exec" .       # 🚨 JAMAIS

# 5. Commit avec message explicite
git commit -m "feat: Add FEC validation

- Implement FecAnalyzer service
- Add balance verification
- Support TAB and pipe separators

Fixes #123"

# 6. Push + Pull Request
git push origin feature/nom-feature
# → Créer PR sur GitHub/GitLab
```

---

## 📊 LOGGING

### Quand Logger?

✅ **Toujours:**
- Opérations sensibles (import, deletion)
- Erreurs
- Changements de state importants
- Accès API externe

```php
Logger::info("FEC imported successfully", [
    'file' => 'fec_2024.txt',
    'rows' => 11617,
    'exercice' => 2024,
    'user' => $_SESSION['user_id'] ?? 'anonymous'
]);
```

### Format des Logs

```
[2026-01-15 08:23:06] [ERROR] Import FEC failed | {"file":"test.txt","error":"Invalid format","user_id":123}
```

Lisible + Parseable (JSON) + Contextualisé

---

## 🚀 DÉPLOIEMENT

### Pré-Déploiement Checklist

```bash
# 1. Sécurité
[ ] .env configuré correctement
[ ] APP_ENV=production
[ ] JWT_SECRET défini (long + aléatoire)
[ ] .gitignore inclut .env

# 2. Performance
[ ] Compression GZIP activée (.htaccess)
[ ] Cache headers correctement
[ ] DB indexes présents

# 3. Monitoring
[ ] Logs configurés
[ ] Error reporting activé
[ ] Health endpoint fonctionnel

# 4. Tests
[ ] Tests unitaires passants
[ ] Tests intégration passants
[ ] Tests sécurité passants
```

### Après Déploiement

```bash
# Vérifier santé
curl https://yourdomain.com/api/health

# Vérifier logs pour erreurs
tail -100 backend/logs/$(date +%Y-%m-%d).log

# Vérifier DB accessible
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS -e "SELECT 1;"
```

---

## 📚 RESSOURCES

### Documentation
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)
- [PSR Standards](https://www.php-fig.org/psr/)

### Outils
- **PHPStan:** Analyse statique PHP
- **PHP_CodeSniffer:** Vérification style
- **Composer:** Gestion dépendances
- **PHPUnit:** Tests unitaires

### Commandes Utiles

```bash
# Vérifier style du code
phpcs --standard=PSR12 backend/

# Analyse statique
phpstan analyse backend/

# Tests
phpunit --configuration phpunit.xml

# Sécurité (find issues)
grep -r "mysql_\|\$_GET\[\|eval(" --include="*.php" .
```

---

## ❓ FAQ

**Q: Puis-je utiliser `eval()` ou `exec()`?**  
A: ❌ JAMAIS. C'est extrêmement dangereux. Utiliser des patterns sûrs à la place.

**Q: Comment gérer les montants en EUR avec centimes?**  
A: Utiliser `DECIMAL(15,2)` en BD et `InputValidator::asDecimal()`. Format français accepté (100,50).

**Q: Qui peut accéder à l'API?**  
A: Actuellement TOUS. À implémenter: JWT authentication en phase 2.

**Q: Comment tester avec des FEC réels?**  
A: Utiliser `fec_2024_atc.txt` fourni. Test complet = 11,617 lignes, équilibre garanti.

**Q: Puis-je utiliser des caractères spéciaux en français?**  
A: ✅ OUI. UTF-8 activé partout (database + PHP + .htaccess).

---

## 💬 QUESTIONS?

Consulter les fichiers:
- [AUDIT_SECURITE.md](../AUDIT_SECURITE.md) - Risques détaillés
- [CORRECTIONS_SECURITE_APPLIQUEES.md](../CORRECTIONS_SECURITE_APPLIQUEES.md) - Fixes appliquées
- [README.md](../README.md) - Documentation générale

