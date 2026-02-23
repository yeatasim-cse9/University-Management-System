<?php
/**
 * Migration Script for DRAC (Dynamic Routine Allocation System)
 * Applies schema changes for Time Slots, Availability, and Settings.
 */

require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/database.php';

echo "Starting DRAC Database Migration...\n";

// 1. Create time_slots table
$sql_time_slots = "CREATE TABLE IF NOT EXISTS `time_slots` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `label` varchar(50) DEFAULT NULL COMMENT 'e.g. Slot 1',
  `is_break` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_slot` (`start_time`, `end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($db->query($sql_time_slots)) {
    echo "[SUCCESS] Created 'time_slots' table.\n";
    // Insert default slots
    $db->query("INSERT IGNORE INTO `time_slots` (`start_time`, `end_time`, `label`, `is_break`) VALUES 
        ('08:30:00', '10:00:00', 'Slot 1', 0),
        ('10:00:00', '11:30:00', 'Slot 2', 0),
        ('11:30:00', '13:00:00', 'Slot 3', 0),
        ('13:00:00', '13:30:00', 'Break', 1),
        ('13:30:00', '15:00:00', 'Slot 4', 0),
        ('15:00:00', '16:30:00', 'Slot 5', 0)");
} else {
    echo "[ERROR] Creating 'time_slots': " . $db->error . "\n";
}

// 2. Create teacher_availability table
$sql_availability = "CREATE TABLE IF NOT EXISTS `teacher_availability` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('busy','preferred') DEFAULT 'busy',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($db->query($sql_availability)) {
    echo "[SUCCESS] Created 'teacher_availability' table.\n";
} else {
    echo "[ERROR] Creating 'teacher_availability': " . $db->error . "\n";
}

// 3. Create routine_settings table
$sql_settings = "CREATE TABLE IF NOT EXISTS `routine_settings` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($db->query($sql_settings)) {
    echo "[SUCCESS] Created 'routine_settings' table.\n";
    // Insert defaults
    $db->query("INSERT IGNORE INTO `routine_settings` (`setting_key`, `setting_value`) VALUES 
        ('strict_mode', '1'),
        ('max_daily_classes', '3'),
        ('lab_priority', '1')");
} else {
    echo "[ERROR] Creating 'routine_settings': " . $db->error . "\n";
}

// 4. Alter Users Table (Max Daily Classes)
$check_users = $db->query("SHOW COLUMNS FROM `users` LIKE 'max_daily_classes'");
if ($check_users->num_rows == 0) {
    if ($db->query("ALTER TABLE `users` ADD COLUMN `max_daily_classes` int(11) DEFAULT 3 AFTER `role`")) {
        echo "[SUCCESS] Added 'max_daily_classes' to 'users'.\n";
    } else {
        echo "[ERROR] Altering 'users': " . $db->error . "\n";
    }
} else {
    echo "[INFO] 'max_daily_classes' already exists in 'users'.\n";
}

// 5. Alter Courses Table (Weekly Limit, Duration)
$check_courses = $db->query("SHOW COLUMNS FROM `courses` LIKE 'weekly_limit'");
if ($check_courses->num_rows == 0) {
    if ($db->query("ALTER TABLE `courses` ADD COLUMN `weekly_limit` int(11) DEFAULT 2 AFTER `credit_hours`, 
                                          ADD COLUMN `duration_minutes` int(11) DEFAULT 90 AFTER `weekly_limit`")) {
        echo "[SUCCESS] Added 'weekly_limit' and 'duration_minutes' to 'courses'.\n";
    } else {
        echo "[ERROR] Altering 'courses': " . $db->error . "\n";
    }
} else {
    echo "[INFO] Columns already exist in 'courses'.\n";
}

echo "Migration Completed.\n";
?>
