<?php
/**
 * Create Task Endpoint
 * POST /api/tasks/create.php
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../models/Task.php';
require_once '../../utils/response.php';
require_once '../../utils/validation.php';
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
$user_id = $user_data['user_id'];

// Get posted data
$data = json_decode(file_get_contents("php://input"), true);

if(!$data) {
    Response::error('Invalid JSON data');
}

// Validate task data
$validation_errors = Validation::validateTask($data);

if(!empty($validation_errors)) {
    Response::validationError($validation_errors);
}

// Initialize database and task model
$database = new Database();
$db = $database->getConnection();

if(!$db) {
    Response::serverError('Database connection failed');
}

$task = new Task($db);

// Set task properties
$task->user_id = $user_id;
$task->title = $data['title'];
$task->description = isset($data['description']) ? $data['description'] : '';
$task->task_type = isset($data['task_type']) ? $data['task_type'] : 'other';
$task->urgency = isset($data['urgency']) ? $data['urgency'] : 'medium';
$task->status = isset($data['status']) ? $data['status'] : 'pending';
$task->deadline = isset($data['deadline']) ? $data['deadline'] : null;
$task->estimated_duration = isset($data['estimated_duration']) ? $data['estimated_duration'] : 30;
$task->location = isset($data['location']) ? $data['location'] : '';
$task->distance = isset($data['distance']) ? $data['distance'] : 0;
$task->safety_indicator = isset($data['safety_indicator']) ? $data['safety_indicator'] : 'safe';
$task->client_name = isset($data['client_name']) ? $data['client_name'] : '';
$task->client_phone = isset($data['client_phone']) ? $data['client_phone'] : '';
$task->payment_amount = isset($data['payment_amount']) ? $data['payment_amount'] : 0;
$task->payment_status = isset($data['payment_status']) ? $data['payment_status'] : 'unpaid';
$task->notes = isset($data['notes']) ? $data['notes'] : '';
$task->device_created = isset($data['device_created']) ? $data['device_created'] : false;

// Create task
if($task->create()) {
    Response::success(MSG_TASK_CREATED, [
        'task' => [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'task_type' => $task->task_type,
            'urgency' => $task->urgency,
            'status' => $task->status,
            'priority_score' => $task->priority_score,
            'deadline' => $task->deadline,
            'estimated_duration' => $task->estimated_duration,
            'location' => $task->location,
            'distance' => $task->distance,
            'safety_indicator' => $task->safety_indicator,
            'client_name' => $task->client_name,
            'client_phone' => $task->client_phone,
            'payment_amount' => $task->payment_amount,
            'payment_status' => $task->payment_status,
            'notes' => $task->notes
        ]
    ], 201);
} else {
    Response::serverError('Failed to create task');
}
?>
