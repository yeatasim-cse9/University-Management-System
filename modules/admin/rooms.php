<?php
/**
 * Room Management
 * ACADEMIX - Premium "Neo-Brutalist" Redesign
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('admin');

$page_title = 'Room Management';
$user_id = get_current_user_id();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request');
        redirect(BASE_URL . '/modules/admin/rooms.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $room_number = sanitize_input($_POST['room_number']);
        $building = sanitize_input($_POST['building']);
        $capacity = intval($_POST['capacity']);
        $type = sanitize_input($_POST['type']);
        $status = sanitize_input($_POST['status']);
        $has_projector = isset($_POST['has_projector']) ? 1 : 0;
        $has_ac = isset($_POST['has_ac']) ? 1 : 0;

        $stmt = $db->prepare("INSERT INTO rooms (room_number, building, capacity, type, status, has_projector, has_ac) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissii", $room_number, $building, $capacity, $type, $status, $has_projector, $has_ac);

        if ($stmt->execute()) {
            set_flash('success', 'Room added successfully');
        } else {
            set_flash('error', 'Failed to add room: ' . $db->error);
        }
        redirect(BASE_URL . '/modules/admin/rooms.php');

    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $db->query("DELETE FROM rooms WHERE id = $id");
        set_flash('success', 'Room deleted');
        redirect(BASE_URL . '/modules/admin/rooms.php');
    }
}

// Fetch Rooms
$rooms = [];
$res = $db->query("SELECT * FROM rooms ORDER BY building ASC, room_number ASC");
while ($row = $res->fetch_assoc()) { $rooms[] = $row; }

ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white mb-8">
    <div class="relative z-10">
        <div class="flex items-center gap-3 mb-2">
            <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Infrastructure</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                Physical Assets
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">Room <span class="text-black">Management</span></h1>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Add Room Form -->
    <div class="lg:col-span-1">
        <div class="os-card p-0 bg-white sticky top-8">
            <div class="bg-black p-6 text-white border-b-2 border-black flex items-center gap-4">
                <div class="w-12 h-12 bg-white text-black flex items-center justify-center border-2 border-white">
                    <i class="fas fa-plus text-xl"></i>
                </div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">New Sector</p>
                    <h3 class="text-xl font-black uppercase tracking-tighter">Add Room</h3>
                </div>
            </div>
            
            <div class="p-6">
                <form method="POST" class="space-y-6">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="create">
                    
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Room Number</label>
                        <input type="text" name="room_number" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" required>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Building</label>
                        <input type="text" name="building" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400">
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Capacity</label>
                         <input type="number" name="capacity" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all" value="40" required>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Type</label>
                        <select name="type" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none cursor-pointer">
                            <option value="theory">Theory</option>
                            <option value="lab">Lab</option>
                            <option value="seminar">Seminar</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Facilities</label>
                        <div class="flex gap-6 py-2">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox" name="has_projector" value="1" class="peer appearance-none w-5 h-5 border-2 border-black bg-white checked:bg-black transition-all">
                                    <i class="fas fa-check text-white text-[10px] absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 opacity-0 peer-checked:opacity-100 pointer-events-none"></i>
                                </div>
                                <span class="text-[10px] font-black text-black uppercase tracking-widest group-hover:text-slate-600">Projector</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox" name="has_ac" value="1" class="peer appearance-none w-5 h-5 border-2 border-black bg-white checked:bg-black transition-all">
                                    <i class="fas fa-check text-white text-[10px] absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 opacity-0 peer-checked:opacity-100 pointer-events-none"></i>
                                </div>
                                <span class="text-[10px] font-black text-black uppercase tracking-widest group-hover:text-slate-600">AC</span>
                            </label>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Status</label>
                        <select name="status" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none cursor-pointer">
                            <option value="active">Active</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="w-full btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black hover:border-black flex items-center justify-center gap-2">
                        <i class="fas fa-plus-circle"></i> Create Room
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Room List (Grid View) -->
    <div class="lg:col-span-2">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($rooms as $room): ?>
            <div class="os-card p-0 bg-white group hover:-translate-y-1 hover:shadow-[8px_8px_0px_#000] relative">
                <!-- Status Strip -->
                <div class="h-2 w-full border-b-2 border-black <?php echo $room['status'] === 'active' ? 'bg-green-500' : 'bg-red-500'; ?>"></div>
                
                <div class="p-4 relative">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 flex items-center justify-center border-2 border-black bg-yellow-400 font-black text-xl shadow-[2px_2px_0px_#000]">
                            <?php echo substr($room['room_number'], 0, 1); ?>
                        </div>
                        <div class="flex flex-col items-end">
                            <span class="text-[9px] font-black uppercase tracking-widest text-slate-500"><?php echo e($room['building']); ?></span>
                            <span class="text-2xl font-black uppercase leading-none"><?php echo e($room['room_number']); ?></span>
                        </div>
                    </div>
                    
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between border-b border-black/10 pb-1">
                            <span class="text-[10px] font-bold uppercase text-slate-500">Capacity</span>
                            <span class="text-xs font-black"><?php echo $room['capacity']; ?> Seats</span>
                        </div>
                         <div class="flex justify-between border-b border-black/10 pb-1">
                            <span class="text-[10px] font-bold uppercase text-slate-500">Type</span>
                            <span class="text-xs font-black uppercase"><?php echo ucfirst($room['type']); ?></span>
                        </div>
                    </div>
                    
                    <div class="flex gap-2 mb-4">
                        <span class="flex-1 py-1 border border-black flex items-center justify-center gap-1 text-[9px] font-bold uppercase <?php echo $room['has_projector'] ? 'bg-black text-yellow-400' : 'bg-slate-100 text-slate-400 opacity-50'; ?>">
                            <i class="fas fa-video"></i> PROJ
                        </span>
                        <span class="flex-1 py-1 border border-black flex items-center justify-center gap-1 text-[9px] font-bold uppercase <?php echo $room['has_ac'] ? 'bg-black text-cyan-400' : 'bg-slate-100 text-slate-400 opacity-50'; ?>">
                            <i class="fas fa-snowflake"></i> AC
                        </span>
                    </div>

                    <form method="POST" onsubmit="return confirm('Delete this room permanently?');" class="absolute top-4 left-4 opacity-0 group-hover:opacity-100 transition-opacity">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $room['id']; ?>">
                        <button type="submit" class="w-6 h-6 flex items-center justify-center bg-red-600 text-white border border-black hover:scale-110 transition-transform shadow-[2px_2px_0px_#000]">
                            <i class="fas fa-times text-[10px]"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($rooms)): ?>
            <div class="col-span-full os-card p-12 bg-white flex flex-col items-center justify-center text-center opacity-50">
                 <i class="fas fa-building text-6xl text-slate-300 mb-4"></i>
                 <h3 class="text-xl font-black uppercase text-slate-400">No Infrastructure</h3>
                 <p class="text-[10px] font-mono uppercase text-slate-400">Initialize sector assets to begin</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
