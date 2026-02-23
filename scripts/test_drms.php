<?php
/**
 * Test Script for DRMS/DRAS
 * Run this via CLI: php scripts/test_drms.php
 */

require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/routine_helper.php';

echo "=== STARTING DRMS VERIFICATION ===\n";

// 1. Setup Test Data
$test_room = "TEST-101";
$db->query("INSERT IGNORE INTO rooms (room_number, building) VALUES ('$test_room', 'Test Block')");

$day = "Monday";
$start = "10:00:00";
$end = "11:30:00";

// Clean up previous test schedules
$db->query("DELETE FROM class_schedule WHERE room_number = '$test_room'");
echo "[SETUP] Test Room $test_room cleaned.\n";

// 2. Test Room Conflict
echo "\n--- Testing Room Conflict ---\n";
// Insert a dummy schedule
$setup_sql = "INSERT INTO class_schedule (course_offering_id, day_of_week, start_time, end_time, room_number, building) 
              VALUES (1, '$day', '$start', '$end', '$test_room', 'Test Block')";
if ($db->query($setup_sql)) {
    echo "[SUCCESS] Inserted Base Schedule.\n";
} else {
    die("[FAIL] Could not insert base schedule.\n");
}

// Check conflict
$conflicts = check_routine_conflicts($db, $day, "10:30:00", "12:00:00", $test_room, 0, 0); // Overlapping time
if (!empty($conflicts)) {
    echo "[PASS] Detected conflict: " . implode(", ", $conflicts) . "\n";
} else {
    echo "[FAIL] Failed to detect overlapping room schedule!\n";
}

// 3. Test Teacher Conflict
echo "\n--- Testing Teacher Conflict ---\n";
// Assign Teacher ID 999 to Offering ID 1
// First ensure strict mode doesn't break us
$db->query("INSERT IGNORE INTO users (id, username, password, role) VALUES (999, 'test_prof', 'pass', 'teacher')");
$db->query("INSERT IGNORE INTO teacher_courses (teacher_id, course_offering_id) VALUES (999, 1)");

// Check conflict for same teacher, different room
$conflicts_t = check_routine_conflicts($db, $day, "10:30:00", "12:00:00", "DIFFERENT-ROOM", 999, 2); 
// Note: Offering 2 doesn't matter much unless we check batch, but Teacher 999 is busy in Offering 1 at this time.
if (!empty($conflicts_t)) {
    echo "[PASS] Detected Teacher conflict: " . implode(", ", $conflicts_t) . "\n";
} else {
    echo "[FAIL] Failed to detect teacher busy status!\n";
}

// 4. Test Auto-Allocation Simulation
echo "\n--- Testing DRAS Auto-Allocation ---\n";
// Fetch a semester
$sem_res = $db->query("SELECT id FROM semesters LIMIT 1");
if ($sem_res->num_rows > 0) {
    $sem_id = $sem_res->fetch_assoc()['id'];
    
    // We need valid department IDs, let's fetch all
    $all_depts = [];
    $d_res = $db->query("SELECT id FROM departments");
    while($r=$d_res->fetch_assoc()) $all_depts[] = $r['id'];
    
    echo "Simulating Allocation for Semester ID: $sem_id...\n";
    // We won't actually commit unless we want to, but let's run it and see the result structure
    // To preserve data, we might roll back transaction if we supported it fully, 
    // but for now let's just see if it runs without crashing.
    
    // Create a dummy offering that needs allocation
    $db->query("INSERT IGNORE INTO courses (id, course_code, course_name, credit_hours, department_id, course_type) VALUES (9999, 'TEST-AUTO', 'Auto Test', 3.0, {$all_depts[0]}, 'theory')");
    $db->query("INSERT IGNORE INTO course_offerings (id, course_id, semester_id, section, status) VALUES (9999, 9999, $sem_id, 'Z', 'open')");
    $db->query("DELETE FROM class_schedule WHERE course_offering_id = 9999"); // Ensure empty
    
    $result = auto_allocate_semester($db, $sem_id, $all_depts);
    echo "Allocation Result: Allocated " . $result['allocated'] . " slots.\n";
    
    if ($result['allocated'] > 0) {
        $check = $db->query("SELECT * FROM class_schedule WHERE course_offering_id = 9999");
        if ($check->num_rows > 0) {
            echo "[PASS] Auto-allocator successfully created schedules.\n";
            $row = $check->fetch_assoc();
            echo "   -> Assigned: " . $row['day_of_week'] . " " . $row['start_time'] . " Room " . $row['room_number'] . "\n";
        } else {
            echo "[FAIL] Reported unexpected allocation count.\n";
        }
    } else {
        echo "[INFO] No slots allocated. This might be due to full schedule or no rooms.\n";
        if (!empty($result['errors'])) print_r($result['errors']);
    }
    
    // Cleanup
    $db->query("DELETE FROM class_schedule WHERE course_offering_id = 9999");
    $db->query("DELETE FROM course_offerings WHERE id = 9999");
    $db->query("DELETE FROM courses WHERE id = 9999");
    
} else {
    echo "[SKIP] No semester found.\n";
}

echo "\n=== VERIFICATION COMPLETE ===\n";
?>
