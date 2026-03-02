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

// Clés API stockées en mémoire (env > settings DB > saisie manuelle)
let apiKeys = {
  gemini: import.meta.env.VITE_GEMINI_API_KEY || '',
  copilot: import.meta.env.VITE_COPILOT_API_KEY || '',
};

export const setApiKeys = (keys) => {
  if (keys.gemini) apiKeys.gemini = keys.gemini;
  if (keys.copilot) apiKeys.copilot = keys.copilot;
};

/**
 * Génère le bloc de données financières (partagé entre les prompts)
 */
const buildDataBlock = (data, previousData) => {
  const fmt = (v) => (v != null ? Number(v).toLocaleString('fr-FR', { maximumFractionDigits: 0 }) : 'N/D');
  const pct = (v) => (v != null ? Number(v).toFixed(2) : 'N/D');
  const safe = (v) => (v != null && !isNaN(v) ? v : 0);

  const evol = (n, n1) => {
    if (!n1 || n1 === 0) return 'N/A';
    return ((n - n1) / Math.abs(n1) * 100).toFixed(1) + '%';
  };

  const sig = data.sig || {};
  const r = data.ratios || {};
  const exp = data.expenseBreakdown || [];
  const expMap = {};
  exp.forEach(e => { expMap[e.label] = e.value; });

  const details = data.details || {};
  const detailBlock = (label, items) => {
    if (!items || items.length === 0) return '';
    return items.slice(0, 10).map(i =>
      `  ${i.code} ${(i.libelle || '').padEnd(35).substring(0, 35)} : ${fmt(Math.abs(i.solde))}€`
    ).join('\n');
  };

  const n1 = previousData;
  const n1Bloc = n1 ? `
EXERCICE N-1 (${n1.year}) :
• CA: ${fmt(n1.revenue)}€ | EBE: ${fmt(n1.ebitda)}€ | RN: ${fmt(n1.netIncome)}€ | CAF: ${fmt(n1.caf)}€
• TN: ${fmt(n1.tn)}€ | BFR: ${fmt(n1.bfr)}€ | FRNG: ${fmt(n1.frng)}€

VARIATIONS N / N-1 :
• CA: ${evol(data.revenue, n1.revenue)} | EBE: ${evol(data.ebitda, n1.ebitda)} | RN: ${evol(data.netIncome, n1.netIncome)}
• CAF: ${evol(data.caf, n1.caf)} | BFR: ${evol(data.bfr, n1.bfr)} | TN: ${evol(data.tn, n1.tn)}
` : `EXERCICE N-1 : non disponible (1er exercice ou données absentes).`;

  return `
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
• Charges de personnel TOTAL     : ${fmt(expMap['Personnel'])}€
  └─ Masse salariale (salariés)  : ${fmt(data.masseSalariale || 0)}€
  └─ Rémunération dirigeant (TNS): ${fmt(data.remDirigeant || 0)}€  ⚠ NON-SALARIÉ (comptes 6442+646)
• Dotations & gestion courante   : ${fmt(expMap['Gestion'])}€
• Charges financières            : ${fmt(expMap['Financier'])}€
• Total charges                  : ${fmt(data.totalCharges)}€

⚠ NOTE IMPORTANTE : Les comptes 6442 (Rémunération Poquet T) et 646 (Charges exploitant)
sont la rémunération du dirigeant/gérant TNS (Thierry Poquet), PAS des salaires.
Ils ne doivent pas être analysés comme de la masse salariale.
Le dirigeant est un TNS (Travailleur Non Salarié). Effectif salarié réel = 10.
Le dirigeant est la 11ème personne. Cette distinction est essentielle pour :
le coût horaire moyen salarié, le benchmark masse salariale/CA, et les recommandations.

─── COÛT HORAIRE / MINUTE ───
• Effectif total                 : ${data.nbPersonnes || 11} personnes (10 salariés + 1 dirigeant TNS)
• Base heures / personne         : 1 607h légales
• Total heures                   : ${fmt(data.totalHeures)}h
• Coût horaire global            : ${(data.coutHoraire || 0).toFixed(2)}€/h
• Coût minute global             : ${(data.coutMinute || 0).toFixed(2)}€/min
• Coût horaire salariés seuls    : ${data.masseSalariale && data.totalHeures ? (data.masseSalariale / (1607 * 10)).toFixed(2) : 'N/D'}€/h
• Coût dirigeant / mois          : ${data.remDirigeant ? fmt(data.remDirigeant / 12) : 'N/D'}€

─── DÉTAIL PAR POSTE COMPTABLE (Top comptes par montant) ───

ACHATS & MATIÈRES (classe 60) :
${detailBlock('Achats', details.purchases)}

SERVICES EXTÉRIEURS (classes 61-62) :
${detailBlock('Services', details.external)}

CHARGES DE PERSONNEL (classe 64) :
${detailBlock('Personnel', details.personnel)}

CHARGES FINANCIÈRES & BANCAIRES (classes 66 + 627) :
${detailBlock('Financier', details.debt)}

IMPÔTS & TAXES (classe 63) :
${detailBlock('Taxes', details.taxes)}

GESTION COURANTE & AMORT. (classes 65 + 68) :
${detailBlock('Gestion', details.management)}

─── SEUIL DE RENTABILITÉ ───
• Seuil de rentabilité           : ${fmt(data.breakEvenPoint)}€
• Marge brute sur coûts variables: ${pct(data.marginRate)}%
• Score santé global             : ${safe(data.healthScore)}/100

${n1Bloc}`;
};

