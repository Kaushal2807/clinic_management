<?php
/**
 * Session Management Class
 * Handles all session operations with security features
 */

class Session {
    
    /**
     * Initialize session with security features
     */
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            // Security settings
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
            
            session_start();
            
            // Regenerate session ID periodically to prevent session fixation
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
            
            // Check for session timeout
            if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
                self::destroy();
                return false;
            }
            
            $_SESSION['last_activity'] = time();
        }
        
        return true;
    }
    
    /**
     * Set session variable
     */
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session variable
     */
    public static function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session variable exists
     */
    public static function has($key) {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session variable
     */
    public static function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    /**
     * Destroy session completely
     */
    public static function destroy() {
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    /**
     * Check if user is clinic user
     */
    public static function isClinic() {
        return self::get('user_type') === 'clinic';
    }
    
    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        return self::get('user_type') === 'admin';
    }
    
    /**
     * Get current clinic ID
     */
    public static function getClinicId() {
        return self::get('clinic_id');
    }
    
    /**
     * Get current user ID
     */
    public static function getUserId() {
        return self::get('user_id');
    }
    
    /**
     * Get current user name
     */
    public static function getUserName() {
        return self::get('full_name');
    }
    
    /**
     * Get current user email
     */
    public static function getEmail() {
        return self::get('email');
    }
    
    /**
     * Set flash message
     */
    public static function setFlash($type, $message) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }
    
    /**
     * Get and clear flash message
     */
    public static function getFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}
