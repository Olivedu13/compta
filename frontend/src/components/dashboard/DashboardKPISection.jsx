/**
 * DashboardKPISection - Section des KPI (Stocks, Trésorerie, Tiers)
 */

import React from 'react';
import { Grid, Typography } from '@mui/material';
import KPICard from '../KPICard';

const DashboardKPISection = ({ kpis = {} }) => {
  return (
    <>
      {/* Stocks */}
      <Typography variant="h5" sx={{ mb: 2, mt: 4, fontWeight: 'bold' }}>
        📦 Actifs Principaux
      </Typography>
      <Grid container spacing={2} sx={{ mb: 4 }}>
        <Grid item xs={12} sm={6} md={3}>
          <KPICard
            title="Stock Or"
            value={kpis?.stock?.or || 0}
            color="secondary"
          />
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <KPICard
            title="Total Stock"
            value={kpis?.stock?.or || 0}
          />
        </Grid>
      </Grid>

      {/* Trésorerie & Tiers */}
      <Typography variant="h5" sx={{ mb: 2, mt: 4, fontWeight: 'bold' }}>
        💰 Trésorerie & Tiers
      </Typography>
      <Grid container spacing={2} sx={{ mb: 4 }}>
        <Grid item xs={12} sm={6} md={3}>
          <KPICard
            title="Banque"
            value={kpis?.tresorerie?.banque || 0}
          />
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <KPICard
            title="Caisse"
            value={kpis?.tresorerie?.caisse || 0}
          />
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <KPICard
            title="Clients"
            value={kpis?.tiers?.clients || 0}
            trend={5}
          />
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <KPICard
            title="Fournisseurs"
            value={kpis?.tiers?.fournisseurs || 0}
          />
        </Grid>
      </Grid>
    </>
  );
};

export default DashboardKPISection;
