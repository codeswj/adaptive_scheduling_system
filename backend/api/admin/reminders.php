<?php
/**
 * Admin reminders oversight
 * GET /api/admin/reminders.php?window_hours=24
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

AuthMiddleware::requireAdmin();

$window_hours = isset($_GET['window_hours']) ? (int)$_GET['window_hours'] : 24;
if($window_hours < 1) $window_hours = 1;
if($window_hours > 168) $window_hours = 168;

$database = new Database();
$db = $database->getConnection();
if(!$db) {
    Response::serverError('Database connection failed');
}

$query = "SELECT
            r.id,
            r.task_id,
            r.remind_at,
            r.channel,
            r.status,
            t.title AS task_title,
            u.full_name AS user_name
          FROM task_reminders r
          INNER JOIN tasks t ON t.id = r.task_id
          INNER JOIN users u ON u.id = r.user_id
          WHERE r.status = 'pending'
          AND r.remind_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL :window_hours HOUR)
          ORDER BY r.remind_at ASC
          LIMIT 100";
$stmt = $db->prepare($query);
$stmt->bindParam(':window_hours', $window_hours, PDO::PARAM_INT);
$stmt->execute();

Response::success('Reminder queue retrieved', [
    'window_hours' => $window_hours,
    'reminders' => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
?>
