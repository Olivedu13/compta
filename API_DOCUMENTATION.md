# 📚 Documentation API - Phase 3

## Vue d'ensemble

Phase 3 introduit 4 nouveaux endpoints REST pour l'analyse des tiers et du cashflow. Ces APIs fournissent des données financières détaillées avec pagination, tri et filtrage.

**Serveur:** `http://localhost/api`
**Format:** JSON
**Authentification:** Optionnel (mode développement)
**Version:** 1.0

---

## 1. GET /api/tiers

Récupère la liste paginée des tiers (auxiliaires) avec agrégats financiers.

### Endpoint

```
GET /api/tiers
```

### Paramètres

| Paramètre | Type | Défaut | Description |
|-----------|------|--------|-------------|
| `exercice` | integer | 2024 | Année fiscale |
| `limit` | integer | 10 | Nombre de résultats par page |
| `offset` | integer | 0 | Position de départ (pagination) |
| `tri` | string | "montant" | Critère de tri: "montant", "nom", "ecritures" |

### Exemple de Requête

```bash
curl -X GET "http://localhost/api/tiers?exercice=2024&limit=10&offset=0&tri=montant"
```

### Réponse (200 OK)

```json
{
  "success": true,
  "exercice": 2024,
  "pagination": {
    "total": 125,
    "limit": 10,
    "offset": 0,
    "page": 1
  },
  "tiers": [
    {
      "numero": "08000009",
      "libelle": "GOLDMAN DIAMONDS",
      "nb_ecritures": 272,
      "nb_ecritures_lettrees": 32,
      "total_debit": 3514535.26,
      "total_credit": 3933830.18,
      "solde": -419294.92,
      "total_montant": 7448365.44,
      "date_premiere_ecriture": "2024-01-01",
      "date_derniere_ecriture": "2024-12-31"
    },
    ...
  ]
}
```

### Statuts HTTP

| Code | Description |
|------|-------------|
| 200 | Succès |
| 400 | Paramètres invalides |
| 500 | Erreur serveur |

---

## 2. GET /api/tiers/:numero

Récupère le détail complet d'un tiers avec ses écritures.

### Endpoint

```
GET /api/tiers/{numero}
```

### Paramètres

| Paramètre | Type | Description |
|-----------|------|-------------|
| `numero` | string (URL) | Numéro du tiers (ex: "08000009") |
| `exercice` | integer | Année fiscale (défaut: 2024) |
| `limit` | integer | Max écritures à retourner (défaut: 1000) |

### Exemple de Requête

```bash
curl -X GET "http://localhost/api/tiers/08000009?exercice=2024"
```

### Réponse (200 OK)

```json
{
  "success": true,
  "tiers": {
    "numero": "08000009",
    "libelle": "GOLDMAN DIAMONDS",
    "total_debit": 3514535.26,
    "total_credit": 3933830.18,
    "solde": -419294.92,
    "nb_ecritures": 272,
    "nb_comptes": 1,
    "journaux": ["AN", "CM", "AC", "BPM", "CL"],
    "date_premiere": "2024-01-01",
    "date_derniere": "2024-12-31"
  },
  "ecritures": [
    {
      "id": 11448,
      "date": "2024-12-31",
      "numero": "AC000319",
      "compte": "40100000",
      "compte_lib": "FOURNISSEURS",
      "journal": "AC",
      "libelle": "202412009 SHARON",
      "piece_ref": "8630",
      "piece_date": "2024-12-31",
      "debit": 0,
      "credit": 81668.22,
      "lettrage": {
        "code": "",
        "date": null
      }
    },
    ...
  ]
}
```

---

## 3. GET /api/cashflow

Analyse du cashflow par période avec répartition par journal.

### Endpoint

```
GET /api/cashflow
```

### Paramètres

| Paramètre | Type | Défaut | Description |
|-----------|------|--------|-------------|
| `exercice` | integer | 2024 | Année fiscale |
| `periode` | string | "mois" | Granularité: "mois" ou "trimestre" |

### Exemple de Requête

```bash
curl -X GET "http://localhost/api/cashflow?exercice=2024&periode=mois"
```

### Réponse (200 OK)

```json
{
  "success": true,
  "exercice": 2024,
  "periode": "mois",
  "stats_globales": {
    "total_entrees": 23458885.67,
    "total_sorties": 23458885.67,
    "flux_net_total": 0,
    "solde_theo": 0
  },
  "par_periode": [
    {
      "periode": "2024-01",
      "nb_ecritures": 3245,
      "entrees": 10667000.44,
      "sorties": 10667000.44,
      "flux_net": 0,
      "nb_comptes": 109,
      "nb_tiers": 113
    },
    ...
  ],
  "par_journal": [
    {
      "journal": "VE",
      "entrees": 3050872.6,
      "sorties": 3050872.6,
      "flux_net": 0,
      "nb_ecritures": 2869
    },
    ...
  ]
}
```

### Statuts HTTP

| Code | Description |
|------|-------------|
| 200 | Succès |
| 400 | Paramètres invalides |
| 404 | Exercice non trouvé |
| 500 | Erreur serveur |

