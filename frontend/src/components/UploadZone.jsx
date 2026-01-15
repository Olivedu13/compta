/**
 * Composant Upload Zone - Style Google Moderne
 * Drag & drop pour fichiers avec détection automatique
 */

import React, { useState, useCallback } from 'react';
import {
  Paper,
  Typography,
  LinearProgress,
  Alert,
  Box,
  Button,
  CircularProgress,
  Snackbar,
  Card,
  CardContent,
  Chip,
  Stack,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions
} from '@mui/material';
import { useDropzone } from 'react-dropzone';
import CloudUploadIcon from '@mui/icons-material/CloudUpload';
import CheckCircleIcon from '@mui/icons-material/CheckCircle';
import ErrorIcon from '@mui/icons-material/Error';
import InsertDriveFileIcon from '@mui/icons-material/InsertDriveFile';
import apiService from '../services/api';

// Détecteur de type de fichier amélioré
const detectFileType = (fileName) => {
  const ext = fileName.split('.').pop().toLowerCase();
  
  const types = {
    'xlsx': { name: 'Excel', type: 'excel', icon: '📊' },
    'xls': { name: 'Excel', type: 'excel', icon: '📊' },
    'txt': { name: 'FEC Text', type: 'fec', icon: '📄' },
    'csv': { name: 'FEC CSV', type: 'fec', icon: '📄' },
    'tar': { name: 'Archive TAR', type: 'archive', icon: '📦' },
    'gz': { name: 'Archive Gzip', type: 'archive', icon: '📦' }
  };
  
  // Détecte .tar.gz
  if (fileName.toLowerCase().endsWith('.tar.gz')) {
    return { name: 'Archive TAR.GZ', type: 'archive', icon: '📦', extension: 'tar.gz' };
  }
  
  return types[ext] || null;
};

