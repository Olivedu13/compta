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
 * Génère le prompt d'audit financier complet — niveau Big Four
 */
const buildPrompt = (data, previousData) => {
  const fmt = (v) => (v != null ? Number(v).toLocaleString('fr-FR', { maximumFractionDigits: 0 }) : 'N/D');
  const pct = (v) => (v != null ? Number(v).toFixed(2) : 'N/D');
  const safe = (v) => (v != null && !isNaN(v) ? v : 0);

  // Évolutions N-1
  const evol = (n, n1) => {
    if (!n1 || n1 === 0) return 'N/A';
    return ((n - n1) / Math.abs(n1) * 100).toFixed(1) + '%';
  };

  const sig = data.sig || {};
  const r = data.ratios || {};
  const exp = data.expenseBreakdown || [];
  const expMap = {};
  exp.forEach(e => { expMap[e.label] = e.value; });

  // Bloc N-1 si disponible
  const n1 = previousData;
  const n1Bloc = n1 ? `
EXERCICE N-1 (${n1.year}) :
• CA: ${fmt(n1.revenue)}€ | EBE: ${fmt(n1.ebitda)}€ | RN: ${fmt(n1.netIncome)}€ | CAF: ${fmt(n1.caf)}€
• TN: ${fmt(n1.tn)}€ | BFR: ${fmt(n1.bfr)}€ | FRNG: ${fmt(n1.frng)}€

VARIATIONS N / N-1 :
• CA: ${evol(data.revenue, n1.revenue)} | EBE: ${evol(data.ebitda, n1.ebitda)} | RN: ${evol(data.netIncome, n1.netIncome)}
• CAF: ${evol(data.caf, n1.caf)} | BFR: ${evol(data.bfr, n1.bfr)} | TN: ${evol(data.tn, n1.tn)}
` : `EXERCICE N-1 : non disponible (1er exercice ou données absentes).`;

  return `RÔLE : Tu es un expert-comptable et analyste financier niveau "Big Four" (Deloitte/PwC/KPMG/EY).
Tu produis un diagnostic financier complet, rigoureux, factuel et directement exploitable.
Secteur analysé : commerce de détail — bijouterie/horlogerie/joaillerie.

══════════════════════════════════════════════
         DONNÉES FINANCIÈRES — EXERCICE ${data.year}
══════════════════════════════════════════════

─── SOLDES INTERMÉDIAIRES DE GESTION (SIG) ───
• Chiffre d'affaires (CA)        : ${fmt(data.revenue)}€
• Marge Commerciale              : ${fmt(sig.margeCommerciale)}€
• Production de l'exercice       : ${fmt(sig.productionExercice)}€
• Valeur Ajoutée (VA)            : ${fmt(sig.valeurAjoutee)}€
• EBE                            : ${fmt(data.ebitda)}€
• Résultat d'Exploitation (REX)  : ${fmt(sig.resultatExploitation)}€
• Résultat Financier             : ${fmt(sig.resultatFinancier)}€
• RCAI                           : ${fmt(sig.resultatCourant)}€
• Résultat Exceptionnel          : ${fmt(sig.resultatExceptionnel)}€
• Résultat Net (RN)              : ${fmt(data.netIncome)}€
• CAF                            : ${fmt(data.caf)}€

─── STRUCTURE FINANCIÈRE (BILAN) ───
ACTIF :
• Immobilisations nettes         : ${fmt(data.fixedAssets)}€
• Stocks                         : ${fmt(data.stocks)}€
• Créances clients               : ${fmt(data.receivables)}€
• Trésorerie active              : ${fmt(data.cashPositive)}€
• Total Actif                    : ${fmt(data.totalAssets)}€

PASSIF :
• Capitaux propres               : ${fmt(data.equity)}€
• Dettes financières             : ${fmt(data.debt)}€
• Dettes fournisseurs            : ${fmt(data.payables)}€
• Concours bancaires courants    : ${fmt(data.bankOverdraft)}€

─── ÉQUILIBRE FINANCIER ───
• FRNG (Fonds de Roulement)      : ${fmt(data.frng)}€
• BFR (Besoin en Fonds de Roul.) : ${fmt(data.bfr)}€  (${pct(safe(data.bfr) / safe(data.revenue) * 365)} jours de CA)
• Trésorerie Nette (TN)          : ${fmt(data.tn)}€

─── CYCLES D'EXPLOITATION ───
• DSO (délai clients)            : ${Math.round(safe(data.dso))} jours
• DPO (délai fournisseurs)       : ${Math.round(safe(data.dpo))} jours
• Rotation stocks                : ${pct(safe(data.inventoryTurnover))}x/an
• Cycle de conversion trésorerie : ${Math.round(safe(data.dso) + (safe(data.inventoryTurnover) > 0 ? 360/data.inventoryTurnover : 0) - safe(data.dpo))} jours

─── RATIOS FINANCIERS ───
• Marge nette (RN/CA)            : ${pct(safe(data.netIncome) / safe(data.revenue) * 100)}%
• Marge EBE (EBE/CA)             : ${pct(r.operatingMargin)}%
• CAF/CA                         : ${pct(r.cafOnRevenue)}%
• Liquidité générale             : ${pct(r.liquidityGeneral)}x
• Liquidité immédiate            : ${pct(r.liquidityImmediate)}x
• Solvabilité                    : ${pct(r.solvency)}x
• Autonomie financière           : ${pct(safe(r.financialAutonomy) * 100)}%
• Endettement net                : ${pct(r.debtRatio)}%
• Gearing (dette nette/CP)       : ${pct(r.gearing)}x
• ROE (retour sur CP)            : ${pct(r.roe)}%
• ROA (retour sur actifs)        : ${pct(r.roa)}%
• Couverture charges financières : ${pct(r.interestCoverage)}x
• Capacité de remboursement      : ${pct(r.repaymentCapacity)} années

─── DÉCOMPOSITION DES CHARGES ───
• Achats marchandises/MP         : ${fmt(expMap['Achats'])}€
• Services extérieurs            : ${fmt(expMap['Services'])}€
• Impôts et taxes                : ${fmt(expMap['Impôts'])}€
• Charges de personnel           : ${fmt(expMap['Personnel'])}€
• Dotations & gestion courante   : ${fmt(expMap['Gestion'])}€
• Charges financières            : ${fmt(expMap['Financier'])}€
• Total charges                  : ${fmt(data.totalCharges)}€

─── SEUIL DE RENTABILITÉ ───
• Seuil de rentabilité           : ${fmt(data.breakEvenPoint)}€
• Marge brute sur coûts variables: ${pct(data.marginRate)}%
• Score santé global             : ${safe(data.healthScore)}/100

${n1Bloc}

══════════════════════════════════════════════
         CONSIGNES D'ANALYSE
══════════════════════════════════════════════

Produis un **audit financier exécutif complet** en Markdown (1200-1800 mots).

STRUCTURE OBLIGATOIRE :

## 1. SYNTHÈSE EXÉCUTIVE
Note globale sur 100 avec grille : A (≥80) / B (60-79) / C (40-59) / D (<40).
Diagnostic 3-4 phrases : positionnement santé (Saine / À surveiller / Dégradée / Critique), dynamique vs N-1, enjeux stratégiques majeurs. Résumé SWOT ultra-condensé (2 forces, 2 faiblesses clés).

## 2. ANALYSE DE LA RENTABILITÉ & PERFORMANCE
- Cascade SIG complète : CA → Marge → VA → EBE → REX → RCAI → RN
- Taux de marge à chaque étage + benchmark bijouterie (marge commerciale >45%, EBE/CA 8-12%, RN/CA >3%)
- Si écart vs benchmark : quantifier en € et % et identifier les causes (achats/personnel/financier)
- Évolution N-1 si disponible : chiffrer précisément les écarts
- CAF et autofinancement : capacité à investir et rembourser ?

## 3. STRUCTURE FINANCIÈRE & ÉQUILIBRE
- Triangle FRNG / BFR / TN : cohérence et interprétation
- Si FRNG > 0 mais TN < 0 : piège classique — solide LT mais fragile CT
- Qualité du bilan : poids immo, stocks, créances vs CP
- Ratios de solvabilité et liquidité vs normes (liquidité >1.5, solvabilité >1.2, autonomie >30%)
- Endettement : gearing, couverture charges financières, capacité de remboursement

## 4. ANALYSE DU CYCLE D'EXPLOITATION
- DSO / DPO / rotation stocks : benchmark bijouterie (DSO 30-60j, stocks 4-6 rotations/an)
- Cycle de conversion de trésorerie : est-il optimal ?
- Poids des stocks dans l'actif : normal pour bijouterie (30-40% actif) ou excessif ?
- Corrélation BFR / CA : le BFR croît-il proportionnellement au CA ?

## 5. SIGNAUX D'ALERTE & RED FLAGS
Identifier et hiérarchiser (🔴 critique / 🟠 vigilance / 🟢 satisfaisant) :
- Marges érodées ou inversées
- BFR explosif vs CA
- TN négative persistante
- DSO anormalement élevé
- Stocks immobilisés excessifs
- Charges financières disproportionnées
- Incohérence CA↑ / RN↓

## 6. RECOMMANDATIONS OPÉRATIONNELLES PRIORISÉES
Pour chaque recommandation : impact estimé (€ ou %), difficulté de mise en œuvre, délai.

**🔥 URGENCES (0-3 mois)** : actions cash immédiates (recouvrement, DSO, affacturage, stocks morts)
**⚡ MOYEN TERME (3-6 mois)** : optimisation marge (mix produit, renégociation achats, réduction charges fixes)
**🎯 STRATÉGIQUE (6-12 mois)** : restructuration financière, investissements, financement alternatif

## 7. CONCLUSION & PERSPECTIVES
Résumé en 3 points clés. Projection tendancielle (si les tendances se maintiennent : scénario favorable/défavorable). Actions prioritaires top 3 pour le dirigeant.

STYLE IMPÉRATIF :
- Langage expert-comptable : ratios PCG, normes IFRS/PME, termes techniques précis
- Chaque affirmation justifiée par un chiffre ou un ratio
- Pas d'hypothèses non fondées — si une donnée manque, le signaler explicitement
- Ton : assertif, direct, professionnel, zéro alarmisme injustifié
- Benchmark systématique bijouterie/commerce de détail
- Markdown pur, pas de blocs de code
- Objectif : rapport présentable à un DAF, un banquier ou un investisseur
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
              content: 'Tu es un expert-comptable et analyste financier senior niveau Big Four (Deloitte/PwC/KPMG/EY), spécialisé dans le commerce de détail bijouterie/horlogerie. Tu produis des diagnostics financiers complets, factuels, chiffrés, avec benchmark sectoriel. Style : assertif, direct, professionnel. Format : Markdown structuré.',
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
