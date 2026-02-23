<?php
/**
 * Course Materials Management
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('teacher');

$page_title = 'Course Materials';
$user_id = get_current_user_id();

// Get teacher info
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$teacher_id = $teacher['id'];

// Handle Delete
if (isset($_POST['delete_material'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request');
    } else {
        $id = intval($_POST['id']);
        $check = $db->query("SELECT * FROM course_materials WHERE id = $id AND uploaded_by = $user_id");
        if ($check->num_rows > 0) {
            $file = $check->fetch_assoc();
            $path = __DIR__ . '/../../uploads/materials/' . $file['file_path'];
            if (file_exists($path)) unlink($path);
            
            $db->query("DELETE FROM course_materials WHERE id = $id");
            set_flash('success', 'Material deleted');
        }
        redirect(BASE_URL . '/modules/teacher/course-materials.php');
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
                Course Materials
            </h1>
            <p class="text-sm font-bold uppercase tracking-widest text-gray-600">
                Share resources with students
            </p>
        </div>
        
        <div class="flex items-center gap-4">
            <a href="upload-material.php" class="btn-os">
                <i class="fas fa-upload mr-2"></i> Upload Material
            </a>
        </div>
    </div>

    <!-- Materials List -->
    <div class="os-card overflow-hidden">
        <div class="p-6 border-b-2 border-black bg-yellow-50">
            <h3 class="text-lg font-black uppercase tracking-tighter">Uploaded Files</h3>
            <p class="text-xs font-bold uppercase tracking-widest text-gray-500">Manage your course content</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-black text-white border-b-2 border-black">
                        <th class="px-6 py-4 text-xs font-black uppercase tracking-widest">Title / Description</th>
                        <th class="px-6 py-4 text-xs font-black uppercase tracking-widest">Course</th>
                        <th class="px-6 py-4 text-xs font-black uppercase tracking-widest">File Info</th>
                        <th class="px-6 py-4 text-xs font-black uppercase tracking-widest">Date</th>
                        <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y-2 divide-black">
                    <?php
                    $q = "SELECT cm.*, c.course_code, co.section 
                        FROM course_materials cm 
                        JOIN course_offerings co ON cm.course_offering_id = co.id 
                        JOIN courses c ON co.course_id = c.id 
                        WHERE cm.uploaded_by = $user_id 
                        ORDER BY cm.created_at DESC";
                    $res = $db->query($q);
                    
                    if ($res->num_rows > 0):
                        while ($row = $res->fetch_assoc()):
                            $ext = strtolower(pathinfo($row['file_path'], PATHINFO_EXTENSION));
                            $icon = 'fa-file';
                            $color = 'text-gray-600';
                            
                            switch($ext) {
                                case 'pdf': $icon = 'fa-file-pdf'; $color = 'text-red-500'; break;
                                case 'doc': case 'docx': $icon = 'fa-file-word'; $color = 'text-blue-500'; break;
                                case 'xls': case 'xlsx': $icon = 'fa-file-excel'; $color = 'text-emerald-500'; break;
                                case 'ppt': case 'pptx': $icon = 'fa-file-powerpoint'; $color = 'text-orange-500'; break;
                                case 'zip': $icon = 'fa-file-archive'; $color = 'text-purple-500'; break;
                            }
                    ?>
                        <tr class="hover:bg-yellow-50/50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 border-2 border-black bg-white flex items-center justify-center text-xl <?php echo $color; ?>">
                                        <i class="fas <?php echo $icon; ?>"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black uppercase leading-tight"><?php echo e($row['title']); ?></p>
                                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-0.5 line-clamp-1"><?php echo e($row['description']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="bg-black text-white text-[10px] font-black uppercase tracking-widest px-2 py-1">
                                    <?php echo e($row['course_code']); ?>
                                </span>
                                <span class="block text-[10px] font-bold uppercase tracking-widest mt-1">Sec <?php echo e($row['section']); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-bold uppercase bg-gray-100 border border-gray-300 px-2 py-1 rounded-sm">
                                    <?php echo strtoupper($ext); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-xs font-bold uppercase"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></p>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?php echo BASE_URL . '/uploads/materials/' . e($row['file_path']); ?>" download class="w-8 h-8 border-2 border-black flex items-center justify-center text-black hover:bg-black hover:text-white transition-colors" title="Download">
                                        <i class="fas fa-download text-xs"></i>
                                    </a>
                                    <form method="POST" class="inline" onsubmit="return confirm('Delete this file?');">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="delete_material" value="1">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="w-8 h-8 border-2 border-black flex items-center justify-center text-red-600 hover:bg-red-600 hover:text-white transition-colors" title="Delete">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500 font-bold uppercase tracking-widest">
                            <div class="w-16 h-16 bg-gray-100 border-2 border-black flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-folder-open text-xl text-gray-400"></i>
                            </div>
                            No materials uploaded yet.
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
