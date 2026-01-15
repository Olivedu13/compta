# QUICK START & EXEMPLES

## 5 Minutes pour Démarrer

### 1. Créer la BD (local)

```bash
# Créer base
mysql -u root -p
> CREATE DATABASE compta_atc;
> CREATE USER 'compta_user'@'localhost' IDENTIFIED BY 'password123';
> GRANT ALL ON compta_atc.* TO 'compta_user'@'localhost';

# Importer schema
mysql -u compta_user -p compta_atc < backend/config/schema.sql
```

### 2. Configurer PHP

Éditer `backend/config/Database.php`:

```php
private $host = 'localhost';
private $db = 'compta_atc';
private $user = 'compta_user';
private $password = 'password123';
```

### 3. Build Frontend

```bash
cd frontend
npm install
npm run build

# Build généré dans /public_html/assets
```

### 4. Lancer Serveur Local

```bash
# Option A: PHP intégré (simple)
php -S localhost:8000 -t public_html

# Option B: Apache (mieux)
# Configurer VirtualHost pointant sur public_html
```

### 5. Accéder

```
http://localhost:8000
```

Vous devez voir le dashboard React

---

## Exemples de Code

### Exemple 1: Appeler l'API depuis React

```javascript
import apiService from '../services/api';

export function MonComposant() {
    const [balance, setBalance] = useState(null);
    const [loading, setLoading] = useState(true);
    
    useEffect(() => {
        // Récupère la balance 2024
        apiService.getBalance(2024, 1, 100)
            .then(response => {
                setBalance(response.data.data);
            })
            .catch(error => {
                console.error('Erreur:', error);
            })
            .finally(() => setLoading(false));
    }, []);
    
    if (loading) return <CircularProgress />;
    
    return (
        <div>
            {balance.map(compte => (
                <p key={compte.compte_num}>
                    {compte.compte_num}: {compte.solde}€
                </p>
            ))}
        </div>
    );
}
```

### Exemple 2: Ajouter une Nouvelle Route API

**File:** `/public_html/api/index.php`

```php
// Ajouter dans les routes GET:
$router->get('/custom/mes-donnees/:param', function($param) {
    $db = Database::getInstance();
    
    $data = $db->fetchAll(
        "SELECT * FROM table WHERE condition = ?",
        [$param]
    );
    
    Logger::info("Custom route called", ['param' => $param]);
    
    return json_encode([
        'success' => true,
        'data' => $data
    ]);
});
```

**Depuis React:**

```javascript
const response = await fetch('/api/custom/mes-donnees/valeur');
const data = await response.json();
console.log(data);
```

### Exemple 3: Ajouter un KPI au Dashboard

**File:** `/frontend/src/pages/Dashboard.jsx`

```jsx
// Ajouter dans la section KPI:
<Grid item xs={12} sm={6} md={3}>
    <KPICard
        title="Mon Indicateur"
        value={kpis?.custom?.valeur || 0}
        color="info"
        trend={3.5}
    />
</Grid>
```

**Puis dans SigCalculator pour calculer:**

```php
public function calculMonIndicateur() {
    $val1 = $this->getSolde('601');
    $val2 = $this->getSolde('701');
    
    return $val2 - $val1;
}
```

**Et l'exposer dans KPI:**

```php
public function calculKPIs() {
    // ...
    return [
        // ...
        'custom' => [
            'valeur' => $this->calculMonIndicateur()
        ]
    ];
}
```

### Exemple 4: Créer une Page de Rapport

**File:** `/frontend/src/pages/MonRapport.jsx`

```jsx
import React, { useEffect, useState } from 'react';
import {
    Typography,
    Box,
    Paper,
    CircularProgress
} from '@mui/material';
import apiService from '../services/api';

export default function MonRapport() {
    const [exercice, setExercice] = useState(2024);
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    
    useEffect(() => {
        const fetchData = async () => {
            try {
                // Appel API
                const balance = await apiService.getBalance(exercice);
                setData(balance.data.data);
            } catch (error) {
                console.error('Erreur:', error);
            } finally {
                setLoading(false);
            }
        };
        
        fetchData();
    }, [exercice]);
    
    if (loading) return <CircularProgress />;
    
    return (
        <Box>
            <Typography variant="h4" sx={{ mb: 3 }}>
                Mon Rapport
            </Typography>
            
            <Paper sx={{ p: 2 }}>
                <Typography>
                    Total: {data.reduce((s, r) => s + r.solde, 0)}€
                </Typography>
            </Paper>
        </Box>
    );
}
```

**L'ajouter dans Layout.jsx:**

```jsx
case 'mon-rapport':
    return <MonRapport />;
```

### Exemple 5: Importer et Traiter un Fichier

**File:** `/public_html/api/index.php`

```php
$router->post('/mon-import', function() {
    if (!isset($_FILES['file'])) {
        http_response_code(400);
        return json_encode(['error' => 'Fichier requis']);
    }
    
    $filePath = $_FILES['file']['tmp_name'];
    
    try {
        $importService = new ImportService();
        $result = $importService->importFEC($filePath);
        
        return json_encode([
            'success' => true,
            'data' => $result
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        return json_encode([
            'error' => $e->getMessage()
        ]);
    }
});
```

### Exemple 6: Query Complexe SIG

