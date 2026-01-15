/**
 * Page Dashboard - Phase 3b Refactored
 * Affiche les KPI et cascades SIG avec sélection d'année
 * 416 lignes → ~150 lignes (-64%)
 */

import React, { useEffect, useState } from 'react';
import {
  Typography,
  Box,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Button,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Checkbox,
  FormGroup,
  FormControlLabel,
  CircularProgress,
  Alert
} from '@mui/material';
import CompareIcon from '@mui/icons-material/Compare';
import apiService from '../services/api';
import { LoadingOverlay, ErrorBoundary } from '../components/common';
import {
  DashboardKPISection,
  DashboardSIGCascade,
  DashboardComparisonView
} from '../components/dashboard';
import AnalysisSection from '../components/AnalysisSection';
import AdvancedAnalytics from '../components/AdvancedAnalytics';

export default function Dashboard() {
  const [exercice, setExercice] = useState(null);
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

  // Charger les années
  useEffect(() => {
    const loadAnnees = async () => {
      try {
        const response = await apiService.getAnnees();
        const years = Array.isArray(response.data.data) ? response.data.data : [];
        
        if (years.length > 0) {
          setAnnees(years);
          setExercice(years[0]);
        } else {
          setAnnees([]);
          setExercice(2024);
          setError('Aucune année disponible');
        }
      } catch (err) {
        console.error('Erreur chargement années:', err);
        setAnnees([2024]);
        setExercice(2024);
        setError('Erreur lors du chargement des années');
      }
    };
    loadAnnees();
  }, []);

  // Charger les données du dashboard
  useEffect(() => {
    if (exercice === null) return;

    const fetchData = async () => {
      try {
        setLoading(true);
        setError(null);

        const [kpisDetailedResponse, sigResponse] = await Promise.all([
          apiService.getKPIsDetailed(exercice),
          apiService.getSIG(exercice)
        ]);

        setKpis(kpisDetailedResponse.data.kpis);
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

  // Handlers
  const handleCompareOpen = () => {
    setSelectedYears({});
    setCompareOpen(true);
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

  // Vue comparaison
  if (compareMode && compareData) {
    return (
      <DashboardComparisonView
        compareData={compareData}
        onBack={() => setCompareMode(false)}
      />
    );
  }

  if (loading) {
    return <LoadingOverlay open={true} message="Chargement du tableau de bord..." />;
  }

  if (error) {
    return <Alert severity="error">{error}</Alert>;
  }

  return (
    <ErrorBoundary>
      <Box>
        {/* Contrôles */}
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

        {/* KPIs */}
        <DashboardKPISection kpis={kpis} />

        {/* SIG Cascade */}
        <DashboardSIGCascade waterfallData={waterfallData} sig={sig} />

        {/* Analyse Financière */}
        <Typography variant="h5" sx={{ mb: 3, mt: 4, fontWeight: 'bold' }}>
          📈 Analyse Financière
        </Typography>
        <AnalysisSection exercice={exercice} />

        {/* Analytics Avancée */}
        <Typography variant="h5" sx={{ mb: 3, mt: 4, fontWeight: 'bold' }}>
          🔬 Analytics Avancée
        </Typography>
        <AdvancedAnalytics exercice={exercice} />

        {/* Dialog Comparaison */}
        <Dialog open={compareOpen} onClose={() => setCompareOpen(false)} maxWidth="sm" fullWidth>
          <DialogTitle>Comparer les années</DialogTitle>
          <DialogContent sx={{ py: 2 }}>
            <FormGroup sx={{ mt: 2 }}>
              {annees.map(year => (
                <FormControlLabel
                  key={year.annee || year}
                  control={
                    <Checkbox
                      checked={!!selectedYears[year.annee || year]}
                      onChange={() => handleYearToggle(year.annee || year)}
                    />
                  }
                  label={`${year.annee || year} ${year.ecritures ? `(${year.ecritures} écritures)` : ''}`}
                />
              ))}
            </FormGroup>
          </DialogContent>
          <DialogActions>
            <Button onClick={() => setCompareOpen(false)}>Annuler</Button>
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
    </ErrorBoundary>
  );
}
