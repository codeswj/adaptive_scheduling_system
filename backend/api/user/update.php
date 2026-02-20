<?php
/**
 * Update User Profile Endpoint
 * PUT /api/user/update.php
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: PUT');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../models/User.php';
require_once '../../utils/response.php';
require_once '../../utils/validation.php';
require_once '../../middleware/auth.php';

// Handle preflight
if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept PUT requests
if($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error('Method not allowed', 405);
}

// Authenticate user
$user_data = AuthMiddleware::authenticate();
$user_id = $user_data['user_id'];

// Get posted data
$data = json_decode(file_get_contents("php://input"), true);

if(!$data) {
    Response::error('Invalid JSON data');
}

// Initialize database and user model
$database = new Database();
$db = $database->getConnection();

if(!$db) {
    Response::serverError('Database connection failed');
}

$user = new User($db);
$user->id = $user_id;

// Get current user data
if(!$user->getUserById()) {
    Response::notFound('User not found');
}

// Update only provided fields
if(isset($data['full_name']) && !empty($data['full_name'])) {
    $user->full_name = $data['full_name'];
}

if(isset($data['location'])) {
    $user->location = $data['location'];
}

if(isset($data['work_type'])) {
    if(!Validation::enum($data['work_type'], WORK_TYPES)) {
        Response::error('Invalid work type');
    }
    $user->work_type = $data['work_type'];
}

if(isset($data['device_type'])) {
    $user->device_type = $data['device_type'];
}

if(isset($data['connectivity_profile'])) {
    $valid_profiles = ['2G', '3G', '4G', '5G', 'unstable'];
    if(!Validation::enum($data['connectivity_profile'], $valid_profiles)) {
        Response::error('Invalid connectivity profile');
    }
    $user->connectivity_profile = $data['connectivity_profile'];
}

// Update user
if($user->update()) {
    // Get updated user data
    $user->getUserById();
    
    Response::success('Profile updated successfully', [
        'user' => [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'phone_number' => $user->phone_number,
            'role' => $user->role,
            'work_type' => $user->work_type,
            'location' => $user->location,
            'device_type' => $user->device_type,
            'connectivity_profile' => $user->connectivity_profile
        ]
    ]);
} else {
    Response::serverError('Failed to update profile');
}
?>
