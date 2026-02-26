import React from 'react';

/**
 * KpiCard - Carte KPI cliquable (CA, EBE, CAF, etc.)
 */
const KpiCard = ({ label, value, detail, icon, color, onClick }) => {
  const colorMap = {
    blue: 'bg-blue-50 text-blue-600',
    emerald: 'bg-emerald-50 text-emerald-600',
    amber: 'bg-amber-50 text-amber-600',
    indigo: 'bg-indigo-50 text-indigo-600',
    rose: 'bg-rose-50 text-rose-600',
    violet: 'bg-violet-50 text-violet-600',
  };

  return (
    <button
      onClick={onClick}
      className="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all group overflow-hidden relative text-left w-full block"
    >
      <div className="flex justify-between items-start mb-6">
        <div className="flex flex-col">
          <span className="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">{label}</span>
          <span className="text-[8px] text-slate-400 font-bold uppercase mt-1">{detail}</span>
        </div>
        <div className={`w-12 h-12 rounded-2xl flex items-center justify-center transition-all ${colorMap[color]} shadow-lg group-hover:rotate-12`}>
          <i className={`fa-solid ${icon} text-lg`}></i>
        </div>
      </div>
      <div className="text-3xl font-black text-slate-900 tracking-tighter">{value}</div>
    </button>
  );
};

export default KpiCard;
