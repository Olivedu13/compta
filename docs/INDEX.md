# 📚 Documentation Index

## 🔗 Navigation Rapide

### Démarrage
- [Quickstart](QUICKSTART.md) - Mise en place en 5 min
- [Installation locale](LOCAL_TESTING.md) - Setup dev
- [Deployment Guide](DEPLOYMENT_GUIDE.md) - Production

### Technique
- [API Documentation](API_DOCUMENTATION.md) - Endpoints REST
- [Security Guide](SECURITY_GUIDE.md) - Configuration sécurité
- [Développement](DEVELOPPEMENT.md) - Architecture & patterns
- [Bonnes Pratiques](BONNES_PRATIQUES_EQUIPE.md) - Standards équipe

### Fonctionnalités Métier
- [FEC Workflow](FEC_WORKFLOW_COMPLET.md) - Import FEC complet
- [SIG Formules](SIG_FORMULES_BIJOUTERIE.md) - Calculs SIG
- [Features](FEATURES.md) - Liste des fonctionnalités

### Audit & Refactorisation
- [Audit Complet](../AUDIT_COMPLET.md) - Analyse détaillée (9000+ lignes)
- [Audit Exécutif](../AUDIT_EXECUTIF.md) - Résumé prioritaire
- [Implementation](IMPLEMENTATION_RESUME.md) - État actuel

---

## 📁 Structure Documentation

```
docs/
├── QUICKSTART.md                      # Démarrage rapide
├── LOCAL_TESTING.md                   # Setup local
├── DEPLOYMENT_GUIDE.md                # Déploiement
├── API_DOCUMENTATION.md               # Endpoints
├── SECURITY_GUIDE.md                  # Sécurité
├── DEVELOPPEMENT.md                   # Architecture
├── BONNES_PRATIQUES_EQUIPE.md        # Standards
├── FEC_WORKFLOW_COMPLET.md           # FEC detail
├── SIG_FORMULES_BIJOUTERIE.md        # SIG formulas
├── FEATURES.md                        # Features list
├── VERIFICATION_IMPLEMENTATION.md     # Verification
├── WORKFLOW_USAGE.md                  # Usage workflow
├── ETAT_PROJET_AUDIT_COMPLET.md      # Status report
├── ROADMAP_SECURITE_3_PHASES.md      # Security roadmap
├── CORRECTIONS_SECURITE_APPLIQUEES.md # Applied fixes
├── CONFIG_SECURITE_APACHE_PHP.md     # Apache/PHP config
├── IMPLEMENTATION_RESUME.md           # Implementation
├── CHECKLIST_PRE_PRODUCTION.md       # Pre-prod checklist
├── IONOS_UPLOAD.md                   # Ionos deployment
│
├── archive/                           # Fichiers doublons (v1)
│   ├── INDEX_v1.md
│   ├── INDEX_DOCUMENTATION_v1.md
│   ├── QUICK_START_v1.md
│   ├── QUICK_REFERENCE_DEVELOPER_v1.md
│   └── AUDIT_SECURITE_v1.md
│
└── obsolete/                          # Fichiers obsolètes
    ├── DEPLOY.md
    ├── DEPLOYMENT_CHECKLIST.md
    ├── ETAPES_POUR_TOI.md
    ├── ETAPE_3_JWT_SECRET.md
    ├── IONOS_PRODUCTION.md
    ├── README_MAINTENANT.md
    └── PROJECT_SUMMARY.md
```

---

## ✅ Checklist Onboarding Nouveau Dev

- [ ] Lire [QUICKSTART.md](QUICKSTART.md)
- [ ] Setup local avec [LOCAL_TESTING.md](LOCAL_TESTING.md)
- [ ] Comprendre l'architecture via [DEVELOPPEMENT.md](DEVELOPPEMENT.md)
- [ ] Étudier [API_DOCUMENTATION.md](API_DOCUMENTATION.md)
- [ ] Respecter [BONNES_PRATIQUES_EQUIPE.md](BONNES_PRATIQUES_EQUIPE.md)
- [ ] Vérifier [SECURITY_GUIDE.md](SECURITY_GUIDE.md)

---

## 🔐 Credentials & Configuration

**`.env` (non-commité)**:
```bash
DB_HOST=db5019387279.hosting-data.io
DB_USER=dbu2705925
DB_PASSWORD=Atc13001!74529012!
DB_NAME=dbs15168768
JWT_SECRET=unique-production-secret
```

**Test User**:
```
Email: admin@atelier-thierry.fr
Password: password123
```

---

## 📞 Support & Questions

Pour toute question:
1. Vérifier la doc correspondante dans [docs/](.)
2. Chercher dans les issues GitHub
3. Contacter l'équipe dev

---

**Dernière mise à jour**: 15 janvier 2026  
**Maintaineur**: Atelier Thierry Dev Team
