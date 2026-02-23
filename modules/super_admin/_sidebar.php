<?php
// Super Admin Sidebar Menu - Responsive
require_once __DIR__ . '/../../includes/sidebar_menu.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<?php echo sidebar_item(BASE_URL . '/modules/super_admin/dashboard.php', 'fa-home', 'Dashboard', $current_page === 'dashboard.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/super_admin/departments.php', 'fa-sitemap', 'Departments', $current_page === 'departments.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/super_admin/users.php', 'fa-users', 'Users', $current_page === 'users.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/super_admin/academic-years.php', 'fa-calendar-alt', 'Academic Years', $current_page === 'academic-years.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/super_admin/courses.php', 'fa-book', 'Courses', $current_page === 'courses.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/super_admin/notices.php', 'fa-bullhorn', 'Notices', $current_page === 'notices.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/super_admin/events.php', 'fa-calendar-check', 'Events', $current_page === 'events.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/super_admin/reports.php', 'fa-chart-bar', 'Reports', $current_page === 'reports.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/super_admin/department_monitoring.php', 'fa-chart-line', 'Dept Monitoring', $current_page === 'department_monitoring.php'); ?>
<?php echo sidebar_item(BASE_URL . '/modules/super_admin/settings.php', 'fa-cog', 'Settings', $current_page === 'settings.php'); ?>
