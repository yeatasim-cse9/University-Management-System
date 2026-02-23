<?php
/**
 * Course Management
 * ACADEMIX - University Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('super_admin');

$action = $_GET['action'] ?? 'list';
$course_id = $_GET['id'] ?? null;
$pre_dept = $_GET['dept_id'] ?? '';
$page_title = match($action) {
    'create' => 'Add Course',
    'edit' => 'Edit Course',
    default => 'Course Catalog'
};

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request');
        redirect(BASE_URL . '/modules/super_admin/courses.php');
    }
    
    $post_action = $_POST['action'] ?? '';
    
    if ($post_action === 'create') {
        $department_id = intval($_POST['department_id']);
        $course_code = sanitize_input($_POST['course_code'] ?? '');
        $course_name = sanitize_input($_POST['course_name'] ?? '');
        $credit_hours = floatval($_POST['credit_hours']);
        $course_type = sanitize_input($_POST['course_type'] ?? 'theory');
        $semester_number = intval($_POST['semester_number']);
        $description = sanitize_input($_POST['description'] ?? '');
        $status = sanitize_input($_POST['status'] ?? 'active');
        
        $errors = validate_required(['department_id', 'course_code', 'course_name', 'credit_hours', 'semester_number'], $_POST);
        
        if (empty($errors)) {
            $stmt = $db->prepare("INSERT INTO courses (department_id, course_code, course_name, credit_hours, course_type, semester_number, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issdisss", $department_id, $course_code, $course_name, $credit_hours, $course_type, $semester_number, $description, $status);
            
            if ($stmt->execute()) {
                create_audit_log('create_course', 'courses', $stmt->insert_id, null, ['course_code' => $course_code, 'course_name' => $course_name]);
                set_flash('success', 'Course created successfully');
                redirect(BASE_URL . '/modules/super_admin/courses.php');
            } else {
                set_flash('error', 'Failed to create course. Course code may already exist.');
            }
        } else {
            set_flash('error', implode(', ', $errors));
        }
    }
    
    elseif ($post_action === 'update') {
        $id = intval($_POST['id']);
        $department_id = intval($_POST['department_id']);
        $course_code = sanitize_input($_POST['course_code'] ?? '');
        $course_name = sanitize_input($_POST['course_name'] ?? '');
        $credit_hours = floatval($_POST['credit_hours']);
        $course_type = sanitize_input($_POST['course_type'] ?? 'theory');
        $semester_number = intval($_POST['semester_number']);
        $description = sanitize_input($_POST['description'] ?? '');
        $status = sanitize_input($_POST['status'] ?? 'active');
        
        $stmt = $db->prepare("UPDATE courses SET department_id = ?, course_code = ?, course_name = ?, credit_hours = ?, course_type = ?, semester_number = ?, description = ?, status = ? WHERE id = ?");
        $stmt->bind_param("issdssssi", $department_id, $course_code, $course_name, $credit_hours, $course_type, $semester_number, $description, $status, $id);
        
        if ($stmt->execute()) {
            create_audit_log('update_course', 'courses', $id, null, ['course_name' => $course_name]);
            set_flash('success', 'Course updated successfully');
            redirect(BASE_URL . '/modules/super_admin/courses.php');
        } else {
            set_flash('error', 'Failed to update course');
        }
    }
    
    elseif ($post_action === 'delete') {
        $id = intval($_POST['id']);
        
        $stmt = $db->prepare("UPDATE courses SET deleted_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            create_audit_log('delete_course', 'courses', $id);
            set_flash('success', 'Course deleted successfully');
        } else {
            set_flash('error', 'Failed to delete course');
        }
        redirect(BASE_URL . '/modules/super_admin/courses.php');
    }
}

// Get course for edit
$course = null;
if ($action === 'edit' && $course_id) {
    $stmt = $db->prepare("SELECT * FROM courses WHERE id = ? AND deleted_at IS NULL");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();
}

// Get all departments for dropdown
$departments = [];
$result = $db->query("SELECT id, name, code FROM departments WHERE deleted_at IS NULL ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}

// Get all courses
$search = $_GET['search'] ?? '';
$dept_filter = $_GET['dept'] ?? '';

$where = "c.deleted_at IS NULL";
if ($search) {
    $search_safe = $db->real_escape_string($search);
    $where .= " AND (c.course_code LIKE '%$search_safe%' OR c.course_name LIKE '%$search_safe%')";
}
if ($dept_filter) {
    $where .= " AND c.department_id = " . intval($dept_filter);
}

$courses = [];
$result = $db->query("SELECT c.*, d.name as department_name, d.code as department_code 
    FROM courses c 
    JOIN departments d ON c.department_id = d.id 
    WHERE $where 
    ORDER BY d.name, c.semester_number, c.course_code");
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

// Global Stats for Courses
$course_stats = [
    'total' => count($courses),
    'theory' => count(array_filter($courses, fn($c) => $c['course_type'] === 'theory')),
    'lab' => count(array_filter($courses, fn($c) => $c['course_type'] === 'lab')),
    'credits' => array_sum(array_column($courses, 'credit_hours'))
];

// Sidebar menu
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

// Page content
ob_start();
?>

<div class="space-y-6">
    <!-- Catalog Header -->
    <div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white">
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
                <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Academic Catalog</span>
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                    <span class="w-1.5 h-1.5 rounded-none bg-green-500 inline-block mr-1"></span>
                    System Online
                </span>
            </div>
            <h1 class="text-3xl font-black uppercase tracking-tighter"><?php echo $page_title; ?></h1>
        </div>
        
        <?php if ($action === 'list'): ?>
            <a href="?action=create" class="btn-os bg-green-500 text-white border-black hover:bg-black hover:text-white hover:border-black group relative overflow-hidden">
                <span class="relative z-10 flex items-center gap-2">
                    <i class="fas fa-plus-circle text-sm"></i> Add New Course
                </span>
            </a>
        <?php else: ?>
            <a href="?" class="btn-os bg-white text-black border-black hover:bg-black hover:text-white group">
                <span class="flex items-center gap-2">
                    <i class="fas fa-arrow-left text-sm transition-transform group-hover:-translate-x-1"></i> Back to List
                </span>
            </a>
        <?php endif; ?>
    </div>

    <?php if ($action === 'create' || $action === 'edit'): ?>
        <!-- Course Form -->
        <div class="max-w-4xl mx-auto">
            <div class="os-card p-0 overflow-hidden bg-white">
                <div class="bg-black p-6 text-white flex justify-between items-center border-b-2 border-black">
                    <div>
                        <h3 class="text-xl font-black italic uppercase tracking-widest text-white">Course <span class="text-green-500">Form</span></h3>
                        <p class="text-[10px] font-bold font-mono text-slate-400 uppercase mt-1">
                            MODULE::<?php echo $action === 'edit' ? 'UPDATE' : 'INIT'; ?>_SEQUENCE
                        </p>
                    </div>
                    <i class="fas fa-book-open text-2xl text-green-500"></i>
                </div>

                <form method="POST" class="p-8 space-y-8">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="<?php echo $action === 'edit' ? 'update' : 'create'; ?>">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo e($course['id']); ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] italic">Department <span class="text-red-500">*</span></label>
                            <select name="department_id" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none" required>
                                 <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo ($course['department_id'] ?? $pre_dept) == $dept['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($dept['name']); ?> (<?php echo e($dept['code']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] italic">Course Code <span class="text-red-500">*</span></label>
                            <input type="text" name="course_code" value="<?php echo e($course['course_code'] ?? ''); ?>" placeholder="CSE-101" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-black text-sm text-black uppercase tracking-widest focus:outline-none focus:bg-yellow-50 transition-all placeholder-slate-400" required>
                        </div>

                        <div class="md:col-span-2 space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] italic">Course Name <span class="text-red-500">*</span></label>
                            <input type="text" name="course_name" value="<?php echo e($course['course_name'] ?? ''); ?>" placeholder="Introduction to Advanced Robotics" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" required>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] italic">Credit Hours <span class="text-red-500">*</span></label>
                            <input type="number" step="0.5" name="credit_hours" value="<?php echo e($course['credit_hours'] ?? '3.0'); ?>" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-black text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all" min="0.5" max="6" required>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] italic">Course Type <span class="text-red-500">*</span></label>
                            <select name="course_type" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-black text-sm text-black uppercase tracking-widest focus:outline-none focus:bg-yellow-50 transition-all appearance-none" required>
                                <option value="theory" <?php echo ($course['course_type'] ?? 'theory') === 'theory' ? 'selected' : ''; ?>>Theory</option>
                                <option value="lab" <?php echo ($course['course_type'] ?? '') === 'lab' ? 'selected' : ''; ?>>Lab</option>
                                <option value="project" <?php echo ($course['course_type'] ?? '') === 'project' ? 'selected' : ''; ?>>Project</option>
                                <option value="thesis" <?php echo ($course['course_type'] ?? '') === 'thesis' ? 'selected' : ''; ?>>Thesis</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] italic">Semester Number <span class="text-red-500">*</span></label>
                            <input type="number" name="semester_number" value="<?php echo e($course['semester_number'] ?? '1'); ?>" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-black text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all" min="1" max="12" required>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] italic">Course Status</label>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="status" value="active" <?php echo ($course['status'] ?? 'active') === 'active' ? 'checked' : ''; ?> class="peer sr-only">
                                    <div class="px-4 py-3 bg-white border-2 border-slate-200 peer-checked:border-black peer-checked:bg-black peer-checked:text-white text-[10px] font-black uppercase tracking-widest text-slate-400 text-center transition-all hover:border-black">Active</div>
                                </label>
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="status" value="inactive" <?php echo ($course['status'] ?? '') === 'inactive' ? 'checked' : ''; ?> class="peer sr-only">
                                    <div class="px-4 py-3 bg-white border-2 border-slate-200 peer-checked:border-red-600 peer-checked:bg-red-600 peer-checked:text-white text-[10px] font-black uppercase tracking-widest text-slate-400 text-center transition-all hover:border-red-600">Inactive</div>
                                </label>
                            </div>
                        </div>

                        <div class="md:col-span-2 space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] italic">Description</label>
                            <textarea name="description" rows="3" placeholder="Course objectives and curriculum overview..." class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-medium text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all font-mono placeholder-slate-400"><?php echo e($course['description'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="pt-6 border-t-2 border-black flex justify-end gap-6">
                        <button type="submit" class="btn-os bg-green-500 text-white border-black hover:bg-black hover:text-white hover:border-black">
                            <span class="flex items-center gap-2">
                                <i class="fas fa-save"></i>
                                Save Course
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="os-card p-4 bg-white flex items-center justify-between">
                <div>
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Total Modules</p>
                    <p class="text-3xl font-black text-black italic uppercase"><?php echo $course_stats['total']; ?></p>
                </div>
                <div class="w-10 h-10 border-2 border-black bg-slate-50 flex items-center justify-center text-black">
                    <i class="fas fa-book-bookmark"></i>
                </div>
            </div>
            <div class="os-card p-4 bg-white flex items-center justify-between">
                <div>
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Theory Units</p>
                    <p class="text-3xl font-black text-blue-600 italic uppercase"><?php echo $course_stats['theory']; ?></p>
                </div>
                <div class="w-10 h-10 border-2 border-black bg-blue-50 flex items-center justify-center text-blue-600">
                    <i class="fas fa-chalkboard"></i>
                </div>
            </div>
            <div class="os-card p-4 bg-white flex items-center justify-between">
                <div>
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Practical Labs</p>
                    <p class="text-3xl font-black text-green-600 italic uppercase"><?php echo $course_stats['lab']; ?></p>
                </div>
                <div class="w-10 h-10 border-2 border-black bg-green-50 flex items-center justify-center text-green-600">
                    <i class="fas fa-flask-vial"></i>
                </div>
            </div>
            <div class="os-card p-4 bg-white flex items-center justify-between">
                <div>
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Total Credits</p>
                    <p class="text-3xl font-black text-yellow-500 italic uppercase"><?php echo $course_stats['credits']; ?></p>
                </div>
                <div class="w-10 h-10 border-2 border-black bg-yellow-50 flex items-center justify-center text-yellow-500">
                    <i class="fas fa-star"></i>
                </div>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="os-card p-6 bg-white">
            <form method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-black"></i>
                    <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Search by course code or name..." class="w-full pl-10 pr-4 py-3 bg-white border-2 border-black rounded-none text-xs font-bold text-black focus:outline-none focus:bg-yellow-50 transition-all placeholder-slate-400">
                </div>
                <div class="relative">
                    <select name="dept" class="px-6 py-3 bg-white border-2 border-black rounded-none text-xs font-bold text-black focus:outline-none focus:bg-yellow-50 transition-all appearance-none cursor-pointer uppercase tracking-wider pr-10">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" <?php echo $dept_filter == $dept['id'] ? 'selected' : ''; ?>>
                                <?php echo e($dept['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-black pointer-events-none text-xs"></i>
                </div>
                <button type="submit" class="btn-os bg-black text-white border-black hover:bg-white hover:text-black">
                    Filter Results
                </button>
                <?php if ($search || $dept_filter): ?>
                    <a href="?" class="btn-os bg-white text-black border-black hover:bg-red-500 hover:text-white hover:border-red-500">
                        Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Course Table -->
        <div class="os-card p-0 overflow-hidden bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-black text-white">
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Course Profile</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">Department</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-center border-r border-white/20">Type</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-center border-r border-white/20">Credits</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-center border-r border-white/20">Sem</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-center border-r border-white/20">Status</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-2 divide-slate-100">
                        <?php if (empty($courses)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 border-2 border-dashed border-slate-300 rounded-full flex items-center justify-center text-slate-300 mb-4">
                                            <i class="fas fa-book-open text-2xl"></i>
                                        </div>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">No courses found match your criteria</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($courses as $c): ?>
                                <tr class="hover:bg-yellow-50 transition-all group border-l-2 border-r-2 border-transparent hover:border-black">
                                    <td class="px-6 py-4 border-r border-slate-100">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 bg-black text-white flex items-center justify-center font-black text-[10px] shrink-0 border border-black shadow-[2px_2px_0px_rgba(0,0,0,0.2)]">
                                                <?php echo e(substr($c['course_code'], 0, 3)); ?>
                                            </div>
                                            <div>
                                                <p class="text-xs font-black text-black uppercase leading-none mb-1 group-hover:underline decoration-2 underline-offset-2"><?php echo e($c['course_name']); ?></p>
                                                <p class="text-[9px] font-bold text-slate-500 font-mono"><?php echo e($c['course_code']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 border-r border-slate-100">
                                        <span class="px-2 py-1 bg-white border border-black rounded-none text-[8px] font-black text-black uppercase tracking-widest"><?php echo e($c['department_code']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-center border-r border-slate-100">
                                        <span class="text-[9px] font-black text-slate-600 uppercase tracking-widest"><?php echo e($c['course_type']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-center border-r border-slate-100">
                                        <span class="text-xs font-black text-black font-mono"><?php echo number_format($c['credit_hours'], 1); ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-center border-r border-slate-100">
                                        <span class="inline-flex w-6 h-6 items-center justify-center border border-black rounded-full text-[9px] font-bold bg-white"><?php echo $c['semester_number']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-center border-r border-slate-100">
                                        <span class="px-2 py-1 text-[8px] font-black uppercase border <?php echo $c['status'] === 'active' ? 'bg-green-100 text-green-700 border-green-700' : 'bg-red-100 text-red-700 border-red-700'; ?>">
                                            <?php echo e($c['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <a href="?action=edit&id=<?php echo $c['id']; ?>" class="w-8 h-8 border border-black flex items-center justify-center text-black hover:bg-black hover:text-white transition-all shadow-[2px_2px_0px_#000000] hover:shadow-none hover:translate-x-[1px] hover:translate-y-[1px]" title="Edit Course">
                                                <i class="fas fa-edit text-[10px]"></i>
                                            </a>
                                            <form method="POST" onsubmit="return confirm('DANGER: This will permanently delete course records. Continue?')">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
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
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
