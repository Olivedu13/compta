/**
 * AnalyticsKPIDashboard - Tableau de bord KPI pour analytics avancées
 * Affiche les indicateurs clés: CA, Marges, Ratios
 */

import React from 'react';
import { Grid } from '@mui/material';
import { KPIMetric } from './common';

const AnalyticsKPIDashboard = ({
  ca = {},
  marges = {},
  ratios = {},
  tresorerie = {}
}) => {
  const caTotal = Math.abs(ca.total || 0);
  const margeNet = Math.abs(marges.net || 0);
  const margeNette = caTotal > 0 ? (margeNet / caTotal) * 100 : 0;
  const roic = ratios?.roic || 0;
  const solvabilite = Math.abs(ratios?.solvabilite || 0);

  return (
    <Grid container spacing={2}>
      <Grid item xs={12} sm={6} md={3}>
        <KPIMetric
          label="Chiffre d'Affaires"
          value={formatCurrency(caTotal)}
          unit="€"
          color="#2196f3"
          trend={ca.trend_pct}
          progress={Math.min(caTotal / 300000 * 100, 100)}
        />
      </Grid>

      <Grid item xs={12} sm={6} md={3}>
        <KPIMetric
          label="Marge Nette"
          value={margeNette.toFixed(1)}
          unit="%"
          color={margeNette >= 15 ? '#4caf50' : '#ff9800'}
          trend={marges.trend_pct}
          alert={margeNette < 10}
        />
      </Grid>

      <Grid item xs={12} sm={6} md={3}>
        <KPIMetric
          label="Solvabilité"
          value={solvabilite.toFixed(2)}
          unit="x"
          color={solvabilite >= 1.5 ? '#4caf50' : '#f44336'}
          alert={solvabilite < 1}
        />
      </Grid>

      <Grid item xs={12} sm={6} md={3}>
        <KPIMetric
          label="ROIC"
          value={roic.toFixed(1)}
          unit="%"
          color={roic > 0 ? '#4caf50' : '#f44336'}
          alert={roic < 5}
        />
      </Grid>
    </Grid>
  );
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value);
};

export default AnalyticsKPIDashboard;
