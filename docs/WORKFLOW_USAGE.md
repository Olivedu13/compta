# 📊 Workflow Expert Comptable FEC → Analyse → Import → SIG

## 🎯 Résumé Exécutif

Vous disposez maintenant d'une **solution complète** pour traiter les fichiers FEC de bijouterie comme un expert comptable le ferait :

### ✅ Implémenté

1. **FecAnalyzer** - Analyse robuste du FEC
   - Détecte automatiquement le format (séparateur, encodage)
   - Tolère variations mineures (dates, montants, casse)
   - Valide l'équilibre comptable (débits = crédits)
   - Identifie anomalies bloquantes vs warnings
   - Recommande actions de nettoyage

2. **FecAnalysisDialog** - Interface React
   - Affiche résultats analyse en temps réel
   - Visualise statistiques comptables
   - Signale anomalies détectées
   - Permet re-analyse ou import confirmé

3. **SigFormulaVerifier** - Vérification expert ensemble
   - Affiche toutes formules SIG du PCG 2025
   - Documente comptes utilisés + contexte bijouterie
   - Points de validation + préoccupations métier
   - Permet valider formules avant calcul

4. **Endpoint API `/api/analyze/fec`**
   - Upload FEC → Analyse complète
   - Retourne rapport JSON structuré

---

## 📋 Structure Implémentée

### Backend
```
/backend/services/
├─ FecAnalyzer.php              (1200+ lignes)
│  ├─ Détection format
│  ├─ Normalisation en-tête
│  ├─ Validation données
│  ├─ Détection anomalies
│  └─ Recommandations
│
└─ ImportService.php            (modifié)
   ├─ analyzeFEC()              (wrapper)
   └─ importFEC()               (existant, optimisé)

/public_html/api/
└─ index.php                    (modifié)
   └─ POST /api/analyze/fec     (nouveau)
```

### Frontend
```
/frontend/src/components/
├─ FecAnalysisDialog.jsx        (450+ lignes)
│  ├─ Upload zone
│  ├─ Affichage analyse
│  ├─ Statistiques comptables
│  ├─ Détection anomalies
│  └─ Boutons action
│
└─ SigFormulaVerifier.jsx       (600+ lignes)
   ├─ 6 accordéons (1 par SIG)
   ├─ Formule mathématique
   ├─ Tableaux comptes
   ├─ Points validation
   ├─ Préoccupations métier
   └─ Dialog validation
```

### Documentation
```
├─ FEC_WORKFLOW_COMPLET.md      (guide complet)
└─ sample_fec_bijouterie.txt    (FEC test réaliste)
```

---

## 🚀 Comment Utiliser

### ÉTAPE 1: Tester l'Analyse FEC en CLI

```bash
cd /workspaces/compta

# Créer un test PHP simple
cat > test_analyzer.php << 'EOF'
<?php
require_once 'backend/config/Database.php';
require_once 'backend/config/Logger.php';
require_once 'backend/services/FecAnalyzer.php';

use App\Services\FecAnalyzer;

$analyzer = new FecAnalyzer();
$result = $analyzer->analyze('sample_fec_bijouterie.txt');

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
EOF

# Exécuter
php test_analyzer.php
```

**Résultat attendu:** Rapport JSON complet avec:
- ✓ Format détecté (TAB, 18 colonnes)
- ✓ Statistiques: 40 écritures, 18 comptes, 4 journaux
- ✓ Équilibre: débits = crédits ✓
- ✓ Exercice: 2024
- ✓ Anomalies: aucune (FEC test équilibré)

### ÉTAPE 2: Tester via API REST

```bash
# Démarrer le serveur PHP (s'il n'est pas déjà en cours)
cd /workspaces/compta/public_html

# Ou depuis le root du conteneur:
php -S 127.0.0.1:8000

# Puis dans autre terminal, faire requête:
curl -X POST \
  -F "file=@sample_fec_bijouterie.txt" \
  http://127.0.0.1:8000/api/analyze/fec

# Voir résultat JSON structuré
```

### ÉTAPE 3: Intégrer dans ImportPage.jsx

Dans `/frontend/src/pages/ImportPage.jsx` :

