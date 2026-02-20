<?php
/**
 * Batch Sync Tasks Endpoint
 * POST /api/tasks/sync.php
 * 
 * Syncs multiple tasks from offline queue
 * Request body should contain an array of tasks
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../models/Task.php';
require_once '../../utils/response.php';
require_once '../../middleware/auth.php';

// Handle preflight
if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept POST requests
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Authenticate user
$user_data = AuthMiddleware::authenticate();
$user_id = $user_data['user_id'];

// Get posted data
$data = json_decode(file_get_contents("php://input"), true);

if(!$data) {
    Response::error('Invalid JSON data');
}

// Validate that tasks array exists
if(!isset($data['tasks']) || !is_array($data['tasks'])) {
    Response::error('Tasks array is required');
}

$tasks_to_sync = $data['tasks'];

if(empty($tasks_to_sync)) {
    Response::error('No tasks to sync');
}

// Initialize database and task model
$database = new Database();
$db = $database->getConnection();

if(!$db) {
    Response::serverError('Database connection failed');
}

$task = new Task($db);
$task->user_id = $user_id;

// Start transaction for batch processing
try {
    $db->beginTransaction();
    
    $results = [
        'created' => 0,
        'updated' => 0,
        'failed' => 0,
        'errors' => []
    ];
    
    foreach($tasks_to_sync as $index => $task_data) {
        try {
            // Ensure user_id matches authenticated user
            $task_data['user_id'] = $user_id;
            
            // Check if this is an update or create
            $is_update = false;
            
            if(isset($task_data['id']) && !empty($task_data['id'])) {
                // Try to get existing task
                $task->id = $task_data['id'];
                if($task->getById()) {
                    $is_update = true;
                }
            }
            
            // Set task properties
            foreach($task_data as $key => $value) {
                if(property_exists($task, $key)) {
                    $task->$key = $value;
                }
            }
            
            // Set defaults if not provided
            if(!isset($task_data['task_type'])) {
                $task->task_type = 'other';
            }
            if(!isset($task_data['urgency'])) {
                $task->urgency = 'medium';
            }
            if(!isset($task_data['status'])) {
                $task->status = 'pending';
            }
            if(!isset($task_data['safety_indicator'])) {
                $task->safety_indicator = 'safe';
            }
            if(!isset($task_data['estimated_duration'])) {
                $task->estimated_duration = 30;
            }
            
            // Mark as device created if new
            if(!$is_update) {
                $task->device_created = true;
            }
            
            // Perform update or create
            if($is_update) {
                if($task->update()) {
                    $results['updated']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'index' => $index,
                        'task_id' => $task->id,
                        'error' => 'Failed to update task'
                    ];
                }
            } else {
                if($task->create()) {
                    $results['created']++;
                    
                    // Update the task_data with the new ID for response
                    $tasks_to_sync[$index]['id'] = $task->id;
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'index' => $index,
                        'error' => 'Failed to create task'
                    ];
                }
            }
            
            // Update sync timestamp
            $sync_query = "UPDATE tasks SET synced_at = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $db->prepare($sync_query);
            $stmt->bindParam(":id", $task->id);
            $stmt->execute();
            
        } catch(Exception $e) {
            $results['failed']++;
            $results['errors'][] = [
                'index' => $index,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Commit transaction
    $db->commit();
    
    // Return results
    Response::success(MSG_SYNC_SUCCESS, [
        'summary' => [
            'total' => count($tasks_to_sync),
            'created' => $results['created'],
            'updated' => $results['updated'],
            'failed' => $results['failed']
        ],
        'tasks' => $tasks_to_sync,
        'errors' => $results['errors']
    ]);
    
} catch(Exception $e) {
    // Rollback on error
    $db->rollBack();
    Response::serverError('Sync failed: ' . $e->getMessage());
}
?>
