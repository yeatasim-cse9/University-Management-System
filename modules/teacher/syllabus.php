<?php
/**
 * Teacher Syllabus Management
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('teacher');

$page_title = 'Syllabus Management';
$user_id = get_current_user_id();

// Get teacher info
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$teacher_id = $teacher['id'];

$action = $_GET['action'] ?? 'list';
$course_offering_id = $_GET['course_id'] ?? null;

// Handle CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid Request');
    } else {
        $post_action = $_POST['action'] ?? '';
        $course_offering_id = intval($_POST['course_offering_id'] ?? 0);

        if ($course_offering_id) {
            // Verify access
            $access_chk = $db->query("SELECT id FROM teacher_courses WHERE teacher_id = $teacher_id AND course_offering_id = $course_offering_id");
            if ($access_chk->num_rows > 0) {
                if ($post_action === 'create_topic') {
                    $topic = sanitize_input($_POST['topic_name']);
                    $desc = sanitize_input($_POST['description']);
                    $stmt = $db->prepare("INSERT INTO syllabus_topics (course_offering_id, topic_name, description, sort_order) VALUES (?, ?, ?, 0)");
                    $stmt->bind_param("iss", $course_offering_id, $topic, $desc);
                    if ($stmt->execute()) set_flash('success', 'Topic added to syllabus');
                } elseif ($post_action === 'toggle_status') {
                    $topic_id = intval($_POST['topic_id']);
                    $status = $_POST['status'] === 'completed' ? 'pending' : 'completed'; // Toggle
                    $completed_at = $status === 'completed' ? date('Y-m-d H:i:s') : null;
                    
                    $stmt = $db->prepare("UPDATE syllabus_topics SET status = ?, completed_at = ? WHERE id = ? AND course_offering_id = ?");
                    $stmt->bind_param("ssii", $status, $completed_at, $topic_id, $course_offering_id);
                    $stmt->execute();
                    // AJAX response could be here, but using refresh for MVP
                } elseif ($post_action === 'delete_topic') {
                    $topic_id = intval($_POST['topic_id']);
                    $db->query("DELETE FROM syllabus_topics WHERE id = $topic_id AND course_offering_id = $course_offering_id");
                    set_flash('success', 'Topic deleted');
                }
            } else {
                set_flash('error', 'Access Denied to this course.');
            }
        }
        redirect("syllabus.php?action=manage&course_id=$course_offering_id");
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
    <div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white">
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
                <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Curriculum</span>
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                    <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                    Syllabus Tracker
                </span>
            </div>
            <h1 class="text-3xl font-black uppercase tracking-tighter">Syllabus <span class="text-black">Manager</span></h1>
        </div>
    </div>

    <?php if ($action === 'list'): 
        // Get Teacher's Courses
        $courses = [];
        $res = $db->query("SELECT co.id, c.course_code, c.course_name, co.section, 
            (SELECT COUNT(*) FROM syllabus_topics WHERE course_offering_id = co.id) as total_topics,
            (SELECT COUNT(*) FROM syllabus_topics WHERE course_offering_id = co.id AND status = 'completed') as completed_topics
            FROM course_offerings co
            JOIN courses c ON co.course_id = c.id
            JOIN teacher_courses tc ON co.id = tc.course_offering_id
            WHERE tc.teacher_id = $teacher_id AND co.status = 'open'");
    ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while($c = $res->fetch_assoc()): 
                $percent = $c['total_topics'] > 0 ? round(($c['completed_topics'] / $c['total_topics']) * 100) : 0;
            ?>
                <a href="?action=manage&course_id=<?php echo $c['id']; ?>" class="os-card p-0 bg-white hover:-translate-y-1 hover:shadow-[8px_8px_0px_#000000] transition-all group">
                    <div class="p-6 border-b-2 border-black bg-black text-white">
                        <div class="flex justify-between items-start mb-2">
                            <span class="px-2 py-1 bg-white text-black text-[10px] font-black uppercase tracking-widest border border-white"><?php echo $c['course_code']; ?></span>
                            <span class="text-[10px] font-black text-white uppercase tracking-widest border border-white px-2 py-1">Sec <?php echo $c['section']; ?></span>
                        </div>
                        <h3 class="text-xl font-black uppercase tracking-tight group-hover:text-yellow-400 transition-colors"><?php echo $c['course_name']; ?></h3>
                    </div>
                    
                    <div class="p-6 space-y-4">
                        <div class="space-y-2">
                            <div class="flex justify-between text-[10px] font-black uppercase tracking-widest">
                                <span class="text-black">Progress Status</span>
                                <span class="text-black bg-yellow-400 px-2 border border-black"><?php echo $percent; ?>%</span>
                            </div>
                            <div class="h-4 bg-white border-2 border-black p-0.5">
                                <div class="h-full bg-black transition-all duration-1000" style="width: <?php echo $percent; ?>%"></div>
                            </div>
                            <p class="text-[9px] text-right font-black text-slate-500 uppercase tracking-widest mt-1"><?php echo $c['completed_topics']; ?> / <?php echo $c['total_topics']; ?> Topics Completed</p>
                        </div>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>

    <?php elseif ($action === 'manage' && $course_offering_id): 
        // Get topics
        $topics = [];
        $t_res = $db->query("SELECT * FROM syllabus_topics WHERE course_offering_id = $course_offering_id ORDER BY sort_order ASC, created_at ASC");
        
        $c_info = $db->query("SELECT c.course_code, c.course_name, co.section FROM course_offerings co JOIN courses c ON co.course_id = c.id WHERE co.id = $course_offering_id")->fetch_assoc();
    ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Add Topic Form -->
            <div class="lg:col-span-1">
                <div class="os-card p-0 bg-white sticky top-8">
                    <div class="bg-black p-6 text-white border-b-2 border-black flex items-center gap-4">
                        <div class="w-10 h-10 bg-white text-black flex items-center justify-center border-2 border-white">
                            <i class="fas fa-plus text-lg"></i>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Expansion Protocol</p>
                            <h3 class="text-lg font-black uppercase tracking-tighter">Add Topic</h3>
                        </div>
                    </div>

                    <div class="p-6">
                        <form method="POST" class="space-y-6">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="create_topic">
                            <input type="hidden" name="course_offering_id" value="<?php echo $course_offering_id; ?>">
                            
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Topic Name</label>
                                <input type="text" name="topic_name" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" placeholder="ENTER TOPIC TITLE..." required>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Description (Optional)</label>
                                <textarea name="description" rows="3" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" placeholder="BRIEF DESCRIPTION..."></textarea>
                            </div>
                            
                            <button type="submit" class="w-full btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black hover:border-black flex items-center justify-center gap-2">
                                 <i class="fas fa-plus-circle"></i> Add to Syllabus
                            </button>
                        </form>
                        <div class="mt-6 pt-6 border-t-2 border-black">
                            <a href="syllabus.php" class="w-full btn-os bg-white text-black border-black hover:bg-black hover:text-white flex items-center justify-center gap-2">
                                <i class="fas fa-arrow-left"></i> Back to Courses
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Topic List -->
            <div class="lg:col-span-2 space-y-6">
                 <div class="flex items-center justify-between mb-4 border-b-2 border-black pb-4">
                    <div>
                        <h2 class="text-2xl font-black text-black uppercase tracking-tighter"><?php echo $c_info['course_code']; ?> Syllabus</h2>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Section <?php echo $c_info['section']; ?> - <?php echo $c_info['course_name']; ?></p>
                    </div>
                </div>

                <?php if ($t_res->num_rows > 0): 
                    while($t = $t_res->fetch_assoc()):
                        $is_done = $t['status'] === 'completed';
                ?>
                    <div class="os-card p-0 bg-white group <?php echo $is_done ? 'opacity-75' : ''; ?>">
                        <div class="p-6 flex items-start gap-6">
                            <form method="POST" class="mt-1">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="course_offering_id" value="<?php echo $course_offering_id; ?>">
                                <input type="hidden" name="topic_id" value="<?php echo $t['id']; ?>">
                                <input type="hidden" name="status" value="<?php echo $t['status']; ?>">
                                <button type="submit" class="w-8 h-8 flex items-center justify-center border-2 border-black <?php echo $is_done ? 'bg-black text-yellow-400' : 'bg-white hover:bg-black hover:text-white'; ?> transition-all shadow-[2px_2px_0px_#000]">
                                    <?php if ($is_done): ?>
                                        <i class="fas fa-check text-xs"></i>
                                    <?php else: ?>
                                        <span class="w-full h-full block"></span>
                                    <?php endif; ?>
                                </button>
                            </form>
                            
                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <h4 class="text-lg font-black text-black uppercase tracking-tight <?php echo $is_done ? 'line-through text-slate-400' : ''; ?>"><?php echo e($t['topic_name']); ?></h4>
                                    <form method="POST" onsubmit="return confirm('Initiate deletion protocol?')">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete_topic">
                                        <input type="hidden" name="course_offering_id" value="<?php echo $course_offering_id; ?>">
                                        <input type="hidden" name="topic_id" value="<?php echo $t['id']; ?>">
                                        <button class="w-8 h-8 flex items-center justify-center border-2 border-black bg-white hover:bg-red-600 hover:text-white transition-all shadow-[2px_2px_0px_#000]">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                                <?php if ($t['description']): ?>
                                    <p class="text-xs font-bold text-slate-600 mt-2 font-mono uppercase <?php echo $is_done ? 'text-slate-300' : ''; ?>"><?php echo e($t['description']); ?></p>
                                <?php endif; ?>
                                <?php if ($is_done): ?>
                                    <div class="mt-3 inline-block px-2 py-1 bg-black text-white text-[9px] font-black uppercase tracking-widest border border-black">
                                        <i class="fas fa-check-double mr-1 text-yellow-400"></i> Completed <?php echo date('M d', strtotime($t['completed_at'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; else: ?>
                    <div class="os-card p-12 text-center bg-slate-50 border-dashed">
                        <i class="fas fa-clipboard-list text-4xl text-slate-300 mb-4"></i>
                        <p class="text-xs font-black text-slate-400 uppercase tracking-widest">No curriculum topics detected.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
