<?php
/**
 * Teacher Dashboard
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('teacher');

$page_title = 'Teacher Dashboard';
$user_id = get_current_user_id();

// Get teacher info
$stmt = $db->prepare("SELECT t.*, d.name as department_name, d.code as department_code 
    FROM teachers t 
    JOIN departments d ON t.department_id = d.id 
    WHERE t.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

if (!$teacher) {
    set_flash('error', 'Teacher profile not found');
    redirect(BASE_URL . '/modules/auth/logout.php');
}

$teacher_id = $teacher['id'];

// Check for Reschedule Requests (Pending Action)
$req_query = "SELECT rr.id as req_id, rr.created_at, c.course_code, c.course_name, co.section, cs.course_offering_id,
                     (SELECT COUNT(*) FROM votes v WHERE v.request_id = rr.id) as vote_count,
                     (SELECT COUNT(*) FROM enrollments e WHERE e.course_offering_id = co.id) as total_students
              FROM reschedule_requests rr
              JOIN class_schedule cs ON rr.class_id = cs.id
              JOIN course_offerings co ON cs.course_offering_id = co.id
              JOIN courses c ON co.course_id = c.id
              JOIN teacher_courses tc ON co.id = tc.course_offering_id
              WHERE tc.teacher_id = $teacher_id AND rr.status = 'threshold_reached'";
$pending_requests = [];
if ($r_result = $db->query($req_query)) {
    $pending_requests = $r_result->fetch_all(MYSQLI_ASSOC);
}

// Get statistics (Consolidated Query)
$today = date('l'); // Day name

$stats_query = "SELECT 
    (SELECT COUNT(*) FROM teacher_courses WHERE teacher_id = $teacher_id) as courses,
    (SELECT COUNT(DISTINCT e.student_id) FROM enrollments e 
        JOIN teacher_courses tc ON e.course_offering_id = tc.course_offering_id 
        WHERE tc.teacher_id = $teacher_id) as students,
    (SELECT COUNT(*) FROM assignment_submissions asub 
        JOIN assignments a ON asub.assignment_id = a.id 
        JOIN teacher_courses tc ON a.course_offering_id = tc.course_offering_id 
        WHERE tc.teacher_id = $teacher_id AND asub.status IN ('submitted', 'late')) as pending_grading,
    (SELECT COUNT(*) FROM class_schedule cs 
        JOIN teacher_courses tc ON cs.course_offering_id = tc.course_offering_id 
        WHERE tc.teacher_id = $teacher_id AND cs.day_of_week = '$today') as today_classes";

$result = $db->query($stats_query);
$stats = $result->fetch_assoc();

// Get today's schedule
$today_schedule = [];
$today_date = date('Y-m-d');

// 1. Fetch Regular Schedule
$result = $db->query("SELECT cs.*, c.course_code, c.course_name, c.course_type, co.section, s.semester_number 
    FROM class_schedule cs 
    JOIN course_offerings co ON cs.course_offering_id = co.id 
    JOIN courses c ON co.course_id = c.id 
    JOIN teacher_courses tc ON co.id = tc.course_offering_id 
    JOIN semesters s ON co.semester_id = s.id
    WHERE tc.teacher_id = $teacher_id AND cs.day_of_week = '$today' 
    ORDER BY cs.start_time");

$regular_classes = [];
while ($row = $result->fetch_assoc()) {
    $row['type'] = 'regular';
    $regular_classes[] = $row;
}

// 2. Fetch Rescheduled/Cancelled Exceptions
// 2. Fetch Rescheduled/Cancelled Exceptions
$res_query = "SELECT cr.*, 
                     ra.course_offering_id,
                     c.course_code, c.course_name, c.course_type, 
                     co.section, 
                     s.semester_number,
                     rs.start_time as new_start_time,
                     rs.end_time as new_end_time,
                     r.name as room_number
    FROM class_reschedules cr
    JOIN routine_assignments ra ON cr.routine_assignment_id = ra.id
    JOIN course_offerings co ON ra.course_offering_id = co.id
    JOIN courses c ON co.course_id = c.id
    JOIN semesters s ON co.semester_id = s.id
    JOIN teacher_courses tc ON co.id = tc.course_offering_id
    JOIN routine_slots rs ON cr.new_slot_id = rs.id
    LEFT JOIN rooms r ON cr.new_room_id = r.id
    WHERE tc.teacher_id = $teacher_id 
    AND (cr.original_date = '$today_date' OR cr.new_date = '$today_date')
    AND cr.status = 'active'";

$result = $db->query($res_query);
$reschedules = $result->fetch_all(MYSQLI_ASSOC);

$exceptions_map = []; // original_date_offering_id -> true
$incoming_reschedules = [];

foreach ($reschedules as $r) {
    if ($r['original_date'] == $today_date) {
        // This regular slot is cancelled/moved
        $exceptions_map[$r['original_date'] . '_' . $r['course_offering_id']] = true;
    }
    if ($r['new_date'] == $today_date) {
        // This is an incoming reschedule (new class today)
        $r['type'] = 'rescheduled';
        $r['start_time'] = $r['new_start_time']; // Normalize key for sorting
        $r['end_time'] = $r['new_end_time'];
        $incoming_reschedules[] = $r;
    }
}

// 3. Merge Lists
foreach ($regular_classes as $class) {
    $key = $today_date . '_' . $class['course_offering_id'];
    if (!isset($exceptions_map[$key])) {
        $today_schedule[] = $class;
    }
}

foreach ($incoming_reschedules as $class) {
    $today_schedule[] = $class;
}

// 4. Sort by Start Time
usort($today_schedule, function($a, $b) {
    return strcmp($a['start_time'], $b['start_time']);
});

// Get my courses
$my_courses = [];
$result = $db->query("SELECT co.*, c.course_code, c.course_name, s.name as semester_name 
    FROM course_offerings co 
    JOIN courses c ON co.course_id = c.id 
    JOIN semesters s ON co.semester_id = s.id 
    JOIN teacher_courses tc ON co.id = tc.course_offering_id 
    WHERE tc.teacher_id = $teacher_id 
    ORDER BY c.course_code");
while ($row = $result->fetch_assoc()) {
    $my_courses[] = $row;
}

// Sidebar menu
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

// Page content
ob_start();
?>

<div class="space-y-8 animate-in">
    <!-- Header -->
    <div class="bg-white os-border os-shadow p-8 flex flex-col md:flex-row gap-8 justify-between relative overflow-hidden">
        <div class="absolute top-0 right-0 p-4 opacity-10 pointer-events-none">
            <i class="fas fa-graduation-cap text-9xl text-black transform rotate-12"></i>
        </div>
        
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <span class="bg-black text-white text-[10px] font-black uppercase px-2 py-1 tracking-widest">Teacher_Module</span>
                <span class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest">
                    <span class="w-2 h-2 bg-green-500 rounded-full border border-black"></span>
                    System_Online
                </span>
            </div>
            <h1 class="text-4xl md:text-6xl font-black uppercase leading-none tracking-tighter mb-2">
                WELCOME BACK, <span class="bg-yellow-400 px-2 box-decoration-clone text-black"><?php echo explode(' ', $_SESSION['username'])[0]; ?></span>
            </h1>
            <p class="text-xs font-mono font-bold uppercase tracking-widest text-slate-500">
                User_ID: <?php echo e($teacher['employee_id']); ?> // Dept: <?php echo e($teacher['department_code']); ?>
            </p>
        </div>

        <div class="relative z-10 flex items-end">
            <div class="text-right">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] mb-1 text-slate-400">Current Session</p>
                <p class="text-xl font-black uppercase font-mono bg-black text-white px-3 py-1 inline-block transform -rotate-1">
                    <?php echo date('F d, Y'); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Card 1 -->
        <a href="my-courses.php" class="os-card p-6 flex flex-col justify-between h-[200px] group">
            <div class="flex justify-between items-start">
                <div class="os-icon-box">
                    <i class="fas fa-book text-lg"></i>
                </div>
                <i class="fas fa-arrow-up-right text-xl transition-transform group-hover:translate-x-1 group-hover:-translate-y-1"></i>
            </div>
            <div>
                <h3 class="text-5xl font-black mb-2 leading-none"><?php echo $stats['courses']; ?></h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-black">Active Courses</p>
            </div>
        </a>

        <!-- Card 2 -->
        <a href="students.php" class="os-card p-6 flex flex-col justify-between h-[200px] group">
            <div class="flex justify-between items-start">
                <div class="os-icon-box bg-white text-black">
                    <i class="fas fa-users text-lg"></i>
                </div>
                <i class="fas fa-arrow-up-right text-xl transition-transform group-hover:translate-x-1 group-hover:-translate-y-1"></i>
            </div>
            <div>
                <h3 class="text-5xl font-black mb-2 leading-none"><?php echo $stats['students']; ?></h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-black">Total Enrollment</p>
            </div>
        </a>

        <!-- Card 3 -->
        <a href="assignments.php" class="os-card p-6 flex flex-col justify-between h-[200px] group">
            <div class="flex justify-between items-start">
                <div class="os-icon-box bg-white text-black relative">
                    <i class="fas fa-file-alt text-lg"></i>
                    <?php if ($stats['pending_grading'] > 0): ?>
                        <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 border-2 border-white rounded-full"></span>
                    <?php endif; ?>
                </div>
                <i class="fas fa-arrow-up-right text-xl transition-transform group-hover:translate-x-1 group-hover:-translate-y-1"></i>
            </div>
            <div>
                <h3 class="text-5xl font-black mb-2 leading-none"><?php echo $stats['pending_grading']; ?></h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-black">Assignments Pending</p>
            </div>
        </a>

        <!-- Card 4 (Action) -->
        <a href="attendance.php" class="os-card os-card-invert p-6 flex flex-col justify-between h-[200px] group">
            <div class="flex justify-between items-start">
                <div class="os-icon-box">
                    <i class="fas fa-user-check text-lg"></i>
                </div>
                <i class="fas fa-arrow-right text-xl transition-transform group-hover:translate-x-1"></i>
            </div>
            <div>
                <h3 class="text-2xl font-black uppercase leading-none mb-1 text-white">Mark<br>Attendance</h3>
                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 group-hover:text-white transition-colors">Quick Action</p>
            </div>
        </a>
    </div>

    <?php if (!empty($pending_requests)): ?>
    <div class="os-card p-6 bg-red-50 border-2 border-red-500 mb-8 animate-pulse-slow">
        <h3 class="text-xl font-black uppercase mb-4 flex items-center gap-2 text-red-700">
            <i class="fas fa-bell animate-swing"></i> Action Required: Reschedule Requests
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php foreach ($pending_requests as $req): 
                  $pct = ($req['total_students'] > 0) ? round(($req['vote_count'] / $req['total_students']) * 100) : 0;
            ?>
            <div class="bg-white border-2 border-black p-4 shadow-[4px_4px_0px_rgba(0,0,0,0.1)] relative">
                <div class="absolute -top-3 -right-2 bg-black text-white text-[10px] font-bold px-2 py-1 rounded shadow-sm">
                    <?php echo $pct; ?>% AGREEMENT
                </div>
                <h4 class="font-black uppercase text-lg leading-none mb-1"><?php echo $req['course_code']; ?></h4>
                <p class="text-xs font-bold text-gray-500 uppercase mb-3">Sec <?php echo $req['section']; ?></p>
                <div class="text-[10px] font-mono mb-3 bg-gray-100 p-2 rounded">
                    <?php echo $req['vote_count']; ?>/<?php echo $req['total_students']; ?> Students Voted
                </div>
                <button onclick="openManageModal(<?php echo $req['req_id']; ?>, <?php echo $req['course_offering_id']; ?>, '<?php echo $req['course_code']; ?>')" 
                        class="w-full bg-red-600 text-white text-xs font-black uppercase py-2 hover:bg-black transition-colors">
                    Review & Schedule
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Schedule Section -->
        <div class="lg:col-span-2 os-card p-0 overflow-hidden bg-white">
            <div class="p-6 border-b-3 border-black flex justify-between items-center bg-yellow-400">
                <h3 class="text-xl font-black uppercase tracking-tight flex items-center gap-3">
                    <i class="fas fa-calendar-alt"></i> Today's Schedule
                </h3>
                <span class="bg-black text-white text-[10px] font-mono font-bold px-2 py-1 uppercase">
                    <?php echo date('l'); ?>
                </span>
            </div>

            <div class="p-6">
                <!-- Dashboard Specific Styles for Event Cards -->
                <style>
                    .event-card-dash {
                        padding: 12px;
                        border-radius: 8px;
                        border-left: 6px solid #000;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                        transition: transform 0.2s;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 12px;
                        background: #fff;
                    }
                    .event-card-dash:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
                    }
                    .type-theory { background-color: #e0f2fe; border-left-color: #0369a1; }
                    .type-lab { background-color: #f3e8ff; border-left-color: #7e22ce; }
                    .type-rescheduled { background-color: #fef3c7; border-left-color: #d97706; }
                    
                    .dash-time-box {
                        text-align: center;
                        min-width: 80px;
                        padding-right: 16px;
                        border-right: 2px dashed #cbd5e1;
                        margin-right: 16px;
                    }
                </style>

                <?php if (empty($today_schedule)): ?>
                    <div class="py-12 text-center border-2 border-dashed border-slate-200">
                        <p class="text-sm font-black uppercase mb-1">No Classes Scheduled</p>
                        <p class="text-xs text-slate-500 font-mono">ENJOY YOUR FREE TIME</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($today_schedule as $class): 
                            $now = time();
                            $start = strtotime($class['start_time']);
                            $end = strtotime($class['end_time']);
                            $is_active = ($now >= $start && $now <= $end);
                            
                            // Determine Class Type logic
                            $cardClass = 'event-card-dash';
                            if (isset($class['type']) && $class['type'] === 'rescheduled') {
                                $cardClass .= ' type-rescheduled';
                            } else if (isset($class['course_type']) && $class['course_type'] === 'lab') {
                                $cardClass .= ' type-lab';
                            } else {
                                $cardClass .= ' type-theory';
                            }
                        ?>
                        <div class="<?php echo $cardClass; ?>">
                            <div class="flex items-center flex-1">
                                <!-- Time -->
                                <div class="dash-time-box">
                                    <div class="text-sm font-black text-slate-800"><?php echo date('H:i', $start); ?></div>
                                    <div class="text-xs font-mono text-slate-500"><?php echo date('H:i', $end); ?></div>
                                </div>
                                
                                <!-- Info -->
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="text-sm font-black text-slate-900 uppercase">
                                            <?php echo e($class['course_code']); ?>
                                        </div>
                                        <?php if (isset($class['type']) && $class['type'] === 'rescheduled'): ?>
                                            <span class="bg-black text-white text-[9px] px-2 py-0.5 rounded-full font-bold uppercase">🔄 Rescheduled</span>
                                        <?php endif; ?>
                                        <?php if ($is_active): ?>
                                            <span class="text-[9px] font-black text-green-600 uppercase animate-pulse">● LIVE NOW</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-xs font-bold text-slate-700 uppercase mb-1">
                                        <?php echo e($class['course_name']); ?>
                                    </div>
                                    <div class="flex gap-3 text-[10px] font-bold text-slate-600 uppercase">
                                        <span><i class="fas fa-map-marker-alt mr-1"></i> <?php echo e($class['room_number'] ?? 'TBA'); ?></span>
                                        <span><i class="fas fa-layer-group mr-1"></i> Sem <?php echo e($class['semester_number']); ?> - <?php echo e($class['section']); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action -->
                             <a href="attendance.php?course_id=<?php echo $class['course_offering_id']; ?>" class="ml-4 w-10 h-10 flex items-center justify-center bg-white border-2 border-black rounded-full hover:bg-black hover:text-white transition-all shadow-sm" title="Mark Attendance">
                                <i class="fas fa-check"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar List (My Courses) -->
        <div class="os-card p-0 overflow-hidden bg-white">
            <div class="p-6 border-b-3 border-black bg-black text-white">
                <h3 class="text-xl font-black uppercase tracking-tight">Active Courses</h3>
                <p class="text-[10px] font-mono opacity-60 uppercase mt-1">QUICK NAVIGATION</p>
            </div>
            
            <div class="divide-y-2 divide-black max-h-[400px] overflow-y-auto">
                <?php if (empty($my_courses)): ?>
                    <div class="p-6 text-center text-xs font-mono text-slate-500">NO COURSES ASSIGNED</div>
                <?php else: ?>
                    <?php foreach ($my_courses as $course): ?>
                        <a href="my-courses.php?id=<?php echo $course['id']; ?>" class="block p-4 hover:bg-yellow-50 transition-colors group">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs font-black uppercase bg-slate-100 px-1 border border-black"><?php echo e($course['course_code']); ?></span>
                                <span class="text-[10px] font-mono font-bold">SEC <?php echo e($course['section']); ?></span>
                            </div>
                            <h4 class="text-sm font-black uppercase group-hover:text-black line-clamp-1 leading-tight mb-2">
                                <?php echo e($course['course_name']); ?>
                            </h4>
                            <div class="flex justify-between items-center">
                                <span class="text-[9px] font-bold uppercase text-slate-500">
                                    <i class="fas fa-users mr-1"></i> <?php echo $course['enrolled_students']; ?> Students
                                </span>
                                <i class="fas fa-chevron-right text-[10px] opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="p-4 bg-slate-50 border-t-3 border-black">
                <a href="routine.php" class="block text-center text-[10px] font-black uppercase tracking-widest hover:underline">
                    View Full Routine →
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Include layout
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>

<!-- Manage Reschedule Modal -->
<div id="manageModal" class="hidden fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white w-full max-w-lg border-4 border-black shadow-[8px_8px_0px_#fff]">
        <div class="bg-black text-white p-4 flex justify-between items-center">
            <h3 class="font-black uppercase text-lg">Approve Reschedule</h3>
            <button onclick="closeManageModal()" class="text-white hover:text-red-500 font-bold text-xl">✕</button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[80vh]">
            <input type="hidden" id="manageReqId">
            <input type="hidden" id="manageOfferingId">
            
            <h4 class="font-bold uppercase text-gray-500 text-xs mb-4" id="manageTitle"></h4>
            
            <div class="mb-4 bg-yellow-50 p-3 border border-yellow-200">
                <h5 class="font-bold uppercase text-xs mb-2">Top Suggested Dates</h5>
                <ul id="topSuggestions" class="text-xs font-mono space-y-1">
                    <li>Loading...</li>
                </ul>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-[10px] font-black uppercase mb-1">Original Date (To Cancel)</label>
                    <input type="date" id="origDate" class="w-full border border-black p-2 text-xs">
                    <p class="text-[9px] text-gray-400 mt-0.5">The class session to be moved</p>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase mb-1">New Date</label>
                    <input type="date" id="newDate" class="w-full border border-black p-2 text-xs">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-[10px] font-black uppercase mb-1">New Start Time</label>
                    <input type="time" id="newStart" class="w-full border border-black p-2 text-xs">
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase mb-1">New End Time</label>
                    <input type="time" id="newEnd" class="w-full border border-black p-2 text-xs">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-[10px] font-black uppercase mb-1">Room Number</label>
                <input type="text" id="newRoom" class="w-full border border-black p-2 text-xs" placeholder="e.g. 301">
            </div>
            
            <div class="mb-4">
                <label class="block text-[10px] font-black uppercase mb-1">Message to Students</label>
                <textarea id="teacherMsg" class="w-full border border-black p-2 text-xs" rows="2" placeholder="Reason or confirmation..."></textarea>
            </div>

            <button onclick="submitReschedule()" class="w-full bg-black text-white font-black uppercase py-3 hover:bg-green-600 transition-colors">
                Confirm & Update Schedule
            </button>
        </div>
    </div>
</div>

<script>
function openManageModal(reqId, offId, code) {
    document.getElementById('manageModal').classList.remove('hidden');
    document.getElementById('manageReqId').value = reqId;
    document.getElementById('manageOfferingId').value = offId;
    document.getElementById('manageTitle').textContent = 'Scheduling for ' + code;
    
    // Fetch Details
    fetch('<?php echo BASE_URL; ?>/api/teacher/manage_reschedule.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'get_details', request_id: reqId })
    })
    .then(res => res.json())
    .then(data => {
        const list = document.getElementById('topSuggestions');
        list.innerHTML = '';
        if (data.suggestions && data.suggestions.length > 0) {
            data.suggestions.forEach(s => {
                const date = new Date(s.suggested_date).toLocaleString();
                list.innerHTML += `<li class="flex justify-between"><span>${date}</span> <span class="font-bold">${s.votes} Votes</span></li>`;
                // Auto-fill top suggestion
                if (list.children.length === 1) {
                   const d = new Date(s.suggested_date);
                   document.getElementById('newDate').value = d.toISOString().split('T')[0];
                   document.getElementById('newStart').value = d.toTimeString().slice(0,5);
                   // Default end time +1 hour?
                   d.setHours(d.getHours() + 1); // Simplification
                   document.getElementById('newEnd').value = d.toTimeString().slice(0,5);
                }
            });
        } else {
            list.innerHTML = '<li>No suggestions found</li>';
        }
    });
}

function closeManageModal() {
    document.getElementById('manageModal').classList.add('hidden');
}

function submitReschedule() {
    const data = {
        action: 'approve',
        request_id: document.getElementById('manageReqId').value,
        course_offering_id: document.getElementById('manageOfferingId').value,
        original_date: document.getElementById('origDate').value,
        new_date: document.getElementById('newDate').value,
        new_start_time: document.getElementById('newStart').value,
        new_end_time: document.getElementById('newEnd').value,
        room: document.getElementById('newRoom').value,
        message: document.getElementById('teacherMsg').value
    };

    if(!data.original_date || !data.new_date) {
        alert('Please fill all dates');
        return;
    }

    fetch('<?php echo BASE_URL; ?>/api/teacher/manage_reschedule.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            alert('Schedule Updated Successfully');
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    });
}
</script>
