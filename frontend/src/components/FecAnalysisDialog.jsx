import React, { useState } from 'react';
import {
    Box,
    Button,
    Card,
    CardContent,
    Chip,
    CircularProgress,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Divider,
    Grid,
    LinearProgress,
    List,
    ListItem,
    ListItemIcon,
    ListItemText,
    Paper,
    Stack,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Typography,
    Alert,
    AlertTitle,
} from '@mui/material';
import {
    CheckCircle as CheckCircleIcon,
    Error as ErrorIcon,
    Warning as WarningIcon,
    Info as InfoIcon,
    VerifiedUser as VerifiedUserIcon,
    ArrowForward as ArrowForwardIcon,
} from '@mui/icons-material';
import { useTheme } from '@mui/material/styles';
import { apiService } from '../../services/api';

/**
 * FecAnalysisDialog - Affiche l'analyse complète du FEC
 * Étape 1 : Validation et vérification des données
 * Permet à l'utilisateur de vérifier les données avant import
 */
export default function FecAnalysisDialog({
    open,
    file,
    onClose,
    onConfirmImport,
    onAnalysisChange,
}) {
    const theme = useTheme();
    const [analyzing, setAnalyzing] = useState(false);
    const [analysis, setAnalysis] = useState(null);
    const [error, setError] = useState(null);
    const [confirming, setConfirming] = useState(false);

    // Déclenche l'analyse au chargement du dialog
    React.useEffect(() => {
        if (open && file) {
            performAnalysis();
        }
    }, [open, file]);

    const performAnalysis = async () => {
        setAnalyzing(true);
        setError(null);
        try {
            const formData = new FormData();
            formData.append('file', file);

            const response = await apiService.post('/analyze/fec', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            const analysisData = response.data.data;
            setAnalysis(analysisData);
            onAnalysisChange?.(analysisData);

            if (!analysisData.ready_for_import) {
                setError(
                    'Le FEC contient des anomalies bloquantes. Veuillez corriger le fichier.'
                );
            }
        } catch (err) {
            setError(err.response?.data?.error || 'Erreur lors de l\'analyse du FEC');
            console.error('Erreur analyse:', err);
        } finally {
            setAnalyzing(false);
        }
    };

    const handleImportConfirm = async () => {
        setConfirming(true);
        try {
            await onConfirmImport?.();
        } finally {
            setConfirming(false);
        }
    };

    const formatCurrency = (value) => {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR',
        }).format(value);
    };

    const formatDate = (dateStr) => {
        if (!dateStr) return 'N/A';
        return new Date(dateStr).toLocaleDateString('fr-FR');
    };

    if (analyzing) {
        return (
            <Dialog open={open} maxWidth="sm" fullWidth>
                <DialogTitle>Analyse du FEC en cours...</DialogTitle>
                <DialogContent>
                    <Box sx={{ py: 3, textAlign: 'center' }}>
                        <CircularProgress />
                        <Typography sx={{ mt: 2 }}>
                            Analyse du fichier {file?.name}...
                        </Typography>
                    </Box>
                </DialogContent>
            </Dialog>
        );
    }

    if (!analysis) {
        return null;
    }

    const hasErrors = !analysis.ready_for_import;
    const criticalAnomalies = analysis.anomalies.critical || [];
    const warnings = analysis.anomalies.warnings || [];
    const stats = analysis.data_statistics;

    return (
        <Dialog open={open} maxWidth="md" fullWidth>
            <DialogTitle>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                    {analysis.ready_for_import ? (
                        <CheckCircleIcon sx={{ color: 'success.main' }} />
                    ) : (
                        <ErrorIcon sx={{ color: 'error.main' }} />
                    )}
                    Analyse du FEC - {file?.name}
                </Box>
            </DialogTitle>

            <DialogContent sx={{ maxHeight: '70vh', overflow: 'auto' }}>
                {error && (
                    <Alert severity="error" sx={{ mb: 2 }}>
                        {error}
                    </Alert>
                )}

                {/* Section 1 : Fichier et Format */}
                <Card sx={{ mb: 2 }}>
                    <CardContent>
                        <Typography variant="h6" gutterBottom>
                            📄 Fichier et Format
                        </Typography>
                        <Divider sx={{ mb: 2 }} />
                        <Grid container spacing={2}>
                            <Grid item xs={6}>
                                <Typography variant="caption" color="textSecondary">
                                    Nom du fichier
                                </Typography>
                                <Typography>{file?.name}</Typography>
                            </Grid>
                            <Grid item xs={6}>
                                <Typography variant="caption" color="textSecondary">
                                    Taille
                                </Typography>
                                <Typography>
                                    {(analysis.file_info.size_bytes / 1024).toFixed(2)} KB
                                </Typography>
                            </Grid>
                            <Grid item xs={6}>
                                <Typography variant="caption" color="textSecondary">
                                    Séparateur détecté
                                </Typography>
                                <Chip
                                    label={analysis.format.separator_name}
                                    size="small"
                                    color="primary"
                                    variant="outlined"
                                />
                            </Grid>
                            <Grid item xs={6}>
                                <Typography variant="caption" color="textSecondary">
                                    Encodage
                                </Typography>
                                <Typography>{analysis.format.encoding}</Typography>
                            </Grid>
                            <Grid item xs={6}>
                                <Typography variant="caption" color="textSecondary">
                                    Exercice détecté
                                </Typography>
                                <Typography>
                                    {analysis.exercice_detected || 'N/A'}
                                </Typography>
                            </Grid>
                            <Grid item xs={6}>
                                <Typography variant="caption" color="textSecondary">
                                    Devise
                                </Typography>
                                <Typography>{stats.devise_detected}</Typography>
                            </Grid>
                        </Grid>
                    </CardContent>
                </Card>

                {/* Section 2 : Statistiques Comptables */}
                <Card sx={{ mb: 2 }}>
                    <CardContent>
                        <Typography variant="h6" gutterBottom>
                            📊 Statistiques Comptables
                        </Typography>
                        <Divider sx={{ mb: 2 }} />
                        <Grid container spacing={2}>
                            <Grid item xs={6}>
                                <Typography variant="caption" color="textSecondary">
                                    Nombre de comptes
                                </Typography>
                                <Typography variant="h6">
                                    {stats.accounts_count}
                                </Typography>
                            </Grid>
                            <Grid item xs={6}>
                                <Typography variant="caption" color="textSecondary">
                                    Nombre de journaux
                                </Typography>
                                <Typography variant="h6">
                                    {stats.journals_count}
                                </Typography>
                            </Grid>
                            <Grid item xs={6}>
                                <Typography variant="caption" color="textSecondary">
                                    Lignes valides
                                </Typography>
                                <Typography variant="h6">
                                    {stats.valid_rows}
                                </Typography>
                            </Grid>
                            <Grid item xs={6}>
                                <Typography variant="caption" color="textSecondary">
                                    Lignes en erreur
                                </Typography>
                                <Typography
                                    variant="h6"
                                    sx={{
                                        color: stats.error_rows > 0 ? 'warning.main' : 'success.main',
                                    }}
                                >
                                    {stats.error_rows}
                                </Typography>
                            </Grid>
                            <Grid item xs={12}>
                                <Divider sx={{ my: 1 }} />
                            </Grid>
                            <Grid item xs={6}>
                                <Typography variant="caption" color="textSecondary">
                                    Total Débits
                                </Typography>
                                <Typography variant="body2" sx={{ fontWeight: 'bold' }}>
                                    {formatCurrency(stats.total_debit)}
                                </Typography>
                            </Grid>
                            <Grid item xs={6}>
                                <Typography variant="caption" color="textSecondary">
                                    Total Crédits
                                </Typography>
                                <Typography variant="body2" sx={{ fontWeight: 'bold' }}>
                                    {formatCurrency(stats.total_credit)}
                                </Typography>
                            </Grid>
                            <Grid item xs={12}>
                                <Typography variant="caption" color="textSecondary">
                                    Différence (Équilibre)
                                </Typography>
                                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mt: 0.5 }}>
                                    <LinearProgress
                                        variant="determinate"
                                        value={
                                            stats.total_debit > 0
                                                ? Math.min(
                                                    (stats.balance_difference / stats.total_debit) *
                                                    100,
                                                    100
                                                )
                                                : 0
                                        }
                                        sx={{ flex: 1 }}
                                    />
                                    <Chip
                                        label={formatCurrency(stats.balance_difference)}
                                        size="small"
                                        color={stats.is_balanced ? 'success' : 'warning'}
                                        variant="outlined"
                                        icon={stats.is_balanced ? <CheckCircleIcon /> : <WarningIcon />}
                                    />
                                </Box>
                            </Grid>
                            <Grid item xs={12}>
                                <Typography variant="caption" color="textSecondary">
                                    Période couverte
                                </Typography>
                                <Typography variant="body2">
                                    {formatDate(stats.date_range.min)} à{' '}
                                    {formatDate(stats.date_range.max)}
                                </Typography>
                            </Grid>
                        </Grid>
                    </CardContent>
                </Card>

                {/* Section 3 : Anomalies Critiques */}
                {criticalAnomalies.length > 0 && (
                    <Alert severity="error" sx={{ mb: 2 }}>
                        <AlertTitle>⚠️ Anomalies Critiques (Import Bloqué)</AlertTitle>
                        <Stack spacing={1}>
                            {(criticalAnomalies || []).map((anomaly, idx) => (
                                <Box key={idx}>
                                    <Typography variant="caption" component="div">
                                        <strong>{anomaly.code}</strong>: {anomaly.message}
                                    </Typography>
                                </Box>
                            ))}
                        </Stack>
                    </Alert>
                )}

                {/* Section 4 : Avertissements */}
                {warnings.length > 0 && (
                    <Alert severity="warning" sx={{ mb: 2 }}>
                        <AlertTitle>⚠️ Avertissements (Non-bloquants)</AlertTitle>
                        <Stack spacing={1}>
                            {(warnings || []).map((warning, idx) => (
                                <Box key={idx}>
                                    <Typography variant="caption" component="div">
                                        <strong>{warning.code}</strong>: {warning.message}
                                    </Typography>
                                    {warning.action && (
                                        <Typography
                                            variant="caption"
                                            component="div"
                                            sx={{ ml: 1, mt: 0.5, color: 'info.main' }}
                                        >
                                            → {warning.action}
                                        </Typography>
                                    )}
                                </Box>
                            ))}
                        </Stack>
                    </Alert>
                )}

                {/* Section 5 : Résumé Recommandations */}
                {analysis.recommendations && (
                    <Card sx={{ mb: 2, backgroundColor: 'info.light' }}>
                        <CardContent>
                            <Typography variant="h6" gutterBottom>
                                💡 Recommandations
                            </Typography>
                            <Divider sx={{ mb: 2 }} />
                            <Typography variant="body2">
                                {analysis.recommendations.summary}
                            </Typography>
                            {(analysis?.recommendations?.cleaning_needed || []).length > 0 && (
                                <Box sx={{ mt: 2 }}>
                                    <Typography variant="caption" color="textSecondary">
                                        Actions recommandées:
                                    </Typography>
                                    <List dense>
                                        {(analysis?.recommendations?.cleaning_needed || []).map(
                                            (action, idx) => (
                                                <ListItem key={idx}>
                                                    <ListItemIcon sx={{ minWidth: 32 }}>
                                                        <InfoIcon fontSize="small" />
                                                    </ListItemIcon>
                                                    <ListItemText
                                                        primary={action}
                                                        primaryTypographyProps={{ variant: 'caption' }}
                                                    />
                                                </ListItem>
                                            )
                                        )}
                                    </List>
                                </Box>
                            )}
                        </CardContent>
                    </Card>
                )}

                {/* Section 6 : Colonnes Détectées */}
                <Card>
                    <CardContent>
                        <Typography variant="h6" gutterBottom>
                            📋 Colonnes Détectées
                        </Typography>
                        <Divider sx={{ mb: 2 }} />
                        <TableContainer component={Paper}>
                            <Table size="small">
                                <TableHead>
                                    <TableRow sx={{ backgroundColor: 'primary.light' }}>
                                        <TableCell>Colonne Standard</TableCell>
                                        <TableCell>Nom Original</TableCell>
                                        <TableCell align="center">Status</TableCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {Object.entries(analysis?.headers?.headers || {}).map(
                                        ([standardName, headerData]) => (
                                            <TableRow key={standardName}>
                                                <TableCell>
                                                    <Chip
                                                        label={standardName}
                                                        size="small"
                                                        color={
                                                            headerData.is_custom ? 'default' : 'primary'
                                                        }
                                                        variant={
                                                            headerData.is_custom ? 'outlined' : 'filled'
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>{headerData.original_name}</TableCell>
                                                <TableCell align="center">
                                                    {headerData.is_custom ? (
                                                        <WarningIcon
                                                            fontSize="small"
                                                            sx={{ color: 'warning.main' }}
                                                        />
                                                    ) : (
                                                        <CheckCircleIcon
                                                            fontSize="small"
                                                            sx={{ color: 'success.main' }}
                                                        />
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        )
                                    )}
                                </TableBody>
                            </Table>
                        </TableContainer>
                    </CardContent>
                </Card>
            </DialogContent>

            <DialogActions sx={{ p: 2, backgroundColor: 'grey.50' }}>
                <Button onClick={onClose} variant="outlined">
                    Fermer
                </Button>
                <Button
                    onClick={() => performAnalysis()}
                    variant="text"
                    disabled={analyzing}
                >
                    Re-analyser
                </Button>
                <Button
                    onClick={handleImportConfirm}
                    variant="contained"
                    color="success"
                    disabled={hasErrors || confirming}
                    startIcon={confirming ? <CircularProgress size={20} /> : <ArrowForwardIcon />}
                >
                    {hasErrors ? 'Import Bloqué' : 'Importer le FEC'}
                </Button>
            </DialogActions>
        </Dialog>
    );
}
