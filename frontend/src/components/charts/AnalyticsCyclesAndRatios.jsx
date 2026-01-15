/**
 * AnalyticsCyclesAndRatios - Cycles de Trésorerie et Ratios
 */

import React from 'react';
import { Grid, Paper, Typography, Box, LinearProgress } from '@mui/material';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

const AnalyticsCyclesAndRatios = ({ cycles = {}, ratios = {}, couts = {}, tresorerie = {} }) => {
  const formatCurrency = (value) => {
    return `${value.toLocaleString('fr-FR')} €`;
  };

  const clientsFournisseurData = [
    { name: 'Clients (DSO)', jours: cycles.dso_clients },
    { name: 'Stock', jours: cycles.jours_stock },
    { name: 'Fournisseurs (DPO)', jours: cycles.dpo_fournisseurs }
  ];

  return (
    <Grid container spacing={2}>
      {/* Graphique Cycles */}
      <Grid item xs={12} md={8}>
        <Paper sx={{ p: 3 }}>
          <ResponsiveContainer width="100%" height={300}>
            <BarChart data={clientsFournisseurData}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="name" />
              <YAxis label={{ value: 'Jours', angle: -90, position: 'insideLeft' }} />
              <Tooltip />
              <Bar dataKey="jours" fill="#2196f3" radius={[8, 8, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </Paper>
      </Grid>

      {/* Métriques BFR */}
      <Grid item xs={12} md={4}>
        <Paper sx={{ p: 3 }}>
          <Typography variant="body2" color="textSecondary" sx={{ mb: 2, fontWeight: 'bold' }}>
            Besoin en Fonds de Roulement
          </Typography>
          <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
            <Box sx={{ p: 2, backgroundColor: '#e3f2fd', borderRadius: 1 }}>
              <Typography variant="caption" color="textSecondary">DSO Clients</Typography>
              <Typography variant="h5" sx={{ color: '#2196f3', fontWeight: 'bold' }}>
                {cycles.dso_clients?.toFixed(1)} jours
              </Typography>
            </Box>
            <Box sx={{ p: 2, backgroundColor: '#fff3e0', borderRadius: 1 }}>
              <Typography variant="caption" color="textSecondary">Jours de Stock</Typography>
              <Typography variant="h5" sx={{ color: '#ff9800', fontWeight: 'bold' }}>
                {cycles.jours_stock?.toFixed(1)} jours
              </Typography>
            </Box>
            <Box sx={{ p: 2, backgroundColor: '#f1f8e9', borderRadius: 1 }}>
              <Typography variant="caption" color="textSecondary">DPO Fournisseurs</Typography>
              <Typography variant="h5" sx={{ color: '#4caf50', fontWeight: 'bold' }}>
                {cycles.dpo_fournisseurs?.toFixed(1)} jours
              </Typography>
            </Box>
            <Box sx={{ p: 2, backgroundColor: cycles.cycle_conversion > 0 ? '#ffebee' : '#e8f5e9', borderRadius: 1, border: '2px solid', borderColor: cycles.cycle_conversion > 0 ? '#f44336' : '#4caf50' }}>
              <Typography variant="caption" color="textSecondary">Cycle Conversion</Typography>
              <Typography variant="h6" sx={{ color: cycles.cycle_conversion > 0 ? '#f44336' : '#4caf50', fontWeight: 'bold' }}>
                {cycles.cycle_conversion?.toFixed(1)} jours
              </Typography>
            </Box>
          </Box>
        </Paper>
      </Grid>

      {/* Ratios Exploitation */}
      <Grid item xs={12} md={6}>
        <Paper sx={{ p: 3 }}>
          <Typography variant="body2" color="textSecondary" sx={{ mb: 3, fontWeight: 'bold' }}>
            Ratios Exploitation (% du CA)
          </Typography>
          <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2.5 }}>
            {/* Coût Matière */}
            <Box>
              <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 0.8 }}>
                <Typography variant="body2">Coût Matière</Typography>
                <Typography variant="body2" sx={{ fontWeight: 'bold', color: '#2196f3' }}>
                  {ratios.ratio_achats}%
                </Typography>
              </Box>
              <LinearProgress variant="determinate" value={Math.min(parseFloat(ratios.ratio_achats || 0), 100)} sx={{ height: 8, borderRadius: 4 }} />
            </Box>

            {/* Masse Salariale */}
            <Box>
              <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 0.8 }}>
                <Typography variant="body2">Masse Salariale</Typography>
                <Typography variant="body2" sx={{ fontWeight: 'bold', color: '#ff9800' }}>
                  {ratios.ratio_salaires}%
                </Typography>
              </Box>
              <LinearProgress variant="determinate" value={Math.min(parseFloat(ratios.ratio_salaires || 0), 100)} sx={{ height: 8, borderRadius: 4, backgroundColor: '#ffe0b2' }} />
            </Box>

            {/* Frais Bancaires */}
            <Box>
              <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 0.8 }}>
                <Typography variant="body2">Frais Bancaires</Typography>
                <Typography variant="body2" sx={{ fontWeight: 'bold', color: parseFloat(ratios.ratio_frais_banc) > 2.5 ? '#f44336' : '#4caf50' }}>
                  {ratios.ratio_frais_banc}%
                </Typography>
              </Box>
              <LinearProgress variant="determinate" value={Math.min(parseFloat(ratios.ratio_frais_banc || 0) * 15, 100)} sx={{ height: 8, borderRadius: 4, backgroundColor: parseFloat(ratios.ratio_frais_banc) > 2.5 ? '#ffcdd2' : '#c8e6c9' }} />
            </Box>

            {/* Charge Total */}
            <Box sx={{ mt: 2, p: 2, backgroundColor: '#f5f5f5', borderRadius: 1, borderLeft: '4px solid #2196f3' }}>
              <Typography variant="subtitle2" sx={{ fontWeight: 'bold', mb: 1 }}>
                Charge Totale: {ratios.ratio_charge_total}%
              </Typography>
              <Typography variant="body2" color="textSecondary">
                Marge d'exploitation déduite
              </Typography>
            </Box>
          </Box>
        </Paper>
      </Grid>

      {/* Solvabilité & Endettement */}
      <Grid item xs={12} md={6}>
        <Paper sx={{ p: 3 }}>
          <Typography variant="body2" color="textSecondary" sx={{ mb: 3, fontWeight: 'bold' }}>
            Indicateurs de Solvabilité
          </Typography>
          <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
            <Box sx={{ p: 2, backgroundColor: '#e3f2fd', borderRadius: 1 }}>
              <Typography variant="caption" color="textSecondary">Liquidité Générale</Typography>
              <Typography variant="h6" sx={{ color: '#2196f3', fontWeight: 'bold' }}>
                {(tresorerie.actif_circulant / tresorerie.passif_circulant)?.toFixed(2) || '0'} x
              </Typography>
              <Typography variant="caption" color="textSecondary">Sain si > 1,5x</Typography>
            </Box>
            <Box sx={{ p: 2, backgroundColor: '#f3e5f5', borderRadius: 1 }}>
              <Typography variant="caption" color="textSecondary">Autonomie Financière</Typography>
              <Typography variant="h6" sx={{ color: '#9c27b0', fontWeight: 'bold' }}>
                {((tresorerie.capitaux_propres / tresorerie.total_actif) * 100)?.toFixed(1) || '0'}%
              </Typography>
              <Typography variant="caption" color="textSecondary">Sain si > 30%</Typography>
            </Box>
          </Box>
        </Paper>
      </Grid>
    </Grid>
  );
};

export default AnalyticsCyclesAndRatios;
