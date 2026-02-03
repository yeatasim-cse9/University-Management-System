<?php
/**
 * Teacher Dashboard
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('teacher');

$page_title = 'Teacher Dashboard';
$user_id = get_current_user_id();

// Get teacher info
$stmt = $db->prepare("SELECT t.*, d.name as department_name, d.code as department_code 
    FROM teachers t 
    JOIN departments d ON t.department_id = d.id 
    WHERE t.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

if (!$teacher) {
    set_flash('error', 'Teacher profile not found');
    redirect(BASE_URL . '/modules/auth/logout.php');
}

$teacher_id = $teacher['id'];

// Get statistics (Consolidated Query)
$today = date('l'); // Day name

$stats_query = "SELECT 
    (SELECT COUNT(*) FROM teacher_courses WHERE teacher_id = $teacher_id) as courses,
    (SELECT COUNT(DISTINCT e.student_id) FROM enrollments e 
        JOIN teacher_courses tc ON e.course_offering_id = tc.course_offering_id 
        WHERE tc.teacher_id = $teacher_id) as students,
    (SELECT COUNT(*) FROM assignment_submissions asub 
        JOIN assignments a ON asub.assignment_id = a.id 
        JOIN teacher_courses tc ON a.course_offering_id = tc.course_offering_id 
        WHERE tc.teacher_id = $teacher_id AND asub.status IN ('submitted', 'late')) as pending_grading,
    (SELECT COUNT(*) FROM class_schedule cs 
        JOIN teacher_courses tc ON cs.course_offering_id = tc.course_offering_id 
        WHERE tc.teacher_id = $teacher_id AND cs.day_of_week = '$today') as today_classes";

$result = $db->query($stats_query);
$stats = $result->fetch_assoc();

// Get today's schedule
$today_schedule = [];
$today_date = date('Y-m-d');

// 1. Fetch Regular Schedule
$result = $db->query("SELECT cs.*, c.course_code, c.course_name, c.course_type, co.section, s.semester_number 
    FROM class_schedule cs 
    JOIN course_offerings co ON cs.course_offering_id = co.id 
    JOIN courses c ON co.course_id = c.id 
    JOIN teacher_courses tc ON co.id = tc.course_offering_id 
    JOIN semesters s ON co.semester_id = s.id
    WHERE tc.teacher_id = $teacher_id AND cs.day_of_week = '$today' 
    ORDER BY cs.start_time");

$regular_classes = [];
while ($row = $result->fetch_assoc()) {
    $row['type'] = 'regular';
    $regular_classes[] = $row;
}

// 2. Fetch Rescheduled/Cancelled Exceptions
$res_query = "SELECT cr.*, c.course_code, c.course_name, c.course_type, co.section, s.semester_number 
    FROM class_reschedules cr
    JOIN course_offerings co ON cr.course_offering_id = co.id
    JOIN courses c ON co.course_id = c.id
    JOIN semesters s ON co.semester_id = s.id
    JOIN teacher_courses tc ON co.id = tc.course_offering_id
    WHERE tc.teacher_id = $teacher_id 
    AND (cr.original_date = '$today_date' OR cr.new_date = '$today_date')
    AND cr.status = 'active'";

$result = $db->query($res_query);
$reschedules = $result->fetch_all(MYSQLI_ASSOC);

$exceptions_map = []; // original_date_offering_id -> true
$incoming_reschedules = [];

foreach ($reschedules as $r) {
    if ($r['original_date'] == $today_date) {
        // This regular slot is cancelled/moved
        $exceptions_map[$r['original_date'] . '_' . $r['course_offering_id']] = true;
    }
    if ($r['new_date'] == $today_date) {
        // This is an incoming reschedule (new class today)
        $r['type'] = 'rescheduled';
        $r['start_time'] = $r['new_start_time']; // Normalize key for sorting
        $r['end_time'] = $r['new_end_time'];
        $incoming_reschedules[] = $r;
    }
}

// 3. Merge Lists
foreach ($regular_classes as $class) {
    $key = $today_date . '_' . $class['course_offering_id'];
    if (!isset($exceptions_map[$key])) {
        $today_schedule[] = $class;
    }
}

foreach ($incoming_reschedules as $class) {
    $today_schedule[] = $class;
}

// 4. Sort by Start Time
usort($today_schedule, function($a, $b) {
    return strcmp($a['start_time'], $b['start_time']);
});

// Get my courses
$my_courses = [];
$result = $db->query("SELECT co.*, c.course_code, c.course_name, s.name as semester_name 
    FROM course_offerings co 
    JOIN courses c ON co.course_id = c.id 
    JOIN semesters s ON co.semester_id = s.id 
    JOIN teacher_courses tc ON co.id = tc.course_offering_id 
    WHERE tc.teacher_id = $teacher_id 
    ORDER BY c.course_code");
while ($row = $result->fetch_assoc()) {
    $my_courses[] = $row;
}

// Sidebar menu
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

// Page content
ob_start();
?>

<div class="space-y-8 animate-in">
    <!-- Header -->
    <div class="bg-white os-border os-shadow p-8 flex flex-col md:flex-row gap-8 justify-between relative overflow-hidden">
        <div class="absolute top-0 right-0 p-4 opacity-10 pointer-events-none">
            <i class="fas fa-graduation-cap text-9xl text-black transform rotate-12"></i>
        </div>
        
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <span class="bg-black text-white text-[10px] font-black uppercase px-2 py-1 tracking-widest">Teacher_Module</span>
                <span class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest">
                    <span class="w-2 h-2 bg-green-500 rounded-full border border-black"></span>
                    System_Online
                </span>
            </div>
            <h1 class="text-4xl md:text-6xl font-black uppercase leading-none tracking-tighter mb-2">
                WELCOME BACK, <span class="bg-yellow-400 px-2 box-decoration-clone text-black"><?php echo explode(' ', $_SESSION['username'])[0]; ?></span>
            </h1>
            <p class="text-xs font-mono font-bold uppercase tracking-widest text-slate-500">
                User_ID: <?php echo e($teacher['employee_id']); ?> // Dept: <?php echo e($teacher['department_code']); ?>
            </p>
        </div>

        <div class="relative z-10 flex items-end">
            <div class="text-right">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] mb-1 text-slate-400">Current Session</p>
                <p class="text-xl font-black uppercase font-mono bg-black text-white px-3 py-1 inline-block transform -rotate-1">
                    <?php echo date('F d, Y'); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Card 1 -->
        <a href="my-courses.php" class="os-card p-6 flex flex-col justify-between h-[200px] group">
            <div class="flex justify-between items-start">
                <div class="os-icon-box">
                    <i class="fas fa-book text-lg"></i>
                </div>
                <i class="fas fa-arrow-up-right text-xl transition-transform group-hover:translate-x-1 group-hover:-translate-y-1"></i>
            </div>
            <div>
                <h3 class="text-5xl font-black mb-2 leading-none"><?php echo $stats['courses']; ?></h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-black">Active Courses</p>
            </div>
        </a>

        <!-- Card 2 -->
        <a href="students.php" class="os-card p-6 flex flex-col justify-between h-[200px] group">
            <div class="flex justify-between items-start">
                <div class="os-icon-box bg-white text-black">
                    <i class="fas fa-users text-lg"></i>
                </div>
                <i class="fas fa-arrow-up-right text-xl transition-transform group-hover:translate-x-1 group-hover:-translate-y-1"></i>
            </div>
            <div>
                <h3 class="text-5xl font-black mb-2 leading-none"><?php echo $stats['students']; ?></h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-black">Total Enrollment</p>
            </div>
        </a>

        <!-- Card 3 -->
        <a href="assignments.php" class="os-card p-6 flex flex-col justify-between h-[200px] group">
            <div class="flex justify-between items-start">
                <div class="os-icon-box bg-white text-black relative">
                    <i class="fas fa-file-alt text-lg"></i>
                    <?php if ($stats['pending_grading'] > 0): ?>
                        <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 border-2 border-white rounded-full"></span>
                    <?php endif; ?>
                </div>
                <i class="fas fa-arrow-up-right text-xl transition-transform group-hover:translate-x-1 group-hover:-translate-y-1"></i>
            </div>
            <div>
                <h3 class="text-5xl font-black mb-2 leading-none"><?php echo $stats['pending_grading']; ?></h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-black">Assignments Pending</p>
            </div>
        </a>

        <!-- Card 4 (Action) -->
        <a href="attendance.php" class="os-card os-card-invert p-6 flex flex-col justify-between h-[200px] group">
            <div class="flex justify-between items-start">
                <div class="os-icon-box">
                    <i class="fas fa-user-check text-lg"></i>
                </div>
                <i class="fas fa-arrow-right text-xl transition-transform group-hover:translate-x-1"></i>
            </div>
            <div>
                <h3 class="text-2xl font-black uppercase leading-none mb-1 text-white">Mark<br>Attendance</h3>
                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 group-hover:text-white transition-colors">Quick Action</p>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Schedule Section -->
        <div class="lg:col-span-2 os-card p-0 overflow-hidden bg-white">
            <div class="p-6 border-b-3 border-black flex justify-between items-center bg-yellow-400">
                <h3 class="text-xl font-black uppercase tracking-tight flex items-center gap-3">
                    <i class="fas fa-calendar-alt"></i> Today's Schedule
                </h3>
                <span class="bg-black text-white text-[10px] font-mono font-bold px-2 py-1 uppercase">
                    <?php echo date('l'); ?>
                </span>
            </div>

            <div class="p-6">
                <!-- Dashboard Specific Styles for Event Cards -->
                <style>
                    .event-card-dash {
                        padding: 12px;
                        border-radius: 8px;
                        border-left: 6px solid #000;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                        transition: transform 0.2s;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 12px;
                        background: #fff;
                    }
                    .event-card-dash:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
                    }
                    .type-theory { background-color: #e0f2fe; border-left-color: #0369a1; }
                    .type-lab { background-color: #f3e8ff; border-left-color: #7e22ce; }
                    .type-rescheduled { background-color: #fef3c7; border-left-color: #d97706; }
                    
                    .dash-time-box {
                        text-align: center;
                        min-width: 80px;
                        padding-right: 16px;
                        border-right: 2px dashed #cbd5e1;
                        margin-right: 16px;
                    }
                </style>

                <?php if (empty($today_schedule)): ?>
                    <div class="py-12 text-center border-2 border-dashed border-slate-200">
                        <p class="text-sm font-black uppercase mb-1">No Classes Scheduled</p>
                        <p class="text-xs text-slate-500 font-mono">ENJOY YOUR FREE TIME</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($today_schedule as $class): 
                            $now = time();
                            $start = strtotime($class['start_time']);
                            $end = strtotime($class['end_time']);
                            $is_active = ($now >= $start && $now <= $end);
                            
                            // Determine Class Type logic
                            $cardClass = 'event-card-dash';
                            if (isset($class['type']) && $class['type'] === 'rescheduled') {
                                $cardClass .= ' type-rescheduled';
                            } else if (isset($class['course_type']) && $class['course_type'] === 'lab') {
                                $cardClass .= ' type-lab';
                            } else {
                                $cardClass .= ' type-theory';
                            }
                        ?>
                        <div class="<?php echo $cardClass; ?>">
                            <div class="flex items-center flex-1">
                                <!-- Time -->
                                <div class="dash-time-box">
                                    <div class="text-sm font-black text-slate-800"><?php echo date('H:i', $start); ?></div>
                                    <div class="text-xs font-mono text-slate-500"><?php echo date('H:i', $end); ?></div>
                                </div>
                                
                                <!-- Info -->
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="text-sm font-black text-slate-900 uppercase">
                                            <?php echo e($class['course_code']); ?>
                                        </div>
                                        <?php if (isset($class['type']) && $class['type'] === 'rescheduled'): ?>
                                            <span class="bg-black text-white text-[9px] px-2 py-0.5 rounded-full font-bold uppercase">🔄 Rescheduled</span>
                                        <?php endif; ?>
                                        <?php if ($is_active): ?>
                                            <span class="text-[9px] font-black text-green-600 uppercase animate-pulse">● LIVE NOW</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-xs font-bold text-slate-700 uppercase mb-1">
                                        <?php echo e($class['course_name']); ?>
                                    </div>
                                    <div class="flex gap-3 text-[10px] font-bold text-slate-600 uppercase">
                                        <span><i class="fas fa-map-marker-alt mr-1"></i> <?php echo e($class['room_number'] ?? 'TBA'); ?></span>
                                        <span><i class="fas fa-layer-group mr-1"></i> Sem <?php echo e($class['semester_number']); ?> - <?php echo e($class['section']); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action -->
                             <a href="attendance.php?course_id=<?php echo $class['course_offering_id']; ?>" class="ml-4 w-10 h-10 flex items-center justify-center bg-white border-2 border-black rounded-full hover:bg-black hover:text-white transition-all shadow-sm" title="Mark Attendance">
                                <i class="fas fa-check"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar List (My Courses) -->
        <div class="os-card p-0 overflow-hidden bg-white">
            <div class="p-6 border-b-3 border-black bg-black text-white">
                <h3 class="text-xl font-black uppercase tracking-tight">Active Courses</h3>
                <p class="text-[10px] font-mono opacity-60 uppercase mt-1">QUICK NAVIGATION</p>
            </div>
            
            <div class="divide-y-2 divide-black max-h-[400px] overflow-y-auto">
                <?php if (empty($my_courses)): ?>
                    <div class="p-6 text-center text-xs font-mono text-slate-500">NO COURSES ASSIGNED</div>
                <?php else: ?>
                    <?php foreach ($my_courses as $course): ?>
                        <a href="my-courses.php?id=<?php echo $course['id']; ?>" class="block p-4 hover:bg-yellow-50 transition-colors group">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs font-black uppercase bg-slate-100 px-1 border border-black"><?php echo e($course['course_code']); ?></span>
                                <span class="text-[10px] font-mono font-bold">SEC <?php echo e($course['section']); ?></span>
                            </div>
                            <h4 class="text-sm font-black uppercase group-hover:text-black line-clamp-1 leading-tight mb-2">
                                <?php echo e($course['course_name']); ?>
                            </h4>
                            <div class="flex justify-between items-center">
                                <span class="text-[9px] font-bold uppercase text-slate-500">
                                    <i class="fas fa-users mr-1"></i> <?php echo $course['enrolled_students']; ?> Students
                                </span>
                                <i class="fas fa-chevron-right text-[10px] opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="p-4 bg-slate-50 border-t-3 border-black">
                <a href="routine.php" class="block text-center text-[10px] font-black uppercase tracking-widest hover:underline">
                    View Full Routine →
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Include layout
include __DIR__ . '/../../includes/layouts/dashboard.php';
