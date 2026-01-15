# 📋 Consignes Architecturales - Compta Project

**Version**: 1.0  
**Date**: Janvier 2025  
**Statut**: ✅ Stable - À RESPECTER ABSOLUMENT pour toutes les nouvelles créations via prompt

---

## 1. 🏗️ Structure du Projet

### 1.1 Organisation des Répertoires

```
/frontend
├── public/                    # Fichiers statiques
├── src/
│   ├── components/
│   │   ├── common/           # Composants réutilisables (LoadingOverlay, ErrorBoundary, etc.)
│   │   │   └── __tests__/    # Tests unitaires des composants common
│   │   ├── charts/           # Composants d'analyse avancée
│   │   ├── dashboard/        # Composants du tableau de bord
│   │   ├── sig/              # Composants SIG
│   │   └── Layout.jsx        # Conteneur principal
│   ├── pages/
│   │   ├── Dashboard.jsx     # Page tableau de bord
│   │   ├── BalancePage.jsx   # Page bilan
│   │   ├── ImportPage.jsx    # Page import
│   │   └── SIGPage.jsx       # Page SIG
│   ├── services/
│   │   └── api.js            # Couche API centralisée
│   ├── theme/                # Design system
│   │   ├── designTokens.js   # Jetons de design (couleurs, spacing, etc.)
│   │   ├── animations.js     # Animations et transitions
│   │   ├── responsive.js     # Utilités responsives
│   │   ├── theme.js          # Thème Material-UI
│   │   └── index.js          # Barrel export
│   ├── hooks/                # Hooks React personnalisés
│   ├── App.jsx               # Composant root
│   ├── index.jsx             # Point d'entrée
│   └── setupTests.js         # Configuration tests
├── jest.config.js            # Configuration Jest
├── vite.config.js            # Configuration Vite
└── package.json

/backend
├── config/
│   ├── Database.php          # Connexion BD
│   ├── Logger.php            # Logging
│   ├── Router.php            # Routeur principal
│   └── schema.sql            # Schéma BD
├── models/                   # Classes métier
├── services/
│   ├── ImportService.php     # Import FEC
│   └── SigCalculator.php     # Calcul SIG
└── logs/

/public_html                  # Répertoire public serveur
├── api/                      # Endpoints API
│   ├── index.php             # API v1 principale
│   └── simple-import.php     # Import simplifié
└── [pages simples]           # Pages PHP simples
```

### 1.2 Conventions de Nommage

#### Fichiers de Composants
- **Format**: `PascalCase.jsx` (ex: `DashboardKPICard.jsx`)
- **Fichiers test**: `ComponentName.test.js`
- **Répertoires**: `camelCase` (ex: `components/charts/`, `pages/`)
- **Styles**: Importés via thème centralisé

#### Fichiers Utilitaires
- **Format**: `camelCase.js` (ex: `formatCurrency.js`, `validateEmail.js`)
- **Services**: `camelCaseService.js` (ex: `apiService.js`, `authService.js`)
- **Hooks**: `useCamelCase.js` (ex: `useFormData.js`, `useLocalStorage.js`)

#### Variables et Fonctions
- **Constantes**: `UPPER_SNAKE_CASE` (ex: `API_BASE_URL`, `MAX_FILE_SIZE`)
- **Fonctions**: `camelCase` (ex: `formatDate()`, `calculateTotal()`)
- **Variables**: `camelCase` (ex: `isLoading`, `userData`)
- **Booleans**: Préfixe `is` ou `has` (ex: `isOpen`, `hasError`)

---

## 2. 🧩 Développement de Composants

### 2.1 Règles Générales

✅ **À FAIRE**:
- Composants fonctionnels avec hooks React
- Props destructurées avec PropTypes
- Architecture basée sur la composition
- Importer les tokens de design depuis `/theme/`
- Un fichier = un composant principal
- Décomposer si > 400 lignes
- Utiliser le Material-UI (sx prop) pour les styles
- Tests unitaires obligatoires (70% min)

❌ **À NE PAS FAIRE**:
- Composants stateless/class
- Styles inline sans justification
- CSS global (sauf theme.js)
- Props sans documentation PropTypes
- Nommage ambigu (eviter "Data", "Item", "Handler")
- Logique métier dans les composants
- Dépendances circulaires

### 2.2 Structure d'un Composant

