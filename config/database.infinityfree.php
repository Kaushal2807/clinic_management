<?php
/**
 * Database Configuration for InfinityFree Hosting
 * INSTRUCTIONS:
 * 1. Rename this file to database.php after uploading to InfinityFree
 * 2. Replace the values below with your actual InfinityFree database credentials
 * 3. Keep the original database.php as database.local.php for local development
 */

class Database {
    private static $instance = null;
    private $conn;
    
    // InfinityFree Database Settings - UPDATE THESE!
    private $host = 'sqlXXX.infinityfreeapp.com';  // Change to your MySQL hostname
    private $user = 'epiz_XXXXX';                  // Change to your database username
    private $pass = 'YOUR_PASSWORD_HERE';          // Change to your database password
    private $masterDb = 'epiz_XXXXX_clinicdb';     // Change to your database name
    
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
            return false;
        }
        
        if (!empty($params)) {
            if (empty($types)) {
                $types = str_repeat('s', count($params));
            }
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt;
    }
    
    /**
     * Switch to a different database
     */
    public function switchDatabase($dbName) {
        if (!$this->conn->select_db($dbName)) {
            error_log("Database switch failed: " . $this->conn->error);
            return false;
        }
        return true;
    }
    
    /**
     * Switch back to master database
     */
    public function switchToMaster() {
        return $this->switchDatabase($this->masterDb);
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
