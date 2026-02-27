import React, { useState } from 'react';
import {
  ResponsiveContainer,
  AreaChart,
  Area,
  BarChart,
  Bar,
  PieChart,
  Pie,
  Cell,
  CartesianGrid,
  XAxis,
  YAxis,
  Tooltip,
  Legend,
} from 'recharts';
import KpiCard from './KpiCard';
import RatioCard from './RatioCard';
import ExpenseCard from './ExpenseCard';
import SigRow from './SigRow';

/**
 * DashboardView - Vue principale du tableau de bord
 */
const DashboardView = ({ data }) => {
  const [modal, setModal] = useState(null);

  if (!data) return null;

  const fmt = (v) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(
      Number(v) || 0,
    );
  const pct = (v) => `${(Number(v) || 0).toFixed(1)}%`;
  const dec = (v, d = 1) => (Number(v) || 0).toFixed(d);

  // Monthly data
  const monthlyData = Object.entries(data.monthlyBreakdown || {}).map(([m, d]) => ({
    name: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'][parseInt(m) - 1],
    CA: d.revenue || 0,
    Net: d.net || 0,
  }));

  // Bilan simplifié
  const bilanData = [
    { name: 'FRNG', val: data.frng || 0 },
    { name: 'BFR', val: data.bfr || 0 },
    { name: 'Tréso', val: data.tn || 0 },
  ];

  const expenseData = data.expenseBreakdown || [];
  const ca = data.revenue;
  const achats = data.expenseBreakdown[0].value;
  const marginRateDecimal = data.marginRate / 100;
  const chargesFixes = data.expenseBreakdown.slice(1).reduce((s, e) => s + e.value, 0);

  return (
    <div className="space-y-10 animate-in fade-in duration-500 pb-24">
      {/* Header */}
      <div className="mb-2">
        <h2 className="font-black text-slate-900 uppercase tracking-tight text-2xl">Indicateurs Clés de Gestion</h2>
      </div>

      {/* KPI Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <KpiCard
          label="Chiffre d'Affaires"
          value={fmt(data.revenue)}
          detail="Total des ventes HT"
          icon="fa-sack-dollar"
          color="blue"
          onClick={() => setModal({ title: "Audit Chiffre d'Affaires", items: data.details.revenue })}
        />
        <KpiCard
          label="Excédent Brut (EBE)"
          value={fmt(data.ebitda)}
          detail="Performance d'exploitation"
          icon="fa-chart-line"
          color="emerald"
          onClick={() =>
            setModal({
              title: "Calcul de l'EBE",
              formula: {
                label: 'EBE = Valeur Ajoutée + Subv. - Impôts - Personnel',
                parts: [
                  { label: 'Valeur Ajoutée', value: data.sig.valeurAjoutee, operator: '+' },
                  { label: 'Subventions', value: 0, operator: '+' },
                  { label: 'Impôts & Taxes', value: data.expenseBreakdown[2].value, operator: '-' },
                  { label: 'Personnel', value: data.expenseBreakdown[3].value, operator: '-' },
                  { label: 'EBE Final', value: data.ebitda, operator: '=' },
                ],
              },
            })
          }
        />
        <KpiCard
          label="Capacité Auto. (CAF)"
          value={fmt(data.caf)}
          detail="Flux généré"
          icon="fa-bolt"
          color="amber"
          onClick={() =>
            setModal({
              title: 'Calcul de la CAF',
              formula: {
                label: 'CAF = Résultat Net + Amortissements - Reprises',
                parts: [
                  { label: 'Résultat Net', value: data.netIncome, operator: '+' },
                  { label: 'Amortissements (68)', value: data.expenseBreakdown[4].value, operator: '+' },
                  { label: 'CAF Finale', value: data.caf, operator: '=' },
                ],
              },
            })
          }
        />
        <KpiCard
          label="Charges Exploit."
          value={fmt(data.totalCharges)}
          detail="Total charges exploitation"
          icon="fa-file-invoice-dollar"
          color="rose"
          onClick={() =>
            setModal({
              title: 'Détail des Charges d\'Exploitation',
              formula: {
                label: 'Total = Achats + Services + Impôts + Personnel + Gestion + Financier',
                parts: [
                  { label: 'Achats (60x)', value: data.expenseBreakdown[0].value, operator: '+' },
                  { label: 'Services Ext (61+62)', value: data.expenseBreakdown[1].value, operator: '+' },
                  { label: 'Impôts & Taxes (63)', value: data.expenseBreakdown[2].value, operator: '+' },
                  { label: 'Personnel (64)', value: data.expenseBreakdown[3].value, operator: '+' },
                  { label: 'Gestion (65+68)', value: data.expenseBreakdown[4].value, operator: '+' },
                  { label: 'Financier (66+627)', value: data.expenseBreakdown[5].value, operator: '+' },
                  { label: 'Total Charges', value: data.totalCharges, operator: '=' },
                ],
              },
            })
          }
        />
        <KpiCard
          label="Stocks"
          value={fmt(data.stocks)}
          detail="Valeur inventaire"
          icon="fa-boxes-stacked"
          color="amber"
          onClick={() => setModal({ title: 'Audit Stocks', items: data.details.stocks })}
        />
        <KpiCard
          label="Trésorerie Nette"
          value={fmt(data.tn)}
          detail="Liquidités disponibles"
          icon="fa-piggy-bank"
          color="indigo"
          onClick={() => setModal({ title: 'Audit Trésorerie', items: data.details.cash })}
        />
        <KpiCard
          label="Point Mort (€)"
          value={fmt(data.breakEvenPoint)}
          detail="Seuil de rentabilité"
          icon="fa-flag-checkered"
          color="rose"
          onClick={() =>
            setModal({
              title: 'Calcul du Point Mort',
              formula: {
                label: 'PM = Charges Fixes / ((CA - Coûts Variables) / CA)',
                parts: [
                  { label: "Chiffre d'Affaires (CA)", value: ca, operator: '+' },
                  { label: 'Coûts Variables (Achats)', value: achats, operator: '-' },
                  { label: 'Charges Fixes (Total 61 à 68)', value: chargesFixes, operator: '/' },
                  { label: 'Taux Marge s/ CV appliqué', value: marginRateDecimal, operator: undefined },
                  { label: 'Point Mort Final (€)', value: data.breakEvenPoint, operator: '=' },
                ],
              },
            })
          }
        />
        <KpiCard
          label="Résultat Net"
          value={fmt(data.netIncome)}
          detail="Vérification Profit"
          icon="fa-trophy"
          color="violet"
          onClick={() =>
            setModal({
              title: 'Calcul Résultat Net',
              formula: {
                label: 'Net = REX + Financier + Exceptionnel - IS',
                parts: [
                  { label: 'Résultat Exploit.', value: data.sig.resultatExploitation, operator: '+' },
                  { label: 'Résultat Financier', value: data.sig.resultatCourant - data.sig.resultatExploitation, operator: '+' },
                  { label: 'Impôt Sociétés', value: data.sig.is, operator: '-' },
                  { label: 'Résultat Net', value: data.netIncome, operator: '=' },
                ],
              },
            })
          }
        />
      </div>

      {/* Separator */}
      <div className="border-t border-slate-200 py-2 text-center">
        <span className="text-[10px] font-black text-slate-400 uppercase tracking-[0.4em]">
          Indicateurs de Performance Expert
        </span>
      </div>

      {/* Ratio Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
        <RatioCard
          title="🧱 Structure"
          color="slate"
          items={[
            { label: 'Autonomie Fin.', val: pct(data.ratios?.financialAutonomy ? data.ratios.financialAutonomy * 100 : 0) },
            { label: 'Endettement', val: dec(data.ratios?.debtRatio, 2) },
            { label: 'Capacité Remb.', val: `${dec(data.ratios?.repaymentCapacity, 1)} ans` },
          ]}
          onClick={() =>
            setModal({
              title: 'Audit de Structure Financière',
              formula: {
                label: "Calcul de solvabilité et d'autonomie",
                parts: [
                  { label: 'Capitaux Propres', value: data.equity, operator: '+' },
                  { label: 'Total Bilan', value: data.totalAssets, operator: '/' },
                  { label: 'Autonomie Financière (%)', value: data.ratios.financialAutonomy * 100, operator: '=' },
                  { label: 'Dettes Financières', value: data.debt + data.bankOverdraft, operator: '+' },
                  { label: 'Capacité Remb. (Dette/CAF)', value: data.ratios.repaymentCapacity, operator: undefined },
                ],
              },
            })
          }
        />
        <RatioCard
          title="💧 Liquidité"
          color="blue"
          items={[
            { label: 'FRNG', val: fmt(data.frng) },
            { label: 'BFR', val: fmt(data.bfr) },
            { label: 'Ratio Liquidité', val: dec(data.ratios?.liquidityGeneral, 2) },
          ]}
          onClick={() =>
            setModal({
              title: "Équilibre Financier (Cycle d'Exploitation)",
              formula: {
                label: 'Calcul du BFR et du Fond de Roulement',
                parts: [
                  { label: 'Ressources Stables (Equity+DetteLMT)', value: data.equity + data.debt, operator: '+' },
                  { label: 'Emplois Stables (Immos Nettes)', value: data.fixedAssets, operator: '-' },
                  { label: 'FRNG (Excédent stable)', value: data.frng, operator: '=' },
                  { label: 'Besoins (Stocks+Clients)', value: data.stocks + data.receivables, operator: '+' },
                  { label: 'Ressources (Fournisseurs+Fisc)', value: data.payables, operator: '-' },
                  { label: 'BFR (Besoin Exploitation)', value: data.bfr, operator: '=' },
                  { label: 'Trésorerie Nette (FRNG - BFR)', value: data.tn, operator: '=' },
                ],
              },
            })
          }
        />
        <RatioCard
          title="⚙️ Activité"
          color="indigo"
          items={[
            { label: 'Rotation Stocks', val: `${Math.round(360 / (data.inventoryTurnover || 1))} j` },
            { label: 'DSO (Clients)', val: `${Math.round(data.dso || 0)} j` },
            { label: 'Vitesse Actif', val: dec(data.ratios?.assetTurnover, 2) },
          ]}
          onClick={() =>
            setModal({
              title: "Audit d'Efficacité Opérationnelle",
              formula: {
                label: 'Vitesse de rotation des actifs et délais',
                parts: [
                  { label: 'Créances Clients', value: data.receivables, operator: '+' },
                  { label: 'CA TTC (CA * 1.2)', value: data.revenue * 1.2, operator: '/' },
                  { label: 'Coefficient Jours', value: 360, operator: '*' },
                  { label: 'DSO (Délai Client Moyen)', value: data.dso, operator: '=' },
                  { label: "Chiffre d'Affaires", value: data.revenue, operator: '+' },
                  { label: 'Total Bilan', value: data.totalAssets, operator: '/' },
                  { label: "Vitesse de l'Actif", value: data.ratios.assetTurnover, operator: '=' },
                ],
              },
            })
          }
        />
        <RatioCard
          title="💰 Rentabilité"
          color="emerald"
          items={[
            { label: 'ROE (FP)', val: pct(data.ratios?.roe) },
            { label: 'ROA (Eco)', val: pct(data.ratios?.roa) },
            { label: 'Marge Exploit.', val: pct(data.ratios?.operatingMargin) },
          ]}
          onClick={() =>
            setModal({
              title: 'Audit de Rentabilité (Performance)',
              formula: {
                label: 'Calcul du rendement financier et économique',
                parts: [
                  { label: 'Résultat Net', value: data.netIncome, operator: '+' },
                  { label: 'Capitaux Propres', value: data.equity, operator: '/' },
                  { label: 'ROE (Rendement FP)', value: data.ratios.roe / 100, operator: '=' },
                  { label: 'EBE (Excédent Brut)', value: data.ebitda, operator: '+' },
                  { label: "Chiffre d'Affaires", value: data.revenue, operator: '/' },
                  { label: "Marge d'Exploitation (%)", value: data.ratios.operatingMargin / 100, operator: '=' },
                ],
              },
            })
          }
        />
        <RatioCard
          title="🏗️ Patrimoine"
          color="violet"
          items={[
            { label: 'Immo / Total', val: pct(data.ratios?.fixedAssetsWeight ? data.ratios.fixedAssetsWeight * 100 : 0) },
            { label: 'Fonds Propres', val: fmt(data.equity) },
            { label: 'Cash Brut', val: fmt(data.cashPositive) },
          ]}
          onClick={() =>
            setModal({
              title: 'Analyse Patrimoniale',
              formula: {
                label: "Solidité de l'assise financière",
                parts: [
                  { label: 'Immobilisations Nettes', value: data.fixedAssets, operator: '+' },
                  { label: 'Total Bilan', value: data.totalAssets, operator: '/' },
                  { label: 'Poids des Immobilisations (%)', value: data.ratios.fixedAssetsWeight * 100, operator: '=' },
                  { label: 'Trésorerie Positive (Cash)', value: data.cashPositive, operator: '+' },
                  { label: 'Concours Bancaires', value: data.bankOverdraft, operator: '-' },
                  { label: 'Trésorerie Nette Finale', value: data.tn, operator: '=' },
                ],
              },
            })
          }
        />
      </div>

      {/* Charts: Monthly + Bilan */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div className="lg:col-span-2 bg-white p-10 rounded-[3rem] border border-slate-200 shadow-sm">
          <h3 className="font-black text-slate-900 uppercase tracking-tight text-sm mb-12">Activité Mensuelle</h3>
          <div className="h-[300px]">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={monthlyData}>
                <defs>
                  <linearGradient id="colorCA" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#3b82f6" stopOpacity={0.1} />
                    <stop offset="95%" stopColor="#3b82f6" stopOpacity={0} />
                  </linearGradient>
                  <linearGradient id="colorNet" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#10b981" stopOpacity={0.1} />
                    <stop offset="95%" stopColor="#10b981" stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fill: '#94a3b8', fontSize: 10, fontWeight: 700 }} />
                <YAxis axisLine={false} tickLine={false} tick={{ fill: '#94a3b8', fontSize: 10 }} tickFormatter={(v) => `${v / 1e3}k`} />
                <Tooltip contentStyle={{ borderRadius: '16px', border: 'none', boxShadow: '0 10px 15px -3px rgba(0,0,0,0.1)' }} formatter={(v) => fmt(v)} />
                <Legend verticalAlign="top" height={36} align="right" iconType="circle" />
                <Area type="monotone" dataKey="CA" stroke="#3b82f6" fillOpacity={1} fill="url(#colorCA)" strokeWidth={3} name="Ventes" />
                <Area type="monotone" dataKey="Net" stroke="#10b981" fillOpacity={1} fill="url(#colorNet)" strokeWidth={3} name="Profit Net" />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </div>

        <div className="bg-white p-10 rounded-[3rem] border border-slate-200 shadow-sm flex flex-col">
          <h3 className="font-black text-slate-900 uppercase tracking-tight text-sm mb-12">Bilan Simplifié</h3>
          <div className="flex-1 min-h-[250px]">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={bilanData}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 10, fontWeight: 800, fill: '#64748b' }} />
                <YAxis hide={true} />
                <Tooltip formatter={(v) => fmt(v)} />
                <Bar dataKey="val" radius={[10, 10, 0, 0]}>
                  {bilanData.map((entry, idx) => (
                    <Cell key={`cell-${idx}`} fill={entry.val < 0 ? '#ef4444' : idx === 2 ? '#10b981' : '#3b82f6'} />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>
      </div>

      {/* Charges d'Exploitation + Donut */}
      <div className="bg-white p-10 rounded-[3rem] border border-slate-200 shadow-sm grid grid-cols-1 lg:grid-cols-3 gap-12">
        <div className="lg:col-span-2">
          <h3 className="font-black text-slate-900 uppercase tracking-tight text-2xl mb-10">Charges d&apos;Exploitation</h3>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <ExpenseCard label="Achats" val={fmt(data.expenseBreakdown[0].value)} color="blue" onClick={() => setModal({ title: 'Audit Achats', items: data.details.purchases })} />
            <ExpenseCard label="Charges Ext." val={fmt(data.expenseBreakdown[1].value)} color="indigo" onClick={() => setModal({ title: 'Audit Charges Externes', items: data.details.external })} />
            <ExpenseCard label="Fiscalité" val={fmt(data.expenseBreakdown[2].value)} color="amber" onClick={() => setModal({ title: 'Audit Fiscalité', items: data.details.taxes })} />
            <ExpenseCard label="Personnel" val={fmt(data.expenseBreakdown[3].value)} color="pink" onClick={() => setModal({ title: 'Audit Frais Personnel', items: data.details.personnel })} />
            <ExpenseCard label="Gestion" val={fmt(data.expenseBreakdown[4].value)} color="emerald" onClick={() => setModal({ title: 'Audit Dotations & Gestion', items: data.details.management })} />
            <ExpenseCard label="Financier" val={fmt(data.expenseBreakdown[5].value)} color="cyan" onClick={() => setModal({ title: 'Audit Charges Financières', items: data.details.debt })} />
          </div>
        </div>

        <div className="bg-slate-50/50 rounded-[2.5rem] p-8 border border-slate-100 flex flex-col items-center">
          <h3 className="font-black text-slate-400 uppercase tracking-widest text-[9px] mb-8">Poids des Dépenses</h3>
          <div className="h-[250px] w-full">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={expenseData.filter((e) => e.value > 0)}
                  cx="50%"
                  cy="50%"
                  innerRadius={70}
                  outerRadius={90}
                  paddingAngle={5}
                  dataKey="value"
                  nameKey="label"
                >
                  {expenseData.map((entry, idx) => (
                    <Cell key={`cell-${idx}`} fill={entry.color} />
                  ))}
                </Pie>
                <Tooltip formatter={(v) => fmt(v)} />
                <Legend iconType="circle" wrapperStyle={{ fontSize: '9px', fontWeight: 800, textTransform: 'uppercase' }} />
              </PieChart>
            </ResponsiveContainer>
          </div>
        </div>
      </div>

      {/* Tableau SIG */}
      <div className="bg-white rounded-[3rem] border border-slate-200 shadow-sm overflow-hidden">
        <div className="p-10 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
          <h3 className="font-black text-slate-900 uppercase tracking-tight text-sm">Soldes Intermédiaires de Gestion</h3>
          <span className="text-[9px] font-black text-slate-400 uppercase tracking-widest">Tableau de Bord Normé</span>
        </div>
        <table className="w-full text-left">
          <tbody className="divide-y divide-slate-100">
            <SigRow label="Production de l'exercice" val={data.sig.productionExercice} />
            <SigRow label="Marge de Production" val={data.sig.margeProduction} bold={true} color="text-slate-900" />
            <SigRow label="Valeur Ajoutée (VA)" val={data.sig.valeurAjoutee} bold={true} />
            <SigRow label="EBITDA / Excédent Brut (EBE)" val={data.sig.ebe} bold={true} color="text-blue-600" />
            <SigRow label="Résultat d'Exploitation (REX)" val={data.sig.resultatExploitation} />
            <SigRow label="Résultat Financier" val={data.sig.resultatFinancier} color={(data.sig.resultatFinancier || 0) >= 0 ? 'text-emerald-600' : 'text-red-500'} />
            <SigRow label="RCAI" val={data.sig.resultatCourant} />
            <SigRow label="Capacité d'Autofinancement (CAF)" val={data.caf} color="text-amber-600" />
            <SigRow
              label="Résultat Net Comptable"
              val={data.sig.resultatNet}
              bold={true}
              large={true}
              color={(data.sig.resultatNet || 0) >= 0 ? 'text-emerald-600' : 'text-red-600'}
            />
          </tbody>
        </table>
      </div>

      {/* Audit Modal */}
      {modal && (
        <div
          className="fixed inset-0 bg-slate-900/90 backdrop-blur-md z-50 flex items-center justify-center p-6 animate-in fade-in"
          onClick={() => setModal(null)}
        >
          <div
            className="bg-white rounded-[3rem] w-full max-w-4xl max-h-[90vh] flex flex-col shadow-2xl overflow-hidden"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Modal Header */}
            <div className="p-10 border-b border-slate-100 flex justify-between items-center bg-slate-50">
              <div>
                <h3 className="font-black text-slate-900 uppercase tracking-tight text-xl">{modal.title}</h3>
                <p className="text-[10px] text-slate-400 font-black uppercase tracking-[0.3em] mt-1">
                  Audit Expert - Exercice {data.year}
                </p>
              </div>
              <button
                onClick={() => setModal(null)}
                className="w-12 h-12 rounded-2xl flex items-center justify-center text-slate-400 hover:bg-white hover:text-red-500 shadow-sm transition-all bg-white border border-slate-100"
              >
                <i className="fa-solid fa-times text-xl"></i>
              </button>
            </div>

            {/* Modal Body */}
            <div className="flex-1 overflow-y-auto p-10">
              {modal.formula ? (
                <div className="py-8 px-4">
                  <div className="bg-slate-900 rounded-[2.5rem] p-12 text-white shadow-2xl relative overflow-hidden">
                    <p className="text-blue-400 font-black uppercase tracking-[0.2em] text-[10px] mb-10">
                      {modal.formula.label}
                    </p>
                    <div className="space-y-8 relative z-10">
                      {modal.formula.parts.map((part, idx) => (
                        <div
                          key={idx}
                          className={`flex items-center justify-between ${part.operator === '=' ? 'pt-8 border-t border-white/10 mt-8' : ''}`}
                        >
                          <div className="flex items-center gap-6">
                            <span className="text-slate-500 font-mono text-sm w-8">{part.operator || ' '}</span>
                            <span className="text-slate-300 font-black uppercase tracking-widest text-[11px]">
                              {part.label}
                            </span>
                          </div>
                          <span
                            className={`text-2xl font-black tracking-tighter ${part.operator === '=' ? 'text-emerald-400 text-4xl' : 'text-white'}`}
                          >
                            {part.label.includes('DSO')
                              ? Math.round(part.value) + ' j'
                              : part.label.includes('Capacité Remb')
                                ? part.value.toFixed(1) + ' ans'
                                : part.label.includes('%')
                                  ? (part.value * 100).toFixed(2) + '%'
                                  : part.label.includes('Coefficient') ||
                                      part.label.includes('Vitesse') ||
                                      part.label.includes('Ratio') ||
                                      part.label.includes('Autonomie') ||
                                      part.label.includes('Endettement') ||
                                      part.label.includes('Marge') ||
                                      part.label.includes('ROE') ||
                                      part.label.includes('Poids')
                                    ? part.value.toFixed(2)
                                    : fmt(part.value)}
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
              ) : modal.items ? (
                <table className="w-full text-left">
                  <thead>
                    <tr className="text-[10px] text-slate-400 font-black uppercase tracking-[0.3em] border-b border-slate-100 pb-6">
                      <th className="pb-8">Compte</th>
                      <th className="pb-8">Libellé</th>
                      <th className="pb-8 text-right">Solde HT</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {modal.items
                      .sort((a, b) => Math.abs(b.solde) - Math.abs(a.solde))
                      .map((item, idx) => (
                        <tr key={idx} className="group hover:bg-slate-50 transition-colors">
                          <td className="py-6 text-xs font-mono font-black text-slate-300 group-hover:text-blue-600">
                            {item.code}
                          </td>
                          <td className="py-6 text-sm text-slate-800 font-black uppercase">{item.libelle}</td>
                          <td className={`py-6 text-base text-right font-black ${item.solde < 0 ? 'text-red-500' : 'text-slate-900'}`}>
                            {fmt(item.solde)}
                          </td>
                        </tr>
                      ))}
                  </tbody>
                </table>
              ) : null}
            </div>

            {/* Modal Footer */}
            <div className="p-10 bg-slate-900 text-white flex justify-between items-center shrink-0">
              <span className="text-[10px] font-black uppercase tracking-widest text-slate-400">Total Validé</span>
              <span className="text-3xl font-black tracking-tighter">
                {modal.items
                  ? fmt(modal.items.reduce((s, i) => s + i.solde, 0))
                  : modal.formula?.parts.find((p) => p.operator === '=')?.label.includes('%')
                    ? ((modal.formula?.parts.find((p) => p.operator === '=')?.value || 0) * 100).toFixed(2) + '%'
                    : fmt(modal.formula?.parts.find((p) => p.operator === '=')?.value || 0)}
              </span>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default DashboardView;
