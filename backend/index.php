<?php
/**
 * Adaptive Micro-Scheduling System API
 * Main Entry Point
 */

require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'utils/response.php';

// Test database connection
$database = new Database();
$db = $database->getConnection();

if(!$db) {
    Response::serverError('Database connection failed. Please check your configuration.');
}

// API information
$api_info = [
    'name' => 'Adaptive Micro-Scheduling System API',
    'version' => API_VERSION,
    'status' => 'online',
    'timestamp' => date('Y-m-d H:i:s'),
    'timezone' => API_TIMEZONE,
    'database' => 'connected',
    'endpoints' => [
        'auth' => [
            'register' => 'POST /api/auth/register.php',
            'login' => 'POST /api/auth/login.php',
            'logout' => 'POST /api/auth/logout.php'
        ],
        'tasks' => [
            'create' => 'POST /api/tasks/create.php',
            'read' => 'GET /api/tasks/read.php',
            'update' => 'PUT /api/tasks/update.php',
            'delete' => 'DELETE /api/tasks/delete.php',
            'sync' => 'POST /api/tasks/sync.php'
        ],
        'user' => [
            'profile' => 'GET /api/user/profile.php',
            'update' => 'PUT /api/user/update.php'
        ]
    ],
    'documentation' => 'See README.md for detailed API documentation'
];

Response::success('API is running', $api_info);
?>
