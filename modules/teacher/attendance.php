<?php
/**
 * Mark Attendance
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('teacher');

$page_title = 'Mark Attendance';
$user_id = get_current_user_id();

// Get teacher info
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$teacher_id = $teacher['id'];

// Get assigned courses
$courses = [];
$c_stmt = $db->query("SELECT co.id, c.course_code, c.course_name, co.section 
    FROM course_offerings co 
    JOIN courses c ON co.course_id = c.id 
    JOIN teacher_courses tc ON co.id = tc.course_offering_id 
    WHERE tc.teacher_id = $teacher_id AND co.status = 'open'
    ORDER BY c.course_code");
while ($row = $c_stmt->fetch_assoc()) {
    $courses[] = $row;
}

$course_id = $_GET['course_id'] ?? ($_POST['course_id'] ?? null);
$date = $_GET['date'] ?? ($_POST['date'] ?? date('Y-m-d'));

$students = [];
$existing_attendance = [];
$success_msg = '';
$error_msg = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_msg = 'Invalid request token';
    } else {
        $statuses = $_POST['status'] ?? [];
        $remarks = $_POST['remarks'] ?? [];
        $course_id = intval($_POST['course_id']);
        $date = sanitize_input($_POST['date']);
        
        // Verify ownership
        $check = $db->query("SELECT id FROM teacher_courses WHERE teacher_id = $teacher_id AND course_offering_id = $course_id");
        if ($check->num_rows > 0) {
            $db->begin_transaction();
            try {
                foreach ($statuses as $enrollment_id => $status) {
                    $enrollment_id = intval($enrollment_id);
                    $status = sanitize_input($status); // present, absent, late, excused
                    $remark = sanitize_input($remarks[$enrollment_id] ?? '');
                    
                    // Check if exists
                    $exists_q = $db->query("SELECT id FROM attendance WHERE enrollment_id = $enrollment_id AND attendance_date = '$date'");
                    
                    if ($exists_q->num_rows > 0) {
                        $update_stmt = $db->prepare("UPDATE attendance SET status = ?, remarks = ?, marked_by = ? WHERE enrollment_id = ? AND attendance_date = ?");
                        $update_stmt->bind_param("ssiis", $status, $remark, $user_id, $enrollment_id, $date);
                        $update_stmt->execute();
                    } else {
                        $insert_stmt = $db->prepare("INSERT INTO attendance (enrollment_id, course_offering_id, attendance_date, status, marked_by, remarks) VALUES (?, ?, ?, ?, ?, ?)");
                        $insert_stmt->bind_param("iissss", $enrollment_id, $course_id, $date, $status, $user_id, $remark);
                        $insert_stmt->execute();
                    }
                }
                $db->commit();
                create_audit_log('mark_attendance', 'attendance', null, null, ['course_id' => $course_id, 'date' => $date]);
                $success_msg = 'Attendance saved successfully';
            } catch (Exception $e) {
                $db->rollback();
                $error_msg = 'Failed to save attendance: ' . $e->getMessage();
            }
        } else {
            $error_msg = 'Permission denied';
        }
    }
}

// Fetch Data if course selected
if ($course_id) {
    // Validate ownership
    $valid = false;
    foreach ($courses as $c) {
        if ($c['id'] == $course_id) $valid = true;
    }
    
    if (!$valid) {
        $error_msg = 'Invalid course selection';
        $course_id = null;
    } else {
        // Get Students
        $q = "SELECT e.id as enrollment_id, s.student_id, up.first_name, up.last_name 
            FROM enrollments e
            JOIN students s ON e.student_id = s.id
            JOIN users u ON s.user_id = u.id
            JOIN user_profiles up ON u.id = up.user_id
            WHERE e.course_offering_id = $course_id AND e.status = 'enrolled'
            ORDER BY s.student_id";
        $res = $db->query($q);
        while ($row = $res->fetch_assoc()) {
            $students[] = $row;
        }
        
        // Get Existing Attendance
        $att_q = "SELECT enrollment_id, status, remarks FROM attendance WHERE course_offering_id = $course_id AND attendance_date = '$date'";
        $att_res = $db->query($att_q);
        while ($row = $att_res->fetch_assoc()) {
            $existing_attendance[$row['enrollment_id']] = $row;
        }
    }
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
                Mark Attendance
            </h1>
            <p class="text-sm font-bold uppercase tracking-widest text-gray-600">
                Record student presence for <?php echo date('F j, Y'); ?>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <div class="os-icon-box bg-black text-white">
                <i class="fas fa-calendar-check text-xl"></i>
            </div>
            <div class="text-right hidden md:block">
                <div class="text-xs font-bold uppercase tracking-widest text-gray-500">System Status</div>
                <div class="text-sm font-black uppercase text-emerald-600 flex items-center justify-end gap-2">
                    <span class="w-2 h-2 bg-emerald-600 rounded-none transform rotate-45"></span>
                    Active
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($success_msg): ?>
        <div class="bg-emerald-100 border-l-4 border-black p-4 data-os-alert flex items-center gap-4 shadow-os">
            <div class="w-10 h-10 bg-emerald-500 text-white flex items-center justify-center border-2 border-black font-black text-lg">
                <i class="fas fa-check"></i>
            </div>
            <div>
                <p class="font-black uppercase text-sm tracking-wider">Success</p>
                <p class="text-xs font-bold uppercase tracking-wide text-emerald-800"><?php echo $success_msg; ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($error_msg): ?>
        <div class="bg-red-100 border-l-4 border-black p-4 data-os-alert flex items-center gap-4 shadow-os">
            <div class="w-10 h-10 bg-red-500 text-white flex items-center justify-center border-2 border-black font-black text-lg">
                <i class="fas fa-exclamation"></i>
            </div>
            <div>
                <p class="font-black uppercase text-sm tracking-wider">Error</p>
                <p class="text-xs font-bold uppercase tracking-wide text-red-800"><?php echo $error_msg; ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Selection Panel -->
    <div class="os-card p-6">
        <form method="GET" class="flex flex-col lg:flex-row items-end gap-6">
            <div class="w-full lg:flex-1">
                <label class="block text-xs font-black uppercase tracking-widest mb-2">Select Course</label>
                <div class="relative">
                    <select name="course_id" class="w-full bg-white border-2 border-black p-4 text-sm font-bold uppercase focus:ring-0 focus:border-black focus:shadow-os transition-all appearance-none cursor-pointer" onchange="this.form.submit()">
                        <option value="">-- Choose Course --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $course_id == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo e($c['course_code'] . ' - ' . $c['course_name'] . ' (Sec ' . $c['section'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-black">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
            </div>
            
            <div class="w-full lg:w-72">
                <label class="block text-xs font-black uppercase tracking-widest mb-2">Select Date</label>
                <input type="date" name="date" value="<?php echo $date; ?>" max="<?php echo date('Y-m-d'); ?>" 
                    class="w-full bg-white border-2 border-black p-4 text-sm font-bold uppercase focus:ring-0 focus:border-black focus:shadow-os transition-all cursor-pointer"
                    onchange="this.form.submit()">
            </div>
            
            <button type="submit" class="btn-os w-full lg:w-auto">
                Load List
            </button>
        </form>
    </div>

    <?php if ($course_id && !empty($students)): ?>
        <form method="POST" action="">
            <?php csrf_field(); ?>
            <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
            <input type="hidden" name="date" value="<?php echo $date; ?>">
            <input type="hidden" name="submit_attendance" value="1">

            <div class="os-card overflow-hidden">
                <div class="p-6 border-b-2 border-black bg-yellow-50 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-black uppercase tracking-tighter">Student List</h3>
                        <p class="text-xs font-bold uppercase tracking-widest text-gray-500"><?php echo date('l, F j, Y', strtotime($date)); ?></p>
                    </div>
                    <button type="button" onclick="markAllPresent()" class="text-xs font-black uppercase tracking-widest hover:text-emerald-600 underline decoration-2 underline-offset-4">
                        Mark All Present
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-black text-white border-b-2 border-black">
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-widest">Student</th>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-center">Status</th>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-widest">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y-2 divide-black">
                            <?php foreach ($students as $student): 
                                $eid = $student['enrollment_id'];
                                $current_status = $existing_attendance[$eid]['status'] ?? 'present';
                                $current_remark = $existing_attendance[$eid]['remarks'] ?? '';
                            ?>
                                <tr class="hover:bg-yellow-50/50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-4">
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
                                        <div class="flex justify-center items-center gap-2">
                                            <?php 
                                            $statuses = [
                                                'present' => ['color' => 'emerald', 'icon' => 'fa-check', 'label' => 'P'],
                                                'late' => ['color' => 'amber', 'icon' => 'fa-clock', 'label' => 'L'],
                                                'absent' => ['color' => 'rose', 'icon' => 'fa-xmark', 'label' => 'A'],
                                                'excused' => ['color' => 'purple', 'icon' => 'fa-envelope', 'label' => 'E']
                                            ];
                                            foreach ($statuses as $key => $val):
                                            ?>
                                            <label class="relative cursor-pointer group/radio">
                                                <input type="radio" name="status[<?php echo $eid; ?>]" value="<?php echo $key; ?>" 
                                                    class="peer sr-only <?php echo $key === 'present' ? 'present-radio' : ''; ?>"
                                                    <?php echo $current_status === $key ? 'checked' : ''; ?>>
                                                <div class="
                                                    w-10 h-10 flex flex-col items-center justify-center border-2 border-gray-200 
                                                    text-gray-400 bg-white hover:border-black hover:text-black transition-all
                                                    peer-checked:border-black peer-checked:bg-<?php echo $val['color']; ?>-400 peer-checked:text-black peer-checked:shadow-os
                                                ">
                                                    <span class="text-xs font-black uppercase"><?php echo $val['label']; ?></span>
                                                </div>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <input type="text" name="remarks[<?php echo $eid; ?>]" 
                                            value="<?php echo e($current_remark); ?>"
                                            placeholder="REMARKS..."
                                            class="w-full bg-transparent border-b-2 border-gray-200 py-2 text-xs font-bold uppercase focus:border-black focus:outline-none transition-all placeholder:text-gray-300">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end pt-6">
                <button type="submit" class="btn-os">
                    <i class="fas fa-save mr-2"></i> Save Attendance
                </button>
            </div>
        </form>
        
        <script>
        function markAllPresent() {
            document.querySelectorAll('.present-radio').forEach(radio => {
                radio.checked = true;
                // Trigger change event if needed for custom UI updates, though pure CSS handles it here
            });
        }
        </script>
    <?php elseif ($course_id): ?>
        <div class="os-card p-12 text-center">
            <div class="w-16 h-16 bg-gray-100 border-2 border-black flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-users-slash text-2xl text-gray-400"></i>
            </div>
            <h3 class="text-xl font-black uppercase tracking-tighter mb-2">No Students Found</h3>
            <p class="text-sm font-bold text-gray-500 uppercase tracking-widest">No enrolled students found for the selected course.</p>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
