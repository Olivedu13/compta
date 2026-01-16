#!/usr/bin/env node

/**
 * TEST DÉTAILLÉ - AnalyticsRevenueCharts
 * Vérifie que les graphiques reçoivent les bonnes données
 */

const mockData = {
  analytics: {
    stats_globales: {
      ca_debut_periode: 0,
      ca_fin_periode: 35000,
      nb_transactions: 16
    },
    evolution_mensuelle: [
      { mois: '2024-01', debit: 17000, credit: 0 },
      { mois: '2024-02', debit: 15000, credit: 0 },
      { mois: '2024-03', debit: 3000, credit: 0 }
    ]
  }
};

console.log('\n' + '='.repeat(80));
console.log('🧪 TEST - AnalyticsRevenueCharts');
console.log('='.repeat(80));

// ============================================================================
// PROBLÈME ACTUEL
// ============================================================================

console.log('\n❌ PROBLÈME DÉTECTÉ:');
console.log('-'.repeat(80));

// Simulation du code ACTUEL (CASSÉ)
const analytics = mockData.analytics;
const evolution_mensuelle = analytics.evolution_mensuelle || [];

const caMensuelTransformed = evolution_mensuelle.map(m => ({ 
  mois: m.mois, 
  ca: m.debit || 0
}));

const caTotalCalculated = caMensuelTransformed.reduce((sum, m) => sum + (m.ca || 0), 0);

const caOBJECT = {
  total: caTotalCalculated,
  mensuel: caMensuelTransformed || [],
  trimestriel: []  // <-- PROBLÈME: TOUJOURS VIDE!
};

console.log('ca.mensuel reçu par AnalyticsRevenueCharts:');
caOBJECT.mensuel.forEach(m => {
  console.log(`  ${m.mois}: ${m.ca} EUR`);
});

console.log('\nca.trimestriel reçu par AnalyticsRevenueCharts:');
console.log(`  ${caOBJECT.trimestriel.length === 0 ? '❌ VIDE!' : caOBJECT.trimestriel}`);

// ============================================================================
// SOLUTION: Calculer les trimestres
// ============================================================================

console.log('\n\n✅ SOLUTION PROPOSÉE:');
console.log('-'.repeat(80));

const calculateTrimestriel = (evolution_mensuelle) => {
  const trimestriels = {};
  
  (evolution_mensuelle || []).forEach(m => {
    const [annee, mois] = m.mois.split('-');
    const moisNum = parseInt(mois);
    
    let trimestre;
    if (moisNum <= 3) trimestre = 'Q1';
    else if (moisNum <= 6) trimestre = 'Q2';
    else if (moisNum <= 9) trimestre = 'Q3';
    else trimestre = 'Q4';
    
    const key = `${annee}-${trimestre}`;
    
    if (!trimestriels[key]) {
      trimestriels[key] = {
        trimestre: key,
        ca: 0,
        debit: 0,
        credit: 0
      };
    }
    
    trimestriels[key].ca += m.debit || 0;
    trimestriels[key].debit += m.debit || 0;
    trimestriels[key].credit += m.credit || 0;
  });
  
  return Object.values(trimestriels);
};

const caTrimestrielCalculated = calculateTrimestriel(evolution_mensuelle);

console.log('ca.trimestriel CALCULÉ:');
caTrimestrielCalculated.forEach(t => {
  console.log(`  ${t.trimestre}: ${t.ca} EUR`);
});

// ============================================================================
// RÉSULTAT FINAL
// ============================================================================

console.log('\n\n' + '='.repeat(80));
console.log('📊 RÉSULTAT FINAL');
console.log('='.repeat(80));

const caFIXED = {
  total: caTotalCalculated,
  mensuel: caMensuelTransformed,
  trimestriel: caTrimestrielCalculated
};

console.log('\n✅ Données complètes pour AnalyticsRevenueCharts:');
console.log(`
  {
    total: ${caFIXED.total} EUR
    mensuel: [
      { mois: '2024-01', ca: ${caFIXED.mensuel[0].ca} EUR }
      { mois: '2024-02', ca: ${caFIXED.mensuel[1].ca} EUR }
      { mois: '2024-03', ca: ${caFIXED.mensuel[2].ca} EUR }
    ]
    trimestriel: [
      { trimestre: '${caFIXED.trimestriel[0].trimestre}', ca: ${caFIXED.trimestriel[0].ca} EUR }
    ]
  }
`);

console.log('✅ Les graphiques afficheront maintenant:');
console.log('  - LineChart Mensuel: 3 points (17k, 15k, 3k) ✓');
console.log('  - BarChart Trimestriel: 1 barre (35k pour Q1) ✓');

console.log('\n' + '='.repeat(80));
console.log('🔧 FIX À APPLIQUER:');
console.log('='.repeat(80));

console.log(`
Dans AdvancedAnalytics.jsx, ligne 93, remplacer:
  trimestriel: []

Par une fonction qui calcule les trimestres:
  trimestriel: calculateTrimestriel(evolution_mensuelle)

Cela permettra aux graphiques d'afficher les données trimestrielles.
`);

console.log('='.repeat(80) + '\n');
