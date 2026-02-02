<?php
/**
 * Admin Course Progress Monitoring
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('admin');

$page_title = 'Course Progress';
$user_id = get_current_user_id();

// Get Admin Dept
$stmt = $db->prepare("SELECT department_id FROM department_admins WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$dept = $stmt->get_result()->fetch_assoc();
$dept_id = $dept['department_id'];

// Get Courses with Syllabus Stats
$q = "SELECT co.id, c.course_code, c.course_name, co.section, u.username as teacher_name, up.first_name, up.last_name,
    (SELECT COUNT(*) FROM syllabus_topics WHERE course_offering_id = co.id) as total_topics,
    (SELECT COUNT(*) FROM syllabus_topics WHERE course_offering_id = co.id AND status = 'completed') as completed_topics
    FROM course_offerings co
    JOIN courses c ON co.course_id = c.id
    LEFT JOIN teacher_courses tc ON co.id = tc.course_offering_id
    LEFT JOIN teachers t ON tc.teacher_id = t.id
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE c.department_id = $dept_id AND co.status = 'open'
    ORDER BY c.course_code ASC";
    
$res = $db->query($q);

// Sidebar
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<div class="space-y-8">
    <!-- Header -->
    <div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white">
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
                <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Department</span>
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                    <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                    Analytics
                </span>
            </div>
            <h1 class="text-3xl font-black uppercase tracking-tighter">Course <span class="text-black">Progress</span></h1>
        </div>
        
        <div class="flex items-center gap-4 relative z-10">
             <div class="w-10 h-10 bg-white border-2 border-black flex items-center justify-center text-black text-lg shadow-[2px_2px_0px_#000]">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>

    <div class="os-card p-0 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-black text-white text-[10px] font-black uppercase tracking-widest border-b-2 border-black">
                        <th class="px-6 py-4">Course</th>
                        <th class="px-6 py-4">Instructor</th>
                        <th class="px-6 py-4">Syllabus Status</th>
                        <th class="px-6 py-4 text-right">Progress</th>
                    </tr>
                </thead>
                <tbody class="divide-y-2 divide-black">
                    <?php if ($res->num_rows > 0): ?>
                        <?php while($row = $res->fetch_assoc()): 
                            $percent = $row['total_topics'] > 0 ? round(($row['completed_topics'] / $row['total_topics']) * 100) : 0;
                            $progress_color = $percent >= 100 ? 'bg-green-500' : ($percent >= 50 ? 'bg-blue-600' : 'bg-yellow-400');
                        ?>
                            <tr class="hover:bg-yellow-50 transition-all group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 flex items-center justify-center border-2 border-black bg-white text-black text-[10px] font-black">
                                            <?php echo substr($row['course_code'], 0, 3); ?>
                                        </div>
                                        <div>
                                            <p class="text-xs font-black text-black uppercase decoration-clone"><?php echo $row['course_name']; ?></p>
                                            <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest"><?php echo $row['course_code']; ?> • Sec <?php echo $row['section']; ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                     <p class="text-xs font-black text-black uppercase italic"><?php echo $row['first_name'] ? $row['first_name'] . ' ' . $row['last_name'] : $row['teacher_name']; ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-black text-black"><?php echo $percent; ?>%</span>
                                        <div class="w-32 h-2 bg-white border border-black p-0.5">
                                            <div class="h-full <?php echo $progress_color; ?>" style="width: <?php echo $percent; ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="px-2 py-1 bg-white border border-black text-black rounded-none text-[9px] font-black uppercase tracking-widest">
                                        <?php echo $row['completed_topics']; ?> / <?php echo $row['total_topics']; ?> Topics
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                         <tr><td colspan="4" class="px-6 py-12 text-center text-slate-400 italic text-[10px] font-bold uppercase tracking-widest">No active courses found.</td></tr>
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
