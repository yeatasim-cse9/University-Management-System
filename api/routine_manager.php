<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent HTML errors from breaking JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Check authentication
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
     http_response_code(403);
     echo json_encode(['error' => 'Unauthorized access']);
     exit;
}

$conn = get_db_connection();
$action = $_GET['action'] ?? '';

// Error handling helper
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

switch ($action) {
    case 'fetch_all':
        fetchAllData($conn);
        break;

    case 'save_slot':
        saveSlot($conn);
        break;

    case 'delete_slot':
        deleteSlot($conn);
        break;
        
    case 'save_room':
        saveRoom($conn);
        break;
        
    case 'delete_room':
        deleteRoom($conn);
        break;

    default:
        sendError('Invalid action');
}

function fetchAllData($conn) {
    $response = [];

    // 1. Fetch Teachers
    $sql_teachers = "
        SELECT t.id, up.first_name, up.last_name, t.department_id 
        FROM teachers t
        JOIN users u ON t.user_id = u.id
        JOIN user_profiles up ON u.id = up.user_id
        WHERE u.status = 'active'
    ";
    
    $result = $conn->query($sql_teachers);
    if (!$result) sendError("Error fetching teachers: " . $conn->error);
    
    $teachers = [];
    while ($row = $result->fetch_assoc()) {
        $row['full_name'] = $row['first_name'] . ' ' . $row['last_name'];
        $teachers[] = $row;
    }
    $response['teachers'] = $teachers;

    // 2. Fetch Rooms
    $sql_rooms = "SELECT * FROM rooms WHERE status = 'active'";
    $result = $conn->query($sql_rooms);
    if (!$result) sendError("Error fetching rooms: " . $conn->error);
    
    $rooms = [];
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
    $response['rooms'] = $rooms;

    // 3. Fetch Course Offerings (Batches)
    $sql_offerings = "
        SELECT co.id, co.semester_id, co.section, 
               c.course_code, c.course_name,
               s.semester_name
        FROM course_offerings co
        JOIN courses c ON co.course_id = c.id
        JOIN semesters s ON co.semester_id = s.id
        ORDER BY s.id, co.section
    ";
    $result_offerings = $conn->query($sql_offerings);
    if (!$result_offerings) {
        sendError("Database error fetching offerings: " . $conn->error);
    }

    $offerings = [];
    $batches = []; // Unique batch list for filter
    $seen_batches = [];

    while ($row = $result_offerings->fetch_assoc()) {
        $offerings[] = $row;
        
        $batch_key = $row['semester_id'] . '_' . $row['section'];
        if (!isset($seen_batches[$batch_key])) {
            $seen_batches[$batch_key] = true;
            $batches[] = [
                'id' => $batch_key,
                'name' => $row['semester_name'] . ' - Sec ' . $row['section'],
                'semester_id' => $row['semester_id'],
                'section' => $row['section']
            ];
        }
    }
    $response['offerings'] = $offerings;
    $response['batches'] = $batches;

    // 4. Fetch Schedule
    $sql_schedule = "
        SELECT cs.id, cs.course_offering_id, cs.teacher_id, cs.room_id, cs.day_of_week, cs.start_time, cs.end_time,
               c.course_code, c.course_name,
               up.first_name, up.last_name,
               co.semester_id, co.section,
               s.semester_name,
               r.room_number, r.building
        FROM class_schedule cs
        JOIN course_offerings co ON cs.course_offering_id = co.id
        JOIN courses c ON co.course_id = c.id
        JOIN semesters s ON co.semester_id = s.id
        LEFT JOIN teachers t ON cs.teacher_id = t.id
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        LEFT JOIN rooms r ON cs.room_id = r.id
    ";
    
    $result = $conn->query($sql_schedule);
    if (!$result) {
        sendError("Database error fetching schedule: " . $conn->error);
    }

    $schedule = [];
    while ($row = $result->fetch_assoc()) {
        $row['batch_id'] = $row['semester_id'] . '_' . $row['section'];
        $row['teacher_name'] = $row['first_name'] ? $row['first_name'] . ' ' . $row['last_name'] : 'TBA';
        $row['room_display'] = $row['room_number'] ? $row['room_number'] : 'TBA';
        
        $schedule[] = $row;
    }
    $response['schedule'] = $schedule;

    echo json_encode($response);
}

