<?php
/**
 * Point d'entrée API REST
 * /public_html/api/index.php
 * 
 * Redirigée par .htaccess depuis /api/* vers api/index.php
 * Structure : /api/endpoint/param1/param2
 */

// ========================================
// Configuration d'initialisation
// ========================================

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Chemins pour le chargement des classes
// Sur Ionos, le backend est en dehors du web root
define('APP_ROOT', dirname(dirname(dirname(__FILE__))));  // /compta
define('BACKEND_ROOT', APP_ROOT . '/backend');

// Log des chemins pour debug
error_log("API init - __DIR__: " . __DIR__);
error_log("API init - APP_ROOT: " . APP_ROOT);
error_log("API init - BACKEND_ROOT: " . BACKEND_ROOT);
error_log("API init - Database file: " . BACKEND_ROOT . '/config/Database.php');
error_log("API init - Database exists: " . (file_exists(BACKEND_ROOT . '/config/Database.php') ? 'YES' : 'NO'));

// Autoloader simple (sans Composer dans ce contexte)
spl_autoload_register(function($class) {
    // Convertit App\Config\Logger en config/Logger.php
    $class = str_replace('App\\', '', $class);
    // Convertit les backslashes en slashes
    $path = str_replace('\\', '/', $class);
    // Convertit la première partie (Config, Services) en minuscules
    $parts = explode('/', $path);
    if (count($parts) > 0) {
        $parts[0] = strtolower($parts[0]);
    }
    $path = implode('/', $parts);
    
    $filePath = BACKEND_ROOT . '/' . $path . '.php';
    
    if (file_exists($filePath)) {
        require_once $filePath;
    } else {
        error_log("Autoloader - File NOT found: " . $filePath . " (Original class: " . $class . ")");
    }
});

// ========================================
// Imports
// ========================================

use App\Config\Database;
use App\Config\Router;
use App\Config\Logger;
use App\Services\ImportService;
use App\Services\SigCalculator;

Logger::init();

