<?php
/**
 * Authentication and Authorization
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';

/**
 * Require user to be logged in
 */
function require_login() {
    if (check_session_timeout()) {
        set_flash('warning', 'Your session has expired. Please login again.');
        redirect(BASE_URL . '/modules/auth/login.php');
    }
    
    if (!is_logged_in()) {
        redirect(BASE_URL . '/modules/auth/login.php');
    }
    
    // Check if first login and force password change
    if (isset($_SESSION['first_login']) && $_SESSION['first_login'] == 1) {
        $current_page = $_SERVER['PHP_SELF'];
        if (strpos($current_page, 'change-password.php') === false && strpos($current_page, 'logout.php') === false) {
            redirect(BASE_URL . '/modules/auth/change-password.php');
        }
    }
}

/**
 * Require specific role(s)
 */
function require_role($roles) {
    require_login();
    
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    $user_role = get_current_user_role();
    
    if (!in_array($user_role, $roles)) {
        set_flash('error', 'You do not have permission to access this page.');
        redirect(BASE_URL . '/index.php');
    }
}

/**
 * Check if user has role
 */
function has_role($role) {
    return get_current_user_role() === $role;
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output CSRF token field
 */
function csrf_field() {
    $token = generate_csrf_token();
    echo '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Check if account is locked
 */
function is_account_locked($user_id) {
    global $db;
    
    $stmt = $db->prepare("SELECT locked_until FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if ($row['locked_until'] && strtotime($row['locked_until']) > time()) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check login attempts and lock if necessary
 */
function check_login_attempts($username) {
    global $db;
    
    $stmt = $db->prepare("SELECT id, failed_login_attempts, locked_until FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Check if account is locked
        if ($row['locked_until'] && strtotime($row['locked_until']) > time()) {
            $remaining = strtotime($row['locked_until']) - time();
            $minutes = ceil($remaining / 60);
            return [
                'locked' => true,
                'message' => "Account is locked. Please try again in {$minutes} minute(s)."
            ];
        }
        
        // Reset lock if time has passed
        if ($row['locked_until'] && strtotime($row['locked_until']) <= time()) {
            $stmt = $db->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
            $stmt->bind_param("i", $row['id']);
            $stmt->execute();
        }
        
        return ['locked' => false];
    }
    
    return ['locked' => false];
}

/**
 * Increment failed login attempts
 */
function increment_failed_attempts($username) {
    global $db;
    
    $stmt = $db->prepare("SELECT id, failed_login_attempts FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $attempts = $row['failed_login_attempts'] + 1;
        $locked_until = null;
        
        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            $locked_until = date('Y-m-d H:i:s', time() + LOCKOUT_DURATION);
        }
        
        $stmt = $db->prepare("UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?");
        $stmt->bind_param("isi", $attempts, $locked_until, $row['id']);
        $stmt->execute();
        
        return $attempts;
    }
    
    return 0;
}

/**
 * Reset failed login attempts
 */
function reset_failed_attempts($user_id) {
    global $db;
    
    $stmt = $db->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

/**
 * Log login attempt
 */
function log_login_attempt($username, $user_id, $status, $failure_reason = null) {
    global $db;
    
    $ip_address = get_client_ip();
    $user_agent = get_user_agent();
    
    $stmt = $db->prepare("INSERT INTO login_history (user_id, username, status, failure_reason, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $username, $status, $failure_reason, $ip_address, $user_agent);
    $stmt->execute();
}

/**
 * Create audit log entry
 */
function create_audit_log($action, $table_name = null, $record_id = null, $old_values = null, $new_values = null) {
    global $db;
    
    $user_id = get_current_user_id();
    $ip_address = get_client_ip();
    $user_agent = get_user_agent();
    
    $old_values_json = $old_values ? json_encode($old_values) : null;
    $new_values_json = $new_values ? json_encode($new_values) : null;
    
    $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssisss", $user_id, $action, $table_name, $record_id, $old_values_json, $new_values_json, $ip_address, $user_agent);
    $stmt->execute();
}

/**
 * Handle remember me functionality
 */
function set_remember_me($user_id) {
    global $db;
    
    $token = generate_token(32);
    $expires = date('Y-m-d H:i:s', time() + REMEMBER_ME_DURATION);
    
    $stmt = $db->prepare("UPDATE users SET remember_token = ?, remember_token_expires = ? WHERE id = ?");
    $stmt->bind_param("ssi", $token, $expires, $user_id);
    $stmt->execute();
    
    setcookie('remember_me', $token, time() + REMEMBER_ME_DURATION, '/', '', false, true);
}

/**
 * Check remember me cookie
 */
function check_remember_me() {
    global $db;
    
    if (!isset($_COOKIE['remember_me'])) {
        return false;
    }
    
    $token = $_COOKIE['remember_me'];
    
    $stmt = $db->prepare("SELECT u.*, up.first_name, up.last_name FROM users u LEFT JOIN user_profiles up ON u.id = up.user_id WHERE u.remember_token = ? AND u.remember_token_expires > NOW() AND u.status = 'active'");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        init_session($user);
        return true;
    }
    
    // Invalid token, clear cookie
    setcookie('remember_me', '', time() - 3600, '/');
    return false;
}

/**
 * Clear remember me token
 */
function clear_remember_me($user_id) {
    global $db;
    
    $stmt = $db->prepare("UPDATE users SET remember_token = NULL, remember_token_expires = NULL WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    if (isset($_COOKIE['remember_me'])) {
        setcookie('remember_me', '', time() - 3600, '/');
    }
}
