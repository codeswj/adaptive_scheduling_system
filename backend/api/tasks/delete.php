<?php
/**
 * Delete Task Endpoint
 * DELETE /api/tasks/delete.php
 * Requires task ID in request body or query parameter
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../models/Task.php';
require_once '../../utils/response.php';
require_once '../../middleware/auth.php';

// Handle preflight
if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept DELETE requests
if($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    Response::error('Method not allowed', 405);
}

// Authenticate user
$user_data = AuthMiddleware::authenticate();
$user_id = $user_data['user_id'];

// Get task ID from query parameter or request body
$task_id = null;

if(isset($_GET['id'])) {
    $task_id = $_GET['id'];
} else {
    $data = json_decode(file_get_contents("php://input"), true);
    if($data && isset($data['id'])) {
        $task_id = $data['id'];
    }
}

if(!$task_id) {
    Response::error('Task ID is required');
}

// Initialize database and task model
$database = new Database();
$db = $database->getConnection();

if(!$db) {
    Response::serverError('Database connection failed');
}

$task = new Task($db);
$task->id = $task_id;
$task->user_id = $user_id;

// Check if task exists and belongs to user
if(!$task->getById()) {
    Response::notFound(MSG_TASK_NOT_FOUND);
}

// Delete task
if($task->delete()) {
    Response::success(MSG_TASK_DELETED, [
        'deleted_task_id' => $task_id
    ]);
} else {
    Response::serverError('Failed to delete task');
}
?>
