# 📈 Formules SIG - Expert Comptable Bijouterie

## Plan Comptable Général 2025 - Adaptations Bijouterie

### Cascade Complète Soldes Intermédiaires de Gestion

```
MARGE DE PRODUCTION (MP)
= (70 + 71 + 72) - (601 + 602 ± 603)
│
├─ 70: Ventes de marchandises (bijoux fabriqués/vendus)
├─ 71: Production stockée (pièces en cours, stock travail)
├─ 72: Production immobilisée (éléments patrimoine atelier)
│
├─ MOINS 601: Achats matières premières
│  └─ Or, argent, pierres précieuses, diamants
│
├─ MOINS 602: Achats fournitures
│  └─ Composants, outils, consommables
│
└─ +/- 603: Variation stocks
   └─ Stock final - Stock initial (signe!)
   └─ IMPORTANT: Tous en-cours bijouterie inclus


VALEUR AJOUTÉE (VA)
= MP - (61 + 62)
│
├─ Formule: Richesse CRÉÉE par l'entreprise
│
├─ MOINS 61: Services extérieurs
│  └─ Sous-traitance (gravure, sertissage externe)
│
└─ MOINS 62: Autres services extérieurs
   └─ Assurances, frais divers (protection marchandise)


EXCÉDENT BRUT D'EXPLOITATION (EBE / EBITDA)
= VA + 74 - (63 + 64 + 68*)
│
├─ Formule: Cash généré avant intérêts, impôts, amortissements
│  └─ Mesure CAPACITÉ AUTOFINANCEMENT
│
├─ PLUS 74: Produits exceptionnels
│  └─ Or de récupération valorisé (fonte stocks)
│
├─ MOINS 63: Impôts et taxes
│  └─ CVAE (Cotisation Valeur Ajoutée Entreprise)
│  └─ Taxes atelier, patentes
│
├─ MOINS 64: Charges de personnel
│  └─ ⚠️ TRÈS IMPORTANT POUR BIJOUTERIE!
│  └─ Salaire apprentis (formation métier)
│  └─ Contributions sociales
│
└─ MOINS 68*: Éléments exceptionnels UNIQUEMENT
   └─ ⚠️ ATTENTION: N'inclure PAS les amortissements (ils vont en 681)


RÉSULTAT D'EXPLOITATION (RE)
= EBE - 681
│
├─ Formule: Rentabilité du MÉTIER en soi
│  └─ Avant intérêts et impôts
│
└─ MOINS 681: Amortissements et provisions
   └─ Tour de bijoutier (5 ans)
   └─ Établi bijoutier, mobilier atelier (10 ans)
   └─ Équipement électrique (5-10 ans)
   └─ ⚠️ Charge NON-CASH! Importante pour cash flow


RÉSULTAT FINANCIER (RF)
= 69 - 76
│
├─ Formule: Impact financements et placements
│
├─ MOINS 69: Charges financières
│  ├─ Intérêts emprunts exploitation (crédit court terme)
│  └─ Intérêts emprunts investissement (crédit long terme)
│
└─ PLUS 76: Produits financiers
   └─ Intérêts comptes courants (rare pour atelier)


RÉSULTAT NET (RN)
= RE + RF - 69 (Impôt si applicable)
│
├─ Formule: Bénéfice / Perte FINAL
│
├─ ⚠️ Bijouterie SOUVENT = Micro-entreprise
│  └─ Pas de calcul IS (Impôt Sociétés)
│  └─ Prélèvements sociaux TNS (Travailleur Non Salarié)
│
└─ À comparer: Salaire patron + bénéfice = revenu total

```

---

## 📊 Données Source et Calculs

### Source: Table `fin_balance` (après import FEC)

