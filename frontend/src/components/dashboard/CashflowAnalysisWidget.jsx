/**
 * CashflowAnalysisWidget - Analyse du cashflow
 * Affiche cashflow par période et journal
 * Source: GET /api/cashflow et GET /api/cashflow/detail/:journal
 */

import React, { useEffect, useState } from 'react';
import {
  Card,
  CardContent,
  Box,
  CircularProgress,
  Alert,
  Tabs,
  Tab,
  Grid,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Chip,
  ToggleButton,
  ToggleButtonGroup
} from '@mui/material';
import {
  BarChart,
  Bar,
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell
} from 'recharts';
import apiService from '../../services/api';

export default function CashflowAnalysisWidget({ exercice }) {
  const [cashflow, setCashflow] = useState(null);
  const [selectedJournal, setSelectedJournal] = useState('VE');
  const [journalDetail, setJournalDetail] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [tabValue, setTabValue] = useState(0);
  const [periode, setPeriode] = useState('mois');

  useEffect(() => {
    const fetchCashflow = async () => {
      try {
        setLoading(true);
        setError(null);
        const response = await apiService.getCashflow({
          exercice,
          periode
        });
        setCashflow(response.data);
      } catch (err) {
        console.error('Erreur chargement cashflow:', err);
        setError('Erreur lors du chargement du cashflow');
      } finally {
        setLoading(false);
      }
    };

    if (exercice) {
      fetchCashflow();
    }
  }, [exercice, periode]);

  // Charger détail journal
  useEffect(() => {
    const fetchJournalDetail = async () => {
      try {
        const response = await apiService.getCashflowDetail(selectedJournal, {
          exercice
        });
        setJournalDetail(response.data);
      } catch (err) {
        console.error('Erreur chargement journal detail:', err);
      }
    };

    if (exercice && selectedJournal) {
      fetchJournalDetail();
    }
  }, [exercice, selectedJournal]);

  if (loading) {
    return (
      <Card>
        <CardContent sx={{ textAlign: 'center', py: 4 }}>
          <CircularProgress />
        </CardContent>
      </Card>
    );
  }

  if (error) {
    return (
      <Card>
        <CardContent>
          <Alert severity="error">{error}</Alert>
        </CardContent>
      </Card>
    );
  }

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'EUR'
    }).format(value);
  };

  const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8', '#82CA9D', '#FFC658'];

  // Préparer données pour graphique périodes
  const periodeData = cashflow?.par_periode?.map(p => ({
    periode: p.periode.split('-')[1] || p.periode,
    entrees: p.entrees,
    sorties: p.sorties,
    comptes: p.nb_comptes,
    ecritures: p.nb_ecritures
  })) || [];

  // Préparer données pour graphique journaux
  const journalData = cashflow?.par_journal?.map(j => ({
    journal: j.journal,
    montant: j.entrees,
    ecritures: j.nb_ecritures
  })) || [];

  return (
    <Card sx={{ mb: 3 }}>
      <CardContent>
        <Box sx={{ mb: 3 }}>
          <h3>💰 Analyse du Cashflow</h3>
          
          {/* Stats globales */}
          <Grid container spacing={2} sx={{ mb: 3 }}>
            <Grid item xs={6} sm={3}>
              <Paper sx={{ p: 2, textAlign: 'center', backgroundColor: '#e8f5e9' }}>
                <Box sx={{ fontSize: '0.85rem', color: '#666' }}>Total Entrées</Box>
                <Box sx={{ fontSize: '1.1rem', fontWeight: 'bold', color: '#2e7d32' }}>
                  {formatCurrency(cashflow?.stats_globales?.total_entrees || 0)}
                </Box>
              </Paper>
            </Grid>
            <Grid item xs={6} sm={3}>
              <Paper sx={{ p: 2, textAlign: 'center', backgroundColor: '#ffebee' }}>
                <Box sx={{ fontSize: '0.85rem', color: '#666' }}>Total Sorties</Box>
                <Box sx={{ fontSize: '1.1rem', fontWeight: 'bold', color: '#c62828' }}>
                  {formatCurrency(cashflow?.stats_globales?.total_sorties || 0)}
                </Box>
              </Paper>
            </Grid>
            <Grid item xs={6} sm={3}>
              <Paper sx={{ p: 2, textAlign: 'center', backgroundColor: '#e3f2fd' }}>
                <Box sx={{ fontSize: '0.85rem', color: '#666' }}>Solde Net</Box>
                <Box sx={{ fontSize: '1.1rem', fontWeight: 'bold', color: '#1565c0' }}>
                  {formatCurrency(cashflow?.stats_globales?.flux_net_total || 0)}
                </Box>
              </Paper>
            </Grid>
            <Grid item xs={6} sm={3}>
              <Paper sx={{ p: 2, textAlign: 'center', backgroundColor: '#f3e5f5' }}>
                <Box sx={{ fontSize: '0.85rem', color: '#666' }}>Écritures</Box>
                <Box sx={{ fontSize: '1.1rem', fontWeight: 'bold', color: '#6a1b9a' }}>
                  {cashflow?.par_periode?.reduce((sum, p) => sum + p.nb_ecritures, 0) || 0}
                </Box>
              </Paper>
            </Grid>
          </Grid>

          {/* Options */}
          <Box sx={{ display: 'flex', gap: 2, mb: 3 }}>
            <ToggleButtonGroup
              value={periode}
              exclusive
              onChange={(e, newPeriode) => newPeriode && setPeriode(newPeriode)}
              size="small"
            >
              <ToggleButton value="mois">Mois</ToggleButton>
              <ToggleButton value="trimestre">Trimestre</ToggleButton>
            </ToggleButtonGroup>
          </Box>
        </Box>

        {/* Tabs */}
        <Tabs value={tabValue} onChange={(e, v) => setTabValue(v)} sx={{ mb: 2 }}>
          <Tab label="📈 Par Période" />
          <Tab label="📊 Par Journal" />
          <Tab label="💼 Détail Journal" />
          <Tab label="🎯 Top Comptes" />
        </Tabs>

        {/* Tab: Par Période */}
        {tabValue === 0 && (
          <Box>
            <ResponsiveContainer width="100%" height={300}>
              <BarChart data={periodeData}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="periode" />
                <YAxis />
                <Tooltip formatter={(value) => formatCurrency(value)} />
                <Legend />
                <Bar dataKey="entrees" fill="#82ca9d" name="Entrées" />
                <Bar dataKey="sorties" fill="#8884d8" name="Sorties" />
              </BarChart>
            </ResponsiveContainer>
          </Box>
        )}

        {/* Tab: Par Journal */}
        {tabValue === 1 && (
          <Grid container spacing={2}>
            <Grid item xs={12} md={6}>
              <ResponsiveContainer width="100%" height={300}>
                <PieChart>
                  <Pie
                    data={journalData}
                    cx="50%"
                    cy="50%"
                    labelLine={false}
                    label={({ journal, percent }) => `${journal} ${(percent * 100).toFixed(0)}%`}
                    outerRadius={100}
                    fill="#8884d8"
                    dataKey="montant"
                  >
                    {journalData.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                    ))}
                  </Pie>
                  <Tooltip formatter={(value) => formatCurrency(value)} />
                </PieChart>
              </ResponsiveContainer>
            </Grid>
            <Grid item xs={12} md={6}>
              <TableContainer component={Paper}>
                <Table size="small">
                  <TableHead sx={{ backgroundColor: '#f5f5f5' }}>
                    <TableRow>
                      <TableCell><strong>Journal</strong></TableCell>
                      <TableCell align="right"><strong>Montant</strong></TableCell>
                      <TableCell align="right"><strong>Écritures</strong></TableCell>
                    </TableRow>
                  </TableHead>
                  <TableBody>
                    {cashflow?.par_journal?.map(j => (
                      <TableRow
                        key={j.journal}
                        hover
                        sx={{ cursor: 'pointer' }}
                        onClick={() => {
                          setSelectedJournal(j.journal);
                          setTabValue(2);
                        }}
                      >
                        <TableCell sx={{ fontWeight: 'bold' }}>{j.journal}</TableCell>
                        <TableCell align="right">{formatCurrency(j.entrees)}</TableCell>
                        <TableCell align="right">
                          <Chip label={j.nb_ecritures} size="small" />
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </TableContainer>
            </Grid>
          </Grid>
        )}

        {/* Tab: Détail Journal */}
        {tabValue === 2 && journalDetail && (
          <Box>
            <Box sx={{ mb: 3, p: 2, backgroundColor: '#f5f5f5', borderRadius: 1 }}>
              <h4>Journal: {selectedJournal}</h4>
              <Grid container spacing={2}>
                <Grid item xs={6} sm={3}>
                  <Box sx={{ fontSize: '0.85rem', color: '#666' }}>Écritures</Box>
                  <Box sx={{ fontSize: '1.1rem', fontWeight: 'bold' }}>
                    {journalDetail.stats.nb_ecritures}
                  </Box>
                </Grid>
                <Grid item xs={6} sm={3}>
                  <Box sx={{ fontSize: '0.85rem', color: '#666' }}>Jours Actifs</Box>
                  <Box sx={{ fontSize: '1.1rem', fontWeight: 'bold' }}>
                    {journalDetail.stats.nb_jours_actifs}
                  </Box>
                </Grid>
                <Grid item xs={6} sm={3}>
                  <Box sx={{ fontSize: '0.85rem', color: '#666' }}>Total Débit</Box>
                  <Box sx={{ fontSize: '1.1rem', fontWeight: 'bold' }}>
                    {formatCurrency(journalDetail.stats.total_debit)}
                  </Box>
                </Grid>
                <Grid item xs={6} sm={3}>
                  <Box sx={{ fontSize: '0.85rem', color: '#666' }}>Solde</Box>
                  <Box sx={{ fontSize: '1.1rem', fontWeight: 'bold' }}>
                    {formatCurrency(journalDetail.stats.solde)}
                  </Box>
                </Grid>
              </Grid>
            </Box>

            <h4>Top 5 Comptes</h4>
            <TableContainer component={Paper}>
              <Table size="small">
                <TableHead sx={{ backgroundColor: '#f5f5f5' }}>
                  <TableRow>
                    <TableCell><strong>Compte</strong></TableCell>
                    <TableCell><strong>Libellé</strong></TableCell>
                    <TableCell align="right"><strong>Débit</strong></TableCell>
                    <TableCell align="right"><strong>Crédit</strong></TableCell>
                    <TableCell align="right"><strong>Solde</strong></TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {journalDetail.top_comptes?.slice(0, 5).map(c => (
                    <TableRow key={c.compte}>
                      <TableCell sx={{ fontWeight: 'bold' }}>{c.compte}</TableCell>
                      <TableCell>{c.libelle}</TableCell>
                      <TableCell align="right">{formatCurrency(c.debit)}</TableCell>
                      <TableCell align="right">{formatCurrency(c.credit)}</TableCell>
                      <TableCell align="right" sx={{ fontWeight: 'bold' }}>
                        {formatCurrency(c.solde)}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </TableContainer>
          </Box>
        )}

        {/* Tab: Top Comptes */}
        {tabValue === 3 && journalDetail && (
          <TableContainer component={Paper}>
            <Table size="small">
              <TableHead sx={{ backgroundColor: '#f5f5f5' }}>
                <TableRow>
                  <TableCell><strong>Compte</strong></TableCell>
                  <TableCell><strong>Libellé</strong></TableCell>
                  <TableCell align="right"><strong>Débit</strong></TableCell>
                  <TableCell align="right"><strong>Crédit</strong></TableCell>
                  <TableCell align="right"><strong>Solde</strong></TableCell>
                  <TableCell align="center"><strong>Écritures</strong></TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {journalDetail.top_comptes?.map(c => (
                  <TableRow key={c.compte}>
                    <TableCell sx={{ fontWeight: 'bold' }}>{c.compte}</TableCell>
                    <TableCell>{c.libelle}</TableCell>
                    <TableCell align="right">{formatCurrency(c.debit)}</TableCell>
                    <TableCell align="right">{formatCurrency(c.credit)}</TableCell>
                    <TableCell align="right" sx={{ fontWeight: 'bold' }}>
                      {formatCurrency(c.solde)}
                    </TableCell>
                    <TableCell align="center">
                      <Chip label={c.nb_ecritures} size="small" />
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </TableContainer>
        )}
      </CardContent>
    </Card>
  );
}
