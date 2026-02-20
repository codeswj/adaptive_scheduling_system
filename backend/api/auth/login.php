<?php
/**
 * User Login Endpoint
 * POST /api/auth/login.php
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../models/User.php';
require_once '../../utils/response.php';
require_once '../../utils/validation.php';
require_once '../../utils/jwt.php';

// Handle preflight
if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept POST requests
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Get posted data
$data = json_decode(file_get_contents("php://input"), true);

if(!$data) {
    Response::error('Invalid JSON data');
}

// Validate required fields
$required_errors = Validation::required($data, ['phone_number', 'password']);

if(!empty($required_errors)) {
    Response::validationError($required_errors);
}

// Initialize database and user model
$database = new Database();
$db = $database->getConnection();

if(!$db) {
    Response::serverError('Database connection failed');
}

$user = new User($db);

// Normalize phone number
$user->phone_number = Validation::normalizePhone($data['phone_number']);

// Check if user exists
if(!$user->phoneExists()) {
    Response::error(MSG_LOGIN_FAILED, 401);
}

// Check if user is active
if(!$user->is_active) {
    Response::error('Account is inactive. Please contact support.', 403);
}

// Verify password
if(!password_verify($data['password'], $user->password)) {
    Response::error(MSG_LOGIN_FAILED, 401);
}

// Update last login
$user->updateLastLogin();

// Generate JWT token
$token = JWT::encode([
    'user_id' => $user->id,
    'phone_number' => $user->phone_number,
    'full_name' => $user->full_name,
    'role' => $user->role
]);

// Get user statistics
$stats = $user->getStatistics();

// Return success response
Response::success(MSG_LOGIN_SUCCESS, [
    'user' => [
        'id' => $user->id,
        'full_name' => $user->full_name,
        'phone_number' => $user->phone_number,
        'role' => $user->role,
        'work_type' => $user->work_type,
        'location' => $user->location
    ],
    'token' => $token,
    'statistics' => $stats
]);
?>
