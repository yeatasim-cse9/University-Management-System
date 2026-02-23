<?php
/**
 * Teacher Notices
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('teacher');

$page_title = 'Notices';
$user_id = get_current_user_id();

// Get Teacher Department
$stmt = $db->prepare("SELECT department_id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$dept_id = $teacher['department_id'];

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
            $allowed_targets = ['all', 'students', 'teachers', 'department'];
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
                    // Check ownership
                    $chk = $db->query("SELECT id FROM notices WHERE id = $id AND created_by = $user_id");
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
                redirect(BASE_URL . '/modules/teacher/notices.php');
            }
        } elseif ($post_action === 'delete') {
            $id = intval($_POST['id']);
            $chk = $db->query("SELECT id FROM notices WHERE id = $id AND created_by = $user_id");
            if ($chk->num_rows > 0) {
                $db->query("DELETE FROM notices WHERE id = $id");
                create_audit_log('delete_notice', 'notices', $id);
                set_flash('success', 'Notice deleted');
            } else {
                set_flash('error', 'Access denied');
            }
             redirect(BASE_URL . '/modules/teacher/notices.php');
        }
    }
}

// Get Data for Edit
$edit_notice = null;
if ($action === 'edit' && $notice_id) {
    $stmt = $db->prepare("SELECT * FROM notices WHERE id = ? AND created_by = ?");
    $stmt->bind_param("ii", $notice_id, $user_id);
    $stmt->execute();
    $edit_notice = $stmt->get_result()->fetch_assoc();
    if (!$edit_notice) {
        set_flash('error', 'Notice not found or access denied');
        redirect('notices.php');
    }
}

// Sidebar
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<div class="space-y-6">
    
    <?php if ($action === 'create' || $action === 'edit'): ?>
        <!-- Create/Edit Form -->
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                 <div>
                    <h1 class="text-3xl font-black uppercase tracking-tighter border-b-4 border-black inline-block pb-2 mb-2">
                        <?php echo $action === 'create' ? 'Post New Notice' : 'Edit Notice'; ?>
                    </h1>
                    <p class="text-sm font-bold uppercase tracking-widest text-gray-600">
                        Broadcast information
                    </p>
                </div>
                <a href="notices.php" class="btn-os bg-white hover:bg-black hover:text-white">
                    <i class="fas fa-arrow-left mr-2"></i> Cancel
                </a>
            </div>

            <div class="os-card p-8 bg-white">
                <form method="POST" class="space-y-6">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="<?php echo $action === 'create' ? 'create' : 'update'; ?>">
                    <?php if ($edit_notice): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_notice['id']; ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                             <label class="block text-xs font-black uppercase tracking-widest mb-2">Notice Title</label>
                             <input type="text" name="title" value="<?php echo $edit_notice['title'] ?? ''; ?>" placeholder="ENTER TITLE..." class="w-full bg-white border-2 border-black p-4 text-sm font-bold uppercase focus:ring-0 focus:border-black focus:shadow-os transition-all" required>
                        </div>

                        <div class="md:col-span-2">
                             <label class="block text-xs font-black uppercase tracking-widest mb-2">Content</label>
                             <textarea name="content" rows="6" placeholder="TYPE CONTENT HERE..." class="w-full bg-white border-2 border-black p-4 text-sm font-bold uppercase focus:ring-0 focus:border-black focus:shadow-os transition-all" required><?php echo $edit_notice['content'] ?? ''; ?></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest mb-2">Target Audience</label>
                            <div class="relative">
                                <select name="target_audience" class="w-full bg-white border-2 border-black p-4 text-sm font-bold uppercase focus:ring-0 focus:border-black focus:shadow-os transition-all appearance-none cursor-pointer" required>
                                    <?php
                                    $targets = ['all' => 'Everyone', 'students' => 'Students', 'teachers' => 'Teachers', 'department' => 'My Department'];
                                    foreach ($targets as $val => $label) {
                                        $sel = ($edit_notice['target_audience'] ?? '') === $val ? 'selected' : '';
                                        echo "<option value='$val' $sel>$label</option>";
                                    }
                                    ?>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-black">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest mb-2">Priority</label>
                            <div class="relative">
                                <select name="priority" class="w-full bg-white border-2 border-black p-4 text-sm font-bold uppercase focus:ring-0 focus:border-black focus:shadow-os transition-all appearance-none cursor-pointer">
                                    <?php
                                    $priorities = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];
                                    foreach ($priorities as $val => $label) {
                                        $sel = ($edit_notice['priority'] ?? 'medium') === $val ? 'selected' : '';
                                        echo "<option value='$val' $sel>$label</option>";
                                    }
                                    ?>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-black">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>

                         <div>
                            <label class="block text-xs font-black uppercase tracking-widest mb-2">Publish Date (Optional)</label>
                            <input type="datetime-local" name="publish_date" value="<?php echo isset($edit_notice['publish_date']) ? date('Y-m-d\TH:i', strtotime($edit_notice['publish_date'])) : ''; ?>" class="w-full bg-white border-2 border-black p-4 text-sm font-bold uppercase focus:ring-0 focus:border-black focus:shadow-os transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest mb-2">Expiry Date (Optional)</label>
                            <input type="datetime-local" name="expiry_date" value="<?php echo isset($edit_notice['expiry_date']) ? date('Y-m-d\TH:i', strtotime($edit_notice['expiry_date'])) : ''; ?>" class="w-full bg-white border-2 border-black p-4 text-sm font-bold uppercase focus:ring-0 focus:border-black focus:shadow-os transition-all">
                        </div>
                        
                         <div class="md:col-span-2">
                            <label class="block text-xs font-black uppercase tracking-widest mb-2">Status</label>
                            <div class="flex gap-4">
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="status" value="published" <?php echo ($edit_notice['status'] ?? 'published') === 'published' ? 'checked' : ''; ?> class="peer hidden">
                                    <div class="p-4 border-2 border-black text-center text-xs font-black uppercase tracking-widest peer-checked:bg-black peer-checked:text-white hover:bg-gray-100 transition-all">
                                        Publish Immediately
                                    </div>
                                </label>
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="status" value="draft" <?php echo ($edit_notice['status'] ?? '') === 'draft' ? 'checked' : ''; ?> class="peer hidden">
                                    <div class="p-4 border-2 border-black text-center text-xs font-black uppercase tracking-widest peer-checked:bg-gray-400 peer-checked:text-white hover:bg-gray-100 transition-all">
                                        Save as Draft
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 border-t-2 border-gray-100">
                        <button type="submit" class="w-full py-4 bg-black text-white text-xs font-black uppercase tracking-widest hover:bg-yellow-400 hover:text-black transition-colors shadow-os border-2 border-black">
                            <?php echo $action === 'create' ? 'Post Notice' : 'Update Notice'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif ($action === 'view' && $notice_id): 
        // Single Notice View
         $stmt = $db->prepare("SELECT n.*, u.username as posted_by 
            FROM notices n 
            JOIN users u ON n.created_by = u.id 
            WHERE n.id = ?");
        $stmt->bind_param("i", $notice_id);
        $stmt->execute();
        $single_notice = $stmt->get_result()->fetch_assoc();
        
        if (!$single_notice) {
            echo "<div class='p-4 bg-red-100 text-red-700 border-2 border-red-700 font-bold uppercase'>Notice not found.</div>";
        } else {
             $priority_colors = [
                'urgent' => 'bg-red-500 text-white',
                'high' => 'bg-orange-500 text-white',
                'medium' => 'bg-black text-white',
                'low' => 'bg-gray-400 text-white'
            ];
            $p_color = $priority_colors[$single_notice['priority']] ?? 'bg-black text-white';
    ?>
         <div class="max-w-4xl mx-auto">
            <div class="mb-6">
                <a href="notices.php" class="btn-os inline-flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>

            <div class="os-card p-0 bg-white overflow-hidden">
                <div class="p-8 border-b-4 border-black bg-yellow-400">
                    <div class="flex flex-wrap items-center gap-3 mb-4">
                        <span class="px-2 py-1 text-[10px] font-black uppercase tracking-widest border-2 border-black <?php echo $p_color; ?>">
                            <?php echo strtoupper($single_notice['priority']); ?> Priority
                        </span>
                        <span class="px-2 py-1 text-[10px] font-black uppercase tracking-widest border-2 border-black bg-white text-black">
                            Target: <?php echo strtoupper($single_notice['target_audience']); ?>
                        </span>
                         <span class="px-2 py-1 text-[10px] font-black uppercase tracking-widest border-2 border-black bg-white text-black">
                            Status: <?php echo strtoupper($single_notice['status']); ?>
                        </span>
                    </div>
                    <h1 class="text-4xl font-black uppercase tracking-tighter leading-none mb-2">
                        <?php echo e($single_notice['title']); ?>
                    </h1>
                     <div class="flex items-center gap-4 text-xs font-bold uppercase">
                        <span class="flex items-center gap-1"><i class="fas fa-user-circle"></i> <?php echo e($single_notice['posted_by']); ?></span>
                        <span class="flex items-center gap-1"><i class="fas fa-clock"></i> <?php echo date('M d, Y h:i A', strtotime($single_notice['created_at'])); ?></span>
                    </div>
                </div>

                <div class="p-10 text-lg font-medium leading-relaxed font-mono">
                    <?php echo nl2br(e($single_notice['content'])); ?>
                </div>

                <?php if ($single_notice['created_by'] == $user_id): ?>
                    <div class="p-6 border-t-2 border-black bg-gray-50 flex justify-end gap-4">
                         <a href="?action=edit&id=<?php echo $single_notice['id']; ?>" class="btn-os bg-white hover:bg-black hover:text-white">
                            <i class="fas fa-edit mr-2"></i> Edit
                        </a>
                         <form method="POST" onsubmit="return confirm('Delete this notice?');">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $single_notice['id']; ?>">
                            <button type="submit" class="btn-os bg-red-600 text-white hover:bg-red-700 hover:text-white border-red-800">
                                <i class="fas fa-trash-alt mr-2"></i> Delete
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
         </div>
    <?php } ?>

    <?php else: // LIST VIEW 
        // Check filtering
        $sort = $_GET['sort'] ?? 'date_desc';
        $filter = $_GET['filter'] ?? 'all';
        
        $where_clause = "(n.status = 'published' AND (
            n.target_audience = 'all' 
            OR (n.target_audience = 'teachers' AND (n.department_id IS NULL OR n.department_id = $dept_id))
            OR (n.target_audience = 'department' AND n.department_id = $dept_id)
        ) AND (n.publish_date IS NULL OR n.publish_date <= NOW()) 
        AND (n.expiry_date IS NULL OR n.expiry_date >= NOW())) OR (n.created_by = $user_id)";
        
        if ($filter === 'my') {
            $where_clause = "n.created_by = $user_id";
        }
        
        $order_sql = "n.created_at DESC";
        if ($sort === 'date_asc') $order_sql = "n.created_at ASC";
        if ($sort === 'priority') $order_sql = "FIELD(n.priority, 'urgent', 'high', 'medium', 'low')";

        $query = "SELECT n.*, u.username as posted_by 
            FROM notices n 
            JOIN users u ON n.created_by = u.id 
            WHERE $where_clause
            ORDER BY $order_sql";
            
        $notices = [];
        $res = $db->query($query);
        while ($row = $res->fetch_assoc()) $notices[] = $row;
    ?>
        
        <!-- List Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h1 class="text-4xl font-black uppercase tracking-tighter border-b-4 border-black inline-block pb-2 mb-2">
                    Notices & Updates
                </h1>
                <p class="text-sm font-bold uppercase tracking-widest text-gray-600">
                    Stay informed
                </p>
            </div>
            
            <div class="flex items-center gap-4">
                <a href="?action=create" class="btn-os bg-black text-white hover:bg-yellow-400 hover:text-black">
                    <i class="fas fa-plus mr-2"></i> Post Notice
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-4 bg-white border-2 border-black p-4 shadow-os">
            <span class="text-xs font-black uppercase tracking-widest mr-2">filters:</span>
            <a href="?filter=all" class="px-4 py-2 text-[10px] font-black uppercase tracking-widest border-2 border-black <?php echo $filter === 'all' ? 'bg-black text-white' : 'bg-white hover:bg-gray-100'; ?>">All</a>
            <a href="?filter=my" class="px-4 py-2 text-[10px] font-black uppercase tracking-widest border-2 border-black <?php echo $filter === 'my' ? 'bg-black text-white' : 'bg-white hover:bg-gray-100'; ?>">My Posts</a>
            
            <div class="h-6 w-0.5 bg-gray-300 mx-2"></div>
            
            <select onchange="window.location.href='?filter=<?php echo $filter; ?>&sort='+this.value" class="px-4 py-2 text-[10px] font-black uppercase tracking-widest border-2 border-black bg-white cursor-pointer focus:ring-0">
                <option value="date_desc" <?php echo $sort === 'date_desc' ? 'selected' : ''; ?>>Newest First</option>
                <option value="date_asc" <?php echo $sort === 'date_asc' ? 'selected' : ''; ?>>Oldest First</option>
                <option value="priority" <?php echo $sort === 'priority' ? 'selected' : ''; ?>>By Priority</option>
            </select>
        </div>

        <!-- Notices Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php if (empty($notices)): ?>
                <div class="col-span-full py-16 text-center border-2 border-dashed border-black bg-gray-50">
                    <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                    <p class="text-sm font-black uppercase tracking-widest text-gray-400">No notices found</p>
                </div>
            <?php else: ?>
                <?php foreach ($notices as $n): 
                    $priority_colors = [
                        'urgent' => 'text-red-500 border-red-500',
                        'high' => 'text-orange-500 border-orange-500',
                        'medium' => 'text-black border-black',
                        'low' => 'text-gray-500 border-gray-500'
                    ];
                    $p_class = $priority_colors[$n['priority']] ?? 'text-black border-black';
                    $is_mine = $n['created_by'] == $user_id;
                ?>
                    <div class="os-card p-0 bg-white hover:-translate-y-1 transition-transform duration-200 flex flex-col h-full">
                        <div class="p-6 border-b-2 border-black relative overflow-hidden">
                             <?php if ($is_mine): ?>
                                <div class="absolute top-0 right-0 bg-yellow-400 text-black text-[9px] font-black uppercase px-2 py-1 border-l-2 border-b-2 border-black z-10">
                                    My Post
                                </div>
                            <?php endif; ?>
                            
                            <div class="flex items-center gap-2 mb-3">
                                <span class="px-2 py-0.5 border <?php echo $p_class; ?> text-[9px] font-black uppercase tracking-widest">
                                    <?php echo $n['priority']; ?>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-widest text-gray-400">
                                    <?php echo date('M d', strtotime($n['created_at'])); ?>
                                </span>
                            </div>
                            
                            <h3 class="text-xl font-black uppercase leading-tight line-clamp-2 mb-2 group-hover:underline decoration-2">
                                <a href="?action=view&id=<?php echo $n['id']; ?>"><?php echo e($n['title']); ?></a>
                            </h3>
                            
                            <div class="flex items-center gap-2 text-[10px] font-bold uppercase text-gray-500">
                                <i class="fas fa-user-circle"></i> <?php echo e($n['posted_by']); ?>
                                <span class="mx-1">•</span>
                                <i class="fas fa-eye"></i> <?php echo ucfirst($n['target_audience']); ?>
                            </div>
                        </div>
                        
                        <div class="p-6 flex-1 bg-gray-50">
                            <p class="text-xs font-bold text-gray-600 line-clamp-3 leading-relaxed mb-4">
                                <?php echo e(strip_tags($n['content'])); ?>
                            </p>
                        </div>

                         <a href="?action=view&id=<?php echo $n['id']; ?>" class="block text-center py-3 bg-white border-t-2 border-black text-[10px] font-black uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
                            Read Full Notice <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
