<?php
/**
 * My Class Routine
 * ACADEMIX - Academic Management System
 * 
 * Teachers can view their schedule and request changes
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('teacher');

$page_title = 'Department Class Routine';
$user_id = get_current_user_id();

// Get teacher info
$stmt = $db->prepare("
    SELECT t.id, t.department_id, d.name as department_name,
           CONCAT(up.first_name, ' ', up.last_name) as teacher_name
    FROM teachers t
    JOIN departments d ON t.department_id = d.id
    JOIN user_profiles up ON t.user_id = up.user_id
    WHERE t.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

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
            <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Schedule</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                <?php echo e($teacher['department_name'] ?? 'Department'); ?>
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">Department <span class="text-black">Routine</span></h1>
        <p class="text-xs text-slate-500 mt-1 font-medium"><span id="myClassCount">0</span> of <span id="totalClassCount">0</span> classes are yours • <span id="semesterName">Current Semester</span></p>
    </div>
    
    <div class="flex items-center gap-3">
        <button onclick="openMyRequests()" class="btn-os bg-white text-black border-black hover:bg-slate-100 flex items-center gap-2">
            <i class="fas fa-history"></i> My Requests
        </button>
    </div>
</div>

<!-- Draft Notification Banner -->
<div id="draftBanner" class="hidden mb-6 p-4 bg-amber-50 border-2 border-amber-400 flex items-center gap-3">
    <i class="fas fa-info-circle text-amber-600 text-xl"></i>
    <div>
        <span class="font-bold text-amber-800 uppercase text-sm">Provisional Routine</span>
        <span class="text-amber-700 text-sm ml-2">This routine is still being finalized. You can request changes by clicking on a class.</span>
    </div>
</div>

<!-- Published Banner -->
<div id="publishedBanner" class="hidden mb-6 p-4 bg-emerald-50 border-2 border-emerald-400 flex items-center gap-3">
    <i class="fas fa-check-circle text-emerald-600 text-xl"></i>
    <div>
        <span class="font-bold text-emerald-800 uppercase text-sm">Active Routine</span>
        <span class="text-emerald-700 text-sm ml-2">This routine is currently in effect. Contact your department admin to request changes.</span>
    </div>
</div>

<!-- No Routine Message -->
<div id="noRoutineMessage" class="hidden">
    <div class="os-card p-12 text-center bg-white">
        <i class="fas fa-calendar-times text-6xl text-slate-300 mb-4"></i>
        <h3 class="text-xl font-bold text-slate-600 mb-2">No Routine Available</h3>
        <p class="text-slate-500">The class routine for this semester hasn't been created yet. Please check back later.</p>
    </div>
</div>

<!-- Routine Grid -->
<div id="routineContainer" class="os-card p-0 bg-white overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full border-collapse" id="routineGrid">
            <thead>
                <tr class="bg-black text-white">
                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest border-r border-slate-700 w-24">Day / Time</th>
                    <!-- Slots will be loaded dynamically -->
                </tr>
            </thead>
            <tbody>
                <!-- Days will be loaded dynamically -->
            </tbody>
        </table>
    </div>
</div>

<!-- Change Request Modal -->
<div id="changeRequestModal" class="relative z-[9999] hidden" aria-modal="true">
    <div class="fixed inset-0 bg-black/80 transition-opacity backdrop-blur-sm z-[90]"></div>
    <div class="fixed inset-0 z-[100] w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white w-full max-w-lg border-4 border-black shadow-[8px_8px_0px_#000]">
                <div class="bg-black p-6 text-white">
                    <h3 class="text-xl font-black uppercase tracking-tighter">Request Schedule Change</h3>
                    <p class="text-xs text-slate-400 mt-1">Submit a request to your department admin</p>
                </div>
                
                <div class="p-6 space-y-5">
                    <input type="hidden" id="requestAssignmentId">
                    
                    <!-- Class Details -->
                    <div class="p-4 bg-slate-50 border-2 border-slate-200">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-[10px] font-bold uppercase text-slate-500 block">Course</span>
                                <span class="font-bold" id="requestCourse">CSE-101</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase text-slate-500 block">Time</span>
                                <span class="font-bold" id="requestTime">Sunday • 08:50 - 09:40</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase text-slate-500 block">Room</span>
                                <span class="font-bold" id="requestRoom">C1 - Room 6613</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase text-slate-500 block">Type</span>
                                <span class="font-bold" id="requestType">Theory</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Request Message -->
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest mb-2">Your Request / Reason</label>
                        <textarea id="requestMessage" rows="4" class="w-full px-4 py-3 border-2 border-black font-medium text-sm resize-none focus:border-blue-500 focus:ring-0" placeholder="Explain why you need this change and suggest an alternative if possible..."></textarea>
                        <p class="text-[10px] text-slate-500 mt-1">Be specific about what change you need and why.</p>
                    </div>
                </div>
                
                <div class="p-6 border-t-2 border-slate-200 flex justify-end gap-3">
                    <button onclick="closeChangeRequestModal()" class="px-6 py-3 bg-white border-2 border-black text-black font-bold text-sm hover:bg-slate-100">
                        Cancel
                    </button>
                    <button onclick="submitChangeRequest()" class="px-6 py-3 bg-blue-600 text-white font-bold text-sm hover:bg-blue-700 shadow-[4px_4px_0px_#000]">
                        <i class="fas fa-paper-plane mr-2"></i> Send Request
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reschedule Modal (Date-Specific) -->
<div id="rescheduleModal" class="relative z-[9999] hidden" aria-modal="true">
    <div class="fixed inset-0 bg-black/80 transition-opacity backdrop-blur-sm z-[90]"></div>
    <div class="fixed inset-0 z-[100] w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white w-full max-w-lg border-4 border-black shadow-[8px_8px_0px_#000]">
                <div class="bg-emerald-600 p-6 text-white">
                    <h3 class="text-xl font-black uppercase tracking-tighter">Reschedule Class</h3>
                    <p class="text-xs text-emerald-100 mt-1">Move your class to a different date/time (temporary change)</p>
                </div>
                
                <div class="p-6 space-y-5">
                    <input type="hidden" id="rescheduleAssignmentId">
                    <input type="hidden" id="rescheduleCourseType">
                    
                    <!-- Current Class Info -->
                    <div class="p-4 bg-slate-50 border-2 border-slate-200">
                        <p class="text-[10px] font-bold uppercase text-slate-500 mb-2">Current Schedule</p>
                        <div class="font-bold" id="rescheduleCurrentInfo">Loading...</div>
                    </div>
                    
                    <!-- Step 1: Select Original Date -->
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest mb-2">
                            <span class="bg-black text-white px-2 py-1 mr-2">1</span> Original Class Date
                        </label>
                        <input type="date" id="rescheduleOriginalDate" 
                            class="w-full px-4 py-3 border-2 border-black font-bold focus:border-emerald-500 focus:ring-0"
                            onchange="onOriginalDateSelect()">
                        <p class="text-[10px] text-slate-500 mt-1">Select the date of the class you want to reschedule</p>
                    </div>
                    
                    <!-- Step 2: Select New Date -->
                    <div id="step2Container" class="hidden">
                        <label class="block text-xs font-black uppercase tracking-widest mb-2">
                            <span class="bg-black text-white px-2 py-1 mr-2">2</span> New Date
                        </label>
                        <input type="date" id="rescheduleNewDate" 
                            class="w-full px-4 py-3 border-2 border-black font-bold focus:border-emerald-500 focus:ring-0"
                            onchange="onNewDateSelect()">
                        <p class="text-[10px] text-slate-500 mt-1">Select the date you want to move the class to</p>
                    </div>
                    
                    <!-- Step 3: Select Slot -->
                    <div id="step3Container" class="hidden">
                        <label class="block text-xs font-black uppercase tracking-widest mb-2">
                            <span class="bg-black text-white px-2 py-1 mr-2">3</span> Available Time Slots
                        </label>
                        <div id="availableSlotsContainer" class="max-h-40 overflow-y-auto border-2 border-black">
                            <p class="p-4 text-center text-slate-500">Select a date first</p>
                        </div>
                    </div>
                    
                    <!-- Step 4: Select Room -->
                    <div id="step4Container" class="hidden">
                        <label class="block text-xs font-black uppercase tracking-widest mb-2">
                            <span class="bg-black text-white px-2 py-1 mr-2">4</span> Select Room
                        </label>
                        <select id="rescheduleRoom" class="w-full px-4 py-3 border-2 border-black font-bold focus:border-emerald-500 focus:ring-0">
                            <option value="">-- Select Room --</option>
                        </select>
                    </div>
                    
                    <!-- Optional: Reason -->
                    <div id="reasonContainer" class="hidden">
                        <label class="block text-xs font-black uppercase tracking-widest mb-2">Reason (Optional)</label>
                        <textarea id="rescheduleReason" rows="2" 
                            class="w-full px-4 py-3 border-2 border-black font-medium text-sm resize-none focus:border-emerald-500 focus:ring-0" 
                            placeholder="Brief reason for rescheduling..."></textarea>
                    </div>
                </div>
                
                <div class="p-6 border-t-2 border-slate-200 flex justify-end gap-3">
                    <button onclick="closeRescheduleModal()" class="px-6 py-3 bg-white border-2 border-black text-black font-bold text-sm hover:bg-slate-100">
                        Cancel
                    </button>
                    <button id="confirmRescheduleBtn" onclick="confirmReschedule()" 
                        class="hidden px-6 py-3 bg-emerald-600 text-white font-bold text-sm hover:bg-emerald-700 shadow-[4px_4px_0px_#000]">
                        <i class="fas fa-exchange-alt mr-2"></i> Confirm Reschedule
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- My Requests Modal -->
<div id="myRequestsModal" class="relative z-[9999] hidden" aria-modal="true">
    <div class="fixed inset-0 bg-black/80 transition-opacity backdrop-blur-sm z-[90]"></div>
    <div class="fixed inset-0 z-[100] w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white w-full max-w-2xl border-4 border-black shadow-[8px_8px_0px_#000]">
                <div class="bg-black p-6 text-white flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-black uppercase tracking-tighter">My Change Requests</h3>
                        <p class="text-xs text-slate-400 mt-1">History of your schedule change requests</p>
                    </div>
                    <button onclick="closeMyRequests()" class="text-white hover:text-yellow-400">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div class="p-6 max-h-[60vh] overflow-y-auto" id="requestsContainer">
                    <p class="text-center text-slate-500">Loading...</p>
                </div>
                
                <div class="p-6 border-t-2 border-slate-200 flex justify-end">
                    <button onclick="closeMyRequests()" class="px-6 py-3 bg-white border-2 border-black text-black font-bold text-sm hover:bg-slate-100">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.slot-cell {
    min-height: 80px;
    transition: all 0.15s ease;
    position: relative;
}
.slot-cell.break-slot {
    background-color: #fef3c7;
}
.slot-header {
    min-width: 120px;
}
.slot-header.break-slot {
    background-color: #fbbf24 !important;
}
.slot-header.lab-slot {
    background-color: #8b5cf6 !important;
}
/* My class - Blue */
.my-class-card {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border-left: 4px solid #2563eb;
    padding: 8px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.15s ease;
    position: relative;
    margin-bottom: 4px;
}
.my-class-card:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    z-index: 10;
}
.my-class-card.lab-type {
    background: linear-gradient(135deg, #e9d5ff 0%, #ddd6fe 100%);
    border-left-color: #7c3aed;
}
/* Other teacher's class - Gray */
.other-class-card {
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    border-left: 4px solid #64748b;
    padding: 8px;
    border-radius: 4px;
    transition: all 0.15s ease;
    margin-bottom: 4px;
    opacity: 0.8;
}
.other-class-card.lab-type {
    background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
    border-left-color: #a78bfa;
}
/* Empty slot item for reschedule selection */
.empty-slot-item {
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.1s ease;
}
.empty-slot-item:hover {
    background-color: #ecfdf5;
}
.empty-slot-item:last-child {
    border-bottom: none;
}
.request-item {
    border: 2px solid #e2e8f0;
    border-radius: 4px;
    padding: 16px;
    margin-bottom: 12px;
}
.request-item.pending {
    border-left: 4px solid #f59e0b;
    background: #fffbeb;
}
.request-item.approved {
    border-left: 4px solid #10b981;
    background: #ecfdf5;
}
.request-item.rejected {
    border-left: 4px solid #ef4444;
    background: #fef2f2;
}
</style>

<script>
const API_BASE = '<?php echo BASE_URL; ?>/api/teacher/routine.php';
const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];

