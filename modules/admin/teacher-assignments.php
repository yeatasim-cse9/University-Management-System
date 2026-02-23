<?php
/**
 * Teacher Assignments Management
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('admin');

$page_title = 'Teacher Assignments';
$user_id = get_current_user_id();

// Get admin's department(s)
$stmt = $db->prepare("SELECT d.id FROM departments d JOIN department_admins da ON d.id = da.department_id WHERE da.user_id = ? AND d.deleted_at IS NULL");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$department_ids = [];
while ($row = $result->fetch_assoc()) {
    $department_ids[] = $row['id'];
}
$dept_id_list = !empty($department_ids) ? implode(',', $department_ids) : '0';

$action = $_GET['action'] ?? 'list';
$assignment_id = $_GET['id'] ?? null;

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request');
        redirect(BASE_URL . '/modules/admin/teacher-assignments.php');
    }

    $post_action = $_POST['action'] ?? '';

    if ($post_action === 'assign') {
        $teacher_id = intval($_POST['teacher_id']);
        $offering_id = intval($_POST['course_offering_id']);

        // Verify teacher and offering belong to admin's dept
        $check_teacher = $db->query("SELECT id FROM teachers WHERE id = $teacher_id AND department_id IN ($dept_id_list)");
        $check_offering = $db->query("SELECT co.id FROM course_offerings co JOIN courses c ON co.course_id = c.id WHERE co.id = $offering_id AND c.department_id IN ($dept_id_list)");

        if ($check_teacher->num_rows > 0 && $check_offering->num_rows > 0) {
            // Resolution: Delete any existing assignments for this offering allows strict 1:1 or replacement
            // This handles the user's request: "Delete the previously assigned course teacher and assign a new teacher"
            $db->query("DELETE FROM teacher_courses WHERE course_offering_id = $offering_id");

            $stmt = $db->prepare("INSERT INTO teacher_courses (teacher_id, course_offering_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $teacher_id, $offering_id);
            if ($stmt->execute()) {
                create_audit_log('assign_teacher', 'teacher_courses', $stmt->insert_id, null, ['teacher_id' => $teacher_id, 'offering_id' => $offering_id]);
                set_flash('success', 'Teacher assigned successfully (Previous assignment replaced)');
            } else {
                set_flash('error', 'Failed to assign teacher');
            }
        } else {
            set_flash('error', 'Invalid selection or permission denied');
        }
        redirect(BASE_URL . '/modules/admin/teacher-assignments.php');
    } elseif ($post_action === 'delete') {
        $id = intval($_POST['id']);
        
        $check_perm = $db->query("SELECT tc.id FROM teacher_courses tc 
            JOIN course_offerings co ON tc.course_offering_id = co.id 
            JOIN courses c ON co.course_id = c.id 
            WHERE tc.id = $id AND c.department_id IN ($dept_id_list)");
        
        if ($check_perm->num_rows > 0) {
            $db->query("DELETE FROM teacher_courses WHERE id = $id");
            create_audit_log('delete_teacher_assignment', 'teacher_courses', $id);
            set_flash('success', 'Assignment removed successfully');
        } else {
            set_flash('error', 'Permission denied');
        }
        redirect(BASE_URL . '/modules/admin/teacher-assignments.php');
    }
}

// Fetch Teachers
$teachers = [];
$teachers_res = $db->query("SELECT t.id, t.employee_id, up.first_name, up.last_name 
    FROM teachers t 
    JOIN user_profiles up ON t.user_id = up.user_id 
    WHERE t.department_id IN ($dept_id_list) 
    ORDER BY up.first_name ASC");
while ($row = $teachers_res->fetch_assoc()) {
    $teachers[] = $row;
}

// Fetch Active Offerings
$offerings = [];
$offerings_res = $db->query("SELECT co.id, c.course_code, c.course_name, co.section, s.name as semester_name 
    FROM course_offerings co 
    JOIN courses c ON co.course_id = c.id 
    JOIN semesters s ON co.semester_id = s.id 
    WHERE c.department_id IN ($dept_id_list) AND co.status = 'open' 
    ORDER BY c.course_code ASC, co.section ASC");
while ($row = $offerings_res->fetch_assoc()) {
    $offerings[] = $row;
}

// Fetch View Data
$view_data = null;
if ($action === 'view' && $assignment_id) {
    $stmt = $db->prepare("SELECT tc.*, up.first_name, up.last_name, t.employee_id, t.designation, c.course_code, c.course_name, co.section, s.name as semester_name, ay.year as academic_year
                         FROM teacher_courses tc
                         JOIN teachers t ON tc.teacher_id = t.id
                         JOIN user_profiles up ON t.user_id = up.user_id
                         JOIN course_offerings co ON tc.course_offering_id = co.id
                         JOIN courses c ON co.course_id = c.id
                         JOIN semesters s ON co.semester_id = s.id
                         JOIN academic_years ay ON s.academic_year_id = ay.id
                         WHERE tc.id = ? AND t.department_id IN ($dept_id_list)");
    $stmt->bind_param("i", $assignment_id);
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
            <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Staffing</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                Personnel Allocation
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">Teacher <span class="text-black">Assignments</span></h1>
    </div>
</div>

<?php if ($action === 'view' && $view_data): ?>
    <div class="os-card p-0 bg-white overflow-hidden">
        <div class="bg-black p-8 text-white relative border-b-2 border-black">
            <div class="flex flex-col md:flex-row items-center gap-8">
                <div class="w-32 h-32 bg-white text-black rounded-none border-2 border-white flex items-center justify-center relative shadow-[4px_4px_0px_#fff]">
                    <i class="fas fa-link text-5xl"></i>
                </div>
                <div class="text-center md:text-left">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Assignment Protocol</p>
                    <h2 class="text-4xl md:text-5xl font-black uppercase tracking-tighter leading-none mb-3"><?php echo e($view_data['first_name'] . ' ' . $view_data['last_name']); ?></h2>
                    <div class="flex flex-wrap justify-center md:justify-start gap-2">
                        <span class="px-2 py-1 bg-white text-black text-[9px] font-black uppercase tracking-widest border border-black">EMP: <?php echo e($view_data['employee_id']); ?></span>
                        <span class="px-2 py-1 bg-black border border-white text-white text-[9px] font-black uppercase tracking-widest"><?php echo e($view_data['designation']); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8 border-b-2 border-black">
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3 italic">Designated Offering</p>
                <div class="bg-slate-50 rounded-none p-6 border-2 border-black space-y-3 shadow-[4px_4px_0px_#000]">
                    <div class="text-3xl font-black text-black tracking-tighter uppercase"><?php echo e($view_data['course_code']); ?></div>
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-widest"><?php echo e($view_data['course_name']); ?></div>
                    <div class="flex gap-2 pt-2 border-t-2 border-black border-dashed">
                        <div class="bg-white px-2 py-1 border border-black text-[10px] font-black text-black uppercase">SEC: <?php echo e($view_data['section']); ?></div>
                        <div class="bg-white px-2 py-1 border border-black text-[10px] font-black text-black uppercase"><?php echo e($view_data['semester_name']); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center justify-center">
                <div class="text-center">
                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-4 italic">Assignment Sequence</div>
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-white border-2 border-black flex items-center justify-center text-black shadow-[2px_2px_0px_#000]">
                            <i class="fas fa-user-tie text-2xl"></i>
                        </div>
                        <div class="flex-1 h-0.5 bg-black border-t-2 border-black border-dashed relative w-16">
                            <i class="fas fa-arrow-right text-[10px] text-black absolute right-0 -top-[7px]"></i>
                        </div>
                        <div class="w-16 h-16 bg-black flex items-center justify-center text-white border-2 border-black shadow-[2px_2px_0px_#000]">
                            <i class="fas fa-graduation-cap text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="p-8 flex justify-between items-center bg-slate-50">
            <a href="?" class="btn-os bg-white text-black border-black hover:bg-black hover:text-white flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Terminate View
            </a>
            <form method="POST" onsubmit="return confirm('Revoke this assignment protocol?');">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $view_data['id']; ?>">
                <button type="submit" class="btn-os bg-white text-black border-black hover:bg-red-600 hover:text-white flex items-center gap-2">
                    <i class="fas fa-unlink"></i> Revoke Assignment
                </button>
            </form>
        </div>
    </div>

<?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Assignment Form -->
        <div class="lg:col-span-1">
            <div class="os-card p-0 bg-white sticky top-8">
                <div class="bg-black p-6 text-white border-b-2 border-black flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-600 text-white flex items-center justify-center border-2 border-white">
                        <i class="fas fa-plus text-xl"></i>
                    </div>
                    <div>
                        <p class="text-[9px] font-black text-green-400 uppercase tracking-widest mb-1">Allocation Engine</p>
                        <h3 class="text-xl font-black uppercase tracking-tighter">New Assignment</h3>
                    </div>
                </div>
                
                <div class="p-6">
                    <form method="POST" class="space-y-6">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="assign">
                        
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Select Personnel</label>
                            <div class="relative">
                                <select name="teacher_id" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none cursor-pointer" required>
                                    <option value="">Choose Faculty</option>
                                    <?php foreach ($teachers as $t): ?>
                                        <option value="<?php echo $t['id']; ?>"><?php echo e($t['first_name'] . ' ' . $t['last_name'] . ' (' . $t['employee_id'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                    <i class="fas fa-chevron-down text-black text-xs"></i>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Designated Offering</label>
                             <div class="relative">
                                <select name="course_offering_id" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none cursor-pointer" required>
                                    <option value="">Select Offering</option>
                                    <?php foreach ($offerings as $o): ?>
                                        <option value="<?php echo $o['id']; ?>"><?php echo e($o['course_code'] . ' - ' . $o['section'] . ' (' . $o['semester_name'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                    <i class="fas fa-chevron-down text-black text-xs"></i>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="w-full btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black hover:border-black flex items-center justify-center gap-2">
                            <i class="fas fa-link"></i> Authorize Assignment
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Assignments List -->
        <div class="lg:col-span-2">
            <div class="os-card p-0 bg-white">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-black text-white text-[10px] font-black uppercase tracking-widest border-b-2 border-black">
                                <th class="px-6 py-4 text-left">Academic Asset</th>
                                <th class="px-6 py-4 text-left">Personnel Designated</th>
                                <th class="px-6 py-4 text-right">Operations</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y-2 divide-black">
                            <?php 
                            $list_query = "SELECT tc.id, c.course_code, c.course_name, co.section, up.first_name, up.last_name, t.employee_id 
                                           FROM teacher_courses tc 
                                           JOIN teachers t ON tc.teacher_id = t.id 
                                           JOIN user_profiles up ON t.user_id = up.user_id 
                                           JOIN course_offerings co ON tc.course_offering_id = co.id 
                                           JOIN courses c ON co.course_id = c.id 
                                           WHERE t.department_id IN ($dept_id_list) 
                                           ORDER BY c.course_code ASC, co.section ASC";
                            $list_res = $db->query($list_query);
                            
                            if ($list_res->num_rows > 0):
                                while ($row = $list_res->fetch_assoc()):
                            ?>
                                <tr class="hover:bg-yellow-50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="text-[11px] font-black text-black uppercase transition-colors"><?php echo e($row['course_code']); ?> - <?php echo e($row['section']); ?></div>
                                        <div class="text-[9px] font-bold text-slate-500 uppercase italic"><?php echo e($row['course_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-[10px] font-black text-black uppercase italic"><?php echo e($row['first_name'] . ' ' . $row['last_name']); ?></div>
                                        <div class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">EMP: <?php echo e($row['employee_id']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="?action=view&id=<?php echo $row['id']; ?>" class="w-8 h-8 flex items-center justify-center border-2 border-black bg-white hover:bg-black hover:text-white transition-all shadow-[2px_2px_0px_#000000]" title="View Assignment">
                                                <i class="fas fa-eye text-xs"></i>
                                            </a>
                                            <form method="POST" class="inline" onsubmit="return confirm('Revoke this assignment protocol?');">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" class="w-8 h-8 flex items-center justify-center border-2 border-black bg-white hover:bg-red-600 hover:text-white transition-all shadow-[2px_2px_0px_#000000]" title="Revoke">
                                                    <i class="fas fa-unlink text-xs"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-12 text-center">
                                        <i class="fas fa-link-slash text-4xl text-slate-300 mb-2 block"></i>
                                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">No active personnel allocations detected</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
