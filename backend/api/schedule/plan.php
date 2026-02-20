<?php
/**
 * Daily Schedule Planner
 * GET /api/schedule/plan.php?date=YYYY-MM-DD
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
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    Response::validationError(['date' => 'Invalid date format. Use YYYY-MM-DD']);
}

$database = new Database();
$db = $database->getConnection();
if(!$db) {
    Response::serverError('Database connection failed');
}

$day_of_week = date('w', strtotime($date));

$availability_query = "SELECT start_time, end_time FROM user_availability
                       WHERE user_id = :user_id AND day_of_week = :day_of_week AND is_active = 1
                       LIMIT 1";
$availability_stmt = $db->prepare($availability_query);
$availability_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$availability_stmt->bindParam(':day_of_week', $day_of_week, PDO::PARAM_INT);
$availability_stmt->execute();
$availability = $availability_stmt->fetch(PDO::FETCH_ASSOC);

$start_time = $availability ? $availability['start_time'] : '08:00:00';
$end_time = $availability ? $availability['end_time'] : '18:00:00';

$tasks_query = "SELECT id, title, urgency, estimated_duration, deadline, priority_score, status, location
                FROM tasks
                WHERE user_id = :user_id
                AND status IN ('pending', 'in_progress')
                ORDER BY priority_score DESC, deadline ASC
                LIMIT 30";
$tasks_stmt = $db->prepare($tasks_query);
$tasks_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$tasks_stmt->execute();
$tasks = $tasks_stmt->fetchAll(PDO::FETCH_ASSOC);

$slots = [];
$schedule_cursor = strtotime($date . ' ' . $start_time);
$schedule_end = strtotime($date . ' ' . $end_time);
$total_minutes = 0;

foreach($tasks as $task) {
    $duration = max(10, (int)($task['estimated_duration'] ?? 30));
    $slot_start = $schedule_cursor;
    $slot_end = $slot_start + ($duration * 60);

    if($slot_end > $schedule_end) {
        break;
    }

    $slots[] = [
        'task_id' => (int)$task['id'],
        'title' => $task['title'],
        'status' => $task['status'],
        'urgency' => $task['urgency'],
        'priority_score' => (float)$task['priority_score'],
        'location' => $task['location'],
        'deadline' => $task['deadline'],
        'start_at' => date('Y-m-d H:i:s', $slot_start),
        'end_at' => date('Y-m-d H:i:s', $slot_end),
        'duration_minutes' => $duration
    ];

    $schedule_cursor = $slot_end + (10 * 60);
    $total_minutes += $duration;
}

Response::success('Daily schedule generated', [
    'date' => $date,
    'availability' => [
        'start_time' => $start_time,
        'end_time' => $end_time
    ],
    'summary' => [
        'scheduled_tasks' => count($slots),
        'scheduled_minutes' => $total_minutes
    ],
    'slots' => $slots
]);
?>
