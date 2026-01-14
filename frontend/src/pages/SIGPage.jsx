/**
 * Page SIG (Soldes Intermédiaires de Gestion)
 * Rapports financiers détaillés
 */

import React, { useEffect, useState } from 'react';
import {
  Typography,
  Box,
  CircularProgress,
  Alert,
  Select,
  MenuItem,
  FormControl,
  InputLabel,
  Grid,
  Paper,
  Card,
  CardContent
} from '@mui/material';
import {
  ComposedChart,
  Bar,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer
} from 'recharts';
import apiService from '../services/api';

export default function SIGPage() {
  const [exercice, setExercice] = useState(new Date().getFullYear());
  const [sig, setSig] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchSIG = async () => {
      try {
        setLoading(true);
        setError(null);

        const response = await apiService.getSIGDetail(exercice);
        setSig(response.data.data);
      } catch (err) {
        console.error('Erreur chargement SIG:', err);
        setError('Erreur lors du chargement des SIG');
      } finally {
        setLoading(false);
      }
    };

    fetchSIG();
  }, [exercice]);

  if (loading) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '400px' }}>
        <CircularProgress />
      </Box>
    );
  }

  if (error) {
    return <Alert severity="error">{error}</Alert>;
  }

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'EUR',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(value);
  };

  const getColor = (value) => {
    return value >= 0 ? '#4caf50' : '#f44336';
  };

  return (
    <Box>
      <Typography variant="h4" sx={{ mb: 3 }}>
        Soldes Intermédiaires de Gestion
      </Typography>

      <Box sx={{ mb: 3, display: 'flex', gap: 2, alignItems: 'center' }}>
        <FormControl sx={{ minWidth: 200 }}>
          <InputLabel>Exercice</InputLabel>
          <Select
            value={exercice}
            label="Exercice"
            onChange={(e) => setExercice(e.target.value)}
          >
            {[2024, 2023, 2022].map(year => (
              <MenuItem key={year} value={year}>{year}</MenuItem>
            ))}
          </Select>
        </FormControl>
      </Box>

      {sig && (
        <>
          {/* Cascade visuelle */}
          <Paper sx={{ p: 3, mb: 4 }}>
            <Typography variant="h6" sx={{ mb: 2 }}>
              Cascade des SIG
            </Typography>
            <Grid container spacing={2}>
              {Object.entries(sig).map(([key, value]) => {
                const isPositive = value.formatted.est_positif;
                return (
                  <Grid item xs={12} sm={6} md={4} key={key}>
                    <Card sx={{
                      bgcolor: isPositive ? '#e8f5e9' : '#ffebee',
                      borderLeft: `4px solid ${value.formatted.couleur}`
                    }}>
                      <CardContent>
                        <Typography variant="caption" color="textSecondary" sx={{ fontWeight: 'bold' }}>
                          {key.replace(/_/g, ' ').toUpperCase()}
                        </Typography>
                        <Typography variant="h6" sx={{ my: 1, color: value.formatted.couleur }}>
                          {value.formatted.symbole}{value.formatted.valeur_affichee}
                        </Typography>
                        <Typography variant="body2" color="textSecondary">
                          {value.description}
                        </Typography>
                      </CardContent>
                    </Card>
                  </Grid>
                );
              })}
            </Grid>
          </Paper>

          {/* Graphique comparatif */}
          <Paper sx={{ p: 3 }}>
            <Typography variant="h6" sx={{ mb: 2 }}>
              Analyse
            </Typography>
            <ResponsiveContainer width="100%" height={300}>
              <ComposedChart data={Object.values(sig).map((item, idx) => ({
                name: `SIG ${idx + 1}`,
                valeur: item.formatted.valeur_brute
              }))}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="name" />
                <YAxis />
                <Tooltip formatter={(value) => formatCurrency(value)} />
                <Legend />
                <Bar dataKey="valeur" fill="#1a237e" name="Montant" />
              </ComposedChart>
            </ResponsiveContainer>
          </Paper>
        </>
      )}
    </Box>
  );
}
