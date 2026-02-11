<?php
/**
 * Department Notices Management
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('admin');

$page_title = 'Notices';
$user_id = get_current_user_id();

// Get Admin Department
$stmt = $db->prepare("SELECT department_id FROM department_admins WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$admin_dept = $stmt->get_result()->fetch_assoc();
$dept_id = $admin_dept['department_id'];

// Handle Actions
$action = $_GET['action'] ?? 'list';
$notice_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request');
    } else {
        $post_action = $_POST['action'] ?? '';
        
        if ($post_action === 'create' || $post_action === 'update') {
            $title = sanitize_input($_POST['title']);
            $content = sanitize_input($_POST['content']);
            $target_audience = sanitize_input($_POST['target_audience']);
            $priority = sanitize_input($_POST['priority']);
            $status = sanitize_input($_POST['status']);
            $publish_date = !empty($_POST['publish_date']) ? $_POST['publish_date'] : null;
            $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
            
            // Validate Target Audience
            $allowed_targets = ['students', 'teachers', 'department'];
            if (!in_array($target_audience, $allowed_targets)) {
                set_flash('error', 'Invalid target audience');
            } else {
                if ($post_action === 'create') {
                     $stmt = $db->prepare("INSERT INTO notices (title, content, target_audience, department_id, priority, status, publish_date, expiry_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                     $stmt->bind_param("sssissssi", $title, $content, $target_audience, $dept_id, $priority, $status, $publish_date, $expiry_date, $user_id);
                     if ($stmt->execute()) {
                         create_audit_log('create_notice', 'notices', $stmt->insert_id, null, ['title' => $title]);
                         set_flash('success', 'Notice created successfully');
                     } else {
                         set_flash('error', 'Database error');
                     }
                } else {
                    $id = intval($_POST['id']);
                    // Check ownership/department
                    $chk = $db->query("SELECT id FROM notices WHERE id = $id AND department_id = $dept_id");
                    if ($chk->num_rows > 0) {
                        $stmt = $db->prepare("UPDATE notices SET title = ?, content = ?, target_audience = ?, priority = ?, status = ?, publish_date = ?, expiry_date = ? WHERE id = ?");
                        $stmt->bind_param("sssssssi", $title, $content, $target_audience, $priority, $status, $publish_date, $expiry_date, $id);
                        if ($stmt->execute()) {
                            create_audit_log('update_notice', 'notices', $id, null, ['title' => $title]);
                            set_flash('success', 'Notice updated successfully');
                        }
                    } else {
                         set_flash('error', 'Access denied');
                    }
                }
                redirect(BASE_URL . '/modules/admin/notices.php');
            }
        } elseif ($post_action === 'delete') {
            $id = intval($_POST['id']);
            $chk = $db->query("SELECT id FROM notices WHERE id = $id AND department_id = $dept_id");
            if ($chk->num_rows > 0) {
                $db->query("DELETE FROM notices WHERE id = $id");
                create_audit_log('delete_notice', 'notices', $id);
                set_flash('success', 'Notice deleted');
            } else {
                set_flash('error', 'Access denied');
            }
             redirect(BASE_URL . '/modules/admin/notices.php');
        }
    }
}

// Check for single notice view
$single_notice = null;
if ($action === 'view' && $notice_id) {
    $stmt = $db->prepare("SELECT n.*, u.username as posted_by 
        FROM notices n 
        LEFT JOIN users u ON n.created_by = u.id 
        WHERE n.id = ? AND n.department_id = ?");
    $stmt->bind_param("ii", $notice_id, $dept_id);
    $stmt->execute();
    $single_notice = $stmt->get_result()->fetch_assoc();
}

// List Data
$filter_status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'date_desc';

$where = "n.department_id = $dept_id";
if ($filter_status) $where .= " AND n.status = '" . $db->real_escape_string($filter_status) . "'";

// Determine sort order
$order_by = "n.created_at DESC"; // default
switch ($sort) {
    case 'date_asc': $order_by = "n.created_at ASC"; break;
    case 'date_desc': $order_by = "n.created_at DESC"; break;
    case 'title_asc': $order_by = "n.title ASC"; break;
    case 'creator': $order_by = "u.username ASC, n.created_at DESC"; break;
}

$notices = [];
if ($action === 'list') {
    $query = "SELECT n.*, u.username as posted_by 
        FROM notices n 
        LEFT JOIN users u ON n.created_by = u.id 
        WHERE $where 
        ORDER BY $order_by";
    $result = $db->query($query);
    while ($row = $result->fetch_assoc()) $notices[] = $row;
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
            <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Management</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                Admin Control
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">Department <span class="text-black">Notices</span></h1>
    </div>
    
    <div class="relative z-10">
        <button onclick="openCreateModal()" class="btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black hover:border-black flex items-center gap-2">
            <i class="fas fa-plus"></i> Create Notice
        </button>
    </div>
</div>

<?php if ($single_notice): ?>
    <div class="mb-8">
        <a href="?" class="btn-os bg-white text-black border-black hover:bg-black hover:text-white flex items-center gap-2 w-fit">
            <i class="fas fa-arrow-left"></i> Back to Notices
        </a>
    </div>

    <div class="os-card p-0 bg-white shadow-2xl overflow-hidden">
        <div class="bg-black p-8 text-white relative border-b-2 border-black">
            <div class="flex flex-wrap items-center gap-4 mb-4">
                <span class="px-2 py-1 bg-white text-black border border-white text-[9px] font-black uppercase tracking-widest">Target: <?php echo ucfirst($single_notice['target_audience']); ?></span>
                <span class="px-2 py-1 bg-transparent border border-white text-white text-[9px] font-black uppercase tracking-widest">
                    <?php echo ucfirst($single_notice['priority']); ?> Priority
                </span>
                <span class="px-2 py-1 bg-transparent border border-white text-white text-[9px] font-black uppercase tracking-widest">
                    STATUS: <?php echo strtoupper($single_notice['status']); ?>
                </span>
            </div>
            <h1 class="text-3xl md:text-5xl font-black uppercase tracking-tighter leading-none mb-4"><?php echo e($single_notice['title']); ?></h1>
            <div class="flex flex-wrap items-center gap-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                <span class="flex items-center gap-2"><i class="fas fa-calendar-alt text-yellow-400"></i> <?php echo date('M d, Y', strtotime($single_notice['created_at'])); ?></span>
                <span class="flex items-center gap-2"><i class="fas fa-id-badge text-yellow-400"></i> Posted By: <?php echo e(ucfirst($single_notice['posted_by'])); ?></span>
            </div>
        </div>
        
        <div class="p-8 md:p-12">
            <div class="font-mono text-base font-bold text-slate-800 leading-relaxed mb-10 whitespace-pre-line border-l-4 border-black pl-6">
                <?php echo e($single_notice['content']); ?>
            </div>
            
            <div class="pt-8 border-t-2 border-black flex flex-wrap gap-4">
                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($single_notice)); ?>)" class="btn-os bg-black text-white border-black hover:bg-white hover:text-black flex items-center gap-2">
                    <i class="fas fa-edit"></i> Edit Notice
                </button>
                <form method="POST" onsubmit="return confirm('Delete this notice?');" class="inline">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $single_notice['id']; ?>">
                    <button type="submit" class="btn-os bg-white text-black border-black hover:bg-red-600 hover:text-white flex items-center gap-2">
                        <i class="fas fa-trash-alt"></i> Delete Notice
                    </button>
                </form>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div>
            <label class="block text-[10px] font-black text-black uppercase tracking-widest italic mb-2">Inventory Filter</label>
            <div class="relative">
                <select id="statusFilter" onchange="window.location.href='?status=' + this.value" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none cursor-pointer">
                    <option value="">All Statuses</option>
                    <option value="published" <?php echo $filter_status === 'published' ? 'selected' : ''; ?>>Published</option>
                    <option value="draft" <?php echo $filter_status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="archived" <?php echo $filter_status === 'archived' ? 'selected' : ''; ?>>Archived</option>
                </select>
                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                    <i class="fas fa-chevron-down text-black text-xs"></i>
                </div>
            </div>
        </div>
        <div>
            <label class="block text-[10px] font-black text-black uppercase tracking-widest italic mb-2">Sorting</label>
            <div class="relative">
                <select id="sortSelect" onchange="window.location.href='?sort=' + this.value + '&status=<?php echo $filter_status; ?>'" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none cursor-pointer">
                    <option value="date_desc" <?php echo ($sort ?? 'date_desc') === 'date_desc' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="date_asc" <?php echo ($sort ?? '') === 'date_asc' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="title_asc" <?php echo ($sort ?? '') === 'title_asc' ? 'selected' : ''; ?>>Alphabetical</option>
                    <option value="creator" <?php echo ($sort ?? '') === 'creator' ? 'selected' : ''; ?>>By Creator</option>
                </select>
                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                    <i class="fas fa-chevron-down text-black text-xs"></i>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($notices)): ?>
        <div class="os-card p-20 text-center bg-white border-2 border-dashed border-black">
            <i class="fas fa-broadcast-tower text-4xl text-slate-300 mb-4 block"></i>
            <h3 class="text-xl font-black text-black uppercase tracking-tighter mb-2">No Notices Found</h3>
            <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">Adjust filters to see results.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <?php foreach ($notices as $notice): ?>
                <a href="?action=view&id=<?php echo $notice['id']; ?>" class="block group">
                    <div class="os-card p-0 bg-white hover:-translate-y-1 hover:shadow-[6px_6px_0px_#000000] transition-all duration-300 overflow-hidden flex flex-col h-full bg-slate-50">
                        <div class="bg-white p-6 border-b-2 border-black flex justify-between items-start">
                            <div class="flex gap-2">
                                <span class="px-2 py-1 bg-black text-white text-[8px] font-black uppercase tracking-widest border border-black">
                                    <?php echo ucfirst($notice['priority']); ?> Priority
                                </span>
                                <span class="px-2 py-1 bg-white text-black text-[8px] font-black uppercase tracking-widest border border-black">
                                    <?php echo strtoupper($notice['status']); ?>
                                </span>
                            </div>
                            <div class="w-8 h-8 flex items-center justify-center border-2 border-black bg-white group-hover:bg-yellow-400 transition-colors">
                                <i class="fas fa-eye text-xs"></i>
                            </div>
                        </div>
                        
                        <div class="p-6 flex-1 flex flex-col">
                            <h3 class="text-xl font-black text-black uppercase tracking-tighter leading-tight mb-2 group-hover:text-blue-600 transition-colors"><?php echo e($notice['title']); ?></h3>
                            
                            <div class="flex flex-wrap items-center gap-4 text-[9px] font-black uppercase tracking-widest text-slate-500 mb-4">
                                <span><?php echo date('M d, Y', strtotime($notice['created_at'])); ?></span>
                                <span>By: <?php echo e(ucfirst($notice['posted_by'])); ?></span>
                            </div>
                            
                            <p class="text-xs font-mono text-slate-600 line-clamp-2 mb-4">
                                <?php echo e(strip_tags($notice['content'])); ?>
                            </p>
                            
                            <div class="mt-auto pt-4 border-t-2 border-black border-dashed flex justify-end">
                                <span class="text-[10px] font-black text-black uppercase tracking-widest flex items-center gap-1 group-hover:gap-2 transition-all">
                                    Full Report <i class="fas fa-arrow-right"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Notice Modal (Create/Edit) -->
<div id="noticeModal" class="fixed inset-0 z-[10000] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="relative w-full max-w-lg os-card p-0 bg-white shadow-2xl animate-in zoom-in-95 duration-200">
            <div class="bg-black p-6 text-white border-b-2 border-black flex justify-between items-center">
                <div>
                    <h3 id="modalTitle" class="text-xl font-black uppercase italic tracking-tighter">Create Notice</h3>
                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mt-1">Broadcasting Protocol</p>
                </div>
                <button onclick="closeModal()" class="text-white hover:text-red-500 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="p-8 max-h-[80vh] overflow-y-auto">
                <form method="POST" id="noticeForm" class="space-y-6">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="noticeId" value="">
                    
                    <div>
                        <label class="block text-[10px] font-black text-black uppercase tracking-widest italic mb-2">Notice Title</label>
                        <input type="text" name="title" id="title" placeholder="ENTER TITLE..." class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" required>
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-black text-black uppercase tracking-widest italic mb-2">Content</label>
                        <textarea name="content" id="content" rows="4" placeholder="ENTER DETAILS..." class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all font-mono placeholder-slate-400" required></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-black uppercase tracking-widest italic mb-2">Target Audience</label>
                            <div class="relative">
                                <select name="target_audience" id="target_audience" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none cursor-pointer" required>
                                    <option value="department">Everyone</option>
                                    <option value="students">Students</option>
                                    <option value="teachers">Teachers</option>
                                </select>
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                    <i class="fas fa-chevron-down text-black text-xs"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-[10px] font-black text-black uppercase tracking-widest italic mb-2">Priority</label>
                            <div class="relative">
                                <select name="priority" id="priority" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none cursor-pointer">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                    <i class="fas fa-chevron-down text-black text-xs"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-black uppercase tracking-widest italic mb-2">Publish Date</label>
                            <input type="datetime-local" name="publish_date" id="publish_date" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400">
                        </div>
                        
                        <div>
                            <label class="block text-[10px] font-black text-black uppercase tracking-widest italic mb-2">Expiry Date</label>
                            <input type="datetime-local" name="expiry_date" id="expiry_date" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-black text-black uppercase tracking-widest italic mb-2">Status</label>
                        <div class="flex gap-4">
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="status" value="published" id="statusPublished" checked class="hidden peer">
                                <div class="peer-checked:bg-black peer-checked:text-white bg-white text-slate-400 border-2 border-slate-200 peer-checked:border-black px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest transition-all hover:border-black">
                                    PUBLISH
                                </div>
                            </label>
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="status" value="draft" id="statusDraft" class="hidden peer">
                                <div class="peer-checked:bg-black peer-checked:text-white bg-white text-slate-400 border-2 border-slate-200 peer-checked:border-black px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest transition-all hover:border-black">
                                    DRAFT
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex gap-4 pt-6 border-t-2 border-black border-dashed">
                        <button type="button" onclick="closeModal()" class="flex-1 btn-os bg-white text-black border-black hover:bg-black hover:text-white text-center">Cancel</button>
                        <button type="submit" class="flex-1 btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black">
                            <i class="fas fa-save mr-2"></i> Save Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('modalTitle').innerText = 'Create Notice';
    document.getElementById('formAction').value = 'create';
    document.getElementById('noticeId').value = '';
    document.getElementById('noticeForm').reset();
    document.getElementById('noticeModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function openEditModal(notice) {
    document.getElementById('modalTitle').innerText = 'Edit Notice';
    document.getElementById('formAction').value = 'update';
    document.getElementById('noticeId').value = notice.id;
    document.getElementById('title').value = notice.title;
    document.getElementById('content').value = notice.content;
    document.getElementById('target_audience').value = notice.target_audience;
    document.getElementById('priority').value = notice.priority;
    
    if(notice.publish_date) {
        document.getElementById('publish_date').value = notice.publish_date.replace(' ', 'T').substring(0, 16);
    } else {
        document.getElementById('publish_date').value = '';
    }
    if(notice.expiry_date) {
        document.getElementById('expiry_date').value = notice.expiry_date.replace(' ', 'T').substring(0, 16);
    } else {
        document.getElementById('expiry_date').value = '';
    }
    
    if(notice.status === 'draft') {
        document.getElementById('statusDraft').checked = true;
    } else {
        document.getElementById('statusPublished').checked = true;
    }
    
    document.getElementById('noticeModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('noticeModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
