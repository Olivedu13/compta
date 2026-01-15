/**
 * Dashboard Analytique Avancé - Senior Level KPIs
 */

import React, { useEffect, useState } from 'react';
import {
  Box,
  Paper,
  Typography,
  Grid,
  Card,
  CardContent,
  LinearProgress,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Chip
} from '@mui/material';
import {
  LineChart,
  Line,
  BarChart,
  Bar,
  PieChart,
  Pie,
  Cell,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Legend,
  ScatterChart,
  Scatter
} from 'recharts';
import { apiService } from '../services/api';

const COLORS = ['#4caf50', '#ff9800', '#f44336', '#2196f3', '#9c27b0', '#00bcd4'];

const formatCurrency = (value) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value);
};

const formatShortCurrency = (value) => {
  const abs = Math.abs(value);
  if (abs >= 1000000) {
    return `${(value / 1000000).toFixed(1)}M€`;
  } else if (abs >= 1000) {
    return `${(value / 1000).toFixed(0)}k€`;
  }
  return `${Math.ceil(value)}€`;
};

const KPIMetric = ({ label, value, unit = '', color = '#2196f3', trend = null, alert = false }) => {
  return (
    <Card sx={{ 
      backgroundColor: alert ? '#ffebee' : '#f5f5f5',
      borderLeft: `4px solid ${alert ? '#f44336' : color}`
    }}>
      <CardContent>
        <Typography color="textSecondary" sx={{ fontSize: 12, mb: 0.5 }}>
          {label}
        </Typography>
        <Typography variant="h5" sx={{ color, fontWeight: 'bold', mb: 1 }}>
          {typeof value === 'number' 
            ? (value >= 1000 ? (value / 1000).toFixed(1) + 'k' : value.toFixed(1))
            : value}
          {unit && ` ${unit}`}
        </Typography>
        {trend && (
          <Typography variant="caption" sx={{ 
            color: trend > 0 ? '#4caf50' : '#f44336',
            fontWeight: 'bold'
          }}>
            {trend > 0 ? '↑' : '↓'} {Math.abs(trend).toFixed(1)}%
          </Typography>
        )}
        {alert && (
          <Typography variant="caption" sx={{ color: '#f44336', display: 'block', mt: 1 }}>
            ⚠️ Attention requise
          </Typography>
        )}
      </CardContent>
    </Card>
  );
};

