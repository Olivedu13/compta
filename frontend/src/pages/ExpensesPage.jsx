/**
 * ExpensesPage - Deep Dive Charges (Classe 6)
 * Segmentation fournisseur, doublons, variations atypiques, évolution mensuelle
 * Fintech Premium Style
 */

import React, { useEffect, useState, useMemo } from 'react';
import {
  Box, Typography, Grid, Card, CardContent, Chip, Alert,
  Table, TableHead, TableBody, TableRow, TableCell,
  FormControl, InputLabel, Select, MenuItem, Tabs, Tab,
  LinearProgress, Tooltip, Paper, useTheme, useMediaQuery
} from '@mui/material';
import {
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip as RechartsTooltip,
  ResponsiveContainer, PieChart, Pie, Cell, Legend, LineChart, Line, Area, AreaChart
} from 'recharts';
import {
  Warning as WarningIcon,
  ContentCopy as DuplicateIcon,
  TrendingDown as TrendingDownIcon,
  Category as CategoryIcon,
  Business as BusinessIcon,
  CalendarMonth as CalendarIcon
} from '@mui/icons-material';
import apiService from '../services/api';
import { LoadingOverlay } from '../components/common';

const COLORS = ['#4318FF', '#01B574', '#FFB547', '#EE5D50', '#4299E1', '#868CFF', '#F0A04B', '#38B2AC', '#ED64A6', '#9F7AEA'];

const fmt = (v) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(v);
const fmtPct = (v) => `${(v * 100).toFixed(1)}%`;

function TabPanel({ children, value, index }) {
  return value === index ? <Box sx={{ pt: 3 }}>{children}</Box> : null;
}

