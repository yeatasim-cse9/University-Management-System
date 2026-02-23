<?php
/**
 * My Change Requests - Teacher View
 * ACADEMIX - Academic Management System
 * Shows teacher's submitted change requests and allows new submissions
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('teacher');

$page_title = 'My Change Requests';
$user_id = get_current_user_id();

// Get teacher info
$teacher_id = 0;
$department_id = 0;
$stmt = $db->prepare("SELECT t.id, t.department_id FROM teachers t WHERE t.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher_result = $stmt->get_result()->fetch_assoc();
if ($teacher_result) {
    $teacher_id = $teacher_result['id'];
    $department_id = $teacher_result['department_id'];
}

// Get current template
$template = null;
if ($department_id) {
    $template = $db->query("
        SELECT rt.*, s.name as semester_name
        FROM routine_templates rt
        JOIN semesters s ON rt.semester_id = s.id
        WHERE rt.department_id = $department_id AND rt.status IN ('draft', 'published')
        ORDER BY rt.status = 'published' DESC
        LIMIT 1
    ")->fetch_assoc();
}

// Get teacher's change requests
$requests = [];
if ($teacher_id) {
    $result = $db->query("
        SELECT rcr.*, 
               c.course_code, c.course_name, co.section,
               rts_cur.start_time as current_start, rts_cur.end_time as current_end, rts_cur.day_of_week as current_day, rts_cur.slot_number as current_slot_num,
               rts_req.start_time as requested_start, rts_req.end_time as requested_end, rts_req.day_of_week as requested_day, rts_req.slot_number as requested_slot_num,
               resp_up.first_name as responder_first, resp_up.last_name as responder_last
        FROM routine_change_requests rcr
        LEFT JOIN routine_assignments ra ON rcr.assignment_id = ra.id
        LEFT JOIN course_offerings co ON ra.course_offering_id = co.id
        LEFT JOIN courses c ON co.course_id = c.id
        LEFT JOIN routine_time_slots rts_cur ON rcr.current_slot_id = rts_cur.id
        LEFT JOIN routine_time_slots rts_req ON rcr.requested_slot_id = rts_req.id
        LEFT JOIN users resp ON rcr.responded_by = resp.id
        LEFT JOIN user_profiles resp_up ON resp.id = resp_up.user_id
        WHERE rcr.teacher_id = $teacher_id
        ORDER BY rcr.created_at DESC
    ");
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

// Get teacher's assignments (for change request form)
$my_assignments = [];
if ($template && $teacher_id) {
    $a_result = $db->query("
        SELECT ra.id, ra.time_slot_id, ra.room_number,
               c.course_code, c.course_name, co.section,
               rts.day_of_week, rts.start_time, rts.end_time, rts.slot_number
        FROM routine_assignments ra
        JOIN course_offerings co ON ra.course_offering_id = co.id
        JOIN courses c ON co.course_id = c.id
        JOIN routine_time_slots rts ON ra.time_slot_id = rts.id
        WHERE ra.template_id = {$template['id']} AND ra.teacher_id = $teacher_id
        ORDER BY rts.day_of_week, rts.slot_number
    ");
    while ($row = $a_result->fetch_assoc()) {
        $my_assignments[] = $row;
    }
}

// Get empty slots for rescheduling options
$empty_slots = [];
if ($template && $department_id) {
    $e_result = $db->query("
        SELECT rts.id, rts.day_of_week, rts.start_time, rts.end_time, rts.slot_number
        FROM routine_time_slots rts
        LEFT JOIN routine_assignments ra ON rts.id = ra.time_slot_id AND ra.template_id = {$template['id']}
        WHERE (rts.department_id = $department_id OR rts.department_id IS NULL)
        AND rts.is_break = 0
        AND ra.id IS NULL
        ORDER BY rts.day_of_week, rts.slot_number
    ");
    while ($row = $e_result->fetch_assoc()) {
        $empty_slots[] = $row;
    }
}

// Sidebar
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white">
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
                <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Teacher</span>
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                    <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                    Schedule Changes
                </span>
            </div>
            <h1 class="text-3xl font-black uppercase tracking-tighter">My Change <span class="text-indigo-600">Requests</span></h1>
            <p class="text-xs text-slate-500 mt-1">Request schedule changes and view status</p>
        </div>
        <div class="flex gap-3">
            <?php if ($template): ?>
                <button onclick="openRequestModal()" class="btn-os bg-indigo-500 text-white border-indigo-600 hover:bg-indigo-600 flex items-center gap-2 shadow-[4px_4px_0px_#3730a3]">
                    <i class="fas fa-plus"></i> New Request
                </button>
            <?php endif; ?>
            <a href="routine.php" class="btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black flex items-center gap-2">
                <i class="fas fa-calendar-alt"></i> View Routine
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4">
        <?php
        $pending = count(array_filter($requests, fn($r) => $r['status'] === 'pending'));
        $approved = count(array_filter($requests, fn($r) => $r['status'] === 'approved'));
        $rejected = count(array_filter($requests, fn($r) => $r['status'] === 'rejected'));
        ?>
        <div class="os-card p-4 bg-amber-50 border-l-4 border-amber-500">
            <div class="text-2xl font-black text-amber-700"><?php echo $pending; ?></div>
            <div class="text-[10px] font-bold uppercase text-amber-600 tracking-widest">Pending</div>
        </div>
        <div class="os-card p-4 bg-green-50 border-l-4 border-green-500">
            <div class="text-2xl font-black text-green-700"><?php echo $approved; ?></div>
            <div class="text-[10px] font-bold uppercase text-green-600 tracking-widest">Approved</div>
        </div>
        <div class="os-card p-4 bg-red-50 border-l-4 border-red-500">
            <div class="text-2xl font-black text-red-700"><?php echo $rejected; ?></div>
            <div class="text-[10px] font-bold uppercase text-red-600 tracking-widest">Rejected</div>
        </div>
    </div>

    <!-- Requests List -->
    <?php if (empty($requests)): ?>
        <div class="os-card p-12 text-center bg-white">
            <i class="fas fa-inbox text-6xl text-slate-300 mb-4"></i>
            <h2 class="text-xl font-black uppercase mb-2">No Requests Yet</h2>
            <p class="text-slate-500 mb-4">You haven't submitted any change requests.</p>
            <?php if ($template): ?>
                <button onclick="openRequestModal()" class="btn-os bg-indigo-500 text-white border-indigo-600 hover:bg-indigo-600 inline-flex items-center gap-2">
                    <i class="fas fa-plus"></i> Submit Your First Request
                </button>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="os-card p-0 bg-white overflow-hidden">
            <div class="bg-slate-100 p-4 border-b-2 border-black">
                <h3 class="text-sm font-black uppercase tracking-tight"><i class="fas fa-history mr-2"></i>Request History</h3>
            </div>
            <div class="divide-y-2 divide-slate-200">
                <?php foreach ($requests as $req): ?>
                    <div class="p-4 hover:bg-slate-50 transition">
                        <div class="flex flex-col md:flex-row md:items-start gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="px-2 py-1 text-[10px] font-black uppercase border-2 
                                        <?php echo $req['status'] === 'pending' ? 'bg-amber-50 text-amber-700 border-amber-300' : 
                                            ($req['status'] === 'approved' ? 'bg-green-50 text-green-700 border-green-300' : 'bg-red-50 text-red-700 border-red-300'); ?>">
                                        <?php echo ucfirst($req['status']); ?>
                                    </span>
                                    <span class="px-2 py-1 text-[10px] font-black uppercase bg-slate-100 text-slate-600 border-2 border-slate-200">
                                        <?php echo str_replace('_', ' ', $req['request_type']); ?>
                                    </span>
                                    <span class="text-[10px] text-slate-400">
                                        <?php echo format_date($req['created_at'], true); ?>
                                    </span>
                                </div>
                                
                                <?php if ($req['course_code']): ?>
                                    <div class="text-sm font-bold mb-1">
                                        <?php echo e($req['course_code'] . ' - ' . $req['course_name']); ?>
                                        <span class="text-slate-400 font-normal">(Sec: <?php echo e($req['section']); ?>)</span>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Change Details -->
                                <div class="flex flex-wrap gap-3 text-xs mb-3">
                                    <?php if ($req['current_day']): ?>
                                        <div class="p-2 bg-slate-100 border border-slate-200">
                                            <div class="text-[10px] font-black text-slate-400 uppercase">Current</div>
                                            <div class="font-bold"><?php echo $req['current_day']; ?> Slot <?php echo $req['current_slot_num']; ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($req['requested_day']): ?>
                                        <div class="text-slate-400 flex items-center"><i class="fas fa-arrow-right"></i></div>
                                        <div class="p-2 bg-indigo-50 border border-indigo-200">
                                            <div class="text-[10px] font-black text-indigo-600 uppercase">Requested</div>
                                            <div class="font-bold text-indigo-700"><?php echo $req['requested_day']; ?> Slot <?php echo $req['requested_slot_num']; ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($req['requested_room']): ?>
                                        <div class="p-2 bg-blue-50 border border-blue-200">
                                            <div class="text-[10px] font-black text-blue-600 uppercase">Requested Room</div>
                                            <div class="font-bold text-blue-700"><?php echo e($req['requested_room']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="text-xs text-slate-600 mb-2">
                                    <strong>Reason:</strong> <?php echo e($req['reason']); ?>
                                </div>
                                
                                <?php if ($req['admin_response']): ?>
                                    <div class="p-3 mt-2 text-xs bg-<?php echo $req['status'] === 'approved' ? 'green' : 'red'; ?>-50 border-l-4 border-<?php echo $req['status'] === 'approved' ? 'green' : 'red'; ?>-400">
                                        <div class="text-[10px] font-black uppercase mb-1 text-<?php echo $req['status'] === 'approved' ? 'green' : 'red'; ?>-600">
                                            Admin Response 
                                            <?php if ($req['responder_first']): ?>
                                                by <?php echo e($req['responder_first'] . ' ' . $req['responder_last']); ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php echo e($req['admin_response']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- New Request Modal -->
<?php if ($template): ?>
<div id="requestModal" class="fixed inset-0 z-[9999] hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeRequestModal()"></div>
    <div class="absolute inset-4 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:w-[520px] bg-white border-4 border-black shadow-[8px_8px_0px_#000] overflow-hidden max-h-[90vh] overflow-y-auto">
        <div class="bg-black text-white p-4 flex justify-between items-center sticky top-0">
            <h3 class="font-black uppercase tracking-tight">Submit Change Request</h3>
            <button onclick="closeRequestModal()" class="w-8 h-8 flex items-center justify-center hover:bg-white hover:text-black transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="requestForm" class="p-6 space-y-4">
            <input type="hidden" name="action" value="submit_change_request">
            
            <div>
                <label class="block text-xs font-black uppercase mb-2">Request Type</label>
                <select name="request_type" id="request_type" class="w-full p-3 border-2 border-black text-sm font-bold focus:outline-none focus:bg-yellow-50" required onchange="toggleRequestFields()">
                    <option value="time_change">Time/Slot Change</option>
                    <option value="room_change">Room Change</option>
                    <option value="swap">Swap with Another Teacher</option>
                    <option value="cancel">Cancel/Remove Class</option>
                    <option value="general">General Request</option>
                </select>
            </div>
            
            <div id="assignmentField">
                <label class="block text-xs font-black uppercase mb-2">Select Your Class</label>
                <select name="assignment_id" id="assignment_id" class="w-full p-3 border-2 border-black text-sm font-bold focus:outline-none focus:bg-yellow-50" onchange="updateCurrentSlot()">
                    <option value="">-- Select Class --</option>
                    <?php foreach ($my_assignments as $a): ?>
                        <option value="<?php echo $a['id']; ?>" 
                                data-slot-id="<?php echo $a['time_slot_id']; ?>"
                                data-day="<?php echo $a['day_of_week']; ?>"
                                data-slot="<?php echo $a['slot_number']; ?>">
                            <?php echo e($a['course_code']); ?> (Sec: <?php echo e($a['section']); ?>) - 
                            <?php echo $a['day_of_week']; ?> Slot <?php echo $a['slot_number']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="current_slot_id" id="current_slot_id">
            </div>
            
            <div id="newSlotField">
                <label class="block text-xs font-black uppercase mb-2">Request New Slot</label>
                <select name="requested_slot_id" class="w-full p-3 border-2 border-black text-sm font-bold focus:outline-none focus:bg-yellow-50">
                    <option value="">-- Select Empty Slot --</option>
                    <?php foreach ($empty_slots as $s): ?>
                        <option value="<?php echo $s['id']; ?>">
                            <?php echo $s['day_of_week']; ?> - Slot <?php echo $s['slot_number']; ?> 
                            (<?php echo date('h:i A', strtotime($s['start_time'])); ?> - <?php echo date('h:i A', strtotime($s['end_time'])); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="roomField" style="display: none;">
                <label class="block text-xs font-black uppercase mb-2">Requested Room</label>
                <input type="text" name="requested_room" class="w-full p-3 border-2 border-black text-sm font-bold focus:outline-none focus:bg-yellow-50 uppercase" placeholder="e.g., C1, Lab-2">
            </div>
            
            <div>
                <label class="block text-xs font-black uppercase mb-2">Reason <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="4" class="w-full p-3 border-2 border-black text-sm focus:outline-none focus:bg-yellow-50" placeholder="Explain why you need this change..." required></textarea>
            </div>
            
            <div class="flex gap-4 pt-4 border-t-2 border-slate-200">
                <button type="button" onclick="closeRequestModal()" class="flex-1 py-3 border-2 border-black text-black font-black uppercase text-xs hover:bg-slate-100">Cancel</button>
                <button type="submit" class="flex-1 py-3 bg-indigo-500 text-white border-2 border-indigo-600 font-black uppercase text-xs hover:bg-indigo-600 transition shadow-[4px_4px_0px_#3730a3]">Submit Request</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

function openRequestModal() {
    document.getElementById('requestModal').classList.remove('hidden');
}

function closeRequestModal() {
    document.getElementById('requestModal').classList.add('hidden');
    document.getElementById('requestForm').reset();
}

function toggleRequestFields() {
    const type = document.getElementById('request_type').value;
    const newSlotField = document.getElementById('newSlotField');
    const roomField = document.getElementById('roomField');
    const assignmentField = document.getElementById('assignmentField');
    
    // Show/hide fields based on type
    newSlotField.style.display = (type === 'time_change' || type === 'swap') ? 'block' : 'none';
    roomField.style.display = type === 'room_change' ? 'block' : 'none';
    assignmentField.style.display = type === 'general' ? 'none' : 'block';
}

function updateCurrentSlot() {
    const select = document.getElementById('assignment_id');
    const option = select.options[select.selectedIndex];
    document.getElementById('current_slot_id').value = option.dataset.slotId || '';
}

document.getElementById('requestForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    try {
        const response = await fetch(BASE_URL + '/api/teacher/routine.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            showGlobalModal('Success', result.message || 'Request submitted successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showGlobalModal('Error', result.error || 'Failed to submit request', 'error');
        }
    } catch (error) {
        showGlobalModal('Error', 'Network error occurred', 'error');
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
