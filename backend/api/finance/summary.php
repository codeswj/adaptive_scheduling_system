<?php
/**
 * Finance summary
 * GET /api/finance/summary.php?from=YYYY-MM-DD&to=YYYY-MM-DD
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
$from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
$to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');

$database = new Database();
$db = $database->getConnection();
if(!$db) {
    Response::serverError('Database connection failed');
}

$summary_query = "SELECT
                    COUNT(*) AS total_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks,
                    SUM(CASE WHEN status = 'completed' THEN payment_amount ELSE 0 END) AS earned_amount,
                    SUM(CASE WHEN payment_status = 'unpaid' AND status = 'completed' THEN payment_amount ELSE 0 END) AS pending_collection
                  FROM tasks
                  WHERE user_id = :user_id
                  AND DATE(created_at) BETWEEN :from_date AND :to_date";
$summary_stmt = $db->prepare($summary_query);
$summary_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$summary_stmt->bindParam(':from_date', $from);
$summary_stmt->bindParam(':to_date', $to);
$summary_stmt->execute();
$summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

$daily_query = "SELECT DATE(completed_at) AS day, SUM(payment_amount) AS earnings
                FROM tasks
                WHERE user_id = :user_id
                AND status = 'completed'
                AND completed_at IS NOT NULL
                AND DATE(completed_at) BETWEEN :from_date AND :to_date
                GROUP BY DATE(completed_at)
                ORDER BY day ASC";
$daily_stmt = $db->prepare($daily_query);
$daily_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$daily_stmt->bindParam(':from_date', $from);
$daily_stmt->bindParam(':to_date', $to);
$daily_stmt->execute();

Response::success('Finance summary retrieved', [
    'range' => ['from' => $from, 'to' => $to],
    'summary' => [
        'total_tasks' => (int)($summary['total_tasks'] ?? 0),
        'completed_tasks' => (int)($summary['completed_tasks'] ?? 0),
        'earned_amount' => (float)($summary['earned_amount'] ?? 0),
        'pending_collection' => (float)($summary['pending_collection'] ?? 0)
    ],
    'daily_earnings' => $daily_stmt->fetchAll(PDO::FETCH_ASSOC)
]);
?>
