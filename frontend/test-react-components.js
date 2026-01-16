/**
 * TEST COMPOSANTS REACT - Vérifier formatage données
 * Teste AnalyticsRevenueCharts avec données réelles
 */

// Simuler les données de l'API
const mockApiResponse = {
  success: true,
  data: {
    exercice: 2024,
    stats_globales: {
      total_operations: 16,
      ca_brut: 0,
    },
    evolution_mensuelle: [
      { mois: "2024-01", debit: 17000, credit: 17000, operations: 6 },
      { mois: "2024-02", debit: 15000, credit: 15000, operations: 6 },
      { mois: "2024-03", debit: 3000, credit: 3000, operations: 4 }
    ]
  }
};

console.log("═══════════════════════════════════════════════════════════════");
console.log("TEST COMPOSANTS REACT - DONNÉES API vs AFFICHAGE");
console.log("═══════════════════════════════════════════════════════════════\n");

// Simuler la transformation dans AdvancedAnalytics.jsx
console.log("1️⃣  TRANSFORMATION DONNÉES (AdvancedAnalytics.jsx)");
console.log("─────────────────────────────────────────────────────────────");

const analytics = mockApiResponse.data;
const evolution_mensuelle = analytics.evolution_mensuelle || [];

// ✅ CORRECTION: Utiliser les bonnes clés
const caData = {
  total: analytics.stats_globales?.ca_brut || 0,
  mensuel: evolution_mensuelle.map(m => ({ 
    mois: m.mois, 
    ca: m.debit || 0  
  })) || [],
  trimestriel: []
};

console.log("✅ CA Data structure:");
console.log("   total:", caData.total);
console.log("   mensuel:");
caData.mensuel.forEach(m => {
  console.log(`      - ${m.mois}: ca=${m.ca}`);
});

// Verifier les types et valeurs
console.log("\n✅ Vérification types:");
caData.mensuel.forEach((m, i) => {
  console.log(`   [${i}] mois: ${typeof m.mois} = "${m.mois}"`);
  console.log(`   [${i}] ca: ${typeof m.ca} = ${m.ca}`);
});

// Simuler ce que recharts reçoit
console.log("\n2️⃣  CE QUE RECHARTS REÇOIT");
console.log("─────────────────────────────────────────────────────────────");
console.log("LineChart data prop:");
console.log(JSON.stringify(caData.mensuel, null, 2));

// Vérifier les clés qu'utilise recharts
console.log("\n✅ Clés attendues par recharts LineChart:");
console.log("   - XAxis dataKey='mois'");
console.log("   - Line dataKey='ca'");

console.log("\n✅ Clés présentes dans data:");
caData.mensuel.forEach((m, i) => {
  const hasAll = ('mois' in m) && ('ca' in m);
  console.log(`   [${i}] Complète: ${hasAll ? '✅' : '❌'} (keys: ${Object.keys(m).join(', ')})`);
});

// Test: afficher comme le composant le ferait
console.log("\n3️⃣  SIMULATION AFFICHAGE COMPOSANT");
console.log("─────────────────────────────────────────────────────────────");

// ✅ Code du composant AnalyticsRevenueCharts
function renderChartData(data) {
  console.log("Données reçues par composant:");
  if (!data || data.length === 0) {
    console.log("❌ ERREUR: data vide!");
    return;
  }
  
  console.log(`✅ ${data.length} points à afficher:`);
  data.forEach((point, idx) => {
    console.log(`   Point ${idx}: mois="${point.mois}", ca=${point.ca}`);
    
    // Vérifier si recharts pourrait afficher
    if (point.mois === undefined || point.ca === undefined) {
      console.log(`   ❌ PROBLÈME: mois ou ca manquant!`);
    }
  });
}

renderChartData(caData.mensuel);

// Tester la caMensuelClean du composant
console.log("\n4️⃣  NETTOYAGE DONNÉES (si appliqué)");
console.log("─────────────────────────────────────────────────────────────");

const caMensuelClean = (caData.mensuel || []).map(m => ({
  ...m,
  ca: Math.abs(parseFloat(m.ca || 0))
}));

console.log("Après nettoyage:");
caMensuelClean.forEach((m, i) => {
  console.log(`   [${i}] mois: ${m.mois}, ca: ${m.ca} (type: ${typeof m.ca})`);
});

// Vérifier le formatage pour l'affichage
console.log("\n5️⃣  FORMATAGE POUR AFFICHAGE");
console.log("─────────────────────────────────────────────────────────────");

const caTotal = Math.abs(parseFloat(caData.total || 0));
console.log("CA Total:", caTotal);

caMensuelClean.forEach(m => {
  const pourcentage = caTotal > 0 ? (m.ca / caTotal * 100).toFixed(1) : 0;
  console.log(`   ${m.mois}: ${m.ca.toLocaleString('fr-FR', { 
    minimumFractionDigits: 0, 
    maximumFractionDigits: 0 
  })} EUR (${pourcentage}% du CA)`);
});

// PROBLÈME: Vérifier si données sont réellement 0
console.log("\n6️⃣  DIAGNOSTIC: POURQUOI AFFICHAGE 0?");
console.log("─────────────────────────────────────────────────────────────");

if (caData.total === 0) {
  console.log("❌ PROBLÈME: CA total = 0");
  console.log("   Cause: stats_globales.ca_brut n'existe pas ou = 0");
  console.log("   Solution: Utiliser la somme des débits mensuels");
}

// Calculer le vrai total
const calculateTotal = () => {
  return caData.mensuel.reduce((sum, m) => sum + (m.ca || 0), 0);
};

const realTotal = calculateTotal();
console.log("✅ CA Total calculé:", realTotal);

// Vérifier si mois est vide
if (caData.mensuel.length === 0) {
  console.log("❌ PROBLÈME: Pas de données mensuelles");
}

// Vérifier les NaN ou null
console.log("\n✅ Vérification valeurs numériques:");
caData.mensuel.forEach((m, i) => {
  const caValue = m.ca;
  const isValid = !isNaN(caValue) && caValue !== null && caValue !== undefined;
  console.log(`   [${i}] ${m.mois}: ca=${caValue} - ${isValid ? '✅ Valid' : '❌ INVALID'}`);
});

console.log("\n═══════════════════════════════════════════════════════════════");
console.log("RÉSUMÉ DIAGNOSTIQUE");
console.log("═══════════════════════════════════════════════════════════════");

const issues = [];
if (caData.total === 0) issues.push("1. CA total = 0 (non calculé)");
if (caData.mensuel.length === 0) issues.push("2. Données mensuelles vides");
if (caData.mensuel.some(m => isNaN(m.ca))) issues.push("3. Valeurs NaN détectées");

if (issues.length === 0) {
  console.log("✅ AUCUN PROBLÈME DÉTECTÉ");
  console.log("   Données formatées correctement");
  console.log("   Prêtes pour recharts");
} else {
  console.log("❌ PROBLÈMES DÉTECTÉS:");
  issues.forEach(issue => console.log("   " + issue));
}

console.log("\n✅ Actions recommandées:");
console.log("   1. Calculer CA total = somme débits mensuels");
console.log("   2. Vérifier que evolution_mensuelle n'est pas vide");
console.log("   3. Vérifier le formatage des nombres (parseFloat)");
console.log("   4. Tester le composant avec data complètes");