let slots = [];
let routine = null;
let assignments = [];

// Initialize
document.addEventListener('DOMContentLoaded', async () => {
    await loadRoutineData();
});

async function loadRoutineData() {
    try {
        // Load slots
        const slotsRes = await fetch(`${API_BASE}?action=get_slots`);
        slots = await slotsRes.json();
        
        // Load FULL department routine (not just my classes)
        const routineRes = await fetch(`${API_BASE}?action=get_full_routine`);
        const data = await routineRes.json();
        
        routine = data.routine;
        assignments = data.assignments || [];
        
        if (!routine) {
            document.getElementById('noRoutineMessage').classList.remove('hidden');
            document.getElementById('routineContainer').classList.add('hidden');
            return;
        }
        
        // Update header info with my count / total count
        document.getElementById('myClassCount').textContent = data.my_class_count || 0;
        document.getElementById('totalClassCount').textContent = data.class_count || 0;
        document.getElementById('semesterName').textContent = routine.semester_name || 'Current Semester';
        
        // Show status banner
        if (routine.status === 'draft') {
            document.getElementById('draftBanner').classList.remove('hidden');
        } else if (routine.status === 'published') {
            document.getElementById('publishedBanner').classList.remove('hidden');
        }
        
        renderGrid();
    } catch (error) {
        console.error('Failed to load routine:', error);
        showGlobalModal('Error', 'Failed to load your routine', 'error');
    }
}


