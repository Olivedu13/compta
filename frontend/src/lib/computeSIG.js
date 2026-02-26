/**
 * computeSIG - Calcule les Soldes Intermédiaires de Gestion
 * à partir des écritures FEC pour une année donnée.
 *
 * Filtre les doublons bancaires (627/661 avec patterns ARRET)
 * Retourne un objet complet : SIG, bilan, ratios, breakdown mensuel, etc.
 */
const computeSIG = (entries, year) => {
  // Filtrage par année + exclusion doublons bancaires
  const filtered = entries
    .filter((e) => e.Year === year)
    .filter((e) => {
      if (
        (e.CompteNum.startsWith('627') || e.CompteNum.startsWith('661')) &&
        /ARRET|RESULTAT ARRET|INTERETS.FRAIS|INT ARRET/i.test(e.EcritureLib)
      ) {
        return false;
      }
      return true;
    });

  // Agrégation des soldes par compte
  const accounts = {};
  filtered.forEach((e) => {
    if (!accounts[e.CompteNum]) {
      accounts[e.CompteNum] = { debit: 0, credit: 0, lib: e.CompteLib };
    }
    accounts[e.CompteNum].debit += e.Debit;
    accounts[e.CompteNum].credit += e.Credit;
  });

  // Variables SIG
  let totalRevenue = 0;
  let salesGoods = 0;       // 707
  let salesOther = 0;       // 70 (hors 707)
  let production = 0;       // 71+72
  let prodStocked = 0;      // (unused alias)
  let prodImmobilisee = 0;  // (unused alias)
  let purchaseGoods = 0;    // 607
  let purchaseDiscount = 0; // 603x7
  let purchasesRM = 0;      // 601+602+603
  let stockVariation = 0;   // 603
  let externalCharges = 0;  // 61+62
  let taxes = 0;            // 63
  let personnel = 0;        // 64
  let subventions = 0;      // 74
  let otherRevenue = 0;     // 75
  let reversals = 0;        // 781
  let amortissements = 0;   // 68
  let financialRevenue = 0; // 76
  let exceptionalRevenue = 0; // 77
  let financialCharges = 0; // 66
  let exceptionalCharges = 0; // 67
  let participation = 0;    // 691+695
  let otherTax = 0;         // 69 (hors 691/695)
  let otherOpCharges = 0;   // 65
  let dotGestion = 0;       // 65+68 combined (pour expBreakdown)

  // Bilan
  let equity = 0;
  let debt = 0;
  let fixedAssets = 0;
  let stocks = 0;
  let receivables = 0;   // 41 (clients)
  let payables = 0;      // 40 (fournisseurs)
  let otherReceivables = 0;
  let otherPayables = 0;
  let cashPositive = 0;
  let bankOverdraft = 0;

  // Expense breakdown categories
  let purchasesTotal = 0;
  let servicesTotal = 0;
  let taxesTotal = 0;
  let personnelTotal = 0;
  let gestionTotal = 0;
  let financierTotal = 0;

  // Details par catégorie
  const details = {
    revenue: [],
    purchases: [],
    external: [],
    personnel: [],
    debt: [],
    cash: [],
    assets: [],
    taxes: [],
    management: [],
    stocks: [],
    equity: [],
  };

  Object.entries(accounts).forEach(([num, acc]) => {
    const cls = num[0];
    const soldeDebiteur = acc.debit - acc.credit;
    const soldeCrediteur = acc.credit - acc.debit;

    if (cls === '7') {
      const produit = acc.credit - acc.debit;
      totalRevenue += produit;

      if (num.startsWith('707')) salesGoods += produit;
      else if (num.startsWith('70')) salesOther += produit;
      else if (num.startsWith('71')) production += produit;
      else if (num.startsWith('72')) prodImmobilisee += produit;
      else if (num.startsWith('74')) subventions += produit;
      else if (num.startsWith('75')) otherRevenue += produit;
      else if (num.startsWith('781')) reversals += produit;
      else if (num.startsWith('76')) financialRevenue += produit;
      else if (num.startsWith('77')) exceptionalRevenue += produit;

      if (acc.credit > 0)
        details.revenue.push({ code: num, libelle: `${acc.lib}`, solde: acc.credit });
      if (acc.debit > 0)
        details.revenue.push({ code: num, libelle: `${acc.lib} (Avoirs)`, solde: -acc.debit });
    } else if (cls === '6') {
      const charge = soldeDebiteur;

      if (num.startsWith('607')) purchaseGoods += charge;
      else if (num.startsWith('603') && num.endsWith('7')) purchaseDiscount += charge;
      else if (num.startsWith('601') || num.startsWith('602') || num.startsWith('603')) {
        purchasesRM += charge;
        if (num.startsWith('603')) stockVariation += charge;
      } else if (num.startsWith('61') || num.startsWith('62')) externalCharges += charge;
      else if (num.startsWith('63')) taxes += charge;
      else if (num.startsWith('64')) personnel += charge;
      else if (num.startsWith('65')) {
        otherOpCharges += charge;
        dotGestion += charge;
      } else if (num.startsWith('68')) {
        amortissements += charge;
        dotGestion += charge;
      } else if (num.startsWith('66')) financialCharges += charge;
      else if (num.startsWith('67')) exceptionalCharges += charge;
      else if (num.startsWith('69')) {
        if (num.startsWith('691') || num.startsWith('695')) participation += charge;
        else otherTax += charge;
      }

      // Expense breakdown categories
      if (num.startsWith('60')) {
        purchasesTotal += charge;
        details.purchases.push({ code: num, libelle: acc.lib, solde: charge });
      } else if (num.startsWith('61') || num.startsWith('62')) {
        servicesTotal += charge;
        details.external.push({ code: num, libelle: acc.lib, solde: charge });
      } else if (num.startsWith('63')) {
        taxesTotal += charge;
        details.taxes.push({ code: num, libelle: acc.lib, solde: charge });
      } else if (num.startsWith('64')) {
        personnelTotal += charge;
        details.personnel.push({ code: num, libelle: acc.lib, solde: charge });
      } else if (num.startsWith('65') || num.startsWith('68')) {
        details.management.push({ code: num, libelle: acc.lib, solde: charge });
      } else if (num.startsWith('66')) {
        financierTotal += charge;
        details.debt.push({ code: num, libelle: acc.lib, solde: charge });
      }
    } else if (cls === '1') {
      if (num.startsWith('10') || num.startsWith('11') || num.startsWith('12')) {
        equity += soldeCrediteur;
        details.equity.push({ code: num, libelle: acc.lib, solde: soldeCrediteur });
      } else if (num.startsWith('16')) {
        debt += soldeCrediteur;
      }
    } else if (cls === '2') {
      fixedAssets += soldeDebiteur;
      details.assets.push({ code: num, libelle: acc.lib, solde: soldeDebiteur });
    } else if (cls === '3') {
      stocks += soldeDebiteur;
      details.stocks.push({ code: num, libelle: acc.lib, solde: soldeDebiteur });
    } else if (cls === '4') {
      if (num.startsWith('41')) receivables += soldeDebiteur;
      else if (num.startsWith('40')) payables += soldeCrediteur;
      else if (soldeDebiteur > 0) otherReceivables += soldeDebiteur;
      else otherPayables += Math.abs(soldeDebiteur);
    } else if (cls === '5') {
      if (soldeDebiteur > 0) cashPositive += soldeDebiteur;
      else bankOverdraft += Math.abs(soldeDebiteur);
      details.cash.push({ code: num, libelle: acc.lib, solde: soldeDebiteur });
    }
  });

  // SIG Cascade
  const ca = totalRevenue;
  const margeCommerciale = salesGoods - (purchaseGoods + purchaseDiscount);
  const productionExercice = salesOther + production + prodImmobilisee;
  const consommation = purchasesTotal;
  const valeurAjoutee = margeCommerciale + productionExercice - (purchasesRM + externalCharges);
  const ebe = valeurAjoutee + subventions - (taxes + personnel);
  const resultatExploitation = ebe + otherRevenue + reversals - (otherOpCharges + amortissements);
  const resultatCourant = resultatExploitation + financialRevenue - financialCharges;
  const resultatNet = resultatCourant + (exceptionalRevenue - exceptionalCharges) - (otherTax + participation);
  const caf = resultatNet + amortissements - reversals;

  // Ratios
  const totalChargesFixes = servicesTotal + taxesTotal + personnelTotal + gestionTotal + financierTotal;
  const marginRate = ca > 0 ? (ca - purchasesTotal) / ca : 0;
  const breakEvenPoint = marginRate > 0 ? totalChargesFixes / marginRate : 0;

  // Monthly breakdown
  const monthly = {};
  for (let m = 1; m <= 12; m++) monthly[m] = { revenue: 0, expenses: 0, net: 0 };

  filtered.forEach((e) => {
    if (e.CompteNum[0] === '7') monthly[e.Month].revenue += e.Credit - e.Debit;
    if (e.CompteNum[0] === '6') monthly[e.Month].expenses += e.Debit - e.Credit;
  });

  for (let m = 1; m <= 12; m++) monthly[m].net = monthly[m].revenue - monthly[m].expenses;

  const totalAssets = fixedAssets + stocks + receivables + otherReceivables + cashPositive;
  const currentAssets = stocks + receivables + otherReceivables + cashPositive;
  const frng = equity + debt - fixedAssets;
  const bfr = stocks + receivables + otherReceivables - (payables + otherPayables);
  const tn = frng - bfr;

  return {
    year,
    revenue: ca,
    ebitda: ebe,
    netIncome: resultatNet,
    caf,
    clientConcentration: 0,
    sig: {
      margeCommerciale,
      productionExercice,
      valeurAjoutee,
      ebe,
      resultatExploitation,
      resultatCourant,
      resultatExceptionnel: exceptionalRevenue - exceptionalCharges,
      is: participation,
      resultatNet,
    },
    healthScore: 85,
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
    dso: ca > 0 ? (receivables / (ca * 1.2)) * 360 : 0,
    dpo: purchasesTotal > 0 ? (payables / (purchasesTotal * 1.2)) * 360 : 0,
    inventoryTurnover: stocks > 0 ? purchaseGoods / stocks : 0,
    breakEvenPoint,
    marginRate: marginRate * 100,
    ratios: {
      liquidityGeneral: payables + otherPayables > 0 ? currentAssets / (payables + otherPayables) : 0,
      liquidityImmediate: payables + otherPayables > 0 ? cashPositive / (payables + otherPayables) : 0,
      debtRatio: equity !== 0 ? (debt + bankOverdraft) / equity : 0,
      gearing: equity !== 0 ? (debt + bankOverdraft - cashPositive) / equity : 0,
      financialAutonomy: totalAssets > 0 ? equity / totalAssets : 0,
      fixedAssetsWeight: totalAssets > 0 ? fixedAssets / totalAssets : 0,
      interestCoverage: financialCharges > 0 ? ebe / financialCharges : 100,
      solvency: debt + payables + otherPayables + bankOverdraft > 0 ? equity / (debt + payables + otherPayables + bankOverdraft) : 0,
      roe: equity !== 0 ? (resultatNet / equity) * 100 : 0,
      roa: totalAssets > 0 ? (resultatNet / totalAssets) * 100 : 0,
      operatingMargin: ca !== 0 ? (ebe / ca) * 100 : 0,
      assetTurnover: totalAssets > 0 ? ca / totalAssets : 0,
      repaymentCapacity: caf > 0 ? (debt + bankOverdraft) / caf : 0,
      cafOnRevenue: ca > 0 ? (caf / ca) * 100 : 0,
    },
    monthlyBreakdown: monthly,
    expenseBreakdown: [
      { label: 'Achats', value: Math.abs(purchasesTotal), color: '#3b82f6' },
      { label: 'Services', value: Math.abs(servicesTotal), color: '#6366f1' },
      { label: 'Impôts', value: Math.abs(taxesTotal), color: '#f59e0b' },
      { label: 'Personnel', value: Math.abs(personnelTotal), color: '#ec4899' },
      { label: 'Gestion', value: Math.abs(gestionTotal), color: '#10b981' },
      { label: 'Financier', value: Math.abs(financierTotal), color: '#06b6d4' },
    ],
    topClients: [],
    topSuppliers: [],
    details,
  };
};

export default computeSIG;
