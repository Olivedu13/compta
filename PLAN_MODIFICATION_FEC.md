# 🔧 PLAN DE MODIFICATION - EXPLOITATION DONNÉES FEC

**Date:** 15 Janvier 2026  
**Status:** 📋 EN ATTENTE DE VALIDATION  
**Respecte:** AI_FEATURE_REQUEST_AGENT.md  

---

## 📝 ÉTAPE 1: REFORMULATION STRUCTURÉE

### A. Besoin Original
```
Objectif: Exploiter complètement le fichier FEC pour remplir les deux menus (Dashboard + SIGPage)
avec maximum d'information utile et éliminer les doublons existants.
```

### B. Reformulation Structurée
```
Projet: EXPLOITATION COMPLÈTE DU FEC + REFACTO MENUS

Type: Feature majeure (données + UI)

Scope:
1. Backend: Parser FEC detail tiers (clients/fournisseurs nommés)
2. Backend: Calculer DSO/DPO/BFR basé sur dates d'écriture + lettrage
3. Backend: Analyser âge des créances/dettes
4. Frontend: Refacto Dashboard (6 zones, zéro doublon)
5. Frontend: Refacto SIGPage (7 zones, complet)

Utilisateurs:
- Chef d'entreprise (Dashboard)
- Expert comptable (SIGPage)

KPIs Ajoutés:
- Top 10 clients nommés (vs. total)
- Top 10 fournisseurs nommés (vs. total)
- DSO clients (jours)
- DPO fournisseurs (jours)
- Cycle conversion net (BFR)
- Âge créances (< 30j, 30-60j, 60-90j, > 90j)
- Âge dettes (< 30j, 30-60j, 60-90j, > 90j)
- Concentration Pareto (% par client/fournisseur)
- Créances douteuses (> 90j non lettrées)
- Analyse journal (AN, CL, CM, OD, etc.)
```

### C. Dépendances Identifiées
```
Backend:
  ✓ Database: fin_balance existante (utilise)
  ✓ Database: fin_ecritures (NEW - doit stocker détail FEC)
  ✓ Parser: ImportService::parseFEC (existant, améliorer)
  
Frontend:
  ✓ Material-UI (existant)
  ✓ Recharts (existant)
  ✓ API endpoints (à ajouter)
  
Design System:
  ✓ Tokens design (existant)
  ✓ Media queries (existant)
```

---

## 🔍 ÉTAPE 2: VALIDATION ARCHITECTURE

### Vérification Architecture
```
☑️ Structure DDD respectée?
   → Services/SigCalculator (existant)
   → Créer Services/TiersAnalyzer (nouveau)
   → Créer Services/CashflowAnalyzer (nouveau)

☑️ API endpoints cohérents?
   → GET /api/v1/kpis/detailed → ajouter détail tiers
   → GET /api/v1/analytics/advanced → ajouter cycles trésorerie
   → GET /api/v1/tiers/clients → NEW (top 10 nommés)
   → GET /api/v1/tiers/fournisseurs → NEW (top 10 nommés)
   → GET /api/v1/cashflow/analysis → NEW (DSO/DPO/BFR)

☑️ Base de données?
   → Table fin_ecritures: stocker toutes les écritures détaillées
     Colonnes: id, exercice, journal_code, ecriture_num, ecriture_date,
               compte_num, compte_lib, comp_aux_num, comp_aux_lib,
               piece_ref, piece_date, ecriture_lib,
               debit, credit, lettre, date_lettre, valid_date,
               devise, montant_devise
   
   → Index: (exercice, compte_num), (exercice, comp_aux_num, compte_num)

☑️ Performance?
   → Aggégations sur compte principal (pas sur chaque ligne)
   → Cache SIG calculation (recalc 1x/jour)
   → Indexes sur dates (DSO/DPO queries)

☑️ Sécurité?
   → InputValidator: exercice, dates, montants
   → Logger: toutes les modifications

☑️ Testing?
   → Tests sur imports FEC
   → Tests sur calculs DSO/DPO
   → Tests sur Pareto (top 10)
```

### Checklist Architecture
```
✅ Respecte PSR-4 (namespaces)
✅ Séparation concerns (Service pattern)
✅ Gestion erreurs (try/catch + Logger)
✅ Validation input (InputValidator)
✅ Pas de code dupliqué
✅ Types PHP 8 strict
✅ Commentaires docblock
```

---

## 📊 ÉTAPE 3: PLAN D'IMPLÉMENTATION

### 3.1 PHASE 1: Backend Data Layer (2-3h)

