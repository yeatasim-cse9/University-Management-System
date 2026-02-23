<?php
/**
 * Teacher Schedule API
 * Handles fetching events and rescheduling classes
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Start output buffering to catch any errors
ob_start();

// Set JSON header
header('Content-Type: application/json');

// Error handling to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Clear any buffered output that might have accumulated
    ob_clean();
    
    if (!is_logged_in() || $_SESSION['role'] !== 'teacher') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

$user_id = $_SESSION['user_id'];
$teacher_id = 0;

// Get teacher ID
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $teacher_id = $row['id'];
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Teacher profile not found']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_events') {
    $start = $_GET['start'] ?? date('Y-m-01');
    $end = $_GET['end'] ?? date('Y-m-t');

    $events = [];

    // 1. Fetch Regular Weekly Schedule
    // We need to generate events for each week between start and end
    $stmt = $db->prepare("
        SELECT cs.*, c.course_code, c.course_name, c.course_type, co.section, s.semester_number 
        FROM class_schedule cs
        JOIN course_offerings co ON cs.course_offering_id = co.id
        JOIN courses c ON co.course_id = c.id
        JOIN teacher_courses tc ON co.id = tc.course_offering_id
        JOIN semesters s ON co.semester_id = s.id
        JOIN academic_years ay ON s.academic_year_id = ay.id
        WHERE tc.teacher_id = ? AND ay.year >= '2020'
    ");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // 2. Fetch Rescheduled/Cancelled Exceptions
    $stmt = $db->prepare("
        SELECT cr.* 
        FROM class_reschedules cr
        JOIN course_offerings co ON cr.course_offering_id = co.id
        JOIN teacher_courses tc ON co.id = tc.course_offering_id
        WHERE tc.teacher_id = ? 
        AND (
            (cr.original_date BETWEEN ? AND ?) OR 
            (cr.new_date BETWEEN ? AND ?)
        )
        AND cr.status = 'active'
    ");
    $stmt->bind_param("issss", $teacher_id, $start, $end, $start, $end);
    $stmt->execute();
    $reschedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Map exceptions for easy lookup: original_date + course_offering_id -> reschedule_record
    $exceptions = [];
    foreach ($reschedules as $r) {
        $key = $r['original_date'] . '_' . $r['course_offering_id'];
        $exceptions[$key] = $r;
    }

    // Generate Recurring Events
    $startDate = new DateTime($start);
    $endDate = new DateTime($end);
    
    // Iterate through each day in range
    $current = clone $startDate;
    while ($current <= $endDate) {
        $dayName = $current->format('l');
        $dateStr = $current->format('Y-m-d');

        foreach ($schedules as $sch) {
            if ($sch['day_of_week'] === $dayName) {
                $exceptionKey = $dateStr . '_' . $sch['course_offering_id'];
                
                // If there is an exception (reschedule) for this date and course
                if (isset($exceptions[$exceptionKey])) {
                    // This regular slot is cancelled/moved. 
                    continue; 
                }

                // Add Regular Event
                $events[] = [
                    'id' => 'reg_' . $sch['id'] . '_' . $dateStr,
                    'title' => $sch['course_code'],
                    'start' => $dateStr . 'T' . $sch['start_time'],
                    'end' => $dateStr . 'T' . $sch['end_time'],
                    'extendedProps' => [
                        'type' => 'regular',
                        'schedule_id' => $sch['id'],
                        'course_offering_id' => $sch['course_offering_id'],
                        'course_name' => $sch['course_name'],
                        'course_type' => $sch['course_type'],
                        'section' => $sch['section'],
                        'semester' => $sch['semester_number'],
                        'room' => $sch['room_number'] ?? 'TBA',
                        'description' => "Regular Class"
                    ]
                ];
            }
        }
        $current->modify('+1 day');
    }

    // Add Rescheduled Events (The "New" times)
    foreach ($reschedules as $r) {
        // Fetch course info for title (optimization: join in the query above)
        // For now, let's just find it in $schedules (inefficient if many courses, but fine here)
        $courseCode = 'Class';
        $section = '';
        $courseName = '';
        $courseType = 'theory';
        $semester = '';
        
        foreach ($schedules as $s) {
            if ($s['course_offering_id'] == $r['course_offering_id']) {
                $courseCode = $s['course_code'];
                $section = $s['section'];
                $courseName = $s['course_name'];
                $courseType = $s['course_type'];
                $semester = $s['semester_number'];
                break;
            }
        }

        $events[] = [
            'id' => 'res_' . $r['id'],
            'title' => $courseCode,
            'start' => $r['new_date'] . 'T' . $r['new_start_time'],
            'end' => $r['new_date'] . 'T' . $r['new_end_time'],
            'extendedProps' => [
                'type' => 'rescheduled',
                'reschedule_id' => $r['id'],
                'course_offering_id' => $r['course_offering_id'],
                'course_name' => $courseName,
                'course_type' => $courseType,
                'section' => $section,
                'semester' => $semester,
                'room' => $r['room_number'] ?? 'TBA',
                'reason' => $r['reason'],
                'original_date' => $r['original_date']
            ]
        ];
    }

    echo json_encode($events);

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'check_availability') {
    $date = $_GET['date'] ?? '';
    if (empty($date)) {
        http_response_code(400);
        echo json_encode(['error' => 'Date is required']);
        exit;
    }

    $dayName = date('l', strtotime($date));
    $occupied = [];

    // 1. Get Regular Schedule for this Day
    $stmt = $db->prepare("
        SELECT cs.*, c.course_code, co.section 
        FROM class_schedule cs
        JOIN course_offerings co ON cs.course_offering_id = co.id
        JOIN courses c ON co.course_id = c.id
        JOIN teacher_courses tc ON co.id = tc.course_offering_id
        WHERE tc.teacher_id = ? AND cs.day_of_week = ?
    ");
    $stmt->bind_param("is", $teacher_id, $dayName);
    $stmt->execute();
    $regular = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // 2. Get Reschedules ON this date (Incoming)
    $stmt = $db->prepare("
        SELECT cr.*, c.course_code, co.section 
        FROM class_reschedules cr
        JOIN course_offerings co ON cr.course_offering_id = co.id
        JOIN courses c ON co.course_id = c.id
        JOIN teacher_courses tc ON co.id = tc.course_offering_id
        WHERE tc.teacher_id = ? AND cr.new_date = ? AND cr.status = 'active'
    ");
    $stmt->bind_param("is", $teacher_id, $date);
    $stmt->execute();
    $incoming = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // 3. Get Cancellations/Reschedules FROM this date (Outgoing)
    // We need to exclude regular slots that have been moved AWAY from this date
    $stmt = $db->prepare("
        SELECT cr.course_offering_id 
        FROM class_reschedules cr
        JOIN course_offerings co ON cr.course_offering_id = co.id
        JOIN teacher_courses tc ON co.id = tc.course_offering_id
        WHERE tc.teacher_id = ? AND cr.original_date = ? AND cr.status = 'active'
    ");
    $stmt->bind_param("is", $teacher_id, $date);
    $stmt->execute();
    $outgoing = [];
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $outgoing[] = $row['course_offering_id'];
    }

    // Compile Occupied Slots
    foreach ($regular as $reg) {
        // If this regular slot is NOT in the outgoing list, it's occupied
        // Note: This logic assumes one slot per course per day for simplicity. 
        // If a course has multiple slots, we'd need to match exact times too, but unique course_offering constraint usually handles this.
        // Better robustness: Match course_offering_id + day is enough for single-slot-per-day models.
        if (!in_array($reg['course_offering_id'], $outgoing)) {
            $occupied[] = [
                'type' => 'regular',
                'start' => substr($reg['start_time'], 0, 5),
                'end' => substr($reg['end_time'], 0, 5),
                'title' => $reg['course_code'] . ' (' . $reg['section'] . ')',
                'description' => 'Regular Class'
            ];
        }
    }

    foreach ($incoming as $inc) {
        $occupied[] = [
            'type' => 'rescheduled',
            'start' => substr($inc['new_start_time'], 0, 5),
            'end' => substr($inc['new_end_time'], 0, 5),
            'title' => $inc['course_code'] . ' (' . $inc['section'] . ')',
            'description' => 'Rescheduled: ' . $inc['reason']
        ];
    }

    // Sort by start time
    usort($occupied, function($a, $b) {
        return strcmp($a['start'], $b['start']);
    });

    echo json_encode($occupied);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reschedule') {
    // Handle Rescheduling
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data']);
        exit;
    }

    $course_offering_id = $data['course_offering_id'];
    $original_date = $data['original_date'];
    $new_date = $data['new_date'];
    $new_start_time = $data['new_start_time'];
    $new_end_time = $data['new_end_time'];
    $room_number = $data['room_number'] ?? null;
    $reason = $data['reason'] ?? 'Teacher unavailable';
    
    // Validation
    if (empty($course_offering_id) || empty($original_date) || empty($new_date) || empty($new_start_time) || empty($new_end_time)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    // Verify teacher has access to this course
    $verify_stmt = $db->prepare("
        SELECT tc.id FROM teacher_courses tc 
        WHERE tc.teacher_id = ? AND tc.course_offering_id = ?
    ");
    $verify_stmt->bind_param("ii", $teacher_id, $course_offering_id);
    $verify_stmt->execute();
    if ($verify_stmt->get_result()->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['error' => 'You do not have permission to reschedule this course']);
        exit;
    }

    // Check if an active reschedule already exists for this slot
    $check_stmt = $db->prepare("SELECT id FROM class_reschedules WHERE course_offering_id = ? AND original_date = ? AND status = 'active'");
    $check_stmt->bind_param("is", $course_offering_id, $original_date);
    $check_stmt->execute();
    $existing = $check_stmt->get_result()->fetch_assoc();

    $ignore_id = $existing ? $existing['id'] : null;

    // PERFORM STRICT 3-WAY CONFLICT CHECK
    $conflict_check = check_schedule_conflicts(
        $course_offering_id, 
        $new_date, 
        $new_start_time, 
        $new_end_time, 
        $room_number, 
        $teacher_id, 
        $ignore_id
    );

    if (!$conflict_check['success']) {
        http_response_code(409); // Conflict
        echo json_encode(['error' => $conflict_check['error']]);
        exit;
    }

    if ($existing) {
        // UPDATE existing reschedule
        $stmt = $db->prepare("
            UPDATE class_reschedules 
            SET new_date = ?, new_start_time = ?, new_end_time = ?, room_number = ?, reason = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("sssssi", $new_date, $new_start_time, $new_end_time, $room_number, $reason, $existing['id']);
        $action_type = "updated";
    } else {
        // INSERT new reschedule
        $stmt = $db->prepare("
            INSERT INTO class_reschedules 
            (course_offering_id, original_date, new_date, new_start_time, new_end_time, room_number, reason, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issssssi", $course_offering_id, $original_date, $new_date, $new_start_time, $new_end_time, $room_number, $reason, $user_id);
        $action_type = "created";
    }
    
    if ($stmt->execute()) {
        // Send Notifications to Students
        // 1. Get all students enrolled in this course
        $enrolled_stmt = $db->prepare("
            SELECT e.student_id, s.user_id FROM enrollments e
            JOIN students s ON e.student_id = s.id
            WHERE e.course_offering_id = ? AND e.status = 'enrolled'
        ");
        $enrolled_stmt->bind_param("i", $course_offering_id);
        $enrolled_stmt->execute();
        $students = $enrolled_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // 2. Prepare Notification
        $title = "Class Rescheduled";
        $message = "Your class originally on $original_date has been rescheduled to $new_date ($new_start_time - $new_end_time). Reason: $reason";
        
        // 3. Batch insert notifications
        $notif_stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'alert')");
        
        foreach ($students as $student) {
            $notif_stmt->bind_param("iss", $student['user_id'], $title, $message);
            $notif_stmt->execute();
        }

        echo json_encode(['success' => true, 'message' => 'Class reschedule ' . $action_type . ' and students notified']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $db->error]);
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found or method not allowed']);
}

} catch (Exception $e) {
    // Clear buffer and send error as JSON
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

// End output buffering
ob_end_flush();
