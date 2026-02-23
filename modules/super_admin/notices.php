<?php
/**
 * Notices Management
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('super_admin');

$page_title = 'Notices Management';
$action = $_GET['action'] ?? 'list';
$notice_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request');
        redirect(BASE_URL . '/modules/super_admin/notices.php');
    }
    
    $post_action = $_POST['action'] ?? '';
    
    if ($post_action === 'create' || $post_action === 'update') {
        $title = sanitize_input($_POST['title'] ?? '');
        $content = sanitize_input($_POST['content'] ?? '');
        $target_audience = sanitize_input($_POST['target_audience'] ?? 'all');
        $department_id = $target_audience === 'department' ? intval($_POST['department_id']) : null;
        $priority = sanitize_input($_POST['priority'] ?? 'medium');
        $status = sanitize_input($_POST['status'] ?? 'draft');
        $publish_date = !empty($_POST['publish_date']) ? sanitize_input($_POST['publish_date']) : null;
        $expiry_date = !empty($_POST['expiry_date']) ? sanitize_input($_POST['expiry_date']) : null;
        $created_by = get_current_user_id();
        
        $errors = validate_required(['title', 'content'], $_POST);
        
        if (empty($errors)) {
            if ($post_action === 'create') {
                $stmt = $db->prepare("INSERT INTO notices (title, content, target_audience, department_id, priority, status, publish_date, expiry_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssi", $title, $content, $target_audience, $department_id, $priority, $status, $publish_date, $expiry_date, $created_by);
                
                if ($stmt->execute()) {
                    create_audit_log('create_notice', 'notices', $stmt->insert_id, null, ['title' => $title]);
                    set_flash('success', 'Notice created successfully');
                    redirect(BASE_URL . '/modules/super_admin/notices.php');
                } else {
                    set_flash('error', 'Failed to create notice');
                }
            } else {
                $id = intval($_POST['id']);
                $stmt = $db->prepare("UPDATE notices SET title = ?, content = ?, target_audience = ?, department_id = ?, priority = ?, status = ?, publish_date = ?, expiry_date = ? WHERE id = ?");
                $stmt->bind_param("ssssssssi", $title, $content, $target_audience, $department_id, $priority, $status, $publish_date, $expiry_date, $id);
                
                if ($stmt->execute()) {
                    create_audit_log('update_notice', 'notices', $id, null, ['title' => $title]);
                    set_flash('success', 'Notice updated successfully');
                    redirect(BASE_URL . '/modules/super_admin/notices.php');
                } else {
                    set_flash('error', 'Failed to update notice');
                }
            }
        } else {
            set_flash('error', implode(', ', $errors));
        }
    }
    
    elseif ($post_action === 'delete') {
        $id = intval($_POST['id']);
        
        $stmt = $db->prepare("DELETE FROM notices WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            create_audit_log('delete_notice', 'notices', $id);
            set_flash('success', 'Notice deleted successfully');
        } else {
            set_flash('error', 'Failed to delete notice');
        }
        redirect(BASE_URL . '/modules/super_admin/notices.php');
    }
}

// Get notice for edit
$notice = null;
if ($action === 'edit' && $notice_id) {
    $stmt = $db->prepare("SELECT * FROM notices WHERE id = ?");
    $stmt->bind_param("i", $notice_id);
    $stmt->execute();
    $notice = $stmt->get_result()->fetch_assoc();
}

// Get all departments for dropdown
$departments = [];
$result = $db->query("SELECT id, name, code FROM departments WHERE deleted_at IS NULL ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}

// Get all notices
$status_filter = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'date_desc';

$where = "1=1";
if ($status_filter) {
    $where .= " AND n.status = '" . $db->real_escape_string($status_filter) . "'";
}

// Determine sort order
$order_by = "n.created_at DESC"; // default
switch ($sort) {
    case 'date_asc':
        $order_by = "n.created_at ASC";
        break;
    case 'date_desc':
        $order_by = "n.created_at DESC";
        break;
    case 'title_asc':
        $order_by = "n.title ASC";
        break;
    case 'creator':
        $order_by = "u.username ASC, n.created_at DESC";
        break;
}

$notices = [];
$result = $db->query("SELECT n.*, d.name as department_name, u.username as created_by_name 
    FROM notices n 
    LEFT JOIN departments d ON n.department_id = d.id 
    LEFT JOIN users u ON n.created_by = u.id 
    WHERE $where 
    ORDER BY $order_by");
while ($row = $result->fetch_assoc()) {
    $notices[] = $row;
}

// Sidebar menu
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

// Page content
ob_start();
?>

<!-- Notices Header -->
<div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white mb-6">
    <div class="relative z-10">
        <div class="flex items-center gap-3 mb-2">
            <span class="px-2 py-1 bg-yellow-400 text-black text-[10px] font-black uppercase tracking-widest border border-black">Broadcast</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-yellow-500 inline-block mr-1"></span>
                Communications Hub
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">
            <?php echo $action === 'create' ? 'Initialize <span class="text-yellow-600">Notice</span>' : ($action === 'edit' ? 'Modify <span class="text-yellow-600">Notice</span>' : 'System <span class="text-yellow-600">Notices</span>'); ?>
        </h1>
    </div>
    
    <div class="flex items-center gap-4 relative z-10">
        <?php if ($action === 'list'): ?>
            <div class="hidden md:flex items-center gap-2">
                <div class="relative">
                    <select id="sortSelect" onchange="updateSort()" class="appearance-none bg-white border-2 border-black px-4 py-2 pr-8 text-[10px] font-black uppercase tracking-widest focus:outline-none focus:bg-yellow-50 w-48">
                        <option value="date_desc" <?php echo ($sort ?? 'date_desc') === 'date_desc' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="date_asc" <?php echo ($sort ?? '') === 'date_asc' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="title_asc" <?php echo ($sort ?? '') === 'title_asc' ? 'selected' : ''; ?>>Title A-Z</option>
                        <option value="creator" <?php echo ($sort ?? '') === 'creator' ? 'selected' : ''; ?>>By Author</option>
                    </select>
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                        <i class="fas fa-chevron-down text-xs text-black"></i>
                    </div>
                </div>
            </div>
            <a href="?action=create" class="btn-os bg-yellow-400 text-black border-black hover:bg-black hover:text-white hover:border-black flex items-center gap-2">
                <i class="fas fa-plus"></i>
                <span class="hidden sm:inline">Create Notice</span>
            </a>
        <?php else: ?>
            <a href="?" class="btn-os bg-white text-black border-black hover:bg-black hover:text-white hover:border-black flex items-center gap-2">
                <i class="fas fa-arrow-left"></i>
                <span class="hidden sm:inline">Back to List</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<script>
function updateSort() {
    const sort = document.getElementById('sortSelect').value;
    const url = new URL(window.location.href);
    url.searchParams.set('sort', sort);
    window.location.href = url.toString();
}
</script>

<?php if ($action === 'create' || $action === 'edit'): ?>
    <!-- Create/Edit Form -->
    <div class="os-card p-0 overflow-hidden bg-white max-w-4xl mx-auto">
        <div class="bg-black p-4 text-white border-b-2 border-black flex justify-between items-center">
            <h4 class="text-sm font-black uppercase tracking-widest text-white">Notice Configuration</h4>
            <i class="fas fa-pen-nib text-white/20"></i>
        </div>
        
        <form method="POST" action="" class="p-6 md:p-8 space-y-6">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="<?php echo $action === 'edit' ? 'update' : 'create'; ?>">
            <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo e($notice['id']); ?>">
            <?php endif; ?>
            
            <div class="space-y-6">
                <!-- Title -->
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Subject Line <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="<?php echo e($notice['title'] ?? ''); ?>" 
                        class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" 
                        placeholder="ENTER NOTICE TITLE HERE..." required>
                </div>
                
                <!-- Content -->
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Message Body <span class="text-red-500">*</span></label>
                    <textarea name="content" rows="8" 
                        class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all placeholder-slate-400 font-mono" 
                        placeholder="Type notice content..." required><?php echo e($notice['content'] ?? ''); ?></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Target Audience -->
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Target Sector</label>
                        <div class="relative">
                            <select name="target_audience" id="target_audience" 
                                class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none"
                                onchange="toggleDepartmentField()">
                                <option value="all" <?php echo ($notice['target_audience'] ?? 'all') === 'all' ? 'selected' : ''; ?>>All Sectors</option>
                                <option value="students" <?php echo ($notice['target_audience'] ?? '') === 'students' ? 'selected' : ''; ?>>Students Only</option>
                                <option value="teachers" <?php echo ($notice['target_audience'] ?? '') === 'teachers' ? 'selected' : ''; ?>>Faculty Only</option>
                                <option value="admins" <?php echo ($notice['target_audience'] ?? '') === 'admins' ? 'selected' : ''; ?>>Admins Only</option>
                                <option value="department" <?php echo ($notice['target_audience'] ?? '') === 'department' ? 'selected' : ''; ?>>Specific Department</option>
                            </select>
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                <i class="fas fa-chevron-down text-black text-xs"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Department (Conditional) -->
                    <div id="department_field" class="space-y-2" style="display: <?php echo ($notice['target_audience'] ?? '') === 'department' ? 'block' : 'none'; ?>;">
                        <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Department</label>
                        <div class="relative">
                            <select name="department_id" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none">
                                <option value="">Select Unit</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo ($notice['department_id'] ?? '') == $dept['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                <i class="fas fa-chevron-down text-black text-xs"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Priority -->
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Priority Level</label>
                        <div class="relative">
                            <select name="priority" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none">
                                <option value="low" <?php echo ($notice['priority'] ?? '') === 'low' ? 'selected' : ''; ?>>Low Priority</option>
                                <option value="medium" <?php echo ($notice['priority'] ?? 'medium') === 'medium' ? 'selected' : ''; ?>>Standard</option>
                                <option value="high" <?php echo ($notice['priority'] ?? '') === 'high' ? 'selected' : ''; ?>>High Importance</option>
                                <option value="urgent" <?php echo ($notice['priority'] ?? '') === 'urgent' ? 'selected' : ''; ?>>Critical / Urgent</option>
                            </select>
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                <i class="fas fa-chevron-down text-black text-xs"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status -->
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">State</label>
                        <div class="relative">
                            <select name="status" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none">
                                <option value="draft" <?php echo ($notice['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Draft Mode</option>
                                <option value="published" <?php echo ($notice['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Live Broadcast</option>
                                <option value="archived" <?php echo ($notice['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                <i class="fas fa-chevron-down text-black text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Publish Date -->
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Launch Time</label>
                        <input type="datetime-local" name="publish_date" value="<?php echo $notice['publish_date'] ? date('Y-m-d\TH:i', strtotime($notice['publish_date'])) : ''; ?>" 
                            class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase font-mono">
                    </div>
                </div>
                
                <!-- Expiry Date (Optional) -->
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Termination Time (Optional)</label>
                    <input type="datetime-local" name="expiry_date" value="<?php echo $notice['expiry_date'] ? date('Y-m-d\TH:i', strtotime($notice['expiry_date'])) : ''; ?>" 
                         class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase font-mono">
                </div>
            </div>
            
            <div class="pt-6 border-t-2 border-black flex flex-col md:flex-row gap-4 justify-end">
                <a href="?" class="btn-os bg-white text-black border-black hover:bg-red-600 hover:text-white hover:border-black text-center">
                    Cancel Operation
                </a>
                <button type="submit" class="btn-os bg-blue-600 text-white border-black hover:bg-black hover:text-white">
                    <i class="fas fa-save mr-2"></i> <?php echo $action === 'edit' ? 'Update' : 'Initialize'; ?> Broadcast
                </button>
            </div>
        </form>
    </div>
    
    <script>
    function toggleDepartmentField() {
        const target = document.getElementById('target_audience').value;
        const deptField = document.getElementById('department_field');
        deptField.style.display = target === 'department' ? 'block' : 'none';
    }
    document.addEventListener('DOMContentLoaded', toggleDepartmentField);
    </script>

<?php elseif ($action === 'view' && $notice_id): ?>
    <?php
    $stmt = $db->prepare("SELECT n.*, d.name as department_name, u.username as created_by_name 
        FROM notices n 
        LEFT JOIN departments d ON n.department_id = d.id 
        LEFT JOIN users u ON n.created_by = u.id 
        WHERE n.id = ?");
    $stmt->bind_param("i", $notice_id);
    $stmt->execute();
    $view_notice = $stmt->get_result()->fetch_assoc();
    ?>
    
    <?php if (!$view_notice): ?>
        <div class="os-card p-12 text-center bg-white">
            <i class="fas fa-link-slash text-4xl text-slate-300 mb-4"></i>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Broadcast Signal Terminated or Invalid</p>
        </div>
    <?php else: ?>
        <div class="os-card p-0 overflow-hidden bg-white max-w-4xl mx-auto">
            <div class="bg-black p-8 text-white relative overflow-hidden border-b-2 border-black">
                <div class="flex items-center gap-4 mb-4">
                    <span class="px-2 py-1 bg-yellow-400 text-black text-[10px] font-black uppercase tracking-widest border border-black">
                        <?php echo e($view_notice['priority']); ?> Priority
                    </span>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest font-mono">
                        ID: #<?php echo str_pad($view_notice['id'], 6, '0', STR_PAD_LEFT); ?>
                    </span>
                </div>
                
                <h1 class="text-3xl md:text-5xl font-black uppercase tracking-tighter leading-none mb-6">
                    <?php echo e($view_notice['title']); ?>
                </h1>
                
                <div class="flex flex-wrap gap-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-user text-yellow-500"></i>
                        <span><?php echo e($view_notice['created_by_name']); ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-calendar text-yellow-500"></i>
                        <span><?php echo date('d M Y, H:i', strtotime($view_notice['created_at'])); ?></span>
                    </div>
                    <?php if ($view_notice['department_name']): ?>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-building text-yellow-500"></i>
                            <span><?php echo e($view_notice['department_name']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="p-8 md:p-12 bg-white">
                <div class="prose max-w-none mb-12 font-mono text-sm leading-relaxed">
                    <?php echo nl2br(e($view_notice['content'])); ?>
                </div>
                
                <div class="flex flex-col md:flex-row gap-4 pt-8 border-t-2 border-black">
                    <a href="?action=edit&id=<?php echo $view_notice['id']; ?>" class="btn-os bg-blue-600 text-white border-black hover:bg-black hover:text-white flex-1 text-center">
                        <i class="fas fa-pen mr-2"></i> Modify Signal
                    </a>
                    <form method="POST" onsubmit="return confirm('DANGER: Purge this signal? This cannot be undone.')" class="flex-1">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $view_notice['id']; ?>">
                        <button type="submit" class="btn-os bg-red-600 text-white border-black hover:bg-black hover:text-white w-full">
                            <i class="fas fa-trash mr-2"></i> Purge Signal
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- Filter Bar -->
    <div class="os-card p-6 bg-white mb-8">
        <form method="GET" class="flex flex-col md:flex-row items-end gap-4">
            <div class="w-full md:w-auto flex-grow space-y-2">
                <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Filter by State</label>
                <div class="relative">
                    <select name="status" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none">
                        <option value="">Full Spectrum</option>
                        <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Drafts</option>
                        <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="archived" <?php echo $status_filter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                     <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                        <i class="fas fa-chevron-down text-black text-xs"></i>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black hover:border-black w-full md:w-auto">
                <i class="fas fa-filter mr-2"></i> Apply Filter
            </button>
            
            <?php if ($status_filter): ?>
                <a href="?" class="btn-os bg-slate-100 text-black border-black hover:bg-red-600 hover:text-white hover:border-black w-full md:w-auto text-center">
                    Clear
                </a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Notices Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($notices)): ?>
            <div class="col-span-full py-20 text-center bg-white border-2 border-dashed border-black">
                <i class="fas fa-satellite-dish text-4xl text-slate-300 mb-4 animate-pulse"></i>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No Broadcast Signals Detected</p>
            </div>
        <?php else: ?>
            <?php foreach ($notices as $n): ?>
                <div class="os-card p-0 flex flex-col bg-white hover:-translate-y-1 hover:shadow-[6px_6px_0px_#000000] transition-all duration-300 group cursor-pointer" onclick="if(!event.target.closest('a') && !event.target.closest('button')) window.location.href='?action=view&id=<?php echo $n['id']; ?>'">
                    <div class="p-6 flex-grow">
                        <div class="flex justify-between items-start mb-4">
                            <span class="px-2 py-1 bg-black text-white text-[9px] font-black uppercase tracking-widest">
                                #<?php echo $n['id']; ?>
                            </span>
                            <div class="flex gap-2">
                                <span class="px-2 py-1 border-2 border-black text-[8px] font-black uppercase tracking-widest <?php 
                                    echo $n['priority'] === 'urgent' ? 'bg-red-600 text-white' : 
                                        ($n['priority'] === 'high' ? 'bg-orange-500 text-white' : 'bg-blue-100 text-black'); 
                                ?>">
                                    <?php echo $n['priority']; ?>
                                </span>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-black uppercase leading-tight mb-3 line-clamp-2 group-hover:text-blue-600 transition-colors">
                            <?php echo e($n['title']); ?>
                        </h3>
                        <p class="text-xs font-mono text-slate-600 line-clamp-3 mb-4">
                            <?php echo e($n['content']); ?>
                        </p>
                    </div>
                    
                    <div class="px-6 py-4 border-t-2 border-black bg-slate-50 mt-auto">
                        <div class="flex justify-between items-center mb-4">
                            <div class="flex flex-col">
                                <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Target</span>
                                <span class="text-[10px] font-black text-black uppercase"><?php echo e($n['target_audience']); ?></span>
                            </div>
                            <div class="flex flex-col text-right">
                                <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Date</span>
                                <span class="text-[10px] font-black text-black uppercase"><?php echo time_ago($n['created_at']); ?></span>
                            </div>
                        </div>
                        
                        <div class="flex gap-2">
                            <a href="?action=view&id=<?php echo $n['id']; ?>" class="flex-1 py-2 text-center bg-white border-2 border-black text-[10px] font-black uppercase hover:bg-black hover:text-white transition-all">
                                View
                            </a>
                            <a href="?action=edit&id=<?php echo $n['id']; ?>" class="w-10 flex items-center justify-center bg-white border-2 border-black text-black hover:bg-blue-600 hover:text-white transition-all">
                                <i class="fas fa-pen text-xs"></i>
                            </a>
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
