<?php
/**
 * PDF Report Export
 * GET /api/reports/export.php?from=YYYY-MM-DD&to=YYYY-MM-DD&scope=user|platform
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/database.php';
require_once '../../middleware/auth.php';
require_once '../../utils/response.php';

if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

function pdf_escape($text) {
    $text = preg_replace('/[^\x20-\x7E]/', '', (string)$text);
    $text = str_replace('\\', '\\\\', $text);
    $text = str_replace('(', '\\(', $text);
    $text = str_replace(')', '\\)', $text);
    return $text;
}

function build_simple_pdf($lines) {
    $stream = "BT\n/F1 12 Tf\n";
    $y = 790;
    $max_lines = 38;
    $count = 0;

    foreach($lines as $line) {
        if($count >= $max_lines) {
            break;
        }
        $safe = pdf_escape($line);
        $stream .= "1 0 0 1 50 {$y} Tm ({$safe}) Tj\n";
        $y -= 18;
        $count++;
    }

    $stream .= "ET";

    $objects = [];
    $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
    $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $objects[] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];

    for($i = 0; $i < count($objects); $i++) {
        $offsets[] = strlen($pdf);
        $num = $i + 1;
        $pdf .= $num . " 0 obj\n" . $objects[$i] . "\nendobj\n";
    }

    $xref_pos = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }

    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . $xref_pos . "\n%%EOF";

    return $pdf;
}

$auth = AuthMiddleware::authenticate();
$user_id = (int)$auth['user_id'];
$role = isset($auth['role']) ? $auth['role'] : 'user';

$from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
$to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');
$scope = isset($_GET['scope']) ? $_GET['scope'] : 'user';

if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    Response::validationError(['date' => 'Invalid date format. Use YYYY-MM-DD']);
}

$database = new Database();
$db = $database->getConnection();
if(!$db) {
    Response::serverError('Database connection failed');
}

$lines = [];
$lines[] = 'TaskFlow Report';
$lines[] = 'Generated: ' . date('Y-m-d H:i:s');
$lines[] = 'Range: ' . $from . ' to ' . $to;

if($scope === 'platform') {
    if($role !== 'admin') {
        Response::error('Admin access required for platform report', 403);
    }

    $lines[] = 'Scope: Platform (Admin)';
    $lines[] = str_repeat('-', 64);

    $summary_query = "SELECT
                        COUNT(*) AS total_tasks,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks,
                        SUM(CASE WHEN status IN ('pending','in_progress') THEN 1 ELSE 0 END) AS open_tasks,
                        SUM(CASE WHEN status = 'completed' THEN payment_amount ELSE 0 END) AS total_earnings
                      FROM tasks
                      WHERE DATE(created_at) BETWEEN :from_date AND :to_date";
    $summary_stmt = $db->prepare($summary_query);
    $summary_stmt->bindParam(':from_date', $from);
    $summary_stmt->bindParam(':to_date', $to);
    $summary_stmt->execute();
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

    $lines[] = 'Total Tasks: ' . (int)($summary['total_tasks'] ?? 0);
    $lines[] = 'Completed Tasks: ' . (int)($summary['completed_tasks'] ?? 0);
    $lines[] = 'Open Tasks: ' . (int)($summary['open_tasks'] ?? 0);
    $lines[] = 'Total Earnings: KSh ' . number_format((float)($summary['total_earnings'] ?? 0), 2);
    $lines[] = '';
    $lines[] = 'Top Users by Earnings:';

    $top_query = "SELECT u.full_name,
                         SUM(CASE WHEN t.status = 'completed' THEN t.payment_amount ELSE 0 END) AS earnings
                  FROM users u
                  LEFT JOIN tasks t ON t.user_id = u.id
                  WHERE u.role = 'user'
                  AND DATE(t.created_at) BETWEEN :from_date AND :to_date
                  GROUP BY u.id
                  ORDER BY earnings DESC
                  LIMIT 10";
    $top_stmt = $db->prepare($top_query);
    $top_stmt->bindParam(':from_date', $from);
    $top_stmt->bindParam(':to_date', $to);
    $top_stmt->execute();
    $rows = $top_stmt->fetchAll(PDO::FETCH_ASSOC);

    if(empty($rows)) {
        $lines[] = '- No user earnings found in selected range.';
    } else {
        $rank = 1;
        foreach($rows as $row) {
            $lines[] = $rank . '. ' . $row['full_name'] . ' - KSh ' . number_format((float)$row['earnings'], 2);
            $rank++;
        }
    }

    $filename = 'taskflow_platform_report_' . date('Ymd_His') . '.pdf';
} else {
    $lines[] = 'Scope: User';
    $lines[] = str_repeat('-', 64);

    $summary_query = "SELECT
                        COUNT(*) AS total_tasks,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks,
                        SUM(CASE WHEN status IN ('pending','in_progress') THEN 1 ELSE 0 END) AS open_tasks,
                        SUM(CASE WHEN status = 'completed' THEN payment_amount ELSE 0 END) AS total_earnings
                      FROM tasks
                      WHERE user_id = :user_id
                      AND DATE(created_at) BETWEEN :from_date AND :to_date";
    $summary_stmt = $db->prepare($summary_query);
    $summary_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $summary_stmt->bindParam(':from_date', $from);
    $summary_stmt->bindParam(':to_date', $to);
    $summary_stmt->execute();
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

    $lines[] = 'Total Tasks: ' . (int)($summary['total_tasks'] ?? 0);
    $lines[] = 'Completed Tasks: ' . (int)($summary['completed_tasks'] ?? 0);
    $lines[] = 'Open Tasks: ' . (int)($summary['open_tasks'] ?? 0);
    $lines[] = 'Total Earnings: KSh ' . number_format((float)($summary['total_earnings'] ?? 0), 2);
    $lines[] = '';
    $lines[] = 'Recent Tasks:';

    $task_query = "SELECT title, status, urgency, payment_amount
                   FROM tasks
                   WHERE user_id = :user_id
                   AND DATE(created_at) BETWEEN :from_date AND :to_date
                   ORDER BY created_at DESC
                   LIMIT 12";
    $task_stmt = $db->prepare($task_query);
    $task_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $task_stmt->bindParam(':from_date', $from);
    $task_stmt->bindParam(':to_date', $to);
    $task_stmt->execute();
    $tasks = $task_stmt->fetchAll(PDO::FETCH_ASSOC);

    if(empty($tasks)) {
        $lines[] = '- No tasks found in selected range.';
    } else {
        foreach($tasks as $task) {
            $lines[] = '- ' . $task['title'] . ' | ' . $task['status'] . ' | ' . $task['urgency'] . ' | KSh ' . number_format((float)$task['payment_amount'], 2);
        }
    }

    $filename = 'taskflow_user_report_' . date('Ymd_His') . '.pdf';
}

$pdf_content = build_simple_pdf($lines);

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdf_content));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo $pdf_content;
exit();
?>
