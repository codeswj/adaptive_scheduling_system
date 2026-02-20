<?php
/**
 * Response Helper Functions
 * Standardized API response formatting
 */

class Response {
    
    /**
     * Send success response
     */
    public static function success($message = MSG_SUCCESS, $data = null, $code = 200) {
        http_response_code($code);
        
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    }
    
    /**
     * Send error response
     */
    public static function error($message = MSG_ERROR, $code = 400, $errors = null) {
        http_response_code($code);
        
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if($errors !== null) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    }
    
    /**
     * Send unauthorized response
     */
    public static function unauthorized($message = MSG_UNAUTHORIZED) {
        self::error($message, 401);
    }
    
    /**
     * Send not found response
     */
    public static function notFound($message = MSG_NOT_FOUND) {
        self::error($message, 404);
    }
    
    /**
     * Send validation error response
     */
    public static function validationError($errors, $message = MSG_INVALID_INPUT) {
        self::error($message, 422, $errors);
    }
    
    /**
     * Send server error response
     */
    public static function serverError($message = MSG_SERVER_ERROR) {
        self::error($message, 500);
    }
    
    /**
     * Send custom response
     */
    public static function custom($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit();
    }
}
?>
