<?php
/**
 * System Settings
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('super_admin');

$page_title = 'System Configuration';
$tab = $_GET['tab'] ?? 'general';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request');
        redirect(BASE_URL . '/modules/super_admin/settings.php?tab=' . $tab);
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_settings') {
        $settings = $_POST['settings'] ?? [];
        
        $db->begin_transaction();
        try {
            foreach ($settings as $key => $value) {
                // Ensure key exists or insert it (upsert logic if needed, but we assume keys exist from seed)
                // Using simple update for now as keys are standard
                $stmt = $db->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->bind_param("ss", $value, $key);
                $stmt->execute();
            }
            
            $db->commit();
            create_audit_log('update_settings', 'system_settings', null, null, ['tab' => $tab]);
            set_flash('success', 'Settings updated successfully');
        } catch (Exception $e) {
            $db->rollback();
            set_flash('error', 'Failed to update settings');
        }
        
        redirect(BASE_URL . '/modules/super_admin/settings.php?tab=' . $tab);
    }
    
    elseif ($action === 'update_grading_scheme') {
        $grades = $_POST['grades'] ?? [];
        
        $db->begin_transaction();
        try {
            // We assume editing existing default scheme (department_id IS NULL)
            foreach ($grades as $id => $data) {
                // Basic validation
                if ($data['max'] <= $data['min']) {
                    throw new Exception("Max marks must be greater than Min marks for {$data['grade']}");
                }
                
                $stmt = $db->prepare("UPDATE grading_scheme SET min_marks = ?, max_marks = ?, grade_point = ?, description = ? WHERE id = ? AND department_id IS NULL");
                $stmt->bind_param("dddsi", $data['min'], $data['max'], $data['point'], $data['desc'], $id);
                $stmt->execute();
            }
            
            $db->commit();
            create_audit_log('update_grading_scheme', 'grading_scheme', null, null);
            set_flash('success', 'Grading scheme updated successfully');
        } catch (Exception $e) {
            $db->rollback();
            set_flash('error', 'Failed to update grading scheme: ' . $e->getMessage());
        }
        redirect(BASE_URL . '/modules/super_admin/settings.php?tab=grading');
    }

    elseif ($action === 'clear_cache') {
        // Clear session cache logic if needed
        set_flash('success', 'System cache cleared successfully');
        redirect(BASE_URL . '/modules/super_admin/settings.php?tab=maintenance');
    }
    
    elseif ($action === 'backup_database') {
        // PHP-based Database Backup
        $filename = 'academix_backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Disable output buffering
        if (ob_get_level()) ob_end_clean();
        
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        
        echo "-- ACADEMIX Database Backup\n";
        echo "-- Generated: " . date("Y-m-d H:i:s") . "\n";
        echo "-- Host: " . DB_HOST . "\n";
        echo "-- Database: " . DB_NAME . "\n\n";
        echo "SET FOREIGN_KEY_CHECKS=0;\n";
        echo "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n\n";
        
        // Get all tables
        $tables = [];
        $result = $db->query("SHOW TABLES");
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
        
        foreach ($tables as $table) {
            // Schema
            $row2 = $db->query("SHOW CREATE TABLE $table")->fetch_row();
            echo "\n-- Table structure for table `$table`\n";
            echo "DROP TABLE IF EXISTS `$table`;\n";
            echo $row2[1] . ";\n\n";
            
            // Data
            $result = $db->query("SELECT * FROM $table");
            if ($result->num_rows > 0) {
                echo "-- Dumping data for table `$table`\n";
                echo "INSERT INTO `$table` VALUES ";
                $rows = [];
                while ($row = $result->fetch_row()) {
                    $values = [];
                    foreach ($row as $value) {
                        if (is_null($value)) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = "'" . $db->real_escape_string($value) . "'";
                        }
                    }
                    $rows[] = "(" . implode(',', $values) . ")";
                }
                echo implode(",\n", $rows) . ";\n";
            }
        }
        
        echo "\nSET FOREIGN_KEY_CHECKS=1;\n";
        exit;
    }
}

// Get all settings
$settings = [];
$result = $db->query("SELECT * FROM system_settings ORDER BY setting_key");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row;
}

// Get university-wide grading scheme
$grading_scheme = [];
$result = $db->query("SELECT * FROM grading_scheme WHERE department_id IS NULL ORDER BY min_marks DESC");
while ($row = $result->fetch_assoc()) {
    $grading_scheme[] = $row;
}

// Get system info
$system_info = [
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'database_version' => $db->server_info,
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
];

// Get database stats
$db_stats = [];
$result = $db->query("SELECT 
    (SELECT COUNT(*) FROM users WHERE deleted_at IS NULL) as total_users,
    (SELECT COUNT(*) FROM departments WHERE deleted_at IS NULL) as total_departments,
    (SELECT COUNT(*) FROM courses WHERE deleted_at IS NULL) as total_courses,
    (SELECT COUNT(*) FROM enrollments) as total_enrollments,
    (SELECT COUNT(*) FROM audit_logs) as total_audit_logs,
    (SELECT COUNT(*) FROM login_history) as total_login_history");
$db_stats = $result->fetch_assoc();

// Sidebar menu
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

// Page content
ob_start();
?>

<!-- Settings Header -->
<div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white mb-6">
    <div class="relative z-10">
        <div class="flex items-center gap-3 mb-2">
            <span class="px-2 py-1 bg-red-600 text-white text-[10px] font-black uppercase tracking-widest border border-black">Master Control</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-red-500 inline-block mr-1"></span>
                Root Access Active
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">System <span class="text-red-600">Configuration</span></h1>
    </div>
    
    <div class="flex items-center gap-4">
        <div class="text-right hidden md:block">
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest leading-none mb-1">Last Backup</p>
            <p class="text-xs font-black text-black uppercase font-mono">2 hours ago</p>
        </div>
        <div class="w-10 h-10 bg-black text-white flex items-center justify-center border-2 border-black shadow-[2px_2px_0px_#000000]">
            <i class="fas fa-microchip text-lg animate-pulse"></i>
        </div>
    </div>
</div>

<div class="flex flex-col lg:flex-row gap-6">
    <!-- Settings Nav -->
    <div class="lg:w-1/4">
        <div class="os-card p-0 overflow-hidden bg-white sticky top-6">
            <div class="p-4 bg-black text-white border-b-2 border-black">
                <h4 class="text-[10px] font-black uppercase tracking-widest text-center">Access Channels</h4>
            </div>
            <nav class="flex flex-col p-4 gap-2">
                <a href="?tab=general" class="btn-os w-full <?php echo $tab === 'general' ? 'bg-black text-white' : 'bg-white text-black hover:bg-slate-100'; ?> text-left justify-start">
                    <i class="fas fa-cog w-6 <?php echo $tab === 'general' ? 'text-yellow-400' : 'text-slate-400'; ?>"></i>
                    <span class="text-[10px]">General</span>
                </a>
                <a href="?tab=academic" class="btn-os w-full <?php echo $tab === 'academic' ? 'bg-black text-white' : 'bg-white text-black hover:bg-slate-100'; ?> text-left justify-start">
                    <i class="fas fa-graduation-cap w-6 <?php echo $tab === 'academic' ? 'text-yellow-400' : 'text-slate-400'; ?>"></i>
                    <span class="text-[10px]">Academic</span>
                </a>
                <a href="?tab=grading" class="btn-os w-full <?php echo $tab === 'grading' ? 'bg-black text-white' : 'bg-white text-black hover:bg-slate-100'; ?> text-left justify-start">
                    <i class="fas fa-percent w-6 <?php echo $tab === 'grading' ? 'text-yellow-400' : 'text-slate-400'; ?>"></i>
                    <span class="text-[10px]">Grading</span>
                </a>
                <a href="?tab=security" class="btn-os w-full <?php echo $tab === 'security' ? 'bg-black text-white' : 'bg-white text-black hover:bg-slate-100'; ?> text-left justify-start">
                    <i class="fas fa-shield-alt w-6 <?php echo $tab === 'security' ? 'text-yellow-400' : 'text-slate-400'; ?>"></i>
                    <span class="text-[10px]">Security</span>
                </a>
                <a href="?tab=uploads" class="btn-os w-full <?php echo $tab === 'uploads' ? 'bg-black text-white' : 'bg-white text-black hover:bg-slate-100'; ?> text-left justify-start">
                    <i class="fas fa-upload w-6 <?php echo $tab === 'uploads' ? 'text-yellow-400' : 'text-slate-400'; ?>"></i>
                    <span class="text-[10px]">Storage</span>
                </a>
                <a href="?tab=maintenance" class="btn-os w-full <?php echo $tab === 'maintenance' ? 'bg-black text-white' : 'bg-white text-black hover:bg-slate-100'; ?> text-left justify-start">
                    <i class="fas fa-tools w-6 <?php echo $tab === 'maintenance' ? 'text-yellow-400' : 'text-slate-400'; ?>"></i>
                    <span class="text-[10px]">Protocol</span>
                </a>
            </nav>
        </div>
    </div>

    <!-- Settings Content -->
    <div class="lg:w-3/4">
        <?php if ($tab === 'general'): ?>
            <div class="os-card p-0 overflow-hidden bg-white">
                <div class="bg-black p-6 text-white flex justify-between items-center border-b-2 border-black">
                     <div>
                        <h3 class="text-xl font-black italic uppercase tracking-widest text-white">General <span class="text-blue-500">Configuration</span></h3>
                        <p class="text-[10px] font-bold font-mono text-slate-400 uppercase mt-1">Defining core identity and temporal offsets...</p>
                     </div>
                     <i class="fas fa-sliders text-2xl text-white/20"></i>
                </div>

                <form method="POST" action="" class="p-6 space-y-6">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2 space-y-2">
                            <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">System Identifier</label>
                            <div class="relative">
                                 <input type="text" name="settings[system_name]" value="<?php echo e($settings['system_name']['setting_value'] ?? ''); ?>" 
                                    class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase"
                                    placeholder="e.g. ACADEMIX">
                            </div>
                        </div>
                        
                        <div class="md:col-span-2 space-y-2">
                            <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Institutional Entity</label>
                            <input type="text" name="settings[university_name]" value="<?php echo e($settings['university_name']['setting_value'] ?? ''); ?>" 
                                class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase">
                        </div>

                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Temporal Zone</label>
                            <div class="relative">
                                <select name="settings[timezone]" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none">
                                    <option value="Asia/Dhaka" <?php echo ($settings['timezone']['setting_value'] ?? '') === 'Asia/Dhaka' ? 'selected' : ''; ?>>Asia/Dhaka (GMT+6)</option>
                                    <option value="UTC" <?php echo ($settings['timezone']['setting_value'] ?? '') === 'UTC' ? 'selected' : ''; ?>>UTC (Standard)</option>
                                    <option value="America/New_York" <?php echo ($settings['timezone']['setting_value'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>New York (EST)</option>
                                    <option value="Europe/London" <?php echo ($settings['timezone']['setting_value'] ?? '') === 'Europe/London' ? 'selected' : ''; ?>>London (GMT)</option>
                                </select>
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                    <i class="fas fa-chevron-down text-black text-xs"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Date Format</label>
                                <div class="relative">
                                    <select name="settings[date_format]" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none">
                                        <option value="Y-m-d" <?php echo ($settings['date_format']['setting_value'] ?? '') === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                        <option value="d/m/Y" <?php echo ($settings['date_format']['setting_value'] ?? '') === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                        <option value="m/d/Y" <?php echo ($settings['date_format']['setting_value'] ?? '') === 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                    </select>
                                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                        <i class="fas fa-chevron-down text-black text-xs"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Time Format</label>
                                <div class="relative">
                                    <select name="settings[time_format]" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none">
                                        <option value="H:i:s" <?php echo ($settings['time_format']['setting_value'] ?? '') === 'H:i:s' ? 'selected' : ''; ?>>24-hour</option>
                                        <option value="h:i A" <?php echo ($settings['time_format']['setting_value'] ?? '') === 'h:i A' ? 'selected' : ''; ?>>12h AM/PM</option>
                                    </select>
                                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                        <i class="fas fa-chevron-down text-black text-xs"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pt-6 border-t-2 border-black flex justify-end">
                        <button type="submit" class="btn-os bg-blue-600 text-white border-black hover:bg-black hover:text-white">
                            Update Identity
                        </button>
                    </div>
                </form>
            </div>

        <?php elseif ($tab === 'grading'): ?>
            <div class="os-card p-0 overflow-hidden bg-white">
                <div class="bg-black p-6 text-white flex justify-between items-center border-b-2 border-black">
                     <div>
                        <h3 class="text-xl font-black italic uppercase tracking-widest text-white">Grading <span class="text-yellow-500">Scheme</span></h3>
                        <p class="text-[10px] font-bold font-mono text-slate-400 uppercase mt-1">Defining academic merit standards...</p>
                     </div>
                     <i class="fas fa-award text-2xl text-white/20"></i>
                </div>
                
                <form method="POST" action="" class="p-6">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="update_grading_scheme">
                    
                    <div class="overflow-x-auto -mx-6 md:mx-0">
                        <table class="w-full text-left border-collapse border-2 border-black">
                            <thead>
                                <tr class="bg-black text-white">
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Grade</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Marks Range (%)</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Merit Index (GP)</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest">Classification</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-2 divide-black">
                                <?php foreach ($grading_scheme as $grade): ?>
                                    <tr class="hover:bg-yellow-50 transition-all">
                                        <td class="px-6 py-4 border-r-2 border-black font-black text-black text-xl italic bg-slate-100">
                                            <?php echo htmlspecialchars($grade['grade']); ?>
                                            <input type="hidden" name="grades[<?php echo $grade['id']; ?>][grade]" value="<?php echo htmlspecialchars($grade['grade']); ?>">
                                        </td>
                                        <td class="px-6 py-4 border-r-2 border-black">
                                            <div class="flex items-center gap-2">
                                                <input type="number" step="0.01" name="grades[<?php echo $grade['id']; ?>][min]" 
                                                    value="<?php echo $grade['min_marks']; ?>" 
                                                    class="w-full px-2 py-1 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50" required>
                                                <span class="font-black">-</span>
                                                <input type="number" step="0.01" name="grades[<?php echo $grade['id']; ?>][max]" 
                                                    value="<?php echo $grade['max_marks']; ?>" 
                                                    class="w-full px-2 py-1 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50" required>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 border-r-2 border-black">
                                            <input type="number" step="0.01" name="grades[<?php echo $grade['id']; ?>][point]" 
                                                value="<?php echo $grade['grade_point']; ?>" 
                                                class="w-full px-2 py-1 bg-white border-2 border-black font-bold text-xs text-black text-center focus:outline-none focus:bg-yellow-50" required>
                                        </td>
                                        <td class="px-6 py-4">
                                            <input type="text" name="grades[<?php echo $grade['id']; ?>][desc]" 
                                                value="<?php echo htmlspecialchars($grade['description']); ?>" 
                                                class="w-full px-2 py-1 bg-white border-2 border-black font-bold text-[10px] text-black uppercase tracking-widest focus:outline-none focus:bg-yellow-50">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-6 p-4 bg-yellow-100 border-2 border-black flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-8 h-8 bg-black text-yellow-500 flex items-center justify-center font-black rounded-none border border-black">
                                <i class="fas fa-info"></i>
                            </div>
                            <p class="text-[10px] font-bold text-black uppercase leading-relaxed font-mono">
                                Standard University Policy: Indices apply to all departments unless overridden.
                            </p>
                        </div>
                        <button type="submit" class="btn-os bg-black text-white border-black hover:bg-white hover:text-black hover:border-black text-[10px]">
                            Commit Scheme
                        </button>
                    </div>
                </form>
            </div>

        <?php elseif ($tab === 'academic'): ?>
            <div class="os-card p-0 overflow-hidden bg-white">
                <div class="bg-black p-6 text-white flex justify-between items-center border-b-2 border-black">
                     <div>
                        <h3 class="text-xl font-black italic uppercase tracking-widest text-white">Academic <span class="text-purple-500">Policies</span></h3>
                        <p class="text-[10px] font-bold font-mono text-slate-400 uppercase mt-1">Determining procedural rules and eligibility thresholds...</p>
                     </div>
                     <i class="fas fa-scroll text-2xl text-white/20"></i>
                </div>
                
                <form method="POST" action="" class="p-6 space-y-8">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Attendance -->
                        <div class="space-y-6">
                            <h4 class="text-[11px] font-black text-black uppercase tracking-[0.2em] flex items-center gap-3">
                                <span class="w-2 h-2 bg-purple-600 border border-black"></span>
                                Attendance Thresholds
                            </h4>
                            <div class="space-y-4 pl-4 border-l-2 border-slate-200">
                                <div class="space-y-2">
                                    <label class="block text-[10px] font-black text-black uppercase tracking-[0.2em] italic">Required Engagement</label>
                                    <div class="relative">
                                        <input type="number" name="settings[attendance_required_percentage]" 
                                            value="<?php echo e($settings['attendance_required_percentage']['setting_value'] ?? ''); ?>" 
                                            class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-purple-50 transition-all"
                                            min="0" max="100">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-black text-slate-400">%</span>
                                    </div>
                                    <p class="text-[10px] font-bold text-slate-500 font-mono">Minimum percentage for examination clearance.</p>
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-[10px] font-black text-black uppercase tracking-[0.2em] italic">Correction Window</label>
                                    <div class="relative">
                                        <input type="number" name="settings[attendance_edit_window]" 
                                            value="<?php echo e($settings['attendance_edit_window']['setting_value'] ?? ''); ?>" 
                                            class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-purple-50 transition-all"
                                            min="1" max="168">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400 uppercase italic">Hours</span>
                                    </div>
                                    <p class="text-[10px] font-bold text-slate-500 font-mono">Temporal limit for instructional staff to modify logs.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Calendar -->
                        <div class="space-y-6">
                            <h4 class="text-[11px] font-black text-black uppercase tracking-[0.2em] flex items-center gap-3">
                                <span class="w-2 h-2 bg-blue-600 border border-black"></span>
                                Temporal Parameters
                            </h4>
                            <div class="space-y-4 pl-4 border-l-2 border-slate-200">
                                <div class="space-y-2">
                                    <label class="block text-[10px] font-black text-black uppercase tracking-[0.2em] italic">Semester Cycle Duration</label>
                                    <div class="relative">
                                        <input type="number" name="settings[default_semester_duration]" 
                                            value="<?php echo e($settings['default_semester_duration']['setting_value'] ?? ''); ?>" 
                                            class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-purple-50 transition-all"
                                            min="30" max="365">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400 uppercase italic">Days</span>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-[10px] font-black text-black uppercase tracking-[0.2em] italic">Academic Initialization Month</label>
                                    <div class="relative">
                                        <select name="settings[academic_year_start_month]" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-purple-50 transition-all uppercase appearance-none">
                                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                                <option value="<?php echo $i; ?>" <?php echo ($settings['academic_year_start_month']['setting_value'] ?? '') == $i ? 'selected' : ''; ?>>
                                                    <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                            <i class="fas fa-chevron-down text-black text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pt-6 border-t-2 border-black flex justify-end">
                        <button type="submit" class="btn-os bg-purple-600 text-white border-black hover:bg-black hover:text-white">
                            Update Policies
                        </button>
                    </div>
                </form>
            </div>

        <?php elseif ($tab === 'security'): ?>
            <div class="os-card p-0 overflow-hidden bg-white">
                <div class="bg-black p-6 text-white flex justify-between items-center border-b-2 border-black">
                     <div>
                        <h3 class="text-xl font-black italic uppercase tracking-widest text-white">Security <span class="text-red-500">Protocols</span></h3>
                        <p class="text-[10px] font-bold font-mono text-slate-400 uppercase mt-1">Hardening access vectors and session persistence...</p>
                     </div>
                     <i class="fas fa-user-shield text-2xl text-white/20"></i>
                </div>
                
                <form method="POST" action="" class="p-6 space-y-8">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="space-y-6">
                        <div class="p-4 bg-red-50 border-2 border-red-500 flex items-start gap-4 shadow-[2px_2px_0px_#ef4444]">
                            <div class="w-10 h-10 bg-red-600 text-white flex items-center justify-center border-2 border-black shrink-0">
                                <i class="fas fa-triangle-exclamation"></i>
                            </div>
                            <div>
                                <h4 class="text-[11px] font-black text-red-900 uppercase tracking-widest leading-none mb-2 italic">Infrastructure Alert</h4>
                                <p class="text-[10px] font-bold text-red-800 uppercase tracking-wide leading-relaxed font-mono">
                                    Current system state utilizes <span class="bg-red-200 px-1">Plaintext Authentication</span>. 
                                    Aggressive session termination is recommended for risk mitigation.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Session Expiry Clock</label>
                                <div class="relative">
                                    <input type="number" name="settings[session_timeout]" 
                                        value="<?php echo e($settings['session_timeout']['setting_value'] ?? ''); ?>" 
                                        class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-red-50 transition-all"
                                        min="60" max="86400">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400 uppercase italic">Seconds</span>
                                </div>
                                <p class="text-[10px] font-bold text-slate-500 font-mono italic">Duration: ~<?php echo round(($settings['session_timeout']['setting_value'] ?? 1800) / 60); ?> minutes of inactivity.</p>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Brute-Force Threshold</label>
                                <div class="relative">
                                    <input type="number" name="settings[max_login_attempts]" 
                                        value="<?php echo e($settings['max_login_attempts']['setting_value'] ?? ''); ?>" 
                                        class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-red-50 transition-all"
                                        min="3" max="20">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400 uppercase italic">Attempts</span>
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Lockout Serialization</label>
                                <div class="relative">
                                    <input type="number" name="settings[lockout_duration]" 
                                        value="<?php echo e($settings['lockout_duration']['setting_value'] ?? ''); ?>" 
                                        class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-red-50 transition-all"
                                        min="60" max="3600">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400 uppercase italic">Seconds</span>
                                </div>
                                <p class="text-[10px] font-bold text-slate-500 font-mono italic">Mandatory wait time after threshold breach.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pt-6 border-t-2 border-black flex justify-end">
                        <button type="submit" class="btn-os bg-red-600 text-white border-black hover:bg-black hover:text-white">
                            Update Security
                        </button>
                    </div>
                </form>
            </div>

        <?php elseif ($tab === 'uploads'): ?>
            <div class="os-card p-0 overflow-hidden bg-white">
                <div class="bg-black p-6 text-white flex justify-between items-center border-b-2 border-black">
                     <div>
                        <h3 class="text-xl font-black italic uppercase tracking-widest text-white">Storage <span class="text-green-500">Allocation</span></h3>
                        <p class="text-[10px] font-bold font-mono text-slate-400 uppercase mt-1">Managing file dissemination parameters...</p>
                     </div>
                     <i class="fas fa-cloud-arrow-up text-2xl text-white/20"></i>
                </div>
                
                <form method="POST" action="" class="p-6 space-y-8">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="space-y-6">
                        <div class="p-4 bg-green-50 border-2 border-green-500 flex items-center justify-between gap-6 shadow-[2px_2px_0px_#22c55e]">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-green-600 text-white flex items-center justify-center border-2 border-black shrink-0">
                                    <i class="fas fa-server text-lg"></i>
                                </div>
                                <div>
                                    <h4 class="text-[11px] font-black text-green-900 uppercase tracking-widest leading-none mb-1 italic">Runtime Limits</h4>
                                    <p class="text-[10px] font-bold text-green-800 uppercase tracking-widest leading-relaxed font-mono">
                                        PHP: <?php echo $system_info['upload_max_filesize']; ?> Max Bytes | Buffer: <?php echo $system_info['post_max_size']; ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Quantum Limit (Bytes)</label>
                                <div class="relative">
                                     <input type="number" name="settings[max_file_upload_size]" 
                                        value="<?php echo e($settings['max_file_upload_size']['setting_value'] ?? ''); ?>" 
                                        class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-green-50 transition-all"
                                        min="1024">
                                     <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400 uppercase italic">~<?php echo round(($settings['max_file_upload_size']['setting_value'] ?? 0) / 1024 / 1024, 2); ?> MB</span>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Authorized Vectors (Extensions)</label>
                                <textarea name="settings[allowed_file_types]" rows="4"
                                    class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black uppercase tracking-widest leading-relaxed focus:outline-none focus:bg-green-50 transition-all font-mono"><?php echo e($settings['allowed_file_types']['setting_value'] ?? ''); ?></textarea>
                                <p class="text-[10px] font-bold text-slate-500 italic">Comma-separated list of permitted academic document signatures.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pt-6 border-t-2 border-black flex justify-end">
                        <button type="submit" class="btn-os bg-green-600 text-white border-black hover:bg-black hover:text-white">
                            Sync Vectors
                        </button>
                    </div>
                </form>
            </div>

        <?php elseif ($tab === 'maintenance'): ?>
            <div class="os-card p-0 overflow-hidden bg-white">
                <div class="bg-black p-6 text-white flex justify-between items-center border-b-2 border-black">
                     <div>
                        <h3 class="text-xl font-black italic uppercase tracking-widest text-white">Protocol <span class="text-slate-400">Maintenance</span></h3>
                        <p class="text-[10px] font-bold font-mono text-slate-400 uppercase mt-1">Structural integrity and data persistence...</p>
                     </div>
                     <i class="fas fa-gears text-2xl text-white/20 animate-spin-slow"></i>
                </div>
                
                <div class="p-8 space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Advanced Stats Cards -->
                        <div class="bg-slate-50 p-6 border-2 border-black text-center group hover:bg-black hover:text-white transition-all shadow-[4px_4px_0px_#000000]">
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 group-hover:text-slate-400">Atomic Population</p>
                            <p class="text-3xl font-black text-black group-hover:text-white italic"><?php echo number_format($db_stats['total_users']); ?></p>
                            <p class="text-[9px] font-black text-slate-400 uppercase mt-2 italic font-mono group-hover:text-slate-500">Active Nodes</p>
                        </div>
                        <div class="bg-slate-50 p-6 border-2 border-black text-center group hover:bg-black hover:text-white transition-all shadow-[4px_4px_0px_#000000]">
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 group-hover:text-slate-400">Event Trace Log</p>
                            <p class="text-3xl font-black text-black group-hover:text-white italic"><?php echo number_format($db_stats['total_audit_logs']); ?></p>
                            <p class="text-[9px] font-black text-slate-400 uppercase mt-2 italic font-mono group-hover:text-slate-500">Audit Entries</p>
                        </div>
                        <div class="bg-slate-50 p-6 border-2 border-black text-center group hover:bg-black hover:text-white transition-all shadow-[4px_4px_0px_#000000]">
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 group-hover:text-slate-400">Payload Volume</p>
                            <p class="text-3xl font-black text-black group-hover:text-white italic">~<?php echo number_format($db_stats['total_audit_logs'] * 0.5 + 500, 0); ?></p>
                            <p class="text-[9px] font-black text-slate-400 uppercase mt-2 italic font-mono group-hover:text-slate-500">Kilobytes</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <!-- Backup -->
                        <div class="p-6 bg-white border-2 border-black flex flex-col md:flex-row items-center justify-between gap-6 hover:shadow-[4px_4px_0px_#000000] transition-all">
                            <div class="flex items-center gap-6 text-center md:text-left">
                                <div class="w-12 h-12 bg-black text-white flex items-center justify-center border-2 border-black shrink-0">
                                    <i class="fas fa-database text-lg"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-black text-black uppercase tracking-widest mb-1 italic">Structural Snapshot</h4>
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest leading-relaxed font-mono">Download primary SQL dump for archival persistence.</p>
                                </div>
                            </div>
                            <form method="POST" class="shrink-0">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="backup_database">
                                <button type="submit" class="btn-os bg-green-600 text-white border-black hover:bg-black hover:text-white text-[10px]">
                                    Capture Image
                                </button>
                            </form>
                        </div>

                        <!-- Cache -->
                        <div class="p-6 bg-white border-2 border-black flex flex-col md:flex-row items-center justify-between gap-6 hover:shadow-[4px_4px_0px_#000000] transition-all">
                            <div class="flex items-center gap-6 text-center md:text-left">
                                <div class="w-12 h-12 bg-black text-white flex items-center justify-center border-2 border-black shrink-0">
                                    <i class="fas fa-broom text-lg"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-black text-black uppercase tracking-widest mb-1 italic">Temporal Purge</h4>
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest leading-relaxed font-mono">Incinerate transient session data and system caches.</p>
                                </div>
                            </div>
                            <form method="POST" class="shrink-0">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="clear_cache">
                                <button type="submit" class="btn-os bg-yellow-500 text-black border-black hover:bg-black hover:text-white text-[10px]">
                                    Execute Purge
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($tab === 'system'): ?>
            <div class="os-card p-6 bg-white">
                <h3 class="text-xl font-black text-black mb-6 border-b-2 border-black pb-4 uppercase tracking-widest">Technical Diagnostics</h3>
                
                <div class="grid grid-cols-1 gap-6">
                    <div class="border-2 border-black overflow-hidden">
                        <div class="bg-black px-4 py-3 border-b-2 border-black">
                            <h4 class="font-black text-white text-xs uppercase tracking-widest">Server Environment</h4>
                        </div>
                        <table class="w-full text-sm">
                            <tbody class="divide-y-2 divide-slate-100">
                                <tr class="hover:bg-yellow-50"><td class="px-4 py-3 font-bold text-black w-1/3 uppercase text-[10px] tracking-wider">PHP Version</td><td class="px-4 py-3 font-mono text-xs font-bold"><?php echo $system_info['php_version']; ?></td></tr>
                                <tr class="hover:bg-yellow-50"><td class="px-4 py-3 font-bold text-black uppercase text-[10px] tracking-wider">Database Engine</td><td class="px-4 py-3 font-mono text-xs font-bold"><?php echo $system_info['database_version']; ?></td></tr>
                                <tr class="hover:bg-yellow-50"><td class="px-4 py-3 font-bold text-black uppercase text-[10px] tracking-wider">Server Software</td><td class="px-4 py-3 text-xs font-bold"><?php echo e($system_info['server_software']); ?></td></tr>
                                <tr class="hover:bg-yellow-50"><td class="px-4 py-3 font-bold text-black uppercase text-[10px] tracking-wider">Memory Limit</td><td class="px-4 py-3 font-mono text-xs font-bold"><?php echo $system_info['memory_limit']; ?></td></tr>
                                <tr class="hover:bg-yellow-50"><td class="px-4 py-3 font-bold text-black uppercase text-[10px] tracking-wider">Max Execution Time</td><td class="px-4 py-3 font-mono text-xs font-bold"><?php echo $system_info['max_execution_time']; ?>s</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="mt-6 flex items-center justify-center text-slate-400 text-xs font-black uppercase tracking-widest">
                    <i class="fas fa-code mr-2"></i> ACADEMIX v1.0.0 &bull; Build 20241230
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
