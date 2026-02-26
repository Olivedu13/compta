<?php
/**
 * api.php - Endpoint principal du frontend AuditCompta
 * 
 * Le frontend (index-B0cMD3EL.js) utilise ./api.php pour :
 *   GET  → récupérer les rapports sauvegardés + settings (clés API)
 *   POST {year, revenue, ...} → sauvegarder un rapport d'audit
 *   POST {api_keys: {gemini, copilot}} → sauvegarder les clés IA
 *   POST {auth_attempt: CODE} → vérifier le code d'accès
 * 
 * Stockage : SQLite (compta.db) — tables reports + settings
 * Sécurité : aucune clé en dur, tout est en base
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Résolution du chemin de la base SQLite ──
function findDatabase() {
    // Essai 1 : même répertoire que ce script
    $dir = __DIR__;
    if (file_exists($dir . '/compta.db')) return $dir . '/compta.db';
    
    // Essai 2 : un niveau au-dessus
    $dir = dirname(__DIR__);
    if (file_exists($dir . '/compta.db')) return $dir . '/compta.db';
    
    // Essai 3 : deux niveaux au-dessus
    $dir = dirname(dirname(__DIR__));
    if (file_exists($dir . '/compta.db')) return $dir . '/compta.db';
    
    // Essai 4 : /compta/ (structure Ionos)
    if (file_exists('/compta/compta.db')) return '/compta/compta.db';
    
    // Créer dans le même répertoire que le script
    return __DIR__ . '/compta.db';
}

function getDb() {
    $dbPath = findDatabase();
    $db = new SQLite3($dbPath);
    $db->busyTimeout(5000);
    $db->exec('PRAGMA journal_mode=WAL');
    
    // Créer les tables si elles n'existent pas
    $db->exec('CREATE TABLE IF NOT EXISTS reports (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        year INTEGER NOT NULL UNIQUE,
        data_json TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    
    $db->exec('CREATE TABLE IF NOT EXISTS settings (
        key TEXT PRIMARY KEY,
        value TEXT NOT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    
    return $db;
}

try {
    $db = getDb();
    $method = $_SERVER['REQUEST_METHOD'];
    
    // ════════════ GET : retourner settings + reports ════════════
    if ($method === 'GET') {
        // Récupérer les settings
        $settings = [];
        $result = $db->query("SELECT key, value FROM settings");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $settings[$row['key']] = $row['value'];
        }
        
        // Récupérer les rapports
        $reports = [];
        $result = $db->query("SELECT year, data_json FROM reports ORDER BY year DESC");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $reports[] = ['data_json' => $row['data_json']];
        }
        
        echo json_encode([
            'settings' => [
                'api_key_gemini' => $settings['api_key_gemini'] ?? '',
                'api_key_copilot' => $settings['api_key_copilot'] ?? ''
            ],
            'reports' => $reports
        ]);
        exit;
    }
    
    // ════════════ POST : sauvegarder données ════════════
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !is_array($input)) {
            http_response_code(400);
            echo json_encode(['error' => 'JSON invalide']);
            exit;
        }
        
    // ── Purge des données (maintenance) ──
        if (isset($input['action']) && $input['action'] === 'purge_reports') {
            $db->exec("DELETE FROM reports");
            echo json_encode(['success' => true, 'message' => 'Reports purged']);
            exit;
        }
        
        // ── Auth attempt ──
        if (isset($input['auth_attempt'])) {
            $code = strtoupper(trim($input['auth_attempt']));
            // Codes d'accès valides
            $validCodes = ['EXPERT25', 'AUDIT2024', 'COMPTA2025', 'ATCO2025'];
            if (in_array($code, $validCodes)) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(401);
                echo json_encode(['error' => 'Code invalide']);
            }
            exit;
        }
        
        // ── Sauvegarde des clés API ──
        if (isset($input['api_keys'])) {
            $keys = $input['api_keys'];
            
            if (isset($keys['gemini'])) {
                $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value, updated_at) VALUES ('api_key_gemini', :val, datetime('now'))");
                $stmt->bindValue(':val', $keys['gemini'], SQLITE3_TEXT);
                $stmt->execute();
            }
            
            if (isset($keys['copilot'])) {
                $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value, updated_at) VALUES ('api_key_copilot', :val, datetime('now'))");
                $stmt->bindValue(':val', $keys['copilot'], SQLITE3_TEXT);
                $stmt->execute();
            }
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // ── Sauvegarde d'un rapport d'audit ──
        if (isset($input['year'])) {
            $year = intval($input['year']);
            if ($year < 2000 || $year > 2100) {
                http_response_code(400);
                echo json_encode(['error' => 'Année invalide']);
                exit;
            }
            
            $dataJson = json_encode($input, JSON_UNESCAPED_UNICODE);
            
            $stmt = $db->prepare("INSERT OR REPLACE INTO reports (year, data_json, updated_at) VALUES (:year, :data, datetime('now'))");
            $stmt->bindValue(':year', $year, SQLITE3_INTEGER);
            $stmt->bindValue(':data', $dataJson, SQLITE3_TEXT);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'year' => $year]);
            exit;
        }
        
        // ── POST inconnu ──
        http_response_code(400);
        echo json_encode(['error' => 'Requête non reconnue']);
        exit;
    }
    
    // ── Autre méthode ──
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non supportée']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur', 'detail' => $e->getMessage()]);
}
