<?php
/**
 * Admin Routine Management API
 * ACADEMIX - Academic Management System
 * 
 * Handles slot configuration, class assignments, draft/publish workflow
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
    
    if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    
    // Get admin's department(s)
    $stmt = $db->prepare("SELECT d.id FROM departments d JOIN department_admins da ON d.id = da.department_id WHERE da.user_id = ? AND d.deleted_at IS NULL");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $department_ids = [];
    while ($row = $result->fetch_assoc()) {
        $department_ids[] = $row['id'];
    }
    $dept_id_list = !empty($department_ids) ? implode(',', $department_ids) : '0';
    $dept_id = $department_ids[0] ?? 0;

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
    // SAVE SLOTS
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_slots') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['slots'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data']);
            exit;
        }

        $db->begin_transaction();
        
        try {
            // Deactivate removed slots
            $existing_ids = array_filter(array_column($data['slots'], 'id'));
            if (!empty($existing_ids)) {
                $ids_str = implode(',', array_map('intval', $existing_ids));
                $db->query("UPDATE routine_slots SET is_active = 0 WHERE id NOT IN ($ids_str)");
            } else {
                $db->query("UPDATE routine_slots SET is_active = 0");
            }

            $order = 1;
            foreach ($data['slots'] as $slot) {
                if (!empty($slot['id'])) {
                    // Update existing
                    $stmt = $db->prepare("UPDATE routine_slots SET start_time = ?, end_time = ?, label = ?, slot_type = ?, slot_order = ?, is_active = 1 WHERE id = ?");
                    $stmt->bind_param("ssssii", $slot['start_time'], $slot['end_time'], $slot['label'], $slot['slot_type'], $order, $slot['id']);
                    $stmt->execute();
                } else {
                    // Insert new
                    $stmt = $db->prepare("INSERT INTO routine_slots (start_time, end_time, label, slot_type, slot_order, is_active) VALUES (?, ?, ?, ?, ?, 1)");
                    $stmt->bind_param("ssssi", $slot['start_time'], $slot['end_time'], $slot['label'], $slot['slot_type'], $order);
                    $stmt->execute();
                }
                $order++;
            }

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Slots saved successfully']);
        } catch (Exception $e) {
            $db->rollback();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save slots: ' . $e->getMessage()]);
        }
    }

    // =============================================
    // GET ROOMS
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_rooms') {
        $rooms = [];
        $result = $db->query("SELECT * FROM rooms WHERE is_active = 1 ORDER BY code ASC");
        while ($row = $result->fetch_assoc()) {
            $rooms[] = [
                'id' => (int)$row['id'],
                'code' => $row['code'],
                'name' => $row['name'],
                'building' => $row['building'],
                'room_type' => $row['room_type'],
                'capacity' => (int)$row['capacity']
            ];
        }
        echo json_encode($rooms);
    }

    // =============================================
    // GET CURRENT DRAFT
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_draft') {
        $semester_id = intval($_GET['semester_id'] ?? 0);
        
        if (!$semester_id) {
            // Get active semester
            $sem = $db->query("SELECT id FROM semesters WHERE status = 'active' LIMIT 1")->fetch_assoc();
            $semester_id = $sem['id'] ?? 0;
        }

        // Find existing draft or create new one
        $stmt = $db->prepare("SELECT * FROM routine_drafts WHERE semester_id = ? AND department_id IN ($dept_id_list) AND status IN ('draft', 'published') ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param("i", $semester_id);
        $stmt->execute();
        $draft = $stmt->get_result()->fetch_assoc();

        if (!$draft) {
            // Create new draft
            $draft_name = "Routine Draft - " . date('Y-m-d H:i');
            $stmt = $db->prepare("INSERT INTO routine_drafts (semester_id, department_id, draft_name, status, created_by) VALUES (?, ?, ?, 'draft', ?)");
            $stmt->bind_param("iisi", $semester_id, $dept_id, $draft_name, $user_id);
            $stmt->execute();
            $draft_id = $stmt->insert_id;
            
            $draft = [
                'id' => $draft_id,
                'semester_id' => $semester_id,
                'department_id' => $dept_id,
                'draft_name' => $draft_name,
                'status' => 'draft',
                'created_by' => $user_id,
                'published_at' => null
            ];
        }

        echo json_encode($draft);
    }

    // =============================================
    // GET ASSIGNMENTS
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_assignments') {
        $draft_id = intval($_GET['draft_id'] ?? 0);
        
        if (!$draft_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Draft ID required']);
            exit;
        }

        $assignments = [];
        $stmt = $db->prepare("
            SELECT ra.*, 
                   c.course_code, c.course_name, c.course_type,
                   co.section,
                   r.code as room_code, r.name as room_name, r.building,
                   rs.label as slot_label, rs.slot_type,
                   CONCAT(up.first_name, ' ', up.last_name) as teacher_name,
                   t.id as teacher_id
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
        $stmt->bind_param("i", $draft_id);
        $stmt->execute();
        $result = $stmt->get_result();

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
                'teacher_name' => $row['teacher_name'] ?? 'TBA',
                'teacher_id' => $row['teacher_id'] ? (int)$row['teacher_id'] : null
            ];
        }

        echo json_encode($assignments);
    }

    // =============================================
    // GET COURSES (for dropdown)
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_courses') {
        $semester_id = intval($_GET['semester_id'] ?? 0);
        
        if (!$semester_id) {
            $sem = $db->query("SELECT id FROM semesters WHERE status = 'active' LIMIT 1")->fetch_assoc();
            $semester_id = $sem['id'] ?? 0;
        }

        $courses = [];
        // Show ALL open course offerings (not filtered by semester)
        // This allows scheduling any course in the routine
        $result = $db->query("
            SELECT co.id as course_offering_id, 
                   c.id as course_id, c.course_code, c.course_name, c.course_type,
                   co.section,
                   s.name as semester_name,
                   CONCAT(up.first_name, ' ', up.last_name) as teacher_name,
                   t.id as teacher_id,
                   c.default_room_id,
                   r.code as default_room_code, r.name as default_room_name
            FROM course_offerings co
            JOIN courses c ON co.course_id = c.id
            JOIN semesters s ON co.semester_id = s.id
            LEFT JOIN teacher_courses tc ON co.id = tc.course_offering_id
            LEFT JOIN teachers t ON tc.teacher_id = t.id
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN user_profiles up ON u.id = up.user_id
            LEFT JOIN rooms r ON c.default_room_id = r.id
            WHERE co.status = 'open'
            ORDER BY c.course_code ASC, co.section ASC
        ");

        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }

        echo json_encode($courses);
    }

    // =============================================
    // CHECK TEACHER AVAILABILITY
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'check_availability') {
        $teacher_id = intval($_GET['teacher_id'] ?? 0);
        $slot_id = intval($_GET['slot_id'] ?? 0);
        $day = $_GET['day'] ?? '';
        $draft_id = intval($_GET['draft_id'] ?? 0);
        $exclude_assignment_id = intval($_GET['exclude_id'] ?? 0);

        if (!$teacher_id || !$slot_id || !$day || !$draft_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing parameters']);
            exit;
        }

        // Check if teacher already has a class in this slot/day
        $query = "
            SELECT ra.id, c.course_code, co.section
            FROM routine_assignments ra
            JOIN course_offerings co ON ra.course_offering_id = co.id
            JOIN courses c ON co.course_id = c.id
            JOIN teacher_courses tc ON co.id = tc.course_offering_id
            WHERE tc.teacher_id = ?
              AND ra.slot_id = ?
              AND ra.day_of_week = ?
              AND ra.routine_draft_id = ?
        ";
        
        if ($exclude_assignment_id) {
            $query .= " AND ra.id != ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("iisii", $teacher_id, $slot_id, $day, $draft_id, $exclude_assignment_id);
        } else {
            $stmt = $db->prepare($query);
            $stmt->bind_param("iisi", $teacher_id, $slot_id, $day, $draft_id);
        }
        
        $stmt->execute();
        $conflict = $stmt->get_result()->fetch_assoc();

        if ($conflict) {
            echo json_encode([
                'available' => false,
                'conflict' => [
                    'course_code' => $conflict['course_code'],
                    'section' => $conflict['section']
                ]
            ]);
        } else {
            echo json_encode(['available' => true]);
        }
    }

    // =============================================
    // GET TEACHER'S AVAILABLE SLOTS
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_teacher_schedule') {
        $teacher_id = intval($_GET['teacher_id'] ?? 0);
        $draft_id = intval($_GET['draft_id'] ?? 0);

        if (!$teacher_id || !$draft_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing parameters']);
            exit;
        }

        // Get all the slots where teacher is busy
        $busy_slots = [];
        $stmt = $db->prepare("
            SELECT ra.slot_id, ra.day_of_week, c.course_code, co.section
            FROM routine_assignments ra
            JOIN course_offerings co ON ra.course_offering_id = co.id
            JOIN courses c ON co.course_id = c.id
            JOIN teacher_courses tc ON co.id = tc.course_offering_id
            WHERE tc.teacher_id = ? AND ra.routine_draft_id = ?
        ");
        $stmt->bind_param("ii", $teacher_id, $draft_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $key = $row['day_of_week'] . '_' . $row['slot_id'];
            $busy_slots[$key] = [
                'course_code' => $row['course_code'],
                'section' => $row['section']
            ];
        }

        echo json_encode($busy_slots);
    }

    // =============================================
    // ASSIGN CLASS
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'assign_class') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data']);
            exit;
        }

        $draft_id = intval($data['draft_id'] ?? 0);
        $course_offering_id = intval($data['course_offering_id'] ?? 0);
        $slot_id = intval($data['slot_id'] ?? 0);
        $room_id = intval($data['room_id'] ?? 0);
        $day = $data['day_of_week'] ?? '';

        if (!$draft_id || !$course_offering_id || !$slot_id || !$room_id || !$day) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }

        // Get teacher for this course
        $teacher_stmt = $db->prepare("SELECT t.id FROM teachers t JOIN teacher_courses tc ON t.id = tc.teacher_id WHERE tc.course_offering_id = ?");
        $teacher_stmt->bind_param("i", $course_offering_id);
        $teacher_stmt->execute();
        $teacher = $teacher_stmt->get_result()->fetch_assoc();
        $teacher_id = $teacher['id'] ?? null;

        // Check for teacher conflict
        if ($teacher_id) {
            $conflict_stmt = $db->prepare("
                SELECT ra.id, c.course_code 
                FROM routine_assignments ra
                JOIN course_offerings co ON ra.course_offering_id = co.id
                JOIN courses c ON co.course_id = c.id
                JOIN teacher_courses tc ON co.id = tc.course_offering_id
                WHERE tc.teacher_id = ? AND ra.slot_id = ? AND ra.day_of_week = ? AND ra.routine_draft_id = ?
            ");
            $conflict_stmt->bind_param("iisi", $teacher_id, $slot_id, $day, $draft_id);
            $conflict_stmt->execute();
            $conflict = $conflict_stmt->get_result()->fetch_assoc();

            if ($conflict) {
                http_response_code(409);
                echo json_encode([
                    'error' => 'Teacher is not available at this time',
                    'conflict' => 'Already assigned to ' . $conflict['course_code']
                ]);
                exit;
            }
        }

        // Check if slot/day/room is already taken
        $room_conflict_stmt = $db->prepare("
            SELECT ra.id, c.course_code 
            FROM routine_assignments ra
            JOIN course_offerings co ON ra.course_offering_id = co.id
            JOIN courses c ON co.course_id = c.id
            WHERE ra.room_id = ? AND ra.slot_id = ? AND ra.day_of_week = ? AND ra.routine_draft_id = ?
        ");
        $room_conflict_stmt->bind_param("iisi", $room_id, $slot_id, $day, $draft_id);
        $room_conflict_stmt->execute();
        $room_conflict = $room_conflict_stmt->get_result()->fetch_assoc();

        if ($room_conflict) {
            http_response_code(409);
            echo json_encode([
                'error' => 'Room is already booked for this slot',
                'conflict' => $room_conflict['course_code'] . ' is already scheduled in this room'
            ]);
            exit;
        }

        // Insert assignment
        $stmt = $db->prepare("INSERT INTO routine_assignments (routine_draft_id, course_offering_id, slot_id, room_id, day_of_week) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiis", $draft_id, $course_offering_id, $slot_id, $room_id, $day);


        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $stmt->insert_id, 'message' => 'Class assigned successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to assign class']);
        }
    }

    // =============================================
    // BULK ASSIGN CLASS (Multi-slot)
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'bulk_assign') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $draft_id = intval($data['draft_id'] ?? 0);
        $course_offering_id = intval($data['course_offering_id'] ?? 0);
        $room_id = intval($data['room_id'] ?? 0);
        $slots = $data['slots'] ?? [];
        
        if (!$draft_id || !$course_offering_id || !$room_id || empty($slots)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }
        
        // Get teacher for this course offering
        $teacher_stmt = $db->prepare("SELECT t.id FROM teachers t JOIN teacher_courses tc ON t.id = tc.teacher_id WHERE tc.course_offering_id = ?");
        $teacher_stmt->bind_param("i", $course_offering_id);
        $teacher_stmt->execute();
        $teacher = $teacher_stmt->get_result()->fetch_assoc();
        $teacher_id = $teacher['id'] ?? null;
        
        $assigned_count = 0;
        $errors = [];
        
        $db->begin_transaction();
        
        try {
            $stmt = $db->prepare("INSERT INTO routine_assignments (routine_draft_id, course_offering_id, slot_id, room_id, day_of_week) VALUES (?, ?, ?, ?, ?)");
            
            foreach ($slots as $slot) {
                $slot_id = intval($slot['slot_id']);
                $day = $slot['day'];
                
                // 1. Check Room Conflict
                $room_check = $db->prepare("SELECT id FROM routine_assignments WHERE routine_draft_id = ? AND day_of_week = ? AND slot_id = ? AND room_id = ?");
                $room_check->bind_param("isii", $draft_id, $day, $slot_id, $room_id);
                $room_check->execute();
                if ($room_check->get_result()->num_rows > 0) {
                    // Room is taken. We can either error or overwrite.
                    // For bulk, let's overwrite to avoid getting stuck, assuming admin knows best.
                    $del_stmt = $db->prepare("DELETE FROM routine_assignments WHERE routine_draft_id = ? AND day_of_week = ? AND slot_id = ? AND room_id = ?");
                    $del_stmt->bind_param("isii", $draft_id, $day, $slot_id, $room_id);
                    $del_stmt->execute();
                }
                
                // 2. Check Teacher Conflict (if teacher assigned)
                if ($teacher_id) {
                    $teach_check = $db->prepare("
                        SELECT ra.id 
                        FROM routine_assignments ra
                        JOIN course_offerings co ON ra.course_offering_id = co.id
                        JOIN teacher_courses tc ON co.id = tc.course_offering_id
                        WHERE ra.routine_draft_id = ? AND ra.day_of_week = ? AND ra.slot_id = ? AND tc.teacher_id = ?
                    ");
                    $teach_check->bind_param("isii", $draft_id, $day, $slot_id, $teacher_id);
                    $teach_check->execute();
                    if ($teach_check->get_result()->num_rows > 0) {
                        // Teacher is busy. Remove the conflicting assignment to enforce the new one.
                        // Or should we fail? Admin usually wants to force assign.
                        // Let's remove the *other* assignment that causes conflict
                        $del_teach = $db->prepare("
                            DELETE ra FROM routine_assignments ra
                            JOIN course_offerings co ON ra.course_offering_id = co.id
                            JOIN teacher_courses tc ON co.id = tc.course_offering_id
                            WHERE ra.routine_draft_id = ? AND ra.day_of_week = ? AND ra.slot_id = ? AND tc.teacher_id = ?
                        ");
                        $del_teach->bind_param("isii", $draft_id, $day, $slot_id, $teacher_id);
                        $del_teach->execute();
                    }
                }
                
                // 3. Remove duplicate assignment of SAME course in same slot (e.g. assigning same section twice)
                $dup_check = $db->prepare("DELETE FROM routine_assignments WHERE routine_draft_id = ? AND day_of_week = ? AND slot_id = ? AND course_offering_id = ?");
                $dup_check->bind_param("isii", $draft_id, $day, $slot_id, $course_offering_id);
                $dup_check->execute();

                $stmt->bind_param("iiiis", $draft_id, $course_offering_id, $slot_id, $room_id, $day);
                if ($stmt->execute()) {
                    $assigned_count++;
                }
            }
            
            $db->commit();
            echo json_encode(['success' => true, 'assigned' => $assigned_count, 'message' => "Assigned to $assigned_count slots"]);
            
        } catch (Exception $e) {
            $db->rollback();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to assign classes: ' . $e->getMessage()]);
        }
    }

    // =============================================
    // REMOVE ASSIGNMENT
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'remove_assignment') {
        $data = json_decode(file_get_contents('php://input'), true);
        $assignment_id = intval($data['assignment_id'] ?? 0);

        if (!$assignment_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Assignment ID required']);
            exit;
        }

        $stmt = $db->prepare("DELETE FROM routine_assignments WHERE id = ?");
        $stmt->bind_param("i", $assignment_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Assignment removed']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to remove assignment']);
        }
    }

    // =============================================
    // PUBLISH ROUTINE
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'publish_routine') {
        $data = json_decode(file_get_contents('php://input'), true);
        $draft_id = intval($data['draft_id'] ?? 0);

        if (!$draft_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Draft ID required']);
            exit;
        }

        $db->begin_transaction();

        try {
            // Update draft status
            $stmt = $db->prepare("UPDATE routine_drafts SET status = 'published', published_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $draft_id);
            $stmt->execute();

            // Get all teachers affected by this routine
            $teachers_stmt = $db->prepare("
                SELECT DISTINCT t.user_id, CONCAT(up.first_name, ' ', up.last_name) as teacher_name
                FROM routine_assignments ra
                JOIN course_offerings co ON ra.course_offering_id = co.id
                JOIN teacher_courses tc ON co.id = tc.course_offering_id
                JOIN teachers t ON tc.teacher_id = t.id
                JOIN user_profiles up ON t.user_id = up.user_id
                WHERE ra.routine_draft_id = ?
            ");
            $teachers_stmt->bind_param("i", $draft_id);
            $teachers_stmt->execute();
            $teachers = $teachers_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Send notifications to all teachers
            $notif_stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'alert')");
            $title = "New Class Routine Published";
            $message = "The new class routine has been published. Please check your schedule for the upcoming semester.";

            foreach ($teachers as $teacher) {
                $notif_stmt->bind_param("iss", $teacher['user_id'], $title, $message);
                $notif_stmt->execute();
            }

            $db->commit();
            echo json_encode([
                'success' => true, 
                'message' => 'Routine published successfully. ' . count($teachers) . ' teacher(s) notified.'
            ]);
        } catch (Exception $e) {
            $db->rollback();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to publish routine: ' . $e->getMessage()]);
        }
    }

    // =============================================
    // GET CHANGE REQUESTS
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_change_requests') {
        $draft_id = intval($_GET['draft_id'] ?? 0);

        $query = "
            SELECT rcr.*, 
                   ra.day_of_week, ra.slot_id,
                   c.course_code, c.course_name, co.section,
                   rs.label as slot_label, rs.start_time, rs.end_time,
                   r.code as room_code, r.name as room_name,
                   CONCAT(up.first_name, ' ', up.last_name) as teacher_name
            FROM routine_change_requests rcr
            JOIN routine_assignments ra ON rcr.routine_assignment_id = ra.id
            JOIN course_offerings co ON ra.course_offering_id = co.id
            JOIN courses c ON co.course_id = c.id
            JOIN routine_slots rs ON ra.slot_id = rs.id
            JOIN rooms r ON ra.room_id = r.id
            JOIN users u ON rcr.requested_by = u.id
            JOIN user_profiles up ON u.id = up.user_id
            WHERE ra.routine_draft_id IN (SELECT id FROM routine_drafts WHERE department_id IN ($dept_id_list))
        ";

        if ($draft_id) {
            $query .= " AND ra.routine_draft_id = $draft_id";
        }

        $query .= " ORDER BY rcr.created_at DESC";

        $result = $db->query($query);
        $requests = [];

        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }

        echo json_encode($requests);
    }

    // =============================================
    // RESPOND TO CHANGE REQUEST
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'respond_to_request') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $request_id = intval($data['request_id'] ?? 0);
        $status = $data['status'] ?? '';
        $response = $data['response'] ?? '';

        if (!$request_id || !in_array($status, ['approved', 'rejected'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request']);
            exit;
        }

        $stmt = $db->prepare("UPDATE routine_change_requests SET status = ?, admin_response = ?, responded_by = ?, responded_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssii", $status, $response, $user_id, $request_id);

        if ($stmt->execute()) {
            // Notify teacher
            $req_stmt = $db->prepare("SELECT requested_by FROM routine_change_requests WHERE id = ?");
            $req_stmt->bind_param("i", $request_id);
            $req_stmt->execute();
            $req = $req_stmt->get_result()->fetch_assoc();

            if ($req) {
                $title = "Change Request " . ucfirst($status);
                $message = "Your routine change request has been " . $status . ".";
                if ($response) {
                    $message .= " Admin response: " . $response;
                }

                $notif_stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'alert')");
                $notif_stmt->bind_param("iss", $req['requested_by'], $title, $message);
                $notif_stmt->execute();
            }

            echo json_encode(['success' => true, 'message' => 'Response sent']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to respond']);
        }
    }

    // =============================================
    // NOTIFY TEACHERS (Draft notification)
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'notify_draft') {
        $data = json_decode(file_get_contents('php://input'), true);
        $draft_id = intval($data['draft_id'] ?? 0);

        if (!$draft_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Draft ID required']);
            exit;
        }

        // Get all teachers in department
        $teachers_stmt = $db->prepare("
            SELECT DISTINCT t.user_id
            FROM teachers t
            WHERE t.department_id IN ($dept_id_list)
        ");
        $teachers_stmt->execute();
        $teachers = $teachers_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Send notifications
        $notif_stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'info')");
        $title = "Draft Routine Available";
        $message = "The admin is setting up the class routine. You can view the provisional schedule and request changes if needed.";

        foreach ($teachers as $teacher) {
            $notif_stmt->bind_param("iss", $teacher['user_id'], $title, $message);
            $notif_stmt->execute();
        }

        echo json_encode(['success' => true, 'message' => count($teachers) . ' teacher(s) notified']);
    }

    // =============================================
    // BULK ASSIGN (Multi-slot assignment)
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'bulk_assign') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['draft_id']) || !isset($data['course_offering_id']) || 
            !isset($data['room_id']) || !isset($data['slots']) || !is_array($data['slots'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }
        
        $draft_id = (int)$data['draft_id'];
        $course_offering_id = (int)$data['course_offering_id'];
        $room_id = (int)$data['room_id'];
        $slots = $data['slots'];
        
        if (empty($slots)) {
            http_response_code(400);
            echo json_encode(['error' => 'No slots selected']);
            exit;
        }
        
        $db->begin_transaction();
        
        try {
            $insert_stmt = $db->prepare("
                INSERT INTO routine_assignments (routine_draft_id, course_offering_id, slot_id, room_id, day_of_week)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $success_count = 0;
            $conflicts = [];
            
            foreach ($slots as $slot) {
                $day = $slot['day'];
                $slot_id = (int)$slot['slot_id'];
                
                // Check for existing assignment in this slot
                $check_stmt = $db->prepare("
                    SELECT ra.id, c.course_code 
                    FROM routine_assignments ra
                    JOIN course_offerings co ON ra.course_offering_id = co.id
                    JOIN courses c ON co.course_id = c.id
                    WHERE ra.routine_draft_id = ? AND ra.day_of_week = ? AND ra.slot_id = ?
                ");
                $check_stmt->bind_param("isi", $draft_id, $day, $slot_id);
                $check_stmt->execute();
                $existing = $check_stmt->get_result()->fetch_assoc();
                
                if ($existing) {
                    $conflicts[] = "{$day} - Slot {$slot_id} ({$existing['course_code']})";
                    continue;
                }
                
                $insert_stmt->bind_param("iiiss", $draft_id, $course_offering_id, $slot_id, $room_id, $day);
                $insert_stmt->execute();
                $success_count++;
            }
            
            $db->commit();
            
            $message = "Successfully assigned to {$success_count} slot(s)";
            if (!empty($conflicts)) {
                $message .= ". Skipped " . count($conflicts) . " conflicting slot(s).";
            }
            
            echo json_encode([
                'success' => true, 
                'message' => $message,
                'assigned' => $success_count,
                'conflicts' => $conflicts
            ]);
        } catch (Exception $e) {
            $db->rollback();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to assign: ' . $e->getMessage()]);
        }
    }

    // =============================================
    // SAVE AS DRAFT (with teacher notifications)
    // =============================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_as_draft') {
        $data = json_decode(file_get_contents('php://input'), true);
        $draft_id = intval($data['draft_id'] ?? 0);

        if (!$draft_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Draft ID required']);
            exit;
        }

        $db->begin_transaction();

        try {
            // Update draft status to 'draft'
            $stmt = $db->prepare("UPDATE routine_drafts SET status = 'draft' WHERE id = ?");
            $stmt->bind_param("i", $draft_id);
            $stmt->execute();

            // Get all teachers whose classes are assigned in this routine
            $teachers_stmt = $db->prepare("
                SELECT DISTINCT t.user_id, CONCAT(up.first_name, ' ', up.last_name) as teacher_name
                FROM routine_assignments ra
                JOIN course_offerings co ON ra.course_offering_id = co.id
                JOIN teacher_courses tc ON co.id = tc.course_offering_id
                JOIN teachers t ON tc.teacher_id = t.id
                JOIN user_profiles up ON t.user_id = up.user_id
                WHERE ra.routine_draft_id = ?
            ");
            $teachers_stmt->bind_param("i", $draft_id);
            $teachers_stmt->execute();
            $teachers = $teachers_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Notify each teacher
            $notif_stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'info')");
            $title = "Draft Routine Updated";
            $message = "The class routine has been saved as a draft. Please review your schedule and provide feedback if needed.";

            foreach ($teachers as $teacher) {
                $notif_stmt->bind_param("iss", $teacher['user_id'], $title, $message);
                $notif_stmt->execute();
            }

            $db->commit();
            echo json_encode([
                'success' => true, 
                'message' => 'Routine saved as draft. ' . count($teachers) . ' teacher(s) notified.'
            ]);
        } catch (Exception $e) {
            $db->rollback();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save as draft: ' . $e->getMessage()]);
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
