<?php
/**
 * Student Class Routine
 * ACADEMIX - Academic Management System
 * 
 * Students can view their enrolled courses in a grid view
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('student');

$page_title = 'Class Routine';
$user_id = get_current_user_id();

// Get Student Details
$query = "SELECT s.id as student_id, s.department_id, s.batch_year, d.name as dept_name 
          FROM students s 
          JOIN departments d ON s.department_id = d.id 
          WHERE s.user_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    die("Student record not found.");
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
            <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Schedule</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                <?php echo e($student['dept_name'] ?? 'Department'); ?>
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">My Class <span class="text-black">Routine</span></h1>
        <p class="text-xs text-slate-500 mt-1 font-medium"><span id="classCount">0</span> enrolled classes • <span id="semesterName">Current Semester</span></p>
    </div>
    
    <div class="flex flex-col items-end gap-2">
        <label class="text-[10px] font-bold uppercase text-slate-500">View Routine For Week</label>
        <div class="flex items-center gap-2">
            <button onclick="changeWeek(-1)" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition-colors">
                <i class="fas fa-chevron-left text-xs"></i>
            </button>
            <input type="date" id="weekPicker" class="px-3 py-1.5 border border-slate-300 rounded text-sm font-bold" onchange="onWeekChange()">
            <button onclick="changeWeek(1)" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition-colors">
                <i class="fas fa-chevron-right text-xs"></i>
            </button>
        </div>
        <div class="flex items-center gap-3 mt-1">
            <button onclick="window.print()" class="btn-os bg-white text-black border-black hover:bg-slate-100 flex items-center gap-2 btn-print-hide text-xs px-3 py-1">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
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

<style>
.slot-cell {
    min-height: 80px;
    transition: all 0.15s ease;
    position: relative;
    vertical-align: top;
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
.assignment-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    padding: 6px;
    margin-bottom: 4px;
}
.enrolled-class-card {
    background: #dcfce7;
    border: 1px solid #86efac;
    border-radius: 4px;
    padding: 6px;
    margin-bottom: 4px;
    transition: transform 0.1s;
}
.enrolled-class-card:hover {
    transform: scale(1.02);
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.enrolled-class-card.lab-type {
    background: #e0f2fe;
    border-color: #7dd3fc;
}
.other-class-card {
    background: #f1f5f9;
    border: 1px dashed #cbd5e1;
    border-radius: 4px;
    padding: 4px;
    margin-bottom: 4px;
    opacity: 0.7;
}
.rescheduled-card {
    background: #fef9c3;
    border: 1px solid #fde047;
    border-radius: 4px;
    padding: 6px;
    margin-bottom: 4px;
    position: relative;
}
.cancelled-card {
    background: #fee2e2;
    border: 1px solid #fca5a5;
    border-radius: 4px;
    padding: 6px;
    margin-bottom: 4px;
    opacity: 0.6;
    text-decoration: line-through;
}
@media print {
    .btn-print-hide { display: none !important; }
    #main-content { margin: 0 !important; width: 100% !important; }
    .os-card { box-shadow: none; border: 1px solid #ddd; }
}
</style>

<script>
const API_BASE = '<?php echo BASE_URL; ?>/api/student/routine.php';
const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
const studentId = <?php echo $student['student_id']; ?>;

let slots = [];
let routine = null;
let assignments = [];
let reschedules = [];
let myEnrolledCourseOfferings = [];
let currentWeekDates = {};
let selectedDate = new Date();

// Set date picker to today
document.getElementById('weekPicker').valueAsDate = selectedDate;

// Initialize
document.addEventListener('DOMContentLoaded', loadRoutineData);

function onWeekChange() {
    const input = document.getElementById('weekPicker');
    if (input.value) {
        selectedDate = new Date(input.value);
        loadRoutineData(); // Reload to refresh grid dates
    }
}

function changeWeek(offset) {
    selectedDate.setDate(selectedDate.getDate() + (offset * 7));
    document.getElementById('weekPicker').valueAsDate = selectedDate;
    loadRoutineData();
}

function getWeekDates(date) {
    const weekDates = {};
    const curr = new Date(date);
    // Adjust to find Sunday (0)
    const first = curr.getDate() - curr.getDay();
    
    for (let i = 0; i < 7; i++) {
        const next = new Date(curr);
        next.setDate(first + i);
        const dayName = DAYS[i];
        weekDates[dayName] = next.toISOString().split('T')[0];
    }
    return weekDates;
}

async function loadRoutineData() {
    try {
        // Load routine data for student
        const response = await fetch(`${API_BASE}?action=get_my_routine`);
        const data = await response.json();
        
        if (data.error) {
            console.error('Error:', data.error);
            document.getElementById('noRoutineMessage').classList.remove('hidden');
            document.getElementById('routineContainer').classList.add('hidden');
            return;
        }
        
        slots = data.slots || [];
        routine = data.routine;
        assignments = data.assignments || [];
        reschedules = data.reschedules || [];
        myEnrolledCourseOfferings = data.enrolled_course_offerings || [];
        
        if (!routine) {
            document.getElementById('noRoutineMessage').classList.remove('hidden');
            document.getElementById('routineContainer').classList.add('hidden');
            return;
        }
        
        // Update header info
        document.getElementById('classCount').textContent = data.enrolled_class_count || 0;
        document.getElementById('semesterName').textContent = routine.semester_name || 'Current Semester';
        
        // Calculate dates for the selected week
        currentWeekDates = getWeekDates(selectedDate);
        
        renderGrid();
    } catch (error) {
        console.error('Failed to load routine:', error);
        document.getElementById('noRoutineMessage').classList.remove('hidden');
        document.getElementById('routineContainer').classList.add('hidden');
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
            <th class="${slotClass} px-3 py-3 text-center text-[10px] font-black uppercase tracking-widest border-r border-slate-700">
                <div>${slot.label}${badge}</div>
                <div class="text-[9px] font-normal opacity-75 mt-1">
                    <i class="far fa-clock mr-1"></i>${slot.start_time}-${slot.end_time}
                </div>
            </th>
        `;
    });
    
    // 5 Days (Sunday - Thursday)
    const academicDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
    
    academicDays.forEach(day => {
        const dateStr = currentWeekDates[day];
        const displayDate = new Date(dateStr).toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
        const isToday = new Date().toISOString().split('T')[0] === dateStr;
        const dayClass = isToday ? 'bg-blue-50 text-blue-800' : 'bg-slate-100';
        
        let rowHtml = `
            <tr class="border-b-2 border-slate-200">
                <td class="px-4 py-3 ${dayClass} font-black text-sm uppercase tracking-wider border-r-2 border-slate-300 w-24">
                    ${day}<br><span class="text-[10px] font-normal opacity-75">${displayDate}</span>
                </td>
        `;
        
        slots.forEach(slot => {
            const isBreak = slot.slot_type === 'break';
            const cellClass = isBreak ? 'slot-cell break-slot' : 'slot-cell';
            
            // 1. Get Normal Assignments for this Slot
            const slotAssignments = assignments.filter(a => a.day_of_week === day && a.slot_id === slot.id);
            
            // 2. Check for NEW classes rescheduled TO this slot
            const rescheduledHere = reschedules.filter(r => r.new_date === dateStr && r.new_slot_id === slot.id);
            
            let cellContent = '';
            
            if (isBreak) {
                cellContent = `<span class="text-amber-600 text-xs font-bold uppercase"><i class="fas fa-coffee mr-1"></i>Break</span>`;
            } else {
                cellContent = '<div class="flex flex-col gap-1">';
                
                // Render Normal Assignments (check if cancelled)
                slotAssignments.forEach(assignment => {
                    const isEnrolled = myEnrolledCourseOfferings.includes(assignment.course_offering_id);
                    
                    // Check if Moved/Cancelled for this specific date
                    const isMovedAway = reschedules.find(r => 
                        r.original_routine_id === assignment.id && 
                        r.original_date === dateStr
                    );
                    
                    if (isEnrolled && isMovedAway) {
                        cellContent += `
                            <div class="cancelled-card text-left">
                                <div class="font-bold text-xs">${assignment.course_code}</div>
                                <div class="text-[9px] text-red-600 font-bold no-underline mt-1">Rescheduled</div>
                                <div class="text-[9px] text-slate-500">See new time</div>
                            </div>
                        `;
                    } else if (isEnrolled) {
                         const cardClass = assignment.course_type === 'lab' ? 'enrolled-class-card lab-type' : 'enrolled-class-card';
                         cellContent += `
                            <div class="${cardClass} text-left">
                                <div class="font-bold text-sm">${assignment.course_code}</div>
                                <div class="text-[10px] text-slate-600 mt-1">Sec ${assignment.section}</div>
                                <div class="text-[10px] text-slate-500 flex items-center gap-1">
                                    <i class="fas fa-user"></i> ${assignment.teacher_name || 'TBA'}
                                </div>
                                <div class="text-[10px] text-slate-500 flex items-center gap-1">
                                    <i class="fas fa-map-marker-alt"></i> ${assignment.room_code}
                                </div>
                            </div>
                        `;
                    } else {
                        // Not enrolled - smaller view
                         cellContent += `
                            <div class="other-class-card text-left">
                                <div class="font-bold text-xs text-slate-500">${assignment.course_code}</div>
                            </div>
                        `;
                    }
                });
                
                // Render INCOMING Reschedules
                rescheduledHere.forEach(res => {
                    // We need to fetch course info for this rescheduled class. 
                    // Since it's from 'enrolled' list, we can try to find matching assignment details in 'assignments'
                    // by matching routine_assignment_id (conceptually, though new structure makes this tricky if direct link missing).
                    // But we have 'original_routine_id'.
                    
                    const originalAssignment = assignments.find(a => a.id === res.original_routine_id);
                    if (originalAssignment) {
                        cellContent += `
                            <div class="rescheduled-card text-left">
                                <div class="flex justify-between items-start">
                                    <div class="font-bold text-sm">${originalAssignment.course_code}</div>
                                    <span class="text-[8px] bg-yellow-400 px-1 rounded font-bold">NEW TIME</span>
                                </div>
                                <div class="text-[10px] text-slate-700 mt-1">Rescheduled Class</div>
                                <div class="text-[10px] text-slate-600 flex items-center gap-1">
                                    <i class="fas fa-map-marker-alt"></i> ${res.new_room_code}
                                </div>
                            </div>
                        `;
                    }
                });
                
                cellContent += '</div>';
                
                if (cellContent === '<div class="flex flex-col gap-1"></div>') {
                     cellContent = `<span class="text-slate-300 text-xs">—</span>`;
                }
            }
            
            rowHtml += `<td class="${cellClass} px-2 py-2 border-r border-slate-200 text-center">${cellContent}</td>`;
        });
        
        rowHtml += '</tr>';
        tbody.innerHTML += rowHtml;
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
