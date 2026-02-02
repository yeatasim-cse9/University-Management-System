<?php
/**
 * Student Syllabus View
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('student');

$page_title = 'My Syllabus';
$user_id = get_current_user_id();

// Get student info
$stmt = $db->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$student_id = $student['id'];

$action = $_GET['action'] ?? 'list';
$course_offering_id = $_GET['course_id'] ?? null;

// Sidebar
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<div class="space-y-8 animate-in">
    <!-- Header -->
    <div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white">
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
                <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Curriculum</span>
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                    <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                    Coverage
                </span>
            </div>
            <h1 class="text-3xl font-black uppercase tracking-tighter">Course <span class="text-transparent bg-clip-text bg-gradient-to-r from-black to-slate-500">Syllabus</span></h1>
        </div>
         <div class="flex items-center gap-4 relative z-10">
            <div class="w-10 h-10 bg-white border-2 border-black flex items-center justify-center text-black text-lg shadow-[2px_2px_0px_#000]">
                <i class="fas fa-list-alt"></i>
            </div>
        </div>
    </div>

    <?php if ($action === 'list'): 
        // Get Student's Courses
        $courses = [];
        $res = $db->query("SELECT co.id, c.course_code, c.course_name, co.section, 
            (SELECT COUNT(*) FROM syllabus_topics WHERE course_offering_id = co.id) as total_topics,
            (SELECT COUNT(*) FROM syllabus_topics WHERE course_offering_id = co.id AND status = 'completed') as completed_topics
            FROM enrollments e
            JOIN course_offerings co ON e.course_offering_id = co.id
            JOIN courses c ON co.course_id = c.id
            WHERE e.student_id = $student_id AND e.status = 'enrolled'");
    ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while($c = $res->fetch_assoc()): 
                $percent = $c['total_topics'] > 0 ? round(($c['completed_topics'] / $c['total_topics']) * 100) : 0;
            ?>
                <a href="?action=view&course_id=<?php echo $c['id']; ?>" class="os-card p-0 bg-white hover:-translate-y-1 transition-transform group flex flex-col h-full">
                    <div class="p-6 pb-4 border-b-2 border-black bg-slate-50 group-hover:bg-yellow-400 transition-colors">
                        <div class="flex justify-between items-start mb-2">
                             <span class="bg-black text-white text-[10px] font-black uppercase px-2 py-1 tracking-widest"><?php echo $c['course_code']; ?></span>
                             <span class="text-[10px] font-black uppercase tracking-widest border border-black px-1 bg-white">Sec <?php echo $c['section']; ?></span>
                        </div>
                        <h3 class="text-xl font-black uppercase leading-tight line-clamp-2 min-h-[3.5rem] mt-2 group-hover:text-black"><?php echo $c['course_name']; ?></h3>
                    </div>
                    
                    <div class="p-6 flex-1 flex flex-col justify-end">
                        <div class="flex justify-between text-[10px] font-black uppercase tracking-widest mb-2">
                            <span class="text-slate-500">Completion</span>
                            <span class="text-black"><?php echo $percent; ?>%</span>
                        </div>
                        <div class="h-3 w-full bg-slate-100 border-2 border-black p-0.5">
                            <div class="h-full bg-black" style="width: <?php echo $percent; ?>%"></div>
                        </div>
                         <p class="text-[9px] text-right font-black text-slate-400 mt-2 uppercase tracking-widest"><?php echo $c['completed_topics']; ?>/<?php echo $c['total_topics']; ?> Topics</p>
                    </div>
                    
                    <div class="p-3 border-t-2 border-black text-center bg-white">
                        <span class="text-[10px] font-black uppercase tracking-widest group-hover:underline">View Topics</span>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>

    <?php elseif ($action === 'view' && $course_offering_id): 
        // Get topics
        $t_res = $db->query("SELECT * FROM syllabus_topics WHERE course_offering_id = $course_offering_id ORDER BY sort_order ASC, created_at ASC");
        $c_info = $db->query("SELECT c.course_code, c.course_name, co.section FROM course_offerings co JOIN courses c ON co.course_id = c.id WHERE co.id = $course_offering_id")->fetch_assoc();
        
        // Calculate progress
        $total = $t_res->num_rows;
        $completed = 0;
        $topics = [];
        while($row = $t_res->fetch_assoc()) {
            $topics[] = $row;
            if ($row['status'] === 'completed') $completed++;
        }
        $percent = $total > 0 ? round(($completed / $total) * 100) : 0;
    ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Progress Card -->
            <div class="lg:col-span-1">
                <div class="os-card p-0 bg-black text-white sticky top-6">
                    <div class="p-6 border-b-2 border-white/20">
                         <h2 class="text-2xl font-black uppercase tracking-tighter mb-1"><?php echo $c_info['course_code']; ?></h2>
                         <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Section <?php echo $c_info['section']; ?></p>
                    </div>

                    <div class="p-8 text-center">
                        <div class="inline-block relative">
                            <svg class="w-32 h-32 transform -rotate-90">
                                <circle cx="64" cy="64" r="60" stroke="currentColor" stroke-width="8" fill="transparent" class="text-slate-800" />
                                <circle cx="64" cy="64" r="60" stroke="currentColor" stroke-width="8" fill="transparent" stroke-dasharray="377" stroke-dashoffset="<?php echo 377 - (377 * $percent / 100); ?>" class="text-yellow-400" />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center flex-col">
                                <span class="text-3xl font-black text-white"><?php echo $percent; ?>%</span>
                            </div>
                        </div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mt-4">Syllabus Covered</p>
                    </div>
                    
                    <div class="p-6 border-t-2 border-white/20">
                         <div class="flex justify-between text-[10px] font-black uppercase tracking-widest mb-2 text-slate-400">
                            <span>Topics</span>
                            <span class="text-white"><?php echo $completed; ?> / <?php echo $total; ?></span>
                        </div>
                         <a href="syllabus.php" class="btn-os w-full bg-white text-black hover:bg-yellow-400 border-transparent mt-4">
                            <i class="fas fa-arrow-left"></i> Back to Courses
                        </a>
                    </div>
                </div>
            </div>

            <!-- Topic List -->
            <div class="lg:col-span-2 space-y-4">
                 <?php if (!empty($topics)): 
                    foreach($topics as $t):
                        $is_done = $t['status'] === 'completed';
                ?>
                    <div class="os-card p-6 bg-white flex items-start gap-4 hover:translate-x-1 transition-transform group <?php echo $is_done ? 'border-emerald-500' : 'border-black'; ?>">
                        <div class="w-8 h-8 flex-shrink-0 border-2 <?php echo $is_done ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-black bg-white text-white'; ?> flex items-center justify-center">
                             <?php if ($is_done): ?>
                                <i class="fas fa-check text-xs"></i>
                             <?php endif; ?>
                        </div>
                        
                        <div class="flex-1">
                            <h4 class="text-sm font-black text-black uppercase leading-tight <?php echo $is_done ? 'line-through text-slate-400' : ''; ?>"><?php echo e($t['topic_name']); ?></h4>
                            <?php if ($t['description']): ?>
                                <p class="text-xs font-mono text-slate-500 mt-2 leading-relaxed uppercase"><?php echo e($t['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="mt-3">
                                <?php if ($is_done): ?>
                                    <span class="inline-block px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[9px] font-black uppercase tracking-widest border border-emerald-300">
                                        Completed
                                    </span>
                                <?php else: ?>
                                    <span class="inline-block px-2 py-0.5 bg-slate-100 text-slate-500 text-[9px] font-black uppercase tracking-widest border border-slate-300">
                                        Pending Class
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; else: ?>
                    <div class="os-card p-12 text-center border-dashed">
                        <i class="fas fa-clipboard-list text-4xl text-slate-300 mb-4"></i>
                        <p class="text-xs font-black uppercase tracking-widest text-slate-400">No topics published yet.</p>
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
