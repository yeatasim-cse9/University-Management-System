<?php
/**
 * Export Routine
 * Generates valid CSV for routine data.
 */
session_start();
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('admin');

$user_id = get_current_user_id();

// Fetch Admin's Department
$dept_ids = [];
$res = $db->query("SELECT department_id FROM department_admins WHERE user_id = $user_id");
while($row = $res->fetch_assoc()) { $dept_ids[] = $row['department_id']; }
$dept_str = implode(',', $dept_ids);

if (empty($dept_str)) die("No Department Assigned");

// Fetch Schedule
$sql = "SELECT cs.day_of_week, 
               cs.start_time, 
               cs.end_time, 
               c.course_code, 
               c.course_name, 
               co.section, 
               r.room_number,
               u.username as teacher_name
        FROM class_schedule cs
        JOIN course_offerings co ON cs.course_offering_id = co.id
        JOIN courses c ON co.course_id = c.id
        LEFT JOIN rooms r ON cs.room_id = r.id
        LEFT JOIN teacher_courses tc ON co.id = tc.course_offering_id
        LEFT JOIN users u ON tc.teacher_id = u.id
        WHERE c.department_id IN ($dept_str)
        ORDER BY FIELD(cs.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), cs.start_time";

$result = $db->query($sql);

// Set Headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="routine_export_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// Header Row
fputcsv($output, ['Day', 'Start', 'End', 'Course Code', 'Course Name', 'Section', 'Room', 'Teacher']);

// Data Rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['day_of_week'],
        date('H:i', strtotime($row['start_time'])),
        date('H:i', strtotime($row['end_time'])),
        $row['course_code'],
        $row['course_name'],
        $row['section'],
        $row['room_number'] ?? 'TBA',
        $row['teacher_name'] ?? 'Unassigned'
    ]);
}

fclose($output);
exit;
?>
