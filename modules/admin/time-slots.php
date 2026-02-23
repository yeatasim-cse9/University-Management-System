<?php
/**
 * Time Slot Management
 * ACADEMIX
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('admin');

$page_title = 'Time Slot Management';
$user_id = get_current_user_id();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request');
        redirect(BASE_URL . '/modules/admin/time-slots.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $start_time = sanitize_input($_POST['start_time']);
        $end_time = sanitize_input($_POST['end_time']);
        $label = sanitize_input($_POST['label']);
        $is_break = isset($_POST['is_break']) ? 1 : 0;

        $stmt = $db->prepare("INSERT INTO time_slots (start_time, end_time, label, is_break) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $start_time, $end_time, $label, $is_break);

        if ($stmt->execute()) {
            set_flash('success', 'Time slot added successfully');
        } else {
            set_flash('error', 'Failed to add time slot: ' . $db->error);
        }
        redirect(BASE_URL . '/modules/admin/time-slots.php');

    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $db->query("DELETE FROM time_slots WHERE id = $id");
        set_flash('success', 'Time slot deleted');
        redirect(BASE_URL . '/modules/admin/time-slots.php');
    }
}

// Fetch Time Slots
$slots = [];
$res = $db->query("SELECT * FROM time_slots ORDER BY start_time ASC");
while ($row = $res->fetch_assoc()) { $slots[] = $row; }

ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white mb-8">
    <div class="relative z-10">
        <div class="flex items-center gap-3 mb-2">
            <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Logistics</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                Temporal Assets
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">Time Slot <span class="text-black">Management</span></h1>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Add Slot Form -->
    <div class="lg:col-span-1">
        <div class="os-card p-0 bg-white sticky top-8">
            <div class="bg-black p-6 text-white border-b-2 border-black flex items-center gap-4">
                <div class="w-12 h-12 bg-white text-black flex items-center justify-center border-2 border-white">
                    <i class="fas fa-plus text-xl"></i>
                </div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">New Window</p>
                    <h3 class="text-xl font-black uppercase tracking-tighter">Add Slot</h3>
                </div>
            </div>
            
            <div class="p-6">
                <form method="POST" class="space-y-6">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="create">
                    
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Label (Optional)</label>
                        <input type="text" name="label" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" placeholder="e.g. MORNING A">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Start</label>
                            <input type="time" name="start_time" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all" required>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">End</label>
                            <input type="time" name="end_time" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all" required>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 bg-slate-50 p-4 border-2 border-slate-200">
                        <input type="checkbox" name="is_break" id="is_break" class="w-5 h-5 border-2 border-black text-black focus:ring-0 cursor-pointer">
                        <label for="is_break" class="text-[10px] font-black text-black uppercase tracking-widest cursor-pointer">Mark as Break/Recess</label>
                    </div>
                    
                    <button type="submit" class="w-full btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black hover:border-black flex items-center justify-center gap-2">
                        <i class="fas fa-plus-circle"></i> Create Slot
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Slot List -->
    <div class="lg:col-span-2">
        <div class="os-card p-0 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-black text-white text-[10px] font-black uppercase tracking-widest border-b-2 border-black">
                            <th class="px-6 py-4 text-left">Window</th>
                            <th class="px-6 py-4 text-left">Details</th>
                            <th class="px-6 py-4 text-left">Duration</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-2 divide-black">
                        <?php foreach ($slots as $slot): 
                            $start = new DateTime($slot['start_time']);
                            $end = new DateTime($slot['end_time']);
                            $duration = $start->diff($end);
                        ?>
                        <tr class="hover:bg-yellow-50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="text-[11px] font-black text-black uppercase"><?php echo $start->format('h:i A'); ?> - <?php echo $end->format('h:i A'); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-[10px] font-bold text-black uppercase"><?php echo e($slot['label'] ?: 'N/A'); ?></div>
                                <?php if($slot['is_break']): ?>
                                    <span class="text-[9px] font-black text-amber-600 uppercase tracking-widest border border-amber-600 px-1">BREAK</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-[10px] font-bold text-slate-500 uppercase italic"><?php echo $duration->format('%h hr %i min'); ?></div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this time slot?');">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $slot['id']; ?>">
                                    <button type="submit" class="w-8 h-8 flex items-center justify-center border-2 border-black bg-white hover:bg-red-600 hover:text-white transition-all shadow-[2px_2px_0px_#000000]">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($slots)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <i class="fas fa-clock text-4xl text-slate-300 mb-2 block"></i>
                                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">No time slots defined</div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
