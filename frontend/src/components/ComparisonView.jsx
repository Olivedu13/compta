import React from 'react';
import {
  ResponsiveContainer,
  BarChart,
  Bar,
  CartesianGrid,
  XAxis,
  YAxis,
  Tooltip,
  Legend,
} from 'recharts';

/**
 * ComparisonRow - Ligne du tableau comparatif
 */
const ComparisonRow = ({ label, values, format }) => {
  const last = Number(values[values.length - 1]) || 0;
  const prev = Number(values[values.length - 2]) || 0;
  const evol = prev !== 0 ? ((last - prev) / Math.abs(prev)) * 100 : 0;

  return (
    <tr className="hover:bg-slate-50 transition-colors">
      <td className="px-6 py-4 text-sm font-medium text-slate-600">{label}</td>
      {values.map((v, idx) => (
        <td key={idx} className="px-6 py-4 text-sm text-slate-900 text-right font-mono">
          {format(Number(v) || 0)}
        </td>
      ))}
      <td className={`px-6 py-4 text-sm text-right font-bold ${evol >= 0 ? 'text-emerald-600' : 'text-red-500'}`}>
        {evol > 0 ? '+' : ''}
        {isFinite(evol) ? evol.toFixed(1) : '0.0'}%
      </td>
    </tr>
  );
};

/**
 * ComparisonView - Vue historique / comparaison pluriannuelle
 */
const ComparisonView = ({ years }) => {
  const data = Object.values(years).sort((a, b) => a.year - b.year);

  const chartData = data.map((d) => ({
    name: `Exercice ${d.year}`,
    CA: Number(d.revenue) || 0,
    EBE: Number(d.ebitda) || 0,
    Net: Number(d.netIncome) || 0,
  }));

  const fmt = (v) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(
      Number(v) || 0,
    );

  const pct = (v) => {
    const n = Number(v);
    return isFinite(n) ? `${n.toFixed(1)}%` : '0.0%';
  };

  const dec = (v) => {
    const n = Number(v);
    return isFinite(n) ? n.toFixed(2) : '0.00';
  };

  return (
    <div className="space-y-8 animate-in fade-in slide-in-from-left-4 duration-500">
      <div>
        <h1 className="text-3xl font-bold text-slate-900">Comparatif des Exercices</h1>
        <p className="text-slate-500">Visualisation de l&apos;évolution pluriannuelle de votre activité.</p>
      </div>

      {/* Bar Chart */}
      <div className="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
        <h3 className="text-lg font-semibold mb-8 flex items-center gap-2">
          <i className="fa-solid fa-arrows-up-down text-blue-500"></i>
          Trajectoire de Croissance
        </h3>
        <div className="h-[400px]">
          <ResponsiveContainer width="100%" height="100%">
            <BarChart data={chartData}>
              <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
              <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fill: '#64748b', fontSize: 13 }} />
              <YAxis
                axisLine={false}
                tickLine={false}
                tick={{ fill: '#64748b', fontSize: 12 }}
                tickFormatter={(v) => `${(Number(v) || 0) / 1e3}k`}
              />
              <Tooltip
                contentStyle={{ borderRadius: '12px', border: 'none', boxShadow: '0 4px 12px rgba(0,0,0,0.1)' }}
                formatter={(v) => fmt(v)}
              />
              <Legend verticalAlign="top" height={36} />
              <Bar dataKey="CA" fill="#3b82f6" radius={[6, 6, 0, 0]} name="Chiffre d'Affaires" />
              <Bar dataKey="EBE" fill="#10b981" radius={[6, 6, 0, 0]} name="EBITDA" />
              <Bar dataKey="Net" fill="#6366f1" radius={[6, 6, 0, 0]} name="Résultat Net" />
            </BarChart>
          </ResponsiveContainer>
        </div>
      </div>

      {/* Comparison Table */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <table className="w-full text-left border-collapse">
          <thead>
            <tr className="bg-slate-50 border-b border-slate-200">
              <th className="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Indicateur</th>
              {data.map((d) => (
                <th key={d.year} className="px-6 py-4 text-xs font-bold text-slate-900 uppercase tracking-wider text-right">
                  {d.year}
                </th>
              ))}
              <th className="px-6 py-4 text-xs font-bold text-blue-600 uppercase tracking-wider text-right">
                Évolution %
              </th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            <ComparisonRow label="Chiffre d'Affaires" values={data.map((d) => d.revenue)} format={fmt} />
            <ComparisonRow label="Marge d'Exploitation (%)" values={data.map((d) => d.ratios?.operatingMargin)} format={pct} />
            <ComparisonRow label="EBITDA (EBE)" values={data.map((d) => d.ebitda)} format={fmt} />
            <ComparisonRow label="Résultat Net" values={data.map((d) => d.netIncome)} format={fmt} />
            <ComparisonRow label="BFR / Trésorerie" values={data.map((d) => d.tn)} format={fmt} />
            <ComparisonRow label="Ratio de Solvabilité" values={data.map((d) => d.ratios?.solvency)} format={dec} />
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default ComparisonView;
