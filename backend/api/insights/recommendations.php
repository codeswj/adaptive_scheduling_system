<?php
/**
 * Productivity insights and recommendations
 * GET /api/insights/recommendations.php
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

$stats_query = "SELECT
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks,
                SUM(CASE WHEN status IN ('pending','in_progress') THEN 1 ELSE 0 END) AS open_tasks,
                AVG(NULLIF(estimated_duration, 0)) AS avg_duration,
                SUM(CASE WHEN status = 'completed' THEN payment_amount ELSE 0 END) AS total_earnings
                FROM tasks
                WHERE user_id = :user_id";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

$completed = (int)($stats['completed_tasks'] ?? 0);
$open = (int)($stats['open_tasks'] ?? 0);
$avg_duration = (float)($stats['avg_duration'] ?? 0);
$earnings = (float)($stats['total_earnings'] ?? 0);

$recommendations = [];

if($open > 10) {
    $recommendations[] = 'Backlog is high. Prioritize top 5 tasks by urgency and deadline every morning.';
}
if($avg_duration > 90) {
    $recommendations[] = 'Average task duration is long. Break large tasks into 30-45 minute chunks.';
}
if($completed > 0 && $earnings / $completed < 500) {
    $recommendations[] = 'Average earnings per completed task are low. Prioritize higher-paying tasks when possible.';
}
if(empty($recommendations)) {
    $recommendations[] = 'Current flow looks healthy. Keep using daily planning and reminders for consistency.';
}

Response::success('Insights generated', [
    'metrics' => [
        'completed_tasks' => $completed,
        'open_tasks' => $open,
        'average_duration_minutes' => round($avg_duration, 1),
        'total_earnings' => $earnings
    ],
    'recommendations' => $recommendations
]);
?>
