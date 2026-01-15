# 👥 Guide Utilisateur - Application COMPTA

## 📋 Table des Matières

1. [Accueil & Navigation](#accueil--navigation)
2. [Dashboard](#dashboard)
3. [SIG (Soldes Intermédiaires de Gestion)](#sig-soldes-intermédiaires-de-gestion)
4. [Import FEC](#import-fec)
5. [Conseils & Bonnes Pratiques](#conseils--bonnes-pratiques)

---

## Accueil & Navigation

### Structure de l'Application

L'application COMPTA est organisée en plusieurs sections principales:

```
┌─────────────────────────────────┐
│      NAVIGATION PRINCIPALE       │
├─────────────────────────────────┤
│ 📊 Dashboard                     │  Vue globale + KPIs
│ 📈 SIG                           │  Analyse financière détaillée
│ 📥 Import                        │  Importer fichiers FEC
│ 💾 Balance                       │  Balance comptable
│ ⚙️  Paramètres                   │  Configuration
└─────────────────────────────────┘
```

### Sélecteur d'Année

Toutes les pages permettent de sélectionner l'**exercice** (année fiscale) en haut à gauche:

- Clique sur le dropdown **"Exercice"**
- Sélectionne l'année désirée
- Les données se mettent à jour automatiquement

---

## Dashboard

### Vue d'ensemble

Le Dashboard offre une **vue holistique** de vos données financières avec:

1. **KPIs Principaux** - Indicateurs clés de performance
2. **SIG Cascade** - Soldes Intermédiaires de Gestion
3. **👥 Analyse des Tiers** - *(Nouveau Phase 4)*
4. **💰 Analyse du Cashflow** - *(Nouveau Phase 4)*
5. **📈 Analyse Financière** - Graphiques détaillés
6. **🔬 Analytics Avancée** - Statistiques poussées

### 1️⃣ KPIs Principaux

Affiche 4-6 indicateurs clés en cartes coloriées:

```
┌─────────────┬─────────────┬─────────────┐
│   Chiffre   │  Résultat   │ Trésorerie  │
│ d'Affaires  │ d'Exercice  │   Nette     │
├─────────────┼─────────────┼─────────────┤
│ €2.4M       │ €180K ✓     │ €250K ✓     │
└─────────────┴─────────────┴─────────────┘
```

**Comment lire:**
- 🟢 Vert = Positif (bon)
- 🔴 Rouge = Négatif (attention)
- 🔵 Bleu = Neutre

### 2️⃣ SIG Cascade (SIGPage)

Affiche l'état de toutes les étapes de la cascade SIG:

```
Ventes
  ↓
- Achats
  ↓
= Marge Brute
  ↓
- Charges
  ↓
= Résultat Net
```

Voir **section SIG** pour plus de détails.

### 3️⃣ Analyse des Tiers (NEW - Phase 4)

Tableau complet de tous vos clients/fournisseurs:

| Feature | Description |
|---------|-------------|
| 🔍 Recherche | Cherche par numéro ou nom |
| 📊 Tri | Trie par montant/nom/écritures |
| 📋 Pagination | 5/10/25/50 lignes par page |
| 💾 Colonnes | Debit, Crédit, Solde, Écritures |

**Exemple d'utilisation:**
1. Cherche "GOLDMAN" dans la recherche
2. Clique sur la ligne pour voir détail du tiers
3. Observe ses écritures et solde

### 4️⃣ Analyse du Cashflow (NEW - Phase 4)

**4 Onglets:**

#### Onglet 1: Par Période
- Affiche graphique **Bar Chart** Entrées vs Sorties
- Permet voir les mois actifs

#### Onglet 2: Par Journal
- **Pie chart** répartition par journal (VE, AC, CM...)
- Clique sur un journal → voir détails

#### Onglet 3: Détail Journal
- Tableau **Top 5 comptes** du journal
- Stats par jour d'activité

#### Onglet 4: Top Comptes
- Tous les comptes avec débit/crédit/solde
- Trier par montant

### Comparer les Années

**Bouton:** "Comparer les années" (haut droit)

1. Coches 2+ années
2. Clique "Comparer"
3. Vois la **vue comparative** côte à côte

---

## SIG (Soldes Intermédiaires de Gestion)

### Qu'est-ce que le SIG?

Le SIG mesure la **profitabilité en cascade**:

```
📊 Chiffre d'Affaires (CA)
   ↓
   - Coût des Marchandises Vendues (CMV)
   ↓
🟢 = Marge Brute
   ↓
   - Charges Opérationnelles
   ↓
🟢 = Résultat d'Exploitation
   ↓
   +/- Éléments Financiers
   ↓
🟢 = Résultat Net
```

### Interface SIGPage (Phase 5)

La SIGPage offre **4 onglets:**

#### 1️⃣ Cascade SIG

Affiche chaque indicateur en **cartes visuelles**:

```
┌──────────────────┐
│ CHIFFRE AFFAIRES │
│  €23.4M          │ 🟢
└──────────────────┘

┌──────────────────┐
│ MARGE BRUTE      │
│  €8.2M           │ 🟢
└──────────────────┘
```

**Couleurs:**
- 🟢 Vert = Positif
- 🔴 Rouge = Négatif

#### 2️⃣ Graphiques

**Affiche 2 graphiques:**

**A) Bar Chart Cascade SIG**
- Hauteur = montant de chaque indicateur
- Permet voir la progression visuelle

**B) Composed Chart Cashflow**
- Entrées (barres vertes)
- Sorties (barres bleues)
- Flux Net (ligne orange)
- Par période (mois)

#### 3️⃣ Détails

**Tableau complet** avec colonnes:
- Indicateur (libellé)
- Montant (valeur)
- Statut (Positif/Négatif)
- Description

#### 4️⃣ Comparaison Cashflow

Intègre les données **Phase 3** directement:
- **4 KPIs**: Total Entrées/Sorties/Solde/Écritures
- **Tableau par Journal**: Montant, Écritures, %

---

## Import FEC

### Qu'est-ce qu'un FEC?

**FEC** = **Fichier des Écritures Comptables**
- Fichier TAB-delimited avec toutes les écritures
- Format français standardisé (douanes)
- 18 colonnes obligatoires

### Où trouver le FEC?

Dans ton logiciel comptable (Sage, Ciel, etc.):
1. Menu **Fichier** → **Exporter**
2. Choisis **FEC** ou **Export TAB**
3. Sélectionne l'exercice
4. Sauvegarde le fichier

### Comment Importer?

1. Clique **"Import"** dans la navigation
2. Charge ton fichier FEC (drag & drop ou browse)
3. Attends la validation ✅
4. Confirme l'import

**Le système:**
- ✅ Valide 11,617+ écritures en 0.34s
- ✅ Crée la balance automatiquement
- ✅ Index les tiers et journaux

---

## Conseils & Bonnes Pratiques

### 📊 Analyse Financière

**1. Lire les KPIs d'abord**
- Commence par le Dashboard
- Observe les 4-6 KPIs principaux
- Note les anomalies

**2. Creuser avec le SIG**
- Va au SIG pour voir la cascade
- Identifie où se situe le problème
- Marge brute? Charges? Résultat?

**3. Analyser les Tiers**
- Dashboard → Analyse des Tiers
- Cherche les tiers avec gros solde
- Clique pour voir leurs écritures

**4. Étudier le Cashflow**
- SIGPage → Tab Cashflow
- Vois la répartition par journal
- Analyse par jour pour volatilité

### 💡 Optimisation

**Naviguer efficacement:**
- Utilise la **recherche** pour les tiers
- Trie par **montant** pour voir les gros
- Change d'**exercice** pour comparaison
- Utilise **Comparer** pour tendances

**Exporter les données:**
- Clique droit sur tableau → Copier
- Clique droit sur graphique → Télécharger PNG
- Ou utilise l'API JSON `/api/tiers`, `/api/cashflow`

### ⚠️ Points d'Attention

**Balance Comptable:**
- Doit TOUJOURS être €0.00
- Si pas zéro → erreur import
- Réimporte le FEC

**Doublons de Tiers:**
- Vérifie les nombres (08000009 vs 08000090)
- Recherche les variantes de noms
- Consolide si nécessaire

**Écritures Lettrées:**
- "Lettrées" = paiement confirmé
- Non lettrées = en attente
- Important pour trésorerie

---

## FAQ

### Q: Pourquoi mon solde n'est pas bon?
**R:** 
1. Vérifies le FEC source
2. Réimporte avec nouveau FEC
3. Contacts le support si persiste

### Q: Où voir les écritures détaillées?
**R:** 
- Dashboard → Analyse des Tiers → Clique tiers
- Voir toutes ses écritures ligne par ligne

### Q: Comment exporter un rapport?
**R:**
- Clique droit sur graphique → PNG
- Clique droit sur tableau → CSV/Copier
- Ou API JSON pour intégration

### Q: Quel est le meilleur journal pour ventes?
**R:** 
- **VE** = Ventes
- C'est le journal des factures client

### Q: Comment savoir si on est bénéficiaire?
**R:**
- Aller au SIG
- Chercher "Résultat Net"
- 🟢 Positif = Bénéfice
- 🔴 Négatif = Perte

---

## Support

**Documentation:**
- [API Complète](./API_DOCUMENTATION.md)
- [Releases Notes](./RELEASES.md)

**Contacts:**
- Bugs: `issues@compta.local`
- Features: `features@compta.local`

---

**Version:** 1.0  
**Dernière mise à jour:** 2024-01-15  
**Licence:** MIT
