import React from 'react';

/**
 * SigRow - Ligne du tableau SIG
 */
const SigRow = ({ label, val, bold, color, large }) => (
  <tr className="hover:bg-slate-50 transition-colors">
    <td className={`px-10 py-7 text-sm ${bold ? 'font-black text-slate-900 uppercase tracking-tight' : 'font-bold text-slate-500'}`}>
      {label}
    </td>
    <td className={`px-10 py-7 text-right font-black ${large ? 'text-4xl tracking-tighter' : 'text-lg'} ${color || 'text-slate-900'}`}>
      {new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val || 0)}
    </td>
  </tr>
);

export default SigRow;
