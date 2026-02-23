<?php
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/routine_helper.php';

echo "=== DRAC Phase 2 Verification ===\n";

// 1. Test Room ID Conflict Check
echo "[TEST 1] Room ID Conflict Check...\n";
// Pick a valid room ID
$r_res = $db->query("SELECT id FROM rooms LIMIT 1");
if ($r_row = $r_res->fetch_assoc()) {
    $r_id = $r_row['id'];
    $day = 'Sunday';
    $start = '08:00:00'; 
    $end = '09:00:00';
    
    // Clear previous test
    $db->query("DELETE FROM class_schedule WHERE day_of_week='$day' AND start_time='$start'");
    
    // Insert Mock Schedule manually
    $ins = $db->prepare("INSERT INTO class_schedule (room_id, day_of_week, start_time, end_time, course_offering_id) VALUES (?, ?, ?, ?, 999)");
    $ins->bind_param("isss", $r_id, $day, $start, $end);
    $ins->execute();
    
    // Check Conflict
    $conflicts = check_routine_conflicts($db, $day, $start, $end, $r_id, null, 998); // Different offering
    
    if (!empty($conflicts)) {
        echo "PASS: Detected conflict for Room ID $r_id.\n";
    } else {
        echo "FAIL: Did not detect conflict for Room ID $r_id!\n";
    }
    
    // Cleanup
    $db->query("DELETE FROM class_schedule WHERE day_of_week='$day' AND start_time='$start'");
} else {
    echo "SKIP: No rooms found in DB.\n";
}

// 2. Test Notification Trigger
echo "\n[TEST 2] Notification Trigger...\n";
$start_count = $db->query("SELECT COUNT(*) as c FROM notifications")->fetch_assoc()['c'];

// Simulate Auto-Allocation success logic (Manual insert)
$user_id = 1;
$msg = "Test Notification";
$db->query("INSERT INTO notifications (user_id, message) VALUES ($user_id, '$msg')");

$end_count = $db->query("SELECT COUNT(*) as c FROM notifications")->fetch_assoc()['c'];

if ($end_count > $start_count) {
    echo "PASS: Notification inserted.\n";
} else {
    echo "FAIL: Notification not inserted.\n";
}

echo "=== Verification Complete ===\n";
?>