```jsx
import FecAnalysisDialog from '../components/FecAnalysisDialog';
import SigFormulaVerifier from '../components/SigFormulaVerifier';
import { useState } from 'react';

export default function ImportPage() {
  const [showAnalysis, setShowAnalysis] = useState(false);
  const [selectedFile, setSelectedFile] = useState(null);
  const [analysis, setAnalysis] = useState(null);

  const handleFileSelected = (file) => {
    setSelectedFile(file);
    setShowAnalysis(true);
  };

  const handleAnalysisComplete = (analysisData) => {
    setAnalysis(analysisData);
  };

  const handleConfirmImport = async () => {
    // Lance import via API
    const formData = new FormData();
    formData.append('file', selectedFile);
    
    const response = await fetch('/api/import/fec', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    alert(`Import réussi: ${result.data.count} écritures`);
    setShowAnalysis(false);
    
    // Affiche ensuite SigFormulaVerifier pour validation
  };

  return (
    <div>
      <UploadZone onFilesSelected={handleFileSelected} />
      
      <FecAnalysisDialog
        open={showAnalysis}
        file={selectedFile}
        onClose={() => setShowAnalysis(false)}
        onAnalysisChange={handleAnalysisComplete}
        onConfirmImport={handleConfirmImport}
      />

      {analysis && (
        <SigFormulaVerifier
          analysisData={analysis}
          onFormulaValidation={(formulaId, notes) => {
            console.log(`Validé: ${formulaId}`, notes);
          }}
        />
      )}
    </div>
  );
}
```

### ÉTAPE 4: Workflow Complet Utilisateur

```
1. Accès page ImportPage
   ↓
2. Télécharge FEC bijouterie
   ↓
3. FecAnalysisDialog s'ouvre
   - Affiche "Analyse en cours..."
   - Appelle POST /api/analyze/fec
   - Affiche résultats: ✓ Format, ✓ Équilibre, ✓ 40 écritures
   ↓
4. Utilisateur revoit ensemble:
   - Séparateur détecté: TAB ✓
   - Exercice: 2024 ✓
   - Comptes: 18 ✓
   - Débits = Crédits ✓
   - Anomalies: aucune ✓
   ↓
5. Clic "Importer le FEC"
   - POST /api/import/fec
   - ImportService lance import batch
   - Retourne: ✓ 40 écritures importées
   ↓
6. SigFormulaVerifier s'affiche
   - Marge de Production = (70+71+72) - (601+602±603)
   - Valeur Ajoutée = MP - (61+62)
   - ... (cascade complète)
   ↓
7. Utilisateur valide chaque formule:
   - Vérifie comptes présents
   - Vérifie pertinence métier
   - Coche "Valider la Formule"
   ↓
8. SigCalculator calcule résultats
   - Utilise balance importée
   - Calcule chaque SIG
   - Affiche cascade visuelle
   ↓
9. Dashboard affiche résultats
   - KPI bijouterie
   - Graphique waterfall
   - Balance détaillée
```

---

## 🔍 Anatomie FecAnalyzer

### Input
```
File: FEC.txt (TAB-separated, 18 colonnes, 2500 lignes)
```

### Process

**1. Détection Format**
```python
Pour chaque séparateur (TAB, |, ,, ;):
  - Parse 50 dernières lignes
  - Compte colonnes par ligne
  - Calcule variance
  - Score = avg_colonnes / (1 + variance)

Sélectionne séparateur avec meilleur score
```

**2. Normalisation En-tête**
```python
header_raw = ["JournalCode", "journal_lib", "COMPTE_NUM", ...]

Chaque colonne:
  - Normalise: lowercase, remove special chars
  - Cherche dans COLUMN_ALIASES
  - Si trouvé: mappe vers nom standard (JournalCode, CompteNum, etc.)
  - Si pas trouvé: marque "Custom_XXX"

Résultat: 18 colonnes FEC standard mappées
```

**3. Analyse Données**
```python
Pour chaque ligne:
  - Extrait debit, credit, compte, journal, date
  - Parse montants: tolère "1.000,50" ou "1000.50"
  - Parse dates: tolère formats variantes
  - Collecte stats:
    * Total débit, total crédit
    * Comptes uniques
    * Journaux uniques
    * Plage dates
    * Exercice (année min date)
```

