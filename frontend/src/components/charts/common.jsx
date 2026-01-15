import React from 'react';
import { Box, Typography, Paper } from '@mui/material';

export const KPIMetric = ({ label, value, unit, color = '#2196f3', icon: Icon }) => {
  return (
    <Paper sx={{ p: 2, backgroundColor: '#f5f5f5', borderRadius: 1 }}>
      <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
        {Icon && <Icon sx={{ color, fontSize: 40 }} />}
        <Box>
          <Typography variant="caption" color="textSecondary">{label}</Typography>
          <Typography variant="h6" sx={{ color, fontWeight: 'bold' }}>
            {value} <Typography component="span" variant="body2" sx={{ color: 'textSecondary' }}>{unit}</Typography>
          </Typography>
        </Box>
      </Box>
    </Paper>
  );
};
