<?php
/**
 * Admin leaderboard
 * GET /api/admin/leaderboard.php
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

$query = "SELECT
            u.id,
            u.full_name,
            u.work_type,
            SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks,
            SUM(CASE WHEN t.status = 'completed' THEN t.payment_amount ELSE 0 END) AS earnings,
            AVG(CASE WHEN t.status = 'completed' THEN NULLIF(t.estimated_duration,0) ELSE NULL END) AS avg_duration
          FROM users u
          LEFT JOIN tasks t ON t.user_id = u.id
          WHERE u.role = 'user'
          GROUP BY u.id
          ORDER BY earnings DESC, completed_tasks DESC
          LIMIT 20";
$stmt = $db->prepare($query);
$stmt->execute();

Response::success('Leaderboard retrieved', [
    'leaderboard' => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
?>
