/**
 * TiersAnalysisWidget - Analyse des tiers
 * Affiche liste des tiers avec KPIs financiers
 * Source: GET /api/tiers
 */

import React, { useEffect, useState } from 'react';
import {
  Card,
  CardContent,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  Box,
  CircularProgress,
  Alert,
  TablePagination,
  TextField,
  MenuItem,
  FormControl,
  InputLabel,
  Select,
  Chip
} from '@mui/material';
import apiService from '../../services/api';

export default function TiersAnalysisWidget({ exercice }) {
  const [tiers, setTiers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(10);
  const [searchTerm, setSearchTerm] = useState('');
  const [sortBy, setSortBy] = useState('montant'); // montant, nom, ecritures

  useEffect(() => {
    const fetchTiers = async () => {
      try {
        setLoading(true);
        setError(null);
        const response = await apiService.getTiers({
          exercice,
          limit: 100,
          offset: 0,
          tri: sortBy === 'nom' ? 'nom' : 'montant'
        });
        setTiers(response.data.tiers || []);
      } catch (err) {
        console.error('Erreur chargement tiers:', err);
        setError('Erreur lors du chargement des tiers');
      } finally {
        setLoading(false);
      }
    };

    if (exercice) {
      fetchTiers();
    }
  }, [exercice, sortBy]);

  const handleChangePage = (event, newPage) => {
    setPage(newPage);
  };

  const handleChangeRowsPerPage = (event) => {
    setRowsPerPage(parseInt(event.target.value, 10));
    setPage(0);
  };

  // Filtrer et paginer
  const filteredTiers = tiers.filter(t =>
    t.libelle.toLowerCase().includes(searchTerm.toLowerCase()) ||
    t.numero.includes(searchTerm)
  );

  const displayedTiers = filteredTiers.slice(
    page * rowsPerPage,
    page * rowsPerPage + rowsPerPage
  );

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

  const getSoldeColor = (solde) => {
    if (solde > 0) return '#4caf50'; // vert
    if (solde < 0) return '#ff9800'; // orange
    return '#2196f3'; // bleu
  };

  return (
    <Card sx={{ mb: 3 }}>
      <CardContent>
        <Box sx={{ mb: 3 }}>
          <h3>📊 Analyse des Tiers</h3>
          <Box sx={{ display: 'flex', gap: 2, mb: 2 }}>
            <TextField
              placeholder="Rechercher par numéro ou nom..."
              size="small"
              value={searchTerm}
              onChange={(e) => {
                setSearchTerm(e.target.value);
                setPage(0);
              }}
              sx={{ flex: 1 }}
            />
            <FormControl sx={{ minWidth: 150 }}>
              <InputLabel>Tri</InputLabel>
              <Select
                size="small"
                value={sortBy}
                label="Tri"
                onChange={(e) => setSortBy(e.target.value)}
              >
                <MenuItem value="montant">Par montant</MenuItem>
                <MenuItem value="nom">Par nom (A-Z)</MenuItem>
                <MenuItem value="ecritures">Par écritures</MenuItem>
              </Select>
            </FormControl>
          </Box>
          <Box sx={{ fontSize: '0.9rem', color: '#666' }}>
            Affichage: {displayedTiers.length} de {filteredTiers.length} tiers
          </Box>
        </Box>

        <TableContainer component={Paper}>
          <Table size="small">
            <TableHead sx={{ backgroundColor: '#f5f5f5' }}>
              <TableRow>
                <TableCell><strong>Numéro</strong></TableCell>
                <TableCell><strong>Libellé</strong></TableCell>
                <TableCell align="right"><strong>Débit</strong></TableCell>
                <TableCell align="right"><strong>Crédit</strong></TableCell>
                <TableCell align="right"><strong>Solde</strong></TableCell>
                <TableCell align="center"><strong>Écritures</strong></TableCell>
                <TableCell align="center"><strong>Lettrées</strong></TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {displayedTiers.map((tier) => (
                <TableRow key={tier.numero} hover>
                  <TableCell sx={{ fontWeight: 'bold' }}>{tier.numero}</TableCell>
                  <TableCell>{tier.libelle}</TableCell>
                  <TableCell align="right">{formatCurrency(tier.total_debit || 0)}</TableCell>
                  <TableCell align="right">{formatCurrency(tier.total_credit || 0)}</TableCell>
                  <TableCell align="right">
                    <Box sx={{
                      fontWeight: 'bold',
                      color: getSoldeColor(tier.solde || 0)
                    }}>
                      {formatCurrency(tier.solde || 0)}
                    </Box>
                  </TableCell>
                  <TableCell align="center">
                    <Chip
                      label={tier.nb_ecritures || 0}
                      size="small"
                      variant="outlined"
                    />
                  </TableCell>
                  <TableCell align="center">
                    <Chip
                      label={tier.nb_ecritures_lettrees || 0}
                      size="small"
                      color={tier.nb_ecritures_lettrees > 0 ? 'success' : 'default'}
                      variant={tier.nb_ecritures_lettrees > 0 ? 'filled' : 'outlined'}
                    />
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>

        <TablePagination
          rowsPerPageOptions={[5, 10, 25, 50]}
          component="div"
          count={filteredTiers.length}
          rowsPerPage={rowsPerPage}
          page={page}
          onPageChange={handleChangePage}
          onRowsPerPageChange={handleChangeRowsPerPage}
          labelRowsPerPage="Lignes par page:"
          sx={{ mt: 2 }}
        />
      </CardContent>
    </Card>
  );
}
