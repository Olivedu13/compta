<?php
namespace App\Config;

use Exception;

/**
 * Authentication Middleware
 * 
 * Valide le JWT token dans Authorization header
 * À inclure au début de chaque endpoint protégé
 */
class AuthMiddleware
{
    /**
     * Verify JWT token and return user payload
     * 
     * @return object User payload from token
     * @throws Exception If token invalid or missing
     */
    public static function requireAuth()
    {
        try {
            // Get token from Authorization header
            $token = JwtManager::getTokenFromHeader();
            
            if (!$token) {
                http_response_code(401);
                throw new Exception('No authentication token provided');
            }
            
            // Verify and decode token
            $payload = JwtManager::verifyToken($token);
            
            return $payload;
            
        } catch (Exception $e) {
            http_response_code(401);
            Logger::warning("Authentication failed", ['error' => $e->getMessage()]);
            throw new Exception('Unauthorized: ' . $e->getMessage());
        }
    }
    
    /**
     * Verify user has required role
     * 
     * @param object $user User payload
     * @param array|string $requiredRoles Role(s) required
     * @throws Exception If user doesn't have required role
     */
    public static function requireRole($user, $requiredRoles)
    {
        $roles = is_array($requiredRoles) ? $requiredRoles : [$requiredRoles];
        
        if (!isset($user->role) || !in_array($user->role, $roles)) {
            http_response_code(403);
            throw new Exception('Forbidden: insufficient permissions');
        }
    }
}