```jsx
/**
 * ComponentName
 * 
 * @description Brief description
 * @component
 * 
 * @example
 * <ComponentName prop1="value" onAction={handleAction} />
 */

import React, { useState, useCallback } from 'react';
import PropTypes from 'prop-types';
import { Box, Button } from '@mui/material';
import { designTokens, media } from '../theme';
import { useCustomHook } from '../hooks';

/**
 * ComponentName - Detailed description
 * 
 * Features:
 * - Feature 1
 * - Feature 2
 */
const ComponentName = ({ 
  prop1, 
  prop2 = 'default',
  onAction 
}) => {
  // Hooks
  const [state, setState] = useState(null);
  const customValue = useCustomHook();

  // Callbacks
  const handleClick = useCallback(() => {
    onAction();
  }, [onAction]);

  // Render
  return (
    <Box
      sx={{
        padding: designTokens.spacing[4],
        backgroundColor: designTokens.colors.primary[50],
        [media.md]: {
          padding: designTokens.spacing[6],
        },
      }}
    >
      <Button onClick={handleClick}>{prop1}</Button>
    </Box>
  );
};

ComponentName.propTypes = {
  prop1: PropTypes.string.isRequired,
  prop2: PropTypes.string,
  onAction: PropTypes.func.isRequired,
};

export default ComponentName;
```

### 2.3 Hiérarchie des Composants

#### Composants Réutilisables (`/components/common/`)
- LoadingOverlay
- ErrorBoundary
- FormInput
- KPIMetric
- ChartCard
- Modal
- Notification
- Badge
- StatusIndicator

#### Composants de Pages (`/components/[section]/`)
- Groupent les composants réutilisables
- Gèrent la logique métier locale
- Taille: 300-500 lignes
- Pas de logique API (→ utiliser services)

#### Pages (`/pages/`)
- Conteneur de composants
- Gère la pagination, filtrage global
- Appelle les services API
- Routage via React Router

---

## 3. 🎨 Styling & Theme System

### 3.1 Hiérarchie des Styles

```
MUI Base Styles (Material-UI par défaut)
    ↓
Theme MUI (theme.js)
    ↓
Design Tokens (designTokens.js)
    ↓
Composant sx prop (styles spécifiques)
    ↓
Media Queries (responsive.js)
```

### 3.2 Utiliser les Tokens de Design

✅ **CORRECT**:
```jsx
import { designTokens, media } from '../theme';

<Box sx={{
  padding: designTokens.spacing[4],
  color: designTokens.colors.primary[600],
  [media.md]: {
    padding: designTokens.spacing[6],
  },
}} />
```

❌ **INCORRECT**:
```jsx
// Pas d'hardcoding de valeurs
<Box sx={{ padding: '16px', color: '#1976d2' }} />

// Pas de CSS global
<Box className="custom-class" />
```

### 3.3 Responsive Design (Mobile-First)

```jsx
import { media } from '../theme';

<Box sx={{
  // Mobile first (default)
  fontSize: designTokens.typography.fontSize.sm,
  padding: designTokens.spacing[2],
  
  // Tablet et au-dessus
  [media.md]: {
    fontSize: designTokens.typography.fontSize.base,
    padding: designTokens.spacing[4],
  },
  
  // Desktop et au-dessus
  [media.lg]: {
    fontSize: designTokens.typography.fontSize.lg,
    padding: designTokens.spacing[6],
  },
}} />
```

### 3.4 Tokens Disponibles

#### Couleurs
```javascript
designTokens.colors = {
  primary: { 50, 100, 200, ..., 900 },
  secondary, success, error, warning, info, neutral, semantic,
  bijouterie: { or, argent, platine, cuivre, gemstone }
}
```

#### Typographie
```javascript
fontSize: { xs, sm, base, lg, xl, 2xl, 3xl, 4xl, 5xl }
fontWeight: { thin, light, normal, medium, semibold, bold, extrabold, black }
lineHeight: { none, tight, normal, relaxed }
letterSpacing: { tight, normal, wide, wider }
```

#### Espacement
```javascript
spacing: [0, 2, 4, 6, 8, 12, 16, 20, 24, 32, 40, 48, 56, 64, 72, 80, 96]
```

#### Animations
```javascript
animations.fadeIn, slideInUp, scaleIn, pulse, bounce, spin...
animationPresets: { fadeInSlow, slideInUpSlow, ... }
transitions: { colorTransition, shadowTransition, ... }
hoverEffects: { elevate, scale, brighten, ... }
```

---

## 4. 🔗 Couche API & Services

### 4.1 Pattern Service Centralisé

**Fichier**: `/frontend/src/services/api.js`

```javascript
// ✅ CORRECT: Centralisé, réutilisable, cohérent
import API from '../services/api';

const handleImport = async (file) => {
  try {
    const result = await API.import.uploadFile(file);
    setData(result);
  } catch (error) {
    setError(error.message);
  }
};
```

### 4.2 Structure des Appels API

```javascript
// services/api.js

const API = {
  import: {
    uploadFile: async (file) => {
      const formData = new FormData();
      formData.append('file', file);
      return fetch('/api/import', {
        method: 'POST',
        body: formData,
      }).then(res => res.json());
    },
    getStatus: async () => {
      return fetch('/api/import/status')
        .then(res => res.json());
    },
  },
  
  balance: {
    getBalance: async (year) => {
      return fetch(`/api/balance?year=${year}`)
        .then(res => res.json());
    },
  },
};

export default API;
```