```sql
-- Après import et agrégation
SELECT 
    compte_num,
    SUM(debit) as debit,
    SUM(credit) as credit,
    SUM(debit) - SUM(credit) as solde
FROM fin_ecritures_fec
WHERE exercice = 2024
GROUP BY compte_num;

-- Exemple résultat:
-- 70:     débit=0,   crédit=28500,  solde=-28500  (négatif=produit)
-- 601:    débit=11650, crédit=0,     solde=11650   (positif=charge)
-- 641:    débit=15000, crédit=0,     solde=15000   (positif=charge)
-- 51200:  débit=8700,  crédit=5350,  solde=3350    (positif=cash)
```

### Gestion des Signes Comptables

**Principe fondamental:**
```
Solde = Débit - Crédit

Classe 1-5 (Actif/Passif):
  - Solde > 0: Débit prédominant (emploi)
  - Solde < 0: Crédit prédominant (ressource)

Classe 6 (Charges):
  - Solde > 0: Débit prédominant (consommations)
  - Solde < 0: EXCEPTION (produits rattachés)

Classe 7 (Produits):
  - Solde < 0: Crédit prédominant (NORMAL)
  - Solde > 0: EXCEPTION (charges rattachées)
```

**Formules calcul SIG:**
```php
// Charges (classe 6): ajouter valeur absolue
$charges_matieres = abs($compte_601->solde) + abs($compte_602->solde);

// Produits (classe 7): soustraire (= ajouter négatif)
$marge = abs($compte_70->solde) - abs($compte_601->solde);

// Résumé: toujours utiliser logique ALGÉBRIQUE (signes)
```

---

## 🔍 Implémentation PHP - SigCalculator.php

### Structure

