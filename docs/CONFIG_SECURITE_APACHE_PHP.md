# Configuration Sécurité - PHP & Apache

**Note:** À adapter selon votre hébergement Ionos

---

## 📁 .htaccess - Security Headers & Configuration

Créer ou mettre à jour `/public_html/.htaccess`:

```apache
# ============================================================================
# SECURITY HEADERS
# ============================================================================

# Prevent MIME sniffing
Header set X-Content-Type-Options "nosniff"

# Prevent clickjacking
Header set X-Frame-Options "SAMEORIGIN"

# Enable XSS protection in older browsers
Header set X-XSS-Protection "1; mode=block"

# Referrer policy
Header set Referrer-Policy "strict-origin-when-cross-origin"

# ============================================================================
# CORS CONFIGURATION (Temporaire - À remplacer par JWT Phase 2)
# ============================================================================

# RESTRICTIF: Only your domain (UPDATE THIS)
Header set Access-Control-Allow-Origin "https://compta.sarlatc.com"
Header set Access-Control-Allow-Credentials "true"
Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
Header set Access-Control-Allow-Headers "Content-Type, Authorization, X-CSRF-Token"

# Preflight caching
Header set Access-Control-Max-Age "86400"

# Handle OPTIONS requests
RewriteEngine On
RewriteCond %{REQUEST_METHOD} OPTIONS
RewriteRule ^(.*)$ - [L]

# ============================================================================
# PERFORMANCE - COMPRESSION & CACHING
# ============================================================================

# Enable GZIP compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE text/javascript
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
    AddOutputFilterByType DEFLATE application/json
</IfModule>

# Browser caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresDefault "access plus 1 week"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
    ExpiresByType application/x-shockwave-flash "access plus 1 month"
    ExpiresByType font/truetype "access plus 1 year"
    ExpiresByType font/opentype "access plus 1 year"
    ExpiresByType application/vnd.ms-fontobject "access plus 1 year"
</IfModule>

# ============================================================================
# REWRITING - PRETTY URLs
# ============================================================================

RewriteEngine On

# Remove .php extension
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([^.]+)$ $1.php [QSA,L]

# Force HTTPS (if SSL configured)
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# ============================================================================
# FILE RESTRICTIONS - Security
# ============================================================================

# Deny access to dangerous files
<FilesMatch "\.env|\.git|\.gitignore|composer.json|phpunit.xml">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order allow,deny
        Deny from all
    </IfModule>
</FilesMatch>

# Deny access to backup files
<FilesMatch "\.(bak|backup|swp|tmp|sqlite|sql)$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order allow,deny
        Deny from all
    </IfModule>
</FilesMatch>

# Disable PHP execution in uploads folder (if exists)
<Directory "*/uploads">
    <FilesMatch "\.php$">
        <IfModule mod_authz_core.c>
            Require all denied
        </IfModule>
    </FilesMatch>
</Directory>

# ============================================================================
# PREVENT COMMON ATTACKS
# ============================================================================

# Disable directory listing
Options -Indexes

# Prevent SQL injection attempts
RewriteCond %{QUERY_STRING} (union|select|insert|update|delete|drop|create|alter|exec|execute|script|javascript|onerror|onclick) [NC]
RewriteRule ^(.*)$ / [L]

# Prevent XSS attempts
RewriteCond %{QUERY_STRING} (<|>|%3C|%3E) [NC]
RewriteRule ^(.*)$ / [L]
```

---

## 🐘 PHP.ini Configuration

**Sur Ionos mutualisé, certains paramètres peuvent être limités.**

Créer `/public_html/.user.ini` ou `/backend/.user.ini`:

```ini
; ============================================================================
; SECURITY SETTINGS
; ============================================================================

; Disable dangerous functions
disable_functions = "exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source,posix_kill,proc_terminate"

; Limit file uploads
upload_max_filesize = 64M
post_max_size = 65M
max_file_uploads = 10

; Session security
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = "Strict"
session.use_only_cookies = 1
session.gc_maxlifetime = 3600

; Error handling
display_errors = 0
display_startup_errors = 0
log_errors = 1
error_log = "/var/www/vhosts/yourdomain.com/backend/logs/php-errors.log"

; Info disclosure
expose_php = 0
url_fopen = 0

; Default charset UTF-8
default_charset = "UTF-8"

; Allow .env override
variables_order = "GPCS"
```

---

## 🔐 Permissions Fichiers & Dossiers

**À configurer via FTP/SSH:**

```bash
# Logs writeable only
chmod 750 backend/logs
chmod 640 backend/logs/*

# Database files readable by PHP only
chmod 640 backend/config/schema.sql

# Source code not executable
chmod 644 backend/**/*.php
chmod 644 public_html/**/*.php

# .env not readable by web
chmod 600 .env

# Services readable
chmod 644 backend/services/*.php
```

---

## 📊 Database Configuration (MySQL)

**Créer l'utilisateur dédié sur Ionos:**

```sql
-- À exécuter une fois
CREATE USER 'dbu2705925'@'localhost' IDENTIFIED BY 'ComplexPassword123!@#';

-- Permissions restrictives
GRANT SELECT, INSERT, UPDATE, DELETE ON dbs15168768.* TO 'dbu2705925'@'localhost';

-- Ne PAS donner: CREATE, ALTER, DROP, GRANT
REVOKE ALL PRIVILEGES ON *.* FROM 'dbu2705925'@'localhost';
REVOKE ALL PRIVILEGES ON mysql.* FROM 'dbu2705925'@'localhost';

-- Pour FEC imports seulement (si besoin)
-- GRANT FILE ON *.* TO 'dbu2705925'@'localhost';

FLUSH PRIVILEGES;
```

