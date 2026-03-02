import React, { useMemo } from 'react';

/**
 * ExecSummaryDashboard — Synthèse visuelle ultra-simplifiée pour le dirigeant
 * Jauges, feux tricolores, barres de progression — zéro jargon comptable
 */

// ─── Jauge circulaire SVG ───
const GaugeCircle = ({ value, max = 100, label, sublabel, size = 120, color }) => {
  const pct = Math.min(Math.max(value / max, 0), 1);
  const r = (size - 16) / 2;
  const circ = 2 * Math.PI * r;
  const offset = circ * (1 - pct);
  const gradientColor = color || (pct >= 0.7 ? '#22c55e' : pct >= 0.4 ? '#f59e0b' : '#ef4444');

  return (
    <div className="flex flex-col items-center">
      <svg width={size} height={size} className="transform -rotate-90">
        <circle cx={size / 2} cy={size / 2} r={r} fill="none" stroke="#f1f5f9" strokeWidth="10" />
        <circle
          cx={size / 2} cy={size / 2} r={r} fill="none"
          stroke={gradientColor} strokeWidth="10" strokeLinecap="round"
          strokeDasharray={circ} strokeDashoffset={offset}
          className="transition-all duration-1000 ease-out"
        />
      </svg>
      <div className="absolute flex flex-col items-center justify-center" style={{ width: size, height: size }}>
        <span className="text-2xl font-black text-slate-900">{Math.round(value)}</span>
        <span className="text-[10px] font-bold text-slate-400 uppercase">{sublabel || `/ ${max}`}</span>
      </div>
      <p className="mt-2 text-xs font-bold text-slate-600 text-center">{label}</p>
    </div>
  );
};

