<?php
/**
 * Phase 2 Migration: Room Foreign Key & Notifications
 */
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/database.php';

echo "=== DRAC Phase 2 Migration ===\n";

// 1. Add room_id to class_schedule
$check_col = $db->query("SHOW COLUMNS FROM class_schedule LIKE 'room_id'");
if ($check_col->num_rows == 0) {
    if ($db->query("ALTER TABLE class_schedule ADD COLUMN room_id int(10) UNSIGNED DEFAULT NULL AFTER room_number")) {
        echo "[SUCCESS] Added column 'room_id' to class_schedule.\n";
    } else {
        echo "[ERROR] Failed to add column: " . $db->error . "\n";
    }
}

// 2. Data Migration: Map room_number to room_id
echo "Migrating Room Data...\n";
$updates = 0;
// Fetch all schedules with room_number but no room_id
$q = $db->query("SELECT id, room_number FROM class_schedule WHERE room_id IS NULL AND room_number IS NOT NULL");
while ($row = $q->fetch_assoc()) {
    $r_num = $db->real_escape_string($row['room_number']);
    // Find room id
    $r_q = $db->query("SELECT id FROM rooms WHERE room_number = '$r_num' LIMIT 1");
    if ($r_row = $r_q->fetch_assoc()) {
        $r_id = $r_row['id'];
        $db->query("UPDATE class_schedule SET room_id = $r_id WHERE id = {$row['id']}");
        $updates++;
    } else {
        echo "[WARNING] Room '{$row['room_number']}' not found in rooms table. Keeping NULL.\n";
    }
}
echo "[DONE] Migrated $updates records.\n";

// 3. Create Notifications Table (if strict spec requires it)
$sql_notif = "CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($db->query($sql_notif)) {
    echo "[SUCCESS] Verified 'notifications' table.\n";
}

echo "=== Migration Complete ===\n";
?>
