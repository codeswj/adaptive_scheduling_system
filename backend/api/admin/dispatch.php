<?php
/**
 * Admin Dispatch Board
 * GET /api/admin/dispatch.php
 * PUT /api/admin/dispatch.php { "task_id": 1, "to_user_id": 3, "reason": "Closer location" }
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/database.php';
require_once '../../utils/response.php';
require_once '../../middleware/auth.php';

if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$admin = AuthMiddleware::requireAdmin();
$admin_id = (int)$admin['user_id'];

$database = new Database();
$db = $database->getConnection();
if(!$db) {
    Response::serverError('Database connection failed');
}

if($_SERVER['REQUEST_METHOD'] === 'GET') {
    $task_query = "SELECT
                    t.id,
                    t.title,
                    t.user_id AS owner_id,
                    u.full_name AS owner_name,
                    t.status,
                    t.urgency,
                    t.deadline,
                    t.priority_score
                   FROM tasks t
                   INNER JOIN users u ON u.id = t.user_id
                   WHERE t.status IN ('pending', 'in_progress')
                   ORDER BY
                     (CASE WHEN t.deadline IS NOT NULL AND t.deadline < NOW() THEN 0 ELSE 1 END) ASC,
                     t.priority_score DESC,
                     t.deadline ASC
                   LIMIT 120";
    $task_stmt = $db->prepare($task_query);
    $task_stmt->execute();
    $tasks = $task_stmt->fetchAll(PDO::FETCH_ASSOC);

    $candidate_query = "SELECT
                          u.id,
                          u.full_name,
                          u.work_type,
                          SUM(CASE WHEN t.status IN ('pending','in_progress') THEN 1 ELSE 0 END) AS active_load
                        FROM users u
                        LEFT JOIN tasks t ON t.user_id = u.id
                        WHERE u.role = 'user' AND u.is_active = 1
                        GROUP BY u.id
                        ORDER BY active_load ASC, u.full_name ASC";
    $candidate_stmt = $db->prepare($candidate_query);
    $candidate_stmt->execute();
    $candidates = $candidate_stmt->fetchAll(PDO::FETCH_ASSOC);

    $history_query = "SELECT
                        d.id,
                        d.task_id,
                        t.title AS task_title,
                        fu.full_name AS from_user_name,
                        tu.full_name AS to_user_name,
                        au.full_name AS admin_name,
                        d.reason,
                        d.created_at
                      FROM admin_dispatch_logs d
                      INNER JOIN tasks t ON t.id = d.task_id
                      INNER JOIN users fu ON fu.id = d.from_user_id
                      INNER JOIN users tu ON tu.id = d.to_user_id
                      INNER JOIN users au ON au.id = d.admin_id
                      ORDER BY d.created_at DESC
                      LIMIT 40";
    $history_stmt = $db->prepare($history_query);
    $history_stmt->execute();
    $history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

    Response::success('Dispatch board data retrieved', [
        'tasks' => $tasks,
        'candidates' => $candidates,
        'recent_dispatches' => $history
    ]);
}

if($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if(!$data || !isset($data['task_id']) || !isset($data['to_user_id'])) {
        Response::validationError([
            'task_id' => 'task_id is required',
            'to_user_id' => 'to_user_id is required'
        ]);
    }

    $task_id = (int)$data['task_id'];
    $to_user_id = (int)$data['to_user_id'];
    $reason = isset($data['reason']) ? trim($data['reason']) : '';

    if($task_id <= 0 || $to_user_id <= 0) {
        Response::validationError(['ids' => 'task_id and to_user_id must be positive integers']);
    }

    $task_query = "SELECT id, user_id, status FROM tasks WHERE id = :task_id LIMIT 1";
    $task_stmt = $db->prepare($task_query);
    $task_stmt->bindParam(':task_id', $task_id, PDO::PARAM_INT);
    $task_stmt->execute();
    $task = $task_stmt->fetch(PDO::FETCH_ASSOC);
    if(!$task) {
        Response::notFound('Task not found');
    }
    if(!in_array($task['status'], ['pending', 'in_progress'])) {
        Response::error('Only pending or in-progress tasks can be reassigned', 400);
    }

    $from_user_id = (int)$task['user_id'];
    if($from_user_id === $to_user_id) {
        Response::error('Task already assigned to the selected user', 400);
    }

    $user_query = "SELECT id FROM users WHERE id = :id AND role = 'user' AND is_active = 1 LIMIT 1";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(':id', $to_user_id, PDO::PARAM_INT);
    $user_stmt->execute();
    if(!$user_stmt->fetch(PDO::FETCH_ASSOC)) {
        Response::error('Target user not found or inactive', 400);
    }

    try {
        $db->beginTransaction();

        $update_query = "UPDATE tasks SET user_id = :to_user_id, updated_at = CURRENT_TIMESTAMP WHERE id = :task_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':to_user_id', $to_user_id, PDO::PARAM_INT);
        $update_stmt->bindParam(':task_id', $task_id, PDO::PARAM_INT);
        $update_stmt->execute();

        $dispatch_query = "INSERT INTO admin_dispatch_logs
                           (task_id, from_user_id, to_user_id, admin_id, reason)
                           VALUES
                           (:task_id, :from_user_id, :to_user_id, :admin_id, :reason)";
        $dispatch_stmt = $db->prepare($dispatch_query);
        $dispatch_stmt->bindParam(':task_id', $task_id, PDO::PARAM_INT);
        $dispatch_stmt->bindParam(':from_user_id', $from_user_id, PDO::PARAM_INT);
        $dispatch_stmt->bindParam(':to_user_id', $to_user_id, PDO::PARAM_INT);
        $dispatch_stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $dispatch_stmt->bindParam(':reason', $reason);
        $dispatch_stmt->execute();

        $detail_json = json_encode([
            'dispatch' => [
                'from_user_id' => $from_user_id,
                'to_user_id' => $to_user_id,
                'admin_id' => $admin_id,
                'reason' => $reason
            ]
        ]);

        $log_query = "INSERT INTO task_logs
                      (task_id, user_id, action, old_status, new_status, details)
                      VALUES
                      (:task_id, :user_id, 'updated', :old_status, :new_status, :details)";
        $log_stmt = $db->prepare($log_query);
        $log_stmt->bindParam(':task_id', $task_id, PDO::PARAM_INT);
        $log_stmt->bindParam(':user_id', $to_user_id, PDO::PARAM_INT);
        $log_stmt->bindParam(':old_status', $task['status']);
        $log_stmt->bindParam(':new_status', $task['status']);
        $log_stmt->bindParam(':details', $detail_json);
        $log_stmt->execute();

        $db->commit();
        Response::success('Task reassigned successfully');
    } catch(Exception $e) {
        $db->rollBack();
        Response::serverError('Failed to reassign task');
    }
}

Response::error('Method not allowed', 405);
?>
