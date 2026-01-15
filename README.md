# 📊 Compta - Accounting Analysis Platform

Modern accounting analysis platform with advanced analytics and FEC file processing.

**Status**: ✅ Production Ready (Phase 1-5 Complete)  
**Version**: 2.0  
**Stack**: React 18 + Material-UI 5 (frontend) | PHP 8+ (backend) | MySQL (database)

---

## 🚀 Quick Start

### Requirements
- Node.js 16+
- PHP 8+
- MySQL 5.7+

### Local Installation

```bash
# Frontend
cd frontend
npm install
npm run dev

# Backend
# Configure .env with MySQL credentials
# Database must exist with schema.sql imported
php -S localhost:8000 -t public_html
```

### Production Deployment
See [docs/DEPLOYMENT_GUIDE.md](docs/DEPLOYMENT_GUIDE.md) for hosting options.

---

## 📚 Documentation - Read in This Order

### 🎯 Start Here
1. **[📖 00_START_HERE.md](00_START_HERE.md)** ← Visual overview & entry point
   - What was delivered
   - Statistics
   - Getting started
   - Quick reference card

### 📋 For Developers
2. **[ARCHITECTURE_GUIDELINES.md](ARCHITECTURE_GUIDELINES.md)** ← The Source of Truth
   - Project structure & conventions
   - Component development rules
   - Styling & Design System
   - Testing requirements
   - Git workflow
   - Performance & Accessibility
   - Deployment checklist

3. **[QUICK_START_NEW_COMPONENT.md](QUICK_START_NEW_COMPONENT.md)** ← How to Create
   - Pre-creation checklist
   - AI prompt templates
   - Manual creation steps
   - Standard imports
   - Token usage examples

### 🤖 For AI Agents
4. **[AI_FEATURE_REQUEST_AGENT.md](AI_FEATURE_REQUEST_AGENT.md)** ← AI Workflow
   - Restructure feature requests
   - Validate architecture
   - Plan implementation
   - Quality verification

### Additional Resources
- **[Quickstart](docs/QUICKSTART.md)** - Quick setup
- **[API Documentation](docs/API_DOCUMENTATION.md)** - REST Endpoints
- **[Security Guide](docs/SECURITY_GUIDE.md)** - Security configuration
- **[Deployment Guide](docs/DEPLOYMENT_GUIDE.md)** - Production deployment

---

## 🏗️ Architecture

```
/backend           Backend PHP (outside web root)
/frontend          React 18 + Vite frontend
/public_html       Web root + API endpoints
/docs              Documentation
```

Frontend structure:
```
/src
├── /components
│   ├── /common         ← Reusable components
│   ├── /charts         ← Analytics components
│   ├── /sig            ← SIG components
│   └── /dashboard      ← Dashboard components
├── /pages              ← Page components
├── /services           ← API layer
├── /theme              ← Design System
│   ├── designTokens.js (100+ tokens)
│   ├── animations.js   (13 keyframes + presets)
│   ├── responsive.js   (media queries + helpers)
│   └── index.js        (barrel export)
└── App.jsx             ← Root component
```

---

## 🎨 Design System - Ready to Use

### Available Tokens
- **Colors**: 8 palettes + bijouterie colors
- **Typography**: 9 sizes, 9 weights
- **Spacing**: 25 values
- **Animations**: 13 keyframes + 10 presets
- **Responsive**: 5 breakpoints (xs-xl)
- **Shadows**: 8 levels

### Import & Usage
```javascript
import { designTokens, media, animations } from './theme';

<Box sx={{
  color: designTokens.colors.primary[600],
  padding: designTokens.spacing[4],
  [media.md]: { padding: designTokens.spacing[6] },
}}>
```

---

## 🧪 Testing Infrastructure

### Run Tests
```bash
cd frontend
npm test                    # Watch mode
npm test -- --coverage      # Coverage report
npm test ComponentName       # Specific component
```

### Coverage Target
- **Minimum**: 70% (branches, functions, lines, statements)
- **Pattern**: See `src/components/common/__tests__/common.test.js`

---

## ✅ Quality Standards

### Code Quality
- ✅ No ESLint errors
- ✅ PropTypes validated
- ✅ JSDoc complete
- ✅ Design tokens used
- ✅ No inline styles

### Testing
- ✅ 70%+ coverage
- ✅ All tests passing
- ✅ No console.error/warn

### Accessibility
- ✅ WCAG 2.1 AA
- ✅ ARIA labels
- ✅ Keyboard navigation
- ✅ 4.5:1 contrast ratio

---

## 🔐 Authentication

- JWT (HS256, 24h expiry)
- Login: `POST /api/auth/login.php`
- Credentials: `.env` (not committed)

---

## 📊 Main Features

- ✅ Automated FEC import
- ✅ Analytical dashboards
- ✅ SIG calculation (Intermediate Balances)
- ✅ Data export
- ✅ Multi-year management
- ✅ Advanced analytics
- ✅ Real-time KPIs
- ✅ Accessible interface

---

## 🐛 Report a Bug

Create a GitHub issue with:
1. Problem description
2. Steps to reproduce
3. Expected vs actual result
4. Screenshots (if UI related)

---

## 📊 Statistics

| Metric | Value |
|--------|-------|
| **Total Files Created** | 30+ |
| **Total Lines Added** | ~2,600 |
| **Design Tokens** | 100+ |
| **Components** | 25+ |
| **API Endpoints** | 7 |
| **Test Coverage** | 70% minimum |
| **Production Ready** | ✅ YES |

---

## ⚠️ Important

**MANDATORY**: All future code MUST follow:
1. [ARCHITECTURE_GUIDELINES.md](ARCHITECTURE_GUIDELINES.md) - Rules
2. [QUICK_START_NEW_COMPONENT.md](QUICK_START_NEW_COMPONENT.md) - How to create
3. [AI_FEATURE_REQUEST_AGENT.md](AI_FEATURE_REQUEST_AGENT.md) - AI workflow

No exceptions!

---

## 📝 License

Propriétaire - Atelier Thierry

---

**Audit & Refactorisation**: Voir [AUDIT_EXECUTIF.md](AUDIT_EXECUTIF.md)  
**Docs Archivées**: [docs/obsolete/](docs/obsolete/)