```php
<?php
namespace App\Services;

class SigCalculator {
    private $db;              // Database::getInstance()
    private $exercice;        // Année comptable
    private $balances = [];   // Cache soldes par compte
    
    public function __construct($exercice = null) {
        $this->exercice = $exercice ?? date('Y');
    }
    
    // =========== MÉTHODES PUBLIQUES ===========
    
    /**
     * Calcule TOUS les SIG
     * @return array [mp, va, ebe, re, rf, rn]
     */
    public function calculateSIG() {
        $this->loadBalances();
        
        $mp = $this->calculateMargeProduction();
        $va = $this->calculateValeurAjoutee();
        $ebe = $this->calculateEBE();
        $re = $this->calculateResultatExploitation();
        $rf = $this->calculateResultatFinancier();
        $rn = $this->calculateResultatNet($re, $rf);
        
        return [
            'marge_production' => $mp,
            'valeur_ajoutee' => $va,
            'ebe' => $ebe,
            'resultat_exploitation' => $re,
            'resultat_financier' => $rf,
            'resultat_net' => $rn,
        ];
    }
    
    // =========== FORMULE 1: MARGE DE PRODUCTION ===========
    
    /**
     * MP = (70 + 71 + 72) - (601 + 602 ± 603)
     * 
     * Pour bijouterie:
     * - 70: Ventes bijoux
     * - 71: Stock travail en cours
     * - 72: Production immobilisée
     * - 601: Or, argent, pierres (matières)
     * - 602: Outils, fournitures consommables
     * - 603: Variation stocks (stock_final - stock_initial)
     */
    private function calculateMargeProduction() {
        // Production: somme classe 7 (produits)
        $produits = $this->sumSoldes(['70', '71', '72']);
        
        // Charges matières
        $charges_matieres = $this->sumSoldes(['601', '602']);
        
        // Variation stocks
        $variation_stocks = $this->getSolde('603');
        
        // Formule: Produits - Charges + Variation
        // (Variation: si positive = augmentation stock = à soustraire)
        $mp = $produits - $charges_matieres - $variation_stocks;
        
        return round($mp, 2);
    }
    
    // =========== FORMULE 2: VALEUR AJOUTÉE ===========
    
    /**
     * VA = MP - (61 + 62)
     * 
     * Richesse créée après déduction services externes
     */
    private function calculateValeurAjoutee() {
        $mp = $this->calculateMargeProduction();
        
        // Services extérieurs
        $services = $this->sumSoldes(['61', '62']);
        
        $va = $mp - $services;
        
        return round($va, 2);
    }
    
    // =========== FORMULE 3: EBE / EBITDA ===========
    
    /**
     * EBE = VA + 74 - (63 + 64 + 68*)
     * 
     * Capacité autofinancement (avant intérêts/impôts/amort)
     */
    private function calculateEBE() {
        $va = $this->calculateValeurAjoutee();
        
        // Produits exceptionnels
        $produits_except = $this->getSolde('74');
        
        // Impôts et taxes
        $impots_taxes = $this->getSolde('63');
        
        // Charges de personnel
        $personnel = $this->getSolde('64');
        
        // Éléments exceptionnels (SANS amortissements!)
        $except_charges = $this->getSolde('68') - $this->getSolde('681');
        
        $ebe = $va + $produits_except - $impots_taxes - $personnel - $except_charges;
        
        return round($ebe, 2);
    }
    
    // =========== FORMULE 4: RÉSULTAT D'EXPLOITATION ===========
    
    /**
     * RE = EBE - 681
     * 
     * Rentabilité du métier (avant intérêts/impôts)
     */
    private function calculateResultatExploitation() {
        $ebe = $this->calculateEBE();
        
        // Amortissements
        $amortissements = $this->getSolde('681');
        
        $re = $ebe - $amortissements;
        
        return round($re, 2);
    }
    
    // =========== FORMULE 5: RÉSULTAT FINANCIER ===========
    
    /**
     * RF = 69 - 76
     * 
     * Impact financements et placements
     */
    private function calculateResultatFinancier() {
        // Charges financières
        $charges_fin = $this->getSolde('69');
        
        // Produits financiers
        $produits_fin = $this->getSolde('76');
        
        $rf = -$charges_fin + $produits_fin;
        
        return round($rf, 2);
    }
    
    // =========== FORMULE 6: RÉSULTAT NET ===========
    
    /**
     * RN = RE + RF - 69 (impôt si IS)
     * 
     * Bénéfice / Perte final
     */
    private function calculateResultatNet($re, $rf) {
        $rn = $re + $rf;
        
        // Note: Pour micro-entreprise, impôt IS = 0
        // Pour SARL/EURL: déduire IS (compte 695 si applicable)
        
        return round($rn, 2);
    }
    
    // =========== MÉTHODES UTILITAIRES ===========
    
    /**
     * Charge les soldes de balance en cache
     */
    private function loadBalances() {
        if (!empty($this->balances)) return;
        
        $rows = $this->db->fetchAll(
            "SELECT compte_num, debit, credit, solde 
             FROM fin_balance 
             WHERE exercice = ?",
            [$this->exercice]
        );
        
        foreach ($rows as $row) {
            $this->balances[$row['compte_num']] = [
                'debit' => (float) $row['debit'],
                'credit' => (float) $row['credit'],
                'solde' => (float) $row['solde']
            ];
        }
    }
    
    /**
     * Récupère solde d'un compte (algébrique)
     */
    private function getSolde($compte) {
        $this->loadBalances();
        return $this->balances[$compte]['solde'] ?? 0;
    }
    
    /**
     * Somme soldes multiples comptes
     */
    private function sumSoldes($comptes) {
        $sum = 0;
        foreach ((array) $comptes as $compte) {
            $sum += $this->getSolde($compte);
        }
        return $sum;
    }
}
```

---

## ✅ Validation Mathématique

### Exemple numérique (bijouterie 2024)

