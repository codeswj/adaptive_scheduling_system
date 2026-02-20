<?php
/**
 * Admin overview metrics
 * GET /api/admin/overview.php
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

$database = new Database();
$db = $database->getConnection();
if(!$db) {
    Response::serverError('Database connection failed');
}

$summary_query = "SELECT
                    (SELECT COUNT(*) FROM users WHERE role = 'user') AS total_users,
                    (SELECT COUNT(*) FROM users WHERE role = 'user' AND is_active = 1) AS active_users,
                    (SELECT COUNT(*) FROM tasks) AS total_tasks,
                    (SELECT COUNT(*) FROM tasks WHERE status = 'completed') AS completed_tasks,
                    (SELECT SUM(payment_amount) FROM tasks WHERE status = 'completed') AS total_earnings,
                    (SELECT COUNT(*) FROM tasks WHERE status IN ('pending','in_progress') AND deadline < NOW()) AS overdue_tasks";
$summary_stmt = $db->prepare($summary_query);
$summary_stmt->execute();
$summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

Response::success('Admin overview retrieved', [
    'overview' => [
        'total_users' => (int)($summary['total_users'] ?? 0),
        'active_users' => (int)($summary['active_users'] ?? 0),
        'total_tasks' => (int)($summary['total_tasks'] ?? 0),
        'completed_tasks' => (int)($summary['completed_tasks'] ?? 0),
        'total_earnings' => (float)($summary['total_earnings'] ?? 0),
        'overdue_tasks' => (int)($summary['overdue_tasks'] ?? 0)
    ]
]);
?>
