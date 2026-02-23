<?php
/**
 * Logout
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (is_logged_in()) {
    $user_id = get_current_user_id();
    
    // Clear remember me token
    clear_remember_me($user_id);
    
    // Log logout
    create_audit_log('logout', 'users', $user_id);
    
    // Destroy session
    destroy_session();
}

redirect(BASE_URL . '/modules/auth/login.php');
