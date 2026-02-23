<?php
/**
 * Marks Entry
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('teacher');

$page_title = 'Marks Entry';
$user_id = get_current_user_id();

// Get teacher info
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$teacher_id = $teacher['id'];

// Get My Courses for Dropdown
$courses = [];
$c_stmt = $db->query("SELECT co.id, c.course_code, c.course_name, co.section, c.department_id 
    FROM course_offerings co 
    JOIN courses c ON co.course_id = c.id 
    JOIN teacher_courses tc ON co.id = tc.course_offering_id 
    WHERE tc.teacher_id = $teacher_id AND co.status = 'open'");
while ($row = $c_stmt->fetch_assoc()) {
    $courses[] = $row;
}
$course_ids = array_column($courses, 'id');

$offering_id = $_GET['offering_id'] ?? ($_POST['offering_id'] ?? null);
$component_id = $_GET['component_id'] ?? ($_POST['component_id'] ?? null);

$components = [];
$students = [];
$existing_marks = [];
$msg = '';

// Handle Loading Components
if ($offering_id) {
    // Find department_id for this offering
    $dept_id = 0;
    foreach ($courses as $c) {
        if ($c['id'] == $offering_id) $dept_id = $c['department_id'];
    }
    
    // Get Assessment Components
    $comp_q = $db->query("SELECT * FROM assessment_components WHERE department_id = $dept_id ORDER BY id");
    while ($row = $comp_q->fetch_assoc()) {
        $components[] = $row;
    }
}

// Handle Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $msg = "Invalid request";
    } else {
        $offering_id = intval($_POST['offering_id']);
        $component_id = intval($_POST['component_id']);
        $total_marks = floatval($_POST['total_marks']);
        $submit_action = $_POST['submit_action'] ?? 'draft'; // draft or submitted
        
        // Verify ownership
        if (in_array($offering_id, $course_ids)) {
            $student_marks = $_POST['marks'] ?? [];
            $remarks = $_POST['remarks'] ?? [];
            
            $db->begin_transaction();
            try {
                $count = 0;
                foreach ($student_marks as $enrollment_id => $mark_val) {
                    $enrollment_id = intval($enrollment_id);
                    $remark = sanitize_input($remarks[$enrollment_id] ?? '');
                    
                    if ($mark_val === '' || $mark_val === null) continue; // Skip empty
                    
                    $mark_val = floatval($mark_val);
                    
                    // Check existing
                    $check = $db->query("SELECT id, status FROM student_marks WHERE enrollment_id = $enrollment_id AND assessment_component_id = $component_id");
                    
                    if ($check->num_rows > 0) {
                        $row = $check->fetch_assoc();
                        // Only update if not verified
                        if ($row['status'] !== 'verified') {
                            $stmt = $db->prepare("UPDATE student_marks SET marks_obtained = ?, total_marks = ?, remarks = ?, status = ?, entered_by = ? WHERE id = ?");
                            $stmt->bind_param("ddssii", $mark_val, $total_marks, $remark, $submit_action, $user_id, $row['id']);
                            $stmt->execute();
                        }
                    } else {
                        $stmt = $db->prepare("INSERT INTO student_marks (enrollment_id, assessment_component_id, marks_obtained, total_marks, remarks, status, entered_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("iidsssi", $enrollment_id, $component_id, $mark_val, $total_marks, $remark, $submit_action, $user_id);
                        $stmt->execute();
                    }
                    $count++;
                }
                $db->commit();
                create_audit_log('enter_marks', 'student_marks', null, null, ['offering' => $offering_id, 'status' => $submit_action]);
                set_flash('success', "Marks saved as " . ucfirst($submit_action));
                // Reload to reflect changes
                redirect(BASE_URL . "/modules/teacher/marks-entry.php?offering_id=$offering_id&component_id=$component_id");
            } catch (Exception $e) {
                $db->rollback();
                set_flash('error', "Error saving marks: " . $e->getMessage());
            }
        } else {
            set_flash('error', "Permission denied");
        }
    }
}

// Fetch Students and Existing Marks
if ($offering_id && $component_id) {
    // Verify permission
    if (!in_array($offering_id, $course_ids)) {
        set_flash('error', "Invalid course");
        $offering_id = null;
    } else {
        // Students
        $q = "SELECT e.id as enrollment_id, s.student_id, up.first_name, up.last_name 
            FROM enrollments e
            JOIN students s ON e.student_id = s.id
            JOIN users u ON s.user_id = u.id
            JOIN user_profiles up ON u.id = up.user_id
            WHERE e.course_offering_id = $offering_id AND e.status = 'enrolled'
            ORDER BY s.student_id";
        $res = $db->query($q);
        while ($row = $res->fetch_assoc()) $students[] = $row;
        
        // Existing Marks
        $mq = "SELECT enrollment_id, marks_obtained, total_marks, remarks, status 
            FROM student_marks 
            WHERE assessment_component_id = $component_id 
            AND enrollment_id IN (SELECT id FROM enrollments WHERE course_offering_id = $offering_id)";
        $mres = $db->query($mq);
        while ($row = $mres->fetch_assoc()) $existing_marks[$row['enrollment_id']] = $row;
    }
}

// Default Total Marks
$default_total = 100;
if (!empty($existing_marks)) {
    // Get from first record
    $first = reset($existing_marks);
    $default_total = $first['total_marks'];
}

// Sidebar
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-4xl font-black uppercase tracking-tighter border-b-4 border-black inline-block pb-2 mb-2">
                Marks Entry
            </h1>
            <p class="text-sm font-bold uppercase tracking-widest text-gray-600">
                Grading and assessment management
            </p>
        </div>
        
        <div class="flex items-center gap-3">
            <div class="os-icon-box bg-indigo-600 text-white">
                <i class="fas fa-calculator text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Selection Panel -->
    <div class="os-card p-6">
        <form method="GET" class="flex flex-col lg:flex-row items-end gap-6">
            <div class="flex-1 w-full">
                <label class="block text-xs font-black uppercase tracking-widest mb-2">Course</label>
                <div class="relative">
                    <select name="offering_id" class="w-full bg-white border-2 border-black p-4 text-sm font-bold uppercase focus:ring-0 focus:border-black focus:shadow-os transition-all appearance-none cursor-pointer" onchange="this.form.submit()">
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $offering_id == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo e($c['course_code'] . ' - ' . $c['course_name'] . ' (Sec ' . $c['section'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-black">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
            </div>
            
            <?php if ($offering_id): ?>
            <div class="flex-1 w-full animate-in slide-in-from-left-5 duration-500">
                <label class="block text-xs font-black uppercase tracking-widest mb-2">Assessment Component</label>
                <div class="relative">
                    <select name="component_id" class="w-full bg-white border-2 border-black p-4 text-sm font-bold uppercase focus:ring-0 focus:border-black focus:shadow-os transition-all appearance-none cursor-pointer" onchange="this.form.submit()">
                        <option value="">Select Component</option>
                        <?php foreach ($components as $comp): ?>
                            <option value="<?php echo $comp['id']; ?>" <?php echo $component_id == $comp['id'] ? 'selected' : ''; ?>>
                                <?php echo e($comp['component_name']); ?> (Weight: <?php echo $comp['weightage']; ?>%)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-black">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($offering_id): ?>
        <?php if (empty($components)): ?>
            <div class="bg-amber-100 border-l-4 border-black p-6 flex items-center gap-4">
                <div class="w-12 h-12 bg-amber-400 text-black flex items-center justify-center font-black text-xl border-2 border-black">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div>
                    <p class="text-sm font-black uppercase tracking-widest">No Components Found</p>
                    <p class="text-xs font-bold uppercase mt-1">No assessment components found for this department.</p>
                </div>
            </div>
        <?php elseif ($component_id && !empty($students)): ?>
            <form method="POST" action="" class="space-y-6">
                <?php csrf_field(); ?>
                <input type="hidden" name="offering_id" value="<?php echo $offering_id; ?>">
                <input type="hidden" name="component_id" value="<?php echo $component_id; ?>">
                <input type="hidden" name="save_marks" value="1">
                
                <div class="os-card overflow-hidden">
                    <div class="p-6 border-b-2 border-black bg-yellow-50 flex flex-wrap justify-between items-center gap-6">
                        <div>
                            <h3 class="text-lg font-black uppercase tracking-tighter">Student Marks</h3>
                            <p class="text-xs font-bold uppercase tracking-widest text-gray-500">Entry & Verification</p>
                        </div>
                        <div class="flex items-center gap-2 bg-white px-4 py-2 border-2 border-black shadow-os">
                            <label class="text-xs font-black uppercase tracking-widest">Total Marks:</label>
                            <input type="number" name="total_marks" value="<?php echo $default_total; ?>" class="w-16 bg-transparent text-sm font-black text-right focus:outline-none" required>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-black text-white border-b-2 border-black">
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-widest">Student</th>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-widest">Score</th>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-center">Status</th>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-widest">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-2 divide-black">
                                <?php foreach ($students as $student): 
                                    $eid = $student['enrollment_id'];
                                    $data = $existing_marks[$eid] ?? [];
                                    $status = $data['status'] ?? 'new';
                                    $read_only = ($status === 'verified' || $status === 'submitted');
                                    $is_rejected = ($status === 'correction_requested');
                                ?>
                                    <tr class="hover:bg-yellow-50/50 transition-colors group <?php echo $is_rejected ? 'bg-red-50' : ''; ?>">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 bg-black text-white flex items-center justify-center font-black text-xs border-2 border-black group-hover:bg-white group-hover:text-black transition-colors">
                                                    ST
                                                </div>
                                                <div>
                                                    <p class="text-sm font-black uppercase leading-tight"><?php echo e($student['first_name'] . ' ' . $student['last_name']); ?></p>
                                                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-0.5"><?php echo e($student['student_id']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <input type="number" step="0.5" name="marks[<?php echo $eid; ?>]" 
                                                value="<?php echo e($data['marks_obtained'] ?? ''); ?>"
                                                class="w-24 bg-white border-2 border-black p-2 text-sm font-black text-center focus:ring-0 focus:shadow-os transition-all <?php echo $is_rejected ? 'border-red-500 bg-red-50' : ''; ?>"
                                                <?php echo $read_only && !$is_rejected ? 'readonly disabled' : ''; ?>>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <?php if ($status === 'new'): ?>
                                                <span class="text-[10px] font-black uppercase tracking-widest bg-gray-200 px-2 py-1">Pending</span>
                                            <?php elseif ($status === 'draft'): ?>
                                                <span class="text-[10px] font-black uppercase tracking-widest bg-indigo-100 text-indigo-700 px-2 py-1 border border-indigo-200">Draft</span>
                                            <?php elseif ($status === 'submitted'): ?>
                                                <span class="text-[10px] font-black uppercase tracking-widest bg-amber-100 text-amber-700 px-2 py-1 border border-amber-200">Submitted</span>
                                            <?php elseif ($status === 'verified'): ?>
                                                <span class="text-[10px] font-black uppercase tracking-widest bg-emerald-100 text-emerald-700 px-2 py-1 border border-emerald-200">Verified</span>
                                            <?php elseif ($status === 'correction_requested'): ?>
                                                <span class="text-[10px] font-black uppercase tracking-widest bg-red-100 text-red-700 px-2 py-1 border border-red-200 cursor-help" title="<?php echo e($data['remarks']); ?>">Correction</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <input type="text" name="remarks[<?php echo $eid; ?>]" 
                                                value="<?php echo e($data['remarks'] ?? ''); ?>"
                                                class="w-full bg-transparent border-b-2 border-gray-200 py-2 text-xs font-bold uppercase focus:border-black focus:outline-none transition-all placeholder:text-gray-300"
                                                placeholder="ENTER REMARKS..."
                                                <?php echo $read_only && !$is_rejected ? 'readonly disabled' : ''; ?>>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="flex justify-end gap-4">
                    <button type="submit" name="submit_action" value="draft" class="px-6 py-3 bg-white border-2 border-black text-black text-xs font-black uppercase tracking-widest hover:bg-gray-100 transition-all">
                        <i class="fas fa-save mr-2"></i> Save Draft
                    </button>
                    <button type="submit" name="submit_action" value="submitted" class="btn-os" onclick="return confirm('Submit marks? Once submitted, you cannot edit them unless requested for correction.');">
                        <i class="fas fa-check mr-2"></i> Submit Marks
                    </button>
                </div>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