**Objectif:** Parser FEC complet + stocker détail tiers

**Fichiers à modifier:**
```
1. /backend/config/schema.sql
   - Créer table fin_ecritures (si n'existe pas)
   - Indexer sur (exercice, compte_num), (exercice, comp_aux_num)

2. /backend/services/ImportService.php
   - Ajouter méthode parseFECDetail($file)
   - Extraire: CompAuxNum, CompAuxLib, EcritureDate, DateLet
   - Stocker en fin_ecritures
   - Valider dates + montants

3. /tests/import-fec.test.php (NEW)
   - Test parseFECDetail avec fec_2024_atc.txt
   - Vérifier 10617 lignes bien importées
   - Vérifier structure table

4. /backend/services/TiersAnalyzer.php (NEW)
   - Service pour analyser clients/fournisseurs détaillés
   - Méthodes:
     * getTopClients($exercice, $limit=10)
     * getTopFournisseurs($exercice, $limit=10)
     * getClientDetailed($exercice, $comp_aux_num)
     * getFournisseurDetailed($exercice, $comp_aux_num)
```

**À tester:**
- Import 11619 lignes de fec_2024_atc.txt
- Vérification table fin_ecritures remplie
- Vérification indexes créés
- Performance requête TOP 10

---

### 3.2 PHASE 2: Backend Calculations (2-3h)

**Objectif:** Calculer DSO/DPO/BFR/Ages créances/dettes

**Fichiers à modifier:**
```
1. /backend/services/CashflowAnalyzer.php (NEW)
   - Service pour analyser cycles trésorerie
   - Méthodes:
     * calculateDSO($exercice)
       → (Créances clients / CA) * 365
       → Basé sur DateLet (date de paiement réelle)
       → Si non lettré: utiliser DateEcriture + 30j par défaut
       
     * calculateDPO($exercice)
       → (Dettes fournisseurs / Achats) * 365
       → Basé sur DateLet
       
     * calculateBFR($exercice)
       → DSO + Jours Stock - DPO
       → Besoin Fonds Roulement estimation
     
     * getCreancesAges($exercice)
       → Grouper par tranches: < 30j, 30-60j, 60-90j, > 90j
       → Montant + nombre de pièces
     
     * getDettesAges($exercice)
       → Même structure que créances
     
     * getCreancesDouteuses($exercice)
       → Créances > 90j non lettrées
       → Flag risque

2. /backend/services/SigCalculator.php (MODIFIER)
   - Ajouter propriété $cashflowAnalyzer
   - Intégrer DSO/DPO dans ratios_solvabilite
   - Ajouter BFR dans output

3. /tests/cashflow-analysis.test.php (NEW)
   - Test DSO calculation
   - Test DPO calculation
   - Test BFR calculation
   - Test ages créances
   - Test créances douteuses
```

**À tester:**
- DSO = ~30-45 jours (normal)
- DPO = ~30-60 jours (normal)
- BFR = DSO + Stock - DPO (< 60j OK, > 90j alerte)

---

### 3.3 PHASE 3: Backend APIs (2h)

**Objectif:** Créer endpoints pour données détaillées

**Fichiers à créer:**
```
1. /public_html/api/v1/tiers/clients.php (NEW)
   GET /api/v1/tiers/clients?exercice=2024&limit=10
   Response:
   {
     "success": true,
     "data": [
       {
         "comp_aux_num": "01200000",
         "comp_aux_lib": "CLIENT DIVERS",
         "montant_total": 125000,
         "montant_paye": 120000,
         "montant_impaye": 5000,
         "pourcentage_ca": 15.2,
         "age_jours": 45,
         "age_bucket": "30-60j",
         "douteuse": false
       },
       ...
     ],
     "total": 825000,
     "douteux": 12000,
     "parite": { "80pct": 662000, "20pct": 163000 }  // Pareto 80/20
   }

2. /public_html/api/v1/tiers/fournisseurs.php (NEW)
   Même structure que clients

3. /public_html/api/v1/cashflow/analysis.php (NEW)
   GET /api/v1/cashflow/analysis?exercice=2024
   Response:
   {
     "success": true,
     "data": {
       "dso": 42,
       "dpo": 55,
       "bfr": 87,
       "creances_ages": {
         "0_30": 150000,
         "30_60": 50000,
         "60_90": 10000,
         "90_plus": 5000
       },
       "dettes_ages": {
         "0_30": 200000,
         "30_60": 80000,
         "60_90": 20000,
         "90_plus": 2000
       },
       "creances_douteuses": 5000,
       "dettes_payees_tard": 2000,
       "alertes": [
         "Cycle conversion long: 87j (> 60j)",
         "Créances > 90j: 5000 €"
       ]
     }
   }

4. /public_html/api/v1/kpis/detailed.php (MODIFIER)
   Ajouter à la réponse:
   - top_clients: [clients array]
   - top_fournisseurs: [fournisseurs array]
   - cycles_tresorerie: { dso, dpo, bfr }
```

