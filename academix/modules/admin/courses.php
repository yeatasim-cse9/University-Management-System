<?php
/**
 * Department Course Management
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('admin');

$page_title = 'Course Management';
$user_id = get_current_user_id();

// Get admin's department(s)
$stmt = $db->prepare("SELECT d.id, d.name FROM departments d JOIN department_admins da ON d.id = da.department_id WHERE da.user_id = ? AND d.deleted_at IS NULL");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$departments = [];
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}
$dept_id_list = !empty($departments) ? implode(',', array_column($departments, 'id')) : '0';

$action = $_GET['action'] ?? 'list';
$course_id = $_GET['id'] ?? null;

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request');
        redirect(BASE_URL . '/modules/admin/courses.php');
    }

    $post_action = $_POST['action'] ?? '';

    if ($post_action === 'create' || $post_action === 'update') {
        $department_id = intval($_POST['department_id']);
        $course_code = sanitize_input($_POST['course_code']);
        $course_name = sanitize_input($_POST['course_name']);
        $credit_hours = floatval($_POST['credit_hours']);
        $course_type = sanitize_input($_POST['course_type']);
        $semester_number = intval($_POST['semester_number']);
        $description = sanitize_input($_POST['description']);
        $status = sanitize_input($_POST['status']);
        $id = isset($_POST['id']) ? intval($_POST['id']) : null;

        // Security: Ensure department belongs to admin
        if (!in_array($department_id, array_column($departments, 'id'))) {
            set_flash('error', 'Permission denied for this department');
            redirect(BASE_URL . '/modules/admin/courses.php');
        }

        $errors = validate_required(['department_id', 'course_code', 'course_name', 'credit_hours', 'semester_number'], $_POST);

        if (empty($errors)) {
            if ($post_action === 'create') {
                $stmt = $db->prepare("INSERT INTO courses (department_id, course_code, course_name, credit_hours, course_type, semester_number, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issdisss", $department_id, $course_code, $course_name, $credit_hours, $course_type, $semester_number, $description, $status);
                if ($stmt->execute()) {
                    create_audit_log('create_course', 'courses', $stmt->insert_id, null, ['code' => $course_code]);
                    set_flash('success', 'Course created successfully');
                } else {
                    set_flash('error', 'Failed to create course');
                }
            } else {
                $stmt = $db->prepare("UPDATE courses SET department_id = ?, course_code = ?, course_name = ?, credit_hours = ?, course_type = ?, semester_number = ?, description = ?, status = ? WHERE id = ?");
                $stmt->bind_param("issdisssi", $department_id, $course_code, $course_name, $credit_hours, $course_type, $semester_number, $description, $status, $id);
                if ($stmt->execute()) {
                    create_audit_log('update_course', 'courses', $id);
                    set_flash('success', 'Course updated successfully');
                } else {
                    set_flash('error', 'Failed to update course');
                }
            }
            redirect(BASE_URL . '/modules/admin/courses.php');
        } else {
            set_flash('error', implode('<br>', $errors));
        }
    } elseif ($post_action === 'delete') {
        $id = intval($_POST['id']);
        // Security check
        $check = $db->query("SELECT id FROM courses WHERE id = $id AND department_id IN ($dept_id_list)");
        if ($check->num_rows > 0) {
            $db->query("UPDATE courses SET deleted_at = NOW() WHERE id = $id");
            create_audit_log('delete_course', 'courses', $id);
            set_flash('success', 'Course deleted successfully');
        } else {
            set_flash('error', 'Permission denied');
        }
        redirect(BASE_URL . '/modules/admin/courses.php');
    }
}

// Fetch Data for List
$courses = [];
if ($action === 'list') {
    $search = isset($_GET['search']) ? $db->real_escape_string($_GET['search']) : '';
    $query = "SELECT c.*, d.name as dept_name FROM courses c JOIN departments d ON c.department_id = d.id WHERE c.department_id IN ($dept_id_list) AND c.deleted_at IS NULL";
    
    if (!empty($search)) {
        $query .= " AND (c.course_code LIKE '%$search%' OR c.course_name LIKE '%$search%')";
    }
    
    $query .= " ORDER BY c.course_code";
    $res = $db->query($query);
    while ($row = $res->fetch_assoc()) { $courses[] = $row; }
}

// Fetch Edit/View Data
$view_data = null;
if (($action === 'edit' || $action === 'view') && $course_id) {
    $stmt = $db->prepare("SELECT c.*, d.name as dept_name FROM courses c JOIN departments d ON c.department_id = d.id WHERE c.id = ? AND c.department_id IN ($dept_id_list)");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $view_data = $stmt->get_result()->fetch_assoc();
}

// Sidebar
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<!-- Header -->
<div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white mb-8">
    <div class="relative z-10">
        <div class="flex items-center gap-3 mb-2">
            <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Inventory</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                Asset Management
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">Course <span class="text-black">Registry</span></h1>
    </div>
    
    <div class="relative z-10 flex flex-col md:flex-row gap-4 items-center">
        <?php if ($action === 'list'): ?>
            <form action="" method="GET" class="flex">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="SEARCH ASSETS..." class="bg-white text-black px-4 py-2 font-bold uppercase text-xs border-2 border-transparent focus:border-black outline-none w-48 md:w-64">
                <button type="submit" class="bg-black px-4 py-2 text-white font-black uppercase text-xs hover:bg-yellow-400 hover:text-black transition-colors">
                    <i class="fas fa-search"></i>
                </button>
            </form>
            <a href="?action=create" class="btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black hover:border-black flex items-center gap-2">
                <i class="fas fa-plus"></i> Initialize Asset
            </a>
        <?php else: ?>
            <a href="?" class="btn-os bg-white text-black border-black hover:bg-black hover:text-white hover:border-black flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back to Fleet
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($action === 'view' && $view_data): ?>
    <div class="os-card p-0 bg-white overflow-hidden">
        <div class="bg-black p-8 text-white relative border-b-2 border-black">
            <div class="flex flex-col md:flex-row items-center gap-8">
                <div class="w-32 h-32 bg-white text-black rounded-none border-2 border-white flex items-center justify-center relative shadow-[4px_4px_0px_#fff]">
                    <i class="fas fa-book text-5xl"></i>
                    <div class="absolute -top-3 -right-3 px-2 py-1 <?php echo $view_data['status'] === 'active' ? 'bg-green-600' : 'bg-red-600'; ?> text-white text-[8px] font-black uppercase tracking-widest border border-black shadow-[2px_2px_0px_#000]">
                        <?php echo strtoupper($view_data['status']); ?>
                    </div>
                </div>
                <div class="text-center md:text-left">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Asset Specification</p>
                    <h2 class="text-4xl md:text-5xl font-black uppercase tracking-tighter leading-none mb-3"><?php echo e($view_data['course_name']); ?></h2>
                    <div class="flex flex-wrap justify-center md:justify-start gap-2">
                        <span class="px-2 py-1 bg-white text-black text-[9px] font-black uppercase tracking-widest border border-black">CODE: <?php echo e($view_data['course_code']); ?></span>
                        <span class="px-2 py-1 bg-black border border-white text-white text-[9px] font-black uppercase tracking-widest"><?php echo e($view_data['dept_name']); ?></span>
                        <span class="px-2 py-1 bg-black border border-white text-white text-[9px] font-black uppercase tracking-widest">SEM: <?php echo e($view_data['semester_number']); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="p-8 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="space-y-6">
                <div>
                    <h4 class="text-xl font-black uppercase mb-4 border-b-2 border-black inline-block">Technical Weight</h4>
                    <div class="bg-slate-50 border-2 border-black p-4 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Credit Value</span>
                            <span class="text-sm font-black text-black"><?php echo number_format($view_data['credit_hours'], 1); ?> Unit</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Classification</span>
                            <span class="text-sm font-black text-black uppercase"><?php echo e($view_data['course_type']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2 space-y-6">
                <div>
                    <h4 class="text-xl font-black uppercase mb-4 border-b-2 border-black inline-block">Operational Briefing</h4>
                    <div class="bg-white border-2 border-black p-6 font-mono text-sm leading-relaxed">
                        <?php echo nl2br(e($view_data['description'] ?: 'No technical blueprint available for this asset.')); ?>
                    </div>
                </div>

                <div class="flex flex-wrap gap-4 pt-4 border-t-2 border-black border-dashed">
                    <a href="?action=edit&id=<?php echo $view_data['id']; ?>" class="btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black flex items-center gap-2">
                        <i class="fas fa-edit"></i> Modify Blueprint
                    </a>
                    <button onclick="window.print()" class="btn-os bg-white text-black border-black hover:bg-black hover:text-white flex items-center gap-2">
                        <i class="fas fa-file-export"></i> Export Schema
                    </button>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
    <div class="os-card p-0 bg-white max-w-5xl mx-auto">
        <div class="bg-black p-6 text-white border-b-2 border-black flex items-center gap-4">
            <div class="w-12 h-12 bg-white text-black flex items-center justify-center border-2 border-white">
                <i class="fas <?php echo $action === 'edit' ? 'fa-edit' : 'fa-plus'; ?> text-xl"></i>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Asset Configuration</p>
                <h3 class="text-2xl font-black uppercase tracking-tighter leading-none"><?php echo $action === 'edit' ? 'Synchronize' : 'Register'; ?> Course</h3>
            </div>
        </div>

        <form method="POST" class="p-8 space-y-8">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="<?php echo $action; ?>">
            <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo $view_data['id']; ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Parent Sector</label>
                    <div class="relative">
                        <select name="department_id" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none cursor-pointer" required>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?php echo $d['id']; ?>" <?php echo ($view_data['department_id'] ?? '') == $d['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($d['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                            <i class="fas fa-chevron-down text-black text-xs"></i>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Course Registry ID (Code)</label>
                    <input type="text" name="course_code" value="<?php echo e($view_data['course_code'] ?? ''); ?>" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" placeholder="e.g. CS101" required>
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Asset Designation (Name)</label>
                    <input type="text" name="course_name" value="<?php echo e($view_data['course_name'] ?? ''); ?>" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" placeholder="e.g. Introduction to Systems" required>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Credit Weight</label>
                    <input type="number" step="0.5" name="credit_hours" value="<?php echo e($view_data['credit_hours'] ?? '3.0'); ?>" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" required>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Asset Type</label>
                    <div class="relative">
                        <select name="course_type" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none cursor-pointer" required>
                             <option value="theory" <?php echo ($view_data['course_type'] ?? '') === 'theory' ? 'selected' : ''; ?>>THEORY</option>
                            <option value="lab" <?php echo ($view_data['course_type'] ?? '') === 'lab' ? 'selected' : ''; ?>>LABORATORY</option>
                            <option value="project" <?php echo ($view_data['course_type'] ?? '') === 'project' ? 'selected' : ''; ?>>PROJECT</option>
                        </select>
                         <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                            <i class="fas fa-chevron-down text-black text-xs"></i>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Target Semester</label>
                    <input type="number" name="semester_number" value="<?php echo e($view_data['semester_number'] ?? '1'); ?>" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" required>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Operational Status</label>
                    <div class="flex gap-4">
                        <?php foreach (['active' => 'peer-checked:bg-black', 'inactive' => 'peer-checked:bg-red-600'] as $val => $color): ?>
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="status" value="<?php echo $val; ?>" <?php echo ($view_data['status'] ?? 'active') === $val ? 'checked' : ''; ?> class="hidden peer">
                                <div class="<?php echo $color; ?> peer-checked:text-white bg-white text-slate-400 border-2 border-slate-200 peer-checked:border-black px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest transition-all hover:border-black">
                                    <?php echo strtoupper($val); ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Technical Briefing (Description)</label>
                    <textarea name="description" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all font-mono placeholder-slate-400 h-32"><?php echo e($view_data['description'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="flex gap-4 pt-6 border-t-2 border-black border-dashed">
                <a href="?" class="flex-1 btn-os bg-white text-black border-black hover:bg-black hover:text-white hover:border-black text-center">Abort Mission</a>
                <button type="submit" class="flex-1 btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black hover:border-black">
                    <i class="fas fa-rocket mr-2"></i> <?php echo $action === 'edit' ? 'Synchronize' : 'Register'; ?> Asset
                </button>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- List View -->
    <div class="os-card p-0 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-black text-white text-[10px] font-black uppercase tracking-widest border-b-2 border-black">
                        <th class="px-6 py-4 text-left">Asset Identification</th>
                        <th class="px-6 py-4 text-left">Sector</th>
                        <th class="px-6 py-4 text-left">Configuration</th>
                        <th class="px-6 py-4 text-left">Status</th>
                        <th class="px-6 py-4 text-right">Operations</th>
                    </tr>
                </thead>
                <tbody class="divide-y-2 divide-black">
                    <?php if (!empty($courses)): foreach ($courses as $c): ?>
                        <tr class="hover:bg-yellow-50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="text-[11px] font-black text-black uppercase transition-colors group-hover:text-blue-600"><?php echo e($c['course_code']); ?></div>
                                <div class="text-[9px] font-bold text-slate-500 uppercase italic leading-tight"><?php echo e($c['course_name']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-white border border-black text-black rounded-none text-[9px] font-black uppercase tracking-widest"><?php echo e($c['dept_name']); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-[10px] font-black text-black uppercase italic"><?php echo number_format($c['credit_hours'], 1); ?> Credits</div>
                                <div class="text-[9px] font-bold text-slate-500 uppercase italic">Type: <?php echo e(strtoupper($c['course_type'])); ?> | SEM: <?php echo e($c['semester_number']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 border-2 border-black text-[9px] font-black uppercase tracking-widest <?php echo $c['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo strtoupper($c['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="?action=view&id=<?php echo $c['id']; ?>" class="w-8 h-8 flex items-center justify-center border-2 border-black bg-white hover:bg-black hover:text-white transition-all shadow-[2px_2px_0px_#000000]" title="View Specification">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    <a href="?action=edit&id=<?php echo $c['id']; ?>" class="w-8 h-8 flex items-center justify-center border-2 border-black bg-white hover:bg-black hover:text-white transition-all shadow-[2px_2px_0px_#000000]" title="Modify Blueprint">
                                        <i class="fas fa-edit text-xs"></i>
                                    </a>
                                    <form method="POST" onsubmit="return confirm('Initiate asset decommissioning?');">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                        <button type="submit" class="w-8 h-8 flex items-center justify-center border-2 border-black bg-white hover:bg-red-600 hover:text-white transition-all shadow-[2px_2px_0px_#000000]" title="Decommission">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <i class="fas fa-folder-open text-4xl text-slate-300 mb-2 block"></i>
                                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">No academic assets detected</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
