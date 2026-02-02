<?php
/**
 * Student Class Routine
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('student');

$page_title = 'Class Routine';
$user_id = get_current_user_id();

// 1. Get Student Details
// 1. Get Student Details
$query = "SELECT s.id as student_table_id, s.department_id, s.batch_year, d.name as dept_name 
          FROM students s 
          JOIN departments d ON s.department_id = d.id 
          WHERE s.user_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    die("Student record not found.");
}

$student_table_id = $student['student_table_id'];
$batch_year = $student['batch_year'];
// 2. Fetch Routine (Based on Enrollments)
$sql = "SELECT cs.*, cs.day_of_week as day, cs.room_number as room_no, 
               c.course_code, c.course_name, 
               t.employee_id as t_code, up.first_name as t_fname, up.last_name as t_lname
        FROM class_schedule cs
        JOIN course_offerings co ON cs.course_offering_id = co.id
        JOIN courses c ON co.course_id = c.id
        JOIN enrollments e ON co.id = e.course_offering_id
        LEFT JOIN teacher_courses tc ON co.id = tc.course_offering_id
        LEFT JOIN teachers t ON tc.teacher_id = t.id
        LEFT JOIN user_profiles up ON t.user_id = up.user_id
        WHERE e.student_id = ?
        ORDER BY FIELD(cs.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), cs.start_time";

$stmt = $db->prepare($sql);
$stmt->bind_param("i", $student_table_id);
$stmt->execute();
$result = $stmt->get_result();

$routine = [];
$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; 
$day_map = [
    'Sunday' => 'Sun', 'Monday' => 'Mon', 'Tuesday' => 'Tue', 
    'Wednesday' => 'Wed', 'Thursday' => 'Thu', 'Friday' => 'Fri', 'Saturday' => 'Sat'
];

foreach ($days as $d) $routine[$d] = [];

while ($row = $result->fetch_assoc()) {
    $full_day = $row['day_of_week'] ?? $row['day']; // 'day' alias used in query
    $day = $day_map[$full_day] ?? $full_day;
    
    if (in_array($day, $days)) {
        $routine[$day][] = $row;
    }
}

// Sidebar
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

// View Data
ob_start();
?>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
             <h1 class="text-4xl font-black uppercase tracking-tighter border-b-4 border-black inline-block pb-2">
                Fall 2025 Routine
            </h1>
             <p class="text-sm font-bold uppercase tracking-widest text-gray-600 mt-2">
                <?php echo $student['dept_name']; ?> | Batch <?php echo $batch_year; ?>
            </p>
        </div>
         <button onclick="window.print()" class="btn-print-hide btn-os bg-white hover:bg-black hover:text-white">
            <i class="fas fa-print mr-2"></i> Print API
        </button>
    </div>

    <!-- Routine Grid -->
    <div class="os-card p-0 overflow-hidden bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-black text-white uppercase text-xs font-black tracking-widest">
                        <th class="p-4 border border-black w-24 text-center">Day</th>
                        <th class="p-4 border border-black text-left">Classes</th>
                    </tr>
                </thead>
                <tbody class="font-mono">
                    <?php foreach ($days as $day): ?>
                    <tr class="group hover:bg-yellow-50">
                        <td class="p-6 border border-black font-black text-center text-lg bg-gray-50 uppercase shadow-inner">
                            <?php echo $day; ?>
                        </td>
                        <td class="p-4 border border-black align-top">
                            <?php if (empty($routine[$day])): ?>
                                <div class="text-gray-400 font-bold uppercase text-xs p-2 text-center">No Classes</div>
                            <?php else: ?>
                                <div class="flex flex-wrap gap-4">
                                <?php foreach ($routine[$day] as $class): 
                                    $start = date('h:i A', strtotime($class['start_time']));
                                    $end = date('h:i A', strtotime($class['end_time']));
                                    $teacher = $class['t_code'] ?? ($class['t_lname'] ?? 'TBA');
                                ?>
                                    <div class="flex-shrink-0 w-64 bg-white border-2 border-black p-3 hover:shadow-[4px_4px_0px_#000] hover:-translate-y-1 transition-all duration-200 cursor-pointer">
                                        <div class="flex justify-between items-start border-b-2 border-dashed border-gray-300 pb-2 mb-2">
                                            <span class="text-xs font-black uppercase bg-black text-white px-1.5 py-0.5">
                                                <?php echo $class['room_no'] ?? 'TBA'; ?>
                                            </span>
                                            <span class="text-[10px] font-bold text-gray-500 uppercase">
                                                <?php echo $start . ' - ' . $end; ?>
                                            </span>
                                        </div>
                                        <div>
                                            <div class="text-lg font-black uppercase leading-tight mb-1">
                                                <?php echo $class['course_code']; ?>
                                            </div>
                                            <div class="text-xs font-bold text-gray-600 truncate mb-2" title="<?php echo $class['course_name']; ?>">
                                                <?php echo $class['course_name']; ?>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <div class="w-2 h-2 bg-yellow-400 rounded-full"></div>
                                                <span class="text-[10px] font-black uppercase tracking-wider text-black">
                                                    <?php echo $teacher; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    .btn-print-hide, #sidebar { display: none !important; }
    #main-content { margin: 0 !important; width: 100% !important; }
    .os-card { border: none !important; box-shadow: none !important; }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
