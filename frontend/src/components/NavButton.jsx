import React from 'react';

/**
 * NavButton - Bouton de navigation dans la barre de tabs
 */
const NavButton = ({ active, onClick, label }) => (
  <button
    onClick={onClick}
    className={`px-6 py-2.5 rounded-xl text-[10px] font-black transition-all tracking-widest uppercase ${
      active ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-900'
    }`}
  >
    {label}
  </button>
);

export default NavButton;
