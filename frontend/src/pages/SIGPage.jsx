/**
 * Page SIGPage - Phase 5 Refactored
 * Soldes Intermédiaires de Gestion avec visualisations avancées
 * Intègre données Phase 3 (Tiers + Cashflow) avec SIG
 */

import React, { useEffect, useState } from 'react';
import {
  Typography,
  Box,
  CircularProgress,
  Alert,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Grid,
  Paper,
  Tabs,
  Tab,
  Card,
  CardContent,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Chip
} from '@mui/material';
import {
  BarChart,
  Bar,
  LineChart,
  Line,
  AreaChart,
  Area,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
  ComposedChart
} from 'recharts';
import InfoIcon from '@mui/icons-material/Info';
import apiService from '../services/api';
import { LoadingOverlay, ErrorBoundary } from '../components/common';
import SIGCascadeCard from '../components/dashboard/SIGCascadeCard';
import SIGDetailedView from '../components/dashboard/SIGDetailedView';

export default function SIGPage() {
  const [exercice, setExercice] = useState(null);
  const [annees, setAnnees] = useState([]);
  const [sig, setSig] = useState(null);
  const [cashflow, setCashflow] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [tabValue, setTabValue] = useState(0);

  // Charger les années
  useEffect(() => {
    const loadAnnees = async () => {
      try {
        const response = await apiService.getAnnees();
        const years = Array.isArray(response.data.data) ? response.data.data : [];
        
        if (years.length > 0) {
          setAnnees(years);
          setExercice(years[0]);
        } else {
          setAnnees([]);
          setExercice(2024);
          setError('Aucune année disponible');
        }
      } catch (err) {
        console.error('Erreur chargement années:', err);
        setAnnees([2024]);
        setExercice(2024);
      }
    };
    loadAnnees();
  }, []);

  // Charger les données SIG + Cashflow
  useEffect(() => {
    if (exercice === null) return;

    const fetchData = async () => {
      try {
        setLoading(true);
        setError(null);

        console.log('📊 SIGPage fetching data for exercice:', exercice);
        const [sigResponse, cashflowResponse] = await Promise.all([
          apiService.getSIGDetail(exercice),
          apiService.getCashflow({ exercice, periode: 'mois' })
        ]);

        console.log('✅ SIG Response:', sigResponse);
        console.log('✅ Cashflow Response:', cashflowResponse);
        
        const sig = sigResponse?.data?.data || {};
        const cashflow = cashflowResponse?.data?.data || {};
        
        if (!sig || !Object.keys(sig).length) {
          throw new Error('SIG data is missing from response');
        }
        if (!cashflow || !Object.keys(cashflow).length) {
          console.warn('⚠️ Cashflow data is missing or empty');
          // Ne pas bloquer si cashflow est vide
        }

        setSig(sig);
        setCashflow(cashflow);
        
        console.log('✅ SIGPage data set successfully');
      } catch (err) {
        console.error('❌ Erreur chargement données:', err);
        console.error('❌ Error message:', err.message);
        setError('Erreur lors du chargement des données: ' + err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [exercice]);

  if (loading) {
    return <LoadingOverlay open={true} message="Chargement des SIG..." />;
  }

  if (error) {
    return <Alert severity="error">{error}</Alert>;
  }

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'EUR'
    }).format(value || 0);
  };

  const getColorForSIG = (value) => {
    return value >= 0 ? '#4caf50' : '#f44336';
  };

  // Préparer données pour graphique cascade
  const cascadeData = sig?.cascade ? Object.entries(sig?.cascade || {}).map(([key, val]) => ({
    name: key.replace(/_/g, '\n'),
    value: val.formatted?.valeur_brute || 0,
    color: val.formatted?.couleur || '#999'
  })) : [];

  // Préparer données périodes pour comparaison
  const periodeData = (cashflow?.par_periode || []).map(p => ({
    periode: (p.periode || '').split('-')[1] || p.periode || 'N/A',
    entrees: p.entrees,
    sorties: p.sorties,
    flux_net: p.flux_net
  })) || [];

  return (
    <ErrorBoundary>
      <Box>
        {/* Header */}
        <Box sx={{ mb: 3 }}>
          <Typography variant="h4" sx={{ mb: 2, fontWeight: 'bold' }}>
            📊 Soldes Intermédiaires de Gestion
          </Typography>
          
          {/* Sélecteur Exercice */}
          <Box sx={{ display: 'flex', gap: 2, alignItems: 'center' }}>
            <FormControl sx={{ minWidth: 200 }}>
              <InputLabel>Exercice</InputLabel>
              <Select
                value={exercice}
                label="Exercice"
                onChange={(e) => setExercice(e.target.value)}
              >
                {annees.map(year => (
                  <MenuItem key={year} value={year}>{year}</MenuItem>
                ))}
              </Select>
            </FormControl>
            <Chip 
              icon={<InfoIcon />} 
              label="Phase 5: SIG + Cashflow Analysis"
              color="primary"
              variant="outlined"
            />
          </Box>
        </Box>

        {/* Tabs */}
        <Tabs value={tabValue} onChange={(e, v) => setTabValue(v)} sx={{ mb: 3 }}>
          <Tab label="🎯 Cascade SIG" />
          <Tab label="📈 Graphiques" />
          <Tab label="📋 Détails" />
          <Tab label="💰 Comparaison Cashflow" />
        </Tabs>

        {/* Tab 1: Cascade SIG */}
        {tabValue === 0 && sig?.cascade && (
          <Box>
            <Grid container spacing={2}>
              {Object.entries(sig?.cascade || {}).map(([key, item]) => {
                const formatted = item.formatted || {};
                return (
                  <Grid item xs={12} sm={6} md={4} lg={3} key={key}>
                    <SIGCascadeCard
                      label={key.replace(/_/g, ' ').toUpperCase()}
                      value={formatted.valeur_brute || 0}
                      description={formatted.description || ''}
                      isPositive={formatted.est_positif || false}
                      color={formatted.couleur || '#999'}
                    />
                  </Grid>
                );
              })}
            </Grid>
          </Box>
        )}

        {/* Tab 2: Graphiques */}
        {tabValue === 1 && (
          <Grid container spacing={3}>
            {/* Graphique Cascade */}
            <Grid item xs={12}>
              <Paper sx={{ p: 3 }}>
                <Typography variant="h6" sx={{ mb: 2 }}>
                  📊 Cascade des SIG
                </Typography>
                <ResponsiveContainer width="100%" height={300}>
                  <BarChart data={cascadeData}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="name" />
                    <YAxis />
                    <Tooltip formatter={(value) => formatCurrency(value)} />
                    <Legend />
                    <Bar dataKey="value" fill="#1a237e" name="Montant" />
                  </BarChart>
                </ResponsiveContainer>
              </Paper>
            </Grid>

            {/* Graphique Flux Périodes */}
            {periodeData.length > 0 && (
              <Grid item xs={12}>
                <Paper sx={{ p: 3 }}>
                  <Typography variant="h6" sx={{ mb: 2 }}>
                    💰 Flux par Période (Entrées/Sorties)
                  </Typography>
                  <ResponsiveContainer width="100%" height={300}>
                    <ComposedChart data={periodeData}>
                      <CartesianGrid strokeDasharray="3 3" />
                      <XAxis dataKey="periode" />
                      <YAxis />
                      <Tooltip formatter={(value) => formatCurrency(value)} />
                      <Legend />
                      <Bar dataKey="entrees" fill="#82ca9d" name="Entrées" />
                      <Bar dataKey="sorties" fill="#8884d8" name="Sorties" />
                      <Line 
                        type="monotone" 
                        dataKey="flux_net" 
                        stroke="#ff7300" 
                        strokeWidth={2}
                        name="Flux Net"
                      />
                    </ComposedChart>
                  </ResponsiveContainer>
                </Paper>
              </Grid>
            )}
          </Grid>
        )}

        {/* Tab 3: Détails */}
        {tabValue === 2 && (
          <Paper sx={{ p: 3 }}>
            <Typography variant="h6" sx={{ mb: 2 }}>
              📋 Tableau Récapitulatif
            </Typography>
            <TableContainer>
              <Table size="small">
                <TableHead sx={{ backgroundColor: '#f5f5f5' }}>
                  <TableRow>
                    <TableCell><strong>Indicateur</strong></TableCell>
                    <TableCell align="right"><strong>Montant</strong></TableCell>
                    <TableCell align="center"><strong>Statut</strong></TableCell>
                    <TableCell><strong>Description</strong></TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {sig?.cascade && Object.entries(sig?.cascade || {}).map(([key, item]) => {
                    const formatted = item.formatted || {};
                    return (
                      <TableRow key={key} hover>
                        <TableCell sx={{ fontWeight: 'bold' }}>
                          {key.replace(/_/g, ' ')}
                        </TableCell>
                        <TableCell 
                          align="right"
                          sx={{ 
                            fontWeight: 'bold',
                            color: getColorForSIG(formatted.valeur_brute)
                          }}
                        >
                          {formatCurrency(formatted.valeur_brute || 0)}
                        </TableCell>
                        <TableCell align="center">
                          <Chip
                            label={formatted.est_positif ? 'Positif' : 'Négatif'}
                            color={formatted.est_positif ? 'success' : 'error'}
                            size="small"
                          />
                        </TableCell>
                        <TableCell>{formatted.description || '-'}</TableCell>
                      </TableRow>
                    );
                  })}
                </TableBody>
              </Table>
            </TableContainer>
          </Paper>
        )}

        {/* Tab 4: Comparaison Cashflow */}
        {tabValue === 3 && (
          <Grid container spacing={3}>
            {/* Stats globales */}
            <Grid item xs={12}>
              <Grid container spacing={2}>
                <Grid item xs={6} sm={3}>
                  <Card sx={{ backgroundColor: '#e8f5e9' }}>
                    <CardContent sx={{ textAlign: 'center' }}>
                      <Typography variant="caption" sx={{ color: '#666' }}>
                        Total Entrées
                      </Typography>
                      <Typography variant="h6" sx={{ color: '#2e7d32', fontWeight: 'bold', mt: 1 }}>
                        {formatCurrency(cashflow?.stats_globales?.total_entrees || 0)}
                      </Typography>
                    </CardContent>
                  </Card>
                </Grid>
                <Grid item xs={6} sm={3}>
                  <Card sx={{ backgroundColor: '#ffebee' }}>
                    <CardContent sx={{ textAlign: 'center' }}>
                      <Typography variant="caption" sx={{ color: '#666' }}>
                        Total Sorties
                      </Typography>
                      <Typography variant="h6" sx={{ color: '#c62828', fontWeight: 'bold', mt: 1 }}>
                        {formatCurrency(cashflow?.stats_globales?.total_sorties || 0)}
                      </Typography>
                    </CardContent>
                  </Card>
                </Grid>
                <Grid item xs={6} sm={3}>
                  <Card sx={{ backgroundColor: '#e3f2fd' }}>
                    <CardContent sx={{ textAlign: 'center' }}>
                      <Typography variant="caption" sx={{ color: '#666' }}>
                        Solde Net
                      </Typography>
                      <Typography variant="h6" sx={{ color: '#1565c0', fontWeight: 'bold', mt: 1 }}>
                        {formatCurrency(cashflow?.stats_globales?.flux_net_total || 0)}
                      </Typography>
                    </CardContent>
                  </Card>
                </Grid>
                <Grid item xs={6} sm={3}>
                  <Card sx={{ backgroundColor: '#f3e5f5' }}>
                    <CardContent sx={{ textAlign: 'center' }}>
                      <Typography variant="caption" sx={{ color: '#666' }}>
                        Écritures Total
                      </Typography>
                      <Typography variant="h6" sx={{ color: '#6a1b9a', fontWeight: 'bold', mt: 1 }}>
                        {cashflow?.par_periode?.reduce((sum, p) => sum + p.nb_ecritures, 0) || 0}
                      </Typography>
                    </CardContent>
                  </Card>
                </Grid>
              </Grid>
            </Grid>

            {/* Détail par journal */}
            <Grid item xs={12}>
              <Paper sx={{ p: 3 }}>
                <Typography variant="h6" sx={{ mb: 2 }}>
                  📊 Détail par Journal
                </Typography>
                <TableContainer>
                  <Table size="small">
                    <TableHead sx={{ backgroundColor: '#f5f5f5' }}>
                      <TableRow>
                        <TableCell><strong>Journal</strong></TableCell>
                        <TableCell align="right"><strong>Montant</strong></TableCell>
                        <TableCell align="right"><strong>Écritures</strong></TableCell>
                        <TableCell align="right"><strong>% Total</strong></TableCell>
                      </TableRow>
                    </TableHead>
                    <TableBody>
                      {(cashflow?.par_journal || []).map(j => {
                        const pct = cashflow?.stats_globales?.total_entrees > 0 
                          ? (j.entrees / cashflow.stats_globales.total_entrees * 100).toFixed(1)
                          : 0;
                        return (
                          <TableRow key={j.journal} hover>
                            <TableCell sx={{ fontWeight: 'bold' }}>{j.journal}</TableCell>
                            <TableCell align="right">{formatCurrency(j.entrees)}</TableCell>
                            <TableCell align="right">
                              <Chip label={j.nb_ecritures} size="small" />
                            </TableCell>
                            <TableCell align="right">{pct}%</TableCell>
                          </TableRow>
                        );
                      })}
                    </TableBody>
                  </Table>
                </TableContainer>
              </Paper>
            </Grid>
          </Grid>
        )}
      </Box>
    </ErrorBoundary>
  );
}
