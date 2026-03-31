<?php
/**
 * Update reminder details
 * POST /api/reminders/update.php
 * body: { "id": 1, "remind_at": "...", "channel": "in_app", "message": "...", "status": "pending" }
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

if(!$data || empty($data['id'])) {
    Response::validationError(['id' => 'Reminder id is required']);
}

$id = (int)$data['id'];

$fields = [];
$params = [
    ':id' => $id,
    ':user_id' => $user_id
];

if(isset($data['remind_at'])) {
    $fields[] = 'remind_at = :remind_at';
    $params[':remind_at'] = $data['remind_at'];
}

if(isset($data['channel'])) {
    $fields[] = 'channel = :channel';
    $params[':channel'] = $data['channel'];
}

if(isset($data['message'])) {
    $fields[] = 'message = :message';
    $params[':message'] = $data['message'];
}

if(isset($data['status'])) {
    $fields[] = 'status = :status';
    $params[':status'] = $data['status'];
}

if(isset($data['task_title'])) {
    $fields[] = 'task_title = :task_title';
    $params[':task_title'] = $data['task_title'];
}

if(empty($fields)) {
    Response::validationError(['update' => 'No fields to update']);
}

$query = "UPDATE reminders SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP
          WHERE id = :id AND user_id = :user_id";

$database = new Database();
$db = $database->getConnection();
if(!$db) {
    Response::serverError('Database connection failed');
}

$stmt = $db->prepare($query);
foreach($params as $param => $value) {
    $stmt->bindValue($param, $value);
}

if(!$stmt->execute()) {
    Response::serverError('Failed to update reminder');
}

if($stmt->rowCount() === 0) {
    Response::notFound('Reminder not found');
}

Response::success('Reminder updated');
