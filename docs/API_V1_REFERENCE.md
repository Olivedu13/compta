# 🔌 API v1 Documentation

**Base URL**: `https://compta.sarlatc.com/api/v1`

## 📋 Endpoints

### Accounting (Comptabilité)

#### 1. **GET /accounting/years**
Liste les années disponibles

```bash
GET /api/v1/accounting/years
```

**Response**:
```json
{
  "success": true,
  "data": ["2024", "2023", "2022"]
}
```

---

#### 2. **GET /accounting/balance**
Balance générale pour un exercice

```bash
GET /api/v1/accounting/balance?exercice=2024&page=1&limit=100
```

**Parameters**:
- `exercice` (required): Année comptable
- `page` (optional): Numéro de page (défaut: 1)
- `limit` (optional): Résultats par page (défaut: 100, max: 500)

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "compte_num": "512100",
      "solde": 15234.50,
      "debit": 50000,
      "credit": 34765.50,
      "libelle": "Compte courant"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 100,
    "total": 342,
    "pages": 4
  }
}
```

---

#### 3. **GET /accounting/ledger**
Grand livre détaillé (alias pour balance)

```bash
GET /api/v1/accounting/ledger?exercice=2024
```

Identique à `/balance`

---

#### 4. **GET /accounting/accounts**
Liste des comptes avec leurs soldes

```bash
GET /api/v1/accounting/accounts?exercice=2024&classe=4
```

**Parameters**:
- `exercice` (required): Année comptable
- `classe` (optional): Filtrer par classe (1-9)

**Response**:
```json
{
  "success": true,
  "count": 87,
  "data": [
    {
      "compte_num": "411100",
      "classe": "4",
      "solde": 5234.50,
      "debit": 15000,
      "credit": 9765.50,
      "compte_libelle": "Clients"
    }
  ]
}
```

---

#### 5. **GET /accounting/sig**
Soldes Intermédiaires de Gestion (SIG)

```bash
GET /api/v1/accounting/sig?exercice=2024
```

**Response**:
```json
{
  "success": true,
  "data": {
    "exercice": "2024",
    "ventes": 250000,
    "charges_exploitation": 180000,
    "resultat_exploitation": 70000,
    "actif_total": 125000,
    "passif_total": 125000,
    "tresorerie": 15000
  },
  "classes": {
    "1": { "total_solde": 100000, ... },
    "2": { "total_solde": 100000, ... }
  }
}
```

---

### Analytics (Analyses)

#### 1. **GET /analytics/kpis**
Indicateurs clés de performance (KPIs)

```bash
GET /api/v1/analytics/kpis?exercice=2024
```

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "classe": "1",
      "nb_comptes": 15,
      "total_debit": 100000,
      "total_credit": 50000,
      "total_solde": 50000
    }
  ],
  "totals": {
    "total_debit": 500000,
    "total_credit": 300000,
    "total_solde": 200000
  },
  "exercice": "2024"
}
```

---

#### 2. **GET /analytics/analysis**
Analyse complète (CA mensuel, top clients/fournisseurs, coûts)

```bash
GET /api/v1/analytics/analysis?exercice=2024
```

**Response**:
```json
{
  "success": true,
  "exercice": "2024",
  "ca_mensuel": [
    {
      "mois": "2024-01",
      "ca_mensuel": 18500
    }
  ],
  "top_clients": [
    {
      "client": "Client A",
      "compte_num": "411100",
      "montant": 25000
    }
  ],
  "top_fournisseurs": [
    {
      "fournisseur": "Fournisseur X",
      "compte_num": "401100",
      "montant": 8500
    }
  ],
  "structure_couts": {
    "achats": 50000,
    "salaires": 60000,
    "frais_bancaires": 1200
  },
  "ca_total": 250000
}
```

---

## 🔐 Authentification

### Endpoints Publics
- `/accounting/years`
- `/accounting/balance`
- `/accounting/accounts`
- `/accounting/ledger`
- `/accounting/sig`
- `/analytics/kpis`
- `/analytics/analysis`

### Endpoints Privés (requièrent JWT)
*(À ajouter dans Phase 3)*
- `POST /admin/import-fec`
- `POST /users/change-password`

---

## 📊 Codes de Réponse

| Code | Meaning |
|------|---------|
| 200 | ✅ Succès |
| 400 | ❌ Paramètre invalide |
| 401 | ❌ Non authentifié (JWT manquant) |
| 403 | ❌ Non autorisé |
| 404 | ❌ Route non trouvée |
| 500 | ❌ Erreur serveur |

---

## 📈 Exemples cURL

```bash
# Récupérer les années
curl -X GET "https://compta.sarlatc.com/api/v1/accounting/years"

# Récupérer la balance
curl -X GET "https://compta.sarlatc.com/api/v1/accounting/balance?exercice=2024"

# Récupérer les KPIs
curl -X GET "https://compta.sarlatc.com/api/v1/analytics/kpis?exercice=2024"

# Avec filtre sur comptes classe 4
curl -X GET "https://compta.sarlatc.com/api/v1/accounting/accounts?exercice=2024&classe=4"
```

---

## 🔄 Migration depuis les anciens endpoints

### Avant (Legacy)
```
GET /annees-simple.php
GET /balance-simple.php?exercice=2024
GET /comptes-simple.php?exercice=2024
GET /kpis-simple.php?exercice=2024
GET /sig-simple.php?exercice=2024
GET /analyse-simple.php?exercice=2024
```

### Après (v1)
```
GET /api/v1/accounting/years
GET /api/v1/accounting/balance?exercice=2024
GET /api/v1/accounting/accounts?exercice=2024
GET /api/v1/analytics/kpis?exercice=2024
GET /api/v1/accounting/sig?exercice=2024
GET /api/v1/analytics/analysis?exercice=2024
```

**Breaking changes**: Aucun (endpoints legacy continuent de fonctionner)

---

## 🚀 Versioning

- **v1**: Actuel (2026-01-15)
- **v2**: Planifié (inclura les uploads, imports, admin)

---

## ❓ FAQ

**Q: Comment changer d'exercice?**
R: Ajouter le paramètre `?exercice=2024` à chaque requête

**Q: Peux-je combiner `page` et `limit`?**
R: Oui! Exemple: `?exercice=2024&page=2&limit=50`

**Q: Quels sont les filtres disponibles?**
R: Voir chaque endpoint. Actuellement: `exercice`, `classe`, `page`, `limit`

---

**Dernière mise à jour**: 15 janvier 2026  
**Statut**: 🟢 Production
