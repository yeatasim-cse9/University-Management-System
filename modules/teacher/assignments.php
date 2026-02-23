<?php
/**
 * Assignments Management
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('teacher');

$page_title = 'Assignments';
$user_id = get_current_user_id();

// Get teacher info
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$teacher_id = $teacher['id'];

// Actions
$action = $_GET['action'] ?? 'list';
$assignment_id = $_GET['id'] ?? null;

// Get My Courses for Dropdown
$courses = [];
$c_stmt = $db->query("SELECT co.id, c.course_code, c.course_name, co.section 
    FROM course_offerings co 
    JOIN courses c ON co.course_id = c.id 
    JOIN teacher_courses tc ON co.id = tc.course_offering_id 
    WHERE tc.teacher_id = $teacher_id AND co.status = 'open'");
while ($row = $c_stmt->fetch_assoc()) {
    $courses[] = $row;
}
$course_ids = array_column($courses, 'id');
$course_list = !empty($course_ids) ? implode(',', $course_ids) : '0';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request');
    } else {
        $post_action = $_POST['action'] ?? '';
        
        if ($post_action === 'create' || $post_action === 'edit') {
            $course_id = intval($_POST['course_offering_id']);
            $title = sanitize_input($_POST['title']);
            $desc = sanitize_input($_POST['description']);
            $marks = floatval($_POST['total_marks']);
            $due = sanitize_input($_POST['due_date']);
            $status = sanitize_input($_POST['status']);
            
            // Verify course ownership
            if (!in_array($course_id, $course_ids)) {
                set_flash('error', 'Invalid course selection');
            } else {
                if ($post_action === 'create') {
                    $stmt = $db->prepare("INSERT INTO assignments (course_offering_id, title, description, total_marks, due_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issdssi", $course_id, $title, $desc, $marks, $due, $status, $user_id);
                    if ($stmt->execute()) {
                        set_flash('success', 'Assignment created successfully');
                        create_audit_log('create_assignment', 'assignments', $stmt->insert_id, null, ['title' => $title]);
                    }
                } else {
                    $id = intval($_POST['id']);
                    // Verify assignment ownership
                    $check = $db->query("SELECT id FROM assignments WHERE id = $id AND created_by = $user_id");
                    if ($check->num_rows > 0) {
                        $stmt = $db->prepare("UPDATE assignments SET course_offering_id=?, title=?, description=?, total_marks=?, due_date=?, status=? WHERE id=?");
                        $stmt->bind_param("issdssi", $course_id, $title, $desc, $marks, $due, $status, $id);
                        if ($stmt->execute()) {
                            set_flash('success', 'Assignment updated successfully');
                            create_audit_log('update_assignment', 'assignments', $id);
                        }
                    } else {
                        set_flash('error', 'Permission denied');
                    }
                }
                redirect(BASE_URL . '/modules/teacher/assignments.php');
            }
        } elseif ($post_action === 'delete') {
            $id = intval($_POST['id']);
            $check = $db->query("SELECT id FROM assignments WHERE id = $id AND created_by = $user_id");
            if ($check->num_rows > 0) {
                $db->query("DELETE FROM assignments WHERE id = $id");
                set_flash('success', 'Assignment deleted');
                create_audit_log('delete_assignment', 'assignments', $id);
            }
            redirect(BASE_URL . '/modules/teacher/assignments.php');
        } elseif ($post_action === 'grade_submission') {
            $sub_id = intval($_POST['submission_id']);
            $marks = floatval($_POST['marks_obtained']);
            $feedback = sanitize_input($_POST['feedback']);
            
            // Allow teacher to also edit status if needed
            // Verify via join that assignment belongs to teacher
            $check = $db->query("SELECT asub.id FROM assignment_submissions asub 
                JOIN assignments a ON asub.assignment_id = a.id 
                WHERE asub.id = $sub_id AND a.created_by = $user_id");
                
            if ($check->num_rows > 0) {
                $now = date('Y-m-d H:i:s');
                $stmt = $db->prepare("UPDATE assignment_submissions SET marks_obtained = ?, feedback = ?, graded_by = ?, graded_at = ?, status = 'graded' WHERE id = ?");
                $stmt->bind_param("dsisi", $marks, $feedback, $user_id, $now, $sub_id);
                $stmt->execute();
                set_flash('success', 'Grading saved');
                create_audit_log('grade_assignment', 'assignment_submissions', $sub_id);
            }
            // Return to grading view
            $assign_id = intval($_POST['assignment_id']);
            redirect(BASE_URL . "/modules/teacher/assignments.php?action=view&id=$assign_id");
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
                Assignments
            </h1>
            <p class="text-sm font-bold uppercase tracking-widest text-gray-600">
                Manage benchmarks and feedback
            </p>
        </div>
        
        <div class="flex items-center gap-4">
            <?php if ($action === 'list'): ?>
                <a href="?action=create" class="btn-os">
                    <i class="fas fa-plus mr-2"></i> Create Assignment
                </a>
            <?php else: ?>
                <a href="?" class="btn-os">
                    <i class="fas fa-arrow-left mr-2"></i> Back to List
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($action === 'create' || $action === 'edit'): 
        $edit_data = null;
        if ($action === 'edit' && $assignment_id) {
            $res = $db->query("SELECT * FROM assignments WHERE id = $assignment_id AND created_by = $user_id");
            if ($res->num_rows > 0) $edit_data = $res->fetch_assoc();
        }
    ?>
        <div class="os-card p-8">
            <h2 class="text-xl font-black uppercase tracking-tighter border-b-2 border-black pb-4 mb-8">
                <?php echo $edit_data ? 'Edit Assignment' : 'New Assignment'; ?>
            </h2>
            
            <form method="POST" action="" class="space-y-8">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                <?php endif; ?>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest mb-2">Course</label>
                            <div class="relative">
                                <select name="course_offering_id" class="w-full bg-white border-2 border-black p-4 text-sm font-bold uppercase focus:ring-0 focus:border-black focus:shadow-os transition-all appearance-none cursor-pointer" required>
                                    <option value="">Select Course</option>
                                    <?php foreach ($courses as $c): ?>
                                        <option value="<?php echo $c['id']; ?>" <?php echo ($edit_data['course_offering_id'] ?? '') == $c['id'] ? 'selected' : ''; ?>>
                                            <?php echo e($c['course_code'] . ' - ' . $c['course_name'] . ' (Sec ' . $c['section'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-black">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest mb-2">Title</label>
                            <input type="text" name="title" value="<?php echo e($edit_data['title'] ?? ''); ?>" placeholder="ASSIGNMENT TITLE" 
                                class="w-full bg-white border-2 border-black p-4 text-sm font-bold uppercase focus:ring-0 focus:border-black focus:shadow-os transition-all placeholder:text-gray-400" required>
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest mb-2">Description</label>
                            <textarea name="description" rows="6" placeholder="ENTER ASSIGNMENT DETAILS..." 
                                class="w-full bg-white border-2 border-black p-4 text-sm font-bold focus:ring-0 focus:border-black focus:shadow-os transition-all placeholder:text-gray-400"><?php echo e($edit_data['description'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest mb-2">Total Marks</label>
                            <input type="number" step="0.5" name="total_marks" value="<?php echo e($edit_data['total_marks'] ?? '10'); ?>" 
                                class="w-full bg-white border-2 border-black p-4 text-sm font-black focus:ring-0 focus:border-black focus:shadow-os transition-all" required>
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest mb-2">Due Date</label>
                            <input type="datetime-local" name="due_date" 
                                value="<?php echo isset($edit_data['due_date']) ? date('Y-m-d\TH:i', strtotime($edit_data['due_date'])) : ''; ?>" 
                                class="w-full bg-white border-2 border-black p-4 text-sm font-bold uppercase focus:ring-0 focus:border-black focus:shadow-os transition-all" required>
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest mb-2">Status</label>
                            <div class="relative">
                                <select name="status" class="w-full bg-white border-2 border-black p-4 text-sm font-bold uppercase focus:ring-0 focus:border-black focus:shadow-os transition-all appearance-none cursor-pointer">
                                    <option value="draft" <?php echo ($edit_data['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="published" <?php echo ($edit_data['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                                    <option value="closed" <?php echo ($edit_data['status'] ?? '') === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-black">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end pt-6 border-t-2 border-black">
                    <button type="submit" class="btn-os">
                        <i class="fas fa-save mr-2"></i> <?php echo $edit_data ? 'Update Assignment' : 'Create Assignment'; ?>
                    </button>
                </div>
            </form>
        </div>

    <?php elseif ($action === 'view' && $assignment_id): 
        $assign_q = $db->query("SELECT a.*, c.course_code, c.course_name, co.section 
            FROM assignments a 
            JOIN course_offerings co ON a.course_offering_id = co.id 
            JOIN courses c ON co.course_id = c.id
            WHERE a.id = $assignment_id AND a.created_by = $user_id");
            
        if ($assign_q->num_rows === 0) {
            echo "<div class='bg-red-100 border-l-4 border-black p-4 font-bold uppercase text-red-600'>Assignment not found</div>";
        } else {
            $assignment = $assign_q->fetch_assoc();
    ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Assignment Info -->
            <div class="lg:col-span-1 space-y-6">
                <div class="os-card-invert p-8">
                    <p class="text-xs font-black text-yellow-400 uppercase tracking-widest mb-2">Assignment Details</p>
                    <h2 class="text-3xl font-black uppercase tracking-tighter leading-none mb-2"><?php echo e($assignment['title']); ?></h2>
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-widest border-b border-gray-700 pb-4 mb-4">
                        <?php echo e($assignment['course_code']); ?> | Sec <?php echo e($assignment['section']); ?>
                    </p>
                    
                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 border-2 border-white flex items-center justify-center text-yellow-400">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Due Date</p>
                                <p class="text-sm font-bold text-white uppercase"><?php echo date('M d, Y | h:i A', strtotime($assignment['due_date'])); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 border-2 border-white flex items-center justify-center text-emerald-400">
                                <i class="fas fa-medal"></i>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Marks</p>
                                <p class="text-sm font-bold text-white uppercase"><?php echo $assignment['total_marks']; ?> Points</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 p-4 border-2 border-dashed border-gray-600 font-mono text-xs text-gray-300">
                        <?php echo nl2br(e($assignment['description'])); ?>
                    </div>
                </div>
            </div>

            <!-- Submissions -->
            <div class="lg:col-span-2">
                <div class="os-card overflow-hidden">
                    <div class="p-6 border-b-2 border-black bg-yellow-50">
                        <h3 class="text-lg font-black uppercase tracking-tighter">Student Submissions</h3>
                        <p class="text-xs font-bold uppercase tracking-widest text-gray-500">Review and Grade</p>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-black text-white border-b-2 border-black">
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-widest">Student</th>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-widest">Submitted At</th>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-widest">Score</th>
                                    <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-2 divide-black">
                                <?php
                                $sub_q = "SELECT asub.*, s.student_id, up.first_name, up.last_name 
                                    FROM assignment_submissions asub 
                                    JOIN students s ON asub.student_id = s.id 
                                    JOIN user_profiles up ON s.user_id = up.user_id 
                                    WHERE asub.assignment_id = $assignment_id
                                    ORDER BY asub.submitted_at DESC";
                                $sub_res = $db->query($sub_q);
                                if ($sub_res->num_rows > 0):
                                    while ($sub = $sub_res->fetch_assoc()):
                                ?>
                                    <tr class="hover:bg-yellow-50/50 transition-colors group">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 bg-black text-white flex items-center justify-center font-black text-[10px] border-2 border-black">
                                                    ST
                                                </div>
                                                <div>
                                                    <p class="text-xs font-black uppercase leading-tight"><?php echo e($sub['first_name'] . ' ' . $sub['last_name']); ?></p>
                                                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest"><?php echo e($sub['student_id']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <p class="text-xs font-bold uppercase">
                                                <?php echo date('M d, h:i A', strtotime($sub['submitted_at'])); ?>
                                            </p>
                                            <?php if ($sub['submitted_at'] > $assignment['due_date']): ?>
                                                <span class="text-[9px] font-black text-red-600 uppercase tracking-widest block bg-red-100 inline-block px-1 mt-1">Late</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($sub['status'] === 'graded'): ?>
                                                <div class="flex items-center gap-1">
                                                    <span class="text-sm font-black text-emerald-600"><?php echo $sub['marks_obtained']; ?></span>
                                                    <span class="text-[10px] font-bold text-gray-400">/ <?php echo $assignment['total_marks']; ?></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-[9px] font-bold uppercase tracking-widest text-amber-600 bg-amber-100 px-2 py-1">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-right space-x-2">
                                            <?php if ($sub['submission_file']): ?>
                                                <a href="<?php echo BASE_URL . '/uploads/assignments/' . e($sub['submission_file']); ?>" target="_blank" class="inline-flex items-center justify-center w-8 h-8 border-2 border-black text-black hover:bg-black hover:text-white transition-colors" title="View Submission">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button onclick="openGradeModal(<?php echo htmlspecialchars(json_encode($sub)); ?>, <?php echo $assignment['total_marks']; ?>)" class="px-4 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest hover:bg-yellow-400 hover:text-black hover:shadow-os transition-all border-2 border-black">
                                                Grade
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="4" class="px-6 py-12 text-center">
                                        <div class="w-16 h-16 bg-gray-100 border-2 border-black flex items-center justify-center mx-auto mb-4">
                                            <i class="fas fa-inbox text-xl text-gray-400"></i>
                                        </div>
                                        <p class="text-xs font-black text-gray-400 uppercase tracking-widest">No submissions yet</p>
                                    </td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grading Modal -->
        <div id="gradeModal" class="relative z-[9999] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/80 transition-opacity backdrop-blur-sm"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden bg-white text-left shadow-2xl transition-all sm:my-8 w-full sm:max-w-md border-4 border-black box-shadow-os">
                        <div class="bg-black p-6 text-white border-b-4 border-black">
                            <h3 class="text-xl font-black uppercase tracking-tighter">Grade Submission</h3>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">Updates are final unless re-graded</p>
                        </div>

                        <div class="p-8">
                            <form method="POST" class="space-y-6">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="grade_submission">
                                <input type="hidden" name="assignment_id" value="<?php echo $assignment_id; ?>">
                                <input type="hidden" name="submission_id" id="modal_sub_id">
                                
                                <div>
                                    <label class="block text-xs font-black uppercase tracking-widest mb-2">Marks Obtained (Max: <span id="modal_max_marks"></span>)</label>
                                    <input type="number" step="0.5" name="marks_obtained" id="modal_marks" class="w-full bg-white border-2 border-black p-4 text-lg font-black focus:ring-0 focus:border-black focus:shadow-os transition-all" required>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-black uppercase tracking-widest mb-2">Feedback</label>
                                    <textarea name="feedback" id="modal_feedback" rows="4" placeholder="ENTER FEEDBACK..." class="w-full bg-white border-2 border-black p-4 text-xs font-bold focus:ring-0 focus:border-black focus:shadow-os transition-all uppercase placeholder:text-gray-300"></textarea>
                                </div>
                                
                                <div class="flex gap-4 pt-4 border-t-2 border-gray-100">
                                    <button type="button" onclick="document.getElementById('gradeModal').classList.add('hidden')" class="flex-1 py-4 bg-white border-2 border-black text-black text-xs font-black uppercase tracking-widest hover:bg-gray-100">Cancel</button>
                                    <button type="submit" class="flex-1 py-4 bg-black border-2 border-black text-white text-xs font-black uppercase tracking-widest hover:bg-yellow-400 hover:text-black transition-colors shadow-os">Submit Grade</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function openGradeModal(submission, maxMarks) {
            document.getElementById('modal_sub_id').value = submission.id;
            document.getElementById('modal_marks').value = submission.marks_obtained || '';
            document.getElementById('modal_feedback').value = submission.feedback || '';
            document.getElementById('modal_max_marks').innerText = maxMarks;
            document.getElementById('modal_marks').max = maxMarks;
            document.getElementById('gradeModal').classList.remove('hidden');
        }
        </script>
    <?php } ?>

    <?php else: // List View ?>
        <div class="os-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-black text-white border-b-2 border-black">
                            <th class="px-6 py-4 text-xs font-black uppercase tracking-widest">Assignment</th>
                            <th class="px-6 py-4 text-xs font-black uppercase tracking-widest">Course / Section</th>
                            <th class="px-6 py-4 text-xs font-black uppercase tracking-widest">Due Date</th>
                            <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-center">Submissions</th>
                            <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-2 divide-black">
                        <?php
                        $q = "SELECT a.*, c.course_code, co.section,
                            (SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = a.id) as sub_count
                            FROM assignments a 
                            JOIN course_offerings co ON a.course_offering_id = co.id 
                            JOIN courses c ON co.course_id = c.id
                            WHERE a.created_by = $user_id
                            ORDER BY a.created_at DESC";
                        $res = $db->query($q);
                        if ($res->num_rows > 0):
                            while ($row = $res->fetch_assoc()):
                        ?>
                            <tr class="hover:bg-yellow-50/50 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-black text-white flex items-center justify-center font-black text-xs border-2 border-black group-hover:bg-white group-hover:text-black transition-colors">
                                            AS
                                        </div>
                                        <div>
                                            <p class="text-sm font-black uppercase leading-tight group-hover:text-indigo-600 transition-colors"><?php echo e($row['title']); ?></p>
                                            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-0.5">#<?php echo $row['id']; ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-xs font-bold uppercase"><?php echo e($row['course_code']); ?></p>
                                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Sec <?php echo e($row['section']); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-xs font-bold uppercase"><?php echo date('M d, Y', strtotime($row['due_date'])); ?></p>
                                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest"><?php echo date('h:i A', strtotime($row['due_date'])); ?></p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-lg font-black"><?php echo $row['sub_count']; ?></span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="?action=view&id=<?php echo $row['id']; ?>" class="w-8 h-8 border-2 border-black flex items-center justify-center text-black hover:bg-black hover:text-white transition-colors" title="View">
                                            <i class="fas fa-eye text-xs"></i>
                                        </a>
                                        <a href="?action=edit&id=<?php echo $row['id']; ?>" class="w-8 h-8 border-2 border-black flex items-center justify-center text-black hover:bg-black hover:text-white transition-colors" title="Edit">
                                            <i class="fas fa-edit text-xs"></i>
                                        </a>
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this assignment?');">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="w-8 h-8 border-2 border-black flex items-center justify-center text-red-600 hover:bg-red-600 hover:text-white transition-colors" title="Delete">
                                                <i class="fas fa-trash text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500 font-bold uppercase tracking-widest">No assignments found.</td></tr>
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
?>
