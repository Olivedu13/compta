# 🚀 PRE-PRODUCTION CHECKLIST

**À compléter avant chaque déploiement en production**

---

## SECTION 1️⃣: SÉCURITÉ CRITIQUE

**Responsable:** Responsable Sécurité  
**Temps estimé:** 30 minutes

### Secrets & Configuration
- [ ] `.env` configuré avec VRAIS secrets de production
- [ ] `APP_ENV=production` (pas development!)
- [ ] `JWT_SECRET` long et aléatoire (min 32 chars): `$(openssl rand -hex 32)`
- [ ] `DB_PASS` complexe (mix uppercase, lowercase, numbers, symbols)
- [ ] `.env` NOT in git: `cat .gitignore | grep "\.env"`
- [ ] `.env` permissions restrictives: `ls -la .env` → `-rw-------`

**Vérification:**
```bash
# Vérifier .env NOT accessible from web
curl https://yourdomain.com/.env  # Should be 404/403

# Vérifier variables chargées
php -r "require 'backend/bootstrap.php'; echo getenv('APP_ENV');"
# Doit afficher: production
```

### Codes Sources
- [ ] Aucun `eval()`, `exec()`, `system()`, `passthru()`
- [ ] Aucun hardcoded credentials (grep entire codebase)
- [ ] Aucun `var_dump()`, `print_r()`, `die()` en production
- [ ] Aucun debug code (search TODO, FIXME, HACK comments)

**Vérification:**
```bash
git grep -i "TODO\|FIXME\|DEBUG\|HACK" --exclude-dir=vendor --exclude-dir=node_modules
# Should be empty or only in comments

git grep -E "eval\(|exec\(|system\(|mysql_" --exclude-dir=vendor
# Should be completely empty
```

### API & Données
- [ ] Tous les endpoints implémentent InputValidator
- [ ] Toutes les requêtes SQL sont paramétrées
- [ ] Pas de direct `$_GET`/`$_POST` accès en SQL
- [ ] Error messages génériques (no tech details)
- [ ] Logging activé et fonctionnel

**Vérification:**
```bash
# Test endpoint avec injection
curl "https://yourdomain.com/api/balance?exercice=2024 OR 1=1"
# Should return error, not all data

# Check logs
tail -20 backend/logs/$(date +%Y-%m-%d).log
# Should show operation logs, no errors
```

### Authentication & Authorization
- [ ] JWT activation checked (Phase 2)
- [ ] Role-based access working
- [ ] No anonymous access to sensitive endpoints
- [ ] CSRF tokens on POST/PUT/DELETE

**Vérification:**
```bash
# Test without auth
curl https://yourdomain.com/api/analyze/fec
# Should return 401 Unauthorized (after Phase 2)
```

### File Upload Security
- [ ] MIME type validation implemented
- [ ] File size limits enforced (64MB max)
- [ ] Upload directory outside web root if possible
- [ ] No executable upload (.php, .exe, .sh)

**Vérification:**
```bash
# Test upload of suspicious file
curl -X POST -F "file=@shell.php" https://yourdomain.com/api/upload
# Should reject
```

---

## SECTION 2️⃣: DATABASE

**Responsable:** Responsable DBA/Infra  
**Temps estimé:** 45 minutes

### Structure & Data
- [ ] Schéma créé: `mysql ... < backend/config/schema.sql`
- [ ] Tables créées et vérifiées: `SHOW TABLES;`
- [ ] Indexes présents: `SHOW INDEX FROM fec_lines;`
- [ ] Test data imported: `SELECT COUNT(*) FROM fec_lines;`

**Vérification:**
```bash
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME -e "
SHOW TABLES;
SHOW CREATE TABLE fec_lines;
SELECT COUNT(*) AS row_count FROM fec_lines;
"
```

### User & Permissions
- [ ] Utilisateur créé avec permissions MINIMALES
- [ ] No DROP, CREATE, ALTER permissions
- [ ] No GRANT permission
- [ ] File privileges removed: `REVOKE FILE ON *.* FROM user;`

