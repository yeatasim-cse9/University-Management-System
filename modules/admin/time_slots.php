<?php
/**
 * Time Slot Management Module
 * Text-book definition of academic hours.
 */
require_once __DIR__ . '/../../config/settings.php';
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/routine_helper.php';
require_role('admin');

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $start = $_POST['start_time'];
            $end = $_POST['end_time'];
            $label = $_POST['label'];
            $is_break = isset($_POST['is_break']) ? 1 : 0;
            
            $stmt = $db->prepare("INSERT INTO time_slots (start_time, end_time, label, is_break) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $start, $end, $label, $is_break);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Time Slot Deployed.";
            } else {
                $_SESSION['error'] = "Deployment Failed: " . $db->error;
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            $db->query("DELETE FROM time_slots WHERE id = $id");
            $_SESSION['success'] = "Slot Decommissioned.";
        }
    }
    header("Location: time_slots.php");
    exit;
}

$slots = get_time_slots($db);

$page_title = "Time Slots";
ob_start();
?>

<!-- Header -->
<div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white mb-8">
    <div class="relative z-10">
        <div class="flex items-center gap-3 mb-2">
            <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Configuration</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                Temporal Grid
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">Time <span class="text-black">Slots</span></h1>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- List -->
    <div class="lg:col-span-2">
        <div class="space-y-4">
            <?php foreach ($slots as $slot): 
                $border = $slot['is_break'] ? 'border-dashed' : 'border-solid';
                $bg = $slot['is_break'] ? 'bg-slate-100' : 'bg-white';
            ?>
            <div class="flex items-center justify-between p-4 border-2 border-black shadow-[4px_4px_0px_#000] <?php echo $bg . ' ' . $border; ?>">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-black text-white flex items-center justify-center font-black text-xs border-2 border-black">
                        <?php echo $slot['is_break'] ? '<i class="fas fa-coffee"></i>' : '<i class="fas fa-clock"></i>'; ?>
                    </div>
                    <div>
                        <h3 class="font-black text-lg uppercase"><?php echo htmlspecialchars($slot['label']); ?></h3>
                        <p class="text-[10px] font-bold text-slate-500 font-mono tracking-widest">
                            <?php echo date('H:i', strtotime($slot['start_time'])); ?> - <?php echo date('H:i', strtotime($slot['end_time'])); ?>
                            // <?php echo (strtotime($slot['end_time']) - strtotime($slot['start_time'])) / 60; ?> MIN
                        </p>
                    </div>
                </div>
                <form method="POST" onsubmit="return confirm('Decommission Slot?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $slot['id']; ?>">
                    <button class="w-8 h-8 flex items-center justify-center border-2 border-black hover:bg-black hover:text-white transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($slots)): ?>
                <div class="p-8 border-2 border-dashed border-black text-center">
                    <p class="font-black text-slate-400 uppercase">No Time Slots Defined.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create Form -->
    <div>
        <div class="os-card p-6 bg-black text-white border-2 border-black relative overflow-hidden shadow-[8px_8px_0px_#e2e8f0]">
            <h3 class="font-black text-lg uppercase mb-6 tracking-widest border-b border-white/20 pb-2">Deploy New Slot</h3>
            
            <form method="POST" class="space-y-4 relative z-10">
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Slot Label</label>
                    <input type="text" name="label" placeholder="e.g. MORNING SESSION A" class="w-full bg-white/10 border-2 border-white/30 p-2 text-xs font-bold text-white uppercase focus:border-yellow-400 focus:outline-none" required>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Start</label>
                        <input type="time" name="start_time" class="w-full bg-white/10 border-2 border-white/30 p-2 text-xs font-bold text-white focus:border-yellow-400 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold uppercase text-slate-400 block mb-1">End</label>
                        <input type="time" name="end_time" class="w-full bg-white/10 border-2 border-white/30 p-2 text-xs font-bold text-white focus:border-yellow-400 focus:outline-none" required>
                    </div>
                </div>
                
                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="is_break" id="is_break" class="accent-yellow-400 w-4 h-4 rounded-none">
                    <label for="is_break" class="text-[10px] font-bold uppercase text-slate-300">Mark as Break / Recess</label>
                </div>
                
                <button type="submit" class="w-full bg-white text-black py-3 text-xs font-black uppercase tracking-widest hover:bg-yellow-400 transition-colors mt-4 border-2 border-transparent hover:border-black">
                    Initialize Slot
                </button>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
