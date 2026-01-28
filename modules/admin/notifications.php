<?php
/**
 * Admin Notifications
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('admin');

$page_title = 'Notifications';
$user_id = get_current_user_id();

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request');
        redirect(BASE_URL . '/modules/admin/notifications.php');
    }
    
    if ($_POST['action'] === 'mark_read' && isset($_POST['id'])) {
        $notif_id = intval($_POST['id']);
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notif_id, $user_id);
        $stmt->execute();
    } elseif ($_POST['action'] === 'mark_all_read') {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $notif_id = intval($_POST['id']);
        $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notif_id, $user_id);
        $stmt->execute();
    }
    redirect(BASE_URL . '/modules/admin/notifications.php');
}

// Filter
$filter = $_GET['filter'] ?? 'all';

// Get notifications
$where = "user_id = ?";
if ($filter === 'unread') {
    $where .= " AND is_read = 0";
} elseif ($filter === 'read') {
    $where .= " AND is_read = 1";
}

$notifications = [];
$stmt = $db->prepare("SELECT * FROM notifications WHERE $where ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['time_ago'] = time_ago($row['created_at']);
    $notifications[] = $row;
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
            <span class="px-2 py-1 bg-red-600 text-white text-[10px] font-black uppercase tracking-widest border border-black">Alert Stream</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-red-600 inline-block mr-1"></span>
                Live Sync
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">Intelligence <span class="text-red-600">Feed</span></h1>
    </div>
    
    <div class="relative z-10">
        <?php if (!empty($notifications)): ?>
            <form method="POST">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="btn-os bg-black text-white border-black hover:bg-green-600 hover:text-white hover:border-black flex items-center gap-2">
                    <i class="fas fa-check-double"></i> Purge Unread
                </button>
            </form>
        <?php else: ?>
            <div class="w-12 h-12 bg-white flex items-center justify-center border-2 border-black shadow-[4px_4px_0px_#000000]">
                <i class="fas fa-bell text-xl text-black"></i>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Interface Filter -->
<div class="flex gap-2 mb-8 overflow-x-auto pb-2">
    <a href="?" class="btn-os <?php echo $filter === 'all' ? 'bg-black text-white border-black' : 'bg-white text-black border-black hover:bg-slate-100'; ?> text-[10px]">All Signals</a>
    <a href="?filter=unread" class="btn-os <?php echo $filter === 'unread' ? 'bg-black text-white border-black' : 'bg-white text-black border-black hover:bg-slate-100'; ?> text-[10px]">Pending Only</a>
    <a href="?filter=read" class="btn-os <?php echo $filter === 'read' ? 'bg-black text-white border-black' : 'bg-white text-black border-black hover:bg-slate-100'; ?> text-[10px]">Archived Logs</a>
</div>

<!-- Signals Feed -->
<?php if (empty($notifications)): ?>
    <div class="os-card p-20 text-center bg-white border-2 border-dashed border-black">
        <i class="fas fa-bell-slash text-4xl text-slate-300 mb-4 block"></i>
        <h3 class="text-xl font-black text-black uppercase tracking-tighter mb-2">Atmosphere Clear</h3>
        <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">
            <?php echo $filter === 'unread' ? "Zero pending signals in immediate queue." : "Command center feed currently dormant."; ?>
        </p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 gap-6">
        <?php foreach ($notifications as $notif): ?>
            <div class="os-card p-0 bg-white hover:-translate-y-1 hover:shadow-[6px_6px_0px_#000000] transition-all duration-300 flex flex-col md:flex-row items-stretch <?php echo $notif['is_read'] ? 'opacity-70' : ''; ?>">
                <div class="md:w-24 flex items-center justify-center p-6 border-b-2 md:border-b-0 md:border-r-2 border-black <?php 
                    echo $notif['type'] === 'alert' ? 'bg-red-600 text-white' : 
                        ($notif['type'] === 'success' ? 'bg-green-600 text-white' : 
                        ($notif['type'] === 'info' ? 'bg-blue-600 text-white' : 'bg-black text-white')); 
                ?>">
                    <i class="fas fa-<?php 
                        echo $notif['type'] === 'alert' ? 'bolt' : 
                            ($notif['type'] === 'success' ? 'check' : 
                            ($notif['type'] === 'info' ? 'info' : 'bell')); 
                    ?> text-2xl"></i>
                </div>
                
                <div class="flex-1 p-6 md:p-8">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-3">
                        <div class="flex items-center gap-3">
                            <h3 class="text-xl font-black text-black uppercase tracking-tighter leading-none group-hover:text-blue-600 transition-colors">
                                <?php echo e($notif['title']); ?>
                            </h3>
                            <?php if (!$notif['is_read']): ?>
                                <span class="bg-red-600 text-white text-[8px] font-black uppercase tracking-widest px-2 py-0.5 border border-black animate-pulse">New</span>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest font-mono flex items-center gap-1">
                                <i class="far fa-clock"></i> <?php echo strtoupper($notif['time_ago']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <p class="text-sm font-mono text-slate-800 leading-relaxed mb-6 whitespace-pre-line">
                        <?php echo e($notif['message']); ?>
                    </p>
                    
                    <div class="flex items-center gap-4 pt-4 border-t-2 border-slate-100">
                        <?php if (!$notif['is_read']): ?>
                            <form method="POST">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="mark_read">
                                <input type="hidden" name="id" value="<?php echo $notif['id']; ?>">
                                <button type="submit" class="text-[9px] font-black uppercase tracking-widest text-black hover:text-green-600 transition-colors flex items-center gap-2 hover:underline decoration-2 underline-offset-4">
                                    <i class="fas fa-check"></i> Mark as Finalized
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <form method="POST" onsubmit="return confirm('Initiate signal purge protocol?');">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $notif['id']; ?>">
                            <button type="submit" class="text-[9px] font-black uppercase tracking-widest text-red-600 hover:text-black transition-colors flex items-center gap-2 hover:underline decoration-2 underline-offset-4">
                                <i class="fas fa-trash-alt"></i> Purge Signal
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