---

## 4. GET /api/cashflow/detail/:journal

Détail du cashflow pour un journal spécifique avec flux par jour et top comptes.

### Endpoint

```
GET /api/cashflow/detail/{journal}
```

### Paramètres

| Paramètre | Type | Description |
|-----------|------|-------------|
| `journal` | string (URL) | Code journal (ex: "VE", "AC", "CM") |
| `exercice` | integer | Année fiscale (défaut: 2024) |

### Journaux Disponibles

| Code | Libellé |
|------|---------|
| VE | Ventes |
| OD | Opérations Diverses |
| CM | Cotisations Sociales |
| CL | Clôture |
| BPM | Banque/Paiements |
| AN | Annulation |
| AC | Achats |

### Exemple de Requête

```bash
curl -X GET "http://localhost/api/cashflow/detail/VE?exercice=2024"
```

### Réponse (200 OK)

```json
{
  "success": true,
  "journal": "VE",
  "exercice": 2024,
  "stats": {
    "total_debit": 3050872.6,
    "total_credit": 3050872.6,
    "solde": 0,
    "nb_ecritures": 2869,
    "nb_jours_actifs": 189,
    "date_debut": "2024-01-08",
    "date_fin": "2024-12-23"
  },
  "flux_par_jour": [
    {
      "date": "2024-12-23",
      "nb_ecritures": 15,
      "entrees": 3919.2,
      "sorties": 3919.2,
      "flux_net": 0
    },
    ...
  ],
  "top_comptes": [
    {
      "compte": "41100000",
      "libelle": "CLIENTS",
      "debit": 2917993,
      "credit": 132879.6,
      "solde": 2785113.4,
      "nb_ecritures": 960
    },
    ...
  ]
}
```

### Statuts HTTP

| Code | Description |
|------|-------------|
| 200 | Succès |
| 400 | Paramètres invalides |
| 404 | Journal non trouvé |
| 500 | Erreur serveur |

---

## Codes d'Erreur

```json
{
  "success": false,
  "error": "Description de l'erreur",
  "code": "ERROR_CODE"
}
```

### Codes Erreur Courants

| Code | Description |
|------|-------------|
| INVALID_PARAMS | Paramètres manquants ou invalides |
| NOT_FOUND | Ressource non trouvée |
| UNAUTHORIZED | Authentification requise |
| SERVER_ERROR | Erreur interne serveur |

---

## Exemples d'Utilisation

### JavaScript/Fetch

```javascript
// Récupérer tiers
const response = await fetch('/api/tiers?exercice=2024&limit=10');
const data = await response.json();
console.log(data.tiers);

// Récupérer cashflow
const cfResponse = await fetch('/api/cashflow?exercice=2024&periode=mois');
const cfData = await cfResponse.json();
console.log(cfData.stats_globales);
```

### cURL

```bash
# Tiers
curl -X GET "http://localhost/api/tiers?exercice=2024&limit=10" \
  -H "Accept: application/json"

# Cashflow detail
curl -X GET "http://localhost/api/cashflow/detail/VE?exercice=2024" \
  -H "Accept: application/json"
```

### Python

```python
import requests

# Tiers
response = requests.get('http://localhost/api/tiers', params={
    'exercice': 2024,
    'limit': 10
})
tiers = response.json()['tiers']

# Cashflow
cf = requests.get('http://localhost/api/cashflow', params={
    'exercice': 2024,
    'periode': 'mois'
})
stats = cf.json()['stats_globales']
```

---

## Limitations & Notes

- **Pagination:** Max 1000 résultats par requête
- **Cache:** Les données sont cachées 5 minutes
- **Rate Limit:** 100 requêtes/minute par IP
- **Timeout:** 30 secondes max par requête
- **Balance:** Toujours parfaite (Débit = Crédit)

---

## Questions Fréquentes

### Q: Que signifient "Débit" et "Crédit"?
**R:** En comptabilité:
- **Débit:** Ressources utilisées, actifs, charges
- **Crédit:** Ressources reçues, passifs, produits
- **Solde:** Débit - Crédit

### Q: Pourquoi le flux net est-il toujours 0?
**R:** Par principe comptable, toute écriture balance (elle affecte au moins 2 comptes).

### Q: Comment filtrer par tiers dans cashflow?
**R:** Utilisez `/api/tiers/{numero}` pour voir les écritures d'un tiers spécifique.

### Q: Quel est l'impact du paramètre "tri"?
**R:** 
- `montant`: Tris par total_debit décroissant
- `nom`: Tris alphabétique par libellé
- `ecritures`: Tris par nombre d'écritures décroissant

---

## Roadmap Future

- [ ] Authentification JWT complète
- [ ] Webhooks pour alertes financières
- [ ] Export CSV/PDF pour rapports
- [ ] Filtrage avancé (date range, comptes spécifiques)
- [ ] API GraphQL alternative
- [ ] Audit trail complet

---

**Version:** 1.0  
**Dernière mise à jour:** 2024-01-15  
**Auteur:** Phase 3 Development Team
