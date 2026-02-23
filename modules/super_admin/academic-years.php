<?php
/**
 * Academic Calendar Management
 * ACADEMIX - University Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('super_admin');

$page_title = 'Academic Years & Semesters';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$type = $_GET['type'] ?? 'year'; // 'year' or 'semester'

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Security error');
        redirect(BASE_URL . '/modules/super_admin/academic-years.php');
    }
    
    $post_action = $_POST['action'] ?? '';
    
    if ($post_action === 'create_year') {
        $year = sanitize_input($_POST['year'] ?? '');
        $start_date = sanitize_input($_POST['start_date'] ?? '');
        $end_date = sanitize_input($_POST['end_date'] ?? '');
        $status = isset($_POST['is_current']) ? 'active' : 'inactive';
        
        $errors = validate_required(['year', 'start_date', 'end_date'], $_POST);
        
        if (empty($errors)) {
            // If setting as active, unset others
            if ($status === 'active') {
                $db->query("UPDATE academic_years SET status = 'inactive'");
            }
            
            $stmt = $db->prepare("INSERT INTO academic_years (year, start_date, end_date, status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $year, $start_date, $end_date, $status);
            
            if ($stmt->execute()) {
                create_audit_log('create_academic_year', 'academic_years', $stmt->insert_id, null, ['year' => $year]);
                set_flash('success', 'Academic year created successfully');
            } else {
                set_flash('error', 'Failed to create academic year');
            }
        } else {
            set_flash('error', implode(', ', $errors));
        }
        redirect(BASE_URL . '/modules/super_admin/academic-years.php');
    }
    
    elseif ($post_action === 'update_year') {
        $id = intval($_POST['id']);
        $year = sanitize_input($_POST['year'] ?? '');
        $start_date = sanitize_input($_POST['start_date'] ?? '');
        $end_date = sanitize_input($_POST['end_date'] ?? '');
        $status = isset($_POST['is_current']) ? 'active' : 'inactive';
        
        if ($status === 'active') {
            $db->query("UPDATE academic_years SET status = 'inactive'");
        }
        
        $stmt = $db->prepare("UPDATE academic_years SET year = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $year, $start_date, $end_date, $status, $id);
        
        if ($stmt->execute()) {
            create_audit_log('update_academic_year', 'academic_years', $id);
            set_flash('success', 'Academic year updated successfully');
        } else {
            set_flash('error', 'Failed to update academic year');
        }
        redirect(BASE_URL . '/modules/super_admin/academic-years.php');
    }
    
    elseif ($post_action === 'create_semester') {
        $academic_year_id = intval($_POST['academic_year_id']);
        $name = sanitize_input($_POST['name'] ?? '');
        $semester_number = intval($_POST['semester_number'] ?? 1);
        $start_date = sanitize_input($_POST['start_date'] ?? '');
        $end_date = sanitize_input($_POST['end_date'] ?? '');
        $status = isset($_POST['is_active']) ? 'active' : 'upcoming';
        
        $errors = validate_required(['academic_year_id', 'name', 'semester_number', 'start_date', 'end_date'], $_POST);
        
        if (empty($errors)) {
            if ($status === 'active') {
                $db->query("UPDATE semesters SET status = 'upcoming'");
            }
            
            $stmt = $db->prepare("INSERT INTO semesters (academic_year_id, name, semester_number, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isisss", $academic_year_id, $name, $semester_number, $start_date, $end_date, $status);
            
            if ($stmt->execute()) {
                create_audit_log('create_semester', 'semesters', $stmt->insert_id, null, ['name' => $name]);
                set_flash('success', 'Semester created successfully');
            } else {
                set_flash('error', 'Failed to create semester');
            }
        } else {
            set_flash('error', implode(', ', $errors));
        }
        redirect(BASE_URL . '/modules/super_admin/academic-years.php?type=semester');
    }
    
    elseif ($post_action === 'update_semester') {
        $id = intval($_POST['id']);
        $academic_year_id = intval($_POST['academic_year_id']);
        $name = sanitize_input($_POST['name'] ?? '');
        $semester_number = intval($_POST['semester_number'] ?? 1);
        $start_date = sanitize_input($_POST['start_date'] ?? '');
        $end_date = sanitize_input($_POST['end_date'] ?? '');
        $status = isset($_POST['is_active']) ? 'active' : 'upcoming';
        
        if ($status === 'active') {
            $db->query("UPDATE semesters SET status = 'upcoming'");
        }
        
        $stmt = $db->prepare("UPDATE semesters SET academic_year_id = ?, name = ?, semester_number = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?");
        $stmt->bind_param("isisssi", $academic_year_id, $name, $semester_number, $start_date, $end_date, $status, $id);
        
        if ($stmt->execute()) {
            create_audit_log('update_semester', 'semesters', $id);
            set_flash('success', 'Semester updated successfully');
        } else {
            set_flash('error', 'Failed to update semester');
        }
        redirect(BASE_URL . '/modules/super_admin/academic-years.php?type=semester');
    }
    
    elseif ($post_action === 'delete_year') {
        $id = intval($_POST['id']);
        
        // Check if has semesters
        $result = $db->query("SELECT COUNT(*) as count FROM semesters WHERE academic_year_id = $id");
        if ($result->fetch_assoc()['count'] > 0) {
            set_flash('error', 'Cannot delete academic year with semesters. Delete semesters first.');
        } else {
            $stmt = $db->prepare("DELETE FROM academic_years WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                create_audit_log('delete_academic_year', 'academic_years', $id);
                set_flash('success', 'Academic year deleted successfully');
            } else {
                set_flash('error', 'Failed to delete academic year');
            }
        }
        redirect(BASE_URL . '/modules/super_admin/academic-years.php');
    }
    
    elseif ($post_action === 'delete_semester') {
        $id = intval($_POST['id']);
        
        $stmt = $db->prepare("DELETE FROM semesters WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            create_audit_log('delete_semester', 'semesters', $id);
            set_flash('success', 'Semester deleted successfully');
        } else {
            set_flash('error', 'Failed to delete semester');
        }
        redirect(BASE_URL . '/modules/super_admin/academic-years.php?type=semester');
    }
}

// Get data for edit
$edit_data = null;
if ($action === 'edit' && $id) {
    if ($type === 'year') {
        $stmt = $db->prepare("SELECT * FROM academic_years WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $edit_data = $stmt->get_result()->fetch_assoc();
    } else {
        $stmt = $db->prepare("SELECT * FROM semesters WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $edit_data = $stmt->get_result()->fetch_assoc();
    }
}

// Get all academic years
$academic_years = [];
$result = $db->query("SELECT ay.*, 
    (SELECT COUNT(*) FROM semesters WHERE academic_year_id = ay.id) as semester_count
    FROM academic_years ay 
    ORDER BY ay.start_date DESC");
while ($row = $result->fetch_assoc()) {
    $academic_years[] = $row;
}

// Get all semesters
$semesters = [];
$result = $db->query("SELECT s.*, ay.year 
    FROM semesters s 
    JOIN academic_years ay ON s.academic_year_id = ay.id 
    ORDER BY s.start_date DESC");
while ($row = $result->fetch_assoc()) {
    $semesters[] = $row;
}

// Sidebar menu
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

// Page content
ob_start();
?>

<div class="space-y-6">
    <!-- Calendar Header -->
    <div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white">
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
                <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Academic Calendar</span>
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                    <span class="w-1.5 h-1.5 rounded-none bg-green-500 inline-block mr-1"></span>
                    System Online
                </span>
            </div>
            <h1 class="text-3xl font-black uppercase tracking-tighter">Calendar <span class="text-yellow-500">Management</span></h1>
        </div>
        
        <?php if ($action === 'list'): ?>
            <div class="flex flex-wrap gap-2">
                <a href="?action=create&type=year" class="btn-os bg-black text-white border-black hover:bg-white hover:text-black">
                    <span class="flex items-center gap-2">
                        <i class="fas fa-plus-circle text-yellow-500"></i> New Year
                    </span>
                </a>
                <a href="?action=create&type=semester" class="btn-os bg-yellow-400 text-black border-black hover:bg-white hover:text-black hover:border-black">
                    <span class="flex items-center gap-2">
                        <i class="fas fa-plus-circle"></i> New Semester
                    </span>
                </a>
            </div>
        <?php else: ?>
            <a href="?" class="btn-os bg-white text-black border-black hover:bg-black hover:text-white group">
                <span class="flex items-center gap-2">
                    <i class="fas fa-arrow-left text-sm transition-transform group-hover:-translate-x-1"></i> Back to List
                </span>
            </a>
        <?php endif; ?>
    </div>

<?php if ($action === 'create' || $action === 'edit'): ?>
    <!-- Create/Edit Form: Protocol Configuration -->
    <div class="max-w-4xl mx-auto">
        <div class="os-card p-0 overflow-hidden bg-white">
            <div class="bg-black p-6 text-white flex justify-between items-center border-b-2 border-black">
                <div>
                    <h3 class="text-xl font-black italic uppercase tracking-widest text-white"><?php echo $type === 'year' ? 'Year' : 'Semester'; ?> <span class="text-green-500">Configuration</span></h3>
                    <p class="text-[10px] font-bold font-mono text-slate-400 uppercase mt-1">
                        TEMPORAL_MAP::<?php echo $action === 'edit' ? 'UPDATE' : 'INIT'; ?>_SEQUENCE
                    </p>
                </div>
                <i class="fas fa-calendar-plus text-2xl text-green-500"></i>
            </div>
            <div class="p-8">
                <?php if ($type === 'year'): ?>
                    <form method="POST" action="">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="<?php echo $action === 'edit' ? 'update_year' : 'create_year'; ?>">
                    <input type="hidden" name="type" value="year">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo e($edit_data['id']); ?>">
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="md:col-span-2 space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] italic">
                                Academic Year <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="year" value="<?php echo e($edit_data['year'] ?? ''); ?>" 
                                class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-black text-sm text-black uppercase tracking-widest focus:outline-none focus:bg-yellow-50 transition-all placeholder-slate-400" 
                                placeholder="e.g., 2024-2025" required>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] italic">
                                Start Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="start_date" value="<?php echo e($edit_data['start_date'] ?? ''); ?>" 
                                class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all" 
                                required>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] italic">
                                End Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="end_date" value="<?php echo e($edit_data['end_date'] ?? ''); ?>" 
                                class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all" 
                                required>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="flex items-center gap-3 cursor-pointer group select-none">
                                <div class="relative">
                                    <input type="checkbox" name="is_current" value="1" 
                                        <?php echo ($edit_data['status'] ?? '') === 'active' ? 'checked' : ''; ?>
                                        class="peer sr-only">
                                    <div class="w-5 h-5 border-2 border-black bg-white peer-checked:bg-green-500 peer-checked:border-black transition-all"></div>
                                    <i class="fas fa-check text-white text-[10px] absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 opacity-0 peer-checked:opacity-100"></i>
                                </div>
                                <span class="text-xs font-black text-black uppercase tracking-widest">Set as current academic year</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mt-8 flex gap-4 pt-6 border-t-2 border-black">
                        <button type="submit" class="btn-os bg-green-500 text-white border-black hover:bg-black hover:text-white hover:border-black">
                            <span class="flex items-center gap-2">
                                <i class="fas fa-save"></i> <?php echo $action === 'edit' ? 'Update Year' : 'Save Year'; ?>
                            </span>
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <form method="POST" action="">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="<?php echo $action === 'edit' ? 'update_semester' : 'create_semester'; ?>">
                    <input type="hidden" name="type" value="semester">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo e($edit_data['id']); ?>">
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] italic">
                                Academic Year <span class="text-red-500">*</span>
                            </label>
                            <select name="academic_year_id" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none" required>
                                <option value="">Select Academic Year</option>
                                <?php foreach ($academic_years as $year): ?>
                                    <option value="<?php echo $year['id']; ?>" <?php echo ($edit_data['academic_year_id'] ?? '') == $year['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($year['year']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] italic">
                                Semester Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" value="<?php echo e($edit_data['name'] ?? ''); ?>" 
                                class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all placeholder-slate-400" 
                                placeholder="e.g., Spring 2024" required>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] italic">
                                Semester Number <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="semester_number" value="<?php echo e($edit_data['semester_number'] ?? '1'); ?>" 
                                class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-black text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all" 
                                min="1" max="12" required>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] italic">
                                Start Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="start_date" value="<?php echo e($edit_data['start_date'] ?? ''); ?>" 
                                class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all" 
                                required>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] italic">
                                End Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="end_date" value="<?php echo e($edit_data['end_date'] ?? ''); ?>" 
                                class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all" 
                                required>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="flex items-center gap-3 cursor-pointer group select-none">
                                <div class="relative">
                                    <input type="checkbox" name="is_active" value="1" 
                                        <?php echo ($edit_data['status'] ?? '') === 'active' ? 'checked' : ''; ?>
                                        class="peer sr-only">
                                    <div class="w-5 h-5 border-2 border-black bg-white peer-checked:bg-green-500 peer-checked:border-black transition-all"></div>
                                    <i class="fas fa-check text-white text-[10px] absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 opacity-0 peer-checked:opacity-100"></i>
                                </div>
                                <span class="text-xs font-black text-black uppercase tracking-widest">Set as active semester</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mt-8 flex gap-4 pt-6 border-t-2 border-black">
                        <button type="submit" class="btn-os bg-green-500 text-white border-black hover:bg-black hover:text-white hover:border-black">
                            <span class="flex items-center gap-2">
                                <i class="fas fa-save"></i> <?php echo $action === 'edit' ? 'Update Semester' : 'Save Semester'; ?>
                            </span>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <!-- List View with Tabs: Temporal Matrix -->
    <div class="os-card p-0 bg-white overflow-hidden">
        <!-- Tabs -->
        <div class="bg-black p-2 border-b-2 border-black">
            <nav class="flex gap-2">
                <button onclick="showTab('years')" id="tab-years" class="tab-button active px-6 py-2 rounded-none text-[10px] font-black uppercase tracking-[0.2em] transition-all bg-yellow-400 text-black border-2 border-white shadow-[2px_2px_0px_#ffffff]">
                    Academic Years
                </button>
                <button onclick="showTab('semesters')" id="tab-semesters" class="tab-button px-6 py-2 rounded-none text-[10px] font-black uppercase tracking-[0.2em] transition-all text-gray-400 hover:text-white border-2 border-transparent hover:border-white">
                    Semesters
                </button>
            </nav>
        </div>
        
        <!-- Academic Years Tab -->
        <div id="content-years" class="tab-content group-data-[tab=years]:block">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-black text-white">
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Academic Cycle</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Temporal Range</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-center border-r border-white/20">Sub-Cycles</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-center border-r border-white/20">Status</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-2 divide-slate-100">
                        <?php if (empty($academic_years)): ?>
                             <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 border-2 border-dashed border-slate-300 rounded-full flex items-center justify-center text-slate-300 mb-4">
                                            <i class="fas fa-calendar-xmark text-2xl"></i>
                                        </div>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">No academic years registered</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($academic_years as $year): ?>
                                <tr class="hover:bg-yellow-50 transition-all group border-l-2 border-r-2 border-transparent hover:border-black">
                                    <td class="px-6 py-4 border-r border-slate-100">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 bg-black text-yellow-400 flex items-center justify-center font-black text-xs shrink-0 border border-black shadow-[2px_2px_0px_rgba(0,0,0,0.2)]">
                                                <i class="fas fa-calendar-check"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-black text-black uppercase leading-none mb-1"><?php echo e($year['year']); ?></p>
                                                <p class="text-[9px] font-bold text-slate-400 uppercase">Academic Year</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 border-r border-slate-100">
                                        <div class="flex items-center gap-3">
                                            <span class="text-[10px] font-black text-black uppercase font-mono"><?php echo date('M d, Y', strtotime($year['start_date'])); ?></span>
                                            <i class="fas fa-arrow-right text-[8px] text-slate-300"></i>
                                            <span class="text-[10px] font-black text-black uppercase font-mono"><?php echo date('M d, Y', strtotime($year['end_date'])); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center border-r border-slate-100">
                                        <span class="px-2 py-1 bg-slate-100 border border-slate-200 text-[9px] font-black text-slate-600 uppercase tracking-widest"><?php echo $year['semester_count']; ?> Semesters</span>
                                    </td>
                                    <td class="px-6 py-4 text-center border-r border-slate-100">
                                        <span class="px-2 py-1 text-[8px] font-black uppercase border <?php echo $year['status'] === 'active' ? 'bg-green-100 text-green-700 border-green-700' : 'bg-slate-50 text-slate-400 border-slate-300'; ?>">
                                            <?php echo $year['status'] === 'active' ? 'Current' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <a href="?action=edit&type=year&id=<?php echo $year['id']; ?>" class="w-8 h-8 border border-black flex items-center justify-center text-black hover:bg-black hover:text-white transition-all shadow-[2px_2px_0px_#000000] hover:shadow-none hover:translate-x-[1px] hover:translate-y-[1px]" title="Edit Year">
                                                <i class="fas fa-edit text-[10px]"></i>
                                            </a>
                                            <form method="POST" onsubmit="return confirm('DANGER: This will fail if semesters exist. Continue?')">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete_year">
                                                <input type="hidden" name="id" value="<?php echo $year['id']; ?>">
                                                <button type="submit" class="w-8 h-8 border border-black bg-red-500 flex items-center justify-center text-white hover:bg-red-600 transition-all shadow-[2px_2px_0px_#000000] hover:shadow-none hover:translate-x-[1px] hover:translate-y-[1px]">
                                                    <i class="fas fa-trash-can text-[10px]"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Semesters Tab -->
        <div id="content-semesters" class="tab-content hidden group-data-[tab=semesters]:block">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-black text-white">
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Semester Profile</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Academic Cycle</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-center border-r border-white/20">Phase</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Temporal Range</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-center border-r border-white/20">Status</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-2 divide-slate-100">
                        <?php if (empty($semesters)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 border-2 border-dashed border-slate-300 rounded-full flex items-center justify-center text-slate-300 mb-4">
                                            <i class="fas fa-layer-group text-2xl"></i>
                                        </div>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">No semesters defined</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($semesters as $sem): ?>
                                <tr class="hover:bg-green-50 transition-all group border-l-2 border-r-2 border-transparent hover:border-black">
                                    <td class="px-6 py-4 border-r border-slate-100">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 bg-black text-green-400 flex items-center justify-center font-black text-xs shrink-0 border border-black shadow-[2px_2px_0px_rgba(0,0,0,0.2)]">
                                                <i class="fas fa-leaf"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-black text-black uppercase leading-none mb-1"><?php echo e($sem['name']); ?></p>
                                                <p class="text-[9px] font-bold text-slate-400 uppercase">Academic Phase</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 border-r border-slate-100">
                                        <span class="px-2 py-1 bg-white border border-black text-[9px] font-black text-black uppercase tracking-widest"><?php echo e($sem['year']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-center border-r border-slate-100">
                                        <span class="w-6 h-6 rounded-full bg-black text-white flex items-center justify-center text-[10px] font-black mx-auto border border-black"><?php echo $sem['semester_number']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 border-r border-slate-100">
                                        <div class="flex items-center gap-3">
                                            <span class="text-[10px] font-black text-black uppercase font-mono"><?php echo date('M d, Y', strtotime($sem['start_date'])); ?></span>
                                            <i class="fas fa-arrow-right text-[8px] text-slate-300"></i>
                                            <span class="text-[10px] font-black text-black uppercase font-mono"><?php echo date('M d, Y', strtotime($sem['end_date'])); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center border-r border-slate-100">
                                        <span class="px-2 py-1 text-[8px] font-black uppercase border <?php 
                                            echo match($sem['status']) {
                                                'active' => 'bg-green-100 text-green-700 border-green-700',
                                                'upcoming' => 'bg-blue-100 text-blue-700 border-blue-700',
                                                default => 'bg-slate-50 text-slate-400 border-slate-300'
                                            };
                                        ?>">
                                            <?php echo e($sem['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <a href="?action=edit&type=semester&id=<?php echo $sem['id']; ?>" class="w-8 h-8 border border-black flex items-center justify-center text-black hover:bg-black hover:text-white transition-all shadow-[2px_2px_0px_#000000] hover:shadow-none hover:translate-x-[1px] hover:translate-y-[1px]" title="Edit Semester">
                                                <i class="fas fa-edit text-[10px]"></i>
                                            </a>
                                            <form method="POST" onsubmit="return confirm('Confirm deletion of academic semester phase?')">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete_semester">
                                                <input type="hidden" name="id" value="<?php echo $sem['id']; ?>">
                                                <button type="submit" class="w-8 h-8 border border-black bg-red-500 flex items-center justify-center text-white hover:bg-red-600 transition-all shadow-[2px_2px_0px_#000000] hover:shadow-none hover:translate-x-[1px] hover:translate-y-[1px]">
                                                    <i class="fas fa-trash-can text-[10px]"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
    function showTab(tabName) {
        // Update URL safely without reload
        const url = new URL(window.location);
        url.searchParams.set('type', tabName === 'years' ? 'year' : 'semester');
        window.history.pushState({}, '', url);

        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active', 'bg-yellow-400', 'text-black', 'border-white', 'shadow-[2px_2px_0px_#ffffff]');
            btn.classList.add('text-gray-400', 'border-transparent');
        });
        
        // Show selected tab
        document.getElementById('content-' + tabName).classList.remove('hidden');
        const activeBtn = document.getElementById('tab-' + tabName);
        activeBtn.classList.add('active', 'bg-yellow-400', 'text-black', 'border-white', 'shadow-[2px_2px_0px_#ffffff]');
        activeBtn.classList.remove('text-gray-400', 'border-transparent');
    }

    // Initialize tab based on URL
    window.addEventListener('DOMContentLoaded', () => {
        const type = new URLSearchParams(window.location.search).get('type');
        if (type === 'semester') showTab('semesters');
    });
    </script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
