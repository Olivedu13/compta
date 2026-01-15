# ✅ Checklist Phase 7 - Production Deployment

## Pre-Deployment Verification

### 1. Code Quality
- [ ] No console.error() in frontend (dev mode)
- [ ] No console.log() left in production code
- [ ] No TODO/FIXME comments in critical files
- [ ] CSS minified in build
- [ ] No unused imports in React components

### 2. Environment Setup
- [ ] .env.production exists and configured
- [ ] Database path set correctly
- [ ] Log directories writable (backend/logs/)
- [ ] Backups directory exists (backups/)
- [ ] CORS origins configured

### 3. Security Audit
- [ ] No hardcoded credentials in code
- [ ] No API keys in frontend code
- [ ] SQL injection protection (parameterized queries)
- [ ] XSS protection in React (no dangerouslySetInnerHTML)
- [ ] HTTPS configured (if applicable)

### 4. Performance Optimization
- [ ] Frontend assets cached (Cache-Control headers)
- [ ] Database indexes created:
  - [ ] CREATE INDEX idx_journal ON ecritures(journal_code)
  - [ ] CREATE INDEX idx_tiers ON ecritures(numero_tiers)
  - [ ] CREATE INDEX idx_date ON ecritures(date_ecriture)
- [ ] API responses under 100ms
- [ ] Frontend bundle < 500KB (gzipped)

### 5. Database Verification
- [ ] Total ecritures: 11,617
- [ ] Total tiers: 125
- [ ] Balance: €0.00 exactly
- [ ] All journals present (7 total)
- [ ] No duplicate entries
- [ ] Backup created before production

### 6. API Testing (Manual)
- [ ] GET /api/health → 200 OK
- [ ] GET /api/tiers?limit=5 → 200 OK, valid JSON
- [ ] GET /api/tiers/08000001 → 200 OK with detail
- [ ] GET /api/cashflow → 200 OK with stats
- [ ] GET /api/cashflow/detail/VE → 200 OK with journal detail
- [ ] All responses have 'success' field

### 7. Frontend Testing (Manual)
- [ ] Dashboard loads without errors
- [ ] KPI cards display values
- [ ] SIGPage all 4 tabs functional
- [ ] Tiers widget pagination works
- [ ] Cashflow widget tabs work
- [ ] Graphs render correctly
- [ ] No "404" errors in console

### 8. Error Handling
- [ ] 404 Not Found responses handled
- [ ] 500 Server Error displays user message
- [ ] Network timeout handled gracefully
- [ ] Invalid data shows error message (not crash)
- [ ] Logs show error details

### 9. Deployment Test
- [ ] Run: bash deploy.sh staging
- [ ] Verify output: "✅ DEPLOYMENT SUCCESSFUL"
- [ ] Frontend build completes (dist/)
- [ ] Database schema applied
- [ ] No PHP errors in logs
- [ ] No JavaScript console errors

### 10. Monitoring Setup
- [ ] Application logs configured
- [ ] Error alerting enabled (if available)
- [ ] Backup task scheduled (daily)
- [ ] Disk space monitoring active
- [ ] Database integrity checks scheduled

---

## Deployment Steps

### A. Pre-Flight Check
```bash
# 1. Verify Node.js
node --version    # Should be 18+

# 2. Verify PHP
php --version     # Should be 7.4+

# 3. Check git status
git status        # Should be clean (no uncommitted changes)

# 4. Verify database exists
ls -la compta.db
```

### B. Build & Test
```bash
# 1. Build frontend
cd frontend
npm ci --prefer-offline
npm run build

# 2. Verify build
ls dist/          # Should have index.html, assets/

# 3. Validate backend
cd ../backend/config
php -l Database.php
php -l Router.php
```

### C. Database Setup
```bash
# 1. Backup current database
cp compta.db compta.db.backup.$(date +%s)

# 2. Apply schema
sqlite3 compta.db < backend/config/schema.sql

# 3. Verify integrity
sqlite3 compta.db "SELECT COUNT(*) FROM ecritures;"
# Should show: 11617

sqlite3 compta.db "SELECT SUM(debit), SUM(credit) FROM ecritures;"
# Should show equal values (balance = 0)
```