export default function ExpensesPage() {
  const [exercice, setExercice] = useState(null);
  const [annees, setAnnees] = useState([]);
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [tab, setTab] = useState(0);
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));

  useEffect(() => {
    const loadAnnees = async () => {
      try {
        const response = await apiService.getAnnees();
        const years = Array.isArray(response.data.data) ? response.data.data : [];
        if (years.length > 0) { setAnnees(years); setExercice(years[0]); }
        else { setAnnees([2024]); setExercice(2024); }
      } catch { setAnnees([2024]); setExercice(2024); }
    };
    loadAnnees();
  }, []);

  useEffect(() => {
    if (!exercice) return;
    const fetch = async () => {
      try {
        setLoading(true); setError(null);
        const res = await apiService.getExpensesDeepDive(exercice);
        setData(res.data?.data || null);
      } catch (err) {
        setError('Erreur chargement: ' + err.message);
      } finally {
        setLoading(false);
      }
    };
    fetch();
  }, [exercice]);

  if (loading) return <LoadingOverlay open message="Analyse des charges en cours..." />;
  if (error) return <Alert severity="error">{error}</Alert>;
  if (!data) return <Alert severity="info">Aucune donnée de charges disponible</Alert>;

  const { categories, top_comptes, fournisseurs, evolution_mensuelle, variations_atypiques, doublons_potentiels, charges_fixes_variables } = data;
  const totalCharges = categories?.reduce((s, c) => s + Math.abs(c.montant), 0) || 0;

  return (
    <Box>
      {/* Header */}
      <Box sx={{ mb: 4, display: 'flex', flexWrap: 'wrap', alignItems: 'center', justifyContent: 'space-between', gap: 2 }}>
        <Box>
          <Typography variant="h3" sx={{ fontWeight: 700 }}>Deep Dive Charges</Typography>
          <Typography variant="body2">Analyse granulaire des dépenses — Classe 6 PCG</Typography>
        </Box>
        <FormControl size="small" sx={{ minWidth: 140 }}>
          <InputLabel>Exercice</InputLabel>
          <Select value={exercice} label="Exercice" onChange={(e) => setExercice(e.target.value)}>
            {annees.map(y => <MenuItem key={y} value={y}>{y}</MenuItem>)}
          </Select>
        </FormControl>
      </Box>

      {/* KPI Summary Cards */}
      <Grid container spacing={3} sx={{ mb: 4 }}>
        <Grid item xs={6} md={3}>
          <Card>
            <CardContent sx={{ p: 2.5 }}>
              <Typography variant="overline">Total Charges</Typography>
              <Typography variant="h4" sx={{ fontWeight: 700, mt: 0.5 }}>{fmt(totalCharges)}</Typography>
            </CardContent>
          </Card>
        </Grid>
        <Grid item xs={6} md={3}>
          <Card>
            <CardContent sx={{ p: 2.5 }}>
              <Typography variant="overline">Catégories</Typography>
              <Typography variant="h4" sx={{ fontWeight: 700, mt: 0.5 }}>{categories?.length || 0}</Typography>
            </CardContent>
          </Card>
        </Grid>
        <Grid item xs={6} md={3}>
          <Card>
            <CardContent sx={{ p: 2.5 }}>
              <Typography variant="overline" sx={{ color: 'warning.main' }}>Variations Atypiques</Typography>
              <Typography variant="h4" sx={{ fontWeight: 700, mt: 0.5, color: variations_atypiques?.length > 0 ? 'warning.main' : 'success.main' }}>
                {variations_atypiques?.length || 0}
              </Typography>
            </CardContent>
          </Card>
        </Grid>
        <Grid item xs={6} md={3}>
          <Card>
            <CardContent sx={{ p: 2.5 }}>
              <Typography variant="overline" sx={{ color: 'error.main' }}>Doublons Détectés</Typography>
              <Typography variant="h4" sx={{ fontWeight: 700, mt: 0.5, color: doublons_potentiels?.length > 0 ? 'error.main' : 'success.main' }}>
                {doublons_potentiels?.length || 0}
              </Typography>
            </CardContent>
          </Card>
        </Grid>
      </Grid>

      {/* Tabs */}
      <Paper sx={{ mb: 3 }}>
        <Tabs value={tab} onChange={(_, v) => setTab(v)} variant={isMobile ? 'scrollable' : 'standard'} scrollButtons="auto"
          sx={{ px: 2, '& .MuiTab-root': { textTransform: 'none', fontWeight: 600, minHeight: 54 } }}>
          <Tab icon={<CategoryIcon />} iconPosition="start" label="Catégories" />
          <Tab icon={<BusinessIcon />} iconPosition="start" label="Fournisseurs" />
          <Tab icon={<CalendarIcon />} iconPosition="start" label="Évolution" />
          <Tab icon={<WarningIcon />} iconPosition="start" label={`Alertes (${(variations_atypiques?.length || 0) + (doublons_potentiels?.length || 0)})`} />
        </Tabs>
      </Paper>

      {/* Tab 0: Catégories */}
      <TabPanel value={tab} index={0}>
        <Grid container spacing={3}>
          {/* Pie Chart */}
          <Grid item xs={12} md={5}>
            <Card sx={{ height: '100%' }}>
              <CardContent>
                <Typography variant="h6" gutterBottom>Répartition par catégorie</Typography>
                <ResponsiveContainer width="100%" height={350}>
                  <PieChart>
                    <Pie data={(categories || []).map(c => ({ ...c, montant: Math.abs(c.montant) }))} dataKey="montant" nameKey="label" cx="50%" cy="50%"
                      outerRadius={120} innerRadius={60} paddingAngle={2} label={({ label, percent }) => `${(percent * 100).toFixed(0)}%`}>
                      {(categories || []).map((_, i) => <Cell key={i} fill={COLORS[i % COLORS.length]} />)}
                    </Pie>
                    <Legend />
                    <RechartsTooltip formatter={(v) => fmt(v)} />
                  </PieChart>
                </ResponsiveContainer>
              </CardContent>
            </Card>
          </Grid>

          {/* Category table */}
          <Grid item xs={12} md={7}>
            <Card>
              <CardContent>
                <Typography variant="h6" gutterBottom>Détail par catégorie PCG</Typography>
                <Table size="small">
                  <TableHead>
                    <TableRow>
                      <TableCell>Classe</TableCell>
                      <TableCell>Catégorie</TableCell>
                      <TableCell align="right">Montant</TableCell>
                      <TableCell align="right">% Total</TableCell>
                      <TableCell sx={{ width: 120 }}>Poids</TableCell>
                    </TableRow>
                  </TableHead>
                  <TableBody>
                    {(categories || []).map((cat, i) => {
                      const pct = totalCharges > 0 ? (Math.abs(cat.montant) / totalCharges) : 0;
                      return (
                        <TableRow key={cat.sous_classe} hover>
                          <TableCell><Chip size="small" label={cat.sous_classe} sx={{ backgroundColor: COLORS[i % COLORS.length], color: '#fff', fontWeight: 700 }} /></TableCell>
                          <TableCell sx={{ fontWeight: 500 }}>{cat.label}</TableCell>
                          <TableCell align="right" sx={{ fontWeight: 600, fontFamily: 'monospace' }}>{fmt(Math.abs(cat.montant))}</TableCell>
                          <TableCell align="right">{fmtPct(pct)}</TableCell>
                          <TableCell><LinearProgress variant="determinate" value={pct * 100} sx={{ height: 6, borderRadius: 3 }} /></TableCell>
                        </TableRow>
                      );
                    })}
                  </TableBody>
                </Table>

                {/* Fixed vs Variable */}
                {charges_fixes_variables && (
                  <Box sx={{ mt: 3, p: 2, backgroundColor: '#F4F7FE', borderRadius: 2 }}>
                    <Typography variant="subtitle2" gutterBottom>Charges Fixes vs Variables</Typography>
                    <Grid container spacing={2}>
                      <Grid item xs={6}>
                        <Typography variant="caption">Fixes (loyers, assurances...)</Typography>
                        <Typography variant="h6" sx={{ fontWeight: 700 }}>{fmt(Math.abs(charges_fixes_variables.fixes || 0))}</Typography>
                      </Grid>
                      <Grid item xs={6}>
                        <Typography variant="caption">Variables (achats, sous-traitance...)</Typography>
                        <Typography variant="h6" sx={{ fontWeight: 700 }}>{fmt(Math.abs(charges_fixes_variables.variables || 0))}</Typography>
                      </Grid>
                    </Grid>
                  </Box>
                )}
              </CardContent>
            </Card>
          </Grid>
        </Grid>
      </TabPanel>

      {/* Tab 1: Fournisseurs */}
      <TabPanel value={tab} index={1}>
        <Card>
          <CardContent>
            <Typography variant="h6" gutterBottom>Top Fournisseurs (par volume de charges)</Typography>
            <Table size="small">
              <TableHead>
                <TableRow>
                  <TableCell>#</TableCell>
                  <TableCell>Fournisseur</TableCell>
                  <TableCell align="right">Montant Total</TableCell>
                  <TableCell align="right">Nb Pièces</TableCell>
                  <TableCell align="right">Ticket Moyen</TableCell>
                  <TableCell align="right">% Total</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {(fournisseurs || []).slice(0, 25).map((f, i) => (
                  <TableRow key={i} hover>
                    <TableCell>{i + 1}</TableCell>
                    <TableCell sx={{ fontWeight: 500 }}>
                      {f.lib_tiers || f.numero_tiers || 'N/A'}
                      {f.numero_tiers && <Typography variant="caption" sx={{ ml: 1 }}>({f.numero_tiers})</Typography>}
                    </TableCell>
                    <TableCell align="right" sx={{ fontWeight: 600, fontFamily: 'monospace' }}>{fmt(Math.abs(f.montant_total))}</TableCell>
                    <TableCell align="right">{f.nb_pieces || '-'}</TableCell>
                    <TableCell align="right" sx={{ fontFamily: 'monospace' }}>{f.montant_total && f.nb_pieces ? fmt(Math.abs(f.montant_total / f.nb_pieces)) : '-'}</TableCell>
                    <TableCell align="right">{totalCharges > 0 ? fmtPct(Math.abs(f.montant_total) / totalCharges) : '-'}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </TabPanel>

      {/* Tab 2: Evolution Mensuelle */}
      <TabPanel value={tab} index={2}>
        <Card>
          <CardContent>
            <Typography variant="h6" gutterBottom>Évolution mensuelle des charges</Typography>
            <ResponsiveContainer width="100%" height={400}>
              <AreaChart data={evolution_mensuelle || []}>
                <defs>
                  <linearGradient id="gradCharges" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#4318FF" stopOpacity={0.15} />
                    <stop offset="95%" stopColor="#4318FF" stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke="#E2E8F0" />
                <XAxis dataKey="mois" tick={{ fontSize: 12 }} />
                <YAxis tickFormatter={(v) => `${(v / 1000).toFixed(0)}k`} tick={{ fontSize: 12 }} />
                <RechartsTooltip formatter={(v) => fmt(Math.abs(v))} />
                <Area type="monotone" dataKey="total" stroke="#4318FF" fill="url(#gradCharges)" strokeWidth={2.5} name="Total Charges" />
              </AreaChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>

        {/* Monthly breakdown table */}
        {evolution_mensuelle && evolution_mensuelle.length > 0 && (
          <Card sx={{ mt: 3 }}>
            <CardContent>
              <Typography variant="h6" gutterBottom>Détail mensuel par catégorie</Typography>
              <Box sx={{ overflowX: 'auto' }}>
                <Table size="small">
                  <TableHead>
                    <TableRow>
                      <TableCell>Mois</TableCell>
                      <TableCell align="right">Total</TableCell>
                      {Object.keys(evolution_mensuelle[0]?.detail || {}).slice(0, 6).map(k => (
                        <TableCell key={k} align="right">{k}</TableCell>
                      ))}
                    </TableRow>
                  </TableHead>
                  <TableBody>
                    {evolution_mensuelle.map((m, i) => (
                      <TableRow key={i} hover>
                        <TableCell sx={{ fontWeight: 600 }}>{m.mois}</TableCell>
                        <TableCell align="right" sx={{ fontWeight: 700, fontFamily: 'monospace' }}>{fmt(Math.abs(m.total))}</TableCell>
                        {Object.keys(m.detail || {}).slice(0, 6).map(k => (
                          <TableCell key={k} align="right" sx={{ fontFamily: 'monospace' }}>{fmt(Math.abs(m.detail[k] || 0))}</TableCell>
                        ))}
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </Box>
            </CardContent>
          </Card>
        )}
      </TabPanel>

      {/* Tab 3: Alertes */}
      <TabPanel value={tab} index={3}>
        <Grid container spacing={3}>
          {/* Variations Atypiques */}
          <Grid item xs={12} md={6}>
            <Card>
              <CardContent>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 2 }}>
                  <WarningIcon sx={{ color: 'warning.main' }} />
                  <Typography variant="h6">Variations Atypiques (&gt;50%)</Typography>
                </Box>
                {(!variations_atypiques || variations_atypiques.length === 0) ? (
                  <Alert severity="success" sx={{ borderRadius: 2 }}>Aucune variation atypique détectée</Alert>
                ) : (
                  <Table size="small">
                    <TableHead>
                      <TableRow>
                        <TableCell>Compte</TableCell>
                        <TableCell>Mois</TableCell>
                        <TableCell align="right">Variation</TableCell>
                        <TableCell align="right">Montant</TableCell>
                      </TableRow>
                    </TableHead>
                    <TableBody>
                      {variations_atypiques.map((v, i) => (
                        <TableRow key={i} hover>
                          <TableCell sx={{ fontSize: '0.8rem' }}>{v.compte || v.sous_classe}</TableCell>
                          <TableCell>{v.mois}</TableCell>
                          <TableCell align="right">
                            <Chip size="small" label={`${v.variation > 0 ? '+' : ''}${(v.variation * 100).toFixed(0)}%`}
                              sx={{ backgroundColor: v.variation > 0 ? '#FFF3E0' : '#E8F5E9', color: v.variation > 0 ? '#E65100' : '#1B5E20', fontWeight: 700 }} />
                          </TableCell>
                          <TableCell align="right" sx={{ fontFamily: 'monospace' }}>{fmt(Math.abs(v.montant || 0))}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                )}
              </CardContent>
            </Card>
          </Grid>

          {/* Doublons */}
          <Grid item xs={12} md={6}>
            <Card>
              <CardContent>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 2 }}>
                  <DuplicateIcon sx={{ color: 'error.main' }} />
                  <Typography variant="h6">Doublons Potentiels</Typography>
                </Box>
                {(!doublons_potentiels || doublons_potentiels.length === 0) ? (
                  <Alert severity="success" sx={{ borderRadius: 2 }}>Aucun doublon de facture détecté</Alert>
                ) : (
                  <Table size="small">
                    <TableHead>
                      <TableRow>
                        <TableCell>Pièce Réf</TableCell>
                        <TableCell align="right">Montant</TableCell>
                        <TableCell align="right">Occurrences</TableCell>
                        <TableCell>Risque</TableCell>
                      </TableRow>
                    </TableHead>
                    <TableBody>
                      {doublons_potentiels.map((d, i) => (
                        <TableRow key={i} hover>
                          <TableCell sx={{ fontWeight: 500 }}>{d.piece_ref}</TableCell>
                          <TableCell align="right" sx={{ fontFamily: 'monospace' }}>{fmt(Math.abs(d.montant))}</TableCell>
                          <TableCell align="right">{d.occurrences}x</TableCell>
                          <TableCell>
                            <Chip size="small" label={d.occurrences >= 3 ? 'Élevé' : 'Moyen'}
                              color={d.occurrences >= 3 ? 'error' : 'warning'} />
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                )}
              </CardContent>
            </Card>
          </Grid>
        </Grid>
      </TabPanel>
    </Box>
  );
}
