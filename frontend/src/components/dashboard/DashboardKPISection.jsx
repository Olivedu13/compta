/**
 * DashboardKPISection - Section des KPI (Stocks, Trésorerie, Tiers)
 */

import React from 'react';
import { Grid, Typography } from '@mui/material';
import KPICard from '../KPICard';

const DashboardKPISection = ({ kpis = {} }) => {
  // Fallback pour les champs manquants (en cas de réponse API incomplète)
  const stock = kpis?.stock || { or: 0 };
  const tresorerie = kpis?.tresorerie || { banque: 0, caisse: 0 };
  const tiers = kpis?.tiers || { clients: 0, fournisseurs: 0 };
  
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
            value={stock?.or || 0}
            color="secondary"
          />
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <KPICard
            title="Total Stock"
            value={stock?.or || 0}
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
            value={tresorerie?.banque || 0}
          />
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <KPICard
            title="Caisse"
            value={tresorerie?.caisse || 0}
          />
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <KPICard
            title="Clients"
            value={tiers?.clients || 0}
            trend={5}
          />
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <KPICard
            title="Fournisseurs"
            value={tiers?.fournisseurs || 0}
          />
        </Grid>
      </Grid>
    </>
  );
};

export default DashboardKPISection;
