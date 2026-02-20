<?php
/**
 * Application Constants
 */

// JWT Configuration
define('JWT_SECRET_KEY', 'your-secret-key-change-this-in-production-2024');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION_TIME', 86400 * 7); // 7 days in seconds

// API Configuration
define('API_VERSION', 'v1');
define('API_TIMEZONE', 'Africa/Nairobi');

// Response Messages
define('MSG_SUCCESS', 'Operation successful');
define('MSG_ERROR', 'An error occurred');
define('MSG_UNAUTHORIZED', 'Unauthorized access');
define('MSG_NOT_FOUND', 'Resource not found');
define('MSG_INVALID_INPUT', 'Invalid input data');
define('MSG_SERVER_ERROR', 'Internal server error');

// User related
define('MSG_USER_CREATED', 'User registered successfully');
define('MSG_USER_EXISTS', 'User already exists');
define('MSG_LOGIN_SUCCESS', 'Login successful');
define('MSG_LOGIN_FAILED', 'Invalid phone number or password');
define('MSG_LOGOUT_SUCCESS', 'Logout successful');

// Task related
define('MSG_TASK_CREATED', 'Task created successfully');
define('MSG_TASK_UPDATED', 'Task updated successfully');
define('MSG_TASK_DELETED', 'Task deleted successfully');
define('MSG_TASK_NOT_FOUND', 'Task not found');
define('MSG_SYNC_SUCCESS', 'Tasks synchronized successfully');

// Validation rules
define('MIN_PASSWORD_LENGTH', 6);
define('MAX_PHONE_LENGTH', 20);
define('MAX_NAME_LENGTH', 100);

// Pagination
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Priority calculation weights (can be customized per user)
define('WEIGHT_URGENCY', 0.35);
define('WEIGHT_DEADLINE', 0.30);
define('WEIGHT_DISTANCE', 0.20);
define('WEIGHT_SAFETY', 0.10);
define('WEIGHT_PREFERENCE', 0.05);

// Task types
define('TASK_TYPES', [
    'delivery',
    'pickup',
    'service',
    'purchase',
    'meeting',
    'other'
]);

// Work types
define('WORK_TYPES', [
    'boda_boda',
    'market_vendor',
    'artisan',
    'domestic_worker',
    'plumber',
    'other'
]);

// User roles
define('USER_ROLES', [
    'admin',
    'user'
]);

// Set timezone
date_default_timezone_set(API_TIMEZONE);

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS headers for API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>
