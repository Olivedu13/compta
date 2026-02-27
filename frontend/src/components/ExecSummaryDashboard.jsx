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
const TrafficLight = ({ status, label, detail }) => {
  const colors = {
    green: { bg: 'bg-emerald-500', ring: 'ring-emerald-200', icon: 'fa-check', text: 'text-emerald-700', label: 'Bon' },
    yellow: { bg: 'bg-amber-400', ring: 'ring-amber-200', icon: 'fa-minus', text: 'text-amber-700', label: 'Vigilance' },
    red: { bg: 'bg-red-500', ring: 'ring-red-200', icon: 'fa-xmark', text: 'text-red-700', label: 'Alerte' },
  };
  const c = colors[status] || colors.yellow;

  return (
    <div className="flex items-center gap-3 p-3 rounded-xl bg-slate-50/80">
      <div className={`w-9 h-9 rounded-full ${c.bg} ring-4 ${c.ring} flex items-center justify-center flex-shrink-0`}>
        <i className={`fa-solid ${c.icon} text-white text-xs`}></i>
      </div>
      <div className="min-w-0">
        <div className="flex items-center gap-2">
          <span className="text-sm font-bold text-slate-800">{label}</span>
          <span className={`text-[10px] font-bold uppercase ${c.text}`}>{c.label}</span>
        </div>
        <p className="text-xs text-slate-500 truncate">{detail}</p>
      </div>
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

    // Feux tricolores
    const feux = [
      {
        label: 'Rentabilité',
        status: margeNette > 5 ? 'green' : margeNette > 2 ? 'yellow' : 'red',
        detail: `Marge nette ${margeNette.toFixed(1)}% ${margeNette > 5 ? '— confortable' : margeNette > 2 ? '— à renforcer' : '— insuffisante'}`,
      },
      {
        label: 'Trésorerie',
        status: tn > 0 && tn > bfr * 0.1 ? 'green' : tn > 0 ? 'yellow' : 'red',
        detail: `TN ${(tn / 1000).toFixed(0)}k€ ${tn > 0 ? '— positive' : '— négative, tension'}`,
      },
      {
        label: 'Autonomie',
        status: (ratios.financialAutonomy || 0) > 0.3 ? 'green' : (ratios.financialAutonomy || 0) > 0.15 ? 'yellow' : 'red',
        detail: `${((ratios.financialAutonomy || 0) * 100).toFixed(0)}% de capitaux propres`,
      },
      {
        label: 'Encaissement clients',
        status: dso < 45 ? 'green' : dso < 90 ? 'yellow' : 'red',
        detail: `${Math.round(dso)} jours en moyenne ${dso < 45 ? '— rapide' : dso < 90 ? '— à accélérer' : '— trop lent'}`,
      },
      {
        label: 'Seuil de rentabilité',
        status: ca > breakEven * 1.1 ? 'green' : ca > breakEven ? 'yellow' : 'red',
        detail: ca >= breakEven
          ? `Dépassé de ${((ca / breakEven - 1) * 100).toFixed(0)}% — marge de sécurité`
          : `${((1 - ca / breakEven) * 100).toFixed(0)}% en dessous — déficitaire`,
      },
      {
        label: 'Endettement',
        status: (ratios.gearing || 0) < 1 ? 'green' : (ratios.gearing || 0) < 2 ? 'yellow' : 'red',
        detail: `Gearing ${(ratios.gearing || 0).toFixed(1)}x ${(ratios.gearing || 0) < 1 ? '— maîtrisé' : '— élevé'}`,
      },
    ];

    return {
      ca, ebe, rn, caf, tn, bfr, frng, dso, dpo, equity, debt, bankOverdraft,
      totalAssets, healthScore, breakEven, margeNette, margeEBE, cafSurCA,
      evolCA, evolRN, evolEBE, verdict, verdictColor, verdictIcon, verdictDesc,
      feux, expenses, ratios,
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
              <TrafficLight key={i} status={f.status} label={f.label} detail={f.detail} />
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
