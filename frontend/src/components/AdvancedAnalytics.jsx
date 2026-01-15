/**
 * Dashboard Analytique Avancé - Senior Level KPIs
 * Phase 3b: Refactorisé avec composants réutilisables
 */

import React, { useEffect, useState } from 'react';
import { Box, Paper, Typography, Grid } from '@mui/material';
import { apiService } from '../services/api';
import { LoadingOverlay, ErrorBoundary } from './common';
import {
  AnalyticsKPIDashboard,
  AnalyticsRevenueCharts,
  AnalyticsDetailedAnalysis,
  AnalyticsProfitabilityMetrics
} from './charts';
import AnalyticsCyclesAndRatios from './charts/AnalyticsCyclesAndRatios';
import AnalyticsAlerts from './charts/AnalyticsAlerts';

const AdvancedAnalytics = ({ exercice }) => {
  const [analytics, setAnalytics] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchAnalytics = async () => {
      try {
        setError(null);
        const response = await apiService.getAnalyticsAdvanced(exercice);
        setAnalytics(response.data);
      } catch (err) {
        console.error('Erreur chargement analytics:', err);
        setError(err.response?.data?.error || 'Erreur lors du chargement des données');
      } finally {
        setLoading(false);
      }
    };

    fetchAnalytics();
  }, [exercice]);

  if (loading) {
    return <LoadingOverlay open={true} message="Chargement des analyses avancées..." />;
  }

  if (error) {
    return (
      <ErrorBoundary>
        <Paper sx={{ p: 3, backgroundColor: '#ffebee', borderLeft: '4px solid #f44336' }}>
          <Typography variant="h6" sx={{ color: '#f44336', mb: 1 }}>
            ⚠️ Erreur chargement analytics
          </Typography>
          <Typography color="textSecondary">
            {error}
          </Typography>
        </Paper>
      </ErrorBoundary>
    );
  }

  if (!analytics) {
    return <Typography>Aucune donnée disponible pour cet exercice</Typography>;
  }

  const { ca, marges, ratios_exploitation, ratios_solvabilite, cycles_tresorerie, tresorerie, top_clients, top_fournisseurs, couts, clients_encours, fournisseurs_encours } = analytics;

  // Préparer données pour graphiques
  const caMensuel = (ca.mensuel || []).map(m => ({ ...m, ca: Math.abs(parseFloat(m.ca || 0)) }));
  const caTrimestriel = (ca.trimestriel || []).map(t => ({ ...t, ca: Math.abs(parseFloat(t.ca || 0)) }));
  const topClientsClean = (top_clients || []).filter(c => c?.client).map(c => ({ ...c, ca: Math.abs(parseFloat(c.montant)) }));
  const topFournisseursClean = (top_fournisseurs || []).filter(f => f?.fournisseur).map(f => ({ ...f, purchases: Math.abs(parseFloat(f.montant)) }));

  return (
    <Box>
      <ErrorBoundary>
        {/* 1. KPI Dashboard */}
        <Typography variant="h5" sx={{ mb: 3, mt: 4, fontWeight: 'bold' }}>
          📊 Vue d'ensemble Financière
        </Typography>
        <AnalyticsKPIDashboard
          ca={ca}
          marges={marges}
          ratios={ratios_solvabilite}
          tresorerie={tresorerie}
        />

        {/* 2. Revenue Charts */}
        <Typography variant="h5" sx={{ mb: 3, mt: 4, fontWeight: 'bold' }}>
          📈 Saisonnalité & Tendance CA
        </Typography>
        <AnalyticsRevenueCharts
          caMensuel={caMensuel}
          caTrimestriel={caTrimestriel}
        />

        {/* 3. Profitability Metrics */}
        <Typography variant="h5" sx={{ mb: 3, mt: 4, fontWeight: 'bold' }}>
          💰 Profitabilité & Marges
        </Typography>
        <AnalyticsProfitabilityMetrics
          marges={marges}
          profitability={{ ebitda: tresorerie.ebitda, roi: ratios_solvabilite.roi, netResult: tresorerie.resultat_net }}
          variances={{}}
        />

        {/* 4. Cycles de Trésorerie */}
        <Typography variant="h5" sx={{ mb: 3, mt: 4, fontWeight: 'bold' }}>
          🔄 Cycles de Trésorerie & BFR
        </Typography>
        <AnalyticsCyclesAndRatios
          cycles={cycles_tresorerie}
          ratios={ratios_exploitation}
          couts={couts}
          tresorerie={tresorerie}
        />

        {/* 5. Analyse Détaillée */}
        <Typography variant="h5" sx={{ mb: 3, mt: 4, fontWeight: 'bold' }}>
          👥 Analyse Clients & Fournisseurs
        </Typography>
        <AnalyticsDetailedAnalysis
          topClients={topClientsClean}
          topSuppliers={topFournisseursClean}
          costStructure={[]}
        />

        {/* 6. Alertes */}
        <Typography variant="h5" sx={{ mb: 3, mt: 4, fontWeight: 'bold' }}>
          ⚠️ Alertes & Recommandations
        </Typography>
        <AnalyticsAlerts
          ratios={ratios_exploitation}
          solvabilite={ratios_solvabilite}
          cycles={cycles_tresorerie}
          topClients={topClientsClean}
          ca={ca}
        />
      </ErrorBoundary>
    </Box>
  );
};

export default AdvancedAnalytics;