const AdvancedAnalytics = ({ exercice }) => {
  const [analytics, setAnalytics] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchAnalytics = async () => {
      try {
        setError(null);
        const response = await apiService.getAnalyticsAdvanced(exercice);
        setAnalytics(response.data);
      } catch (err) {
        console.error('Erreur chargement analytics:', err);
        setError(err.response?.data?.error || 'Erreur lors du chargement des données');
      } finally {
        setLoading(false);
      }
    };

    fetchAnalytics();
  }, [exercice]);

  if (loading) {
    return <Typography>Chargement données avancées...</Typography>;
  }

  if (error) {
    return (
      <Paper sx={{ p: 3, backgroundColor: '#ffebee', borderLeft: '4px solid #f44336' }}>
        <Typography variant="h6" sx={{ color: '#f44336', mb: 1 }}>
          ⚠️ Erreur chargement analytics
        </Typography>
        <Typography color="textSecondary">
          {error}
        </Typography>
      </Paper>
    );
  }

  if (!analytics) {
    return <Typography>Aucune donnée disponible pour cet exercice</Typography>;
  }

  const {
    ca,
    couts,
    top_clients,
    top_fournisseurs,
    tresorerie,
    marges,
    ratios_exploitation,
    ratios_solvabilite,
    cycles_tresorerie,
    clients_encours,
    fournisseurs_encours
  } = analytics;

  // Préparer données pour les graphiques - Convertir les CA en positif et en nombres
  const caMensuelClean = (ca.mensuel || []).map(m => ({
    ...m,
    ca: Math.abs(parseFloat(m.ca || 0))
  }));
  
  const caTrimestrielClean = (ca.trimestriel || []).map(t => ({
    ...t,
    ca: Math.abs(parseFloat(t.ca || 0))
  }));

  // Clients & Fournisseurs
  const topClientsClean = (top_clients || [])
    .filter(c => c && c.client && typeof c.montant !== 'undefined')
    .map(c => ({
      ...c,
      montant: Math.abs(parseFloat(c.montant))
    }));

  const topFournisseursClean = (top_fournisseurs || [])
    .filter(f => f && f.fournisseur && typeof f.montant !== 'undefined')
    .map(f => ({
      ...f,
      montant: Math.abs(parseFloat(f.montant))
    }));

  // Préparer données pour pie chart des coûts (acronymes français)
  const coutsPieData = [
    { name: 'Mat.', value: Math.abs(couts?.matiere || 0) },
    { name: 'Sal.', value: Math.abs(couts?.salaires || 0) },
    { name: 'Autres', value: Math.abs((couts?.autres_frais || 0) + (couts?.transport || 0) + (couts?.frais_banc || 0)) }
  ].filter(d => d.value > 0);

  const clientsFournisseurData = [
    { 
      name: 'Clients (DSO)', 
      jours: cycles_tresorerie.dso_clients,
      valeur: clients_encours,
      fill: '#2196f3'
    },
    { 
      name: 'Stock (Jours)', 
      jours: cycles_tresorerie.jours_stock,
      valeur: couts.matiere,
      fill: '#ff9800'
    },
    { 
      name: 'Fournisseurs (DPO)', 
      jours: cycles_tresorerie.dpo_fournisseurs,
      valeur: fournisseurs_encours,
      fill: '#4caf50'
    }
  ];

  return (
    <Box>
      {/* ============================================
          1. OVERVIEW FINANCIER 
          ============================================ */}
      <Typography variant="h5" sx={{ mb: 3, mt: 4, fontWeight: 'bold' }}>
        📊 Vue d'ensemble Financière
      </Typography>
      
      <Grid container spacing={2} sx={{ mb: 4 }}>
        <Grid item xs={12} sm={6} md={3}>
          <KPIMetric 
            label="Chiffre d'Affaires" 
            value={Math.abs(ca.total) / 1000000}
            unit="M€"
            color="#4caf50"
          />
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <KPIMetric 
            label="Marge Exploitation" 
            value={Math.abs(marges.ratio_marge_exploitation)}
            unit="%"
            color={marges.ratio_marge_exploitation > 15 ? '#4caf50' : marges.ratio_marge_exploitation > 5 ? '#ff9800' : '#f44336'}
          />
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <KPIMetric 
            label="Trésorerie Nette" 
            value={tresorerie.tresorerie_nette / 1000}
            unit="k€"
            color={tresorerie.tresorerie_nette > 0 ? '#4caf50' : '#f44336'}
          />
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <KPIMetric 
            label="Endettement" 
            value={Math.abs(ratios_solvabilite.endettement)}
            unit="x"
            alert={ratios_solvabilite.endettement > 2}
            color={ratios_solvabilite.endettement <= 1 ? '#4caf50' : ratios_solvabilite.endettement <= 2 ? '#ff9800' : '#f44336'}
          />
        </Grid>
      </Grid>

      {/* ============================================
          2. ANALYSE SAISONNALITÉ & CA 
          ============================================ */}
      <Typography variant="h5" sx={{ mb: 2, mt: 4, fontWeight: 'bold' }}>
        📈 Saisonnalité & Tendance CA
      </Typography>
      <Grid container spacing={2} sx={{ mb: 4 }}>
        {/* CA Mensuel */}
        <Grid item xs={12} md={8}>
          <Paper sx={{ p: 3 }}>
            <Typography variant="body2" color="textSecondary" sx={{ mb: 2 }}>
              Chiffre d'Affaires Mensuel - Identification saisonnalité T4
            </Typography>
            <ResponsiveContainer width="100%" height={300}>
              <LineChart data={caMensuelClean}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="mois" />
                <YAxis />
                <Tooltip formatter={(value) => formatCurrency(value)} />
                <Line 
                  type="monotone" 
                  dataKey="ca" 
                  stroke="#2196f3" 
                  dot={{ fill: '#2196f3' }}
                  strokeWidth={2}
                  name="CA Mensuel"
                  isAnimationActive={false}
                />
              </LineChart>
            </ResponsiveContainer>
          </Paper>
        </Grid>

        {/* CA Trimestriel */}
        <Grid item xs={12} md={4}>
          <Paper sx={{ p: 3 }}>
            <Typography variant="body2" color="textSecondary" sx={{ mb: 2 }}>
              Répartition par Trimestre
            </Typography>
            <ResponsiveContainer width="100%" height={300}>
              <PieChart>
                <Pie
                  data={caTrimestrielClean}
                  dataKey="ca"
                  nameKey="trimestre"
                  cx="50%"
                  cy="50%"
                  outerRadius={80}
                  label
                  isAnimationActive={false}
                >
                  {caTrimestrielClean.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                  ))}
                </Pie>
                <Tooltip formatter={(value) => formatCurrency(value)} />
                <Legend />
              </PieChart>
            </ResponsiveContainer>
          </Paper>
        </Grid>
      </Grid>

      {/* ============================================
          3. STRUCTURE DES COÛTS 
          ============================================ */}
      <Typography variant="h5" sx={{ mb: 2, mt: 4, fontWeight: 'bold' }}>
        💰 Structure des Coûts & Ratios d'Exploitation
      </Typography>
      <Grid container spacing={2} sx={{ mb: 4 }}>
        {/* Graphique Coûts */}
        <Grid item xs={12} md={6}>
          <Paper sx={{ p: 3 }}>
            <Typography variant="body2" color="textSecondary" sx={{ mb: 2 }}>
              Répartition Charge d'Exploitation
            </Typography>
            <ResponsiveContainer width="100%" height={300}>
              <PieChart>
                <Pie
                  data={coutsPieData}
                  dataKey="value"
                  nameKey="name"
                  cx="50%"
                  cy="50%"
                  outerRadius={80}
                  label
                >
                  {coutsPieData.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                  ))}
                </Pie>
                <Tooltip formatter={(value) => formatCurrency(value)} />
                <Legend />
              </PieChart>
            </ResponsiveContainer>
          </Paper>
        </Grid>

        {/* Ratios Exploitation */}
        <Grid item xs={12} md={6}>
          <Paper sx={{ p: 3 }}>
            <Typography variant="body2" color="textSecondary" sx={{ mb: 2 }}>
              Ratios vs CA (%)
            </Typography>
            <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
              <Box>
                <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 0.5 }}>
                  <Typography variant="body2">Coût Matière</Typography>
                  <Typography variant="body2" sx={{ fontWeight: 'bold' }}>
                    {ratios_exploitation.ratio_achats}%
                  </Typography>
                </Box>
                <LinearProgress 
                  variant="determinate" 
                  value={Math.min(parseFloat(ratios_exploitation.ratio_achats), 100)}
                  sx={{ height: 8 }}
                />
              </Box>
              <Box>
                <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 0.5 }}>
                  <Typography variant="body2">Masse Salariale</Typography>
                  <Typography variant="body2" sx={{ fontWeight: 'bold' }}>
                    {ratios_exploitation.ratio_salaires}%
                  </Typography>
                </Box>
                <LinearProgress 
                  variant="determinate" 
                  value={Math.min(parseFloat(ratios_exploitation.ratio_salaires), 100)}
                  sx={{ height: 8, backgroundColor: '#ff9800' }}
                />
              </Box>
              <Box>
                <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 0.5 }}>
                  <Typography variant="body2">Frais Bancaires</Typography>
                  <Typography 
                    variant="body2" 
                    sx={{ 
                      fontWeight: 'bold',
                      color: parseFloat(ratios_exploitation.ratio_frais_banc) > 2.5 ? '#f44336' : 'inherit'
                    }}
                  >
                    {ratios_exploitation.ratio_frais_banc}%
                  </Typography>
                </Box>
                <LinearProgress 
                  variant="determinate" 
                  value={Math.min(parseFloat(ratios_exploitation.ratio_frais_banc) * 10, 100)}
                  sx={{ 
                    height: 8,
                    backgroundColor: parseFloat(ratios_exploitation.ratio_frais_banc) > 2.5 ? '#ffebee' : '#e3f2fd'
                  }}
                />
              </Box>
              <Box sx={{ mt: 2, p: 2, backgroundColor: '#f5f5f5', borderRadius: 1 }}>
                <Typography variant="subtitle2" sx={{ fontWeight: 'bold', mb: 1 }}>
                  Charge Totale: {ratios_exploitation.ratio_charge_total}%
                </Typography>
                <Typography variant="body2" color="textSecondary">
                  Marge d'exploitation: {marges.ratio_marge_exploitation}%
                </Typography>
              </Box>
            </Box>
          </Paper>
        </Grid>
      </Grid>

      {/* ============================================
          4. CYCLES DE TRÉSORERIE 
          ============================================ */}
      <Typography variant="h5" sx={{ mb: 2, mt: 4, fontWeight: 'bold' }}>
        🔄 Cycles de Trésorerie & BFR
      </Typography>
      <Grid container spacing={2} sx={{ mb: 4 }}>
        {/* Graphique Cycles */}
        <Grid item xs={12} md={8}>
          <Paper sx={{ p: 3 }}>
            <Typography variant="body2" color="textSecondary" sx={{ mb: 2 }}>
              DSO (Jours) vs DPO (Jours) - Impact sur trésorerie
            </Typography>
            <ResponsiveContainer width="100%" height={300}>
              <BarChart data={clientsFournisseurData}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="name" />
                <YAxis yAxisId="left" label={{ value: 'Jours', angle: -90, position: 'insideLeft' }} />
                <YAxis yAxisId="right" orientation="right" label={{ value: 'Montant (€)', angle: 90, position: 'insideRight' }} />
                <Tooltip />
                <Bar yAxisId="left" dataKey="jours" fill="#8884d8" name="Jours" />
              </BarChart>
            </ResponsiveContainer>
          </Paper>
        </Grid>

        {/* Métriques BFR */}
        <Grid item xs={12} md={4}>
          <Paper sx={{ p: 3 }}>
            <Typography variant="body2" color="textSecondary" sx={{ mb: 2 }}>
              Besoin en Fonds de Roulement
            </Typography>
            <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
              <Box sx={{ p: 2, backgroundColor: '#e3f2fd', borderRadius: 1 }}>
                <Typography variant="caption" color="textSecondary">
                  DSO Clients
                </Typography>
                <Typography variant="h5" sx={{ color: '#2196f3', fontWeight: 'bold' }}>
                  {cycles_tresorerie.dso_clients.toFixed(1)} jours
                </Typography>
              </Box>
              <Box sx={{ p: 2, backgroundColor: '#fff3e0', borderRadius: 1 }}>
                <Typography variant="caption" color="textSecondary">
                  Jours de Stock
                </Typography>
                <Typography variant="h5" sx={{ color: '#ff9800', fontWeight: 'bold' }}>
                  {cycles_tresorerie.jours_stock.toFixed(1)} jours
                </Typography>
              </Box>
              <Box sx={{ p: 2, backgroundColor: '#f1f8e9', borderRadius: 1 }}>
                <Typography variant="caption" color="textSecondary">
                  DPO Fournisseurs
                </Typography>
                <Typography variant="h5" sx={{ color: '#4caf50', fontWeight: 'bold' }}>
                  {cycles_tresorerie.dpo_fournisseurs.toFixed(1)} jours
                </Typography>
              </Box>
              <Box sx={{ p: 2, backgroundColor: tresorerie.bfr > 0 ? '#ffebee' : '#e8f5e9', borderRadius: 1, border: '2px solid', borderColor: tresorerie.bfr > 0 ? '#f44336' : '#4caf50' }}>
                <Typography variant="caption" color="textSecondary">
                  Cycle Conversion
                </Typography>
                <Typography variant="h6" sx={{ color: tresorerie.bfr > 0 ? '#f44336' : '#4caf50', fontWeight: 'bold' }}>
                  {cycles_tresorerie.cycle_conversion.toFixed(1)} jours
                </Typography>
              </Box>
            </Box>
          </Paper>
        </Grid>
      </Grid>

      {/* ============================================
          5. RATIOS DE SOLVABILITÉ 
          ============================================ */}
      <Typography variant="h5" sx={{ mb: 2, mt: 4, fontWeight: 'bold' }}>
        🛡️ Santé Financière & Solvabilité
      </Typography>
      <Grid container spacing={2} sx={{ mb: 4 }}>
        <Grid item xs={12} sm={6} md={3}>
          <KPIMetric 
            label="Ratio de Liquidité" 
            value={ratios_solvabilite.ratio_liquidite}
            unit="x"
            color={ratios_solvabilite.ratio_liquidite > 1.5 ? '#4caf50' : ratios_solvabilite.ratio_liquidite > 1 ? '#ff9800' : '#f44336'}
            alert={ratios_solvabilite.ratio_liquidite < 1}
          />
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <KPIMetric 
            label="Ratio Autonomie" 
            value={ratios_solvabilite.ratio_autonomie}
            unit="%"
            color={ratios_solvabilite.ratio_autonomie > 50 ? '#4caf50' : '#f44336'}
          />
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <KPIMetric 
            label="Dettes Financières" 
            value={tresorerie.dettes_financieres / 1000}
            unit="k€"
            color="#2196f3"
          />
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <KPIMetric 
            label="Capitaux Propres" 
            value={tresorerie.capitaux_propres / 1000}
            unit="k€"
            color={tresorerie.capitaux_propres > 0 ? '#4caf50' : '#f44336'}
          />
        </Grid>
      </Grid>

      {/* ============================================
          6. ANALYSE CLIENTS & FOURNISSEURS 
          ============================================ */}
      <Typography variant="h5" sx={{ mb: 2, mt: 4, fontWeight: 'bold' }}>
        👥 Top Clients vs 🏭 Top Fournisseurs
      </Typography>
      <Grid container spacing={2} sx={{ mb: 4 }}>
        {/* Top Clients */}
        <Grid item xs={12} md={6}>
          <Paper sx={{ p: 3 }}>
            <TableContainer>
              <Table size="small">
                <TableHead>
                  <TableRow sx={{ backgroundColor: '#f5f5f5' }}>
                    <TableCell sx={{ fontWeight: 'bold' }}>Client</TableCell>
                    <TableCell align="right" sx={{ fontWeight: 'bold' }}>Montant</TableCell>
                    <TableCell align="right" sx={{ fontWeight: 'bold' }}>% CA</TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {topClientsClean.length > 0 ? topClientsClean.map((client, idx) => {
                    const pct = (client.montant / Math.abs(ca.total) * 100).toFixed(1);
                    return (
                      <TableRow key={idx} sx={{ '&:nth-of-type(odd)': { backgroundColor: '#fafafa' } }}>
                        <TableCell>{client.client || 'N/A'}</TableCell>
                        <TableCell align="right">{formatCurrency(client.montant)}</TableCell>
                        <TableCell align="right">
                          <Chip 
                            label={`${pct}%`} 
                            color={pct > 15 ? 'error' : pct > 8 ? 'warning' : 'success'}
                            variant="outlined"
                            size="small"
                          />
                        </TableCell>
                      </TableRow>
                    );
                  }) : (
                    <TableRow>
                      <TableCell colSpan={3} align="center" sx={{ color: '#999' }}>
                        Aucune donnée client
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </TableContainer>
          </Paper>
        </Grid>

        {/* Top Fournisseurs */}
        <Grid item xs={12} md={6}>
          <Paper sx={{ p: 3 }}>
            <TableContainer>
              <Table size="small">
                <TableHead>
                  <TableRow sx={{ backgroundColor: '#f5f5f5' }}>
                    <TableCell sx={{ fontWeight: 'bold' }}>Fournisseur</TableCell>
                    <TableCell align="right" sx={{ fontWeight: 'bold' }}>Montant</TableCell>
                    <TableCell align="right" sx={{ fontWeight: 'bold' }}>% Achats</TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {topFournisseursClean.length > 0 ? topFournisseursClean.map((fourn, idx) => {
                    const pct = (fourn.montant / Math.abs(couts.matiere) * 100).toFixed(1);
                    return (
                      <TableRow key={idx} sx={{ '&:nth-of-type(odd)': { backgroundColor: '#fafafa' } }}>
                        <TableCell>{fourn.fournisseur || 'N/A'}</TableCell>
                        <TableCell align="right">{formatCurrency(fourn.montant)}</TableCell>
                        <TableCell align="right">
                          <Chip 
                            label={`${pct}%`} 
                            color={pct > 30 ? 'error' : pct > 15 ? 'warning' : 'success'}
                            variant="outlined"
                            size="small"
                          />
                        </TableCell>
                      </TableRow>
                    );
                  }) : (
                    <TableRow>
                      <TableCell colSpan={3} align="center" sx={{ color: '#999' }}>
                        Aucune donnée fournisseur
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </TableContainer>
          </Paper>
        </Grid>
      </Grid>

      {/* ============================================
          7. ALERTES & RECOMMANDATIONS 
          ============================================ */}
      <Typography variant="h5" sx={{ mb: 2, mt: 4, fontWeight: 'bold' }}>
        ⚠️ Alertes & Points d'Attention
      </Typography>
      <Paper sx={{ p: 3, backgroundColor: '#fff8e1', borderLeft: '4px solid #ff9800' }}>
        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1 }}>
          {parseFloat(ratios_exploitation.ratio_frais_banc) > 2.5 && (
            <Typography variant="body2">
              🔴 <strong>Frais Bancaires élevés</strong> ({ratios_exploitation.ratio_frais_banc}% du CA) - Audit des conditions bancaires recommandé
            </Typography>
          )}
          {ratios_solvabilite.endettement > 2 && (
            <Typography variant="body2">
              🔴 <strong>Endettement élevé</strong> ({ratios_solvabilite.endettement}x) - Attention sur la capacité de remboursement
            </Typography>
          )}
          {cycles_tresorerie.cycle_conversion > 60 && (
            <Typography variant="body2">
              🟠 <strong>Cycle de conversion long</strong> ({cycles_tresorerie.cycle_conversion.toFixed(1)} jours) - Risque de trésorerie en période creuse
            </Typography>
          )}
          {top_clients[0] && (parseFloat(top_clients[0].montant) / ca.total > 0.15) && (
            <Typography variant="body2">
              🟠 <strong>Concentration client critique</strong> - Premier client: {((parseFloat(top_clients[0].montant) / ca.total) * 100).toFixed(1)}% du CA
            </Typography>
          )}
          {parseFloat(ratios_exploitation.ratio_achats) > 65 && (
            <Typography variant="body2">
              🟠 <strong>Coût matière élevé</strong> ({ratios_exploitation.ratio_achats}%) - Marge brute comprimée
            </Typography>
          )}
        </Box>
      </Paper>
    </Box>
  );
};

export default AdvancedAnalytics;
