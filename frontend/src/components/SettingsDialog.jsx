import React, { useState, useEffect } from 'react';

/**
 * SettingsDialog - Dialogue de configuration des clés API
 */
const SettingsDialog = ({ onClose }) => {
  const [geminiKey, setGeminiKey] = useState('');
  const [copilotKey, setCopilotKey] = useState('');
  const [saving, setSaving] = useState(false);
  const [saved, setSaved] = useState(false);
  const API_URL = './api.php';

  useEffect(() => {
    loadKeys();
  }, []);

  const loadKeys = async () => {
    try {
      const res = await fetch(API_URL);
      if (res.ok) {
        const data = await res.json();
        if (data.settings) {
          setGeminiKey(data.settings.api_key_gemini || '');
          setCopilotKey(data.settings.api_key_copilot || '');
        }
      }
    } catch (err) {
      console.error('Erreur chargement clés', err);
    }
  };

  const saveKeys = async () => {
    setSaving(true);
    try {
      const res = await fetch(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ api_keys: { gemini: geminiKey, copilot: copilotKey } }),
      });
      if (res.ok) {
        setSaved(true);
        setTimeout(() => window.location.reload(), 1000);
      }
    } catch {
      alert('Erreur lors de la sauvegarde');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div className="bg-white rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="p-8 border-b border-slate-200 flex items-center justify-between sticky top-0 bg-white">
          <div className="flex items-center gap-4">
            <div className="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center text-white">
              <i className="fa-solid fa-key"></i>
            </div>
            <div>
              <h2 className="text-xl font-black text-slate-900">Clés API IA</h2>
              <p className="text-xs text-slate-500">Configuration des services d&apos;analyse</p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="w-10 h-10 flex items-center justify-center text-slate-400 hover:text-slate-900 transition-colors"
          >
            <i className="fa-solid fa-times"></i>
          </button>
        </div>

        {/* Body */}
        <div className="p-8 space-y-6">
          {/* Gemini */}
          <div className="bg-slate-50 rounded-2xl p-6">
            <div className="flex items-center gap-3 mb-4">
              <div className="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white text-xs font-bold">
                G
              </div>
              <div>
                <h3 className="font-bold text-slate-900">Google Gemini</h3>
                <p className="text-xs text-slate-500">Recommandé - Gratuit jusqu&apos;à 1500 requêtes/jour</p>
              </div>
            </div>
            <input
              type="password"
              value={geminiKey}
              onChange={(e) => setGeminiKey(e.target.value)}
              placeholder="AIzaSy..."
              className="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 outline-none transition-all font-mono text-sm"
            />
            <div className="mt-3 space-y-2">
              <p className="text-xs text-slate-600 font-medium">📌 Comment obtenir votre clé :</p>
              <ol className="text-xs text-slate-600 space-y-1 ml-4">
                <li>
                  1. Allez sur{' '}
                  <a href="https://aistudio.google.com/app/apikey" target="_blank" className="text-blue-600 hover:underline">
                    aistudio.google.com/app/apikey
                  </a>
                </li>
                <li>2. Connectez-vous avec votre compte Google</li>
                <li>3. Cliquez sur &quot;Create API Key&quot;</li>
                <li>4. Copiez la clé et collez-la ci-dessus</li>
              </ol>
            </div>
          </div>

          {/* Copilot */}
          <div className="bg-slate-50 rounded-2xl p-6">
            <div className="flex items-center gap-3 mb-4">
              <div className="w-8 h-8 bg-slate-900 rounded-lg flex items-center justify-center text-white text-xs">
                <i className="fa-brands fa-github"></i>
              </div>
              <div>
                <h3 className="font-bold text-slate-900">GitHub Copilot</h3>
                <p className="text-xs text-slate-500">Optionnel - Nécessite un abonnement</p>
              </div>
            </div>
            <input
              type="password"
              value={copilotKey}
              onChange={(e) => setCopilotKey(e.target.value)}
              placeholder="ghp_..."
              className="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 outline-none transition-all font-mono text-sm"
            />
            <div className="mt-3 space-y-2">
              <p className="text-xs text-slate-600 font-medium">📌 Comment obtenir votre token :</p>
              <ol className="text-xs text-slate-600 space-y-1 ml-4">
                <li>
                  1. Allez sur{' '}
                  <a href="https://github.com/settings/tokens" target="_blank" className="text-blue-600 hover:underline">
                    github.com/settings/tokens
                  </a>
                </li>
                <li>2. Générez un nouveau token (Classic)</li>
                <li>3. Cochez les permissions nécessaires</li>
                <li>4. Copiez le token et collez-le ci-dessus</li>
              </ol>
            </div>
          </div>

          {/* Info */}
          <div className="bg-blue-50 border border-blue-200 rounded-xl p-4">
            <div className="flex gap-3">
              <i className="fa-solid fa-info-circle text-blue-600 mt-0.5"></i>
              <div className="text-xs text-blue-900">
                <p className="font-bold mb-1">🎯 Ordre de priorité :</p>
                <p>1. Gemini (meilleure qualité, gratuit)</p>
                <p>2. Copilot (si quota Gemini dépassé)</p>
                <p>3. Mode manuel (si aucune clé configurée)</p>
              </div>
            </div>
          </div>

          {/* Save */}
          <button
            onClick={saveKeys}
            disabled={saving || (!geminiKey && !copilotKey)}
            className="w-full bg-blue-600 text-white py-4 rounded-xl font-bold text-sm uppercase tracking-wider hover:bg-blue-700 disabled:bg-slate-300 disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2"
          >
            {saving ? (
              <>
                <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                Sauvegarde...
              </>
            ) : saved ? (
              <>
                <i className="fa-solid fa-check"></i>
                Sauvegardé !
              </>
            ) : (
              <>
                <i className="fa-solid fa-save"></i>
                Sauvegarder et Recharger
              </>
            )}
          </button>
        </div>
      </div>
    </div>
  );
};

export default SettingsDialog;
