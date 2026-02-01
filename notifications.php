<?php
/**
 * Inbound Intelligence - Signal Matrix
 * ACADEMIX - Premium Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('student');

$user_id = get_current_user_id();

// Protocol: Intelligence Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Security Error: Invalid Request');
        redirect(BASE_URL . '/modules/student/notifications.php');
    }
    
    if ($_POST['action'] === 'mark_read' && isset($_POST['id'])) {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $_POST['id'], $user_id);
        $stmt->execute();
    } elseif ($_POST['action'] === 'mark_all_read') {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $_POST['id'], $user_id);
        $stmt->execute();
    }
    redirect(BASE_URL . '/modules/student/notifications.php');
}

$notif_id = $_GET['id'] ?? null;
$single_notif = null;

if ($notif_id) {
    $stmt = $db->prepare("SELECT * FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notif_id, $user_id);
    $stmt->execute();
    $single_notif = $stmt->get_result()->fetch_assoc();
    if ($single_notif) {
        $single_notif['time_ago'] = time_ago($single_notif['created_at']);
        if (!$single_notif['is_read']) {
            $db->query("UPDATE notifications SET is_read = 1 WHERE id = $notif_id");
        }
    }
}

$filter = $_GET['filter'] ?? 'all';
$notifications = [];
if (!$single_notif) {
    $where = "user_id = ?";
    if ($filter === 'unread') $where .= " AND is_read = 0";
    elseif ($filter === 'read') $where .= " AND is_read = 1";

    $stmt = $db->prepare("SELECT * FROM notifications WHERE $where ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $row['time_ago'] = time_ago($row['created_at']);
        $notifications[] = $row;
    }
}

$page_title = $single_notif ? 'Notification Details' : 'Notifications';

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
            <span class="px-2 py-1 bg-red-600 text-white text-[10px] font-black uppercase tracking-widest border border-black">Signals</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-red-600 inline-block mr-1"></span>
                Inbound Matrix
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">Recent <span class="text-red-600">Notifications</span></h1>
    </div>
    
    <div class="relative z-10">
        <?php if ($single_notif): ?>
            <a href="notifications.php" class="btn-os bg-white text-black border-black hover:bg-black hover:text-white flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back to Feed
            </a>
        <?php else: ?>
             <?php if (!empty($notifications)): ?>
                <form method="POST" class="relative z-10">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="mark_all_read">
                    <button type="submit" class="btn-os bg-black text-white border-black hover:bg-green-600 hover:text-white hover:border-black flex items-center gap-2">
                        <i class="fas fa-check-double"></i> Mark All Read
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($single_notif): ?>
    <!-- Transmission Detail View -->
    <div class="max-w-4xl mx-auto">
        <div class="os-card p-0 bg-white">
            <div class="p-8 md:p-12 border-b-2 border-black">
                <div class="flex flex-col md:flex-row items-start gap-8 mb-8">
                    <?php 
                        $typeMeta = match($single_notif['type']) {
                            'alert' => ['icon' => 'bolt', 'class' => 'bg-red-600 text-white'],
                            'success' => ['icon' => 'check-double', 'class' => 'bg-green-600 text-white'],
                            'info' => ['icon' => 'info', 'class' => 'bg-blue-600 text-white'],
                            default => ['icon' => 'bell', 'class' => 'bg-black text-white'],
                        };
                    ?>
                    <div class="w-20 h-20 <?php echo $typeMeta['class']; ?> flex items-center justify-center border-2 border-black shadow-[4px_4px_0px_#000000]">
                        <i class="fas fa-<?php echo $typeMeta['icon']; ?> text-3xl"></i>
                    </div>
                    <div class="flex-1 space-y-2">
                        <div class="flex items-center gap-4">
                            <span class="inline-block px-2 py-1 bg-black text-white text-[9px] font-black uppercase tracking-widest border border-black"><?php echo $single_notif['type']; ?> CLASS</span>
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest font-mono"><?php echo $single_notif['time_ago']; ?></span>
                        </div>
                        <h2 class="text-3xl font-black text-black uppercase tracking-tighter leading-none"><?php echo e($single_notif['title']); ?></h2>
                    </div>
                </div>
                
                <div class="bg-slate-50 p-8 border-2 border-black shadow-[4px_4px_0px_#000000]">
                    <p class="text-sm md:text-lg font-mono text-slate-800 leading-relaxed whitespace-pre-line">
                        <?php echo e($single_notif['message']); ?>
                    </p>
                </div>
                
                <div class="mt-8 pt-6 border-t-2 border-slate-100 flex flex-col md:flex-row items-center justify-between gap-4">
                    <form method="POST" onsubmit="return confirm('Delete this notification?');">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $single_notif['id']; ?>">
                        <button type="submit" class="text-red-600 hover:text-black font-black text-[10px] uppercase tracking-widest hover:underline decoration-2 underline-offset-4">
                            <i class="fas fa-trash-alt mr-2"></i> Delete Record
                        </button>
                    </form>
                    <p class="text-[9px] font-black text-slate-300 uppercase tracking-[0.3em]">System generated</p>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Management Matrix Feed -->
    <div class="flex gap-2 mb-8 overflow-x-auto pb-2">
        <a href="?" class="btn-os <?php echo $filter === 'all' ? 'bg-black text-white border-black' : 'bg-white text-black border-black hover:bg-slate-100'; ?> text-[10px]">All</a>
        <a href="?filter=unread" class="btn-os <?php echo $filter === 'unread' ? 'bg-black text-white border-black' : 'bg-white text-black border-black hover:bg-slate-100'; ?> text-[10px]">Unread</a>
        <a href="?filter=read" class="btn-os <?php echo $filter === 'read' ? 'bg-black text-white border-black' : 'bg-white text-black border-black hover:bg-slate-100'; ?> text-[10px]">Read</a>
    </div>

    <!-- Signal Stream -->
    <div class="grid grid-cols-1 gap-6">
        <?php if (empty($notifications)): ?>
            <div class="os-card p-20 text-center bg-white border-2 border-dashed border-black">
                <i class="fas fa-satellite text-4xl text-slate-300 mb-4 block"></i>
                <h3 class="text-xl font-black text-black uppercase tracking-tighter mb-2">No Signals</h3>
                <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">You have no notifications yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif): 
                $isRead = $notif['is_read'];
                $typeColor = match($notif['type']) {
                    'alert' => 'bg-red-600 border-red-600 text-white',
                    'success' => 'bg-green-600 border-green-600 text-white',
                    'info' => 'bg-blue-600 border-blue-600 text-white',
                    default => 'bg-black border-black text-white',
                };
            ?>
                <a href="?id=<?php echo $notif['id']; ?>" class="block group">
                    <div class="os-card p-0 flex flex-col md:flex-row bg-white hover:-translate-y-1 hover:shadow-[6px_6px_0px_#000000] transition-all duration-300 overflow-hidden <?php echo $isRead ? 'opacity-70' : ''; ?>">
                        <!-- Signal Origin Node -->
                        <div class="w-full md:w-24 <?php echo $typeColor; ?> p-6 flex flex-col items-center justify-center border-b-2 md:border-b-0 md:border-r-2 border-black">
                            <i class="fas fa-<?php echo match($notif['type']) { 'alert'=>'bolt','success'=>'check-double','info'=>'info',default=>'bell' }; ?> text-2xl"></i>
                        </div>

                        <!-- Payload Summary -->
                        <div class="p-6 md:p-8 flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-3 mb-2">
                                <h3 class="text-xl font-black text-black group-hover:text-blue-600 transition-colors uppercase leading-none truncate"><?php echo e($notif['title']); ?></h3>
                                <?php if (!$isRead): ?>
                                    <span class="inline-block px-2 py-0.5 bg-red-600 text-white text-[8px] font-black uppercase tracking-widest border border-black animate-pulse">New</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs font-mono text-slate-600 mb-4 h-10 line-clamp-2 leading-relaxed">
                                <?php echo strip_tags($notif['message']); ?>
                            </p>
                            <div class="flex items-center justify-between border-t-2 border-slate-100 pt-3">
                                <div class="flex items-center gap-4">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest font-mono"><?php echo $notif['time_ago']; ?></span>
                                    <span class="text-[9px] font-black text-black uppercase tracking-widest"><?php echo strtoupper($notif['type']); ?></span>
                                </div>
                                <span class="text-[10px] font-black text-black group-hover:translate-x-1 transition-transform uppercase tracking-widest flex items-center gap-1">View <i class="fas fa-arrow-right text-[8px]"></i></span>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
