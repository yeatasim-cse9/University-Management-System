<?php
// Script to wipe class_schedule table
require_once __DIR__ . '/../config/database.php';

echo "WARNING: This will delete ALL data from class_schedule.\n";
echo "Starting wipe...\n";

try {
    $db->query("SET FOREIGN_KEY_CHECKS = 0");
    $db->query("TRUNCATE TABLE class_schedule");
    $db->query("SET FOREIGN_KEY_CHECKS = 1");
    echo "SUCCESS: class_schedule table has been truncated.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
