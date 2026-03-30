<?php
/**
 * Read reminders with task names
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

$query = "SELECT r.*, t.title as current_task_title, t.status as task_status
          FROM reminders r
          LEFT JOIN tasks t ON r.task_id = t.id
          WHERE r.user_id = :user_id";

if($status !== 'all') {
    $query .= " AND r.status = :status";
}

$query .= " ORDER BY r.remind_at ASC";

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

if($status !== 'all') {
    $stmt->bindParam(':status', $status);
}

$stmt->execute();
$reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format reminders for frontend
$formatted_reminders = [];
foreach($reminders as $reminder) {
    $formatted_reminders[] = [
        'id' => (int)$reminder['id'],
        'task_title' => $reminder['task_title'] ?: $reminder['current_task_title'] ?: 'Unknown Task',
        'task_id' => $reminder['task_id'],
        'remind_at' => $reminder['remind_at'],
        'channel' => $reminder['channel'],
        'message' => $reminder['message'],
        'status' => $reminder['status'],
        'task_status' => $reminder['task_status'],
        'created_at' => $reminder['created_at']
    ];
}

Response::success('Reminders retrieved', [
    'reminders' => $formatted_reminders
]);
?>