# ⚡ Quick Start - Créer un Nouveau Composant

**À lire avant TOUTE création de code via prompt!**

---

## 🎯 Checklist Pré-Création

Avant de demander une création à l'IA, vous devez avoir:

- [ ] Lu [ARCHITECTURE_GUIDELINES.md](ARCHITECTURE_GUIDELINES.md)
- [ ] Compris la structure dans `/frontend/src/`
- [ ] Connu les tokens de design dans `/frontend/src/theme/`
- [ ] Décidé si c'est un composant `common/` ou spécialisé
- [ ] Préparé les props attendues et leur type

---

## 📝 Prompts Recommandés pour l'IA

### Template Composant Reusable

```
Crée un nouveau composant React:

Nom: NomComposant
Localisation: /frontend/src/components/common/
Type: Composant réutilisable
Utilité: Description courte

Props requises:
- prop1: string - Description
- prop2: boolean - Description

Features:
- Feature 1
- Feature 2

Notes:
- Utiliser les tokens de designTokens.js
- Responsive design mobile-first via media queries
- PropTypes obligatoires
- JSDoc complète
- Tests unitaires (70% min coverage)
- Suivre ARCHITECTURE_GUIDELINES.md absolument

Exemple d'utilisation:
<NomComposant prop1="value" prop2={true} />
```

### Template Composant Spécialisé

```
Crée un nouveau composant React:

Nom: SectionName
Localisation: /frontend/src/components/[section]/
Section: charts|sig|dashboard

Utilité: Description détaillée
Parent attendu: ParentComponent

Features:
- Feature 1
- Feature 2

APIs utilisées:
- API.getEndpoint() si nécessaire

Notes:
- Utiliser designTokens et media queries
- Gestion d'erreurs avec ErrorBoundary
- Loading states avec LoadingOverlay
- Tests requis (exemples dans common.test.js)
- ARCHITECTURE_GUIDELINES.md à respecter absolument
```

---

## 🔧 Étapes de Création Manuelle

Si vous créez sans IA, suivez ce workflow:

### 1. Créer le fichier
```
/frontend/src/components/[section]/ComponentName.jsx
```

### 2. Template de base
```jsx
import React, { useState, useCallback } from 'react';
import PropTypes from 'prop-types';
import { Box, Button } from '@mui/material';
import { designTokens, media } from '../../theme';

/**
 * ComponentName
 * @description Courte description
 * @component
 * @example
 * <ComponentName prop1="value" />
 */
const ComponentName = ({ prop1, prop2 }) => {
  return (
    <Box sx={{ padding: designTokens.spacing[4] }}>
      {/* Content */}
    </Box>
  );
};

ComponentName.propTypes = {
  prop1: PropTypes.string.isRequired,
  prop2: PropTypes.string,
};

export default ComponentName;
```

### 3. Créer les tests
```
/frontend/src/components/[section]/__tests__/ComponentName.test.js
```

Utiliser le pattern de `common.test.js`

### 4. Vérifier la qualité
```bash
cd frontend
npm test ComponentName.test.js          # Tests
npm run lint                             # ESLint
npm run build                            # Build
```

### 5. Committer
```bash
git commit -m "🎨 Feature: Créer ComponentName

- Ajouter ComponentName avec props X, Y
- Tests unitaires (70% coverage)
- Responsive design mobile-first
- Accessible (ARIA labels)"
```

---

## 📦 Imports Standards

### Design System
```jsx
import { designTokens, media, animations } from '../theme';
// ou
import { designTokens } from '../../theme';
import { media } from '../../theme';
```

### Material-UI
```jsx
import { Box, Button, TextField, Card } from '@mui/material';
import { CloudUpload, Error, Info } from '@mui/icons-material';
```

### Testing
```jsx
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
```

### Composants Common
```jsx
import { LoadingOverlay, ErrorBoundary } from '../common';
```

---

## 🎨 Utiliser les Tokens

### Couleurs
```jsx
sx={{
  color: designTokens.colors.primary[600],
  backgroundColor: designTokens.colors.neutral[50],
  borderColor: designTokens.colors.error[500],
}}
```

### Spacing
```jsx
sx={{
  padding: designTokens.spacing[4],     // 16px
  marginBottom: designTokens.spacing[6], // 24px
  gap: designTokens.spacing[2],          // 8px
}}
```

### Typography
```jsx
sx={{
  fontSize: designTokens.typography.fontSize.lg,
  fontWeight: designTokens.typography.fontWeight.semibold,
  lineHeight: designTokens.typography.lineHeight.normal,
}}
```

