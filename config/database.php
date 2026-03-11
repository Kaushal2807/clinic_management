<?php
/**
 * Database Configuration and Connection Manager
 * Singleton pattern for database connection management
 * Single Database Architecture: clinic_management
 */

class Database {
    private static $instance = null;
    private $conn;
    private $host = 'localhost';
    private $user = 'root';
    private $pass = '';
    private $database = 'clinic_management';
    
    private function __construct() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->database);
        
        if ($this->conn->connect_error) {
            error_log("Database Connection Error: " . $this->conn->connect_error);
            die("Connection failed. Please contact administrator.");
        }
        
        $this->conn->set_charset("utf8mb4");
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get database connection
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Execute prepared statement query
     */
    public function query($sql, $params = [], $types = '') {
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            throw new Exception("Query preparation failed");
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            throw new Exception("Query execution failed");
        }
        
        return $stmt;
    }
    
    /**
     * Legacy methods kept for backward compatibility (no-op)
     * These do nothing now since we use a single database
     */
    public function switchToClinicData() {
        // No-op: kept for backward compatibility
    }
    
    public function switchToMaster() {
        // No-op: kept for backward compatibility
    }
    
    public function switchDatabase($dbName) {
        // No-op: kept for backward compatibility
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
