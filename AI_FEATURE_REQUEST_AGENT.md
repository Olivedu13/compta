# 🤖 AI FEATURE REQUEST AGENT - Guidelines

**Version**: 1.0  
**Date**: Janvier 2025  
**Purpose**: Standardiser et valider toutes les demandes de nouvelles fonctionnalités/modifications  
**Scope**: Agents IA générant du code pour le projet Compta

---

## 📋 Mission de l'Agent IA

Avant de **générer du code**, l'agent IA doit:

1. **Reformuler** la demande de l'utilisateur de manière structurée
2. **Valider** la conformité avec ARCHITECTURE_GUIDELINES.md
3. **Planifier** la démarche (architecture, tech, design, tests)
4. **Proposer** une implémentation cohérente
5. **Vérifier** la qualité (pas d'anti-patterns, tests, accessibilité)

---

## 🔄 Flux de Traitement d'une Demande

```
Demande utilisateur
    ↓
[1] Reformulation structurée
    ↓
[2] Validation architecture
    ↓
[3] Planification démarche
    ↓
[4] Génération code (si OK)
    ↓
[5] Vérification qualité
    ↓
Code validé + Tests + Docs
```

---

## 📝 ÉTAPE 1: Reformulation Structurée

L'agent DOIT clarifier:

### A. Clarifier le Besoin
```
Demande originale:
"Ajoute un composant pour afficher les KPIs"

Reformulation:
Type: Nouveau composant React
Localisation: /components/common/ (réutilisable) ou /components/[section]/ (spécialisé)
Utilité: Afficher métriques clés (KPI) avec:
  - Valeur numérique
  - Tendance (↑↓)
  - Comparaison période
  - Seuils (alerte/ok)
Props: value, label, trend, status, onClick?
```

### B. Identifier les Dépendances
```
Dépendances:
- Material-UI (components: Box, Card, Typography)
- Design tokens (colors, spacing, typography)
- Responsive utils (media queries)
- Peut être réutilisable? Oui → /common/
```

### C. Préciser les Contraintes
```
Contraintes:
- Coverage tests: 70% minimum
- Responsive: xs/sm/md/lg/xl
- Accessible: WCAG 2.1 AA (ARIA labels)
- Performance: pas de render inutiles
- Pas d'anti-patterns
```

---

## ✅ ÉTAPE 2: Validation Architecture

L'agent DOIT vérifier:

### ✅ Checklist Validation

```javascript
// 1. Structure correcte?
├─ Fichier: PascalCase.jsx ✅
├─ Localisation: /components/[section]/ ✅
├─ Barrel export (index.js)? ✅
└─ Tests: ComponentName.test.js ✅

// 2. Dépendances OK?
├─ MUI components utilisés ✅
├─ Design tokens importés ✅
├─ Media queries pour responsive ✅
└─ Pas de dépendances circulaires ✅

// 3. Code patterns?
├─ Fonctionnel + hooks ✅
├─ Props destructurées ✅
├─ PropTypes obligatoires ✅
├─ JSDoc complète ✅
└─ Pas de styles inline ✅

// 4. Tests inclus?
├─ Suite de tests ✅
├─ Coverage ≥ 70% ✅
├─ Tests d'interactions ✅
└─ Accès au ARIA/accessibility ✅

// 5. Qualité?
├─ Pas d'anti-patterns ✅
├─ Mobile-first ✅
├─ Accessible (ARIA) ✅
└─ JSDoc + exemples ✅
```

**Si quelque chose n'est pas OK:**
```
❌ STOP! Reformuler pour corriger le problème.
   Exemple: "Ajoute aussi les tests pour ce composant"
```

---

## 🎯 ÉTAPE 3: Planification Démarche

L'agent DOIT fournir un PLAN clair:

### Template Plan

```markdown
## Plan d'Implémentation: NomComposant

### 1. Architecture
- Localisation: /frontend/src/components/[section]/NomComposant.jsx
- Type: Composant fonctionnel React + hooks
- Composition: Reutilisable (common/) ou Spécialisé (charts/sig/dashboard/)
- Dépendances: MUI, designTokens, media queries

### 2. Technologie
- Framework: React 18 + Hooks
- Styling: MUI sx prop + designTokens
- State: useState/useCallback si nécessaire
- Imports: Tokens de design depuis ../theme/

### 3. Design
- Responsive: Mobile-first (xs → xl)
- Tokens utilisés: colors, spacing, typography
- Animations: SI approprié, utiliser presets
- Accessibilité: ARIA labels + keyboard nav

### 4. Tests
- Suite: NomComposant.test.js
- Coverage: ≥ 70%
- Tests: rendering, interactions, states
- Accessibility tests inclus

### 5. Étapes
1. Créer NomComposant.jsx
2. Ajouter PropTypes + JSDoc
3. Écrire tests (common.test.js = pattern)
4. Vérifier coverage: npm test -- --coverage
5. Commit: 🎨 Feature: Créer NomComposant

### 6. Validation
- ✅ ESLint: npm run lint
- ✅ Tests: npm test
- ✅ Build: npm run build
- ✅ Responsive: testé xs/md/lg
- ✅ Accessible: ARIA labels + focus
- ✅ Pas d'anti-patterns
```

---

## 💻 ÉTAPE 4: Génération Code

L'agent DOIT fournir:

### 1. Composant avec Template Complet

```jsx
/**
 * ComponentName
 * 
 * @description Courte description
 * @component
 * 
 * @example
 * <ComponentName prop1="value" onAction={handleAction} />
 */

import React, { useState, useCallback } from 'react';
import PropTypes from 'prop-types';
import { Box, Button } from '@mui/material';
import { designTokens, media } from '../../theme';

const ComponentName = ({ prop1, prop2 = 'default', onAction }) => {
  const [state, setState] = useState(null);

  const handleClick = useCallback(() => {
    onAction();
  }, [onAction]);

  return (
    <Box
      sx={{
        padding: designTokens.spacing[4],
        [media.md]: {
          padding: designTokens.spacing[6],
        },
      }}
    >
      {/* Content */}
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

### 2. Tests avec Pattern Établi

```javascript
import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ComponentName from './ComponentName';

describe('ComponentName', () => {
  it('should render correctly', () => {
    render(<ComponentName prop1="test" onAction={() => {}} />);
    expect(screen.getByText('test')).toBeInTheDocument();
  });

  it('should handle click events', async () => {
    const handleAction = jest.fn();
    render(<ComponentName prop1="test" onAction={handleAction} />);
    await userEvent.click(screen.getByRole('button'));
    expect(handleAction).toHaveBeenCalled();
  });
});
```

### 3. Fichier index.js (Barrel Export)

```javascript
export { default as ComponentName } from './ComponentName';
```

---

## 🔍 ÉTAPE 5: Vérification Qualité

L'agent DOIT valider AVANT de livrer:

### Checklist Final

- [ ] **Code Quality**
  - [ ] Pas d'erreurs ESLint
  - [ ] PropTypes valides
  - [ ] JSDoc complète
  - [ ] Pas de `any` TypeScript
  - [ ] Pas de styles inline
  - [ ] Tokens utilisés partout

- [ ] **Tests**
  - [ ] Suite de tests écrite
  - [ ] Coverage ≥ 70%
  - [ ] Tous les tests passent
  - [ ] Pas de console.error/warn
  - [ ] Accessibility tests inclus

- [ ] **Responsive & Accessibility**
  - [ ] Testé sur xs/sm/md/lg/xl
  - [ ] ARIA labels présents
  - [ ] Focus visible
  - [ ] Contraste couleur OK (4.5:1)
  - [ ] Keyboard navigation fonctionne

- [ ] **Documentation**
  - [ ] JSDoc sur composant
  - [ ] Props documentées
  - [ ] @example fourni
  - [ ] README mise à jour si besoin

- [ ] **Git**
  - [ ] Message clair
  - [ ] Commit atomic
  - [ ] Pas de fichiers inutiles

**❌ Si quelque chose n'est pas OK → CORRIGER avant livraison**

---

## 📋 FORMULAIRE - Reformulation Structurée

Quand un utilisateur fait une demande, l'agent DOIT remplir ce formulaire:

```markdown
# Demande de Fonctionnalité: [NOM]

## 1. Reformulation

**Demande originale:**
[Copier la demande exacte de l'utilisateur]

**Reformulation structurée:**
- Type: Nouveau composant / Modification / Feature / Fix
- Catégorie: [common / charts / sig / dashboard / service / page / autre]
- Utilité: [Description claire]
- Features principales: [Liste]

## 2. Validation Architecture

✅ Conforme? [OUI/NON]
Si NON: [Explique pourquoi et demande clarification]

Validations:
- [ ] Structure correcte (fichier, localisation)
- [ ] Dépendances OK
- [ ] Patterns respectés
- [ ] Tests inclus
- [ ] Pas d'anti-patterns

## 3. Plan d'Implémentation

### Localisation & Structure
- Fichier: /frontend/src/components/[section]/ComponentName.jsx
- Tests: /frontend/src/components/[section]/__tests__/ComponentName.test.js
- Barrel export: /frontend/src/components/[section]/index.js

### Technologie
- Technologies: [List]
- Dépendances: [List]
- Imports: [Show]

### Design & Responsive
- Responsive: Mobile-first (xs → xl)
- Tokens: [List utilisés]
- Accessibility: [ARIA labels, keyboard nav, etc.]

### Tests
- Coverage: ≥ 70%
- Tests à écrire: [rendering, interactions, states, etc.]

### Étapes d'Implémentation
1. [Step 1]
2. [Step 2]
3. [Step 3]

## 4. Prochaines Étapes
- Générer code
- Écrire tests
- Valider qualité
- Commit & push

---

## ⚠️ Points Bloquants
[Si quelque chose n'est pas clair ou viole les guidelines]
```

---

## 🚫 Anti-Patterns Interdits

L'agent DOIT **REFUSER** les demandes contenant:

```javascript
// ❌ Pas d'inline styles
sx={{ color: '#1976d2', padding: '16px' }}

// ❌ Pas de composants sans tests
// Component sans suite de tests → NON

// ❌ Pas de logique API dans les composants
useEffect(() => { fetch('/api'); }, [])

// ❌ Pas de composants > 400 lignes
// → Demander décomposition

// ❌ Pas de noms ambigus
handleData()  // ❌ REFUSE
processUserBalance()  // ✅ OK

// ❌ Pas d'accessibilité ignorée
<button>X</button>  // ❌ REFUSE
<button aria-label="Fermer">X</button>  // ✅ OK
```

**Si une demande viole ces règles:**
```
❌ REFUSE poliment et propose une reformulation.
Exemple: "Je ne peux pas créer ce composant sans tests.
Peux-tu demander: 'Crée un composant X avec une suite de tests (70% min)?'"
```

---

## 💬 Réponse Type de l'Agent IA

Quand un utilisateur demande une nouvelle fonctionnalité:

```markdown
# 📋 Reformulation de Votre Demande

## Comprendre Votre Besoin

**Vous demandez:**
[Citation exacte de la demande]

**Je comprends:**
[Reformulation structurée]

## Validation

✅ Conforme aux guidelines (ARCHITECTURE_GUIDELINES.md)

## Plan d'Implémentation

### 1. Architecture
[Détails architecture]

### 2. Technologie
[Stack tech]

### 3. Design & Responsive
[Design details]

### 4. Tests
[Tests plan]

### 5. Étapes
1. Créer le composant
2. Écrire les tests
3. Valider la qualité
4. Commit & Push

## Code à Générer

[Code template]

## Prochaines Étapes

1. Valider cette reformulation
2. Générer le code
3. Écrire les tests
4. Verifier npm test && npm run lint
5. Commit & push

**Vous êtes d'accord avec cette approche?**
```

---

## 🎯 Règles Prioritaires

### 1. Tests OBLIGATOIRES
Aucun code sans tests. Period.
```
Couverture minimum: 70%
Pattern: Voir common.test.js
```

### 2. Design Tokens OBLIGATOIRES
Aucun hardcoding de couleurs, spacing, etc.
```
✅ CORRECT: designTokens.colors.primary[600]
❌ INCORRECT: '#1976d2'
```

### 3. Responsive OBLIGATOIRE
Mobile-first approach systématique
```
Breakpoints: xs, sm, md, lg, xl
Pattern: [media.md]: { /* styles */ }
```

### 4. Accessibility OBLIGATOIRE
WCAG 2.1 AA minimum
```
✅ ARIA labels sur boutons
✅ Focus visible
✅ Keyboard navigation
✅ Contraste 4.5:1
```

### 5. Pas d'Anti-Patterns
Voir ARCHITECTURE_GUIDELINES.md Section 10
```
❌ Jamais de styles inline (sauf tokens)
❌ Jamais de composants sans tests
❌ Jamais de logique API dans composants
❌ Jamais de composants > 400 lignes
```

---

## 📞 Escalade

**Si la demande viole les guidelines:**

```
L'agent DOIT:
1. Expliquer pourquoi c'est non-conforme
2. Proposer une reformulation conforme
3. Demander confirmation avant de coder

Exemple:
"Cette demande viole: [Règle X]
Pourquoi: [Explication]
Reformulation proposée: [Alternative]
Puis-je procéder ainsi?"
```

**Si la demande est ambiguë:**

```
L'agent DOIT clarifier:
- Localisation exacte du composant
- Props précises attendues
- Exemples d'utilisation
- Quels tests écrire
- Dépendances
```

**Jamais coder sans:**
- ✅ Reformulation structurée validée
- ✅ Plan approuvé
- ✅ Checklist qualité claire
- ✅ Tests prévus
- ✅ Aucun anti-pattern

---

## 🔄 Workflow Complet

```
1. Utilisateur demande une fonctionnalité
   ↓
2. Agent reformule de manière structurée
   ↓
3. Agent valide architecture/guidelines
   ↓
4. Agent propose plan + template code
   ↓
5. Utilisateur approuve
   ↓
6. Agent génère code (component + tests + docs)
   ↓
7. Agent valide qualité
   ↓
8. Agent propose: git commit + push
   ↓
9. Code livré, conforme, testé, documenté
```

---

## 📚 References

**À TOUJOURS Consulter AVANT de coder:**

1. [ARCHITECTURE_GUIDELINES.md](ARCHITECTURE_GUIDELINES.md) - La source de vérité
2. [QUICK_START_NEW_COMPONENT.md](QUICK_START_NEW_COMPONENT.md) - Guide création
3. `/frontend/src/theme/` - Tokens de design
4. `/frontend/src/components/common/__tests__/common.test.js` - Pattern tests
5. `/frontend/src/components/common/` - Exemples composants

---

## 🚀 Résumé

**L'Agent IA DOIT:**

1. ✅ Reformuler chaque demande de manière structurée
2. ✅ Valider la conformité aux guidelines
3. ✅ Planifier la démarche complète
4. ✅ Générer code + tests + docs
5. ✅ Vérifier la qualité
6. ✅ JAMAIS accepter un code non-conforme

**Résultat:**
- Code toujours de qualité
- Tests systématiques
- Pas de régression
- Projet maintenable long-terme

---

**Version**: 1.0  
**Status**: ✅ Active  
**À utiliser pour**: Toute génération de code future

*Généré: Janvier 2025 - Garantit l'intégrité du projet Compta*
