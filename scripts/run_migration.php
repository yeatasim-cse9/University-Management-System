<?php
require_once __DIR__ . '/../config/database.php';

$sql = file_get_contents(__DIR__ . '/../database/migrations/001_create_reschedules_table.sql');

if ($db->multi_query($sql)) {
    echo "Migration executed successfully.\n";
    do {
        if ($result = $db->store_result()) {
            $result->free();
        }
    } while ($db->more_results() && $db->next_result());
} else {
    echo "Error executing migration: " . $db->error . "\n";
}
?>
