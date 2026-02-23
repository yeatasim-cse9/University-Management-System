<?php
/**
 * Student Routine API
 * ACADEMIX - Academic Management System
 * 
 * Handles fetching routine data for students based on their enrollments
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

try {
    ob_clean();
    
    if (!is_logged_in() || $_SESSION['role'] !== 'student') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    
    // Get student ID and department
    $stmt = $db->prepare("SELECT s.id, s.department_id FROM students s WHERE s.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    
    if (!$student) {
        http_response_code(400);
        echo json_encode(['error' => 'Student profile not found']);
        exit;
    }
    
    $student_id = $student['id'];
    $department_id = $student['department_id'];

    $action = $_GET['action'] ?? '';

    // =============================================
    // GET MY ROUTINE (Student's enrolled courses)
    // =============================================
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_my_routine') {
        
        // Get slots
        $slots = [];
        $slot_result = $db->query("SELECT * FROM routine_slots WHERE is_active = 1 ORDER BY slot_order ASC");
        while ($row = $slot_result->fetch_assoc()) {
            $slots[] = [
                'id' => (int)$row['id'],
                'start_time' => substr($row['start_time'], 0, 5),
                'end_time' => substr($row['end_time'], 0, 5),
                'label' => $row['label'],
                'slot_type' => $row['slot_type'],
                'slot_order' => (int)$row['slot_order']
            ];
        }
        
        // Get the latest draft/published routine for student's department
        $stmt = $db->prepare("
            SELECT rd.id, rd.status, rd.draft_name, rd.published_at, s.name as semester_name
            FROM routine_drafts rd
            JOIN semesters s ON rd.semester_id = s.id
            WHERE rd.department_id = ? 
              AND rd.status = 'published'
            ORDER BY rd.created_at DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $routine = $stmt->get_result()->fetch_assoc();
        
        if (!$routine) {
            echo json_encode([
                'routine' => null,
                'slots' => $slots,
                'assignments' => [],
                'enrolled_course_offerings' => [],
                'message' => 'No routine has been created yet'
            ]);
            exit;
        }
        
        // Get student's enrolled course offering IDs
        $enrolled_stmt = $db->prepare("
            SELECT e.course_offering_id 
            FROM enrollments e
            WHERE e.student_id = ?
        ");
        $enrolled_stmt->bind_param("i", $student_id);
        $enrolled_stmt->execute();
        $enrolled_result = $enrolled_stmt->get_result();
        
        $enrolled_course_offerings = [];
        while ($row = $enrolled_result->fetch_assoc()) {
            $enrolled_course_offerings[] = (int)$row['course_offering_id'];
        }
        
        // Get ALL assignments in this routine (we'll filter on frontend)
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
        $enrolled_class_count = 0;
        
        // Helper to formatting details
        while ($row = $result->fetch_assoc()) {
            $is_enrolled = in_array((int)$row['course_offering_id'], $enrolled_course_offerings);
            if ($is_enrolled) $enrolled_class_count++;
            
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
                'teacher_name' => $row['teacher_name'] ?? 'TBA'
            ];
        }
        
        // FETCH ACTIVE RESCHEDULES FOR ENROLLED COURSES
        $reschedules = [];
        if (!empty($enrolled_course_offerings)) {
            $placeholders = implode(',', array_fill(0, count($enrolled_course_offerings), '?'));
            $reschedule_query = "
                SELECT cr.*, 
                       ra.course_offering_id,
                       rs.start_time, rs.end_time, rs.label as slot_label,
                       r.code as new_room_code
                FROM class_reschedules cr
                JOIN routine_assignments ra ON cr.routine_assignment_id = ra.id
                JOIN routine_slots rs ON cr.new_slot_id = rs.id
                JOIN rooms r ON cr.new_room_id = r.id
                WHERE ra.course_offering_id IN ($placeholders)
                  AND cr.status = 'active'
                  AND cr.new_date >= CURDATE()
                ORDER BY cr.new_date ASC
            ";
            
            $stmt = $db->prepare($reschedule_query);
            $stmt->bind_param(str_repeat('i', count($enrolled_course_offerings)), ...$enrolled_course_offerings);
            $stmt->execute();
            $res_result = $stmt->get_result();
            while ($row = $res_result->fetch_assoc()) {
                $reschedules[] = [
                    'original_routine_id' => (int)$row['routine_assignment_id'],
                    'original_date' => $row['original_date'],
                    'new_date' => $row['new_date'],
                    'new_slot_id' => (int)$row['new_slot_id'],
                    'new_room_code' => $row['new_room_code'],
                    'reason' => $row['reason'],
                    'start_time' => substr($row['start_time'], 0, 5),
                    'end_time' => substr($row['end_time'], 0, 5)
                ];
            }
        }
        
        echo json_encode([
            'routine' => $routine,
            'slots' => $slots,
            'assignments' => $assignments,
            'reschedules' => $reschedules,
            'enrolled_course_offerings' => $enrolled_course_offerings,
            'enrolled_class_count' => $enrolled_class_count
        ]);
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
