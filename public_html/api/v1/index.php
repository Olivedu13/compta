<?php
/**
 * API v1 Router - Point d'entrée centralisé
 * 
 * Route les requêtes vers les endpoints appropriés
 * Pattern: /api/v1/{resource}/{action}
 * 
 * Examples:
 * - GET /api/v1/accounting/years
 * - GET /api/v1/accounting/balance?exercice=2024
 * - GET /api/v1/accounting/ledger?exercice=2024
 * - POST /api/v1/accounting/import
 * - GET /api/v1/analytics/kpis?exercice=2024
 * - GET /api/v1/users/profile (requiert JWT)
 */

require_once dirname(dirname(dirname(__FILE__))) . '/backend/bootstrap.php';

use App\Config\Logger;

header('Content-Type: application/json; charset=utf-8');

try {
    // Récupère l'URI et parse la route
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $basePath = '/api/v1';
    
    // Extrait la route relative
    $relativePath = str_replace($basePath, '', $uri);
    $segments = array_filter(explode('/', $relativePath));
    
    if (empty($segments)) {
        http_response_code(400);
        throw new Exception('Missing resource');
    }
    
    $resource = array_shift($segments);
    $action = array_shift($segments) ?? 'index';
    
    // Map les ressources vers les fichiers
    $routeFile = __DIR__ . '/' . $resource . '/' . $action . '.php';
    
    // Sécurité: empêcher les path traversal
    if (!file_exists($routeFile) || !is_file($routeFile)) {
        http_response_code(404);
        throw new Exception("Route not found: {$resource}/{$action}");
    }
    
    // Charge et exécute le fichier route
    include $routeFile;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    Logger::error('API Error', ['message' => $e->getMessage()]);
}
