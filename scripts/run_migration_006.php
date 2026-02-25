<?php
$db = new mysqli('localhost', 'root', '', 'academix');
if ($db->connect_error) { echo "Connection failed: " . $db->connect_error . PHP_EOL; exit(1); }

// Show current columns
echo "=== Current Columns ===" . PHP_EOL;
$r = $db->query('SHOW COLUMNS FROM class_reschedules');
if ($r) {
    while($row = $r->fetch_assoc()) {
        echo $row['Field'] . ' | ' . $row['Type'] . PHP_EOL;
    }
} else {
    echo "Table does not exist." . PHP_EOL;
    exit(1);
}

// Check if column already exists
$check = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='academix' AND TABLE_NAME='class_reschedules' AND COLUMN_NAME='reschedule_type'");
if ($check && $check->num_rows > 0) {
    echo PHP_EOL . "reschedule_type column already exists. Skipping." . PHP_EOL;
} else {
    // Add after 'id' column to avoid referencing columns that might not exist
    $sql = "ALTER TABLE class_reschedules ADD COLUMN reschedule_type ENUM('reschedule', 'cancel') DEFAULT 'reschedule' AFTER id";
    if ($db->query($sql)) {
        echo PHP_EOL . "SUCCESS: Added reschedule_type column." . PHP_EOL;
    } else {
        echo PHP_EOL . "ERROR: " . $db->error . PHP_EOL;
    }
}

$db->close();
