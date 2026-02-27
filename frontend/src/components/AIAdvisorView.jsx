import React, { useState, useEffect } from 'react';
import { analyzeWithAI } from '../lib/aiService';
import ExecSummaryDashboard from './ExecSummaryDashboard';

/**
 * AIAdvisorView - Vue d'audit IA Stratégique (Gemini / Copilot)
 */
const AIAdvisorView = ({ data, previousData, onOpenSettings }) => {
  const [result, setResult] = useState(null);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('summary');

  const runAnalysis = async () => {
    setLoading(true);
    try {
      const res = await analyzeWithAI(data, previousData);
      setResult(res);
    } catch {
      setResult({
        text: `## Erreur\n\nUne erreur est survenue lors de l'analyse.`,
        modelUsed: 'Erreur',
      });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    runAnalysis();
  }, [data, previousData]);

  /**
   * Renders simple markdown to React elements
   */
  const renderMarkdown = (text) =>
    text.split('\n').map((line, idx) => {
      if (line.startsWith('# '))
        return (
          <h1 key={idx} className="text-2xl font-bold text-slate-900 mb-6 border-b border-slate-100 pb-2 uppercase">
            {line.substring(2)}
          </h1>
        );
      if (line.startsWith('## '))
        return (
          <h2 key={idx} className="text-lg font-bold text-blue-600 mt-8 mb-4 uppercase flex items-center gap-2">
            {line.substring(3)}
          </h2>
        );
      if (line.startsWith('### '))
        return (
          <h3 key={idx} className="text-base font-bold text-slate-700 mt-6 mb-3">
            {line.substring(4)}
          </h3>
        );
      if (line.match(/^\d\./))
        return (
          <div key={idx} className="font-bold text-slate-900 mt-4 mb-2">
            {line}
          </div>
        );
      if (line.trim().startsWith('-'))
        return (
          <div key={idx} className="ml-4 mb-2 flex gap-3 text-slate-600">
            <i className="fa-solid fa-circle text-[6px] mt-2 opacity-30"></i>{' '}
            <span>{line.substring(1).trim()}</span>
          </div>
        );
      if (line.includes('http')) {
        const urlRegex = /(https?:\/\/[^\s]+)/g;
        const parts = line.split(urlRegex);
        return (
          <p key={idx} className="mb-4">
            {parts.map((part, i) =>
              urlRegex.test(part) ? (
                <a key={i} href={part} target="_blank" className="text-blue-600 hover:underline">
                  {part}
                </a>
              ) : (
                part
              ),
            )}
          </p>
        );
      }
      return (
        <p key={idx} className="mb-4">
          {line}
        </p>
      );
    });

  return (
    <div className="max-w-4xl mx-auto space-y-6 animate-in fade-in duration-500">
      {/* Header + Tabs */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="bg-slate-50 p-6 border-b border-slate-100">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-4">
              <div className="bg-blue-600 w-10 h-10 rounded-xl flex items-center justify-center text-white">
                <i className="fa-solid fa-wand-magic-sparkles"></i>
              </div>
              <div>
                <h2 className="font-bold text-slate-900">Audit IA Stratégique</h2>
                <p className="text-[10px] text-slate-400 font-bold uppercase tracking-widest">
                  {result ? `Analysé par ${result.modelUsed}` : 'Intelligence Artificielle'}
                </p>
              </div>
            </div>
            {!loading && (
              <button onClick={runAnalysis} className="text-slate-400 hover:text-blue-600 transition-colors">
                <i className="fa-solid fa-rotate"></i>
              </button>
            )}
          </div>
          {/* Onglets */}
          <div className="flex gap-1 bg-slate-200/60 rounded-xl p-1">
            <button
              onClick={() => setActiveTab('summary')}
              className={`flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-xs font-bold transition-all ${
                activeTab === 'summary'
                  ? 'bg-white text-blue-600 shadow-sm'
                  : 'text-slate-500 hover:text-slate-700'
              }`}
            >
              <i className="fa-solid fa-gauge-high"></i>
              Synthèse Dirigeant
            </button>
            <button
              onClick={() => setActiveTab('report')}
              className={`flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-xs font-bold transition-all ${
                activeTab === 'report'
                  ? 'bg-white text-blue-600 shadow-sm'
                  : 'text-slate-500 hover:text-slate-700'
              }`}
            >
              <i className="fa-solid fa-file-lines"></i>
              Rapport Complet IA
            </button>
          </div>
        </div>

        {/* Content */}
        <div className="p-8 min-h-[400px]">
          {activeTab === 'summary' ? (
            <ExecSummaryDashboard data={data} previousData={previousData} />
          ) : loading ? (
            <div className="flex flex-col items-center justify-center py-20">
              <div className="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mb-4"></div>
              <p className="text-slate-500 text-sm font-medium">L&apos;IA analyse vos données...</p>
            </div>
          ) : (
            <div className="prose prose-slate max-w-none prose-sm">
              <div className="whitespace-pre-wrap text-slate-700 leading-relaxed font-medium">
                {renderMarkdown(result?.text || '')}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default AIAdvisorView;