function saveSlot($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validation
    // Frontend likely sends 'room_id' or 'room_number' (as ID).
    // Let's assume input maps to room_id if valid.
    
    $required = ['day', 'start_time', 'end_time', 'course_offering_id', 'teacher_id', 'room_id'];
    
    foreach ($required as $field) {
        if (empty($input[$field])) sendError("Missing field: $field");
    }

    $day = $conn->real_escape_string($input['day']);
    $start_time = $conn->real_escape_string($input['start_time']);
    $end_time = $conn->real_escape_string($input['end_time']);
    $offering_id = (int)$input['course_offering_id'];
    $teacher_id = (int)$input['teacher_id'];
    $room_id = (int)$input['room_id'];
    
    // 1. Conflict Detection
    
    // A. Teacher Conflict
    $sql = "SELECT id FROM class_schedule 
            WHERE teacher_id = $teacher_id 
              AND day_of_week = '$day'
              AND (
                  (start_time < '$end_time' AND end_time > '$start_time')
              )";
    if (isset($input['id'])) {
        $currentId = (int)$input['id'];
        $sql .= " AND id != $currentId";
    }
    $res = $conn->query($sql);
    if (!$res) sendError("Teacher conflict check failed: " . $conn->error);
    if ($res->num_rows > 0) {
        sendError("Conflict: Teacher is already booked at this time.");
    }

    // B. Room Conflict
    $sql = "SELECT id FROM class_schedule 
            WHERE room_id = $room_id 
              AND day_of_week = '$day' 
              AND (
                  (start_time < '$end_time' AND end_time > '$start_time')
              )";
     if (isset($input['id'])) {
        $currentId = (int)$input['id'];
        $sql .= " AND id != $currentId";
    }
    $res = $conn->query($sql);
    if (!$res) sendError("Room conflict check failed: " . $conn->error);
    if ($res->num_rows > 0) {
        // Fetch room name for better error
        $rname = $conn->query("SELECT room_number FROM rooms WHERE id = $room_id")->fetch_object()->room_number ?? 'Unknown';
        sendError("Conflict: Room '$rname' is occupied at this time.");
    }

    // C. Batch Conflict (Same Semester + Section)
    $off_query = $conn->query("SELECT semester_id, section FROM course_offerings WHERE id = $offering_id");
    if (!$off_query) sendError("Error checking offering: " . $conn->error);
    if ($off_query->num_rows == 0) sendError("Invalid course offering.");
    $off_data = $off_query->fetch_assoc();
    $sem_id = $off_data['semester_id'];
    $sec = $conn->real_escape_string($off_data['section']);

    $sql = "SELECT cs.id 
            FROM class_schedule cs
            JOIN course_offerings co ON cs.course_offering_id = co.id
            WHERE co.semester_id = $sem_id AND co.section = '$sec'
              AND cs.day_of_week = '$day'
              AND (
                  (cs.start_time < '$end_time' AND cs.end_time > '$start_time')
              )";
    if (isset($input['id'])) {
        $currentId = (int)$input['id'];
        $sql .= " AND cs.id != $currentId";
    }
    $res = $conn->query($sql);
    if (!$res) sendError("Batch conflict check failed: " . $conn->error);
    if ($res->num_rows > 0) {
        sendError("Conflict: This batch (Semester $sem_id - Sec $sec) already has a class.");
    }
    
    // Save
    if (isset($input['id']) && $input['id']) {
        // Update
        $id = (int)$input['id'];
        $stmt = $conn->prepare("UPDATE class_schedule SET course_offering_id=?, teacher_id=?, room_id=?, day_of_week=?, start_time=?, end_time=? WHERE id=?");
        $stmt->bind_param("iiisssi", $offering_id, $teacher_id, $room_id, $day, $start_time, $end_time, $id);
    } else {
        // Insert
        // Note: is_recurring default 1
        $stmt = $conn->prepare("INSERT INTO class_schedule (course_offering_id, teacher_id, room_id, day_of_week, start_time, end_time, is_recurring) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("iiisss", $offering_id, $teacher_id, $room_id, $day, $start_time, $end_time);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        sendError("Database error: " . $stmt->error, 500);
    }
}

function deleteSlot($conn) {
    if (!isset($_POST['id'])) {
         $input = json_decode(file_get_contents('php://input'), true);
         $id = (int)($input['id'] ?? 0);
    } else {
        $id = (int)$_POST['id'];
    }
    
    if ($id <= 0) sendError("Invalid ID");

    $sql = "DELETE FROM class_schedule WHERE id = $id";
    if ($conn->query($sql)) {
        echo json_encode(['success' => true]);
    } else {
        sendError("Delete failed: " . $conn->error, 500);
    }
}

function saveRoom($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['room_number'])) sendError("Room number required");
    
    $room_num = $conn->real_escape_string($input['room_number']);
    $type = $conn->real_escape_string($input['type'] ?? 'theory');
    $capacity = (int)($input['capacity'] ?? 60);
    
    $stmt = $conn->prepare("INSERT INTO rooms (room_number, type, capacity) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $room_num, $type, $capacity);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
    } else {
        sendError("Failed to add room: " . $stmt->error);
    }
}

function deleteRoom($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) sendError("Invalid ID");
    
    if ($conn->query("DELETE FROM rooms WHERE id = $id")) {
        echo json_encode(['success' => true]);
    } else {
        sendError("Delete failed: " . $conn->error);
    }
}
?>
