/**
 * LoadingOverlay - Overlay de chargement réutilisable
 */

import React from 'react';
import { Backdrop, CircularProgress, Box, Typography } from '@mui/material';

export const LoadingOverlay = ({ 
  open = false, 
  message = 'Chargement...',
  fullScreen = false 
}) => {
  return (
    <Backdrop
      sx={{
        color: '#fff',
        zIndex: 1300,
        backgroundColor: 'rgba(0, 0, 0, 0.5)',
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center'
      }}
      open={open}
    >
      <Box sx={{ textAlign: 'center' }}>
        <CircularProgress color="inherit" size={50} />
        {message && (
          <Typography 
            sx={{ mt: 2, fontSize: '1.1rem', fontWeight: 500 }}
          >
            {message}
          </Typography>
        )}
      </Box>
    </Backdrop>
  );
};

export default LoadingOverlay;
