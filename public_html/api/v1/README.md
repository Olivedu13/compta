# 🔌 API v1

## Structure

```
api/v1/
├── index.php              # Router centralisé
├── accounting/            # Endpoints comptabilité
│   ├── years.php
│   ├── balance.php
│   ├── ledger.php         # (alias balance)
│   ├── accounts.php
│   └── sig.php
├── analytics/             # Endpoints analytics
│   ├── kpis.php
│   └── analysis.php
├── users/                 # Endpoints authentification (Phase 3)
└── admin/                 # Endpoints admin (Phase 3)
```

## 🚀 Router Centralisé

Toutes les requêtes passent par `index.php`:
```
GET /api/v1/{resource}/{action}?params=...
```

Exemple:
```
GET /api/v1/accounting/balance?exercice=2024
→ charge: accounting/balance.php
```

## 📚 Documentation Complète

Voir [API_V1_REFERENCE.md](../../docs/API_V1_REFERENCE.md)

## ✅ Endpoints Disponibles

### Accounting
- ✅ GET `/accounting/years` - Années disponibles
- ✅ GET `/accounting/balance` - Balance générale
- ✅ GET `/accounting/ledger` - Grand livre
- ✅ GET `/accounting/accounts` - Liste comptes
- ✅ GET `/accounting/sig` - SIG calculation

### Analytics
- ✅ GET `/analytics/kpis` - KPIs
- ✅ GET `/analytics/analysis` - Analyse complète

### Users (TODO Phase 3)
- ⏳ POST `/users/login`
- ⏳ GET `/users/profile`
- ⏳ POST `/users/change-password`

### Admin (TODO Phase 3)
- ⏳ POST `/admin/import-fec`
- ⏳ GET `/admin/import-status`

---

**Status**: 🟢 Production Ready  
**Version**: 1.0.0  
**Date**: 2026-01-15
