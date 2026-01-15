/**
 * ChartCard - Conteneur pour les graphiques
 */

import React from 'react';
import { Paper, Typography, Box, CircularProgress } from '@mui/material';

export const ChartCard = ({
  title,
  subtitle,
  children,
  loading = false,
  error = null,
  height = 300,
  sx = {}
}) => {
  return (
    <Paper
      sx={{
        p: 2,
        height,
        display: 'flex',
        flexDirection: 'column',
        ...sx
      }}
    >
      {/* Header */}
      {(title || subtitle) && (
        <Box sx={{ mb: 2 }}>
          {title && (
            <Typography variant="h6" sx={{ fontWeight: 600 }}>
              {title}
            </Typography>
          )}
          {subtitle && (
            <Typography variant="body2" color="textSecondary">
              {subtitle}
            </Typography>
          )}
        </Box>
      )}

      {/* Content */}
      {loading ? (
        <Box sx={{ 
          flex: 1, 
          display: 'flex', 
          alignItems: 'center', 
          justifyContent: 'center' 
        }}>
          <CircularProgress />
        </Box>
      ) : error ? (
        <Box sx={{
          flex: 1,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          backgroundColor: '#ffebee',
          borderRadius: 1,
          p: 2
        }}>
          <Typography color="error">{error}</Typography>
        </Box>
      ) : (
        <Box sx={{ flex: 1, overflow: 'auto' }}>
          {children}
        </Box>
      )}
    </Paper>
  );
};

export default ChartCard;
