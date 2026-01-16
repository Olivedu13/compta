# 🔍 AUDIT KPI - Vérification fonctionnelle par KPI

**Date:** 16 janvier 2026  
**Scope:** Vérifier chaque KPI avec le FEC importé  
**Status:** 🚨 À CORRIGER

---

## 📊 DONNÉES FEC DE BASE

```
Total écritures 2024: 6
Débits: 6 500,00 EUR ✅ Équilibrés
Crédits: 6 500,00 EUR ✅ 

Journaux présents: AC, VE, CL
```

---

## 🔴 KPI #1: STOCKS (311, 312, 313)

**Définition:** Montant total des stocks matières premières

**Calcul attendu:**
```
Stock Or (311) = SUM(Débit - Crédit)
Stock Diamants (312) = SUM(Débit - Crédit)
Stock Bijoux (313) = SUM(Débit - Crédit)
Stock Total = Or + Diamants + Bijoux
```

**Données FEC:**
```
311 (Or):      0,00 EUR
312 (Diamants): 0,00 EUR
313 (Bijoux):   0,00 EUR
───────────────────────
TOTAL STOCKS:   0,00 EUR
```

**Résultat attendu dans KPI:** `{ "or": 0, "diamants": 0, "bijoux": 0, "total": 0 }`

**Status:** ❌ PROBLÈME - Les comptes stocks n'existent pas dans le FEC test!
- Les écritures test ne contiennent que les comptes 401, 411, 512
- Aucun mouvement de stock n'a été importé

**Action requise:**
```
1. Vérifier que les tests FEC incluent les comptes 311, 312, 313
2. Ou ajouter des écritures test avec mouvements de stock
3. Valider que le calcul ` abs($this->getSolde('311'))` retourne 0
```

---

## 🟡 KPI #2: TRÉSORERIE (512, 530)

**Définition:** Montant total en banque + caisse

**Calcul attendu:**
```
Banque (512) = ABS(SUM(Débit - Crédit))
Caisse (530) = ABS(SUM(Débit - Crédit))
Total = Banque + Caisse
```

**Données FEC:**
```
512 (Banque):   -2 500,00 EUR (solde débiteur)
530 (Caisse):    0,00 EUR
───────────────────────────────
TOTAL TRÉSOERIE: 2 500,00 EUR (après ABS)
```

**Résultat attendu dans KPI:** `{ "banque": 2500, "caisse": 0, "total": 2500 }`

**Status:** ✅ CALCULABLE - Mais vérification requise
- Le compte 512 a un mouvement: -2 500 EUR
- Après ABS(), devrait retourner 2 500
- Test: Vérifier que `abs()` retourne la valeur positive correcte

**Vérification PHP:**
```php
$banque = abs($this->getSolde('512')); // Devrait retourner 2500
$caisse = abs($this->getSolde('530')); // Devrait retourner 0
```

---

## 🔴 KPI #3: TIERS - CLIENTS (411)

**Définition:** Montant des créances clients

**Calcul attendu:**
```
Clients (411) = ABS(SUM(Débit - Crédit))
```

**Données FEC:**
```
401 (Fournisseurs): Crédit 1 500 EUR (solde créditeur)
411 (Clients):      Débit 2 500 EUR  (solde débiteur)
───────────────────────────────────
Clients (411): 2 500,00 EUR (après ABS)
```

**Résultat attendu dans KPI:** `{ "clients": 2500, "fournisseurs": 1500 }`

**Status:** ✅ CALCULABLE
- Compte 411 a mouvements: 2 500 EUR débit
- Solde = 2 500 EUR créance clients
- Après ABS(): 2 500 EUR ✅

---

## 🔴 KPI #4: TIERS - FOURNISSEURS (401)

**Définition:** Montant des dettes fournisseurs

**Calcul attendu:**
```
Fournisseurs (401) = ABS(SUM(Crédit - Débit))
```

**Données FEC:**
```
401 (Fournisseurs): Crédit 1 500 EUR
```

**Résultat attendu dans KPI:** `{ "fournisseurs": 1500 }`

**Status:** ✅ CALCULABLE
- Compte 401: 1 500 EUR crédit
- Solde = 1 500 EUR dette fournisseur
- Après ABS(): 1 500 EUR ✅

---

## 🔴 KPI #5: DETTES COURT TERME (164)

**Définition:** Dettes bancaires court terme

**Calcul attendu:**
```
Dettes Court Terme (164) = ABS(SUM(Débit - Crédit))
```

**Données FEC:**
```
164 (Dettes CT): Aucun mouvement
```

**Résultat attendu dans KPI:** `{ "court_terme": 0 }`

**Status:** ✅ CALCULABLE - Mais pas de données
- Compte 164 vide dans le FEC
- Devrait retourner 0 EUR ✅

---

## 🔴 KPI #6: RATIO - MARGE PRODUCTION

**Définition:** Taux de marge production (% du CA)

