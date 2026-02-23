<?php
/**
 * Department Admin Dashboard - Academix OS
 * ACADEMIX - Premium Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('admin');

$page_title = 'Admin Dashboard';
$user_id = get_current_user_id();

// Get admin's department(s)
$stmt = $db->prepare("SELECT d.* FROM departments d JOIN department_admins da ON d.id = da.department_id WHERE da.user_id = ? AND d.deleted_at IS NULL");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$departments = [];
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}

$department_ids = array_column($departments, 'id');
$dept_id_list = !empty($department_ids) ? implode(',', $department_ids) : '0';

// Get statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM students WHERE department_id IN ($dept_id_list)) as students,
    (SELECT COUNT(*) FROM teachers WHERE department_id IN ($dept_id_list)) as teachers,
    (SELECT COUNT(*) FROM courses WHERE department_id IN ($dept_id_list) AND deleted_at IS NULL) as courses,
    (SELECT COUNT(*) FROM course_offerings co JOIN courses c ON co.course_id = c.id WHERE c.department_id IN ($dept_id_list) AND co.status = 'open') as offerings,
    (SELECT COUNT(DISTINCT sm.id) FROM student_marks sm 
        JOIN enrollments e ON sm.enrollment_id = e.id 
        JOIN course_offerings co ON e.course_offering_id = co.id 
        JOIN courses c ON co.course_id = c.id 
        WHERE c.department_id IN ($dept_id_list) AND sm.status = 'submitted') as pending_marks";

$result = $db->query($stats_query);
$stats = $result->fetch_assoc();

// Sidebar
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<div class="space-y-8 animate-in">
    <!-- Header -->
    <div class="bg-white os-border os-shadow p-8 flex flex-col md:flex-row gap-8 justify-between relative overflow-hidden">
        <div class="absolute top-0 right-0 p-4 opacity-10 pointer-events-none">
            <i class="fas fa-shield-halved text-9xl text-black transform rotate-12"></i>
        </div>
        
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <span class="bg-black text-white text-[10px] font-black uppercase px-2 py-1 tracking-widest">Admin_Console</span>
                <span class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest">
                    <span class="w-2 h-2 bg-green-500 rounded-full border border-black"></span>
                    System_Active
                </span>
            </div>
            <h1 class="text-4xl md:text-6xl font-black uppercase leading-none tracking-tighter mb-2">
                ADMIN <span class="bg-yellow-400 px-2 box-decoration-clone text-black">DASHBOARD</span>
            </h1>
            <div class="flex flex-wrap gap-2 mt-2">
                <?php foreach ($departments as $dept): ?>
                    <span class="text-[10px] font-mono font-bold uppercase border border-black px-1 text-slate-500">
                        DEPT: <?php echo e($dept['code']); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="relative z-10 flex items-end justify-between md:justify-end gap-8">
            <div class="text-right hidden sm:block">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] mb-1 text-slate-400">Current Session</p>
                <p class="text-xl font-black uppercase font-mono bg-black text-white px-3 py-1 inline-block transform -rotate-1">
                    <?php echo date('Y'); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Students -->
        <a href="students.php" class="os-card p-6 flex flex-col justify-between h-[200px] group">
            <div class="flex justify-between items-start">
                <div class="os-icon-box">
                    <i class="fas fa-user-graduate text-lg"></i>
                </div>
                <i class="fas fa-arrow-up-right text-xl transition-transform group-hover:translate-x-1 group-hover:-translate-y-1"></i>
            </div>
            <div>
                <h3 class="text-5xl font-black mb-2 leading-none"><?php echo number_format($stats['students']); ?></h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-black">Total Students</p>
            </div>
        </a>

        <!-- Teachers -->
        <a href="teachers.php" class="os-card p-6 flex flex-col justify-between h-[200px] group">
            <div class="flex justify-between items-start">
                <div class="os-icon-box bg-white text-black">
                    <i class="fas fa-chalkboard-teacher text-lg"></i>
                </div>
                <i class="fas fa-arrow-up-right text-xl transition-transform group-hover:translate-x-1 group-hover:-translate-y-1"></i>
            </div>
            <div>
                <h3 class="text-5xl font-black mb-2 leading-none"><?php echo number_format($stats['teachers']); ?></h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-black">Faculty Members</p>
            </div>
        </a>

        <!-- Courses -->
        <a href="courses.php" class="os-card p-6 flex flex-col justify-between h-[200px] group">
            <div class="flex justify-between items-start">
                <div class="os-icon-box bg-white text-black">
                    <i class="fas fa-book text-lg"></i>
                </div>
                 <i class="fas fa-arrow-up-right text-xl transition-transform group-hover:translate-x-1 group-hover:-translate-y-1"></i>
            </div>
            <div>
                <h3 class="text-5xl font-black mb-2 leading-none"><?php echo number_format($stats['courses']); ?></h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-black">Active Courses</p>
            </div>
        </a>

        <!-- Marks Verification -->
        <a href="marks-verification.php" class="os-card p-6 flex flex-col justify-between h-[200px] group">
            <div class="flex justify-between items-start">
                <div class="os-icon-box bg-white text-black relative">
                    <i class="fas fa-check-double text-lg"></i>
                    <?php if ($stats['pending_marks'] > 0): ?>
                        <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 border-2 border-white rounded-full"></span>
                    <?php endif; ?>
                </div>
                 <i class="fas fa-arrow-up-right text-xl transition-transform group-hover:translate-x-1 group-hover:-translate-y-1"></i>
            </div>
            <div>
                <h3 class="text-5xl font-black mb-2 leading-none"><?php echo number_format($stats['pending_marks']); ?></h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-black">Pending Marks</p>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Quick Actions -->
        <div class="lg:col-span-1 os-card p-0 bg-white h-fit">
            <div class="p-6 border-b-3 border-black bg-black text-white">
                <h3 class="text-xl font-black uppercase tracking-tight">Quick Actions</h3>
                <p class="text-[10px] font-mono opacity-60 uppercase mt-1">ADMINISTRATIVE TASKS</p>
            </div>
            <div class="divide-y-2 divide-black">
                <a href="course-offerings.php?action=create" class="flex items-center gap-4 p-4 hover:bg-yellow-400 group transition-colors">
                    <div class="w-8 h-8 flex items-center justify-center border border-black bg-white group-hover:bg-black group-hover:text-white transition-colors">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-black uppercase">Create Offering</h4>
                        <p class="text-[10px] font-mono text-slate-500 group-hover:text-black">New course section</p>
                    </div>
                </a>
                <a href="teacher-assignments.php" class="flex items-center gap-4 p-4 hover:bg-yellow-400 group transition-colors">
                    <div class="w-8 h-8 flex items-center justify-center border border-black bg-white group-hover:bg-black group-hover:text-white transition-colors">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-black uppercase">Assign Teacher</h4>
                        <p class="text-[10px] font-mono text-slate-500 group-hover:text-black">Allocale faculty</p>
                    </div>
                </a>
                <a href="class-schedule.php?action=create" class="flex items-center gap-4 p-4 hover:bg-yellow-400 group transition-colors">
                    <div class="w-8 h-8 flex items-center justify-center border border-black bg-white group-hover:bg-black group-hover:text-white transition-colors">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-black uppercase">Add Schedule</h4>
                        <p class="text-[10px] font-mono text-slate-500 group-hover:text-black">Manage timetable</p>
                    </div>
                </a>
                <a href="exam-eligibility.php" class="flex items-center gap-4 p-4 hover:bg-yellow-400 group transition-colors">
                    <div class="w-8 h-8 flex items-center justify-center border border-black bg-white group-hover:bg-black group-hover:text-white transition-colors">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-black uppercase">Check Eligibility</h4>
                        <p class="text-[10px] font-mono text-slate-500 group-hover:text-black">Exam validation</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Recent Activity Table -->
        <div class="lg:col-span-2 os-card p-0 bg-white">
            <div class="p-6 border-b-3 border-black bg-yellow-400 flex justify-between items-center">
                <h3 class="text-xl font-black uppercase tracking-tight">Recent Offerings</h3>
                <span class="bg-black text-white text-[10px] font-mono font-bold px-2 py-1 uppercase">Live Data</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b-3 border-black">
                        <tr>
                            <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest">Course Info</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest">Section</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest">Enrollment</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-2 divide-black/10">
                        <?php
                        $offerings_query = "SELECT co.*, c.course_code, c.course_name 
                            FROM course_offerings co 
                            JOIN courses c ON co.course_id = c.id 
                            WHERE c.department_id IN ($dept_id_list) 
                            ORDER BY co.created_at DESC LIMIT 5";
                        $offerings_result = $db->query($offerings_query);
                        
                        if ($offerings_result->num_rows > 0):
                            while ($offering = $offerings_result->fetch_assoc()):
                        ?>
                        <tr class="hover:bg-slate-50 group">
                            <td class="px-6 py-4">
                                <div class="text-sm font-black uppercase"><?php echo e($offering['course_code']); ?></div>
                                <div class="text-[10px] font-mono uppercase text-slate-500"><?php echo e($offering['course_name']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs font-bold uppercase border border-black px-2 inline-block bg-white shadow-[2px_2px_0px_#000]">
                                    <?php echo e($offering['section']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 w-20 h-2 bg-slate-200 border border-black">
                                        <div class="h-full bg-black" style="width: <?php echo ($offering['enrolled_students'] / $offering['max_students']) * 100; ?>%"></div>
                                    </div>
                                    <span class="text-[10px] font-mono font-bold"><?php echo $offering['enrolled_students']; ?>/<?php echo $offering['max_students']; ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php if($offering['status'] === 'open'): ?>
                                    <span class="text-[10px] font-black bg-green-100 text-green-700 px-2 py-1 uppercase border border-green-700">OPEN</span>
                                <?php elseif($offering['status'] === 'closed'): ?>
                                    <span class="text-[10px] font-black bg-red-100 text-red-700 px-2 py-1 uppercase border border-red-700">CLOSED</span>
                                <?php else: ?>
                                    <span class="text-[10px] font-black bg-slate-100 text-slate-700 px-2 py-1 uppercase border border-slate-700"><?php echo strtoupper($offering['status']); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-xs font-mono text-slate-500">
                                NO DATA FOUND
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
