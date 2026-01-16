<?php
/**
 * DIAGNOSTIC COMPLET - Vérification point par point
 * Teste chaque composant du système
 */

$errors = [];
$warnings = [];
$success = [];

echo "═══════════════════════════════════════════════════════════════\n";
echo "DIAGNOSTIC COMPLET - 16 JANVIER 2026\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. BASE DE DONNÉES
echo "1️⃣  VÉRIFICATION BASE DE DONNÉES\n";
echo "─────────────────────────────────\n";

$projectRoot = dirname(__FILE__);
$dbPath = $projectRoot . '/compta.db';

if (!file_exists($dbPath)) {
    $errors[] = "❌ Base de données introuvable: $dbPath";
} else {
    $success[] = "✅ Base de données trouvée";
    
    try {
        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $success[] = "✅ Connexion PDO réussie";
        
        // Vérifier les tables
        $tables = ['ecritures', 'fin_balance', 'fin_ecritures_fec', 'client_sales', 'monthly_sales'];
        foreach ($tables as $table) {
            $stmt = $db->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "   • $table: $count lignes\n";
        }
        
        // Vérifier les écritures
        echo "\n   Écritures par mois (2024):\n";
        $stmt = $db->query("
            SELECT strftime('%Y-%m', ecriture_date) as mois, 
                   COUNT(*) as cnt,
                   SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END) as debit,
                   SUM(CASE WHEN credit > 0 THEN credit ELSE 0 END) as credit
            FROM ecritures 
            WHERE exercice = 2024 
            GROUP BY mois 
            ORDER BY mois
        ");
        $monthData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($monthData as $row) {
            echo "      {$row['mois']}: {$row['cnt']} écritures, Débit={$row['debit']}, Crédit={$row['credit']}\n";
        }
        
    } catch (Exception $e) {
        $errors[] = "❌ Erreur PDO: " . $e->getMessage();
    }
}

echo "\n";

// 2. VÉRIFIER L'API /api/v1/analytics/advanced.php
echo "2️⃣  VÉRIFICATION API ADVANCED ANALYTICS\n";
echo "───────────────────────────────────────\n";

$apiFile = $projectRoot . '/public_html/api/v1/analytics/advanced.php';
if (!file_exists($apiFile)) {
    $errors[] = "❌ Fichier API introuvable: $apiFile";
} else {
    $success[] = "✅ Fichier API trouvé";
    
    // Simulation appel API
    $_GET['exercice'] = 2024;
    ob_start();
    include $apiFile;
    $apiResponse = ob_get_clean();
    
    $decoded = json_decode($apiResponse, true);
    if ($decoded === null) {
        $errors[] = "❌ Réponse API non JSON valide";
        echo "Réponse brute:\n";
        echo substr($apiResponse, 0, 500) . "\n";
    } else {
        if ($decoded['success'] === true) {
            $success[] = "✅ API retourne success: true";
            
            $data = $decoded['data'];
            echo "   Structure data reçue:\n";
            echo "   • exercice: " . $data['exercice'] . "\n";
            echo "   • stats_globales.total_operations: " . ($data['stats_globales']['total_operations'] ?? 'MANQUANT') . "\n";
            echo "   • evolution_mensuelle: " . count($data['evolution_mensuelle'] ?? []) . " lignes\n";
            
            if (count($data['evolution_mensuelle']) > 0) {
                echo "     Premier mois:\n";
                $first = $data['evolution_mensuelle'][0];
                foreach ($first as $k => $v) {
                    echo "       - $k: $v\n";
                }
            } else {
                $errors[] = "❌ evolution_mensuelle est vide!";
            }
            
        } else {
            $errors[] = "❌ API retourne success: false";
            echo "   Erreur: " . ($decoded['error'] ?? 'Inconnue') . "\n";
        }
    }
}

echo "\n";

// 3. VÉRIFIER LA TRANSFORMATION DANS ADVANCEDANALYTICS.JSX
echo "3️⃣  VÉRIFICATION TRANSFORMATION REACT\n";
echo "────────────────────────────────────\n";

$frontendFile = $projectRoot . '/frontend/src/components/AdvancedAnalytics.jsx';
if (!file_exists($frontendFile)) {
    $errors[] = "❌ Fichier React introuvable: $frontendFile";
} else {
    $success[] = "✅ Fichier React trouvé";
    
    $content = file_get_contents($frontendFile);
    
    // Chercher la transformation
    if (preg_match('/\.map\s*\(\s*m\s*=>\s*\(\s*\{\s*periode/', $content)) {
        $warnings[] = "⚠️  Transformation utilise 'm.periode' mais API retourne 'm.mois'";
    }
    
    if (preg_match('/ca:\s*m\.ca_net/', $content)) {
        $warnings[] = "⚠️  Transformation utilise 'm.ca_net' mais API n'a pas ce champ";
    }
}

echo "\n";

// 4. VÉRIFIER LE COMPOSANT ANALYTICSREVENUECHARTSTYPE
echo "4️⃣  VÉRIFICATION COMPOSANT CHARTSTYPE\n";
echo "───────────────────────────────────────\n";

$chartFile = $projectRoot . '/frontend/src/components/charts/AnalyticsRevenueCharts.jsx';
if (!file_exists($chartFile)) {
    $errors[] = "❌ Fichier Chart introuvable: $chartFile";
} else {
    $success[] = "✅ Fichier Chart trouvé";
    
    $content = file_get_contents($chartFile);
    
    if (preg_match('/dataKey="mois"/', $content)) {
        echo "   ✅ Chart utilise dataKey='mois' pour l'axe X\n";
    }
    
    if (preg_match('/dataKey="ca"/', $content)) {
        echo "   ✅ Chart utilise dataKey='ca' pour les valeurs\n";
    }
    
    if (preg_match('/XAxis.*dataKey="mois".*YAxis/', $content)) {
        $success[] = "✅ Configuration graphique correcte";
    }
}

echo "\n";

// 5. RÉSUMÉ DES PROBLÈMES
echo "═══════════════════════════════════════════════════════════════\n";
echo "RÉSUMÉ DES PROBLÈMES\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if (count($errors) > 0) {
    echo "❌ ERREURS CRITIQUES:\n";
    foreach ($errors as $err) {
        echo "   $err\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "⚠️  AVERTISSEMENTS:\n";
    foreach ($warnings as $warn) {
        echo "   $warn\n";
    }
    echo "\n";
}

echo "✅ SUCCÈS:\n";
foreach ($success as $succ) {
    echo "   $succ\n";
}

echo "\n";

// 6. RECOMMANDATIONS
echo "═══════════════════════════════════════════════════════════════\n";
echo "RECOMMANDATIONS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "1. Corriger AdvancedAnalytics.jsx:\n";
echo "   - Changer 'm.periode' en 'm.mois'\n";
echo "   - Changer 'm.ca_net' en 'm.debit' (ou calculer CA = debit - credit)\n";
echo "   - Ajouter calcul CA mensuel depuis evolution_mensuelle\n\n";

echo "2. Vérifier pourquoi le composant 'clignote':\n";
echo "   - Check pour boucles de re-render infinies\n";
echo "   - Check pour états non synchronisés\n";
echo "   - Check pour erreurs JavaScript en console\n\n";

echo "3. Tester les 5 endpoints:\n";
echo "   - /api/v1/kpis/detailed.php\n";
echo "   - /api/v1/balance/simple.php\n";
echo "   - /api/v1/analytics/kpis.php\n";
echo "   - /api/v1/analytics/analysis.php\n";
echo "   - /api/v1/analytics/advanced.php\n\n";
?>
