<?php
require_once __DIR__ . '/../config/database.php';

$db = get_db_connection();

// Get admin user
$r = $db->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$user = $r->fetch_assoc();
if (!$user) {
    echo "No admin user found!\n";
    exit;
}
$uid = $user['id'];

// Get department (CSE)
$r2 = $db->query("SELECT id FROM departments WHERE code = 'CSE' LIMIT 1");
$dept = $r2->fetch_assoc();
if (!$dept) {
    $r2 = $db->query("SELECT id FROM departments LIMIT 1");
    $dept = $r2->fetch_assoc();
}
if (!$dept) {
    echo "No department found!\n";
    exit;
}
$did = $dept['id'];

// Link admin to department
$result = $db->query("INSERT IGNORE INTO department_admins (user_id, department_id) VALUES ($uid, $did)");
if ($result) {
    echo "SUCCESS: Linked admin user $uid to department $did\n";
} else {
    echo "Error: " . $db->error . "\n";
}

// Verify
$r = $db->query("SELECT * FROM department_admins WHERE user_id = $uid");
echo "Rows in department_admins for this admin: " . $r->num_rows . "\n";