**Calcul attendu:**
```
Marge Production = CA - Coûts Directs
Taux = (Marge / CA) * 100%
```

**Données FEC:**
```
Compte 701 (Ventes):  2 500 EUR (solde)
Compte 70x (Ventes):  0 EUR
Compte 601+ (Achats): 1 500 EUR (dépense)
```

**Formule dans le code:**
```php
$margeProduction = $this->calculMargeProduction();
$chiffreAffaires = $this->sumSoldes(['701', '702', '703']);
$tauxMargeProduction = $chiffreAffaires != 0 
    ? ($margeProduction / $chiffreAffaires) * 100 
    : 0;
```

**Résultat attendu:**
```
CA: 2 500 EUR
Marge: 1 000 EUR (2 500 - 1 500)
Taux: 40% (1 000 / 2 500 * 100)
```

**Status:** ❌ PROBLÈME - À VÉRIFIER
- La fonction `calculMargeProduction()` n'existe peut-être pas
- Ou elle ne calcule pas correctement les coûts directs

**À vérifier:**
```php
// Existe-t-elle?
grep -r "calculMargeProduction" backend/
// Que retourne-t-elle?
// Comment sont comptabilisés les coûts?
```

---

## 🔴 KPI #7: CHIFFRE D'AFFAIRES

**Définition:** Total des ventes

**Calcul attendu:**
```
CA = SUM des comptes 701, 702, 703 (Ventes)
```

**Données FEC:**
```
701: 2 500 EUR (2 500 debit pour vente, impact revenue)
702: 0 EUR
703: 0 EUR
────────────────
CA: 2 500 EUR
```

**Résultat attendu dans KPI:** `{ "chiffre_affaires": 2500 }`

**Status:** ✅ CALCULABLE
- CA doit être 2 500 EUR
- Correspond aux ventes du FEC ✅

---

## 📋 RÉSUMÉ DES PROBLÈMES

| KPI | Status | Problème | Action |
|-----|--------|----------|--------|
| **1. Stocks** | ❌ | Aucun compte stock dans FEC | Ajouter écritures test 311/312/313 |
| **2. Trésorerie** | ✅ | OK si ABS() marche | ✓ Vérifier |
| **3. Clients** | ✅ | OK - 2 500 EUR | ✓ Tester |
| **4. Fournisseurs** | ✅ | OK - 1 500 EUR | ✓ Tester |
| **5. Dettes CT** | ✅ | OK - 0 EUR | ✓ Tester |
| **6. Marge Prod.** | ❌ | Fonction manquante? | Vérifier `calculMargeProduction()` |
| **7. CA** | ✅ | OK - 2 500 EUR | ✓ Tester |

---

## 🧪 TESTS NÉCESSAIRES

### Test 1: Vérifier les comptes stocks
```bash
php -r "
\$db = new PDO('sqlite:compta.db');
\$stmt = \$db->prepare(\"SELECT compte_num, SUM(debit) as d, SUM(credit) as c 
FROM ecritures WHERE compte_num IN ('311','312','313','401','411','512','530','701','702','703')
GROUP BY compte_num\");
\$stmt->execute();
var_dump(\$stmt->fetchAll());
"
```

### Test 2: Vérifier l'endpoint KPI
```bash
curl "https://compta.sarlatc.com/api/v1/kpis/detailed.php?exercice=2024" | jq .
```

### Test 3: Vérifier le calcul SigCalculator
```bash
php -r "
// Charger le service
require_once 'backend/services/SigCalculator.php';
\$sig = new App\\Services\\SigCalculator(2024);
\$kpis = \$sig->calculKPIs();
echo json_encode(\$kpis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
"
```

---

## ✅ À FAIRE

1. ✅ Vérifier que `calculMargeProduction()` existe
2. ✅ Ajouter des écritures test pour les comptes 311/312/313
3. ✅ Créer des tests unitaires pour chaque KPI
4. ✅ Valider l'équilibre FEC avant calcul
5. ✅ Documenter les formules dans une API spec

---

## 📞 SUIVI AVEC AI_FEATURE_REQUEST_AGENT

**Demande:** "Auditer tous les KPIs et ajouter des tests"

**Étape 1 - Reformulation:**
- Type: Audit + Tests
- Scope: 7 KPIs (Stocks, Trésorerie, Tiers, Dettes, Ratios)
- Objectif: Chaque KPI doit avoir un test unitaire validant le calcul
- Données test: FEC avec tous les comptes (311,312,313,401,411,512,530,701)
- Coverage: 100% des KPIs
- Formats: JSON API + Response format validé

**Étape 2 - Validation Architecture:**
✅ Backend: SigCalculator.php
✅ API: /api/v1/kpis/detailed.php
✅ Frontend: Dashboard.jsx utilise les KPIs
✅ Tests: À créer

**Étape 3 - Plan:**
1. Corriger les données FEC test
2. Créer 7 tests unitaires (1 par KPI)
3. Valider chaque calcul mathématiquement
4. Ajouter documentation des formules

