<?php
/**
 * Session Management
 * File: config/session.php
 */

// Konfigurasi session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_lifetime', 3600); // 1 hour

// Mulai session jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

class SessionManager {
    
    public static function start() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerate session ID secara berkala untuk keamanan
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 menit
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }
    
    public static function has($key) {
        return isset($_SESSION[$key]);
    }
    
    public static function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    public static function destroy() {
        session_unset();
        session_destroy();
        
        // Hapus cookie session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
    }
    
    public static function login($user_data) {
        // Regenerate session ID untuk mencegah session fixation
        session_regenerate_id(true);
        
        // Set user data
        $_SESSION['user_id'] = $user_data['id'];
        $_SESSION['username'] = $user_data['username'];
        $_SESSION['user_role'] = $user_data['role'];
        $_SESSION['full_name'] = $user_data['full_name'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Set additional data based on role
        if ($user_data['role'] == 'siswa') {
            $_SESSION['siswa_id'] = $user_data['siswa_id'];
            $_SESSION['kelas_id'] = $user_data['kelas_id'];
        } elseif ($user_data['role'] == 'wali') {
            $_SESSION['wali_id'] = $user_data['wali_id'];
        }
    }
    
    public static function logout() {
        self::destroy();
        header("Location: " . BASE_URL . "auth/login.php");
        exit();
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public static function getUserId() {
        return self::get('user_id');
    }
    
    public static function getUsername() {
        return self::get('username');
    }
    
    public static function getUserRole() {
        return self::get('user_role');
    }
    
    public static function getFullName() {
        return self::get('full_name');
    }
    
    public static function checkAuth($required_role = null) {
        if (!self::isLoggedIn()) {
            header("Location: " . BASE_URL . "auth/login.php");
            exit();
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        // Check session timeout (30 menit)
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 1800)) {
            self::logout();
        }
        
        // Check role if required
        if ($required_role && self::getUserRole() !== $required_role) {
            header("Location: " . BASE_URL . "auth/unauthorized.php");
            exit();
        }
        
        return true;
    }
    
    public static function checkRole($roles) {
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        return in_array(self::getUserRole(), $roles);
    }
    
    public static function setFlash($type, $message) {
        $_SESSION['flash'][$type] = $message;
    }
    
    public static function getFlash($type) {
        if (isset($_SESSION['flash'][$type])) {
            $message = $_SESSION['flash'][$type];
            unset($_SESSION['flash'][$type]);
            return $message;
        }
        return null;
    }
    
    public static function hasFlash($type) {
        return isset($_SESSION['flash'][$type]);
    }
    
    public static function getSessionInfo() {
        return [
            'user_id' => self::getUserId(),
            'username' => self::getUsername(),
            'full_name' => self::getFullName(),
            'role' => self::getUserRole(),
            'login_time' => self::get('login_time'),
            'last_activity' => self::get('last_activity')
        ];
    }
}

// Auto-start session
SessionManager::start();
?>