<?php
/**
 * Upsert weekly availability
 * POST /api/availability/upsert.php
 * body: { "availability": [{ "day_of_week": 1, "start_time":"08:00", "end_time":"18:00", "is_active": true }] }
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/database.php';
require_once '../../utils/response.php';
require_once '../../middleware/auth.php';

if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$user_data = AuthMiddleware::authenticate();
$user_id = $user_data['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if(!$data || !isset($data['availability']) || !is_array($data['availability'])) {
    Response::validationError(['availability' => 'Availability array is required']);
}

$database = new Database();
$db = $database->getConnection();
if(!$db) {
    Response::serverError('Database connection failed');
}

try {
    $db->beginTransaction();

    $query = "INSERT INTO user_availability (user_id, day_of_week, start_time, end_time, is_active)
              VALUES (:user_id, :day_of_week, :start_time, :end_time, :is_active)
              ON DUPLICATE KEY UPDATE
                start_time = VALUES(start_time),
                end_time = VALUES(end_time),
                is_active = VALUES(is_active)";
    $stmt = $db->prepare($query);

    foreach($data['availability'] as $row) {
        if(!isset($row['day_of_week'], $row['start_time'], $row['end_time'])) {
            continue;
        }

        $day_of_week = (int)$row['day_of_week'];
        if($day_of_week < 0 || $day_of_week > 6) {
            continue;
        }

        $is_active = isset($row['is_active']) && $row['is_active'] ? 1 : 0;

        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':day_of_week', $day_of_week, PDO::PARAM_INT);
        $stmt->bindParam(':start_time', $row['start_time']);
        $stmt->bindParam(':end_time', $row['end_time']);
        $stmt->bindParam(':is_active', $is_active, PDO::PARAM_INT);
        $stmt->execute();
    }

    $db->commit();
    Response::success('Availability saved');
} catch(Exception $e) {
    $db->rollBack();
    Response::serverError('Failed to save availability');
}
?>
