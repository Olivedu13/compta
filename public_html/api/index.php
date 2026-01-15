<?php
/**
 * Point d'entrée API REST
 * /public_html/api/index.php
 * 
 * Redirigée par .htaccess depuis /api/* vers api/index.php
 * Structure : /api/endpoint/param1/param2
 */

// ========================================
// Bootstrap Unique - Initialisation Complète
// ========================================

require_once dirname(dirname(dirname(__FILE__))) . '/backend/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// ========================================
// Imports des Classes Métier
// ========================================

use App\Config\Router;
use App\Config\Database;
use App\Config\Logger;
use App\Services\ImportService;
use App\Services\SigCalculator;

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
            'ecritures' => 0,
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
        $sql = "SELECT * FROM ecritures WHERE exercice = ?";
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
                    "SELECT COUNT(*) as cnt FROM ecritures WHERE exercice = ?",
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
                "SELECT COUNT(*) as cnt FROM ecritures WHERE exercice = ?",
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
                "DELETE FROM ecritures WHERE exercice = ?",
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
     * POST /api/analyze/fec
     * Analyse un fichier FEC AVANT import
     * Détecte format, anomalies, équilibre comptable
     * Retourne: format, headers, statistiques, anomalies, recommandations
     */
    $router->post('/analyze/fec', function() {
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
            Logger::info("FEC Analyse", ['file' => $file['name'], 'size' => filesize($tempPath)]);
            
            $importService = new ImportService();
            $analysis = $importService->analyzeFEC($tempPath);
            
            Logger::info("Analyse FEC réussie", [
                'ready_for_import' => $analysis['ready_for_import'],
                'anomalies_critical' => count($analysis['anomalies']['critical']),
                'anomalies_warnings' => count($analysis['anomalies']['warnings']),
            ]);
            
            return json_encode([
                'success' => true,
                'data' => $analysis
            ]);
        } catch (\Exception $e) {
            Logger::error("Erreur analyse FEC", ['error' => $e->getMessage(), 'file' => $file['name']]);
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
                FROM ecritures
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
    
    /**
     * GET /api/tiers
     * Liste tous les tiers avec détails financiers
     * Optionnel: ?exercice=2024&tri=nom
     */
    $router->get('/tiers', function() {
        $exercice = $_GET['exercice'] ?? (int) date('Y');
        $tri = $_GET['tri'] ?? 'montant'; // nom, montant, debit, credit
        $limit = (int)($_GET['limit'] ?? 100);
        $offset = (int)($_GET['offset'] ?? 0);
        
        $db = Database::getInstance();
        
        try {
            // Mappe les colonnes de tri acceptées
            $sortMap = [
                'nom' => 'lib_tiers',
                'montant' => 'total_montant',
                'debit' => 'total_debit',
                'credit' => 'total_credit',
                'ecritures' => 'nb_ecritures'
            ];
            $sortCol = $sortMap[$tri] ?? 'total_montant';
            
            // Requête pour récupérer les tiers avec leurs montants
            $sql = "
                SELECT 
                    numero_tiers as numero,
                    lib_tiers as libelle,
                    COUNT(*) as nb_ecritures,
                    SUM(debit) as total_debit,
                    SUM(credit) as total_credit,
                    SUM(debit) - SUM(credit) as solde,
                    SUM(debit + credit) as total_montant,
                    MIN(ecriture_date) as date_premiere_ecriture,
                    MAX(ecriture_date) as date_derniere_ecriture,
                    SUM(CASE WHEN lettrage_flag = 1 THEN 1 ELSE 0 END) as nb_ecritures_lettrees
                FROM ecritures
                WHERE exercice = ? AND numero_tiers IS NOT NULL AND numero_tiers != ''
                GROUP BY numero_tiers
                ORDER BY $sortCol DESC
                LIMIT ? OFFSET ?
            ";
            
            $tiers = $db->fetchAll($sql, [$exercice, $limit, $offset]);
            
            // Compte total pour pagination
            $countSql = "
                SELECT COUNT(DISTINCT numero_tiers) as total
                FROM ecritures
                WHERE exercice = ? AND numero_tiers IS NOT NULL AND numero_tiers != ''
            ";
            $countRow = $db->fetchOne($countSql, [$exercice]);
            
            return json_encode([
                'success' => true,
                'exercice' => (int)$exercice,
                'pagination' => [
                    'total' => (int)$countRow['total'],
                    'limit' => $limit,
                    'offset' => $offset,
                    'page' => floor($offset / $limit) + 1
                ],
                'tiers' => array_map(function($t) {
                    return [
                        'numero' => $t['numero'],
                        'libelle' => $t['libelle'],
                        'nb_ecritures' => (int)$t['nb_ecritures'],
                        'nb_ecritures_lettrees' => (int)$t['nb_ecritures_lettrees'],
                        'total_debit' => (float)$t['total_debit'],
                        'total_credit' => (float)$t['total_credit'],
                        'solde' => (float)$t['solde'],
                        'total_montant' => (float)$t['total_montant'],
                        'date_premiere_ecriture' => $t['date_premiere_ecriture'],
                        'date_derniere_ecriture' => $t['date_derniere_ecriture']
                    ];
                }, $tiers)
            ]);
        } catch (\Exception $e) {
            Logger::error("Erreur API tiers", ['error' => $e->getMessage()]);
            http_response_code(500);
            return json_encode(['error' => $e->getMessage()]);
        }
    });
    
    /**
     * GET /api/tiers/:numero
     * Détails d'un tiers spécifique avec toutes ses écritures
     */
    $router->get('/tiers/:numero', function($numero) {
        $exercice = $_GET['exercice'] ?? (int) date('Y');
        $db = Database::getInstance();
        
        try {
            // Info du tiers
            $tiersSql = "
                SELECT 
                    numero_tiers as numero,
                    lib_tiers as libelle,
                    COUNT(*) as nb_ecritures,
                    SUM(debit) as total_debit,
                    SUM(credit) as total_credit,
                    SUM(debit) - SUM(credit) as solde,
                    MIN(ecriture_date) as date_premiere,
                    MAX(ecriture_date) as date_derniere,
                    GROUP_CONCAT(DISTINCT journal_code) as journaux,
                    COUNT(DISTINCT compte_num) as nb_comptes
                FROM ecritures
                WHERE exercice = ? AND numero_tiers = ?
                GROUP BY numero_tiers
            ";
            $result = $db->query($tiersSql, [$exercice, $numero]);
            $tiers = ($result instanceof PDOStatement) ? $result->fetch(PDO::FETCH_ASSOC) : null;
            
            if (!$tiers) {
                http_response_code(404);
                return json_encode(['error' => 'Tiers non trouvé']);
            }
            
            // Écritures du tiers
            $ecrituresSql = "
                SELECT 
                    id,
                    ecriture_date,
                    ecriture_num,
                    compte_num,
                    compte_lib,
                    journal_code,
                    piece_ref,
                    piece_date,
                    libelle_ecriture,
                    debit,
                    credit,
                    debit - credit as solde_ligne,
                    lettrage_flag,
                    date_lettrage
                FROM ecritures
                WHERE exercice = ? AND numero_tiers = ?
                ORDER BY ecriture_date DESC
                LIMIT 1000
            ";
            $result = $db->query($ecrituresSql, [$exercice, $numero]);
            $ecritures = ($result instanceof PDOStatement) ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
            
            return json_encode([
                'success' => true,
                'tiers' => [
                    'numero' => $tiers['numero'],
                    'libelle' => $tiers['libelle'],
                    'total_debit' => (float)$tiers['total_debit'],
                    'total_credit' => (float)$tiers['total_credit'],
                    'solde' => (float)$tiers['solde'],
                    'nb_ecritures' => (int)$tiers['nb_ecritures'],
                    'nb_comptes' => (int)$tiers['nb_comptes'],
                    'journaux' => explode(',', $tiers['journaux'] ?? ''),
                    'date_premiere' => $tiers['date_premiere'],
                    'date_derniere' => $tiers['date_derniere']
                ],
                'ecritures' => array_map(function($e) {
                    return [
                        'id' => (int)$e['id'],
                        'date' => $e['ecriture_date'],
                        'numero' => $e['ecriture_num'],
                        'compte' => $e['compte_num'],
                        'compte_lib' => $e['compte_lib'],
                        'journal' => $e['journal_code'],
                        'libelle' => $e['libelle_ecriture'],
                        'piece_ref' => $e['piece_ref'],
                        'piece_date' => $e['piece_date'],
                        'debit' => (float)$e['debit'],
                        'credit' => (float)$e['credit'],
                        'lettrage' => [
                            'code' => $e['lettrage_flag'] ? 'L' : '',
                            'date' => $e['date_lettrage']
                        ]
                    ];
                }, $ecritures)
            ]);
        } catch (\Exception $e) {
            Logger::error("Erreur API tiers détail", ['error' => $e->getMessage()]);
            http_response_code(500);
            return json_encode(['error' => $e->getMessage()]);
        }
    });
    
    /**
     * GET /api/cashflow
     * Analyse des flux de trésorerie par mois/période
     * Optionnel: ?exercice=2024&periode=mois
     */
    $router->get('/cashflow', function() {
        $exercice = $_GET['exercice'] ?? (int) date('Y');
        $periode = $_GET['periode'] ?? 'mois'; // mois, trimestre, semaine
        $db = Database::getInstance();
        
        try {
            $sql = "";
            
            // SQL différent selon la période
            if ($periode === 'mois') {
                $sql = "
                    SELECT 
                        strftime('%Y-%m', ecriture_date) as periode,
                        COUNT(*) as nb_ecritures,
                        SUM(debit) as entrees,
                        SUM(credit) as sorties,
                        SUM(debit) - SUM(credit) as flux_net,
                        COUNT(DISTINCT compte_num) as nb_comptes,
                        COUNT(DISTINCT comp_aux_num) as nb_tiers
                    FROM ecritures
                    WHERE exercice = ?
                    GROUP BY strftime('%Y-%m', ecriture_date)
                    ORDER BY periode ASC
                ";
            } elseif ($periode === 'trimestre') {
                $sql = "
                    SELECT 
                        CONCAT(YEAR(ecriture_date), '-T', QUARTER(ecriture_date)) as periode,
                        COUNT(*) as nb_ecritures,
                        SUM(debit) as entrees,
                        SUM(credit) as sorties,
                        SUM(debit) - SUM(credit) as flux_net,
                        COUNT(DISTINCT compte_num) as nb_comptes,
                        COUNT(DISTINCT comp_aux_num) as nb_tiers
                    FROM ecritures
                    WHERE exercice = ?
                    GROUP BY YEAR(ecriture_date), QUARTER(ecriture_date)
                    ORDER BY YEAR(ecriture_date) DESC, QUARTER(ecriture_date) DESC
                ";
            }
            
            // Version SQLite-compatible
            if (empty($sql)) {
                $sql = "
                    SELECT 
                        strftime('%Y-%m', ecriture_date) as periode,
                        COUNT(*) as nb_ecritures,
                        SUM(debit) as entrees,
                        SUM(credit) as sorties,
                        SUM(debit) - SUM(credit) as flux_net,
                        COUNT(DISTINCT compte_num) as nb_comptes,
                        COUNT(DISTINCT comp_aux_num) as nb_tiers
                    FROM ecritures
                    WHERE exercice = ?
                    GROUP BY strftime('%Y-%m', ecriture_date)
                    ORDER BY periode ASC
                ";
            }
            
            $cashflows = $db->fetchAll($sql, [$exercice]);
            
            // Calcul des statistiques
            $totalEntrees = 0;
            $totalSorties = 0;
            $totalFluxNet = 0;
            
            foreach ($cashflows as $cf) {
                $totalEntrees += (float)$cf['entrees'];
                $totalSorties += (float)$cf['sorties'];
                $totalFluxNet += (float)$cf['flux_net'];
            }
            
            // Analyse par journal
            $journalSql = "
                SELECT 
                    journal_code,
                    SUM(debit) as entrees,
                    SUM(credit) as sorties,
                    SUM(debit) - SUM(credit) as flux_net,
                    COUNT(*) as nb_ecritures
                FROM ecritures
                WHERE exercice = ?
                GROUP BY journal_code
                ORDER BY flux_net DESC
            ";
            $parJournal = $db->fetchAll($journalSql, [$exercice]);
            
            return json_encode([
                'success' => true,
                'exercice' => (int)$exercice,
                'periode' => $periode,
                'stats_globales' => [
                    'total_entrees' => (float)$totalEntrees,
                    'total_sorties' => (float)$totalSorties,
                    'flux_net_total' => (float)$totalFluxNet,
                    'solde_theo' => (float)($totalEntrees - $totalSorties)
                ],
                'par_periode' => array_map(function($cf) {
                    return [
                        'periode' => $cf['periode'],
                        'nb_ecritures' => (int)$cf['nb_ecritures'],
                        'entrees' => (float)$cf['entrees'],
                        'sorties' => (float)$cf['sorties'],
                        'flux_net' => (float)$cf['flux_net'],
                        'nb_comptes' => (int)$cf['nb_comptes'],
                        'nb_tiers' => (int)$cf['nb_tiers']
                    ];
                }, $cashflows),
                'par_journal' => array_map(function($j) {
                    return [
                        'journal' => $j['journal_code'],
                        'entrees' => (float)$j['entrees'],
                        'sorties' => (float)$j['sorties'],
                        'flux_net' => (float)$j['flux_net'],
                        'nb_ecritures' => (int)$j['nb_ecritures']
                    ];
                }, $parJournal)
            ]);
        } catch (\Exception $e) {
            Logger::error("Erreur API cashflow", ['error' => $e->getMessage()]);
            http_response_code(500);
            return json_encode(['error' => $e->getMessage()]);
        }
    });
    
    /**
     * GET /api/cashflow/detail/:journal
     * Détail du cashflow pour un journal spécifique
     */
    $router->get('/cashflow/detail/:journal', function($journal) {
        $exercice = $_GET['exercice'] ?? (int) date('Y');
        $db = Database::getInstance();
        
        try {
            // Stats du journal
            $statsSQL = "
                SELECT 
                    journal_code,
                    SUM(debit) as total_debit,
                    SUM(credit) as total_credit,
                    SUM(debit) - SUM(credit) as solde,
                    COUNT(*) as nb_ecritures,
                    COUNT(DISTINCT ecriture_date) as nb_jours,
                    MIN(ecriture_date) as date_debut,
                    MAX(ecriture_date) as date_fin
                FROM ecritures
                WHERE exercice = ? AND journal_code = ?
                GROUP BY journal_code
            ";
            $stats = $db->fetchOne($statsSQL, [$exercice, $journal]);
            
            if (!$stats) {
                http_response_code(404);
                return json_encode(['error' => 'Journal non trouvé']);
            }
            
            // Flux par jour
            $flux = "
                SELECT 
                    ecriture_date as date,
                    COUNT(*) as nb_ecritures,
                    SUM(debit) as entrees,
                    SUM(credit) as sorties,
                    SUM(debit) - SUM(credit) as flux_net
                FROM ecritures
                WHERE exercice = ? AND journal_code = ?
                GROUP BY ecriture_date
                ORDER BY ecriture_date DESC
            ";
            $fluxJour = $db->fetchAll($flux, [$exercice, $journal]);
            
            // Top comptes
            $comptes = "
                SELECT 
                    compte_num,
                    compte_lib,
                    SUM(debit) as debit,
                    SUM(credit) as credit,
                    SUM(debit) - SUM(credit) as solde,
                    COUNT(*) as nb_ecritures
                FROM ecritures
                WHERE exercice = ? AND journal_code = ?
                GROUP BY compte_num
                ORDER BY ABS(solde) DESC
                LIMIT 20
            ";
            $topComptes = $db->fetchAll($comptes, [$exercice, $journal]);
            
            return json_encode([
                'success' => true,
                'journal' => $journal,
                'exercice' => (int)$exercice,
                'stats' => [
                    'total_debit' => (float)$stats['total_debit'],
                    'total_credit' => (float)$stats['total_credit'],
                    'solde' => (float)$stats['solde'],
                    'nb_ecritures' => (int)$stats['nb_ecritures'],
                    'nb_jours_actifs' => (int)$stats['nb_jours'],
                    'date_debut' => $stats['date_debut'],
                    'date_fin' => $stats['date_fin']
                ],
                'flux_par_jour' => array_map(function($f) {
                    return [
                        'date' => $f['date'],
                        'nb_ecritures' => (int)$f['nb_ecritures'],
                        'entrees' => (float)$f['entrees'],
                        'sorties' => (float)$f['sorties'],
                        'flux_net' => (float)$f['flux_net']
                    ];
                }, $fluxJour),
                'top_comptes' => array_map(function($c) {
                    return [
                        'compte' => $c['compte_num'],
                        'libelle' => $c['compte_lib'],
                        'debit' => (float)$c['debit'],
                        'credit' => (float)$c['credit'],
                        'solde' => (float)$c['solde'],
                        'nb_ecritures' => (int)$c['nb_ecritures']
                    ];
                }, $topComptes)
            ]);
        } catch (\Exception $e) {
            Logger::error("Erreur API cashflow détail", ['error' => $e->getMessage()]);
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
