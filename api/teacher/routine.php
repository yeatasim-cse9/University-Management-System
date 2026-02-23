<?php
/**
 * Teacher Routine API
 * ACADEMIX - Academic Management System
 * 
 * Handles fetching routine data and submitting change requests
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Start output buffering
ob_start();

// Set JSON header
header('Content-Type: application/json');

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);

// =============================================
// HELPER FUNCTIONS FOR ROOM AVAILABILITY
// =============================================

/**
 * Count available rooms for a specific slot on a specific date
 */
function countAvailableRooms($db, $routine_id, $target_date, $target_day, $slot_id, $course_type) {
    $rooms = getAvailableRooms($db, $routine_id, $target_date, $target_day, $slot_id, $course_type);
    return count($rooms);
}

/**
 * Get list of available rooms for a specific slot on a specific date
 * Checks both permanent routine assignments and date-specific reschedules
 */
function getAvailableRooms($db, $routine_id, $target_date, $target_day, $slot_id, $course_type) {
    // 1. Get all rooms of appropriate type
    $room_type = ($course_type === 'lab') ? 'lab' : 'classroom';
    $stmt = $db->prepare("SELECT * FROM rooms WHERE room_type = ? AND is_active = 1");
    $stmt->bind_param("s", $room_type);
    $stmt->execute();
    $all_rooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // 2. Get rooms occupied by PERMANENT routine on this day/slot
    $occupied_routine_ids = [];
    $stmt = $db->prepare("
        SELECT room_id FROM routine_assignments 
        WHERE routine_draft_id = ? AND day_of_week = ? AND slot_id = ?
    ");
    $stmt->bind_param("isi", $routine_id, $target_day, $slot_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $occupied_routine_ids[] = (int)$row['room_id'];
    }
    
    // 3. Get rooms occupied by INCOMING reschedules (Moved TO this date/slot)
    $occupied_reschedule_ids = [];
    $stmt = $db->prepare("
        SELECT new_room_id FROM class_reschedules 
        WHERE new_date = ? AND new_slot_id = ? AND status = 'active'
    ");
    $stmt->bind_param("si", $target_date, $slot_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $occupied_reschedule_ids[] = (int)$row['new_room_id'];
    }
    
    // 4. Get rooms FREED by OUTGOING reschedules (Moved FROM this date/slot)
    // These are rooms that are normally occupied by a routine class, but that class was moved.
    $freed_routine_ids = [];
    $stmt = $db->prepare("
        SELECT ra.room_id 
        FROM class_reschedules cr
        JOIN routine_assignments ra ON cr.routine_assignment_id = ra.id
        WHERE cr.original_date = ? AND ra.day_of_week = ? AND ra.slot_id = ? AND cr.status = 'active'
    ");
    $stmt->bind_param("ssi", $target_date, $target_day, $slot_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $freed_routine_ids[] = (int)$row['room_id'];
    }
    
    // Filter available rooms
    $available_rooms = [];
    foreach ($all_rooms as $room) {
        $room_id = (int)$room['id'];
        
        // Logic:
        // A room is UNAVAILABLE if:
        // 1. It is occupied by an incoming reschedule (Takes precedence over everything)
        // 2. OR (It is occupied by routine AND NOT freed by an outgoing reschedule)
        
        $is_occupied_by_incoming = in_array($room_id, $occupied_reschedule_ids);
        $is_occupied_by_routine = in_array($room_id, $occupied_routine_ids);
        $is_freed = in_array($room_id, $freed_routine_ids);
        
        if ($is_occupied_by_incoming) {
            // Definitely taken
            continue;
        }
        
        if ($is_occupied_by_routine && !$is_freed) {
            // Still taken by original class
            continue;
        }
        
        // If we get here, it's available
        $available_rooms[] = [
            'id' => $room_id,
            'code' => $room['code'],
            'name' => $room['name'],
            'building' => $room['building'] ?? '',
            'capacity' => (int)$room['capacity']
        ];
    }
    
    // Sort by Room Code
    usort($available_rooms, function($a, $b) {
        return strnatcasecmp($a['code'], $b['code']);
    });
    
    return $available_rooms;
}

try {
    ob_clean();
    
    if (!is_logged_in() || $_SESSION['role'] !== 'teacher') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    
    // Get teacher ID and department
    $stmt = $db->prepare("SELECT t.id, t.department_id FROM teachers t WHERE t.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $teacher = $stmt->get_result()->fetch_assoc();
    
    if (!$teacher) {
        http_response_code(400);
        echo json_encode(['error' => 'Teacher profile not found']);
        exit;
    }
    
    $teacher_id = $teacher['id'];
    $department_id = $teacher['department_id'];

    $action = $_GET['action'] ?? '';

    // =============================================
    // GET SLOTS
    // =============================================
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_slots') {
        $slots = [];
        $result = $db->query("SELECT * FROM routine_slots WHERE is_active = 1 ORDER BY slot_order ASC");
        while ($row = $result->fetch_assoc()) {
            $slots[] = [
                'id' => (int)$row['id'],
                'start_time' => substr($row['start_time'], 0, 5),
                'end_time' => substr($row['end_time'], 0, 5),
                'label' => $row['label'],
                'slot_type' => $row['slot_type'],
                'slot_order' => (int)$row['slot_order']
            ];
        }
        echo json_encode($slots);
    }

    // =============================================
    // GET MY ROUTINE (Teacher's own classes only)
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_my_routine') {
        // Get the latest draft/published routine for teacher's department
        $stmt = $db->prepare("
            SELECT rd.id, rd.status, rd.draft_name, rd.published_at, s.name as semester_name
            FROM routine_drafts rd
            JOIN semesters s ON rd.semester_id = s.id
            WHERE rd.department_id = ? 
              AND rd.status IN ('draft', 'published')
            ORDER BY rd.created_at DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $routine = $stmt->get_result()->fetch_assoc();
        
        if (!$routine) {
            echo json_encode([
                'routine' => null,
                'assignments' => [],
                'message' => 'No routine has been created yet'
            ]);
            exit;
        }
        
        // Get teacher's assignments in this routine
        $stmt = $db->prepare("
            SELECT ra.*, 
                   c.course_code, c.course_name, c.course_type,
                   co.section,
                   r.code as room_code, r.name as room_name, r.building,
                   rs.label as slot_label, rs.slot_type, rs.start_time, rs.end_time
            FROM routine_assignments ra
            JOIN course_offerings co ON ra.course_offering_id = co.id
            JOIN courses c ON co.course_id = c.id
            JOIN rooms r ON ra.room_id = r.id
            JOIN routine_slots rs ON ra.slot_id = rs.id
            JOIN teacher_courses tc ON co.id = tc.course_offering_id
            WHERE ra.routine_draft_id = ? AND tc.teacher_id = ?
            ORDER BY FIELD(ra.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'), rs.slot_order
        ");
        $stmt->bind_param("ii", $routine['id'], $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $assignments = [];
        while ($row = $result->fetch_assoc()) {
            $assignments[] = [
                'id' => (int)$row['id'],
                'slot_id' => (int)$row['slot_id'],
                'room_id' => (int)$row['room_id'],
                'day_of_week' => $row['day_of_week'],
                'course_offering_id' => (int)$row['course_offering_id'],
                'course_code' => $row['course_code'],
                'course_name' => $row['course_name'],
                'course_type' => $row['course_type'],
                'section' => $row['section'],
                'room_code' => $row['room_code'],
                'room_name' => $row['room_name'],
                'building' => $row['building'],
                'slot_label' => $row['slot_label'],
                'slot_type' => $row['slot_type'],
                'start_time' => substr($row['start_time'], 0, 5),
                'end_time' => substr($row['end_time'], 0, 5),
                'is_mine' => true
            ];
        }
        
        // Count classes per week
        $class_count = count($assignments);
        
        echo json_encode([
            'routine' => $routine,
            'assignments' => $assignments,
            'class_count' => $class_count,
            'teacher_id' => $teacher_id
        ]);
    }

    // =============================================
    // GET FULL ROUTINE (All department classes)
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_full_routine') {
        // Get the latest draft/published routine for teacher's department
        $stmt = $db->prepare("
            SELECT rd.id, rd.status, rd.draft_name, rd.published_at, s.name as semester_name
            FROM routine_drafts rd
            JOIN semesters s ON rd.semester_id = s.id
            WHERE rd.department_id = ? 
              AND rd.status IN ('draft', 'published')
            ORDER BY rd.created_at DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $routine = $stmt->get_result()->fetch_assoc();
        
        if (!$routine) {
            echo json_encode([
                'routine' => null,
                'assignments' => [],
                'message' => 'No routine has been created yet'
            ]);
            exit;
        }
        
        // Get ALL assignments in this routine with teacher info
        $stmt = $db->prepare("
            SELECT ra.*, 
                   c.course_code, c.course_name, c.course_type,
                   co.section,
                   r.code as room_code, r.name as room_name, r.building,
                   rs.label as slot_label, rs.slot_type, rs.start_time, rs.end_time,
                   t.id as assigned_teacher_id,
                   CONCAT(up.first_name, ' ', up.last_name) as teacher_name
            FROM routine_assignments ra
            JOIN course_offerings co ON ra.course_offering_id = co.id
            JOIN courses c ON co.course_id = c.id
            JOIN rooms r ON ra.room_id = r.id
            JOIN routine_slots rs ON ra.slot_id = rs.id
            LEFT JOIN teacher_courses tc ON co.id = tc.course_offering_id
            LEFT JOIN teachers t ON tc.teacher_id = t.id
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE ra.routine_draft_id = ?
            ORDER BY FIELD(ra.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'), rs.slot_order
        ");
        $stmt->bind_param("i", $routine['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $assignments = [];
        $my_class_count = 0;
        while ($row = $result->fetch_assoc()) {
            $is_mine = ((int)$row['assigned_teacher_id'] === $teacher_id);
            if ($is_mine) $my_class_count++;
            
            $assignments[] = [
                'id' => (int)$row['id'],
                'slot_id' => (int)$row['slot_id'],
                'room_id' => (int)$row['room_id'],
                'day_of_week' => $row['day_of_week'],
                'course_offering_id' => (int)$row['course_offering_id'],
                'course_code' => $row['course_code'],
                'course_name' => $row['course_name'],
                'course_type' => $row['course_type'],
                'section' => $row['section'],
                'room_code' => $row['room_code'],
                'room_name' => $row['room_name'],
                'building' => $row['building'],
                'slot_label' => $row['slot_label'],
                'slot_type' => $row['slot_type'],
                'start_time' => substr($row['start_time'], 0, 5),
                'end_time' => substr($row['end_time'], 0, 5),
                'teacher_name' => $row['teacher_name'] ?? 'TBA',
                'assigned_teacher_id' => (int)$row['assigned_teacher_id'],
                'is_mine' => $is_mine
            ];
        }
        
        echo json_encode([
            'routine' => $routine,
            'assignments' => $assignments,
            'class_count' => count($assignments),
            'my_class_count' => $my_class_count,
            'teacher_id' => $teacher_id
        ]);
    }

    // =============================================
    // GET ASSIGNMENT DETAILS
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_assignment') {
        $assignment_id = intval($_GET['id'] ?? 0);
        
        if (!$assignment_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Assignment ID required']);
            exit;
        }
        
        $stmt = $db->prepare("
            SELECT ra.*, 
                   c.course_code, c.course_name, c.course_type,
                   co.section,
                   r.code as room_code, r.name as room_name, r.building,
                   rs.label as slot_label, rs.slot_type, rs.start_time, rs.end_time,
                   CONCAT(up.first_name, ' ', up.last_name) as teacher_name
            FROM routine_assignments ra
            JOIN course_offerings co ON ra.course_offering_id = co.id
            JOIN courses c ON co.course_id = c.id
            JOIN rooms r ON ra.room_id = r.id
            JOIN routine_slots rs ON ra.slot_id = rs.id
            JOIN teacher_courses tc ON co.id = tc.course_offering_id
            JOIN teachers t ON tc.teacher_id = t.id
            JOIN user_profiles up ON t.user_id = up.user_id
            WHERE ra.id = ? AND tc.teacher_id = ?
        ");
        $stmt->bind_param("ii", $assignment_id, $teacher_id);
        $stmt->execute();
        $assignment = $stmt->get_result()->fetch_assoc();
        
        if (!$assignment) {
            http_response_code(404);
            echo json_encode(['error' => 'Assignment not found']);
            exit;
        }
        
        echo json_encode($assignment);
    }

    // =============================================
    // SUBMIT CHANGE REQUEST
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'submit_change_request') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $assignment_id = intval($data['assignment_id'] ?? 0);
        $message = trim($data['message'] ?? '');
        
        if (!$assignment_id || empty($message)) {
            http_response_code(400);
            echo json_encode(['error' => 'Assignment ID and message are required']);
            exit;
        }
        
        // Verify teacher owns this assignment
        $verify_stmt = $db->prepare("
            SELECT ra.id FROM routine_assignments ra
            JOIN course_offerings co ON ra.course_offering_id = co.id
            JOIN teacher_courses tc ON co.id = tc.course_offering_id
            WHERE ra.id = ? AND tc.teacher_id = ?
        ");
        $verify_stmt->bind_param("ii", $assignment_id, $teacher_id);
        $verify_stmt->execute();
        
        if ($verify_stmt->get_result()->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have permission to request changes for this assignment']);
            exit;
        }
        
        // Check if there's already a pending request
        $check_stmt = $db->prepare("SELECT id FROM routine_change_requests WHERE routine_assignment_id = ? AND requested_by = ? AND status = 'pending'");
        $check_stmt->bind_param("ii", $assignment_id, $user_id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'You already have a pending change request for this slot']);
            exit;
        }
        
        // Insert change request
        $stmt = $db->prepare("INSERT INTO routine_change_requests (routine_assignment_id, requested_by, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $assignment_id, $user_id, $message);
        
        if ($stmt->execute()) {
            // Notify admin(s)
            $admin_stmt = $db->prepare("
                SELECT da.user_id FROM department_admins da
                JOIN teachers t ON da.department_id = t.department_id
                WHERE t.id = ?
            ");
            $admin_stmt->bind_param("i", $teacher_id);
            $admin_stmt->execute();
            $admins = $admin_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Get teacher name
            $name_stmt = $db->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM user_profiles WHERE user_id = ?");
            $name_stmt->bind_param("i", $user_id);
            $name_stmt->execute();
            $teacher_name = $name_stmt->get_result()->fetch_assoc()['name'] ?? 'A teacher';
            
            $title = "Routine Change Request";
            $notif_message = "$teacher_name has requested a change to their class schedule.";
            
            $notif_stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'alert')");
            
            foreach ($admins as $admin) {
                $notif_stmt->bind_param("iss", $admin['user_id'], $title, $notif_message);
                $notif_stmt->execute();
            }
            
            echo json_encode(['success' => true, 'message' => 'Change request submitted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to submit change request']);
        }
    }

    // =============================================
    // GET MY CHANGE REQUESTS
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_my_requests') {
        $stmt = $db->prepare("
            SELECT rcr.*, 
                   ra.day_of_week,
                   c.course_code, c.course_name,
                   rs.label as slot_label, rs.start_time, rs.end_time, 
                   r.code as room_code
            FROM routine_change_requests rcr
            JOIN routine_assignments ra ON rcr.routine_assignment_id = ra.id
            JOIN course_offerings co ON ra.course_offering_id = co.id
            JOIN courses c ON co.course_id = c.id
            JOIN routine_slots rs ON ra.slot_id = rs.id
            JOIN rooms r ON ra.room_id = r.id
            WHERE rcr.requested_by = ?
            ORDER BY rcr.created_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $requests = [];
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
        
        echo json_encode($requests);
    }

    // =============================================
    // GET EMPTY SLOTS FOR DATE (For reschedule)
    // Shows slots where teacher is free on a specific date
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_empty_slots') {
        $assignment_id = intval($_GET['assignment_id'] ?? 0);
        $target_date = trim($_GET['date'] ?? '');
        
        if (!$assignment_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Assignment ID required']);
            exit;
        }
        
        // If no date provided, use today
        if (empty($target_date)) {
            $target_date = date('Y-m-d');
        }
        
        // Validate date format
        $date_obj = DateTime::createFromFormat('Y-m-d', $target_date);
        if (!$date_obj) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD']);
            exit;
        }
        
        // Get day of week from date
        $target_day = $date_obj->format('l'); // "Sunday", "Monday", etc.
        
        // Verify teacher owns this assignment
        $verify_stmt = $db->prepare("
            SELECT ra.id, ra.routine_draft_id, ra.room_id, ra.day_of_week, ra.slot_id, c.course_type
            FROM routine_assignments ra
            JOIN course_offerings co ON ra.course_offering_id = co.id
            JOIN courses c ON co.course_id = c.id
            JOIN teacher_courses tc ON co.id = tc.course_offering_id
            WHERE ra.id = ? AND tc.teacher_id = ?
        ");
        $verify_stmt->bind_param("ii", $assignment_id, $teacher_id);
        $verify_stmt->execute();
        $assignment = $verify_stmt->get_result()->fetch_assoc();
        
        if (!$assignment) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have permission to reschedule this class']);
            exit;
        }
        
        $routine_id = $assignment['routine_draft_id'];
        $course_type = $assignment['course_type'];
        $original_slot_id = $assignment['slot_id'];
        
        // Get all slots
        $slots = [];
        $slot_result = $db->query("SELECT * FROM routine_slots WHERE is_active = 1 ORDER BY slot_order ASC");
        while ($row = $slot_result->fetch_assoc()) {
            $slots[$row['id']] = $row;
        }
        
        // Get teacher's regular schedule for this day (permanent routine)
        $teacher_busy = [];
        $stmt = $db->prepare("
            SELECT ra.slot_id 
            FROM routine_assignments ra
            JOIN course_offerings co ON ra.course_offering_id = co.id
            JOIN teacher_courses tc ON co.id = tc.course_offering_id
            WHERE ra.routine_draft_id = ? AND ra.day_of_week = ? AND tc.teacher_id = ? AND ra.id != ?
        ");
        $stmt->bind_param("isii", $routine_id, $target_day, $teacher_id, $assignment_id);
        $stmt->execute();
        $busy_result = $stmt->get_result();
        while ($row = $busy_result->fetch_assoc()) {
            $teacher_busy[$row['slot_id']] = true;
        }
        
        // Check for existing reschedules affecting teacher on this date
        $stmt = $db->prepare("
            SELECT cr.new_slot_id 
            FROM class_reschedules cr
            JOIN routine_assignments ra ON cr.routine_assignment_id = ra.id
            JOIN course_offerings co ON ra.course_offering_id = co.id
            JOIN teacher_courses tc ON co.id = tc.course_offering_id
            WHERE cr.new_date = ? AND tc.teacher_id = ? AND cr.status = 'active'
        ");
        $stmt->bind_param("si", $target_date, $teacher_id);
        $stmt->execute();
        $reschedule_result = $stmt->get_result();
        while ($row = $reschedule_result->fetch_assoc()) {
            $teacher_busy[$row['new_slot_id']] = true;
        }
        
        // Find available slots where teacher is free
        $available_slots = [];
        
        foreach ($slots as $slot) {
            // Skip break slots
            if ($slot['slot_type'] === 'break') continue;
            
            // Check if slot type matches course type (lab course needs lab slot)
            if ($course_type === 'lab' && $slot['slot_type'] !== 'lab') continue;
            if ($course_type === 'theory' && $slot['slot_type'] !== 'theory') continue;
            
            // Skip if teacher is busy at this slot
            if (isset($teacher_busy[$slot['id']])) continue;
            
            // Count available rooms for this slot on this date
            $room_count = countAvailableRooms($db, $routine_id, $target_date, $target_day, $slot['id'], $course_type);
            
            if ($room_count > 0) {
                $available_slots[] = [
                    'slot_id' => (int)$slot['id'],
                    'slot_label' => $slot['label'],
                    'start_time' => substr($slot['start_time'], 0, 5),
                    'end_time' => substr($slot['end_time'], 0, 5),
                    'slot_type' => $slot['slot_type'],
                    'available_rooms' => $room_count
                ];
            }
        }
        
        echo json_encode([
            'date' => $target_date,
            'day_of_week' => $target_day,
            'available_slots' => $available_slots
        ]);
    }

    // =============================================
    // GET AVAILABLE ROOMS FOR SLOT
    // Returns rooms that are free at a specific slot on a specific date
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_available_rooms') {
        $slot_id = intval($_GET['slot_id'] ?? 0);
        $target_date = trim($_GET['date'] ?? '');
        $course_type = trim($_GET['course_type'] ?? 'theory');
        
        if (!$slot_id || empty($target_date)) {
            http_response_code(400);
            echo json_encode(['error' => 'Slot ID and date are required']);
            exit;
        }
        
        // Get day of week from date
        $date_obj = DateTime::createFromFormat('Y-m-d', $target_date);
        if (!$date_obj) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid date format']);
            exit;
        }
        $target_day = $date_obj->format('l');
        
        // Get routine ID
        $stmt = $db->prepare("
            SELECT rd.id FROM routine_drafts rd
            WHERE rd.department_id = ? AND rd.status IN ('draft', 'published')
            ORDER BY rd.created_at DESC LIMIT 1
        ");
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $routine = $stmt->get_result()->fetch_assoc();
        $routine_id = $routine['id'] ?? 0;
        
        $available_rooms = getAvailableRooms($db, $routine_id, $target_date, $target_day, $slot_id, $course_type);
        
        echo json_encode(['rooms' => $available_rooms]);
    }

    // =============================================
    // RESCHEDULE CLASS (Date-specific, not permanent)
    // Creates a reschedule record for a specific date only
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reschedule_class') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $assignment_id = intval($data['assignment_id'] ?? 0);
        $original_date = trim($data['original_date'] ?? '');
        $new_date = trim($data['new_date'] ?? '');
        $new_slot_id = intval($data['new_slot_id'] ?? 0);
        $new_room_id = intval($data['new_room_id'] ?? 0);
        $reason = trim($data['reason'] ?? '');
        
        if (!$assignment_id || empty($original_date) || empty($new_date) || !$new_slot_id || !$new_room_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Assignment ID, original date, new date, slot, and room are required']);
            exit;
        }
        
        // Validate dates
        $orig_date_obj = DateTime::createFromFormat('Y-m-d', $original_date);
        $new_date_obj = DateTime::createFromFormat('Y-m-d', $new_date);
        if (!$orig_date_obj || !$new_date_obj) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid date format']);
            exit;
        }
        
        $new_day = $new_date_obj->format('l');
        
        // Verify teacher owns this assignment and get details
        $verify_stmt = $db->prepare("
            SELECT ra.*, c.course_code, c.course_name, c.course_type, co.id as co_id, 
                   rs.start_time as old_start, rs.end_time as old_end
            FROM routine_assignments ra
            JOIN course_offerings co ON ra.course_offering_id = co.id
            JOIN courses c ON co.course_id = c.id
            JOIN routine_slots rs ON ra.slot_id = rs.id
            JOIN teacher_courses tc ON co.id = tc.course_offering_id
            WHERE ra.id = ? AND tc.teacher_id = ?
        ");
        $verify_stmt->bind_param("ii", $assignment_id, $teacher_id);
        $verify_stmt->execute();
        $old_assignment = $verify_stmt->get_result()->fetch_assoc();
        
        if (!$old_assignment) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }
        
        $routine_id = $old_assignment['routine_draft_id'];
        $course_offering_id = $old_assignment['co_id'];
        $course_type = $old_assignment['course_type'];
        $old_day = $old_assignment['day_of_week'];
        
        // Get new slot info
        $slot_stmt = $db->prepare("SELECT * FROM routine_slots WHERE id = ?");
        $slot_stmt->bind_param("i", $new_slot_id);
        $slot_stmt->execute();
        $new_slot = $slot_stmt->get_result()->fetch_assoc();
        
        if (!$new_slot) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid slot']);
            exit;
        }
        
        // Check if room is available at the new slot on the new date
        $available_rooms = getAvailableRooms($db, $routine_id, $new_date, $new_day, $new_slot_id, $course_type);
        $room_available = false;
        foreach ($available_rooms as $room) {
            if ($room['id'] === $new_room_id) {
                $room_available = true;
                break;
            }
        }
        
        if (!$room_available) {
            http_response_code(409);
            echo json_encode(['error' => 'Selected room is not available at this time']);
            exit;
        }
        
        // Check if teacher is free at new slot on new date
        $teacher_busy_stmt = $db->prepare("
            SELECT ra.id FROM routine_assignments ra
            JOIN course_offerings co ON ra.course_offering_id = co.id
            JOIN teacher_courses tc ON co.id = tc.course_offering_id
            WHERE ra.routine_draft_id = ? AND ra.day_of_week = ? AND ra.slot_id = ? 
              AND tc.teacher_id = ? AND ra.id != ?
        ");
        $teacher_busy_stmt->bind_param("isiii", $routine_id, $new_day, $new_slot_id, $teacher_id, $assignment_id);
        $teacher_busy_stmt->execute();
        if ($teacher_busy_stmt->get_result()->num_rows > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'You already have another class at this time']);
            exit;
        }
        
        // Check for existing reschedule on the original date
        $existing_stmt = $db->prepare("
            SELECT id FROM class_reschedules 
            WHERE routine_assignment_id = ? AND original_date = ? AND status = 'active'
        ");
        $existing_stmt->bind_param("is", $assignment_id, $original_date);
        $existing_stmt->execute();
        $existing = $existing_stmt->get_result()->fetch_assoc();
        
        if ($existing) {
            // Update existing reschedule
            $update_stmt = $db->prepare("
                UPDATE class_reschedules 
                SET new_date = ?, new_slot_id = ?, new_room_id = ?, reason = ?
                WHERE id = ?
            ");
            $update_stmt->bind_param("siisi", $new_date, $new_slot_id, $new_room_id, $reason, $existing['id']);
            $success = $update_stmt->execute();
        } else {
            // Create new reschedule record
            $insert_stmt = $db->prepare("
                INSERT INTO class_reschedules 
                (routine_assignment_id, original_date, new_date, new_slot_id, new_room_id, teacher_id, reason)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $insert_stmt->bind_param("issiiss", $assignment_id, $original_date, $new_date, $new_slot_id, $new_room_id, $teacher_id, $reason);
            $success = $insert_stmt->execute();
        }
        
        if ($success) {
            // Notify enrolled students
            $students_stmt = $db->prepare("
                SELECT e.student_id, s.user_id
                FROM enrollments e
                JOIN students s ON e.student_id = s.id
                WHERE e.course_offering_id = ?
            ");
            $students_stmt->bind_param("i", $course_offering_id);
            $students_stmt->execute();
            $students = $students_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Get teacher name
            $name_stmt = $db->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM user_profiles WHERE user_id = ?");
            $name_stmt->bind_param("i", $user_id);
            $name_stmt->execute();
            $teacher_name = $name_stmt->get_result()->fetch_assoc()['name'] ?? 'Your teacher';
            
            // Format dates for notification
            $orig_formatted = $orig_date_obj->format('M j, Y');
            $new_formatted = $new_date_obj->format('M j, Y');
            
            $title = "Class Rescheduled: {$old_assignment['course_code']}";
            $message = "{$old_assignment['course_name']} on {$orig_formatted} has been rescheduled to {$new_formatted} ({$new_slot['start_time']}-{$new_slot['end_time']}).";
            if (!empty($reason)) {
                $message .= " Reason: {$reason}";
            }
            
            $notif_stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'info')");
            
            foreach ($students as $student) {
                $notif_stmt->bind_param("iss", $student['user_id'], $title, $message);
                $notif_stmt->execute();
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Class rescheduled for ' . $new_formatted . '. ' . count($students) . ' students notified.',
                'students_notified' => count($students)
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to reschedule class']);
        }
    }

    else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

ob_end_flush();
