<?php
/**
 * Super Admin Notifications
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('super_admin');

$page_title = 'My Notifications';
$user_id = get_current_user_id();

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request');
        redirect(BASE_URL . '/modules/super_admin/notifications.php');
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
    redirect(BASE_URL . '/modules/super_admin/notifications.php');
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

<!-- Notifications Header -->
<div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white mb-6">
    <div class="relative z-10">
        <div class="flex items-center gap-3 mb-2">
            <span class="px-2 py-1 bg-red-600 text-white text-[10px] font-black uppercase tracking-widest border border-black">System Alert</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-red-500 inline-block mr-1"></span>
                Event Log
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">My <span class="text-red-600">Notifications</span></h1>
    </div>
    
    <div class="flex items-center gap-4 relative z-10">
        <div class="w-10 h-10 bg-black text-white flex items-center justify-center border-2 border-black shadow-[2px_2px_0px_#000000]">
            <i class="fas fa-bell text-lg animate-swing"></i>
        </div>
    </div>
</div>

<!-- Filter and Actions -->
<div class="os-card p-4 mb-8 bg-white flex flex-col md:flex-row items-center justify-between gap-4">
    <div class="flex gap-2 w-full md:w-auto overflow-x-auto pb-2 md:pb-0">
        <a href="?" class="btn-os <?php echo $filter === 'all' ? 'bg-black text-white border-black' : 'bg-white text-black border-black hover:bg-slate-100'; ?> text-[10px]">
            All Events
        </a>
        <a href="?filter=unread" class="btn-os <?php echo $filter === 'unread' ? 'bg-black text-white border-black' : 'bg-white text-black border-black hover:bg-slate-100'; ?> text-[10px]">
            Unread
        </a>
        <a href="?filter=read" class="btn-os <?php echo $filter === 'read' ? 'bg-black text-white border-black' : 'bg-white text-black border-black hover:bg-slate-100'; ?> text-[10px]">
            Archived
        </a>
    </div>
    
    <?php if (!empty($notifications)): ?>
        <form method="POST" class="w-full md:w-auto">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="mark_all_read">
            <button type="submit" class="btn-os bg-green-600 text-white border-black hover:bg-black hover:text-white w-full md:w-auto text-[10px]">
                <i class="fas fa-check-double mr-2"></i>Acknowledge All
            </button>
        </form>
    <?php endif; ?>
</div>

<!-- Notifications List -->
<?php if (empty($notifications)): ?>
    <div class="os-card p-12 text-center bg-white border-2 border-dashed border-black">
        <div class="w-20 h-20 bg-slate-100 mx-auto rounded-none flex items-center justify-center border-2 border-black mb-6">
            <i class="fas fa-bell-slash text-2xl text-slate-300"></i>
        </div>
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
            <?php echo $filter === 'unread' ? "All Clear: No Pending Alerts" : "Event Log Empty"; ?>
        </p>
    </div>
<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($notifications as $notif): ?>
            <?php
            // Determine styles based on notification type
            $icon = 'bell';
            $color_class = 'bg-blue-600';
            $border_class = 'border-black';
            $bg_class = 'bg-white';
            
            if ($notif['type'] === 'alert') {
                $icon = 'exclamation-triangle';
                $color_class = 'bg-red-600';
                $bg_class = 'bg-red-50';
            } elseif ($notif['type'] === 'success') {
                $icon = 'check-circle';
                $color_class = 'bg-green-600';
                $bg_class = 'bg-green-50';
            } elseif ($notif['type'] === 'info') {
                $icon = 'info-circle';
                $color_class = 'bg-blue-600';
                $bg_class = 'bg-blue-50';
            }
            
            $is_unread = !$notif['is_read'];
            ?>
            
            <div class="os-card p-0 flex flex-col md:flex-row bg-white <?php echo $is_unread ? 'border-black shadow-[4px_4px_0px_#000000]' : 'border-slate-300 shadow-none opacity-80 hover:opacity-100'; ?> transition-all duration-300 group">
                <!-- Icon Strip -->
                <div class="w-full md:w-16 <?php echo $color_class; ?> text-white flex items-center justify-center p-4 md:p-0 border-b-2 md:border-b-0 md:border-r-2 border-black">
                    <i class="fas fa-<?php echo $icon; ?> text-xl"></i>
                </div>
                
                <div class="flex-grow p-6">
                    <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-3">
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <span class="text-[8px] font-black uppercase tracking-widest <?php echo str_replace('bg-', 'text-', $color_class); ?>">
                                    <?php echo ucfirst($notif['type']); ?>
                                </span>
                                <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                                <span class="text-[8px] font-black uppercase tracking-widest text-slate-400 font-mono">
                                    <?php echo $notif['time_ago']; ?>
                                </span>
                            </div>
                            <h3 class="text-lg font-black uppercase leading-tight text-black">
                                <?php echo e($notif['title']); ?>
                                <?php if ($is_unread): ?>
                                    <span class="inline-block w-2 h-2 bg-red-500 ml-2 animate-pulse"></span>
                                <?php endif; ?>
                            </h3>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <?php if ($is_unread): ?>
                                <form method="POST" class="inline">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="id" value="<?php echo $notif['id']; ?>">
                                    <button type="submit" class="px-3 py-1 bg-black text-white text-[9px] font-black uppercase tracking-widest border border-black hover:bg-white hover:text-black transition-all">
                                        Read
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <form method="POST" class="inline" onsubmit="return confirm('Purge this entry?');">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $notif['id']; ?>">
                                <button type="submit" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-red-600 transition-all">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <p class="text-xs font-mono text-slate-600 leading-relaxed max-w-4xl">
                        <?php echo nl2br(e($notif['message'])); ?>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
