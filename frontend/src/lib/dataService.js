/**
 * dataService - Récupère toutes les données depuis les APIs serveur
 * Plus de calcul côté client : tout vient du backend PHP
 */

const API_BASE = '/api';

/**
 * Récupère la liste des exercices disponibles
 */
export const fetchYears = async () => {
  const res = await fetch(`${API_BASE}/v1/years/list.php`);
  if (!res.ok) throw new Error('Erreur récupération exercices');
  const json = await res.json();
  if (!json.success) throw new Error(json.error || 'Erreur API years');
  return json.data; // [2025, 2024, ...]
};

/**
 * Upload un fichier FEC vers le serveur
 * @returns {{ count, exercice, is_balanced }}
 */
export const uploadFEC = async (file) => {
  const form = new FormData();
  form.append('file', file);
  const res = await fetch(`${API_BASE}/simple-import.php`, {
    method: 'POST',
    body: form,
  });
  const json = await res.json();
  if (!json.success) throw new Error(json.error || "Erreur d'import FEC");
  return json.data;
};

/**
 * Récupère les données SIG pour un exercice
 */
const fetchSIG = async (exercice) => {
  const res = await fetch(`${API_BASE}/v1/sig/simple.php?exercice=${exercice}`);
  if (!res.ok) throw new Error(`Erreur SIG ${exercice}`);
  const json = await res.json();
  if (!json.success) throw new Error(json.error || 'Erreur API SIG');
  return json.data;
};

/**
 * Récupère les KPIs financiers pour un exercice
 */
const fetchKPIs = async (exercice) => {
  const res = await fetch(`${API_BASE}/v1/kpis/financial.php?exercice=${exercice}`);
  if (!res.ok) throw new Error(`Erreur KPIs ${exercice}`);
  const json = await res.json();
  if (!json.success) throw new Error(json.error || 'Erreur API KPIs');
  return json.data;
};

/**
 * Récupère les dépenses détaillées pour un exercice
 */
const fetchExpenses = async (exercice) => {
  const res = await fetch(`${API_BASE}/v1/expenses/deep-dive.php?exercice=${exercice}`);
  if (!res.ok) return null; // Non bloquant
  const json = await res.json();
  return json.success ? json.data : null;
};

/**
 * Récupère et fusionne toutes les données d'un exercice
 * Retourne un objet au format attendu par les composants Dashboard/Comparison
 */