/**
 * Génère le prompt d'audit financier complet — niveau Big Four
 */
const buildPrompt = (data, previousData) => {
  return `RÔLE : Tu es un expert-comptable et analyste financier niveau "Big Four" (Deloitte/PwC/KPMG/EY).
Tu produis un diagnostic financier complet, rigoureux, factuel et directement exploitable.
Secteur analysé : commerce de détail — bijouterie/horlogerie/joaillerie.
${buildDataBlock(data, previousData)}

══════════════════════════════════════════════
         CONSIGNES D'ANALYSE
══════════════════════════════════════════════

Produis un **audit financier exécutif complet** en Markdown (1500-2500 mots).

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

## 6. PRÉCONISATIONS — POSTES À SUPPRIMER / RÉDUIRE / SURVEILLER
À partir du détail par poste comptable fourni ci-dessus, classer chaque poste significatif dans l'une de ces catégories :

**🔴 À SUPPRIMER ou RÉDUIRE drastiquement** : postes non essentiels, doublons, montants disproportionnés par rapport au CA. Chiffrer l'économie potentielle en €.
**🟠 À SURVEILLER / RENÉGOCIER** : postes dont le niveau est élevé vs benchmark bijouterie. Proposer un objectif réaliste.
**🟢 CONFORME** : postes dans les normes du secteur.

Exemples d'analyses attendues :
- Frais bancaires : comparer le total frais bancaires/financiers au CA (norme < 1.5%)
- Véhicules (leasing + carburant + péage) : est-ce proportionné à l'activité ?
- Titres restaurant / primes : vérifier s'il y a des doublons comptables
- Services extérieurs : honoraires, UPS, télécom — benchmark
- Cadeaux clients, amendes : pertinence
Fournir un tableau récapitulatif avec : Poste, Montant, % CA, Verdict (🔴/🟠/🟢), Économie potentielle.

## 7. RECOMMANDATIONS OPÉRATIONNELLES PRIORISÉES
Pour chaque recommandation : impact estimé (€ ou %), difficulté de mise en œuvre, délai.

**🔥 URGENCES (0-3 mois)** : actions cash immédiates (recouvrement, DSO, affacturage, stocks morts)
**⚡ MOYEN TERME (3-6 mois)** : optimisation marge (mix produit, renégociation achats, réduction charges fixes)
**🎯 STRATÉGIQUE (6-12 mois)** : restructuration financière, investissements, financement alternatif

## 8. CONCLUSION & PERSPECTIVES
Résumé en 3 points clés. Projection tendancielle (si les tendances se maintiennent : scénario favorable/défavorable). Actions prioritaires top 3 pour le dirigeant.

POINT CLÉ RÉMUNÉRATION DIRIGEANT :
Les comptes 6442 (Rémunération Poquet T) et 646 (Charges exploitant) ne sont PAS
des salaires mais la rémunération du dirigeant TNS (Thierry Poquet, gérant).
Dans ton analyse :
- Sépare TOUJOURS "masse salariale" (10 salariés) et "rémunération dirigeant" (1 TNS)
- Le benchmark masse salariale/CA doit s'appliquer aux salariés UNIQUEMENT
- La rém. dirigeant est un prélèvement sur résultat, pas une charge salariale classique
- Analyse si la rém. dirigeant est cohérente : comparer au RN, au CA, et au marché
- Un dirigeant TNS de bijouterie en PACA avec ~2M€ CA : rém. ~70-100k€ = normal

STYLE IMPÉRATIF :
- Langage expert-comptable : ratios PCG, normes IFRS/PME, termes techniques précis
- Chaque affirmation justifiée par un chiffre ou un ratio
- Pas d'hypothèses non fondées — si une donnée manque, le signaler explicitement
- Ton : assertif, direct, professionnel, zéro alarmisme injustifié
- Benchmark systématique bijouterie/commerce de détail
- Markdown pur, pas de blocs de code
- Ne JAMAIS ajouter de signature, date, nom de cabinet, ou formule de politesse à la fin
- Objectif : rapport présentable à un DAF, un banquier ou un investisseur
`;
};

