import React, { useState, useEffect, useCallback, useRef } from 'react';
import { fetchAllExercices, uploadFEC, fetchExerciceData } from './lib/dataService';
import { setApiKeys } from './lib/aiService';
import DashboardView from './components/DashboardView';
import ComparisonView from './components/ComparisonView';
import AIAdvisorView from './components/AIAdvisorView';
import SettingsDialog from './components/SettingsDialog';
import NavButton from './components/NavButton';
import PrintableReport from './components/PrintableReport';

const PASSWORD_HASH = '6e7a635139c3a2fe2de8ed9d14a8a5691650ff0680c4afd27b5aebd6893b2ac8';

const App = () => {
  const [state, setState] = useState({
    isLoaded: false,
    isAuthenticated: false,
    years: {},
    currentYear: 0,
    selectedYears: [],
    logs: [],
  });
  const [loading, setLoading] = useState(false);
  const [password, setPassword] = useState('');
  const [authError, setAuthError] = useState('');
  const [authLoading, setAuthLoading] = useState(false);
  const [activeTab, setActiveTab] = useState('dashboard');
  const [showConsole, setShowConsole] = useState(false);
  const [showSettings, setShowSettings] = useState(false);
  const [aiResult, setAiResult] = useState(null);
  const [ceoResult, setCeoResult] = useState(null);
  const consoleEndRef = useRef(null);
  const API_URL = './api.php';

  const log = useCallback((message, level = 'info') => {
    (level === 'error' ? console.error : level === 'warn' ? console.warn : console.log)(`[AUDITLOG] ${message}`);
    const entry = { timestamp: new Date().toLocaleTimeString(), message, level };
    setState((prev) => ({ ...prev, logs: [...prev.logs, entry].slice(-100) }));
  }, []);

  /**
   * Charge les données depuis les APIs serveur
   */
  const loadFromAPI = async () => {
    log('Chargement des données depuis l\'API...', 'info');
    try {
      // Charge les clés API
      try {
        const res = await fetch(API_URL);
        if (res.ok) {
          const data = await res.json();
          if (data.settings) {
            setApiKeys({ gemini: data.settings.api_key_gemini || '', copilot: data.settings.api_key_copilot || '' });
          }
        }
      } catch {}

      // Charge tous les exercices depuis les APIs
      const years = await fetchAllExercices();
      const sortedYears = Object.keys(years).map(Number).sort((a, b) => b - a);

      if (sortedYears.length > 0) {
        setState((prev) => ({
          ...prev,
          isLoaded: true,
          years,
          currentYear: prev.currentYear || sortedYears[0],
          selectedYears: sortedYears,
        }));
        log(`${sortedYears.length} exercice(s) chargés depuis l'API.`, 'info');
      } else {
        log('Aucun exercice disponible.', 'warn');
      }
    } catch (err) {
      log(`Erreur API : ${err.message}`, 'error');
    }
  };

  useEffect(() => {
    if (localStorage.getItem('auditcompta_auth') === 'true') {
      setState((prev) => ({ ...prev, isAuthenticated: true }));
      loadFromAPI();
    }
  }, []);

  /**
   * Authentification par SHA-256 hash
   */
  const handleLogin = async (e) => {
    e.preventDefault();
    const code = password.trim().toUpperCase();
    if (!code) return;

    setAuthLoading(true);
    setAuthError('');

    try {
      // Vérification locale par SHA-256
      const hashBuffer = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(code));
      const hashHex = Array.from(new Uint8Array(hashBuffer))
        .map((b) => b.toString(16).padStart(2, '0'))
        .join('');

      if (hashHex === PASSWORD_HASH) {
        log('Authentification réussie', 'info');
        localStorage.setItem('auditcompta_auth', 'true');
        setState((prev) => ({ ...prev, isAuthenticated: true }));
        loadFromAPI();
        return;
      }

      // Fallback serveur
      const res = await fetch(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ auth_attempt: code }),
      });

      if (res.ok) {
        localStorage.setItem('auditcompta_auth', 'true');
        setState((prev) => ({ ...prev, isAuthenticated: true }));
        loadFromAPI();
      } else {
        setAuthError('CODE INVALIDE');
      }
    } catch {
      setAuthError('ERREUR SERVEUR');
    } finally {
      setAuthLoading(false);
      setPassword('');
    }
  };

  /**
   * Import FEC : upload vers le serveur puis recharge les données via API
   */
  const handleFileUpload = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    setLoading(true);
    log(`Upload du fichier : ${file.name}`, 'info');

    try {
      // 1. Upload vers le serveur
      const result = await uploadFEC(file);
      log(`Import réussi : ${result.count} écritures (exercice ${result.exercice})`, 'info');

      // 2. Recharger les données depuis les APIs
      log('Rechargement des données...', 'info');
      const years = await fetchAllExercices();
      const sortedYears = Object.keys(years).map(Number).sort((a, b) => b - a);

      setState((prev) => ({
        ...prev,
        isLoaded: true,
        years,
        currentYear: result.exercice || sortedYears[0],
        selectedYears: sortedYears,
      }));
      log('Audit terminé avec succès.', 'info');
    } catch (err) {
      log(`Erreur : ${err.message}`, 'error');
    } finally {
      setLoading(false);
    }
  };

  // ==================== AUTHENTICATED VIEW ====================
  if (state.isAuthenticated) {
    return (
      <div className="min-h-screen bg-[#f8fafc] flex flex-col">
        {/* Header */}
        <header className="bg-white border-b border-slate-200 sticky top-0 z-20 h-20 shadow-sm">
          <div className="max-w-7xl mx-auto px-6 h-full flex items-center justify-between gap-8">
            {/* Logo */}
            <div className="flex items-center gap-4 shrink-0">
              <div className="bg-slate-900 px-3 py-1.5 rounded-xl flex items-center justify-center font-black text-white text-sm italic tracking-tighter">
                ATCO BI
              </div>
              <h2 className="text-sm font-black text-slate-900 tracking-tight uppercase hidden lg:block">
                AuditCompta
              </h2>
            </div>

            {/* Navigation */}
            <div className="flex-1 flex items-center justify-center gap-4">
              <nav className="flex items-center gap-1 bg-slate-50 p-1 rounded-2xl border border-slate-200">
                <NavButton active={activeTab === 'dashboard'} onClick={() => setActiveTab('dashboard')} label="Dashboard" />
                <NavButton active={activeTab === 'comparison'} onClick={() => setActiveTab('comparison')} label="Historique" />
                <NavButton active={activeTab === 'advisor'} onClick={() => setActiveTab('advisor')} label="Analyse IA" />
              </nav>

              {state.isLoaded && state.selectedYears.length > 0 && (
                <div className="h-10 w-px bg-slate-200 mx-2 hidden md:block"></div>
              )}

              {state.isLoaded && state.selectedYears.length > 0 && (
                <div className="flex items-center gap-3 bg-white px-4 py-1 rounded-2xl border border-slate-200 shadow-sm">
                  <span className="text-[9px] font-black text-slate-400 uppercase tracking-widest hidden sm:block">
                    Exercice
                  </span>
                  <select
                    value={state.currentYear}
                    onChange={(e) => setState((prev) => ({ ...prev, currentYear: parseInt(e.target.value) }))}
                    className="bg-transparent text-slate-900 text-xs font-black outline-none cursor-pointer py-1 pr-2"
                  >
                    {state.selectedYears.map((y) => (
                      <option key={y} value={y}>
                        {y}
                      </option>
                    ))}
                  </select>
                </div>
              )}
            </div>

            {/* Actions */}
            <div className="flex items-center gap-3 shrink-0">
              <button
                onClick={() => setShowSettings(true)}
                className="w-10 h-10 rounded-xl flex items-center justify-center transition-all text-slate-400 border border-slate-200 hover:bg-slate-50 hover:text-blue-600"
              >
                <i className="fa-solid fa-cog text-[12px]"></i>
              </button>
              <button
                onClick={() => setShowConsole(!showConsole)}
                className={`w-10 h-10 rounded-xl flex items-center justify-center transition-all ${
                  showConsole ? 'bg-slate-900 text-white' : 'text-slate-400 border border-slate-200 hover:bg-slate-50'
                }`}
              >
                <i className="fa-solid fa-terminal text-[12px]"></i>
              </button>
              <label className="cursor-pointer bg-blue-600 text-white px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 shadow-lg hover:bg-blue-700 transition-all">
                <i className="fa-solid fa-upload"></i> FEC
                <input type="file" className="hidden" onChange={handleFileUpload} accept=".fec,.txt" />
              </label>
              <button
                onClick={() => {
                  localStorage.clear();
                  window.location.reload();
                }}
                className="w-10 h-10 flex items-center justify-center text-slate-300 hover:text-red-500 transition-colors"
              >
                <i className="fa-solid fa-power-off"></i>
              </button>
            </div>
          </div>
        </header>

        {/* Main Content */}
        <main className="flex-1 p-6 md:p-10 max-w-7xl mx-auto w-full">
          {/* Console */}
          {showConsole && (
            <div className="fixed bottom-10 right-10 w-[450px] h-96 bg-slate-900/95 backdrop-blur-md rounded-3xl shadow-2xl border border-white/10 z-50 flex flex-col overflow-hidden">
              <div className="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-slate-800">
                <span className="text-[10px] text-slate-500 font-black uppercase">Console Audit</span>
                <button onClick={() => setShowConsole(false)} className="text-slate-500">
                  <i className="fa-solid fa-times"></i>
                </button>
              </div>
              <div className="flex-1 overflow-y-auto p-6 font-mono text-[10px] space-y-1">
                {state.logs.map((entry, idx) => (
                  <div key={idx} className="flex gap-3">
                    <span className="text-slate-600">[{entry.timestamp}]</span>
                    <span
                      className={
                        entry.level === 'error'
                          ? 'text-red-400'
                          : entry.level === 'warn'
                            ? 'text-amber-400'
                            : 'text-blue-300'
                      }
                    >
                      {entry.message}
                    </span>
                  </div>
                ))}
                <div ref={consoleEndRef}></div>
              </div>
            </div>
          )}

          {/* Empty State */}
          {!state.isLoaded && !loading && (
            <div className="h-[60vh] flex flex-col items-center justify-center">
              <div className="bg-white p-16 rounded-[4rem] border border-slate-200 shadow-xl text-center">
                <div className="w-24 h-24 bg-slate-900 rounded-3xl flex items-center justify-center mx-auto mb-10 text-white font-black text-3xl italic tracking-tighter shadow-2xl rotate-3">
                  ATCO
                </div>
                <h3 className="text-2xl font-black mb-12 uppercase tracking-tight text-slate-900">
                  AuditCompta Intelligence
                </h3>
                <label className="cursor-pointer bg-blue-600 text-white px-12 py-5 rounded-2xl font-black text-[11px] uppercase tracking-widest hover:bg-blue-700 transition-all shadow-xl block">
                  DÉMARRER UN AUDIT FEC
                  <input type="file" className="hidden" onChange={handleFileUpload} accept=".fec,.txt" />
                </label>
                <p className="mt-8 text-[9px] text-slate-400 font-bold uppercase tracking-[0.3em]">
                  Format supporté : Quadra / FEC Standard
                </p>
              </div>
            </div>
          )}

          {/* Loading */}
          {loading && (
            <div className="h-[60vh] flex flex-col items-center justify-center">
              <div className="w-16 h-16 border-[6px] border-blue-600 border-t-transparent rounded-full animate-spin mb-6"></div>
              <p className="text-slate-900 font-black text-[11px] tracking-[0.5em] uppercase">Audit en cours...</p>
            </div>
          )}

          {/* Dashboard */}
          {!loading && state.isLoaded && activeTab === 'dashboard' && (
            <DashboardView data={state.years[state.currentYear]} />
          )}

          {/* Comparison */}
          {!loading && state.isLoaded && activeTab === 'comparison' && <ComparisonView years={state.years} />}

          {/* AI Advisor */}
          {!loading && state.isLoaded && activeTab === 'advisor' && (
            <AIAdvisorView
              data={state.years[state.currentYear]}
              previousData={
                state.years[
                  Object.keys(state.years)
                    .map(Number)
                    .sort((a, b) => b - a)
                    .find((y) => y < state.currentYear) || 0
                ]
              }
              onOpenSettings={() => setShowSettings(true)}
              onAiResult={setAiResult}
              onCeoResult={setCeoResult}
            />
          )}
        </main>

        {/* Print Module */}
        {state.isLoaded && (
          <PrintableReport
            data={state.years[state.currentYear]}
            previousData={
              state.years[
                Object.keys(state.years)
                  .map(Number)
                  .sort((a, b) => b - a)
                  .find((y) => y < state.currentYear) || 0
              ]
            }
            aiResult={aiResult}
            ceoResult={ceoResult}
          />
        )}

        {/* Settings Dialog */}
        {showSettings && <SettingsDialog onClose={() => setShowSettings(false)} />}
      </div>
    );
  }

  // ==================== LOGIN VIEW ====================
  return (
    <div className="min-h-screen bg-slate-900 flex items-center justify-center p-6">
      <div className="bg-white p-10 rounded-[2.5rem] shadow-2xl w-full max-w-sm border border-slate-200">
        <div className="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-8 text-white shadow-xl font-black italic tracking-tighter">
          ATCO
        </div>
        <h1 className="text-xl font-black text-slate-900 mb-1 text-center uppercase tracking-tight">Accès Sécurisé</h1>
        <p className="text-slate-400 text-[9px] mb-10 text-center uppercase tracking-widest font-black">
          ATCO BI AuditCompta
        </p>
        <form onSubmit={handleLogin} className="space-y-6">
          <input
            type="password"
            placeholder="Code d'accès"
            className={`w-full bg-slate-50 border ${authError ? 'border-red-500' : 'border-slate-200'} rounded-2xl px-6 py-4 text-center text-2xl font-black tracking-widest`}
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            autoFocus={true}
            disabled={authLoading}
          />
          {authError && (
            <div className="text-red-600 text-[10px] font-black text-center">{authError}</div>
          )}
          <button
            type="submit"
            className="w-full bg-slate-900 text-white py-4 rounded-2xl font-black text-[11px] uppercase tracking-widest hover:bg-black transition-all shadow-lg"
          >
            {authLoading ? 'VÉRIFICATION...' : 'DÉVERROUILLER'}
          </button>
        </form>
        <p className="mt-6 text-center text-[8px] text-slate-300 uppercase font-bold tracking-widest">
          Accès réservé aux utilisateurs autorisés
        </p>
      </div>
    </div>
  );
};

export default App;
