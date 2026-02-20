<?php
/**
 * Admin operational tasks monitor
 * GET /api/admin/tasks.php
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
            t.id,
            t.title,
            t.user_id AS owner_id,
            t.status,
            t.urgency,
            t.deadline,
            t.priority_score,
            t.payment_amount,
            u.full_name AS owner_name
          FROM tasks t
          INNER JOIN users u ON u.id = t.user_id
          WHERE t.status IN ('pending', 'in_progress')
          ORDER BY
            (CASE WHEN t.deadline IS NOT NULL AND t.deadline < NOW() THEN 0 ELSE 1 END) ASC,
            t.priority_score DESC,
            t.deadline ASC
          LIMIT 100";
$stmt = $db->prepare($query);
$stmt->execute();

Response::success('Operational tasks retrieved', [
    'tasks' => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
?>
