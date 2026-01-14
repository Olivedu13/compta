<?php
/**
 * Routeur léger (alternative à AltoRouter, sans dépendance externe)
 * Gère les routes de l'API REST
 */

namespace App\Config;

class Router {
    private $routes = [];
    private $method;
    private $uri;
    
    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        // Supprime le préfixe /api/ pour le matching
        $this->uri = str_replace('/api', '', $this->uri);
        if ($this->uri === '') {
            $this->uri = '/';
        }
    }
    
    /**
     * Enregistre une route GET
     */
    public function get($pattern, $callback) {
        $this->routes[] = ['method' => 'GET', 'pattern' => $pattern, 'callback' => $callback];
    }
    
    /**
     * Enregistre une route POST
     */
    public function post($pattern, $callback) {
        $this->routes[] = ['method' => 'POST', 'pattern' => $pattern, 'callback' => $callback];
    }
    
    /**
     * Enregistre une route PUT
     */
    public function put($pattern, $callback) {
        $this->routes[] = ['method' => 'PUT', 'pattern' => $pattern, 'callback' => $callback];
    }
    
    /**
     * Enregistre une route DELETE
     */
    public function delete($pattern, $callback) {
        $this->routes[] = ['method' => 'DELETE', 'pattern' => $pattern, 'callback' => $callback];
    }
    
    /**
     * Exécute la route correspondante
     */
    public function run() {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $this->method) {
                continue;
            }
            
            // Convertit le pattern en regex
            // /balance/:exercice => /balance/([0-9]+)
            $pattern = preg_replace('/:[a-zA-Z_][a-zA-Z0-9_]*/', '([a-zA-Z0-9_\-]+)', $route['pattern']);
            $pattern = '#^' . $pattern . '$#';
            
            if (preg_match($pattern, $this->uri, $matches)) {
                // Supprime la première correspondance (URI complète)
                array_shift($matches);
                
                // Appelle le callback avec les paramètres extraits
                return call_user_func_array($route['callback'], $matches);
            }
        }
        
        // Route non trouvée
        http_response_code(404);
        return json_encode(['error' => 'Route not found']);
    }
}
