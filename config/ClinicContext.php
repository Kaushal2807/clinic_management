<?php
/**
 * Clinic Context Manager
 * Handles switching between clinic databases and managing clinic-specific operations
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../core/Session.php';

class ClinicContext {
    private static $currentDatabase = null;
    private static $clinicInfo = null;
    
    /**
     * Initialize clinic context and switch to clinic database
     */
    public static function init() {
        if (!Session::isClinic()) {
            return false;
        }
        
        $clinicId = Session::getClinicId();
        if (!$clinicId) {
            return false;
        }
        
        $db = Database::getInstance();
        
        // Get clinic information
        $stmt = $db->getConnection()->prepare(
            "SELECT id, clinic_name, database_name, logo_path, contact_email, contact_phone, address 
             FROM clinics WHERE id = ? AND is_active = 1"
        );
        $stmt->bind_param('i', $clinicId);
        $stmt->execute();
        $result = $stmt->get_result();
        $clinic = $result->fetch_assoc();
        
        if (!$clinic) {
            return false;
        }
        
        // Switch to clinic database
        $db->switchDatabase($clinic['database_name']);
        self::$currentDatabase = $clinic['database_name'];
        self::$clinicInfo = $clinic;
        
        // Store clinic info in session for easy access
        Session::set('clinic_info', $clinic);
        
        return true;
    }
    
    /**
     * Get current clinic info
     */
    public static function getClinicInfo() {
        if (self::$clinicInfo) {
            return self::$clinicInfo;
        }
        return Session::get('clinic_info');
    }
    
    /**
     * Get clinic database connection
     */
    public static function getConnection() {
        return Database::getInstance()->getConnection();
    }
    
    /**
     * Get clinic ID
     */
    public static function getClinicId() {
        $info = self::getClinicInfo();
        return $info['id'] ?? null;
    }
    
    /**
     * Get clinic name
     */
    public static function getClinicName() {
        $info = self::getClinicInfo();
        return $info['clinic_name'] ?? '';
    }
    
    /**
     * Get clinic logo path
     */
    public static function getLogoPath() {
        $info = self::getClinicInfo();
        return $info['logo_path'] ?? '';
    }
    
    /**
     * Execute query in clinic context
     */
    public static function query($sql, $params = [], $types = '') {
        $conn = self::getConnection();
        
        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            if ($types) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            return $stmt->get_result();
        }
        
        return $conn->query($sql);
    }
}