### 4.3 Gestion des Erreurs

```javascript
// Dans les composants
const [error, setError] = useState(null);
const [loading, setLoading] = useState(false);

const fetchData = async () => {
  setLoading(true);
  setError(null);
  try {
    const data = await API.getData();
    setData(data);
  } catch (err) {
    setError(err.message || 'Erreur lors du chargement');
    console.error('API Error:', err);
  } finally {
    setLoading(false);
  }
};
```

### 4.4 États de Chargement

```jsx
import { LoadingOverlay, ErrorBoundary } from '../components/common';

{loading && <LoadingOverlay open={true} message="Chargement..." />}
{error && <Alert severity="error">{error}</Alert>}
{data && <Component data={data} />}
```

---

## 5. 🧪 Testing & QA

### 5.1 Configuration (Jest)

**Fichier**: `/frontend/jest.config.js`

- **Environnement**: jsdom (simulation navigateur)
- **Coverage minimum**: 70% (branches, functions, lines, statements)
- **Pattern des tests**: `**/__tests__/**/*.js` ou `**/*.test.js`

### 5.2 Structure des Tests

```javascript
// ComponentName.test.js

import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ComponentName from './ComponentName';

describe('ComponentName', () => {
  // Tests d'affichage
  it('should render correctly', () => {
    render(<ComponentName prop1="test" />);
    expect(screen.getByText('test')).toBeInTheDocument();
  });

  // Tests d'interactions
  it('should handle click events', async () => {
    const handleClick = jest.fn();
    render(<ComponentName onClick={handleClick} />);
    
    await userEvent.click(screen.getByRole('button'));
    expect(handleClick).toHaveBeenCalled();
  });

  // Tests conditionnels
  it('should show error state', () => {
    render(<ComponentName error="Error message" />);
    expect(screen.getByText('Error message')).toBeInTheDocument();
  });

  // Tests asynchrones
  it('should load data', async () => {
    render(<ComponentName />);
    
    await waitFor(() => {
      expect(screen.getByText('Loaded')).toBeInTheDocument();
    });
  });
});
```

### 5.3 Commandes de Test

```bash
# Lancer tous les tests
npm test

# Mode watch
npm test -- --watch

# Coverage
npm test -- --coverage

# Test fichier spécifique
npm test ComponentName.test.js
```

### 5.4 Checklist de Qualité

Avant de commiter:
- [ ] Tests unitaires écrits (70% min coverage)
- [ ] Pas d'erreurs ESLint
- [ ] PropTypes validés
- [ ] Responsive design testé (mobile, tablet, desktop)
- [ ] Accessibilité: ARIA labels présents
- [ ] Performance: pas de render inutiles
- [ ] Pas de console.error ou console.warn
- [ ] Documentation JSDoc complète

---

## 6. 📝 Git & Commits

### 6.1 Convention des Messages

**Format**:
```
emoji Phase/Feature: Description courte (Français)

Description optionnelle si nécessaire:
- Point 1
- Point 2
```

**Exemples**:
```
🎨 Phase 4: Créer système de design tokens
📦 Phase 2: Ajouter endpoints API /import
🧪 Phase 5: Écrire tests pour composants common
🐛 Fix: Corriger calcul SIG section
📚 Docs: Mettre à jour README
♻️ Refactor: Décomposer AdvancedAnalytics
```

### 6.2 Emojis Standards

- 🎨 Design/Styling
- 📦 Feature/Composant
- 🧪 Tests
- 🐛 Bug fix
- 📚 Documentation
- ♻️ Refactoring
- ⚡ Performance
- 🔐 Security
- 🔧 Config
- 🚀 Deployment

### 6.3 Workflow Git

```bash
# Créer branche feature
git checkout -b feature/nom-feature

# Commits réguliers
git add .
git commit -m "🎨 Feature: Description"

# Push
git push origin feature/nom-feature

# PR vers main
# Attendre review avant merge
```

---

## 7. 📊 Performance & Optimisation

### 7.1 Optimisations React

```javascript
// ✅ Utiliser useCallback pour les callbacks stables
const handleClick = useCallback(() => {
  setData(value);
}, [value]);

// ✅ Utiliser useMemo pour les calculs coûteux
const expensiveValue = useMemo(() => {
  return data.map(item => process(item));
}, [data]);

// ✅ Code splitting au niveau des pages
const Dashboard = lazy(() => import('./Dashboard'));

// ✅ Lazy loading des composants lourds
<Suspense fallback={<LoadingOverlay open={true} />}>
  <HeavyComponent />
</Suspense>
```

