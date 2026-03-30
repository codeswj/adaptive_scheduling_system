<?php
/**
 * Create task reminder using task name
 * POST /api/reminders/create.php
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/database.php';
require_once '../../utils/response.php';
require_once '../../middleware/auth.php';

if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$user_data = AuthMiddleware::authenticate();
$user_id = $user_data['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if(!$data || !isset($data['task_title'], $data['remind_at'])) {
    Response::validationError([
        'task_title' => 'Task title is required',
        'remind_at' => 'Reminder datetime is required'
    ]);
}

$database = new Database();
$db = $database->getConnection();
if(!$db) {
    Response::serverError('Database connection failed');
}

// Find task by title (case-insensitive search)
$task_query = "SELECT id, title FROM tasks WHERE user_id = :user_id AND LOWER(title) = LOWER(:task_title) LIMIT 1";
$task_stmt = $db->prepare($task_query);
$task_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$task_stmt->bindParam(':task_title', $data['task_title']);
$task_stmt->execute();

$task = $task_stmt->fetch(PDO::FETCH_ASSOC);

if(!$task) {
    Response::notFound('Task with title "' . htmlspecialchars($data['task_title']) . '" not found');
}

$channel = isset($data['channel']) ? $data['channel'] : 'in_app';
$message = isset($data['message']) ? $data['message'] : "Reminder for task: " . $task['title'];

$query = "INSERT INTO reminders (task_id, user_id, task_title, remind_at, channel, message)
          VALUES (:task_id, :user_id, :task_title, :remind_at, :channel, :message)";
$stmt = $db->prepare($query);
$stmt->bindParam(':task_id', $task['id'], PDO::PARAM_INT);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindParam(':task_title', $task['title']);
$stmt->bindParam(':remind_at', $data['remind_at']);
$stmt->bindParam(':channel', $channel);
$stmt->bindParam(':message', $message);

if(!$stmt->execute()) {
    Response::serverError('Failed to create reminder');
}

Response::success('Reminder created', [
    'reminder_id' => (int)$db->lastInsertId(),
    'task_title' => $task['title'],
    'task_id' => $task['id']
], 201);
?>