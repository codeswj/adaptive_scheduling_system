<?php
/**
 * Get User Profile Endpoint
 * GET /api/user/profile.php
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../models/User.php';
require_once '../../utils/response.php';
require_once '../../middleware/auth.php';

// Handle preflight
if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept GET requests
if($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

// Authenticate user
$user_data = AuthMiddleware::authenticate();
$user_id = $user_data['user_id'];

// Initialize database and user model
$database = new Database();
$db = $database->getConnection();

if(!$db) {
    Response::serverError('Database connection failed');
}

$user = new User($db);
$user->id = $user_id;

// Get user details
if(!$user->getUserById()) {
    Response::notFound('User not found');
}

// Get user statistics
$stats = $user->getStatistics();

// Return user profile
Response::success('Profile retrieved successfully', [
    'user' => [
        'id' => $user->id,
        'full_name' => $user->full_name,
        'phone_number' => $user->phone_number,
        'role' => $user->role,
        'work_type' => $user->work_type,
        'location' => $user->location,
        'device_type' => $user->device_type,
        'connectivity_profile' => $user->connectivity_profile,
        'created_at' => $user->created_at,
        'last_login' => $user->last_login
    ],
    'statistics' => $stats
]);
?>