function renderGrid() {
    const thead = document.querySelector('#routineGrid thead tr');
    const tbody = document.querySelector('#routineGrid tbody');
    
    // Clear existing
    thead.innerHTML = '<th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest border-r border-slate-700 w-24">Day / Time</th>';
    tbody.innerHTML = '';
    
    // Render slot headers
    slots.forEach(slot => {
        let slotClass = 'slot-header';
        let badge = '';
        
        if (slot.slot_type === 'break') {
            slotClass += ' break-slot';
        } else if (slot.slot_type === 'lab') {
            slotClass += ' lab-slot';
            badge = '<span class="ml-1 px-1 py-0.5 bg-white/30 text-[8px] rounded">LAB</span>';
        }
        
        thead.innerHTML += `
            <th class="${slotClass} px-3 py-3 text-center text-[10px] font-black uppercase tracking-widest border-r border-slate-700 min-w-[120px]">
                <div>${slot.label}${badge}</div>
                <div class="text-[9px] font-normal opacity-75 mt-1">
                    <i class="far fa-clock mr-1"></i>${slot.start_time}-${slot.end_time}
                </div>
            </th>
        `;
    });
    
    // Render day rows
    DAYS.forEach(day => {
        let rowHtml = `
            <tr class="border-b-2 border-slate-200">
                <td class="px-4 py-3 bg-slate-100 font-black text-sm uppercase tracking-wider border-r-2 border-slate-300 w-24">${day}</td>
        `;
        
        slots.forEach(slot => {
            const isBreak = slot.slot_type === 'break';
            const cellClass = isBreak ? 'slot-cell break-slot' : 'slot-cell';
            
            // Find ALL assignments for this cell (support parallel classes)
            const slotAssignments = assignments.filter(a => a.day_of_week === day && a.slot_id === slot.id);
            
            let cellContent = '';
            
            if (isBreak) {
                cellContent = `<div class="h-full flex items-center justify-center"><span class="text-amber-600 text-xs font-bold uppercase"><i class="fas fa-coffee mr-1"></i>Break</span></div>`;
            } else if (slotAssignments.length > 0) {
                 cellContent = `<div class="space-y-2 p-1">`;
                 
                 slotAssignments.forEach(assignment => {
                    if (assignment.is_mine) {
                        // MY CLASS - Blue, clickable for reschedule
                        const cardClass = assignment.course_type === 'lab' ? 'my-class-card lab-type' : 'my-class-card';
                        cellContent += `
                            <div class="${cardClass} relative group" onclick="openRescheduleModal(${assignment.id})" title="Click to reschedule">
                                <div class="font-bold text-xs truncate">${assignment.course_code}</div>
                                <div class="text-[10px] text-slate-600 mt-1 truncate">Sec ${assignment.section}</div>
                                <div class="flex justify-between items-center mt-1">
                                    <span class="text-[9px] font-bold bg-white/50 px-1 rounded text-slate-600">${assignment.room_code}</span>
                                    <span class="text-[8px] text-blue-600 font-bold uppercase"><i class="fas fa-calendar-alt mr-1"></i>Reschedule</span>
                                </div>
                            </div>
                        `;
                    } else {
                        // OTHER TEACHER'S CLASS - Gray, not clickable
                        const cardClass = assignment.course_type === 'lab' ? 'other-class-card lab-type' : 'other-class-card';
                        cellContent += `
                            <div class="${cardClass}">
                                <div class="font-bold text-xs text-slate-600 truncate">${assignment.course_code}</div>
                                <div class="text-[10px] text-slate-500 mt-1 truncate">Sec ${assignment.section}</div>
                                <div class="flex flex-col gap-0.5 mt-1">
                                    <div class="text-[9px] text-slate-400 truncate flex items-center gap-1">
                                        <i class="fas fa-user"></i> ${assignment.teacher_name || 'TBA'}
                                    </div>
                                    <div class="text-[9px] text-slate-400 flex items-center gap-1">
                                        <i class="fas fa-map-marker-alt"></i> ${assignment.room_code}
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                 });
                 cellContent += '</div>';
            } else {
                // Empty cell - no class
                cellContent = `<span class="text-slate-300 text-xs">—</span>`;
            }
            
            // Note: Removed align-center from TD to allow top alignment for stacked cards
            rowHtml += `<td class="${cellClass} border-r border-slate-200 align-top p-0">${cellContent}</td>`;
        });
        
        rowHtml += '</tr>';
        tbody.innerHTML += rowHtml;
    });
}

// Change Request Modal
function openChangeRequest(assignmentId) {
    const assignment = assignments.find(a => a.id === assignmentId);
    if (!assignment) return;
    
    document.getElementById('requestAssignmentId').value = assignment.id;
    document.getElementById('requestCourse').textContent = `${assignment.course_code} - ${assignment.course_name}`;
    document.getElementById('requestTime').textContent = `${assignment.day_of_week} • ${assignment.start_time} - ${assignment.end_time}`;
    document.getElementById('requestRoom').textContent = `${assignment.room_code} - ${assignment.room_name}`;
    document.getElementById('requestType').textContent = assignment.course_type.charAt(0).toUpperCase() + assignment.course_type.slice(1);
    document.getElementById('requestMessage').value = '';
    
    document.getElementById('changeRequestModal').classList.remove('hidden');
}

function closeChangeRequestModal() {
    document.getElementById('changeRequestModal').classList.add('hidden');
}

async function submitChangeRequest() {
    const assignmentId = document.getElementById('requestAssignmentId').value;
    const message = document.getElementById('requestMessage').value.trim();
    
    if (!message) {
        showGlobalModal('Error', 'Please enter your request message', 'error');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}?action=submit_change_request`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                assignment_id: parseInt(assignmentId),
                message: message
            })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            closeChangeRequestModal();
            showGlobalModal('Success', 'Your change request has been submitted. You will be notified when the admin responds.', 'success');
        } else {
            showGlobalModal('Error', result.error || 'Failed to submit request', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showGlobalModal('Error', 'Network error', 'error');
    }
}

// My Requests Modal
async function openMyRequests() {
    document.getElementById('myRequestsModal').classList.remove('hidden');
    
    try {
        const response = await fetch(`${API_BASE}?action=get_my_requests`);
        const requests = await response.json();
        
        const container = document.getElementById('requestsContainer');
        
        if (requests.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-inbox text-4xl text-slate-300 mb-3"></i>
                    <p class="text-slate-500">You haven't made any change requests yet.</p>
                </div>
            `;
            return;
        }
        
        const statusColors = {
            pending: { bg: 'bg-amber-100', text: 'text-amber-800' },
            approved: { bg: 'bg-emerald-100', text: 'text-emerald-800' },
            rejected: { bg: 'bg-red-100', text: 'text-red-800' }
        };
        
        container.innerHTML = requests.map(req => `
            <div class="request-item ${req.status}">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <span class="font-bold text-sm">${req.course_code}</span>
                        <span class="text-xs text-slate-500 ml-2">${req.day_of_week} • ${req.start_time.substring(0,5)}-${req.end_time.substring(0,5)}</span>
                    </div>
                    <span class="px-2 py-1 text-[10px] font-bold uppercase rounded ${statusColors[req.status].bg} ${statusColors[req.status].text}">${req.status}</span>
                </div>
                <p class="text-sm text-slate-700 mb-2">${req.message}</p>
                ${req.admin_response ? `
                    <div class="mt-3 p-3 bg-slate-100 border-l-2 border-slate-400">
                        <span class="text-[10px] font-bold uppercase text-slate-500">Admin Response:</span>
                        <p class="text-sm text-slate-700 mt-1">${req.admin_response}</p>
                    </div>
                ` : ''}
                <div class="text-[10px] text-slate-400 mt-2">
                    Submitted: ${new Date(req.created_at).toLocaleString()}
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('requestsContainer').innerHTML = '<p class="text-center text-red-500">Failed to load requests</p>';
    }
}

function closeMyRequests() {
    document.getElementById('myRequestsModal').classList.add('hidden');
}

// ============================
// RESCHEDULE MODAL (Date-Specific)
// ============================
let selectedSlotId = null;

function openRescheduleModal(assignmentId) {
    const assignment = assignments.find(a => a.id === assignmentId);
    if (!assignment || !assignment.is_mine) return;
    
    // Store assignment info
    document.getElementById('rescheduleAssignmentId').value = assignment.id;
    document.getElementById('rescheduleCourseType').value = assignment.course_type || 'theory';
    document.getElementById('rescheduleCurrentInfo').textContent = 
        `${assignment.course_code} - ${assignment.course_name} | ${assignment.day_of_week} ${assignment.start_time}-${assignment.end_time} | Room: ${assignment.room_code}`;
    
    // Reset form
    selectedSlotId = null;
    document.getElementById('rescheduleOriginalDate').value = '';
    document.getElementById('rescheduleNewDate').value = '';
    document.getElementById('rescheduleReason').value = '';
    document.getElementById('rescheduleRoom').innerHTML = '<option value="">-- Select Room --</option>';
    
    // Hide steps
    document.getElementById('step2Container').classList.add('hidden');
    document.getElementById('step3Container').classList.add('hidden');
    document.getElementById('step4Container').classList.add('hidden');
    document.getElementById('reasonContainer').classList.add('hidden');
    document.getElementById('confirmRescheduleBtn').classList.add('hidden');
    document.getElementById('availableSlotsContainer').innerHTML = '<p class="p-4 text-center text-slate-500">Select a date first</p>';
    
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('rescheduleOriginalDate').min = today;
    document.getElementById('rescheduleNewDate').min = today;
    
    // Auto-select the next occurrence of this class day
    const dayMap = {
        'Sunday': 0, 'Monday': 1, 'Tuesday': 2, 'Wednesday': 3, 'Thursday': 4, 'Friday': 5, 'Saturday': 6
    };
    const targetDay = dayMap[assignment.day_of_week];
    const todayDate = new Date();
    const currentDay = todayDate.getDay();
    
    let daysUntilTarget = targetDay - currentDay;
    if (daysUntilTarget < 0) {
        daysUntilTarget += 7;
    }
    
    const nextDate = new Date(todayDate);
    nextDate.setDate(todayDate.getDate() + daysUntilTarget);
    
    // Format as YYYY-MM-DD
    const yyyy = nextDate.getFullYear();
    const mm = String(nextDate.getMonth() + 1).padStart(2, '0');
    const dd = String(nextDate.getDate()).padStart(2, '0');
    const formattedDate = `${yyyy}-${mm}-${dd}`;
    
    document.getElementById('rescheduleOriginalDate').value = formattedDate;
    
    // Trigger the change event to load step 2
    onOriginalDateSelect();
    
    document.getElementById('rescheduleModal').classList.remove('hidden');
}

function closeRescheduleModal() {
    document.getElementById('rescheduleModal').classList.add('hidden');
    selectedSlotId = null;
}

function onOriginalDateSelect() {
    const originalDate = document.getElementById('rescheduleOriginalDate').value;
    if (!originalDate) return;
    
    // Check if the selected date matches the class's day of week
    const assignmentId = document.getElementById('rescheduleAssignmentId').value;
    const assignment = assignments.find(a => a.id === parseInt(assignmentId));
    
    const dateObj = new Date(originalDate + 'T00:00:00');
    const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const selectedDay = dayNames[dateObj.getDay()];
    
    if (selectedDay !== assignment.day_of_week) {
        showGlobalModal('Warning', `This class is scheduled for ${assignment.day_of_week}. Please select a ${assignment.day_of_week}.`, 'error');
        document.getElementById('rescheduleOriginalDate').value = '';
        return;
    }
    
    // Show step 2
    document.getElementById('step2Container').classList.remove('hidden');
}

async function onNewDateSelect() {
    const newDate = document.getElementById('rescheduleNewDate').value;
    const assignmentId = document.getElementById('rescheduleAssignmentId').value;
    
    if (!newDate || !assignmentId) return;
    
    // Show step 3 and load available slots
    document.getElementById('step3Container').classList.remove('hidden');
    document.getElementById('availableSlotsContainer').innerHTML = '<p class="p-4 text-center text-slate-500"><i class="fas fa-spinner fa-spin mr-2"></i>Loading available slots...</p>';
    
    // Reset subsequent steps
    document.getElementById('step4Container').classList.add('hidden');
    document.getElementById('reasonContainer').classList.add('hidden');
    document.getElementById('confirmRescheduleBtn').classList.add('hidden');
    selectedSlotId = null;
    
    try {
        const res = await fetch(`${API_BASE}?action=get_empty_slots&assignment_id=${assignmentId}&date=${newDate}`);
        const data = await res.json();
        
        const container = document.getElementById('availableSlotsContainer');
        
        if (data.error) {
            container.innerHTML = `<p class="p-4 text-center text-red-500">${data.error}</p>`;
            return;
        }
        
        if (!data.available_slots || data.available_slots.length === 0) {
            container.innerHTML = `<p class="p-4 text-center text-slate-500">No available slots on ${data.day_of_week}. You may have classes at all times, or no rooms are available.</p>`;
            return;
        }
        
        container.innerHTML = data.available_slots.map(slot => `
            <div class="slot-item p-3 border-b border-slate-200 hover:bg-emerald-50 cursor-pointer transition-all" 
                 onclick="selectSlot(${slot.slot_id}, '${slot.start_time}', '${slot.end_time}')"
                 data-slot-id="${slot.slot_id}">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="font-bold text-sm">${slot.start_time} - ${slot.end_time}</span>
                        <span class="text-slate-500 text-xs ml-2">(${slot.slot_label})</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded">
                            ${slot.available_rooms} room${slot.available_rooms > 1 ? 's' : ''} available
                        </span>
                        <span class="text-xs px-2 py-1 bg-emerald-100 text-emerald-800 rounded uppercase font-bold">${slot.slot_type}</span>
                    </div>
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('availableSlotsContainer').innerHTML = '<p class="p-4 text-center text-red-500">Failed to load slots</p>';
    }
}

async function selectSlot(slotId, startTime, endTime) {
    selectedSlotId = slotId;
    
    // Highlight selected slot
    document.querySelectorAll('.slot-item').forEach(el => {
        el.classList.remove('bg-emerald-100', 'border-emerald-500', 'border-2');
    });
    const selectedEl = document.querySelector(`.slot-item[data-slot-id="${slotId}"]`);
    if (selectedEl) {
        selectedEl.classList.add('bg-emerald-100', 'border-emerald-500', 'border-2');
    }
    
    // Show step 4 and load available rooms
    document.getElementById('step4Container').classList.remove('hidden');
    document.getElementById('rescheduleRoom').innerHTML = '<option value="">Loading rooms...</option>';
    
    const newDate = document.getElementById('rescheduleNewDate').value;
    const courseType = document.getElementById('rescheduleCourseType').value;
    
    try {
        const res = await fetch(`${API_BASE}?action=get_available_rooms&slot_id=${slotId}&date=${newDate}&course_type=${courseType}`);
        const data = await res.json();
        
        const select = document.getElementById('rescheduleRoom');
        
        if (data.error) {
            select.innerHTML = `<option value="">Error: ${data.error}</option>`;
            return;
        }
        
        if (!data.rooms || data.rooms.length === 0) {
            select.innerHTML = '<option value="">No rooms available</option>';
            return;
        }
        
        select.innerHTML = '<option value="">-- Select Room --</option>' + 
            data.rooms.map(room => 
                `<option value="${room.id}">${room.code} - ${room.name} (Capacity: ${room.capacity})</option>`
            ).join('');
        
        // Show reason field and confirm button
        document.getElementById('reasonContainer').classList.remove('hidden');
        document.getElementById('confirmRescheduleBtn').classList.remove('hidden');
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('rescheduleRoom').innerHTML = '<option value="">Failed to load rooms</option>';
    }
}

async function confirmReschedule() {
    const assignmentId = document.getElementById('rescheduleAssignmentId').value;
    const originalDate = document.getElementById('rescheduleOriginalDate').value;
    const newDate = document.getElementById('rescheduleNewDate').value;
    const roomId = document.getElementById('rescheduleRoom').value;
    const reason = document.getElementById('rescheduleReason').value;
    
    if (!assignmentId || !originalDate || !newDate || !selectedSlotId || !roomId) {
        showGlobalModal('Error', 'Please complete all required fields', 'error');
        return;
    }
    
    // Get friendly date format
    const newDateFormatted = new Date(newDate + 'T00:00:00').toLocaleDateString('en-US', { 
        weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' 
    });
    
    if (!confirm(`Are you sure you want to reschedule this class to ${newDateFormatted}? All enrolled students will be notified.`)) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}?action=reschedule_class`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                assignment_id: parseInt(assignmentId),
                original_date: originalDate,
                new_date: newDate,
                new_slot_id: selectedSlotId,
                new_room_id: parseInt(roomId),
                reason: reason
            })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            closeRescheduleModal();
            showGlobalModal('Success', result.message || 'Class rescheduled successfully!', 'success');
        } else {
            showGlobalModal('Error', result.error || 'Failed to reschedule', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showGlobalModal('Error', 'Network error', 'error');
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
