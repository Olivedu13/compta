/**
 * AnalyticsDetailedAnalysis - Analyses détaillées
 * Affiche top clients, top fournisseurs, structure des coûts
 */

import React, { useState } from 'react';
import { Grid, Paper, Typography, Tabs, Tab, Box, Table, TableBody, TableCell, TableHead, TableRow } from '@mui/material';

const AnalyticsDetailedAnalysis = ({ 
  topClients = [], 
  topSuppliers = [], 
  costStructure = [] 
}) => {
  const [tabValue, setTabValue] = useState(0);

  const handleTabChange = (event, newValue) => {
    setTabValue(newValue);
  };

  const formatCurrency = (value) => {
    return `${value.toLocaleString('fr-FR')} €`;
  };

  const ClientsTable = () => (
    <Box sx={{ overflowX: 'auto' }}>
      <Table size="small">
        <TableHead>
          <TableRow sx={{ backgroundColor: '#f5f5f5' }}>
            <TableCell sx={{ fontWeight: 'bold' }}>Rang</TableCell>
            <TableCell sx={{ fontWeight: 'bold' }}>Nom</TableCell>
            <TableCell align="right" sx={{ fontWeight: 'bold' }}>CA</TableCell>
            <TableCell align="right" sx={{ fontWeight: 'bold' }}>% Total</TableCell>
          </TableRow>
        </TableHead>
        <TableBody>
          {topClients.slice(0, 10).map((client, idx) => (
            <TableRow key={idx}>
              <TableCell>{idx + 1}</TableCell>
              <TableCell>{client.name}</TableCell>
              <TableCell align="right">{formatCurrency(client.ca)}</TableCell>
              <TableCell align="right">{client.percentage?.toFixed(1)}%</TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </Box>
  );

  const SuppliersTable = () => (
    <Box sx={{ overflowX: 'auto' }}>
      <Table size="small">
        <TableHead>
          <TableRow sx={{ backgroundColor: '#f5f5f5' }}>
            <TableCell sx={{ fontWeight: 'bold' }}>Rang</TableCell>
            <TableCell sx={{ fontWeight: 'bold' }}>Nom</TableCell>
            <TableCell align="right" sx={{ fontWeight: 'bold' }}>Achats</TableCell>
            <TableCell align="right" sx={{ fontWeight: 'bold' }}>% Total</TableCell>
          </TableRow>
        </TableHead>
        <TableBody>
          {topSuppliers.slice(0, 10).map((supplier, idx) => (
            <TableRow key={idx}>
              <TableCell>{idx + 1}</TableCell>
              <TableCell>{supplier.name}</TableCell>
              <TableCell align="right">{formatCurrency(supplier.purchases)}</TableCell>
              <TableCell align="right">{supplier.percentage?.toFixed(1)}%</TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </Box>
  );

  const CostStructureTable = () => (
    <Box sx={{ overflowX: 'auto' }}>
      <Table size="small">
        <TableHead>
          <TableRow sx={{ backgroundColor: '#f5f5f5' }}>
            <TableCell sx={{ fontWeight: 'bold' }}>Catégorie</TableCell>
            <TableCell align="right" sx={{ fontWeight: 'bold' }}>Montant</TableCell>
            <TableCell align="right" sx={{ fontWeight: 'bold' }}>% du CA</TableCell>
          </TableRow>
        </TableHead>
        <TableBody>
          {(costStructure || []).map((item, idx) => (
            <TableRow key={idx}>
              <TableCell>{item.category}</TableCell>
              <TableCell align="right">{formatCurrency(item.amount)}</TableCell>
              <TableCell align="right">{item.percentage?.toFixed(1)}%</TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </Box>
  );

  return (
    <Paper sx={{ p: 3 }}>
      <Typography variant="h6" gutterBottom sx={{ fontWeight: 'bold', mb: 2 }}>
        Analyses Détaillées
      </Typography>

      <Tabs value={tabValue} onChange={handleTabChange}>
        <Tab label={`Top 10 Clients (${topClients.length})`} />
        <Tab label={`Top 10 Fournisseurs (${topSuppliers.length})`} />
        <Tab label="Structure des Coûts" />
      </Tabs>

      <Box sx={{ mt: 3, minHeight: 400 }}>
        {tabValue === 0 && <ClientsTable />}
        {tabValue === 1 && <SuppliersTable />}
        {tabValue === 2 && <CostStructureTable />}
      </Box>
    </Paper>
  );
};

export default AnalyticsDetailedAnalysis;
