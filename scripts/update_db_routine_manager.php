<?php
require_once __DIR__ . '/../config/database.php';

$conn = get_db_connection();

echo "Starting Database Updates for Routine Manager System...\n";

// 1. Create classrooms table
$sql_classrooms = "
CREATE TABLE IF NOT EXISTS `classrooms` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `department_id` int(10) UNSIGNED DEFAULT NULL,
  `room_number` varchar(50) NOT NULL,
  `capacity` int(11) DEFAULT 60,
  `type` enum('theory','lab') DEFAULT 'theory',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dept_room` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql_classrooms)) {
    echo "[SUCCESS] Table 'classrooms' created or already exists.\n";
} else {
    echo "[ERROR] Creating 'classrooms' table: " . $conn->error . "\n";
}

// 2. Update class_schedule table
// Check if columns exist first to avoid errors on multiple runs
$columns_result = $conn->query("SHOW COLUMNS FROM `class_schedule` LIKE 'teacher_id'");
if ($columns_result->num_rows == 0) {
    $sql_update_schedule = "
    ALTER TABLE `class_schedule`
    ADD COLUMN `teacher_id` int(10) UNSIGNED DEFAULT NULL AFTER `course_offering_id`
    ";
    
    if ($conn->query($sql_update_schedule)) {
         echo "[SUCCESS] Column 'teacher_id' added to 'class_schedule'.\n";
         
         // Add FK
         $sql_fk = "
         ALTER TABLE `class_schedule`
         ADD CONSTRAINT `fk_schedule_teacher_new` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL
         ";
         if($conn->query($sql_fk)){
             echo "[SUCCESS] Foreign key 'fk_schedule_teacher_new' added.\n";
         } else {
             echo "[ERROR] Adding foreign key: " . $conn->error . "\n";
         }

    } else {
        echo "[ERROR] Adding 'teacher_id' column: " . $conn->error . "\n";
    }
} else {
    echo "[INFO] Column 'teacher_id' already exists in 'class_schedule'.\n";
}

// Check for classroom_id
$columns_result_room = $conn->query("SHOW COLUMNS FROM `class_schedule` LIKE 'classroom_id'");
if ($columns_result_room->num_rows == 0) {
     // Optional normalization as per instructions, but adding it allows linking to our new rooms table
    $sql_update_schedule_room = "
    ALTER TABLE `class_schedule`
    ADD COLUMN `classroom_id` int(10) UNSIGNED DEFAULT NULL AFTER `teacher_id`
    ";
     if ($conn->query($sql_update_schedule_room)) {
         echo "[SUCCESS] Column 'classroom_id' added to 'class_schedule'.\n";
          // Add FK
         $sql_fk_room = "
         ALTER TABLE `class_schedule`
         ADD CONSTRAINT `fk_schedule_classroom_new` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms` (`id`) ON DELETE SET NULL
         ";
         if($conn->query($sql_fk_room)){
             echo "[SUCCESS] Foreign key 'fk_schedule_classroom_new' added.\n";
         } else {
             echo "[ERROR] Adding foreign key for classroom: " . $conn->error . "\n";
         }
    } else {
        echo "[ERROR] Adding 'classroom_id' column: " . $conn->error . "\n";
    }
} else {
    echo "[INFO] Column 'classroom_id' already exists in 'class_schedule'.\n";
}

echo "Database updates completed.\n";
$conn->close();
?>
