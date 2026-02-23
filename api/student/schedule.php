<?php
/**
 * Student Schedule API
 * Handles fetching events for students
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in() || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$student_id = 0;

// Get student ID
$stmt = $db->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $student_id = $row['id'];
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Student profile not found']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_events') {
    $start = $_GET['start'] ?? date('Y-m-01');
    $end = $_GET['end'] ?? date('Y-m-t');

    $events = [];

    // 1. Fetch Regular Weekly Schedule for Enrolled Courses
    $stmt = $db->prepare("
        SELECT cs.*, c.course_code, c.course_name, co.section 
        FROM class_schedule cs
        JOIN course_offerings co ON cs.course_offering_id = co.id
        JOIN courses c ON co.course_id = c.id
        JOIN enrollments e ON co.id = e.course_offering_id
        WHERE e.student_id = ? AND e.status = 'enrolled'
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // 2. Fetch Rescheduled/Cancelled Exceptions for Enrolled Courses
    $stmt = $db->prepare("
        SELECT cr.* 
        FROM class_reschedules cr
        JOIN course_offerings co ON cr.course_offering_id = co.id
        JOIN enrollments e ON co.id = e.course_offering_id
        WHERE e.student_id = ? AND e.status = 'enrolled'
        AND (
            (cr.original_date BETWEEN ? AND ?) OR 
            (cr.new_date BETWEEN ? AND ?)
        )
        AND cr.status = 'active'
    ");
    $stmt->bind_param("issss", $student_id, $start, $end, $start, $end);
    $stmt->execute();
    $reschedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Map exceptions
    $exceptions = [];
    foreach ($reschedules as $r) {
        $key = $r['original_date'] . '_' . $r['course_offering_id'];
        $exceptions[$key] = $r;
    }

    // Generate Recurring Events
    $startDate = new DateTime($start);
    $endDate = new DateTime($end);
    
    $current = clone $startDate;
    while ($current <= $endDate) {
        $dayName = $current->format('l');
        $dateStr = $current->format('Y-m-d');

        foreach ($schedules as $sch) {
            if ($sch['day_of_week'] === $dayName) {
                $exceptionKey = $dateStr . '_' . $sch['course_offering_id'];
                
                if (isset($exceptions[$exceptionKey])) {
                    continue; 
                }

                $events[] = [
                    'id' => 'reg_' . $sch['id'] . '_' . $dateStr,
                    'title' => $sch['course_code'] . ' (' . $sch['section'] . ')',
                    'start' => $dateStr . 'T' . $sch['start_time'],
                    'end' => $dateStr . 'T' . $sch['end_time'],
                    'backgroundColor' => '#3b82f6', // blue-500
                    'borderColor' => '#2563eb',
                    'extendedProps' => [
                        'type' => 'regular',
                        'course_name' => $sch['course_name'],
                        'room' => $sch['room_number'] ?? 'TBA',
                        'course_offering_id' => $sch['course_offering_id']
                    ]
                ];
            }
        }
        $current->modify('+1 day');
    }

    // Add Rescheduled Events
    foreach ($reschedules as $r) {
        // Find course details
        $courseCode = 'Class';
        $section = '';
        foreach ($schedules as $s) {
            if ($s['course_offering_id'] == $r['course_offering_id']) {
                $courseCode = $s['course_code'];
                $section = $s['section'];
                break;
            }
        }

        $events[] = [
            'id' => 'res_' . $r['id'],
            'title' => $courseCode . ' (' . $section . ') - Rescheduled',
            'start' => $r['new_date'] . 'T' . $r['new_start_time'],
            'end' => $r['new_date'] . 'T' . $r['new_end_time'],
            'backgroundColor' => '#f59e0b', // amber-500
            'borderColor' => '#d97706',
            'extendedProps' => [
                'type' => 'rescheduled',
                'room' => $r['room_number'] ?? 'TBA',
                'reason' => $r['reason'],
                'course_offering_id' => $r['course_offering_id']
            ]
        ];
    }

    echo json_encode($events);

} else {
    http_response_code(404);
}