**File:** `/backend/services/SigCalculator.php`

```php
/**
 * Calcule un ratio personnalisé
 * Ratio = (Produits / Charges) * 100
 */
public function calculRatioCustom() {
    $produits = $this->sumSoldes(['701', '702', '703']);
    $charges = $this->sumSoldes(['601', '602', '64', '63']);
    
    if ($charges == 0) return 0;
    
    $ratio = ($produits / abs($charges)) * 100;
    
    Logger::info("Ratio Custom", ['ratio' => $ratio]);
    
    return $ratio;
}
```

**L'exposer:**

```php
public function calculKPIs() {
    return [
        // ...
        'ratios' => [
            'custom' => $this->calculRatioCustom()
        ]
    ];
}
```

---

## Commandes Utiles

### Development

```bash
# Frontend en mode dev
cd frontend && npm run dev

# Voir changes en temps réel
# http://localhost:5173

# Tester API
curl http://localhost:8000/api/health

# Voir logs PHP
tail -f /tmp/php.log
```

### Production

```bash
# Build Final
cd frontend && npm run build

# Uploader via FTP
# Utiliser FileZilla ou WinSCP

# Tester
curl https://votredomaine.fr/api/health

# Checker logs
tail -f /home/www.votredomaine.fr/backend/logs/2024-01-13.log
```

### Database

```bash
# Accéder MySQL
mysql -u compta_user -p compta_atc

# Voir les tables
SHOW TABLES;

# Voir le schema
DESCRIBE fin_ecritures_fec;

# Query rapide
SELECT COUNT(*) FROM fin_ecritures_fec WHERE exercice = 2024;

# Exporter backup
mysqldump -u compta_user -p compta_atc > backup.sql
```

---

## Workflow Complet: Importer + Voir SIG

### Étape 1: Préparer FEC

Créer fichier `mon_fec.txt`:

```
JournalCode	JournalLib	EcritureNum	EcritureDate	CompteNum	CompteLib	CompAuxNum	CompAuxLib	PieceRef	PieceDate	EcritureLib	Debit	Credit	EcritureLet	DateLet	ValidDate	MontantDevise	IdDevise
VE	Ventes	1	01/01/2024	701	Ventes	CLIENT001	Acme	FAC001	01/01/2024	Facture Acme	1000	0			01/01/2024	1000	EUR
AC	Achats	2	02/01/2024	601	Matières	FURN001	Diamant Inc	BL001	02/01/2024	Achat diamants	0	500			02/01/2024	500	EUR
BQ	Banque	3	03/01/2024	512	Banque	0	0	VIR001	03/01/2024	Virement	500	0			03/01/2024	500	EUR
```

### Étape 2: Importer via Interface

1. Aller à "Import FEC/Excel"
2. Déposer le fichier
3. Voir la barre de progression
4. ✅ "3 écritures importées"

### Étape 3: Voir Dashboard

1. Aller au "Dashboard"
2. Vérifier que les KPI se mettent à jour
3. Voir la cascade SIG (Marge, VA, EBE, etc.)

### Étape 4: Voir Rapports

1. Aller à "Rapports SIG"
2. Voir les graphiques
3. Voir le détail des SIG

### Étape 5: Voir Balance

1. Aller à "Balance"
2. Voir 3 comptes:
   - 601: Solde -500 (crédit)
   - 701: Solde 1000 (débit)
   - 512: Solde 500 (débit)

---

## Débugging

### Erreur: "API Health retourne 404"

**Cause:** .htaccess ne route pas vers /api/index.php

**Vérifier:**
```bash
# 1. Fichier existe?
ls -la public_html/.htaccess

# 2. Mod_rewrite actif?
apache2ctl -M | grep rewrite

# 3. Tester accès direct
curl http://localhost:8000/api/index.php?path=health
```

### Erreur: "Connexion MySQL échouée"

**Cause:** Identifiants incorrects

**Vérifier:**
```bash
# Identifiants dans Database.php
cat backend/config/Database.php | grep -E "host|user|password"

# Tester connexion
mysql -h localhost -u compta_user -p compta_atc -e "SELECT 1;"
```

### Erreur: "Upload timeout"

**Cause:** Fichier trop volumineux ou timeout PHP

**Solution:**
```php
// Vérifier .user.ini
cat public_html/.user.ini

// Doit avoir:
// upload_max_filesize = 64M
// post_max_size = 64M
// max_execution_time = 300
```

### Frontend ne charge pas

**Cause:** Build Vite manquant

**Solution:**
```bash
cd frontend
npm install
npm run build

# Vérifier
ls -la public_html/assets/index.jsx
```

---

## Ressources Utiles

- [Vite Docs](https://vitejs.dev/)
- [React Docs](https://react.dev/)
- [Material UI](https://mui.com/)
- [Recharts](https://recharts.org/)
- [Axios](https://axios-http.com/)
- [PDO](https://www.php.net/manual/en/book.pdo.php)
- [FEC Format](https://www.economie.gouv.fr/dgfip/fec)

---

**Besoin d'aide?**

1. Vérifier les logs: `/backend/logs/`
2. Ouvrir la console: F12 → Console
3. Tester l'API: `curl /api/health`
4. Lire DEVELOPPEMENT.md pour architecture
5. Contacter support Ionos pour problèmes serveur
