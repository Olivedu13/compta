/**
 * Section Analyse Financière
 * Affiche CA mensuel, Top Clients, Top Fournisseurs, Structure des coûts
 */

import React, { useEffect, useState } from 'react';
import {
  Box,
  Paper,
  Typography,
  Grid,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  LinearProgress,
  Card,
  CardContent
} from '@mui/material';
import {
  LineChart,
  Line,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Legend
} from 'recharts';
import { apiService } from '../services/api';

const AnalysisSection = ({ exercice }) => {
  const [analyse, setAnalyse] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchAnalyse = async () => {
      try {
        // Utiliser analytics-advanced au lieu d'analyse-simple pour avoir les détails clients/fournisseurs
        const response = await apiService.getAnalyticsAdvanced(exercice);
        setAnalyse(response.data);
      } catch (err) {
        console.error('Erreur chargement analyse:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchAnalyse();
  }, [exercice]);

  if (loading || !analyse) {
    return <Typography>Chargement...</Typography>;
  }

  const { 
    ca, 
    couts, 
    top_clients, 
    top_fournisseurs, 
    ratios_exploitation
  } = analyse;

  // Données mensuelles CA avec conversion en positif
  const caMensuelClean = (ca?.mensuel || []).map(m => ({
    ...m,
    ca: Math.abs(parseFloat(m.ca || 0))
  }));
  
  const caTotal = Math.abs(parseFloat(ca?.total || 0));

  // Calculs pour les ratios
  const ratioAchats = caTotal > 0 ? ratios_exploitation.ratio_achats : 0;
  const ratioFrais = caTotal > 0 ? ratios_exploitation.ratio_frais_banc : 0;
  const ratioSalaires = caTotal > 0 ? ratios_exploitation.ratio_salaires : 0;
  const margeAchats = caTotal - (couts?.matiere || 0);

  return (
    <Box>
      {/* 1. SAISONNALITÉ & CA MENSUEL */}
      <Typography variant="h5" sx={{ mb: 2, mt: 4, fontWeight: 'bold' }}>
        📈 Analyse du Chiffre d'Affaires (Saisonnalité)
      </Typography>
      <Paper sx={{ p: 3, mb: 4 }}>
        <Typography variant="body2" color="textSecondary" sx={{ mb: 2 }}>
          Tendance mensuelle du CA - Permet d'identifier la saisonnalité et anticiper les besoins en BFR
        </Typography>
        <ResponsiveContainer width="100%" height={300}>
          <LineChart data={caMensuelClean}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="mois" />
            <YAxis />
            <Tooltip 
              formatter={(value) => `${parseFloat(value).toLocaleString('fr-FR', { 
                minimumFractionDigits: 0, 
                maximumFractionDigits: 0 
              })} €`}
            />
            <Legend />
            <Line 
              type="monotone" 
              dataKey="ca" 
              stroke="#2196f3" 
              name="CA Mensuel"
              strokeWidth={2}
              isAnimationActive={false}
            />
          </LineChart>
        </ResponsiveContainer>
      </Paper>

      {/* 2. TOP CLIENTS */}
      <Typography variant="h5" sx={{ mb: 2, mt: 4, fontWeight: 'bold' }}>
        👥 Top 10 Clients (Pareto 80/20)
      </Typography>
      <Paper sx={{ p: 3, mb: 4 }}>
        <Typography variant="body2" color="textSecondary" sx={{ mb: 2 }}>
          Analyse de dépendance commerciale - Permet d'identifier les risques client
        </Typography>
        <TableContainer>
          <Table>
            <TableHead>
              <TableRow sx={{ backgroundColor: '#f5f5f5' }}>
                <TableCell sx={{ fontWeight: 'bold' }}>Client</TableCell>
                <TableCell align="right" sx={{ fontWeight: 'bold' }}>Montant TTC</TableCell>
                <TableCell align="right" sx={{ fontWeight: 'bold' }}>% du CA</TableCell>
                <TableCell sx={{ fontWeight: 'bold' }}>Risque</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {(top_clients || []).map((client, idx) => {
                const montantNum = Math.abs(parseFloat(client.montant || 0));
                const pourcentage = caTotal > 0 ? (montantNum / caTotal * 100).toFixed(1) : 0;
                let risque = '🟢 Faible';
                if (pourcentage > 15) risque = '🔴 Critique';
                else if (pourcentage > 8) risque = '🟠 Moyen';
                
                return (
                  <TableRow key={idx} sx={{ '&:nth-of-type(odd)': { backgroundColor: '#fafafa' } }}>
                    <TableCell>{client.client || 'N/A'}</TableCell>
                    <TableCell align="right">
                      {montantNum.toLocaleString('fr-FR', { 
                        minimumFractionDigits: 0, 
                        maximumFractionDigits: 0 
                      })} €
                    </TableCell>
                    <TableCell align="right">{pourcentage}%</TableCell>
                    <TableCell>{risque}</TableCell>
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        </TableContainer>
      </Paper>

      {/* 3. TOP FOURNISSEURS */}
      <Typography variant="h5" sx={{ mb: 2, mt: 4, fontWeight: 'bold' }}>
        🏭 Top 10 Fournisseurs
      </Typography>
      <Paper sx={{ p: 3, mb: 4 }}>
        <Typography variant="body2" color="textSecondary" sx={{ mb: 2 }}>
          Concentration des achats - Permet d'identifier les dépendances d'approvisionnement
        </Typography>
        <TableContainer>
          <Table>
            <TableHead>
              <TableRow sx={{ backgroundColor: '#f5f5f5' }}>
                <TableCell sx={{ fontWeight: 'bold' }}>Fournisseur</TableCell>
                <TableCell align="right" sx={{ fontWeight: 'bold' }}>Montant HT</TableCell>
                <TableCell align="right" sx={{ fontWeight: 'bold' }}>% des Achats</TableCell>
                <TableCell sx={{ fontWeight: 'bold' }}>Criticité</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {(top_fournisseurs || []).map((fourn, idx) => {
                const montantNum = Math.abs(parseFloat(fourn.montant || 0));
                const coutsMat = Math.abs(couts?.matiere || 0);
                const pourcentage = coutsMat > 0 ? (montantNum / coutsMat * 100).toFixed(1) : 0;
                let criticite = '🟢 Faible';
                if (pourcentage > 30) criticite = '🔴 Critique';
                else if (pourcentage > 15) criticite = '🟠 Importante';
                
                return (
                  <TableRow key={idx} sx={{ '&:nth-of-type(odd)': { backgroundColor: '#fafafa' } }}>
                    <TableCell>{fourn.fournisseur || 'N/A'}</TableCell>
                    <TableCell align="right">
                      {montantNum.toLocaleString('fr-FR', { 
                        minimumFractionDigits: 0, 
                        maximumFractionDigits: 0 
                      })} €
                    </TableCell>
                    <TableCell align="right">{pourcentage}%</TableCell>
                    <TableCell>{criticite}</TableCell>
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        </TableContainer>
      </Paper>

      {/* 4. STRUCTURE DES COÛTS */}
      <Typography variant="h5" sx={{ mb: 2, mt: 4, fontWeight: 'bold' }}>
        💰 Structure des Coûts & Marge
      </Typography>
      <Grid container spacing={2} sx={{ mb: 4 }}>
        {/* Achats */}
        <Grid item xs={12} md={6}>
          <Card>
            <CardContent>
              <Typography color="textSecondary" gutterBottom>
                Achats Matières Premières (601)
              </Typography>
              <Typography variant="h4" sx={{ mb: 1, color: '#f44336' }}>
                {Math.ceil(couts?.matiere || 0).toLocaleString('fr-FR')} €
              </Typography>
              <Box sx={{ mb: 2 }}>
                <LinearProgress 
                  variant="determinate" 
                  value={Math.min(parseFloat(ratioAchats), 100)} 
                  sx={{ height: 8 }}
                />
              </Box>
              <Typography variant="body2" color="textSecondary">
                {ratioAchats}% du CA - Ratio critique pour la marge brute
              </Typography>
            </CardContent>
          </Card>
        </Grid>

        {/* Marge sur Achats */}
        <Grid item xs={12} md={6}>
          <Card>
            <CardContent>
              <Typography color="textSecondary" gutterBottom>
                Marge Brute (CA - Achats)
              </Typography>
              <Typography variant="h4" sx={{ mb: 1, color: '#4caf50' }}>
                {margeAchats.toLocaleString('fr-FR', { 
                  minimumFractionDigits: 2, 
                  maximumFractionDigits: 2 
                })} €
              </Typography>
              <Typography variant="body2" color="textSecondary">
                {((margeAchats / caTotal * 100) || 0).toFixed(1)}% du CA
              </Typography>
            </CardContent>
          </Card>
        </Grid>

        {/* Salaires */}
        <Grid item xs={12} md={6}>
          <Card>
            <CardContent>
              <Typography color="textSecondary" gutterBottom>
                Masse Salariale (641 + 645)
              </Typography>
              <Typography variant="h4" sx={{ mb: 1, color: '#ff9800' }}>
                {Math.ceil(couts?.salaires || 0).toLocaleString('fr-FR')} €
              </Typography>
              <Box sx={{ mb: 2 }}>
                <LinearProgress 
                  variant="determinate" 
                  value={Math.min(parseFloat(ratioSalaires), 100)} 
                  sx={{ height: 8 }}
                />
              </Box>
              <Typography variant="body2" color="textSecondary">
                {ratioSalaires}% du CA
              </Typography>
            </CardContent>
          </Card>
        </Grid>

        {/* Frais Bancaires */}
        <Grid item xs={12} md={6}>
          <Card>
            <CardContent>
              <Typography color="textSecondary" gutterBottom>
                Frais Bancaires (627)
              </Typography>
              <Typography variant="h4" sx={{ mb: 1, color: ratioFrais > 2 ? '#ff5722' : '#2196f3' }}>
                {Math.ceil(couts?.frais_banc || 0).toLocaleString('fr-FR')} €
              </Typography>
              <Box sx={{ mb: 2 }}>
                <LinearProgress 
                  variant="determinate" 
                  value={Math.min(parseFloat(ratioFrais) * 10, 100)} 
                  sx={{ height: 8, backgroundColor: ratioFrais > 2 ? '#ffebee' : '#e3f2fd' }}
                />
              </Box>
              <Typography variant="body2" color={ratioFrais > 2 ? 'error' : 'textSecondary'}>
                {ratioFrais}% du CA {ratioFrais > 2 ? '⚠️ ALERTE - Audit bancaire conseillé' : ''}
              </Typography>
            </CardContent>
          </Card>
        </Grid>
      </Grid>
    </Box>
  );
};

export default AnalysisSection;