export const fetchExerciceData = async (exercice) => {
  const [sig, kpis, expenses] = await Promise.all([
    fetchSIG(exercice),
    fetchKPIs(exercice),
    fetchExpenses(exercice),
  ]);

  const dc = sig.detail_charges || {};
  const bilan = kpis.bilan || {};
  const eq = kpis.equilibre || {};
  const cycles = kpis.cycles || {};
  const solv = kpis.solvabilite || {};
  const prof = kpis.profitabilite || {};
  const seuil = kpis.seuil_rentabilite || {};
  const cafData = kpis.caf || {};

  // Extraction des montants par catégorie depuis expenses API
  const catMap = {};
  if (expenses?.par_categorie) {
    expenses.par_categorie.forEach((c) => { catMap[c.code] = c.montant; });
  }

  const achats = catMap['60'] || dc.achats_matieres || 0;
  const services = (catMap['61'] || 0) + (catMap['62'] || 0) || dc.services_exterieurs || 0;
  const impots = catMap['63'] || dc.impots_taxes || 0;
  const personnelVal = catMap['64'] || dc.charges_personnel || 0;
  const gestion = (catMap['65'] || 0) + (catMap['68'] || dc.dotations_amortissements || 0);
  // Financier = 66 + 627 (frais bancaires reclassés par l'API)
  const financier = catMap['66'] || dc.charges_financieres || 0;

  // Trésorerie nette
  const cashPositive = bilan.tresorerie_active || 0;
  const bankOverdraft = bilan.tresorerie_passive || 0;
  const tn = eq.tresorerie_nette || (cashPositive - bankOverdraft);

  // Total actif
  const fixedAssets = bilan.actif_immobilise || 0;
  const stocks = bilan.stocks || 0;
  const receivables = bilan.creances_clients || 0;
  const otherReceivables = bilan.autres_creances || 0;
  const totalAssets = bilan.total_actif || (fixedAssets + stocks + receivables + otherReceivables + cashPositive);
  const currentAssets = bilan.actif_circulant || (stocks + receivables + otherReceivables + cashPositive);

  const equity = bilan.capitaux_propres || 0;
  const debt = bilan.dettes_financieres || 0;
  const payables = bilan.dettes_fournisseurs || 0;
  const otherPayables = bilan.dettes_fiscales || 0;

  const frng = eq.fonds_roulement || 0;
  const bfr = eq.bfr || 0;

  const ca = sig.ca_net || 0;
  const ebeVal = sig.ebe || 0;
  const rnVal = sig.resultat_net || 0;
  const cafVal = sig.caf || cafData.montant || 0;
  const rexVal = sig.resultat_exploitation || 0;
  const rcaiVal = sig.rcai || 0;

  // Données mensuelles (pas dispo via API — on construit un placeholder vide)
  const monthly = {};
  for (let m = 1; m <= 12; m++) monthly[m] = { revenue: 0, expenses: 0, net: 0 };

  // Ratios
  const marginRate = ca > 0 ? ((ca - achats) / ca) * 100 : 0;
  const breakEvenPoint = seuil.seuil || 0;

  // Construction des détails par catégorie à partir des comptes deep-dive
  const buildDetails = (comptes, filter) =>
    (comptes || [])
      .filter(filter)
      .map((c) => ({ code: c.compte_num, libelle: c.compte_lib, solde: c.montant }))
      .sort((a, b) => Math.abs(b.solde) - Math.abs(a.solde));

  const parCompte = expenses?.par_compte || [];
  const comptesProduits = expenses?.comptes_produits || [];
  const comptesStocks = expenses?.comptes_stocks || [];
  const comptesTresorerie = expenses?.comptes_tresorerie || [];

  return {
    year: exercice,
    revenue: ca,
    ebitda: ebeVal,
    netIncome: rnVal,
    caf: cafVal,
    clientConcentration: 0,
    sig: {
      margeCommerciale: sig.marge_commerciale || 0,
      margeProduction: sig.marge_production || 0,
      productionExercice: sig.production || 0,
      valeurAjoutee: sig.valeur_ajoutee || 0,
      ebe: ebeVal,
      resultatExploitation: rexVal,
      resultatFinancier: sig.resultat_financier || 0,
      resultatCourant: rcaiVal,
      resultatExceptionnel: sig.resultat_exceptionnel || 0,
      is: 0,
      resultatNet: rnVal,
    },
    healthScore: kpis.score_sante || 75,
    fixedAssets,
    stocks,
    receivables,
    payables,
    equity,
    debt,
    bankOverdraft,
    cashPositive,
    totalAssets,
    currentAssets,
    frng,
    bfr,
    tn,
    dso: cycles.dso_clients || 0,
    dpo: cycles.dpo_fournisseurs || 0,
    inventoryTurnover: cycles.jours_stock ? 360 / cycles.jours_stock : 0,
    totalCharges: Math.abs(achats) + Math.abs(services) + Math.abs(impots) + Math.abs(personnelVal) + Math.abs(gestion) + Math.abs(financier),
    breakEvenPoint,
    marginRate,
    ratios: {
      liquidityGeneral: solv.ratio_liquidite || 0,
      liquidityImmediate: solv.ratio_liquidite_immediate || 0,
      debtRatio: solv.ratio_endettement || 0,
      gearing: equity !== 0 ? (debt + bankOverdraft - cashPositive) / equity : 0,
      financialAutonomy: (solv.ratio_autonomie || 0) / 100,
      fixedAssetsWeight: totalAssets > 0 ? fixedAssets / totalAssets : 0,
      interestCoverage: financier > 0 ? ebeVal / financier : 100,
      solvency: solv.ratio_solvabilite || 0,
      roe: prof.roe || 0,
      roa: prof.roi || 0,
      operatingMargin: prof.taux_ebe || 0,
      assetTurnover: totalAssets > 0 ? ca / totalAssets : 0,
      repaymentCapacity: cafVal > 0 ? (debt + bankOverdraft) / cafVal : 0,
      cafOnRevenue: cafData.taux_caf || (ca > 0 ? (cafVal / ca) * 100 : 0),
    },
    monthlyBreakdown: monthly,
    expenseBreakdown: [
      { label: 'Achats', value: Math.abs(achats), color: '#3b82f6' },
      { label: 'Services', value: Math.abs(services), color: '#6366f1' },
      { label: 'Impôts', value: Math.abs(impots), color: '#f59e0b' },
      { label: 'Personnel', value: Math.abs(personnelVal), color: '#ec4899' },
      { label: 'Gestion', value: Math.abs(gestion), color: '#10b981' },
      { label: 'Financier', value: Math.abs(financier), color: '#06b6d4' },
    ],
    topClients: [],
    topSuppliers: [],
    details: {
      revenue: comptesProduits
        .map((c) => ({ code: c.compte_num, libelle: c.compte_lib, solde: c.montant }))
        .sort((a, b) => Math.abs(b.solde) - Math.abs(a.solde)),
      purchases: buildDetails(parCompte, (c) => c.compte_num.startsWith('60')),
      external: buildDetails(parCompte, (c) => c.compte_num.startsWith('61') || c.compte_num.startsWith('62')),
      personnel: buildDetails(parCompte, (c) => c.compte_num.startsWith('64')),
      debt: buildDetails(parCompte, (c) => c.compte_num.startsWith('66') || c.compte_num.startsWith('627')),
      cash: comptesTresorerie
        .map((c) => ({ code: c.compte_num, libelle: c.compte_lib, solde: c.montant }))
        .sort((a, b) => Math.abs(b.solde) - Math.abs(a.solde)),
      assets: buildDetails(parCompte, (c) => c.compte_num.startsWith('2')),
      taxes: buildDetails(parCompte, (c) => c.compte_num.startsWith('63')),
      management: buildDetails(parCompte, (c) => c.compte_num.startsWith('65') || c.compte_num.startsWith('68')),
      stocks: comptesStocks
        .map((c) => ({ code: c.compte_num, libelle: c.compte_lib, solde: c.montant }))
        .sort((a, b) => Math.abs(b.solde) - Math.abs(a.solde)),
      equity: [],
    },
    // Données brutes de l'API pour référence
    _api: { sig, kpis, expenses },
  };
};

/**
 * Charge tous les exercices disponibles
 */
export const fetchAllExercices = async () => {
  const years = await fetchYears();
  const results = {};
  for (const y of years) {
    try {
      results[y] = await fetchExerciceData(y);
    } catch (err) {
      console.warn(`Erreur chargement exercice ${y}:`, err);
    }
  }
  return results;
};
