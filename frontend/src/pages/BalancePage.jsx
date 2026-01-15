/**
 * Page Balance
 * Affiche le détail de la balance comptable
 */

import React, { useEffect, useState } from 'react';
import {
  Typography,
  Box,
  CircularProgress,
  Alert,
  Select,
  MenuItem,
  FormControl,
  InputLabel,
  Paper
} from '@mui/material';
import { DataGrid } from '@mui/x-data-grid';
import apiService from '../services/api';

export default function BalancePage() {
  const [exercice, setExercice] = useState(null); // null au démarrage
  const [annees, setAnnees] = useState([]);
  const [data, setData] = useState([]);
  const [paginationModel, setPaginationModel] = useState({ page: 0, pageSize: 50 });
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Charger les années disponibles en premier
  useEffect(() => {
    const loadAnnees = async () => {
      try {
        const response = await apiService.getAnnees();
        const years = Array.isArray(response.data.data) ? response.data.data : [];
        
        if (years.length > 0) {
          setAnnees(years);
          setExercice(years[0]); // Défini à la première année
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

  // Charger les données UNIQUEMENT après que exercice soit défini
  useEffect(() => {
    if (exercice === null) return;

    const fetchBalance = async () => {
      try {
        setLoading(true);
        setError(null);

        const response = await apiService.getBalance(
          exercice,
          paginationModel.page + 1,
          paginationModel.pageSize
        );

        const rows = (response.data.data || []).map((row, idx) => ({
          id: row.compte_num,
          ...row
        }));

        setData(rows);
        setTotal(response.data.pagination.total);
      } catch (err) {
        console.error('Erreur chargement balance:', err);
        setError('Erreur lors du chargement de la balance');
      } finally {
        setLoading(false);
      }
    };

    fetchBalance();
  }, [exercice, paginationModel]);

  const columns = [
    {
      field: 'compte_num',
      headerName: 'Compte',
      width: 100,
      sortable: true
    },
    {
      field: 'libelle',
      headerName: 'Libellé',
      width: 250,
      sortable: true
    },
    {
      field: 'classe_racine',
      headerName: 'Classe',
      width: 80,
      sortable: true
    },
    {
      field: 'debit',
      headerName: 'Débit',
      width: 120,
      sortable: true,
      renderCell: (params) => (
        <span>
          {new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
          }).format(params.value || 0)}
        </span>
      ),
      align: 'right'
    },
    {
      field: 'credit',
      headerName: 'Crédit',
      width: 120,
      sortable: true,
      renderCell: (params) => (
        <span>
          {new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
          }).format(params.value || 0)}
        </span>
      ),
      align: 'right'
    },
    {
      field: 'solde',
      headerName: 'Solde',
      width: 120,
      sortable: true,
      renderCell: (params) => (
        <span style={{ color: params.value >= 0 ? '#4caf50' : '#f44336' }}>
          {new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
          }).format(params.value || 0)}
        </span>
      ),
      align: 'right'
    }
  ];

  if (loading) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '400px' }}>
        <CircularProgress />
      </Box>
    );
  }

  if (error) {
    return <Alert severity="error">{error}</Alert>;
  }

  return (
    <Box>
      <Typography variant="h4" sx={{ mb: 3 }}>
        Balance Comptable
      </Typography>

      <Box sx={{ mb: 3, display: 'flex', gap: 2, alignItems: 'center' }}>
        <FormControl sx={{ minWidth: 200 }}>
          <InputLabel>Exercice</InputLabel>
          <Select
            value={exercice}
            label="Exercice"
            onChange={(e) => {
              setExercice(e.target.value);
              setPaginationModel({ page: 0, pageSize: 50 });
            }}
          >
            {[2024, 2023, 2022].map(year => (
              <MenuItem key={year} value={year}>{year}</MenuItem>
            ))}
          </Select>
        </FormControl>
      </Box>

      <Paper sx={{ height: 600, width: '100%' }}>
        <DataGrid
          rows={data}
          columns={columns}
          paginationModel={paginationModel}
          onPaginationModelChange={setPaginationModel}
          rowCount={total}
          pageSizeOptions={[25, 50, 100]}
          paginationMode="server"
          loading={loading}
          disableSelectionOnClick
          density="comfortable"
        />
      </Paper>

      <Typography variant="body2" color="textSecondary" sx={{ mt: 2 }}>
        Total: {total} comptes | Exercice: {exercice}
      </Typography>
    </Box>
  );
}
