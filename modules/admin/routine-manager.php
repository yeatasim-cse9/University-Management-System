<?php
/**
 * Routine Manager
 * ACADEMIX - Academic Management System
 * Dynamic class routine management with slot-based scheduling
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('admin');

$page_title = 'Routine Manager';
$user_id = get_current_user_id();

// Get admin's department(s)
$stmt = $db->prepare("SELECT d.id, d.name, d.code FROM departments d JOIN department_admins da ON d.id = da.department_id WHERE da.user_id = ? AND d.deleted_at IS NULL");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$departments = [];
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}
$department_ids = array_column($departments, 'id');
$dept_id_list = !empty($department_ids) ? implode(',', $department_ids) : '0';

// Get current semester
$current_semester = null;
$sem_result = $db->query("SELECT s.*, ay.year FROM semesters s JOIN academic_years ay ON s.academic_year_id = ay.id WHERE s.status = 'active' LIMIT 1");
if ($sem_result && $row = $sem_result->fetch_assoc()) {
    $current_semester = $row;
}

// Get slot types
$slot_types = [];
$st_result = $db->query("SELECT * FROM routine_slot_types WHERE is_active = 1 ORDER BY name");
while ($row = $st_result->fetch_assoc()) {
    $slot_types[] = $row;
}

// Get time slots for current department
$time_slots = [];
if (!empty($department_ids)) {
    $ts_result = $db->query("SELECT rts.*, rst.name as type_name, rst.color_code, rst.duration_minutes 
        FROM routine_time_slots rts 
        JOIN routine_slot_types rst ON rts.slot_type_id = rst.id 
        WHERE rts.department_id IN ($dept_id_list) OR rts.department_id IS NULL
        ORDER BY rts.day_of_week, rts.slot_number");
    while ($row = $ts_result->fetch_assoc()) {
        $time_slots[] = $row;
    }
}

// Get current draft template or create one
$template = null;
if ($current_semester && !empty($department_ids)) {
    $dept_id = $department_ids[0]; // Use first department
    $stmt = $db->prepare("SELECT * FROM routine_templates WHERE semester_id = ? AND department_id = ? AND status IN ('draft', 'published') ORDER BY status = 'published' DESC LIMIT 1");
    $stmt->bind_param("ii", $current_semester['id'], $dept_id);
    $stmt->execute();
    $template = $stmt->get_result()->fetch_assoc();
}

// Get course offerings for assignment
$course_offerings = [];
if (!empty($department_ids) && $current_semester) {
    $co_result = $db->query("SELECT co.id, c.course_code, c.course_name, c.course_type, co.section 
        FROM course_offerings co 
        JOIN courses c ON co.course_id = c.id 
        WHERE c.department_id IN ($dept_id_list) AND co.semester_id = {$current_semester['id']} AND co.status = 'open'
        ORDER BY c.course_code");
    while ($row = $co_result->fetch_assoc()) {
        $course_offerings[] = $row;
    }
}

// Get teachers
$teachers = [];
if (!empty($department_ids)) {
    $t_result = $db->query("SELECT t.id, t.employee_id, t.designation, up.first_name, up.last_name 
        FROM teachers t 
        JOIN users u ON t.user_id = u.id 
        JOIN user_profiles up ON u.id = up.user_id 
        WHERE t.department_id IN ($dept_id_list) AND u.status = 'active'
        ORDER BY up.first_name");
    while ($row = $t_result->fetch_assoc()) {
        $teachers[] = $row;
    }
}

// Get max classes per day setting
$max_classes_result = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'max_classes_per_teacher_per_day'");
$max_classes_per_day = 3;
if ($row = $max_classes_result->fetch_assoc()) {
    $max_classes_per_day = intval($row['setting_value']);
}

// Get existing assignments if template exists
$assignments = [];
if ($template) {
    $a_result = $db->query("SELECT ra.*, t.employee_id, up.first_name, up.last_name, c.course_code, c.course_name, co.section
        FROM routine_assignments ra
        JOIN teachers t ON ra.teacher_id = t.id
        JOIN users u ON t.user_id = u.id
        JOIN user_profiles up ON u.id = up.user_id
        JOIN course_offerings co ON ra.course_offering_id = co.id
        JOIN courses c ON co.course_id = c.id
        WHERE ra.template_id = {$template['id']}");
    while ($row = $a_result->fetch_assoc()) {
        $assignments[$row['time_slot_id']] = $row;
    }
}

// Sidebar
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<!-- Header -->
<div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white mb-8">
    <div class="relative z-10">
        <div class="flex items-center gap-3 mb-2">
            <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Admin</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                Schedule Control
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">Routine <span class="text-indigo-600">Manager</span></h1>
        <p class="text-xs text-slate-500 mt-1">
            <?php if ($current_semester): ?>
                <?php echo e($current_semester['name']); ?> • <?php echo e($current_semester['year']); ?>
            <?php else: ?>
                No Active Semester
            <?php endif; ?>
    </div>
    <div class="flex flex-wrap gap-3">
        <?php 
        // Get pending change requests count
        $pending_requests = 0;
        if (!empty($department_ids)) {
            $req_result = $db->query("SELECT COUNT(*) as cnt FROM routine_change_requests rcr JOIN routine_templates rt ON rcr.template_id = rt.id WHERE rt.department_id IN ($dept_id_list) AND rcr.status = 'pending'");
            if ($row = $req_result->fetch_assoc()) {
                $pending_requests = $row['cnt'];
            }
        }
        ?>
        
        <?php if ($pending_requests > 0): ?>
            <a href="routine-change-requests.php" class="btn-os bg-red-500 text-white border-red-600 hover:bg-red-600 flex items-center gap-2 shadow-[4px_4px_0px_#991b1b] relative">
                <i class="fas fa-exchange-alt"></i> Change Requests
                <span class="absolute -top-2 -right-2 w-6 h-6 bg-white text-red-600 rounded-full text-xs font-black flex items-center justify-center border-2 border-red-600"><?php echo $pending_requests; ?></span>
            </a>
        <?php endif; ?>
        
        <button onclick="generateStandardSlots()" class="btn-os bg-indigo-500 text-white border-indigo-600 hover:bg-indigo-600 flex items-center gap-2 shadow-[4px_4px_0px_#3730a3]">
            <i class="fas fa-magic"></i> Generate Slots
        </button>
        
        <?php if ($template && $template['status'] === 'draft'): ?>
            <button onclick="sendDraftNotification()" class="btn-os bg-amber-500 text-white border-amber-600 hover:bg-amber-600 flex items-center gap-2 shadow-[4px_4px_0px_#b45309]">
                <i class="fas fa-bell"></i> Notify Teachers
            </button>
            <button onclick="publishRoutine()" class="btn-os bg-green-500 text-white border-green-600 hover:bg-green-600 flex items-center gap-2 shadow-[4px_4px_0px_#166534]">
                <i class="fas fa-check-circle"></i> Publish Routine
            </button>
        <?php elseif ($template && $template['status'] === 'published'): ?>
            <span class="px-4 py-2 bg-green-100 text-green-800 text-sm font-black uppercase border-2 border-green-500">
                <i class="fas fa-check mr-2"></i> Published
            </span>
        <?php endif; ?>
        <button onclick="openSlotModal()" class="btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black flex items-center gap-2">
            <i class="fas fa-plus"></i> Add Time Slot
        </button>
    </div>
</div>

<!-- Break Time Info Banner -->
<div class="os-card p-4 mb-6 bg-slate-50 border-l-4 border-slate-500 flex items-center gap-4">
    <i class="fas fa-clock text-2xl text-slate-500"></i>
    <div>
        <div class="text-sm font-black uppercase">Slot Configuration</div>
        <div class="text-xs text-slate-500">
            5 slots × 90 min each | Break: <strong>01:30 PM – 02:00 PM</strong> | Working days: Sun – Thu
        </div>
    </div>
</div>


<?php if (empty($departments)): ?>
    <div class="os-card p-12 text-center">
        <i class="fas fa-exclamation-triangle text-6xl text-amber-400 mb-4"></i>
        <h2 class="text-xl font-black uppercase mb-2">No Department Access</h2>
        <p class="text-slate-500">You are not assigned to any department. Please contact the super admin.</p>
    </div>
<?php elseif (!$current_semester): ?>
    <div class="os-card p-12 text-center">
        <i class="fas fa-calendar-times text-6xl text-slate-300 mb-4"></i>
        <h2 class="text-xl font-black uppercase mb-2">No Active Semester</h2>
        <p class="text-slate-500">There is no active semester. Please set up academic periods first.</p>
    </div>
<?php else: ?>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Slot Types Panel -->
    <div class="lg:col-span-1">
        <div class="os-card p-0 bg-white sticky top-6">
            <div class="bg-black p-4 text-white border-b-2 border-black">
                <h3 class="text-sm font-black uppercase tracking-tight"><i class="fas fa-palette mr-2"></i>Slot Types</h3>
            </div>
            <div class="p-4 space-y-3">
                <?php foreach ($slot_types as $st): ?>
                    <div class="flex items-center justify-between p-3 border-2 border-black bg-slate-50">
                        <div class="flex items-center gap-3">
                            <span class="w-4 h-4 border-2 border-black" style="background-color: <?php echo e($st['color_code']); ?>"></span>
                            <div>
                                <div class="text-xs font-black uppercase"><?php echo e($st['name']); ?></div>
                                <div class="text-[10px] text-slate-500"><?php echo $st['duration_minutes']; ?> min</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="border-t-2 border-black p-4">
                <h4 class="text-xs font-black uppercase mb-3"><i class="fas fa-info-circle mr-2"></i>Quick Stats</h4>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Total Slots:</span>
                        <span class="font-black"><?php echo count($time_slots); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Assigned:</span>
                        <span class="font-black text-green-600"><?php echo count($assignments); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Unassigned:</span>
                        <span class="font-black text-amber-600"><?php echo count($time_slots) - count($assignments); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Max Classes/Teacher/Day:</span>
                        <span class="font-black"><?php echo $max_classes_per_day; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="border-t-2 border-black p-4">
                <h4 class="text-xs font-black uppercase mb-3"><i class="fas fa-chalkboard-teacher mr-2"></i>Teachers</h4>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    <?php foreach ($teachers as $t): ?>
                        <div class="text-xs p-2 border border-slate-200 bg-white hover:bg-yellow-50 transition cursor-pointer" data-teacher-id="<?php echo $t['id']; ?>">
                            <div class="font-bold"><?php echo e($t['first_name'] . ' ' . $t['last_name']); ?></div>
                            <div class="text-slate-400 text-[10px]"><?php echo e($t['designation']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Weekly Grid -->
    <div class="lg:col-span-3">
        <div class="os-card p-0 bg-white overflow-hidden">
            <div class="bg-slate-100 p-4 border-b-2 border-black flex justify-between items-center">
                <h3 class="text-sm font-black uppercase tracking-tight"><i class="fas fa-calendar-week mr-2"></i>Weekly Routine Grid</h3>
                <?php if ($template): ?>
                    <span class="px-3 py-1 text-xs font-black uppercase <?php echo $template['status'] === 'draft' ? 'bg-amber-100 text-amber-800 border-amber-400' : 'bg-green-100 text-green-800 border-green-400'; ?> border-2">
                        <?php echo ucfirst($template['status']); ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-black text-white">
                            <th class="p-3 text-[10px] font-black uppercase tracking-widest text-left border-r border-slate-700 w-20">Time</th>
                            <?php 
                            $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
                            foreach ($days as $day): 
                            ?>
                                <th class="p-3 text-[10px] font-black uppercase tracking-widest text-center border-r border-slate-700"><?php echo $day; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Group slots by time
                        $slots_by_time = [];
                        foreach ($time_slots as $slot) {
                            $time_key = $slot['start_time'] . '-' . $slot['end_time'];
                            if (!isset($slots_by_time[$time_key])) {
                                $slots_by_time[$time_key] = [
                                    'start' => $slot['start_time'],
                                    'end' => $slot['end_time'],
                                    'slots' => []
                                ];
                            }
                            $slots_by_time[$time_key]['slots'][$slot['day_of_week']] = $slot;
                        }
                        
                        if (empty($slots_by_time)):
                        ?>
                            <tr>
                                <td colspan="6" class="p-12 text-center">
                                    <i class="fas fa-clock text-4xl text-slate-300 mb-3 block"></i>
                                    <p class="text-sm font-bold text-slate-400 uppercase">No time slots defined</p>
                                    <p class="text-xs text-slate-400 mt-1">Click "Add Time Slot" to create your first slot</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($slots_by_time as $time_key => $time_data): ?>
                                <tr class="border-b-2 border-black">
                                    <td class="p-2 text-[10px] font-black bg-slate-50 border-r-2 border-black align-top">
                                        <div><?php echo date('h:i A', strtotime($time_data['start'])); ?></div>
                                        <div class="text-slate-400">to</div>
                                        <div><?php echo date('h:i A', strtotime($time_data['end'])); ?></div>
                                    </td>
                                    <?php foreach ($days as $day): ?>
                                        <td class="p-2 border-r border-slate-200 align-top min-w-[140px]">
                                            <?php if (isset($time_data['slots'][$day])): 
                                                $slot = $time_data['slots'][$day];
                                                $assignment = $assignments[$slot['id']] ?? null;
                                            ?>
                                                <?php if ($assignment): ?>
                                                    <!-- Assigned Slot -->
                                                    <div class="p-2 border-2 border-black rounded-none cursor-pointer hover:shadow-lg transition group relative"
                                                         style="background-color: <?php echo $slot['color_code']; ?>15; border-left: 4px solid <?php echo $slot['color_code']; ?>;"
                                                         onclick="openAssignModal(<?php echo $slot['id']; ?>, '<?php echo e($day); ?>', '<?php echo $time_data['start']; ?>', '<?php echo $time_data['end']; ?>')">
                                                        <div class="text-xs font-black uppercase" style="color: <?php echo $slot['color_code']; ?>">
                                                            <?php echo e($assignment['course_code']); ?>
                                                        </div>
                                                        <div class="text-[10px] text-slate-600 truncate">
                                                            <?php echo e($assignment['first_name'] . ' ' . $assignment['last_name']); ?>
                                                        </div>
                                                        <div class="text-[9px] text-slate-400 mt-1">
                                                            Sec: <?php echo e($assignment['section']); ?>
                                                            <?php if ($assignment['room_number']): ?>
                                                                • Rm: <?php echo e($assignment['room_number']); ?>
                                                            <?php endif; ?>
                                                        </div>
                                                        <button onclick="event.stopPropagation(); removeAssignment(<?php echo $assignment['id']; ?>)" 
                                                                class="absolute top-1 right-1 w-5 h-5 bg-red-500 text-white text-[10px] rounded-full opacity-0 group-hover:opacity-100 transition">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <!-- Empty Slot -->
                                                    <div class="p-3 border-2 border-dashed border-slate-300 rounded-none cursor-pointer hover:border-black hover:bg-yellow-50 transition text-center"
                                                         onclick="openAssignModal(<?php echo $slot['id']; ?>, '<?php echo e($day); ?>', '<?php echo $time_data['start']; ?>', '<?php echo $time_data['end']; ?>')">
                                                        <i class="fas fa-plus text-slate-300 text-lg"></i>
                                                        <div class="text-[9px] text-slate-400 font-bold uppercase mt-1"><?php echo e($slot['type_name']); ?></div>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <!-- No slot defined for this day/time -->
                                                <div class="p-3 bg-slate-50 text-center opacity-50">
                                                    <div class="text-[9px] text-slate-300">-</div>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Time Slot Modal -->
<div id="slotModal" class="fixed inset-0 z-[9999] hidden">
    <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" onclick="closeSlotModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white border-4 border-black shadow-2xl w-full max-w-md relative">
            <div class="bg-black p-6 text-white">
                <h3 class="text-xl font-black uppercase tracking-tighter">Add Time Slot</h3>
                <p class="text-xs text-slate-400 mt-1">Define a new period in the schedule</p>
            </div>
            <form id="slotForm" class="p-6 space-y-4">
                <input type="hidden" name="action" value="create_slot">
                <input type="hidden" name="department_id" value="<?php echo $department_ids[0] ?? 0; ?>">
                
                <div>
                    <label class="block text-xs font-black uppercase mb-2">Slot Type</label>
                    <select name="slot_type_id" class="w-full p-3 border-2 border-black text-sm font-bold focus:outline-none focus:bg-yellow-50" required>
                        <?php foreach ($slot_types as $st): ?>
                            <option value="<?php echo $st['id']; ?>" data-duration="<?php echo $st['duration_minutes']; ?>"><?php echo e($st['name']); ?> (<?php echo $st['duration_minutes']; ?> min)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-xs font-black uppercase mb-2">Day of Week</label>
                    <select name="day_of_week" class="w-full p-3 border-2 border-black text-sm font-bold focus:outline-none focus:bg-yellow-50" required>
                        <?php foreach ($days as $day): ?>
                            <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black uppercase mb-2">Start Time</label>
                        <input type="time" name="start_time" class="w-full p-3 border-2 border-black text-sm font-bold focus:outline-none focus:bg-yellow-50" required onchange="calculateEndTime()">
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase mb-2">End Time</label>
                        <input type="time" name="end_time" class="w-full p-3 border-2 border-black text-sm font-bold focus:outline-none focus:bg-yellow-50" required>
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs font-black uppercase mb-2">Slot Number (Period)</label>
                    <input type="number" name="slot_number" min="1" max="10" class="w-full p-3 border-2 border-black text-sm font-bold focus:outline-none focus:bg-yellow-50" required placeholder="e.g., 1, 2, 3...">
                </div>
                
                <div class="flex gap-4 pt-4 border-t-2 border-slate-200">
                    <button type="button" onclick="closeSlotModal()" class="flex-1 py-3 border-2 border-black text-black font-black uppercase text-xs hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="flex-1 py-3 bg-black text-white font-black uppercase text-xs hover:bg-yellow-500 hover:text-black transition shadow-[4px_4px_0px_#000]">Create Slot</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assignment Modal -->
<div id="assignModal" class="fixed inset-0 z-[9999] hidden">
    <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" onclick="closeAssignModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white border-4 border-black shadow-2xl w-full max-w-lg relative">
            <div class="bg-black p-6 text-white">
                <h3 class="text-xl font-black uppercase tracking-tighter">Assign Class</h3>
                <p class="text-xs text-slate-400 mt-1" id="assignSlotInfo">-</p>
            </div>
            <form id="assignForm" class="p-6 space-y-4">
                <input type="hidden" name="action" value="assign_class">
                <input type="hidden" name="time_slot_id" id="assign_slot_id">
                <input type="hidden" name="template_id" value="<?php echo $template['id'] ?? 0; ?>">
                
                <div>
                    <label class="block text-xs font-black uppercase mb-2">Select Teacher</label>
                    <select name="teacher_id" id="assign_teacher" class="w-full p-3 border-2 border-black text-sm font-bold focus:outline-none focus:bg-yellow-50" required onchange="checkTeacherAvailability()">
                        <option value="">-- Select Teacher --</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?php echo $t['id']; ?>"><?php echo e($t['first_name'] . ' ' . $t['last_name']); ?> (<?php echo e($t['designation']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Availability Warning -->
                <div id="availabilityWarning" class="hidden p-4 border-2 border-red-400 bg-red-50">
                    <div class="flex items-center gap-2 text-red-600 font-black text-sm mb-2">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Teacher Unavailable</span>
                    </div>
                    <p class="text-xs text-red-700" id="availabilityMessage"></p>
                    <div class="mt-3">
                        <p class="text-xs font-bold text-slate-600 uppercase mb-2">Available Slots:</p>
                        <div id="availableSlots" class="flex flex-wrap gap-2"></div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs font-black uppercase mb-2">Select Course</label>
                    <select name="course_offering_id" class="w-full p-3 border-2 border-black text-sm font-bold focus:outline-none focus:bg-yellow-50" required>
                        <option value="">-- Select Course --</option>
                        <?php foreach ($course_offerings as $co): ?>
                            <option value="<?php echo $co['id']; ?>"><?php echo e($co['course_code']); ?> - <?php echo e($co['course_name']); ?> (Sec: <?php echo e($co['section']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black uppercase mb-2">Room Number</label>
                        <input type="text" name="room_number" class="w-full p-3 border-2 border-black text-sm font-bold focus:outline-none focus:bg-yellow-50 uppercase" placeholder="e.g., 301">
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase mb-2">Building</label>
                        <input type="text" name="building" class="w-full p-3 border-2 border-black text-sm font-bold focus:outline-none focus:bg-yellow-50 uppercase" placeholder="e.g., Main">
                    </div>
                </div>
                
                <div class="flex gap-4 pt-4 border-t-2 border-slate-200">
                    <button type="button" onclick="closeAssignModal()" class="flex-1 py-3 border-2 border-black text-black font-black uppercase text-xs hover:bg-slate-100">Cancel</button>
                    <button type="submit" id="assignSubmitBtn" class="flex-1 py-3 bg-black text-white font-black uppercase text-xs hover:bg-yellow-500 hover:text-black transition shadow-[4px_4px_0px_#000]">Assign Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
const MAX_CLASSES_PER_DAY = <?php echo $max_classes_per_day; ?>;
let currentSlotDay = '';

// Slot Modal
function openSlotModal() {
    document.getElementById('slotModal').classList.remove('hidden');
}

function closeSlotModal() {
    document.getElementById('slotModal').classList.add('hidden');
    document.getElementById('slotForm').reset();
}

// Calculate end time based on slot type duration
function calculateEndTime() {
    const startTime = document.querySelector('[name="start_time"]').value;
    const slotType = document.querySelector('[name="slot_type_id"]');
    const duration = slotType.options[slotType.selectedIndex].dataset.duration;
    
    if (startTime && duration) {
        const [hours, mins] = startTime.split(':').map(Number);
        const totalMins = hours * 60 + mins + parseInt(duration);
        const endHours = Math.floor(totalMins / 60) % 24;
        const endMins = totalMins % 60;
        document.querySelector('[name="end_time"]').value = 
            String(endHours).padStart(2, '0') + ':' + String(endMins).padStart(2, '0');
    }
}

// Handle slot type change
document.querySelector('[name="slot_type_id"]')?.addEventListener('change', calculateEndTime);

// Slot Form Submit
document.getElementById('slotForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    try {
        const response = await fetch(BASE_URL + '/api/admin/routine.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            showGlobalModal('Success', 'Time slot created successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showGlobalModal('Error', result.error || 'Failed to create slot', 'error');
        }
    } catch (error) {
        showGlobalModal('Error', 'Network error occurred', 'error');
    }
});

// Assignment Modal
function openAssignModal(slotId, day, startTime, endTime) {
    document.getElementById('assignModal').classList.remove('hidden');
    document.getElementById('assign_slot_id').value = slotId;
    document.getElementById('assignSlotInfo').textContent = `${day} • ${startTime} - ${endTime}`;
    currentSlotDay = day;
    
    // Reset warning
    document.getElementById('availabilityWarning').classList.add('hidden');
}

function closeAssignModal() {
    document.getElementById('assignModal').classList.add('hidden');
    document.getElementById('assignForm').reset();
    currentSlotDay = '';
}

// Check teacher availability
async function checkTeacherAvailability() {
    const teacherId = document.getElementById('assign_teacher').value;
    const slotId = document.getElementById('assign_slot_id').value;
    const warningDiv = document.getElementById('availabilityWarning');
    const submitBtn = document.getElementById('assignSubmitBtn');
    
    if (!teacherId) {
        warningDiv.classList.add('hidden');
        submitBtn.disabled = false;
        return;
    }
    
    try {
        const response = await fetch(BASE_URL + `/api/admin/routine.php?action=check_availability&teacher_id=${teacherId}&time_slot_id=${slotId}&day=${currentSlotDay}`);
        const result = await response.json();
        
        if (!result.available) {
            warningDiv.classList.remove('hidden');
            document.getElementById('availabilityMessage').textContent = result.message;
            
            // Show available slots
            const slotsDiv = document.getElementById('availableSlots');
            slotsDiv.innerHTML = '';
            if (result.available_slots && result.available_slots.length > 0) {
                result.available_slots.forEach(slot => {
                    slotsDiv.innerHTML += `<span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-bold border border-green-400">${slot.day} P${slot.slot_number}</span>`;
                });
            } else {
                slotsDiv.innerHTML = '<span class="text-xs text-slate-500">No available slots today</span>';
            }
            
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            warningDiv.classList.add('hidden');
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    } catch (error) {
        console.error('Availability check failed:', error);
    }
}

// Assignment Form Submit
document.getElementById('assignForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    try {
        const response = await fetch(BASE_URL + '/api/admin/routine.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            showGlobalModal('Success', 'Class assigned successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showGlobalModal('Error', result.error || 'Failed to assign class', 'error');
        }
    } catch (error) {
        showGlobalModal('Error', 'Network error occurred', 'error');
    }
});

// Remove assignment
async function removeAssignment(assignmentId) {
    if (!confirm('Remove this class assignment?')) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'remove_assignment');
        formData.append('assignment_id', assignmentId);
        
        const response = await fetch(BASE_URL + '/api/admin/routine.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            showGlobalModal('Success', 'Assignment removed!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showGlobalModal('Error', result.error || 'Failed to remove', 'error');
        }
    } catch (error) {
        showGlobalModal('Error', 'Network error occurred', 'error');
    }
}

// Publish routine
async function publishRoutine() {
    if (!confirm('Publish this routine? It will become visible to all teachers.')) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'publish_routine');
        formData.append('template_id', <?php echo $template['id'] ?? 0; ?>);
        
        const response = await fetch(BASE_URL + '/api/admin/routine.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            showGlobalModal('Success', 'Routine published successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showGlobalModal('Error', result.error || 'Failed to publish', 'error');
        }
    } catch (error) {
        showGlobalModal('Error', 'Network error occurred', 'error');
    }
}

// Generate Standard Slots (5 slots x 5 days)
async function generateStandardSlots() {
    if (!confirm('Generate standard time slots?\n\nThis will create 5 slots (90 min each) for Sunday through Thursday:\n• 09:00-10:30\n• 10:30-12:00\n• 12:00-13:30\n• 14:00-15:30 (after break)\n• 15:30-17:00\n\nExisting slots will be kept.')) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'generate_standard_slots');
        formData.append('department_id', <?php echo $department_ids[0] ?? 0; ?>);
        
        const response = await fetch(BASE_URL + '/api/admin/routine.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            showGlobalModal('Success', result.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showGlobalModal('Error', result.error || 'Failed to generate slots', 'error');
        }
    } catch (error) {
        showGlobalModal('Error', 'Network error occurred', 'error');
    }
}

// Send Draft Notification to Teachers
async function sendDraftNotification() {
    if (!confirm('Send notification to all teachers about this draft routine?\n\nTeachers will be notified to review and submit change requests.')) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'send_draft_notification');
        formData.append('template_id', <?php echo $template['id'] ?? 0; ?>);
        
        const response = await fetch(BASE_URL + '/api/admin/routine.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            showGlobalModal('Success', result.message, 'success');
        } else {
            showGlobalModal('Error', result.error || 'Failed to send notifications', 'error');
        }
    } catch (error) {
        showGlobalModal('Error', 'Network error occurred', 'error');
    }
}

// Auto-fill teacher and room when course is selected
document.querySelector('[name="course_offering_id"]')?.addEventListener('change', async function() {
    const courseId = this.value;
    if (!courseId) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'get_course_defaults');
        formData.append('course_offering_id', courseId);
        
        const response = await fetch(BASE_URL + '/api/admin/routine.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            // Auto-fill teacher
            if (result.teacher_id) {
                const teacherSelect = document.querySelector('[name="teacher_id"]');
                if (teacherSelect) {
                    teacherSelect.value = result.teacher_id;
                }
            }
            // Auto-fill room
            if (result.room) {
                const roomInput = document.querySelector('[name="room_number"]');
                if (roomInput) {
                    roomInput.value = result.room;
                }
            }
            // Auto-fill building
            if (result.building) {
                const buildingInput = document.querySelector('[name="building"]');
                if (buildingInput) {
                    buildingInput.value = result.building;
                }
            }
        }
    } catch (error) {
        console.log('Could not auto-fill course defaults');
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
