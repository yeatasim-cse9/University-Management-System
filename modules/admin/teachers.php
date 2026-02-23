<?php
/**
 * Department Teacher Management
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('admin');

$page_title = 'Faculty Management';
$user_id = get_current_user_id();

// Get admin's department(s)
$stmt = $db->prepare("SELECT d.id, d.name FROM departments d JOIN department_admins da ON d.id = da.department_id WHERE da.user_id = ? AND d.deleted_at IS NULL");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$departments = [];
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}
$dept_id_list = !empty($departments) ? implode(',', array_column($departments, 'id')) : '0';

$action = $_GET['action'] ?? 'list';
$t_id = $_GET['id'] ?? null; // user_id

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request');
        redirect(BASE_URL . '/modules/admin/teachers.php');
    }

    $post_action = $_POST['action'] ?? '';

    if ($post_action === 'create' || $post_action === 'update') {
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'] ?? '';
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $department_id = intval($_POST['department_id']);
        $employee_id = sanitize_input($_POST['employee_id']);
        $designation = sanitize_input($_POST['designation']);
        $status = sanitize_input($_POST['status'] ?? 'active');
        $id = isset($_POST['id']) ? intval($_POST['id']) : null;

        if (!in_array($department_id, array_column($departments, 'id'))) {
            set_flash('error', 'Permission denied for this department');
            redirect(BASE_URL . '/modules/admin/teachers.php');
        }

        $errors = validate_required(['username', 'email', 'first_name', 'last_name', 'department_id', 'employee_id'], $_POST);
        if ($post_action === 'create' && empty($password)) $errors[] = "Password is required for new users";

        if (empty($errors)) {
            $db->begin_transaction();
            try {
                if ($post_action === 'create') {
                    // Create User
                    $stmt = $db->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'teacher', ?)");
                    $stmt->bind_param("ssss", $username, $email, $password, $status);
                    if (!$stmt->execute()) throw new Exception('Username or email already exists');
                    $new_user_id = $stmt->insert_id;

                    // Create Profile
                    $stmt = $db->prepare("INSERT INTO user_profiles (user_id, first_name, last_name) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $new_user_id, $first_name, $last_name);
                    $stmt->execute();

                    // Create Teacher Info
                    $stmt = $db->prepare("INSERT INTO teachers (user_id, department_id, employee_id, designation) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiss", $new_user_id, $department_id, $employee_id, $designation);
                    $stmt->execute();

                    create_audit_log('create_teacher', 'users', $new_user_id);
                    set_flash('success', 'Faculty member registered successfully');
                } else {
                    // Security check
                    $check = $db->query("SELECT user_id FROM teachers WHERE user_id = $id AND department_id IN ($dept_id_list)");
                    if ($check->num_rows == 0) throw new Exception('Permission denied');

                    // Update User
                    if (!empty($password)) {
                        $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, password = ?, status = ? WHERE id = ?");
                        $stmt->bind_param("ssssi", $username, $email, $password, $status, $id);
                    } else {
                        $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, status = ? WHERE id = ?");
                        $stmt->bind_param("sssi", $username, $email, $status, $id);
                    }
                    $stmt->execute();

                    // Update Profile
                    $stmt = $db->prepare("UPDATE user_profiles SET first_name = ?, last_name = ? WHERE user_id = ?");
                    $stmt->bind_param("ssi", $first_name, $last_name, $id);
                    $stmt->execute();

                    // Update Teacher Info
                    $stmt = $db->prepare("UPDATE teachers SET department_id = ?, employee_id = ?, designation = ? WHERE user_id = ?");
                    $stmt->bind_param("issi", $department_id, $employee_id, $designation, $id);
                    $stmt->execute();

                    create_audit_log('update_teacher', 'users', $id);
                    set_flash('success', 'Faculty details updated');
                }
                $db->commit();
                redirect(BASE_URL . '/modules/admin/teachers.php');
            } catch (Exception $e) {
                $db->rollback();
                set_flash('error', $e->getMessage());
            }
        } else {
            set_flash('error', implode('<br>', $errors));
        }
    } elseif ($post_action === 'delete') {
        $id = intval($_POST['id']);
        $check = $db->query("SELECT user_id FROM teachers WHERE user_id = $id AND department_id IN ($dept_id_list)");
        if ($check->num_rows > 0) {
            $db->query("UPDATE users SET status = 'inactive' WHERE id = $id"); // Soft delete/Deactivate
            create_audit_log('deactivate_teacher', 'users', $id);
            set_flash('success', 'Faculty member deactivated');
        }
        redirect(BASE_URL . '/modules/admin/teachers.php');
    }
}

// Fetch List Data
$teachers = [];
if ($action === 'list') {
    $search = isset($_GET['search']) ? $db->real_escape_string($_GET['search']) : '';
    $query = "SELECT u.id, u.username, u.email, u.status, up.first_name, up.last_name, t.employee_id, t.designation, d.name as dept_name 
              FROM teachers t 
              JOIN users u ON t.user_id = u.id 
              JOIN user_profiles up ON u.id = up.user_id 
              JOIN departments d ON t.department_id = d.id 
              WHERE t.department_id IN ($dept_id_list)";
    
    if (!empty($search)) {
        $query .= " AND (t.employee_id LIKE '%$search%' OR CONCAT(up.first_name, ' ', up.last_name) LIKE '%$search%' OR u.username LIKE '%$search%' OR u.email LIKE '%$search%')";
    }

    $query .= " ORDER BY up.first_name ASC";
    $res = $db->query($query);
    while ($row = $res->fetch_assoc()) { $teachers[] = $row; }
}

// Fetch Edit/View Data
$view_data = null;
if (($action === 'edit' || $action === 'view') && $t_id) {
    $query = "SELECT u.username, u.email, u.status, u.created_at as account_created, up.first_name, up.last_name, t.*, d.name as dept_name 
              FROM teachers t 
              JOIN users u ON t.user_id = u.id 
              JOIN user_profiles up ON u.id = up.user_id 
              JOIN departments d ON t.department_id = d.id
              WHERE u.id = ? AND t.department_id IN ($dept_id_list)";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $t_id);
    $stmt->execute();
    $view_data = $stmt->get_result()->fetch_assoc();
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
            <span class="px-2 py-1 bg-green-600 text-white text-[10px] font-black uppercase tracking-widest border border-black">Faculty</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-green-600 inline-block mr-1"></span>
                Academic Personnel
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">Faculty <span class="text-green-600">Members</span></h1>
    </div>
    
    <div class="relative z-10 flex flex-col md:flex-row gap-4 items-center">
        <?php if ($action === 'list'): ?>
            <form action="" method="GET" class="flex">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="SEARCH FACULTY..." class="bg-white text-black px-4 py-2 font-bold uppercase text-xs border-2 border-transparent focus:border-green-600 outline-none w-48 md:w-64">
                <button type="submit" class="bg-green-600 px-4 py-2 text-white font-black uppercase text-xs hover:bg-black transition-colors">
                    <i class="fas fa-search"></i>
                </button>
            </form>
            <a href="?action=create" class="btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black hover:border-black flex items-center gap-2">
                <i class="fas fa-user-plus"></i> Recruit Personnel
            </a>
        <?php else: ?>
            <a href="?" class="btn-os bg-white text-black border-black hover:bg-black hover:text-white hover:border-black flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back to Fleet
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($action === 'view' && $view_data): ?>
    <div class="os-card p-0 bg-white overflow-hidden">
        <div class="bg-black p-8 text-white relative border-b-2 border-black">
            <div class="flex flex-col md:flex-row items-center gap-8">
                <div class="w-32 h-32 bg-green-600 rounded-none border-2 border-white text-white flex items-center justify-center relative shadow-[4px_4px_0px_#fff]">
                    <i class="fas fa-chalkboard-teacher text-5xl"></i>
                    <div class="absolute -top-3 -right-3 px-2 py-1 <?php echo $view_data['status'] === 'active' ? 'bg-green-600' : 'bg-red-600'; ?> text-white text-[8px] font-black uppercase tracking-widest border border-black shadow-[2px_2px_0px_#000]">
                        <?php echo $view_data['status']; ?>
                    </div>
                </div>
                <div class="text-center md:text-left">
                    <p class="text-[10px] font-black text-green-400 uppercase tracking-widest mb-1">Faculty Dossier</p>
                    <h2 class="text-4xl md:text-5xl font-black uppercase tracking-tighter leading-none mb-3"><?php echo e($view_data['first_name'] . ' ' . $view_data['last_name']); ?></h2>
                    <div class="flex flex-wrap justify-center md:justify-start gap-2">
                        <span class="px-2 py-1 bg-white text-black text-[9px] font-black uppercase tracking-widest border border-black">EMP ID: <?php echo e($view_data['employee_id']); ?></span>
                        <span class="px-2 py-1 bg-black border border-white text-white text-[9px] font-black uppercase tracking-widest">@<?php echo e($view_data['username']); ?></span>
                        <span class="px-2 py-1 bg-black border border-white text-white text-[9px] font-black uppercase tracking-widest"><?php echo e($view_data['dept_name']); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="p-8 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="space-y-6">
                <div>
                    <h4 class="text-xl font-black uppercase mb-4 border-b-2 border-black inline-block">Professional Status</h4>
                    <div class="bg-slate-50 border-2 border-black p-4 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Designation</span>
                            <span class="text-sm font-black text-black"><?php echo e($view_data['designation'] ?: 'Faculty Member'); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Role Type</span>
                            <span class="text-sm font-black text-black">ACADEMIC</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2 space-y-6">
                <div>
                     <h4 class="text-xl font-black uppercase mb-4 border-b-2 border-black inline-block">Communication Channels</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="os-card p-4 bg-white hover:bg-yellow-50 transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-black text-white flex items-center justify-center border-2 border-black">
                                    <i class="fas fa-envelope text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest">Mail Protocol</p>
                                    <p class="text-sm font-black text-black mt-1"><?php echo e($view_data['email']); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="os-card p-4 bg-white hover:bg-yellow-50 transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-black text-white flex items-center justify-center border-2 border-black">
                                    <i class="fas fa-calendar-check text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest">Commissioned</p>
                                    <p class="text-sm font-black text-black mt-1"><?php echo date('M d, Y', strtotime($view_data['account_created'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-4 pt-4 border-t-2 border-black border-dashed">
                    <a href="?action=edit&id=<?php echo $view_data['user_id']; ?>" class="btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black flex items-center gap-2">
                        <i class="fas fa-edit"></i> Modify Record
                    </a>
                    <button onclick="window.print()" class="btn-os bg-white text-black border-black hover:bg-black hover:text-white flex items-center gap-2">
                        <i class="fas fa-print"></i> Print Log
                    </button>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
    <div class="os-card p-0 bg-white max-w-5xl mx-auto">
        <div class="bg-black p-6 text-white border-b-2 border-black flex items-center gap-4">
            <div class="w-12 h-12 bg-green-600 text-white flex items-center justify-center border-2 border-white">
                <i class="fas <?php echo $action === 'edit' ? 'fa-user-edit' : 'fa-user-plus'; ?> text-xl"></i>
            </div>
            <div>
                <p class="text-[9px] font-black text-green-400 uppercase tracking-widest mb-1">Personnel Configuration</p>
                <h3 class="text-2xl font-black uppercase tracking-tighter leading-none"><?php echo $action === 'edit' ? 'Finalize' : 'Register'; ?> Faculty Member</h3>
            </div>
        </div>

        <form method="POST" class="p-8 space-y-8">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="<?php echo $action; ?>">
            <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo $view_data['user_id']; ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Assignment Sector (Dept)</label>
                    <div class="relative">
                        <select name="department_id" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none cursor-pointer" required>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?php echo $d['id']; ?>" <?php echo ($view_data['department_id'] ?? '') == $d['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($d['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                            <i class="fas fa-chevron-down text-black text-xs"></i>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Employee Protocol ID</label>
                    <input type="text" name="employee_id" value="<?php echo e($view_data['employee_id'] ?? ''); ?>" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" placeholder="e.g. EMP-123" required>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">First Name</label>
                    <input type="text" name="first_name" value="<?php echo e($view_data['first_name'] ?? ''); ?>" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" required>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Last Name</label>
                    <input type="text" name="last_name" value="<?php echo e($view_data['last_name'] ?? ''); ?>" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" required>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Username (Network ID)</label>
                    <input type="text" name="username" value="<?php echo e($view_data['username'] ?? ''); ?>" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" required>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Operational Designation (Rank)</label>
                    <input type="text" name="designation" value="<?php echo e($view_data['designation'] ?? ''); ?>" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" placeholder="e.g. Senior Lecturer">
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Secure Email (Comms)</label>
                    <input type="email" name="email" value="<?php echo e($view_data['email'] ?? ''); ?>" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" required>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Access Key (Password)</label>
                    <input type="password" name="password" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase placeholder-slate-400" <?php echo $action === 'create' ? 'required' : ''; ?> placeholder="<?php echo $action === 'edit' ? 'LEAVE BLANK TO PRESERVE' : 'DEFINE SECURE KEY'; ?>">
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Operational Status</label>
                    <div class="flex gap-4">
                        <?php foreach (['active' => 'peer-checked:bg-green-600', 'inactive' => 'peer-checked:bg-red-600'] as $val => $color): ?>
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="status" value="<?php echo $val; ?>" <?php echo ($view_data['status'] ?? 'active') === $val ? 'checked' : ''; ?> class="hidden peer">
                                <div class="<?php echo $color; ?> peer-checked:text-white bg-white text-slate-400 border-2 border-slate-200 peer-checked:border-black px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest transition-all hover:border-black">
                                    <?php echo strtoupper($val); ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="flex gap-4 pt-6 border-t-2 border-black border-dashed">
                <a href="?" class="flex-1 btn-os bg-white text-black border-black hover:bg-red-600 hover:text-white hover:border-black text-center">Cancel</a>
                <button type="submit" class="flex-1 btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black hover:border-black">
                    <i class="fas fa-save mr-2"></i> <?php echo $action === 'edit' ? 'Finalize' : 'Authorize'; ?> Personnel
                </button>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- List View -->
    <div class="os-card p-0 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-black text-white text-[10px] font-black uppercase tracking-widest border-b-2 border-black">
                        <th class="px-6 py-4 text-left">Personnel Designation</th>
                        <th class="px-6 py-4 text-left">Internal ID / Username</th>
                        <th class="px-6 py-4 text-left">Sector / Rank</th>
                        <th class="px-6 py-4 text-left">Status</th>
                        <th class="px-6 py-4 text-right">Operations</th>
                    </tr>
                </thead>
                <tbody class="divide-y-2 divide-black">
                    <?php if (!empty($teachers)): foreach ($teachers as $t): ?>
                        <tr class="hover:bg-yellow-50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="text-[11px] font-black text-black uppercase transition-colors"><?php echo e($t['first_name'] . ' ' . $t['last_name']); ?></div>
                                <div class="text-[9px] font-bold text-slate-500 uppercase italic leading-tight"><?php echo e($t['email']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-[10px] font-black text-black uppercase"><?php echo e($t['employee_id']); ?></div>
                                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">@<?php echo e($t['username']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-[10px] font-black text-black uppercase"><?php echo e($t['dept_name']); ?></div>
                                <div class="text-[9px] font-bold text-slate-500 uppercase leading-tight"><?php echo e(strtoupper($t['designation'] ?: 'FACULTY MEMBER')); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 border-2 border-black text-[9px] font-black uppercase tracking-widest <?php echo $t['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo strtoupper($t['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="?action=view&id=<?php echo $t['id']; ?>" class="w-8 h-8 flex items-center justify-center border-2 border-black bg-white hover:bg-black hover:text-white transition-all shadow-[2px_2px_0px_#000000]" title="View Dossier">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    <a href="?action=edit&id=<?php echo $t['id']; ?>" class="w-8 h-8 flex items-center justify-center border-2 border-black bg-white hover:bg-black hover:text-white transition-all shadow-[2px_2px_0px_#000000]" title="Modify Record">
                                        <i class="fas fa-user-edit text-xs"></i>
                                    </a>
                                    <form method="POST" data-confirm-delete="Initiate personnel deactivation?">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <i class="fas fa-users-slash text-4xl text-slate-300 mb-2 block"></i>
                                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">No personnel detected in sector</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