### 7.2 Bundle Size

- Vérifier régulièrement: `npm run analyze`
- Pas de librairies inutiles
- Importer uniquement ce qui est nécessaire
- Tree-shaking: ES6 modules seulement

### 7.3 Lighthouse Audit

```bash
# Audit local
npm run build && npm run lighthouse

# Cibles minimales
- Performance: 80+
- Accessibility: 90+
- Best Practices: 90+
- SEO: 90+
```

---

## 8. ♿ Accessibilité

### 8.1 Standards WCAG 2.1 (AA)

✅ **À FAIRE**:
- ARIA labels sur les boutons sans texte: `aria-label="Fermer"`
- Role sémantique: `role="button"`, `role="navigation"`
- Keyboard navigation: Tab, Enter, Escape
- Focus management: visible focus state
- Contraste couleur: ratio 4.5:1 minimum
- Alt text sur images: `alt="Description"`
- Labels associés aux input: `htmlFor="inputId"`

❌ **À NE PAS FAIRE**:
- Couleur seule pour communiquer l'info
- Contraste faible
- Tabindex > 0 sans justification
- Images sans alt
- Inputs sans labels

### 8.2 Exemple d'Accessibilité

```jsx
<Button
  onClick={handleClose}
  aria-label="Fermer la modal"
  aria-pressed={isPressed}
>
  ✕
</Button>

<input
  id="email"
  type="email"
  aria-describedby="email-hint"
/>
<small id="email-hint">Format: email@example.com</small>
```

---

## 9. 📋 Checklist Avant Déploiement

### Phase de Développement
- [ ] Branche feature créée et bien nommée
- [ ] Commits atomiques avec messages clairs
- [ ] Tests écrits et passing (70%+ coverage)
- [ ] ESLint et Prettier passent
- [ ] PropTypes valides
- [ ] Documentation JSDoc complète
- [ ] Pas de console.error/warn
- [ ] Responsive design testé

### Phase de Review
- [ ] PR créée avec description claire
- [ ] Code review complétée
- [ ] Tests unitaires approuvés
- [ ] Performance acceptable
- [ ] Accessibilité respectée
- [ ] Pas de régression

### Phase de Déploiement
- [ ] Build production réussit
- [ ] Tests tous passing
- [ ] Lighthouse audit: 80+
- [ ] Pas de erreurs CI/CD
- [ ] Version bump en place
- [ ] Release notes écrites
- [ ] Merged vers main
- [ ] Tags en place

---

## 10. 🚨 Anti-patterns à Éviter

### 🔴 À NE JAMAIS FAIRE

```javascript
// ❌ Styles inline sans justification
<div style={{ color: '#1976d2', padding: '16px' }} />

// ❌ Logique métier dans les composants
function Component() {
  const [data, setData] = useState(null);
  useEffect(() => {
    // Appel API ici au lieu du service
    fetch('/api/data').then(res => setData(res));
  }, []);
}

// ❌ Props non documentées
const Component = ({ a, b, c }) => {};

// ❌ Dépendances circulaires
// serviceA.js -> serviceB.js -> serviceA.js

// ❌ Composants > 500 lignes
// Décomposer!

// ❌ Pas de tests
// Minimum 70% coverage requis

// ❌ Noms ambigus
const handleData = () => {}; // Trop vague!
const processUserData = () => {}; // Meilleur

// ❌ État global mal utilisé (Context sans justification)
// Préférer: Props -> useReducer -> Context -> Redux
```

---

## 11. 📞 Support & Questions

### Ressources Disponibles
- [Material-UI Documentation](https://mui.com)
- [React Hooks Guide](https://react.dev/reference/react)
- [Jest Testing](https://jestjs.io)
- [Vite Documentation](https://vitejs.dev)

### Questions Récurrentes

**Q: Où placer la logique métier?**
A: Dans les services (`/services/`), pas dans les composants.

**Q: Comment gérer les erreurs?**
A: ErrorBoundary pour les composants, try/catch pour l'API.

**Q: Quand utiliser Context vs Props?**
A: Props: < 3 niveaux de profondeur. Context: partage global ou auth.

**Q: Comment tester les appels API?**
A: Mocker avec jest.mock() dans setupTests.js.

---

## 12. 🎯 Version & Historique

| Version | Date | Changements |
|---------|------|-------------|
| 1.0 | Jan 2025 | Version initiale - Phase 1-5 complète |

**Auteur**: Compta Development Team  
**Statut**: ✅ Approuvé et en vigueur  
**Révision**: Annuelle recommandée

---

> ⚠️ **IMPORTANT**: Ces consignes doivent être respectées ABSOLUMENT pour toute création future via prompt IA.  
> Tout nouveau code doit suivre cette architecture et ces patterns.  
> En cas de doute, référer à ce document ou ouvrir une issue.

---
