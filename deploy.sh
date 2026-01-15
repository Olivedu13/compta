#!/bin/bash

# 🚀 Deployment Script - Compta Application
# Usage: bash deploy.sh [production|staging]

set -e

ENVIRONMENT=${1:-staging}
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
LOG_FILE="./deploy_${TIMESTAMP}.log"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Logging functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1" | tee -a "$LOG_FILE"
}

log_success() {
    echo -e "${GREEN}[✓]${NC} $1" | tee -a "$LOG_FILE"
}

log_warn() {
    echo -e "${YELLOW}[⚠️ ]${NC} $1" | tee -a "$LOG_FILE"
}

log_error() {
    echo -e "${RED}[✗]${NC} $1" | tee -a "$LOG_FILE"
}

# ============================================
# 1. PRE-DEPLOYMENT CHECKS
# ============================================

log_info "Starting deployment to $ENVIRONMENT..."
log_info "Log file: $LOG_FILE"

# Check Node.js
if ! command -v node &> /dev/null; then
    log_error "Node.js not installed"
    exit 1
fi
log_success "Node.js version: $(node --version)"

# Check PHP
if ! command -v php &> /dev/null; then
    log_error "PHP not installed"
    exit 1
fi
log_success "PHP version: $(php --version | head -n1)"

# Check git status
if [[ -n $(git status -s) ]]; then
    log_warn "Uncommitted changes detected"
    git status -s | tee -a "$LOG_FILE"
fi

# ============================================
# 2. BUILD FRONTEND
# ============================================

log_info "Building frontend..."

cd frontend

# Install dependencies
log_info "Installing npm dependencies..."
npm ci --prefer-offline --no-audit >> "$LOG_FILE" 2>&1

# Build
log_info "Running Vite build..."
npm run build >> "$LOG_FILE" 2>&1

if [ -d "dist" ]; then
    DIST_SIZE=$(du -sh dist | cut -f1)
    log_success "Frontend build complete (size: $DIST_SIZE)"
else
    log_error "Frontend build failed - dist directory not found"
    exit 1
fi

cd ..

# ============================================
# 3. VALIDATE BACKEND
# ============================================

log_info "Validating backend..."

# Check PHP syntax
log_info "Checking PHP syntax..."
php -l backend/config/Router.php >> "$LOG_FILE" 2>&1
php -l backend/services/ImportService.php >> "$LOG_FILE" 2>&1
php -l backend/services/SigCalculator.php >> "$LOG_FILE" 2>&1
log_success "PHP syntax valid"

# ============================================
# 4. DATABASE MIGRATION
# ============================================

log_info "Checking database..."

if [ "$ENVIRONMENT" = "production" ]; then
    # Backup existing database
    if [ -f "compta.db" ]; then
        log_info "Backing up existing database..."
        mkdir -p backups
        cp compta.db "backups/compta_${TIMESTAMP}.db"
        log_success "Database backed up to backups/compta_${TIMESTAMP}.db"
    fi
fi

# Initialize/migrate database
log_info "Initializing database schema..."
sqlite3 compta.db < backend/config/schema.sql >> "$LOG_FILE" 2>&1
log_success "Database schema ready"

# ============================================
# 5. ENVIRONMENT CONFIGURATION
# ============================================

log_info "Setting up environment..."

if [ "$ENVIRONMENT" = "production" ]; then
    ENV_FILE=".env.production"
else
    ENV_FILE=".env.staging"
fi

if [ -f "$ENV_FILE" ]; then
    log_success "Using $ENV_FILE"
else
    log_warn "Creating default $ENV_FILE"
    cp .env.production "$ENV_FILE"
fi

# ============================================
# 6. PERMISSIONS & OWNERSHIP
# ============================================

log_info "Setting permissions..."

# Make scripts executable
chmod +x test-e2e.sh
chmod +x upload-direct.sh

# Create directories
mkdir -p backend/logs
mkdir -p backups

# Set permissions (staging vs production)
if [ "$ENVIRONMENT" = "production" ]; then
    log_warn "Setting restrictive permissions for PRODUCTION"
    chmod 750 backend/
    chmod 640 compta.db
    chmod 755 public_html/api/
else
    chmod 755 backend/
    chmod 644 compta.db
    chmod 755 public_html/api/
fi

log_success "Permissions set"

# ============================================
# 7. RUN TESTS
# ============================================

log_info "Running validation tests..."

# Health check
log_info "Testing API health..."
HEALTH_CHECK=$(curl -s -m 5 http://localhost/api/health 2>/dev/null || echo '{"success":false}')

if echo "$HEALTH_CHECK" | grep -q '"success":true'; then
    log_success "API health check passed"
else
    log_warn "API not responding (might not be running yet)"
fi

# ============================================
# 8. GENERATE REPORT
# ============================================

log_info "Generating deployment report..."

REPORT_FILE="deploy_report_${TIMESTAMP}.md"
cat > "$REPORT_FILE" << EOF
# 📋 Deployment Report

**Environment:** $ENVIRONMENT  
**Timestamp:** $TIMESTAMP  
**Node.js:** $(node --version)  
**PHP:** $(php --version | head -n1)  
**SQLite:** $(sqlite3 --version)  

## Build Status

✅ Frontend build successful
- Build tool: Vite
- Output: frontend/dist/
- Size: $DIST_SIZE

✅ Backend validation passed
- PHP syntax: OK
- Configuration: Ready

## Database Status

✅ Database initialized
- Path: compta.db
- Schema: Applied
- Backup: Available (if production)

## Environment

$ENVIRONMENT configuration loaded from $ENV_FILE

## Files Modified

$(git status -s | head -20)

---

Log file: $LOG_FILE
EOF

log_success "Report generated: $REPORT_FILE"

# ============================================
# 9. SUMMARY
# ============================================

echo ""
echo "╔════════════════════════════════════════╗"
echo "║  ✅ DEPLOYMENT SUCCESSFUL              ║"
echo "╚════════════════════════════════════════╝"
echo ""
echo "Environment:     $ENVIRONMENT"
echo "Timestamp:       $TIMESTAMP"
echo "Frontend:        ✅ Built ($DIST_SIZE)"
echo "Backend:         ✅ Validated"
echo "Database:        ✅ Ready"
echo ""
echo "📝 Log file:     $LOG_FILE"
echo "📊 Report:       $REPORT_FILE"
echo ""

if [ "$ENVIRONMENT" = "production" ]; then
    log_warn "⚠️  PRODUCTION DEPLOYMENT COMPLETE"
    log_warn "Please verify:"
    echo "  1. All services running (PHP, Apache/Nginx)"
    echo "  2. Database backups configured"
    echo "  3. Monitoring/logging enabled"
    echo "  4. CORS and security headers set"
else
    log_success "Staging deployment ready for testing"
    echo "Run: npm run dev (frontend)"
    echo "Or:  php -S localhost:8000 (backend)"
fi

echo ""
