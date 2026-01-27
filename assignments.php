<?php
/**
 * Student Assignments
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('student');

$page_title = 'Assignments';
$user_id = get_current_user_id();

// Get student info
$stmt = $db->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$student_id = $student['id'];

$action = $_GET['action'] ?? 'list';
$assignment_id = $_GET['id'] ?? null;

// Handle Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'submit') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid Request');
    } else {
        $assignment_id = intval($_POST['assignment_id']);
        $text = sanitize_input($_POST['submission_text']);
        
        // Check if assignment exists and is open
        $chk = $db->query("SELECT a.due_date, e.id as enrollment_id 
            FROM assignments a 
            JOIN enrollments e ON a.course_offering_id = e.course_offering_id 
            WHERE a.id = $assignment_id AND e.student_id = $student_id AND a.status = 'published'");
            
        if ($chk->num_rows > 0) {
            $assign = $chk->fetch_assoc();
            
            // Check if already submitted
            $sub_chk = $db->query("SELECT id FROM assignment_submissions WHERE assignment_id = $assignment_id AND student_id = $student_id");
            if ($sub_chk->num_rows > 0) {
                // Check if graded
                $graded_chk = $db->query("SELECT status FROM assignment_submissions WHERE assignment_id = $assignment_id AND student_id = $student_id AND status = 'graded'");
                if ($graded_chk->num_rows > 0) {
                    set_flash('error', 'Cannot update graded assignment.');
                    redirect(BASE_URL . "/modules/student/assignments.php?action=view&id=$assignment_id");
                }
            }
            
            // Handle File
            $filename = null;
            if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
                $upload_dir = __DIR__ . '/../../uploads/assignments/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
                $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip', 'jpg', 'png'];
                
                if (in_array($file_ext, $allowed)) {
                    $filename = uniqid('sub_') . '.' . $file_ext;
                    move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $filename);
                } else {
                    set_flash('error', 'Invalid file type.');
                    redirect(BASE_URL . "/modules/student/assignments.php?action=view&id=$assignment_id");
                }
            }
            
            // Determine status
            $status = (strtotime($assign['due_date']) < time()) ? 'late' : 'submitted';
            
            if ($sub_chk->num_rows > 0) {
                // Update
                $update_cols = "submission_text = ?, submitted_at = NOW(), status = ?";
                $params = [$text, $status];
                $types = "ss";
                
                if ($filename) {
                    $update_cols .= ", submission_file = ?";
                    $params[] = $filename;
                    $types .= "s";
                }
                
                $params[] = $assignment_id;
                $params[] = $student_id;
                $types .= "ii";
                
                $stmt = $db->prepare("UPDATE assignment_submissions SET $update_cols WHERE assignment_id = ? AND student_id = ?");
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                
            } else {
                // Insert
                $stmt = $db->prepare("INSERT INTO assignment_submissions (assignment_id, student_id, submission_file, submission_text, submitted_at, status) VALUES (?, ?, ?, ?, NOW(), ?)");
                $stmt->bind_param("iisss", $assignment_id, $student_id, $filename, $text, $status);
                $stmt->execute();
            }
            
            set_flash('success', 'Assignment submitted successfully.');
            create_audit_log('submit_assignment', 'assignment_submissions', null, null, ['assignment_id' => $assignment_id]);
            
        } else {
            set_flash('error', 'Assignment not found or closed.');
        }
        redirect(BASE_URL . "/modules/student/assignments.php?action=view&id=$assignment_id");
    }
}

// Sidebar
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<div class="space-y-6">
    <?php if ($action === 'view' && $assignment_id): 
        // Get Assignment Details
        $q = "SELECT a.*, c.course_code, c.course_name, 
            (SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = a.id AND student_id = $student_id) as submitted_count
            FROM assignments a 
            JOIN course_offerings co ON a.course_offering_id = co.id 
            JOIN courses c ON co.course_id = c.id
            JOIN enrollments e ON co.id = e.course_offering_id
            WHERE a.id = $assignment_id AND e.student_id = $student_id AND a.status = 'published'";
        $res = $db->query($q);
        
        if ($res->num_rows === 0) {
            echo "<div class='bg-red-100 border-l-4 border-black p-4 font-bold uppercase text-red-600'>Assignment not found.</div>";
        } else {
            $assign = $res->fetch_assoc();
            
            // Get Submission if exists
            $sub = null;
            if ($assign['submitted_count'] > 0) {
                $sub_res = $db->query("SELECT * FROM assignment_submissions WHERE assignment_id = $assignment_id AND student_id = $student_id");
                $sub = $sub_res->fetch_assoc();
            }
            
            $is_late = strtotime($assign['due_date']) < time();
            $can_submit = (!$sub || $sub['status'] !== 'graded');
    ?>
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <span class="bg-black text-white text-[10px] font-black uppercase tracking-widest px-2 py-1"><?php echo e($assign['course_code']); ?></span>
                <?php if($is_late && !$sub): ?>
                    <span class="bg-red-600 text-white text-[10px] font-black uppercase tracking-widest px-2 py-1">Overdue</span>
                <?php endif; ?>
            </div>
            <h1 class="text-3xl font-black uppercase tracking-tighter mb-2">
                <?php echo e($assign['title']); ?>
            </h1>
            <p class="text-xs font-bold uppercase tracking-widest text-gray-500">
                Due: <?php echo date('M d, Y | h:i A', strtotime($assign['due_date'])); ?>
            </p>
        </div>
        
        <div class="flex items-center gap-4">
            <a href="assignments.php" class="btn-os">
                <i class="fas fa-arrow-left mr-2"></i> Back to List
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Assignment Info -->
        <div class="lg:col-span-2 space-y-6">
            <div class="os-card p-8 min-h-[300px]">
                <h3 class="text-lg font-black uppercase tracking-tighter border-b-2 border-black pb-2 mb-6">Instructions</h3>
                <div class="prose max-w-none font-mono text-sm">
                    <?php echo nl2br(e($assign['description'])); ?>
                </div>
                
                <div class="grid grid-cols-2 gap-6 mt-8 pt-8 border-t-2 border-dashed border-gray-300">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1">Total Marks</p>
                        <p class="text-2xl font-black"><?php echo $assign['total_marks']; ?></p>
                    </div>
                </div>
            </div>

            <?php if ($sub && $sub['status'] === 'graded'): ?>
                <div class="os-card-invert p-6 border-l-8 border-l-emerald-400">
                    <h3 class="text-lg font-black uppercase tracking-tighter text-emerald-400 mb-4 flex items-center gap-2">
                        <i class="fas fa-check-circle"></i> Graded
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1">Score</p>
                            <p class="text-4xl font-black text-white"><?php echo $sub['marks_obtained']; ?> <span class="text-lg text-gray-500">/ <?php echo $assign['total_marks']; ?></span></p>
                        </div>
                        <?php if ($sub['feedback']): ?>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1">Feedback</p>
                            <p class="text-sm font-bold text-gray-300 italic">"<?php echo e($sub['feedback']); ?>"</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Submission Panel -->
        <div class="space-y-6">
            <div class="os-card h-full flex flex-col">
                <div class="p-6 border-b-2 border-black bg-yellow-50">
                    <h3 class="text-lg font-black uppercase tracking-tighter">My Submission</h3>
                </div>
                
                <div class="p-6 flex-1 flex flex-col gap-6">
                    <?php if ($sub): ?>
                        <div class="bg-gray-100 border-2 border-black p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">Status</span>
                                <span class="text-[10px] font-black uppercase tracking-widest bg-black text-white px-2 py-0.5"><?php echo $sub['status']; ?></span>
                            </div>
                            <p class="text-xs font-bold uppercase mb-2">Submitted: <?php echo date('M d, h:i A', strtotime($sub['submitted_at'])); ?></p>
                            <?php if ($sub['submission_file']): ?>
                                <a href="<?php echo BASE_URL . '/uploads/assignments/' . e($sub['submission_file']); ?>" target="_blank" class="block w-full text-center py-2 bg-white border-2 border-black text-xs font-black uppercase hover:bg-black hover:text-white transition-all">
                                    <i class="fas fa-file-download mr-1"></i> Download File
                                </a>
                            <?php endif; ?>
                            <?php if ($sub['submission_text']): ?>
                                <div class="mt-4 pt-4 border-t-2 border-gray-300">
                                     <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1">Notes</p>
                                     <p class="text-xs font-mono"><?php echo nl2br(e($sub['submission_text'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($can_submit): ?>
                        <form method="POST" action="?action=submit" enctype="multipart/form-data" class="space-y-4 mt-auto">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="assignment_id" value="<?php echo $assignment_id; ?>">
                            
                            <div>
                                <label class="block text-xs font-black uppercase tracking-widest mb-2">Upload File</label>
                                <input type="file" name="file" class="w-full bg-gray-100 border-2 border-black p-2 text-xs font-bold uppercase file:mr-2 file:py-1 file:px-2 file:border-2 file:border-black file:text-[10px] file:font-black file:uppercase file:bg-white file:text-black hover:file:bg-black hover:file:text-white transition-all">
                            </div>

                            <div>
                                <label class="block text-xs font-black uppercase tracking-widest mb-2">Notes</label>
                                <textarea name="submission_text" rows="3" class="w-full bg-white border-2 border-black p-2 text-sm font-bold uppercase focus:ring-0 focus:shadow-os transition-all" placeholder="OPTIONAL NOTES..."><?php echo $sub['submission_text'] ?? ''; ?></textarea>
                            </div>

                            <button type="submit" class="w-full py-4 bg-black text-white text-xs font-black uppercase tracking-widest hover:bg-yellow-400 hover:text-black hover:shadow-os transition-all border-2 border-black">
                                <?php echo $sub ? 'Update Submission' : 'Submit Assignment'; ?>
                            </button>
                        </form>
                    <?php elseif (!$sub): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-lock text-3xl text-gray-300 mb-2"></i>
                            <p class="text-xs font-black text-gray-400 uppercase tracking-widest">Submission Closed</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php } ?>

    <?php else: // List View ?>
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h1 class="text-4xl font-black uppercase tracking-tighter border-b-4 border-black inline-block pb-2 mb-2">
                    Assignments
                </h1>
                <p class="text-sm font-bold uppercase tracking-widest text-gray-600">
                    Tasks & Deadlines
                </p>
            </div>
            
            <div class="flex bg-white border-2 border-black p-1 shadow-os">
                <a href="?filter=pending" class="px-6 py-2 text-[10px] font-black uppercase tracking-widest transition-all <?php echo (!isset($_GET['filter']) || $_GET['filter'] === 'pending') ? 'bg-black text-white' : 'text-gray-500 hover:text-black'; ?>">Pending</a>
                <a href="?filter=submitted" class="px-6 py-2 text-[10px] font-black uppercase tracking-widest transition-all <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'submitted') ? 'bg-black text-white' : 'text-gray-500 hover:text-black'; ?>">Submitted</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            $filter = $_GET['filter'] ?? 'pending';
            if ($filter === 'pending') {
                $q = "SELECT a.*, c.course_code, c.course_name
                    FROM assignments a
                    JOIN enrollments e ON a.course_offering_id = e.course_offering_id
                    JOIN course_offerings co ON a.course_offering_id = co.id
                    JOIN courses c ON co.course_id = c.id
                    LEFT JOIN assignment_submissions sub ON a.id = sub.assignment_id AND sub.student_id = $student_id
                    WHERE e.student_id = $student_id 
                    AND a.status = 'published' 
                    AND sub.id IS NULL
                    ORDER BY a.due_date ASC";
            } else {
                 $q = "SELECT a.*, c.course_code, c.course_name, sub.status as sub_status, sub.marks_obtained, sub.submitted_at
                    FROM assignments a
                    JOIN enrollments e ON a.course_offering_id = e.course_offering_id
                    JOIN course_offerings co ON a.course_offering_id = co.id
                    JOIN courses c ON co.course_id = c.id
                    JOIN assignment_submissions sub ON a.id = sub.assignment_id AND sub.student_id = $student_id
                    WHERE e.student_id = $student_id 
                    AND a.status = 'published'
                    ORDER BY sub.submitted_at DESC";
            }
            
            $res = $db->query($q);
            
            if ($res->num_rows > 0):
                while ($row = $res->fetch_assoc()):
                    $is_late = strtotime($row['due_date']) < time();
                    // Determine card style based on status
                    $status_label = 'Pending';
                    $status_color = 'bg-gray-200 text-gray-600';
                    $card_border = 'border-black';
                    
                    if (isset($row['sub_status'])) {
                        if ($row['sub_status'] === 'graded') {
                            $status_label = 'Graded';
                            $status_color = 'bg-emerald-400 text-emerald-900 border-2 border-emerald-900';
                            $card_border = 'border-emerald-600';
                        } else {
                            $status_label = 'Submitted';
                            $status_color = 'bg-indigo-400 text-indigo-900 border-2 border-indigo-900';
                        }
                    } elseif ($is_late) {
                        $status_label = 'Missing';
                        $status_color = 'bg-red-400 text-red-900 border-2 border-red-900';
                        $card_border = 'border-red-600';
                    }
            ?>
                <div class="os-card flex flex-col h-full hover:-translate-y-1 transition-transform duration-300">
                    <div class="p-6 flex-1">
                        <div class="flex justify-between items-start mb-4">
                            <span class="bg-black text-white text-[10px] font-black uppercase tracking-widest px-2 py-1"><?php echo e($row['course_code']); ?></span>
                            <?php if (isset($row['marks_obtained'])): ?>
                                <span class="font-black text-lg"><?php echo $row['marks_obtained']; ?><span class="text-xs text-gray-500">/<?php echo $row['total_marks']; ?></span></span>
                            <?php else: ?>
                                <span class="text-[10px] font-black uppercase tracking-widest px-2 py-1 <?php echo $status_color; ?>"><?php echo $status_label; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <h3 class="text-xl font-black uppercase leading-tight mb-2 line-clamp-2" title="<?php echo e($row['title']); ?>">
                            <?php echo e($row['title']); ?>
                        </h3>
                        
                        <div class="flex items-center gap-2 text-xs font-bold uppercase text-gray-500 mt-4">
                            <i class="fas fa-calendar"></i>
                            <span>Due: <?php echo date('M d', strtotime($row['due_date'])); ?></span>
                        </div>
                    </div>
                    
                    <a href="?action=view&id=<?php echo $row['id']; ?>" class="block w-full text-center py-4 border-t-2 border-black bg-gray-50 hover:bg-black hover:text-white transition-colors text-xs font-black uppercase tracking-widest">
                        View Details <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            <?php endwhile; else: ?>
                <div class="col-span-full py-12 text-center border-2 border-dashed border-black bg-gray-50">
                    <div class="w-16 h-16 bg-white border-2 border-black flex items-center justify-center mx-auto mb-4 text-gray-400 text-2xl">
                        <i class="fas fa-check"></i>
                    </div>
                    <p class="text-xs font-black uppercase tracking-widest text-gray-500">No <?php echo $filter; ?> assignments found.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
