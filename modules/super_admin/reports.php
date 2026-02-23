<?php
/**
 * Intelligence & Analytics Hub
 * ACADEMIX - University Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('super_admin');

$page_title = 'Intelligence Hub';
$report_type = $_GET['type'] ?? 'overview';

// Get date range from filters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Overview Statistics
$stats = [];

// Total users by role
$result = $db->query("SELECT role, COUNT(*) as count FROM users WHERE deleted_at IS NULL GROUP BY role");
while ($row = $result->fetch_assoc()) {
    $stats['users'][$row['role']] = $row['count'];
}
$stats['users']['total'] = array_sum($stats['users']);

// Active users (logged in last 30 days)
$result = $db->query("SELECT COUNT(*) as count FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND deleted_at IS NULL");
$stats['active_users'] = $result->fetch_assoc()['count'];

// Departments
$result = $db->query("SELECT COUNT(*) as count FROM departments WHERE deleted_at IS NULL");
$stats['departments'] = $result->fetch_assoc()['count'];

// Courses
$result = $db->query("SELECT COUNT(*) as count FROM courses WHERE deleted_at IS NULL");
$stats['courses'] = $result->fetch_assoc()['count'];

// Enrollments
$result = $db->query("SELECT COUNT(*) as count FROM enrollments WHERE status = 'enrolled'");
$stats['enrollments'] = $result->fetch_assoc()['count'];

// Recent activity (last 7 days)
$result = $db->query("SELECT COUNT(*) as count FROM audit_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['recent_activity'] = $result->fetch_assoc()['count'];

// User Activity Report
$user_activity = [];
if ($report_type === 'user_activity') {
    $result = $db->query("SELECT u.username, u.role, u.last_login, u.created_at,
        (SELECT COUNT(*) FROM audit_logs WHERE user_id = u.id AND created_at BETWEEN '$start_date' AND '$end_date') as activity_count
        FROM users u 
        WHERE u.deleted_at IS NULL 
        ORDER BY u.last_login DESC 
        LIMIT 100");
    while ($row = $result->fetch_assoc()) {
        $user_activity[] = $row;
    }
}

// Department Report
$department_report = [];
if ($report_type === 'departments') {
    $result = $db->query("SELECT d.name, d.code,
        (SELECT COUNT(*) FROM students s WHERE s.department_id = d.id) as student_count,
        (SELECT COUNT(*) FROM teachers t WHERE t.department_id = d.id) as teacher_count,
        (SELECT COUNT(*) FROM courses c WHERE c.department_id = d.id AND c.deleted_at IS NULL) as course_count
        FROM departments d 
        WHERE d.deleted_at IS NULL 
        ORDER BY d.name");
    while ($row = $result->fetch_assoc()) {
        $department_report[] = $row;
    }
}

// Enrollment Report
$enrollment_report = [];
if ($report_type === 'enrollments') {
    $result = $db->query("SELECT c.course_code, c.course_name, d.name as department,
        COUNT(e.id) as enrolled_students,
        co.max_students,
        ROUND((COUNT(e.id) / co.max_students) * 100, 2) as fill_percentage
        FROM courses c
        JOIN departments d ON c.department_id = d.id
        LEFT JOIN course_offerings co ON c.id = co.course_id
        LEFT JOIN enrollments e ON co.id = e.course_offering_id AND e.status = 'enrolled'
        WHERE c.deleted_at IS NULL
        GROUP BY c.id, co.id
        ORDER BY enrolled_students DESC
        LIMIT 50");
    while ($row = $result->fetch_assoc()) {
        $enrollment_report[] = $row;
    }
}

// Audit Log Report
$audit_logs = [];
if ($report_type === 'audit') {
    $result = $db->query("SELECT al.*, u.username 
        FROM audit_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        WHERE al.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
        ORDER BY al.created_at DESC 
        LIMIT 200");
    while ($row = $result->fetch_assoc()) {
        $audit_logs[] = $row;
    }
}

// Login History Report
$login_history = [];
if ($report_type === 'login_history') {
    $result = $db->query("SELECT lh.*, u.role 
        FROM login_history lh 
        LEFT JOIN users u ON lh.user_id = u.id 
        WHERE lh.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
        ORDER BY lh.created_at DESC 
        LIMIT 200");
    while ($row = $result->fetch_assoc()) {
        $login_history[] = $row;
    }
}

// Student Performance Report
$student_performance = [];
if ($report_type === 'student_performance') {
    $result = $db->query("SELECT s.student_id, 
        CONCAT(up.first_name, ' ', up.last_name) as student_name,
        d.name as department,
        s.current_semester,
        s.cgpa,
        COUNT(e.id) as enrolled_courses
        FROM students s
        JOIN users u ON s.user_id = u.id
        JOIN user_profiles up ON u.id = up.user_id
        JOIN departments d ON s.department_id = d.id
        LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'enrolled'
        WHERE s.status = 'active'
        GROUP BY s.id
        ORDER BY s.cgpa DESC
        LIMIT 100");
    while ($row = $result->fetch_assoc()) {
        $student_performance[] = $row;
    }
}

// Sidebar menu
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

// Page content
ob_start();
?>

<div class="space-y-6">
    <!-- Intelligence Header -->
    <div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white">
         <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
                <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Analytics Engine</span>
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                    <span class="w-1.5 h-1.5 rounded-none bg-blue-500 inline-block mr-1"></span>
                    Real-time Data Stream
                </span>
            </div>
            <h1 class="text-3xl font-black uppercase tracking-tighter">Intelligence <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-cyan-500">Hub</span></h1>
        </div>
    </div>

    <!-- Navigation Matrix -->
    <div class="os-card p-0 overflow-hidden bg-white">
        <div class="bg-black p-2 border-b-2 border-black">
            <nav class="flex flex-wrap gap-2">
                <a href="?type=overview" class="btn-os <?php echo $report_type === 'overview' ? 'bg-yellow-400 text-black border-white shadow-[2px_2px_0px_#ffffff]' : 'bg-black text-white border-transparent hover:border-white hover:text-white'; ?> py-2 text-[10px]">
                    <i class="fas fa-chart-line mr-2"></i> Global Metrics
                </a>
                <a href="?type=user_activity" class="btn-os <?php echo $report_type === 'user_activity' ? 'bg-yellow-400 text-black border-white shadow-[2px_2px_0px_#ffffff]' : 'bg-black text-white border-transparent hover:border-white hover:text-white'; ?> py-2 text-[10px]">
                    <i class="fas fa-users mr-2"></i> Personnel Dynamics
                </a>
                <a href="?type=departments" class="btn-os <?php echo $report_type === 'departments' ? 'bg-yellow-400 text-black border-white shadow-[2px_2px_0px_#ffffff]' : 'bg-black text-white border-transparent hover:border-white hover:text-white'; ?> py-2 text-[10px]">
                    <i class="fas fa-building mr-2"></i> Infrastructure
                </a>
                <a href="?type=enrollments" class="btn-os <?php echo $report_type === 'enrollments' ? 'bg-yellow-400 text-black border-white shadow-[2px_2px_0px_#ffffff]' : 'bg-black text-white border-transparent hover:border-white hover:text-white'; ?> py-2 text-[10px]">
                    <i class="fas fa-book mr-2"></i> Enrollment Flow
                </a>
                <a href="?type=student_performance" class="btn-os <?php echo $report_type === 'student_performance' ? 'bg-yellow-400 text-black border-white shadow-[2px_2px_0px_#ffffff]' : 'bg-black text-white border-transparent hover:border-white hover:text-white'; ?> py-2 text-[10px]">
                    <i class="fas fa-graduation-cap mr-2"></i> Academic Merit
                </a>
                <a href="?type=audit" class="btn-os <?php echo $report_type === 'audit' ? 'bg-yellow-400 text-black border-white shadow-[2px_2px_0px_#ffffff]' : 'bg-black text-white border-transparent hover:border-white hover:text-white'; ?> py-2 text-[10px]">
                    <i class="fas fa-history mr-2"></i> Operations Audit
                </a>
                <a href="?type=login_history" class="btn-os <?php echo $report_type === 'login_history' ? 'bg-yellow-400 text-black border-white shadow-[2px_2px_0px_#ffffff]' : 'bg-black text-white border-transparent hover:border-white hover:text-white'; ?> py-2 text-[10px]">
                    <i class="fas fa-sign-in-alt mr-2"></i> Access Logs
                </a>
            </nav>
        </div>
    </div>


    <!-- Temporal Filters -->
    <?php if (in_array($report_type, ['user_activity', 'audit', 'login_history'])): ?>
        <div class="os-card p-6 bg-white">
            <form method="GET" class="flex flex-wrap items-end gap-6">
                <input type="hidden" name="type" value="<?php echo e($report_type); ?>">
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest italic">Temporal Start</label>
                    <input type="date" name="start_date" value="<?php echo e($start_date); ?>" class="px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase">
                </div>
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest italic">Temporal End</label>
                    <input type="date" name="end_date" value="<?php echo e($end_date); ?>" class="px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase">
                </div>
                <button type="submit" class="btn-os bg-black text-white border-black hover:bg-white hover:text-black">
                    <i class="fas fa-filter text-yellow-500 mr-2"></i> Sync Data Range
                </button>
            </form>
        </div>
    <?php endif; ?>

<!-- Report Content -->
    <?php if ($report_type === 'overview'): ?>
        <!-- Global Metrics Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Card: Total Users -->
            <div class="os-card p-6 bg-white flex flex-col justify-between group hover:-translate-y-1 transition-transform duration-300">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 bg-black text-white flex items-center justify-center border-2 border-black shadow-[4px_4px_0px_#000000]">
                        <i class="fas fa-users text-lg"></i>
                    </div>
                    <?php if ($stats['active_users'] > 0): ?>
                        <span class="px-2 py-1 bg-green-100 text-green-700 text-[9px] font-black uppercase tracking-widest border border-green-700"><?php echo $stats['active_users']; ?> Active</span>
                    <?php endif; ?>
                </div>
                <div>
                    <h3 class="text-4xl font-black text-black tracking-tighter mb-1"><?php echo $stats['users']['total']; ?></h3>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Total Personnel</p>
                </div>
                <div class="mt-4 pt-4 border-t-2 border-black">
                     <p class="text-[9px] font-bold text-slate-400 font-mono uppercase">Collective University Staff</p>
                </div>
            </div>

            <!-- Card: Students -->
            <div class="os-card p-6 bg-white flex flex-col justify-between group hover:-translate-y-1 transition-transform duration-300">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 bg-green-500 text-white flex items-center justify-center border-2 border-black shadow-[4px_4px_0px_#000000]">
                        <i class="fas fa-user-graduate text-lg"></i>
                    </div>
                </div>
                <div>
                     <h3 class="text-4xl font-black text-black tracking-tighter mb-1"><?php echo $stats['users']['student'] ?? 0; ?></h3>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Student Registry</p>
                </div>
                <div class="mt-4 pt-4 border-t-2 border-black">
                    <p class="text-[9px] font-bold text-slate-400 font-mono uppercase">Active Academic Candidates</p>
                </div>
            </div>

            <!-- Card: Teachers -->
             <div class="os-card p-6 bg-white flex flex-col justify-between group hover:-translate-y-1 transition-transform duration-300">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-500 text-white flex items-center justify-center border-2 border-black shadow-[4px_4px_0px_#000000]">
                        <i class="fas fa-chalkboard-teacher text-lg"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-4xl font-black text-black tracking-tighter mb-1"><?php echo $stats['users']['teacher'] ?? 0; ?></h3>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Academic Faculty</p>
                </div>
                <div class="mt-4 pt-4 border-t-2 border-black">
                     <p class="text-[9px] font-bold text-slate-400 font-mono uppercase">Instructors & Mentors</p>
                </div>
            </div>

            <!-- Card: Infrastructure -->
             <div class="os-card p-6 bg-white flex flex-col justify-between group hover:-translate-y-1 transition-transform duration-300">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 bg-orange-500 text-white flex items-center justify-center border-2 border-black shadow-[4px_4px_0px_#000000]">
                        <i class="fas fa-building text-lg"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-4xl font-black text-black tracking-tighter mb-1"><?php echo $stats['departments']; ?></h3>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Departments</p>
                </div>
                <div class="mt-4 pt-4 border-t-2 border-black">
                    <p class="text-[9px] font-bold text-slate-400 font-mono uppercase">Structural Academic Units</p>
                </div>
            </div>

            <!-- Card: Course Count -->
             <div class="os-card p-6 bg-white flex flex-col justify-between group hover:-translate-y-1 transition-transform duration-300">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-500 text-white flex items-center justify-center border-2 border-black shadow-[4px_4px_0px_#000000]">
                         <i class="fas fa-book text-lg"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-4xl font-black text-black tracking-tighter mb-1"><?php echo $stats['courses']; ?></h3>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Active Modules</p>
                </div>
                <div class="mt-4 pt-4 border-t-2 border-black">
                    <p class="text-[9px] font-bold text-slate-400 font-mono uppercase">Curriculum Units Defined</p>
                </div>
            </div>

            <!-- Card: Enrollments -->
            <div class="os-card p-6 bg-white flex flex-col justify-between group hover:-translate-y-1 transition-transform duration-300">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 bg-red-400 text-white flex items-center justify-center border-2 border-black shadow-[4px_4px_0px_#000000]">
                         <i class="fas fa-user-check text-lg"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-4xl font-black text-black tracking-tighter mb-1"><?php echo $stats['enrollments']; ?></h3>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Registry Flow</p>
                </div>
                <div class="mt-4 pt-4 border-t-2 border-black">
                    <p class="text-[9px] font-bold text-slate-400 font-mono uppercase">Total Course Subscriptions</p>
                </div>
            </div>

            <!-- Card: Admins -->
            <div class="os-card p-6 bg-white flex flex-col justify-between group hover:-translate-y-1 transition-transform duration-300">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 bg-yellow-400 text-black flex items-center justify-center border-2 border-black shadow-[4px_4px_0px_#000000]">
                         <i class="fas fa-user-shield text-lg"></i>
                    </div>
                </div>
                <div>
                     <h3 class="text-4xl font-black text-black tracking-tighter mb-1"><?php echo ($stats['users']['super_admin'] ?? 0) + ($stats['users']['admin'] ?? 0); ?></h3>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Operations Team</p>
                </div>
                <div class="mt-4 pt-4 border-t-2 border-black">
                    <p class="text-[9px] font-bold text-slate-400 font-mono uppercase">System Administrators</p>
                </div>
            </div>

            <!-- Card: Operations Audit -->
            <div class="os-card p-6 bg-white flex flex-col justify-between group hover:-translate-y-1 transition-transform duration-300">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 bg-black text-yellow-400 flex items-center justify-center border-2 border-black shadow-[4px_4px_0px_#000000]">
                         <i class="fas fa-microchip text-lg"></i>
                    </div>
                     <?php if ($stats['recent_activity'] > 0): ?>
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-[9px] font-black uppercase tracking-widest border border-yellow-700">Active</span>
                    <?php endif; ?>
                </div>
                <div>
                     <h3 class="text-4xl font-black text-black tracking-tighter mb-1"><?php echo $stats['recent_activity']; ?></h3>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Audit Events</p>
                </div>
                <div class="mt-4 pt-4 border-t-2 border-black">
                     <p class="text-[9px] font-bold text-slate-400 font-mono uppercase">System Actions (Last 7 Days)</p>
                </div>
            </div>
        </div>

        <div class="os-card p-6 bg-black text-white flex flex-col md:flex-row items-center justify-between gap-8 mt-6">
            <div>
                <h4 class="text-xl font-black italic uppercase tracking-widest mb-2">Detailed <span class="text-yellow-400">Intelligence</span> Report</h4>
                <p class="text-[10px] font-medium text-slate-400 uppercase tracking-[0.2em] font-mono leading-relaxed">Select a specific dimension from the matrix above to generate granular analytics and temporal datasets.</p>
            </div>
            <i class="fas fa-brain text-4xl text-yellow-400 animate-pulse"></i>
        </div>
<?php elseif ($report_type === 'user_activity'): ?>
    <!-- Personnel Dynamics Table -->
    <div class="os-card p-0 overflow-hidden bg-white">
        <div class="bg-black p-6 text-white flex justify-between items-center border-b-2 border-black">
             <div>
                <h3 class="text-xl font-black italic uppercase tracking-widest text-white">Personnel <span class="text-yellow-400">Dynamics</span></h3>
                <p class="text-[10px] font-bold font-mono text-slate-400 uppercase mt-1">Tracking temporal active state and engagement metrics...</p>
             </div>
             <button onclick="window.print()" class="btn-os bg-white text-black border-white hover:bg-yellow-400 hover:border-yellow-400 hover:text-black text-xs">
                 <i class="fas fa-print mr-2"></i> Export Analytics
             </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-black text-white">
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Personnel Node</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Access Clearance</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Latest Engagement</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-center border-r border-white/20">Activity Metric</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest">Initialization Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y-2 divide-slate-100">
                    <?php foreach ($user_activity as $user): ?>
                        <tr class="hover:bg-yellow-50 transition-all group border-l-2 border-r-2 border-transparent hover:border-black">
                            <td class="px-6 py-4 border-r border-slate-100">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-black text-yellow-400 flex items-center justify-center font-black text-xs shrink-0 border border-black shadow-[2px_2px_0px_rgba(0,0,0,0.2)]">
                                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-black uppercase leading-none mb-1">@<?php echo e($user['username']); ?></p>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase font-mono">Identity Confirmed</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 border-r border-slate-100">
                                <span class="px-2 py-1 border-2 border-black text-[9px] font-black uppercase tracking-widest <?php 
                                    echo $user['role'] === 'super_admin' ? 'bg-red-500 text-white' : 
                                        ($user['role'] === 'admin' ? 'bg-blue-500 text-white' : 
                                        ($user['role'] === 'teacher' ? 'bg-purple-500 text-white' : 'bg-green-500 text-white')); 
                                ?>">
                                    <?php echo e(ucwords(str_replace('_', ' ', $user['role']))); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 border-r border-slate-100">
                                <div class="flex items-center gap-2">
                                     <span class="w-2 h-2 border border-black <?php echo $user['last_login'] && (strtotime($user['last_login']) > strtotime('-1 hour')) ? 'bg-green-500 animate-pulse' : 'bg-slate-300'; ?>"></span>
                                     <span class="text-[10px] font-bold text-black uppercase font-mono"><?php echo $user['last_login'] ? time_ago($user['last_login']) : 'Stale Node'; ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center border-r border-slate-100">
                                <span class="text-sm font-black text-black uppercase"><?php echo $user['activity_count']; ?> <span class="text-[10px] text-slate-400">Events</span></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-[10px] font-bold text-slate-500 uppercase font-mono"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($report_type === 'departments'): ?>
    <!-- Infrastructure Report Table -->
    <div class="os-card p-0 overflow-hidden bg-white">
        <div class="bg-black p-6 text-white flex justify-between items-center border-b-2 border-black">
             <div>
                <h3 class="text-xl font-black italic uppercase tracking-widest text-white">Academic <span class="text-green-500">Infrastructure</span></h3>
                <p class="text-[10px] font-bold font-mono text-slate-400 uppercase mt-1">Structural analytics of university departments...</p>
             </div>
             <button onclick="window.print()" class="btn-os bg-white text-black border-white hover:bg-green-500 hover:border-green-500 hover:text-white text-xs">
                 <i class="fas fa-print mr-2"></i> Generate Audit
             </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-black text-white">
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Department Node</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">System Code</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-center border-r border-white/20">Personnel Count</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-center border-r border-white/20">Faculty Size</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-center border-r border-white/20">Module Library</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-center">Faculty Ratio</th>
                    </tr>
                </thead>
                <tbody class="divide-y-2 divide-slate-100">
                    <?php foreach ($department_report as $dept): ?>
                        <tr class="hover:bg-green-50 transition-all group border-l-2 border-r-2 border-transparent hover:border-black">
                            <td class="px-6 py-4 border-r border-slate-100">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-black text-green-500 flex items-center justify-center font-black text-xs shrink-0 border border-black shadow-[2px_2px_0px_rgba(0,0,0,0.2)]">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-black uppercase leading-none mb-1"><?php echo e($dept['name']); ?></p>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase font-mono">Unit Active</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 border-r border-slate-100">
                                <span class="px-2 py-1 bg-white border border-black text-[10px] font-black text-black uppercase"><?php echo e($dept['code']); ?></span>
                            </td>
                            <td class="px-6 py-4 text-center border-r border-slate-100">
                                <p class="text-sm font-black text-black"><?php echo $dept['student_count']; ?></p>
                                <p class="text-[9px] font-bold text-slate-400 uppercase">Students</p>
                            </td>
                            <td class="px-6 py-4 text-center border-r border-slate-100">
                                <p class="text-sm font-black text-black"><?php echo $dept['teacher_count']; ?></p>
                                <p class="text-[9px] font-bold text-slate-400 uppercase">Teachers</p>
                            </td>
                            <td class="px-6 py-4 text-center border-r border-slate-100">
                                <p class="text-sm font-black text-black"><?php echo $dept['course_count']; ?></p>
                                <p class="text-[9px] font-bold text-slate-400 uppercase">Courses</p>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="inline-flex items-center gap-2 px-3 py-1 bg-black text-white border border-black">
                                    <span class="text-[11px] font-black"><?php echo $dept['teacher_count'] > 0 ? round($dept['student_count'] / $dept['teacher_count'], 1) : '0'; ?></span>
                                    <span class="text-[8px] font-bold text-slate-300 uppercase">S:T</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($report_type === 'enrollments'): ?>
    <!-- Enrollment Flow Table -->
    <div class="os-card p-0 overflow-hidden bg-white">
        <div class="bg-black p-6 text-white flex justify-between items-center border-b-2 border-black">
             <div>
                <h3 class="text-xl font-black italic uppercase tracking-widest text-white">Enrollment <span class="text-blue-500">Flow</span></h3>
                <p class="text-[10px] font-bold font-mono text-slate-400 uppercase mt-1">Course subscription and capacity analytics...</p>
             </div>
             <button onclick="window.print()" class="btn-os bg-white text-black border-white hover:bg-blue-500 hover:border-blue-500 hover:text-white text-xs">
                 <i class="fas fa-print mr-2"></i> Flow Report
             </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-black text-white">
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Course Node</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Department</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-center border-r border-white/20">Engagement</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-center">Load Factor</th>
                    </tr>
                </thead>
                <tbody class="divide-y-2 divide-slate-100">
                    <?php foreach ($enrollment_report as $enroll): ?>
                        <tr class="hover:bg-blue-50 transition-all group border-l-2 border-r-2 border-transparent hover:border-black">
                            <td class="px-6 py-4 border-r border-slate-100">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-black text-blue-500 flex items-center justify-center font-black text-[9px] shrink-0 border border-black shadow-[2px_2px_0px_rgba(0,0,0,0.2)]">
                                        <?php echo e($enroll['course_code']); ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-black uppercase leading-none mb-1"><?php echo e($enroll['course_name']); ?></p>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase font-mono">Module Active</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 border-r border-slate-100">
                                <span class="text-[10px] font-bold text-slate-600 uppercase font-mono"><?php echo e($enroll['department']); ?></span>
                            </td>
                            <td class="px-6 py-4 text-center border-r border-slate-100">
                                <p class="text-sm font-black text-black"><?php echo $enroll['enrolled_students']; ?> <span class="text-slate-400">/</span> <?php echo $enroll['max_students'] ?? '??'; ?></p>
                                <p class="text-[9px] font-bold text-slate-400 uppercase">Subscribed</p>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($enroll['fill_percentage']): ?>
                                    <div class="flex flex-col gap-2">
                                        <div class="w-full bg-slate-100 border border-black h-2 overflow-hidden">
                                            <div class="bg-blue-600 h-full border-r border-black" style="width: <?php echo min($enroll['fill_percentage'], 100); ?>%"></div>
                                        </div>
                                        <span class="text-[10px] font-black text-blue-600 uppercase font-mono text-right"><?php echo $enroll['fill_percentage']; ?>% Load</span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-[10px] font-black text-slate-300 uppercase font-mono text-center block">Undefined</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($report_type === 'student_performance'): ?>
    <!-- Academic Merit Table -->
    <div class="os-card p-0 overflow-hidden bg-white">
        <div class="bg-black p-6 text-white flex justify-between items-center border-b-2 border-black">
             <div>
                <h3 class="text-xl font-black italic uppercase tracking-widest text-white">Academic <span class="text-yellow-500">Merit</span></h3>
                <p class="text-[10px] font-bold font-mono text-slate-400 uppercase mt-1">Student performance and CGPA distribution...</p>
             </div>
             <button onclick="window.print()" class="btn-os bg-white text-black border-white hover:bg-yellow-500 hover:border-yellow-500 hover:text-black text-xs">
                 <i class="fas fa-print mr-2"></i> Merit Report
             </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-black text-white">
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Candidate Node</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Department</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-center border-r border-white/20">Semester</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-center border-r border-white/20">Merit Index</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-center">Modules</th>
                    </tr>
                </thead>
                <tbody class="divide-y-2 divide-slate-100">
                    <?php foreach ($student_performance as $student): ?>
                        <tr class="hover:bg-yellow-50 transition-all group border-l-2 border-r-2 border-transparent hover:border-black">
                            <td class="px-6 py-4 border-r border-slate-100">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-black text-yellow-500 flex items-center justify-center text-xs font-black shrink-0 border border-black shadow-[2px_2px_0px_rgba(0,0,0,0.2)]">
                                        <?php echo e(substr($student['student_id'], -4)); ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-black uppercase leading-none mb-1"><?php echo e($student['student_name']); ?></p>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase font-mono"><?php echo e($student['student_id']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 border-r border-slate-100">
                                <span class="text-[10px] font-bold text-slate-500 uppercase font-mono"><?php echo e($student['department']); ?></span>
                            </td>
                            <td class="px-6 py-4 text-center border-r border-slate-100">
                                <span class="px-2 py-1 bg-slate-100 border border-black text-[10px] font-black text-black">S-<?php echo $student['current_semester']; ?></span>
                            </td>
                            <td class="px-6 py-4 text-center border-r border-slate-100">
                                <span class="px-3 py-1 border-2 border-black text-sm font-black tracking-widest <?php 
                                    echo $student['cgpa'] >= 3.5 ? 'bg-green-500 text-white' : 
                                        ($student['cgpa'] >= 3.0 ? 'bg-blue-500 text-white' : 
                                        ($student['cgpa'] >= 2.5 ? 'bg-yellow-500 text-black' : 'bg-red-500 text-white')); 
                                ?>">
                                    <?php echo number_format($student['cgpa'], 2); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-sm font-black text-black uppercase"><?php echo $student['enrolled_courses']; ?> <span class="text-[10px] text-slate-400 italic">Active</span></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($report_type === 'audit'): ?>
    <!-- Operations Audit Table -->
    <div class="os-card p-0 overflow-hidden bg-white">
        <div class="bg-black p-6 text-white flex justify-between items-center border-b-2 border-black">
             <div>
                <h3 class="text-xl font-black italic uppercase tracking-widest text-white">Operations <span class="text-yellow-500">Audit</span></h3>
                <p class="text-[10px] font-bold font-mono text-slate-400 uppercase mt-1">Temporal log of system-wide administrative actions...</p>
             </div>
             <button onclick="window.print()" class="btn-os bg-white text-black border-white hover:bg-yellow-500 hover:border-yellow-500 hover:text-black text-xs">
                 <i class="fas fa-print mr-2"></i> Export Log
             </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-black text-white">
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Temporal Stamp</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Personnel Node</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Action Type</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">System Target</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest">Network Origin</th>
                    </tr>
                </thead>
                <tbody class="divide-y-2 divide-slate-100">
                    <?php foreach ($audit_logs as $log): ?>
                        <tr class="hover:bg-yellow-50 transition-all group border-l-2 border-r-2 border-transparent hover:border-black">
                            <td class="px-6 py-4 border-r border-slate-100">
                                <div class="flex flex-col">
                                    <span class="text-sm font-black text-black"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></span>
                                    <span class="text-[9px] font-bold text-slate-400 uppercase font-mono"><?php echo date('M d, Y', strtotime($log['created_at'])); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 border-r border-slate-100">
                                <span class="text-[10px] font-black text-blue-600 uppercase font-mono">@<?php echo e($log['username'] ?? 'System Core'); ?></span>
                            </td>
                            <td class="px-6 py-4 border-r border-slate-100">
                                <span class="px-2 py-1 bg-slate-100 border border-black text-[9px] font-black text-black uppercase tracking-widest"><?php echo e($log['action']); ?></span>
                            </td>
                            <td class="px-6 py-4 border-r border-slate-100">
                                <div class="flex flex-col">
                                    <span class="text-[11px] font-black text-black uppercase tracking-tighter"><?php echo e($log['table_name']); ?></span>
                                    <span class="text-[8px] font-bold text-slate-400 uppercase font-mono">ID: <?php echo $log['record_id'] ?? '??'; ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-[10px] font-bold text-slate-500 uppercase font-mono tabular-nums"><?php echo e($log['ip_address']); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($report_type === 'login_history'): ?>
    <!-- Access Logs Table -->
    <div class="os-card p-0 overflow-hidden bg-white">
        <div class="bg-black p-6 text-white flex justify-between items-center border-b-2 border-black">
             <div>
                <h3 class="text-xl font-black italic uppercase tracking-widest text-white">Access <span class="text-red-500">Logs</span></h3>
                <p class="text-[10px] font-bold font-mono text-slate-400 uppercase mt-1">Temporal trace of system authentication events...</p>
             </div>
             <button onclick="window.print()" class="btn-os bg-white text-black border-white hover:bg-red-500 hover:border-red-500 hover:text-white text-xs">
                 <i class="fas fa-print mr-2"></i> Export History
             </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-black text-white">
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Temporal Stamp</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Identity</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Clearance Status</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Event Insight</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest">Network Origin</th>
                    </tr>
                </thead>
                <tbody class="divide-y-2 divide-slate-100">
                    <?php foreach ($login_history as $login): ?>
                        <tr class="hover:bg-red-50 transition-all group border-l-2 border-r-2 border-transparent hover:border-black">
                            <td class="px-6 py-4 border-r border-slate-100">
                                <div class="flex flex-col">
                                    <span class="text-sm font-black text-black"><?php echo date('H:i:s', strtotime($login['created_at'])); ?></span>
                                    <span class="text-[9px] font-bold text-slate-400 uppercase font-mono"><?php echo date('M d, Y', strtotime($login['created_at'])); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 border-r border-slate-100">
                                <div class="flex flex-col">
                                    <span class="text-[11px] font-black text-black uppercase">@<?php echo e($login['username']); ?></span>
                                    <span class="text-[8px] font-bold text-slate-400 uppercase font-mono"><?php echo e(ucwords(str_replace('_', ' ', $login['role'] ?? 'Unknown'))); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 border-r border-slate-100">
                                <?php if ($login['status'] === 'success'): ?>
                                    <span class="px-2 py-1 bg-green-100 text-green-700 border border-green-700 text-[9px] font-black uppercase tracking-widest">Granted</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 bg-red-100 text-red-700 border border-red-700 text-[9px] font-black uppercase tracking-widest">Denied</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 border-r border-slate-100">
                                <span class="text-[10px] font-bold text-slate-600 uppercase font-mono leading-none"><?php echo e($login['failure_reason'] ?? 'Authentication Successful'); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-[10px] font-bold text-slate-400 tabular-nums uppercase font-mono"><?php echo e($login['ip_address']); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
