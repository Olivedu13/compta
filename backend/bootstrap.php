<?php
/**
 * bootstrap.php - Initialisation Unique
 * 
 * Centralise tous les includes/requires
 * À inclure UNE FOIS au début de chaque script
 * 
 * Usage:
 *   require_once __DIR__ . '/backend/bootstrap.php';
 *   // Toutes les classes sont disponibles
 */

// ========================================
// Configuration des Chemins
// ========================================

// Sur serveur mutualisé Ionos:
// - /public_html/ est le web root
// - /backend/ est DEHORS du web root (sécurisé)

// Déterminer APP_ROOT correctement sur Ionos
if (defined('APP_ROOT')) {
    // Déjà défini (ex: depuis public_html bootstrap)
} else {
    // Depuis backend/bootstrap.php directement
    $appRoot = dirname(__FILE__);  // /backend
    $appRoot = dirname($appRoot);   // / (racine)
    define('APP_ROOT', $appRoot);
}

define('BACKEND_ROOT', APP_ROOT . '/backend');
define('PUBLIC_ROOT', APP_ROOT . '/public_html');
define('LOGS_ROOT', BACKEND_ROOT . '/logs');

// ========================================
// Chargement des Variables d'Environnement
// ========================================

/**
 * Charge le fichier .env à la racine du projet
 * Format: KEY=value
 * Cherche dans plusieurs emplacements (dev local, Ionos, etc)
 */
function loadEnvFile() {
    $possiblePaths = [
        APP_ROOT . '/.env',              // APP_ROOT/.env
        dirname(APP_ROOT) . '/.env',     // Parent de APP_ROOT (Ionos)
        '/.env',                         // Racine (Ionos alternative)
    ];
    
    $envFile = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $envFile = $path;
            break;
        }
    }
    
    if (!$envFile) {
        trigger_error("No .env file found in: " . implode(', ', $possiblePaths), E_USER_WARNING);
        return;
    }
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignore les commentaires
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=value
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");  // Retire les quotes
            
            // Défini comme variable d'environnement
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

loadEnvFile();

// ========================================
// Vérifications Préliminaires
// ========================================

if (!is_dir(BACKEND_ROOT)) {
    die("Erreur: Répertoire backend non trouvé\n");
}

if (!is_dir(LOGS_ROOT)) {
    mkdir(LOGS_ROOT, 0755, true);
}

// ========================================
// Autoloader PSR-4 Personnalisé
// ========================================

spl_autoload_register(function($class) {
    // Namespace: App\Config\Logger
    // Fichier: backend/config/Logger.php
    
    // Supprime le namespace App\
    $class = str_replace('App\\', '', $class);
    
    // Convertit les backslashes en slashes
    $path = str_replace('\\', '/', $class);
    
    // Convertit Config, Services, Models en minuscules
    $parts = explode('/', $path);
    if (count($parts) > 0) {
        $parts[0] = strtolower($parts[0]);
    }
    $path = implode('/', $parts);
    
    // Construit le chemin complet
    $filePath = BACKEND_ROOT . '/' . $path . '.php';
    
    // Charge si le fichier existe
    if (file_exists($filePath)) {
        require_once $filePath;
    } else {
        // Erreur silencieuse en production, log en dev
        if (PHP_SAPI === 'cli') {
            error_log("Autoloader - Fichier non trouvé: " . $filePath);
        }
    }
});

// ========================================
// Initialisation des Services Globaux
// ========================================

use App\Config\Database;
use App\Config\Logger;

// Initialize Logger
try {
    Logger::init();
} catch (Exception $e) {
    die("Erreur Logger: " . $e->getMessage());
}

/**
 * Helper function to get Database instance
 * Usage: $db = getDatabase();
 */
function getDatabase() {
    return \App\Config\Database::getInstance();
}

// Initialize Database (Singleton - connexion unique)
// NOTE: Commenté pour éviter timeout - la DB se connectera quand elle sera utilisée
/*
try {
    $db = Database::getInstance();
} catch (Exception $e) {
    die("Erreur Database: " . $e->getMessage());
}
*/

// ========================================
// Configuration globale
// ========================================

// Headers de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Timezone
date_default_timezone_set('Europe/Paris');

// Locale FR
setlocale(LC_ALL, 'fr_FR.UTF-8');

// ========================================
// Alias Globaux pour Faciliter l'Accès
// ========================================

// Usage: global $db, $logger;
// Au lieu de: Database::getInstance(), Logger::...

$logger = Logger::class;

// ========================================
// Log d'initialisation
// ========================================

if (PHP_SAPI === 'cli') {
    // CLI: pour tests
    $msg = "Bootstrap: Initialisation CLI complète";
} else {
    // HTTP: API/Web
    $msg = "Bootstrap: Initialisation Web complète | {$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']}";
}

Logger::debug($msg);

// ========================================
// Gestion Erreurs Personnalisée
// ========================================

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    Logger::error("PHP Error", [
        'errno' => $errno,
        'errstr' => $errstr,
        'errfile' => $errfile,
        'errline' => $errline
    ]);
});

set_exception_handler(function($exception) {
    Logger::error("Exception", [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
    
    // Retour JSON si API
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur serveur',
        'debug' => [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]
    ]);
});

// ========================================
// Fin Bootstrap - Système Prêt à l'Emploi
// ========================================

// À partir d'ici:
// - Logger est disponible: Logger::info(), Logger::error(), etc.
// - Database est disponible: Database::getInstance()
// - Autoloader est actif: new App\Services\ImportService()
// - Erreurs sont loggées automatiquement
// - Configuration de sécurité appliquée