**Vérification:**
```bash
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS -e "
SHOW GRANTS FOR 'dbu2705925'@'localhost';
"
# Should show only: SELECT, INSERT, UPDATE, DELETE on specific DB
```

### Backups
- [ ] Backup plan documented
- [ ] Restore test successful (can restore from backup)
- [ ] Backup automation configured
- [ ] Backup storage secure and off-site

**Vérification:**
```bash
# Make test backup
mysqldump -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME > backup-test.sql

# Verify restore
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME_TEST < backup-test.sql
# Should work without errors
```

### Performance
- [ ] Query analysis done: `EXPLAIN SELECT ...`
- [ ] Slow queries optimized (< 200ms target)
- [ ] Indexes added for WHERE/JOIN/ORDER BY
- [ ] Table statistics updated: `ANALYZE TABLE fec_lines;`

---

## SECTION 3️⃣: INFRA & HOSTING

**Responsable:** Responsable Ops  
**Temps estimé:** 30 minutes

### Web Server (Apache)
- [ ] `.htaccess` deployed with security headers
- [ ] HTTPS/SSL certificate installed
- [ ] mod_rewrite enabled
- [ ] mod_deflate (GZIP) enabled
- [ ] Directory listing disabled

**Vérification:**
```bash
# Test HTTPS
curl -I https://yourdomain.com
# Should show 200, not 404

# Check headers
curl -I https://yourdomain.com | grep "X-Content-Type-Options"
# Should show: nosniff

# Check GZIP
curl -I -H "Accept-Encoding: gzip" https://yourdomain.com | grep "Content-Encoding"
# Should show: gzip
```

### PHP Configuration
- [ ] PHP version compatible (7.4+)
- [ ] error_reporting set correctly
- [ ] display_errors = 0 (production)
- [ ] log_errors = 1 (enabled)
- [ ] memory_limit sufficient (128MB+)
- [ ] max_execution_time adequate (30s+)
- [ ] upload_max_filesize = 64M

**Vérification:**
```bash
# Create test file
echo "<?php phpinfo(); ?>" > public_html/phpinfo.php
curl https://yourdomain.com/phpinfo.php | grep "display_errors"
# Should show: display_errors = Off

# Then delete
rm public_html/phpinfo.php
```

### SSL Certificate
- [ ] Certificate valid and installed
- [ ] Not self-signed (trusted CA)
- [ ] Expiry date checked (> 30 days minimum)
- [ ] Renewal reminder set

**Vérification:**
```bash
# Check certificate
openssl s_client -connect yourdomain.com:443 -showcerts
# Look for: not expired, valid CA

# Test SSL strength
nmap --script ssl-enum-ciphers -p 443 yourdomain.com
```

### DNS & Routing
- [ ] Domain points to correct IP
- [ ] DNS propagated globally
- [ ] CNAME/A records correct
- [ ] Email records (MX, SPF, DKIM) if needed

**Vérification:**
```bash
# Test DNS
nslookup yourdomain.com
dig yourdomain.com

# Test connectivity
ping yourdomain.com
curl -I https://yourdomain.com
```

---

## SECTION 4️⃣: CODE & TESTS

**Responsable:** Responsable QA/Dev Lead  
**Temps estimé:** 60 minutes

### Code Review
- [ ] All changes reviewed by 2+ people
- [ ] No merge without approval
- [ ] Security review completed
- [ ] Performance checked

### Tests
- [ ] Unit tests pass: `phpunit tests/`
- [ ] Integration tests pass
- [ ] API endpoints tested with real data
- [ ] FEC import tested with production file

**Vérification:**
```bash
# Run full test suite
vendor/bin/phpunit tests/ --coverage-html coverage/

# Test FEC import
curl -X POST \
  -F "file=@fec_2024_atc.txt" \
  https://yourdomain.com/api/fec/import
# Should return success + validation results
```

