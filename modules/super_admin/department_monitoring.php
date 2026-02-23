<?php
/**
 * Super Admin Department Monitoring
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('super_admin');

$page_title = 'Department Monitoring';

// Get Departments with Aggregate Progress
$q = "SELECT d.id, d.name, d.code,
    COUNT(DISTINCT co.id) as active_courses,
    SUM(CASE WHEN st.status = 'completed' THEN 1 ELSE 0 END) as total_completed_topics,
    COUNT(st.id) as total_topics
    FROM departments d
    LEFT JOIN courses c ON d.id = c.department_id
    LEFT JOIN course_offerings co ON c.id = co.course_id AND co.status = 'open'
    LEFT JOIN syllabus_topics st ON co.id = st.course_offering_id
    WHERE d.status = 'active'
    GROUP BY d.id
    ORDER BY d.name";

$res = $db->query($q);

// Sidebar
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<!-- Header -->
<div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white mb-6">
    <div class="relative z-10">
        <div class="flex items-center gap-3 mb-2">
            <span class="px-2 py-1 bg-indigo-600 text-white text-[10px] font-black uppercase tracking-widest border border-black">Surveillance</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-indigo-500 inline-block mr-1"></span>
                Academic Progress Matrix
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">Department <span class="text-indigo-600">Monitoring</span></h1>
    </div>
    
    <div class="flex items-center gap-4 relative z-10">
        <div class="w-10 h-10 bg-black text-white flex items-center justify-center border-2 border-black shadow-[2px_2px_0px_#000000]">
            <i class="fas fa-chart-pie text-lg animate-pulse"></i>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
    <?php if ($res->num_rows > 0): ?>
        <?php while($row = $res->fetch_assoc()): 
            $percent = $row['total_topics'] > 0 ? round(($row['total_completed_topics'] / $row['total_topics']) * 100) : 0;
            
            // Determine status color based on completion
            $status_color = 'bg-slate-200';
            $bar_color = 'bg-black';
            if ($percent >= 80) {
                $status_color = 'bg-green-500';
                $bar_color = 'bg-green-600';
            } elseif ($percent >= 50) {
                $status_color = 'bg-yellow-400';
                $bar_color = 'bg-yellow-500';
            } elseif ($percent > 0) {
                $status_color = 'bg-blue-400';
                $bar_color = 'bg-blue-500';
            }
        ?>
            <div class="os-card p-0 flex flex-col bg-white hover:-translate-y-1 hover:shadow-[6px_6px_0px_#000000] transition-all duration-300 group">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-10 h-10 bg-black text-white flex items-center justify-center border-2 border-black font-black text-xs">
                            <?php echo $row['code']; ?>
                        </div>
                        <div class="flex flex-col items-end">
                            <span class="text-3xl font-black italic <?php echo str_replace('bg-', 'text-', $bar_color); ?> leading-none"><?php echo $percent; ?>%</span>
                            <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 mt-1">Completion</span>
                        </div>
                    </div>
                    
                    <h3 class="text-xl font-black uppercase italic leading-tight mb-2 pr-4 min-h-[3.5rem] flex items-center"><?php echo $row['name']; ?></h3>
                    
                    <div class="w-full h-4 bg-slate-100 border-2 border-black mb-6 relative">
                        <div class="h-full <?php echo $bar_color; ?> border-r-2 border-black transition-all duration-1000" style="width: <?php echo $percent; ?>%"></div>
                        <!-- Tick marks -->
                        <div class="absolute top-0 left-1/4 w-px h-full bg-black/20"></div>
                        <div class="absolute top-0 left-2/4 w-px h-full bg-black/20"></div>
                        <div class="absolute top-0 left-3/4 w-px h-full bg-black/20"></div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 border-2 border-black p-2 text-center">
                            <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest">Active Courses</p>
                            <p class="text-lg font-black text-black"><?php echo $row['active_courses']; ?></p>
                        </div>
                        <div class="bg-slate-50 border-2 border-black p-2 text-center">
                            <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest">Topics Done</p>
                            <p class="text-lg font-black text-black"><?php echo $row['total_completed_topics']; ?> <span class="text-xs text-slate-400">/ <?php echo $row['total_topics']; ?></span></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-span-full py-20 text-center bg-white border-2 border-dashed border-black">
             <i class="fas fa-chart-bar text-4xl text-slate-300 mb-4"></i>
             <p class="text-slate-400 font-black uppercase tracking-widest italic text-xs">No active department data found.</p>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
