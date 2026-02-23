<?php
/**
 * Department Management
 * ACADEMIX - University Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('super_admin');

$action = $_GET['action'] ?? 'list';
$dept_id = $_GET['id'] ?? null;
$page_title = match($action) {
    'create' => 'Add Department',
    'edit' => 'Edit Department',
    'view' => 'Department Analysis',
    default => 'Departments'
};

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Security error');
        redirect(BASE_URL . '/modules/super_admin/departments.php');
    }
    
    $post_action = $_POST['action'] ?? '';
    
    if ($post_action === 'create') {
        $name = sanitize_input($_POST['name'] ?? '');
        $code = sanitize_input($_POST['code'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $status = sanitize_input($_POST['status'] ?? 'active');
        
        $errors = validate_required(['name', 'code'], $_POST);
        
        if (empty($errors)) {
            $stmt = $db->prepare("INSERT INTO departments (name, code, description, status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $code, $description, $status);
            
            if ($stmt->execute()) {
                create_audit_log('create_department', 'departments', $stmt->insert_id, null, ['name' => $name, 'code' => $code]);
                set_flash('success', 'Department added successfully');
                redirect(BASE_URL . '/modules/super_admin/departments.php');
            } else {
                set_flash('error', 'Initialization failure: Code conflict detected.');
            }
        } else {
            set_flash('error', implode(', ', $errors));
        }
    } elseif ($post_action === 'update') {
        $id = intval($_POST['id']);
        $name = sanitize_input($_POST['name'] ?? '');
        $code = sanitize_input($_POST['code'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $status = sanitize_input($_POST['status'] ?? 'active');
        
        $stmt = $db->prepare("UPDATE departments SET name = ?, code = ?, description = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $code, $description, $status, $id);
        
        if ($stmt->execute()) {
            create_audit_log('update_department', 'departments', $id, null, ['name' => $name]);
            set_flash('success', 'Department updated successfully');
            redirect(BASE_URL . '/modules/super_admin/departments.php');
        } else {
            set_flash('error', 'Configuration update failed');
        }
    } elseif ($post_action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $db->prepare("UPDATE departments SET deleted_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            create_audit_log('delete_department', 'departments', $id);
            set_flash('success', 'Department deleted successfully');
        }
        redirect(BASE_URL . '/modules/super_admin/departments.php');
    }
}

// Data Fetching
$department = null;
if ($action === 'edit' && $dept_id) {
    $stmt = $db->prepare("SELECT * FROM departments WHERE id = ? AND deleted_at IS NULL");
    $stmt->bind_param("i", $dept_id);
    $stmt->execute();
    $department = $stmt->get_result()->fetch_assoc();
}

// Fetch department details for view
$dept_details = [
    'teachers' => [],
    'students' => [],
    'courses' => []
];

if ($action === 'view' && $dept_id) {
    // Basic Dept Info
    $stmt = $db->prepare("SELECT * FROM departments WHERE id = ? AND deleted_at IS NULL");
    $stmt->bind_param("i", $dept_id);
    $stmt->execute();
    $department = $stmt->get_result()->fetch_assoc();

    if ($department) {
        $page_title = $department['name'] . ' - Analytics';
        
        // Department Head/Admin
        $stmt = $db->prepare("
            SELECT u.id, u.username, up.first_name, up.last_name, u.email
            FROM department_admins da
            JOIN users u ON da.user_id = u.id
            JOIN user_profiles up ON u.id = up.user_id
            WHERE da.department_id = ? AND u.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->bind_param("i", $dept_id);
        $stmt->execute();
        $dept_details['admin'] = $stmt->get_result()->fetch_assoc();

        // Teachers
        $result = $db->query("
            SELECT u.id, up.first_name, up.last_name, t.designation, t.employee_id
            FROM teachers t
            JOIN users u ON t.user_id = u.id
            JOIN user_profiles up ON u.id = up.user_id
            WHERE t.department_id = $dept_id AND u.deleted_at IS NULL
        ");
        while($row = $result->fetch_assoc()) $dept_details['teachers'][] = $row;

        // Students
        $result = $db->query("
            SELECT u.id, u.email, up.first_name, up.last_name, s.student_id, s.current_semester
            FROM students s
            JOIN users u ON s.user_id = u.id
            JOIN user_profiles up ON u.id = up.user_id
            WHERE s.department_id = $dept_id AND u.deleted_at IS NULL
        ");
        while($row = $result->fetch_assoc()) $dept_details['students'][] = $row;

        // Courses
        $result = $db->query("
            SELECT * FROM courses 
            WHERE department_id = $dept_id AND deleted_at IS NULL 
            ORDER BY semester_number, course_code
        ");
        while($row = $result->fetch_assoc()) $dept_details['courses'][] = $row;
    }
}

$departments = [];
$result = $db->query("SELECT d.*, 
    (SELECT COUNT(*) FROM students WHERE department_id = d.id) as student_count,
    (SELECT COUNT(*) FROM teachers WHERE department_id = d.id) as teacher_count,
    (SELECT COUNT(*) FROM courses WHERE department_id = d.id AND deleted_at IS NULL) as course_count
    FROM departments d 
    WHERE d.deleted_at IS NULL 
    ORDER BY d.name");
while ($row = $result->fetch_assoc()) $departments[] = $row;

// Global Stats
$global_stats = [
    'total' => count($departments),
    'active' => count(array_filter($departments, fn($d) => $d['status'] === 'active')),
    'capacity' => array_sum(array_column($departments, 'student_count'))
];

// UI Assembly
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<div class="space-y-6">
    <!-- Hub Header -->
    <div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white">
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
                <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Academic Departments</span>
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
                    <i class="fas fa-plus-circle text-sm"></i> Add New Department
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

    <?php if ($action === 'list'): ?>
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="os-card p-4 bg-white flex items-center justify-between">
                <div>
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Total Departments</p>
                    <p class="text-3xl font-black text-black italic uppercase"><?php echo $global_stats['total']; ?></p>
                </div>
                <div class="w-10 h-10 border-2 border-black bg-slate-50 flex items-center justify-center text-black">
                    <i class="fas fa-layer-group"></i>
                </div>
            </div>
            <div class="os-card p-4 bg-white flex items-center justify-between">
                <div>
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Active Units</p>
                    <p class="text-3xl font-black text-green-600 italic uppercase"><?php echo $global_stats['active']; ?></p>
                </div>
                <div class="w-10 h-10 border-2 border-black bg-green-50 flex items-center justify-center text-green-600">
                    <i class="fas fa-circle-check"></i>
                </div>
            </div>
            <div class="os-card p-4 bg-white flex items-center justify-between">
                <div>
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Total Students</p>
                    <p class="text-3xl font-black text-blue-600 italic uppercase"><?php echo number_format($global_stats['capacity']); ?></p>
                </div>
                <div class="w-10 h-10 border-2 border-black bg-blue-50 flex items-center justify-center text-blue-600">
                    <i class="fas fa-users-rays"></i>
                </div>
            </div>
        </div>

        <!-- Department Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($departments)): ?>
                <div class="col-span-full py-20 text-center bg-white border-2 border-dashed border-black p-10">
                    <i class="fas fa-ghost text-4xl text-slate-300 mb-4"></i>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">No Departments Found</p>
                </div>
            <?php else: ?>
                <?php foreach ($departments as $dept): ?>
                    <div class="group relative bg-white p-6 border-2 border-black shadow-[4px_4px_0px_#000000] hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-none transition-all duration-200 flex flex-col justify-between h-[340px] overflow-hidden">
                        
                        <div class="relative z-10">
                            <div class="flex items-center justify-between mb-4">
                                <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black"><?php echo e($dept['code']); ?></span>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 border border-black <?php echo $dept['status'] === 'active' ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                                    <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest"><?php echo $dept['status']; ?></span>
                                </div>
                            </div>
                            
                            <h3 class="text-xl font-black text-black tracking-tighter uppercase leading-tight mb-2"><?php echo e($dept['name']); ?></h3>
                            <p class="text-[10px] font-medium text-slate-500 line-clamp-3 uppercase tracking-wide h-12 mb-6 font-mono"><?php echo e($dept['description'] ?: 'No description provided for this department.'); ?></p>
                        </div>

                        <div class="relative z-10 mt-auto">
                            <div class="grid grid-cols-3 gap-2 border-t-2 border-black pt-4 mb-6">
                                <div class="text-center">
                                    <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest mb-1">Students</p>
                                    <p class="text-lg font-black text-black tracking-tighter italic"><?php echo $dept['student_count']; ?></p>
                                </div>
                                <div class="text-center border-x-2 border-slate-100">
                                    <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest mb-1">Faculty</p>
                                    <p class="text-lg font-black text-black tracking-tighter italic"><?php echo $dept['teacher_count']; ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest mb-1">Courses</p>
                                    <p class="text-lg font-black text-black tracking-tighter italic"><?php echo $dept['course_count']; ?></p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="?action=view&id=<?php echo $dept['id']; ?>" class="w-10 h-10 bg-white border-2 border-black flex items-center justify-center text-black hover:bg-black hover:text-white transition-all shadow-[2px_2px_0px_#000000] hover:shadow-none hover:translate-x-[1px] hover:translate-y-[1px]" title="View Details">
                                    <i class="fas fa-chart-line"></i>
                                </a>
                                <a href="?action=edit&id=<?php echo $dept['id']; ?>" class="flex-1 py-2 bg-white border-2 border-black text-[10px] font-black text-black uppercase tracking-widest text-center hover:bg-black hover:text-white transition-all shadow-[2px_2px_0px_#000000] hover:shadow-none hover:translate-x-[1px] hover:translate-y-[1px]">Edit</a>
                                <form method="POST" class="shrink-0" onsubmit="return confirm('DANGER: This will permanently delete the department. Continue?')">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $dept['id']; ?>">
                                    <button type="submit" class="w-10 h-10 bg-red-500 border-2 border-black flex items-center justify-center text-white hover:bg-red-600 transition-all shadow-[2px_2px_0px_#000000] hover:shadow-none hover:translate-x-[1px] hover:translate-y-[1px]">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php elseif ($action === 'view' && $department): ?>
        <!-- Department In-depth Analysis View -->
        <div class="space-y-6">
            <!-- Stats Header -->
            <div class="os-card p-8 bg-black text-white relative overflow-hidden">
                <div class="flex flex-col md:flex-row items-center justify-between gap-8 relative z-10">
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <span class="px-2 py-0.5 bg-white text-black text-[10px] font-black uppercase tracking-widest border-2 border-white"><?php echo e($department['code']); ?></span>
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Department Analytics</span>
                        </div>
                        <h2 class="text-4xl font-black uppercase tracking-tighter leading-none mb-4"><?php echo e($department['name']); ?></h2>
                        <p class="text-slate-400 text-sm font-mono max-w-2xl border-l-2 border-yellow-400 pl-4"><?php echo e($department['description'] ?: 'Academic sector overview and resource distribution.'); ?></p>
                        
                        <?php if ($dept_details['admin']): ?>
                            <div class="mt-8 flex items-center gap-6">
                                <div class="flex items-center gap-4 p-4 border-2 border-white/20 bg-white/5">
                                    <div class="w-10 h-10 bg-yellow-400 flex items-center justify-center border-2 border-white">
                                        <i class="fas fa-crown text-black"></i>
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-black text-yellow-400 uppercase tracking-widest mb-1">Sector Commander</p>
                                        <p class="text-sm font-black text-white uppercase"><?php echo e($dept_details['admin']['first_name'] . ' ' . $dept_details['admin']['last_name']); ?></p>
                                    </div>
                                </div>
                                <div class="flex gap-4">
                                    <div class="text-center">
                                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Students</p>
                                        <p class="text-2xl font-black text-white"><?php echo count($dept_details['students']); ?></p>
                                    </div>
                                    <div class="w-px h-8 bg-white/20 mt-2"></div>
                                    <div class="text-center">
                                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Courses</p>
                                        <p class="text-2xl font-black text-white"><?php echo count($dept_details['courses']); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex flex-col gap-3 shrink-0 w-full md:w-auto">
                        <a href="?action=edit&id=<?php echo $department['id']; ?>" class="btn-os bg-white text-black border-white hover:bg-yellow-400 hover:text-black hover:border-yellow-400">
                            <i class="fas fa-edit mr-2"></i> Modify Infrastructure
                        </a>
                        <form method="POST" onsubmit="return confirm('DANGER: This will permanently delete the department. Continue?')">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $department['id']; ?>">
                            <button type="submit" class="w-full btn-os bg-red-600 text-white border-red-600 hover:bg-white hover:text-red-600">
                                <i class="fas fa-trash-alt mr-2"></i> Decommission Logic
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Faculty Registry -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="os-card p-6 bg-white h-full">
                        <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-black">
                            <h3 class="text-xs font-black text-black uppercase tracking-widest flex items-center gap-2">
                                <i class="fas fa-chalkboard-teacher"></i> Faculty Nodes (<?php echo count($dept_details['teachers']); ?>)
                            </h3>
                            <a href="users.php?action=create&role=teacher&dept_id=<?php echo $dept_id; ?>" class="w-6 h-6 border border-black flex items-center justify-center text-black hover:bg-black hover:text-white transition-all" title="Add Faculty Node">
                                <i class="fas fa-plus text-[10px]"></i>
                            </a>
                        </div>
                        <div class="space-y-3">
                            <?php if (empty($dept_details['teachers'])): ?>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest py-8 text-center bg-slate-50 border border-slate-200 border-dashed">No faculty assigned.</p>
                            <?php else: ?>
                                <?php foreach ($dept_details['teachers'] as $t): ?>
                                    <div class="flex items-center justify-between p-3 border border-slate-200 hover:border-black transition-all group/row hover:shadow-[2px_2px_0px_#000]">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-black text-white flex items-center justify-center text-[10px] font-black border border-black">
                                                <?php echo strtoupper(substr($t['first_name'], 0, 1) . substr($t['last_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <p class="text-[11px] font-black text-black uppercase leading-none mb-0.5"><?php echo e($t['first_name'] . ' ' . $t['last_name']); ?></p>
                                                <p class="text-[8px] font-bold text-slate-500 uppercase tracking-widest"><?php echo e($t['designation']); ?></p>
                                            </div>
                                        </div>
                                        <div class="flex gap-2 opacity-0 group-hover/row:opacity-100 transition-opacity">
                                            <a href="users.php?action=edit&id=<?php echo $t['id']; ?>" class="text-[10px] text-slate-400 hover:text-black"><i class="fas fa-pen"></i></a>
                                            <form method="POST" action="users.php" class="inline" onsubmit="return confirm('Purge faculty node?')">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                                <button type="submit" class="text-[10px] text-slate-400 hover:text-red-600"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Strategic Catalog (Courses) -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="os-card p-6 bg-white h-full">
                        <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-black">
                            <h3 class="text-xs font-black text-black uppercase tracking-widest flex items-center gap-2">
                                <i class="fas fa-book"></i> Course Catalog (<?php echo count($dept_details['courses']); ?>)
                            </h3>
                            <a href="courses.php?action=create&dept_id=<?php echo $dept_id; ?>" class="w-6 h-6 border border-black flex items-center justify-center text-black hover:bg-black hover:text-white transition-all" title="Add Course">
                                <i class="fas fa-plus text-[10px]"></i>
                            </a>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php if (empty($dept_details['courses'])): ?>
                                <p class="col-span-full text-[10px] font-black text-slate-400 uppercase tracking-widest py-8 text-center bg-slate-50 border border-slate-200 border-dashed">No courses offered.</p>
                            <?php else: ?>
                                <?php foreach ($dept_details['courses'] as $c): ?>
                                    <div class="p-4 border-2 border-slate-100 hover:border-black hover:shadow-[3px_3px_0px_#000] transition-all group/item bg-white">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-[10px] font-black text-black bg-yellow-300 px-1 uppercase tracking-widest border border-black"><?php echo e($c['course_code']); ?></span>
                                            <div class="flex items-center gap-3">
                                                <div class="flex items-center gap-2 opacity-0 group-hover/item:opacity-100 transition-opacity">
                                                    <a href="courses.php?action=edit&id=<?php echo $c['id']; ?>" class="text-[8px] text-slate-400 hover:text-black"><i class="fas fa-pen"></i></a>
                                                    <form method="POST" action="courses.php" class="inline" onsubmit="return confirm('Purge course record?')">
                                                        <?php csrf_field(); ?>
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                                        <button type="submit" class="text-[8px] text-slate-400 hover:text-red-600"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </div>
                                                <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Sem <?php echo $c['semester_number']; ?></span>
                                            </div>
                                        </div>
                                        <h4 class="text-xs font-black text-black uppercase leading-tight mb-2 truncate group-hover/item:underline decoration-2 underline-offset-2"><?php echo e($c['course_name']); ?></h4>
                                        <div class="flex items-center justify-between mt-2 pt-2 border-t border-dashed border-slate-200">
                                            <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest font-mono"><?php echo $c['credit_hours']; ?> Credits</span>
                                            <span class="text-[9px] font-black text-black uppercase tracking-widest"><?php echo e($c['course_type']); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Student Population -->
                <div class="lg:col-span-3">
                    <div class="os-card p-6 bg-white">
                        <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-black">
                            <h3 class="text-xs font-black text-black uppercase tracking-widest flex items-center gap-2">
                                <i class="fas fa-users"></i> Student Population (<?php echo count($dept_details['students']); ?>)
                            </h3>
                            <a href="users.php?action=create&role=student&dept_id=<?php echo $dept_id; ?>" class="w-6 h-6 border border-black flex items-center justify-center text-black hover:bg-black hover:text-white transition-all" title="Enroll Student Member">
                                <i class="fas fa-plus text-[10px]"></i>
                            </a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-black text-white">
                                        <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest">Identity</th>
                                        <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest">Student ID</th>
                                        <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest">Current Phase</th>
                                        <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest">Status</th>
                                        <th class="px-4 py-3 text-[9px] font-black uppercase tracking-widest text-right">Operations</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y-2 divide-slate-100">
                                    <?php if (empty($dept_details['students'])): ?>
                                        <tr><td colspan="5" class="px-4 py-6 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest italic">No students registered in this sector.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($dept_details['students'] as $s): ?>
                                            <tr class="hover:bg-yellow-50 transition-all group border-l-2 border-r-2 border-transparent hover:border-black">
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-8 h-8 bg-white border-2 border-black flex items-center justify-center text-[9px] font-black text-black shadow-[2px_2px_0px_rgba(0,0,0,1)]">
                                                            <?php echo strtoupper(substr($s['first_name'], 0, 1) . substr($s['last_name'], 0, 1)); ?>
                                                        </div>
                                                        <div class="flex flex-col">
                                                            <span class="text-xs font-black text-black uppercase leading-none mb-0.5"><?php echo e($s['first_name'] . ' ' . $s['last_name']); ?></span>
                                                            <span class="text-[9px] font-bold text-slate-400 lowercase font-mono"><?php echo e($s['email']); ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-[10px] font-bold font-mono text-black uppercase"><?php echo e($s['student_id']); ?></td>
                                                <td class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest">Semester <?php echo $s['current_semester']; ?></td>
                                                <td class="px-4 py-3">
                                                    <span class="px-2 py-0.5 bg-green-500 text-white text-[8px] font-black uppercase border border-black">Live</span>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <div class="flex items-center justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <a href="users.php?action=edit&id=<?php echo $s['id']; ?>" class="text-[10px] text-slate-400 hover:text-black"><i class="fas fa-pen"></i></a>
                                                        <form method="POST" action="users.php" class="inline" onsubmit="return confirm('Purge student node?')">
                                                            <?php csrf_field(); ?>
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                                            <button type="submit" class="text-[10px] text-slate-400 hover:text-red-600"><i class="fas fa-trash"></i></button>
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
            </div>
        </div>
    <?php else: ?>
        <!-- Department Form -->
        <div class="max-w-4xl mx-auto">
            <div class="os-card p-0 overflow-hidden bg-white">
                <div class="bg-black p-6 text-white flex justify-between items-center border-b-2 border-black">
                    <div>
                        <h3 class="text-xl font-black italic uppercase tracking-widest text-white">Department <span class="text-green-500">Form</span></h3>
                        <p class="text-[10px] font-bold font-mono text-slate-400 uppercase mt-1">
                            System::<?php echo $action === 'edit' ? 'UPDATE' : 'INIT'; ?>_PROTOCOL
                        </p>
                    </div>
                    <i class="fas fa-compass-drafting text-2xl text-green-500"></i>
                </div>

                <form method="POST" class="p-8 space-y-8">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="<?php echo $action === 'edit' ? 'update' : 'create'; ?>">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo e($department['id']); ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Department Name</label>
                            <input type="text" name="name" value="<?php echo e($department['name'] ?? ''); ?>" placeholder="Computer Science Engineering" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" required>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Department Code</label>
                            <input type="text" name="code" value="<?php echo e($department['code'] ?? ''); ?>" placeholder="CSE" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-black text-sm text-black uppercase tracking-widest focus:outline-none focus:bg-yellow-50 transition-all placeholder-slate-400" required>
                        </div>

                        <div class="md:col-span-2 space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Department Description</label>
                            <textarea name="description" rows="4" placeholder="Brief description of the department's goals and focus..." class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-medium text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all placeholder-slate-400 font-mono"><?php echo e($department['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Operation State</label>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="status" value="active" <?php echo ($department['status'] ?? 'active') === 'active' ? 'checked' : ''; ?> class="peer sr-only">
                                    <div class="px-4 py-3 bg-white border-2 border-slate-200 peer-checked:border-black peer-checked:bg-black peer-checked:text-white text-[10px] font-black uppercase tracking-widest text-slate-400 text-center transition-all hover:border-black">Active</div>
                                </label>
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="status" value="inactive" <?php echo ($department['status'] ?? '') === 'inactive' ? 'checked' : ''; ?> class="peer sr-only">
                                    <div class="px-4 py-3 bg-white border-2 border-slate-200 peer-checked:border-red-600 peer-checked:bg-red-600 peer-checked:text-white text-[10px] font-black uppercase tracking-widest text-slate-400 text-center transition-all hover:border-red-600">Inactive</div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 border-t-2 border-black flex justify-end gap-6">
                        <button type="submit" class="btn-os bg-green-500 text-white border-black hover:bg-black hover:text-white hover:border-black">
                            <span class="flex items-center gap-2">
                                <i class="fas fa-save"></i>
                                Save Department
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
