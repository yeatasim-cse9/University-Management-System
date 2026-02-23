<?php
/**
 * Student Performance Dashboard (Teacher View)
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/components/PerformanceMetrics.php';

require_role('teacher');

$teacher_id = get_current_user_id();
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

if (!$student_id) {
    set_flash('error', 'Invalid student ID');
    redirect(BASE_URL . '/modules/teacher/dashboard.php');
}

// Security Check: Verify student is enrolled in one of the teacher's courses
// Or is in the teacher's department? Let's assume teacher can view any student they teach.
$chk_query = "
    SELECT DISTINCT e.student_id 
    FROM enrollments e
    JOIN teacher_courses tc ON e.course_offering_id = tc.course_offering_id
    WHERE tc.teacher_id = (SELECT id FROM teachers WHERE user_id = ?) AND e.student_id = ?
";
$stmt = $db->prepare($chk_query);
$stmt->bind_param("ii", $teacher_id, $student_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    // Also check if teacher is department head or admin of department?
    // For now, simple check: strict access
    set_flash('error', 'You do not have permission to view this student\'s performance.');
    redirect(BASE_URL . '/modules/teacher/dashboard.php');
}

// Get Student Details
$stmt_std = $db->prepare("
    SELECT s.*, up.first_name, up.last_name, up.profile_picture, d.name as dept_name, s.student_id as matric_no
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.id = ?
");
$stmt_std->bind_param("i", $student_id);
$stmt_std->execute();
$student = $stmt_std->get_result()->fetch_assoc();

// Handle Review Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_text'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Security token invalid');
    } else {
        $review = sanitize_input($_POST['review_text']);
        if (!empty($review)) {
            // Check if table exists (graceful degradation handled in class, but here we assumme migration ran)
            $stmt_ins = $db->prepare("INSERT INTO student_performance_reviews (student_id, reviewer_id, review_text) VALUES (?, ?, ?)");
            $stmt_ins->bind_param("iis", $student_id, $teacher_id, $review);
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

// Common View Logic
$is_teacher = true;
$can_add_review = true;

// START OUTPUT
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<!-- Performance Dashboard UI (Teacher View - Brutalist) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-8 animate-in">
    <!-- Header -->
    <div class="os-card p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white relative overflow-hidden">
         <div class="relative z-10">
            <div class="flex items-center gap-2 mb-2">
                <a href="<?php echo BASE_URL; ?>/modules/teacher/students.php" class="text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-black transition-colors">
                    <i class="fas fa-arrow-left"></i> Directory
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-[10px] font-black uppercase tracking-widest text-black bg-yellow-400 px-2">Analytics</span>
            </div>
            <h1 class="text-3xl font-black uppercase tracking-tighter">
                <?php echo e($student['first_name'] . ' ' . $student['last_name']); ?>
                <span class="text-lg text-slate-400 font-normal ml-2 tracking-normal">// <?php echo e($student['matric_no']); ?></span>
            </h1>
        </div>
        
        <?php if($can_add_review): ?>
        <button onclick="document.getElementById('reviewModal').classList.remove('hidden')" class="btn-os bg-black text-white hover:bg-yellow-400 hover:text-black border-2 border-black shadow-[4px_4px_0px_#000] hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all flex items-center gap-2 px-6 py-3">
            <i class="fas fa-comment-medical"></i> Add Remark
        </button>
        <?php endif; ?>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- CGPA -->
        <div class="os-card p-6 bg-white flex flex-col justify-between h-40">
            <div class="flex justify-between items-start">
                  <span class="text-[10px] font-black uppercase tracking-widest bg-black text-white px-2 py-1">Est. CGPA</span>
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
        
        <!-- Department -->
        <div class="os-card p-6 bg-yellow-400 text-black flex flex-col justify-between h-40 border-2 border-black shadow-[4px_4px_0px_#000]">
             <div class="flex justify-between items-start">
                <span class="text-[10px] font-black uppercase tracking-widest bg-black text-white px-2 py-1">Department</span>
                <i class="fas fa-university text-2xl text-black"></i>
            </div>
            <div class="text-right">
                <h3 class="text-xl font-black text-black leading-tight uppercase truncate"><?php echo e($student['dept_name']); ?></h3>
                <p class="text-[10px] font-bold text-black uppercase tracking-widest mt-1"><?php echo e($student['session']); ?> Session</p>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Radar Chart: Subject Performance -->
        <div class="os-card p-6 bg-white">
            <h3 class="text-xl font-black text-black uppercase mb-1">Subject Proficiency</h3>
             <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-6">Comparative Analysis</p>
            <div class="relative h-80">
                <canvas id="radarChart"></canvas>
            </div>
        </div>

        <!-- Line Chart: Trend -->
        <div class="os-card p-6 bg-white">
             <h3 class="text-xl font-black text-black uppercase mb-1">Performance Trend</h3>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-6">Historical Progression</p>
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
                    <i class="fas fa-comment-slash text-4xl text-slate-300 mb-4"></i>
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">No remarks recorded yet.</p>
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

<!-- Review Modal -->
<div id="reviewModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm transition-opacity" onclick="document.getElementById('reviewModal').classList.add('hidden')"></div>
    <div class="flex items-center justify-center min-h-screen p-4 fade-in">
        <div class="os-card bg-white w-full max-w-lg relative z-10 animate-in zoom-in-95 duration-200 p-0 border-2 border-black shadow-[8px_8px_0px_#000]">
            <div class="bg-black p-6 text-white border-b-2 border-black">
                <h3 class="text-xl font-black uppercase tracking-tight">Add Remark</h3>
                <p class="text-slate-400 text-[10px] uppercase tracking-widest mt-1">Visible to student and admins</p>
            </div>
            <form method="POST" class="p-8 space-y-6">
                <?php csrf_field(); ?>
                <div>
                    <label class="block text-xs font-black text-black uppercase tracking-widest mb-2">Observation</label>
                    <textarea name="review_text" rows="4" class="w-full bg-white border-2 border-black p-4 text-black font-mono text-sm uppercase placeholder-slate-400 outline-none focus:shadow-[4px_4px_0px_#000] resize-none transaction-shadow" placeholder="ENTER YOUR ASSESSMENT..." required></textarea>
                </div>
                <div class="flex justify-end gap-4">
                    <button type="button" onclick="document.getElementById('reviewModal').classList.add('hidden')" class="px-6 py-3 bg-white text-black border-2 border-black font-black text-xs uppercase tracking-widest hover:bg-slate-100 transition-colors shadow-[2px_2px_0px_#000] active:translate-x-[1px] active:translate-y-[1px] active:shadow-none">Cancel</button>
                    <button type="submit" class="px-6 py-3 bg-black text-white border-2 border-black font-black text-xs uppercase tracking-widest hover:bg-yellow-400 hover:text-black transition-colors shadow-[4px_4px_0px_#000] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none">Submit</button>
                </div>
            </form>
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
