<?php
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/routine_helper.php';

echo "=== DRAC VERIFICATION PROTOCOL ===\n";

// 1. Setup Test Data
$db->query("TRUNCATE TABLE time_slots");
$db->query("INSERT INTO time_slots (start_time, end_time, label) VALUES ('09:00:00', '10:30:00', 'Test Slot A')");
$db->query("INSERT INTO routine_settings (setting_key, setting_value) VALUES ('strict_mode', '1') ON DUPLICATE KEY UPDATE setting_value='1'");

// Mock Data
$day = 'Monday';
$room = 'TestRoom';
$teacher_id = 999;
$offering_id = 999;

// 2. Test Invalid Time (Strict Mode)
echo "\n[TEST 1] Testing Strict Mode with INVALID time (09:15 - 10:45)...\n";
$conflicts = check_routine_conflicts($db, $day, '09:15:00', '10:45:00', $room, $teacher_id, $offering_id);
if (!empty($conflicts) && strpos($conflicts[0], 'Time Slot') !== false) {
    echo "PASS: System correctly rejected invalid time.\n";
    echo " > Error: " . $conflicts[0] . "\n";
} else {
    echo "FAIL: System accepted invalid time in Strict Mode!\n";
    print_r($conflicts);
}

// 3. Test Valid Time (Strict Mode)
echo "\n[TEST 2] Testing Strict Mode with VALID time (09:00 - 10:30)...\n";
$conflicts = check_routine_conflicts($db, $day, '09:00:00', '10:30:00', $room, $teacher_id, $offering_id);
if (empty($conflicts)) {
    echo "PASS: System accepted valid slot.\n";
} else {
    echo "FAIL: System rejected valid slot!\n";
    print_r($conflicts);
}

// 4. Test Teacher Availability
echo "\n[TEST 3] Testing Teacher Availability Conflict...\n";
// Set teacher as BUSY
$db->query("INSERT INTO teacher_availability (user_id, day, start_time, end_time, status) VALUES ($teacher_id, '$day', '09:00:00', '10:30:00', 'busy')");

$conflicts = check_routine_conflicts($db, $day, '09:00:00', '10:30:00', $room, $teacher_id, $offering_id);
if (!empty($conflicts) && strpos($conflicts[0], 'Unavailable') !== false) {
    echo "PASS: System detected Teacher Unavailability.\n";
    echo " > Error: " . $conflicts[0] . "\n";
} else {
    echo "FAIL: System ignored Teacher Availability!\n";
    print_r($conflicts);
}

// Cleanup
$db->query("DELETE FROM teacher_availability WHERE user_id = $teacher_id");
echo "\n=== VERIFICATION COMPLETE ===\n";
?>
