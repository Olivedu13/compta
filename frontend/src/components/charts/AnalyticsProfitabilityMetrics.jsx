/**
 * AnalyticsProfitabilityMetrics - Métriques de profitabilité
 * Affiche marges, rentabilité, analyse des écarts
 */

import React from 'react';
import { Grid, Paper, Typography, LinearProgress, Box } from '@mui/material';
import TrendingUpIcon from '@mui/icons-material/TrendingUp';
import TrendingDownIcon from '@mui/icons-material/TrendingDown';

const AnalyticsProfitabilityMetrics = ({ 
  marges = {}, 
  profitability = {},
  variances = {}
}) => {
  const formatCurrency = (value) => {
    return `${value.toLocaleString('fr-FR')} €`;
  };

  const MetricCard = ({ title, value, unit, progress, trend, color = '#2196f3' }) => (
    <Paper sx={{ p: 2.5, borderLeft: `4px solid ${color}` }}>
      <Typography variant="caption" sx={{ color: '#999' }}>
        {title}
      </Typography>
      <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mt: 1 }}>
        <Typography variant="h6" sx={{ fontWeight: 'bold' }}>
          {value} {unit}
        </Typography>
        {trend !== undefined && (
          trend > 0 ? 
            <TrendingUpIcon sx={{ color: '#4caf50' }} /> :
            <TrendingDownIcon sx={{ color: '#f44336' }} />
        )}
      </Box>
      {progress !== undefined && (
        <Box sx={{ mt: 1.5 }}>
          <LinearProgress variant="determinate" value={progress} sx={{ height: 6, borderRadius: 3 }} />
          <Typography variant="caption" sx={{ color: '#999', mt: 0.5, display: 'block' }}>
            {progress.toFixed(1)}% de l'objectif
          </Typography>
        </Box>
      )}
    </Paper>
  );

  return (
    <Grid container spacing={2}>
      {/* Marges */}
      <Grid item xs={12}>
        <Typography variant="h6" sx={{ fontWeight: 'bold', mb: 2 }}>
          Marges Commerciales
        </Typography>
      </Grid>

      <Grid item xs={12} sm={6} md={3}>
        <MetricCard
          title="Marge Brute"
          value={marges.brutMarginPercent?.toFixed(1) || '0'}
          unit="%"
          progress={Math.min((marges.brutMarginPercent || 0) / 50 * 100, 100)}
          color="#4caf50"
        />
      </Grid>

      <Grid item xs={12} sm={6} md={3}>
        <MetricCard
          title="Marge Opérationnelle"
          value={marges.operatingMarginPercent?.toFixed(1) || '0'}
          unit="%"
          progress={Math.min((marges.operatingMarginPercent || 0) / 30 * 100, 100)}
          color="#2196f3"
        />
      </Grid>

      <Grid item xs={12} sm={6} md={3}>
        <MetricCard
          title="Marge Nette"
          value={marges.netMarginPercent?.toFixed(1) || '0'}
          unit="%"
          progress={Math.min((marges.netMarginPercent || 0) / 15 * 100, 100)}
          color="#ff9800"
        />
      </Grid>

      <Grid item xs={12} sm={6} md={3}>
        <MetricCard
          title="Taux de Rentabilité"
          value={profitability.rentabilityRate?.toFixed(1) || '0'}
          unit="%"
          progress={Math.min((profitability.rentabilityRate || 0) / 20 * 100, 100)}
          color="#9c27b0"
        />
      </Grid>

      {/* Analyse des Écarts */}
      <Grid item xs={12}>
        <Typography variant="h6" sx={{ fontWeight: 'bold', mb: 2, mt: 2 }}>
          Analyse des Écarts (vs Budget)
        </Typography>
      </Grid>

      {Object.entries(variances).map(([key, value]) => (
        <Grid item xs={12} sm={6} md={3} key={key}>
          <MetricCard
            title={key}
            value={formatCurrency(value.amount)}
            unit=""
            trend={value.favorable ? 1 : -1}
            color={value.favorable ? '#4caf50' : '#f44336'}
          />
        </Grid>
      ))}

      {/* Ratios de Rentabilité */}
      <Grid item xs={12}>
        <Typography variant="h6" sx={{ fontWeight: 'bold', mb: 2, mt: 2 }}>
          Indicateurs de Performance
        </Typography>
      </Grid>

      <Grid item xs={12} sm={6} md={4}>
        <MetricCard
          title="EBITDA"
          value={formatCurrency(profitability.ebitda || 0)}
          unit=""
          color="#00bcd4"
        />
      </Grid>

      <Grid item xs={12} sm={6} md={4}>
        <MetricCard
          title="ROI"
          value={profitability.roi?.toFixed(1) || '0'}
          unit="%"
          progress={Math.min((profitability.roi || 0) / 25 * 100, 100)}
          color="#03a9f4"
        />
      </Grid>

      <Grid item xs={12} sm={6} md={4}>
        <MetricCard
          title="Résultat Net"
          value={formatCurrency(profitability.netResult || 0)}
          unit=""
          trend={profitability.netResult >= 0 ? 1 : -1}
          color={profitability.netResult >= 0 ? '#4caf50' : '#f44336'}
        />
      </Grid>
    </Grid>
  );
};

export default AnalyticsProfitabilityMetrics;
