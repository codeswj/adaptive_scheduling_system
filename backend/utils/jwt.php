<?php
/**
 * JWT Helper Functions
 * Simple JWT implementation for authentication
 */

class JWT {
    
    /**
     * Generate JWT token
     */
    public static function encode($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => JWT_ALGORITHM]);
        
        // Add issued at and expiration time
        $payload['iat'] = time();
        $payload['exp'] = time() + JWT_EXPIRATION_TIME;
        
        $payload_encoded = json_encode($payload);
        
        // Encode Header and Payload
        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode($payload_encoded);
        
        // Create Signature
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET_KEY, true);
        $base64UrlSignature = self::base64UrlEncode($signature);
        
        // Create JWT
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
        
        return $jwt;
    }
    
    /**
     * Decode and verify JWT token
     */
    public static function decode($jwt) {
        if(empty($jwt)) {
            return false;
        }
        
        // Split the JWT
        $tokenParts = explode('.', $jwt);
        
        if(count($tokenParts) !== 3) {
            return false;
        }
        
        $header = base64_decode($tokenParts[0]);
        $payload = base64_decode($tokenParts[1]);
        $signatureProvided = $tokenParts[2];
        
        // Verify signature
        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode($payload);
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET_KEY, true);
        $base64UrlSignature = self::base64UrlEncode($signature);
        
        if($base64UrlSignature !== $signatureProvided) {
            return false;
        }
        
        // Decode payload
        $payloadArray = json_decode($payload, true);
        
        // Check expiration
        if(isset($payloadArray['exp']) && $payloadArray['exp'] < time()) {
            return false;
        }
        
        return $payloadArray;
    }
    
    /**
     * Get JWT from Authorization header
     */
    public static function getBearerToken() {
        $headers = self::getAuthorizationHeader();
        
        if(!empty($headers)) {
            if(preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Get Authorization header
     */
    private static function getAuthorizationHeader() {
        $headers = null;
        
        if(isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if(isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif(function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)), 
                array_values($requestHeaders)
            );
            
            if(isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        
        return $headers;
    }
    
    /**
     * Base64 URL encode
     */
    private static function base64UrlEncode($text) {
        return str_replace(
            ['+', '/', '='],
            ['-', '_', ''],
            base64_encode($text)
        );
    }
}
?>
