<?php
/**
 * Authentication Middleware
 * Verifies JWT tokens and authenticates requests
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../utils/jwt.php';
require_once __DIR__ . '/../utils/response.php';

class AuthMiddleware {
    
    /**
     * Verify JWT token and return user data
     */
    public static function authenticate() {
        // Get token from header
        $jwt = JWT::getBearerToken();
        
        if(!$jwt) {
            Response::unauthorized('No authentication token provided');
        }
        
        // Decode and verify token
        $decoded = JWT::decode($jwt);
        
        if(!$decoded) {
            Response::unauthorized('Invalid or expired token');
        }
        
        // Check if user_id exists in token
        if(!isset($decoded['user_id'])) {
            Response::unauthorized('Invalid token payload');
        }
        
        return $decoded;
    }
    
    /**
     * Get authenticated user ID
     */
    public static function getUserId() {
        $user_data = self::authenticate();
        return $user_data['user_id'];
    }
    
    /**
     * Optional authentication - returns user data if authenticated, null otherwise
     */
    public static function optionalAuth() {
        $jwt = JWT::getBearerToken();
        
        if(!$jwt) {
            return null;
        }
        
        $decoded = JWT::decode($jwt);
        
        if(!$decoded || !isset($decoded['user_id'])) {
            return null;
        }
        
        return $decoded;
    }

    /**
     * Require an admin role from authenticated token
     */
    public static function requireAdmin() {
        $user_data = self::authenticate();

        if(!isset($user_data['role']) || $user_data['role'] !== 'admin') {
            Response::error('Admin access required', 403);
        }

        return $user_data;
    }
}
?>
