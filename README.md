# 📊 Compta - Gestion Comptable Bijouterie

Application web complète pour la gestion comptable et l'analyse financière de bijouterie. Réunion expertise comptable, imports FEC automatisés et tableaux de bord analytiques.

**Stack**: React 18 + Material-UI 5 (frontend) | PHP 8+ (backend) | MySQL (database)

## 🚀 Quick Start

### Prérequis
- Node.js 16+
- PHP 8+
- MySQL 5.7+

### Installation Locale

```bash
# Frontend
cd frontend && npm install && npm run dev

# Backend
# Configurer .env avec credentials MySQL
# Base de données existe et schema.sql importé
php -S localhost:8000 -t public_html
```

### Déploiement Production

Voir [docs/DEPLOYMENT_GUIDE.md](docs/DEPLOYMENT_GUIDE.md) pour Ionos ou autres hébergeurs.

## 📚 Documentation

- **[Quickstart](docs/QUICKSTART.md)** - Mise en place rapide
- **[API Documentation](docs/API_DOCUMENTATION.md)** - Endpoints REST
- **[Security Guide](docs/SECURITY_GUIDE.md)** - Configuration sécurité
- **[Architecture](docs/)** - Vue d'ensemble technique
- **[Audit Complet](AUDIT_COMPLET.md)** - Analyse détaillée du projet

## 🏗️ Architecture

```
/backend           Backend PHP (outside web root)
/frontend          React + Vite frontend
/public_html       Web root + API endpoints
/docs              Documentation
```

## 🔐 Authentification

- JWT (HS256, 24h expiry)
- Login: `POST /api/auth/login.php`
- Credentials: `.env` (non-commité)

## 📊 Fonctionnalités Principales

- ✅ Import FEC automatisé
- ✅ Tableaux de bord analytiques
- ✅ Calcul SIG (Soldes Intermédiaires)
- ✅ Export données
- ✅ Gestion multi-années

## 🐛 Signaler un Bug

Créer une issue GitHub avec:
1. Description du problème
2. Étapes pour reproduire
3. Résultat attendu vs actuel

## 📝 License

Propriétaire - Atelier Thierry

---

**Audit & Refactorisation**: Voir [AUDIT_EXECUTIF.md](AUDIT_EXECUTIF.md)  
**Docs Archivées**: [docs/obsolete/](docs/obsolete/)