### Animations
```jsx
sx={{
  animation: `${animations.fadeIn} 0.3s ease-in`,
  '&:hover': {
    animation: `${animations.scaleIn} 0.2s ease`,
  },
}}
```

### Responsive
```jsx
sx={{
  // Mobile (default)
  fontSize: designTokens.typography.fontSize.sm,
  padding: designTokens.spacing[2],
  
  // Tablet
  [media.md]: {
    fontSize: designTokens.typography.fontSize.base,
    padding: designTokens.spacing[4],
  },
  
  // Desktop
  [media.lg]: {
    fontSize: designTokens.typography.fontSize.lg,
    padding: designTokens.spacing[6],
  },
}}
```

---

## ✅ Checklist Final

Avant de soumettre (commit/PR):

### Code
- [ ] Pas d'erreurs ESLint/Prettier
- [ ] PropTypes valides
- [ ] JSDoc complète
- [ ] Pas de `any` TypeScript (si applicable)
- [ ] Pas de styles inline (sauf tokens)
- [ ] Tokens utilisés partout

### Tests
- [ ] Tests écrits (pattern: common.test.js)
- [ ] Coverage ≥ 70%
- [ ] Tous les tests passent: `npm test`
- [ ] Pas de console.error/warn

### Responsif & Accessibilité
- [ ] Testé sur mobile (< 600px)
- [ ] Testé sur tablet (600-960px)
- [ ] Testé sur desktop (> 960px)
- [ ] ARIA labels sur boutons/inputs
- [ ] Focus visible
- [ ] Contraste couleur OK (4.5:1)

### Documentation
- [ ] JSDoc sur composant
- [ ] Props documentées
- [ ] @example fourni
- [ ] Readme mise à jour si besoin

### Git
- [ ] Message clair: `emoji Feature: Description`
- [ ] Commit atomic
- [ ] Branche bien nommée
- [ ] Pas de fichiers inutiles

---

## 🚫 Patterns Dangereux

### ❌ NE PAS FAIRE

```jsx
// Styles inline
<Box style={{ color: '#1976d2', padding: '16px' }} />

// Pas de PropTypes
const Component = (props) => {};

// Logique API dans le composant
useEffect(() => {
  fetch('/api/data').then(res => setData(res));
}, []);

// Composant trop grand (> 500 lignes)
// → Décomposer!

// Pas de tests
// → Minimum 70% coverage requis!

// Noms ambigus
const handleData = () => {}; // Trop vague
const processUserBalance = () => {}; // Meilleur

// Import relatif complexe
import x from '../../../utils'; // Trop profond
// Utiliser instead des barrel exports ou alias
```

---

## 📞 Aide Rapide

| Question | Réponse |
|----------|--------|
| **Où mettre mes styles?** | Dans `sx` prop avec `designTokens` |
| **Comment responsif?** | Utiliser `media` de theme + mobile-first |
| **Comment tester?** | Pattern: `common.test.js` + Jest |
| **API comment?** | Service centralisé: `/services/api.js` |
| **Erreurs?** | ErrorBoundary pour composants + try/catch pour API |
| **Loading?** | LoadingOverlay component |
| **Accessible?** | ARIA labels + keyboard nav + contraste |
| **Plus de 400 lignes?** | DÉCOMPOSER en sous-composants! |
| **Component trop big?** | Voir anti-patterns in ARCHITECTURE_GUIDELINES.md |

---

## 🎓 Ressources

1. **[ARCHITECTURE_GUIDELINES.md](ARCHITECTURE_GUIDELINES.md)** - La source de vérité
2. **[SESSION_COMPLETION_SUMMARY.md](SESSION_COMPLETION_SUMMARY.md)** - Contexte global
3. **[/frontend/src/theme/](frontend/src/theme/)** - Voir les tokens en détail
4. **[/frontend/src/components/common/__tests__/common.test.js](frontend/src/components/common/__tests__/common.test.js)** - Exemple tests
5. **GitHub Issues** - Questions techniques

---

## 🚀 À Retenir

> ⚠️ **IMPORTANT**: Toute création DOIT respecter [ARCHITECTURE_GUIDELINES.md](ARCHITECTURE_GUIDELINES.md)

- ✅ Tokens de design obligatoires
- ✅ Tests 70% minimum
- ✅ PropTypes obligatoires
- ✅ Mobile-first responsive
- ✅ Accessible (WCAG 2.1 AA)
- ✅ Commits clairs
- ✅ Pas d'anti-patterns

**Doute?** → Consultez ARCHITECTURE_GUIDELINES.md ou ouvrez une issue

---

*Créé: Janvier 2025*  
*Version: 1.0*  
*Status: ✅ Active*