**À tester:**
- Requêtes API en moins de 500ms
- Données cohérentes entre endpoints

---

### 3.4 PHASE 4: Frontend Refacto Dashboard (2-3h)

**Objectif:** Restructurer Dashboard (6 zones, zéro doublon)

**Fichiers à modifier:**
```
1. /frontend/src/pages/Dashboard.jsx (MAJOR REFACTO)
   Structure nouvelle:
   ├─ Zone 1: Indicateurs critiques (4 KPI alertes)
   ├─ Zone 2: Stocks bijouterie (3 KPI)
   ├─ Zone 3: Trésorerie (3 KPI)
   ├─ Zone 4: Snapshot financier (6 KPI Grid)
   ├─ Zone 5: Comparaison annuelle (1 graphique)
   └─ Zone 6: Modal "Analyse Détaillée" (bouton optionnel)
   
   ✂️ Supprimer:
   - DashboardSIGCascade (entièrement)
   - Saisonnalité CA (2 affichages)
   - Top clients/fournisseurs (sauf bouton modal)

2. /frontend/src/components/dashboard/DashboardCriticalMetrics.jsx (NEW)
   Component: 4 KPI alertes
   Props: trésorerie_nette, cycle_bfr, solvabilité, rentabilité_nette
   Format: Cards avec codes couleur 🔴🟠🟢

3. /frontend/src/components/dashboard/DashboardSnapshotFinancial.jsx (NEW)
   Component: Grid 2x3 avec 6 KPI
   Props: ca, marge_brute, marge_nette, resultat_net, endettement, solvabilité
   Format: Cards Grid, couleurs basées seuils

4. /frontend/src/components/dashboard/AdvancedAnalyticsModal.jsx (NEW)
   Component: Modal pour AdvancedAnalytics
   Peut être ouvert depuis bouton Dashboard
   Contient: AdvancedAnalytics component

5. /frontend/src/pages/Dashboard.jsx (Nouveau layout)
   ```jsx
   <>
     <DashboardCriticalMetrics kpis={kpis} />
     <DashboardStocks kpis={kpis} />
     <DashboardTresorerie kpis={kpis} />
     <DashboardSnapshotFinancial kpis={kpis} sig={sig} />
     <DashboardComparison annees={annees} />
     <Button onClick={handleAnalysisOpen}>Analyse Détaillée</Button>
     <AdvancedAnalyticsModal open={analysisOpen} onClose={handleAnalysisClose} />
   </>
   ```
```

**À tester:**
- Dashboard lisible en 2-3 min
- Zéro doublon données
- Responsive mobile OK

---

### 3.5 PHASE 5: Frontend Refacto SIGPage (3-4h)

**Objectif:** Enrichir SIGPage (7 zones complètes)

