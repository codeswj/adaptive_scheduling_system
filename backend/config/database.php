<?php
/**
 * Database Configuration
 * Update these settings based on your XAMPP configuration
 */

class Database {
    // Database credentials
    private $host = "localhost";
    private $db_name = "adaptive_scheduling_db";
    private $username = "root";  // Default XAMPP username
    private $password = "";      // Default XAMPP password (empty)
    private $charset = "utf8mb4";
    
    public $conn;

    /**
     * Get database connection
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            return null;
        }

        return $this->conn;
    }

    /**
     * Test database connection
     * @return bool
     */
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            return $conn !== null;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
