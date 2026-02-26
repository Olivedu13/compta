/**
 * AIAnalysisPage - Note de Synthèse Financière IA
 * Génère une analyse Big Four automatique
 * Mission 3 — XML Prompt Financial Expert
 */

import React, { useEffect, useState, useRef } from 'react';
import {
  Box, Typography, Grid, Card, CardContent, Alert,
  FormControl, InputLabel, Select, MenuItem, Button,
  CircularProgress, Chip, Divider, Paper, useTheme
} from '@mui/material';
import {
  AutoAwesome as AIIcon,
  Refresh as RefreshIcon,
  Assessment as AssessmentIcon,
  Warning as WarningIcon,
  CheckCircle as CheckIcon,
  Error as ErrorIcon,
  Download as DownloadIcon
} from '@mui/icons-material';
import apiService from '../services/api';

const SEVERITY_COLORS = {
  critique: { bg: '#FEF2F2', color: '#991B1B', border: '#FECACA' },
  important: { bg: '#FFFBEB', color: '#92400E', border: '#FDE68A' },
  info: { bg: '#F0F9FF', color: '#0C4A6E', border: '#BAE6FD' },
  success: { bg: '#F0FDF4', color: '#166534', border: '#BBF7D0' }
};

const GRADE_COLORS = { A: '#01B574', B: '#4299E1', C: '#FFB547', D: '#EE5D50' };

function SectionCard({ title, icon, children, sx = {} }) {
  return (
    <Card sx={{ mb: 3, ...sx }}>
      <CardContent sx={{ p: 3 }}>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mb: 2 }}>
          {icon}
          <Typography variant="h6" sx={{ fontWeight: 600 }}>{title}</Typography>
        </Box>
        {children}
      </CardContent>
    </Card>
  );
}

function MarkdownBlock({ text }) {
  if (!text) return null;
  // Simple Markdown rendering for tables and text
  const lines = text.split('\n');
  return (
    <Box sx={{ fontFamily: '"DM Sans", sans-serif', fontSize: '0.9rem', lineHeight: 1.7, color: '#1B2559',
      '& strong': { fontWeight: 700 },
      '& table': { width: '100%', borderCollapse: 'collapse', my: 2 },
      '& th, & td': { p: 1.5, textAlign: 'left', borderBottom: '1px solid #E2E8F0', fontSize: '0.85rem' },
      '& th': { fontWeight: 700, backgroundColor: '#F4F7FE', color: '#8F9BBA', textTransform: 'uppercase', fontSize: '0.75rem', letterSpacing: '0.05em' }
    }}>
      <pre style={{
        whiteSpace: 'pre-wrap',
        wordWrap: 'break-word',
        fontFamily: '"DM Sans", sans-serif',
        fontSize: '0.9rem',
        lineHeight: 1.8,
        margin: 0,
        color: '#1B2559'
      }}>
        {text}
      </pre>
    </Box>
  );
}

