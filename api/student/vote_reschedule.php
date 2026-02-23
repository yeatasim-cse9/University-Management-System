<?php
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = get_current_user_id();

// Get student ID
$stmt = $db->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    echo json_encode(['success' => false, 'error' => 'Student info not found']);
    exit;
}

$student_id = $student['id'];
$data = json_decode(file_get_contents('php://input'), true);

$class_id = $data['class_id'] ?? null;
$suggested_date = $data['suggested_date'] ?? null;

if (!$class_id || !$suggested_date) {
    echo json_encode(['success' => false, 'error' => 'Class ID and Suggested Date are required']);
    exit;
}

// 1. Check/Create Request
$stmt = $db->prepare("SELECT id, status FROM reschedule_requests WHERE class_id = ? AND status != 'rescheduled'");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();

$request_id = null;

if (!$request) {
    // Create new request
    $stmt = $db->prepare("INSERT INTO reschedule_requests (class_id, status) VALUES (?, 'pending')");
    $stmt->bind_param("i", $class_id);
    if ($stmt->execute()) {
        $request_id = $stmt->insert_id;
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error creating request']);
        exit;
    }
} else {
    $request_id = $request['id'];
}

// 2. Add Vote
$stmt = $db->prepare("INSERT INTO votes (request_id, student_id, suggested_date) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $request_id, $student_id, $suggested_date);

try {
    $stmt->execute();
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() == 1062) { // Duplicate entry
         echo json_encode(['success' => false, 'error' => 'You have already voted for this request']);
         exit;
    }
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// 3. Calculate Threshold
// Get course offering ID from class_schedule to count enrollments
$q_enroll = "SELECT COUNT(*) as total 
             FROM enrollments e 
             JOIN class_schedule cs ON e.course_offering_id = cs.course_offering_id 
             WHERE cs.id = ?";
$stmt = $db->prepare($q_enroll);
$stmt->bind_param("i", $class_id);
$stmt->execute();
$total_students = $stmt->get_result()->fetch_assoc()['total'];

$q_votes = "SELECT COUNT(*) as total FROM votes WHERE request_id = ?";
$stmt = $db->prepare($q_votes);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$vote_count = $stmt->get_result()->fetch_assoc()['total'];

$threshold_met = false;
$msg = "Vote recorded.";

if ($total_students > 0 && ($vote_count / $total_students) > 0.60) {
    // 4. Update Status & Notify Teacher
    
    // Check if already threshold_reached to avoid spamming notification
    // (Though simple update is fine)
    $stmt = $db->prepare("UPDATE reschedule_requests SET status = 'threshold_reached' WHERE id = ? AND status != 'threshold_reached'");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $threshold_met = true;
        // Notify Teacher
        $q_teacher = "SELECT t.user_id 
                      FROM teachers t 
                      JOIN teacher_courses tc ON t.id = tc.teacher_id 
                      JOIN class_schedule cs ON tc.course_offering_id = cs.course_offering_id 
                      WHERE cs.id = ?";
        $stmt = $db->prepare($q_teacher);
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $teacher = $stmt->get_result()->fetch_assoc();
        
        if ($teacher) {
            $t_uid = $teacher['user_id'];
            $notif_title = "Reschedule Request Alert";
            $notif_msg = "Over 60% of students in your class have requested a reschedule.";
            $q_notif = "INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)";
            $stmt = $db->prepare($q_notif);
            $stmt->bind_param("iss", $t_uid, $notif_title, $notif_msg);
            $stmt->execute();
        }
    }
    $msg = "Vote recorded. Threshold reached! Teacher notified.";
}

echo json_encode([
    'success' => true, 
    'message' => $msg, 
    'vote_count' => $vote_count, 
    'total_students' => $total_students,
    'threshold_met' => $threshold_met
]);
?>
