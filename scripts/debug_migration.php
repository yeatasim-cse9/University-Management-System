<?php
require_once __DIR__ . '/../config/database.php';

try {
    $sql = file_get_contents(__DIR__ . '/../database/migrations/001_create_reschedules_table.sql');
    if ($db->multi_query($sql)) {
         do {
            if ($result = $db->store_result()) {
                $result->free();
            }
        } while ($db->more_results() && $db->next_result());
        echo "Migration executed successfully.\n";
    } else {
        echo "Error: " . $db->error . "\n";
    }
} catch (mysqli_sql_exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "General Exception: " . $e->getMessage() . "\n";
}
?>
