# 📋 PLAN DE REFACTO - MENUS DASHBOARD vs SIG

**Date:** 15 Janvier 2026  
**Objectif:** Clarifier les responsabilités des deux menus pour éliminer les doublons  
**Statut:** ✋ PROPOSITION (pas de modification)

---

## 🔍 AUDIT ACTUEL

### Résumé Exécutif
```
PROBLÈME IDENTIFIÉ:
- Dashboard = "petit SIG" (cascade SIG réduite + KPI)
- SIGPage = "grand SIG" (cascade complète + analyse)
- Beaucoup de DOUBLON dans les données affichées
- Pas de distinction claire entre les deux menus
```

---

## 📊 STATE ACTUEL - DASHBOARD (Chef d'entreprise)

**Localisation:** `/frontend/src/pages/Dashboard.jsx`

### Sections Actuelles:

1. **KPI Stocks** (DashboardKPISection)
   - Stock Or
   - Total Stock
   - ⚠️ REDONDANT: Stock Or affiché 2x

2. **Trésorerie & Tiers**
   - Banque
   - Caisse
   - Clients (avec trend)
   - Fournisseurs

3. **Cascade SIG** (DashboardSIGCascade)
   - Affiche tous les 6 SIG (production, VA, EBE, exploitation, financier, net)
   - ⚠️ DOUBLON: Même chose que SIGPage!

4. **Analyse Financière** (AnalysisSection)
   - CA mensuel (saisonnalité)
   - Top 10 clients
   - Top 10 fournisseurs
   - Structure des coûts

