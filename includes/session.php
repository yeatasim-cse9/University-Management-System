<?php
/**
 * Session Management
 * ACADEMIX - Academic Management System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Initialize session for logged-in user
 */
function init_session($user_data) {
    $_SESSION['user_id'] = $user_data['id'];
    $_SESSION['username'] = $user_data['username'];
    $_SESSION['email'] = $user_data['email'];
    $_SESSION['role'] = $user_data['role'];
    $_SESSION['first_login'] = $user_data['first_login'];
    $_SESSION['last_activity'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    
    // Generate CSRF token
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 */
function get_current_user_role() {
    return $_SESSION['role'] ?? null;
}

/**
 * Check session timeout
 */
function check_session_timeout() {
    if (!is_logged_in()) {
        return false;
    }
    
    $last_activity = $_SESSION['last_activity'] ?? 0;
    $timeout = SESSION_TIMEOUT;
    
    if (time() - $last_activity > $timeout) {
        session_destroy();
        return true;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    return false;
}

/**
 * Destroy session and logout
 */
function destroy_session() {
    // Clear remember me cookie if exists
    if (isset($_COOKIE['remember_me'])) {
        setcookie('remember_me', '', time() - 3600, '/');
    }
    
    $_SESSION = array();
    
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
 * Regenerate session ID for security
 */
function regenerate_session() {
    session_regenerate_id(true);
}
