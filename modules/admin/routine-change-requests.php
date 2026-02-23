<?php
/**
 * Routine Change Requests Management
 * ACADEMIX - Academic Management System
 * Manage teacher change requests for routine schedules
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('admin');

$page_title = 'Change Requests';
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

// Filter
$status_filter = sanitize_input($_GET['status'] ?? 'pending');

// Get change requests
$requests = [];
if (!empty($department_ids)) {
    $query = "
        SELECT rcr.*, 
               up.first_name, up.last_name, t.employee_id,
               c.course_code, c.course_name, co.section,
               rts_cur.start_time as current_start, rts_cur.end_time as current_end, rts_cur.day_of_week as current_day, rts_cur.slot_number as current_slot_num,
               rts_req.start_time as requested_start, rts_req.end_time as requested_end, rts_req.day_of_week as requested_day, rts_req.slot_number as requested_slot_num,
               resp_up.first_name as responder_first, resp_up.last_name as responder_last
        FROM routine_change_requests rcr
        JOIN teachers t ON rcr.teacher_id = t.id
        JOIN users u ON t.user_id = u.id
        JOIN user_profiles up ON u.id = up.user_id
        JOIN routine_templates rt ON rcr.template_id = rt.id
        LEFT JOIN routine_assignments ra ON rcr.assignment_id = ra.id
        LEFT JOIN course_offerings co ON ra.course_offering_id = co.id
        LEFT JOIN courses c ON co.course_id = c.id
        LEFT JOIN routine_time_slots rts_cur ON rcr.current_slot_id = rts_cur.id
        LEFT JOIN routine_time_slots rts_req ON rcr.requested_slot_id = rts_req.id
        LEFT JOIN users resp ON rcr.responded_by = resp.id
        LEFT JOIN user_profiles resp_up ON resp.id = resp_up.user_id
        WHERE rt.department_id IN ($dept_id_list)
    ";
    
    if ($status_filter !== 'all') {
        $query .= " AND rcr.status = '" . $db->real_escape_string($status_filter) . "'";
    }
    
    $query .= " ORDER BY rcr.created_at DESC";
    
    $r_result = $db->query($query);
    while ($row = $r_result->fetch_assoc()) {
        $requests[] = $row;
    }
}

// Get counts
$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
if (!empty($department_ids)) {
    $count_result = $db->query("SELECT rcr.status, COUNT(*) as cnt FROM routine_change_requests rcr JOIN routine_templates rt ON rcr.template_id = rt.id WHERE rt.department_id IN ($dept_id_list) GROUP BY rcr.status");
    while ($row = $count_result->fetch_assoc()) {
        $counts[$row['status']] = $row['cnt'];
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
                Schedule Management
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">Change <span class="text-red-500">Requests</span></h1>
        <p class="text-xs text-slate-500 mt-1">Review and respond to teacher schedule change requests</p>
    </div>
    <div class="flex gap-3">
        <a href="routine-manager.php" class="btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Back to Routine
        </a>
    </div>
</div>

<!-- Filter Tabs -->
<div class="flex gap-2 mb-6">
    <a href="?status=pending" class="px-4 py-2 text-xs font-black uppercase border-2 transition <?php echo $status_filter === 'pending' ? 'bg-amber-500 text-white border-amber-600' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'; ?>">
        <i class="fas fa-clock mr-2"></i>Pending (<?php echo $counts['pending']; ?>)
    </a>
    <a href="?status=approved" class="px-4 py-2 text-xs font-black uppercase border-2 transition <?php echo $status_filter === 'approved' ? 'bg-green-500 text-white border-green-600' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'; ?>">
        <i class="fas fa-check mr-2"></i>Approved (<?php echo $counts['approved']; ?>)
    </a>
    <a href="?status=rejected" class="px-4 py-2 text-xs font-black uppercase border-2 transition <?php echo $status_filter === 'rejected' ? 'bg-red-500 text-white border-red-600' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'; ?>">
        <i class="fas fa-times mr-2"></i>Rejected (<?php echo $counts['rejected']; ?>)
    </a>
    <a href="?status=all" class="px-4 py-2 text-xs font-black uppercase border-2 transition <?php echo $status_filter === 'all' ? 'bg-slate-700 text-white border-slate-800' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'; ?>">
        <i class="fas fa-list mr-2"></i>All
    </a>
</div>

<?php if (empty($requests)): ?>
    <div class="os-card p-12 text-center">
        <i class="fas fa-inbox text-6xl text-slate-300 mb-4"></i>
        <h2 class="text-xl font-black uppercase mb-2">No Requests Found</h2>
        <p class="text-slate-500">There are no <?php echo $status_filter !== 'all' ? $status_filter : ''; ?> change requests at this time.</p>
    </div>
<?php else: ?>
    <div class="grid gap-4">
        <?php foreach ($requests as $req): ?>
            <div class="os-card p-0 bg-white overflow-hidden">
                <div class="flex border-b-2 border-slate-200">
                    <!-- Status indicator -->
                    <div class="w-2 <?php 
                        echo $req['status'] === 'pending' ? 'bg-amber-500' : 
                            ($req['status'] === 'approved' ? 'bg-green-500' : 'bg-red-500'); 
                    ?>"></div>
                    
                    <div class="flex-1 p-4">
                        <div class="flex flex-col md:flex-row md:items-start gap-4">
                            <!-- Request Info -->
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
                                
                                <div class="text-sm font-bold mb-1">
                                    <?php echo e($req['first_name'] . ' ' . $req['last_name']); ?>
                                    <span class="text-slate-400 font-normal">(<?php echo e($req['employee_id']); ?>)</span>
                                </div>
                                
                                <?php if ($req['course_code']): ?>
                                    <div class="text-xs text-slate-500 mb-2">
                                        <i class="fas fa-book mr-1"></i>
                                        <?php echo e($req['course_code'] . ' - ' . $req['course_name']); ?>
                                        (Sec: <?php echo e($req['section']); ?>)
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Change Details -->
                                <div class="flex flex-wrap gap-4 text-xs mb-3">
                                    <?php if ($req['current_day']): ?>
                                        <div class="p-2 bg-slate-100 border border-slate-200">
                                            <div class="text-[10px] font-black text-slate-400 uppercase mb-1">Current</div>
                                            <div class="font-bold"><?php echo $req['current_day']; ?></div>
                                            <div class="text-slate-500">Slot <?php echo $req['current_slot_num']; ?>: <?php echo date('h:i A', strtotime($req['current_start'])); ?> - <?php echo date('h:i A', strtotime($req['current_end'])); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($req['requested_day']): ?>
                                        <div class="text-slate-400 flex items-center">
                                            <i class="fas fa-arrow-right"></i>
                                        </div>
                                        <div class="p-2 bg-green-50 border border-green-200">
                                            <div class="text-[10px] font-black text-green-600 uppercase mb-1">Requested</div>
                                            <div class="font-bold text-green-700"><?php echo $req['requested_day']; ?></div>
                                            <div class="text-green-600">Slot <?php echo $req['requested_slot_num']; ?>: <?php echo date('h:i A', strtotime($req['requested_start'])); ?> - <?php echo date('h:i A', strtotime($req['requested_end'])); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($req['requested_room']): ?>
                                        <div class="p-2 bg-blue-50 border border-blue-200">
                                            <div class="text-[10px] font-black text-blue-600 uppercase mb-1">Requested Room</div>
                                            <div class="font-bold text-blue-700"><?php echo e($req['requested_room']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Reason -->
                                <div class="p-3 bg-slate-50 border-l-4 border-slate-400 text-xs">
                                    <div class="text-[10px] font-black text-slate-400 uppercase mb-1">Reason</div>
                                    <?php echo nl2br(e($req['reason'])); ?>
                                </div>
                                
                                <?php if ($req['admin_response']): ?>
                                    <div class="p-3 mt-2 bg-<?php echo $req['status'] === 'approved' ? 'green' : 'red'; ?>-50 border-l-4 border-<?php echo $req['status'] === 'approved' ? 'green' : 'red'; ?>-400 text-xs">
                                        <div class="text-[10px] font-black text-<?php echo $req['status'] === 'approved' ? 'green' : 'red'; ?>-600 uppercase mb-1">
                                            Response by <?php echo e($req['responder_first'] . ' ' . $req['responder_last']); ?>
                                        </div>
                                        <?php echo nl2br(e($req['admin_response'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Actions -->
                            <?php if ($req['status'] === 'pending'): ?>
                                <div class="flex flex-col gap-2 min-w-[140px]">
                                    <button onclick="openResponseModal(<?php echo $req['id']; ?>, 'approved')" 
                                            class="w-full py-2 px-4 bg-green-500 text-white text-xs font-black uppercase border-2 border-green-600 hover:bg-green-600 transition">
                                        <i class="fas fa-check mr-1"></i> Approve
                                    </button>
                                    <button onclick="openResponseModal(<?php echo $req['id']; ?>, 'rejected')" 
                                            class="w-full py-2 px-4 bg-red-500 text-white text-xs font-black uppercase border-2 border-red-600 hover:bg-red-600 transition">
                                        <i class="fas fa-times mr-1"></i> Reject
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Response Modal -->
<div id="responseModal" class="fixed inset-0 z-[9999] hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeResponseModal()"></div>
    <div class="absolute inset-4 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:w-[480px] bg-white border-4 border-black shadow-[8px_8px_0px_#000] overflow-hidden">
        <div class="bg-black text-white p-4 flex justify-between items-center">
            <h3 class="font-black uppercase tracking-tight" id="responseModalTitle">Respond to Request</h3>
            <button onclick="closeResponseModal()" class="w-8 h-8 flex items-center justify-center hover:bg-white hover:text-black transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="responseForm" class="p-6 space-y-4">
            <input type="hidden" name="action" value="respond_change_request">
            <input type="hidden" name="request_id" id="response_request_id">
            <input type="hidden" name="response_status" id="response_status">
            
            <div>
                <label class="block text-xs font-black uppercase mb-2">Your Response (Optional)</label>
                <textarea name="admin_response" rows="4" class="w-full p-3 border-2 border-black text-sm focus:outline-none focus:bg-yellow-50" placeholder="Add a note explaining your decision..."></textarea>
            </div>
            
            <div class="flex gap-4 pt-4 border-t-2 border-slate-200">
                <button type="button" onclick="closeResponseModal()" class="flex-1 py-3 border-2 border-black text-black font-black uppercase text-xs hover:bg-slate-100">Cancel</button>
                <button type="submit" id="responseSubmitBtn" class="flex-1 py-3 text-white font-black uppercase text-xs shadow-[4px_4px_0px_#000]">Submit</button>
            </div>
        </form>
    </div>
</div>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

function openResponseModal(requestId, status) {
    document.getElementById('response_request_id').value = requestId;
    document.getElementById('response_status').value = status;
    
    const btn = document.getElementById('responseSubmitBtn');
    const title = document.getElementById('responseModalTitle');
    
    if (status === 'approved') {
        btn.className = 'flex-1 py-3 bg-green-500 border-2 border-green-600 text-white font-black uppercase text-xs shadow-[4px_4px_0px_#166534] hover:bg-green-600';
        title.textContent = 'Approve Request';
    } else {
        btn.className = 'flex-1 py-3 bg-red-500 border-2 border-red-600 text-white font-black uppercase text-xs shadow-[4px_4px_0px_#991b1b] hover:bg-red-600';
        title.textContent = 'Reject Request';
    }
    
    document.getElementById('responseModal').classList.remove('hidden');
}

function closeResponseModal() {
    document.getElementById('responseModal').classList.add('hidden');
    document.getElementById('responseForm').reset();
}

document.getElementById('responseForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    try {
        const response = await fetch(BASE_URL + '/api/admin/routine.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            showGlobalModal('Success', result.message || 'Response saved!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showGlobalModal('Error', result.error || 'Failed to save response', 'error');
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
