/**
 * ErrorBoundary - Capture les erreurs React
 */

import React from 'react';
import { Box, Paper, Typography, Button } from '@mui/material';
import ErrorIcon from '@mui/icons-material/Error';

export class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { 
      hasError: false, 
      error: null,
      errorInfo: null 
    };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true };
  }

  componentDidCatch(error, errorInfo) {
    console.error('ErrorBoundary caught:', error, errorInfo);
    this.setState({
      error,
      errorInfo
    });
  }

  handleReset = () => {
    this.setState({ 
      hasError: false, 
      error: null,
      errorInfo: null 
    });
  };

  render() {
    if (this.state.hasError) {
      return (
        <Box 
          sx={{
            p: 3,
            m: 2,
            backgroundColor: '#ffebee',
            border: '2px solid #f44336',
            borderRadius: 1,
            display: 'flex',
            flexDirection: 'column',
            gap: 2
          }}
        >
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
            <ErrorIcon sx={{ color: '#f44336', fontSize: 32 }} />
            <Typography variant="h6" sx={{ color: '#f44336' }}>
              Une erreur s'est produite
            </Typography>
          </Box>
          
          {process.env.NODE_ENV === 'development' && (
            <Paper 
              sx={{ 
                p: 2, 
                backgroundColor: '#fff3e0',
                fontFamily: 'monospace',
                fontSize: '0.85rem',
                overflow: 'auto',
                maxHeight: '200px'
              }}
            >
              <Typography variant="body2" sx={{ whiteSpace: 'pre-wrap' }}>
                {this.state.error?.toString()}
                {'\n\n'}
                {this.state.errorInfo?.componentStack}
              </Typography>
            </Paper>
          )}
          
          <Box sx={{ display: 'flex', gap: 1 }}>
            <Button 
              variant="contained" 
              color="primary"
              onClick={this.handleReset}
            >
              Réessayer
            </Button>
            <Button 
              variant="outlined" 
              color="primary"
              onClick={() => window.location.href = '/'}
            >
              Retour à l'accueil
            </Button>
          </Box>
        </Box>
      );
    }

    return this.props.children;
  }
}

export default ErrorBoundary;
