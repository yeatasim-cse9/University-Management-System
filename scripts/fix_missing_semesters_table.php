<?php
require_once __DIR__ . '/../config/database.php';

$conn = get_db_connection();

echo "Checking for missing 'semesters' table...\n";

// Create Table
$sql = "
CREATE TABLE IF NOT EXISTS `semesters` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `semester_name` varchar(50) NOT NULL,
  `year` varchar(20) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql)) {
    echo "[SUCCESS] Table 'semesters' created or exists.\n";
    
    // Seed Data if empty
    $check = $conn->query("SELECT COUNT(*) as count FROM semesters");
    $row = $check->fetch_assoc();
    if ($row['count'] == 0) {
        $insert = "INSERT INTO semesters (semester_name, year, status) VALUES 
        ('Spring', '2025', 'active'),
        ('Fall', '2025', 'inactive'),
        ('Summer', '2025', 'inactive')";
        if ($conn->query($insert)) {
            echo "[SUCCESS] Seeded default semesters.\n";
        } else {
             echo "[ERROR] Seeding failed: " . $conn->error . "\n";
        }
    } else {
        echo "[INFO] Table 'semesters' already has data.\n";
    }

} else {
    echo "[ERROR] Creating table: " . $conn->error . "\n";
}

$conn->close();
?>
