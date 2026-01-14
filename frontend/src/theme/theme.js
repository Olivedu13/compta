import { createTheme } from '@mui/material/styles';

/**
 * Thème Apple/Google Minimaliste
 * Design épuré, lisible, moderne - Silicon Valley Style
 */

const theme = createTheme({
  palette: {
    primary: {
      main: '#0f172a', // Bleu très foncé (presque noir)
      light: '#1e293b',
      dark: '#020617',
      contrastText: '#fff'
    },
    secondary: {
      main: '#0ea5e9', // Bleu ciel léger
      light: '#38bdf8',
      dark: '#0284c7',
      contrastText: '#fff'
    },
    success: {
      main: '#10b981',
      light: '#6ee7b7',
      dark: '#059669'
    },
    error: {
      main: '#ef4444',
      light: '#fca5a5',
      dark: '#dc2626'
    },
    warning: {
      main: '#f59e0b',
      light: '#fbbf24',
      dark: '#d97706'
    },
    info: {
      main: '#06b6d4',
      light: '#67e8f9',
      dark: '#0891b2'
    },
    background: {
      default: '#f8fafc', // Gris très clair
      paper: '#ffffff'
    },
    text: {
      primary: '#0f172a',
      secondary: '#64748b',
      disabled: '#cbd5e1'
    },
    divider: '#e2e8f0'
  },
  typography: {
    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", "Roboto", "Oxygen", "Ubuntu", "Cantarell", sans-serif',
    h1: {
      fontSize: '2.5rem',
      fontWeight: 700,
      lineHeight: 1.2,
      letterSpacing: '-0.02em'
    },
    h2: {
      fontSize: '2rem',
      fontWeight: 700,
      lineHeight: 1.3,
      letterSpacing: '-0.01em'
    },
    h3: {
      fontSize: '1.75rem',
      fontWeight: 600,
      lineHeight: 1.3
    },
    h4: {
      fontSize: '1.5rem',
      fontWeight: 600,
      lineHeight: 1.4
    },
    h5: {
      fontSize: '1.25rem',
      fontWeight: 600,
      lineHeight: 1.4
    },
    h6: {
      fontSize: '1rem',
      fontWeight: 600,
      lineHeight: 1.5
    },
    body1: {
      fontSize: '1rem',
      fontWeight: 400,
      lineHeight: 1.6,
      color: '#0f172a'
    },
    body2: {
      fontSize: '0.9375rem',
      fontWeight: 400,
      lineHeight: 1.6,
      color: '#64748b'
    },
    button: {
      fontSize: '0.9375rem',
      fontWeight: 600,
      lineHeight: 1.5,
      textTransform: 'none',
      letterSpacing: '0em'
    },
    caption: {
      fontSize: '0.8125rem',
      fontWeight: 400,
      lineHeight: 1.5,
      color: '#94a3b8'
    }
  },
  shape: {
    borderRadius: 12
  },
  components: {
    MuiCssBaseline: {
      styleOverrides: {
        body: {
          backgroundColor: '#f8fafc',
          color: '#0f172a'
        }
      }
    },
    MuiButton: {
      styleOverrides: {
        root: {
          textTransform: 'none',
          fontWeight: 600,
          fontSize: '0.9375rem',
          padding: '10px 20px',
          borderRadius: '10px',
          transition: 'all 0.25s cubic-bezier(0.4, 0, 0.2, 1)',
          '&:hover': {
            transform: 'translateY(-1px)',
            boxShadow: '0 4px 12px rgba(15, 23, 42, 0.1)'
          },
          '&:active': {
            transform: 'translateY(0px)'
          }
        },
        contained: {
          boxShadow: '0 2px 6px rgba(15, 23, 42, 0.06)',
          '&:hover': {
            boxShadow: '0 8px 16px rgba(15, 23, 42, 0.12)',
            backgroundColor: '#0d1117'
          }
        },
        outlined: {
          borderWidth: '1.5px',
          '&:hover': {
            borderWidth: '1.5px',
            backgroundColor: 'rgba(15, 23, 42, 0.03)'
          }
        },
        text: {
          '&:hover': {
            backgroundColor: 'rgba(15, 23, 42, 0.04)'
          }
        }
      },
      defaultProps: {
        disableElevation: false
      }
    },
    MuiCard: {
      styleOverrides: {
        root: {
          boxShadow: '0 1px 3px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06)',
          borderRadius: '12px',
          border: '1px solid #e2e8f0',
          transition: 'all 0.25s cubic-bezier(0.4, 0, 0.2, 1)',
          backgroundColor: '#ffffff',
          '&:hover': {
            boxShadow: '0 10px 25px rgba(0, 0, 0, 0.08)',
            borderColor: '#cbd5e1',
            transform: 'translateY(-2px)'
          }
        }
      }
    },
    MuiPaper: {
      styleOverrides: {
        root: {
          backgroundImage: 'none',
          backgroundColor: '#ffffff',
          border: '1px solid #e2e8f0'
        },
        elevation0: {
          boxShadow: 'none',
          border: '1px solid #e2e8f0'
        },
        elevation1: {
          boxShadow: '0 1px 2px rgba(0, 0, 0, 0.03)',
          border: '1px solid #e2e8f0'
        }
      }
    },
    MuiAppBar: {
      styleOverrides: {
        root: {
          backgroundColor: '#ffffff',
          color: '#0f172a',
          boxShadow: '0 1px 3px rgba(0, 0, 0, 0.04)',
          borderBottom: '1px solid #e2e8f0'
        }
      }
    },
    MuiTextField: {
      styleOverrides: {
        root: {
          '& .MuiOutlinedInput-root': {
            borderRadius: '10px',
            backgroundColor: '#f8fafc',
            transition: 'all 0.2s',
            '& fieldset': {
              borderColor: '#e2e8f0',
              borderWidth: '1.5px'
            },
            '&:hover fieldset': {
              borderColor: '#cbd5e1'
            },
            '&.Mui-focused fieldset': {
              borderColor: '#0ea5e9',
              borderWidth: '2px'
            }
          }
        }
      }
    },
    MuiChip: {
      styleOverrides: {
        root: {
          borderRadius: '8px',
          fontWeight: 500,
          fontSize: '0.875rem'
        },
        filled: {
          backgroundColor: '#f1f5f9'
        }
      }
    },
    MuiTabs: {
      styleOverrides: {
        root: {
          borderBottom: '1px solid #e2e8f0',
          '& .MuiTabs-indicator': {
            backgroundColor: '#0ea5e9',
            height: '3px',
            borderRadius: '3px 3px 0 0'
          }
        },
        tab: {
          textTransform: 'none',
          fontWeight: 500,
          fontSize: '0.9375rem',
          color: '#64748b',
          '&.Mui-selected': {
            color: '#0f172a'
          }
        }
      }
    },
    MuiLinearProgress: {
      styleOverrides: {
        root: {
          height: '6px',
          borderRadius: '3px',
          backgroundColor: '#e2e8f0'
        },
        bar: {
          borderRadius: '3px',
          background: 'linear-gradient(90deg, #0ea5e9 0%, #06b6d4 100%)'
        }
      }
    },
    MuiAlert: {
      styleOverrides: {
        root: {
          borderRadius: '10px',
          border: 'none',
          fontSize: '0.9375rem'
        },
        standardSuccess: {
          backgroundColor: '#f0fdf4',
          color: '#166534'
        },
        standardError: {
          backgroundColor: '#fef2f2',
          color: '#991b1b'
        },
        standardWarning: {
          backgroundColor: '#fffbeb',
          color: '#92400e'
        },
        standardInfo: {
          backgroundColor: '#f0f9ff',
          color: '#0c4a6e'
        }
      }
    }
  }
});

export default theme;
