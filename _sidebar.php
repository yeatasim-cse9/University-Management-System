<?php
// Student Sidebar Menu - Responsive
require_once __DIR__ . '/../../includes/sidebar_menu.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<?php echo sidebar_item(BASE_URL . '/modules/student/dashboard.php', 'fa-home', 'Dashboard', $current_page === 'dashboard.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/student/my-courses.php', 'fa-book', 'My Courses', $current_page === 'my-courses.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/student/syllabus.php', 'fa-list-check', 'Syllabus', $current_page === 'syllabus.php'); ?>
    <a href="<?php echo BASE_URL; ?>/modules/student/performance.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group <?php echo strpos($_SERVER['PHP_SELF'], 'performance.php') !== false ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30' : 'text-slate-400 hover:bg-white/5 hover:text-white'; ?>">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors <?php echo strpos($_SERVER['PHP_SELF'], 'performance.php') !== false ? 'bg-white/10' : 'bg-slate-800 group-hover:bg-indigo-500'; ?>">
            <i class="fas fa-chart-line text-sm"></i>
        </div>
        <span class="font-bold text-xs uppercase tracking-wider">Performance</span>
    </a>
<?php echo sidebar_item(BASE_URL . '/modules/student/attendance.php', 'fa-clipboard-check', 'Attendance', $current_page === 'attendance.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/student/assignments.php', 'fa-tasks', 'Assignments', $current_page === 'assignments.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/student/results.php', 'fa-chart-line', 'Results', $current_page === 'results.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/student/notices.php', 'fa-bullhorn', 'Notices', $current_page === 'notices.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/student/notifications.php', 'fa-bell', 'Notifications', $current_page === 'notifications.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/student/routine.php', 'fa-calendar-alt', 'Class Routine', $current_page === 'routine.php'); ?>
