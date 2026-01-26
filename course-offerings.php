<?php
/**
 * Department Course Offerings Management
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('admin');

$page_title = 'Course Offerings';
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
$offering_id = $_GET['id'] ?? null;

// Handle Form Submissions (ID 56)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request');
        redirect(BASE_URL . '/modules/admin/course-offerings.php');
    }

    $post_action = $_POST['action'] ?? '';

    if ($post_action === 'create' || $post_action === 'update') {
        $course_id = intval($_POST['course_id']);
        $semester_id = intval($_POST['semester_id']);
        $section = sanitize_input($_POST['section']);
        $max_students = intval($_POST['max_students']);
        $status = sanitize_input($_POST['status']);
        $id = isset($_POST['id']) ? intval($_POST['id']) : null;

        // Security: Ensure course belongs to admin's department
        $check = $db->query("SELECT id FROM courses WHERE id = $course_id AND department_id IN ($dept_id_list)");
        if ($check->num_rows === 0) {
            set_flash('error', 'Permission denied for this course');
            redirect(BASE_URL . '/modules/admin/course-offerings.php');
        }

        $errors = validate_required(['course_id', 'semester_id', 'section', 'max_students'], $_POST);

        if (empty($errors)) {
            if ($post_action === 'create') {
                $stmt = $db->prepare("INSERT INTO course_offerings (course_id, semester_id, section, max_students, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iisis", $course_id, $semester_id, $section, $max_students, $status);
                if ($stmt->execute()) {
                    create_audit_log('create_offering', 'course_offerings', $stmt->insert_id);
                    set_flash('success', 'Course deployed successfully');
                } else {
                    set_flash('error', 'Deployment failed: ' . $db->error);
                }
            } else {
                $stmt = $db->prepare("UPDATE course_offerings SET course_id = ?, semester_id = ?, section = ?, max_students = ?, status = ? WHERE id = ?");
                $stmt->bind_param("iisisi", $course_id, $semester_id, $section, $max_students, $status, $id);
                if ($stmt->execute()) {
                    create_audit_log('update_offering', 'course_offerings', $id);
                    set_flash('success', 'Offering updated successfully');
                } else {
                    set_flash('error', 'Update failed');
                }
            }
            redirect(BASE_URL . '/modules/admin/course-offerings.php');
        } else {
            set_flash('error', implode('<br>', $errors));
        }
    } elseif ($post_action === 'delete') {
        $id = intval($_POST['id']);
        // Security check
        $check = $db->query("SELECT co.id FROM course_offerings co JOIN courses c ON co.course_id = c.id WHERE co.id = $id AND c.department_id IN ($dept_id_list)");
        if ($check->num_rows > 0) {
            $db->query("DELETE FROM course_offerings WHERE id = $id"); // Or soft delete if exists
            create_audit_log('delete_offering', 'course_offerings', $id);
            set_flash('success', 'Offering decommissioned');
        } else {
            set_flash('error', 'Permission denied');
        }
        redirect(BASE_URL . '/modules/admin/course-offerings.php');
    }
}

// PART 1: List View Implementation (ID 55)
$offerings = [];
if ($action === 'list') {
    $search = isset($_GET['search']) ? $db->real_escape_string($_GET['search']) : '';
    $query = "SELECT co.*, c.course_code, c.course_name, s.name as semester_name, sy.year as academic_year 
              FROM course_offerings co 
              JOIN courses c ON co.course_id = c.id 
              JOIN semesters s ON co.semester_id = s.id 
              JOIN academic_years sy ON s.academic_year_id = sy.id
              WHERE c.department_id IN ($dept_id_list)";
    
    if (!empty($search)) {
        $query .= " AND (c.course_code LIKE '%$search%' OR c.course_name LIKE '%$search%' OR co.section LIKE '%$search%')";
    }
    
    $query .= " ORDER BY sy.year DESC, s.semester_number DESC, c.course_code ASC";
    
    $res = $db->query($query);
    while ($row = $res->fetch_assoc()) { $offerings[] = $row; }
}

// Fetch Data for Edit
$view_data = null;
if (($action === 'edit') && $offering_id) {
    $stmt = $db->prepare("SELECT co.*, c.course_name FROM course_offerings co JOIN courses c ON co.course_id = c.id WHERE co.id = ? AND c.department_id IN ($dept_id_list)");
    $stmt->bind_param("i", $offering_id);
    $stmt->execute();
    $view_data = $stmt->get_result()->fetch_assoc();
}

// Get Courses for Dropdown
$course_list = [];
$res = $db->query("SELECT id, course_code, course_name FROM courses WHERE department_id IN ($dept_id_list) AND status = 'active' ORDER BY course_code");
while ($row = $res->fetch_assoc()) { $course_list[] = $row; }

// Get Semesters for Dropdown
$semester_list = [];
$res = $db->query("SELECT s.*, ay.year FROM semesters s JOIN academic_years ay ON s.academic_year_id = ay.id WHERE s.status != 'completed' ORDER BY ay.year DESC, s.semester_number DESC");
while ($row = $res->fetch_assoc()) { $semester_list[] = $row; }

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
            <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Deployment</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                Course Offerings
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">Active <span class="text-black">Offerings</span></h1>
    </div>
    
    <div class="relative z-10 flex flex-col md:flex-row gap-4 items-center">
        <?php if ($action === 'list'): ?>
            <form action="" method="GET" class="flex">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="SEARCH OFFERINGS..." class="bg-white text-black px-4 py-2 font-bold uppercase text-xs border-2 border-transparent focus:border-black outline-none w-48 md:w-64">
                <button type="submit" class="bg-black px-4 py-2 text-white font-black uppercase text-xs hover:bg-yellow-400 hover:text-black transition-colors">
                    <i class="fas fa-search"></i>
                </button>
            </form>
            <a href="?action=create" class="btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black hover:border-black flex items-center gap-2">
                <i class="fas fa-plus"></i> Deploy Course
            </a>
        <?php else: ?>
            <a href="?" class="btn-os bg-white text-black border-black hover:bg-black hover:text-white hover:border-black flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back to Fleet
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($action === 'create' || $action === 'edit'): ?>
    <div class="os-card p-0 bg-white max-w-5xl mx-auto">
        <div class="bg-black p-6 text-white border-b-2 border-black flex items-center gap-4">
            <div class="w-12 h-12 bg-white text-black flex items-center justify-center border-2 border-white">
                <i class="fas <?php echo $action === 'edit' ? 'fa-edit' : 'fa-plus'; ?> text-xl"></i>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Deployment Configuration</p>
                <h3 class="text-2xl font-black uppercase tracking-tighter leading-none"><?php echo $action === 'edit' ? 'Modify' : 'Initialize'; ?> Offering</h3>
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
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Target Course</label>
                    <div class="relative">
                        <select name="course_id" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none cursor-pointer" required>
                            <option value="">-- SELECT ASSET --</option>
                            <?php foreach ($course_list as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo ($view_data['course_id'] ?? '') == $c['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($c['course_code']); ?> - <?php echo e($c['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                            <i class="fas fa-chevron-down text-black text-xs"></i>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Academic Period (Semester)</label>
                    <div class="relative">
                        <select name="semester_id" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none cursor-pointer" required>
                            <option value="">-- SELECT PERIOD --</option>
                            <?php foreach ($semester_list as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo ($view_data['semester_id'] ?? '') == $s['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($s['year']); ?> - <?php echo e($s['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                            <i class="fas fa-chevron-down text-black text-xs"></i>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Section Designation</label>
                    <input type="text" name="section" value="<?php echo e($view_data['section'] ?? 'A'); ?>" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" placeholder="e.g. A, B, C" required>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Maximum Capacity</label>
                    <input type="number" name="max_students" value="<?php echo e($view_data['max_students'] ?? '60'); ?>" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" required>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Deployment Status</label>
                    <div class="flex gap-4">
                        <?php foreach (['open' => 'peer-checked:bg-green-600', 'closed' => 'peer-checked:bg-red-600', 'completed' => 'peer-checked:bg-blue-600'] as $val => $color): ?>
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="status" value="<?php echo $val; ?>" <?php echo ($view_data['status'] ?? 'open') === $val ? 'checked' : ''; ?> class="hidden peer">
                                <div class="<?php echo $color; ?> peer-checked:text-white bg-white text-slate-400 border-2 border-slate-200 peer-checked:border-black px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest transition-all hover:border-black">
                                    <?php echo strtoupper($val); ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="flex gap-4 pt-6 border-t-2 border-black border-dashed">
                <a href="?" class="flex-1 btn-os bg-white text-black border-black hover:bg-black hover:text-white hover:border-black text-center">Abort Mission</a>
                <button type="submit" class="flex-1 btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black hover:border-black">
                    <i class="fas fa-rocket mr-2"></i> <?php echo $action === 'edit' ? 'Update' : 'Deploy'; ?> Asset
                </button>
            </div>
        </form>
    </div>
<?php elseif ($action === 'list'): ?>
    <div class="os-card p-0 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-black text-white text-[10px] font-black uppercase tracking-widest border-b-2 border-black">
                        <th class="px-6 py-4 text-left">Course Specification</th>
                        <th class="px-6 py-4 text-left">Semester / Period</th>
                        <th class="px-6 py-4 text-left">Capacity</th>
                        <th class="px-6 py-4 text-left">Status</th>
                        <th class="px-6 py-4 text-right">Operations</th>
                    </tr>
                </thead>
                <tbody class="divide-y-2 divide-black">
                    <?php if (!empty($offerings)): foreach ($offerings as $o): ?>
                        <tr class="hover:bg-yellow-50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="text-[11px] font-black text-black uppercase transition-colors group-hover:text-blue-600"><?php echo e($o['course_code']); ?> - SEC <?php echo e($o['section']); ?></div>
                                <div class="text-[9px] font-bold text-slate-500 uppercase italic leading-tight"><?php echo e($o['course_name']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-[10px] font-black text-black uppercase"><?php echo e($o['semester_name']); ?></div>
                                <div class="text-[9px] font-bold text-slate-500 uppercase italic"><?php echo e($o['academic_year']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-[10px] font-black text-black uppercase italic"><?php echo e($o['enrolled_students']); ?> / <?php echo e($o['max_students']); ?> Units</div>
                                <div class="w-24 h-1.5 bg-slate-100 border border-black mt-1">
                                    <div class="h-full bg-black" style="width: <?php echo min(100, ($o['enrolled_students']/$o['max_students'])*100); ?>%"></div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 border-2 border-black text-[9px] font-black uppercase tracking-widest <?php 
                                    echo $o['status'] === 'open' ? 'bg-green-100 text-green-800' : 
                                        ($o['status'] === 'closed' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'); 
                                ?>">
                                    <?php echo strtoupper($o['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="?action=edit&id=<?php echo $o['id']; ?>" class="w-8 h-8 flex items-center justify-center border-2 border-black bg-white hover:bg-black hover:text-white transition-all shadow-[2px_2px_0px_#000000]" title="Modify Offering">
                                        <i class="fas fa-edit text-xs"></i>
                                    </a>
                                    <form method="POST" onsubmit="return confirm('Decommission this offering?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
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
                                <i class="fas fa-layer-group text-4xl text-slate-300 mb-2 block"></i>
                                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">No deployments detected</div>
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