### Deployment
- [ ] Deploy script tested in staging
- [ ] Rollback procedure documented and tested
- [ ] Database migrations ready
- [ ] Schema updates tested

**Vérification:**
```bash
# Test deployment script
bash deploy-staging.sh
# Should complete without errors

# Check staging fully functional
curl https://staging.yourdomain.com/api/health
# Should return 200 OK
```

---

## SECTION 5️⃣: MONITORING & LOGS

**Responsable:** Responsable Ops  
**Temps estimé:** 20 minutes

### Application Health
- [ ] Health endpoint responds: `/api/health`
- [ ] Logs directory writable and initialized
- [ ] First log file created
- [ ] Monitoring configured for alerts

**Vérification:**
```bash
# Check health endpoint
curl https://yourdomain.com/api/health
# Should return {"status": "ok"}

# Check logs
ls -la backend/logs/
# Should show today's log file with content
```

### Error Handling
- [ ] No error details exposed to users (production)
- [ ] All errors logged with full context
- [ ] Critical errors trigger alerts

**Vérification:**
```bash
# Force error to test handling
curl "https://yourdomain.com/api/balance?exercice=invalid"

# Check log for error without exposing details
grep "2024-01-15" backend/logs/2024-01-15.log | tail -5
```

### Performance Baseline
- [ ] Response time baseline recorded (< 200ms)
- [ ] Database query time monitored
- [ ] Memory usage monitored
- [ ] CPU usage monitored

---

## SECTION 6️⃣: FINAL CHECKS

**Responsable:** Project Lead  
**Temps estimé:** 15 minutes

### Documentation
- [ ] README.md current
- [ ] API documentation updated
- [ ] Deployment guide written
- [ ] Runbook for common issues created
- [ ] Team trained on new patterns

### Stakeholders
- [ ] Business stakeholders informed
- [ ] Support team briefed
- [ ] Emergency contacts updated
- [ ] Change request filed if required

### Post-Deployment Plan
- [ ] Rollback plan documented (< 15 min)
- [ ] Escalation contacts identified
- [ ] Communication channel for issues
- [ ] Post-mortem scheduled for day after

---

## ✅ GO/NO-GO DECISION

### GO if:
- ✅ All critical sections 100% complete
- ✅ All tests passing
- ✅ Security review signed off
- ✅ Team trained and ready
- ✅ Rollback plan verified

### NO-GO if:
- ❌ Any security check failed
- ❌ Tests not passing
- ❌ Performance issues identified
- ❌ Team not ready
- ❌ Unclear rollback procedure

---

## 📝 SIGN-OFF

```
Deployment Date: ________________
Deployment Time: ________________
Deployed By: ____________________

Approvals Required:

Security Lead:        ____________________  Date: ______
Database Admin:       ____________________  Date: ______
Operations Manager:   ____________________  Date: ______
Project Lead:         ____________________  Date: ______

Post-Deployment Contact:
Primary:   ____________________  Phone: ____________________
Backup:    ____________________  Phone: ____________________

Success Criteria Met: YES / NO
Post-Deployment Review: Scheduled for ____________________
```

---

## 🔗 Important Links

- **Ionos Admin Panel:** https://ionos.com/admin
- **Database Host:** `db5019387279.hosting-data.io`
- **Database Name:** `dbs15168768`
- **Application URL:** `https://yourdomain.com`
- **Staging URL:** `https://staging.yourdomain.com`

---

## 📞 ESCALATION PATH

1. **First Issue:** Contact Primary Post-Deployment Contact
2. **Unresolved (15 min):** Contact Backup Contact
3. **Still Unresolved (30 min):** Contact Ionos Support + Project Lead
4. **Critical (DB Down, No Service):** ROLLBACK IMMEDIATELY

---

**Last Updated:** 15/01/2026  
**Valid Until:** After next security audit

