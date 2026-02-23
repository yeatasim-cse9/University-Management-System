<?php
/**
 * Event Calendar Management
 * ACADEMIX - University Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('super_admin');

$action = $_GET['action'] ?? 'list';
$event_id = $_GET['id'] ?? null;
$page_title = match($action) {
    'create' => 'Initialize Event',
    'edit' => 'Modify Event',
    default => 'Event Pulse'
};

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request');
        redirect(BASE_URL . '/modules/super_admin/events.php');
    }
    
    $post_action = $_POST['action'] ?? '';
    
    if ($post_action === 'create') {
        $title = sanitize_input($_POST['title'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $event_date = sanitize_input($_POST['event_date'] ?? '');
        $start_time = sanitize_input($_POST['start_time'] ?? '');
        $end_time = sanitize_input($_POST['end_time'] ?? '');
        $location = sanitize_input($_POST['location'] ?? '');
        $event_type = sanitize_input($_POST['event_type'] ?? '');
        $created_by = get_current_user_id();
        
        $errors = validate_required(['title', 'event_date'], $_POST);
        
        if (empty($errors)) {
            $stmt = $db->prepare("INSERT INTO events (title, description, event_date, start_time, end_time, location, event_type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssi", $title, $description, $event_date, $start_time, $end_time, $location, $event_type, $created_by);
            
            if ($stmt->execute()) {
                create_audit_log('create_event', 'events', $stmt->insert_id, null, ['title' => $title]);
                set_flash('success', 'Event created successfully');
                redirect(BASE_URL . '/modules/super_admin/events.php');
            } else {
                set_flash('error', 'Failed to create event');
            }
        } else {
            set_flash('error', implode(', ', $errors));
        }
    }
    
    elseif ($post_action === 'update') {
        $id = intval($_POST['id']);
        $title = sanitize_input($_POST['title'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $event_date = sanitize_input($_POST['event_date'] ?? '');
        $start_time = sanitize_input($_POST['start_time'] ?? '');
        $end_time = sanitize_input($_POST['end_time'] ?? '');
        $location = sanitize_input($_POST['location'] ?? '');
        $event_type = sanitize_input($_POST['event_type'] ?? '');
        
        $stmt = $db->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, start_time = ?, end_time = ?, location = ?, event_type = ? WHERE id = ?");
        $stmt->bind_param("sssssssi", $title, $description, $event_date, $start_time, $end_time, $location, $event_type, $id);
        
        if ($stmt->execute()) {
            create_audit_log('update_event', 'events', $id);
            set_flash('success', 'Event updated successfully');
            redirect(BASE_URL . '/modules/super_admin/events.php');
        } else {
            set_flash('error', 'Failed to update event');
        }
    }
    
    elseif ($post_action === 'delete') {
        $id = intval($_POST['id']);
        
        $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            create_audit_log('delete_event', 'events', $id);
            set_flash('success', 'Event deleted successfully');
        } else {
            set_flash('error', 'Failed to delete event');
        }
        redirect(BASE_URL . '/modules/super_admin/events.php');
    }
}

// Get event for edit
$event = null;
if ($action === 'edit' && $event_id) {
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
}

// Get all events
$filter = $_GET['filter'] ?? 'all'; // all, upcoming, past
$search = $_GET['search'] ?? '';

$where = "1=1";
if ($filter === 'upcoming') {
    $where .= " AND event_date >= CURDATE()";
} elseif ($filter === 'past') {
    $where .= " AND event_date < CURDATE()";
}
if ($search) {
    $search_safe = $db->real_escape_string($search);
    $where .= " AND (title LIKE '%$search_safe%' OR description LIKE '%$search_safe%' OR location LIKE '%$search_safe%')";
}

$events = [];
$result = $db->query("SELECT e.*, u.username as created_by_name 
    FROM events e 
    LEFT JOIN users u ON e.created_by = u.id 
    WHERE $where 
    ORDER BY e.event_date DESC, e.start_time DESC");
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}

// Sidebar menu
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

// Page content
ob_start();
?>

<!-- Header -->
<div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white mb-6">
    <div class="relative z-10">
        <div class="flex items-center gap-3 mb-2">
            <span class="px-2 py-1 bg-red-600 text-white text-[10px] font-black uppercase tracking-widest border border-black">Schedule</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-red-500 inline-block mr-1"></span>
                Event Pulse
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">
            <?php echo $action === 'create' ? 'Initialize <span class="text-red-600">Event</span>' : ($action === 'edit' ? 'Modify <span class="text-red-600">Event</span>' : 'Event <span class="text-red-600">Calendar</span>'); ?>
        </h1>
    </div>
    
    <div class="flex items-center gap-4 relative z-10">
        <?php if ($action === 'list'): ?>
            <a href="?action=create" class="btn-os bg-red-600 text-white border-black hover:bg-black hover:text-white hover:border-black flex items-center gap-2">
                <i class="fas fa-plus"></i>
                <span class="hidden sm:inline">New Event</span>
            </a>
        <?php else: ?>
            <a href="?" class="btn-os bg-white text-black border-black hover:bg-black hover:text-white hover:border-black flex items-center gap-2">
                <i class="fas fa-arrow-left"></i>
                <span class="hidden sm:inline">Back to List</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($action === 'create' || $action === 'edit'): ?>
    <!-- Event Form -->
    <div class="os-card p-0 overflow-hidden bg-white max-w-4xl mx-auto">
        <div class="bg-black p-4 text-white border-b-2 border-black flex justify-between items-center">
            <h4 class="text-sm font-black uppercase tracking-widest text-white">Event Configuration</h4>
            <i class="fas fa-calendar-plus text-white/20"></i>
        </div>

        <form method="POST" class="p-6 md:p-8 space-y-6">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="<?php echo $action === 'edit' ? 'update' : 'create'; ?>">
            <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo e($event['id']); ?>">
            <?php endif; ?>

            <div class="space-y-6">
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Event Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="<?php echo e($event['title'] ?? ''); ?>" placeholder="ENTER EVENT TITLE..." class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" required>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Description</label>
                    <textarea name="description" rows="4" placeholder="EVENT DETAILS..." class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all placeholder-slate-400 font-mono"><?php echo e($event['description'] ?? ''); ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Date <span class="text-red-500">*</span></label>
                        <input type="date" name="event_date" value="<?php echo e($event['event_date'] ?? ''); ?>" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" required>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Category</label>
                        <div class="relative">
                            <select name="event_type" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none cursor-pointer">
                                <option value="">Select Category</option>
                                <option value="Academic" <?php echo ($event['event_type'] ?? '') === 'Academic' ? 'selected' : ''; ?>>Academic</option>
                                <option value="Cultural" <?php echo ($event['event_type'] ?? '') === 'Cultural' ? 'selected' : ''; ?>>Cultural</option>
                                <option value="Sports" <?php echo ($event['event_type'] ?? '') === 'Sports' ? 'selected' : ''; ?>>Sports</option>
                                <option value="Seminar" <?php echo ($event['event_type'] ?? '') === 'Seminar' ? 'selected' : ''; ?>>Seminar</option>
                                <option value="Workshop" <?php echo ($event['event_type'] ?? '') === 'Workshop' ? 'selected' : ''; ?>>Workshop</option>
                                <option value="Conference" <?php echo ($event['event_type'] ?? '') === 'Conference' ? 'selected' : ''; ?>>Conference</option>
                                <option value="Other" <?php echo ($event['event_type'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                             <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                <i class="fas fa-chevron-down text-black text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Start Time</label>
                        <input type="time" name="start_time" value="<?php echo e($event['start_time'] ?? ''); ?>" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400">
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">End Time</label>
                        <input type="time" name="end_time" value="<?php echo e($event['end_time'] ?? ''); ?>" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400">
                    </div>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Location</label>
                    <input type="text" name="location" value="<?php echo e($event['location'] ?? ''); ?>" placeholder="SPECIFY VENUE..." class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400">
                </div>
            </div>

            <div class="pt-6 border-t-2 border-black flex justify-end gap-4">
                 <a href="?" class="btn-os bg-white text-black border-black hover:bg-red-600 hover:text-white hover:border-black text-center">
                    Cancel
                </a>
                <button type="submit" class="btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black hover:border-black">
                    <i class="fas fa-save mr-2"></i> Confirm Event
                </button>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- List View -->
    <div class="os-card p-6 bg-white mb-8">
        <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
             <div class="w-full md:flex-1 space-y-2">
                <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Search Events</label>
                <div class="relative">
                    <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Search..." class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400">
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                        <i class="fas fa-search text-black text-xs"></i>
                    </div>
                </div>
            </div>
            
             <div class="w-full md:w-auto space-y-2">
                <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Filter</label>
                <div class="relative">
                     <select name="filter" class="w-full md:w-48 px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none cursor-pointer">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Events</option>
                        <option value="upcoming" <?php echo $filter === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                        <option value="past" <?php echo $filter === 'past' ? 'selected' : ''; ?>>Past</option>
                    </select>
                     <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                        <i class="fas fa-chevron-down text-black text-xs"></i>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black hover:border-black w-full md:w-auto h-[46px]">
                Sync
            </button>
            
            <?php if ($search || $filter !== 'all'): ?>
                <a href="?" class="btn-os bg-slate-100 text-black border-black hover:bg-red-600 hover:text-white hover:border-black w-full md:w-auto text-center h-[46px] flex items-center justify-center">
                    Clear
                </a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($events)): ?>
            <div class="col-span-full py-20 text-center bg-white border-2 border-dashed border-black">
                <div class="w-16 h-16 bg-slate-100 mx-auto border-2 border-black flex items-center justify-center mb-4">
                     <i class="fas fa-calendar-times text-2xl text-slate-400"></i>
                </div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No events scheduled.</p>
            </div>
        <?php else: ?>
            <?php foreach ($events as $e): ?>
                <?php 
                $is_past = strtotime($e['event_date']) < strtotime(date('Y-m-d'));
                $is_today = $e['event_date'] === date('Y-m-d');
                ?>
                <div class="os-card p-0 flex flex-col bg-white hover:-translate-y-1 hover:shadow-[6px_6px_0px_#000000] transition-all duration-300 group <?php echo $is_past ? 'opacity-70 grayscale' : ''; ?>">
                    <!-- Date Header -->
                    <div class="bg-black text-white p-4 flex justify-between items-center border-b-2 border-black">
                        <div class="flex flex-col items-center border-r-2 border-white pr-4">
                             <span class="text-2xl font-black leading-none"><?php echo date('d', strtotime($e['event_date'])); ?></span>
                             <span class="text-[9px] font-black uppercase tracking-widest"><?php echo date('M', strtotime($e['event_date'])); ?></span>
                        </div>
                        <div class="flex-1 text-right">
                             <span class="px-2 py-1 bg-white text-black text-[9px] font-black uppercase tracking-widest border border-black"><?php echo $e['event_type'] ?: 'Event'; ?></span>
                        </div>
                    </div>

                    <div class="p-6 flex-1 flex flex-col">
                        <div class="mb-4">
                             <?php if ($is_today): ?>
                                <span class="inline-block px-2 py-0.5 bg-red-600 text-white text-[8px] font-black uppercase tracking-widest mb-2 animate-pulse">Live Today</span>
                            <?php endif; ?>
                            <h3 class="text-xl font-black uppercase leading-tight group-hover:text-blue-600 transition-colors">
                                <?php echo e($e['title']); ?>
                            </h3>
                        </div>
                        
                        <p class="text-xs font-mono text-slate-600 leading-relaxed mb-6 line-clamp-3">
                            <?php echo e($e['description'] ?: 'No details provided.'); ?>
                        </p>

                        <div class="space-y-2 mt-auto pt-4 border-t-2 border-black border-dashed">
                            <div class="flex items-center gap-2 text-[10px] font-black text-black uppercase">
                                <i class="fas fa-clock text-slate-400 w-4"></i>
                                <?php echo $e['start_time'] ? date('g:i A', strtotime($e['start_time'])) : 'TBD'; ?>
                            </div>
                            <div class="flex items-center gap-2 text-[10px] font-black text-black uppercase">
                                <i class="fas fa-map-marker-alt text-slate-400 w-4"></i>
                                <span class="truncate"><?php echo e($e['location'] ?: 'TBA'); ?></span>
                            </div>
                        </div>
                        
                        <div class="flex gap-2 mt-6">
                            <a href="?action=edit&id=<?php echo $e['id']; ?>" class="flex-1 btn-os bg-white text-black border-black hover:bg-black hover:text-white py-2 text-center text-[10px]">
                                Modify
                            </a>
                            <form method="POST" onsubmit="return confirm('Delete this event?')" class="w-10">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $e['id']; ?>">
                                <button type="submit" class="w-full h-full flex items-center justify-center border-2 border-black bg-white hover:bg-red-600 hover:text-white transition-all shadow-[2px_2px_0px_#000000] active:shadow-none active:translate-x-[2px] active:translate-y-[2px]">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
