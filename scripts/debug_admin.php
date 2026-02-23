<?php
require_once __DIR__ . '/../config/database.php';

$conn = get_db_connection();

// Check department_admins
echo "=== department_admins ===\n";
$r = $conn->query("SELECT * FROM department_admins");
if (!$r) {
    echo "Table doesn't exist or error: " . $conn->error . "\n";
} else {
    echo "Rows: " . $r->num_rows . "\n";
    while ($row = $r->fetch_assoc()) {
        print_r($row);
    }
}

// Check admin users
echo "\n=== Admin Users ===\n";
$r = $conn->query("SELECT id, email, role FROM users WHERE role = 'admin'");
while ($row = $r->fetch_assoc()) {
    print_r($row);
}

// Check students
echo "\n=== Students ===\n";
$r = $conn->query("SELECT COUNT(*) as cnt FROM students");
$row = $r->fetch_assoc();
echo "Total students: " . $row['cnt'] . "\n";

// Check departments
echo "\n=== Departments ===\n";
$r = $conn->query("SELECT id, name, code FROM departments LIMIT 5");
while ($row = $r->fetch_assoc()) {
    print_r($row);
}
