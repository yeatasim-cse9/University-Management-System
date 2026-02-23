<?php
/**
 * Dynamic Class Routine Management
 * ACADEMIX - Academic Management System
 * 
 * Features:
 * - Configurable time slots (Theory/Lab/Break)
 * - Grid-based routine view with dynamic columns
 * - Smart conflict detection (teacher/room)
 * - Draft/Publish workflow
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('admin');

$page_title = 'Manage Class Routine';
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
$department_id = $departments[0]['id'] ?? 0;

// Get active semester
$semester_query = $db->query("SELECT s.id, s.name, ay.year FROM semesters s JOIN academic_years ay ON s.academic_year_id = ay.id WHERE s.status = 'active' LIMIT 1");
$active_semester = $semester_query->fetch_assoc();
$semester_id = $active_semester['id'] ?? 0;

// Get or create draft routine
$draft_query = $db->prepare("SELECT * FROM routine_drafts WHERE department_id = ? AND semester_id = ? AND status IN ('draft', 'review') ORDER BY id DESC LIMIT 1");
$draft_query->bind_param("ii", $department_id, $semester_id);
$draft_query->execute();
$current_draft = $draft_query->get_result()->fetch_assoc();

if (!$current_draft && $department_id > 0 && $semester_id > 0) {
    // Create new draft
    $create_draft = $db->prepare("INSERT INTO routine_drafts (department_id, semester_id, title, status, created_by) VALUES (?, ?, ?, 'draft', ?)");
    $title = ($active_semester['name'] ?? 'Current') . ' Class Routine';
    $create_draft->bind_param("iisi", $department_id, $semester_id, $title, $user_id);
    $create_draft->execute();
    $draft_id = $db->insert_id;
    
    $draft_query->execute();
    $current_draft = $draft_query->get_result()->fetch_assoc();
} else {
    $draft_id = $current_draft['id'] ?? 0;
}

// Fetch routine slots
$slots_query = $db->prepare("SELECT * FROM routine_slots WHERE department_id = ? AND is_active = 1 ORDER BY display_order ASC");
$slots_query->bind_param("i", $department_id);
$slots_query->execute();
$slots = $slots_query->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch rooms
$rooms_query = $db->prepare("SELECT * FROM rooms WHERE (department_id = ? OR department_id IS NULL) AND status = 'active' ORDER BY building, room_number");
$rooms_query->bind_param("i", $department_id);
$rooms_query->execute();
$rooms = $rooms_query->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch course offerings with teachers
$courses_query = $db->prepare("
    SELECT co.id, c.course_code, c.course_name, c.course_type, co.section,
           t.id as teacher_id, CONCAT(up.first_name, ' ', up.last_name) as teacher_name,
           u.id as teacher_user_id
    FROM course_offerings co
    JOIN courses c ON co.course_id = c.id
    JOIN semesters s ON co.semester_id = s.id
    LEFT JOIN teacher_courses tc ON co.id = tc.course_offering_id
    LEFT JOIN teachers t ON tc.teacher_id = t.id
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE c.department_id = ? AND s.id = ? AND co.status = 'open'
    ORDER BY c.course_code
");
$courses_query->bind_param("ii", $department_id, $semester_id);
$courses_query->execute();
$courses = $courses_query->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch existing assignments for current draft
$assignments_query = $db->prepare("
    SELECT ra.*, rs.slot_name, rs.slot_type, rs.start_time, rs.end_time,
           c.course_code, c.course_name, c.course_type, co.section,
           r.room_number, r.building,
           CONCAT(up.first_name, ' ', up.last_name) as teacher_name
    FROM routine_assignments ra
    JOIN routine_slots rs ON ra.slot_id = rs.id
    JOIN course_offerings co ON ra.course_offering_id = co.id
    JOIN courses c ON co.course_id = c.id
    LEFT JOIN rooms r ON ra.room_id = r.id
    LEFT JOIN teacher_courses tc ON co.id = tc.course_offering_id
    LEFT JOIN teachers t ON tc.teacher_id = t.id
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE ra.routine_draft_id = ?
");
$assignments_query->bind_param("i", $draft_id);
$assignments_query->execute();
$assignments_result = $assignments_query->get_result()->fetch_all(MYSQLI_ASSOC);

// Index assignments by slot_id and day
$assignments = [];
foreach ($assignments_result as $a) {
    $key = $a['slot_id'] . '_' . $a['day_of_week'];
    $assignments[$key] = $a;
}

// Days of week (Bangladesh standard - starts from Sunday)
$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];

// Fetch pending change requests count
$change_requests_count = 0;
if ($draft_id) {
    $cr_query = $db->prepare("SELECT COUNT(*) as count FROM routine_change_requests WHERE routine_draft_id = ? AND status = 'pending'");
    $cr_query->bind_param("i", $draft_id);
    $cr_query->execute();
    $change_requests_count = $cr_query->get_result()->fetch_assoc()['count'] ?? 0;
}

// Sidebar
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<!-- Header Section -->
<div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white mb-8">
    <div class="relative z-10">
        <div class="flex items-center gap-3 mb-2">
            <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Routine</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                <?php echo e($active_semester['name'] ?? 'No Active Semester'); ?>
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">Manage Class <span class="text-black">Routine</span></h1>
        <p class="text-xs text-slate-500 mt-1">Define slots, assign teachers, and publish schedules.</p>
    </div>
    
    <div class="flex items-center gap-3">
        <?php if ($change_requests_count > 0): ?>
        <a href="?view=requests" class="btn-os bg-amber-400 text-black border-black hover:bg-amber-500 flex items-center gap-2 relative">
            <i class="fas fa-comments"></i> Change Requests
            <span class="absolute -top-2 -right-2 w-5 h-5 bg-red-600 text-white text-[10px] font-black flex items-center justify-center border border-black"><?php echo $change_requests_count; ?></span>
        </a>
        <?php endif; ?>
        
        <button onclick="openConfigureSlotsModal()" class="btn-os bg-white text-black border-black hover:bg-slate-100 flex items-center gap-2">
            <i class="fas fa-cog"></i> Configure Slots
        </button>
        
        <?php if ($current_draft && $current_draft['status'] === 'draft'): ?>
        <button onclick="publishRoutine()" class="btn-os bg-emerald-500 text-white border-black hover:bg-emerald-600 flex items-center gap-2 shadow-[4px_4px_0px_#000]">
            <i class="fas fa-check-circle"></i> Publish Routine
        </button>
        <?php elseif ($current_draft && $current_draft['status'] === 'review'): ?>
        <span class="px-4 py-2 bg-amber-100 text-amber-800 border-2 border-amber-400 text-xs font-black uppercase">Under Review</span>
        <?php endif; ?>
    </div>
</div>

<!-- Draft Status Banner -->
<?php if ($current_draft): ?>
<div class="mb-6 p-4 border-2 border-dashed <?php echo $current_draft['status'] === 'draft' ? 'border-blue-400 bg-blue-50' : 'border-amber-400 bg-amber-50'; ?> flex items-center gap-4">
    <div class="w-10 h-10 bg-<?php echo $current_draft['status'] === 'draft' ? 'blue' : 'amber'; ?>-500 text-white flex items-center justify-center border-2 border-black">
        <i class="fas fa-<?php echo $current_draft['status'] === 'draft' ? 'file-alt' : 'hourglass-half'; ?>"></i>
    </div>
    <div class="flex-1">
        <p class="text-sm font-black uppercase"><?php echo $current_draft['status'] === 'draft' ? 'Draft Mode' : 'Under Review'; ?></p>
        <p class="text-xs text-slate-600">
            <?php echo $current_draft['status'] === 'draft' 
                ? 'Changes are not visible to teachers yet. Click "Publish Routine" when ready.' 
                : 'Routine has been sent for teacher review. Waiting for feedback.'; ?>
        </p>
    </div>
    <span class="text-[10px] font-mono text-slate-400">v<?php echo $current_draft['version']; ?></span>
</div>
<?php endif; ?>

<!-- Routine Grid -->
<div class="os-card p-0 bg-white overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[1200px]" id="routineGrid">
            <thead>
                <tr class="bg-black text-white">
                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest w-24 border-r border-white/20">Day / Time</th>
                    <?php foreach ($slots as $slot): ?>
                    <th class="px-3 py-3 text-center text-[10px] font-black uppercase tracking-widest border-r border-white/20 <?php echo $slot['slot_type'] === 'break' ? 'bg-amber-600' : ($slot['slot_type'] === 'lab' ? 'bg-purple-700' : ''); ?>">
                        <div class="flex flex-col items-center gap-1">
                            <span><?php echo e($slot['slot_name']); ?></span>
                            <span class="text-[9px] font-normal opacity-75">
                                <i class="far fa-clock mr-1"></i>
                                <?php echo date('h:i', strtotime($slot['start_time'])); ?>-<?php echo date('h:i', strtotime($slot['end_time'])); ?>
                            </span>
                            <?php if ($slot['slot_type'] === 'lab'): ?>
                            <span class="text-[8px] px-1 py-0.5 bg-white/20 rounded uppercase">Lab</span>
                            <?php endif; ?>
                        </div>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($days as $day): ?>
                <tr class="border-b-2 border-black/10 hover:bg-slate-50">
                    <td class="px-4 py-6 text-sm font-black uppercase bg-slate-50 border-r-2 border-black/10"><?php echo $day; ?></td>
                    <?php foreach ($slots as $slot): 
                        $key = $slot['id'] . '_' . $day;
                        $assignment = $assignments[$key] ?? null;
                        $isBreak = $slot['slot_type'] === 'break';
                    ?>
                    <td class="px-2 py-2 text-center border-r border-black/5 <?php echo $isBreak ? 'bg-amber-50' : ''; ?>" 
                        data-slot-id="<?php echo $slot['id']; ?>" 
                        data-day="<?php echo $day; ?>"
                        data-slot-type="<?php echo $slot['slot_type']; ?>">
                        
                        <?php if ($isBreak): ?>
                            <div class="text-amber-600 text-[10px] font-black uppercase tracking-widest py-4">
                                <i class="fas fa-coffee"></i> Break
                            </div>
                        <?php elseif ($assignment): ?>
                            <div class="routine-cell p-3 bg-white border-2 border-black shadow-[3px_3px_0px_#000] cursor-pointer hover:shadow-[1px_1px_0px_#000] hover:translate-x-[2px] hover:translate-y-[2px] transition-all group"
                                 onclick="openEditAssignmentModal(<?php echo htmlspecialchars(json_encode($assignment)); ?>)">
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs font-black text-black uppercase"><?php echo e($assignment['course_code']); ?></span>
                                    <span class="text-[9px] text-slate-500"><?php echo e($assignment['teacher_name'] ?? 'TBA'); ?></span>
                                    <span class="text-[9px] text-slate-400">
                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                        <?php echo e($assignment['room_number'] ?? 'TBA'); ?>
                                    </span>
                                </div>
                                <div class="absolute top-1 right-1 hidden group-hover:flex gap-1">
                                    <span class="w-5 h-5 bg-red-500 text-white text-[10px] flex items-center justify-center border border-black" onclick="event.stopPropagation(); removeAssignment(<?php echo $assignment['id']; ?>)">
                                        <i class="fas fa-times"></i>
                                    </span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="empty-slot p-4 border-2 border-dashed border-slate-200 cursor-pointer hover:border-black hover:bg-yellow-50 transition-all rounded"
                                 onclick="openScheduleClassModal(<?php echo $slot['id']; ?>, '<?php echo $day; ?>', '<?php echo $slot['slot_type']; ?>')">
                                <span class="text-slate-300 text-lg"><i class="fas fa-plus"></i></span>
                            </div>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Configure Slots Modal -->
<div id="configureSlotsModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" onclick="closeConfigureSlotsModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white border-4 border-black shadow-[8px_8px_0px_#000] w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="bg-white p-6 border-b-2 border-black flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-black text-white flex items-center justify-center">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black uppercase tracking-tight">Configure Routine Slots</h3>
                        <p class="text-xs text-slate-500">Define the grid structure for the routine. You can mix Theory (short) and Lab (long) slots.</p>
                    </div>
                </div>
                <button onclick="closeConfigureSlotsModal()" class="w-8 h-8 flex items-center justify-center border-2 border-black hover:bg-black hover:text-white transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-6" id="slotsListContainer">
                <!-- Slots will be loaded here -->
            </div>
            
            <!-- Add New Slot Form -->
            <div class="p-6 border-t-2 border-black bg-slate-50">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-3">Add New Slot</p>
                <form id="addSlotForm" class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-[100px]">
                        <label class="block text-[9px] font-bold uppercase text-slate-500 mb-1">Start Time</label>
                        <input type="time" name="start_time" required class="w-full px-3 py-2 border-2 border-black text-sm font-bold">
                    </div>
                    <div class="flex-1 min-w-[100px]">
                        <label class="block text-[9px] font-bold uppercase text-slate-500 mb-1">End Time</label>
                        <input type="time" name="end_time" required class="w-full px-3 py-2 border-2 border-black text-sm font-bold">
                    </div>
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-[9px] font-bold uppercase text-slate-500 mb-1">Label</label>
                        <input type="text" name="slot_name" placeholder="e.g. Lab Session" required class="w-full px-3 py-2 border-2 border-black text-sm font-bold">
                    </div>
                    <div class="flex-1 min-w-[100px]">
                        <label class="block text-[9px] font-bold uppercase text-slate-500 mb-1">Type</label>
                        <select name="slot_type" required class="w-full px-3 py-2 border-2 border-black text-sm font-bold bg-white">
                            <option value="theory">Theory</option>
                            <option value="lab">Lab</option>
                            <option value="break">Break</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black px-4 py-2">
                        <i class="fas fa-plus mr-1"></i> Add
                    </button>
                </form>
            </div>
            
            <div class="p-4 border-t-2 border-black bg-white flex justify-end gap-3">
                <button onclick="closeConfigureSlotsModal()" class="btn-os bg-white text-black border-black hover:bg-slate-100">Cancel</button>
                <button onclick="saveSlotConfiguration()" class="btn-os bg-blue-600 text-white border-black hover:bg-blue-700">Save Configuration</button>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Class Modal -->
<div id="scheduleClassModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" onclick="closeScheduleClassModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white border-4 border-black shadow-[8px_8px_0px_#000] w-full max-w-md">
            <div class="p-6 border-b-2 border-black">
                <h3 class="text-lg font-black uppercase tracking-tight">Schedule Class</h3>
                <p class="text-xs text-slate-500 mt-1" id="scheduleClassSubtitle">SUNDAY • 08:00 - 08:50 • THEORY</p>
            </div>
            
            <form id="scheduleClassForm" class="p-6 space-y-4">
                <input type="hidden" name="slot_id" id="schedule_slot_id">
                <input type="hidden" name="day_of_week" id="schedule_day">
                <input type="hidden" name="routine_draft_id" value="<?php echo $draft_id; ?>">
                
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest mb-2">Select Course</label>
                    <select name="course_offering_id" id="course_select" required 
                            class="w-full px-4 py-3 border-2 border-black text-sm font-bold focus:border-blue-500 focus:ring-0"
                            onchange="autoFillCourseDetails()">
                        <option value="">-- Choose a course --</option>
                        <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>" 
                                data-teacher="<?php echo e($course['teacher_name'] ?? 'Not Assigned'); ?>"
                                data-teacher-id="<?php echo $course['teacher_id'] ?? ''; ?>"
                                data-type="<?php echo $course['course_type']; ?>">
                            <?php echo e($course['course_code'] . ' - ' . $course['course_name'] . ' (' . strtoupper($course['course_type']) . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-[9px] text-emerald-600 mt-1 hidden" id="autoFillNotice">
                        <i class="fas fa-check-circle mr-1"></i> Auto-filled teacher & room
                    </p>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest mb-2">Assigned Teacher</label>
                        <input type="text" id="teacher_display" readonly 
                               class="w-full px-4 py-3 border-2 border-black bg-slate-50 text-sm font-bold text-slate-600 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest mb-2">Room / Lab</label>
                        <select name="room_id" id="room_select" required 
                                class="w-full px-4 py-3 border-2 border-black text-sm font-bold focus:border-blue-500 focus:ring-0">
                            <option value="">-- Select Room --</option>
                            <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room['id']; ?>" data-type="<?php echo $room['room_type']; ?>">
                                <?php echo e($room['room_number'] . ' (' . $room['building'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Conflict Warning -->
                <div id="conflictWarning" class="hidden p-4 bg-red-50 border-2 border-red-500 text-red-700">
                    <p class="text-xs font-bold"><i class="fas fa-exclamation-triangle mr-1"></i> Conflict Detected</p>
                    <p class="text-xs mt-1" id="conflictMessage"></p>
                </div>
            </form>
            
            <div class="p-4 border-t-2 border-black bg-slate-50 flex justify-end gap-3">
                <button onclick="closeScheduleClassModal()" class="btn-os bg-white text-black border-black hover:bg-slate-100">Cancel</button>
                <button onclick="submitScheduleClass()" class="btn-os bg-emerald-500 text-white border-black hover:bg-emerald-600">
                    <i class="fas fa-calendar-check mr-1"></i> Assign Class
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$extra_scripts = '
<script>
const BASE_URL = "' . BASE_URL . '";
const DRAFT_ID = ' . $draft_id . ';
const DEPARTMENT_ID = ' . $department_id . ';

// ===== Configure Slots Modal =====
function openConfigureSlotsModal() {
    document.getElementById("configureSlotsModal").classList.remove("hidden");
    loadSlotsList();
}

function closeConfigureSlotsModal() {
    document.getElementById("configureSlotsModal").classList.add("hidden");
}

async function loadSlotsList() {
    const container = document.getElementById("slotsListContainer");
    container.innerHTML = `<div class="text-center py-8 text-slate-400"><i class="fas fa-spinner fa-spin text-2xl"></i></div>`;
    
    try {
        const response = await fetch(`${BASE_URL}/api/admin/routine.php?action=get_slots`);
        const slots = await response.json();
        
        if (slots.length === 0) {
            container.innerHTML = `<div class="text-center py-8 text-slate-400">No slots configured yet.</div>`;
            return;
        }
        
        let html = `<div class="space-y-2">`;
        slots.forEach(slot => {
            const typeColor = slot.slot_type === "break" ? "bg-amber-100 text-amber-700" : 
                              (slot.slot_type === "lab" ? "bg-purple-100 text-purple-700" : "bg-blue-100 text-blue-700");
            html += `
                <div class="flex items-center gap-4 p-4 border-2 border-black ${slot.slot_type === "break" ? "bg-amber-50" : "bg-white"}">
                    <div class="text-sm font-mono text-slate-500 w-24">
                        ${formatTime(slot.start_time)} - ${formatTime(slot.end_time)}
                    </div>
                    <div class="flex-1 text-sm font-bold">${slot.slot_name}</div>
                    <span class="px-2 py-1 ${typeColor} text-[9px] font-black uppercase">${slot.slot_type}</span>
                    <button onclick="deleteSlot(${slot.id})" class="w-8 h-8 flex items-center justify-center text-red-500 hover:bg-red-500 hover:text-white border border-red-500 transition-colors">
                        <i class="fas fa-trash-alt text-xs"></i>
                    </button>
                </div>
            `;
        });
        html += `</div>`;
        container.innerHTML = html;
    } catch (error) {
        container.innerHTML = `<div class="text-center py-8 text-red-500">Error loading slots</div>`;
    }
}

function formatTime(timeStr) {
    if (!timeStr) return "";
    const [h, m] = timeStr.split(":");
    const hour = parseInt(h);
    const ampm = hour >= 12 ? "PM" : "AM";
    const hour12 = hour % 12 || 12;
    return `${hour12}:${m}`;
}

document.getElementById("addSlotForm").addEventListener("submit", async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    data.department_id = DEPARTMENT_ID;
    
    try {
        const response = await fetch(`${BASE_URL}/api/admin/routine.php?action=save_slot`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        
        if (result.success) {
            this.reset();
            loadSlotsList();
            showGlobalModal("Success", "Slot added successfully!", "success");
        } else {
            showGlobalModal("Error", result.error || "Failed to add slot", "error");
        }
    } catch (error) {
        showGlobalModal("Error", "Network error", "error");
    }
});

async function deleteSlot(slotId) {
    if (!confirm("Delete this slot? Any existing assignments will be removed.")) return;
    
    try {
        const response = await fetch(`${BASE_URL}/api/admin/routine.php?action=delete_slot&id=${slotId}`, {
            method: "DELETE"
        });
        const result = await response.json();
        
        if (result.success) {
            loadSlotsList();
        } else {
            showGlobalModal("Error", result.error || "Failed to delete slot", "error");
        }
    } catch (error) {
        showGlobalModal("Error", "Network error", "error");
    }
}

function saveSlotConfiguration() {
    closeConfigureSlotsModal();
    window.location.reload();
}

// ===== Schedule Class Modal =====
function openScheduleClassModal(slotId, day, slotType) {
    document.getElementById("scheduleClassModal").classList.remove("hidden");
    document.getElementById("schedule_slot_id").value = slotId;
    document.getElementById("schedule_day").value = day;
    document.getElementById("scheduleClassSubtitle").textContent = `${day.toUpperCase()} • ${slotType.toUpperCase()}`;
    document.getElementById("scheduleClassForm").reset();
    document.getElementById("autoFillNotice").classList.add("hidden");
    document.getElementById("conflictWarning").classList.add("hidden");
    document.getElementById("teacher_display").value = "";
    
    // Filter courses based on slot type
    const courseSelect = document.getElementById("course_select");
    Array.from(courseSelect.options).forEach(option => {
        if (option.value === "") return;
        const courseType = option.dataset.type;
        if (slotType === "lab" && courseType !== "lab") {
            option.style.display = "none";
        } else if (slotType === "theory" && courseType === "lab") {
            option.style.display = "none";
        } else {
            option.style.display = "";
        }
    });
}

function closeScheduleClassModal() {
    document.getElementById("scheduleClassModal").classList.add("hidden");
}

function autoFillCourseDetails() {
    const select = document.getElementById("course_select");
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        document.getElementById("teacher_display").value = option.dataset.teacher || "Not Assigned";
        document.getElementById("autoFillNotice").classList.remove("hidden");
        
        // Check for conflicts
        checkConflicts();
    } else {
        document.getElementById("teacher_display").value = "";
        document.getElementById("autoFillNotice").classList.add("hidden");
    }
}

async function checkConflicts() {
    const courseId = document.getElementById("course_select").value;
    const roomId = document.getElementById("room_select").value;
    const slotId = document.getElementById("schedule_slot_id").value;
    const day = document.getElementById("schedule_day").value;
    
    if (!courseId) return;
    
    try {
        const response = await fetch(`${BASE_URL}/api/admin/routine.php?action=check_conflicts&course_offering_id=${courseId}&room_id=${roomId}&slot_id=${slotId}&day=${day}&draft_id=${DRAFT_ID}`);
        const result = await response.json();
        
        const warning = document.getElementById("conflictWarning");
        const message = document.getElementById("conflictMessage");
        
        if (result.hasConflict) {
            warning.classList.remove("hidden");
            message.textContent = result.message;
        } else {
            warning.classList.add("hidden");
        }
    } catch (error) {
        console.error("Conflict check failed:", error);
    }
}

document.getElementById("room_select").addEventListener("change", checkConflicts);

async function submitScheduleClass() {
    const form = document.getElementById("scheduleClassForm");
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // Validation
    if (!data.course_offering_id || !data.room_id) {
        showGlobalModal("Error", "Please select both a course and a room.", "error");
        return;
    }
    
    try {
        const response = await fetch(`${BASE_URL}/api/admin/routine.php?action=assign_class`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        
        if (result.success) {
            closeScheduleClassModal();
            window.location.reload();
        } else {
            showGlobalModal("Error", result.error || "Failed to assign class", "error");
        }
    } catch (error) {
        showGlobalModal("Error", "Network error", "error");
    }
}

// ===== Edit/Remove Assignment =====
function openEditAssignmentModal(assignment) {
    // For now, just show details. Can be extended for editing.
    alert(`Course: ${assignment.course_code}\\nTeacher: ${assignment.teacher_name || "TBA"}\\nRoom: ${assignment.room_number || "TBA"}`);
}

async function removeAssignment(assignmentId) {
    if (!confirm("Remove this class from the routine?")) return;
    
    try {
        const response = await fetch(`${BASE_URL}/api/admin/routine.php?action=remove_assignment&id=${assignmentId}`, {
            method: "DELETE"
        });
        const result = await response.json();
        
        if (result.success) {
            window.location.reload();
        } else {
            showGlobalModal("Error", result.error || "Failed to remove assignment", "error");
        }
    } catch (error) {
        showGlobalModal("Error", "Network error", "error");
    }
}

// ===== Publish Routine =====
async function publishRoutine() {
    if (!confirm("Are you sure you want to publish this routine?\\n\\nOnce published:\\n- All teachers will be notified\\n- The routine will become active\\n- Students will see the updated schedule")) return;
    
    try {
        const response = await fetch(`${BASE_URL}/api/admin/routine.php?action=publish_routine`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ draft_id: DRAFT_ID })
        });
        const result = await response.json();
        
        if (result.success) {
            showGlobalModal("Success", "Routine published successfully! All teachers have been notified.", "success");
            setTimeout(() => window.location.reload(), 2000);
        } else {
            showGlobalModal("Error", result.error || "Failed to publish routine", "error");
        }
    } catch (error) {
        showGlobalModal("Error", "Network error", "error");
    }
}
</script>
';
?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
