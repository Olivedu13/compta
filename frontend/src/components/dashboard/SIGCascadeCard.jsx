/**
 * SIGCascadeCard - Affichage d'une étape de la cascade SIG
 * Composant réutilisable pour chaque indicateur
 */

import React from 'react';
import { Card, CardContent, Typography, Box, Grid } from '@mui/material';
import TrendingUpIcon from '@mui/icons-material/TrendingUp';
import TrendingDownIcon from '@mui/icons-material/TrendingDown';

export default function SIGCascadeCard({ label, value, description, isPositive, color, variance = null }) {
  const formatCurrency = (val) => {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'EUR',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(val || 0);
  };

  return (
    <Card sx={{
      bgcolor: isPositive ? '#e8f5e9' : '#ffebee',
      borderLeft: `4px solid ${color}`,
      height: '100%',
      display: 'flex',
      flexDirection: 'column',
      transition: 'all 0.3s ease',
      '&:hover': {
        boxShadow: 3,
        transform: 'translateY(-2px)'
      }
    }}>
      <CardContent sx={{ flex: 1 }}>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 1 }}>
          <Typography variant="caption" sx={{ fontWeight: 'bold', color: '#666', flex: 1 }}>
            {label}
          </Typography>
          {isPositive ? (
            <TrendingUpIcon sx={{ fontSize: '1rem', color: '#4caf50' }} />
          ) : (
            <TrendingDownIcon sx={{ fontSize: '1rem', color: '#f44336' }} />
          )}
        </Box>

        <Typography 
          variant="h6" 
          sx={{ 
            my: 1, 
            color: color,
            fontWeight: 'bold',
            fontSize: '1.25rem'
          }}
        >
          {formatCurrency(value)}
        </Typography>

        {description && (
          <Typography variant="body2" color="textSecondary" sx={{ mb: 1 }}>
            {description}
          </Typography>
        )}

        {variance !== null && (
          <Box sx={{ 
            mt: 2, 
            pt: 1, 
            borderTop: '1px solid #ddd',
            fontSize: '0.85rem',
            color: variance >= 0 ? '#4caf50' : '#f44336'
          }}>
            <strong>Variance: </strong>{formatCurrency(variance)}
          </Box>
        )}
      </CardContent>
    </Card>
  );
}
