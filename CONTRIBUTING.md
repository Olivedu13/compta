# 🤝 Contributing Guide

Merci de contribuer à Compta! Ce guide vous aidera à bien démarrer.

## 📋 Avant de Commencer

1. **Lire la Documentation**
   - [Quickstart](docs/QUICKSTART.md) - Setup rapide
   - [Bonnes Pratiques](docs/BONNES_PRATIQUES_EQUIPE.md) - Standards

2. **Vérifier les Issues Ouvertes**
   - Éviter les doublons
   - Demander de l'aide si besoin

## 🔧 Setup Développement

```bash
# Clone le repo
git clone https://github.com/Olivedu13/compta.git
cd compta

# Frontend
cd frontend && npm install && npm run dev

# Backend (terminal 2)
# Configurer .env
php -S localhost:8000 -t public_html
```

## 💡 Processus Contribution

### 1. Créer une Branche
```bash
git checkout -b feature/ma-feature
# ou
git checkout -b fix/mon-bug
```

### 2. Coder
- Respecter les standards (voir [Bonnes Pratiques](docs/BONNES_PRATIQUES_EQUIPE.md))
- Faire des commits atomiques
- Messages clairs en français

### 3. Tester Avant Push
```bash
# Frontend
cd frontend && npm run build

# Backend
php -l backend/config/*.php
```

### 4. Push & Pull Request
```bash
git push origin ma-branche
```

Puis ouvrir une PR sur GitHub avec:
- Description claire du changement
- Référence à l'issue (si applicable)
- Screenshots (pour UI changes)

## ✅ Checklist avant Merge

- [ ] Code testé localement
- [ ] Pas de `.env` ou secrets dans les commits
- [ ] Messages git clairs
- [ ] Docs mises à jour si nécessaire
- [ ] Pas de console.log() ou dump() en prod

## 📝 Convention de Code

### JavaScript/React
```javascript
// PascalCase pour composants
const MyComponent = () => {
  // camelCase pour variables/functions
  const [userData, setUserData] = useState(null);
  
  // Arrow functions
  const handleClick = () => {};
};
```

### PHP
```php
// PascalCase pour classes
class MyService {
  // camelCase pour méthodes et variables
  public function myMethod() {}
}

// App\ namespace
namespace App\Services;
```

## 🐛 Signaler un Bug

Créer une issue avec:
1. **Titre clair**: "Bug: Description en une ligne"
2. **Description**: Qu'est-ce qui se passe?
3. **Étapes**: Comment reproduire?
4. **Attendu**: Quel est le comportement correct?
5. **Actuel**: Qu'observe-t-on?
6. **Environment**: PHP version, navigateur, etc.

Exemple:
```markdown
## Bug: La page dashboard ne charge pas

### Description
Quand je clique sur "Dashboard", la page affiche "Erreur 500"

### Étapes
1. Login avec admin@atelier-thierry.fr
2. Cliquer sur Dashboard
3. Voir l'erreur

### Attendu
La page devrait afficher les KPIs

### Actuel
Erreur 500 - Internal Server Error

### Environment
- PHP 8.2
- Chrome 120
- macOS
```

## 🎯 Types de Contributions

### Feature (Nouvelle Fonctionnalité)
- Créer branche: `feature/description`
- Inclure tests
- Documenter dans [FEATURES.md](docs/FEATURES.md)

### Bug Fix (Correction)
- Créer branche: `fix/description`
- Inclure test pour éviter régression
- Documenter la cause dans la PR

### Docs (Documentation)
- Créer branche: `docs/description`
- Mettre à jour [docs/INDEX.md](docs/INDEX.md)
- Vérifier les liens

### Refactor (Restructuration)
- Créer branche: `refactor/description`
- Pas de changement fonctionnel
- Inclure justification

## 🚀 Après Merge

- La branche sera supprimée automatiquement
- Vérifier que les tests CI passent
- Fête! 🎉

---

**Questions?** Créer une issue avec le label `question`

**Audit & Roadmap**: Voir [AUDIT_EXECUTIF.md](AUDIT_EXECUTIF.md)