**4. Détection Anomalies**
```python
Critique (bloque import):
  if (total_debit - total_credit) / total_debit > 0.001:
    ❌ "Déséquilibre comptable > 0.1%"
  
  if error_rows / total_rows > 0.05:
    ❌ "Trop de lignes erreur (> 5%)"
  
  if valid_rows == 0:
    ❌ "Aucune donnée valide"

Warning (non-bloquant):
  if balance_difference > 0.01:
    ⚠️ "Léger déséquilibre (centimes)"
  
  if valid_rows < 10:
    ⚠️ "Très faible volume données"
```

**5. Recommandations**
```python
can_import = empty(critical_anomalies)
suggested_actions = [...]
cleaning_needed = [...]
summary = "127 comptes, 8 journaux, 2496 écritures valides | Débit: 156740.50€ = Crédit: 156740.50€"
```

### Output
```json
{
  "ready_for_import": true,
  "file_info": {...},
  "format": {...},
  "headers": {...},
  "data_statistics": {...},
  "anomalies": {...},
  "recommendations": {...}
}
```

---

## 🎓 Anatomie SigFormulaVerifier

### 6 SIG avec Documentation Complète

#### 1️⃣ Marge de Production (MP)
```
Formule:    (70 + 71 + 72) - (601 + 602 ± 603)
Contexte:   Produits - Matières = Marge brute production

Pour Bijouterie:
- 70: Ventes bijoux fabriqués
- 71: Stock travail en cours (très important!)
- 72: Pièces incorporées
- 601: Or, argent, pierres précieuses (volatiles!)
- 602: Outils, composants
- 603: Stock initial→final (signe!)

Points Validation:
✓ 70,71,72 crédités (produits)
✓ 601,602 débités (charges)
✓ Variation 603 inclut tous en-cours
✓ Métaux précieux: vérifier valorisation

Préoccupations:
⚠️ Prix marché métaux (volatilité)
⚠️ En-cours bijouterie importante?
⚠️ Déchets valorisés?
```

#### 2️⃣ Valeur Ajoutée (VA)
```
Formule:    MP - (61 + 62)
Contexte:   Richesse créée = MP - Services externes

Pour Bijouterie:
- 61: Sous-traitance (gravure, sertissage externe)
- 62: Assurance, frais divers

Validation:
✓ VA doit être SIGNIFICATIVE (c'est le métier!)
✓ Pas trop de sous-traitance

Préoccupations:
⚠️ Si VA faible: peu de création in-house
⚠️ Bijouterie luxe: VA = création artistique
```

#### 3️⃣ EBE / EBITDA
```
Formule:    VA + 74 - (63 + 64 + 68*)
Contexte:   Cash généré avant intérêts/impôts/amortissements

Pour Bijouterie:
- 63: CVAE, taxes atelier
- 64: Salaire apprentis (TRÈS important!)
- 74: Or de récupération (plus-value)
- 68*: Uniquement exceptionnel

Validation:
✓ EBE > 0 (doit générer cash)
✓ 64 cohérent avec apprentissage

Préoccupations:
⚠️ Si EBE < 0: revoir modèle
⚠️ Salaire patron inclus?
```

#### 4️⃣ Résultat d'Exploitation (RE)
```
Formule:    EBE - 681
Contexte:   Rentabilité métier (avant intérêts/impôts)

Pour Bijouterie:
- 681: Amortissement outils, tours, établis (5-10 ans)

Validation:
✓ RE > 0 = métier rentable
✓ Amortissements cohérents

Préoccupations:
⚠️ Durée amortissement outils?
⚠️ RE < 0 mais EBE > 0: amort excessifs?
```

#### 5️⃣ Résultat Financier (RF)
```
Formule:    69 (Intérêts) - 76 (Produits financiers)
Contexte:   Impact financements et placements

Pour Bijouterie:
- 69: Intérêts emprunts exploitation/investissement
- 76: Intérêts comptes (rare)

Validation:
✓ RF < 0 généralement (coût financement)
✓ Normal si entreprise s'endette

Préoccupations:
⚠️ Crédit fournisseurs (stocks or) important?
```

#### 6️⃣ Résultat Net (RN)
```
Formule:    RE + RF - 69 (impôt si IS applicable)
Contexte:   Bénéfice/Perte final

Pour Bijouterie:
- Souvent micro-entreprise (pas IS)
- Comparer avec salaire patron

Validation:
✓ RN > 0 et proportionné au travail
```

