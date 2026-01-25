<?php
// Department Admin Sidebar Menu - Responsive
require_once __DIR__ . '/../../includes/sidebar_menu.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<?php echo sidebar_item(BASE_URL . '/modules/admin/dashboard.php', 'fa-home', 'Dashboard', $current_page === 'dashboard.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/admin/students.php', 'fa-user-graduate', 'Students', $current_page === 'students.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/admin/teachers.php', 'fa-chalkboard-teacher', 'Faculty', $current_page === 'teachers.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/admin/courses.php', 'fa-book', 'Courses', $current_page === 'courses.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/admin/course-offerings.php', 'fa-book-open', 'Course Offerings', $current_page === 'course-offerings.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/admin/teacher-assignments.php', 'fa-user-tie', 'Teacher Assignments', $current_page === 'teacher-assignments.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/admin/class-schedule.php', 'fa-calendar-alt', 'Class Schedule', $current_page === 'class-schedule.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/admin/marks-verification.php', 'fa-check-circle', 'Marks Verification', $current_page === 'marks-verification.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/admin/exam-eligibility.php', 'fa-clipboard-check', 'Exam Eligibility', $current_page === 'exam-eligibility.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/admin/course_progress.php', 'fa-chart-pie', 'Course Progress', $current_page === 'course_progress.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/admin/notices.php', 'fa-bullhorn', 'Notices', $current_page === 'notices.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/admin/notifications.php', 'fa-bell', 'Notifications', $current_page === 'notifications.php'); ?>
