<?php
/**
 * My Courses - Academic Hub
 * ACADEMIX - Premium Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('student');

$page_title = 'My Courses';
$user_id = get_current_user_id();

// Get student info
$stmt = $db->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$student_id = $student['id'];

$course_id = $_GET['id'] ?? null;

if ($course_id) {
    // Detail View
    // Verify enrollment
    $check = $db->query("SELECT * FROM enrollments WHERE student_id = $student_id AND course_offering_id = $course_id AND status = 'enrolled'");
    if ($check->num_rows === 0) {
        set_flash('error', 'Sector access denied: Not enrolled in this deployment.');
        redirect(BASE_URL . '/modules/student/my-courses.php');
    }
    
    // Get Course Details
    $query = "SELECT co.*, c.course_code, c.course_name, c.credit_hours, c.description,
        s.name as semester_name, 
        up.first_name as teacher_first, up.last_name as teacher_last, up.profile_picture as teacher_image, u.email as teacher_email, t.designation
        FROM course_offerings co
        JOIN courses c ON co.course_id = c.id
        JOIN semesters s ON co.semester_id = s.id
        LEFT JOIN teacher_courses tc ON co.id = tc.course_offering_id
        LEFT JOIN teachers t ON tc.teacher_id = t.id
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE co.id = $course_id";
    $course = $db->query($query)->fetch_assoc();
    
    // Get Schedule
    $schedule = [];
    $sched_q = $db->query("SELECT * FROM class_schedule WHERE course_offering_id = $course_id ORDER BY FIELD(day_of_week, 'Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), start_time");
    while ($row = $sched_q->fetch_assoc()) $schedule[] = $row;
    
    // Get Materials
    $materials = [];
    $mat_q = $db->query("SELECT * FROM course_materials WHERE course_offering_id = $course_id ORDER BY created_at DESC");
    while ($row = $mat_q->fetch_assoc()) $materials[] = $row;
    
} else {
    // List View
    $courses = [];
    $query = "SELECT co.id, c.course_code, c.course_name, c.credit_hours, co.section, s.name as semester_name,
        up.first_name, up.last_name, up.profile_picture as teacher_image,
        (SELECT COUNT(*) FROM attendance att WHERE att.enrollment_id = e.id AND att.status = 'present') as attended,
        (SELECT COUNT(*) FROM class_schedule WHERE course_offering_id = co.id) as total_days
        FROM enrollments e
        JOIN course_offerings co ON e.course_offering_id = co.id
        JOIN courses c ON co.course_id = c.id
        JOIN semesters s ON co.semester_id = s.id
        LEFT JOIN teacher_courses tc ON co.id = tc.course_offering_id
        LEFT JOIN teachers t ON tc.teacher_id = t.id
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE e.student_id = $student_id AND e.status = 'enrolled'
        ORDER BY s.start_date DESC";
    $result = $db->query($query);
    while ($row = $result->fetch_assoc()) {
        $row['attendance_pct'] = $row['total_days'] > 0 ? round(($row['attended'] / $row['total_days']) * 100, 1) : 100;
        $courses[] = $row;
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
                        <span class="bg-black text-white text-[10px] font-black uppercase px-2 py-1 tracking-widest">Student_Terminal</span>
                        <span class="bg-yellow-400 text-black text-[10px] font-black uppercase px-2 py-1 tracking-widest border border-black"><?php echo e($course['course_code']); ?></span>
                    </div>
                    <h1 class="text-4xl md:text-5xl font-black uppercase leading-none tracking-tighter mb-2">
                        <?php echo e($course['course_name']); ?>
                    </h1>
                    <p class="text-xs font-mono font-bold uppercase tracking-widest text-slate-500">
                        Semester: <?php echo e($course['semester_name']); ?> // Section: <?php echo e($course['section']); ?>

                    </p>
                </div>
                
                <a href="my-courses.php" class="btn-os bg-white hover:bg-black hover:text-white transition-colors">
                    <i class="fas fa-arrow-left"></i> Back to Courses
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Strategic Overview -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Data Registry -->
                <div class="os-card p-0 bg-white">
                    <div class="p-6 border-b-3 border-black bg-yellow-400 flex justify-between items-center">
                         <h3 class="text-xl font-black uppercase tracking-tight flex items-center gap-2">
                            <i class="fas fa-info-circle"></i> Course Intel
                        </h3>
                    </div>
                   
                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                            <div class="p-6 border-2 border-black hover:bg-black hover:text-white transition-colors group">
                                <p class="text-[9px] font-black text-slate-400 group-hover:text-slate-400 uppercase tracking-widest mb-2">Credits</p>
                                <p class="text-4xl font-black tracking-tighter"><?php echo e($course['credit_hours']); ?>.0 <span class="text-xs opacity-40 uppercase">Hours</span></p>
                            </div>
                            <div class="p-6 border-2 border-black hover:bg-black hover:text-white transition-colors group">
                                <p class="text-[9px] font-black text-slate-400 group-hover:text-slate-400 uppercase tracking-widest mb-2">Instructor</p>
                                <p class="text-xl font-black uppercase truncate"><?php echo $course['teacher_first'] ? e($course['teacher_first']  . ' ' . $course['teacher_last']) : 'TBA'; ?></p>
                                <p class="text-[10px] font-bold text-yellow-600 group-hover:text-yellow-400 mt-1 uppercase"><?php echo e($course['designation'] ?: 'Faculty Member'); ?></p>
                            </div>
                        </div>
                        <?php if ($course['description']): ?>
                        <div class="pl-6 border-l-4 border-yellow-400">
                            <p class="text-sm font-bold text-slate-600 uppercase leading-relaxed"><?php echo e($course['description']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Course Materials -->
                <div class="os-card p-0 bg-white">
                    <div class="p-6 border-b-3 border-black flex justify-between items-center">
                        <h3 class="text-xl font-black uppercase tracking-tight">
                            Materials
                        </h3>
                        <span class="bg-black text-white text-[9px] font-black px-2 py-1 uppercase tracking-widest"><?php echo count($materials); ?> Files</span>
                    </div>

                    <?php if (empty($materials)): ?>
                        <div class="py-12 text-center border-b-3 border-dashed border-black/5 bg-slate-50">
                            <i class="fas fa-box-open text-4xl text-slate-300 mb-4"></i>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No materials uploaded</p>
                        </div>
                    <?php else: ?>
                        <div class="divide-y-2 divide-slate-100">
                            <?php foreach ($materials as $mat): 
                                $ext = strtolower(pathinfo($mat['file_path'], PATHINFO_EXTENSION));
                                $icon = match($ext) {
                                    'pdf' => 'fa-file-pdf',
                                    'doc', 'docx' => 'fa-file-word',
                                    'ppt', 'pptx' => 'fa-file-powerpoint',
                                    'zip', 'rar' => 'fa-file-zipper',
                                    default => 'fa-file'
                                };
                            ?>
                                <div class="p-6 hover:bg-yellow-50 transition-colors group flex items-center justify-between gap-4">
                                    <div class="flex items-start gap-4">
                                        <div class="os-icon-box bg-white text-black border-black group-hover:bg-black group-hover:text-white transition-colors">
                                            <i class="fas <?php echo $icon; ?>"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-black uppercase leading-tight mb-1 group-hover:underline"><?php echo e($mat['title']); ?></h4>
                                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest"><?php echo date('M d, Y', strtotime($mat['created_at'])); ?> // <?php echo strtoupper($ext); ?></p>
                                        </div>
                                    </div>
                                    <a href="<?php echo BASE_URL . '/uploads/materials/' . e($mat['file_path']); ?>" target="_blank" class="btn-os text-[9px] px-3 py-2 bg-white hover:bg-black hover:text-white">
                                        Download
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar Info -->
            <div class="space-y-8">
                <!-- Class Schedule -->
                <div class="os-card p-0 bg-white">
                     <div class="p-6 border-b-3 border-black bg-black text-white flex justify-between items-center">
                        <h3 class="text-xl font-black uppercase tracking-tight flex items-center gap-2">
                             <i class="fas fa-calendar-alt text-yellow-400"></i> Schedule
                        </h3>
                    </div>
                    <?php if (empty($schedule)): ?>
                        <div class="p-8 text-center">
                             <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No classes scheduled.</p>
                        </div>
                    <?php else: ?>
                        <div class="divide-y-2 divide-slate-100">
                            <?php foreach ($schedule as $slot): ?>
                                <div class="p-5 hover:bg-yellow-50 transition-colors">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-black text-xs uppercase bg-yellow-400 text-black px-1"><?php echo e($slot['day_of_week']); ?></span>
                                        <span class="text-[10px] font-black uppercase text-slate-500">Room <?php echo e($slot['room_number']); ?></span>
                                    </div>
                                    <div class="text-lg font-black tracking-tighter">
                                        <?php echo date('H:i', strtotime($slot['start_time'])); ?> - <?php echo date('H:i', strtotime($slot['end_time'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Links -->
                <div class="os-card p-0 bg-black text-white">
                    <div class="p-6 border-b-3 border-white/20">
                        <h3 class="text-lg font-black uppercase tracking-tight text-white">Quick Access</h3>
                    </div>
                    <div class="divide-y divide-white/10">
                        <a href="assignments.php?course_id=<?php echo $course_id; ?>" class="flex items-center justify-between p-5 hover:bg-yellow-400 hover:text-black transition-colors group">
                            <span class="text-[10px] font-black uppercase tracking-widest">Assignments</span>
                            <i class="fas fa-chevron-right text-[8px] opacity-50 group-hover:translate-x-1 transition-transform"></i>
                        </a>
                        <a href="attendance.php?course_id=<?php echo $course_id; ?>" class="flex items-center justify-between p-5 hover:bg-yellow-400 hover:text-black transition-colors group">
                            <span class="text-[10px] font-black uppercase tracking-widest">Attendance Record</span>
                            <i class="fas fa-chevron-right text-[8px] opacity-50 group-hover:translate-x-1 transition-transform"></i>
                        </a>
                        <a href="results.php" class="flex items-center justify-between p-5 hover:bg-yellow-400 hover:text-black transition-colors group">
                            <span class="text-[10px] font-black uppercase tracking-widest">Exam Results</span>
                            <i class="fas fa-chevron-right text-[8px] opacity-50 group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Course List (Terminal Hub) -->
        <div class="bg-white os-border os-shadow p-8 flex flex-col md:flex-row md:items-center justify-between gap-8 relative overflow-hidden">
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-4">
                    <span class="bg-black text-white text-[10px] font-black uppercase px-2 py-1 tracking-widest">Student_Terminal</span>
                    <span class="bg-yellow-400 text-black text-[10px] font-black uppercase px-2 py-1 tracking-widest border border-black">Active</span>
                </div>
                <h1 class="text-4xl md:text-6xl font-black uppercase leading-none tracking-tighter mb-2">
                    My <span class="text-transparent bg-clip-text bg-gradient-to-r from-black to-slate-500">Courses</span>
                </h1>
                <p class="text-xs font-mono font-bold uppercase tracking-widest text-slate-500">
                    Enrolled Modules
                </p>
            </div>
             <div class="hidden md:block">
                <i class="fas fa-layer-group text-6xl text-black opacity-10"></i>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
            <?php if (empty($courses)): ?>
                <div class="md:col-span-2 xl:col-span-3 py-20 text-center border-3 border-dashed border-slate-300">
                    <i class="fas fa-box-open text-6xl text-slate-200 mb-6"></i>
                    <h3 class="text-xl font-black uppercase tracking-tight text-slate-400">No Enrollment Data</h3>
                    <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest mt-2">Contact Administration</p>
                </div>
            <?php else: ?>
                <?php foreach ($courses as $c): 
                    $pct = $c['attendance_pct'];
                    $is_low = $pct < 75;
                ?>
                    <a href="?id=<?php echo $c['id']; ?>" class="os-card p-0 bg-white group hover:-translate-y-2 transition-transform block h-full flex flex-col">
                         <div class="p-6 border-b-3 border-black bg-slate-50 group-hover:bg-yellow-400 transition-colors">
                            <div class="flex justify-between items-start mb-4">
                                <span class="bg-black text-white text-[10px] font-black uppercase px-2 py-1"><?php echo e($c['course_code']); ?></span>
                                <div class="os-icon-box bg-white text-black border-black group-hover:bg-black group-hover:text-white">
                                    <?php echo e($c['section']); ?>
                                </div>
                            </div>
                            <h3 class="text-2xl font-black uppercase leading-tight group-hover:text-black line-clamp-2 min-h-[3.5rem]">
                                <?php echo e($c['course_name']); ?>
                            </h3>
                        </div>

                        <div class="p-6 flex-1 flex flex-col justify-between">
                            <div class="flex items-center gap-4 mb-6">
                                <div class="w-10 h-10 border-2 border-black bg-slate-200 flex items-center justify-center overflow-hidden">
                                     <?php if ($c['teacher_image']): ?>
                                        <img src="<?php echo ASSETS_URL . '/uploads/profiles/' . $c['teacher_image']; ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="font-black text-sm"><?php echo strtoupper(substr($c['first_name'] ?? 'T', 0, 1)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Instructor</p>
                                    <p class="text-[11px] font-black text-black truncate italic leading-none uppercase"><?php echo e($c['first_name'] . ' ' . $c['last_name']); ?></p>
                                </div>
                            </div>

                            <!-- Attendance Pulse -->
                            <div>
                                <div class="flex items-center justify-between text-[10px] font-black uppercase mb-1">
                                    <span class="text-slate-400">Attendance</span>
                                    <span class="<?php echo $is_low ? 'text-red-600' : 'text-black'; ?>"><?php echo $pct; ?>%</span>
                                </div>
                                <div class="w-full h-3 border-2 border-black p-0.5">
                                    <div class="h-full <?php echo $is_low ? 'bg-red-500' : 'bg-black'; ?>" style="width: <?php echo $pct; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        
                         <div class="p-4 border-t-2 border-black bg-slate-50 text-center">
                            <span class="text-[10px] font-black uppercase tracking-widest">Access Terminal <i class="fas fa-chevron-right ml-1"></i></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
