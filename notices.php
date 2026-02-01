<?php
/**
 * Bulletin Intelligence - Campus Signal Feed
 * ACADEMIX - Premium Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('student');

$user_id = get_current_user_id();

// Protocol Identification
$stmt = $db->prepare("SELECT department_id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$dept_id = $student['department_id'];

$sort = $_GET['sort'] ?? 'date_desc';
$notice_id = $_GET['id'] ?? null;
$single_notice = null;

if ($notice_id) {
    // Single Bulletin Retrieval
    $stmt = $db->prepare("SELECT n.*, u.username as posted_by 
        FROM notices n 
        JOIN users u ON n.created_by = u.id 
        WHERE n.id = ? AND n.status = 'published' 
        AND (n.target_audience = 'all' OR (n.target_audience = 'students' AND (n.department_id IS NULL OR n.department_id = ?)))");
    $stmt->bind_param("ii", $notice_id, $dept_id);
    $stmt->execute();
    $single_notice = $stmt->get_result()->fetch_assoc();
    
    if ($single_notice) {
        // Log Access
        $stmt = $db->prepare("INSERT INTO notice_interactions (user_id, notice_id, is_read, read_at) VALUES (?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE is_read = 1, read_at = NOW()");
        $stmt->bind_param("ii", $user_id, $notice_id);
        $stmt->execute();
    }
}

$notices = [];
if (!$single_notice) {
    // Dispatch Queue Retrieval
    $order_by = match($sort) {
        'date_asc' => "n.created_at ASC",
        'title_asc' => "n.title ASC",
        'creator' => "u.username ASC, n.created_at DESC",
        default => "n.created_at DESC"
    };

    $query = "SELECT n.*, u.username as posted_by FROM notices n JOIN users u ON n.created_by = u.id WHERE n.status = 'published' AND (n.target_audience = 'all' OR (n.target_audience = 'students' AND (n.department_id IS NULL OR n.department_id = $dept_id))) AND (n.publish_date <= NOW() OR n.publish_date IS NULL) AND (n.expiry_date >= NOW() OR n.expiry_date IS NULL) ORDER BY $order_by";
    $result = $db->query($query);
    while ($row = $result->fetch_assoc()) $notices[] = $row;
}

$page_title = $single_notice ? 'Notice Details' : 'Notices';

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
            <span class="px-2 py-1 bg-yellow-400 text-black text-[10px] font-black uppercase tracking-widest border border-black">Intelligence</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-yellow-400 inline-block mr-1"></span>
                Official Broadcasts
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">Academic <span class="text-yellow-400">Notices</span></h1>
    </div>
    
    <div class="relative z-10">
        <?php if ($single_notice): ?>
            <a href="notices.php" class="btn-os bg-white text-black border-black hover:bg-black hover:text-white flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back to Feed
            </a>
        <?php else: ?>
            <div class="relative">
                <select onchange="window.location.href='?sort=' + this.value" class="w-full md:w-64 px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none cursor-pointer">
                    <option value="date_desc" <?php echo $sort === 'date_desc' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="date_asc" <?php echo $sort === 'date_asc' ? 'selected' : ''; ?>>Chronological</option>
                    <option value="title_asc" <?php echo $sort === 'title_asc' ? 'selected' : ''; ?>>Alphabetical</option>
                    <option value="creator" <?php echo $sort === 'creator' ? 'selected' : ''; ?>>By Publisher</option>
                </select>
                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                    <i class="fas fa-chevron-down text-black text-xs"></i>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($single_notice): ?>
    <!-- Bulletin Detail View -->
    <div class="max-w-4xl mx-auto">
        <div class="os-card p-0 bg-white shadow-2xl overflow-hidden">
            <div class="p-8 md:p-12 border-b-2 border-black">
                <!-- Header Component -->
                <div class="mb-8 text-center space-y-4">
                    <?php 
                        $priorityClass = match($single_notice['priority']) {
                            'urgent' => 'bg-red-600 text-white',
                            'high' => 'bg-yellow-400 text-black',
                            default => 'bg-blue-600 text-white',
                        };
                    ?>
                    <span class="inline-block px-3 py-1 <?php echo $priorityClass; ?> text-[9px] font-black uppercase tracking-widest border border-black shadow-[2px_2px_0px_#000000]"><?php echo $single_notice['priority']; ?> Priority</span>
                    
                    <h2 class="text-3xl md:text-5xl font-black text-black uppercase tracking-tighter leading-none"><?php echo e($single_notice['title']); ?></h2>
                    
                    <div class="flex items-center justify-center gap-4 text-[10px] font-black text-slate-500 uppercase tracking-widest pt-4">
                        <span class="flex items-center gap-2"><i class="fas fa-calendar-day text-black"></i> <?php echo date('F d, Y', strtotime($single_notice['created_at'])); ?></span>
                        <span class="w-1 h-1 bg-black"></span>
                        <span class="flex items-center gap-2"><i class="fas fa-user-shield text-black"></i> Posted by <?php echo e($single_notice['posted_by']); ?></span>
                    </div>
                </div>
                
                <div class="bg-slate-50 p-8 border-2 border-black shadow-[4px_4px_0px_#000000]">
                    <p class="text-sm md:text-base font-mono text-slate-800 leading-relaxed whitespace-pre-line">
                        <?php echo e($single_notice['content']); ?>
                    </p>
                </div>
                
                <?php if (isset($single_notice['attachment_path']) && $single_notice['attachment_path']): ?>
                    <div class="mt-8 p-6 bg-black text-white flex flex-col sm:flex-row items-center justify-between gap-6 border-2 border-black">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white text-black flex items-center justify-center border-2 border-white">
                                <i class="fas fa-file-pdf text-2xl"></i>
                            </div>
                            <div>
                                <h4 class="font-black text-lg uppercase tracking-tight">Attachment</h4>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Download official document</p>
                            </div>
                        </div>
                        <a href="<?php echo BASE_URL . '/uploads/notices/' . e($single_notice['attachment_path']); ?>" class="w-full sm:w-auto btn-os bg-white text-black border-white hover:bg-yellow-400 hover:text-black hover:border-white text-center">
                            Download File
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="bg-slate-100 p-4 text-center border-t-2 border-black">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                    ID: <?php echo str_pad($single_notice['id'], 6, '0', STR_PAD_LEFT); ?> • TYPE: <?php echo strtoupper($single_notice['notice_type'] ?? 'GENERAL'); ?> • E: <?php echo strtoupper(bin2hex(random_bytes(4))); ?>
                </p>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Bulletin Dispatch List -->
    <div class="grid grid-cols-1 gap-6">
        <?php if (empty($notices)): ?>
            <div class="os-card p-20 text-center bg-white border-2 border-dashed border-black">
                <i class="fas fa-bullhorn text-4xl text-slate-300 mb-4 block"></i>
                <h3 class="text-xl font-black text-black uppercase tracking-tighter mb-2">No Notices</h3>
                <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">There are no notices to display at this time.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notices as $notice): 
                $priorityColor = match($notice['priority']) {
                    'urgent' => 'bg-red-600 text-white',
                    'high' => 'bg-yellow-400 text-black',
                    default => 'bg-blue-600 text-white',
                };
            ?>
                <a href="?id=<?php echo $notice['id']; ?>" class="block group">
                    <div class="os-card p-0 flex flex-col md:flex-row bg-white hover:-translate-y-1 hover:shadow-[6px_6px_0px_#000000] transition-all duration-300 overflow-hidden">
                        <!-- Temporal Marker -->
                        <div class="w-full md:w-32 bg-black text-white p-6 flex flex-col items-center justify-center border-b-2 md:border-b-0 md:border-r-2 border-black group-hover:bg-yellow-400 group-hover:text-black transition-colors">
                            <span class="text-xs font-black uppercase tracking-widest mb-1"><?php echo date('M', strtotime($notice['created_at'])); ?></span>
                            <span class="text-4xl font-black leading-none"><?php echo date('d', strtotime($notice['created_at'])); ?></span>
                        </div>

                        <!-- Briefing Stream -->
                        <div class="p-6 md:p-8 flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-3 mb-3">
                                <span class="px-2 py-0.5 <?php echo $priorityColor; ?> text-[8px] font-black uppercase tracking-widest border border-black shadow-[2px_2px_0px_#000000]">
                                    <?php echo $notice['priority']; ?> Priority
                                </span>
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Posted by <?php echo e($notice['posted_by']); ?></span>
                            </div>
                            <h3 class="text-2xl font-black text-black group-hover:text-blue-600 transition-colors uppercase leading-tight truncate mb-2"><?php echo e($notice['title']); ?></h3>
                            <p class="text-xs font-mono text-slate-600 line-clamp-2 leading-relaxed">
                                <?php echo strip_tags($notice['content']); ?>
                            </p>
                            
                            <div class="mt-4 pt-4 border-t-2 border-slate-100 flex justify-end">
                                <span class="text-[10px] font-black text-black uppercase tracking-widest flex items-center gap-1 group-hover:gap-2 transition-all">
                                    Read Briefing <i class="fas fa-arrow-right"></i>
                                </span>
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
