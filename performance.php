<?php
/**
 * Student Performance Dashboard (My Performance)
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/components/PerformanceMetrics.php';

require_role('student');

$student_id = get_current_user_id(); // Student's own user_id 
// BUT PerformanceMetrics needs student table ID, not user_id. 
// auth.php functions usually deal with user_id. Let's fix this mapping.

// Get Student Table ID
$stmt = $db->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if (!$res) {
    set_flash('error', 'Student record not found');
    redirect(BASE_URL . '/modules/student/dashboard.php');
}
$student_table_id = $res['id'];

// Get Student Details for Header
$stmt_std = $db->prepare("
    SELECT s.*, up.first_name, up.last_name, up.profile_picture, d.name as dept_name, s.student_id as matric_no
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.id = ?
");
$stmt_std->bind_param("i", $student_table_id);
$stmt_std->execute();
$student = $stmt_std->get_result()->fetch_assoc();


// Calculate Metrics
$metrics = new PerformanceMetrics($db, $student_table_id);
$cgpa = $metrics->getCGPA();
$attendance = $metrics->getAttendanceStats();
$assignments = $metrics->getAssignmentStats();
$radar_data = $metrics->getSubjectPerformance();
$trend_data = $metrics->getPerformanceTrend();
$reviews = $metrics->getReviews();

$page_title = "My Performance";

// START OUTPUT
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<!-- Performance Dashboard UI (Student View - Brutalist) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-8 animate-in">
    <!-- Header -->
    <div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white">
         <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
                <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Analysis</span>
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                    <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                    Metrics
                </span>
            </div>
            <h1 class="text-3xl font-black uppercase tracking-tighter">My <span class="text-transparent bg-clip-text bg-gradient-to-r from-black to-slate-500">Performance</span></h1>
        </div>
        
        <div class="flex items-center gap-4 relative z-10">
            <div class="w-10 h-10 bg-white border-2 border-black flex items-center justify-center text-black text-lg shadow-[2px_2px_0px_#000]">
                <i class="fas fa-chart-pie"></i>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- CGPA -->
        <div class="os-card p-6 bg-white flex flex-col justify-between h-40">
            <div class="flex justify-between items-start">
                  <span class="text-[10px] font-black uppercase tracking-widest bg-black text-white px-2 py-1">CGPA</span>
                  <i class="fas fa-graduation-cap text-2xl text-black"></i>
            </div>
            <div class="text-right">
                <h3 class="text-5xl font-black text-black leading-none"><?php echo number_format($cgpa, 2); ?></h3>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1">/ 4.00 Scale</p>
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
            <div class="flex items-center gap-4 justify-end">
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
        
        <!-- Status -->
        <div class="os-card p-6 bg-yellow-400 text-black flex flex-col justify-between h-40 border-2 border-black shadow-[4px_4px_0px_#000]">
             <div class="flex justify-between items-start">
                <span class="text-[10px] font-black uppercase tracking-widest bg-black text-white px-2 py-1">Status</span>
                <i class="fas fa-flag text-2xl text-black"></i>
            </div>
            <div class="text-right">
                <h3 class="text-2xl font-black text-black leading-tight uppercase"><?php echo $cgpa > 3.0 ? 'Good Standing' : 'Needs Action'; ?></h3>
                <p class="text-[10px] font-bold text-black uppercase tracking-widest mt-1"><?php echo e($student['session']); ?> Session</p>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Radar Chart: Subject Performance -->
        <div class="os-card p-6 bg-white">
            <h3 class="text-xl font-black text-black uppercase mb-1">Course Proficiency</h3>
             <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-6">Subject Breakdown</p>
            <div class="relative h-80">
                <canvas id="radarChart"></canvas>
            </div>
        </div>

        <!-- Line Chart: Trend -->
        <div class="os-card p-6 bg-white">
             <h3 class="text-xl font-black text-black uppercase mb-1">Performance Trend</h3>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-6">Historical Data</p>
            <div class="relative h-80">
                <canvas id="lineChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Reviews Section -->
    <div class="os-card p-0 bg-white">
        <div class="p-6 border-b-3 border-black bg-black text-white flex justify-between items-center">
            <div>
                 <h3 class="text-xl font-black uppercase tracking-tight">Faculty Remarks</h3>
                <p class="text-[10px] font-mono opacity-60 uppercase mt-1">Feedback Log</p>
            </div>
            <i class="fas fa-comments text-2xl text-yellow-400"></i>
        </div>
            
        <div class="p-8">
            <?php if (empty($reviews)): ?>
                <div class="text-center py-12 border-2 border-dashed border-slate-300">
                    <i class="fas fa-clipboard text-4xl text-slate-300 mb-4"></i>
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">No detailed feedback available</p>
                </div>
            <?php else: ?>
                <div class="grid gap-6">
                    <?php foreach ($reviews as $rev): ?>
                        <div class="bg-white border-2 border-black p-6 shadow-[4px_4px_0px_#000] hover:translate-x-1 hover:-translate-y-1 transition-transform">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-black text-white flex items-center justify-center font-black text-sm border-2 border-black">
                                        <?php echo strtoupper(substr($rev['first_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="font-black text-sm text-black uppercase"><?php echo e($rev['first_name'] . ' ' . $rev['last_name']); ?></p>
                                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest"><?php echo e(ucfirst($rev['role'])); ?></p>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold bg-slate-100 px-2 py-1 border border-black uppercase text-black"><?php echo time_ago($rev['created_at']); ?></span>
                            </div>
                            <p class="text-black font-mono text-sm leading-relaxed border-l-4 border-yellow-400 pl-4">
                                "<?php echo nl2br(e($rev['review_text'])); ?>"
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
    // Styling Defaults
    Chart.defaults.font.family = "'Outfit', sans-serif";
    Chart.defaults.color = '#000000';
    
    // Radar Chart Data
    const radarCtx = document.getElementById('radarChart').getContext('2d');
    const radarData = <?php echo json_encode($radar_data); ?>;
    
    new Chart(radarCtx, {
        type: 'radar',
        data: {
            labels: radarData.map(d => d.subject),
            datasets: [{
                label: 'Score %',
                data: radarData.map(d => d.score),
                 backgroundColor: 'rgba(0, 0, 0, 0)',
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
                    angleLines: { color: 'rgba(0,0,0,0.1)', lineWidth: 1 },
                    grid: { color: 'rgba(0,0,0,0.1)', lineWidth: 1 },
                    pointLabels: {
                        font: { size: 10, weight: 'bold', family: "'Courier Prime', monospace" },
                        color: '#000'
                    },
                    suggestedMin: 0,
                    suggestedMax: 100,
                    ticks: { backdropColor: 'transparent', display: false }
                }
            },
            plugins: { legend: { display: false } }
        }
    });

    // Line Chart Data
    const lineCtx = document.getElementById('lineChart').getContext('2d');
    const lineData = <?php echo json_encode($trend_data); ?>;

    new Chart(lineCtx, {
        type: 'line',
        data: {
            labels: lineData.map(d => d.label),
            datasets: [{
                label: 'Average Score %',
                data: lineData.map(d => d.value),
                borderColor: '#000000',
                backgroundColor: 'transparent',
                borderWidth: 2,
                tension: 0,
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
