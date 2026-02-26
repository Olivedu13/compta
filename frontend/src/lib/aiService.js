/**
 * aiService - Gestion des appels IA (Gemini, Copilot, fallback)
 * Stocke les clés API et génère les audits financiers
 */
import { GoogleGenAI } from '@google/genai';

// Modèles disponibles
const GEMINI_MODELS = [
  { name: 'models/gemini-3-pro-preview', displayName: 'Gemini 3 Pro Preview' },
  { name: 'models/gemini-3-flash-preview', displayName: 'Gemini 3 Flash Preview' },
  { name: 'models/gemini-2.5-pro', displayName: 'Gemini 2.5 Pro' },
  { name: 'models/gemini-2.5-flash', displayName: 'Gemini 2.5 Flash' },
  { name: 'models/gemini-2.0-flash', displayName: 'Gemini 2.0 Flash' },
  { name: 'models/gemini-flash-latest', displayName: 'Gemini Flash Latest' },
];

const COPILOT_MODELS = [
  { name: 'gpt-4o', displayName: 'GitHub Copilot (GPT-4o)' },
  { name: 'gpt-4o-mini', displayName: 'GitHub Copilot (GPT-4o-mini)' },
];

// Clés API stockées en mémoire
let apiKeys = { gemini: '', copilot: '' };

export const setApiKeys = (keys) => {
  if (keys.gemini) apiKeys.gemini = keys.gemini;
  if (keys.copilot) apiKeys.copilot = keys.copilot;
};

/**
 * Génère le prompt d'audit financier
 */
const buildPrompt = (data, previousData) => {
  const caEvol = previousData
    ? ((data.revenue - previousData.revenue) / previousData.revenue * 100).toFixed(1)
    : 'N/A';
  const rnEvol = previousData
    ? ((data.netIncome - previousData.netIncome) / previousData.netIncome * 100).toFixed(1)
    : 'N/A';

  return `
RÔLE : Tu es un expert-comptable qui produit des diagnostics rigoureux avec une vision DAF et des insights BI.

DONNÉES - EXERCICE ${data.year} :
• CA: ${data.revenue.toLocaleString()}€ | EBE: ${data.ebitda.toLocaleString()}€ | RN: ${data.netIncome.toLocaleString()}€ | CAF: ${data.caf.toLocaleString()}€
• TN: ${data.tn.toLocaleString()}€ | BFR: ${data.bfr.toLocaleString()}€ | FRNG: ${data.frng.toLocaleString()}€
• DSO: ${Math.round(data.dso)}j | Marge nette: ${data.marginRate.toFixed(2)}% | EBE/CA: ${(data.ebitda / data.revenue * 100).toFixed(2)}%
${previousData ? `N-1 BASE : CA ${previousData.revenue.toLocaleString()}€ | RN ${previousData.netIncome.toLocaleString()}€
VARIATION : CA ${caEvol}% | RN ${rnEvol}%` : 'N-1: non disponible (1er exercice)'}

PRODUCTION ATTENDUE : Audit financier exécutif professionnel (Markdown, 900-1200 mots).

STRUCTURE OBLIGATOIRE :

## SYNTHÈSE ÉCLAIR
Diagnostic 2-3 phrases : positionnement (Saine / À surveiller / Dégradée / Critique), dynamique vs N-1, premiers enjeux stratégiques.

## ANALYSE DE RENTABILITÉ
- Marge nette : benchmark (sain >5%, alerte 2-5%, critique <2%), évolution, causes (prix/coûts).
- EBE/CA : qualité exploitation avant financier & fiscal.
- Interprétation N-1 : écarts en € et %, les chiffrer précisément.
- Si CA > mais RN < : analyser charges fixes, impacts, structure coûts.

## ÉQUILIBRE FINANCIER & TRÉSORERIE
- Triangle TN/BFR/FRNG : logique, cohérence, cycles.
- Si TN < BFR : danger court terme, besoin financement.
- Si FRNG > 0 mais TN < 0 : piège : solide LT, fragile CT.
- DSO : normal ? Élevé ? Corrélation CA/BFR logique ?
- CAF/RN : autonomie autofinancement.

## SIGNAUX D'ALERTE & ANOMALIES
Identifier et hiérarchiser :
- Incohérences (CA↑ mais RN↓ brutalement).
- Ratios aberrants (EBE négatif, BFR explosif, DSO >100j).
- Structures fragiles (marge <2%, EBE insuffisant, TN très négatif).
- Ruptures (N-1/N dégradation acérée).

## FACTEURS DE RISQUE (top 5)
1. Risque trésorerie CT (TN faible/négatif, BFR croissant, DSO élevé).
2. Risque rentabilité (marges érodées, EBE faible, charges fixes non couverts).
3. Risque opérationnel (mauvais payeurs ? stocks incontrôlés ?).
4. Risque croissance (CA monte / TN baisse = endettement croissant implicite).
5. Dépendance autofinancement (CAF faible, latitude limitée).

## RECOMMANDATIONS OPÉRATIONNELLES (priorisées)
**Court terme (0-3 mois)** : accélérer recouvrement clients, réduire stocks, optimiser DSO. Impact: trésorerie immédiate (+€ directs).
**Moyen terme (1-6 mois)** : optimiser marge (réduction coûts variables, prix/mix produits), absorber charges fixes par croissance.
**Long terme (3-12 mois)** : restructurer charges fixes, financement alternatif (crédit, affacturage, délais fournisseurs).
**Gouvernance** : mettre en place suivi mensuel KPIs (CA, EBE, TN, DSO, BFR, stock days).

Pour chaque axe : impact estimé (€ ou %), difficulté (faible/moyenne/forte), délai réaliste.

STYLE :
- Langage CAC/expert-comptable : ratios, normes comptables, termes précis.
- Pas d'hypothèses non fondées (si inconnu, le dire).
- Pas de redondance inutile (synthétiser, chiffrer).
- Ton : assertif, direct, légèrement sec, zéro alarmisme.
- Markdown seul, pas de code.
- Objectif final : rapport qu'un DAF ou banquier lit avant RDV critique.
`;
};

