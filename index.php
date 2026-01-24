<?php
/**
 * Main Entry Point
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Check if user is logged in
if (!is_logged_in()) {
    // Check remember me
    if (!check_remember_me()) {
        redirect(BASE_URL . '/modules/auth/login.php');
    }
}

// Require login
require_login();

// Route to appropriate dashboard based on role
$role = get_current_user_role();

switch ($role) {
    case 'super_admin':
        redirect(BASE_URL . '/modules/super_admin/dashboard.php');
        break;
    
    case 'admin':
        redirect(BASE_URL . '/modules/admin/dashboard.php');
        break;
    
    case 'teacher':
        redirect(BASE_URL . '/modules/teacher/dashboard.php');
        break;
    
    case 'student':
        redirect(BASE_URL . '/modules/student/dashboard.php');
        break;
    
    default:
        set_flash('error', 'Invalid user role');
        redirect(BASE_URL . '/modules/auth/logout.php');
}
