<?php
require_once __DIR__ . '/../config/database.php';
echo "--- Class Schedule ---\n";
$res = $db->query("DESCRIBE class_schedule");
while($row=$res->fetch_assoc()){ echo $row['Field'] . " " . $row['Type'] . "\n"; }

echo "\n--- Students ---\n";
$res = $db->query("DESCRIBE students");
while($row=$res->fetch_assoc()){ echo $row['Field'] . " " . $row['Type'] . "\n"; }

echo "\n--- Class Reschedules ---\n";
$res = $db->query("DESCRIBE class_reschedules");
while($row=$res->fetch_assoc()){ echo $row['Field'] . " " . $row['Type'] . "\n"; }
?>
