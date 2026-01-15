/**
 * SIGDetailedView - Vue détaillée des SIG avec tableaux
 * Affiche les détails de chaque étape de la cascade
 */

import React, { useState } from 'react';
import {
  Box,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Tabs,
  Tab,
  Typography,
  Card,
  CardContent,
  Grid
} from '@mui/material';

export default function SIGDetailedView({ sig }) {
  const [tabValue, setTabValue] = useState(0);

  if (!sig || !sig.details) {
    return null;
  }

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'EUR'
    }).format(value || 0);
  };

  const getSoldeColor = (value) => {
    if (value > 0) return '#4caf50';
    if (value < 0) return '#f44336';
    return '#2196f3';
  };

  const details = Array.isArray(sig.details) ? sig.details : [sig.details];

  return (
    <Box>
      <Tabs value={tabValue} onChange={(e, v) => setTabValue(v)} sx={{ mb: 2 }}>
        {details.map((detail, idx) => (
          <Tab key={idx} label={`Détail ${idx + 1}`} />
        ))}
      </Tabs>

      {details[tabValue] && (
        <Box>
          {/* Résumé */}
          <Grid container spacing={2} sx={{ mb: 3 }}>
            {typeof details[tabValue] === 'object' && Object.entries(details[tabValue]).map(([key, value]) => {
              if (typeof value !== 'number') return null;
              return (
                <Grid item xs={6} sm={3} key={key}>
                  <Paper sx={{ p: 2, textAlign: 'center' }}>
                    <Typography variant="caption" sx={{ color: '#666', display: 'block' }}>
                      {key.replace(/_/g, ' ')}
                    </Typography>
                    <Typography variant="h6" sx={{ color: getSoldeColor(value), fontWeight: 'bold' }}>
                      {formatCurrency(value)}
                    </Typography>
                  </Paper>
                </Grid>
              );
            })}
          </Grid>

          {/* Tableau détail */}
          <TableContainer component={Paper}>
            <Table size="small">
              <TableHead sx={{ backgroundColor: '#f5f5f5' }}>
                <TableRow>
                  <TableCell><strong>Catégorie</strong></TableCell>
                  <TableCell align="right"><strong>Montant</strong></TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {typeof details[tabValue] === 'object' && Object.entries(details[tabValue]).map(([key, value]) => {
                  if (typeof value !== 'number') return null;
                  return (
                    <TableRow key={key} hover>
                      <TableCell>{key.replace(/_/g, ' ').toUpperCase()}</TableCell>
                      <TableCell align="right" sx={{ fontWeight: 'bold', color: getSoldeColor(value) }}>
                        {formatCurrency(value)}
                      </TableCell>
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
          </TableContainer>
        </Box>
      )}
    </Box>
  );
}
