<?php
/**
 * Database Configuration and Connection Manager
 * Singleton pattern for database connection management
 */

class Database {
    private static $instance = null;
    private $conn;
    private $host = 'localhost';
    private $user = 'root';
    private $pass = '';
    private $masterDb = 'clinic_management_master';
    
    private function __construct() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->masterDb);
        
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
     * Switch to specific database
     */
    public function switchDatabase($dbName) {
        if (!$this->conn->select_db($dbName)) {
            throw new Exception("Cannot switch to database: $dbName");
        }
    }
    
    /**
     * Switch back to master database
     */
    public function switchToMaster() {
        $this->conn->select_db($this->masterDb);
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
