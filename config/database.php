<?php
/**
 * Database Configuration
 * ACADEMIX - Academic Management System
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'academix');
define('DB_CHARSET', 'utf8mb4');

// Create database connection
function get_db_connection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $conn->set_charset(DB_CHARSET);
            $conn->query("SET time_zone = '+06:00'");
        } catch (Exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database Error: Unable to connect to the database. Please check if the MySQL server is running properly.");
        }
    }
    
    return $conn;
}

// Get database connection instance
$db = get_db_connection();
