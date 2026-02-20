<?php
/**
 * User Registration Endpoint
 * POST /api/auth/register.php
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

// Validate input
$validation_errors = Validation::validateUserRegistration($data);

if(!empty($validation_errors)) {
    Response::validationError($validation_errors);
}

// Initialize database and user model
$database = new Database();
$db = $database->getConnection();

if(!$db) {
    Response::serverError('Database connection failed');
}

$user = new User($db);

// Set user properties
$user->full_name = $data['full_name'];
$user->phone_number = Validation::normalizePhone($data['phone_number']);
$user->password = $data['password'];
$user->role = isset($data['role']) ? $data['role'] : 'user';
$user->work_type = isset($data['work_type']) ? $data['work_type'] : 'other';
$user->location = isset($data['location']) ? $data['location'] : '';
$user->device_type = isset($data['device_type']) ? $data['device_type'] : '';
$user->connectivity_profile = isset($data['connectivity_profile']) ? $data['connectivity_profile'] : 'unstable';

// Check if user already exists
if($user->phoneExists()) {
    Response::error(MSG_USER_EXISTS, 409);
}

// Create user
if($user->register()) {
    // Generate JWT token
    $token = JWT::encode([
        'user_id' => $user->id,
        'phone_number' => $user->phone_number,
        'full_name' => $user->full_name,
        'role' => $user->role
    ]);
    
    // Return success response with token
    Response::success(MSG_USER_CREATED, [
        'user' => [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'phone_number' => $user->phone_number,
            'role' => $user->role,
            'work_type' => $user->work_type,
            'location' => $user->location
        ],
        'token' => $token
    ], 201);
} else {
    Response::serverError('Failed to register user');
}
?>
