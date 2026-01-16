/**
 * DashboardComparisonView - Vue de comparaison d'années
 */

import React from 'react';
import { Box, Button, Typography, Grid, Paper, Table, TableHead, TableBody, TableRow, TableCell } from '@mui/material';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

const DashboardComparisonView = ({ compareData, onBack }) => {
  if (!compareData) return null;

  return (
    <Box>
      <Button 
        onClick={onBack}
        sx={{ mb: 3 }}
        variant="outlined"
      >
        ← Retour au tableau de bord
      </Button>

      <Typography variant="h5" sx={{ mb: 3, fontWeight: 'bold' }}>
        📊 Comparaison d'années
      </Typography>

      {/* KPIs Comparaison */}
      {compareData.kpis && (
        <>
          <Typography variant="h6" sx={{ mb: 2, mt: 4, fontWeight: 'bold' }}>
            Stocks Bijouterie
          </Typography>
          <Grid container spacing={2} sx={{ mb: 4 }}>
            {Object.entries(compareData.kpis).map(([category, years]) => (
              <Grid item xs={12} md={6} key={category}>
                <Paper sx={{ p: 3 }}>
                  <Typography variant="subtitle2" sx={{ mb: 2, fontWeight: 'bold' }}>
                    {category.replace(/_/g, ' ').toUpperCase()}
                  </Typography>
                  <ResponsiveContainer width="100%" height={250}>
                    <BarChart
                      data={Object.entries(years).map(([year, value]) => ({
                        year,
                        value: value || 0
                      }))}
                    >
                      <CartesianGrid strokeDasharray="3 3" />
                      <XAxis dataKey="year" />
                      <YAxis />
                      <Tooltip formatter={(value) => `€ ${value.toFixed(2)}`} />
                      <Bar dataKey="value" fill="#0ea5e9" radius={[8, 8, 0, 0]} />
                    </BarChart>
                  </ResponsiveContainer>
                </Paper>
              </Grid>
            ))}
          </Grid>
        </>
      )}

      {/* Cascade SIG Comparaison */}
      {compareData.cascade && (
        <>
          <Typography variant="h6" sx={{ mb: 2, mt: 4, fontWeight: 'bold' }}>
            Cascade SIG
          </Typography>
          <Paper sx={{ p: 2, mb: 4, overflowX: 'auto' }}>
            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
              <TableHead>
                <TableRow sx={{ borderBottom: '2px solid #0f172a' }}>
                  <TableCell sx={{ fontWeight: 600, padding: '12px', textAlign: 'left' }}>
                    Indicateur
                  </TableCell>
                  {Object.keys((compareData?.cascade?.[Object.keys(compareData?.cascade || {})[0]] || {})).map(year => (
                    <TableCell key={year} sx={{ fontWeight: 600, padding: '12px', textAlign: 'right' }}>
                      {year}
                    </TableCell>
                  ))}
                </TableRow>
              </TableHead>
              <TableBody>
                {Object.entries(compareData?.cascade || {}).map(([indicator, yearData]) => (
                  <TableRow key={indicator} sx={{ borderBottom: '1px solid #e0e0e0' }}>
                    <TableCell sx={{ padding: '12px', fontWeight: 500 }}>
                      {indicator}
                    </TableCell>
                    {Object.entries(yearData).map(([year, value]) => (
                      <TableCell key={year} sx={{ padding: '12px', textAlign: 'right' }}>
                        {new Intl.NumberFormat('fr-FR', {
                          style: 'currency',
                          currency: 'EUR'
                        }).format(value || 0)}
                      </TableCell>
                    ))}
                  </TableRow>
                ))}
              </TableBody>
            </table>
          </Paper>
        </>
      )}
    </Box>
  );
};

export default DashboardComparisonView;