// ─── Feu tricolore ───
const TrafficLight = ({ status, label, detail, subItems }) => {
  const colors = {
    green: { bg: 'bg-emerald-500', ring: 'ring-emerald-200', icon: 'fa-check', text: 'text-emerald-700', label: 'Bon', detailBg: 'bg-emerald-50', border: 'border-emerald-100' },
    yellow: { bg: 'bg-amber-400', ring: 'ring-amber-200', icon: 'fa-minus', text: 'text-amber-700', label: 'Vigilance', detailBg: 'bg-amber-50', border: 'border-amber-100' },
    red: { bg: 'bg-red-500', ring: 'ring-red-200', icon: 'fa-xmark', text: 'text-red-700', label: 'Alerte', detailBg: 'bg-red-50', border: 'border-red-100' },
  };
  const c = colors[status] || colors.yellow;

  return (
    <div className={`rounded-xl overflow-hidden border ${c.border}`}>
      <div className={`flex items-center gap-3 p-3 ${c.detailBg}`}>
        <div className={`w-9 h-9 rounded-full ${c.bg} ring-4 ${c.ring} flex items-center justify-center flex-shrink-0`}>
          <i className={`fa-solid ${c.icon} text-white text-xs`}></i>
        </div>
        <div className="min-w-0 flex-1">
          <div className="flex items-center gap-2">
            <span className="text-sm font-bold text-slate-800">{label}</span>
            <span className={`text-[10px] font-bold uppercase ${c.text}`}>{c.label}</span>
          </div>
          <p className="text-xs text-slate-600 font-medium">{detail}</p>
        </div>
      </div>
      {subItems && subItems.length > 0 && (
        <div className="px-4 py-2 bg-white/80 space-y-1">
          {subItems.map((si, idx) => (
            <div key={idx} className="flex items-start gap-2 text-[11px]">
              <span className={`mt-0.5 flex-shrink-0 ${si.status === 'red' ? 'text-red-400' : si.status === 'green' ? 'text-emerald-400' : 'text-amber-400'}`}>
                <i className={`fa-solid ${si.status === 'red' ? 'fa-circle-xmark' : si.status === 'green' ? 'fa-circle-check' : 'fa-circle-minus'} text-[9px]`}></i>
              </span>
              <span className="text-slate-600">{si.text}</span>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

// ─── Barre horizontale ───
const HBar = ({ label, value, max, suffix = '€', color = '#3b82f6' }) => {
  const pct = max > 0 ? Math.min((Math.abs(value) / max) * 100, 100) : 0;
  const fmt = (v) => {
    const abs = Math.abs(v);
    if (abs >= 1000000) return (v / 1000000).toFixed(1) + 'M';
    if (abs >= 1000) return (v / 1000).toFixed(0) + 'k';
    return v.toFixed(0);
  };

  return (
    <div className="mb-3">
      <div className="flex justify-between items-baseline mb-1">
        <span className="text-xs font-semibold text-slate-600">{label}</span>
        <span className="text-xs font-bold text-slate-900">{fmt(value)}{suffix}</span>
      </div>
      <div className="h-2.5 bg-slate-100 rounded-full overflow-hidden">
        <div
          className="h-full rounded-full transition-all duration-1000 ease-out"
          style={{ width: `${pct}%`, backgroundColor: color }}
        />
      </div>
    </div>
  );
};

// ─── KPI Card mini ───
const KpiMini = ({ icon, label, value, evolution, good }) => (
  <div className="bg-white rounded-xl border border-slate-100 p-4 text-center shadow-sm hover:shadow-md transition-shadow">
    <div className={`w-10 h-10 mx-auto rounded-lg flex items-center justify-center mb-2 ${good ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'}`}>
      <i className={`fa-solid ${icon}`}></i>
    </div>
    <p className="text-lg font-black text-slate-900">{value}</p>
    <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{label}</p>
    {evolution && (
      <p className={`text-[10px] font-bold mt-1 ${evolution > 0 ? 'text-emerald-600' : 'text-red-500'}`}>
        {evolution > 0 ? '▲' : '▼'} {Math.abs(evolution).toFixed(1)}% vs N-1
      </p>
    )}
  </div>
);

// ─── Composant principal ───
const ExecSummaryDashboard = ({ data, previousData }) => {
  const analysis = useMemo(() => {
    if (!data) return null;

    const ca = data.revenue || 0;
    const ebe = data.ebitda || 0;
    const rn = data.netIncome || 0;
    const caf = data.caf || 0;
    const tn = data.tn || 0;
    const bfr = data.bfr || 0;
    const frng = data.frng || 0;
    const dso = data.dso || 0;
    const dpo = data.dpo || 0;
    const equity = data.equity || 0;
    const debt = data.debt || 0;
    const bankOverdraft = data.bankOverdraft || 0;
    const totalAssets = data.totalAssets || 1;
    const healthScore = data.healthScore || 50;
    const breakEven = data.breakEvenPoint || 0;
    const ratios = data.ratios || {};
    const expenses = data.expenseBreakdown || [];

    // Marges
    const margeNette = ca > 0 ? (rn / ca) * 100 : 0;
    const margeEBE = ca > 0 ? (ebe / ca) * 100 : 0;
    const cafSurCA = ca > 0 ? (caf / ca) * 100 : 0;

    // Évolutions
    const evolCA = previousData?.revenue ? ((ca - previousData.revenue) / Math.abs(previousData.revenue)) * 100 : null;
    const evolRN = previousData?.netIncome ? ((rn - previousData.netIncome) / Math.abs(previousData.netIncome)) * 100 : null;
    const evolEBE = previousData?.ebitda ? ((ebe - previousData.ebitda) / Math.abs(previousData.ebitda)) * 100 : null;

    // Verdict global
    let verdict, verdictColor, verdictIcon, verdictDesc;
    if (healthScore >= 70 && margeNette > 3 && tn > 0) {
      verdict = 'Entreprise saine';
      verdictColor = 'from-emerald-500 to-emerald-600';
      verdictIcon = 'fa-shield-check';
      verdictDesc = 'Les fondamentaux financiers sont solides.';
    } else if (healthScore >= 50 && margeNette > 1) {
      verdict = 'À surveiller';
      verdictColor = 'from-amber-400 to-amber-500';
      verdictIcon = 'fa-eye';
      verdictDesc = 'Quelques indicateurs méritent votre attention.';
    } else if (healthScore >= 30) {
      verdict = 'Situation tendue';
      verdictColor = 'from-orange-500 to-red-500';
      verdictIcon = 'fa-triangle-exclamation';
      verdictDesc = 'Plusieurs signaux d\'alerte sont présents.';
    } else {
      verdict = 'Situation critique';
      verdictColor = 'from-red-600 to-red-700';
      verdictIcon = 'fa-circle-exclamation';
      verdictDesc = 'Action immédiate recommandée.';
    }

    // Feux tricolores — enrichis avec sous-détails
    const fmtK = (v) => {
      const abs = Math.abs(v);
      if (abs >= 1000000) return (v / 1000000).toFixed(1) + 'M€';
      if (abs >= 1000) return (v / 1000).toFixed(0) + 'k€';
      return v.toFixed(0) + '€';
    };

    const fraisBancaires = (data.details?.debt || [])
      .filter(d => d.code?.startsWith('627'))
      .reduce((s, d) => s + Math.abs(d.solde), 0);
    const pctFraisBancaires = ca > 0 ? (fraisBancaires / ca) * 100 : 0;

    const chargesPersonnel = expenses.find(e => e.label === 'Personnel')?.value || 0;
    const pctPersonnel = ca > 0 ? (chargesPersonnel / ca) * 100 : 0;

    // Séparation dirigeant vs salariés
    const remDirigeant = data.remDirigeant || 0;
    const masseSalariale = data.masseSalariale || 0;
    const pctMasseSalariale = ca > 0 ? (masseSalariale / ca) * 100 : 0;
    const pctRemDirigeant = ca > 0 ? (remDirigeant / ca) * 100 : 0;
    const remDirigeantMensuelle = remDirigeant / 12;

    const chargesFinancieres = expenses.find(e => e.label === 'Financier')?.value || 0;
    const pctFinancier = ca > 0 ? (chargesFinancieres / ca) * 100 : 0;

    const achatsVal = expenses.find(e => e.label === 'Achats')?.value || 0;
    const pctAchats = ca > 0 ? (achatsVal / ca) * 100 : 0;

    const totalCharges = expenses.reduce((s, e) => s + e.value, 0);
    const pctCharges = ca > 0 ? (totalCharges / ca) * 100 : 0;

    const feux = [
      {
        label: 'Rentabilité',
        status: margeNette > 5 ? 'green' : margeNette > 2 ? 'yellow' : 'red',
        detail: `Marge nette ${margeNette.toFixed(2)}% — Résultat ${fmtK(rn)} sur ${fmtK(ca)} de CA`,
        subItems: [
          { text: `Marge EBE : ${margeEBE.toFixed(2)}% (${margeEBE > 8 ? 'saine' : margeEBE > 4 ? 'fragile' : 'insuffisante'}) — EBE = ${fmtK(ebe)}`, status: margeEBE > 8 ? 'green' : margeEBE > 4 ? 'yellow' : 'red' },
          { text: `VA / CA : ${ca > 0 ? ((data.sig?.valeurAjoutee || 0) / ca * 100).toFixed(1) : 0}% — Richesse créée par l'entreprise`, status: ca > 0 && (data.sig?.valeurAjoutee || 0) / ca > 0.3 ? 'green' : 'yellow' },
          { text: `ROE : ${(ratios.roe || 0).toFixed(1)}% — Rendement pour l'actionnaire`, status: (ratios.roe || 0) > 10 ? 'green' : (ratios.roe || 0) > 3 ? 'yellow' : 'red' },
          { text: `Charges totales = ${pctCharges.toFixed(1)}% du CA (${fmtK(totalCharges)})`, status: pctCharges < 95 ? 'green' : pctCharges < 99 ? 'yellow' : 'red' },
          ...(evolRN ? [{ text: `Évolution résultat N/N-1 : ${evolRN > 0 ? '+' : ''}${evolRN.toFixed(1)}%`, status: evolRN > 0 ? 'green' : evolRN > -10 ? 'yellow' : 'red' }] : []),
        ],
      },
      {
        label: 'Trésorerie',
        status: tn > 0 && tn > bfr * 0.1 ? 'green' : tn > 0 ? 'yellow' : 'red',
        detail: `TN ${fmtK(tn)} — ${tn > 0 ? 'Cash disponible' : 'Découvert, tension de trésorerie'}`,
        subItems: [
          { text: `Trésorerie active : ${fmtK(data.cashPositive || 0)} en banque`, status: (data.cashPositive || 0) > 0 ? 'green' : 'red' },
          { text: `Découvert bancaire : ${fmtK(bankOverdraft)}${bankOverdraft > 0 ? ' — utilisation du découvert' : ' — pas de découvert'}`, status: bankOverdraft === 0 ? 'green' : bankOverdraft < 10000 ? 'yellow' : 'red' },
          { text: `Frais bancaires : ${fmtK(fraisBancaires)} (${pctFraisBancaires.toFixed(2)}% du CA)${pctFraisBancaires > 3 ? ' ⚠ ANORMALEMENT ÉLEVÉ' : ''}`, status: pctFraisBancaires < 1 ? 'green' : pctFraisBancaires < 3 ? 'yellow' : 'red' },
          { text: `CAF : ${fmtK(caf)} (${cafSurCA.toFixed(1)}% du CA) — Capacité de remboursement`, status: cafSurCA > 5 ? 'green' : cafSurCA > 2 ? 'yellow' : 'red' },
        ],
      },
      {
        label: 'Autonomie financière',
        status: (ratios.financialAutonomy || 0) > 0.3 ? 'green' : (ratios.financialAutonomy || 0) > 0.15 ? 'yellow' : 'red',
        detail: `${((ratios.financialAutonomy || 0) * 100).toFixed(1)}% de fonds propres — ${(ratios.financialAutonomy || 0) > 0.3 ? 'Indépendance solide' : (ratios.financialAutonomy || 0) > 0.15 ? 'Dépendance modérée' : 'Trop dépendant des tiers'}`,
        subItems: [
          { text: `Capitaux propres : ${fmtK(equity)} / Total actif : ${fmtK(totalAssets)}`, status: equity > 0 ? 'green' : 'red' },
          { text: `Endettement net : ${fmtK(debt + bankOverdraft - (data.cashPositive || 0))} — Gearing : ${(ratios.gearing || 0).toFixed(2)}x`, status: (ratios.gearing || 0) < 1 ? 'green' : (ratios.gearing || 0) < 2 ? 'yellow' : 'red' },
          { text: `Solvabilité : ${(ratios.solvency || 0).toFixed(2)}x ${(ratios.solvency || 0) >= 1.5 ? '— solide' : (ratios.solvency || 0) >= 1 ? '— juste' : '— risque'}`, status: (ratios.solvency || 0) >= 1.5 ? 'green' : (ratios.solvency || 0) >= 1 ? 'yellow' : 'red' },
          { text: `Capacité remboursement : ${(ratios.repaymentCapacity || 0).toFixed(1)} ans`, status: (ratios.repaymentCapacity || 0) < 3 ? 'green' : (ratios.repaymentCapacity || 0) < 7 ? 'yellow' : 'red' },
        ],
      },
      {
        label: 'Cycle clients / fournisseurs',
        status: dso < 45 && dpo > 20 ? 'green' : dso < 90 ? 'yellow' : 'red',
        detail: `Clients payent en ${Math.round(dso)}j, vous payez en ${Math.round(dpo)}j — Écart : ${Math.round(dso - dpo)}j`,
        subItems: [
          { text: `DSO clients : ${Math.round(dso)} jours ${dso < 30 ? '— excellent' : dso < 45 ? '— correct' : dso < 90 ? '— à raccourcir' : '— critique, relances urgentes'}`, status: dso < 45 ? 'green' : dso < 90 ? 'yellow' : 'red' },
          { text: `DPO fournisseurs : ${Math.round(dpo)} jours ${dpo > 45 ? '— bon levier' : dpo > 30 ? '— standard' : '— vous payez trop vite'}`, status: dpo > 45 ? 'green' : dpo > 30 ? 'yellow' : 'red' },
          { text: `Décalage DSO-DPO : ${Math.round(dso - dpo)}j — ${dso > dpo ? 'Vous financez vos clients' : 'Vos fournisseurs vous financent'}`, status: dso <= dpo ? 'green' : dso - dpo < 30 ? 'yellow' : 'red' },
          { text: `Créances clients : ${fmtK(data.receivables || 0)} / Dettes fournisseurs : ${fmtK(data.payables || 0)}`, status: 'yellow' },
        ],
      },
      {
        label: 'Seuil de rentabilité',
        status: ca > breakEven * 1.1 ? 'green' : ca > breakEven ? 'yellow' : 'red',
        detail: ca >= breakEven
          ? `Atteint — Marge de sécurité ${((ca / breakEven - 1) * 100).toFixed(1)}% (${fmtK(ca - breakEven)} au-dessus)`
          : `NON ATTEINT — Déficit de ${fmtK(breakEven - ca)} (${((1 - ca / breakEven) * 100).toFixed(1)}% en dessous)`,
        subItems: [
          { text: `Point mort : ${fmtK(breakEven)} — CA minimum pour couvrir les charges`, status: ca > breakEven ? 'green' : 'red' },
          { text: `CA réalisé : ${fmtK(ca)} — ${ca > breakEven ? 'Excédent' : 'Déficit'} : ${fmtK(Math.abs(ca - breakEven))}`, status: ca > breakEven ? 'green' : 'red' },
          { text: `Taux de marge variable : ${marginRate.toFixed(1)}% — ${marginRate > 40 ? 'forte' : marginRate > 25 ? 'moyenne' : 'faible'} capacité d'absorption`, status: marginRate > 40 ? 'green' : marginRate > 25 ? 'yellow' : 'red' },
        ],
      },
      {
        label: 'Structure des charges',
        status: pctMasseSalariale < 30 && pctAchats < 55 && pctFinancier < 2 ? 'green' : pctMasseSalariale < 40 && pctAchats < 65 ? 'yellow' : 'red',
        detail: `Achats ${pctAchats.toFixed(1)}% + Salariés ${pctMasseSalariale.toFixed(1)}% + Dirigeant ${pctRemDirigeant.toFixed(1)}% + Financier ${pctFinancier.toFixed(1)}% du CA`,
        subItems: [
          { text: `Achats/MP : ${fmtK(achatsVal)} (${pctAchats.toFixed(1)}% CA) ${pctAchats > 60 ? '— poids élevé, négociez' : ''}`, status: pctAchats < 50 ? 'green' : pctAchats < 65 ? 'yellow' : 'red' },
          { text: `Masse salariale (10 salariés) : ${fmtK(masseSalariale)} (${pctMasseSalariale.toFixed(1)}% CA)`, status: pctMasseSalariale < 30 ? 'green' : pctMasseSalariale < 40 ? 'yellow' : 'red' },
          { text: `Rém. dirigeant TNS (Poquet T) : ${fmtK(remDirigeant)} (${fmtK(remDirigeantMensuelle)}/mois) — prélèvement gérant, non-salarié`, status: pctRemDirigeant < 5 ? 'green' : pctRemDirigeant < 8 ? 'yellow' : 'red' },
          { text: `Charges financières : ${fmtK(chargesFinancieres)} (${pctFinancier.toFixed(1)}% CA) ${pctFinancier > 3 ? '— COÛT EXCESSIF' : ''}`, status: pctFinancier < 1.5 ? 'green' : pctFinancier < 3 ? 'yellow' : 'red' },
          { text: `Couverture intérêts EBE/CF : ${(ratios.interestCoverage || 0).toFixed(1)}x ${(ratios.interestCoverage || 0) > 3 ? '— confortable' : '— sous pression'}`, status: (ratios.interestCoverage || 0) > 3 ? 'green' : (ratios.interestCoverage || 0) > 1.5 ? 'yellow' : 'red' },
        ],
      },
    ];

    // ─── Détection anomalies comptables ───
    const anomalies = [];
    const monthlyArr = Object.entries(data.monthlyBreakdown || {}).map(([m, d]) => ({ mois: parseInt(m), ...d }));

    // 1. Mois sans CA
    const moisSansCA = monthlyArr.filter(m => m.revenue === 0 && m.expenses > 0);
    if (moisSansCA.length > 0) {
      const noms = ['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Août','Sep','Oct','Nov','Déc'];
      anomalies.push({
        severity: 'critical',
        icon: 'fa-calendar-xmark',
        title: `${moisSansCA.length} mois sans chiffre d'affaires`,
        detail: `${moisSansCA.map(m => noms[m.mois - 1]).join(', ')} — charges supportées sans recettes`,
        hint: 'Vérifier : fermeture, erreur de saisie, ou facturation non comptabilisée',
      });
    }

    // 2. Mois déficitaires
    const moisDeficitaires = monthlyArr.filter(m => m.net < 0 && m.revenue > 0);
    if (moisDeficitaires.length >= 3) {
      const noms = ['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Août','Sep','Oct','Nov','Déc'];
      const totalPerte = moisDeficitaires.reduce((s, m) => s + m.net, 0);
      anomalies.push({
        severity: 'warning',
        icon: 'fa-chart-line-down',
        title: `${moisDeficitaires.length} mois déficitaires`,
        detail: `${moisDeficitaires.map(m => noms[m.mois - 1]).join(', ')} — Perte cumulée : ${fmtK(totalPerte)}`,
        hint: 'Analyser la saisonnalité et les charges fixes incompressibles',
      });
    }

    // 3. FRNG négatif
    if (frng < 0) {
      anomalies.push({
        severity: 'critical',
        icon: 'fa-scale-unbalanced',
        title: 'Fonds de roulement négatif',
        detail: `FRNG = ${fmtK(frng)} — Les immobilisations ne sont pas financées par des ressources stables`,
        hint: 'Risque structurel : renforcer les capitaux propres ou restructurer la dette',
      });
    }

    // 4. BFR > FRNG (déséquilibre structurel)
    if (bfr > 0 && frng > 0 && bfr > frng) {
      anomalies.push({
        severity: 'warning',
        icon: 'fa-arrows-rotate',
        title: 'BFR supérieur au fonds de roulement',
        detail: `BFR ${fmtK(bfr)} > FRNG ${fmtK(frng)} — Gap de ${fmtK(bfr - frng)} financé par du découvert`,
        hint: 'Réduire le BFR (stocks, relances clients) ou augmenter les fonds propres',
      });
    }

    // 5. Capitaux propres négatifs
    if (equity < 0) {
      anomalies.push({
        severity: 'critical',
        icon: 'fa-skull-crossbones',
        title: 'Capitaux propres négatifs',
        detail: `CP = ${fmtK(equity)} — Situation de faillite comptable (article L.225-248 C.com)`,
        hint: 'Obligation légale : régulariser dans les 2 ans ou risque de dissolution',
      });
    }

    // 6. Frais bancaires > 3% du CA
    if (pctFraisBancaires > 3) {
      anomalies.push({
        severity: 'critical',
        icon: 'fa-building-columns',
        title: `Frais bancaires excessifs : ${pctFraisBancaires.toFixed(2)}% du CA`,
        detail: `${fmtK(fraisBancaires)} de frais bancaires — norme secteur < 1%, vous êtes ${(pctFraisBancaires / 1).toFixed(0)}x au-dessus`,
        hint: 'Renégocier immédiatement : commissions TPE, tenue de compte, frais de mouvement',
      });
    } else if (pctFraisBancaires > 1.5) {
      anomalies.push({
        severity: 'warning',
        icon: 'fa-building-columns',
        title: `Frais bancaires élevés : ${pctFraisBancaires.toFixed(2)}% du CA`,
        detail: `${fmtK(fraisBancaires)} — au-dessus de la norme (< 1%)`,
        hint: 'Analyser les commissions et comparer avec d\'autres banques',
      });
    }

    // 7. Résultat net < 1% du CA
    if (ca > 0 && margeNette < 1 && margeNette >= 0) {
      anomalies.push({
        severity: 'warning',
        icon: 'fa-droplet',
        title: `Marge nette quasi nulle : ${margeNette.toFixed(2)}%`,
        detail: `Résultat de ${fmtK(rn)} pour ${fmtK(ca)} de CA — aucune marge de manœuvre`,
        hint: 'Le moindre imprévu peut générer une perte. Travailler sur le mix produits / charges',
      });
    }

    // 8. Résultat net négatif
    if (rn < 0) {
      anomalies.push({
        severity: 'critical',
        icon: 'fa-arrow-trend-down',
        title: `Exercice déficitaire : ${fmtK(rn)}`,
        detail: `Perte de ${fmtK(Math.abs(rn))} — ${ca > 0 ? `soit ${Math.abs(margeNette).toFixed(1)}% du CA` : 'aucun CA'}`,
        hint: 'Identifier les postes de charge à réduire en priorité',
      });
    }

    // 9. Variations atypiques depuis l'API
    const variationsAtypiques = data.variationsAtypiques || [];
    if (variationsAtypiques.length > 0) {
      const critiques = variationsAtypiques.filter(v => v.severity === 'critical');
      const warnings = variationsAtypiques.filter(v => v.severity === 'warning');
      if (critiques.length > 0) {
        anomalies.push({
          severity: 'critical',
          icon: 'fa-bolt-lightning',
          title: `${critiques.length} variation(s) critique(s) entre mois`,
          detail: critiques.slice(0, 3).map(v => `${v.mois_precedent}→${v.mois} : ${v.type} ${v.variation_pct > 0 ? '+' : ''}${v.variation_pct.toFixed(0)}% (${fmtK(v.variation_euros)})`).join(' | '),
          hint: 'Vérifier : charge exceptionnelle, erreur d\'imputation, ou événement ponctuel',
        });
      }
      if (warnings.length > 0) {
        anomalies.push({
          severity: 'warning',
          icon: 'fa-chart-bar',
          title: `${warnings.length} variation(s) atypique(s) entre mois`,
          detail: warnings.slice(0, 3).map(v => `${v.mois_precedent}→${v.mois} : ${v.type} ${v.variation_pct > 0 ? '+' : ''}${v.variation_pct.toFixed(0)}%`).join(' | '),
          hint: 'Analyser les écarts d\'un mois sur l\'autre pour valider la cohérence',
        });
      }
    }

    // 10. Doublons factures
    const doublons = data.doublonsFactures || [];
    if (doublons.length > 0) {
      const totalDoublon = doublons.reduce((s, d) => s + Math.abs(d.montant || 0), 0);
      anomalies.push({
        severity: 'warning',
        icon: 'fa-clone',
        title: `${doublons.length} doublon(s) de factures détecté(s)`,
        detail: `Montant concerné : ${fmtK(totalDoublon)} — ${doublons.slice(0, 3).map(d => `${d.fournisseur || d.piece_ref || '?'}`).join(', ')}${doublons.length > 3 ? '...' : ''}`,
        hint: 'Vérifier les écritures pour éviter le double paiement',
      });
    }

    // 11. Liquidité insuffisante
    if ((ratios.liquidityGeneral || 0) < 1) {
      anomalies.push({
        severity: 'critical',
        icon: 'fa-water',
        title: `Ratio de liquidité < 1 : ${(ratios.liquidityGeneral || 0).toFixed(2)}x`,
        detail: `L'actif circulant ne couvre pas les dettes à court terme`,
        hint: 'Risque de cessation de paiements si aucune ligne de crédit disponible',
      });
    }

    // 12. EBE négatif
    if (ebe < 0) {
      anomalies.push({
        severity: 'critical',
        icon: 'fa-fire',
        title: `EBE négatif : ${fmtK(ebe)}`,
        detail: `L'exploitation ne dégage pas assez pour couvrir les charges courantes`,
        hint: 'Problème fondamental de modèle économique — actions urgentes requises',
      });
    }

    // 13. Ecart DSO/DPO très défavorable
    if (dso > 0 && dpo > 0 && (dso - dpo) > 60) {
      anomalies.push({
        severity: 'warning',
        icon: 'fa-hourglass-half',
        title: `Décalage DSO-DPO critique : ${Math.round(dso - dpo)} jours`,
        detail: `Vous payez en ${Math.round(dpo)}j mais encaissez en ${Math.round(dso)}j — besoin de trésorerie ${fmtK((dso - dpo) / 360 * ca)}`,
        hint: 'Négocier des délais fournisseurs plus longs et accélérer les encaissements',
      });
    }

    return {
      ca, ebe, rn, caf, tn, bfr, frng, dso, dpo, equity, debt, bankOverdraft,
      totalAssets, healthScore, breakEven, margeNette, margeEBE, cafSurCA,
      evolCA, evolRN, evolEBE, verdict, verdictColor, verdictIcon, verdictDesc,
      feux, expenses, ratios, anomalies, marginRate: data.marginRate || 0,
    };
  }, [data, previousData]);

  if (!analysis) return null;

  const fmt = (v) => {
    const abs = Math.abs(v);
    if (abs >= 1000000) return (v / 1000000).toFixed(1) + 'M€';
    if (abs >= 1000) return (v / 1000).toFixed(0) + 'k€';
    return v.toFixed(0) + '€';
  };

  return (
    <div className="space-y-6 mb-8">
      {/* ─── VERDICT GLOBAL ─── */}
      <div className={`bg-gradient-to-r ${analysis.verdictColor} rounded-2xl p-6 text-white shadow-lg`}>
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            <div className="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
              <i className={`fa-solid ${analysis.verdictIcon} text-2xl`}></i>
            </div>
            <div>
              <h2 className="text-2xl font-black">{analysis.verdict}</h2>
              <p className="text-white/80 text-sm">{analysis.verdictDesc}</p>
            </div>
          </div>
          <div className="text-right">
            <div className="text-4xl font-black">{Math.round(analysis.healthScore)}</div>
            <div className="text-xs font-bold text-white/60 uppercase">Score / 100</div>
          </div>
        </div>
      </div>

      {/* ─── CHIFFRES CLÉS ─── */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <KpiMini
          icon="fa-coins"
          label="Chiffre d'affaires"
          value={fmt(analysis.ca)}
          evolution={analysis.evolCA}
          good={analysis.ca > 0}
        />
        <KpiMini
          icon="fa-chart-line"
          label="Résultat net"
          value={fmt(analysis.rn)}
          evolution={analysis.evolRN}
          good={analysis.rn > 0}
        />
        <KpiMini
          icon="fa-piggy-bank"
          label="Trésorerie"
          value={fmt(analysis.tn)}
          good={analysis.tn > 0}
        />
        <KpiMini
          icon="fa-seedling"
          label="Capacité d'autofi."
          value={fmt(analysis.caf)}
          good={analysis.caf > 0}
        />
      </div>

      {/* ─── JAUGES + FEUX ─── */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {/* Jauges circulaires */}
        <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
          <h3 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-5">
            <i className="fa-solid fa-gauge-high mr-2"></i>Performance
          </h3>
          <div className="flex justify-around">
            <div className="relative">
              <GaugeCircle
                value={analysis.healthScore} max={100}
                label="Santé globale" sublabel="/ 100"
              />
            </div>
            <div className="relative">
              <GaugeCircle
                value={Math.max(0, analysis.margeEBE)} max={15}
                label="Marge EBE" sublabel="%"
                color={analysis.margeEBE > 8 ? '#22c55e' : analysis.margeEBE > 4 ? '#f59e0b' : '#ef4444'}
              />
            </div>
            <div className="relative">
              <GaugeCircle
                value={Math.max(0, analysis.margeNette)} max={10}
                label="Marge nette" sublabel="%"
                color={analysis.margeNette > 5 ? '#22c55e' : analysis.margeNette > 2 ? '#f59e0b' : '#ef4444'}
              />
            </div>
          </div>
        </div>

        {/* Feux tricolores */}
        <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
          <h3 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">
            <i className="fa-solid fa-traffic-light mr-2"></i>Diagnostic rapide
          </h3>
          <div className="space-y-2">
            {analysis.feux.map((f, i) => (
              <TrafficLight key={i} status={f.status} label={f.label} detail={f.detail} subItems={f.subItems} />
            ))}
          </div>
        </div>
      </div>

      {/* ─── EQUILIBRE FR/BFR/TN + CHARGES ─── */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {/* Triangle financier */}
        <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
          <h3 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-5">
            <i className="fa-solid fa-scale-balanced mr-2"></i>Équilibre financier
          </h3>
          <div className="space-y-4">
            <div className="flex items-center justify-between p-3 rounded-xl bg-blue-50">
              <div>
                <span className="text-xs font-bold text-blue-700">Fonds de Roulement</span>
                <p className="text-[10px] text-blue-500">Ce que l'entreprise a en réserve</p>
              </div>
              <span className={`text-lg font-black ${analysis.frng >= 0 ? 'text-blue-700' : 'text-red-600'}`}>
                {fmt(analysis.frng)}
              </span>
            </div>
            <div className="flex justify-center text-slate-300">
              <i className="fa-solid fa-minus text-xl"></i>
            </div>
            <div className="flex items-center justify-between p-3 rounded-xl bg-amber-50">
              <div>
                <span className="text-xs font-bold text-amber-700">Besoin en Fonds de Roulement</span>
                <p className="text-[10px] text-amber-500">Ce que l'activité consomme</p>
              </div>
              <span className="text-lg font-black text-amber-700">{fmt(analysis.bfr)}</span>
            </div>
            <div className="flex justify-center text-slate-300">
              <i className="fa-solid fa-equals text-xl"></i>
            </div>
            <div className={`flex items-center justify-between p-3 rounded-xl ${analysis.tn >= 0 ? 'bg-emerald-50' : 'bg-red-50'}`}>
              <div>
                <span className={`text-xs font-bold ${analysis.tn >= 0 ? 'text-emerald-700' : 'text-red-700'}`}>
                  Trésorerie Nette
                </span>
                <p className={`text-[10px] ${analysis.tn >= 0 ? 'text-emerald-500' : 'text-red-500'}`}>
                  {analysis.tn >= 0 ? 'L\'entreprise a du cash disponible' : 'L\'entreprise manque de cash'}
                </p>
              </div>
              <span className={`text-lg font-black ${analysis.tn >= 0 ? 'text-emerald-700' : 'text-red-600'}`}>
                {fmt(analysis.tn)}
              </span>
            </div>
          </div>
        </div>

        {/* Répartition des charges */}
        <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
          <h3 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-5">
            <i className="fa-solid fa-chart-pie mr-2"></i>Où part votre argent ?
          </h3>
          {(() => {
            const sorted = [...analysis.expenses].sort((a, b) => b.value - a.value);
            const maxVal = sorted.length > 0 ? sorted[0].value : 1;
            return sorted.map((e, i) => (
              <HBar key={i} label={e.label} value={e.value} max={maxVal} color={e.color} />
            ));
          })()}
          <div className="mt-3 pt-3 border-t border-slate-100 flex justify-between">
            <span className="text-xs font-bold text-slate-500">Total charges</span>
            <span className="text-sm font-black text-slate-900">{fmt(data.totalCharges || 0)}</span>
          </div>
        </div>
      </div>

      {/* ─── ANOMALIES COMPTABLES ─── */}
      {analysis.anomalies.length > 0 && (
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="bg-gradient-to-r from-red-50 to-amber-50 p-5 border-b border-slate-100">
            <h3 className="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-2">
              <i className="fa-solid fa-triangle-exclamation text-red-500"></i>
              Anomalies & Alertes Comptables
              <span className="ml-auto bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-[10px] font-black">
                {analysis.anomalies.length}
              </span>
            </h3>
          </div>
          <div className="divide-y divide-slate-100">
            {analysis.anomalies
              .sort((a, b) => (a.severity === 'critical' ? -1 : 1) - (b.severity === 'critical' ? -1 : 1))
              .map((a, i) => (
              <div key={i} className={`p-4 ${a.severity === 'critical' ? 'bg-red-50/30' : 'bg-amber-50/20'}`}>
                <div className="flex items-start gap-3">
                  <div className={`w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 ${a.severity === 'critical' ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600'}`}>
                    <i className={`fa-solid ${a.icon} text-sm`}></i>
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 mb-1">
                      <span className={`text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded ${a.severity === 'critical' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'}`}>
                        {a.severity === 'critical' ? '🔴 Critique' : '🟠 Attention'}
                      </span>
                    </div>
                    <p className="text-sm font-bold text-slate-800 mb-0.5">{a.title}</p>
                    <p className="text-xs text-slate-600">{a.detail}</p>
                    <p className="text-[11px] text-blue-600 mt-1.5 flex items-center gap-1.5">
                      <i className="fa-solid fa-lightbulb text-amber-400 text-[9px]"></i>
                      {a.hint}
                    </p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* ─── POINTS ATTENTION RAPIDES ─── */}
      <div className="bg-gradient-to-r from-slate-50 to-blue-50 rounded-2xl border border-slate-200 p-6">
        <h3 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">
          <i className="fa-solid fa-lightbulb mr-2 text-amber-400"></i>En un coup d'œil
        </h3>
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div className="text-center p-3">
            <div className="text-2xl font-black text-slate-900">{Math.round(analysis.dso)}j</div>
            <div className="text-xs text-slate-500">Vos clients payent en</div>
            <div className={`text-[10px] font-bold mt-1 ${analysis.dso < 45 ? 'text-emerald-600' : analysis.dso < 90 ? 'text-amber-600' : 'text-red-600'}`}>
              {analysis.dso < 45 ? '✓ Rapide' : analysis.dso < 90 ? '⚠ À accélérer' : '✗ Trop lent'}
            </div>
          </div>
          <div className="text-center p-3">
            <div className="text-2xl font-black text-slate-900">{Math.round(analysis.dpo)}j</div>
            <div className="text-xs text-slate-500">Vous payez vos fournisseurs en</div>
            <div className={`text-[10px] font-bold mt-1 ${analysis.dpo > 30 ? 'text-emerald-600' : 'text-amber-600'}`}>
              {analysis.dpo > 30 ? '✓ Bon levier' : '⚠ Rapide — négociez'}
            </div>
          </div>
          <div className="text-center p-3">
            <div className="text-2xl font-black text-slate-900">{analysis.cafSurCA.toFixed(1)}%</div>
            <div className="text-xs text-slate-500">Du CA génère du cash</div>
            <div className={`text-[10px] font-bold mt-1 ${analysis.cafSurCA > 5 ? 'text-emerald-600' : analysis.cafSurCA > 2 ? 'text-amber-600' : 'text-red-600'}`}>
              {analysis.cafSurCA > 5 ? '✓ Bonne capacité' : analysis.cafSurCA > 2 ? '⚠ Marge faible' : '✗ Insuffisant'}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ExecSummaryDashboard;
