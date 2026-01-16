/**
 * SigFormulaVerifier - Composant refactorisé (Phase 3b)
 * Vérification des formules SIG (Soldes Intermédiaires de Gestion)
 * Réduit de 647 → 75 lignes
 */

import React, { useState } from 'react';
import { Box, Card, CardContent, Typography, Alert, AlertTitle } from '@mui/material';
import { ErrorBoundary } from './common';
import SigFormulaCard from './sig/SigFormulaCard';
import { sigFormulas } from './sig/SigFormulasLibrary';

const SigFormulaVerifier = ({ analysisData, onFormulaValidation }) => {
  const [validationNotes, setValidationNotes] = useState({});

  const handleValidationSave = (formulaId, notes) => {
    setValidationNotes({
      ...validationNotes,
      [formulaId]: notes,
    });
    onFormulaValidation?.(formulaId, notes);
  };

  return (
    <ErrorBoundary>
      <Box sx={{ py: 3 }}>
        <Card sx={{ mb: 3, backgroundColor: 'info.light' }}>
          <CardContent>
            <Typography variant="h5" gutterBottom sx={{ fontWeight: 'bold' }}>
              🧮 Vérification des Formules SIG
            </Typography>
            <Typography variant="body2" color="textSecondary" paragraph>
              Cette section permet de vérifier ensemble la cohérence des formules de calcul
              du Solde Intermédiaire de Gestion (SIG) et les données utilisées pour les remplir.
            </Typography>
            <Alert severity="info" sx={{ mt: 2 }}>
              <AlertTitle>Approche d'Expert Comptable</AlertTitle>
              Chaque formule est documentée avec son contexte métier bijouterie. Veuillez
              vérifier la pertinence des comptes et l'absence d'erreur de calcul.
            </Alert>
          </CardContent>
        </Card>

        {/* Liste des Formules */}
        <Box>
          {(sigFormulas || []).map((formula) => (
            <SigFormulaCard
              key={formula.id}
              formula={formula}
              validationNotes={validationNotes}
              onValidationSave={handleValidationSave}
            />
          ))}
        </Box>

        {/* Résumé Validation */}
        {Object.keys(validationNotes).length > 0 && (
          <Card sx={{ mt: 3, backgroundColor: '#e8f5e9', borderLeft: '4px solid #4caf50' }}>
            <CardContent>
              <Typography variant="subtitle2" sx={{ fontWeight: 'bold', mb: 1 }}>
                ✓ {Object.keys(validationNotes).length}/{sigFormulas.length} formules validées
              </Typography>
              <Typography variant="body2" color="textSecondary">
                Continuez la vérification des formules restantes.
              </Typography>
            </CardContent>
          </Card>
        )}
      </Box>
    </ErrorBoundary>
  );
};

export default SigFormulaVerifier;
