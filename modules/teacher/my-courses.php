<?php
/**
 * My Courses
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('teacher');

$page_title = 'My Courses';
$user_id = get_current_user_id();

// Get teacher info
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$teacher_id = $teacher['id'];

$course_id = $_GET['id'] ?? null;

if ($course_id) {
    // Detail View Logic
    $offering_query = "SELECT co.*, c.course_code, c.course_name, c.credit_hours, s.name as semester_name, 
        (SELECT COUNT(*) FROM enrollments WHERE course_offering_id = co.id) as enrolled_count
        FROM course_offerings co 
        JOIN courses c ON co.course_id = c.id 
        JOIN semesters s ON co.semester_id = s.id 
        JOIN teacher_courses tc ON co.id = tc.course_offering_id 
        WHERE tc.teacher_id = $teacher_id AND co.id = $course_id";
    $offering_res = $db->query($offering_query);
    
    if ($offering_res->num_rows === 0) {
        set_flash('error', 'Course not found or access denied');
        redirect(BASE_URL . '/modules/teacher/my-courses.php');
    }
    $course = $offering_res->fetch_assoc();
    
    // Get Students
    $search = sanitize_input($_GET['search'] ?? '');
    $students = [];
    $query = "SELECT s.id as internal_id, s.student_id, up.first_name, up.last_name, e.status, u.email
        FROM enrollments e 
        JOIN students s ON e.student_id = s.id 
        JOIN users u ON s.user_id = u.id 
        JOIN user_profiles up ON u.id = up.user_id 
        WHERE e.course_offering_id = $course_id";
    
    if ($search) {
        $query .= " AND (s.student_id LIKE '%$search%' OR CONCAT(up.first_name, ' ', up.last_name) LIKE '%$search%' OR up.first_name LIKE '%$search%' OR up.last_name LIKE '%$search%')";
    }
    
    $query .= " ORDER BY s.student_id";
    
    $stmt = $db->query($query);
    while ($row = $stmt->fetch_assoc()) {
        $students[] = $row;
    }
    
    // Get Schedule
    $schedule = [];
    $stmt = $db->query("SELECT * FROM class_schedule WHERE course_offering_id = $course_id ORDER BY FIELD(day_of_week, 'Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), start_time");
    while ($row = $stmt->fetch_assoc()) {
        $schedule[] = $row;
    }
} else {
    // List View Logic
    $my_courses = [];
    $result = $db->query("SELECT co.*, c.course_code, c.course_name, s.name as semester_name,
        (SELECT COUNT(*) FROM enrollments WHERE course_offering_id = co.id) as enrolled_count
        FROM course_offerings co 
        JOIN courses c ON co.course_id = c.id 
        JOIN semesters s ON co.semester_id = s.id 
        JOIN teacher_courses tc ON co.id = tc.course_offering_id 
        WHERE tc.teacher_id = $teacher_id 
        ORDER BY s.start_date DESC, c.course_code");
    while ($row = $result->fetch_assoc()) {
        $my_courses[] = $row;
    }
}

// Sidebar
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<div class="space-y-8 animate-in">
<?php if ($course_id): ?>
    <!-- Course Detail Header -->
    <div class="bg-white os-border os-shadow p-8 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-4 opacity-10 pointer-events-none">
            <i class="fas fa-microchip text-9xl text-black transform -rotate-12"></i>
        </div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-8">
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <span class="bg-black text-white text-[10px] font-black uppercase px-2 py-1 tracking-widest">Course_Terminal</span>
                    <span class="bg-yellow-400 text-black text-[10px] font-black uppercase px-2 py-1 tracking-widest border border-black"><?php echo e($course['course_code']); ?></span>
                </div>
                <h1 class="text-4xl md:text-5xl font-black uppercase leading-none tracking-tighter mb-2">
                    <?php echo e($course['course_name']); ?>
                </h1>
                <p class="text-xs font-mono font-bold uppercase tracking-widest text-slate-500">
                    Section: <?php echo e($course['section']); ?> // Semester: <?php echo e($course['semester_name']); ?>

                </p>
            </div>
            
            <a href="my-courses.php" class="btn-os bg-white hover:bg-black hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i> Back to Console
            </a>
        </div>
    </div>
    
    <!-- Statistics Grid (Award Winning Style) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Students -->
        <div class="os-card p-6 flex flex-col justify-between h-[180px] group">
            <div class="flex justify-between items-start">
                <div class="os-icon-box">
                    <i class="fas fa-users text-lg"></i>
                </div>
                <i class="fas fa-arrow-up-right text-xl opacity-0 group-hover:opacity-100 transition-opacity"></i>
            </div>
            <div>
                <h3 class="text-5xl font-black mb-2 leading-none"><?php echo $course['enrolled_count']; ?></h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-black">Total Students</p>
            </div>
        </div>

        <!-- Credits -->
        <div class="os-card p-6 flex flex-col justify-between h-[180px] group">
            <div class="flex justify-between items-start">
                <div class="os-icon-box bg-white text-black">
                    <i class="fas fa-award text-lg"></i>
                </div>
            </div>
            <div>
                <h3 class="text-5xl font-black mb-2 leading-none"><?php echo $course['credit_hours']; ?>.0</h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-black">Credit Hours</p>
            </div>
        </div>

        <!-- Sessions -->
        <div class="os-card p-6 flex flex-col justify-between h-[180px] group">
            <div class="flex justify-between items-start">
                <div class="os-icon-box bg-white text-black">
                    <i class="fas fa-calendar-alt text-lg"></i>
                </div>
            </div>
            <div>
                <h3 class="text-5xl font-black mb-2 leading-none"><?php echo count($schedule); ?></h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-black">Weekly Sessions</p>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Class Schedule -->
        <div class="os-card p-0 bg-white">
            <div class="p-6 border-b-3 border-black bg-yellow-400 flex justify-between items-center">
                <h3 class="text-xl font-black uppercase tracking-tight flex items-center gap-2">
                    <i class="fas fa-clock"></i> Weekly Schedule
                </h3>
            </div>
            
            <div class="p-6">
                <?php if (empty($schedule)): ?>
                    <div class="py-12 text-center border-2 border-dashed border-black/10">
                        <i class="fas fa-calendar-times text-4xl text-slate-200 mb-4"></i>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No sessions scheduled</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($schedule as $slot): ?>
                            <div class="p-4 border-2 border-black hover:bg-yellow-50 transition-colors group">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-4">
                                        <div class="os-icon-box group-hover:bg-yellow-400 group-hover:text-black transition-colors">
                                            <span class="font-black text-xs"><?php echo strtoupper(substr($slot['day_of_week'], 0, 3)); ?></span>
                                        </div>
                                        <div>
                                            <p class="text-xs font-black uppercase mb-1"><?php echo e($slot['day_of_week']); ?></p>
                                            <p class="text-[10px] font-mono font-bold text-slate-500">
                                                <?php echo date('H:i', strtotime($slot['start_time'])); ?> - <?php echo date('H:i', strtotime($slot['end_time'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="bg-black text-white text-[10px] font-black px-2 py-1 uppercase inline-block mb-1">Room <?php echo e($slot['room_number']); ?></div>
                                        <p class="text-[9px] font-black uppercase text-slate-400"><?php echo e($slot['building']); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="os-card p-0 bg-black text-white">
            <div class="p-6 border-b-3 border-white/20 flex justify-between items-center">
                <h3 class="text-xl font-black uppercase tracking-tight flex items-center gap-2">
                    <i class="fas fa-bolt text-yellow-400"></i> Command Center
                </h3>
            </div>
            
            <div class="grid grid-cols-2 gap-4 p-6">
                <?php 
                $actions = [
                    ['url' => 'attendance.php?course_id=', 'icon' => 'fa-clipboard-check', 'label' => 'Attendance'],
                    ['url' => 'assignments.php?course_id=', 'icon' => 'fa-tasks', 'label' => 'Assignments'],
                    ['url' => 'marks-entry.php?course_id=', 'icon' => 'fa-edit', 'label' => 'Marks Entry'],
                    ['url' => 'course-materials.php?course_id=', 'icon' => 'fa-folder-open', 'label' => 'Materials']
                ];
                foreach ($actions as $act):
                ?>
                <a href="<?php echo $act['url'] . $course_id; ?>" class="group p-6 border-2 border-white/20 hover:bg-yellow-400 hover:border-yellow-400 transition-all text-center">
                    <div class="w-12 h-12 border-2 border-white group-hover:border-black group-hover:text-black flex items-center justify-center mx-auto mb-3 transition-colors">
                        <i class="fas <?php echo $act['icon']; ?> text-xl"></i>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest group-hover:text-black transition-colors"><?php echo $act['label']; ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Student List -->
    <div class="os-card p-0 bg-white">
        <div class="p-6 border-b-3 border-black flex justify-between items-center bg-black text-white">
            <div>
                <h3 class="text-xl font-black uppercase tracking-tight">Student Roster</h3>
                <p class="text-[10px] font-mono opacity-60 uppercase mt-1">Enrolled Personnel</p>
            </div>
            <div class="flex items-center gap-4">
                 <form action="" method="GET" class="flex">
                    <input type="hidden" name="id" value="<?php echo $course_id; ?>">
                    <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="SEARCH BY NAME/ROLL..." class="bg-white text-black px-4 py-2 font-bold uppercase text-xs border-2 border-transparent focus:border-yellow-400 outline-none w-48">
                    <button type="submit" class="bg-yellow-400 px-4 py-2 text-black font-black uppercase text-xs hover:bg-white transition-colors">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                <div class="w-px h-8 bg-white/20"></div>
                <button onclick="window.print()" class="btn-os bg-yellow-400 text-black hover:bg-white border-white">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 border-b-3 border-black">
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-black">Student Name</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-black">ID / Program</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-black">Contact</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-black text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y-2 divide-slate-100">
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No students enrolled</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <tr class="hover:bg-yellow-50 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-black text-white flex items-center justify-center font-black text-[10px] border border-black group-hover:bg-yellow-400 group-hover:text-black transition-colors">
                                            ST
                                        </div>
                                        <a href="student-performance.php?student_id=<?php echo $student['internal_id']; ?>" class="font-bold text-sm uppercase hover:text-yellow-600 hover:underline"><?php echo e($student['first_name'] . ' ' . $student['last_name']); ?></a>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-mono text-xs font-bold"><?php echo e($student['student_id']); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs font-bold text-slate-500"><?php echo e($student['email']); ?></span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="px-2 py-1 text-[9px] font-black uppercase border-2 <?php echo $student['status'] === 'enrolled' ? 'border-black bg-black text-white' : 'border-red-500 text-red-500'; ?>">
                                        <?php echo $student['status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: ?>
    <!-- Course List View -->
    <div class="bg-white os-border os-shadow p-8 flex flex-col md:flex-row md:items-center justify-between gap-8 relative overflow-hidden">
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <span class="bg-black text-white text-[10px] font-black uppercase px-2 py-1 tracking-widest">Teacher_Console</span>
                <span class="bg-yellow-400 text-black text-[10px] font-black uppercase px-2 py-1 tracking-widest border border-black">v2.0</span>
            </div>
            <h1 class="text-4xl md:text-6xl font-black uppercase leading-none tracking-tighter mb-2">
                My <span class="text-transparent bg-clip-text bg-gradient-to-r from-black to-slate-500">Courses</span>
            </h1>
            <p class="text-xs font-mono font-bold uppercase tracking-widest text-slate-500">
                Managed Offerings: <?php echo count($my_courses); ?>
            </p>
        </div>
        
        <div class="hidden md:block">
            <i class="fas fa-layer-group text-6xl text-black opacity-10"></i>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($my_courses as $course): ?>
            <a href="?id=<?php echo $course['id']; ?>" class="os-card p-0 bg-white group hover:-translate-y-2 transition-transform block h-full">
                <div class="p-6 border-b-3 border-black bg-slate-50 group-hover:bg-yellow-400 transition-colors">
                    <div class="flex justify-between items-start mb-4">
                        <span class="bg-black text-white text-[10px] font-black uppercase px-2 py-1"><?php echo e($course['course_code']); ?></span>
                        <div class="os-icon-box bg-white text-black border-black group-hover:bg-black group-hover:text-white">
                            <?php echo e($course['section']); ?>
                        </div>
                    </div>
                    <h3 class="text-2xl font-black uppercase leading-tight group-hover:text-black line-clamp-2 min-h-[3.5rem]">
                        <?php echo e($course['course_name']); ?>
                    </h3>
                </div>
                
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Enrolled</span>
                        <span class="text-lg font-black"><?php echo $course['enrolled_count']; ?></span>
                    </div>
                    <div class="w-full h-2 bg-slate-200 border border-black">
                        <div class="h-full bg-black" style="width: <?php echo min(100, ($course['enrolled_count'] / 40) * 100); ?>%"></div>
                    </div>
                    
                    <div class="flex items-center justify-between pt-4 border-t-2 border-slate-100">
                        <div class="text-[10px] font-bold uppercase">
                            <span class="text-slate-400">Semester:</span>
                            <br><?php echo e($course['semester_name']); ?>
                        </div>
                        <span class="btn-os text-[9px] px-3 py-1">View <i class="fas fa-arrow-right ml-1"></i></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($my_courses)): ?>
        <div class="py-20 text-center border-3 border-dashed border-slate-300">
            <i class="fas fa-folder-open text-6xl text-slate-200 mb-4"></i>
            <p class="text-sm font-black uppercase text-slate-400 tracking-widest">No courses assigned</p>
        </div>
    <?php endif; ?>
<?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
