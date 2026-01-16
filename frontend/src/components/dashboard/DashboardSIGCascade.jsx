/**
 * DashboardSIGCascade - Affichage Cascade SIG avec graphique et détails
 */

import React from 'react';
import { Grid, Paper, Typography, Box } from '@mui/material';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

const DashboardSIGCascade = ({ waterfallData = [], sig = {} }) => {
  return (
    <>
      {/* Cascade SIG Graphique */}
      <Typography variant="h5" sx={{ mb: 2, mt: 4, fontWeight: 'bold' }}>
        📊 Cascade des Soldes Intermédiaires de Gestion
      </Typography>
      <Paper sx={{ p: 3, mb: 4 }}>
        <ResponsiveContainer width="100%" height={400}>
          <BarChart data={waterfallData || []}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="name" angle={-45} textAnchor="end" height={80} />
            <YAxis />
            <Tooltip formatter={(value) => `€ ${value.toFixed(2)}`} />
            <Bar dataKey="value" fill="#1a237e" name="Montant" radius={[8, 8, 0, 0]} />
          </BarChart>
        </ResponsiveContainer>
      </Paper>

      {/* Détail SIG */}
      <Typography variant="h5" sx={{ mb: 2, mt: 4, fontWeight: 'bold' }}>
        📋 Détail des SIG
      </Typography>
      <Grid container spacing={2}>
        {sig?.cascade && Object.entries(sig?.cascade || {}).map(([key, value]) => {
          const { est_positif = false, couleur = '#999', valeur_affichee = '0,00' } = value.formatted || {};

          return (
            <Grid item xs={12} md={6} key={key}>
              <Paper sx={{ p: 2.5, borderLeft: `4px solid ${couleur}` }}>
                <Typography variant="body2" color="textSecondary">
                  {value.description}
                </Typography>
                <Typography variant="h6" sx={{ my: 1.5, color: couleur, fontWeight: 'bold' }}>
                  {est_positif ? '+' : '−'} {valeur_affichee} €
                </Typography>
                <Typography variant="caption" sx={{ color: '#999', fontWeight: 'bold' }}>
                  {key.replace(/_/g, ' ').toUpperCase()}
                </Typography>
              </Paper>
            </Grid>
          );
        })}
      </Grid>
    </>
  );
};

export default DashboardSIGCascade;
