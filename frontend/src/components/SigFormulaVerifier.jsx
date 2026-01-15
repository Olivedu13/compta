import React, { useState } from 'react';
import {
    Accordion,
    AccordionDetails,
    AccordionSummary,
    Box,
    Card,
    CardContent,
    Chip,
    Divider,
    Grid,
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
    Button,
    Dialog,
    DialogTitle,
    DialogContent,
    DialogActions,
} from '@mui/material';
import {
    ExpandMore as ExpandMoreIcon,
    CheckCircle as CheckCircleIcon,
    Error as ErrorIcon,
    Info as InfoIcon,
    Edit as EditIcon,
} from '@mui/icons-material';

/**
 * SigFormulaVerifier - Composant de vérification des formules SIG
 * 
 * Affiche les formules de calcul du SIG (Soldes Intermédiaires de Gestion)
 * avec documentation expert comptable et permet la vérification ensemble.
 * 
 * Étape 2 : Vérification des formules et données utilisées
 */
export default function SigFormulaVerifier({ analysisData, onFormulaValidation }) {
    const [openFormula, setOpenFormula] = useState(null);
    const [validationNotes, setValidationNotes] = useState({});
    const [openEdit, setOpenEdit] = useState(null);

    /**
     * Formules SIG selon le Plan Comptable Général 2025
     * Adaptées à la bijouterie avec comptes spécifiques
     */
    const sigFormulas = [
        {
            id: 'marge_production',
            title: 'Marge de Production (MP)',
            description: 'Différence entre production et consommations matières',
            formula: '(70 + 71 + 72) - (601 + 602 ± 603)',
            details: {
                numerator: [
                    {
                        code: '70',
                        label: 'Ventes de marchandises',
                        bijouterie: 'Bijoux fabriqués / vendus',
                    },
                    {
                        code: '71',
                        label: 'Production stockée',
                        bijouterie: 'Pièces en cours/stock travail',
                    },
                    {
                        code: '72',
                        label: 'Production immobilisée',
                        bijouterie: 'Éléments incorporés au patrimoine',
                    },
                ],
                denominator: [
                    {
                        code: '601',
                        label: 'Achats de matières premières',
                        bijouterie: 'Or, argent, pierres précieuses',
                    },
                    {
                        code: '602',
                        label: 'Achats de fournitures',
                        bijouterie: 'Composants, outils, consommables',
                    },
                    {
                        code: '603',
                        label: 'Variation stocks',
                        bijouterie: 'Porte un signe (+ variation positive)',
                    },
                ],
            },
            validationPoints: [
                '✓ Les comptes 70, 71, 72 doivent être crédités (produits)',
                '✓ Les comptes 601, 602 doivent être débités (charges)',
                '✓ La variation 603 inclut stock initial et final',
                '✓ Pour bijouterie: vérifier valorisation stocks métaux précieux',
            ],
            concerns: [
                'Vérifier les prix d\'achat vs prix marché (métaux précieux volatiles)',
                'La variation de stock doit inclure tous les en-cours bijouterie',
                'Attention aux déchets et pertes de transformation',
            ],
        },

        {
            id: 'valeur_ajoutee',
            title: 'Valeur Ajoutée (VA)',
            description: 'Richesse créée par l\'entreprise',
            formula: 'MP - (61 + 62)',
            details: {
                base: 'Marge de Production',
                deductions: [
                    {
                        code: '61',
                        label: 'Services extérieurs',
                        bijouterie: 'Sous-traitance gravure, sertissage externe',
                    },
                    {
                        code: '62',
                        label: 'Autres services extérieurs',
                        bijouterie: 'Frais divers (assurance marchandise, etc.)',
                    },
                ],
            },
            validationPoints: [
                '✓ VA représente la vraie richesse créée',
                '✓ Pour bijouterie artisanale: doit être significative (c\'est le métier)',
                '✓ Vérifier que la sous-traitance n\'est pas excessive',
            ],
            concerns: [
                'Si VA est faible: l\'entreprise ne crée pas beaucoup de valeur',
                'Pour bijouterie luxe: VA doit refléter le travail de création',
            ],
        },

        {
            id: 'ebe',
            title: 'EBE / EBITDA (Résultat d\'exploitation avant intérêts, impôts, amortissements et dépréciations)',
            description: 'Capacité d\'autofinancement opérationnel',
            formula: 'VA + 74 - (63 + 64 + 68*)',
            details: {
                base: 'Valeur Ajoutée',
                additions: [
                    {
                        code: '74',
                        label: 'Produits exceptionnels',
                        bijouterie: 'Ventes d\'or de récupération, rebuts valorisés',
                    },
                ],
                deductions: [
                    {
                        code: '63',
                        label: 'Impôts et taxes',
                        bijouterie: 'Taxes foncières, CVAE, permis exploitation',
                    },
                    {
                        code: '64',
                        label: 'Charges de personnel',
                        bijouterie: 'Salaires apprentis bijoutiers + patron',
                    },
                    {
                        code: '68*',
                        label: 'ATTENTION: N\'inclure QUE les éléments exceptionnels',
                        bijouterie: 'Normalement pas inclus (les amortissements sont à 681)',
                    },
                ],
            },
            validationPoints: [
                '✓ EBE positif = entreprise génère du cash opérationnel',
                '✓ Pour bijouterie: doit être positif (sinon problème métier)',
                '✓ Les charges de personnel (64) sont significatives (apprentissage)',
            ],
            concerns: [
                'Pour bijouterie: comparer VA vs 64 (part personnel)',
                'Si EBE négatif: revoir le modèle économique',
                'Attention aux impôts et taxes locales (atelier)',
            ],
        },

        {
            id: 'resultat_exploitation',
            title: 'Résultat d\'Exploitation (RE)',
            description: 'Capacité bénéficiaire du métier',
            formula: 'EBE - 681 (Amortissements et provisions)',
            details: {
                base: 'EBE/EBITDA',
                deductions: [
                    {
                        code: '681',
                        label: 'Amortissements et provisions',
                        bijouterie: 'Outillage, mobilier atelier, équipement',
                    },
                ],
            },
            validationPoints: [
                '✓ Amortissements = charge non-cash (important pour cash flow)',
                '✓ Pour bijouterie: matériel peut être amortissable (tours, établis)',
                '✓ RE positif = métier rentable en soi',
            ],
            concerns: [
                'Les amortissements doivent être cohérents avec immobilisations',
                'Vérifier la durée d\'amortissement des outils bijouterie (5-10 ans)',
                'RE < 0 mais EBE > 0: amortissements excessifs ou immobilisations trop fortes',
            ],
        },

        {
            id: 'resultat_financier',
            title: 'Résultat Financier (RF)',
            description: 'Impact des financements et placements',
            formula: '69 (Intérêts, frais financiers) - 76 (Produits financiers)',
            details: {
                charges: [
                    {
                        code: '69',
                        label: 'Charges financières',
                        bijouterie: 'Intérêts emprunts (crédit exploitation, crédit investissement)',
                    },
                ],
                products: [
                    {
                        code: '76',
                        label: 'Produits financiers',
                        bijouterie: 'Intérêts comptes, dividendes (rare pour atelier)',
                    },
                ],
            },
            validationPoints: [
                '✓ RF généralement négatif (coût des financements)',
                '✓ Pour bijouterie: dépend du niveau d\'endettement',
                '✓ RF négatif = normal si entreprise investit',
            ],
            concerns: [
                'Si RF très négatif: vérifier taux et montant emprunts',
                'Bijouterie: peut avoir crédit fournisseurs (stocks or) important',
            ],
        },

        {
            id: 'resultat_net',
            title: 'Résultat Net (RN)',
            description: 'Bénéfice ou perte finale',
            formula: 'RE + RF - 69 (Impôt sur société si applicable)',
            details: {
                note: 'Formule complète = RE + RF - impôt (compte 69)',
            },
            validationPoints: [
                '✓ RN positif = bénéfice distribué/capitalisé',
                '✓ RN négatif = perte affectée au capital ou reportée',
                '✓ Pour bijouterie: RN doit être positif et proportionné au travail',
            ],
            concerns: [
                'Comparer RN avec salaire patron (si auto-entrepreneur)',
                'Bijouterie: souvent micro-entreprise => pas d\'IS',
                'Vérifier cohérence RN avec trésorerie réelle',
            ],
        },
    ];

    const handleOpenFormula = (formulaId) => {
        setOpenFormula(formulaId);
    };

    const handleCloseFormula = () => {
        setOpenFormula(null);
    };

    const handleValidationSave = (formulaId, notes) => {
        setValidationNotes({
            ...validationNotes,
            [formulaId]: notes,
        });
        onFormulaValidation?.(formulaId, notes);
        setOpenEdit(null);
    };

    const formatAccountList = (accounts) => {
        return accounts.map((a) => `${a.code}`).join(' + ');
    };

    return (
        <Box sx={{ py: 3 }}>
            <Card sx={{ mb: 3, backgroundColor: 'info.light' }}>
                <CardContent>
                    <Typography variant="h5" gutterBottom>
                        🧮 Vérification des Formules SIG
                    </Typography>
                    <Typography variant="body2" color="textSecondary" paragraph>
                        Cette section permet de vérifier ensemble la cohérence des formules de calcul
                        du Solde Intermédiaire de Gestion (SIG) et les données utilisées pour les
                        remplir.
                    </Typography>
                    <Alert severity="info" sx={{ mt: 2 }}>
                        <AlertTitle>Approche d'Expert Comptable</AlertTitle>
                        Chaque formule est documentée avec son contexte métier bijouterie. Veuillez
                        vérifier la pertinence des comptes et l'absence d'erreur de calcul.
                    </Alert>
                </CardContent>
            </Card>

            <Stack spacing={2}>
                {sigFormulas.map((sig) => (
                    <Accordion
                        key={sig.id}
                        defaultExpanded={false}
                        sx={{
                            border: '1px solid',
                            borderColor: 'divider',
                            '&.Mui-expanded': { mb: 2 },
                        }}
                    >
                        <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                            <Box sx={{ display: 'flex', alignItems: 'center', gap: 2, flex: 1 }}>
                                <CheckCircleIcon sx={{ color: 'success.main' }} fontSize="small" />
                                <Box>
                                    <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                                        {sig.title}
                                    </Typography>
                                    <Typography variant="caption" color="textSecondary">
                                        {sig.description}
                                    </Typography>
                                </Box>
                                {validationNotes[sig.id] && (
                                    <Chip
                                        label="Validé"
                                        size="small"
                                        color="success"
                                        variant="outlined"
                                    />
                                )}
                            </Box>
                        </AccordionSummary>

                        <AccordionDetails>
                            <Stack spacing={3}>
                                {/* Formule principale */}
                                <Box sx={{ backgroundColor: 'primary.light', p: 2, borderRadius: 1 }}>
                                    <Typography variant="caption" color="textSecondary">
                                        Formule Mathématique
                                    </Typography>
                                    <Typography
                                        variant="h6"
                                        sx={{
                                            fontFamily: 'monospace',
                                            backgroundColor: 'primary.main',
                                            color: 'primary.contrastText',
                                            p: 1.5,
                                            borderRadius: 1,
                                            mt: 1,
                                        }}
                                    >
                                        {sig.formula}
                                    </Typography>
                                </Box>

                                {/* Détails des comptes */}
                                {sig.details.numerator && (
                                    <Box>
                                        <Typography variant="subtitle2" sx={{ fontWeight: 600, mb: 1 }}>
                                            ➕ Éléments Additionnés
                                        </Typography>
                                        <TableContainer component={Paper} variant="outlined">
                                            <Table size="small">
                                                <TableHead>
                                                    <TableRow sx={{ backgroundColor: 'success.light' }}>
                                                        <TableCell>Compte</TableCell>
                                                        <TableCell>Libellé PCG</TableCell>
                                                        <TableCell>Contexte Bijouterie</TableCell>
                                                    </TableRow>
                                                </TableHead>
                                                <TableBody>
                                                    {sig.details.numerator.map((item) => (
                                                        <TableRow key={item.code}>
                                                            <TableCell>
                                                                <Chip
                                                                    label={item.code}
                                                                    size="small"
                                                                    variant="filled"
                                                                />
                                                            </TableCell>
                                                            <TableCell>{item.label}</TableCell>
                                                            <TableCell>
                                                                <Typography variant="caption">
                                                                    {item.bijouterie}
                                                                </Typography>
                                                            </TableCell>
                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>
                                        </TableContainer>
                                    </Box>
                                )}

                                {sig.details.denominator && (
                                    <Box>
                                        <Typography variant="subtitle2" sx={{ fontWeight: 600, mb: 1 }}>
                                            ➖ Éléments Soustraits
                                        </Typography>
                                        <TableContainer component={Paper} variant="outlined">
                                            <Table size="small">
                                                <TableHead>
                                                    <TableRow sx={{ backgroundColor: 'error.light' }}>
                                                        <TableCell>Compte</TableCell>
                                                        <TableCell>Libellé PCG</TableCell>
                                                        <TableCell>Contexte Bijouterie</TableCell>
                                                    </TableRow>
                                                </TableHead>
                                                <TableBody>
                                                    {sig.details.denominator.map((item) => (
                                                        <TableRow key={item.code}>
                                                            <TableCell>
                                                                <Chip
                                                                    label={item.code}
                                                                    size="small"
                                                                    variant="filled"
                                                                />
                                                            </TableCell>
                                                            <TableCell>{item.label}</TableCell>
                                                            <TableCell>
                                                                <Typography variant="caption">
                                                                    {item.bijouterie}
                                                                </Typography>
                                                            </TableCell>
                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>
                                        </TableContainer>
                                    </Box>
                                )}

                                {sig.details.deductions && (
                                    <Box>
                                        <Typography variant="subtitle2" sx={{ fontWeight: 600, mb: 1 }}>
                                            ➖ Éléments Soustraits
                                        </Typography>
                                        <TableContainer component={Paper} variant="outlined">
                                            <Table size="small">
                                                <TableHead>
                                                    <TableRow sx={{ backgroundColor: 'error.light' }}>
                                                        <TableCell>Compte</TableCell>
                                                        <TableCell>Libellé PCG</TableCell>
                                                        <TableCell>Contexte Bijouterie</TableCell>
                                                    </TableRow>
                                                </TableHead>
                                                <TableBody>
                                                    {sig.details.deductions.map((item) => (
                                                        <TableRow key={item.code}>
                                                            <TableCell>
                                                                <Chip
                                                                    label={item.code}
                                                                    size="small"
                                                                    variant="filled"
                                                                />
                                                            </TableCell>
                                                            <TableCell>{item.label}</TableCell>
                                                            <TableCell>
                                                                <Typography variant="caption">
                                                                    {item.bijouterie}
                                                                </Typography>
                                                            </TableCell>
                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>
                                        </TableContainer>
                                    </Box>
                                )}

                                {sig.details.additions && (
                                    <Box>
                                        <Typography variant="subtitle2" sx={{ fontWeight: 600, mb: 1 }}>
                                            ➕ Éléments Additionnés
                                        </Typography>
                                        <TableContainer component={Paper} variant="outlined">
                                            <Table size="small">
                                                <TableHead>
                                                    <TableRow sx={{ backgroundColor: 'success.light' }}>
                                                        <TableCell>Compte</TableCell>
                                                        <TableCell>Libellé PCG</TableCell>
                                                        <TableCell>Contexte Bijouterie</TableCell>
                                                    </TableRow>
                                                </TableHead>
                                                <TableBody>
                                                    {sig.details.additions.map((item) => (
                                                        <TableRow key={item.code}>
                                                            <TableCell>
                                                                <Chip
                                                                    label={item.code}
                                                                    size="small"
                                                                    variant="filled"
                                                                />
                                                            </TableCell>
                                                            <TableCell>{item.label}</TableCell>
                                                            <TableCell>
                                                                <Typography variant="caption">
                                                                    {item.bijouterie}
                                                                </Typography>
                                                            </TableCell>
                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>
                                        </TableContainer>
                                    </Box>
                                )}

                                <Divider />

                                {/* Points de validation */}
                                <Box>
                                    <Typography variant="subtitle2" sx={{ fontWeight: 600, mb: 1 }}>
                                        ✓ Points de Validation
                                    </Typography>
                                    <Stack spacing={1}>
                                        {sig.validationPoints.map((point, idx) => (
                                            <Box
                                                key={idx}
                                                sx={{
                                                    display: 'flex',
                                                    gap: 1,
                                                    p: 1,
                                                    backgroundColor: 'success.light',
                                                    borderRadius: 1,
                                                }}
                                            >
                                                <CheckCircleIcon
                                                    fontSize="small"
                                                    sx={{ color: 'success.main', mt: 0.5 }}
                                                />
                                                <Typography variant="caption">{point}</Typography>
                                            </Box>
                                        ))}
                                    </Stack>
                                </Box>

                                {/* Préoccupations métier */}
                                <Box>
                                    <Typography variant="subtitle2" sx={{ fontWeight: 600, mb: 1 }}>
                                        ⚠️ Préoccupations Métier
                                    </Typography>
                                    <Stack spacing={1}>
                                        {sig.concerns.map((concern, idx) => (
                                            <Alert key={idx} severity="warning" sx={{ mb: 0 }}>
                                                <Typography variant="caption">{concern}</Typography>
                                            </Alert>
                                        ))}
                                    </Stack>
                                </Box>

                                {/* Bouton de validation */}
                                <Box sx={{ display: 'flex', gap: 1, justifyContent: 'flex-end', pt: 2 }}>
                                    <Button
                                        variant="contained"
                                        size="small"
                                        color="success"
                                        startIcon={<CheckCircleIcon />}
                                        onClick={() => handleOpenFormula(sig.id)}
                                    >
                                        Valider la Formule
                                    </Button>
                                </Box>
                            </Stack>
                        </AccordionDetails>
                    </Accordion>
                ))}
            </Stack>

            {/* Dialog de validation */}
            <ValidateFormulaDialog
                open={openFormula !== null}
                formulaId={openFormula}
                formula={sigFormulas.find((f) => f.id === openFormula)}
                currentNotes={validationNotes[openFormula]}
                onClose={handleCloseFormula}
                onSave={(notes) => {
                    if (openFormula) {
                        handleValidationSave(openFormula, notes);
                        handleCloseFormula();
                    }
                }}
            />
        </Box>
    );
}

/**
 * Dialog de validation des formules
 * Permet d'ajouter des notes et de confirmer la validation
 */
function ValidateFormulaDialog({ open, formulaId, formula, currentNotes, onClose, onSave }) {
    const [notes, setNotes] = React.useState(currentNotes || '');

    React.useEffect(() => {
        setNotes(currentNotes || '');
    }, [currentNotes, formulaId]);

    return (
        <Dialog open={open} maxWidth="sm" fullWidth>
            <DialogTitle>Valider la Formule: {formula?.title}</DialogTitle>
            <DialogContent sx={{ pt: 2 }}>
                <Typography variant="body2" paragraph>
                    Avez-vous vérifié que les comptes utilisés sont corrects et que la formule
                    est appropriée pour le contexte bijouterie ?
                </Typography>
                <Typography variant="subtitle2" sx={{ fontWeight: 600, mb: 1 }}>
                    Ajouter des notes (optionnel):
                </Typography>
                <Box
                    component="textarea"
                    value={notes}
                    onChange={(e) => setNotes(e.target.value)}
                    placeholder="Ex: Vérification effectuée, tous les comptes présents..."
                    sx={{
                        width: '100%',
                        minHeight: 100,
                        p: 1,
                        border: '1px solid',
                        borderColor: 'divider',
                        borderRadius: 1,
                        fontFamily: 'monospace',
                        fontSize: '0.875rem',
                    }}
                />
            </DialogContent>
            <DialogActions>
                <Button onClick={onClose} variant="outlined">
                    Annuler
                </Button>
                <Button
                    onClick={() => onSave(notes)}
                    variant="contained"
                    color="success"
                    startIcon={<CheckCircleIcon />}
                >
                    Confirmer la Validation
                </Button>
            </DialogActions>
        </Dialog>
    );
}
