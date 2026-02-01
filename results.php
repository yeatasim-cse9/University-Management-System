<?php
/**
 * Student Results
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('student');

$page_title = 'Results';
$user_id = get_current_user_id();

// Get student info
$stmt = $db->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$student_id = $student['id'];

// Get Results Grouped by Semester
$results = [];
$query = "SELECT 
    c.id as course_id, c.course_code, c.course_name, c.credit_hours,
    ac.component_name, ac.weightage,
    sm.marks_obtained, sm.total_marks, sm.remarks, sm.status,
    s.id as semester_id, s.name as semester_name, s.start_date
    FROM student_marks sm
    JOIN enrollments e ON sm.enrollment_id = e.id
    JOIN course_offerings co ON e.course_offering_id = co.id
    JOIN courses c ON co.course_id = c.id
    JOIN semesters s ON co.semester_id = s.id
    JOIN assessment_components ac ON sm.assessment_component_id = ac.id
    WHERE e.student_id = $student_id AND sm.status = 'verified'
    ORDER BY s.start_date DESC, c.course_code, ac.id";

$res = $db->query($query);
while ($row = $res->fetch_assoc()) {
    $sem_id = $row['semester_id'];
    $sem_name = $row['semester_name'];
    $course_code = $row['course_code'];
    
    if (!isset($results[$sem_id])) {
        $results[$sem_id] = [
            'name' => $sem_name,
            'courses' => []
        ];
    }
    
    if (!isset($results[$sem_id]['courses'][$course_code])) {
        $results[$sem_id]['courses'][$course_code] = [
            'name' => $row['course_name'],
            'credits' => $row['credit_hours'],
            'marks' => []
        ];
    }
    
    $results[$sem_id]['courses'][$course_code]['marks'][] = [
        'component' => $row['component_name'],
        'weight' => $row['weightage'],
        'obtained' => $row['marks_obtained'],
        'total' => $row['total_marks']
    ];
}

// Function to calculate Grade Point
function calculate_grade($pct) {
    if ($pct >= 80) return ['grade' => 'A+', 'point' => 4.00, 'col' => 'text-emerald-600'];
    if ($pct >= 75) return ['grade' => 'A', 'point' => 3.75, 'col' => 'text-emerald-600'];
    if ($pct >= 70) return ['grade' => 'A-', 'point' => 3.50, 'col' => 'text-indigo-600'];
    if ($pct >= 65) return ['grade' => 'B+', 'point' => 3.25, 'col' => 'text-indigo-600'];
    if ($pct >= 60) return ['grade' => 'B', 'point' => 3.00, 'col' => 'text-indigo-600'];
    if ($pct >= 55) return ['grade' => 'B-', 'point' => 2.75, 'col' => 'text-amber-600'];
    if ($pct >= 50) return ['grade' => 'C+', 'point' => 2.50, 'col' => 'text-amber-600'];
    if ($pct >= 45) return ['grade' => 'C', 'point' => 2.25, 'col' => 'text-amber-600'];
    if ($pct >= 40) return ['grade' => 'D', 'point' => 2.00, 'col' => 'text-amber-600'];
    return ['grade' => 'F', 'point' => 0.00, 'col' => 'text-red-600'];
}

// Calculate GPA/CGPA Logic
$total_credits = 0;
$total_points = 0;

foreach ($results as &$sem) {
    $sem_credits = 0;
    $sem_points = 0;
    
    foreach ($sem['courses'] as &$course) {
        $total_weighted_pct = 0;
        $total_weight = 0;
        
        foreach ($course['marks'] as $m) {
            $pct = ($m['obtained'] / $m['total']) * 100;
            $total_weighted_pct += ($pct * ($m['weight'] / 100));
            $total_weight += $m['weight'];
        }
        
        // Handle cases where not all weights are in yet
        $final_pct = $total_weight > 0 ? ($total_weighted_pct / ($total_weight / 100)) : 0;
        $grade_info = calculate_grade($final_pct);
        
        $course['grade'] = $grade_info['grade'];
        $course['point'] = $grade_info['point'];
        $course['color'] = $grade_info['col'];
        $course['final_pct'] = round($final_pct, 1);
        
        $sem_credits += $course['credits'];
        $sem_points += ($course['point'] * $course['credits']);
    }
    
    $sem['gpa'] = $sem_credits > 0 ? round($sem_points / $sem_credits, 2) : 0.00;
    $sem['total_credits'] = $sem_credits;
    
    $total_credits += $sem_credits;
    $total_points += $sem_points;
}

$cgpa = $total_credits > 0 ? round($total_points / $total_credits, 2) : 0.00;

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
                My Results
            </h1>
            <p class="text-sm font-bold uppercase tracking-widest text-gray-600">
                Academic Performance Record
            </p>
        </div>
        
        <div class="flex items-center gap-6">
            <div class="text-right hidden md:block">
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Total Credits</p>
                <p class="text-2xl font-black"><?php echo $total_credits; ?></p>
            </div>
            <div class="os-card p-4 flex items-center gap-4 bg-black text-white border-0">
                <div class="text-right">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">CGPA</p>
                    <p class="text-3xl font-black leading-none text-yellow-400"><?php echo number_format($cgpa, 2); ?></p>
                </div>
                <div class="text-3xl text-yellow-400">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($results)): ?>
        <div class="os-card p-12 text-center text-gray-400">
            <i class="fas fa-folder-open text-4xl mb-4"></i>
            <p class="text-xs font-black uppercase tracking-widest">No results found yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($results as $sem_id => $sem): ?>
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b-2 border-black pb-2 mt-8">
                    <h2 class="text-xl font-black uppercase tracking-tighter"><?php echo e($sem['name']); ?></h2>
                    <span class="bg-black text-white text-xs font-black uppercase px-3 py-1">GPA: <?php echo number_format($sem['gpa'], 2); ?></span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($sem['courses'] as $code => $c): ?>
                        <div class="os-card flex flex-col h-full hover:-translate-y-1 transition-transform duration-300">
                            <div class="p-6 pb-4 border-b-2 border-dashed border-gray-200">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="bg-gray-100 text-gray-600 text-[10px] font-black uppercase tracking-widest px-2 py-1"><?php echo e($code); ?></span>
                                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-400"><?php echo $c['credits']; ?> CR</span>
                                </div>
                                <h3 class="text-lg font-black uppercase leading-tight line-clamp-2 min-h-[3rem]"><?php echo e($c['name']); ?></h3>
                            </div>
                            
                            <div class="p-6 flex-1 flex flex-col justify-center items-center">
                                <div class="text-5xl font-black <?php echo $c['color']; ?> mb-2"><?php echo $c['grade']; ?></div>
                                <div class="w-full h-2 bg-gray-100 border border-black overflow-hidden relative">
                                    <div class="absolute inset-y-0 left-0 bg-black" style="width: <?php echo $c['final_pct']; ?>%"></div>
                                </div>
                                <div class="flex justify-between w-full mt-2 text-[10px] font-black uppercase tracking-widest text-gray-500">
                                    <span><?php echo $c['final_pct']; ?>%</span>
                                    <span><?php echo number_format($c['point'], 2); ?> GP</span>
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 p-4 border-t-2 border-black">
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Breakdown</p>
                                <div class="space-y-1">
                                    <?php foreach ($c['marks'] as $m): ?>
                                    <div class="flex justified-between items-center text-[10px] font-bold uppercase">
                                        <span class="flex-1 truncate pr-2 text-gray-600"><?php echo e($m['component']); ?></span>
                                        <span class="bg-white border border-gray-300 px-1"><?php echo $m['obtained']; ?><span class="text-gray-400">/<?php echo $m['total']; ?></span></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
