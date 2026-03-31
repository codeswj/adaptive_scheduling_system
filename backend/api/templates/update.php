<?php
/**
 * Update task template
 * POST /api/templates/update.php
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/database.php';
require_once '../../utils/response.php';
require_once '../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$user_data = AuthMiddleware::authenticate();
$user_id = $user_data['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['id'])) {
    Response::validationError(['id' => 'Template id is required']);
}

$id = (int)$data['id'];
$fields = [];
$params = [
    ':id' => $id,
    ':user_id' => $user_id
];

if (isset($data['name'])) {
    $fields[] = 'name = :name';
    $params[':name'] = $data['name'];
}

if (isset($data['title'])) {
    $fields[] = 'title = :title';
    $params[':title'] = $data['title'];
}

if (isset($data['urgency'])) {
    $fields[] = 'urgency = :urgency';
    $params[':urgency'] = $data['urgency'];
}

if (isset($data['estimated_duration'])) {
    $fields[] = 'estimated_duration = :estimated_duration';
    $params[':estimated_duration'] = (int)$data['estimated_duration'];
}

if (isset($data['task_type'])) {
    $fields[] = 'task_type = :task_type';
    $params[':task_type'] = $data['task_type'];
}

if (isset($data['payment_amount'])) {
    $fields[] = 'payment_amount = :payment_amount';
    $params[':payment_amount'] = (float)$data['payment_amount'];
}

if (isset($data['location'])) {
    $fields[] = 'location = :location';
    $params[':location'] = $data['location'];
}

if (isset($data['notes'])) {
    $fields[] = 'notes = :notes';
    $params[':notes'] = $data['notes'];
}

if (empty($fields)) {
    Response::validationError(['update' => 'No fields provided for update']);
}

$query = "UPDATE task_templates SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP
          WHERE id = :id AND user_id = :user_id";

$database = new Database();
$db = $database->getConnection();
if (!$db) {
    Response::serverError('Database connection failed');
}

$stmt = $db->prepare($query);
foreach ($params as $param => $value) {
    if ($param === ':estimated_duration') {
        $stmt->bindValue($param, $value, PDO::PARAM_INT);
    } elseif ($param === ':user_id' || $param === ':id') {
        $stmt->bindValue($param, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($param, $value);
    }
}

if (!$stmt->execute()) {
    Response::serverError('Failed to update template');
}

if ($stmt->rowCount() === 0) {
    Response::notFound('Template not found');
}

Response::success('Template updated');
*** End Patch