/**
 * Tente l'analyse via Google Gemini
 */
const tryGemini = async (prompt) => {
  if (!apiKeys.gemini) return null;

  const genai = new GoogleGenAI({ apiKey: apiKeys.gemini });
  const errors = [];

  for (const model of GEMINI_MODELS) {
    try {
      const response = await genai.models.generateContent({
        model: model.name,
        contents: [{ role: 'user', parts: [{ text: prompt }] }],
        config: { temperature: 0.7 },
      });
      const text = response.text;
      if (text) return { text, modelUsed: model.displayName };
      errors.push(`${model.displayName}: réponse vide`);
    } catch (err) {
      const msg = err?.message || 'erreur inconnue';
      const status = err?.status || err?.code || '';
      if (msg.includes('NOT_FOUND') || status === 404 || status === '404') {
        // Model not available, skip
      } else {
        errors.push(`${model.displayName}: ${msg}`);
        if (msg.includes('429') || msg.includes('Quota') || msg.includes('RESOURCE_EXHAUSTED') || status === 429 || status === '429') {
          continue;
        }
      }
    }
  }

  if (errors.length) throw new Error(`GEMINI_FAILED: ${errors.join(' | ')}`);
  return null;
};

/**
 * Tente l'analyse via GitHub Copilot
 */
const tryCopilot = async (prompt) => {
  if (!apiKeys.copilot) return null;

  const errors = [];
  const url = 'https://models.inference.ai.azure.com/chat/completions';

  for (const model of COPILOT_MODELS) {
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${apiKeys.copilot}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          model: model.name,
          messages: [
            {
              role: 'system',
              content: 'Tu es un expert-comptable senior. Produis des diagnostics financiers professionnels, concis, assertifs, sans alarmisme.',
            },
            { role: 'user', content: prompt },
          ],
          temperature: 0.7,
        }),
      });

      if (res.ok) {
        const content = (await res.json()).choices?.[0]?.message?.content;
        if (content) return { text: content, modelUsed: model.displayName };
        errors.push(`${model.displayName}: réponse vide`);
      } else {
        await res.json().catch(() => ({}));
        errors.push(`${model.displayName}: HTTP ${res.status}`);
      }
    } catch (err) {
      errors.push(`${model.displayName}: ${err?.message || 'erreur inconnue'}`);
      continue;
    }
  }

  if (errors.length) throw new Error(`COPILOT_FAILED: ${errors.join(' | ')}`);
  return null;
};

/**
 * Fallback quand aucune API n'est configurée
 */
const manualFallback = async () => ({
  text: `## Configuration requise

Les services d'IA ne sont pas activés. Pour générer un audit professionnel :

### 1. Ajoutez votre clé API Google Gemini
- Allez sur : https://aistudio.google.com/app/apikey
- Créez une clé API (gratuit jusqu'à 1500 requêtes/jour)
- Paramètres app (⚙️) → Collez la clé

### 2. (Optionnel) GitHub Copilot
- Générez un token: https://github.com/settings/tokens
- Nécessite un abonnement Copilot actif

### Diagnostic manuel des chiffres clés
- Performance acceptable si Marge nette > 5% et TN > 0
- Attention si BFR > TN (risque trésorerie)
- Vérifiez cohérence CA vs EBE vs RN (pas de chute inexpliquée)

Configurez une clé pour un rapport complet.`,
  modelUsed: 'Mode Manuel (API non configurée)',
});

/**
 * Fonction principale : lance l'analyse IA avec fallback en cascade
 */
export const analyzeWithAI = async (data, previousData) => {
  const prompt = buildPrompt(data, previousData);
  let geminiError, copilotError;

  try {
    const result = await tryGemini(prompt);
    if (result) return result;
  } catch (err) {
    geminiError = err?.message;
  }

  try {
    const result = await tryCopilot(prompt);
    if (result) return result;
  } catch (err) {
    copilotError = err?.message;
  }

  if (!apiKeys.gemini && !apiKeys.copilot) return manualFallback();

  const errorMsg = copilotError || geminiError || 'IA_FAILED';
  throw new Error(errorMsg);
};
