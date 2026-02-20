<?php
/**
 * Update Task Endpoint
 * PUT /api/tasks/update.php
 * Requires task ID in request body
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: PUT');
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

// Validate task ID
if(!isset($data['id']) || empty($data['id'])) {
    Response::error('Task ID is required');
}

// Initialize database and task model
$database = new Database();
$db = $database->getConnection();

if(!$db) {
    Response::serverError('Database connection failed');
}

$task = new Task($db);
$task->id = $data['id'];
$task->user_id = $user_id;

// Check if task exists and belongs to user
if(!$task->getById()) {
    Response::notFound(MSG_TASK_NOT_FOUND);
}

// Update only provided fields
if(isset($data['title'])) {
    $task->title = $data['title'];
}
if(isset($data['description'])) {
    $task->description = $data['description'];
}
if(isset($data['task_type'])) {
    $task->task_type = $data['task_type'];
}
if(isset($data['urgency'])) {
    $task->urgency = $data['urgency'];
}
if(isset($data['status'])) {
    $task->status = $data['status'];
    
    // Set completed_at if status is completed
    if($data['status'] === 'completed') {
        $completed_query = "UPDATE tasks SET completed_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $db->prepare($completed_query);
        $stmt->bindParam(":id", $task->id);
        $stmt->execute();
    }
}
if(isset($data['deadline'])) {
    $task->deadline = $data['deadline'];
}
if(isset($data['estimated_duration'])) {
    $task->estimated_duration = $data['estimated_duration'];
}
if(isset($data['location'])) {
    $task->location = $data['location'];
}
if(isset($data['distance'])) {
    $task->distance = $data['distance'];
}
if(isset($data['safety_indicator'])) {
    $task->safety_indicator = $data['safety_indicator'];
}
if(isset($data['client_name'])) {
    $task->client_name = $data['client_name'];
}
if(isset($data['client_phone'])) {
    $task->client_phone = $data['client_phone'];
}
if(isset($data['payment_amount'])) {
    $task->payment_amount = $data['payment_amount'];
}
if(isset($data['payment_status'])) {
    $task->payment_status = $data['payment_status'];
}
if(isset($data['notes'])) {
    $task->notes = $data['notes'];
}

// Validate updated task data
$validation_errors = Validation::validateTask((array)$task);

if(!empty($validation_errors)) {
    Response::validationError($validation_errors);
}

// Update task
if($task->update()) {
    // Get updated task
    $task->getById();
    
    Response::success(MSG_TASK_UPDATED, [
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
            'notes' => $task->notes,
            'completed_at' => $task->completed_at
        ]
    ]);
} else {
    Response::serverError('Failed to update task');
}
?>