```
FEC importé:
- 70: crédit 28500  → solde = -28500 (produit)
- 71: débits 500    → solde = 500    (stock)
- 601: débit 11650  → solde = 11650  (matières)
- 602: débit 3000   → solde = 3000   (fournitures)
- 603: débit 2000   → solde = 2000   (variation stock)
- 61: débit 500     → solde = 500    (sous-traitance)
- 62: débit 1800    → solde = 1800   (assurance)
- 63: débit 600     → solde = 600    (CVAE)
- 64: débit 15000   → solde = 15000  (salaire apprenti)
- 74: crédit 1500   → solde = -1500  (or récupération)
- 681: débit 500    → solde = 500    (amort tour)
- 69: débit 200     → solde = 200    (intérêts)
- 76: crédit 50     → solde = -50    (produits fin)

CALCULS:

1. Marge Production
   = (70+71+72) - (601+602±603)
   = (-28500 + 500 + 0) - (11650 + 3000 + 2000)
   = -28000 - 16650
   = -44650  ❌ ERREUR!

Correction (signes algebraiques):
   Produits = 70 + 71 + 72
           = 28500 + 500 + 0 = 29000 (valeur absolue)
   
   Charges = 601 + 602 = 11650 + 3000 = 14650
   
   Variation = 2000 (augmentation stock = réduit marge)
   
   MP = 29000 - 14650 - 2000 = 12350 ✓

2. Valeur Ajoutée
   = MP - (61 + 62)
   = 12350 - (500 + 1800)
   = 12350 - 2300
   = 10050 ✓

3. EBE
   = VA + 74 - (63 + 64)
   = 10050 + 1500 - (600 + 15000)
   = 11550 - 15600
   = -4050 ❌ NÉGATIF!
   
   Interprétation: Charges personnelles très fortes
   (apprentissage coûteux vs peu de production)

4. Résultat d'Exploitation
   = EBE - 681
   = -4050 - 500
   = -4550 (perte exploitation)

5. Résultat Financier
   = -69 + 76
   = -200 + 50
   = -150 (coût financement)

6. Résultat Net
   = RE + RF
   = -4550 + (-150)
   = -4700 (perte nette)
```

### Interprétation Expert

Ce FEC test montre:
- ✓ Production: 29k€
- ✓ Marge brute: 12.3k€ (42% production) = acceptable bijouterie
- ⚠️ Valeur ajoutée: 10k€ (86% MP) = bonne création in-house
- ❌ EBE négatif: problème!
  - Charges personnel trop fortes (15k€) vs production
  - Ou: FEC test incomplet (6 mois seulement?)

**Pour année complète:** multiplier x 2 → RE devrait être positif

---

## 📋 Checklist Implémentation

- [ ] FecAnalyzer.php testée (format, normalisation, validation)
- [ ] ImportService.php intégrée FecAnalyzer
- [ ] API /analyze/fec implémentée et fonctionnelle
- [ ] API /import/fec utilise FecAnalyzer avant import
- [ ] SigCalculator.php implémentée correctement
- [ ] Calculs SIG testés avec données réelles
- [ ] FecAnalysisDialog.jsx affiche résultats
- [ ] SigFormulaVerifier.jsx affiche formules + validation
- [ ] ImportPage.jsx intègre les 2 composants
- [ ] Dashboard affiche résultats SIG
- [ ] Workflow complet testé bout-en-bout

---

## 🎓 Points Clés à Valider Ensemble

### Questions pour Expert Comptable

1. **Plan Comptable**
   - [ ] Comptes 70/71/72 pour produits bijouterie: corrects?
   - [ ] Comptes 601/602 pour matières/fournitures: corrects?
   - [ ] Comptes 64 pour personnel: inclure patron?

2. **Calculs SIG**
   - [ ] Formules correspondent au PCG 2025?
   - [ ] Gestion des signes comptables correcte?
   - [ ] Variation stocks (603) traitée correctement?

3. **Bijouterie Spécifique**
   - [ ] Valorisation stocks métaux précieux?
   - [ ] Traitement or de récupération (compte 74)?
   - [ ] Amortissement outils (durée: 5 ou 10 ans)?
   - [ ] Apprentissage impacte-t-il modèle économique?

4. **Robustesse**
   - [ ] Format FEC: tolérances appropriées?
   - [ ] Seuils anomalies: bloquant vs warning?
   - [ ] Recommandations nettoyage: pertinentes?

---

**Document validé:** ⏳ En attente validation ensemble
