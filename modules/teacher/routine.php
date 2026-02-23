<?php
/**
 * Class Routine
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('teacher');

$page_title = 'Class Routine';
$user_id = get_current_user_id();

// Sidebar
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>
<div class="space-y-6">
    <!-- Class Routine Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-4xl font-black uppercase tracking-tighter border-b-4 border-black inline-block pb-2 mb-2">
                Class Routine
            </h1>
            <p class="text-sm font-bold uppercase tracking-widest text-gray-600">
                Weekly Schedule Management
            </p>
        </div>
        
        <div class="flex items-center gap-4">
             <div class="px-4 py-2 border-2 border-black bg-yellow-400 text-black text-xs font-black uppercase tracking-widest shadow-os">
                <i class="fas fa-info-circle mr-2"></i> Click a class to reschedule
            </div>
        </div>
    </div>

    <!-- Calendar Interface -->
    <div class="os-card p-0 bg-white overflow-hidden">
        <div class="p-4 border-b-2 border-black bg-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-black uppercase tracking-tight">Weekly Overview</h3>
             <div class="flex gap-2">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 bg-indigo-100 border border-indigo-500"></span>
                    <span class="text-[10px] font-bold uppercase tracking-widest">Regular</span>
                </div>
                 <div class="flex items-center gap-2">
                    <span class="w-3 h-3 bg-amber-100 border border-amber-500"></span>
                    <span class="text-[10px] font-bold uppercase tracking-widest">Rescheduled</span>
                </div>
            </div>
        </div>
        <div class="p-6">
            <div id='calendar' class="custom-calendar font-mono"></div>
        </div>
    </div>
</div>

<style>
/* Brutalist FullCalendar Customization */
.custom-calendar .fc-header-toolbar { margin-bottom: 1.5rem !important; }
.custom-calendar .fc-button {
    background: #fff !important; color: #000 !important; border: 2px solid #000 !important;
    border-radius: 0 !important; font-size: 10px !important; text-transform: uppercase !important;
    font-weight: 900 !important; padding: 8px 16px !important; box-shadow: 4px 4px 0px #000 !important;
}
.custom-calendar .fc-button:hover { background: #000 !important; color: #fff !important; transform: translate(2px, 2px) !important; box-shadow: 0px 0px 0px #000 !important; }
.custom-calendar .fc-button-active { background: #000 !important; color: #facc15 !important; }
.custom-calendar .fc-toolbar-title { font-weight: 900 !important; text-transform: uppercase !important; font-size: 1.5rem !important; }
.custom-calendar .fc-col-header-cell { background: #000 !important; padding: 10px 0 !important; }
.custom-calendar .fc-col-header-cell-cushion { font-size: 12px !important; text-transform: uppercase !important; font-weight: 900 !important; color: #fff !important; }

/* Event Card Reset */
.custom-calendar .fc-event {
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
    margin-bottom: 4px !important;
}
.custom-calendar .fc-event-main {
    padding: 0 !important;
    overflow: hidden !important;
}

/* New Event Card Design */
.event-card {
    height: 100%;
    width: 100%;
    padding: 8px;
    border-radius: 6px; /* Smooth corners */
    border-left: 4px solid #000; /* Accent border default */
    box-shadow: 2px 2px 4px rgba(0,0,0,0.1); /* Subtle depth */
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    cursor: pointer;
}
.event-card:hover {
    transform: translateY(-1px);
    box-shadow: 4px 4px 6px rgba(0,0,0,0.15);
}

/* Theory Class Style */
.type-theory {
    background-color: #e0f2fe; /* Light Blue */
    border-left-color: #0369a1; /* Dark Blue */
}
.type-theory .event-code { color: #0c4a6e; }

/* Lab Class Style */
.type-lab {
    background-color: #f3e8ff; /* Light Purple */
    border-left-color: #7e22ce; /* Dark Purple */
}
.type-lab .event-code { color: #581c87; }

/* Rescheduled Class Style */
.type-rescheduled {
    background-color: #fef3c7; /* Light Orange/Yellow */
    border-left-color: #d97706; /* Dark Amber */
}
.type-rescheduled .event-code { color: #78350f; }

/* Typography & Layout */
.event-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}
.event-code {
    font-size: 14px;
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 2px;
}
.event-time, .event-room {
    font-size: 11px;
    color: #4b5563; /* Gray-600 */
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 4px;
}
.event-batch {
    font-size: 10px;
    font-weight: 700;
    color: #6b7280;
    text-align: right;
    margin-top: auto;
    text-transform: uppercase;
}
.rescheduled-badge {
    font-size: 9px;
    background: #000;
    color: #fff;
    padding: 2px 6px;
    border-radius: 999px;
    font-weight: 700;
    text-transform: uppercase;
    display: inline-block;
}
</style>

<?php 
$extra_scripts = '
<!-- Reschedule Modal -->
<div id="rescheduleModal" class="relative z-[9999] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-black/80 transition-opacity backdrop-blur-sm z-[90]"></div>
    <div class="fixed inset-0 z-[100] w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden bg-white text-left shadow-2xl transition-all sm:my-8 w-full sm:max-w-lg border-4 border-black box-shadow-os">
                <div class="bg-black p-6 text-white border-b-4 border-black">
                    <h3 class="text-xl font-black uppercase tracking-tighter" id="modal-title">Reschedule Class</h3>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">Modify schedule for this session</p>
                </div>

                <div class="p-8">
                    <form id="rescheduleForm" class="space-y-6">
                        <input type="hidden" id="course_offering_id" name="course_offering_id">
                        <input type="hidden" id="original_date" name="original_date">
                        
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest mb-2">Course</label>
                            <input type="text" id="course_display" readonly class="w-full bg-gray-100 border-2 border-black p-4 text-sm font-bold uppercase cursor-not-allowed text-gray-500">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-black uppercase tracking-widest mb-2">New Date</label>
                                <input type="date" id="new_date" name="new_date" required class="w-full bg-white border-2 border-black p-4 text-sm font-bold uppercase focus:ring-0 focus:border-black focus:shadow-os transition-all" onchange="checkAvailability()">
                            </div>
                            <!-- Schedule Preview -->
                            <div class="col-span-2 md:col-span-2 hidden" id="schedule_preview_container">
                                <label class="block text-xs font-black uppercase tracking-widest mb-2 text-indigo-600">
                                    <i class="fas fa-calendar-day mr-1"></i> Existing Schedule for <span id="preview_date_label"></span>
                                </label>
                                <div id="schedule_preview_list" class="space-y-2 max-h-40 overflow-y-auto border-2 border-dashed border-gray-300 p-2 bg-gray-50">
                                    <!-- Slots will go here -->
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs font-black uppercase tracking-widest mb-2">Start</label>
                                    <input type="time" id="new_start_time" name="new_start_time" required class="w-full bg-white border-2 border-black p-4 text-sm font-bold uppercase focus:ring-0 focus:border-black focus:shadow-os transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-black uppercase tracking-widest mb-2">End</label>
                                    <input type="time" id="new_end_time" name="new_end_time" required class="w-full bg-white border-2 border-black p-4 text-sm font-bold uppercase focus:ring-0 focus:border-black focus:shadow-os transition-all">
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest mb-2">Room (Optional)</label>
                            <input type="text" id="room_number" name="room_number" placeholder="OVERRIDE ROOM..." class="w-full bg-white border-2 border-black p-4 text-sm font-bold uppercase focus:ring-0 focus:border-black focus:shadow-os transition-all">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest mb-2">Reason</label>
                            <textarea id="reason" name="reason" rows="2" placeholder="REASON FOR CHANGE..." class="w-full bg-white border-2 border-black p-4 text-sm font-bold uppercase focus:ring-0 focus:border-black focus:shadow-os transition-all"></textarea>
                        </div>

                         <div class="flex gap-4 pt-4 border-t-2 border-gray-100">
                            <button type="button" onclick="closeModal()" class="flex-1 py-4 bg-white border-2 border-black text-black text-xs font-black uppercase tracking-widest hover:bg-gray-100 transition-colors">Cancel</button>
                            <button type="button" onclick="submitReschedule()" class="flex-1 py-4 bg-black border-2 border-black text-white text-xs font-black uppercase tracking-widest hover:bg-yellow-400 hover:text-black transition-colors shadow-os">Confirm Change</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var calendarEl = document.getElementById("calendar");
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: "timeGridWeek",
            slotMinTime: "08:00:00",
            slotMaxTime: "19:00:00",
            allDaySlot: false,
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "dayGridMonth,timeGridWeek,timeGridDay"
            },
            events: "' . BASE_URL . '/api/teacher/schedule.php?action=get_events",
            eventClick: function(info) {
                // Allow rescheduling for both regular and already rescheduled classes
                openRescheduleModal(info.event);
            },
            height: "auto",
            eventContent: function(arg) {
                let props = arg.event.extendedProps;
                let startTime = arg.event.start.toLocaleTimeString([], {hour: \'numeric\', minute:\'2-digit\'});
                let endTime = arg.event.end ? arg.event.end.toLocaleTimeString([], {hour: \'numeric\', minute:\'2-digit\'}) : \'\';
                
                // Determine CSS Class based on properties
                let cardClass = \'event-card\';
                let isRescheduled = props.type === \'rescheduled\';
                
                if (isRescheduled) {
                    cardClass += \' type-rescheduled\';
                } else if (props.course_type === \'lab\') {
                    cardClass += \' type-lab\';
                } else {
                    cardClass += \' type-theory\';
                }
                
                let badgeHtml = isRescheduled ? \'<div class="rescheduled-badge">🔄 Rescheduled</div>\' : \'\';
                
                return {
                    html: `
                        <div class="${cardClass}">
                            <div class="event-header">
                                <div class="event-code">${arg.event.title}</div>
                                ${badgeHtml}
                            </div>
                            
                            <div class="flex flex-col gap-1 mt-1">
                                <div class="event-time">
                                    <i class="far fa-clock"></i> ${startTime} - ${endTime}
                                </div>
                                <div class="event-room">
                                    <i class="fas fa-map-marker-alt"></i> ${props.room}
                                </div>
                            </div>
                            
                            <div class="event-batch">
                                Sem ${props.semester} - ${props.section}
                            </div>
                        </div>
                    `
                };
            }
        });
        calendar.render();
        window.calendarApi = calendar;
    });

    function openRescheduleModal(event) {
        document.getElementById("rescheduleModal").classList.remove("hidden");
        document.getElementById("course_display").value = event.title;
        document.getElementById("course_offering_id").value = event.extendedProps.course_offering_id;
        
        var props = event.extendedProps;
        var dateStr;
        
        // Determine Original Date: If it\'s a reschedule, use the stored original_date.
        // If it\'s regular, use the event\'s current start date.
        if (props.type === "rescheduled" && props.original_date) {
            dateStr = props.original_date;
        } else {
            var date = event.start;
            dateStr = date.getFullYear() + "-" + String(date.getMonth() + 1).padStart(2, "0") + "-" + String(date.getDate()).padStart(2, "0");
        }
        
        document.getElementById("original_date").value = dateStr;
        
        // For pre-filling "New Date", if it\'s already rescheduled, show the *current* scheduled date
        // If it\'s regular, show the original date (which is the current scheduled date)
        var currentSchedDate = event.start;
        var currentSchedDateStr = currentSchedDate.getFullYear() + "-" + String(currentSchedDate.getMonth() + 1).padStart(2, "0") + "-" + String(currentSchedDate.getDate()).padStart(2, "0");
        document.getElementById("new_date").value = currentSchedDateStr;
        
        // Trigger availability check for the initial date
        checkAvailability();
        
        var date = event.start; // Ensure date is defined from event.start for time extraction
        var startStr = String(date.getHours()).padStart(2, "0") + ":" + String(date.getMinutes()).padStart(2, "0");
        document.getElementById("new_start_time").value = startStr;
        
        if (event.end) {
            var endStr = String(event.end.getHours()).padStart(2, "0") + ":" + String(event.end.getMinutes()).padStart(2, "0");
            document.getElementById("new_end_time").value = endStr;
        }
    }

    function closeModal() {
        document.getElementById("rescheduleModal").classList.add("hidden");
        document.getElementById("rescheduleForm").reset();
        document.getElementById("schedule_preview_container").classList.add("hidden");
        document.getElementById("schedule_preview_list").innerHTML = "";
    }

    async function checkAvailability() {
        const dateStr = document.getElementById("new_date").value;
        const previewContainer = document.getElementById("schedule_preview_container");
        const previewList = document.getElementById("schedule_preview_list");
        const dateLabel = document.getElementById("preview_date_label");

        if (!dateStr) {
            previewContainer.classList.add("hidden");
            return;
        }

        dateLabel.textContent = dateStr;
        previewContainer.classList.remove("hidden");
        previewList.innerHTML = \'<div class="text-xs font-bold text-gray-400 animate-pulse">Checking availability...</div>\';

        try {
            const response = await fetch("' . BASE_URL . '/api/teacher/schedule.php?action=check_availability&date=" + dateStr);
            const slots = await response.json();

            previewList.innerHTML = "";

            if (slots.length === 0) {
                previewList.innerHTML = \'<div class="text-xs font-bold text-green-600"><i class="fas fa-check-circle mr-1"></i> No classes scheduled. Time is free.</div>\';
            } else {
                slots.forEach(slot => {
                    const bgColor = slot.type === \'regular\' ? \'bg-indigo-300 border-black text-black\' : \'bg-amber-300 border-black text-black\';
                    const html = `
                        <div class="flex justify-between items-center p-2 border-l-4 ${bgColor} text-xs">
                            <div>
                                <span class="font-black mr-2">${slot.start} - ${slot.end}</span>
                                <span class="font-bold uppercase">${slot.title}</span>
                            </div>
                            <span class="text-[10px] font-bold text-gray-500 uppercase">${slot.description}</span>
                        </div>
                    `;
                    previewList.innerHTML += html;
                });
            }
        } catch (error) {
            console.error("Error fetching schedule:", error);
            previewList.innerHTML = \'<div class="text-xs font-bold text-red-500">Error loading schedule.</div>\';
        }
    }

    async function submitReschedule() {
        const formData = {
            course_offering_id: document.getElementById("course_offering_id").value,
            original_date: document.getElementById("original_date").value,
            new_date: document.getElementById("new_date").value,
            new_start_time: document.getElementById("new_start_time").value,
            new_end_time: document.getElementById("new_end_time").value,
            room_number: document.getElementById("room_number").value,
            reason: document.getElementById("reason").value
        };

        try {
            const response = await fetch("' . BASE_URL . '/api/teacher/schedule.php?action=reschedule", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(formData),
            });
            const result = await response.json();
            if (response.ok) {
                closeModal();
                window.calendarApi.refetchEvents();
                closeModal();
                window.calendarApi.refetchEvents();
                showGlobalModal("Success", "CLASS RESCHEDULED SUCCESSFULLY", "success");
            } else {
                showGlobalModal("Error", result.error || "Reschedule failed.", "error");
            }
        } catch (error) {
            console.error("Error:", error);
            showGlobalModal("System Error", "Network connection failure.", "error");
        }
    }
</script>
';
?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
