/**
 * Composant KPI Card
 * Affiche un indicateur clé de performance
 */

import React from 'react';
import {
  Card,
  CardContent,
  Typography,
  Box,
  Chip
} from '@mui/material';
import TrendingUpIcon from '@mui/icons-material/TrendingUp';
import TrendingDownIcon from '@mui/icons-material/TrendingDown';

export default function KPICard({ title, value, unit = '€', trend = null, color = 'primary', icon: Icon = null }) {
  const isPositive = trend !== null ? trend >= 0 : value >= 0;
  const trendIcon = isPositive ? <TrendingUpIcon /> : <TrendingDownIcon />;
  const trendColor = isPositive ? '#4caf50' : '#f44336';

  // Formate la valeur avec séparateurs de milliers
  const formattedValue = new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(Math.abs(value));

  return (
    <Card sx={{ height: '100%', display: 'flex', flexDirection: 'column' }}>
      <CardContent sx={{ pb: 1 }}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', mb: 2 }}>
          <Typography color="textSecondary" gutterBottom sx={{ fontSize: '0.875rem' }}>
            {title}
          </Typography>
          {Icon && <Icon sx={{ color: `${color}.main` }} />}
        </Box>

        <Typography variant="h5" sx={{ mb: 1, fontWeight: 'bold', color: value >= 0 ? 'text.primary' : 'error.main' }}>
          {value >= 0 ? '+' : '-'}{formattedValue}
        </Typography>

        {trend !== null && (
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
            <Box sx={{ color: trendColor, display: 'flex', alignItems: 'center' }}>
              {trendIcon}
            </Box>
            <Chip
              label={`${Math.abs(trend)}%`}
              size="small"
              sx={{ bgcolor: trendColor, color: 'white' }}
            />
          </Box>
        )}
      </CardContent>
    </Card>
  );
}
