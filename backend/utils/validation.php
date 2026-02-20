<?php
/**
 * Validation Helper Functions
 * Input validation and sanitization
 */

class Validation {
    
    /**
     * Validate required fields
     */
    public static function required($data, $fields) {
        $errors = [];
        
        foreach($fields as $field) {
            if(!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate phone number (Kenyan format)
     */
    public static function phone($phone) {
        // Remove spaces and special characters
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Check if it's a valid Kenyan phone number
        // Formats: +254..., 254..., 07..., 01...
        $pattern = '/^(\+?254|0)[17]\d{8}$/';
        
        return preg_match($pattern, $phone) === 1;
    }
    
    /**
     * Validate password strength
     */
    public static function password($password) {
        if(strlen($password) < MIN_PASSWORD_LENGTH) {
            return 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters long';
        }
        
        return true;
    }
    
    /**
     * Validate email
     */
    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate enum value
     */
    public static function enum($value, $allowed_values) {
        return in_array($value, $allowed_values);
    }
    
    /**
     * Validate date format
     */
    public static function date($date, $format = 'Y-m-d H:i:s') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Validate numeric value
     */
    public static function numeric($value, $min = null, $max = null) {
        if(!is_numeric($value)) {
            return false;
        }
        
        if($min !== null && $value < $min) {
            return false;
        }
        
        if($max !== null && $value > $max) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitize string
     */
    public static function sanitizeString($string) {
        return htmlspecialchars(strip_tags(trim($string)));
    }
    
    /**
     * Normalize Kenyan phone number to international format
     */
    public static function normalizePhone($phone) {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Convert to international format
        if(substr($phone, 0, 1) === '0') {
            $phone = '+254' . substr($phone, 1);
        } elseif(substr($phone, 0, 3) === '254') {
            $phone = '+' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Validate task data
     */
    public static function validateTask($data) {
        $errors = [];
        
        // Required fields
        if(empty($data['title'])) {
            $errors['title'] = 'Task title is required';
        }
        
        // Validate task type
        if(isset($data['task_type']) && !self::enum($data['task_type'], TASK_TYPES)) {
            $errors['task_type'] = 'Invalid task type';
        }
        
        // Validate urgency
        $valid_urgency = ['low', 'medium', 'high', 'critical'];
        if(isset($data['urgency']) && !self::enum($data['urgency'], $valid_urgency)) {
            $errors['urgency'] = 'Invalid urgency level';
        }
        
        // Validate status
        $valid_status = ['pending', 'in_progress', 'completed', 'cancelled'];
        if(isset($data['status']) && !self::enum($data['status'], $valid_status)) {
            $errors['status'] = 'Invalid status';
        }
        
        // Validate deadline format if provided
        if(isset($data['deadline']) && !empty($data['deadline'])) {
            if(!self::date($data['deadline'], 'Y-m-d H:i:s') && 
               !self::date($data['deadline'], 'Y-m-d')) {
                $errors['deadline'] = 'Invalid deadline format. Use Y-m-d H:i:s or Y-m-d';
            }
        }
        
        // Validate numeric fields
        if(isset($data['estimated_duration']) && !self::numeric($data['estimated_duration'], 0)) {
            $errors['estimated_duration'] = 'Estimated duration must be a positive number';
        }
        
        if(isset($data['distance']) && !self::numeric($data['distance'], 0)) {
            $errors['distance'] = 'Distance must be a positive number';
        }
        
        if(isset($data['payment_amount']) && !self::numeric($data['payment_amount'], 0)) {
            $errors['payment_amount'] = 'Payment amount must be a positive number';
        }
        
        return $errors;
    }
    
    /**
     * Validate user registration data
     */
    public static function validateUserRegistration($data) {
        $errors = [];
        
        // Required fields
        $required = self::required($data, ['full_name', 'phone_number', 'password']);
        $errors = array_merge($errors, $required);
        
        // Validate phone number
        if(isset($data['phone_number']) && !self::phone($data['phone_number'])) {
            $errors['phone_number'] = 'Invalid phone number format';
        }
        
        // Validate password
        if(isset($data['password'])) {
            $password_check = self::password($data['password']);
            if($password_check !== true) {
                $errors['password'] = $password_check;
            }
        }
        
        // Validate work type if provided
        if(isset($data['work_type']) && !self::enum($data['work_type'], WORK_TYPES)) {
            $errors['work_type'] = 'Invalid work type';
        }

        // Validate role if provided
        if(isset($data['role']) && !self::enum($data['role'], USER_ROLES)) {
            $errors['role'] = 'Invalid role';
        }

        return $errors;
    }
}
?>
