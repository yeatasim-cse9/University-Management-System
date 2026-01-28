<?php
/**
 * Student Attendance
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('student');

$page_title = 'Attendance';
$user_id = get_current_user_id();

// Get student info
$stmt = $db->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$student_id = $student['id'];

// Get overall stats
$summary = [];
$sum_q = "SELECT 
    co.id, c.course_code, c.course_name,
    COUNT(a.id) as total_records,
    SUM(CASE WHEN a.status IN ('present', 'late') THEN 1 ELSE 0 END) as present,
    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent,
    SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END) as excused
    FROM enrollments e 
    JOIN course_offerings co ON e.course_offering_id = co.id 
    JOIN courses c ON co.course_id = c.id 
    LEFT JOIN attendance a ON e.id = a.enrollment_id 
    WHERE e.student_id = $student_id AND e.status = 'enrolled'
    GROUP BY co.id, c.id 
    ORDER BY c.course_code";
$sum_res = $db->query($sum_q);

$total_present = 0;
$total_classes = 0;

while ($row = $sum_res->fetch_assoc()) {
    $total = $row['total_records'];
    $row['percentage'] = $total > 0 ? round(($row['present'] / $total) * 100, 1) : 100;
    $summary[] = $row;
    
    $total_present += $row['present'];
    $total_classes += $total;
}

$global_pct = $total_classes > 0 ? round(($total_present / $total_classes) * 100, 1) : 100;

// Get History if course selected
$course_id = $_GET['course_id'] ?? null;
$history = [];
if ($course_id) {
    $hist_q = "SELECT a.* 
        FROM attendance a
        JOIN enrollments e ON a.enrollment_id = e.id
        WHERE e.student_id = $student_id AND e.course_offering_id = $course_id
        ORDER BY a.attendance_date DESC";
    $hist_res = $db->query($hist_q);
    while ($row = $hist_res->fetch_assoc()) $history[] = $row;
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
                My Attendance
            </h1>
            <p class="text-sm font-bold uppercase tracking-widest text-gray-600">
                Track your class presence
            </p>
        </div>
        
        <div class="flex items-center gap-4">
            <div class="os-card p-2 flex items-center gap-3">
                <div class="w-10 h-10 <?php echo $global_pct < 75 ? 'bg-red-500' : 'bg-emerald-500'; ?> text-white flex items-center justify-center font-black border-2 border-black">
                    <i class="fas fa-percent"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-500">Overall Rate</p>
                    <p class="text-lg font-black leading-none"><?php echo $global_pct; ?>%</p>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($global_pct < 75): ?>
        <div class="os-card bg-red-100 border-l-8 border-l-black p-6 flex flex-col md:flex-row items-center gap-6">
            <div class="w-16 h-16 bg-red-500 text-white flex items-center justify-center font-black text-2xl border-2 border-black shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div>
                <h3 class="text-xl font-black uppercase tracking-tighter text-red-900">Attendance Warning</h3>
                <p class="text-sm font-bold uppercase tracking-widest text-red-700 mt-1">
                    Your overall attendance is below 75%. You must attend upcoming classes to be eligible for exams.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Course List -->
        <div class="lg:col-span-2 space-y-6">
            <div class="os-card overflow-hidden">
                <div class="p-6 border-b-2 border-black bg-black text-white">
                    <h3 class="text-lg font-black uppercase tracking-tighter">Course Breakdown</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-100 border-b-2 border-black">
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-gray-600">Course</th>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-gray-600">Rate</th>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-gray-600 text-center">Summary</th>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-right text-gray-600">History</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y-2 divide-black">
                            <?php foreach ($summary as $s): 
                                $is_low = $s['percentage'] < 75;
                            ?>
                                <tr class="hover:bg-yellow-50/50 transition-colors group <?php echo $course_id == $s['id'] ? 'bg-yellow-100' : ''; ?>">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 border-2 border-black flex items-center justify-center font-black text-xs bg-white">
                                                <?php echo substr($s['course_code'], 0, 3); ?>
                                            </div>
                                            <div>
                                                <p class="text-sm font-black uppercase leading-tight"><?php echo e($s['course_code']); ?></p>
                                                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-0.5 line-clamp-1"><?php echo e($s['course_name']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg font-black <?php echo $is_low ? 'text-red-600' : 'text-emerald-600'; ?>"><?php echo $s['percentage']; ?>%</span>
                                            <?php if($is_low): ?>
                                                <i class="fas fa-exclamation-circle text-red-500 text-xs" title="Low Attendance"></i>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-4 text-xs font-bold uppercase text-gray-600">
                                            <div class="text-center" title="Present">
                                                <span class="block text-emerald-600 font-black"><?php echo $s['present']; ?></span>
                                                <span class="text-[9px]">P</span>
                                            </div>
                                            <div class="text-center" title="Absent">
                                                <span class="block text-red-600 font-black"><?php echo $s['absent']; ?></span>
                                                <span class="text-[9px]">A</span>
                                            </div>
                                            <div class="text-center" title="Excused">
                                                <span class="block text-amber-600 font-black"><?php echo $s['excused']; ?></span>
                                                <span class="text-[9px]">L/E</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="?course_id=<?php echo $s['id']; ?>" class="inline-flex items-center justify-center w-8 h-8 border-2 border-black text-black hover:bg-black hover:text-white transition-colors" title="View History">
                                            <i class="fas fa-list"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Attendance History -->
        <div class="lg:col-span-1">
            <div class="os-card h-full flex flex-col">
                <div class="p-6 border-b-2 border-black flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-black uppercase tracking-tighter">Detailed Logs</h3>
                        <p class="text-xs font-bold uppercase tracking-widest text-gray-500">
                            <?php 
                            if ($course_id) {
                                foreach($summary as $s) if ($s['id'] == $course_id) echo $s['course_code']; 
                            } else {
                                echo "Select a course";
                            }
                            ?>
                        </p>
                    </div>
                    <?php if ($course_id): ?>
                        <a href="attendance.php" class="text-xs font-black uppercase underline hover:text-red-600">Clear</a>
                    <?php endif; ?>
                </div>

                <div class="flex-1 overflow-y-auto max-h-[600px] p-0">
                    <?php if (!$course_id): ?>
                        <div class="h-full flex flex-col items-center justify-center p-8 text-center text-gray-400">
                            <i class="fas fa-arrow-left text-4xl mb-4"></i>
                            <p class="text-xs font-black uppercase tracking-widest">Select a course from the list to view detailed attendance history.</p>
                        </div>
                    <?php elseif (empty($history)): ?>
                         <div class="h-full flex flex-col items-center justify-center p-8 text-center text-gray-400">
                            <i class="fas fa-calendar-times text-4xl mb-4"></i>
                            <p class="text-xs font-black uppercase tracking-widest">No attendance records found for this course.</p>
                        </div>
                    <?php else: ?>
                        <div class="divide-y-2 divide-black">
                            <?php foreach ($history as $h): 
                                $st = match($h['status']) {
                                    'present' => ['bg' => 'bg-emerald-400', 'txt' => 'text-emerald-900', 'icon' => 'fa-check'],
                                    'absent' => ['bg' => 'bg-red-400', 'txt' => 'text-red-900', 'icon' => 'fa-times'],
                                    'late' => ['bg' => 'bg-amber-400', 'txt' => 'text-amber-900', 'icon' => 'fa-clock'],
                                    'excused' => ['bg' => 'bg-blue-400', 'txt' => 'text-blue-900', 'icon' => 'fa-paperclip'],
                                    default => ['bg' => 'bg-gray-200', 'txt' => 'text-gray-600', 'icon' => 'fa-question']
                                };
                            ?>
                                <div class="p-4 flex items-center gap-4 hover:bg-gray-50">
                                    <div class="w-12 h-12 <?php echo $st['bg']; ?> <?php echo $st['txt']; ?> border-2 border-black flex items-center justify-center text-lg shrink-0">
                                        <i class="fas <?php echo $st['icon']; ?>"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-black uppercase"><?php echo date('M d, Y', strtotime($h['attendance_date'])); ?></p>
                                        <div class="flex items-center justify-between mt-1">
                                            <span class="text-[10px] font-bold uppercase tracking-widest bg-black text-white px-2 py-0.5"><?php echo ucfirst($h['status']); ?></span>
                                            <?php if ($h['remarks']): ?>
                                                <span class="text-[10px] font-bold text-gray-500 uppercase truncate max-w-[120px]" title="<?php echo e($h['remarks']); ?>"><?php echo e($h['remarks']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
