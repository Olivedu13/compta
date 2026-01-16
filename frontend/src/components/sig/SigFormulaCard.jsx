/**
 * SigFormulaCard - Affichage d'une formule SIG
 */

import React, { useState } from 'react';
import {
  Accordion,
  AccordionDetails,
  AccordionSummary,
  Card,
  CardContent,
  Chip,
  Box,
  Typography,
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableRow,
  Alert,
  AlertTitle,
  Button,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  TextField,
} from '@mui/material';
import {
  ExpandMore as ExpandMoreIcon,
  CheckCircle as CheckCircleIcon,
  Error as ErrorIcon,
  Info as InfoIcon,
  Edit as EditIcon,
} from '@mui/icons-material';

const SigFormulaCard = ({ formula, validationNotes = {}, onValidationSave }) => {
  const [openEdit, setOpenEdit] = useState(false);
  const [notes, setNotes] = useState(validationNotes[formula.id] || '');

  const handleSaveNotes = () => {
    onValidationSave?.(formula.id, notes);
    setOpenEdit(false);
  };

  const hasValidation = validationNotes[formula.id];

  return (
    <>
      <Accordion sx={{ mb: 2, borderLeft: `4px solid ${hasValidation ? '#4caf50' : '#2196f3'}` }}>
        <AccordionSummary expandIcon={<ExpandMoreIcon />}>
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, width: '100%' }}>
            {hasValidation ? (
              <CheckCircleIcon sx={{ color: '#4caf50' }} />
            ) : (
              <InfoIcon sx={{ color: '#2196f3' }} />
            )}
            <Box sx={{ flex: 1 }}>
              <Typography variant="subtitle1" sx={{ fontWeight: 'bold' }}>
                {formula.title}
              </Typography>
              <Typography variant="caption" color="textSecondary">
                {formula.description}
              </Typography>
            </Box>
            <Chip
              label={formula.formula}
              size="small"
              variant="outlined"
              sx={{ fontFamily: 'monospace' }}
            />
          </Box>
        </AccordionSummary>

        <AccordionDetails>
          <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
            {/* Formule */}
            <Box sx={{ p: 2, backgroundColor: '#f5f5f5', borderRadius: 1 }}>
              <Typography variant="caption" sx={{ color: '#999', fontWeight: 'bold' }}>
                FORMULE
              </Typography>
              <Typography variant="body2" sx={{ fontFamily: 'monospace', fontWeight: 'bold', mt: 1 }}>
                {formula.formula}
              </Typography>
            </Box>

            {/* Détails */}
            {formula.details && (
              <Box>
                <Typography variant="subtitle2" sx={{ fontWeight: 'bold', mb: 1 }}>
                  Composition
                </Typography>

                {/* Numérateur */}
                {formula.details.numerator && (
                  <Box sx={{ mb: 2 }}>
                    <Typography variant="caption" sx={{ color: '#4caf50', fontWeight: 'bold' }}>
                      ➕ À AJOUTER
                    </Typography>
                    <Table size="small" sx={{ mt: 1 }}>
                      <TableHead>
                        <TableRow sx={{ backgroundColor: '#f5f5f5' }}>
                          <TableCell sx={{ fontWeight: 'bold' }}>Code</TableCell>
                          <TableCell sx={{ fontWeight: 'bold' }}>Label</TableCell>
                          <TableCell sx={{ fontWeight: 'bold' }}>Bijouterie</TableCell>
                        </TableRow>
                      </TableHead>
                      <TableBody>
                        {(formula.details.numerator || []).map((item, idx) => (
                          <TableRow key={idx}>
                            <TableCell sx={{ fontFamily: 'monospace', fontWeight: 'bold' }}>
                              {item.code}
                            </TableCell>
                            <TableCell>{item.label}</TableCell>
                            <TableCell sx={{ fontSize: '0.85rem' }}>{item.bijouterie}</TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </Box>
                )}

                {/* Dénominateur */}
                {formula.details.denominator && (
                  <Box sx={{ mb: 2 }}>
                    <Typography variant="caption" sx={{ color: '#f44336', fontWeight: 'bold' }}>
                      ➖ À SOUSTRAIRE
                    </Typography>
                    <Table size="small" sx={{ mt: 1 }}>
                      <TableHead>
                        <TableRow sx={{ backgroundColor: '#f5f5f5' }}>
                          <TableCell sx={{ fontWeight: 'bold' }}>Code</TableCell>
                          <TableCell sx={{ fontWeight: 'bold' }}>Label</TableCell>
                          <TableCell sx={{ fontWeight: 'bold' }}>Bijouterie</TableCell>
                        </TableRow>
                      </TableHead>
                      <TableBody>
                        {(formula.details.denominator || []).map((item, idx) => (
                          <TableRow key={idx}>
                            <TableCell sx={{ fontFamily: 'monospace', fontWeight: 'bold' }}>
                              {item.code}
                            </TableCell>
                            <TableCell>{item.label}</TableCell>
                            <TableCell sx={{ fontSize: '0.85rem' }}>{item.bijouterie}</TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </Box>
                )}

                {/* Déductions */}
                {formula.details.deductions && (
                  <Box sx={{ mb: 2 }}>
                    <Typography variant="caption" sx={{ color: '#f44336', fontWeight: 'bold' }}>
                      ➖ À SOUSTRAIRE
                    </Typography>
                    <Table size="small" sx={{ mt: 1 }}>
                      <TableHead>
                        <TableRow sx={{ backgroundColor: '#f5f5f5' }}>
                          <TableCell sx={{ fontWeight: 'bold' }}>Code</TableCell>
                          <TableCell sx={{ fontWeight: 'bold' }}>Label</TableCell>
                          <TableCell sx={{ fontWeight: 'bold' }}>Bijouterie</TableCell>
                        </TableRow>
                      </TableHead>
                      <TableBody>
                        {(formula.details.deductions || []).map((item, idx) => (
                          <TableRow key={idx}>
                            <TableCell sx={{ fontFamily: 'monospace', fontWeight: 'bold' }}>
                              {item.code}
                            </TableCell>
                            <TableCell>{item.label}</TableCell>
                            <TableCell sx={{ fontSize: '0.85rem' }}>{item.bijouterie}</TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </Box>
                )}
              </Box>
            )}

            {/* Points de Validation */}
            {formula.validationPoints && (
              <Box>
                <Typography variant="subtitle2" sx={{ fontWeight: 'bold', mb: 1 }}>
                  Points de Vérification
                </Typography>
                {(formula.validationPoints || []).map((point, idx) => (
                  <Alert key={idx} severity="success" sx={{ mb: 1 }}>
                    <Typography variant="body2">{point}</Typography>
                  </Alert>
                ))}
              </Box>
            )}

            {/* Préoccupations */}
            {formula.concerns && (
              <Box>
                <Typography variant="subtitle2" sx={{ fontWeight: 'bold', mb: 1 }}>
                  Attention
                </Typography>
                {(formula.concerns || []).map((concern, idx) => (
                  <Alert key={idx} severity="warning" sx={{ mb: 1 }}>
                    <Typography variant="body2">• {concern}</Typography>
                  </Alert>
                ))}
              </Box>
            )}

            {/* Actions */}
            <Box sx={{ display: 'flex', gap: 1, justifyContent: 'flex-end', mt: 2 }}>
              <Button
                startIcon={<EditIcon />}
                variant="outlined"
                size="small"
                onClick={() => setOpenEdit(true)}
              >
                Ajouter Note
              </Button>
              {hasValidation && (
                <Chip
                  label="✓ Validé"
                  color="success"
                  size="small"
                />
              )}
            </Box>
          </Box>
        </AccordionDetails>
      </Accordion>

      {/* Dialog Édition */}
      <Dialog open={openEdit} onClose={() => setOpenEdit(false)} maxWidth="sm" fullWidth>
        <DialogTitle>Notes de Validation - {formula.title}</DialogTitle>
        <DialogContent sx={{ pt: 2 }}>
          <TextField
            fullWidth
            multiline
            rows={4}
            label="Vos notes"
            placeholder="Ex: Formule vérifiée, tous les comptes présents..."
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
            variant="outlined"
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpenEdit(false)}>Annuler</Button>
          <Button onClick={handleSaveNotes} variant="contained" color="primary">
            Enregistrer
          </Button>
        </DialogActions>
      </Dialog>
    </>
  );
};

export default SigFormulaCard;
