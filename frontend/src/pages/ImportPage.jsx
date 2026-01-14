/**
 * Page Import - Minimaliste & Moderne
 * Détection automatique du type de fichier
 */

import React, { useState } from 'react';
import {
  Typography,
  Box,
  Paper,
  Alert,
  Card,
  CardContent,
  List,
  ListItem,
  ListItemIcon,
  ListItemText,
  Stack,
  Divider,
  Container
} from '@mui/material';
import DescriptionIcon from '@mui/icons-material/Description';
import CheckCircleIcon from '@mui/icons-material/CheckCircle';
import UploadZone from '../components/UploadZone';

export default function ImportPage() {
  const [importStatus, setImportStatus] = useState(null);

  const handleUploadSuccess = (result) => {
    setImportStatus({
      type: 'success',
      message: result.message,
      details: result
    });
  };

  return (
    <Container maxWidth="lg">
      <Box sx={{ py: 4 }}>
        {/* Header */}
        <Box sx={{ mb: 4 }}>
          <Typography 
            variant="h3" 
            sx={{ 
              fontWeight: 700,
              color: '#0f172a',
              mb: 1 
            }}
          >
            Importer vos Données
          </Typography>
          <Typography 
            variant="body1" 
            sx={{ 
              color: '#64748b',
              maxWidth: '600px'
            }}
          >
            Téléchargez vos fichiers FEC, Excel ou archives. Détection automatique du format.
          </Typography>
        </Box>

        {/* Main Upload Zone */}
        <Card sx={{ mb: 4 }}>
          <CardContent sx={{ p: 4 }}>
            <UploadZone onUploadSuccess={handleUploadSuccess} />
          </CardContent>
        </Card>

        {/* Formats Supportés */}
        <Typography 
          variant="h5" 
          sx={{ 
            fontWeight: 600,
            color: '#0f172a',
            mb: 3 
          }}
        >
          Formats Acceptés
        </Typography>

        <Stack direction={{ xs: 'column', md: 'row' }} spacing={3} sx={{ mb: 4 }}>
          {/* FEC Format */}
          <Card sx={{ flex: 1 }}>
            <CardContent>
              <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
                <Box 
                  sx={{ 
                    width: 40, 
                    height: 40, 
                    borderRadius: '8px',
                    backgroundColor: '#f0f9ff',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    mr: 2
                  }}
                >
                  <span sx={{ fontSize: '1.5rem' }}>📄</span>
                </Box>
                <Typography variant="h6" sx={{ fontWeight: 600 }}>
                  Fichier FEC
                </Typography>
              </Box>
              <Typography variant="body2" sx={{ color: '#64748b', mb: 2 }}>
                Fichiers d'écritures comptables standards
              </Typography>
              <Stack spacing={1}>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                  <CheckCircleIcon sx={{ fontSize: 18, color: '#10b981' }} />
                  <Typography variant="caption" sx={{ color: '#64748b' }}>
                    .txt (Tab-séparé)
                  </Typography>
                </Box>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                  <CheckCircleIcon sx={{ fontSize: 18, color: '#10b981' }} />
                  <Typography variant="caption" sx={{ color: '#64748b' }}>
                    .csv (Pipe-séparé)
                  </Typography>
                </Box>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                  <CheckCircleIcon sx={{ fontSize: 18, color: '#10b981' }} />
                  <Typography variant="caption" sx={{ color: '#64748b' }}>
                    18 colonnes obligatoires
                  </Typography>
                </Box>
              </Stack>
            </CardContent>
          </Card>

          {/* Excel Format */}
          <Card sx={{ flex: 1 }}>
            <CardContent>
              <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
                <Box 
                  sx={{ 
                    width: 40, 
                    height: 40, 
                    borderRadius: '8px',
                    backgroundColor: '#f0fdf4',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    mr: 2
                  }}
                >
                  <span sx={{ fontSize: '1.5rem' }}>📊</span>
                </Box>
                <Typography variant="h6" sx={{ fontWeight: 600 }}>
                  Fichier Excel
                </Typography>
              </Box>
              <Typography variant="body2" sx={{ color: '#64748b', mb: 2 }}>
                Données de balance ou écritures
              </Typography>
              <Stack spacing={1}>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                  <CheckCircleIcon sx={{ fontSize: 18, color: '#10b981' }} />
                  <Typography variant="caption" sx={{ color: '#64748b' }}>
                    .xlsx (recommended)
                  </Typography>
                </Box>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                  <CheckCircleIcon sx={{ fontSize: 18, color: '#10b981' }} />
                  <Typography variant="caption" sx={{ color: '#64748b' }}>
                    .xls (legacy)
                  </Typography>
                </Box>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                  <CheckCircleIcon sx={{ fontSize: 18, color: '#10b981' }} />
                  <Typography variant="caption" sx={{ color: '#64748b' }}>
                    En-têtes standards
                  </Typography>
                </Box>
              </Stack>
            </CardContent>
          </Card>

          {/* Archive Format */}
          <Card sx={{ flex: 1 }}>
            <CardContent>
              <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
                <Box 
                  sx={{ 
                    width: 40, 
                    height: 40, 
                    borderRadius: '8px',
                    backgroundColor: '#fffbeb',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    mr: 2
                  }}
                >
                  <span sx={{ fontSize: '1.5rem' }}>📦</span>
                </Box>
                <Typography variant="h6" sx={{ fontWeight: 600 }}>
                  Archive Compressée
                </Typography>
              </Box>
              <Typography variant="body2" sx={{ color: '#64748b', mb: 2 }}>
                Plusieurs fichiers en une archive
              </Typography>
              <Stack spacing={1}>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                  <CheckCircleIcon sx={{ fontSize: 18, color: '#10b981' }} />
                  <Typography variant="caption" sx={{ color: '#64748b' }}>
                    .tar.gz (TAR + Gzip)
                  </Typography>
                </Box>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                  <CheckCircleIcon sx={{ fontSize: 18, color: '#10b981' }} />
                  <Typography variant="caption" sx={{ color: '#64748b' }}>
                    .tar (TAR)
                  </Typography>
                </Box>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                  <CheckCircleIcon sx={{ fontSize: 18, color: '#10b981' }} />
                  <Typography variant="caption" sx={{ color: '#64748b' }}>
                    Jusqu'à 500k écritures
                  </Typography>
                </Box>
              </Stack>
            </CardContent>
          </Card>
        </Stack>

        {/* Info Section */}
        <Paper sx={{ p: 3, backgroundColor: '#f0f9ff', border: '1px solid #e0f2fe' }}>
          <Typography variant="h6" sx={{ fontWeight: 600, mb: 2 }}>
            📋 Spécifications FEC
          </Typography>
          <Typography variant="body2" sx={{ color: '#64748b', mb: 2 }}>
            Le fichier FEC doit contenir exactement 18 colonnes dans cet ordre:
          </Typography>
          <List dense>
            {[
              'JournalCode, JournalLib, EcritureNum, EcritureDate',
              'CompteNum, CompteLib, CompAuxNum, CompAuxLib',
              'PieceRef, PieceDate, EcritureLib',
              'Debit, Credit, EcritureLet, DateLet',
              'ValidDate, MontantDevise, IdDevise'
            ].map((item, idx) => (
              <ListItem key={idx} disableGutters dense>
                <ListItemIcon sx={{ minWidth: 24 }}>
                  <DescriptionIcon fontSize="small" sx={{ color: '#0ea5e9' }} />
                </ListItemIcon>
                <ListItemText 
                  primary={item}
                  primaryTypographyProps={{ variant: 'caption', color: '#64748b' }}
                />
              </ListItem>
            ))}
          </List>
        </Paper>

        {/* Status message */}
        {importStatus && (
          <Alert 
            severity={importStatus.type} 
            sx={{ 
              mt: 4,
              borderRadius: '10px'
            }}
          >
            <Typography variant="body2" sx={{ fontWeight: 500 }}>
              {importStatus.message}
            </Typography>
          </Alert>
        )}
      </Box>
    </Container>
  );
}
