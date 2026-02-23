<?php
// Teacher Sidebar Menu - Responsive
require_once __DIR__ . '/../../includes/sidebar_menu.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<?php echo sidebar_item(BASE_URL . '/modules/teacher/dashboard.php', 'fa-home-lg-alt', 'Dashboard', $current_page === 'dashboard.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/teacher/my-courses.php', 'fa-book-open-reader', 'My Courses', $current_page === 'my-courses.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/teacher/syllabus.php', 'fa-list-check', 'Syllabus', $current_page === 'syllabus.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/teacher/my-routine.php', 'fa-table', 'My Routine', $current_page === 'my-routine.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/teacher/attendance.php', 'fa-user-check', 'Attendance', $current_page === 'attendance.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/teacher/assignments.php', 'fa-file-signature', 'Assignments', $current_page === 'assignments.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/teacher/marks-entry.php', 'fa-chart-line-up', 'Marks', $current_page === 'marks-entry.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/teacher/course-materials.php', 'fa-folder-tree', 'Materials', $current_page === 'course-materials.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/teacher/notices.php', 'fa-tower-broadcast', 'Notices', $current_page === 'notices.php'); ?>
