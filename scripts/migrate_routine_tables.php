<?php
/**
 * Run Routine Tables Migration
 * Execute: php scripts/migrate_routine_tables.php
 */

require_once __DIR__ . '/../config/database.php';

echo "===========================================\n";
echo "Dynamic Routine Management - Migration\n";
echo "===========================================\n\n";

// Read the migration file
$migration_file = __DIR__ . '/../database/migrations/002_create_routine_tables.sql';

if (!file_exists($migration_file)) {
    die("Error: Migration file not found!\n");
}

$sql = file_get_contents($migration_file);

// Split by semicolons to get individual statements
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        // Filter out empty statements and comments-only statements
        $clean = trim(preg_replace('/--.*$/m', '', $stmt));
        return !empty($clean);
    }
);

echo "Found " . count($statements) . " SQL statements to execute.\n\n";

$success = 0;
$failed = 0;

foreach ($statements as $index => $statement) {
    $statement = trim($statement);
    if (empty($statement)) continue;
    
    // Extract first line for display
    $first_line = strtok($statement, "\n");
    $display = strlen($first_line) > 60 ? substr($first_line, 0, 60) . '...' : $first_line;
    
    echo "[" . ($index + 1) . "] Executing: $display\n";
    
    if ($db->multi_query($statement)) {
        // Consume all results
        do {
            if ($result = $db->store_result()) {
                $result->free();
            }
        } while ($db->next_result());
        
        echo "    ✓ Success\n";
        $success++;
    } else {
        echo "    ✗ Error: " . $db->error . "\n";
        $failed++;
    }
}

echo "\n===========================================\n";
echo "Migration Complete!\n";
echo "Success: $success | Failed: $failed\n";
echo "===========================================\n";

// Verify tables exist
echo "\nVerifying tables...\n";
$tables = ['routine_slot_types', 'routine_time_slots', 'routine_templates', 'routine_assignments'];

foreach ($tables as $table) {
    $result = $db->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "  ✓ $table exists\n";
    } else {
        echo "  ✗ $table NOT FOUND\n";
    }
}

// Show slot types
echo "\nDefault Slot Types:\n";
$result = $db->query("SELECT * FROM routine_slot_types");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  - {$row['name']}: {$row['duration_minutes']} minutes\n";
    }
}

echo "\nMigration script completed.\n";
