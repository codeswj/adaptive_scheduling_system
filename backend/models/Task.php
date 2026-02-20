<?php
/**
 * Task Model
 * Handles all task-related database operations
 */

class Task {
    private $conn;
    private $table_name = "tasks";

    // Task properties
    public $id;
    public $user_id;
    public $title;
    public $description;
    public $task_type;
    public $priority_score;
    public $urgency;
    public $status;
    public $deadline;
    public $estimated_duration;
    public $location;
    public $distance;
    public $safety_indicator;
    public $client_name;
    public $client_phone;
    public $payment_amount;
    public $payment_status;
    public $notes;
    public $created_at;
    public $updated_at;
    public $completed_at;
    public $synced_at;
    public $device_created;

    /**
     * Constructor
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create new task
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id=:user_id,
                      title=:title,
                      description=:description,
                      task_type=:task_type,
                      urgency=:urgency,
                      status=:status,
                      deadline=:deadline,
                      estimated_duration=:estimated_duration,
                      location=:location,
                      distance=:distance,
                      safety_indicator=:safety_indicator,
                      client_name=:client_name,
                      client_phone=:client_phone,
                      payment_amount=:payment_amount,
                      payment_status=:payment_status,
                      notes=:notes,
                      device_created=:device_created";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->task_type = htmlspecialchars(strip_tags($this->task_type));
        $this->urgency = htmlspecialchars(strip_tags($this->urgency));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->location = htmlspecialchars(strip_tags($this->location));
        $this->safety_indicator = htmlspecialchars(strip_tags($this->safety_indicator));
        $this->client_name = htmlspecialchars(strip_tags($this->client_name));
        $this->client_phone = htmlspecialchars(strip_tags($this->client_phone));
        $this->notes = htmlspecialchars(strip_tags($this->notes));

        // Bind parameters
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":task_type", $this->task_type);
        $stmt->bindParam(":urgency", $this->urgency);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":deadline", $this->deadline);
        $stmt->bindParam(":estimated_duration", $this->estimated_duration);
        $stmt->bindParam(":location", $this->location);
        $stmt->bindParam(":distance", $this->distance);
        $stmt->bindParam(":safety_indicator", $this->safety_indicator);
        $stmt->bindParam(":client_name", $this->client_name);
        $stmt->bindParam(":client_phone", $this->client_phone);
        $stmt->bindParam(":payment_amount", $this->payment_amount);
        $stmt->bindParam(":payment_status", $this->payment_status);
        $stmt->bindParam(":notes", $this->notes);
        $stmt->bindParam(":device_created", $this->device_created);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            
            // Calculate priority score
            $this->calculatePriorityScore();
            
            // Log task creation
            $this->logAction('created');
            
            return true;
        }

        return false;
    }

    /**
     * Get all tasks for a user
     */
    public function getUserTasks($limit = 50, $offset = 0, $status = null) {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE user_id = :user_id";
        
        if($status !== null) {
            $query .= " AND status = :status";
        }
        
        $query .= " ORDER BY priority_score DESC, deadline ASC
                   LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id, PDO::PARAM_INT);
        
