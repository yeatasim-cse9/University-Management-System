<?php
/**
 * Student Performance Dashboard (Super Admin View)
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/components/PerformanceMetrics.php';

require_role('super_admin');

$user_id = get_current_user_id();
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

if (!$student_id) {
    set_flash('error', 'Invalid student ID');
    redirect(BASE_URL . '/modules/super_admin/users.php');
}

// Get Student Details with Profile
$stmt_std = $db->prepare("
    SELECT s.*, up.first_name, up.last_name, up.profile_picture, u.email, d.name as dept_name, s.student_id as matric_no
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.id = ?
");
$stmt_std->bind_param("i", $student_id);
$stmt_std->execute();
$student = $stmt_std->get_result()->fetch_assoc();

if (!$student) {
    set_flash('error', 'Student not found');
    redirect(BASE_URL . '/modules/super_admin/users.php');
}

// Handle Review Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_text'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Security token invalid');
    } else {
        $review = sanitize_input($_POST['review_text']);
        if (!empty($review)) {
            $stmt_ins = $db->prepare("INSERT INTO student_performance_reviews (student_id, reviewer_id, review_text) VALUES (?, ?, ?)");
            $stmt_ins->bind_param("iis", $student_id, $user_id, $review);
            if ($stmt_ins->execute()) {
                set_flash('success', 'Performance review added');
            } else {
                set_flash('error', 'Failed to add review');
            }
        }
    }
    redirect("?student_id=$student_id");
}

// Calculate Metrics
$metrics = new PerformanceMetrics($db, $student_id);
$cgpa = $metrics->getCGPA();
$attendance = $metrics->getAttendanceStats();
$assignments = $metrics->getAssignmentStats();
$radar_data = $metrics->getSubjectPerformance();
$trend_data = $metrics->getPerformanceTrend();
$reviews = $metrics->getReviews();

$page_title = "Performance: " . $student['first_name'] . " " . $student['last_name'];

// START OUTPUT
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<!-- Performance Dashboard UI (Super Admin View) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-6">
    
    <!-- Hero Profile Section -->
    <div class="os-card p-0 overflow-hidden bg-white mb-6">
        <div class="bg-black p-6 flex flex-col md:flex-row items-center gap-8 relative overflow-hidden border-b-2 border-black">
            <!-- Breadcrumb -->
            <div class="absolute top-4 left-6 z-20">
                <a href="<?php echo BASE_URL; ?>/modules/super_admin/users.php" class="text-white text-[10px] font-black uppercase tracking-widest hover:text-yellow-400 transition-colors flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i> Directory
                </a>
            </div>

            <!-- Avatar -->
            <div class="relative z-10 mt-6 md:mt-0">
                <div class="w-32 h-32 bg-white p-1 border-2 border-white shadow-[4px_4px_0px_#ffffff50]">
                    <div class="w-full h-full bg-slate-200 overflow-hidden flex items-center justify-center">
                            <?php if ($student['profile_picture']): ?>
                            <img src="<?php echo ASSETS_URL . '/uploads/profiles/' . $student['profile_picture']; ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="text-4xl font-black text-black italic"><?php echo substr($student['first_name'], 0, 1); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Info -->
            <div class="text-center md:text-left flex-1 relative z-10 text-white">
                <h1 class="text-4xl md:text-5xl font-black italic tracking-tighter uppercase mb-2">
                    <?php echo e($student['first_name']); ?> <span class="text-yellow-400"><?php echo e($student['last_name']); ?></span>
                </h1>
                <div class="flex flex-wrap justify-center md:justify-start gap-4 text-xs font-bold uppercase tracking-widest">
                    <span class="bg-white text-black px-2 py-1 font-black transform skew-x-[-12deg] border border-black shadow-[2px_2px_0px_#888888]">
                        <span class="transform skew-x-[12deg] inline-block"><?php echo e($student['matric_no']); ?></span>
                    </span>
                    <span class="flex items-center gap-2">
                        <i class="fas fa-layer-group text-yellow-400"></i> <?php echo e($student['dept_name']); ?>
                    </span>
                    <span class="flex items-center gap-2">
                        <i class="fas fa-clock text-yellow-400"></i> Batch <?php echo e($student['batch_year']); ?>
                    </span>
                </div>
            </div>

            <!-- Custom Action -->
             <button onclick="document.getElementById('reviewModal').classList.remove('hidden')" class="btn-os bg-yellow-400 text-black border-white hover:bg-white hover:text-black mt-4 md:mt-0 relative z-10 shrink-0">
                <i class="fas fa-plus-circle mr-2"></i> Add Review
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- CGPA Card -->
        <div class="os-card p-6 bg-white flex flex-col justify-between group hover:-translate-y-1 hover:shadow-[6px_6px_0px_#000000] transition-all duration-300">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 bg-black text-white flex items-center justify-center border-2 border-black">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <span class="px-2 py-1 bg-black text-white text-[9px] font-black uppercase tracking-widest">CGPA</span>
            </div>
            <div>
                <h3 class="text-4xl font-black text-black tracking-tighter italic mb-2"><?php echo number_format($cgpa, 2); ?></h3>
                <div class="w-full bg-slate-200 h-3 border-2 border-black">
                    <div class="h-full bg-black" style="width: <?php echo ($cgpa/4)*100; ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Attendance Card -->
        <div class="os-card p-6 bg-white flex flex-col justify-between group hover:-translate-y-1 hover:shadow-[6px_6px_0px_#000000] transition-all duration-300">
             <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 bg-black text-white flex items-center justify-center border-2 border-black">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <span class="px-2 py-1 bg-green-600 text-white text-[9px] font-black uppercase tracking-widest border border-black">Present</span>
            </div>
            <div>
                <h3 class="text-4xl font-black text-black tracking-tighter italic mb-1"><?php echo $attendance['percentage']; ?><span class="text-xl text-green-600">%</span></h3>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest"><?php echo $attendance['present']; ?> / <?php echo $attendance['total']; ?> Classes</p>
            </div>
        </div>

        <!-- Assignments Card -->
        <div class="os-card p-6 bg-white flex flex-col justify-between group hover:-translate-y-1 hover:shadow-[6px_6px_0px_#000000] transition-all duration-300">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 bg-black text-white flex items-center justify-center border-2 border-black">
                    <i class="fas fa-tasks"></i>
                </div>
                <span class="px-2 py-1 bg-yellow-400 text-black text-[9px] font-black uppercase tracking-widest border border-black">Tasks</span>
            </div>
            <div class="flex items-end gap-3">
                <div>
                    <h3 class="text-3xl font-black text-black tracking-tighter italic"><?php echo $assignments['submitted']; ?></h3>
                     <p class="text-[9px] text-slate-500 font-bold uppercase tracking-widest">Done</p>
                </div>
                <div class="w-px h-8 bg-black mx-2"></div>
                <div>
                    <h3 class="text-xl font-black text-red-600 tracking-tighter italic"><?php echo $assignments['missed']; ?></h3>
                    <p class="text-[9px] text-slate-500 font-bold uppercase tracking-widest">Missed</p>
                </div>
            </div>
        </div>

        <!-- Department/Status Card -->
         <div class="os-card p-6 bg-black text-white flex flex-col justify-between group hover:-translate-y-1 hover:shadow-[6px_6px_0px_#888888] transition-all duration-300">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 bg-white text-black flex items-center justify-center border-2 border-white">
                    <i class="fas fa-university"></i>
                </div>
                <div class="w-3 h-3 bg-red-600 border border-white animate-pulse"></div>
            </div>
            <div>
                 <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-1">Semester</p>
                <h3 class="text-4xl font-black tracking-tighter italic">Level <?php echo e($student['current_semester']); ?></h3>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-2"><?php echo e($student['session']); ?></p>
            </div>
        </div>
    </div>

    <!-- Analytics Board -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Radar Section -->
        <div class="os-card p-6 bg-white">
            <h3 class="text-xl font-black text-black italic uppercase mb-1 border-b-2 border-black pb-2">Technical Proficiency</h3>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-6">Subject scorecard</p>
            <div class="h-80">
                <canvas id="radarChart"></canvas>
            </div>
        </div>

        <!-- Trend Section -->
        <div class="os-card p-6 bg-white">
             <h3 class="text-xl font-black text-black italic uppercase mb-1 border-b-2 border-black pb-2">Performance Trajectory</h3>
             <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-6">Historical analysis</p>
            <div class="h-80">
                <canvas id="lineChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Reviews / Feedback Loop -->
    <div class="max-w-4xl mx-auto mt-8">
        <div class="flex items-center gap-4 mb-6 p-4 bg-black text-white border-2 border-black shadow-[4px_4px_0px_#000000]">
             <div class="w-10 h-10 bg-white text-black flex items-center justify-center border-2 border-white">
                <i class="fas fa-comments"></i>
            </div>
            <div>
                <h3 class="text-xl font-black italic uppercase tracking-tight">Executive Remarks</h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Official administration logs</p>
            </div>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="os-card p-12 text-center bg-white border-2 border-dashed border-black">
                <i class="fas fa-clipboard text-slate-300 text-4xl mb-4"></i>
                <p class="text-slate-400 font-bold uppercase tracking-wider text-xs">No remarks available in the log.</p>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($reviews as $index => $rev): ?>
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 bg-white border-2 border-black flex items-center justify-center font-black text-lg">
                                <?php echo strtoupper(substr($rev['first_name'], 0, 1)); ?>
                            </div>
                            <div class="w-0.5 h-full bg-black my-2"></div>
                        </div>
                        <div class="flex-1 pb-4">
                             <div class="os-card p-6 bg-white relative">
                                <div class="flex justify-between items-start mb-4 border-b-2 border-slate-100 pb-2">
                                    <div>
                                        <h4 class="font-black text-black text-sm uppercase tracking-wide"><?php echo e($rev['first_name'] . ' ' . $rev['last_name']); ?></h4>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="px-2 py-0.5 bg-black text-white text-[9px] font-black uppercase tracking-wider"><?php echo e(ucfirst($rev['role'])); ?></span>
                                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider font-mono"><?php echo time_ago($rev['created_at']); ?></span>
                                        </div>
                                    </div>
                                    <div class="text-slate-200 text-4xl font-serif leading-none absolute top-4 right-6">"</div>
                                </div>
                                <p class="text-slate-700 leading-relaxed font-mono text-sm relative z-10"><?php echo nl2br(e($rev['review_text'])); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Super Admin Review Modal -->
<div id="reviewModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="document.getElementById('reviewModal').classList.add('hidden')"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="os-card p-0 w-full max-w-lg relative z-10 bg-white shadow-2xl animate-in zoom-in-95 duration-200">
            <div class="bg-black p-6 text-white flex justify-between items-center border-b-2 border-black">
                <div>
                    <h3 class="text-xl font-black italic uppercase tracking-tight">Executive Remark</h3>
                    <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mt-1">Add detailed assessment</p>
                </div>
                <button onclick="document.getElementById('reviewModal').classList.add('hidden')" class="text-white hover:text-red-500 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" class="p-8 space-y-6">
                <?php csrf_field(); ?>
                <div>
                    <textarea name="review_text" rows="5" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all placeholder-slate-400 font-mono resize-none" placeholder="Type your observation here..." required></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t-2 border-black">
                    <button type="button" onclick="document.getElementById('reviewModal').classList.add('hidden')" class="btn-os bg-white text-black border-black hover:bg-black hover:text-white">Cancel</button>
                    <button type="submit" class="btn-os bg-black text-white border-black hover:bg-yellow-500 hover:text-black">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Styling Defaults
    Chart.defaults.font.family = "'Courier New', monospace";
    Chart.defaults.color = '#000';
    
    // Radar Chart
    const radarCtx = document.getElementById('radarChart').getContext('2d');
    const radarData = <?php echo json_encode($radar_data); ?>;
    
    new Chart(radarCtx, {
        type: 'radar',
        data: {
            labels: radarData.map(d => d.subject),
            datasets: [{
                label: 'Score',
                data: radarData.map(d => d.score),
                backgroundColor: 'rgba(0, 0, 0, 0.1)',
                borderColor: '#000',
                borderWidth: 2,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#000',
                pointHoverBackgroundColor: '#000',
                pointHoverBorderColor: '#fff',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    angleLines: { color: 'rgba(0,0,0,0.1)' },
                    grid: { color: 'rgba(0,0,0,0.1)' },
                    pointLabels: {
                        font: { size: 10, weight: 'bold' },
                        color: '#000'
                    },
                    suggestedMin: 0,
                    suggestedMax: 100,
                    ticks: { backdropColor: 'transparent', display: false }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });

    // Line Chart
    const lineCtx = document.getElementById('lineChart').getContext('2d');
    const lineData = <?php echo json_encode($trend_data); ?>;

    new Chart(lineCtx, {
        type: 'line',
        data: {
            labels: lineData.map(d => d.label),
            datasets: [{
                label: 'Average Score',
                data: lineData.map(d => d.value),
                borderColor: '#000',
                backgroundColor: 'rgba(0, 0, 0, 0.05)',
                borderWidth: 2,
                tension: 0, // Hard lines for brutalist look
                fill: true,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#000',
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: { color: 'rgba(0,0,0,0.05)', borderDash: [2, 2] },
                    border: { display: true, color: '#000' },
                    ticks: { color: '#000', font: { weight: 'bold' } }
                },
                x: {
                    grid: { display: false },
                    border: { display: true, color: '#000' },
                    ticks: { color: '#000', font: { weight: 'bold' } }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#000',
                    titleFont: { family: 'Courier New' },
                    bodyFont: { family: 'Courier New' },
                    padding: 10,
                    cornerRadius: 0,
                    displayColors: false
                }
            }
        }
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
