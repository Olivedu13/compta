<?php
/**
 * API v1 Router - Self-Contained (No dependencies)
 * Routes les requêtes vers les endpoints appropriés
 */

header('Content-Type: application/json; charset=utf-8');

try {
    // Get DB connection
    $projectRoot = dirname(dirname(dirname(__FILE__)));
    $dbPath = $projectRoot . '/compta.db';
    
    if (!file_exists($dbPath)) {
        throw new Exception("Database not found");
    }
    
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Parse request
    $uri = $_SERVER['REQUEST_URI'] ?? $_SERVER['ORIG_REQUEST_URI'] ?? '';
    $uri = parse_url($uri, PHP_URL_PATH);
    $basePath = '/api/v1';
    
    $relativePath = str_replace($basePath, '', $uri);
    $segments = array_filter(explode('/', $relativePath));
    
    if (empty($segments)) {
        $resource = 'accounting';
        $action = 'years';
    } else {
        $resource = array_shift($segments);
        $action = array_shift($segments) ?? 'index';
    }
    
    // Route to file-based handler
    $routeFile = __DIR__ . '/' . $resource . '/' . $action . '.php';
    
    if (!file_exists($routeFile)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => "Route not found: {$resource}/{$action}"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Include route file and pass $db
    include $routeFile;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
