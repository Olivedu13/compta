<?php
namespace App\Config;

use Exception;

/**
 * JWT Token Management
 * 
 * Simple JWT implementation without external dependencies
 * Compatible with server constraints
 */
class JwtManager
{
    private static $secret = null;
    private static $algorithm = 'HS256';
    
    /**
     * Initialize with secret from environment
     */
    public static function init($secret = null)
    {
        self::$secret = $secret ?? getenv('JWT_SECRET');
        
        if (!self::$secret) {
            throw new Exception('JWT_SECRET not configured in environment');
        }
    }
    
    /**
     * Create JWT token
     * 
     * @param array $payload Token data
     * @param int $expiresIn Expiration time in seconds (default 24h)
     * @return string JWT token
     */
    public static function createToken(array $payload, $expiresIn = 86400)
    {
        self::init();
        
        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + $expiresIn;
        
        $header = json_encode(['alg' => self::$algorithm, 'typ' => 'JWT']);
        $headerEncoded = self::base64UrlEncode($header);
        
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            self::$secret,
            true
        );
        $signatureEncoded = self::base64UrlEncode($signature);
        
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }
    
    /**
     * Verify and decode JWT token
     * 
     * @param string $token JWT token
     * @return object Decoded payload
     * @throws Exception If token invalid or expired
     */
    public static function verifyToken($token)
    {
        self::init();
        
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }
        
        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;
        
        // Verify signature
        $expectedSignature = hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            self::$secret,
            true
        );
        $expectedSignatureEncoded = self::base64UrlEncode($expectedSignature);
        
        if (!hash_equals($signatureEncoded, $expectedSignatureEncoded)) {
            throw new Exception('Invalid token signature');
        }
        
        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded));
        
        // Check expiration
        if (isset($payload->exp) && $payload->exp < time()) {
            throw new Exception('Token expired');
        }
        
        return $payload;
    }
    
    /**
     * Get token from Authorization header
     * 
     * @return string|null Token or null if not found
     */
    public static function getTokenFromHeader()
    {
        $headers = getallheaders();
        $authorization = $headers['Authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.+)/i', $authorization, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Base64 URL encode
     */
    private static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 4 - strlen($data) % 4));
    }
}
