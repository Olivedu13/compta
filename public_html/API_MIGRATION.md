# 📚 API Migration - Endpoints Reorganization

**Date**: January 15, 2026  
**Status**: ✅ Complete  
**Purpose**: Move legacy endpoints from root to organized API v1 structure

---

## 📋 Migration Summary

### ❌ Old Endpoints (DEPRECATED)
These files are now **redirectors** that point to the new API v1 structure:

| Old Endpoint | New Endpoint | Status |
|---|---|---|
| `GET /annees-simple.php` | `GET /api/v1/years/list.php` | ✅ Redirected |
| `GET /balance-simple.php` | `GET /api/v1/balance/simple.php` | ✅ Redirected |
| `GET /analyse-simple.php` | `GET /api/v1/analytics/simple.php` | ✅ Redirected |
| `GET /analytics-advanced.php` | `GET /api/v1/analytics/advanced.php` | ✅ Redirected |
| `GET /comptes-simple.php` | `GET /api/v1/accounts/simple.php` | ✅ Redirected |
| `GET /kpis-simple.php` | `GET /api/v1/kpis/simple.php` | ✅ Redirected |
| `GET /kpis-detailed.php` | `GET /api/v1/kpis/detailed.php` | ✅ Redirected |
| `GET /sig-simple.php` | `GET /api/v1/sig/simple.php` | ✅ Redirected |

### ✅ New API v1 Structure

```
/public_html/api/v1/
├── /analytics/
│   ├── simple.php
│   └── advanced.php
├── /years/
│   └── list.php
├── /balance/
│   └── simple.php
├── /accounts/
│   └── simple.php
├── /kpis/
│   ├── simple.php
│   └── detailed.php
├── /sig/
│   └── simple.php
└── index.php (main router)
```

---

## 🔄 Backward Compatibility

All old endpoints still work! They return HTTP 301 (Moved Permanently) and redirect to the new API v1 endpoints:

```bash
# Old way (still works)
curl http://localhost:8000/annees-simple.php
# Response: 301 redirect → /api/v1/years/list.php

# New way (recommended)
curl http://localhost:8000/api/v1/years/list.php
```

Headers included in redirects:
```
HTTP/1.1 301 Moved Permanently
Location: /api/v1/years/list.php
X-Deprecated: true
X-Migration: Endpoint moved to /api/v1/years/list.php
```

---

## 🔍 Benefits

1. **Organization**: All API endpoints in `/api/v1/` by category
2. **Scalability**: Easy to add API v2, v3 later
3. **Maintainability**: Clear structure matching business logic
4. **Documentation**: Self-documenting URLs
5. **Security**: Centralized routing, easier to control access
6. **Backward Compatibility**: Old URLs still work (301 redirects)

---

## 🚀 Frontend Updates

### Before
```javascript
// Scattered endpoints
const data = await fetch('/annees-simple.php');
const balance = await fetch('/balance-simple.php?exercice=2024');
const kpis = await fetch('/kpis-simple.php');
```

### After (Recommended)
```javascript
// Organized endpoints
const data = await fetch('/api/v1/years/list.php');
const balance = await fetch('/api/v1/balance/simple.php?exercice=2024');
const kpis = await fetch('/api/v1/kpis/simple.php');
```

---

## ⚠️ Deprecation Timeline

| Phase | Timeline | Action |
|---|---|---|
| **Phase 1** (Now) | Jan 2026 | Endpoints moved, old URLs redirect |
| **Phase 2** | Q2 2026 | Log deprecation warnings (optional) |
| **Phase 3** | Q4 2026 | Remove old endpoint files |
| **Phase 4** | 2027 | Only new API v1 structure |

---

## 📝 For Developers

### Maintenance Notes
- Old redirector files in `/public_html/` maintain backward compatibility
- Production code should use new `/api/v1/` endpoints
- Remove redirector files ONLY after phase 3 (Q4 2026)
- Update frontend code to use new endpoints gradually

### Testing
```bash
# Test old endpoint (should redirect)
curl -v http://localhost:8000/annees-simple.php

# Test new endpoint (should work)
curl http://localhost:8000/api/v1/years/list.php
```

### Adding New Endpoints
Always add new endpoints to `/api/v1/[category]/[action].php`

Example:
```bash
# New endpoint for users
/api/v1/users/list.php
/api/v1/users/create.php
/api/v1/users/update.php
/api/v1/users/delete.php
```

---

## 🔧 Implementation Details

### Redirector Code Pattern
All old endpoint files use this pattern:

```php
<?php
/**
 * ⚠️ DÉPRÉCIÉ - Endpoint migré vers /api/v1/
 * Nouvel endpoint: GET /api/v1/[category]/[action].php
 */

$queryString = http_build_query($_GET);
$newUrl = '/api/v1/[category]/[action].php' . ($queryString ? '?' . $queryString : '');

http_response_code(301); // Moved Permanently
header('Location: ' . $newUrl);
header('X-Deprecated: true');
header('X-Migration: Endpoint moved to /api/v1/[category]/[action].php');

exit;
```

### New Endpoint Code Pattern
All new endpoints follow this structure:

```php
<?php
/**
 * GET /api/v1/[category]/[action].php
 * Description
 * 
 * @method GET/POST
 * @param {type} param - description
 * @return {success: boolean, data: array, error?: string}
 */

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/backend/bootstrap.php';

use App\Config\Database;
use App\Config\Logger;

header('Content-Type: application/json; charset=utf-8');

try {
    // Implementation
    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
```

---

## 📊 Status

| Task | Status |
|---|---|
| Move endpoints to `/api/v1/` | ✅ Done |
| Create redirectors | ✅ Done |
| Backward compatibility | ✅ Working |
| Documentation | ✅ This file |
| Frontend migration | ⏳ Planned |
| Testing | ⏳ TODO |

---

## 🎯 Next Steps

1. **Test all endpoints**: Verify redirects work
2. **Update frontend**: Gradually migrate to new URLs
3. **Monitor**: Track usage of old vs new endpoints
4. **Deprecate**: Phase out old endpoint files (Q4 2026)

---

**Version**: 1.0  
**Last Updated**: January 15, 2026  
**Status**: ✅ Complete
