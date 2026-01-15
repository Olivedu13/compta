/**
 * SigFormulasLibrary - Bibliothèque complète des formules SIG
 */

export const sigFormulas = [
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
    title: 'EBE / EBITDA',
    description: 'Résultat d\'exploitation avant intérêts, impôts, amortissements',
    formula: 'VA + 74 - (63 + 64 + 68*)',
    details: {
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
    formula: 'EBE - 681 (Amortissements)',
    details: {
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
    formula: '76 (Produits financiers) - 69 (Charges financières)',
    details: {
      deductions: [
        {
          code: '69',
          label: 'Charges financières',
          bijouterie: 'Intérêts emprunts (crédit exploitation, crédit investissement)',
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
    formula: 'RE + RF - Impôts (69)',
    details: {
      numerator: [
        {
          code: 'RE',
          label: 'Résultat d\'Exploitation',
          bijouterie: 'Capacité bénéficiaire du métier',
        },
      ],
      denominator: [
        {
          code: '69',
          label: 'Impôt sur société',
          bijouterie: 'IS ou CFP selon régime',
        },
      ],
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
