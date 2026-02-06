<?php
/**
 * Student Performance Dashboard (Admin View)
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/components/PerformanceMetrics.php';

require_role('admin');

$user_id = get_current_user_id();
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

if (!$student_id) {
    set_flash('error', 'Invalid student ID');
    redirect(BASE_URL . '/modules/admin/dashboard.php');
}

// Get Student Details
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
    redirect(BASE_URL . '/modules/admin/dashboard.php');
}

// Check if admin manages this department (Strict Access Control)
$stmt_dept = $db->prepare("SELECT department_id FROM department_admins WHERE user_id = ?");
$stmt_dept->bind_param("i", $user_id);
$stmt_dept->execute();
$res_dept = $stmt_dept->get_result();
$allowed = false;
while ($row = $res_dept->fetch_assoc()) {
    if ($row['department_id'] == $student['department_id']) {
        $allowed = true;
        break;
    }
}
if (!$allowed) {
    set_flash('error', 'Access denied: Student is not in your department');
    redirect(BASE_URL . '/modules/admin/students.php');
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
$can_add_review = true;

// START OUTPUT
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<!-- Performance Dashboard UI (Admin View - Brutalist) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-8">
    
    <!-- Hero Profile Section -->
    <div class="os-card p-0 bg-white overflow-hidden">
        <div class="bg-black p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 border-b-2 border-black">
            <div class="flex items-center gap-6">
                <div class="w-24 h-24 bg-white border-2 border-white relative shadow-[4px_4px_0px_#fff]">
                    <?php if ($student['profile_picture']): ?>
                        <img src="<?php echo ASSETS_URL . '/uploads/profiles/' . $student['profile_picture']; ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center bg-black text-white text-3xl font-black uppercase">
                            <?php echo substr($student['first_name'], 0, 1); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                     <div class="flex items-center gap-3 mb-1">
                        <a href="<?php echo BASE_URL; ?>/modules/admin/students.php" class="text-white hover:text-yellow-400 transition-colors uppercase font-bold text-xs tracking-widest">
                            <i class="fas fa-arrow-left mr-1"></i> Return
                        </a>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-black uppercase text-white leading-none tracking-tighter">
                        <?php echo e($student['first_name']); ?> <span class="text-yellow-400"><?php echo e($student['last_name']); ?></span>
                    </h1>
                    <div class="flex flex-wrap gap-4 mt-2">
                        <span class="inline-block bg-white text-black text-[10px] font-black uppercase px-2 py-1 tracking-widest"><?php echo e($student['matric_no']); ?></span>
                        <span class="inline-block border border-white text-white text-[10px] font-black uppercase px-2 py-1 tracking-widest"><?php echo e($student['dept_name']); ?></span>
                    </div>
                </div>
            </div>
            
             <button onclick="document.getElementById('reviewModal').classList.remove('hidden')" class="btn-os bg-white text-black border-white hover:bg-yellow-400 hover:text-black hover:border-transparent flex items-center gap-2 shadow-[4px_4px_0px_#fff]">
                <i class="fas fa-plus-circle"></i> Add Review
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- CGPA -->
        <div class="os-card p-6 bg-white flex flex-col justify-between h-40">
            <div class="flex justify-between items-start">
                <span class="text-[10px] font-black uppercase tracking-widest bg-black text-white px-2 py-1">CGPA</span>
                <i class="fas fa-graduation-cap text-2xl text-black"></i>
            </div>
            <div class="text-right">
                <h3 class="text-5xl font-black text-black leading-none"><?php echo number_format($cgpa, 2); ?></h3>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1">Academic Standing</p>
            </div>
        </div>

        <!-- Attendance -->
        <div class="os-card p-6 bg-black text-white flex flex-col justify-between h-40 border-2 border-black shadow-[4px_4px_0px_#000]">
            <div class="flex justify-between items-start">
                <span class="text-[10px] font-black uppercase tracking-widest bg-white text-black px-2 py-1">Attendance</span>
                <i class="fas fa-calendar-check text-2xl text-white"></i>
            </div>
            <div class="text-right">
                <h3 class="text-5xl font-black text-white leading-none"><?php echo $attendance['percentage']; ?><span class="text-2xl">%</span></h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1"><?php echo $attendance['present']; ?> / <?php echo $attendance['total']; ?> Classes</p>
            </div>
        </div>

        <!-- Assignments -->
        <div class="os-card p-6 bg-white flex flex-col justify-between h-40">
            <div class="flex justify-between items-start">
                <span class="text-[10px] font-black uppercase tracking-widest bg-black text-white px-2 py-1">Tasks</span>
                <i class="fas fa-tasks text-2xl text-black"></i>
            </div>
            <div class="flex items-end justify-end gap-4">
                <div class="text-right">
                    <h3 class="text-3xl font-black text-black leading-none"><?php echo $assignments['submitted']; ?></h3>
                    <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Done</p>
                </div>
                <div class="w-px h-8 bg-black"></div>
                <div class="text-right">
                    <h3 class="text-3xl font-black text-red-600 leading-none"><?php echo $assignments['missed']; ?></h3>
                    <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Missed</p>
                </div>
            </div>
        </div>

        <!-- Semester -->
        <div class="os-card p-6 bg-yellow-400 text-black flex flex-col justify-between h-40 border-2 border-black shadow-[4px_4px_0px_#000]">
            <div class="flex justify-between items-start">
                <span class="text-[10px] font-black uppercase tracking-widest bg-black text-white px-2 py-1">Semester</span>
                <i class="fas fa-layer-group text-2xl text-black"></i>
            </div>
            <div class="text-right">
                <h3 class="text-4xl font-black text-black leading-none">Lvl <?php echo e($student['current_semester']); ?></h3>
                <p class="text-[10px] font-bold text-black uppercase tracking-widest mt-1"><?php echo e($student['session']); ?></p>
            </div>
        </div>
    </div>

    <!-- Analytics Board -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Radar Section -->
        <div class="os-card p-6 bg-white">
            <h3 class="text-xl font-black text-black uppercase mb-1">Technical Proficiency</h3>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-6">Subject Breakdown</p>
            <div class="h-80">
                <canvas id="radarChart"></canvas>
            </div>
        </div>

        <!-- Trend Section -->
        <div class="os-card p-6 bg-white">
            <h3 class="text-xl font-black text-black uppercase mb-1">Performance Trajectory</h3>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-6">Historical Analysis</p>
            <div class="h-80">
                <canvas id="lineChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Reviews / Feedback Loop -->
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center gap-4 mb-6 pt-8 border-t-2 border-dashed border-black">
             <div class="w-12 h-12 bg-black text-white flex items-center justify-center text-xl border-2 border-black">
                <i class="fas fa-comments"></i>
            </div>
            <div>
                <h3 class="text-2xl font-black text-black uppercase tracking-tight">Executive Remarks</h3>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Official notes from administration</p>
            </div>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="p-12 text-center border-2 border-black bg-slate-50 border-dashed">
                <i class="fas fa-clipboard text-slate-300 text-5xl mb-4"></i>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No remarks available</p>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($reviews as $rev): ?>
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 bg-white border-2 border-black text-black flex items-center justify-center font-black text-sm shadow-[2px_2px_0px_#000]">
                                <?php echo strtoupper(substr($rev['first_name'], 0, 1)); ?>
                            </div>
                            <div class="w-0.5 h-full bg-black my-2"></div>
                        </div>
                        <div class="flex-1 pb-4">
                             <div class="bg-white p-6 border-2 border-black shadow-[4px_4px_0px_#000 relative">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h4 class="font-black text-black text-sm uppercase tracking-wide"><?php echo e($rev['first_name'] . ' ' . $rev['last_name']); ?></h4>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="px-2 py-0.5 bg-black text-white text-[9px] font-black uppercase tracking-widest"><?php echo e(ucfirst($rev['role'])); ?></span>
                                            <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider"><?php echo time_ago($rev['created_at']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-black font-mono text-sm leading-relaxed"><?php echo nl2br(e($rev['review_text'])); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Admin Review Modal -->
<div id="reviewModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm transition-opacity" onclick="document.getElementById('reviewModal').classList.add('hidden')"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white border-4 border-black shadow-[8px_8px_0px_#000] w-full max-w-lg relative z-10">
            <div class="bg-black p-6 text-white border-b-4 border-black flex justify-between items-center">
                <div>
                    <h3 class="text-2xl font-black uppercase tracking-tighter">Admin Remark</h3>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-yellow-400">Add detailed assessment</p>
                </div>
                <button type="button" onclick="document.getElementById('reviewModal').classList.add('hidden')" class="text-white hover:text-red-500 text-xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" class="p-8 space-y-6">
                <?php csrf_field(); ?>
                <div>
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest mb-2">Observation</label>
                    <textarea name="review_text" rows="5" class="w-full bg-white border-2 border-black p-4 text-black font-mono text-sm focus:outline-none focus:bg-yellow-50 resize-none placeholder-slate-400" placeholder="Type your observation here..." required></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('reviewModal').classList.add('hidden')" class="btn-os bg-white text-black border-black hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black">Submit Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Styling Defaults
    Chart.defaults.font.family = "'Outfit', sans-serif"; // Using Outfit to match theme
    Chart.defaults.color = '#000000';
    
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
                borderColor: '#000000',
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
                        font: { size: 10, weight: 'bold', family: "'Courier Prime', monospace" },
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
                borderColor: '#000000',
                backgroundColor: 'transparent',
                borderWidth: 2,
                tension: 0, // Hard lines for brutalist feel
                pointBackgroundColor: '#fff',
                pointBorderColor: '#000',
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: { color: 'rgba(0,0,0,0.1)', borderDash: [2, 2] },
                    border: { display: true, color: '#000', width: 2 },
                    ticks: { font: { family: "'Courier Prime', monospace" } }
                },
                x: {
                    grid: { display: false },
                    border: { display: true, color: '#000', width: 2 },
                    ticks: { font: { family: "'Courier Prime', monospace" } }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#000',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 10,
                    cornerRadius: 0,
                    titleFont: { family: "'Courier Prime', monospace" },
                    bodyFont: { family: "'Courier Prime', monospace" },
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
