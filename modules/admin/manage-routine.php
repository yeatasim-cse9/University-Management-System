<?php
/**
 * Manage Class Routine
 * ACADEMIX - Academic Management System
 * 
 * Admin can configure slots, assign classes, and publish routine
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('admin');

$page_title = 'Manage Class Routine';
$user_id = get_current_user_id();

// Get admin's department
$stmt = $db->prepare("SELECT d.id, d.name FROM departments d JOIN department_admins da ON d.id = da.department_id WHERE da.user_id = ? AND d.deleted_at IS NULL LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$department = $stmt->get_result()->fetch_assoc();

// Get active semester
$semester = $db->query("SELECT id, name FROM semesters WHERE status = 'active' LIMIT 1")->fetch_assoc();

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
            <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Scheduling</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                <?php echo e($department['name'] ?? 'Department'); ?> • <?php echo e($semester['name'] ?? 'Semester'); ?>
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">Manage Class <span class="text-black">Routine</span></h1>
        <p class="text-xs text-slate-500 mt-1 font-medium">Define slots, assign teachers, and publish schedules.</p>
    </div>
    
    <div class="flex items-center gap-3">
        <button onclick="openConfigureSlots()" class="btn-os bg-white text-black border-black hover:bg-slate-100 flex items-center gap-2">
            <i class="fas fa-cog"></i> Configure Slots
        </button>
        <button id="revertDraftBtn" onclick="saveAsDraft()" class="btn-os bg-amber-500 text-white border-amber-600 hover:bg-amber-600 flex items-center gap-2 hidden">
            <i class="fas fa-save"></i> Save as Draft
        </button>
        <button id="publishBtn" onclick="publishRoutine()" class="btn-os bg-emerald-500 text-white border-emerald-600 hover:bg-emerald-600 flex items-center gap-2">
            <i class="fas fa-check-circle"></i> Publish Routine
        </button>
    </div>
</div>

<!-- Draft Status Banner -->
<div id="draftBanner" class="hidden mb-6 p-4 bg-amber-50 border-2 border-amber-400 flex items-center gap-3">
    <i class="fas fa-info-circle text-amber-600 text-xl"></i>
    <div>
        <span class="font-bold text-amber-800 uppercase text-sm">Draft Mode</span>
        <span class="text-amber-700 text-sm ml-2">Changes are saved automatically. Click "Publish Routine" when ready.</span>
    </div>
</div>

<!-- Published Status Banner -->
<div id="publishedBanner" class="hidden mb-6 p-4 bg-emerald-50 border-2 border-emerald-400 flex items-center gap-3">
    <i class="fas fa-check-circle text-emerald-600 text-xl"></i>
    <div>
        <span class="font-bold text-emerald-800 uppercase text-sm">Published</span>
        <span class="text-emerald-700 text-sm ml-2">This routine is live. Teachers have been notified.</span>
    </div>
</div>

<!-- Routine Grid -->
<div class="os-card p-0 bg-white overflow-hidden">
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

<!-- Configure Slots Modal -->
<div id="configureSlotsModal" class="relative z-[9999] hidden" aria-modal="true">
    <div class="fixed inset-0 bg-black/80 transition-opacity backdrop-blur-sm z-[90]"></div>
    <div class="fixed inset-0 z-[100] w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white w-full max-w-2xl border-4 border-black shadow-[8px_8px_0px_#000]">
                <div class="bg-black p-6 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-clock text-2xl"></i>
                        <div>
                            <h3 class="text-xl font-black uppercase tracking-tighter">Configure Routine Slots</h3>
                            <p class="text-xs text-slate-400 mt-1">Define the grid structure for the routine. You can mix Theory (short) and Lab (long) slots.</p>
                        </div>
                    </div>
                    <button onclick="closeConfigureSlots()" class="text-white hover:text-yellow-400">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div class="p-6 max-h-[60vh] overflow-y-auto" id="slotsContainer">
                    <!-- Slots list will be loaded here -->
                </div>
                
                <!-- Add New Slot Form -->
                <div class="p-6 border-t-2 border-black bg-slate-50">
                    <p class="text-xs font-black uppercase tracking-widest mb-4 text-slate-600">Add New Slot</p>
                    <div class="grid grid-cols-12 gap-3">
                        <div class="col-span-2">
                            <label class="text-[10px] font-bold uppercase text-slate-500 block mb-1">Start Time</label>
                            <input type="time" id="newSlotStart" class="w-full px-3 py-2 border-2 border-black text-sm font-bold">
                        </div>
                        <div class="col-span-2">
                            <label class="text-[10px] font-bold uppercase text-slate-500 block mb-1">End Time</label>
                            <input type="time" id="newSlotEnd" class="w-full px-3 py-2 border-2 border-black text-sm font-bold">
                        </div>
                        <div class="col-span-4">
                            <label class="text-[10px] font-bold uppercase text-slate-500 block mb-1">Label</label>
                            <input type="text" id="newSlotLabel" placeholder="e.g. Lab Session" class="w-full px-3 py-2 border-2 border-black text-sm font-bold">
                        </div>
                        <div class="col-span-2">
                            <label class="text-[10px] font-bold uppercase text-slate-500 block mb-1">Type</label>
                            <select id="newSlotType" class="w-full px-3 py-2 border-2 border-black text-sm font-bold bg-white">
                                <option value="theory">Theory</option>
                                <option value="lab">Lab</option>
                                <option value="break">Break</option>
                            </select>
                        </div>
                        <div class="col-span-2 flex items-end">
                            <button onclick="addNewSlot()" class="w-full px-4 py-2 bg-black text-white font-bold text-sm hover:bg-yellow-400 hover:text-black transition-colors flex items-center justify-center gap-2">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="p-6 border-t-2 border-black flex justify-end gap-3">
                    <button onclick="closeConfigureSlots()" class="px-6 py-3 bg-white border-2 border-black text-black font-bold text-sm hover:bg-slate-100">
                        Cancel
                    </button>
                    <button onclick="saveSlots()" class="px-6 py-3 bg-blue-600 text-white font-bold text-sm hover:bg-blue-700 shadow-[4px_4px_0px_#000]">
                        <i class="fas fa-save mr-2"></i> Save Configuration
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Class Modal -->
<div id="assignClassModal" class="relative z-[9999] hidden" aria-modal="true">
    <div class="fixed inset-0 bg-black/80 transition-opacity backdrop-blur-sm z-[90]"></div>
    <div class="fixed inset-0 z-[100] w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white w-full max-w-lg border-4 border-black shadow-[8px_8px_0px_#000]">
                <div class="bg-black p-6 text-white">
                    <h3 class="text-xl font-black uppercase tracking-tighter">Schedule Class</h3>
                    <p class="text-xs text-slate-400 mt-1" id="assignModalSubtitle">SUNDAY • 08:50 - 09:40 • THEORY</p>
                </div>
                
                <div class="p-6 space-y-5">
                    <input type="hidden" id="assignSlotId">
                    <input type="hidden" id="assignDay">
                    
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest mb-2">Select Course</label>
                        <select id="assignCourse" onchange="onCourseSelect()" class="w-full px-4 py-3 border-2 border-black font-bold text-sm bg-white focus:border-blue-500 focus:ring-0">
                            <option value="">-- Select Course --</option>
                        </select>
                        <p class="text-xs text-emerald-600 mt-1 font-medium hidden" id="autoFillNote">
                            <i class="fas fa-check-circle mr-1"></i> Auto-filled teacher & room
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest mb-2">Assigned Teacher</label>
                            <input type="text" id="assignTeacher" readonly class="w-full px-4 py-3 border-2 border-slate-300 bg-slate-100 font-bold text-sm text-slate-600 cursor-not-allowed">
                            <input type="hidden" id="assignTeacherId">
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest mb-2">Room / Lab</label>
                            <select id="assignRoom" class="w-full px-4 py-3 border-2 border-black font-bold text-sm bg-white">
                                <option value="">-- Select Room --</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Multi-Slot Selection -->
                    <div class="border-t-2 border-slate-200 pt-4">
                        <button type="button" onclick="toggleMultiSlot()" class="flex items-center gap-2 w-full text-left">
                            <i class="fas fa-chevron-right text-xs transition-transform" id="multiSlotArrow"></i>
                            <span class="text-xs font-black uppercase tracking-widest text-slate-600">Also assign to other slots</span>
                            <span class="text-xs text-slate-400 ml-auto" id="additionalSlotCount">(0 selected)</span>
                        </button>
                        <div id="multiSlotContainer" class="hidden mt-3 max-h-40 overflow-y-auto border border-slate-200 rounded p-2 bg-slate-50 space-y-1">
                            <!-- Slot checkboxes populated dynamically -->
                        </div>
                    </div>
                    
                    <!-- Conflict Warning -->
                    <div id="conflictWarning" class="hidden p-4 bg-red-50 border-2 border-red-400 text-red-700">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span class="font-bold text-sm uppercase">Teacher Not Available</span>
                        </div>
                        <p class="text-sm mt-1" id="conflictMessage"></p>
                    </div>
                </div>
                
                <div class="p-6 border-t-2 border-slate-200 flex justify-end gap-3">
                    <button onclick="closeAssignModal()" class="px-6 py-3 bg-white border-2 border-black text-black font-bold text-sm hover:bg-slate-100">
                        Cancel
                    </button>
                    <button id="assignBtn" onclick="submitAssignment()" class="px-6 py-3 bg-blue-600 text-white font-bold text-sm hover:bg-blue-700 shadow-[4px_4px_0px_#000]">
                        <i class="fas fa-calendar-plus mr-2"></i> Assign Class
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Assignment Modal -->
<div id="viewAssignmentModal" class="relative z-[9999] hidden" aria-modal="true">
    <div class="fixed inset-0 bg-black/80 transition-opacity backdrop-blur-sm z-[90]"></div>
    <div class="fixed inset-0 z-[100] w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white w-full max-w-md border-4 border-black shadow-[8px_8px_0px_#000]">
                <div class="bg-black p-6 text-white">
                    <h3 class="text-xl font-black uppercase tracking-tighter" id="viewCourseCode">CSE-101</h3>
                    <p class="text-xs text-slate-400 mt-1" id="viewCourseName">Introduction to Computer Systems</p>
                </div>
                
                <div class="p-6 space-y-4">
                    <input type="hidden" id="viewAssignmentId">
                    
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-500 block">Day</span>
                            <span class="font-bold" id="viewDay">Sunday</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-500 block">Time</span>
                            <span class="font-bold" id="viewTime">08:50 - 09:40</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-500 block">Teacher</span>
                            <span class="font-bold" id="viewTeacher">Dr. Muhammad Yunus</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-500 block">Room</span>
                            <span class="font-bold" id="viewRoom">Room 101</span>
                        </div>
                    </div>
                </div>
                
                <div class="p-6 border-t-2 border-slate-200 flex justify-between">
                    <button onclick="removeAssignment()" class="px-6 py-3 bg-red-600 text-white font-bold text-sm hover:bg-red-700 flex items-center gap-2">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                    <button onclick="closeViewModal()" class="px-6 py-3 bg-white border-2 border-black text-black font-bold text-sm hover:bg-slate-100">
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
    cursor: pointer;
    transition: all 0.15s ease;
    position: relative;
}
.slot-cell:hover {
    background-color: #fef3c7 !important;
}
.slot-cell.break-slot {
    background-color: #fef3c7;
    cursor: default;
}
.slot-cell.break-slot:hover {
    background-color: #fef3c7 !important;
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
.assignment-card {
    background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
    border-left: 4px solid #0284c7;
    padding: 8px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.15s ease;
}
.assignment-card:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.assignment-card.lab-type {
    background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%);
    border-left-color: #7c3aed;
}
.slot-item {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 4px;
    padding: 12px;
    margin-bottom: 8px;
    transition: all 0.15s ease;
}
.slot-item:hover {
    border-color: #000;
}
.slot-item.break-type {
    background: #fef3c7;
    border-color: #f59e0b;
}
.slot-item.lab-type {
    background: #f3e8ff;
    border-color: #8b5cf6;
}
</style>

<script>
// VERSION 2.0 - Updated <?php echo date('Y-m-d H:i:s'); ?>

console.log('=== ROUTINE MANAGER v2.0 LOADED ===');
console.log('If you see this, the new code is active. Course dropdown should show [section]');

const API_BASE = '<?php echo BASE_URL; ?>/api/admin/routine.php';
const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];

let slots = [];
let assignments = [];
let courses = [];
let rooms = [];
let currentDraft = null;

// Initialize
document.addEventListener('DOMContentLoaded', async () => {
    console.log('Loading courses with section data...');
    await loadInitialData();
});

async function loadInitialData() {
    try {
        // Load slots
        const slotsRes = await fetch(`${API_BASE}?action=get_slots`);
        slots = await slotsRes.json();
        
        // Load rooms
        const roomsRes = await fetch(`${API_BASE}?action=get_rooms`);
        rooms = await roomsRes.json();
        
        // Load or create draft
        const draftRes = await fetch(`${API_BASE}?action=get_draft&semester_id=<?php echo $semester['id'] ?? 0; ?>`);
        currentDraft = await draftRes.json();
        
        // Load courses
        const coursesRes = await fetch(`${API_BASE}?action=get_courses&semester_id=<?php echo $semester['id'] ?? 0; ?>`);
        courses = await coursesRes.json();
        
        // Load assignments
        if (currentDraft && currentDraft.id) {
            const assignRes = await fetch(`${API_BASE}?action=get_assignments&draft_id=${currentDraft.id}`);
            assignments = await assignRes.json();
            
            // Update UI banners
            updateBanners();
        }
        
        renderGrid();
        populateRoomDropdown();
    } catch (error) {
        console.error('Failed to load data:', error);
        showGlobalModal('Error', 'Failed to load routine data', 'error');
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
                // Render multiple cards if needed
                cellContent = `<div class="space-y-2 p-1">`;
                
                slotAssignments.forEach(assignment => {
                    const cardClass = assignment.course_type === 'lab' ? 'assignment-card lab-type' : 'assignment-card';
                    // Simplify card for density
                    cellContent += `
                        <div class="${cardClass} relative group" onclick="viewAssignment(${assignment.id}); event.stopPropagation();">
                            <div class="font-bold text-xs truncate" title="${assignment.course_code}">${assignment.course_code}</div>
                            <div class="text-[10px] text-slate-600 truncate" title="${assignment.teacher_name}">${assignment.teacher_name}</div>
                            <div class="flex justify-between items-center mt-1">
                                <span class="text-[9px] font-bold bg-white/50 px-1 rounded text-slate-600">${assignment.room_code}</span>
                                <span class="text-[9px] text-slate-500">${assignment.section || ''}</span>
                            </div>
                        </div>
                    `;
                });
                
                // Add "Plus" button at bottom to add MORE classes to this slot
                cellContent += `
                    <div onclick="openAssignModal(${slot.id}, '${day}', '${slot.label}', '${slot.start_time}', '${slot.end_time}', '${slot.slot_type}')" 
                         class="text-center p-1 rounded hover:bg-slate-100 cursor-pointer text-slate-400 hover:text-blue-600 transition-colors mt-1"
                         title="Add another class to this slot">
                        <i class="fas fa-plus-circle text-xs"></i>
                    </div>
                </div>`;
            } else {
                cellContent = `
                    <div class="w-full h-full min-h-[80px] flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity cursor-pointer"
                         onclick="openAssignModal(${slot.id}, '${day}', '${slot.label}', '${slot.start_time}', '${slot.end_time}', '${slot.slot_type}')">
                        <i class="fas fa-plus text-slate-400"></i>
                    </div>
                `;
            }
            
            // Note: Removed onclick from TD because we handle it inside now for granular control
            rowHtml += `<td class="${cellClass} border-r border-slate-200 align-top p-0">${cellContent}</td>`;
        });
        
        rowHtml += '</tr>';
        tbody.innerHTML += rowHtml;
    });
}

// Configure Slots
function openConfigureSlots() {
    document.getElementById('configureSlotsModal').classList.remove('hidden');
    renderSlotsConfig();
}

function closeConfigureSlots() {
    document.getElementById('configureSlotsModal').classList.add('hidden');
}

function renderSlotsConfig() {
    const container = document.getElementById('slotsContainer');
    container.innerHTML = '';
    
    slots.forEach((slot, index) => {
        let slotClass = 'slot-item';
        if (slot.slot_type === 'break') slotClass += ' break-type';
        if (slot.slot_type === 'lab') slotClass += ' lab-type';
        
        const typeColors = {
            'theory': 'bg-blue-100 text-blue-800',
            'lab': 'bg-purple-100 text-purple-800',
            'break': 'bg-amber-100 text-amber-800'
        };
        
        container.innerHTML += `
            <div class="${slotClass}" data-slot-id="${slot.id}">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="text-sm font-mono font-bold text-slate-500">
                            ${slot.start_time} - ${slot.end_time}
                        </div>
                        <div class="text-sm font-bold">${slot.label}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-1 text-[10px] font-bold uppercase rounded ${typeColors[slot.slot_type]}">${slot.slot_type}</span>
                        <button onclick="removeSlot(${slot.id})" class="w-8 h-8 flex items-center justify-center text-red-500 hover:bg-red-100 rounded">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
}

function addNewSlot() {
    const start = document.getElementById('newSlotStart').value;
    const end = document.getElementById('newSlotEnd').value;
    const label = document.getElementById('newSlotLabel').value;
    const type = document.getElementById('newSlotType').value;
    
    if (!start || !end || !label) {
        showGlobalModal('Error', 'Please fill all fields', 'error');
        return;
    }
    
    slots.push({
        id: null,
        start_time: start,
        end_time: end,
        label: label,
        slot_type: type,
        slot_order: slots.length + 1
    });
    
    // Sort by start time
    slots.sort((a, b) => a.start_time.localeCompare(b.start_time));
    
    renderSlotsConfig();
    
    // Clear form
    document.getElementById('newSlotStart').value = '';
    document.getElementById('newSlotEnd').value = '';
    document.getElementById('newSlotLabel').value = '';
}

function removeSlot(slotId) {
    slots = slots.filter(s => s.id !== slotId);
    renderSlotsConfig();
}

async function saveSlots() {
    try {
        const response = await fetch(`${API_BASE}?action=save_slots`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ slots: slots })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            closeConfigureSlots();
            await loadInitialData();
            showGlobalModal('Success', 'Slot configuration saved', 'success');
        } else {
            showGlobalModal('Error', result.error || 'Failed to save', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showGlobalModal('Error', 'Network error', 'error');
    }
}

// Assign Class Modal
function openAssignModal(slotId, day, slotLabel, startTime, endTime, slotType) {
    document.getElementById('assignSlotId').value = slotId;
    document.getElementById('assignDay').value = day;
    document.getElementById('assignModalSubtitle').textContent = `${day.toUpperCase()} • ${startTime} - ${endTime} • ${slotType.toUpperCase()}`;
    
    // Populate courses dropdown
    const courseSelect = document.getElementById('assignCourse');
    courseSelect.innerHTML = '<option value="">-- Select Course --</option>';
    
    console.log('Populating courses dropdown. Total courses:', courses.length);
    courses.forEach(course => {
        const typeLabel = course.course_type === 'lab' ? ' (LAB)' : ' (THEORY)';
        const semLabel = course.semester_name ? ` - ${course.semester_name}` : '';
        courseSelect.innerHTML += `<option value="${course.course_offering_id}" 
            data-teacher="${course.teacher_name || 'TBA'}"
            data-teacher-id="${course.teacher_id || ''}"
            data-room-id="${course.default_room_id || ''}"
            data-room-code="${course.default_room_code || ''}"
            data-type="${course.course_type}"
        >${course.course_code} [${course.section}]${semLabel}${typeLabel}</option>`;
    });
    
    // Reset fields
    document.getElementById('assignTeacher').value = '';
    document.getElementById('assignTeacherId').value = '';
    document.getElementById('assignRoom').value = '';
    document.getElementById('autoFillNote').classList.add('hidden');
    document.getElementById('conflictWarning').classList.add('hidden');
    
    // Reset multi-slot section
    resetMultiSlotSection();
    
    document.getElementById('assignClassModal').classList.remove('hidden');
}

function closeAssignModal() {
    document.getElementById('assignClassModal').classList.add('hidden');
    resetMultiSlotSection();
}

async function onCourseSelect() {
    const select = document.getElementById('assignCourse');
    const option = select.options[select.selectedIndex];
    
    if (!option.value) return;
    
    const teacherName = option.dataset.teacher;
    const teacherId = option.dataset.teacherId;
    const roomId = option.dataset.roomId;
    const roomCode = option.dataset.roomCode;
    
    document.getElementById('assignTeacher').value = teacherName;
    document.getElementById('assignTeacherId').value = teacherId;
    
    if (roomId) {
        document.getElementById('assignRoom').value = roomId;
    }
    
    document.getElementById('autoFillNote').classList.remove('hidden');
    
    // Check teacher availability
    if (teacherId) {
        const slotId = document.getElementById('assignSlotId').value;
        const day = document.getElementById('assignDay').value;
        
        const response = await fetch(`${API_BASE}?action=check_availability&teacher_id=${teacherId}&slot_id=${slotId}&day=${day}&draft_id=${currentDraft.id}`);
        const result = await response.json();
        
        if (!result.available) {
            document.getElementById('conflictWarning').classList.remove('hidden');
            document.getElementById('conflictMessage').textContent = `This teacher is already assigned to ${result.conflict.course_code} (${result.conflict.section}) at this time.`;
            document.getElementById('assignBtn').disabled = true;
            document.getElementById('assignBtn').classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            document.getElementById('conflictWarning').classList.add('hidden');
            document.getElementById('assignBtn').disabled = false;
            document.getElementById('assignBtn').classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
}

function populateRoomDropdown() {
    const select = document.getElementById('assignRoom');
    select.innerHTML = '<option value="">-- Select Room --</option>';
    
    rooms.forEach(room => {
        const typeLabel = room.room_type === 'lab' ? ' (Lab)' : '';
        select.innerHTML += `<option value="${room.id}">${room.code} - ${room.name}${typeLabel}</option>`;
    });
}

async function submitAssignment() {
    const courseOfferingId = document.getElementById('assignCourse').value;
    const slotId = document.getElementById('assignSlotId').value;
    const roomId = document.getElementById('assignRoom').value;
    const day = document.getElementById('assignDay').value;
    
    if (!courseOfferingId || !slotId || !roomId || !day) {
        showGlobalModal('Error', 'Please fill all required fields', 'error');
        return;
    }
    
    // Collect all slots (primary + additional)
    const allSlots = [{ day: day, slot_id: parseInt(slotId) }];
    
    // Get additional slots from checkboxes
    const additionalCheckboxes = document.querySelectorAll('input[name="additionalSlot"]:checked');
    additionalCheckboxes.forEach(cb => {
        const [extraDay, extraSlotId] = cb.value.split('_');
        allSlots.push({ day: extraDay, slot_id: parseInt(extraSlotId) });
    });
    
    try {
        // Use bulk_assign API to handle all slots at once
        const response = await fetch(`${API_BASE}?action=bulk_assign`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                draft_id: currentDraft.id,
                course_offering_id: parseInt(courseOfferingId),
                room_id: parseInt(roomId),
                slots: allSlots
            })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            closeAssignModal();
            await loadInitialData();
            const msg = allSlots.length > 1 
                ? `Assigned to ${result.assigned} slot(s) successfully` 
                : 'Class assigned successfully';
            showGlobalModal('Success', msg, 'success');
        } else {
            showGlobalModal('Error', result.error || 'Failed to assign class', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showGlobalModal('Error', 'Network error', 'error');
    }
}

// View Assignment
function viewAssignment(assignmentId) {
    const assignment = assignments.find(a => a.id === assignmentId);
    if (!assignment) return;
    
    document.getElementById('viewAssignmentId').value = assignment.id;
    document.getElementById('viewCourseCode').textContent = assignment.course_code;
    document.getElementById('viewCourseName').textContent = assignment.course_name;
    document.getElementById('viewDay').textContent = assignment.day_of_week;
    
    const slot = slots.find(s => s.id === assignment.slot_id);
    document.getElementById('viewTime').textContent = slot ? `${slot.start_time} - ${slot.end_time}` : '';
    
    document.getElementById('viewTeacher').textContent = assignment.teacher_name;
    document.getElementById('viewRoom').textContent = `${assignment.room_code} - ${assignment.room_name}`;
    
    document.getElementById('viewAssignmentModal').classList.remove('hidden');
}

function closeViewModal() {
    document.getElementById('viewAssignmentModal').classList.add('hidden');
}

async function removeAssignment() {
    const assignmentId = document.getElementById('viewAssignmentId').value;
    
    if (!confirm('Are you sure you want to remove this class assignment?')) return;
    
    try {
        const response = await fetch(`${API_BASE}?action=remove_assignment`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ assignment_id: parseInt(assignmentId) })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            closeViewModal();
            await loadInitialData();
            showGlobalModal('Success', 'Assignment removed', 'success');
        } else {
            showGlobalModal('Error', result.error || 'Failed to remove', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showGlobalModal('Error', 'Network error', 'error');
    }
}

// Update Banners based on draft status
function updateBanners() {
    const draftBanner = document.getElementById('draftBanner');
    const publishedBanner = document.getElementById('publishedBanner');
    const publishBtn = document.getElementById('publishBtn');
    const revertDraftBtn = document.getElementById('revertDraftBtn');
    
    // Always show publish button
    publishBtn.classList.remove('hidden');
    
    if (currentDraft.status === 'published') {
        publishedBanner.classList.remove('hidden');
        draftBanner.classList.add('hidden');
        revertDraftBtn.classList.remove('hidden');
    } else {
        draftBanner.classList.remove('hidden');
        publishedBanner.classList.add('hidden');
        // Show revert button only if routine was published at least once
        if (currentDraft.published_at) {
            revertDraftBtn.classList.remove('hidden');
        } else {
            revertDraftBtn.classList.add('hidden');
        }
    }
}

// Auto-revert to draft when editing a published routine
// (Removed - edits now go directly to DB, admin manually saves as draft)

// Save as Draft (manual button)
async function saveAsDraft() {
    if (!confirm('Save this routine as a draft? Only teachers with assigned classes will be notified. Students will not see it until you publish.')) return;
    
    try {
        const response = await fetch(`${API_BASE}?action=save_as_draft`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ draft_id: currentDraft.id })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            currentDraft.status = 'draft';
            updateBanners();
            showGlobalModal('Success', result.message || 'Routine saved as draft. Teachers have been notified.', 'success');
        } else {
            showGlobalModal('Error', result.error || 'Failed to save as draft', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showGlobalModal('Error', 'Network error', 'error');
    }
}

// Publish Routine
async function publishRoutine() {
    if (!confirm('Are you sure you want to publish this routine? All teachers will be notified.')) return;
    
    try {
        const response = await fetch(`${API_BASE}?action=publish_routine`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ draft_id: currentDraft.id })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showGlobalModal('Success', result.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showGlobalModal('Error', result.error || 'Failed to publish', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showGlobalModal('Error', 'Network error', 'error');
    }
}

// ==================================
// MULTI-SLOT INLINE FUNCTIONS
// ==================================
let multiSlotExpanded = false;

function toggleMultiSlot() {
    multiSlotExpanded = !multiSlotExpanded;
    const container = document.getElementById('multiSlotContainer');
    const arrow = document.getElementById('multiSlotArrow');
    
    if (multiSlotExpanded) {
        container.classList.remove('hidden');
        arrow.style.transform = 'rotate(90deg)';
        populateMultiSlotCheckboxes();
    } else {
        container.classList.add('hidden');
        arrow.style.transform = 'rotate(0deg)';
    }
}

function populateMultiSlotCheckboxes() {
    const container = document.getElementById('multiSlotContainer');
    const currentSlotId = parseInt(document.getElementById('assignSlotId').value);
    const currentDay = document.getElementById('assignDay').value;
    const courseSelect = document.getElementById('assignCourse');
    const courseType = courseSelect.selectedIndex > 0 ? courseSelect.options[courseSelect.selectedIndex].dataset.type : null;
    
    container.innerHTML = '';
    
    DAYS.forEach(day => {
        slots.forEach(slot => {
            // Skip breaks
            if (slot.slot_type === 'break') return;
            
            // Skip the current slot (that's the primary one)
            if (day === currentDay && slot.id === currentSlotId) return;
            
            // Filter by course type if specified
            if (courseType && slot.slot_type !== courseType) return;
            
            const key = `${day}_${slot.id}`;
            
            // Check if already occupied
            const existingAssignment = assignments.find(a => a.day_of_week === day && a.slot_id === slot.id);
            const isOccupied = !!existingAssignment;
            
            const labelClass = isOccupied ? 'text-slate-400 line-through' : 'text-slate-700';
            const bgClass = isOccupied ? 'bg-red-50' : '';
            const occupiedNote = isOccupied ? ` [${existingAssignment.course_code}]` : '';
            
            container.innerHTML += `
                <label class="flex items-center gap-2 p-1.5 ${bgClass} rounded cursor-pointer hover:bg-slate-100 text-xs">
                    <input type="checkbox" name="additionalSlot" value="${key}" ${isOccupied ? 'disabled' : ''} onchange="updateAdditionalSlotCount()"
                        class="w-3 h-3 text-blue-600 border border-slate-300 rounded focus:ring-blue-500">
                    <span class="${labelClass}">
                        <strong>${day.substring(0,3)}</strong> ${slot.label} (${slot.start_time}-${slot.end_time})${occupiedNote}
                    </span>
                </label>
            `;
        });
    });
    
    if (container.innerHTML === '') {
        container.innerHTML = '<p class="text-center text-slate-500 py-2 text-xs">No additional slots available for this course type.</p>';
    }
}

function updateAdditionalSlotCount() {
    const checkboxes = document.querySelectorAll('input[name="additionalSlot"]:checked');
    document.getElementById('additionalSlotCount').textContent = `(${checkboxes.length} selected)`;
}

function resetMultiSlotSection() {
    multiSlotExpanded = false;
    const container = document.getElementById('multiSlotContainer');
    const arrow = document.getElementById('multiSlotArrow');
    container.classList.add('hidden');
    container.innerHTML = '';
    arrow.style.transform = 'rotate(0deg)';
    document.getElementById('additionalSlotCount').textContent = '(0 selected)';
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
