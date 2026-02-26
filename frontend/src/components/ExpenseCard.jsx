import React from 'react';

/**
 * ExpenseCard - Carte de poste de charges
 */
const ExpenseCard = ({ label, val, color, onClick }) => {
  const colorMap = {
    blue: 'border-blue-100 text-blue-600',
    indigo: 'border-indigo-100 text-indigo-600',
    amber: 'border-amber-100 text-amber-600',
    pink: 'border-pink-100 text-pink-600',
    emerald: 'border-emerald-100 text-emerald-600',
    cyan: 'border-cyan-100 text-cyan-600',
  };

  return (
    <button
      onClick={onClick}
      className={`p-8 rounded-[2rem] border-2 bg-white ${colorMap[color]} flex flex-col items-start text-left shadow-sm hover:shadow-xl hover:scale-105 transition-all group`}
    >
      <span className="text-sm font-black uppercase tracking-[0.2em] opacity-40 mb-10 group-hover:opacity-100 transition-opacity">
        {label}
      </span>
      <span className="text-xl font-black tracking-tight text-slate-900">{val}</span>
    </button>
  );
};

export default ExpenseCard;