5. **Analytics Avancée** (AdvancedAnalytics)
   - Vue d'ensemble financière (CA, Marge nette, Solvabilité, Endettement)
   - Saisonnalité & Tendance CA (DOUBLON avec #4)
   - Ratios d'exploitation
   - Cycles trésorerie
   - Top clients/fournisseurs (DOUBLON avec #4)
   - Santé financière & Solvabilité
   - Alertes

**Problèmes:**
- 3 affichages de saisonnalité CA
- 2 affichages des top clients/fournisseurs
- Trop dense pour "coup d'œil rapide" du chef
- Mélange KPI + Analytics (dilue le message)

---

## 📋 STATE ACTUEL - SIG PAGE (Expert comptable)

**Localisation:** `/frontend/src/pages/SIGPage.jsx`

### Sections Actuelles:

1. **Cascade Visuelle SIG**
   - 6 cartes avec les SIG (production, VA, EBE, exploitation, financier, net)
   - Code couleur (vert/rouge)
   - Description

2. **Graphique Analyse**
   - Bar chart des SIG (très basique)

**Problèmes:**
- SUPER MINIMALISTE
- Pas d'analyse détaillée des SIG
- Pas de ratios financiers
- Pas de comparaisons
- Pas de drill-down sur les comptes
- Ne satisfait PAS un expert comptable qui cherche de l'analyse

---

## 🎯 AUDIT DES KPIs ACTUELS

### Par Catégorie Métier:

#### 1. STOCKS (Bijouterie)
```
✓ Stock Or (compte 311)
✓ Stock Diamants (compte 312)
❓ Valeur totale stock (somme)
❓ Variation stock YoY
```

#### 2. TRÉSORERIE
```
✓ Banque (compte 512)
✓ Caisse (compte 531)
❌ Cash + découvert (vraie trésorerie nette)
❌ Ratio liquidité (circulant/courant)
```

#### 3. CLIENTS / DETTES
```
✓ Total clients (compte 411)
✓ Total fournisseurs (compte 401)
❌ Days Sales Outstanding (DSO)
❌ Days Payable Outstanding (DPO)
❌ Créances douteuses (417)
```

#### 4. SIG (Cascade complète)
```
✓ Marge production (70-72) - (601+602+603)
✓ Valeur ajoutée (Marge) - (61+62)
✓ EBE (VA + 74 - 64 - 63)
✓ Résultat exploitation (EBE - 681 amortissements)
✓ Résultat financier (+/- 69 intérêts)
✓ Résultat net (Exploitation + Financier)
```

#### 5. RATIOS FINANCIERS (Actuellement dans AdvancedAnalytics)
```
✓ CA total
✓ Marge nette (%)
✓ Solvabilité (ratio)
✓ Endettement (x)
✓ ROIC (%)
✓ Ratio liquidité
✓ Ratio autonomie
✓ Dettes financières
✓ Capitaux propres
```

#### 6. ANALYSE COMMERCIALE
```
✓ CA mensuel (saisonnalité)
✓ CA trimestriel
✓ Top 10 clients
✓ Top 10 fournisseurs
✓ Concentrations (Pareto)
✓ Ratios achats/CA
✓ Ratios salaires/CA
```

#### 7. CYCLES TRÉSORERIE
```
✓ DSO clients (jours)
✓ Jours stock
✓ DPO fournisseurs (jours)
✓ Cycle conversion (net)
✓ BFR (Besoin Fonds Roulement)
```

---

## 🚨 DOUBLONS IDENTIFIÉS

### DOUBLON #1: Cascade SIG
```
Location: Dashboard (section 3) + SIGPage (section 1)
Affichage: Identique dans les 2 pages
❌ PROBLÈME: Chef d'entreprise ne comprend pas SIG
✅ SOLUTION: SIG = SIGPage seulement
```

### DOUBLON #2: Top Clients/Fournisseurs
```
Locations: AnalysisSection + AdvancedAnalytics
Affichage: Tableaux avec même data
✅ SOLUTION: Garder UN seul endroit (AnalysisSection si besoin du détail)
```

### DOUBLON #3: Saisonnalité CA
```
Locations: AnalysisSection (2x) + AdvancedAnalytics
Affichage: Graphiques mensuel/trimestriel
✅ SOLUTION: Un seul affichage, choix utilisateur (vue mensuelle OU trimestrielle)
```

### DOUBLON #4: Stock Or
```
Location: DashboardKPISection
Affichage: Affiche 2x (Stock Or + Total Stock = même valeur)
❌ PROBLÈME: Confusion - "Or" vs "Total"
✅ SOLUTION: Renommer "Total Stock" → "Valeur Stock Complète" ou retirer
```

---

## 💡 PROPOSÉ - NOUVELLE STRUCTURE

### DASHBOARD (Chef d'entreprise) - "Vue d'Ensemble Exécutive"

**Objectif:** Prendre une décision en 2 minutes

**Structure proposée:**

#### 1️⃣ ZONE CRITIQUE (Top de la page - ce qui demande action)
```
Titre: ⚠️ INDICATEURS CRITIQUES

Affichage:
- 4 KPIs essentiels SEULEMENT:
  1. Trésorerie Nette (Banque + Caisse) → Rouge si négatif
  2. Cycle Conversion BFR (jours) → Rouge si > 45j
  3. Solvabilité (Endettement ratio) → Rouge si > 2
  4. Rentabilité Nette (%) → Rouge si < 5%

Pas d'analyse, pas d'historique → DÉCISION IMMÉDIATE
```

#### 2️⃣ STOCKS & ACTIFS (Métier bijouterie)
```
Titre: 💎 INVENTAIRE BIJOUTERIE

Affichage (3 cartes):
1. Stock Or (€) → avec variation YoY
2. Stock Diamants (€) → avec variation YoY
3. Stock Total (€) → avec variation YoY

Pas de drill-down → Juste les montants
```

#### 3️⃣ TRÉSORERIE À VUE
```
Titre: 💳 TRÉSORERIE

Affichage (3 cartes):
1. Banque (€) → solde net
2. Caisse (€)
3. Trésorerie Nette (€) → = Banque + Caisse

Pas de ratio → Valeurs brutes (chef comprend facilement)
```

#### 4️⃣ SNAPSHOT FINANCIER (1 page = rapide)
```
Titre: 📊 RÉSULTAT EXERCICE

Affichage (Grid 2x3):
┌─────────────────────────────────┐
│ CA         │ Marge Brute │ Marge Nette
│ €1.2M      │ 35%         │ 8.5%
├─────────────────────────────────┤
│ Res. Net   │ Endettement │ Solvabilité
│ €102K      │ 1.2x        │ 1.8x
└─────────────────────────────────┘

Format: Petit, lisible, sans détail
Couleurs: Vert/Orange/Rouge basé sur seuils
```

#### 5️⃣ COMPARAISON ANNUELLE (Tendance)
```
Titre: 📈 ÉVOLUTION (vs années précédentes)

Affichage:
- 1 graphique combiné: CA + Marge + Résultat (3 lignes)
- Comparaison 2024 vs 2023 vs 2022
- Permet de voir la tendance rapidement

Sans détail → Juste la courbe
```

#### 6️⃣ ACTION RAPIDE (Dialog/Modal)
```
Bouton: 🔍 "Analyse Détaillée"

Lance modal AdvancedAnalytics complet:
- Saisonnalité
- Top clients/fournisseurs
- Cycles trésorerie
- Ratios détaillés
- Alertes

ℹ️ OPTIONNEL: Chef peut creuser si intéressé
```

**Résumé Dashboard Proposé:**
- 6 sections
- Max 1 graphique par section (sinon : dialog)
- Pas de redondance
- Lisible en 2-3 minutes
- Actionnable immédiatement

---

### SIG PAGE (Expert comptable) - "Rapport Complet d'Analyse"

**Objectif:** Fournir TOUS les éléments pour l'analyse comptable

**Structure proposée:**

#### 1️⃣ EN-TÊTE (Contexte)
```
Affichage:
- Exercice sélectionné
- Période: Du AAAA/MM/DD au AAAA/MM/DD
- Comparaison: vs année N-1 (% écarts)
```

#### 2️⃣ CASCADE SIG COMPLÈTE (Avec formules)
```
Titre: SOLDES INTERMÉDIAIRES DE GESTION

Format tableau (pas cartes):
┌────────────────────────┬──────────────┬─────────────┐
│ Intitulé (Formule)     │ 2024 (€)     │ 2023 (€)    │
├────────────────────────┼──────────────┼─────────────┤
│ Marge Prod.            │ 450,000      │ 420,000     │
│ (70-72) - (601+602)    │              │             │
├────────────────────────┼──────────────┼─────────────┤
│ Valeur Ajoutée         │ 280,000      │ 260,000     │
│ (Marge) - (61+62)      │              │             │
├────────────────────────┼──────────────┼─────────────┤
│ EBE (EBITDA)           │ 250,000      │ 235,000     │
│ (VA + 74 - 64 - 63)    │              │             │
├────────────────────────┼──────────────┼─────────────┤
│ Rés. Exploitation      │ 150,000      │ 135,000     │
│ (EBE - amortissements) │              │             │
├────────────────────────┼──────────────┼─────────────┤
│ Rés. Financier         │ -10,000      │ -8,000      │
│ (+/- intérêts + prod)  │              │             │
├────────────────────────┼──────────────┼─────────────┤
│ Rés. Net               │ 140,000      │ 127,000     │
│ (Expl. + Fin.)         │              │             │
└────────────────────────┴──────────────┴─────────────┘

✓ Affiche formule pour transparence
✓ Comparaison YoY pour analyse tendance
✓ Format comptable professionnel
```

#### 3️⃣ ANALYSE DES SIG (Décomposition)
```
Titre: ANALYSE DÉTAILLÉE

Sous-sections (expandable):

A) Marge Production
   └─ Détail produits (70, 71, 72)
   └─ Détail charges (601, 602, 603)
   └─ Ratio marge/CA

B) Valeur Ajoutée
   └─ Détail charges externes (61, 62)
   └─ VA/CA (%)
   └─ Comparaison secteur

C) EBE vs Exploitation
   └─ Impact amortissements
   └─ EBE/CA (%)
   └─ Tendance

D) Résultat Financier
   └─ Intérêts détail
   └─ Produits financiers
   └─ Coût de la dette
```

#### 4️⃣ RATIOS FINANCIERS (Par catégorie)
```
Titre: RATIOS & INDICATEURS

A) PROFITABILITÉ
   - Marge Brute (%)
   - Marge Exploitation (%)
   - Marge Nette (%)
   - ROIC (%)

B) SOLVABILITÉ & STRUCTURE
   - Ratio Liquidité (actif court terme / passif court terme)
   - Ratio Autonomie financière (capitaux propres / total actif)
   - Endettement (dettes / capitaux propres)
   - Taux coberture intérêts

C) CYCLES TRÉSORERIE
   - DSO Clients (jours)
   - Jours de stock
   - DPO Fournisseurs (jours)
   - Cycle conversion (net)
   - BFR estimation

D) PRODUCTIVITÉ
   - Ratio achats/CA
   - Ratio salaires/CA
   - Ratio frais bancaires/CA
   - Autres charges/CA

Format: Tableau comparatif 3 ans avec seuils/alertes
```

#### 5️⃣ ANALYSE COMMERCIALE (Drill-down disponible)
```
Titre: ANALYSE CLIENTS & FOURNISSEURS

A) Concentration Clients
   - Top 10 clients (montants + %)
   - Dépendance commerciale (% par client)
   - Risque Pareto (si 1 client > 20%)

B) Concentration Fournisseurs
   - Top 10 fournisseurs (montants + %)
   - Dépendance d'approvisionnement
   - Risque concentration

C) Âge des Créances
   - Créances < 30j
   - Créances 30-60j
   - Créances 60-90j
   - Créances > 90j (alerte)
   - Créances douteuses (417)

D) Âge des Dettes
   - Dettes < 30j
   - Dettes 30-60j
   - Dettes 60-90j
   - Dettes > 90j
```

#### 6️⃣ GRAPHIQUES ANALYTIQUES (Non redondants)
```
1. Cascade SIG visuelle (waterfall chart)
   └─ Voir la progression de marge → résultat

2. Évolution 3 ans (CA + Marge + Résultat)
   └─ Tendance générale

3. Décomposition charges exploitation
   └─ Pie chart: achats vs salaires vs autres

4. Cycles trésorerie timeline
   └─ Combien de jours entre paiement client et paiement fournisseur
```

#### 7️⃣ ALERTES & POINTS D'ATTENTION
```
Titre: ⚠️ POINTS D'ATTENTION EXPERT

Affichage dynamique basé sur seuils:
- Endettement élevé? (> 2x)
- Cycle conversion long? (> 60j)
- Solvabilité faible? (< 1.2x)
- Concentration client excessive? (> 15%)
- Concentration fournisseur excessive? (> 30%)
- Créances douteuses? (> 5% des clients)
- Frais financiers élevés? (> 2% CA)
- Rentabilité en baisse? (vs année N-1)

Chaque alerte = cliquable pour drill-down
```

#### 8️⃣ EXPORT & ACTIONS
```
Boutons:
- 📥 Télécharger rapport (PDF)
- 📊 Export Excel détaillé
- 🔗 Comparaison vs année N-1
- 📋 Impression professionnel
```

**Résumé SIG Proposé:**
- 8 sections complètes
- Toutes les données nécessaires
- Format comptable standard
- Drill-down quand besoin
- Alertes intelligentes

---

## 🔄 COMPARISON ACTUEL vs PROPOSÉ

### Avant (DOUBLONS)
```
DASHBOARD (234 lignes)
├─ KPI Stocks (3 cartes)
├─ Trésorerie & Tiers (4 cartes)
├─ Cascade SIG ❌ DOUBLON
├─ Analyse Financière (CA, clients, coûts)
└─ AdvancedAnalytics (CA, clients, ratios, cycles)

SIGPage (150 lignes)
├─ Cascade SIG ❌ DOUBLON
├─ Graphique analyse (très basique)
└─ Fin.
```

### Après (PROPRE)
```
DASHBOARD (Novo - ~200 lignes)
├─ Indicateurs critiques (4 KPI)
├─ Stocks bijouterie (3 KPI)
├─ Trésorerie (3 KPI)
├─ Snapshot financier (6 KPI)
├─ Comparaison annuelle (1 graphique)
└─ Bouton "Analyse Détaillée" → Modal AdvancedAnalytics

SIGPage (Nova - ~400 lignes)
├─ Cascade SIG tableau complet
├─ Analyse détaillée SIG (expandable)
├─ Ratios financiers (4 catégories)
├─ Analyse commerciale (clients, fournisseurs, âges)
├─ Graphiques analytiques (4 graphiques)
├─ Alertes intelligentes
└─ Export & actions

ℹ️ Zéro doublon! Chaque donnée UN seul endroit
```

---

## 📋 KPIs CRITIQUES À RENOMMER/CLARIFIER

```
DASHBOARD:
❌ "Stock Or" + "Total Stock" = confusion
✅ Proposé:
   - "Stock Or" (€)
   - "Stock Diamants" (€)  [NOUVEAU - plus clair]
   - "Stock Total" (€)     [CLAIR maintenant]

DASHBOARD:
❌ "Clients" + "Fournisseurs" dans trésorerie
✅ Proposé:
   - Section "Trésorerie": Banque, Caisse, Trésorerie Nette
   - Section "Crédits & Dettes": Créances clients, Dettes fournisseurs
   
DASHBOARD:
❌ Pas de vrai "trésorerie nette"
✅ Proposé:
   - KPI: "Trésorerie Nette" = Banque + Caisse (en rouge si négatif)
```

---

## 🎬 PLAN D'EXÉCUTION (Si validé)

### PHASE 1: Cleanup Components (1-2h)
```
1. Supprimer les doublons dans AdvancedAnalytics
2. Simplifier DashboardKPISection
3. Renommer les KPI confus
4. Extraire les graphiques Analytics en Modal
```

### PHASE 2: Refacto Dashboard (2-3h)
```
1. Restructurer Dashboard.jsx (6 sections)
2. Créer DashboardCriticalMetrics.jsx
3. Créer DashboardSnapshotFinancial.jsx
4. Ajouter Modal pour AdvancedAnalytics
5. Optimiser: 1 graphique par section max
```

### PHASE 3: Refacto SIGPage (3-4h)
```
1. Ajouter section SIG détaillée avec drill-down
2. Ajouter tableaux ratios financiers
3. Ajouter section analyse clients/fournisseurs
4. Ajouter section alertes intelligentes
5. Ajouter export PDF/Excel
6. Améliorer graphiques analytiques
```

### PHASE 4: Test & Validation (1-2h)
```
1. Test Dashboard: "Coup d'œil 2min" check
2. Test SIGPage: "Expert comptable" check
3. Vérifier zéro doublons
4. Vérifier UX sur mobile
```

### PHASE 5: Documentation (30min)
```
1. Maj ARCHITECTURE_GUIDELINES.md
2. Créer COMPONENT_STRUCTURE.md
3. Maj QUICKSTART.md avec nouvelles sections
```

**Durée totale estimée: 7-12h (1 jour)**

---

## ✅ CHECKLIST VALIDATION

Avant de démarrer, valider:

- [ ] Chef d'entreprise: "Je peux prendre décision en 2-3min"?
- [ ] Expert comptable: "J'ai tous les éléments pour analyser"?
- [ ] Développeur: "Zéro doublons dans le code"?
- [ ] Pas de "redirection forcée" entre les pages
- [ ] Mobile: "Reste lisible sur petit écran"?
- [ ] Performance: "Pas de requête API dupliquée"?

---

## 📞 QUESTIONS À VALIDER

1. **Dashboard - Doublons OK à supprimer?**
   - Retirer cascade SIG complète?
   - Retirer affichages dupliqués de clients/fournisseurs?

2. **SIG - Niveau de détail bon?**
   - Faut-il encore plus de drill-down?
   - Faut-il limiter à TOP 5 clients au lieu de TOP 10?

3. **KPI métier - Complet?**
   - Manque un indicateur? (ex: Ratios secteur bijouterie?)
   - DSO/DPO/BFR assez visibles?

4. **Modal Analytics - OK pour le détail?**
   - OK que chef clique pour creuser?
   - Ou faut-il une page séparée "Analytics"?

5. **SIG - Export nécessaire?**
   - PDF pour audit externe?
   - Excel pour analyse en détail?

---

## 📚 FICHIERS À MODIFIER

```
Frontend:
✏️ /frontend/src/pages/Dashboard.jsx              (refacto major)
✏️ /frontend/src/pages/SIGPage.jsx                (refacto major)
✏️ /frontend/src/components/AdvancedAnalytics.jsx (extraction modal)
✏️ /frontend/src/components/dashboard/           (nouveaux composants)
   ├─ DashboardCriticalMetrics.jsx (NEW)
   ├─ DashboardSnapshotFinancial.jsx (NEW)
   ├─ DashboardKPISection.jsx (RENAME stocks)
   └─ index.js (EXPORT new)

Documentation:
✏️ ARCHITECTURE_GUIDELINES.md                      (sections pages)
✏️ QUICK_START_NEW_COMPONENT.md                   (exemples)
✏️ docs/MENUS_STRUCTURE.md                        (NEW)

Pas de changement backend (APIs OK).
```

---

## 🎯 RÉSUMÉ EXÉCUTIF

```
OBJECTIF: Éliminer doublons, créer 2 menus distincts et complémentaires

DASHBOARD (Nova):
- ✅ Rapide (2-3min)
- ✅ Décisionnel (chef d'entreprise)
- ✅ 6 sections essentielles
- ✅ 0 cascades SIG
- ✅ Modal pour détail si intéressé

SIG PAGE (Nova):
- ✅ Complet (expert comptable)
- ✅ 8 sections détaillées
- ✅ Cascade SIG + ratios + alertes
- ✅ Export PDF/Excel
- ✅ Drill-down quand besoin

RÉSULTAT:
- 0 doublon
- Chaque menu = 1 objectif clair
- Meilleure UX
- Meilleure maintenabilité
```

---

**📌 À FAIRE:** Valider ce plan avant de démarrer l'implémentation.
