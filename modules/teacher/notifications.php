<?php
/**
 * Teacher Notifications
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('teacher');

$page_title = 'My Notifications';
$user_id = get_current_user_id();

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request');
        redirect(BASE_URL . '/modules/teacher/notifications.php');
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
    redirect(BASE_URL . '/modules/teacher/notifications.php');
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

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-4xl font-black uppercase tracking-tighter border-b-4 border-black inline-block pb-2 mb-2">
                Notifications
            </h1>
            <p class="text-sm font-bold uppercase tracking-widest text-gray-600">
                System Alerts & Updates
            </p>
        </div>
        
        <?php if (!empty($notifications) && $filter !== 'read'): ?>
            <form method="POST">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="btn-os bg-white hover:bg-black hover:text-white">
                    <i class="fas fa-check-double mr-2"></i> Mark All Read
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Filter Bar -->
    <div class="flex items-center gap-2 border-b-2 border-black pb-4">
        <a href="?filter=all" class="px-4 py-2 text-[10px] font-black uppercase tracking-widest border-2 border-black <?php echo $filter === 'all' ? 'bg-black text-white' : 'bg-white hover:bg-gray-100'; ?>">
            All
        </a>
        <a href="?filter=unread" class="px-4 py-2 text-[10px] font-black uppercase tracking-widest border-2 border-black <?php echo $filter === 'unread' ? 'bg-black text-white' : 'bg-white hover:bg-gray-100'; ?>">
            Unread
        </a>
        <a href="?filter=read" class="px-4 py-2 text-[10px] font-black uppercase tracking-widest border-2 border-black <?php echo $filter === 'read' ? 'bg-black text-white' : 'bg-white hover:bg-gray-100'; ?>">
            Read
        </a>
    </div>

    <!-- Notifications List -->
    <div class="space-y-4">
        <?php if (empty($notifications)): ?>
            <div class="os-card p-12 text-center bg-gray-50 border-dashed">
                <div class="w-16 h-16 bg-white border-2 border-black flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-bell-slash text-2xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-black uppercase tracking-tight text-gray-500">No Notifications</h3>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">
                    <?php echo $filter === 'unread' ? "You're all caught up!" : "Nothing to report."; ?>
                </p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif): 
                $icon = match($notif['type']) {
                    'alert' => ['class' => 'fa-exclamation-triangle', 'bg' => 'bg-red-500', 'text' => 'text-white'],
                    'success' => ['class' => 'fa-check-circle', 'bg' => 'bg-emerald-500', 'text' => 'text-white'],
                    'info' => ['class' => 'fa-info-circle', 'bg' => 'bg-blue-500', 'text' => 'text-white'],
                    default => ['class' => 'fa-bell', 'bg' => 'bg-black', 'text' => 'text-white']
                };
                $opacity = $notif['is_read'] ? 'opacity-60 bg-gray-50' : 'bg-white shadow-os';
            ?>
                <div class="os-card p-6 flex flex-col md:flex-row gap-6 <?php echo $opacity; ?> transition-all duration-300">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 border-2 border-black flex items-center justify-center text-lg <?php echo $icon['bg'] . ' ' . $icon['text']; ?>">
                            <i class="fas <?php echo $icon['class']; ?>"></i>
                        </div>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 mb-2">
                            <h3 class="text-lg font-black uppercase tracking-tight leading-none <?php echo $notif['is_read'] ? 'text-gray-600' : 'text-black'; ?>">
                                <?php echo e($notif['title']); ?>
                                <?php if (!$notif['is_read']): ?>
                                    <span class="inline-block ml-2 w-2 h-2 bg-red-600 rounded-full animate-pulse"></span>
                                <?php endif; ?>
                            </h3>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                <?php echo $notif['time_ago']; ?>
                            </span>
                        </div>
                        
                        <p class="text-sm font-bold text-gray-600 mb-4 leading-relaxed font-mono">
                            <?php echo nl2br(e($notif['message'])); ?>
                        </p>
                        
                        <div class="flex items-center gap-4 border-t-2 border-gray-100 pt-4">
                            <?php if (!$notif['is_read']): ?>
                                <form method="POST" class="inline">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="id" value="<?php echo $notif['id']; ?>">
                                    <button type="submit" class="text-[10px] font-black uppercase tracking-widest text-black hover:text-emerald-600 flex items-center gap-1 group">
                                        <i class="fas fa-check group-hover:scale-110 transition-transform"></i> Mark Read
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <form method="POST" class="inline" onsubmit="return confirm('Delete this notification?');">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $notif['id']; ?>">
                                <button type="submit" class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-red-600 flex items-center gap-1 group">
                                    <i class="fas fa-trash group-hover:scale-110 transition-transform"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
