# NOTES DE DÉVELOPPEMENT

## Architecture Générale

### Principes de Conception

1. **Vanilla PHP** : Pas de framework lourd (Laravel, Symfony)
   - Routeur minimaliste 450 lignes
   - PDO natif (pas d'ORM)
   - Logs simples

2. **Streaming Obligatoire** : Pour mutualisé
   - OpenSpout (Excel ligne par ligne)
   - fgetcsv (FEC pas charger complet)
   - Batch insert (économise DB)

3. **SPA React** : Interface fluide
   - Vite pour build rapide
   - Material UI pour UI cohérente
   - Recharts pour visualisations

4. **Sécurité par Défaut** :
   - PDO prepared statements
   - .htaccess restrictive
   - Logs détaillés

## Structure Backend

### Database.php (Singleton)

```php
use App\Config\Database;

$db = Database::getInstance();
$rows = $db->fetchAll("SELECT * FROM table WHERE id = ?", [123]);
```

**Avantages:**
- Une seule connexion active (performance)
- Évite les injections SQL
- Code simple et testable

### Router.php (Léger)

```php
$router = new Router();
$router->get('/balance/:exercice', function($exercice) {
    // Traitement
    return json_encode($data);
});
$router->run();
```

**Avantages:**
- Zéro dépendance externe
- Pattern URL simple
- Middleware possible (future)

### ImportService.php (Streaming)

**Why NOT PhpSpreadsheet?**

❌ `$reader->load()` charge TOUT en RAM
❌ Fichier 50MB = 512MB de RAM requis
❌ Timeout en 2-3 minutes

✅ OpenSpout utilise `RowIterator`
✅ Même fichier 50MB = 10MB RAM
✅ Peut traiter 1 million écritures

```php
foreach ($sheet->getRowIterator() as $row) {
    // Traite 1 ligne = yield
    $batch[] = mapRow($row);
    if (count($batch) === 100) {
        insertBatch($batch); // Flush
        $batch = [];
    }
}
```

### SigCalculator.php (Logique Métier)

**Cascade:**

```
Classe 7 (701, 702, 703) = Produits
- Classe 6 (601, 602) = Charges Matières
= Marge de Production

- Classe 61, 62 = Services
= Valeur Ajoutée

- Classe 64 (Personnel)
- Classe 63 (Impôts)
+ Classe 74 (Divers)
= EBE/EBITDA

- Classe 681 (Amortissements)
= Résultat Exploitation
```

**Gestion des Signes:**

En comptabilité:
- Classe 1-5: Actif/Passif (débits positifs = emplois)
- Classe 6: Charges (débits positifs = consommations)
- Classe 7: Produits (crédits positifs = ressources)

```php
$solde = $debit - $credit;
// Si $solde > 0 = Débit prédominant
// Si $solde < 0 = Crédit prédominant
```

Pour l'affichage: toujours valeurs absolues

## Structure Frontend

### Services/api.js

```javascript
import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    timeout: 300000 // 5 min pour imports
});

export const apiService = {
    getBalance(exercice, page, limit),
    importFEC(file, onUploadProgress),
    getSIG(exercice),
    // ...
};
```

### Composants Réutilisables

**KPICard.jsx**: Affiche un KPI avec trend
```jsx
<KPICard 
    title="Stock Or"
    value={12500}
    trend={5}  // +5%
    color="secondary"
/>
```

**UploadZone.jsx**: Drag & drop
```jsx
<UploadZone onUploadSuccess={(result) => refetch()} />
```

### Pages Principales

**Dashboard.jsx**
- Sélecteur exercice
- Grille KPI (stocks, trésorerie, tiers)
- Graphique cascade SIG (Waterfall)
- Détail SIG en cartes

**ImportPage.jsx**
- Tabs: FEC / Excel / Archive
- Info format FEC (18 colonnes)
- Upload zone

**BalancePage.jsx**
- DataGrid (MUI X)
- Pagination côté serveur (important!)
- Tri et filtrage
- Export possible (future)

**SIGPage.jsx**
- Sélecteur exercice
- Cascade visuelle (cartes colorées)
- Graphique comparatif

### Thème Material UI

```javascript
const theme = createTheme({
    palette: {
        primary: { main: '#1a237e' },    // Bleu Nuit
        secondary: { main: '#ffb300' }   // Or
    }
});
```

## Workflow Typique: Import FEC

### 1. Frontend (React)

```javascript
// UploadZone.jsx
const onDrop = async (files) => {
    const formData = new FormData();
    formData.append('file', files[0]);
    
    const response = await apiService.importFEC(file, onUploadProgress);
    
    setUploadResult(response.data.data);
    await apiService.recalculBalance(2024);
};
```

### 2. Backend (PHP)

**/api/index.php routes:**
```php
$router->post('/import/fec', function() {
    $file = $_FILES['file']['tmp_name'];
    $service = new ImportService();
    $result = $service->importFEC($file);
    return json_encode($result);
});
```

### 3. ImportService (Streaming)

```php
while (($line = fgets($handle)) !== false) {
    $fields = str_getcsv($line, $separator);
    $batch[] = validateFecFields($fields);
    
    if (count($batch) >= 500) {
        insertBatchEcritures($batch); // INSERT 500 lignes d'un coup
        $batch = [];
    }
}
```

**Gains:**
- 500K écritures = 1000 requêtes (au lieu de 500K)
- Temps: ~1-2 min (au lieu de 30 min)
- RAM: 50MB (au lieu de 512MB)

### 4. Database Recalcul

```php
$router->post('/recalcul-balance', function() {
    $db->query("DELETE FROM fin_balance WHERE exercice = ?", [$exercice]);
    
    $sql = "INSERT INTO fin_balance (...) 
            SELECT exercice, compte_num, 
                   SUM(debit), SUM(credit), SUM(debit) - SUM(credit)
            FROM fin_ecritures_fec
            WHERE exercice = ?
            GROUP BY compte_num";
    
    $db->query($sql, [$exercice]);
});
```

### 5. SIG Calculé à la Demande

```php
$router->get('/sig/:exercice', function($exercice) {
    $calc = new SigCalculator($exercice);
    return json_encode([
        'cascade' => $calc->calculCascadeSIG(),
        'kpis' => $calc->calculKPIs(),
        'waterfall_data' => $calc->getWaterfallData()
    ]);
});
```

### 6. Frontend Affiche

```javascript
useEffect(() => {
    const { data } = await apiService.getSIG(exercice);
    
    setWaterfallData(data.waterfall_data);
    setKpis(data.kpis);
}, [exercice]);

// Recharts affiche le graphique
<ResponsiveContainer width="100%" height={400}>
    <BarChart data={waterfallData}>
        <Bar dataKey="value" fill="#1a237e" />
    </BarChart>
</ResponsiveContainer>
```

## Optimisations Clés

### 1. Index MySQL

```sql
CREATE INDEX idx_ecriture_date ON fin_ecritures_fec(ecriture_date);
CREATE INDEX idx_compte_num ON fin_ecritures_fec(compte_num);
CREATE INDEX idx_exercice ON fin_ecritures_fec(exercice);
```

Requête SIG sans index: 5 secondes
Requête SIG avec index: 0.2 secondes

### 2. Pagination Serveur

**Balance endpoint:**
```php
// Récupère page 2, 50 lignes par page
$offset = ($page - 1) * $limit;
$rows = $db->fetchAll(
    "SELECT ... LIMIT ? OFFSET ?",
    [$limit, $offset]
);
```

1 million comptes → 20000 pages
Frontend peut charger n'importe quelle page en 0.1s

### 3. Cache Frontend

React hook personnalisé (future):
```javascript
const useBalance = (exercice) => {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(false);
    
    useEffect(() => {
        // Fetch + cache dans localStorage
    }, [exercice]);
    
    return { data, loading };
};
```

### 4. Batch Insert

**SLOW:**
```php
for ($i = 0; $i < count($rows); $i++) {
    $db->insert('table', $rows[$i]); // 5000 requêtes!
}
```

**FAST:**
```php
$batches = array_chunk($rows, 500);
foreach ($batches as $batch) {
    insertBatch($batch); // 10 requêtes
}
```

## Testing (Future)

### Unit Tests (PHPUnit)

```php
class SigCalculatorTest extends TestCase {
    public function testMargeProduction() {
        $calculator = new SigCalculator(2024);
        $marge = $calculator->calculMargeProduction();
        
        $this->assertIsFloat($marge);
        $this->assertGreaterThan(0, $marge);
    }
}
```

### Integration Tests (React)

```javascript
describe('Dashboard', () => {
    it('displays KPI cards', async () => {
        render(<Dashboard />);
        
        const orStock = await screen.findByText(/Stock Or/i);
        expect(orStock).toBeInTheDocument();
    });
});
```

## Logging & Monitoring

### Logger.php

```php
Logger::info("Import FEC", ['rows' => 5000, 'time' => '2.5s']);
Logger::error("Erreur DB", ['error' => 'Timeout']);
Logger::debug("Solde compte 601", ['solde' => 12500]);
```

Logs: `/backend/logs/2024-01-13.log`

### Monitoring Recommandé

- Uptimerobot (ping /api/health)
- Sentry (error tracking)
- New Relic (performance)

## Points Clés

✅ **Pas de Node en production** (Vite fait le build localement)
✅ **Streaming obligatoire** (fichiers > 50MB)
✅ **Index DB critiques** (SIG 100x plus rapide)
✅ **Batch insert** (500x plus rapide que boucle)
✅ **SPA Routing** (.htaccess crucial)
✅ **Permissions fichiers** (backend hors web)
✅ **Logs détaillés** (debug production)

---

**Prochaines améliorations:**

- [ ] Export PDF rapports SIG
- [ ] Authentification utilisateurs
- [ ] Historique versioning
- [ ] Graphiques plus avancés (Nivo)
- [ ] Mobile app (React Native)
- [ ] Tests unitaires complets
- [ ] API docs (OpenAPI/Swagger)
- [ ] Webhooks notifications
