/**
 * KPIMetric - Card KPI réutilisable avec indicateurs
 */

import React from 'react';
import { Card, CardContent, Typography, Box, Chip, LinearProgress } from '@mui/material';
import TrendingUpIcon from '@mui/icons-material/TrendingUp';
import TrendingDownIcon from '@mui/icons-material/TrendingDown';
import WarningIcon from '@mui/icons-material/Warning';

export const KPIMetric = ({
  label,
  value,
  unit = '',
  color = '#2196f3',
  trend = null,
  alert = false,
  progress = null,
  icon: Icon = null,
  onClick = null,
  variant = 'elevated'
}) => {
  const trendIsPositive = trend ? trend > 0 : null;
  
  // Format value for display
  const displayValue = typeof value === 'number' 
    ? (value >= 1000 ? (value / 1000).toFixed(1) + 'k' : value.toFixed(1))
    : value;
  
  return (
    <Card
      sx={{
        backgroundColor: alert ? '#ffebee' : '#f5f5f5',
        borderLeft: `4px solid ${alert ? '#f44336' : color}`,
        cursor: onClick ? 'pointer' : 'default',
        transition: 'all 0.3s ease',
        '&:hover': onClick ? {
          transform: 'translateY(-4px)',
          boxShadow: 4
        } : {}
      }}
      onClick={onClick}
    >
      <CardContent>
        {/* Label */}
        <Typography 
          color="textSecondary" 
          sx={{ fontSize: 12, mb: 0.5 }}
        >
          {label}
        </Typography>

        {/* Valeur principale */}
        <Box sx={{ display: 'flex', alignItems: 'baseline', gap: 0.5, mb: 1 }}>
          {Icon && <Icon sx={{ color, fontSize: '1.5rem' }} />}
          <Typography 
            variant="h5" 
            sx={{ 
              color,
              fontWeight: 'bold'
            }}
          >
            {displayValue}
          </Typography>
          {unit && (
            <Typography sx={{ fontSize: '0.85rem', color: 'textSecondary' }}>
              {unit}
            </Typography>
          )}
        </Box>

        {/* Trend */}
        {trend !== null && (
          <Typography 
            variant="caption" 
            sx={{ 
              color: trendIsPositive ? '#4caf50' : '#f44336',
              fontWeight: 'bold',
              display: 'block',
              mb: alert ? 1 : 0
            }}
          >
            {trendIsPositive ? '↑' : '↓'} {Math.abs(trend).toFixed(1)}%
          </Typography>
        )}

        {/* Alert */}
        {alert && (
          <Typography 
            variant="caption" 
            sx={{ 
              color: '#f44336', 
              display: 'block',
              mt: trend ? 0 : 1
            }}
          >
            ⚠️ Attention requise
          </Typography>
        )}

        {/* Barre de progression optionnelle */}
        {progress !== null && (
          <Box sx={{ mt: 2 }}>
            <LinearProgress 
              variant="determinate" 
              value={Math.min(progress, 100)} 
              sx={{
                height: 6,
                borderRadius: 3,
                backgroundColor: '#e0e0e0',
                '& .MuiLinearProgress-bar': {
                  backgroundColor: color
                }
              }}
            />
            <Typography sx={{ fontSize: '0.75rem', color: 'textSecondary', mt: 0.5 }}>
              {Math.round(progress)}%
            </Typography>
          </Box>
        )}
      </CardContent>
    </Card>
  );
};

export default KPIMetric;
