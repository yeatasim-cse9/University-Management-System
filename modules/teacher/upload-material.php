<?php
/**
 * Upload Course Material
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('teacher');

$page_title = 'Upload Material';
$user_id = get_current_user_id();

// Get teacher info
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$teacher_id = $teacher['id'];

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

// Handle File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request');
    } else {
        $course_id = intval($_POST['course_offering_id']);
        $title = sanitize_input($_POST['title']);
        $description = sanitize_input($_POST['description']);
        
        if (!in_array($course_id, $course_ids)) {
            set_flash('error', 'Invalid course selection');
        } elseif (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            set_flash('error', 'Please select a valid file');
        } else {
            $upload_dir = __DIR__ . '/../../uploads/materials/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip', 'jpg', 'png'];
            
            if (!in_array($file_ext, $allowed)) {
                set_flash('error', 'Invalid file type');
            } else {
                $filename = uniqid() . '_' . preg_replace('/[^a-z0-9.]/i', '_', $_FILES['file']['name']);
                
                if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $filename)) {
                    $stmt = $db->prepare("INSERT INTO course_materials (course_offering_id, title, description, file_path, uploaded_by) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("isssi", $course_id, $title, $description, $filename, $user_id);
                    if ($stmt->execute()) {
                        set_flash('success', 'Material uploaded successfully');
                        create_audit_log('upload_material', 'course_materials', $stmt->insert_id);
                        redirect(BASE_URL . '/modules/teacher/course-materials.php');
                    } else {
                        set_flash('error', 'Database error');
                    }
                } else {
                    set_flash('error', 'Failed to move uploaded file');
                }
            }
        }
    }
}

// Sidebar
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<div class="space-y-6 max-w-2xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between gap-6">
        <div>
            <h1 class="text-3xl font-black uppercase tracking-tighter border-b-4 border-black inline-block pb-2 mb-2">
                Upload Material
            </h1>
            <p class="text-sm font-bold uppercase tracking-widest text-gray-600">
                Add new resource
            </p>
        </div>
        <a href="course-materials.php" class="btn-os bg-white hover:bg-black hover:text-white">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <!-- Upload Form -->
    <div class="os-card p-8 bg-white">
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <?php csrf_field(); ?>
            
            <div>
                <label class="block text-xs font-black uppercase tracking-widest mb-2">Target Course</label>
                <div class="relative">
                    <select name="course_offering_id" class="w-full bg-white border-2 border-black p-4 text-sm font-bold uppercase focus:ring-0 focus:border-black focus:shadow-os transition-all appearance-none cursor-pointer" required>
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>">
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
                <input type="text" name="title" placeholder="LECTURE 1 SLIDES" class="w-full bg-white border-2 border-black p-4 text-sm font-bold uppercase focus:ring-0 focus:border-black focus:shadow-os transition-all" required>
            </div>
            
            <div>
                <label class="block text-xs font-black uppercase tracking-widest mb-2">Description (Optional)</label>
                <textarea name="description" rows="3" placeholder="Brief description..." class="w-full bg-white border-2 border-black p-4 text-sm font-bold uppercase focus:ring-0 focus:border-black focus:shadow-os transition-all"></textarea>
            </div>
            
            <div>
                <label class="block text-xs font-black uppercase tracking-widest mb-2">File</label>
                <input type="file" name="file" class="w-full bg-gray-100 border-2 border-black p-4 text-xs font-bold uppercase file:mr-4 file:py-2 file:px-4 file:border-2 file:border-black file:text-xs file:font-black file:uppercase file:bg-white file:text-black hover:file:bg-black hover:file:text-white transition-all" required>
                <p class="text-[10px] font-bold text-gray-400 mt-2 uppercase tracking-widest">Supported: PDF, DOC, PPT, XLS, ZIP, IMG</p>
            </div>
            
            <div class="pt-4 border-t-2 border-gray-100">
                <button type="submit" class="w-full py-4 bg-black border-2 border-black text-white text-xs font-black uppercase tracking-widest hover:bg-yellow-400 hover:text-black transition-colors shadow-os">
                    Upload Material
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
