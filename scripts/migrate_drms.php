<?php
/**
 * Migration Script for DRMS (Dynamic Routine Management System)
 * Creates 'rooms' table and migrates existing data.
 */

require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/database.php';

echo "Starting DRMS Migration...\n";

// 1. Create 'rooms' table
$sql_create_rooms = "CREATE TABLE IF NOT EXISTS rooms (
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(50) NOT NULL UNIQUE,
    building VARCHAR(100) DEFAULT 'Main',
    capacity INT(11) DEFAULT 60,
    type ENUM('theory', 'lab') DEFAULT 'theory',
    status ENUM('active', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($db->query($sql_create_rooms)) {
    echo "[SUCCESS] 'rooms' table created/verified.\n";
} else {
    die("[ERROR] Failed to create 'rooms' table: " . $db->error . "\n");
}

// 2. Migrate existing rooms from class_schedule
$sql_fetch_rooms = "SELECT DISTINCT room_number, building FROM class_schedule WHERE room_number IS NOT NULL AND room_number != ''";
$result = $db->query($sql_fetch_rooms);

if ($result->num_rows > 0) {
    echo "[INFO] Found " . $result->num_rows . " existing rooms in schedule. Migrating...\n";
    $migrated_count = 0;
    
    $stmt_insert = $db->prepare("INSERT IGNORE INTO rooms (room_number, building, type) VALUES (?, ?, ?)");
    
    while ($row = $result->fetch_assoc()) {
        $room_no = trim($row['room_number']);
        $building = !empty($row['building']) ? $row['building'] : 'Academic Building';
        
        // Guess type based on name
        $type = 'theory';
        if (stripos($room_no, 'lab') !== false || stripos($room_no, 'computer') !== false) {
            $type = 'lab';
        }
        
        $stmt_insert->bind_param("sss", $room_no, $building, $type);
        if ($stmt_insert->execute() && $stmt_insert->affected_rows > 0) {
            $migrated_count++;
        }
    }
    echo "[SUCCESS] Migrated $migrated_count new rooms.\n";
} else {
    echo "[INFO] No existing rooms found to migrate.\n";
}

// 3. Add index to class_schedule for faster conflict checks
// Check if index exists first (MySQL doesn't support IF NOT EXISTS for indexes easily in simple SQL)
try {
    $db->query("CREATE INDEX idx_schedule_conflict ON class_schedule(day_of_week, start_time, end_time)");
    echo "[SUCCESS] Added index for conflict detection.\n";
} catch (Exception $e) {
    echo "[INFO] Index likely already exists.\n";
}

echo "DRMS Migration Completed Successfully.\n";
?>
