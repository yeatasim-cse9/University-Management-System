<?php
require_once __DIR__ . '/../config/database.php';
$conn = get_db_connection();

echo "Recreating 'semesters' table...\n";

// DROP
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("DROP TABLE IF EXISTS semesters");
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// CREATE
$sql = "
CREATE TABLE `semesters` (
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
    echo "Table 'semesters' created.\n";
    
    // Seed
    $insert = "INSERT INTO semesters (semester_name, year, status) VALUES 
    ('Spring', '2025', 'active'),
    ('Fall', '2025', 'inactive'),
    ('Summer', '2025', 'inactive')";
    
    if ($conn->query($insert)) {
        echo "Seeded default semesters.\n";
    } else {
         echo "Seeding failed: " . $conn->error . "\n";
    }

} else {
    echo "Creating table failed: " . $conn->error . "\n";
}

$conn->close();
?>
