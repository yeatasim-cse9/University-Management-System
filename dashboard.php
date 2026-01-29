<?php
/**
 * Student Dashboard - Academix OS
 * ACADEMIX - Premium Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('student');

$page_title = 'Dashboard';
$user_id = get_current_user_id();

// Get Student Data
$stmt = $db->prepare("
    SELECT s.*, up.first_name, up.last_name, up.profile_picture, d.name as dept_name, d.code as dept_code
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$student_id = $student['id'];

// Get Real-time Stats
$stats = [
    'cgpa' => 3.85, // Placeholder as per previous code
    'attendance' => 0,
    'pending_assignments' => 0,
    'enrolled_courses' => 0
];

// 2. Attendance Pulse
$att_q = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN a.status IN ('present', 'late') THEN 1 ELSE 0 END) as present
    FROM attendance a
    JOIN enrollments e ON a.enrollment_id = e.id
    WHERE e.student_id = $student_id";
$att_res = $db->query($att_q)->fetch_assoc();
$stats['attendance'] = $att_res['total'] > 0 ? round(($att_res['present'] / $att_res['total']) * 100, 1) : 100;

// 3. Pending Assignments
$asgn_q = "SELECT COUNT(*) as count 
    FROM assignments a
    JOIN enrollments e ON a.course_offering_id = e.course_offering_id
    LEFT JOIN assignment_submissions sub ON a.id = sub.assignment_id AND sub.student_id = $student_id
    WHERE e.student_id = $student_id AND a.status = 'published' AND sub.id IS NULL AND a.due_date > NOW()";
$stats['pending_assignments'] = $db->query($asgn_q)->fetch_assoc()['count'];

// 4. Enrolled Courses
$stats['enrolled_courses'] = $db->query("SELECT COUNT(*) FROM enrollments WHERE student_id = $student_id AND status = 'enrolled'")->fetch_row()[0];

// 5. Today's Schedule
$today = date('l');
$schedule = [];
$sched_q = "SELECT cs.*, c.course_code, c.course_name 
    FROM class_schedule cs
    JOIN course_offerings co ON cs.course_offering_id = co.id
    JOIN courses c ON co.course_id = c.id
    JOIN enrollments e ON co.id = e.course_offering_id
    WHERE e.student_id = $student_id AND cs.day_of_week = '$today'
    ORDER BY cs.start_time ASC";
$sched_res = $db->query($sched_q);
while($row = $sched_res->fetch_assoc()) $schedule[] = $row;

// 6. Recent Notices
$notices = [];
$not_q = "SELECT * FROM notices WHERE status = 'published' ORDER BY created_at DESC LIMIT 3";
$not_res = $db->query($not_q);
while($row = $not_res->fetch_assoc()) $notices[] = $row;

// Sidebar Content
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<div class="space-y-8 animate-in">
    <!-- Header -->
    <div class="bg-white os-border os-shadow p-8 flex flex-col md:flex-row gap-8 justify-between relative overflow-hidden">
        <div class="absolute top-0 right-0 p-4 opacity-10 pointer-events-none">
            <i class="fas fa-microchip text-9xl text-black transform -rotate-12"></i>
        </div>
        
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <span class="bg-black text-white text-[10px] font-black uppercase px-2 py-1 tracking-widest">Student_Terminal</span>
                <span class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest">
                    <span class="w-2 h-2 bg-green-500 rounded-full border border-black"></span>
                    Link_Active
                </span>
            </div>
            <h1 class="text-4xl md:text-6xl font-black uppercase leading-none tracking-tighter mb-2">
                HELLO, <span class="bg-yellow-400 px-2 box-decoration-clone text-black"><?php echo explode(' ', $student['first_name'])[0]; ?></span>
            </h1>
            <p class="text-xs font-mono font-bold uppercase tracking-widest text-slate-500">
                ID: <?php echo e($student['student_id']); ?> // Dept: <?php echo e($student['dept_code']); ?>
            </p>
        </div>

        <div class="relative z-10 flex items-end justify-between md:justify-end gap-8">
            <div class="text-right hidden sm:block">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] mb-1 text-slate-400">System Time</p>
                <p class="text-xl font-black uppercase font-mono bg-black text-white px-3 py-1 inline-block transform -rotate-1">
                    <?php echo date('H:i'); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Alert Ticker -->
    <?php if ($stats['attendance'] < 75 || $stats['pending_assignments'] > 0): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php if ($stats['attendance'] < 75): ?>
        <div class="os-card p-6 bg-red-500 text-white border-black">
            <div class="flex items-center gap-4">
                <i class="fas fa-triangle-exclamation text-3xl"></i>
                <div>
                    <h3 class="text-lg font-black uppercase leading-none">Attendance Warning</h3>
                    <p class="text-xs font-mono mt-1">CURRENT: <?php echo $stats['attendance']; ?>% // REQUIRED: 75%</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($stats['pending_assignments'] > 0): ?>
        <a href="assignments.php" class="os-card p-6 bg-yellow-400 text-black border-black hover:bg-yellow-500 transition-colors">
            <div class="flex items-center gap-4">
                <i class="fas fa-clock text-3xl"></i>
                <div>
                    <h3 class="text-lg font-black uppercase leading-none">Pending Tasks</h3>
                    <p class="text-xs font-mono font-bold mt-1"><?php echo $stats['pending_assignments']; ?> ASSIGNMENTS OVERDUE/PENDING</p>
                </div>
                <i class="fas fa-arrow-right ml-auto"></i>
            </div>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- CGPA -->
        <div class="os-card p-6 flex flex-col justify-between h-[200px] group">
            <div class="flex justify-between items-start">
                <div class="os-icon-box">
                    <i class="fas fa-chart-line text-lg"></i>
                </div>
                <!-- Static card, so no arrow or maybe a static one -->
            </div>
            <div>
                <h3 class="text-5xl font-black mb-2 leading-none"><?php echo number_format($stats['cgpa'], 2); ?></h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-black">Academic Standing</p>
            </div>
        </div>

        <!-- Attendance -->
        <div class="os-card p-6 flex flex-col justify-between h-[200px] group">
            <div class="flex justify-between items-start">
                <div class="os-icon-box bg-white text-black">
                    <i class="fas fa-fingerprint text-lg"></i>
                </div>
            </div>
            <div>
                <h3 class="text-5xl font-black mb-2 leading-none"><?php echo $stats['attendance']; ?>%</h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-black">Overall Attendance</p>
                <div class="h-1 w-full bg-slate-200 mt-4 border border-black">
                    <div class="h-full bg-black transition-all" style="width: <?php echo $stats['attendance']; ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Courses -->
        <a href="my-courses.php" class="os-card p-6 flex flex-col justify-between h-[200px] group">
            <div class="flex justify-between items-start">
                <div class="os-icon-box bg-white text-black">
                    <i class="fas fa-layer-group text-lg"></i>
                </div>
                <i class="fas fa-arrow-up-right text-xl transition-transform group-hover:translate-x-1 group-hover:-translate-y-1"></i>
            </div>
            <div>
                <h3 class="text-5xl font-black mb-2 leading-none"><?php echo $stats['enrolled_courses']; ?></h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-black">Active Courses</p>
            </div>
        </a>

        <!-- Assignments -->
        <a href="assignments.php" class="os-card p-6 flex flex-col justify-between h-[200px] group">
            <div class="flex justify-between items-start">
                <div class="os-icon-box bg-white text-black">
                    <i class="fas fa-file-alt text-lg"></i>
                </div>
                <i class="fas fa-arrow-up-right text-xl transition-transform group-hover:translate-x-1 group-hover:-translate-y-1"></i>
            </div>
            <div>
                <h3 class="text-5xl font-black mb-2 leading-none"><?php echo $stats['pending_assignments']; ?></h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-black">Pending Tasks</p>
            </div>
        </a>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Schedule -->
        <div class="lg:col-span-2 os-card p-0 bg-white">
            <div class="p-6 border-b-3 border-black bg-yellow-400 flex justify-between items-center">
                <h3 class="text-xl font-black uppercase tracking-tight flex items-center gap-2">
                    <i class="fas fa-calendar-day"></i> Today's Schedule
                </h3>
                <span class="bg-black text-white text-[10px] font-mono font-bold px-2 py-1 uppercase">
                    <?php echo date('l'); ?>
                </span>
            </div>

            <div class="p-6">
                <?php if (empty($schedule)): ?>
                    <div class="py-12 text-center border-2 border-dashed border-slate-300">
                        <p class="text-sm font-black uppercase mb-1">No Classes Today</p>
                        <p class="text-xs text-slate-500 font-mono">SYSTEM STANDBY</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($schedule as $slot): 
                            $is_ongoing = (date('H:i') >= $slot['start_time'] && date('H:i') <= $slot['end_time']);
                        ?>
                        <div class="p-4 border-2 border-black transition-all hover:bg-slate-50 <?php echo $is_ongoing ? 'bg-yellow-50 border-l-8 border-l-black' : ''; ?>">
                            <div class="flex justify-between items-center gap-4">
                                <div class="flex items-start gap-4">
                                    <div class="text-center min-w-[60px]">
                                        <div class="text-sm font-black bg-black text-white px-2 py-1 mb-1">
                                            <?php echo date('H:i', strtotime($slot['start_time'])); ?>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-[10px] font-black uppercase border border-black px-1"><?php echo e($slot['course_code']); ?></span>
                                            <?php if ($is_ongoing): ?>
                                                <span class="text-[9px] font-black text-green-600 uppercase animate-pulse">● LIVE</span>
                                            <?php endif; ?>
                                        </div>
                                        <h4 class="text-lg font-black uppercase leading-none"><?php echo e($slot['course_name']); ?></h4>
                                    </div>
                                </div>
                                <div class="text-right hidden sm:block">
                                    <p class="text-[9px] font-black uppercase text-slate-400">Room</p>
                                    <p class="text-sm font-black uppercase"><?php echo e($slot['room_number']); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Notices -->
        <div class="os-card p-0 bg-white">
            <div class="p-6 border-b-3 border-black bg-black text-white">
                <h3 class="text-xl font-black uppercase tracking-tight">System Notices</h3>
                <p class="text-[10px] font-mono opacity-60 uppercase mt-1">LATEST UPDATES</p>
            </div>
            
            <div class="divide-y-2 divide-black max-h-[400px] overflow-y-auto">
                <?php foreach ($notices as $notice): ?>
                <div class="p-5 hover:bg-yellow-50 transition-colors group">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-[9px] font-mono font-bold bg-slate-100 border border-black px-1">
                            <?php echo date('d M', strtotime($notice['created_at'])); ?>
                        </span>
                    </div>
                    <h4 class="text-sm font-black uppercase leading-tight mb-2 group-hover:underline">
                        <?php echo e($notice['title']); ?>
                    </h4>
                    <p class="text-xs font-mono text-slate-600 line-clamp-2 mb-3">
                        <?php echo strip_tags($notice['content']); ?>
                    </p>
                    <a href="notices.php?id=<?php echo $notice['id']; ?>" class="text-[10px] font-black uppercase flex items-center gap-1 hover:gap-2 transition-all">
                        Read Full <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="p-4 border-t-3 border-black bg-slate-50">
                <a href="notices.php" class="btn-os w-full text-xs py-3">View All Notices</a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
