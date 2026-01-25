<?php
/**
 * Class Schedule Management
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('admin');

$page_title = 'Class Schedule';
$user_id = get_current_user_id();

// Get admin's department(s)
$stmt = $db->prepare("SELECT d.id FROM departments d JOIN department_admins da ON d.id = da.department_id WHERE da.user_id = ? AND d.deleted_at IS NULL");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$department_ids = [];
while ($row = $result->fetch_assoc()) {
    $department_ids[] = $row['id'];
}
$dept_id_list = !empty($department_ids) ? implode(',', $department_ids) : '0';

$action = $_GET['action'] ?? 'list';
$schedule_id = $_GET['id'] ?? null;

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request');
        redirect(BASE_URL . '/modules/admin/class-schedule.php');
    }

    $post_action = $_POST['action'] ?? '';

    if ($post_action === 'create') {
        $offering_id = intval($_POST['course_offering_id']);
        $day = sanitize_input($_POST['day_of_week']);
        $start_time = sanitize_input($_POST['start_time']);
        $end_time = sanitize_input($_POST['end_time']);
        $room = sanitize_input($_POST['room_number']);
        $building = sanitize_input($_POST['building']);

        $errors = validate_required(['course_offering_id', 'day_of_week', 'start_time', 'end_time'], $_POST);

        // Verify offering permission
        $check_perm = $db->query("SELECT co.id FROM course_offerings co JOIN courses c ON co.course_id = c.id WHERE co.id = $offering_id AND c.department_id IN ($dept_id_list)");
        
        if ($check_perm->num_rows > 0) {
            if (!empty($room)) {
                $check_query = "SELECT id FROM class_schedule 
                    WHERE day_of_week = ? AND room_number = ? 
                    AND (start_time < ? AND end_time > ?)";
                
                if (!empty($building)) {
                    $check_query .= " AND building = ?";
                    $stmt_check = $db->prepare($check_query);
                    $stmt_check->bind_param("sssss", $day, $room, $end_time, $start_time, $building);
                } else {
                    $stmt_check = $db->prepare($check_query);
                    $stmt_check->bind_param("ssss", $day, $room, $end_time, $start_time);
                }
                
                $stmt_check->execute();
                if ($stmt_check->get_result()->num_rows > 0) {
                    $errors[] = "Room $room is already booked for this time slot (Regular Schedule Check).";
                }
            }
            
            if (empty($errors)) {
                $stmt = $db->prepare("INSERT INTO class_schedule (course_offering_id, day_of_week, start_time, end_time, room_number, building) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssss", $offering_id, $day, $start_time, $end_time, $room, $building);
                
                if ($stmt->execute()) {
                    create_audit_log('create_schedule', 'class_schedule', $stmt->insert_id, null, ['offering_id' => $offering_id]);
                    set_flash('success', 'Schedule added successfully');
                } else {
                    set_flash('error', 'Failed to add schedule');
                }
            } else {
                set_flash('error', implode('<br>', $errors));
            }
        } else {
            set_flash('error', 'Permission denied');
        }
        redirect(BASE_URL . '/modules/admin/class-schedule.php');
    } elseif ($post_action === 'delete') {
        $id = intval($_POST['id']);
        
        $check_perm = $db->query("SELECT cs.id FROM class_schedule cs 
            JOIN course_offerings co ON cs.course_offering_id = co.id 
            JOIN courses c ON co.course_id = c.id 
            WHERE cs.id = $id AND c.department_id IN ($dept_id_list)");
            
        if ($check_perm->num_rows > 0) {
            $db->query("DELETE FROM class_schedule WHERE id = $id");
            create_audit_log('delete_schedule', 'class_schedule', $id);
            set_flash('success', 'Schedule removed successfully');
        } else {
            set_flash('error', 'Permission denied');
        }
        redirect(BASE_URL . '/modules/admin/class-schedule.php');
    }
}

// Fetch Active Offerings for Select
$offerings = [];
$off_res = $db->query("SELECT co.id, c.course_code, co.section, c.course_name 
    FROM course_offerings co 
    JOIN courses c ON co.course_id = c.id 
    JOIN courses cs ON co.course_id = cs.id
    WHERE c.department_id IN ($dept_id_list) AND co.status = 'open' 
    ORDER BY c.course_code ASC");
// Re-running query correctly without redundant join if not needed, or just keep simple
// Fetch Active Offerings for Select (Filtered for Batches 8-12 / Recent Years)
$off_res = $db->query("SELECT co.id, c.course_code, co.section, c.course_name, ay.year 
    FROM course_offerings co 
    JOIN courses c ON co.course_id = c.id 
    JOIN semesters s ON co.semester_id = s.id
    JOIN academic_years ay ON s.academic_year_id = ay.id
    WHERE c.department_id IN ($dept_id_list) 
    AND co.status = 'open' 
    AND ay.year >= '2020' 
    ORDER BY c.course_code ASC");

while ($row = $off_res->fetch_assoc()) { $offerings[] = $row; }

// Fetch View Data
$view_data = null;
if ($action === 'view' && $schedule_id) {
    $stmt = $db->prepare("SELECT cs.*, c.course_code, c.course_name, co.section, s.name as semester_name, ay.year as academic_year
                         FROM class_schedule cs
                         JOIN course_offerings co ON cs.course_offering_id = co.id
                         JOIN courses c ON co.course_id = c.id
                         JOIN semesters s ON co.semester_id = s.id
                         JOIN academic_years ay ON s.academic_year_id = ay.id
                         WHERE cs.id = ? AND c.department_id IN ($dept_id_list)");
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $view_data = $stmt->get_result()->fetch_assoc();
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
            <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Logistics</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                Temporal Assignments
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">Class <span class="text-black">Schedule</span></h1>
    </div>
</div>

<?php if ($action === 'view' && $view_data): ?>
    <div class="os-card p-0 bg-white overflow-hidden">
        <div class="bg-black p-8 text-white relative border-b-2 border-black">
            <div class="flex flex-col md:flex-row items-center gap-8">
                <div class="w-32 h-32 bg-white text-black rounded-none border-2 border-white flex items-center justify-center relative shadow-[4px_4px_0px_#fff]">
                    <i class="fas fa-clock text-5xl"></i>
                </div>
                <div class="text-center md:text-left">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Schedule Protocol</p>
                    <h2 class="text-4xl md:text-5xl font-black uppercase tracking-tighter leading-none mb-3"><?php echo e($view_data['course_code']); ?> - <?php echo e($view_data['day_of_week']); ?></h2>
                    <div class="flex flex-wrap justify-center md:justify-start gap-2">
                        <span class="px-2 py-1 bg-white text-black text-[9px] font-black uppercase tracking-widest border border-black"><?php echo e($view_data['course_name']); ?></span>
                        <span class="px-2 py-1 bg-black border border-white text-white text-[9px] font-black uppercase tracking-widest">SEC: <?php echo e($view_data['section']); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8 border-b-2 border-black">
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3 italic">Temporal Coordinates</p>
                <div class="bg-slate-50 border-2 border-black p-6 space-y-3 font-mono">
                    <div class="flex justify-between items-center group">
                        <span class="text-[10px] font-bold text-black uppercase">Operating Day</span>
                        <span class="text-sm font-black text-black uppercase"><?php echo e($view_data['day_of_week']); ?></span>
                    </div>
                    <div class="flex justify-between items-center group">
                        <span class="text-[10px] font-bold text-black uppercase">Window</span>
                        <span class="text-sm font-black text-black"><?php echo date('h:i A', strtotime($view_data['start_time'])); ?> - <?php echo date('h:i A', strtotime($view_data['end_time'])); ?></span>
                    </div>
                </div>
            </div>
            
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3 italic">Tactical Coordinates</p>
                <div class="bg-slate-50 border-2 border-black p-6 space-y-3 font-mono">
                    <div class="flex justify-between items-center group">
                        <span class="text-[10px] font-bold text-black uppercase">Installation (Building)</span>
                        <span class="text-sm font-black text-black uppercase"><?php echo e($view_data['building'] ?: 'DEFRAG UNIT'); ?></span>
                    </div>
                    <div class="flex justify-between items-center group">
                        <span class="text-[10px] font-bold text-black uppercase">Sector (Room)</span>
                        <span class="text-sm font-black text-black"><?php echo e($view_data['room_number']); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="p-8 flex justify-between items-center bg-slate-50">
            <a href="?" class="btn-os bg-white text-black border-black hover:bg-black hover:text-white flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Terminate View
            </a>
            <form method="POST" onsubmit="return confirm('Initiate schedule decommissioning protocol?');">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $view_data['id']; ?>">
                <button type="submit" class="btn-os bg-white text-black border-black hover:bg-red-600 hover:text-white flex items-center gap-2 shadow-[4px_4px_0px_#000]">
                    <i class="fas fa-trash-alt"></i> Decommission Slot
                </button>
            </form>
        </div>
    </div>

<?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Schedule Form -->
        <div class="lg:col-span-1">
            <div class="os-card p-0 bg-white sticky top-8">
                <div class="bg-black p-6 text-white border-b-2 border-black flex items-center gap-4">
                    <div class="w-12 h-12 bg-white text-black flex items-center justify-center border-2 border-white">
                        <i class="fas fa-plus text-xl"></i>
                    </div>
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Tactical Engine</p>
                        <h3 class="text-xl font-black uppercase tracking-tighter">Schedule Window</h3>
                    </div>
                </div>
                
                <div class="p-6">
                    <form method="POST" class="space-y-6">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="create">
                        
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Select Offering</label>
                            <div class="relative">
                                <select name="course_offering_id" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none cursor-pointer" required>
                                    <option value="">Choose Asset</option>
                                    <?php foreach ($offerings as $o): ?>
                                        <option value="<?php echo $o['id']; ?>"><?php echo e($o['course_code'] . ' - ' . $o['section']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                    <i class="fas fa-chevron-down text-black text-xs"></i>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Operating Day</label>
                            <div class="relative">
                                <select name="day_of_week" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none cursor-pointer" required>
                                    <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day): ?>
                                        <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                    <i class="fas fa-chevron-down text-black text-xs"></i>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Activation</label>
                                <input type="time" name="start_time" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all" required>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Deactivation</label>
                                <input type="time" name="end_time" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all" required>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Building</label>
                                <input type="text" name="building" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" placeholder="Main">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Sector (Room)</label>
                                <input type="text" name="room_number" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" placeholder="101" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="w-full btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black hover:border-black flex items-center justify-center gap-2">
                            <i class="fas fa-calendar-plus"></i> Authorize Slot
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Schedule List -->
        <div class="lg:col-span-3">
            <div class="os-card p-0 bg-white">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-black text-white text-[10px] font-black uppercase tracking-widest border-b-2 border-black">
                                <th class="px-6 py-4 text-left">Academic Asset</th>
                                <th class="px-6 py-4 text-left">Temporal Coordinates</th>
                                <th class="px-6 py-4 text-left">Tactical Coordinates</th>
                                <th class="px-6 py-4 text-right">Operations</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y-2 divide-black">
                            <?php 
                            $list_query = "SELECT cs.id, c.course_code, co.section, cs.day_of_week, cs.start_time, cs.end_time, cs.room_number, cs.building 
                                           FROM class_schedule cs 
                                           JOIN course_offerings co ON cs.course_offering_id = co.id 
                                           JOIN courses c ON co.course_id = c.id 
                                           WHERE c.department_id IN ($dept_id_list) 
                                           ORDER BY FIELD(cs.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), cs.start_time ASC";
                            $list_res = $db->query($list_query);
                            
                            if ($list_res->num_rows > 0):
                                while ($row = $list_res->fetch_assoc()):
                            ?>
                                <tr class="hover:bg-yellow-50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="text-[11px] font-black text-black uppercase transition-colors"><?php echo e($row['course_code']); ?> - <?php echo e($row['section']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-[10px] font-black text-black uppercase italic"><?php echo e($row['day_of_week']); ?></div>
                                        <div class="text-[9px] font-bold text-slate-500 uppercase italic tracking-widest"><?php echo date('h:i A', strtotime($row['start_time'])); ?> - <?php echo date('h:i A', strtotime($row['end_time'])); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-[10px] font-black text-black uppercase italic">Room <?php echo e($row['room_number']); ?></div>
                                        <div class="text-[9px] font-bold text-slate-500 uppercase italic leading-tight"><?php echo e($row['building'] ?: 'Tactical Sector'); ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="?action=view&id=<?php echo $row['id']; ?>" class="w-8 h-8 flex items-center justify-center border-2 border-black bg-white hover:bg-black hover:text-white transition-all shadow-[2px_2px_0px_#000000]" title="View Slot Details">
                                                <i class="fas fa-eye text-xs"></i>
                                            </a>
                                            <form method="POST" class="inline" onsubmit="return confirm('Initiate decommissioning protocol?');">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" class="w-8 h-8 flex items-center justify-center border-2 border-black bg-white hover:bg-red-600 hover:text-white transition-all shadow-[2px_2px_0px_#000000]" title="Remove Slot">
                                                    <i class="fas fa-trash-alt text-xs"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <i class="fas fa-calendar-times text-4xl text-slate-300 mb-2 block"></i>
                                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">No temporal assignments detected in sectors</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
