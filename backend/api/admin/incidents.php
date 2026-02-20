<?php
/**
 * Admin Incident Center
 * GET /api/admin/incidents.php?status=open
 * PUT /api/admin/incidents.php { "id": 1, "status": "resolved" }
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

function upsert_auto_incident($db, $type, $severity, $title, $description, $context) {
    $fingerprint = hash('sha256', $type . '|' . $title . '|' . json_encode($context));

    $check_query = "SELECT id, status FROM admin_incidents
                    WHERE fingerprint = :fingerprint
                    ORDER BY created_at DESC
                    LIMIT 1";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':fingerprint', $fingerprint);
    $check_stmt->execute();
    $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if($existing && in_array($existing['status'], ['open', 'investigating'])) {
        return;
    }

    $insert_query = "INSERT INTO admin_incidents
                     (fingerprint, type, severity, title, description, context_json, status)
                     VALUES
                     (:fingerprint, :type, :severity, :title, :description, :context_json, 'open')
                     ON DUPLICATE KEY UPDATE
                        severity = VALUES(severity),
                        title = VALUES(title),
                        description = VALUES(description),
                        context_json = VALUES(context_json),
                        status = 'open',
                        resolved_at = NULL,
                        resolved_by = NULL";
    $insert_stmt = $db->prepare($insert_query);
    $context_json = json_encode($context);

    $insert_stmt->bindParam(':fingerprint', $fingerprint);
    $insert_stmt->bindParam(':type', $type);
    $insert_stmt->bindParam(':severity', $severity);
    $insert_stmt->bindParam(':title', $title);
    $insert_stmt->bindParam(':description', $description);
    $insert_stmt->bindParam(':context_json', $context_json);
    $insert_stmt->execute();
}

function run_incident_detection($db) {
    // 1) Sync failures/conflicts in 24h
    $sync_query = "SELECT COUNT(*) AS cnt
                   FROM sync_queue
                   WHERE status IN ('failed', 'conflict')
                   AND server_timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $sync_stmt = $db->prepare($sync_query);
    $sync_stmt->execute();
    $sync_cnt = (int)$sync_stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    if($sync_cnt >= 3) {
        upsert_auto_incident(
            $db,
            'sync_failure',
            $sync_cnt >= 10 ? 'high' : 'medium',
            'Sync failures detected',
            "There are {$sync_cnt} failed/conflict sync events in the last 24 hours.",
            ['count_24h' => $sync_cnt]
        );
    }

    // 2) Overdue spike
    $overdue_query = "SELECT COUNT(*) AS cnt
                      FROM tasks
                      WHERE status IN ('pending', 'in_progress')
                      AND deadline IS NOT NULL
                      AND deadline < NOW()";
    $overdue_stmt = $db->prepare($overdue_query);
    $overdue_stmt->execute();
    $overdue_cnt = (int)$overdue_stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    if($overdue_cnt >= 5) {
        upsert_auto_incident(
            $db,
            'overdue_spike',
            $overdue_cnt >= 15 ? 'critical' : 'high',
            'Overdue task spike',
            "There are currently {$overdue_cnt} overdue operational tasks.",
            ['current_overdue' => $overdue_cnt]
        );
    }

    // 3) Failed login risk (1h)
    $login_query = "SELECT COUNT(*) AS cnt
                    FROM login_logs
                    WHERE login_status = 'failed'
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $login_stmt = $db->prepare($login_query);
    $login_stmt->execute();
    $login_cnt = (int)$login_stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    if($login_cnt >= 5) {
        upsert_auto_incident(
            $db,
            'login_risk',
            $login_cnt >= 20 ? 'critical' : 'high',
            'High failed login activity',
            "There are {$login_cnt} failed login attempts in the last 1 hour.",
            ['failed_logins_1h' => $login_cnt]
        );
    }

    // 4) Reminder backlog
    $reminder_query = "SELECT COUNT(*) AS cnt
                       FROM task_reminders
                       WHERE status = 'pending'
                       AND remind_at < NOW()";
    $reminder_stmt = $db->prepare($reminder_query);
    $reminder_stmt->execute();
    $reminder_cnt = (int)$reminder_stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    if($reminder_cnt >= 5) {
        upsert_auto_incident(
            $db,
            'reminder_backlog',
            $reminder_cnt >= 15 ? 'high' : 'medium',
            'Reminder backlog detected',
            "There are {$reminder_cnt} pending reminders past their trigger time.",
            ['overdue_reminders' => $reminder_cnt]
        );
    }
}

$admin = AuthMiddleware::requireAdmin();
$admin_id = (int)$admin['user_id'];

$database = new Database();
$db = $database->getConnection();
if(!$db) {
    Response::serverError('Database connection failed');
}

if($_SERVER['REQUEST_METHOD'] === 'GET') {
    run_incident_detection($db);

    $status = isset($_GET['status']) ? trim($_GET['status']) : 'all';
    $allowed_status = ['all', 'open', 'investigating', 'resolved', 'dismissed'];
    if(!in_array($status, $allowed_status)) {
        Response::validationError(['status' => 'Invalid status filter']);
    }

    $query = "SELECT
                i.id,
                i.type,
                i.severity,
                i.title,
                i.description,
                i.context_json,
                i.status,
                i.created_at,
                i.updated_at,
                i.resolved_at,
                u.full_name AS resolved_by_name
              FROM admin_incidents i
              LEFT JOIN users u ON u.id = i.resolved_by";

    if($status !== 'all') {
        $query .= " WHERE i.status = :status";
    }

    $query .= " ORDER BY
                FIELD(i.severity, 'critical','high','medium','low'),
                i.created_at DESC
                LIMIT 150";

    $stmt = $db->prepare($query);
    if($status !== 'all') {
        $stmt->bindParam(':status', $status);
    }
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summary_query = "SELECT
                        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open_count,
                        SUM(CASE WHEN status = 'investigating' THEN 1 ELSE 0 END) AS investigating_count,
                        SUM(CASE WHEN severity = 'critical' AND status IN ('open', 'investigating') THEN 1 ELSE 0 END) AS critical_open_count
                      FROM admin_incidents";
    $summary_stmt = $db->prepare($summary_query);
    $summary_stmt->execute();
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

    Response::success('Incidents retrieved', [
        'summary' => [
            'open_count' => (int)($summary['open_count'] ?? 0),
            'investigating_count' => (int)($summary['investigating_count'] ?? 0),
            'critical_open_count' => (int)($summary['critical_open_count'] ?? 0)
        ],
        'incidents' => $items
    ]);
}

if($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if(!$data || !isset($data['id']) || !isset($data['status'])) {
        Response::validationError([
            'id' => 'Incident id is required',
            'status' => 'status is required'
        ]);
    }

    $incident_id = (int)$data['id'];
    $new_status = trim($data['status']);
    $allowed_next = ['open', 'investigating', 'resolved', 'dismissed'];
    if(!in_array($new_status, $allowed_next)) {
        Response::validationError(['status' => 'Invalid status']);
    }

    $query = "UPDATE admin_incidents
              SET status = :status,
                  updated_at = CURRENT_TIMESTAMP,
                  resolved_at = CASE WHEN :status_resolved IN ('resolved','dismissed') THEN CURRENT_TIMESTAMP ELSE NULL END,
                  resolved_by = CASE WHEN :status_resolved IN ('resolved','dismissed') THEN :resolved_by ELSE NULL END
              WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $new_status);
    $stmt->bindParam(':status_resolved', $new_status);
    $stmt->bindParam(':resolved_by', $admin_id, PDO::PARAM_INT);
    $stmt->bindParam(':id', $incident_id, PDO::PARAM_INT);
    $stmt->execute();

    if($stmt->rowCount() === 0) {
        Response::notFound('Incident not found or unchanged');
    }

    Response::success('Incident status updated');
}

Response::error('Method not allowed', 405);
?>
