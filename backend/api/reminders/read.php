<?php
/**
 * Read reminders
 * GET /api/reminders/read.php?status=pending
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
$status = isset($_GET['status']) ? $_GET['status'] : 'pending';

$database = new Database();
$db = $database->getConnection();
if(!$db) {
    Response::serverError('Database connection failed');
}

$query = "SELECT r.id, r.task_id, t.title AS task_title, r.remind_at, r.channel, r.status
          FROM task_reminders r
          INNER JOIN tasks t ON t.id = r.task_id
          WHERE r.user_id = :user_id AND r.status = :status
          ORDER BY r.remind_at ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindParam(':status', $status);
$stmt->execute();

Response::success('Reminders retrieved', [
    'reminders' => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
?>
