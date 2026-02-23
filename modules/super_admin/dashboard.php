<?php
/**
 * Super Admin Dashboard - Academix OS
 * ACADEMIX - University Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('super_admin');

$page_title = 'Dashboard';

// Get statistics
$stats = [];
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'student' AND deleted_at IS NULL) as students,
        (SELECT COUNT(*) FROM users WHERE role = 'teacher' AND deleted_at IS NULL) as teachers,
        (SELECT COUNT(*) FROM departments WHERE deleted_at IS NULL) as departments,
        (SELECT COUNT(*) FROM courses WHERE deleted_at IS NULL) as courses,
        (SELECT COUNT(*) FROM audit_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as daily_events
";
$result = $db->query($stats_query);
$stats = $result->fetch_assoc();

// Active semester
$result = $db->query("SELECT s.*, ay.year FROM semesters s JOIN academic_years ay ON s.academic_year_id = ay.id WHERE s.status = 'active' LIMIT 1");
$active_semester = $result->fetch_assoc();

// Recent Activity
$recent_activities = [];
$result = $db->query("
    SELECT al.*, u.username, u.role as user_role 
    FROM audit_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC LIMIT 8
");
while ($row = $result->fetch_assoc()) {
    $recent_activities[] = $row;
}

// Departmental Density
$dept_density = [];
$result = $db->query("
    SELECT d.name, d.code,
    (SELECT COUNT(*) FROM students s JOIN users u ON s.user_id = u.id WHERE s.department_id = d.id AND u.deleted_at IS NULL) as s_count,
    (SELECT COUNT(*) FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.department_id = d.id AND u.deleted_at IS NULL) as t_count
    FROM departments d WHERE d.deleted_at IS NULL LIMIT 5
");
while ($row = $result->fetch_assoc()) {
    $dept_density[] = $row;
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
            <i class="fas fa-microchip text-9xl text-black transform rotate-12"></i>
        </div>
        
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <span class="bg-black text-white text-[10px] font-black uppercase px-2 py-1 tracking-widest">Root_Access</span>
                <span class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest">
                    <span class="w-2 h-2 bg-green-500 rounded-full border border-black"></span>
                    System_Online
                </span>
            </div>
            <h1 class="text-4xl md:text-6xl font-black uppercase leading-none tracking-tighter mb-2">
                SUPER <span class="bg-yellow-400 px-2 box-decoration-clone text-black">ADMIN</span>
            </h1>
            <p class="text-[10px] font-mono font-bold uppercase tracking-widest text-slate-500">
                Managing: <?php echo UNIVERSITY_NAME; ?>
            </p>
        </div>

        <div class="relative z-10 flex items-end justify-between md:justify-end gap-8">
            <div class="text-right hidden sm:block">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] mb-1 text-slate-400">Current Date</p>
                <p class="text-xl font-black uppercase font-mono bg-black text-white px-3 py-1 inline-block transform -rotate-1">
                    <?php echo date('F d, Y'); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Students -->
        <a href="users.php?role=student" class="os-card p-6 flex flex-col justify-between h-[200px] group">
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
        <a href="users.php?role=teacher" class="os-card p-6 flex flex-col justify-between h-[200px] group">
            <div class="flex justify-between items-start">
                <div class="os-icon-box bg-white text-black">
                    <i class="fas fa-chalkboard-teacher text-lg"></i>
                </div>
                 <i class="fas fa-arrow-up-right text-xl transition-transform group-hover:translate-x-1 group-hover:-translate-y-1"></i>
            </div>
            <div>
                <h3 class="text-5xl font-black mb-2 leading-none"><?php echo number_format($stats['teachers']); ?></h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-black">Total Faculty</p>
            </div>
        </a>

        <!-- Departments -->
        <a href="departments.php" class="os-card p-6 flex flex-col justify-between h-[200px] group">
            <div class="flex justify-between items-start">
                <div class="os-icon-box bg-white text-black">
                    <i class="fas fa-layer-group text-lg"></i>
                </div>
                 <i class="fas fa-arrow-up-right text-xl transition-transform group-hover:translate-x-1 group-hover:-translate-y-1"></i>
            </div>
            <div>
                <h3 class="text-5xl font-black mb-2 leading-none"><?php echo number_format($stats['departments']); ?></h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-black">Departments</p>
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
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-black">Total Courses</p>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- System Logs -->
        <div class="lg:col-span-2 os-card p-0 bg-white">
            <div class="p-6 border-b-3 border-black bg-yellow-400 flex justify-between items-center">
                <h3 class="text-xl font-black uppercase tracking-tight">System Logs</h3>
                <span class="bg-black text-white text-[10px] font-mono font-bold px-2 py-1 uppercase">Live Feed</span>
            </div>

            <div class="p-6">
                <?php if (empty($recent_activities)): ?>
                    <div class="py-12 text-center border-2 border-dashed border-slate-300">
                        <p class="text-sm font-black uppercase mb-1">No Activity Detected</p>
                        <p class="text-xs text-slate-500 font-mono">SYSTEM IDLE</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($recent_activities as $activity): 
                            $role_badge = match($activity['user_role']) {
                                'super_admin' => 'bg-red-500 text-white',
                                'admin' => 'bg-blue-500 text-white',
                                'teacher' => 'bg-green-500 text-white',
                                'student' => 'bg-yellow-400 text-black',
                                default => 'bg-slate-500 text-white'
                            };
                        ?>
                        <div class="p-3 border-2 border-slate-100 hover:border-black transition-colors flex items-center gap-4 group">
                            <span class="w-2 h-2 rounded-full bg-black"></span>
                            <div class="flex-1">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-[10px] font-black uppercase">
                                        <span class="<?php echo $role_badge; ?> px-1 border border-black mr-2"><?php echo strtoupper($activity['user_role']); ?></span>
                                        <?php echo e($activity['username'] ?? 'SYSTEM'); ?>
                                    </span>
                                    <span class="text-[9px] font-mono text-slate-400 uppercase"><?php echo time_ago($activity['created_at']); ?></span>
                                </div>
                                <p class="text-[10px] font-mono text-slate-600 truncate uppercase w-full">
                                    <span class="font-bold text-black"><?php echo e($activity['action']); ?></span> // <?php echo e($activity['details']); ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Infrastructure & Quick Actions -->
        <div class="space-y-8">
            <!-- Semester Card -->
            <div class="os-card p-6 bg-black text-white">
                <h3 class="text-lg font-black uppercase tracking-widest mb-4 border-b-2 border-white/20 pb-2">Active Semester</h3>
                
                <h2 class="text-2xl font-black text-yellow-400 uppercase leading-none mb-1">
                    <?php echo e($active_semester['name'] ?? 'None'); ?>
                </h2>
                <p class="text-xs font-mono uppercase opacity-70 mb-6">
                    Year: <?php echo e($active_semester['year'] ?? 'N/A'); ?>

                </p>

                <div class="space-y-2">
                    <div class="flex justify-between text-[9px] font-black uppercase tracking-widest">
                        <span>Progress</span>
                        <span class="text-green-400">Running</span>
                    </div>
                    <div class="w-full h-2 bg-white/20 border border-white/30">
                        <div class="h-full bg-yellow-400 w-2/3"></div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="os-card p-6 bg-white">
                <h3 class="text-lg font-black uppercase tracking-widest mb-4 border-b-2 border-black pb-2">Admin Tools</h3>
                <div class="grid grid-cols-2 gap-3">
                    <a href="users.php?action=create" class="p-4 border-2 border-black hover:bg-yellow-400 transition-colors text-center group">
                        <i class="fas fa-plus text-xl mb-2 group-hover:scale-110 transition-transform"></i>
                        <p class="text-[9px] font-black uppercase">Add User</p>
                    </a>
                     <a href="departments.php?action=create" class="p-4 border-2 border-black hover:bg-yellow-400 transition-colors text-center group">
                        <i class="fas fa-building text-xl mb-2 group-hover:scale-110 transition-transform"></i>
                        <p class="text-[9px] font-black uppercase">Add Dept</p>
                    </a>
                     <a href="courses.php?action=create" class="p-4 border-2 border-black hover:bg-yellow-400 transition-colors text-center group">
                        <i class="fas fa-book text-xl mb-2 group-hover:scale-110 transition-transform"></i>
                        <p class="text-[9px] font-black uppercase">Add Course</p>
                    </a>
                     <a href="notices.php?action=create" class="p-4 border-2 border-black hover:bg-yellow-400 transition-colors text-center group">
                        <i class="fas fa-bullhorn text-xl mb-2 group-hover:scale-110 transition-transform"></i>
                        <p class="text-[9px] font-black uppercase">Notice</p>
                    </a>
                </div>
            </div>
            
             <!-- Sector Topology -->
            <div class="os-card p-6 bg-white">
                <h3 class="text-sm font-black uppercase tracking-widest mb-4 border-b-2 border-black pb-2">Sector Density</h3>
                <div class="space-y-3">
                    <?php foreach ($dept_density as $dept): ?>
                        <div class="flex justify-between items-center text-[10px] font-black uppercase border-b border-dashed border-slate-300 pb-1">
                            <span><?php echo e($dept['code']); ?></span>
                            <span class="font-mono"><?php echo $dept['s_count']; ?> STU / <?php echo $dept['t_count']; ?> FAC</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
