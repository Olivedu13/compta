import React from 'react';

/**
 * RatioCard - Carte de ratios (Structure, Liquidité, etc.)
 */
const RatioCard = ({ title, items, color, onClick }) => {
  const colorMap = {
    slate: 'bg-slate-50 border-slate-200 text-slate-900',
    blue: 'bg-blue-50 border-blue-100 text-blue-900',
    indigo: 'bg-indigo-50 border-indigo-100 text-indigo-900',
    emerald: 'bg-emerald-50 border-emerald-100 text-emerald-900',
    violet: 'bg-violet-50 border-violet-100 text-violet-900',
  };

  return (
    <button
      onClick={onClick}
      className={`p-8 rounded-[2.5rem] border ${colorMap[color]} shadow-sm text-left hover:shadow-xl hover:-translate-y-1 transition-all group w-full`}
    >
      <div className="flex justify-between items-start mb-6">
        <h4 className="text-[9px] font-black uppercase tracking-[0.3em] opacity-60">{title}</h4>
        <i className="fa-solid fa-circle-info text-[10px] opacity-20 group-hover:opacity-100 transition-opacity"></i>
      </div>
      <div className="space-y-4">
        {items.map((item, idx) => (
          <div key={idx} className="flex flex-col">
            <span className="text-[8px] font-black uppercase opacity-40 mb-1 tracking-widest">{item.label}</span>
            <span className="text-sm font-black tracking-tight">{item.val}</span>
          </div>
        ))}
      </div>
    </button>
  );
};

export default RatioCard;
