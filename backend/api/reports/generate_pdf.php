<?php
/**
 * Generate PDF Report
 * GET /api/reports/generate_pdf.php?from=2026-03-01&to=2026-03-31&scope=user
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

$from = isset($_GET['from']) ? $_GET['from'] : null;
$to = isset($_GET['to']) ? $_GET['to'] : null;
$scope = isset($_GET['scope']) ? $_GET['scope'] : 'user';

if(!$from || !$to) {
    Response::validationError([
        'from' => 'From date is required',
        'to' => 'To date is required'
    ]);
}

$database = new Database();
$db = $database->getConnection();
if(!$db) {
    Response::serverError('Database connection failed');
}

try {
    // Get task data for the report
    $query = "SELECT t.*, 
                     CASE 
                        WHEN t.status = 'completed' THEN 'Completed'
                        WHEN t.status = 'in_progress' THEN 'In Progress'
                        WHEN t.status = 'pending' THEN 'Pending'
                        ELSE t.status
                     END as status_text
              FROM tasks t 
              WHERE t.user_id = :user_id 
              AND DATE(t.created_at) BETWEEN :from AND :to
              ORDER BY t.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':from', $from);
    $stmt->bindParam(':to', $to);
    $stmt->execute();
    
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate summary statistics
    $total_tasks = count($tasks);
    $completed_tasks = 0;
    $pending_tasks = 0;
    $in_progress_tasks = 0;
    $total_earned = 0;
    $total_pending = 0;
    
    foreach($tasks as $task) {
        switch($task['status']) {
            case 'completed':
                $completed_tasks++;
                $total_earned += floatval($task['payment_amount']);
                break;
            case 'pending':
                $pending_tasks++;
                $total_pending += floatval($task['payment_amount']);
                break;
            case 'in_progress':
                $in_progress_tasks++;
                $total_pending += floatval($task['payment_amount']);
                break;
        }
    }
    
    // Generate PDF content
    $pdf_content = generatePDFReport([
        'user_name' => $user_data['full_name'],
        'from_date' => $from,
        'to_date' => $to,
        'tasks' => $tasks,
        'summary' => [
            'total_tasks' => $total_tasks,
            'completed_tasks' => $completed_tasks,
            'pending_tasks' => $pending_tasks,
            'in_progress_tasks' => $in_progress_tasks,
            'total_earned' => $total_earned,
            'total_pending' => $total_pending
        ]
    ]);
    
    // Set PDF headers
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="taskflow_report_' . $from . '_to_' . $to . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    echo $pdf_content;
    exit;
    
} catch (Exception $e) {
    Response::serverError('Failed to generate PDF report: ' . $e->getMessage());
}

function generatePDFReport($data) {
    // Simple PDF generation using TCPDF or fallback to HTML
    try {
        // Try to use TCPDF if available
        if (file_exists('../../vendor/tcpdf/tcpdf.php')) {
            require_once '../../vendor/tcpdf/tcpdf.php';
            
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Set document info
            $pdf->SetCreator('TaskFlow System');
            $pdf->SetAuthor('TaskFlow');
            $pdf->SetTitle('TaskFlow Report - ' . $data['from_date'] . ' to ' . $data['to_date']);
            
            // Add a page
            $pdf->AddPage();
            
            // Set font
            $pdf->SetFont('helvetica', '', 12);
            
            // Title
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'TaskFlow Report', 0, 1, 'C');
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 8, 'Generated for: ' . $data['user_name'], 0, 1, 'C');
            $pdf->Cell(0, 8, 'Period: ' . $data['from_date'] . ' to ' . $data['to_date'], 0, 1, 'C');
            $pdf->Ln(10);
            
            // Summary
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 8, 'Summary', 0, 1);
            $pdf->SetFont('helvetica', '', 11);
            
            $summary = $data['summary'];
            $pdf->Cell(0, 6, 'Total Tasks: ' . $summary['total_tasks'], 0, 1);
            $pdf->Cell(0, 6, 'Completed Tasks: ' . $summary['completed_tasks'], 0, 1);
            $pdf->Cell(0, 6, 'Pending Tasks: ' . $summary['pending_tasks'], 0, 1);
            $pdf->Cell(0, 6, 'In Progress Tasks: ' . $summary['in_progress_tasks'], 0, 1);
            $pdf->Cell(0, 6, 'Total Earned: KSh ' . number_format($summary['total_earned'], 2), 0, 1);
            $pdf->Cell(0, 6, 'Pending Collection: KSh ' . number_format($summary['total_pending'], 2), 0, 1);
            $pdf->Ln(10);
            
            // Tasks table
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 8, 'Task Details', 0, 1);
            $pdf->SetFont('helvetica', 'B', 10);
            
            // Table header
            $pdf->Cell(60, 6, 'Title', 1, 0, 'C');
            $pdf->Cell(25, 6, 'Status', 1, 0, 'C');
            $pdf->Cell(30, 6, 'Payment', 1, 0, 'C');
            $pdf->Cell(25, 6, 'Duration', 1, 0, 'C');
            $pdf->Cell(50, 6, 'Created', 1, 1, 'C');
            
            // Table data
            $pdf->SetFont('helvetica', '', 9);
            foreach($data['tasks'] as $task) {
                $pdf->Cell(60, 6, substr($task['title'], 0, 30), 1, 0, 'L');
                $pdf->Cell(25, 6, $task['status_text'], 1, 0, 'C');
                $pdf->Cell(30, 6, 'KSh ' . number_format($task['payment_amount'], 2), 1, 0, 'R');
                $pdf->Cell(25, 6, $task['estimated_duration'] . ' mins', 1, 0, 'C');
                $pdf->Cell(50, 6, date('M j, Y', strtotime($task['created_at'])), 1, 1, 'C');
            }
            
            return $pdf->Output('', 'S');
        }
    } catch (Exception $e) {
        // Fallback to simple HTML-to-PDF conversion
    }
    
    // Fallback: Generate simple HTML report
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>TaskFlow Report</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .summary { background: #f5f5f5; padding: 15px; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>TaskFlow Report</h1>
            <p><strong>Generated for:</strong> ' . $data['user_name'] . '</p>
            <p><strong>Period:</strong> ' . $data['from_date'] . ' to ' . $data['to_date'] . '</p>
        </div>
        
        <div class="summary">
            <h2>Summary</h2>
            <p><strong>Total Tasks:</strong> ' . $data['summary']['total_tasks'] . '</p>
            <p><strong>Completed Tasks:</strong> ' . $data['summary']['completed_tasks'] . '</p>
            <p><strong>Pending Tasks:</strong> ' . $data['summary']['pending_tasks'] . '</p>
            <p><strong>In Progress Tasks:</strong> ' . $data['summary']['in_progress_tasks'] . '</p>
            <p><strong>Total Earned:</strong> KSh ' . number_format($data['summary']['total_earned'], 2) . '</p>
            <p><strong>Pending Collection:</strong> KSh ' . number_format($data['summary']['total_pending'], 2) . '</p>
        </div>
        
        <h2>Task Details</h2>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th class="text-right">Payment</th>
                    <th class="text-center">Duration</th>
                    <th class="text-center">Created</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach($data['tasks'] as $task) {
        $html .= '
                <tr>
                    <td>' . htmlspecialchars($task['title']) . '</td>
                    <td>' . htmlspecialchars($task['status_text']) . '</td>
                    <td class="text-right">KSh ' . number_format($task['payment_amount'], 2) . '</td>
                    <td class="text-center">' . $task['estimated_duration'] . ' mins</td>
                    <td class="text-center">' . date('M j, Y', strtotime($task['created_at'])) . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
    </body>
    </html>';
    
    // Convert HTML to PDF (simple approach - save as HTML file with .pdf extension)
    // In production, you'd use a proper PDF library
    return $html;
}
?>