        if($status !== null) {
            $stmt->bindParam(":status", $status);
        }
        
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get task by ID
     */
    public function getById() {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE id = :id AND user_id = :user_id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Set properties
            foreach($row as $key => $value) {
                if(property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
            
            return true;
        }

        return false;
    }

    /**
     * Update task
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET title = :title,
                      description = :description,
                      task_type = :task_type,
                      urgency = :urgency,
                      status = :status,
                      deadline = :deadline,
                      estimated_duration = :estimated_duration,
                      location = :location,
                      distance = :distance,
                      safety_indicator = :safety_indicator,
                      client_name = :client_name,
                      client_phone = :client_phone,
                      payment_amount = :payment_amount,
                      payment_status = :payment_status,
                      notes = :notes
                  WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->task_type = htmlspecialchars(strip_tags($this->task_type));
        $this->urgency = htmlspecialchars(strip_tags($this->urgency));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->location = htmlspecialchars(strip_tags($this->location));
        $this->safety_indicator = htmlspecialchars(strip_tags($this->safety_indicator));
        $this->client_name = htmlspecialchars(strip_tags($this->client_name));
        $this->client_phone = htmlspecialchars(strip_tags($this->client_phone));
        $this->notes = htmlspecialchars(strip_tags($this->notes));

        // Bind
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":task_type", $this->task_type);
        $stmt->bindParam(":urgency", $this->urgency);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":deadline", $this->deadline);
        $stmt->bindParam(":estimated_duration", $this->estimated_duration);
        $stmt->bindParam(":location", $this->location);
        $stmt->bindParam(":distance", $this->distance);
        $stmt->bindParam(":safety_indicator", $this->safety_indicator);
        $stmt->bindParam(":client_name", $this->client_name);
        $stmt->bindParam(":client_phone", $this->client_phone);
        $stmt->bindParam(":payment_amount", $this->payment_amount);
        $stmt->bindParam(":payment_status", $this->payment_status);
        $stmt->bindParam(":notes", $this->notes);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":user_id", $this->user_id);

        if($stmt->execute()) {
            // Recalculate priority
            $this->calculatePriorityScore();
            
            // Log update
            $this->logAction('updated');
            
            return true;
        }

        return false;
    }

    /**
     * Delete task
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":user_id", $this->user_id);

        return $stmt->execute();
    }

    /**
     * Calculate priority score based on adaptive algorithm
     */
    private function calculatePriorityScore() {
        $score = 0;
        
        // Urgency weight
        $urgency_scores = [
            'low' => 1,
            'medium' => 2,
            'high' => 3,
            'critical' => 4
        ];
        $score += (isset($urgency_scores[$this->urgency]) ? $urgency_scores[$this->urgency] : 2) * WEIGHT_URGENCY * 25;
        
        // Deadline weight (closer deadline = higher score)
        if($this->deadline) {
            $deadline_timestamp = strtotime($this->deadline);
            $current_timestamp = time();
            $hours_until_deadline = ($deadline_timestamp - $current_timestamp) / 3600;
            
            if($hours_until_deadline < 2) {
                $score += WEIGHT_DEADLINE * 100;
            } elseif($hours_until_deadline < 6) {
                $score += WEIGHT_DEADLINE * 75;
            } elseif($hours_until_deadline < 24) {
                $score += WEIGHT_DEADLINE * 50;
            } else {
                $score += WEIGHT_DEADLINE * 25;
            }
        }
        
        // Distance weight (closer = higher score, but inverted)
        if($this->distance > 0) {
            if($this->distance < 2) {
                $score += WEIGHT_DISTANCE * 100;
            } elseif($this->distance < 5) {
                $score += WEIGHT_DISTANCE * 75;
            } elseif($this->distance < 10) {
                $score += WEIGHT_DISTANCE * 50;
            } else {
                $score += WEIGHT_DISTANCE * 25;
            }
        }
        
        // Safety weight
        $safety_scores = [
            'safe' => 100,
            'caution' => 60,
            'unsafe' => 20
        ];
        $score += (isset($safety_scores[$this->safety_indicator]) ? $safety_scores[$this->safety_indicator] : 60) * WEIGHT_SAFETY;
        
        // Update the score
        $query = "UPDATE " . $this->table_name . "
                  SET priority_score = :score
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":score", $score);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        $this->priority_score = $score;
    }

    /**
     * Log task action
     */
    private function logAction($action) {
        $query = "INSERT INTO task_logs 
                  SET task_id = :task_id,
                      user_id = :user_id,
                      action = :action,
                      new_status = :status";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":task_id", $this->id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":action", $action);
        $stmt->bindParam(":status", $this->status);
        $stmt->execute();
    }

    /**
     * Batch sync tasks from offline queue
     */
    public function batchSync($tasks_array) {
        $synced = 0;
        $failed = 0;
        
        foreach($tasks_array as $task_data) {
            // Set properties from array
            foreach($task_data as $key => $value) {
                if(property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
            
            // Check if task exists (update) or create new
            if(isset($task_data['id']) && $this->getById()) {
                if($this->update()) {
                    $synced++;
                } else {
                    $failed++;
                }
            } else {
                $this->device_created = true;
                if($this->create()) {
                    $synced++;
                } else {
                    $failed++;
                }
            }
        }
        
        return [
            'synced' => $synced,
            'failed' => $failed,
            'total' => count($tasks_array)
        ];
    }
}
?>
