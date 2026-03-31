<?php
/**
 * Delete a saved availability day
 * POST /api/availability/delete.php
 * body: { "day_of_week": 2 }
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

if(!$data || !isset($data['day_of_week'])) {
    Response::validationError(['day_of_week' => 'Day of week is required']);
}

$day = (int)$data['day_of_week'];
if($day < 0 || $day > 6) {
    Response::validationError(['day_of_week' => 'Day of week must be between 0 and 6']);
}

$database = new Database();
$db = $database->getConnection();
if(!$db) {
    Response::serverError('Database connection failed');
}

$query = "DELETE FROM user_availability
          WHERE user_id = :user_id
          AND day_of_week = :day_of_week";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindParam(':day_of_week', $day, PDO::PARAM_INT);
$stmt->execute();

if($stmt->rowCount() === 0) {
    Response::notFound('Availability day not found');
}

Response::success('Availability removed');
