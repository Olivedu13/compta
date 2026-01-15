import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Box,
  Container,
  TextField,
  Button,
  Paper,
  Typography,
  Alert,
  CircularProgress,
  InputAdornment,
} from '@mui/material';
import { styled } from '@mui/material/styles';
import LockIcon from '@mui/icons-material/Lock';
import EmailIcon from '@mui/icons-material/Email';
import VisibilityIcon from '@mui/icons-material/Visibility';
import VisibilityOffIcon from '@mui/icons-material/VisibilityOff';

const StyledContainer = styled(Container)(({ theme }) => ({
  display: 'flex',
  minHeight: '100vh',
  alignItems: 'center',
  justifyContent: 'center',
  background: `linear-gradient(135deg, ${theme.palette.primary.main} 0%, ${theme.palette.primary.dark} 100%)`,
}));

const StyledPaper = styled(Paper)(({ theme }) => ({
  padding: theme.spacing(4),
  borderRadius: theme.spacing(2),
  boxShadow: '0 20px 60px 0 rgba(0, 0, 0, 0.3)',
  backgroundColor: '#ffffff',
  maxWidth: 450,
  width: '100%',
}));

const StyledTextField = styled(TextField)(({ theme }) => ({
  '& .MuiOutlinedInput-root': {
    borderRadius: theme.spacing(1),
    backgroundColor: '#f5f5f5',
    transition: 'all 0.2s ease',
    '& fieldset': {
      borderColor: '#e0e0e0',
      borderWidth: '2px',
    },
    '&:hover fieldset': {
      borderColor: theme.palette.primary.main,
    },
    '&.Mui-focused fieldset': {
      borderColor: theme.palette.primary.main,
      borderWidth: '2px',
    },
  },
  '& .MuiOutlinedInput-input': {
    color: '#333333',
    fontSize: '1rem',
    fontWeight: '500',
    '&::placeholder': {
      color: '#999999',
      opacity: 1,
    },
  },
  '& .MuiInputBase-input:-webkit-autofill': {
    WebkitBoxShadow: '0 0 0 1000px #f5f5f5 inset',
    WebkitTextFillColor: '#333333',
  },
  '& .MuiInputAdornment-root': {
    color: theme.palette.primary.main,
  },
}));

const LoginPage = () => {
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleLogin = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const response = await fetch('/api/auth/login.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password }),
      });

      const data = await response.json();

      if (!data.success) {
        setError(data.error || 'Erreur de connexion');
        return;
      }

      // Store token in localStorage
      localStorage.setItem('token', data.token);
      localStorage.setItem('user', JSON.stringify(data.user));

      // Redirect to dashboard
      navigate('/dashboard');
    } catch (err) {
      setError('Erreur de connexion au serveur');
      console.error('Login error:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleTogglePasswordVisibility = () => {
    setShowPassword(!showPassword);
  };

  return (
    <StyledContainer maxWidth="sm">
      <StyledPaper>
        {/* Header */}
        <Box sx={{ textAlign: 'center', mb: 4 }}>
          <Box sx={{ display: 'flex', justifyContent: 'center', mb: 2 }}>
            <Box
              sx={{
                background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                borderRadius: '50%',
                padding: 2,
              }}
            >
              <LockIcon sx={{ color: 'white', fontSize: 32 }} />
            </Box>
          </Box>
          <Typography variant="h4" sx={{ color: '#333333', fontWeight: 700, mb: 1 }}>
            Compta
          </Typography>
          <Typography variant="body2" sx={{ color: '#666666' }}>
            Système de gestion comptable
          </Typography>
        </Box>

        {/* Error Alert */}
        {error && (
          <Alert severity="error" sx={{ mb: 3, borderRadius: 1 }}>
            {error}
          </Alert>
        )}

        {/* Login Form */}
        <Box component="form" onSubmit={handleLogin} sx={{ display: 'flex', flexDirection: 'column', gap: 2.5 }}>
          {/* Email Field */}
          <StyledTextField
            fullWidth
            type="email"
            placeholder="Email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            disabled={loading}
            InputProps={{
              startAdornment: (
                <InputAdornment position="start">
                  <EmailIcon />
                </InputAdornment>
              ),
            }}
            autoComplete="email"
            required
          />

          {/* Password Field */}
          <StyledTextField
            fullWidth
            type={showPassword ? 'text' : 'password'}
            placeholder="Mot de passe"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            disabled={loading}
            InputProps={{
              startAdornment: (
                <InputAdornment position="start">
                  <LockIcon />
                </InputAdornment>
              ),
              endAdornment: (
                <InputAdornment position="end">
                  <Button
                    type="button"
                    size="small"
                    onClick={handleTogglePasswordVisibility}
                    sx={{
                      color: '#666666',
                      minWidth: 'auto',
                      padding: '4px',
                      '&:hover': {
                        backgroundColor: 'rgba(0, 0, 0, 0.04)',
                      },
                    }}
                    onMouseDown={(e) => e.preventDefault()}
                  >
                    {showPassword ? <VisibilityOffIcon fontSize="small" /> : <VisibilityIcon fontSize="small" />}
                  </Button>
                </InputAdornment>
              ),
            }}
            autoComplete="current-password"
            required
          />

          {/* Submit Button */}
          <Button
            type="submit"
            fullWidth
            variant="contained"
            size="large"
            disabled={loading || !email || !password}
            sx={{
              background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
              color: 'white',
              fontWeight: 600,
              py: 1.5,
              mt: 1,
              '&:hover': {
                boxShadow: '0 8px 16px 0 rgba(102, 126, 234, 0.4)',
              },
              '&:disabled': {
                background: '#cccccc',
              },
            }}
          >
            {loading ? (
              <CircularProgress size={24} sx={{ color: 'white' }} />
            ) : (
              'Se connecter'
            )}
          </Button>
        </Box>

        {/* Footer */}
        <Box sx={{ textAlign: 'center', mt: 4, pt: 3, borderTop: '1px solid #e0e0e0' }}>
          <Typography variant="caption" sx={{ color: '#999999' }}>
            © 2026 Atelier Thierry Christiane
          </Typography>
        </Box>

        {/* Security Note */}
        <Box sx={{ mt: 3, p: 2, backgroundColor: '#f0f7ff', borderRadius: 1 }}>
          <Typography variant="caption" sx={{ color: '#0066cc', display: 'block', fontWeight: 600 }}>
            🔒 Connexion sécurisée
          </Typography>
          <Typography variant="caption" sx={{ color: '#666666', display: 'block', mt: 0.5 }}>
            Vos données sont chiffrées en transit (HTTPS)
          </Typography>
        </Box>
      </StyledPaper>
    </StyledContainer>
  );
};

export default LoginPage;
