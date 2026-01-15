# 📊 WORKFLOW COMPLET D'ANALYSE FEC & VÉRIFICATION SIG

## Vue d'Ensemble

Ce document décrit le **workflow d'expert comptable** implémenté pour :
1. **Analyser** les fichiers FEC reçus (détection format, nettoyage, validation)
2. **Vérifier ensemble** les formules SIG et les données utilisées
3. **Importer** les données dans la base de manière robuste
4. **Valider** les résultats avant publication aux tableaux de bord

---

## PHASE 1 : ANALYSE FEC (FecAnalyzer)

### Objectif
Traiter le FEC **comme un expert comptable** :
- ✅ Tolérer les variations de format (casse, séparateurs, encodage)
- ✅ Corriger les erreurs mineures (dates, montants)
- ✅ Valider l'équilibre comptable fondamental
- ✅ Détector les anomalies AVANT import

### Composants Implémentés

#### Backend : `FecAnalyzer.php`
```php
$analyzer = new FecAnalyzer();
$analysis = $analyzer->analyze('/path/to/file.txt');
```

**Étapes d'Analyse :**

1. **Détection du Format**
   - Test des séparateurs courants (TAB, |, ,, ;)
   - Calcul du score de cohérence (écart-type colonnes)
   - Sélection du séparateur optimal

2. **Extraction et Normalisation de l'En-tête**
   - Recherche de la ligne d'en-tête (signature: JournalCode, CompteNum, etc.)
   - Mappage des colonnes variantes vers noms standard
   - Détection colonnes manquantes ou custom

3. **Analyse des Données**
   - Lecture ligne par ligne (streaming mémoire)
   - Parsing des montants (tolérance formats: 1.000,50 vs 1000.50)
   - Parsing des dates (tolérance formats: DD/MM/YYYY vs YYYY-MM-DD)
   - Collecte des statistiques:
     - Nombre comptes uniques
     - Nombre journaux uniques
     - Total débits vs crédits
     - Plage de dates
     - Exercice détecté

4. **Détection des Anomalies**
   - **CRITIQUES** (bloquent import):
     - Déséquilibre comptable > 0.1%
     - Trop de lignes en erreur (> 5%)
     - Aucune donnée valide
   
   - **WARNINGS** (non-bloquant):
     - Léger déséquilibre comptable (centimes)
     - Faible volume données
     - Devise non-EUR

5. **Recommandations**
   - Actions de nettoyage suggérées
   - Résumé qualité données

### Résultat de l'Analyse

```json
{
  "status": "success",
  "file_info": {
    "size_bytes": 524288,
    "total_lines": 2500,
    "data_lines": 2498
  },
  "format": {
    "separator": "\t",
    "separator_name": "TAB",
    "header_line_idx": 0,
    "encoding": "UTF-8"
  },
  "headers": {
    "headers": {
      "JournalCode": { "original_name": "JournalCode", ... },
      "CompteNum": { "original_name": "Compte_Num", ... }
      // ... (18 colonnes FEC)
    },
    "total_columns": 18
  },
  "data_statistics": {
    "total_rows": 2498,
    "valid_rows": 2496,
    "error_rows": 2,
    "total_debit": 156740.50,
    "total_credit": 156740.50,
    "balance_difference": 0.00,
    "is_balanced": true,
    "accounts_count": 127,
    "journals_count": 8,
    "exercice_detected": 2024,
    "date_range": {
      "min": "2024-01-01",
      "max": "2024-12-31"
    },
    "devise_detected": "EUR"
  },
  "anomalies": {
    "critical": [],
    "warnings": []
  },
  "recommendations": {
    "can_import": true,
    "suggested_actions": [],
    "cleaning_needed": [],
    "summary": "127 comptes, 8 journaux, 2496 lignes valides | Débit: 156740.50 € = Crédit: 156740.50 € (diff: 0.00 €)"
  },
  "ready_for_import": true,
  "exercice_detected": 2024
}
```