export default function AIAnalysisPage() {
  const [exercice, setExercice] = useState(null);
  const [annees, setAnnees] = useState([]);
  const [analysis, setAnalysis] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const theme = useTheme();

  useEffect(() => {
    const loadAnnees = async () => {
      try {
        const response = await apiService.getAnnees();
        const years = Array.isArray(response.data.data) ? response.data.data : [];
        if (years.length > 0) { setAnnees(years); setExercice(years[0]); }
        else { setAnnees([2024]); setExercice(2024); }
      } catch { setAnnees([2024]); setExercice(2024); }
    };
    loadAnnees();
  }, []);

  const loadAnalysis = async () => {
    if (!exercice) return;
    try {
      setLoading(true); setError(null);
      const res = await apiService.getAIAnalysis(exercice);
      setAnalysis(res.data?.data || null);
    } catch (err) {
      setError('Erreur lors de la génération: ' + err.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (exercice) loadAnalysis();
  }, [exercice]);

  const handleExport = () => {
    if (!analysis?.note_synthese) return;
    const blob = new Blob([analysis.note_synthese], { type: 'text/markdown' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `analyse_financiere_${exercice}.md`;
    a.click();
    URL.revokeObjectURL(url);
  };

  return (
    <Box>
      {/* Header */}
      <Box sx={{ mb: 4, display: 'flex', flexWrap: 'wrap', alignItems: 'center', justifyContent: 'space-between', gap: 2 }}>
        <Box>
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
            <Box sx={{
              width: 42, height: 42, borderRadius: '12px',
              background: 'linear-gradient(135deg, #868CFF 0%, #4318FF 100%)',
              display: 'flex', alignItems: 'center', justifyContent: 'center'
            }}>
              <AIIcon sx={{ color: '#fff', fontSize: 22 }} />
            </Box>
            <Box>
              <Typography variant="h3" sx={{ fontWeight: 700 }}>Analyse IA</Typography>
              <Typography variant="body2">Note de synthèse financière — Big Four standard</Typography>
            </Box>
          </Box>
        </Box>
        <Box sx={{ display: 'flex', gap: 1.5, alignItems: 'center' }}>
          <FormControl size="small" sx={{ minWidth: 140 }}>
            <InputLabel>Exercice</InputLabel>
            <Select value={exercice} label="Exercice" onChange={(e) => setExercice(e.target.value)}>
              {annees.map(y => <MenuItem key={y} value={y}>{y}</MenuItem>)}
            </Select>
          </FormControl>
          <Button variant="outlined" startIcon={<RefreshIcon />} onClick={loadAnalysis} disabled={loading} size="small">
            Régénérer
          </Button>
          {analysis && (
            <Button variant="contained" startIcon={<DownloadIcon />} onClick={handleExport} size="small">
              Exporter .md
            </Button>
          )}
        </Box>
      </Box>

      {loading && (
        <Card sx={{ p: 6, textAlign: 'center' }}>
          <CircularProgress sx={{ color: '#4318FF', mb: 2 }} />
          <Typography variant="h6">Génération de l'analyse en cours...</Typography>
          <Typography variant="body2" sx={{ mt: 1 }}>Collecte des données SIG, Bilan, Cycles, Solvabilité...</Typography>
        </Card>
      )}

      {error && <Alert severity="error" sx={{ mb: 3 }}>{error}</Alert>}

      {analysis && !loading && (
        <>
          {/* Score + Grade */}
          {analysis.score_sante && (
            <Grid container spacing={3} sx={{ mb: 4 }}>
              <Grid item xs={12} md={4}>
                <Card sx={{ background: 'linear-gradient(135deg, #868CFF 0%, #4318FF 100%)', color: '#fff' }}>
                  <CardContent sx={{ p: 3, textAlign: 'center' }}>
                    <Typography sx={{ opacity: 0.8, fontSize: '0.75rem', fontWeight: 700, letterSpacing: '0.1em', textTransform: 'uppercase', mb: 1 }}>
                      Score de Santé
                    </Typography>
                    <Typography variant="h2" sx={{ fontWeight: 800, color: '#fff' }}>
                      {analysis.score_sante.score}/100
                    </Typography>
                    <Chip label={`Grade ${analysis.score_sante.grade}`}
                      sx={{ mt: 1, backgroundColor: 'rgba(255,255,255,0.2)', color: '#fff', fontWeight: 700, fontSize: '1rem' }} />
                  </CardContent>
                </Card>
              </Grid>
              <Grid item xs={12} md={8}>
                <Card sx={{ height: '100%' }}>
                  <CardContent sx={{ p: 3 }}>
                    <Typography variant="h6" gutterBottom sx={{ fontWeight: 600 }}>Alertes ({analysis.alertes?.length || 0})</Typography>
                    {(!analysis.alertes || analysis.alertes.length === 0) ? (
                      <Alert severity="success" icon={<CheckIcon />}>Aucune alerte critique détectée</Alert>
                    ) : (
                      <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1 }}>
                        {analysis.alertes.map((alerte, i) => {
                          const sev = SEVERITY_COLORS[alerte.severity] || SEVERITY_COLORS.info;
                          return (
                            <Box key={i} sx={{ p: 1.5, borderRadius: 2, backgroundColor: sev.bg, border: `1px solid ${sev.border}` }}>
                              <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                {alerte.severity === 'critique' ? <ErrorIcon sx={{ color: sev.color, fontSize: 18 }} /> : <WarningIcon sx={{ color: sev.color, fontSize: 18 }} />}
                                <Typography sx={{ color: sev.color, fontSize: '0.85rem', fontWeight: 600 }}>{alerte.message}</Typography>
                              </Box>
                            </Box>
                          );
                        })}
                      </Box>
                    )}
                  </CardContent>
                </Card>
              </Grid>
            </Grid>
          )}

          {/* KPI Cards Grid */}
          {analysis.sig && (
            <SectionCard title="Soldes Intermédiaires de Gestion" icon={<AssessmentIcon sx={{ color: '#4318FF' }} />}>
              <Grid container spacing={2}>
                {Object.entries(analysis.sig).filter(([k]) => !['ratios', 'waterfall_data', 'cascade', 'detail_charges'].includes(k)).map(([key, val]) => {
                  if (typeof val !== 'number') return null;
                  const isPositive = val >= 0;
                  return (
                    <Grid item xs={6} sm={4} md={3} key={key}>
                      <Box sx={{ p: 2, backgroundColor: '#F4F7FE', borderRadius: 2 }}>
                        <Typography variant="caption" sx={{ textTransform: 'capitalize' }}>
                          {key.replace(/_/g, ' ')}
                        </Typography>
                        <Typography variant="h6" sx={{
                          fontWeight: 700, fontFamily: 'monospace',
                          color: isPositive ? '#01B574' : '#EE5D50'
                        }}>
                          {new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(val)}
                        </Typography>
                      </Box>
                    </Grid>
                  );
                })}
              </Grid>
            </SectionCard>
          )}

          {/* Note de Synthèse */}
          <SectionCard title="Note de Synthèse Financière" icon={<AIIcon sx={{ color: '#4318FF' }} />}>
            <Paper sx={{ p: 3, backgroundColor: '#FAFBFF', borderRadius: 3, border: '1px solid #E2E8F0' }}>
              <MarkdownBlock text={analysis.note_synthese} />
            </Paper>
          </SectionCard>
        </>
      )}
    </Box>
  );
}