**Fichiers à modifier:**
```
1. /frontend/src/pages/SIGPage.jsx (MAJOR REFACTO)
   Structure nouvelle:
   ├─ Zone 1: Cascade SIG tableau (avec formules)
   ├─ Zone 2: Analyse détaillée SIG (expandable)
   ├─ Zone 3: Ratios financiers (4 catégories)
   ├─ Zone 4: Analyse clients/fournisseurs
   ├─ Zone 5: Graphiques analytiques (4 charts)
   ├─ Zone 6: Alertes intelligentes
   └─ Zone 7: Export PDF/Excel

2. /frontend/src/components/sig/SIGCascadeTable.jsx (NEW)
   Component: Tableau SIG avec formules
   Props: sig, comparaison_previous_year
   Affiche: 6 SIG + formules + comparaison YoY

3. /frontend/src/components/sig/SIGDetailedAnalysis.jsx (NEW)
   Component: Analyse détaillée SIG
   Props: sig
   Expandable: Production, VA, EBE, Financier

4. /frontend/src/components/sig/FinancialRatios.jsx (NEW)
   Component: Ratios par catégories
   Props: sig, kpis
   Categories: Profitabilité, Solvabilité, Cycles, Productivité

5. /frontend/src/components/sig/TiersAnalysis.jsx (NEW)
   Component: Analyse clients/fournisseurs
   Props: top_clients, top_fournisseurs, creances_ages, dettes_ages
   Affiche:
   - Top 10 clients nommés (montant + %)
   - Top 10 fournisseurs nommés (montant + %)
   - Âges créances (tableau)
   - Âges dettes (tableau)
   - Concentration Pareto

6. /frontend/src/components/sig/CashflowAnalytics.jsx (NEW)
   Component: Graphiques analytiques
   Props: sig, cycles_tresorerie
   Graphs:
   - Waterfall SIG
   - Évolution 3 ans (CA + Marge + Résultat)
   - Décomposition charges (pie)
   - Cycles trésorerie (DSO, jours stock, DPO)

7. /frontend/src/components/sig/IntelligentAlerts.jsx (NEW)
   Component: Alertes intelligentes
   Props: sig, kpis, cycles
   Affiche: Liste alertes cliquables avec drill-down

8. /frontend/src/components/sig/ExportActions.jsx (NEW)
   Component: Boutons export
   Props: exercice, sig_data
   Actions: PDF, Excel, Imprimer

9. /frontend/src/pages/SIGPage.jsx (Nouveau layout)
   ```jsx
   <>
     <SIGCascadeTable sig={sig} prev={sig_previous_year} />
     <SIGDetailedAnalysis sig={sig} />
     <FinancialRatios sig={sig} kpis={kpis} />
     <TiersAnalysis clients={clients} fournisseurs={fournisseurs} />
     <CashflowAnalytics sig={sig} cycles={cycles} />
     <IntelligentAlerts sig={sig} kpis={kpis} cycles={cycles} />
     <ExportActions exercice={exercice} data={sig} />
   </>
   ```
```

**À tester:**
- SIGPage affiche tous les éléments
- Alertes correctes
- Export fonctionne

---

### 3.6 PHASE 6: Test & Validation (2-3h)

**Objectif:** Valider tout fonctionne + documentation

**À faire:**
```
1. Tests unitaires:
   - ImportService::parseFECDetail
   - TiersAnalyzer::getTopClients
   - CashflowAnalyzer::calculateDSO
   - CashflowAnalyzer::calculateBFR
   - Age calculations

2. Tests intégration:
   - Import FEC → API → Frontend
   - Dashboard: 2-3 min lecture OK?
   - SIGPage: Toutes données presentes OK?
   - Zéro doublon OK?

3. Tests UX:
   - Dashboard responsive mobile?
   - SIGPage responsive mobile?
   - Modal AdvancedAnalytics ouvre/ferme?
   - Export PDF/Excel fonctionne?

4. Tests Performance:
   - API response < 500ms?
   - Dashboard load < 2s?
   - SIGPage load < 3s?

5. Documentation:
   - Maj ARCHITECTURE_GUIDELINES.md
   - Créer COMPONENT_STRUCTURE.md
   - Maj QUICKSTART.md
```

---

## 🎯 ÉTAPE 4: VALIDATION DES DONNÉES

### Vérification FEC Complet
```
✅ Colonnes disponibles:
   - CompAuxNum/CompAuxLib: Tiers nommés (clients/fournisseurs)
   - EcritureDate: Date écriture (âge créances)
   - DateLet: Date lettrage (date réelle paiement)
   - EcritureLib: Libellé (description transaction)
   - JournalCode: Type (AN, CL, CM, OD, etc.)

✅ Données suffisantes pour:
   - Top 10 clients/fournisseurs nommés
   - DSO/DPO précis (basé sur DateLet)
   - Âges créances/dettes (basé sur EcritureDate)
   - Cycle conversion BFR
   - Concentration Pareto

✅ Exemple données exploitables:
   AN000001 | 20240101 | 41100000 | CLIENTS | 01200000 | CLIENT DIVERS
   → Créance client "CLIENT DIVERS" de €4000 depuis 2012
   → Âge: > 90j
   → Etat: Non lettré (doublon compte d'à nouveau)

✅ Journal détail:
   AN = À nouveau (soldes anciens)
   CL = Credit Lyonnais (mouvements bancaires)
   CM = Credit Mutuel (mouvements bancaires)
   OD = Opérations Diverses (saisies manuelles, TVA, paie)
```

---

## 📋 ÉTAPE 5: CHECKLIST FINALE

