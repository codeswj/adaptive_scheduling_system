<?php
/**
 * User Model
 * Handles all user-related database operations
 */

class User {
    private $conn;
    private $table_name = "users";

    // User properties
    public $id;
    public $full_name;
    public $phone_number;
    public $password;
    public $role;
    public $work_type;
    public $location;
    public $device_type;
    public $connectivity_profile;
    public $created_at;
    public $updated_at;
    public $last_login;
    public $is_active;

    /**
     * Constructor
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Register new user
     */
    public function register() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET full_name=:full_name,
                      phone_number=:phone_number,
                      password=:password,
                      role=:role,
                      work_type=:work_type,
                      location=:location,
                      device_type=:device_type,
                      connectivity_profile=:connectivity_profile";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->phone_number = htmlspecialchars(strip_tags($this->phone_number));
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->work_type = htmlspecialchars(strip_tags($this->work_type));
        $this->location = htmlspecialchars(strip_tags($this->location));
        $this->device_type = htmlspecialchars(strip_tags($this->device_type));
        $this->connectivity_profile = htmlspecialchars(strip_tags($this->connectivity_profile));

        // Bind parameters
        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":phone_number", $this->phone_number);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":work_type", $this->work_type);
        $stmt->bindParam(":location", $this->location);
        $stmt->bindParam(":device_type", $this->device_type);
        $stmt->bindParam(":connectivity_profile", $this->connectivity_profile);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    /**
     * Check if phone number already exists
     */
    public function phoneExists() {
        $query = "SELECT id, full_name, password, role, work_type, location, is_active
                  FROM " . $this->table_name . "
                  WHERE phone_number = :phone_number
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":phone_number", $this->phone_number);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->id = $row['id'];
            $this->full_name = $row['full_name'];
            $this->password = $row['password'];
            $this->role = $row['role'];
            $this->work_type = $row['work_type'];
            $this->location = $row['location'];
            $this->is_active = $row['is_active'];
            
            return true;
        }

        return false;
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin() {
        $query = "UPDATE " . $this->table_name . "
                  SET last_login = CURRENT_TIMESTAMP
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }

    /**
     * Get user by ID
     */
    public function getUserById() {
        $query = "SELECT id, full_name, phone_number, role, work_type, location,
                         device_type, connectivity_profile, created_at, last_login
                  FROM " . $this->table_name . "
                  WHERE id = :id AND is_active = 1
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->full_name = $row['full_name'];
            $this->phone_number = $row['phone_number'];
            $this->role = $row['role'];
            $this->work_type = $row['work_type'];
            $this->location = $row['location'];
            $this->device_type = $row['device_type'];
            $this->connectivity_profile = $row['connectivity_profile'];
            $this->created_at = $row['created_at'];
            $this->last_login = $row['last_login'];
            
            return true;
        }

        return false;
    }

    /**
     * Update user profile
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET full_name = :full_name,
                      location = :location,
                      work_type = :work_type,
                      device_type = :device_type,
                      connectivity_profile = :connectivity_profile
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->location = htmlspecialchars(strip_tags($this->location));
        $this->work_type = htmlspecialchars(strip_tags($this->work_type));
        $this->device_type = htmlspecialchars(strip_tags($this->device_type));
        $this->connectivity_profile = htmlspecialchars(strip_tags($this->connectivity_profile));

        // Bind
        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":location", $this->location);
        $stmt->bindParam(":work_type", $this->work_type);
        $stmt->bindParam(":device_type", $this->device_type);
        $stmt->bindParam(":connectivity_profile", $this->connectivity_profile);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    /**
     * Get user statistics
     */
    public function getStatistics() {
        $query = "SELECT 
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
                    SUM(payment_amount) as total_earnings
                  FROM tasks
                  WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
