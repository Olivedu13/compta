# 🔧 Guide Développeur - Architecture & Maintenance

## 📋 Table des Matières

1. [Architecture Générale](#architecture-générale)
2. [Stack Technique](#stack-technique)
3. [Structure du Projet](#structure-du-projet)
4. [Phase 1: Parsing FEC](#phase-1-parsing-fec)
5. [Phase 2: Analyse Cashflow](#phase-2-analyse-cashflow)
6. [Phase 3: APIs REST](#phase-3-apis-rest)
7. [Phase 4: Dashboard Refactorisation](#phase-4-dashboard-refactorisation)
8. [Phase 5: SIGPage Refactorisation](#phase-5-sigpage-refactorisation)
9. [Déploiement & Production](#déploiement--production)

---

## Architecture Générale

### Vue d'Ensemble

```
┌─────────────────────────────────────────────────────────┐
│                   FRONTEND (React/Vite)                 │
│  Dashboard | SIGPage | ImportPage | BalancePage         │
└────────────────────┬────────────────────────────────────┘
                     │ API REST JSON
┌────────────────────▼────────────────────────────────────┐
│              BACKEND (PHP Router)                       │
│  /api/tiers | /api/cashflow | /api/balance              │
└────────────────────┬────────────────────────────────────┘
                     │ SQL
┌────────────────────▼────────────────────────────────────┐
│            DATABASE (SQLite)                            │
│  11,617 écritures | 125 tiers | 7 journaux              │
└─────────────────────────────────────────────────────────┘
```

### Flux de Données

```
1. Upload FEC
   ↓
2. Parse TAB → 18 colonnes
   ↓
3. Valide structure + montants
   ↓
4. Insert DB (batch)
   ↓
5. Recalcule balances
   ↓
6. Retourne API JSON
   ↓
7. React affiche dans Dashboard/SIGPage
```

---

## Stack Technique

| Couche | Technologie | Version |
|--------|-------------|---------|
| **Frontend** | React | 18+ |
| **Build** | Vite | 5.0+ |
| **UI Components** | Material-UI | 5.0+ |
| **Graphiques** | Recharts | 2.10+ |
| **Backend** | PHP | 7.4+ |
| **Database** | SQLite | 3.x |
| **Server** | Apache/Nginx | - |
| **Testing** | Bash/cURL | - |

### Dépendances Critiques

**Frontend `package.json`:**
```json
{
  "dependencies": {
    "react": "^18.2.0",
    "react-dom": "^18.2.0",
    "@mui/material": "^5.14.0",
    "@mui/icons-material": "^5.14.0",
    "recharts": "^2.10.0",
    "axios": "^1.6.0"
  }
}
```

**Backend PHP:**
- `PDO` (SQLite driver)
- Standard library (no external deps)

---

## Structure du Projet

```
compta/
├── backend/
│   ├── config/
│   │   ├── Database.php       # SQLite connection
│   │   ├── Logger.php         # Logging utility
│   │   ├── Router.php         # REST routing
│   │   └── schema.sql         # DB schema
│   ├── models/                # Data models (empty, using raw SQL)
│   ├── services/
│   │   ├── ImportService.php  # FEC parsing
│   │   └── SigCalculator.php  # SIG computation
│   └── logs/
│
├── frontend/
│   ├── src/
│   │   ├── components/
│   │   │   ├── AdvancedAnalytics.jsx
│   │   │   ├── AnalysisSection.jsx
│   │   │   ├── KPICard.jsx
│   │   │   ├── Layout.jsx
│   │   │   ├── UploadZone.jsx
│   │   │   └── dashboard/        # Phase 4
│   │   │       ├── index.js
│   │   │       ├── TiersAnalysisWidget.jsx
│   │   │       ├── CashflowAnalysisWidget.jsx
│   │   │       ├── SIGCascadeCard.jsx
│   │   │       └── SIGDetailedView.jsx
│   │   ├── pages/
│   │   │   ├── Dashboard.jsx
│   │   │   ├── SIGPage.jsx      # Phase 5 refactored
│   │   │   ├── ImportPage.jsx
│   │   │   └── BalancePage.jsx
│   │   ├── services/
│   │   │   └── api.js           # API client
│   │   ├── theme/
│   │   │   └── theme.js
│   │   ├── App.jsx
│   │   └── index.jsx
│   ├── package.json
│   ├── vite.config.js
│   └── index.html
│
├── public_html/
│   ├── api/
│   │   ├── index.php            # Router entry point
│   │   └── simple-import.php    # Legacy import
│   ├── analyse-simple.php
│   └── ...
│
├── test-e2e.sh                  # E2E tests (Phase 6)
├── API_DOCUMENTATION.md         # API docs (Phase 6)
├── USER_GUIDE.md               # User guide (Phase 6)
├── DEVELOPER_GUIDE.md          # This file (Phase 6)
└── compta.db                   # SQLite database
```

---

## Phase 1: Parsing FEC

### ImportService.php

**Location:** `/workspaces/compta/backend/services/ImportService.php`

**Responsabilités:**
1. Parse fichier TAB-delimited
2. Valide 18 colonnes obligatoires
3. Insert batch dans DB
4. Retourne statistiques import

**Flux Parsing:**

```php
// 1. Ouvre fichier
$handle = fopen($filePath, 'r');

// 2. Lit header (1ère ligne)
$headers = fgetcsv($handle, 0, "\t");

// 3. Valide colonnes
$colonnes_requises = [
    'JournalCode', 'JournalLib', 'EcritureNum',
    'EcritureDate', 'CompteNum', 'CompteLib',
    'Debit', 'Credit', ...
];

// 4. Parse écritures (batch par 1000)
while (($row = fgetcsv($handle, 0, "\t")) !== false) {
    $ecriture = [];
    foreach ($headers as $idx => $col) {
        $ecriture[$col] = $row[$idx];
    }
    // Insert ou batch
}

// 5. Retourne résultat
return [
    'success' => true,
    'ecritures_count' => 11617,
    'tiers_count' => 125,
    'balance' => '€0.00'
];
```

**Validation:**
- ✅ 18 colonnes exactes
- ✅ Dates format AAAA-MM-DD
- ✅ Montants numériques
- ✅ Balance = 0 (Débit = Crédit)

**Performance:**
- 11,617 écritures importées en **0.34 secondes**

---

## Phase 2: Analyse Cashflow

### SigCalculator.php

**Location:** `/workspaces/compta/backend/services/SigCalculator.php`

**Responsabilités:**
1. Calcule indicateurs SIG
2. Agrège par journal/période
3. Retourne statistiques

**Indicateurs SIG:**

```
1. Ventes (CA)
   = SUM(montants journal VE)

2. Marge Brute
   = CA - Achats

3. Charges Opérationnelles
   = SUM(charges) - CA

4. Résultat d'Exploitation
   = Marge - Charges

5. Résultat Financier
   = Éléments financiers

6. Résultat Net
   = Résultat Exploitation + Financier
```

**Exemple SQL:**

```sql
SELECT 
    SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END) as total_debit,
    SUM(CASE WHEN credit > 0 THEN credit ELSE 0 END) as total_credit,
    (SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END) - 
     SUM(CASE WHEN credit > 0 THEN credit ELSE 0 END)) as solde
FROM ecritures
WHERE journal_code = 'VE'
AND strftime('%Y', date_ecriture) = ?
```

---

## Phase 3: APIs REST

### Router.php

**Location:** `/workspaces/compta/backend/config/Router.php`

**Routes Disponibles:**

#### 1. GET /api/tiers

**Description:** Liste paginée des tiers

**Paramètres:**
```
- exercice: AAAA (optionnel, défaut: année courante)
- limit: 50 (defaut: 25)
- offset: 0
- tri: montant|nom|ecritures
```

**Réponse Success (200):**
```json
{
  "success": true,
  "pagination": {
    "total": 125,
    "limit": 25,
    "offset": 0,
    "pages": 5
  },
  "tiers": [
    {
      "numero": "08000001",
      "libelle": "GOLDMAN SACHS",
      "debit_total": 450000.50,
      "credit_total": 380000.25,
      "solde": 70000.25,
      "ecritures_total": 272,
      "ecritures_lettrees": 195
    }
  ]
}
```

#### 2. GET /api/tiers/:numero

**Description:** Détail d'un tiers

**Réponse:**
```json
{
  "success": true,
  "tiers": {
    "numero": "08000001",
    "libelle": "GOLDMAN SACHS",
    "statistiques": {...}
  },
  "ecritures": [
    {
      "id": 1,
      "date": "2024-01-15",
      "journal": "VE",
      "compte": "411",
      "libelle": "Facture F001",
      "debit": 1000,
      "credit": 0,
      "solde_tiers": 1000
    }
  ]
}
```

#### 3. GET /api/cashflow

**Description:** Cashflow par période et journal

**Paramètres:**
```
- exercice: AAAA
- periode: mois|trimestre
```

**Réponse:**
```json
{
  "success": true,
  "stats_globales": {
    "total_entrees": 2500000,
    "total_sorties": 2300000,
    "solde_net": 200000
  },
  "par_periode": [
    {
      "periode": "2024-01",
      "entrees": 210000,
      "sorties": 195000,
      "solde": 15000
    }
  ],
  "par_journal": [
    {
      "journal": "VE",
      "libelle": "Ventes",
      "entrees": 2400000,
      "sorties": 50000
    }
  ]
}
```

#### 4. GET /api/cashflow/detail/:journal

**Description:** Détail d'un journal

**Réponse:**
```json
{
  "success": true,
  "journal": "VE",
  "stats": {
    "total_entrees": 2400000,
    "total_sorties": 50000,
    "solde": 2350000,
    "ecritures_count": 2869,
    "jours_actifs": 189
  },
  "top_comptes": [
    {
      "compte": "411",
      "libelle": "Clients",
      "debit": 2400000,
      "credit": 50000
    }
  ]
}
```

### Intégration Frontend

**Fichier:** `/workspaces/compta/frontend/src/services/api.js`

```javascript
// GET /api/tiers
export async function getTiers(params = {}) {
  const response = await apiClient.get('/api/tiers', { params });
  return response.data;
}

// GET /api/cashflow
export async function getCashflow(params = {}) {
  const response = await apiClient.get('/api/cashflow', { params });
  return response.data;
}

// Usage dans component
const { data, loading } = useFetch(() => 
  getTiers({ exercice: 2024, limit: 25 })
);
```

---

## Phase 4: Dashboard Refactorisation

### Nouveaux Composants

#### 1. TiersAnalysisWidget.jsx

**Affiche:** Tableau paginé des tiers

**Features:**
- Pagination 5/10/25/50 lignes
- Recherche en temps réel
- 3 options de tri (montant/nom/ecritures)
- Statut lettrage (chips colorées)

**Code Clé:**
```jsx
const [page, setPage] = useState(0);
const [rowsPerPage, setRowsPerPage] = useState(25);
const [search, setSearch] = useState('');
const [sortBy, setSortBy] = useState('montant');

const filtered = tiers.filter(t => 
  t.numero.includes(search) || t.libelle.includes(search)
);

const sorted = [...filtered].sort((a, b) => {
  if (sortBy === 'montant') return b.solde - a.solde;
  if (sortBy === 'nom') return a.libelle.localeCompare(b.libelle);
  if (sortBy === 'ecritures') return b.ecritures_total - a.ecritures_total;
});
```

#### 2. CashflowAnalysisWidget.jsx

**Affiche:** 4 onglets d'analyse cashflow

**Onglets:**
1. **Par Période** - Bar chart entrees vs sorties
2. **Par Journal** - Pie chart distribution + table
3. **Détail Journal** - Deep dive avec top 5 comptes
4. **Top Comptes** - Tous les comptes du journal

**État Management:**
```jsx
const [selectedJournal, setSelectedJournal] = useState('VE');
const [activeTab, setActiveTab] = useState(0);

// Charge data au mount et au changement de journal
useEffect(() => {
  if (activeTab === 2) {
    getCashflowDetail(selectedJournal);
  }
}, [activeTab, selectedJournal]);
```

#### 3. SIGCascadeCard.jsx

**Affiche:** Une carte SIG avec variance

**Propriétés:**
```jsx
<SIGCascadeCard
  titre="Chiffre d'Affaires"
  montant={2400000}
  precedent={2200000}
  icon={<TrendingUpIcon />}
  color="success"
/>
```

---

## Phase 5: SIGPage Refactorisation

### Nouvelle Interface (4 Onglets)

**Fichier:** `/workspaces/compta/frontend/src/pages/SIGPage.jsx`

```jsx
const SIGPage = () => {
  const [activeTab, setActiveTab] = useState(0);
  
  const tabs = [
    { label: '🎯 Cascade SIG', icon: <CascadeIcon /> },
    { label: '📈 Graphiques', icon: <ChartIcon /> },
    { label: '📋 Détails', icon: <TableIcon /> },
    { label: '💰 Cashflow', icon: <MoneyIcon /> }
  ];
  
  return (
    <Box>
      <Tabs value={activeTab} onChange={(e, v) => setActiveTab(v)}>
        {tabs.map(tab => <Tab label={tab.label} icon={tab.icon} />)}
      </Tabs>
      
      {activeTab === 0 && <SIGCascadeView />}
      {activeTab === 1 && <SIGChartsView />}
      {activeTab === 2 && <SIGDetailedView />}
      {activeTab === 3 && <CashflowComparisonView />}
    </Box>
  );
};
```

### Onglet Cashflow

**Intègre données Phase 3:**

```jsx
const CashflowComparisonView = () => {
  const { cashflow } = useCashflow();
  
  return (
    <Box>
      <Grid container spacing={2}>
        {/* 4 KPIs */}
        <KPICard 
          title="Total Entrées"
          value={cashflow.stats_globales.total_entrees}
          color="success"
        />
      </Grid>
      
      {/* Table par Journal */}
      <TableContainer>
        <Table>
          {cashflow.par_journal.map(j => (
            <TableRow>
              <TableCell>{j.journal}</TableCell>
              <TableCell>${j.entrees}</TableCell>
              <TableCell>${j.sorties}</TableCell>
            </TableRow>
          ))}
        </Table>
      </TableContainer>
    </Box>
  );
};
```

---

## Déploiement & Production

### Pré-requis

```bash
# Vérifier versions
php --version          # 7.4+
node --version        # 18+
npm --version         # 9+
```

### Build Frontend

```bash
cd /workspaces/compta/frontend

# Installer dépendances
npm install

# Build production
npm run build
# Crée: dist/

# Ou: Développement
npm run dev
# Server: http://localhost:5173
```

### Configurer Backend

```bash
# Créer DB vide si n'existe pas
php -r "new PDO('sqlite:compta.db');"

# Créer tables
sqlite3 compta.db < backend/config/schema.sql

# Importer FEC
POST /api/import-fec (multipart/form-data file)
```

### Apache Configuration

```apache
<VirtualHost *:80>
    ServerName compta.local
    DocumentRoot /workspaces/compta/public_html
    
    <Directory /workspaces/compta/public_html>
        AllowOverride All
        Require all granted
        
        # Redirect frontend routes to index.html
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteBase /
            RewriteRule ^index\.html$ - [L]
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule . /index.html [L]
        </IfModule>
    </Directory>
</VirtualHost>
```

### Vérification Post-Déploiement

```bash
# Test health check
curl http://localhost/api/health

# Test import
curl -X POST -F "file=@test.fec" http://localhost/api/import-fec

# Vérifier DB
sqlite3 compta.db "SELECT COUNT(*) FROM ecritures;"
```

---

## 🧪 Tests & Validation

### E2E Tests

**Fichier:** `/workspaces/compta/test-e2e.sh`

```bash
# Exécuter tous les tests
bash test-e2e.sh

# Résultats attendus
✅ Health Check
✅ GET /api/tiers
✅ GET /api/tiers/:numero
✅ GET /api/cashflow
✅ GET /api/cashflow/detail/:journal
✅ Data Integrity (balance = 0)
✅ Performance (<1s)
```

### Tests Locaux

```bash
# Curl individual endpoint
curl "http://localhost/api/tiers?limit=5&offset=0"

# Format response
curl "http://localhost/api/tiers" | jq '.tiers[0]'

# Test avec exercice
curl "http://localhost/api/tiers?exercice=2024&limit=10"
```

---

## 📝 Maintenance & Troubleshooting

### Problèmes Courants

**1. Balance incorrecte après import**
```bash
# Vérifier
sqlite3 compta.db "SELECT SUM(debit), SUM(credit) FROM ecritures;"
# Doit être égal

# Réinitialiser
rm compta.db
# Réimporter FEC
```

**2. Lenteur API**
```bash
# Vérifier indexes
sqlite3 compta.db ".indexes"

# Ajouter index
sqlite3 compta.db "CREATE INDEX idx_journal ON ecritures(journal_code);"
sqlite3 compta.db "CREATE INDEX idx_tiers ON ecritures(numero_tiers);"
```

**3. Frontend ne se build pas**
```bash
# Nettoyer cache
rm -rf node_modules package-lock.json
npm install
npm run build
```

---

**Version:** 1.0  
**Dernière mise à jour:** 2024-01-15  
**Auteur:** Compta Dev Team