/**
 * Génère le prompt "CEO Visionnaire" — regard de chef d'entreprise à succès
 */
const buildCeoPrompt = (data, previousData) => {
  return `RÔLE : Tu es un chef d'entreprise chevronné et visionnaire, un serial entrepreneur à succès.
Tu as bâti et revendu plusieurs entreprises profitables. Tu penses comme Bernard Arnault, Steve Jobs ou Elon Musk appliqué au commerce de détail luxe.
Tu ne parles PAS comme un comptable. Tu parles comme un patron qui a de l'instinct, de l'expérience, et qui sait transformer une boîte qui stagne en machine à cash.
Tu tutoies le dirigeant. Tu es direct, cash, parfois provocateur mais toujours bienveillant.
Secteur : bijouterie/horlogerie/joaillerie — commerce de détail avec atelier.
${buildDataBlock(data, previousData)}

══════════════════════════════════════════════
         CONSIGNES — VISION CEO
══════════════════════════════════════════════

Produis un **rapport stratégique de dirigeant** en Markdown (1500-2500 mots).
Tu t'adresses directement au patron (Thierry). Tu parles d'homme à homme, de patron à patron.

STRUCTURE OBLIGATOIRE :

## 🎯 MON VERDICT EN 30 SECONDES
En 3-4 phrases percutantes, dis ce que tu penses vraiment de cette boîte. Pas de langue de bois.
Donne une note /10 avec ton ressenti de patron. Est-ce que tu rachèterais cette boîte ? Pourquoi ?

## 💰 OÙ EST L'ARGENT QUI FUIT ?
Analyse chaque ligne de dépense comme si c'était TON argent.
- Identifie les postes où tu vois du gaspillage, du "confort" ou de l'habitude
- Chiffre exactement combien tu économiserais sur chaque poste
- Sois concret : "3 banques pour 2M€ de CA, c'est du délire. J'en garde une, point."
- Tableau avec : Poste, Ce que tu dépenses, Ce qu'un patron malin dépenserait, Économie

## 🔪 CE QUE JE COUPE DÈS LUNDI MATIN
Les 5 décisions que tu prendrais dès la première semaine si tu rachetais cette boîte.
Pour chaque décision : l'action, le montant économisé, et pourquoi c'est non négociable.
Sois radical mais réaliste.

## 📈 COMMENT JE DOUBLE LE RÉSULTAT EN 12 MOIS
Plan d'action concret en 3 phases :
- **Mois 1-3 — SURVIE** : couper le gras, sécuriser la tréso, renégocier tout
- **Mois 4-6 — OPTIMISATION** : augmenter le panier moyen, le mix produit, la marge
- **Mois 7-12 — CROISSANCE** : nouveaux canaux (e-commerce, réseaux sociaux, événements VIP), fidélisation, montée en gamme

## 🏆 LE BIJOUTIER QUI GAGNE VS CELUI QUI SURVIT
Comparaison entre "ce que fait cette entreprise" et "ce que ferait un bijoutier au top".
Benchmark concret sur : marge, digital, expérience client, gestion des stocks, sourcing.

## 💡 MES 3 IDÉES "OUT OF THE BOX"
3 idées non conventionnelles pour transformer cette bijouterie :
- Des idées que l'expert-comptable ne donnerait jamais
- Inspirées de ce qui marche dans d'autres secteurs (tech, luxe, retail innovant)
- Chiffre l'impact potentiel de chaque idée

## ⚡ LETTRE AU DIRIGEANT
Termine par une lettre personnelle de 10-15 lignes, comme un mentor.
Dis-lui ce qu'il fait bien, ce qu'il doit changer, et donne-lui la motivation pour agir.
Ton : direct, inspirant, sans condescendance. Tu parles d'égal à égal.

STYLE IMPÉRATIF :
- Tutoiement obligatoire
- Langage de patron : "cash", "marge", "levier", "scale", pas de jargon comptable inutile
- Chaque affirmation chiffrée avec les données fournies
- Exemples concrets de ce que tu ferais toi, pas des recommandations vagues
- Tu peux être provocateur ("85k€ de frais bancaires ? Tu finances la retraite de ton banquier ?")
- Markdown pur, pas de blocs de code
- Utilise des émojis pour les titres uniquement
- Ne JAMAIS ajouter de signature, date, ou formule de politesse à la fin
- Ton objectif : que le dirigeant referme ce rapport avec 5 actions claires et l'envie de les exécuter dès demain

POINT CLÉ : les comptes 6442 (Rémunération Poquet T) et 646 (Charges exploitant)
sont la rémunération de Thierry (le patron, TNS/gérant), PAS des salaires.
Quand tu parles de "charges de personnel", distingue toujours :
- La masse salariale des 10 salariés
- SA rémunération de dirigeant
Sa rém. n'est pas une charge à "couper" — c'est son revenu de patron. Analyse-la
comme un prélèvement entrepreneurial. Tu peux challenger son niveau (est-ce qu'il
se paye assez ? trop ? par rapport au CA et au résultat ?) mais ne la mets JAMAIS
dans le même sac que les salaires.
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

  // Détection clé révoquée / invalide
  const errStr = (geminiError || '') + (copilotError || '');
  if (errStr.includes('leaked') || errStr.includes('API_KEY_INVALID') || errStr.includes('invalid')) {
    return {
      text: `## ⚠️ Clé API Gemini Révoquée

Votre clé API a été **invalidée par Google** (signalée comme fuitée car présente sur un dépôt public GitHub).

### Comment corriger :
1. Allez sur **[Google AI Studio](https://aistudio.google.com/app/apikey)**
2. Supprimez l'ancienne clé
3. Cliquez **"Create API Key"** pour en créer une nouvelle
4. Collez-la dans les **Paramètres** (⚙️) de cette application

> ⏱️ Cela prend 30 secondes. La clé est gratuite (1500 requêtes/jour).`,
      modelUsed: 'Clé API invalide',
    };
  }

  const errorMsg = copilotError || geminiError || 'IA_FAILED';
  throw new Error(errorMsg);
};

/**
 * Analyse IA en mode CEO Visionnaire
 */
export const analyzeWithCEO = async (data, previousData) => {
  const prompt = buildCeoPrompt(data, previousData);
  let geminiError, copilotError;

  try {
    const result = await tryGemini(prompt);
    if (result) return { ...result, modelUsed: `${result.modelUsed} — Vision CEO` };
  } catch (err) {
    geminiError = err?.message;
  }

  try {
    const result = await tryCopilot(prompt);
    if (result) return { ...result, modelUsed: `${result.modelUsed} — Vision CEO` };
  } catch (err) {
    copilotError = err?.message;
  }

  if (!apiKeys.gemini && !apiKeys.copilot) return manualFallback();

  const errStr = (geminiError || '') + (copilotError || '');
  if (errStr.includes('leaked') || errStr.includes('API_KEY_INVALID') || errStr.includes('invalid')) {
    return { text: '## ⚠️ Clé API invalide\nVeuillez reconfigurer votre clé Gemini dans les Paramètres.', modelUsed: 'Clé API invalide' };
  }

  throw new Error(copilotError || geminiError || 'IA_FAILED');
};
