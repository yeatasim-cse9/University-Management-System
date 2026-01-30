<?php
/**
 * Course Offerings Management
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
$offering_id = $_GET['id'] ?? null;

// Handle Form Submissions
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
        $current_offering_id = isset($_POST['id']) ? intval($_POST['id']) : null;

        $errors = validate_required(['course_id', 'semester_id', 'section', 'max_students', 'status'], $_POST);

        // Verify course belongs to admin's department
        $check_course = $db->query("SELECT id FROM courses WHERE id = $course_id AND department_id IN ($dept_id_list)");
        if ($check_course->num_rows === 0) {
            $errors[] = "Invalid course selection.";
        }

        // Check for duplicate section
        $check_dup_sql = "SELECT id FROM course_offerings WHERE course_id = ? AND semester_id = ? AND section = ?";
        if ($post_action === 'update') {
            $check_dup_sql .= " AND id != ?";
        }
        $dup_stmt = $db->prepare($check_dup_sql);
        if ($post_action === 'update') {
            $dup_stmt->bind_param("iisi", $course_id, $semester_id, $section, $current_offering_id);
        } else {
            $dup_stmt->bind_param("iis", $course_id, $semester_id, $section);
        }
        $dup_stmt->execute();
        if ($dup_stmt->get_result()->num_rows > 0) {
            $errors[] = "A course offering with this section already exists for the selected semester.";
        }

        if (empty($errors)) {
            if ($post_action === 'create') {
                $stmt = $db->prepare("INSERT INTO course_offerings (course_id, semester_id, section, max_students, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iisss", $course_id, $semester_id, $section, $max_students, $status);
                if ($stmt->execute()) {
                    create_audit_log('create_course_offering', 'course_offerings', $stmt->insert_id, null, ['course_id' => $course_id, 'section' => $section]);
                    set_flash('success', 'Course offering created successfully');
                } else {
                    set_flash('error', 'Failed to create course offering');
                }
            } else {
                $stmt = $db->prepare("UPDATE course_offerings SET course_id = ?, semester_id = ?, section = ?, max_students = ?, status = ? WHERE id = ?");
                $stmt->bind_param("iisssi", $course_id, $semester_id, $section, $max_students, $status, $current_offering_id);
                if ($stmt->execute()) {
                    create_audit_log('update_course_offering', 'course_offerings', $current_offering_id);
                    set_flash('success', 'Course offering updated successfully');
                } else {
                    set_flash('error', 'Failed to update course offering');
                }
            }
            redirect(BASE_URL . '/modules/admin/course-offerings.php');
        } else {
            set_flash('error', implode('<br>', $errors));
        }
    } elseif ($post_action === 'delete') {
        $id = intval($_POST['id']);
        
        // Ensure offering belongs to admin's dept
        $check = $db->query("SELECT co.id FROM course_offerings co JOIN courses c ON co.course_id = c.id WHERE co.id = $id AND c.department_id IN ($dept_id_list)");
        if ($check->num_rows > 0) {
            $db->query("DELETE FROM course_offerings WHERE id = $id");
            create_audit_log('delete_course_offering', 'course_offerings', $id);
            set_flash('success', 'Course offering deleted successfully');
        } else {
            set_flash('error', 'Permission denied');
        }
        redirect(BASE_URL . '/modules/admin/course-offerings.php');
    }
}

// Fetch Edit/View Data
$view_data = null;
if (($action === 'edit' || $action === 'view') && $offering_id) {
    $stmt = $db->prepare("SELECT co.*, c.course_code, c.course_name, s.name as semester_name, ay.year as academic_year_name,
                         (SELECT COUNT(*) FROM enrollments WHERE course_offering_id = co.id) as enrolled_students
                         FROM course_offerings co 
                         JOIN courses c ON co.course_id = c.id 
                         JOIN semesters s ON co.semester_id = s.id 
                         JOIN academic_years ay ON s.academic_year_id = ay.id
                         WHERE co.id = ? AND c.department_id IN ($dept_id_list)");
    $stmt->bind_param("i", $offering_id);
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
            <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Operations</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                Active Deployments
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">Course <span class="text-black">Offerings</span></h1>
    </div>
    
    <div class="relative z-10 flex flex-col md:flex-row gap-4 items-center">
        <?php if ($action === 'list'): ?>
            <form action="" method="GET" class="flex">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="SEARCH COURSES..." class="bg-white text-black px-4 py-2 font-bold uppercase text-xs border-2 border-transparent focus:border-yellow-400 outline-none w-48 md:w-64">
                <button type="submit" class="bg-yellow-400 px-4 py-2 text-black font-black uppercase text-xs hover:bg-white transition-colors">
                    <i class="fas fa-search"></i>
                </button>
            </form>
            <a href="?action=create" class="btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black hover:border-black flex items-center gap-2">
                <i class="fas fa-plus"></i> Deploy Offering
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
                    <i class="fas fa-broadcast-tower text-5xl"></i>
                    <div class="absolute -top-3 -right-3 px-2 py-1 <?php echo $view_data['status'] === 'open' ? 'bg-green-600' : 'bg-red-600'; ?> text-white text-[8px] font-black uppercase tracking-widest border border-black shadow-[2px_2px_0px_#000]">
                        <?php echo strtoupper($view_data['status']); ?>
                    </div>
                </div>
                <div class="text-center md:text-left">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Deployment Manifest</p>
                    <h2 class="text-4xl md:text-5xl font-black uppercase tracking-tighter leading-none mb-3"><?php echo e($view_data['course_code']); ?> - Section <?php echo e($view_data['section']); ?></h2>
                    <div class="flex flex-wrap justify-center md:justify-start gap-2">
                        <span class="px-2 py-1 bg-white text-black text-[9px] font-black uppercase tracking-widest border border-black"><?php echo e($view_data['course_name']); ?></span>
                        <span class="px-2 py-1 bg-black border border-white text-white text-[9px] font-black uppercase tracking-widest"><?php echo e($view_data['semester_name']); ?> (<?php echo e($view_data['academic_year_name']); ?>)</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="p-8 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="space-y-6">
                <div>
                    <h4 class="text-xl font-black uppercase mb-4 border-b-2 border-black inline-block">Capacity Telemetry</h4>
                    <div class="bg-slate-50 border-2 border-black p-6 space-y-4 text-center">
                        <div class="text-5xl font-black text-black tracking-tighter"><?php echo $view_data['enrolled_students']; ?><span class="text-2xl text-slate-400">/<?php echo $view_data['max_students']; ?></span></div>
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest italic">Enrolled Assets</p>
                        <div class="w-full h-4 bg-white border border-black p-0.5">
                            <div class="h-full bg-black" style="width: <?php echo ($view_data['enrolled_students'] / ($view_data['max_students'] ?: 1)) * 100; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2 space-y-6">
                <div>
                    <h4 class="text-xl font-black uppercase mb-4 border-b-2 border-black inline-block">Operational Status</h4>
                    <div class="bg-white border-2 border-black p-6 font-mono text-sm leading-relaxed">
                        This course offering is currently <span class="font-black text-black uppercase"><?php echo $view_data['status']; ?></span>. 
                        Enrollment is being monitored for sector <?php echo e($view_data['section']); ?>.
                    </div>
                </div>

                <div class="flex flex-wrap gap-4 pt-4 border-t-2 border-black border-dashed">
                    <a href="?action=edit&id=<?php echo $view_data['id']; ?>" class="btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black flex items-center gap-2">
                        <i class="fas fa-edit"></i> Modify Deployment
                    </a>
                    <button onclick="window.print()" class="btn-os bg-white text-black border-black hover:bg-black hover:text-white flex items-center gap-2">
                        <i class="fas fa-print"></i> Print Log
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
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Fleet Configuration</p>
                <h3 class="text-2xl font-black uppercase tracking-tighter leading-none"><?php echo $action === 'edit' ? 'Synchronize' : 'Initialize'; ?> Offering</h3>
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
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Academic Asset (Course)</label>
                    <div class="relative">
                        <select name="course_id" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none cursor-pointer" required>
                            <option value="">Select Asset</option>
                            <?php 
                            $courses = $db->query("SELECT id, course_code, course_name FROM courses WHERE department_id IN ($dept_id_list) AND status = 'active' ORDER BY course_code");
                            while($c = $courses->fetch_assoc()): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo ($view_data['course_id'] ?? '') == $c['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($c['course_code'] . ' - ' . $c['course_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                            <i class="fas fa-chevron-down text-black text-xs"></i>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Target Semester</label>
                    <div class="relative">
                        <select name="semester_id" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none cursor-pointer" required>
                            <option value="">Select Window</option>
                            <?php 
                            $semesters = $db->query("SELECT s.id, s.name, ay.year FROM semesters s JOIN academic_years ay ON s.academic_year_id = ay.id WHERE s.status = 'active' ORDER BY s.start_date DESC");
                            while($s = $semesters->fetch_assoc()): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo ($view_data['semester_id'] ?? '') == $s['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($s['name'] . ' (' . $s['year'] . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                         <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                            <i class="fas fa-chevron-down text-black text-xs"></i>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Sector Designation (Section)</label>
                    <input type="text" name="section" value="<?php echo e($view_data['section'] ?? ''); ?>" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" placeholder="e.g. A, B1" required>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Max Payload (Students)</label>
                    <input type="number" name="max_students" value="<?php echo e($view_data['max_students'] ?? '40'); ?>" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" required>
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Operational Status</label>
                    <div class="flex gap-4">
                        <?php foreach (['open' => 'peer-checked:bg-green-600', 'closed' => 'peer-checked:bg-red-600'] as $val => $color): ?>
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
                    <i class="fas fa-rocket mr-2"></i> <?php echo $action === 'edit' ? 'Synchronize' : 'Authorize'; ?> Deployment
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
                        <th class="px-6 py-4 text-left">Academic Asset</th>
                        <th class="px-6 py-4 text-left">Operational Window</th>
                        <th class="px-6 py-4 text-left">Sector</th>
                        <th class="px-6 py-4 text-left">Payload Status</th>
                        <th class="px-6 py-4 text-left">Fleet Status</th>
                        <th class="px-6 py-4 text-right">Operations</th>
                    </tr>
                </thead>
                <tbody class="divide-y-2 divide-black">
                    <?php 
                    $search = sanitize_input($_GET['search'] ?? '');
                    $where = "c.department_id IN ($dept_id_list)";
                    if ($search) {
                         $where .= " AND (c.course_code LIKE '%$search%' OR c.course_name LIKE '%$search%')";
                    }
                    
                    $list_query = "SELECT co.*, c.course_code, c.course_name, s.name as semester_name, ay.year,
                                   (SELECT COUNT(*) FROM enrollments WHERE course_offering_id = co.id) as enrolled_students 
                                   FROM course_offerings co 
                                   JOIN courses c ON co.course_id = c.id 
                                   JOIN semesters s ON co.semester_id = s.id 
                                   JOIN academic_years ay ON s.academic_year_id = ay.id
                                   WHERE $where
                                   ORDER BY s.start_date DESC, c.course_code ASC, co.section ASC";
                    $list_res = $db->query($list_query);
                    
                    if ($list_res->num_rows > 0):
                        while ($row = $list_res->fetch_assoc()):
                    ?>
                        <tr class="hover:bg-yellow-50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="text-[11px] font-black text-black uppercase transition-colors"><?php echo e($row['course_code']); ?></div>
                                <div class="text-[9px] font-bold text-slate-500 uppercase italic leading-tight"><?php echo e($row['course_name']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-[10px] font-black text-black uppercase italic"><?php echo e($row['semester_name']); ?></div>
                                <div class="text-[9px] font-bold text-slate-400 uppercase italic"><?php echo e($row['year']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-white border border-black text-black rounded-none text-[9px] font-black uppercase italic tracking-widest">SEC: <?php echo e($row['section']); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 h-3 bg-white border border-black w-24">
                                        <div class="h-full bg-black" style="width: <?php echo ($row['enrolled_students'] / ($row['max_students']?:1)) * 100; ?>%"></div>
                                    </div>
                                    <span class="text-[10px] font-black text-slate-900 uppercase italic"><?php echo $row['enrolled_students']; ?>/<?php echo $row['max_students']; ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 border-2 border-black text-[9px] font-black uppercase tracking-widest <?php 
                                    echo $row['status'] === 'open' ? 'bg-green-100 text-green-800' : 
                                        ($row['status'] === 'closed' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-800'); 
                                ?>">
                                    <?php echo strtoupper($row['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="?action=view&id=<?php echo $row['id']; ?>" class="w-8 h-8 flex items-center justify-center border-2 border-black bg-white hover:bg-black hover:text-white transition-all shadow-[2px_2px_0px_#000000]" title="View Deployment">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    <a href="?action=edit&id=<?php echo $row['id']; ?>" class="w-8 h-8 flex items-center justify-center border-2 border-black bg-white hover:bg-black hover:text-white transition-all shadow-[2px_2px_0px_#000000]" title="Modify Deployment">
                                        <i class="fas fa-edit text-xs"></i>
                                    </a>
                                    <form method="POST" class="inline" onsubmit="return confirm('Initiate deletion protocol?');">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="w-8 h-8 flex items-center justify-center border-2 border-black bg-white hover:bg-red-600 hover:text-white transition-all shadow-[2px_2px_0px_#000000]" title="Delete">
                                            <i class="fas fa-trash-alt text-xs"></i>
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
                            <td colspan="6" class="px-6 py-12 text-center">
                                <i class="fas fa-folder-open text-4xl text-slate-300 mb-2 block"></i>
                                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">No operational assets detected</div>
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
