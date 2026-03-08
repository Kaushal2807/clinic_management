<?php
/**
 * Authentication Class
 * Handles user authentication and authorization
 */

require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

class Auth {
    
    /**
     * Hash password using bcrypt
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verify password against hash
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Login user with email and password
     */
    public static function login($email, $password) {
        $db = Database::getInstance();
        
        try {
            $stmt = $db->query(
                "SELECT id, email, password, user_type, clinic_id, full_name, is_active 
                 FROM users 
                 WHERE email = ?",
                [$email],
                's'
            );
            
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            $user = $result->fetch_assoc();
            
            // Check if user is active
            if (!$user['is_active']) {
                return ['success' => false, 'message' => 'Account is deactivated. Contact administrator.'];
            }
            
            // Verify password
            if (!self::verifyPassword($password, $user['password'])) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Set session data
            Session::init();
            Session::set('user_id', $user['id']);
            Session::set('email', $user['email']);
            Session::set('full_name', $user['full_name']);
            Session::set('user_type', $user['user_type']);
            Session::set('clinic_id', $user['clinic_id']);
            Session::set('logged_in', true);
            
            // Update last login
            $db->query(
                "UPDATE users SET last_login = NOW() WHERE id = ?",
                [$user['id']],
                'i'
            );
            
            // Log login activity
            self::logActivity($user['id'], 'login', 'User logged in successfully');
            
            return [
                'success' => true, 
                'user_type' => $user['user_type'],
                'clinic_id' => $user['clinic_id']
            ];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }
    
    /**
     * Logout current user
     */
    public static function logout() {
        $userId = Session::getUserId();
        
        if ($userId) {
            self::logActivity($userId, 'logout', 'User logged out');
        }
        
        Session::destroy();
    }
    
    /**
     * Check if user is authenticated
     */
    public static function check() {
        Session::init();
        return Session::get('logged_in') === true && Session::has('user_id');
    }
    
    /**
     * Require authentication (redirect if not logged in)
     */
    public static function requireAuth() {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/public/login.php');
            exit;
        }
    }
    
    /**
     * Require admin role
     */
    public static function requireAdmin() {
        self::requireAuth();
        
        if (!Session::isAdmin()) {
            Session::setFlash('error', 'Access denied. Admin privileges required.');
            header('Location: ' . BASE_URL . '/public/unauthorized.php');
            exit;
        }
    }
    
    /**
     * Require clinic role
     */
    public static function requireClinic() {
        self::requireAuth();
        
        if (!Session::isClinic()) {
            Session::setFlash('error', 'Access denied. Clinic privileges required.');
            header('Location: ' . BASE_URL . '/public/unauthorized.php');
            exit;
        }
    }
    
    /**
     * Get current clinic information
     */
    public static function getClinicInfo() {
        if (!Session::isClinic()) {
            return null;
        }
        
        $clinicId = Session::getClinicId();
        $db = Database::getInstance();
        
        try {
            $stmt = $db->query(
                "SELECT * FROM clinics WHERE id = ? AND is_active = 1",
                [$clinicId],
                'i'
            );
            
            $result = $stmt->get_result();
            return $result->fetch_assoc();
            
        } catch (Exception $e) {
            error_log("Get clinic info error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Log user activity
     */
    private static function logActivity($userId, $action, $description) {
        try {
            $db = Database::getInstance();
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            $db->query(
                "INSERT INTO user_activity_logs (user_id, action, description, ip_address, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [$userId, $action, $description, $ipAddress, $userAgent],
                'issss'
            );
        } catch (Exception $e) {
            error_log("Log activity error: " . $e->getMessage());
        }
    }
    
    /**
     * Generate random secure token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
}