export default function UploadZone({ onUploadSuccess }) {
  const [uploading, setUploading] = useState(false);
  const [progress, setProgress] = useState(0);
  const [uploadResult, setUploadResult] = useState(null);
  const [error, setError] = useState(null);
  const [snackbar, setSnackbar] = useState(false);
  const [currentFile, setCurrentFile] = useState(null);
  const [overrideOpen, setOverrideOpen] = useState(false);
  const [pendingUpload, setPendingUpload] = useState(null);

  const onDrop = useCallback(async (acceptedFiles) => {
    if (acceptedFiles.length === 0) return;

    const file = acceptedFiles[0];
    const fileInfo = detectFileType(file.name);

    if (!fileInfo) {
      setError('❌ Format non supporté. Acceptés: Excel (.xlsx, .xls), FEC (.txt, .csv), Archives (.tar, .tar.gz, .gz)');
      setSnackbar(true);
      return;
    }

    setCurrentFile({ name: file.name, type: fileInfo.name, icon: fileInfo.icon });

    // Stocke le fichier et la info pour le traitement après vérification d'année
    setPendingUpload({ file, fileInfo });
    
    // Vérifie si l'année existe déjà
    const currentYear = new Date().getFullYear();
    try {
      const response = await apiService.getAnneeExists(currentYear);
      // Gère les réponses possibles
      if (response?.data?.exists === true) {
        // L'année existe, affiche le dialog de confirmation
        setOverrideOpen(true);
      } else {
        // L'année n'existe pas, lance l'import directement
        performUpload({ file, fileInfo });
      }
    } catch (err) {
      console.error('Erreur vérification année:', err);
      // En cas d'erreur, procède quand même
      performUpload({ file, fileInfo });
    }
  }, []);

  const performUpload = useCallback(async ({ file, fileInfo }) => {
    setUploading(true);
    setProgress(0);
    setError(null);
    setUploadResult(null);
    setOverrideOpen(false);

    try {
      let response;

      const onUploadProgress = (progressEvent) => {
        const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
        setProgress(percentCompleted);
      };

      // Détecte et importe automatiquement
      if (fileInfo.type === 'excel') {
        response = await apiService.importExcel(file, null, onUploadProgress);
      } else if (fileInfo.type === 'fec') {
        response = await apiService.importFEC(file, onUploadProgress);
      } else if (fileInfo.type === 'archive') {
        response = await apiService.importArchive(file, onUploadProgress);
      }

      setUploadResult({
        success: true,
        count: response?.data?.data?.count || response?.data?.count || 0,
        message: response?.data?.data?.message || response?.data?.message || 'Import réussi',
        timestamp: new Date().toLocaleString('fr-FR'),
        fileName: file.name
      });

      // Notifie le parent pour mise à jour
      if (onUploadSuccess) {
        onUploadSuccess(response?.data?.data || response?.data || {});
      }

      // Recalcule la balance
      const exercice = new Date().getFullYear();
      try {
        await apiService.recalculBalance(exercice);
      } catch (err) {
        console.warn('Erreur recalcul balance:', err);
      }

    } catch (err) {
      console.error('Erreur import détail:', err);
      // Extrait le message d'erreur depuis plusieurs sources possibles
      let errorMsg = 'Erreur lors de l\'import';
      if (err.response?.data?.error) {
        errorMsg = err.response.data.error;
      } else if (err.response?.status === 500) {
        errorMsg = 'Erreur serveur 500 - Vérifiez les logs';
      } else if (err.message) {
        errorMsg = err.message;
      }
      
      setError('❌ ' + errorMsg);
      setUploadResult({
        success: false,
        message: errorMsg,
        fileName: file.name
      });
    } finally {
      setUploading(false);
      setProgress(0);
      setSnackbar(true);
      setPendingUpload(null);
    }
  }, [onUploadSuccess]);

  const handleClearAndReplace = async () => {
    const currentYear = new Date().getFullYear();
    try {
      setOverrideOpen(false);
      // Vide l'année
      await apiService.clearAnnee(currentYear);
      // Puis lance l'import
      if (pendingUpload) {
        performUpload(pendingUpload);
      }
    } catch (err) {
      console.error('Erreur suppression année:', err);
      setError('❌ Erreur lors de la suppression des données');
      setSnackbar(true);
      setPendingUpload(null);
    }
  };

  const handleCancelOverride = () => {
    setOverrideOpen(false);
    setPendingUpload(null);
  };

  const { getRootProps, getInputProps, isDragActive } = useDropzone({
    onDrop,
    disabled: uploading
  });

  return (
    <>
      <Paper
        {...getRootProps()}
        sx={{
          p: 4,
          textAlign: 'center',
          border: '2px dashed',
          borderColor: isDragActive ? '#f59e0b' : '#e5e7eb',
          bgcolor: isDragActive ? '#fef3c7' : '#fafbfc',
          cursor: uploading ? 'not-allowed' : 'pointer',
          transition: 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
          opacity: uploading ? 0.7 : 1,
          borderRadius: '12px',
          '&:hover': {
            borderColor: '#f59e0b',
            bgcolor: '#fef3c7'
          }
        }}
      >
        <input {...getInputProps()} />

        <Box sx={{ mb: 3 }}>
          {uploading ? (
            <Box sx={{ display: 'flex', justifyContent: 'center', mb: 2 }}>
              <CircularProgress size={56} sx={{ color: '#f59e0b' }} />
            </Box>
          ) : (
            <CloudUploadIcon sx={{ fontSize: 56, color: '#f59e0b', mb: 2 }} />
          )}
        </Box>

        {uploading ? (
          <Box>
            <Typography variant="h6" sx={{ mb: 2, fontWeight: 600, color: '#1f2937' }}>
              Import en cours...
            </Typography>
            {currentFile && (
              <Typography variant="body2" sx={{ mb: 2, color: '#6b7280' }}>
                {currentFile.icon} {currentFile.name} - {currentFile.type}
              </Typography>
            )}
            <LinearProgress 
              variant="determinate" 
              value={progress} 
              sx={{ mb: 2, height: 6, borderRadius: 3 }} 
            />
            <Typography variant="body2" sx={{ color: '#f59e0b', fontWeight: 600 }}>
              {progress}%
            </Typography>
          </Box>
        ) : (
          <>
            <Typography variant="h6" sx={{ mb: 1, fontWeight: 600, color: '#1f2937' }}>
              {isDragActive ? '📥 Déposez le fichier ici' : '⬆️ Télécharger vos données'}
            </Typography>
            <Typography variant="body2" sx={{ mb: 3, color: '#6b7280' }}>
              Cliquez ou glissez-déposez votre fichier
            </Typography>
            <Stack direction="row" spacing={1} justifyContent="center" sx={{ flexWrap: 'wrap', gap: 1 }}>
              <Chip label="📊 Excel" size="small" variant="outlined" />
              <Chip label="📄 FEC" size="small" variant="outlined" />
              <Chip label="📦 Archive" size="small" variant="outlined" />
            </Stack>
          </>
        )}
      </Paper>

      {/* Résultat d'import */}
      {uploadResult && !uploading && (
        <Card 
          sx={{ 
            mt: 3, 
            bgcolor: uploadResult.success ? '#f0fdf4' : '#fef2f2',
            border: `2px solid ${uploadResult.success ? '#10b981' : '#ef4444'}`
          }}
        >
          <CardContent>
            <Box sx={{ display: 'flex', alignItems: 'flex-start', gap: 2 }}>
              <Box sx={{ pt: 0.5 }}>
                {uploadResult.success ? (
                  <CheckCircleIcon sx={{ color: '#10b981', fontSize: 32 }} />
                ) : (
                  <ErrorIcon sx={{ color: '#ef4444', fontSize: 32 }} />
                )}
              </Box>
              <Box sx={{ flex: 1 }}>
                <Typography 
                  variant="h6" 
                  sx={{ 
                    mb: 1, 
                    fontWeight: 600,
                    color: uploadResult.success ? '#10b981' : '#ef4444'
                  }}
                >
                  {uploadResult.success ? '✓ Import réussi' : '✗ Erreur lors de l\'import'}
                </Typography>
                <Typography variant="body2" sx={{ mb: 1, color: '#4b5563' }}>
                  {uploadResult.message}
                </Typography>
                {uploadResult.count && (
                  <Typography variant="body2" sx={{ mb: 1, fontWeight: 500, color: '#059669' }}>
                    📊 {uploadResult.count} enregistrements importés
                  </Typography>
                )}
                <Typography variant="caption" sx={{ color: '#9ca3af' }}>
                  {uploadResult.fileName} • {uploadResult.timestamp}
                </Typography>
              </Box>
            </Box>
          </CardContent>
        </Card>
      )}

      {/* Snackbar erreur */}
      <Snackbar
        open={snackbar && !!error}
        autoHideDuration={6000}
        onClose={() => setSnackbar(false)}
        message={error}
        sx={{
          '& .MuiSnackbarContent-root': {
            backgroundColor: '#ef4444',
            borderRadius: '8px'
          }
        }}
      />

      {/* Dialog Confirmation Override */}
      <Dialog open={overrideOpen} onClose={handleCancelOverride}>
        <DialogTitle>Année déjà existante</DialogTitle>
        <DialogContent sx={{ mt: 2 }}>
          <Typography>
            L'année {new Date().getFullYear()} contient déjà des données.
          </Typography>
          <Typography sx={{ mt: 2, color: '#ef4444', fontWeight: 600 }}>
            Que souhaitez-vous faire?
          </Typography>
        </DialogContent>
        <DialogActions sx={{ p: 2 }}>
          <Button onClick={handleCancelOverride} variant="outlined">
            Annuler
          </Button>
          <Button 
            onClick={handleClearAndReplace}
            variant="contained"
            sx={{ bgcolor: '#ef4444', '&:hover': { bgcolor: '#dc2626' } }}
          >
            Remplacer les données
          </Button>
        </DialogActions>
      </Dialog>
    </>
  );
}
