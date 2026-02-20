<?php
/**
 * Read/Get Tasks Endpoint
 * GET /api/tasks/read.php
 * Query parameters:
 *   - status: Filter by status (pending, in_progress, completed, cancelled)
 *   - limit: Number of tasks to return (default: 50, max: 100)
 *   - offset: Offset for pagination (default: 0)
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../models/Task.php';
require_once '../../utils/response.php';
require_once '../../middleware/auth.php';

// Handle preflight
if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept GET requests
if($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

// Authenticate user
$user_data = AuthMiddleware::authenticate();
$user_id = $user_data['user_id'];

// Initialize database and task model
$database = new Database();
$db = $database->getConnection();

if(!$db) {
    Response::serverError('Database connection failed');
}

$task = new Task($db);
$task->user_id = $user_id;

// Get query parameters
$status = isset($_GET['status']) ? $_GET['status'] : null;
$limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Validate status if provided
if($status !== null && !in_array($status, ['pending', 'in_progress', 'completed', 'cancelled'])) {
    Response::error('Invalid status filter');
}

// Get tasks
$tasks = $task->getUserTasks($limit, $offset, $status);

// Count total tasks for pagination info
$count_query = "SELECT COUNT(*) as total FROM tasks WHERE user_id = :user_id";
if($status !== null) {
    $count_query .= " AND status = :status";
}

$count_stmt = $db->prepare($count_query);
$count_stmt->bindParam(":user_id", $user_id);
if($status !== null) {
    $count_stmt->bindParam(":status", $status);
}
$count_stmt->execute();
$total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Return tasks
Response::success('Tasks retrieved successfully', [
    'tasks' => $tasks,
    'pagination' => [
        'total' => (int)$total,
        'limit' => $limit,
        'offset' => $offset,
        'returned' => count($tasks)
    ]
]);
?>