### D. Deploy
```bash
# 1. Run deployment script
bash deploy.sh production

# 2. Check log file
tail -50 deploy_*.log

# 3. Verify report
cat deploy_report_*.md

# 4. Read summary output (should show ✅ DEPLOYMENT SUCCESSFUL)
```

### E. Post-Deployment Validation
```bash
# 1. Health check
curl -s http://localhost/api/health | jq .

# 2. Test /api/tiers
curl -s "http://localhost/api/tiers?limit=5" | jq '.pagination'

# 3. Test /api/cashflow
curl -s http://localhost/api/cashflow | jq '.stats_globales'

# 4. Run E2E tests
bash test-e2e.sh
```

---

## Post-Deployment Actions

### 1. Verify All Services
- [ ] PHP-FPM/Apache running
- [ ] Database service running
- [ ] Frontend accessible (HTTPS working)
- [ ] APIs responding (all 4 endpoints)

### 2. Monitor for Issues
- [ ] Check logs for errors (first 10 minutes)
- [ ] Monitor CPU usage (should be <50%)
- [ ] Monitor memory usage (should be <500MB)
- [ ] Monitor disk space (should be >500MB free)

### 3. Data Integrity
- [ ] Run: sqlite3 compta.db "SELECT COUNT(*) FROM ecritures;"
- [ ] Should show: 11617 (no data loss)
- [ ] Run balance check: balance should be €0.00

### 4. User Communication
- [ ] Notify users application is live
- [ ] Provide access URLs
- [ ] Share USER_GUIDE.md with end-users
- [ ] Set up support contact info

### 5. Ongoing Maintenance
- [ ] Schedule daily backups
- [ ] Schedule weekly log rotation
- [ ] Set up monitoring alerts
- [ ] Document incident response

---

## Rollback Plan (If Needed)

### Quick Rollback
```bash
# 1. Restore database backup
cp compta.db.backup.TIMESTAMP compta.db

# 2. Stop application
sudo systemctl stop apache2  # or nginx

# 3. Restore previous code
git checkout HEAD~1

# 4. Restart application
sudo systemctl start apache2
```

---

## Success Criteria

✅ **Phase 7 is SUCCESSFUL if:**

1. ✅ All 4 APIs responding with 200 OK
2. ✅ Database balance = €0.00 (11,617 ecritures)
3. ✅ Frontend Dashboard loads without errors
4. ✅ SIGPage renders all 4 tabs
5. ✅ E2E tests pass (20+ tests)
6. ✅ Performance < 100ms average (APIs)
7. ✅ No errors in application logs
8. ✅ Backup created successfully
9. ✅ Users can login and use the system
10. ✅ HTTPS certificate valid (if applicable)

---

## Emergency Contacts

- **DevOps Lead:** [Contact Info]
- **Database Admin:** [Contact Info]
- **Frontend Lead:** [Contact Info]
- **Support Queue:** support@compta.local

---

## Documentation References

- 📚 [API Documentation](API_DOCUMENTATION.md)
- 👥 [User Guide](USER_GUIDE.md)
- 🔧 [Developer Guide](DEVELOPER_GUIDE.md)
- 📊 [Project Status](PROJECT_STATUS.md)
- 🎯 [Phase 6 Summary](PHASE_6_COMPLETE.md)

---

**Deployment Date:** [Fill in when deploying]  
**Deployed By:** [Your name]  
**Deployment Duration:** [Actual duration]  
**Deployment Status:** ⏳ PENDING

---

## Sign-Off

- [ ] Code Review: _________________ Date: _______
- [ ] QA Testing: _________________ Date: _______
- [ ] DevOps Approval: _________________ Date: _______
- [ ] Go/No-Go Decision: _________________ Date: _______

---

**Ready for Production Deployment? 🚀**