try {
    // ========================================
    // Initialisation du routeur
    // ========================================
    
    $router = new Router();
    
    // ========================================
    // ROUTES GET - Récupération de données
    // ========================================
    
    /**
     * GET /api/debug/tables
     * Vérifie les tables et données
     */
    $router->get('/debug/tables', function() {
        $db = Database::getInstance();
        
        $tables = [
            'fin_balance' => 0,
            'fin_ecritures_fec' => 0,
            'sys_plan_comptable' => 0
        ];
        
        try {
            foreach ($tables as $table => &$count) {
                $result = $db->fetchOne("SELECT COUNT(*) as cnt FROM $table");
                $count = $result['cnt'] ?? 0;
            }
        } catch (\Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
        
        return json_encode([
            'tables' => $tables,
            'exercice_sample' => $db->fetchOne("SELECT DISTINCT exercice FROM fin_balance LIMIT 5") ?? 'No data'
        ]);
    });
    
    /**
     * GET /api/health
     * Check l'état de l'API
     */
    $router->get('/health', function() {
        $db = Database::getInstance();
        $dbStatus = false;
        $message = 'OK';
        
        try {
            $result = $db->fetchOne("SELECT 1");
            $dbStatus = $result ? true : false;
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }
        
        return json_encode([
            'status' => 'OK',
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => $dbStatus ? 'connected' : 'disconnected',
            'db_message' => $message
        ]);
    });
    
    /**
     * GET /api/balance/:exercice
     * Retourne la balance complète d'un exercice
     * Avec pagination pour très gros fichiers
     */
    $router->get('/balance/:exercice', function($exercice) {
        $db = Database::getInstance();
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 100;
        $offset = ($page - 1) * $limit;
        
        // Total pour pagination
        $total = $db->fetchOne(
            "SELECT COUNT(*) as count FROM fin_balance WHERE exercice = ?",
            [$exercice]
        )['count'];
        
        // Données
        $balances = $db->fetchAll(
            "SELECT b.*, p.libelle, p.classe_racine 
             FROM fin_balance b
             JOIN sys_plan_comptable p ON b.compte_num = p.compte_num
             WHERE b.exercice = ?
             ORDER BY b.compte_num
             LIMIT ? OFFSET ?",
            [$exercice, $limit, $offset]
        );
        
        return json_encode([
            'success' => true,
            'data' => $balances,
            'pagination' => [
                'page' => (int) $page,
                'limit' => (int) $limit,
                'total' => (int) $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    });
    
    /**
     * GET /api/ecritures/:exercice
     * Retourne les écritures du FEC avec filtrage
     */
    $router->get('/ecritures/:exercice', function($exercice) {
        $db = Database::getInstance();
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 50;
        $offset = ($page - 1) * $limit;
        
        $compte = $_GET['compte'] ?? null;
        $journal = $_GET['journal'] ?? null;
        $dateDebut = $_GET['date_debut'] ?? null;
        $dateFin = $_GET['date_fin'] ?? null;
        
        // Filtre
        $sql = "SELECT * FROM fin_ecritures_fec WHERE exercice = ?";
        $params = [$exercice];
        
        if ($compte) {
            $sql .= " AND compte_num = ?";
            $params[] = $compte;
        }
        if ($journal) {
            $sql .= " AND journal_code = ?";
            $params[] = $journal;
        }
        if ($dateDebut) {
            $sql .= " AND ecriture_date >= ?";
            $params[] = $dateDebut;
        }
        if ($dateFin) {
            $sql .= " AND ecriture_date <= ?";
            $params[] = $dateFin;
        }
        
        // Total
        $countSql = str_replace("SELECT *", "SELECT COUNT(*) as count", $sql);
        $total = $db->fetchOne($countSql, $params)['count'];
        
        // Données
        $sql .= " ORDER BY ecriture_date DESC, ecriture_num DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $ecritures = $db->fetchAll($sql, $params);
        
        return json_encode([
            'success' => true,
            'data' => $ecritures,
            'pagination' => [
                'page' => (int) $page,
                'limit' => (int) $limit,
                'total' => (int) $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    });
    
    /**
     * GET /api/sig/:exercice
     * Retourne tous les SIG pour un exercice
     */
    $router->get('/sig/:exercice', function($exercice) {
        $sigCalculator = new SigCalculator($exercice);
        
        return json_encode([
            'success' => true,
            'data' => [
                'cascade' => $sigCalculator->calculCascadeSIG(),
                'kpis' => $sigCalculator->calculKPIs(),
                'waterfall_data' => $sigCalculator->getWaterfallData(),
                'exercice' => (int) $exercice
            ]
        ]);
    });
    
    /**
     * GET /api/annees
     * Retourne la liste des années avec données comptables
     */
    $router->get('/annees', function() {
        $db = Database::getInstance();
        
        try {
            // Récupère toutes les années disponibles dans la balance
            $annees = $db->fetchAll(
                "SELECT DISTINCT exercice FROM fin_balance ORDER BY exercice DESC"
            );
            
            $result = [];
            foreach ($annees as $row) {
                $year = (int) $row['exercice'];
                // Compte les écritures pour chaque année
                $count = $db->fetchOne(
                    "SELECT COUNT(*) as cnt FROM fin_ecritures_fec WHERE exercice = ?",
                    [$year]
                )['cnt'];
                
                $result[] = [
                    'annee' => $year,
                    'ecritures' => $count
                ];
            }
            
            return json_encode([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Logger::error("Erreur récupération années", ['error' => $e->getMessage()]);
            http_response_code(500);
            return json_encode(['error' => $e->getMessage()]);
        }
    });
    
    /**
     * GET /api/annee/:exercice/exists
     * Vérifie si une année a déjà des données
     */
    $router->get('/annee/:exercice/exists', function($exercice) {
        $db = Database::getInstance();
        
        try {
            $count = $db->fetchOne(
                "SELECT COUNT(*) as cnt FROM fin_ecritures_fec WHERE exercice = ?",
                [(int) $exercice]
            )['cnt'] ?? 0;
            
            return json_encode([
                'success' => true,
                'exists' => $count > 0,
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'exists' => false,
                'error' => $e->getMessage()
            ]);
        }
    });
    
    /**
     * POST /api/annee/:exercice/clear
     * Efface toutes les données d'une année
     * Utilisé avant un override
     */
    $router->post('/annee/:exercice/clear', function() {
        $exercice = (int) $_POST['exercice'] ?? null;
        if (!$exercice) {
            http_response_code(400);
            return json_encode(['error' => 'Année requise']);
        }
        
        $db = Database::getInstance();
        
        try {
            Logger::info("Suppression données année", ['exercice' => $exercice]);
            
            // Supprime les écritures
            $db->query(
                "DELETE FROM fin_ecritures_fec WHERE exercice = ?",
                [$exercice]
            );
            
            // Supprime la balance
            $db->query(
                "DELETE FROM fin_balance WHERE exercice = ?",
                [$exercice]
            );
            
            return json_encode([
                'success' => true,
                'message' => 'Données de l\'année ' . $exercice . ' supprimées'
            ]);
        } catch (\Exception $e) {
            Logger::error("Erreur suppression année", ['error' => $e->getMessage()]);
            http_response_code(500);
            return json_encode(['error' => $e->getMessage()]);
        }
    });
    
    /**
     * GET /api/comparaison/annees
     * Retourne les SIG comparés de plusieurs années
     */
    $router->get('/comparaison/annees', function() {
        $annees = $_GET['annees'] ?? null;
        if (!$annees) {
            http_response_code(400);
            return json_encode(['error' => 'Paramètre annees requis (ex: 2024,2025)']);
        }
        
        $anneesList = array_map('intval', explode(',', $annees));
        $comparaison = [];
        
        try {
            foreach ($anneesList as $year) {
                $sigCalculator = new SigCalculator($year);
                $kpis = $sigCalculator->calculKPIs();
                $cascade = $sigCalculator->calculCascadeSIG();
                
                $comparaison[$year] = [
                    'kpis' => $kpis,
                    'cascade' => $cascade
                ];
            }
            
            return json_encode([
                'success' => true,
                'data' => $comparaison
            ]);
        } catch (\Exception $e) {
            Logger::error("Erreur comparaison", ['error' => $e->getMessage()]);
            http_response_code(500);
            return json_encode(['error' => $e->getMessage()]);
        }
    });
    
    /**
     * Détail complet des SIG avec explications
     */
    $router->get('/sig/:exercice/detail', function($exercice) {
        $sigCalculator = new SigCalculator($exercice);
        
        $cascade = $sigCalculator->calculCascadeSIG();
        
        // Formate avec les symboles et couleurs
        foreach ($cascade as &$sig) {
            $sig['formatted'] = $sigCalculator->formatSIG($sig['valeur']);
        }
        
        return json_encode([
            'success' => true,
            'data' => $cascade,
            'exercice' => (int) $exercice
        ]);
    });
    
    /**
     * GET /api/kpis/:exercice
     * KPI bijouterie (stocks, trésorerie, ratios)
     */
    $router->get('/kpis/:exercice', function($exercice) {
        $sigCalculator = new SigCalculator($exercice);
        $kpis = $sigCalculator->calculKPIs();
        
        return json_encode([
            'success' => true,
            'data' => $kpis,
            'exercice' => (int) $exercice
        ]);
    });
    
    /**
     * GET /api/plan-comptable
     * Retourne le plan comptable complet
     */
    $router->get('/plan-comptable', function() {
        $db = Database::getInstance();
        
        $comptes = $db->fetchAll(
            "SELECT compte_num, libelle, classe_racine, type_compte 
             FROM sys_plan_comptable 
             WHERE is_actif = TRUE 
             ORDER BY compte_num"
        );
        
        return json_encode([
            'success' => true,
            'data' => $comptes,
            'count' => count($comptes)
        ]);
    });
    
    /**
     * GET /api/journaux
     * Retourne les journaux
     */
    $router->get('/journaux', function() {
        $db = Database::getInstance();
        
        $journaux = $db->fetchAll(
            "SELECT code, libelle, type_journal FROM sys_journaux WHERE is_actif = TRUE"
        );
        
        return json_encode([
            'success' => true,
            'data' => $journaux
        ]);
    });
    
    // ========================================
    // ROUTES POST - Actions
    // ========================================
    
    /**
     * POST /api/import/fec
     * Importe un fichier FEC (.txt ou .csv)
     */
    $router->post('/import/fec', function() {
        if (!isset($_FILES['file'])) {
            http_response_code(400);
            return json_encode(['error' => 'Fichier requis']);
        }
        
        $file = $_FILES['file'];
        $tempPath = $file['tmp_name'];
        
        if (!file_exists($tempPath)) {
            http_response_code(400);
            return json_encode(['error' => 'Erreur upload fichier']);
        }
        
        try {
            Logger::info("FEC Upload", ['file' => $file['name'], 'size' => filesize($tempPath)]);
            
            $importService = new ImportService();
            $result = $importService->importFEC($tempPath);
            
            Logger::info("Import FEC réussi", $result);
            
            return json_encode([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Logger::error("Erreur import FEC", ['error' => $e->getMessage(), 'file' => $file['name']]);
            http_response_code(500);
            return json_encode([
                'error' => $e->getMessage(),
                'debug' => [
                    'file' => $file['name'],
                    'size' => filesize($tempPath),
                    'exists' => file_exists($tempPath)
                ]
            ]);
        } finally {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    });
    
    /**
     * POST /api/import/excel
     * Importe un fichier Excel
     */
    $router->post('/import/excel', function() {
        if (!isset($_FILES['file'])) {
            http_response_code(400);
            return json_encode(['error' => 'Fichier requis']);
        }
        
        $file = $_FILES['file'];
        $tempPath = $file['tmp_name'];
        $sheetName = $_POST['sheet_name'] ?? null;
        
        if (!file_exists($tempPath)) {
            http_response_code(400);
            return json_encode(['error' => 'Erreur upload fichier']);
        }
        
        try {
            $importService = new ImportService();
            $result = $importService->importExcel($tempPath, $sheetName);
            
            Logger::info("Import Excel réussi", $result);
            
            return json_encode([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Logger::error("Erreur import Excel", ['error' => $e->getMessage()]);
            http_response_code(500);
            return json_encode(['error' => $e->getMessage()]);
        } finally {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    });
    
    /**
     * POST /api/import/archive
     * Importe une archive (.tar, .tar.gz)
     */
    $router->post('/import/archive', function() {
        if (!isset($_FILES['file'])) {
            http_response_code(400);
            return json_encode(['error' => 'Fichier requis']);
        }
        
        $file = $_FILES['file'];
        $tempPath = $file['tmp_name'];
        
        if (!file_exists($tempPath)) {
            http_response_code(400);
            return json_encode(['error' => 'Erreur upload fichier']);
        }
        
        try {
            $importService = new ImportService();
            $result = $importService->importArchive($tempPath);
            
            Logger::info("Import Archive réussi", $result);
            
            return json_encode([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Logger::error("Erreur import Archive", ['error' => $e->getMessage()]);
            http_response_code(500);
            return json_encode(['error' => $e->getMessage()]);
        } finally {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    });
    
    /**
     * POST /api/recalcul-balance
     * Recalcule la balance depuis les écritures FEC
     * Utile après import ou modification
     */
    $router->post('/recalcul-balance', function() {
        $exercice = $_POST['exercice'] ?? (int) date('Y');
        $db = Database::getInstance();
        
        try {
            // Vide la balance pour l'exercice
            $db->query("DELETE FROM fin_balance WHERE exercice = ?", [$exercice]);
            
            // Recalcule
            $sql = "
                INSERT INTO fin_balance (exercice, compte_num, debit, credit, solde)
                SELECT 
                    exercice,
                    compte_num,
                    SUM(debit) as debit,
                    SUM(credit) as credit,
                    SUM(debit) - SUM(credit) as solde
                FROM fin_ecritures_fec
                WHERE exercice = ?
                GROUP BY compte_num
                ON DUPLICATE KEY UPDATE
                    debit = VALUES(debit),
                    credit = VALUES(credit),
                    solde = VALUES(solde)
            ";
            
            $db->query($sql, [$exercice]);
            
            Logger::info("Balance recalculée", ['exercice' => $exercice]);
            
            return json_encode([
                'success' => true,
                'message' => 'Balance recalculée'
            ]);
        } catch (\Exception $e) {
            Logger::error("Erreur recalcul balance", ['error' => $e->getMessage()]);
            http_response_code(500);
            return json_encode(['error' => $e->getMessage()]);
        }
    });
    
    // ========================================
    // Exécution du routeur
    // ========================================
    
    $response = $router->run();
    echo $response;
    
} catch (\Exception $e) {
    Logger::error("Erreur API", ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur serveur',
        'message' => $e->getMessage()
    ]);
}
