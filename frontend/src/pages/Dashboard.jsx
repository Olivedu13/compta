/**
 * Page Dashboard
 * Affiche les KPI et cascades SIG avec sélection d'année
 */

import React, { useEffect, useState } from 'react';
import {
  Typography,
  Box,
  Grid,
  CircularProgress,
  Alert,
  Paper,
  Select,
  MenuItem,
  FormControl,
  InputLabel,
  Button,
  Stack,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Checkbox,
  FormGroup,
  FormControlLabel
} from '@mui/material';
import {
  BarChart,
  Bar,
  LineChart,
  Line,
  ComposedChart,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer
} from 'recharts';
import CompareIcon from '@mui/icons-material/Compare';
import apiService from '../services/api';
import KPICard from '../components/KPICard';
import AnalysisSection from '../components/AnalysisSection';
import AdvancedAnalytics from '../components/AdvancedAnalytics';

export default function Dashboard() {
  const [exercice, setExercice] = useState(null); // null au démarrage, sera défini après chargement des années
  const [annees, setAnnees] = useState([]);
  const [kpis, setKpis] = useState(null);
  const [sig, setSig] = useState(null);
  const [waterfallData, setWaterfallData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [compareMode, setCompareMode] = useState(false);
  const [selectedYears, setSelectedYears] = useState({});
  const [compareData, setCompareData] = useState(null);
  const [compareOpen, setCompareOpen] = useState(false);

  // Charger la liste des années disponibles en premier
  useEffect(() => {
    const loadAnnees = async () => {
      try {
        const response = await apiService.getAnnees();
        // Response.data.data est un array de nombres [2024, 2023, ...]
        const years = Array.isArray(response.data.data) ? response.data.data : [];
        
        if (years.length > 0) {
          setAnnees(years);
          // Défini exercice à la première année disponible
          setExercice(years[0]);
        } else {
          setAnnees([]);
          setExercice(2024); // Fallback
          setError('Aucune année disponible');
        }
      } catch (err) {
        console.error('Erreur chargement années:', err);
        setAnnees([2024]); // Fallback
        setExercice(2024);
        setError('Erreur lors du chargement des années');
      }
    };
    loadAnnees();
  }, []);

  // Charger les données UNIQUEMENT après que exercice soit défini
  useEffect(() => {
    if (exercice === null) return; // N'exécute pas si exercice n'est pas encore défini

    const fetchData = async () => {
      try {
        setLoading(true);
        setError(null);

        const [kpisDetailedResponse, sigResponse] = await Promise.all([
          apiService.getKPIsDetailed(exercice),
          apiService.getSIG(exercice)
        ]);

        // Utilise les vrais KPIs détaillés
        const kpisData = kpisDetailedResponse.data.kpis;
        
        setKpis(kpisData);
        setSig(sigResponse.data.data);
        setWaterfallData(sigResponse.data.data.waterfall_data);
      } catch (err) {
        console.error('Erreur chargement dashboard:', err);
        setError('Erreur lors du chargement des données');
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [exercice]);

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

  const handleCompareOpen = () => {
    setSelectedYears({});
    setCompareOpen(true);
  };

  const handleCompareClose = () => {
    setCompareOpen(false);
  };

  const handleYearToggle = (year) => {
    setSelectedYears(prev => ({
      ...prev,
      [year]: !prev[year]
    }));
  };

  const handleCompareExecute = async () => {
    const selectedYearsList = Object.keys(selectedYears)
      .filter(y => selectedYears[y])
      .map(y => parseInt(y))
      .sort((a, b) => b - a);

    if (selectedYearsList.length < 2) {
      alert('Veuillez sélectionner au moins 2 années');
      return;
    }

    try {
      const response = await apiService.getComparaison(selectedYearsList);
      setCompareData(response.data.data);
      setCompareMode(true);
      setCompareOpen(false);
    } catch (err) {
      console.error('Erreur comparaison:', err);
      alert('Erreur lors de la comparaison');
    }
  };

  // Vue de comparaison
  if (compareMode && compareData) {
    return (
      <Box>
        <Button 
          onClick={() => setCompareMode(false)}
          sx={{ mb: 3 }}
        >
          ← Retour au tableau de bord
        </Button>

        <Typography variant="h5" sx={{ mb: 3 }}>
          Comparaison d'années
        </Typography>

        {compareData.kpis && (
          <>
            <Typography variant="h6" sx={{ mb: 2, mt: 4 }}>
              Stocks Bijouterie
            </Typography>
            <Grid container spacing={2} sx={{ mb: 4 }}>
              {Object.entries(compareData.kpis).map(([category, years]) => (
                <Grid item xs={12} md={6} key={category}>
                  <Paper sx={{ p: 2 }}>
                    <Typography variant="subtitle2" sx={{ mb: 2 }}>
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
                        <Bar dataKey="value" fill="#0ea5e9" />
                      </BarChart>
                    </ResponsiveContainer>
                  </Paper>
                </Grid>
              ))}
            </Grid>
          </>
        )}

        {compareData.cascade && (
          <>
            <Typography variant="h6" sx={{ mb: 2, mt: 4 }}>
              Cascade SIG
            </Typography>
            <Paper sx={{ p: 2, mb: 4, overflowX: 'auto' }}>
              <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                <thead>
                  <tr style={{ borderBottom: '2px solid #0f172a' }}>
                    <th style={{ padding: '12px', textAlign: 'left', fontWeight: 600 }}>Indicateur</th>
                    {Object.keys(compareData.cascade[Object.keys(compareData.cascade)[0]] || {}).map(year => (
                      <th key={year} style={{ padding: '12px', textAlign: 'right', fontWeight: 600 }}>
                        {year}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {Object.entries(compareData.cascade).map(([indicator, yearData]) => (
                    <tr key={indicator} style={{ borderBottom: '1px solid #e0e0e0' }}>
                      <td style={{ padding: '12px', fontWeight: 500 }}>{indicator}</td>
                      {Object.entries(yearData).map(([year, value]) => (
                        <td key={year} style={{ padding: '12px', textAlign: 'right' }}>
                          {new Intl.NumberFormat('fr-FR', {
                            style: 'currency',
                            currency: 'EUR'
                          }).format(value || 0)}
                        </td>
                      ))}
                    </tr>
                  ))}
                </tbody>
              </table>
            </Paper>
          </>
        )}
      </Box>
    );
  }

  return (
    <Box>
      {/* Sélecteur d'exercice */}
      <Box sx={{ mb: 3, display: 'flex', gap: 2, alignItems: 'center' }}>
        <FormControl sx={{ minWidth: 200 }}>
          <InputLabel>Exercice</InputLabel>
          <Select
            value={exercice}
            label="Exercice"
            onChange={(e) => setExercice(e.target.value)}
          >
            {annees.map(year => (
              <MenuItem key={year} value={year}>{year}</MenuItem>
            ))}
          </Select>
        </FormControl>
        <Button 
          startIcon={<CompareIcon />} 
          onClick={handleCompareOpen}
          variant="outlined"
        >
          Comparer les années
        </Button>
      </Box>

      {/* KPI Stocks */}
      <Typography variant="h5" sx={{ mb: 2, mt: 4 }}>
        Actifs Principaux
      </Typography>
      <Grid container spacing={2} sx={{ mb: 4 }}>
        <Grid item xs={12} sm={6} md={3}>
          <KPICard
            title="Stock Or"
            value={kpis?.stock?.or || 0}
            color="secondary"
          />
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <KPICard
            title="Total Stock"
            value={kpis?.stock?.or || 0}
          />
        </Grid>
      </Grid>

      {/* KPI Trésorerie */}
      <Typography variant="h5" sx={{ mb: 2, mt: 4 }}>
        Trésorerie & Tiers
      </Typography>
      <Grid container spacing={2} sx={{ mb: 4 }}>
        <Grid item xs={12} sm={6} md={3}>
          <KPICard
            title="Banque"
            value={kpis?.tresorerie?.banque || 0}
          />
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <KPICard
            title="Caisse"
            value={kpis?.tresorerie?.caisse || 0}
          />
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <KPICard
            title="Clients"
            value={kpis?.tiers?.clients || 0}
            trend={5}
          />
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <KPICard
            title="Fournisseurs"
            value={kpis?.tiers?.fournisseurs || 0}
          />
        </Grid>
      </Grid>

      {/* Cascade SIG */}
      <Typography variant="h5" sx={{ mb: 2, mt: 4 }}>
        Cascade des Soldes Intermédiaires de Gestion
      </Typography>
      <Paper sx={{ p: 2, mb: 4 }}>
        <ResponsiveContainer width="100%" height={400}>
          <BarChart data={waterfallData || []}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="name" angle={-45} textAnchor="end" height={80} />
            <YAxis />
            <Tooltip formatter={(value) => `€ ${value.toFixed(2)}`} />
            <Bar dataKey="value" fill="#1a237e" name="Montant" />
          </BarChart>
        </ResponsiveContainer>
      </Paper>

      {/* Détail SIG */}
      <Typography variant="h5" sx={{ mb: 2, mt: 4 }}>
        Détail des SIG
      </Typography>
      <Grid container spacing={2}>
        {sig?.cascade && Object.entries(sig.cascade).map(([key, value]) => {
          // La structure est directement value = { formatted: {...}, description: "..." }
          // Pas besoin de vérifier, on sait que c'est valide
          const { est_positif = false, couleur = '#999', valeur_affichee = '0,00' } = value.formatted || {};
          
          return (
            <Grid item xs={12} md={6} key={key}>
              <Paper sx={{ p: 2 }}>
                <Typography variant="body2" color="textSecondary">
                  {value.description}
                </Typography>
                <Typography variant="h6" sx={{ my: 1, color: couleur }}>
                  {est_positif ? '+' : '−'} {valeur_affichee} €
                </Typography>
                <Typography variant="caption" color="textSecondary">
                  {key.replace(/_/g, ' ').toUpperCase()}
                </Typography>
              </Paper>
            </Grid>
          );
        })}
      </Grid>

      {/* ANALYSE FINANCIÈRE */}
      <AnalysisSection exercice={exercice} />

      {/* ANALYTICS AVANCÉE */}
      <AdvancedAnalytics exercice={exercice} />

      {/* Dialog Comparaison */}
      <Dialog open={compareOpen} onClose={handleCompareClose} maxWidth="sm" fullWidth>
        <DialogTitle>Comparer les années</DialogTitle>
        <DialogContent sx={{ py: 2 }}>
          <FormGroup sx={{ mt: 2 }}>
            {annees.map(year => (
              <FormControlLabel
                key={year.annee}
                control={
                  <Checkbox
                    checked={!!selectedYears[year.annee]}
                    onChange={() => handleYearToggle(year.annee)}
                  />
                }
                label={`${year.annee} (${year.ecritures} écritures)`}
              />
            ))}
          </FormGroup>
        </DialogContent>
        <DialogActions>
          <Button onClick={handleCompareClose}>Annuler</Button>
          <Button 
            onClick={handleCompareExecute}
            variant="contained"
            disabled={Object.values(selectedYears).filter(Boolean).length < 2}
          >
            Comparer
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}
