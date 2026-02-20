<?php
/**
 * Admin users management
 * GET /api/admin/users.php
 * PUT /api/admin/users.php { "id": 5, "is_active": true }
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, PUT');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/database.php';
require_once '../../utils/response.php';
require_once '../../middleware/auth.php';

if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

AuthMiddleware::requireAdmin();

$database = new Database();
$db = $database->getConnection();
if(!$db) {
    Response::serverError('Database connection failed');
}

if($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : 'all';
    $work_type = isset($_GET['work_type']) ? trim($_GET['work_type']) : '';

    $query = "SELECT
                u.id,
                u.full_name,
                u.phone_number,
                u.work_type,
                u.is_active,
                u.created_at,
                COUNT(t.id) AS tasks_count,
                SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) AS completed_count
              FROM users u
              LEFT JOIN tasks t ON t.user_id = u.id
              WHERE u.role = 'user'";

    if($search !== '') {
        $query .= " AND (u.full_name LIKE :search OR u.phone_number LIKE :search)";
    }

    if($status === 'active') {
        $query .= " AND u.is_active = 1";
    } elseif($status === 'inactive') {
        $query .= " AND u.is_active = 0";
    }

    if($work_type !== '') {
        $query .= " AND u.work_type = :work_type";
    }

    $query .= "
              GROUP BY u.id
              ORDER BY u.created_at DESC";

    $stmt = $db->prepare($query);

    if($search !== '') {
        $search_like = '%' . $search . '%';
        $stmt->bindParam(':search', $search_like);
    }
    if($work_type !== '') {
        $stmt->bindParam(':work_type', $work_type);
    }

    $stmt->execute();

    Response::success('Users retrieved', [
        'users' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
}

if($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if(!$data || !isset($data['id']) || !isset($data['is_active'])) {
        Response::validationError([
            'id' => 'User ID is required',
            'is_active' => 'is_active is required'
        ]);
    }

    $user_id = (int)$data['id'];
    $is_active = $data['is_active'] ? 1 : 0;

    $query = "UPDATE users SET is_active = :is_active WHERE id = :id AND role = 'user'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':is_active', $is_active, PDO::PARAM_INT);
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    if($stmt->rowCount() === 0) {
        Response::notFound('User not found or unchanged');
    }

    Response::success('User status updated');
}

Response::error('Method not allowed', 405);
?>
