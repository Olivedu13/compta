# Fonctionnalités Implémentées - Compta Bijouterie

## 📊 Dashboard Amélioré

### Sélection d'Année
- **Dropdown Année**: Sélectionnez l'année à analyser (charge depuis `/api/annees`)
- **Affichage dynamique**: Les KPI et SIG se mettent à jour selon l'année choisie
- **Chargement**: La liste des années disponibles avec le nombre d'écritures

### Mode Comparaison
- **Bouton "Comparer les années"**: Ouvre un dialog pour sélectionner plusieurs années
- **Sélection multiple**: Checkboxes pour chaque année disponible (min. 2 requises)
- **Vue comparative**: 
  - Graphiques en barres côte à côte pour chaque KPI
  - Tableau de comparaison SIG avec toutes les années
  - Format devise EUR avec 2 décimales

## 📁 Import Intelligent

### Détection Automatique
- Auto-détecte le type de fichier (Excel, FEC, Archive)
- Emojis pour meilleure UX (📊 Excel, 📄 FEC, 📦 Archive)
- Support: `.xlsx`, `.xls`, `.txt`, `.csv`, `.tar`, `.tar.gz`

### Gestion des Conflits
- **Vérification préalable**: Avant import, vérifie si l'année existe
- **Dialog Confirmation**: "L'année XXXX contient déjà des données"
- **Options**:
  - **Annuler**: Abandonne l'import
  - **Remplacer les données**: Vide l'année puis importe

### Processus Import
1. Détecte type via extension
2. Appelle `/api/annee/:year/exists` pour vérifier
3. Si existe → Dialog confirmation
4. Si "Remplacer" → Appelle `/api/annee/:year/clear`
5. Lance l'import selon type
6. Recalcule la balance

## 🔧 Routes API Nouvelles

### Gestion des Années
```
GET  /api/annees
└─ Liste toutes les années avec nombre d'écritures
└─ Réponse: [{ annee: 2024, ecritures: 150 }, ...]

GET  /api/annee/:year/exists
└─ Vérifie si une année contient des données
└─ Réponse: { exists: true/false }

POST /api/annee/:year/clear
└─ Supprime tous les enregistrements d'une année
└─ Réponse: { success: true, deleted: 150 }
```

### Comparaison
```
GET /api/comparaison/annees?annees=2024,2025
└─ Compare deux ou plusieurs années
└─ Réponse: {
    kpis: { stock_or: { 2024: 1000, 2025: 1200 }, ... },
    cascade: { Ventes: { 2024: 5000, 2025: 5500 }, ... }
  }
```

## 📝 Services API Frontend

Nouvelles méthodes dans `api.js`:
- `getAnnees()` - Liste des années
- `getAnneeExists(annee)` - Vérifier existence
- `clearAnnee(annee)` - Supprimer données
- `getComparaison(annees)` - Comparer années

## 🎨 Composants React Modifiés

### Dashboard.jsx
- État: `annees`, `compareMode`, `selectedYears`, `compareData`, `compareOpen`
- Effets: `loadAnnees()` charge au démarrage
- Handlers: `handleCompareOpen()`, `handleYearToggle()`, `handleCompareExecute()`
- Vue comparaison: Graphiques + Tableau SIG

### UploadZone.jsx
- État ajouté: `overrideOpen`, `pendingUpload`
- Préapproval: Vérifie l'année avant import
- Dialog: "Année existante" avec option remplacement
- Handlers: `handleClearAndReplace()`, `handleCancelOverride()`

### Layout.jsx
- AppBar supprimée (simplification UI)
- Navigation drawer nettoyée

## 📋 Flux Utilisateur

### Analyser une Année
1. Va au Dashboard
2. Sélectionne l'année dans le dropdown
3. Les KPI et SIG se mettent à jour
4. Voit: Stocks, Trésorerie, Cascade SIG, Détails SIG

### Importer des Données
1. Va à Import FEC/Excel
2. Glisse-dépose le fichier
3. Si l'année existe → Dialog "Remplacer?"
4. Import procède ou s'annule
5. Balance recalculée auto

### Comparer Années
1. Dashboard → "Comparer les années"
2. Coche 2+ années
3. Clique "Comparer"
4. Voir graphiques et tableau côte à côte
5. Cliquer "Retour" pour revenir à single-year view

## ✅ Validations

- Minimum 2 années pour comparaison
- Format devise EUR sur tous les nombres
- Gestion erreurs au chaque étape
- Snackbars pour feedback utilisateur
- States de chargement (CircularProgress)

## 🚀 Déploiement

Build: `npm run build` (1,357 kB après minification)
Upload: `bash upload-direct.sh`

Fichiers déployés:
- Frontend: `public_html/assets/index.js`
- Backend API: `public_html/api/index.php`
- Services: `backend/services/*.php`
- Config: `backend/config/*.php`

