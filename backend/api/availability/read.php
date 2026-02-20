<?php
/**
 * Read weekly availability
 * GET /api/availability/read.php
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

$query = "SELECT day_of_week, start_time, end_time, is_active
          FROM user_availability
          WHERE user_id = :user_id
          ORDER BY day_of_week ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();

Response::success('Availability retrieved', [
    'availability' => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
?>
