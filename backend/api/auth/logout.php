<?php
/**
 * User Logout Endpoint
 * POST /api/auth/logout.php
 * 
 * Note: With JWT, logout is handled client-side by removing the token
 * This endpoint is optional and can be used for logging or token blacklisting
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/constants.php';
require_once '../../utils/response.php';
require_once '../../middleware/auth.php';

// Handle preflight
if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept POST requests
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Authenticate user
$user_data = AuthMiddleware::authenticate();

// In a production system, you might:
// 1. Add the token to a blacklist table
// 2. Log the logout event
// 3. Clear any server-side sessions

// For now, just return success
// The client should delete the token from local storage
Response::success(MSG_LOGOUT_SUCCESS, [
    'user_id' => $user_data['user_id']
]);
?>