```
DONNÉES:
☐ FEC contient 11619 lignes
☐ Colonnes: JournalCode, CompAuxNum, EcritureDate, DateLet, PieceDate
☐ Suffisant pour DSO/DPO/BFR?  OUI
☐ Suffisant pour top 10 tiers?  OUI
☐ Suffisant pour ages creances? OUI

DASHBOARD NOVA:
☐ 6 zones claires
☐ Zéro SIG cascade
☐ Zéro doublon saisonnalité
☐ 4 KPI critiques (alerte)
☐ Stocks détaillés (Or, Diamants, Total)
☐ Trésorerie Nette (KPI nouveau)
☐ Snapshot financier (6 KPI Grid)
☐ Comparaison annuelle (1 graph)
☐ Modal pour creuser (optionnel)
☐ Lisible 2-3 min?  OUI

SIG PAGE NOVA:
☐ 7 zones complètes
☐ Tableau SIG avec formules
☐ Ratios 4 catégories
☐ Clients/fournisseurs nommés (Top 10)
☐ Âges créances/dettes
☐ Concentration Pareto
☐ 4 graphiques analytiques
☐ Alertes intelligentes (cliquables)
☐ Export PDF/Excel
☐ Complet pour expert?  OUI

BACKEND:
☐ Table fin_ecritures créée
☐ Import FEC complet (11619 lignes)
☐ TiersAnalyzer service (top clients/fournisseurs)
☐ CashflowAnalyzer service (DSO/DPO/BFR)
☐ 3 nouveaux endpoints API
☐ Tests unitaires OK
☐ Performance OK (< 500ms)

FRONTEND:
☐ Dashboard refactorisée (6 zones)
☐ SIGPage enrichie (7 zones)
☐ Zéro doublon data
☐ Responsive mobile OK
☐ Tests intégration OK
☐ UX testing OK

DOCUMENTATION:
☐ ARCHITECTURE_GUIDELINES.md mis à jour
☐ COMPONENT_STRUCTURE.md créé
☐ QUICKSTART.md mis à jour
☐ API_REFERENCE.md mis à jour
```

---

## 📊 EFFORT ESTIMÉ

```
PHASE 1: Backend Data Layer        → 2-3h
  - Schema.sql + table fin_ecritures
  - ImportService amélioration
  - TiersAnalyzer service

PHASE 2: Backend Calculations      → 2-3h
  - CashflowAnalyzer service
  - DSO/DPO/BFR calculations
  - Ages créances/dettes

PHASE 3: Backend APIs              → 2h
  - 3 nouveaux endpoints
  - Validation + erreur handling

PHASE 4: Frontend Dashboard        → 2-3h
  - Refacto major (6 zones)
  - 2 nouveaux components
  - Modal AdvancedAnalytics

PHASE 5: Frontend SIGPage          → 3-4h
  - Refacto major (7 zones)
  - 6 nouveaux components
  - Alertes + export

PHASE 6: Test & Documentation      → 2-3h
  - Tests unitaires/intégration
  - Tests UX/performance
  - Mise à jour documentation

                                    ─────────
                        TOTAL:      15-19h (2 jours)
```

---

## 🚀 ORDRE D'EXÉCUTION

```
JOUR 1:
1. Phase 1: Backend Data (3h)
2. Phase 2: Backend Calculations (3h)
3. Phase 3: Backend APIs (2h)

JOUR 2:
4. Phase 4: Frontend Dashboard (3h)
5. Phase 5: Frontend SIGPage (4h)
6. Phase 6: Test & Doc (2-3h)

COMMITS:
- Commit 1: Backend data layer
- Commit 2: Backend calculations + APIs
- Commit 3: Frontend Dashboard refacto
- Commit 4: Frontend SIGPage refacto
- Commit 5: Documentation + tests
```

---

## ✅ RÉSUMÉ EXÉCUTIF

**AVANT:**
- Dashboard = tout mélangé (5 sections, doublons)
- SIGPage = trop basique (2 sections)
- Données détail tiers = non exploitées
- DSO/DPO/BFR = non calculés
- Créances > 90j = pas identifiées

**APRÈS:**
- Dashboard = 6 zones claires, 2-3min, décisionnel
- SIGPage = 7 zones complètes, expert comptable satisfait
- Top 10 clients/fournisseurs nommés = exploitées
- DSO/DPO/BFR = calculés et affichés
- Créances douteuses = alertées
- Zéro doublon data

**RÉSULTAT:**
✅ Meilleure UX
✅ Toutes données exploitées
✅ Expert comptable satisfait
✅ Chef d'entreprise satisfait
✅ Maintenance facile

---

**📌 PRÊT POUR VALIDATION?** OUI ✅

Valide ou propose ajustements avant démarrage Phase 1.