---

## 📊 Fichier FEC Test

Situé en: `/workspaces/compta/sample_fec_bijouterie.txt`

**Composition réaliste bijouterie :**

- ✓ 40 écritures FEC
- ✓ 8 journaux (VE=ventes, AC=achats, BQ=banque, JO=journaux)
- ✓ 18 comptes utilisés
- ✓ Période: janvier-juin 2024
- ✓ Équilibré: Débits = Crédits

**Comptes inclus :**
- 70: Ventes bijoux
- 71: Stock travail
- 601: Matières premières (or, argent, pierres)
- 602: Fournitures atelier
- 641: Salaire apprenti bijoutier
- 681: Amortissement équipement
- 74: Or de récupération
- 51200: Banque

**Utilisé pour tester :**
```bash
php test_analyzer.php
# → Affiche analyse complète
```

---

## ✅ Checklist Validation Ensemble

### Avant Import
- [ ] Fichier FEC uploadé
- [ ] Analyse affichée sans erreur
- [ ] Séparateur détecté correctement
- [ ] En-tête contient 18 colonnes FEC
- [ ] Équilibre comptable validé (débit = crédit)
- [ ] Aucune anomalie critique
- [ ] Exercice détecté correct

### Après Import
- [ ] Nombre écritures importées = nombre attendu
- [ ] Tous comptes créés
- [ ] Balance agrégée correctement
- [ ] Pas de doublons

### Validation SIG
Pour chaque SIG (Marge → VA → EBE → RE → RN):
- [ ] Formule mathématique vérifiée
- [ ] Comptes utilisés présents dans balance
- [ ] Signes comptables corrects
- [ ] Résultat cohérent (positif/négatif selon attente)
- [ ] Notes de validation enregistrées

### Dashboard Final
- [ ] KPI affichées correctement
- [ ] Graphique waterfall représente cascade SIG
- [ ] Balance consultable
- [ ] Comparaison année N-1 possible

---

## 🐛 Troubleshooting

### FecAnalyzer retourne "ready_for_import: false"

**Causes possibles :**
1. Déséquilibre comptable (débits ≠ crédits)
   - Solution: Vérifier source FEC, recalculer montants

2. Trop d'erreurs de parsing (> 5%)
   - Solution: Vérifier encoding (UTF-8 vs ISO-8859-1)

3. Aucune donnée valide
   - Solution: Vérifier format FEC (séparateur, en-tête)

### SigFormulaVerifier affiche montants=0

**Causes possibles :**
1. Import non réalisé ou échoué
   - Solution: Relancer import FEC

2. Comptes manquants dans balance
   - Solution: Vérifier compte_num utilisés dans formule

3. Exercice mal détecté
   - Solution: Vérifier dates FEC

### API retourne 500

**Check :**
```bash
# Vérifier logs PHP
tail -f /workspaces/compta/backend/logs/$(date +%Y-%m-%d).log

# Vérifier fichier temp
php -r "echo sys_get_temp_dir();"

# Tester FecAnalyzer directement
php test_analyzer.php
```

---

## 📚 Documentation Complète

Voir: `/workspaces/compta/FEC_WORKFLOW_COMPLET.md`

---

## 🎯 Prochaines Étapes

1. **Intégrer composants React dans ImportPage**
   - Importer FecAnalysisDialog
   - Importer SigFormulaVerifier
   - Wirer événements

2. **Tester workflow complet**
   - Upload FEC sample
   - Vérifier analyse
   - Vérifier formules
   - Vérifier résultats dashboard

3. **Amélioration continue**
   - Ajouter export PDF rapport d'analyse
   - Ajouter historique imports
   - Comparer année N vs N-1
   - Alertes anomalies

---

## 🤝 Questions / Validation

**À vérifier ensemble :**
- [ ] Les formules SIG sont-elles exactes selon PCG 2025?
- [ ] Le contexte bijouterie est-il correctement documenté?
- [ ] Les comptes utilisés correspondent-ils à votre plan comptable?
- [ ] Les seuils d'anomalies sont-ils appropriés?
- [ ] Le workflow utilisateur est-il intuitif?

---

**Status:** ✅ Implémentation complète, prêt pour intégration et tests
