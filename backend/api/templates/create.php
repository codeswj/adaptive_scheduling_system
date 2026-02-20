<?php
/**
 * Create task template
 * POST /api/templates/create.php
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

if(!$data || empty($data['name']) || empty($data['title'])) {
    Response::validationError([
        'name' => 'Template name is required',
        'title' => 'Template title is required'
    ]);
}

$database = new Database();
$db = $database->getConnection();
if(!$db) {
    Response::serverError('Database connection failed');
}

$query = "INSERT INTO task_templates
          (user_id, name, title, task_type, urgency, estimated_duration, payment_amount, location, notes)
          VALUES
          (:user_id, :name, :title, :task_type, :urgency, :estimated_duration, :payment_amount, :location, :notes)";
$stmt = $db->prepare($query);

$task_type = isset($data['task_type']) ? $data['task_type'] : 'other';
$urgency = isset($data['urgency']) ? $data['urgency'] : 'medium';
$estimated_duration = isset($data['estimated_duration']) ? (int)$data['estimated_duration'] : 30;
$payment_amount = isset($data['payment_amount']) ? (float)$data['payment_amount'] : 0;
$location = isset($data['location']) ? $data['location'] : '';
$notes = isset($data['notes']) ? $data['notes'] : '';

$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindParam(':name', $data['name']);
$stmt->bindParam(':title', $data['title']);
$stmt->bindParam(':task_type', $task_type);
$stmt->bindParam(':urgency', $urgency);
$stmt->bindParam(':estimated_duration', $estimated_duration, PDO::PARAM_INT);
$stmt->bindParam(':payment_amount', $payment_amount);
$stmt->bindParam(':location', $location);
$stmt->bindParam(':notes', $notes);

if(!$stmt->execute()) {
    Response::serverError('Failed to create template');
}

Response::success('Template created', [
    'template_id' => (int)$db->lastInsertId()
], 201);
?>
