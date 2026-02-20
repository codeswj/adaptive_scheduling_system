<?php
/**
 * Read task templates
 * GET /api/templates/read.php
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/database.php';
require_once '../../utils/response.php';
require_once '../../middleware/auth.php';

if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

$user_data = AuthMiddleware::authenticate();
$user_id = $user_data['user_id'];

$database = new Database();
$db = $database->getConnection();
if(!$db) {
    Response::serverError('Database connection failed');
}

$query = "SELECT id, name, title, task_type, urgency, estimated_duration, payment_amount, location, notes, created_at
          FROM task_templates
          WHERE user_id = :user_id
          ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();

Response::success('Templates retrieved', [
    'templates' => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
?>