### Frontend : `FecAnalysisDialog.jsx`

Composant React affichant:
- 📄 Fichier et format détecté
- 📊 Statistiques comptables (débits, crédits, équilibre)
- ⚠️ Anomalies critiques (s'il y en a)
- ⚠️ Avertissements (non-bloquants)
- 📋 Colonnes détectées (avec validation)
- 💡 Recommandations

**Interactivité :**
- Bouton "Re-analyser" (si doute)
- Bouton "Importer le FEC" (activé si `ready_for_import = true`)

### API Endpoint

```
POST /api/analyze/fec
Content-Type: multipart/form-data

Requête:
- file: <binary FEC file>

Réponse (200):
{
  "success": true,
  "data": { /* analysis object */ }
}

Erreur (500):
{
  "error": "Description erreur",
  "debug": { ... }
}
```

---

## PHASE 2 : VÉRIFICATION FORMULES SIG

### Objectif
Passer en revue **ensemble** :
1. Les comptes utilisés pour chaque calcul
2. Les formules mathématiques
3. La pertinence pour le contexte bijouterie
4. Les valeurs résultantes

### Composant : `SigFormulaVerifier.jsx`

**Affiche pour chaque SIG :**

#### 1️⃣ Marge de Production (MP)
```
Formule: (70 + 71 + 72) - (601 + 602 ± 603)

Comptes Addition:
- 70: Ventes marchandises → Bijoux fabriqués/vendus
- 71: Production stockée → Pièces en cours/stock travail
- 72: Production immobilisée → Éléments patrimoine

Comptes Soustraction:
- 601: Achats matières premières → Or, argent, pierres
- 602: Achats fournitures → Composants, outils
- 603: Variation stocks → (signe: + = augmentation stock)

Validation:
✓ Comptes 70,71,72 doivent être crédités (produits)
✓ Comptes 601,602 débités (charges)
✓ Variation 603 inclut stock initial ET final
✓ Bijouterie: vérifier valorisation stocks métaux précieux

Préoccupations Métier:
⚠️ Prix d'achat vs prix marché (métaux volatiles)
⚠️ Variation stock doit inclure tous en-cours
⚠️ Attention aux déchets transformation
```

#### 2️⃣ Valeur Ajoutée (VA)
```
Formule: MP - (61 + 62)

VA représente la RICHESSE CRÉÉE par l'entreprise.

Validation:
✓ VA doit être significative pour bijouterie (c'est le métier!)
✓ Vérifier que sous-traitance n'est pas excessive

Préoccupations:
⚠️ Si VA faible: peu de valeur créée en-house
⚠️ Bijouterie luxe: VA doit refléter création artistique
```

#### 3️⃣ EBE / EBITDA
```
Formule: VA + 74 - (63 + 64 + 68*)

- 63: Impôts et taxes (CVAE, taxes atelier)
- 64: Charges de personnel (IMPORTANT: apprentis bijoutiers!)
- 68*: ATTENTION: que charges exceptionnelles (pas amortissements)
- 74: Produits exceptionnels (or de récupération)

Validation:
✓ EBE positif = entreprise génère cash opérationnel
✓ Doit être positif pour bijouterie (sinon problème métier)
✓ Charges personnel (64) significatives (apprentissage)

Préoccupations:
⚠️ Comparer VA vs 64 (part personnel)
⚠️ EBE négatif = revoir modèle économique
⚠️ Impôts/taxes locales (atelier peut être soumis)
```

#### 4️⃣ Résultat d'Exploitation (RE)
```
Formule: EBE - 681 (Amortissements et provisions)

681 = charge NON-CASH (important pour cash flow!)

Validation:
✓ RE positif = métier rentable en soi
✓ Amortissements cohérents avec immobilisations

Préoccupations:
⚠️ Durée amortissement outils bijouterie: 5-10 ans
⚠️ RE < 0 mais EBE > 0: amortissements excessifs?
```

#### 5️⃣ Résultat Financier (RF)
```
Formule: 69 (Intérêts) - 76 (Produits financiers)

- 69: Frais financiers (intérêts emprunts exploitation/investissement)
- 76: Produits financiers (rare pour atelier)

Validation:
✓ RF généralement négatif (coût du financement)
✓ Normal si entreprise investit

Préoccupations:
⚠️ Si RF très négatif: vérifier taux et montants emprunts
⚠️ Bijouterie: crédit fournisseurs stocks or peut être important
```

#### 6️⃣ Résultat Net (RN)
```
Formule: RE + RF - 69 (Impôt si applicable)

Validation:
✓ RN positif = bénéfice distribué/capitalisé
✓ Doit être positif et proportionné au travail patron

Préoccupations:
⚠️ Comparer RN avec salaire patron (si auto-entrepreneur)
⚠️ Bijouterie souvent micro-entreprise => pas IS
⚠️ Vérifier cohérence RN avec trésorerie réelle
```

### Fonctionnalités

1. **Affichage Accordéons**
   - Chaque SIG dans un panneau repliable
   - Formule mathématique en évidence
   - Tableau des comptes additionnés
   - Tableau des comptes soustraits

2. **Points de Validation**
   - Checklist d'éléments à vérifier
   - Codifiés ✓ (done) ou ⚠️ (concern)

3. **Contexte Bijouterie**
   - Pour chaque compte: utilisation spécifique métier
   - Préoccupations métier adaptées

4. **Bouton "Valider la Formule"**
   - Ouvre dialog de confirmation
   - Permet ajouter notes (optionnel)
   - Sauvegarde état validation

### API Intégration

```javascript
// Dans ImportPage.jsx
<SigFormulaVerifier
  analysisData={analysis}
  onFormulaValidation={(formulaId, notes) => {
    // Sauvegarde validation côté frontend
    // Optionnel: envoie au backend
    console.log(`Formule ${formulaId} validée:`, notes);
  }}
/>
```

---

## PHASE 3 : IMPORT FEC

### Endpoint API

```
POST /api/import/fec
Content-Type: multipart/form-data

Requête:
- file: <binary FEC file>

Processus:
1. Lance FecAnalyzer (validation préalable)
2. Si analyse OK → continue
3. Scanne comptes FEC, crée comptes racine manquants
4. Import batch (500 lignes par batch)
5. Agrège balance (GROUP BY compte, SUM débit, SUM crédit)
6. Retourne rapport d'import

Réponse (200):
{
  "success": true,
  "data": {
    "count": 2496,
    "errors": 0,
    "accounts_created": 127,
    "message": "2496 écritures FEC importées (127 comptes créés)"
  }
}
```

### Robustesse

**Tolérances implémentées :**
- Format FEC variable (séparateur, casse)
- Dates variantes (17/01/2024 vs 2024-01-17)
- Montants variantes (1.000,50 vs 1000.50)
- Colonnes manquantes (champs optionnels)
- Métadonnées avant en-tête (ignorées)

**Sécurité :**
- PDO prepared statements (injection SQL)
- Validation montants > 0
- Vérification compte racine (3 premiers chiffres)
- Logs structurées JSON

---

## PHASE 4 : AFFICHAGE TABLEAUX DE BORD

### SIG Page

Après import réussi, les données alimentent :

1. **Cascade Visuelle (Waterfall Chart)**
   - MP → VA → EBE → RE → RN

2. **Cartes Détail**
   - Pour chaque SIG: montant, tendance, +/- année précédente

3. **Détail Comptes**
   - Balance détaillée pour validation croisée

### Formules SigCalculator.php

```php
$calculator = new SigCalculator(2024);

// Charge balance en cache mémoire
$sig = $calculator->calculateSIG();

// Retourne:
[
  'marge_production' => 45000.50,
  'valeur_ajoutee' => 30000.00,
  'ebe' => 15000.00,
  'resultat_exploitation' => 10000.00,
  'resultat_financier' => -500.00,
  'resultat_net' => 9500.00
]
```

---

## Workflow Utilisateur Complet

```
1. Télécharger FEC
   ↓
2. [PHASE 1] Analyse FEC
   - Upload fichier
   - FecAnalyzer: détecte format, valide équilibre
   - Affichage FecAnalysisDialog
   - Vérification ensemble des anomalies
   ↓
3. [PHASE 2] Vérification Formules SIG
   - SigFormulaVerifier: affiche formules
   - Pour chaque SIG: vérifier comptes, formule, pertinence métier
   - Cocher validation
   ↓
4. [PHASE 3] Clic "Importer le FEC"
   - ImportService: lance import
   - Batch insert des écritures
   - Agrégation balance
   - Rapport d'import
   ↓
5. [PHASE 4] Affichage Dashboard
   - SIG calculée et affichée
   - Balance détaillée
   - Vérification croisée résultats
```

---

## Points Clés de Validation Ensemble

### Questions à se poser:

#### 1. Analyse FEC
- ✓ Le séparateur détecté est-il correct?
- ✓ L'en-tête contient-il toutes les colonnes FEC?
- ✓ L'équilibre (débit = crédit) est-il respecté?
- ✓ L'exercice détecté est-il le bon?
- ✓ Y a-t-il des anomalies bloquantes?

#### 2. Formules SIG
- ✓ Les comptes utilisés correspondent-ils à la bijouterie?
- ✓ Les montants sont-ils présents dans la balance?
- ✓ Les signes comptables sont-ils corrects?
- ✓ Le résultat final est-il cohérent?
- ✓ Comparaison année N vs N-1?

#### 3. Import
- ✓ Nombre d'écritures importées = nombre attendu?
- ✓ Tous les comptes créés sont-ils valides?
- ✓ La balance agrégée correspond-elle au FEC original?

#### 4. Résultats
- ✓ Le dashboard affiche les bonnes valeurs?
- ✓ Les KPI sont-elles cohérentes?
- ✓ Les graphiques sont-ils pertinents?

---

## Fichiers Implémentés

```
Backend:
- /backend/services/FecAnalyzer.php         (1200+ lignes)
- /backend/services/ImportService.php       (modifié: +20 lignes)
- /public_html/api/index.php               (modifié: +50 lignes)

Frontend:
- /frontend/src/components/FecAnalysisDialog.jsx      (450+ lignes)
- /frontend/src/components/SigFormulaVerifier.jsx     (600+ lignes)
```

---

## Utilisation Immédiate

### Backend Test
```bash
cd /workspaces/compta
php -r "
  require 'backend/services/FecAnalyzer.php';
  \$analyzer = new \App\Services\FecAnalyzer();
  \$result = \$analyzer->analyze('/path/to/file.txt');
  echo json_encode(\$result, JSON_PRETTY_PRINT);
"
```

### Frontend Integration (ImportPage.jsx)
```jsx
import FecAnalysisDialog from '../components/FecAnalysisDialog';
import SigFormulaVerifier from '../components/SigFormulaVerifier';

// Dans component:
const [showAnalysis, setShowAnalysis] = useState(false);
const [analysis, setAnalysis] = useState(null);

<FecAnalysisDialog
  open={showAnalysis}
  file={selectedFile}
  onClose={() => setShowAnalysis(false)}
  onAnalysisChange={setAnalysis}
  onConfirmImport={() => performImport()}
/>

{analysis && (
  <SigFormulaVerifier
    analysisData={analysis}
    onFormulaValidation={(id, notes) => console.log(id, notes)}
  />
)}
```

---

## Conclusion

Ce workflow implémente une **approche d'expert comptable**:
- ✅ Robuste: tolère variations format FEC
- ✅ Transparent: affiche toutes les étapes d'analyse
- ✅ Validé: vérification ensemble des formules
- ✅ Documenté: contexte métier bijouterie à chaque étape
- ✅ Sécurisé: validation avant import
