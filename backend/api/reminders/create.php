<?php
/**
 * Create task reminder
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

if(!$data || !isset($data['task_id'], $data['remind_at'])) {
    Response::validationError([
        'task_id' => 'Task ID is required',
        'remind_at' => 'Reminder datetime is required'
    ]);
}

$database = new Database();
$db = $database->getConnection();
if(!$db) {
    Response::serverError('Database connection failed');
}

$task_query = "SELECT id FROM tasks WHERE id = :task_id AND user_id = :user_id LIMIT 1";
$task_stmt = $db->prepare($task_query);
$task_id = (int)$data['task_id'];
$task_stmt->bindParam(':task_id', $task_id, PDO::PARAM_INT);
$task_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$task_stmt->execute();

if(!$task_stmt->fetch(PDO::FETCH_ASSOC)) {
    Response::notFound('Task not found');
}

$channel = isset($data['channel']) ? $data['channel'] : 'in_app';
$query = "INSERT INTO task_reminders (task_id, user_id, remind_at, channel)
          VALUES (:task_id, :user_id, :remind_at, :channel)";
$stmt = $db->prepare($query);
$stmt->bindParam(':task_id', $task_id, PDO::PARAM_INT);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindParam(':remind_at', $data['remind_at']);
$stmt->bindParam(':channel', $channel);

if(!$stmt->execute()) {
    Response::serverError('Failed to create reminder');
}

Response::success('Reminder created', [
    'reminder_id' => (int)$db->lastInsertId()
], 201);
?>