---

## 🔍 Vérification Sécurité

**Script bash pour vérifier la configuration:**

```bash
#!/bin/bash
# save as: backend/check-security.sh
# run: bash backend/check-security.sh

echo "🔍 VÉRIFICATION SÉCURITÉ"
echo ""

# 1. Check .env exists and not readable
echo "1. Configuration (.env):"
if [ ! -f .env ]; then
    echo "   ❌ .env NOT FOUND"
else
    echo "   ✅ .env exists"
    perms=$(stat -c %a .env 2>/dev/null || stat -f %OLp .env 2>/dev/null)
    if [ "$perms" = "600" ] || [ "$perms" = "-rw-------" ]; then
        echo "   ✅ Permissions sécurisées (600)"
    else
        echo "   ⚠️  Permissions: $perms (should be 600)"
    fi
fi
echo ""

# 2. Check for hardcoded credentials
echo "2. Credentials (recherche de patterns):"
count=$(grep -r "DB_PASS\|password\|mysql_connect" --include="*.php" backend/ public_html/ 2>/dev/null | grep -v "getenv\|InputValidator\|bootstrap" | wc -l)
if [ $count -eq 0 ]; then
    echo "   ✅ Pas de credentials en dur"
else
    echo "   ❌ TROUVÉ $count occurrences suspectes:"
    grep -r "DB_PASS\|password\|mysql_connect" --include="*.php" backend/ public_html/ 2>/dev/null | grep -v "getenv\|InputValidator\|bootstrap" | head -5
fi
echo ""

# 3. Check for SQL injection patterns
echo "3. SQL Injections (patterns dangereux):"
count=$(grep -r '\$_GET\[.*\].*query\|\$_POST\[.*\].*query' --include="*.php" backend/ public_html/ 2>/dev/null | wc -l)
if [ $count -eq 0 ]; then
    echo "   ✅ Pas de patterns SQL injection évidents"
else
    echo "   ⚠️  TROUVÉ $count patterns suspects"
fi
echo ""

# 4. Check logs directory
echo "4. Logs:"
if [ -d backend/logs ]; then
    echo "   ✅ Dossier logs existe"
    if [ -f "backend/logs/$(date +%Y-%m-%d).log" ]; then
        echo "   ✅ Log du jour actif"
    else
        echo "   ℹ️  Pas de log créé encore"
    fi
else
    echo "   ❌ Dossier logs ABSENT"
fi
echo ""

# 5. Check .htaccess
echo "5. Apache Security (.htaccess):"
if grep -q "X-Content-Type-Options" public_html/.htaccess 2>/dev/null; then
    echo "   ✅ Security headers configurés"
else
    echo "   ⚠️  Security headers MANQUANTS"
fi
echo ""

echo "✅ Vérification complétée"
```

**Exécuter:**
```bash
bash backend/check-security.sh
```

---

## 📝 Logging Configuration

**Dans `backend/logs/.htaccess`:**

```apache
# Deny web access to logs
<FilesMatch "\.log$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
</FilesMatch>
```

---

## 🚀 Deploiement Checklist

```
AVANT DE PASSER EN PRODUCTION
================================

Sécurité:
[ ] .env configuré avec vrais secrets
[ ] APP_ENV = production
[ ] display_errors = 0 (no debug output)
[ ] Logs activés et accessible
[ ] Permissions fichiers correctes (600 .env, 755 dossiers)
[ ] Tous les *-simple.php refactorisés

Performance:
[ ] Compression GZIP activée (.htaccess)
[ ] Cache HTTP headers configurés
[ ] DB indexes présents (EXPLAIN queries)
[ ] Pas de N+1 queries

Monitoring:
[ ] Health endpoint testé (/api/health)
[ ] Logs consultables et archivés
[ ] Alertes email configurées
[ ] Monitoring CPU/Memory activé

Base de données:
[ ] Backup automatique configuré
[ ] Restore test successful
[ ] User DB avec permissions minimales
[ ] Tables optimisées (ANALYZE TABLE)

Frontend:
[ ] CORS configuré (domaine spécifique)
[ ] CSP headers configurés (Phase 3)
[ ] Assets minifiés
[ ] Service worker / caching

Tests:
[ ] Endpoint santé donne 200
[ ] FEC test import réussi
[ ] Auth flow complet
[ ] Logging des opérations vérifiée

Documentation:
[ ] README.md à jour
[ ] Ops know how to restore
[ ] Contacts support clarifés
[ ] Rollback plan écrit
```

---

## 🆘 Troubleshooting

| Problème | Cause | Solution |
|----------|-------|----------|
| 500 Internal Error | PHP syntax error | `php -l backend/file.php` |
| DB Connection Failed | Credentials wrong | Vérifier .env, `mysql -h $DB_HOST ...` |
| 403 Forbidden | Permissions | `chmod 755 backend/logs` |
| CORS fails | Header wrong | Vérifier `.htaccess` CORS_ORIGIN |
| Slow queries | Missing index | `EXPLAIN SELECT...`, ajouter INDEX |
| Logs not written | Directory not writable | `chmod 750 backend/logs` |
| .env not loaded | Path wrong | Vérifier `APP_ROOT` dans bootstrap.php |

---

## 📞 Contacts Ionos Support

- **Hosting Panel:** ionos.fr/admin
- **Phone:** +33 9 70 80 89 89
- **Email:** support-en@ionos.com
- **Knowledge Base:** ionos.com/help

**Demander à Ionos:**
- SSL certificate (Let's Encrypt gratuit)
- Enable mod_rewrite (.htaccess)
- Augmenter upload_max_filesize si besoin
- Activer monitoring/alertes

