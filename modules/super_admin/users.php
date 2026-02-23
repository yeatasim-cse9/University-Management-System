<?php
/**
 * User Management Hub
 * ACADEMIX - University Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('super_admin');

$action = $_GET['action'] ?? 'list';
$user_id = $_GET['id'] ?? null;
// Support pre-filled fields for better UX flow
$pre_role = $_GET['role'] ?? '';
$pre_dept = $_GET['dept_id'] ?? '';

$page_title = match($action) {
    'create' => 'Add New User',
    'edit' => 'Edit User',
    'view' => 'Personnel Analysis',
    default => 'User Management'
};

// Handle form submissions (Keeping existing logic for data integrity)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Security error');
        redirect(BASE_URL . '/modules/super_admin/users.php');
    }
    
    $post_action = $_POST['action'] ?? '';
    
    if ($post_action === 'create') {
        $username = sanitize_input($_POST['username'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? ''; 
        $role = sanitize_input($_POST['role'] ?? '');
        $status = sanitize_input($_POST['status'] ?? 'active');
        $first_name = sanitize_input($_POST['first_name'] ?? '');
        $last_name = sanitize_input($_POST['last_name'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $gender = sanitize_input($_POST['gender'] ?? '');
        $date_of_birth = sanitize_input($_POST['date_of_birth'] ?? '');
        $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
        
        $errors = validate_required(['username', 'email', 'password', 'role', 'first_name', 'last_name'], $_POST);
        if (!validate_email($email)) $errors[] = 'Invalid email format';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
        
        if (empty($errors)) {
            $db->begin_transaction();
            try {
                $stmt = $db->prepare("INSERT INTO users (username, email, password, role, status, first_login) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->bind_param("sssss", $username, $email, $password, $role, $status);
                if (!$stmt->execute()) throw new Exception('Error: Username or email already exists.');
                $new_user_id = $stmt->insert_id;
                
                $stmt = $db->prepare("INSERT INTO user_profiles (user_id, first_name, last_name, phone, gender, date_of_birth) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssss", $new_user_id, $first_name, $last_name, $phone, $gender, $date_of_birth);
                $stmt->execute();
                
                if ($role === 'admin' && $department_id) {
                    $stmt = $db->prepare("INSERT INTO department_admins (user_id, department_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $new_user_id, $department_id);
                    $stmt->execute();
                } elseif ($role === 'teacher' && $department_id) {
                    $employee_id = sanitize_input($_POST['employee_id'] ?? '');
                    $designation = sanitize_input($_POST['designation'] ?? '');
                    $specialization = sanitize_input($_POST['specialization'] ?? '');
                    $joining_date = sanitize_input($_POST['joining_date'] ?? date('Y-m-d'));
                    $stmt = $db->prepare("INSERT INTO teachers (user_id, department_id, employee_id, designation, specialization, joining_date) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iissss", $new_user_id, $department_id, $employee_id, $designation, $specialization, $joining_date);
                    $stmt->execute();
                } elseif ($role === 'student' && $department_id) {
                    $student_id = sanitize_input($_POST['student_id'] ?? '');
                    $batch_year = intval($_POST['batch_year'] ?? date('Y'));
                    $session = sanitize_input($_POST['session'] ?? '');
                    $admission_date = sanitize_input($_POST['admission_date'] ?? date('Y-m-d'));
                    $current_semester = intval($_POST['current_semester'] ?? 1);
                    $stmt = $db->prepare("INSERT INTO students (user_id, department_id, student_id, batch_year, session, admission_date, current_semester) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iisissi", $new_user_id, $department_id, $student_id, $batch_year, $session, $admission_date, $current_semester);
                    $stmt->execute();
                }
                
                $db->commit();
                create_audit_log('create_user', 'users', $new_user_id, null, ['username' => $username, 'role' => $role]);
                set_flash('success', 'User added successfully');
                redirect(BASE_URL . '/modules/super_admin/users.php');
            } catch (Exception $e) {
                $db->rollback();
                set_flash('error', $e->getMessage());
            }
        } else {
            set_flash('error', implode(', ', $errors));
        }
    } elseif ($post_action === 'update') {
        $id = intval($_POST['id']);
        $email = sanitize_input($_POST['email'] ?? '');
        $status = sanitize_input($_POST['status'] ?? 'active');
        $new_role = sanitize_input($_POST['role'] ?? '');
        $first_name = sanitize_input($_POST['first_name'] ?? '');
        $last_name = sanitize_input($_POST['last_name'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $gender = sanitize_input($_POST['gender'] ?? '');
        $date_of_birth = sanitize_input($_POST['date_of_birth'] ?? '');
        $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
        $password = $_POST['password'] ?? '';
        
        $db->begin_transaction();
        try {
            $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                throw new Exception("User not found (ID: $id)");
            }
            $old_role = $result->fetch_assoc()['role'];
            
            // Password Update Logic
            if (!empty($password)) {
                 if (strlen($password) < 6) throw new Exception('Password must be at least 6 characters');
                 // Using plain text to maintain consistency with create action (as per reverted state)
                 $stmt = $db->prepare("UPDATE users SET email = ?, status = ?, role = ?, password = ? WHERE id = ?");
                 $stmt->bind_param("ssssi", $email, $status, $new_role, $password, $id);
            } else {
                 $stmt = $db->prepare("UPDATE users SET email = ?, status = ?, role = ? WHERE id = ?");
                 $stmt->bind_param("sssi", $email, $status, $new_role, $id);
            }
            $stmt->execute();
            
            $stmt = $db->prepare("UPDATE user_profiles SET first_name = ?, last_name = ?, phone = ?, gender = ?, date_of_birth = ? WHERE user_id = ?");
            $stmt->bind_param("sssssi", $first_name, $last_name, $phone, $gender, $date_of_birth, $id);
            $stmt->execute();
            
            if ($old_role !== $new_role) {
                if ($old_role === 'admin') $db->query("DELETE FROM department_admins WHERE user_id = $id");
                elseif ($old_role === 'teacher') $db->query("DELETE FROM teachers WHERE user_id = $id");
                elseif ($old_role === 'student') $db->query("DELETE FROM students WHERE user_id = $id");
            }

            if ($new_role === 'admin' && $department_id) {
                $db->query("INSERT INTO department_admins (user_id, department_id) VALUES ($id, $department_id) ON DUPLICATE KEY UPDATE department_id = $department_id");
            } elseif ($new_role === 'teacher') {
                $employee_id = sanitize_input($_POST['employee_id'] ?? '');
                $designation = sanitize_input($_POST['designation'] ?? '');
                $specialization = sanitize_input($_POST['specialization'] ?? '');
                $joining_date = sanitize_input($_POST['joining_date'] ?? date('Y-m-d'));
                $stmt = $db->prepare("INSERT INTO teachers (user_id, department_id, employee_id, designation, specialization, joining_date) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE department_id = ?, employee_id = ?, designation = ?, specialization = ?, joining_date = ?");
                $stmt->bind_param("iisssssssss", $id, $department_id, $employee_id, $designation, $specialization, $joining_date, $department_id, $employee_id, $designation, $specialization, $joining_date);
                $stmt->execute();
            } elseif ($new_role === 'student') {
                $student_id = sanitize_input($_POST['student_id'] ?? '');
                $batch_year = intval($_POST['batch_year'] ?? date('Y'));
                $session = sanitize_input($_POST['session'] ?? '');
                $admission_date = sanitize_input($_POST['admission_date'] ?? date('Y-m-d'));
                $current_semester = intval($_POST['current_semester'] ?? 1);
                $stmt = $db->prepare("INSERT INTO students (user_id, department_id, student_id, batch_year, session, admission_date, current_semester) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE department_id = ?, student_id = ?, batch_year = ?, session = ?, admission_date = ?, current_semester = ?");
                $stmt->bind_param("iisissiisissi", $id, $department_id, $student_id, $batch_year, $session, $admission_date, $current_semester, $department_id, $student_id, $batch_year, $session, $admission_date, $current_semester);
                $stmt->execute();
            }
            
            $db->commit();
            create_audit_log('update_user', 'users', $id, null, ['old_role' => $old_role, 'new_role' => $new_role]);
            set_flash('success', 'User updated successfully');
            redirect(BASE_URL . '/modules/super_admin/users.php');
        } catch (Exception $e) {
            $db->rollback();
            set_flash('error', 'Failed to update user: ' . $e->getMessage());
        }
    } elseif ($post_action === 'delete') {
        $id = intval($_POST['id']);
        if ($db->query("UPDATE users SET deleted_at = NOW() WHERE id = $id")) {
            create_audit_log('delete_user', 'users', $id);
            set_flash('success', 'User deleted successfully');
        }
        redirect(BASE_URL . '/modules/super_admin/users.php');
    }
}

// Data Fetching
$user = null;
if (($action === 'edit' || $action === 'view') && $user_id) {
    $stmt = $db->prepare("SELECT u.*, up.*, 
        T.designation, T.specialization, T.joining_date, T.employee_id,
        S.id as student_table_id, S.student_id, S.batch_year, S.session, S.admission_date, S.current_semester,
        COALESCE(T.department_id, S.department_id, DA.department_id) as department_id
        FROM users u 
        LEFT JOIN user_profiles up ON u.id = up.user_id 
        LEFT JOIN teachers T ON u.id = T.user_id
        LEFT JOIN students S ON u.id = S.user_id
        LEFT JOIN department_admins DA ON u.id = DA.user_id
        WHERE u.id = ? AND u.deleted_at IS NULL");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user) {
        set_flash('error', 'User not found or has been deleted.');
        redirect(BASE_URL . '/modules/super_admin/users.php');
    }
}

$departments = [];
$result = $db->query("SELECT id, name, code FROM departments WHERE deleted_at IS NULL ORDER BY name");
while ($row = $result->fetch_assoc()) $departments[] = $row;

$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$where = "u.deleted_at IS NULL";
if ($role_filter) $where .= " AND u.role = '" . $db->real_escape_string($role_filter) . "'";
if ($status_filter) $where .= " AND u.status = '" . $db->real_escape_string($status_filter) . "'";
if ($search) {
    $s = $db->real_escape_string($search);
    $where .= " AND (u.username LIKE '%$s%' OR u.email LIKE '%$s%' OR up.first_name LIKE '%$s%' OR up.last_name LIKE '%$s%')";
}

$users = [];
$result = $db->query("
    SELECT u.*, up.first_name, up.last_name, 
    COALESCE(d1.code, d2.code, d3.code) as dept_code
    FROM users u 
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN teachers t ON u.id = t.user_id
    LEFT JOIN students s ON u.id = s.user_id
    LEFT JOIN department_admins da ON u.id = da.user_id
    LEFT JOIN departments d1 ON t.department_id = d1.id
    LEFT JOIN departments d2 ON s.department_id = d2.id
    LEFT JOIN departments d3 ON da.department_id = d3.id
    WHERE $where ORDER BY u.created_at DESC
");
while ($row = $result->fetch_assoc()) $users[] = $row;

// UI Assembly
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<div class="space-y-6">
    <!-- Hub Header -->
    <div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white">
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
                <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">User Directory</span>
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest"><?php echo count($users); ?> RECORDS DETECTED</span>
            </div>
            <h1 class="text-3xl font-black uppercase tracking-tighter"><?php echo $page_title; ?></h1>
        </div>
        
        <?php if ($action === 'list'): ?>
            <a href="?action=create" class="btn-os bg-yellow-400 text-black border-black hover:bg-black hover:text-white group">
                <span class="flex items-center gap-2">
                    <i class="fas fa-plus text-sm"></i> Add New User
                    <i class="fas fa-arrow-right text-sm transition-transform group-hover:translate-x-1"></i>
                </span>
            </a>
        <?php else: ?>
            <a href="?" class="btn-os bg-white text-black border-black hover:bg-black hover:text-white group">
                <span class="flex items-center gap-2">
                    <i class="fas fa-arrow-left text-sm transition-transform group-hover:-translate-x-1"></i> Back to List
                </span>
            </a>
        <?php endif; ?>
    </div>

    <?php if ($action === 'list'): ?>
        <!-- Filters -->
        <div class="os-card p-4 bg-white">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="relative col-span-1 md:col-span-2">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-black"></i>
                    <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="SEARCH USERNAME, NAME OR EMAIL..." class="w-full pl-10 pr-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm uppercase placeholder-slate-400 focus:outline-none focus:bg-yellow-50 focus:border-black transition-all">
                </div>
                <div>
                    <div class="relative">
                        <select name="role" class="w-full pl-4 pr-10 py-3 bg-white border-2 border-black font-bold text-sm uppercase appearance-none focus:outline-none focus:bg-yellow-50 focus:border-black transition-all">
                            <option value="">All Roles</option>
                            <option value="super_admin" <?php echo $role_filter === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Dept Admin</option>
                            <option value="teacher" <?php echo $role_filter === 'teacher' ? 'selected' : ''; ?>>Faculty</option>
                            <option value="student" <?php echo $role_filter === 'student' ? 'selected' : ''; ?>>Student</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400"></i>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 btn-os bg-black text-white">FILTER</button>
                    <?php if ($search || $role_filter): ?>
                        <a href="?" class="px-4 py-3 bg-white border-2 border-black flex items-center justify-center hover:bg-slate-100"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Intelligence Matrix (Table) -->
        <div class="os-card p-0 bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-black text-white border-b-2 border-black">
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20">User Details</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20 text-center">Role</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20 text-center">Dept</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest border-r border-white/20 text-center">Status</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-2 divide-slate-100">
                        <?php if (empty($users)): ?>
                            <tr><td colspan="5" class="px-6 py-12 text-center text-slate-400 font-bold uppercase tracking-widest">No users found in database.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): 
                                $role_badge = match($u['role']) {
                                    'super_admin' => 'bg-red-500 text-white',
                                    'admin' => 'bg-blue-500 text-white',
                                    'teacher' => 'bg-green-500 text-white',
                                    'student' => 'bg-yellow-400 text-black',
                                    default => 'bg-slate-500 text-white'
                                };
                            ?>
                                <tr class="hover:bg-yellow-50 transition-colors group">
                                    <td class="px-6 py-4 border-r-2 border-slate-100">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 border-2 border-black bg-white flex items-center justify-center text-black font-black text-sm shadow-[2px_2px_0px_rgba(0,0,0,1)]">
                                                <?php echo strtoupper(substr($u['username'], 0, 2)); ?>
                                            </div>
                                            <div>
                                                <p class="text-xs font-black text-black uppercase tracking-tight"><?php echo e($u['first_name'] . ' ' . $u['last_name']); ?></p>
                                                <p class="text-[10px] font-bold text-slate-500 lowercase">@<?php echo e($u['username']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center border-r-2 border-slate-100">
                                        <span class="inline-block px-2 py-1 border border-black text-[9px] font-black uppercase tracking-widest shadow-[2px_2px_0px_rgba(0,0,0,1)] <?php echo $role_badge; ?>">
                                            <?php echo str_replace('_', ' ', $u['role']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center border-r-2 border-slate-100">
                                        <span class="text-[10px] font-black text-slate-800 uppercase"><?php echo $u['dept_code'] ?: '—'; ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-center border-r-2 border-slate-100">
                                        <div class="flex items-center justify-center gap-2">
                                            <span class="w-2 h-2 rounded-none border border-black <?php echo $u['status'] === 'active' ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                                            <span class="text-[10px] font-black text-slate-800 uppercase tracking-widest"><?php echo $u['status']; ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="?action=view&id=<?php echo $u['id']; ?>" class="w-8 h-8 border border-black bg-white flex items-center justify-center hover:bg-black hover:text-white transition-all shadow-[2px_2px_0px_rgba(0,0,0,1)] hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px]" title="View Details">
                                                <i class="fas fa-eye text-xs"></i>
                                            </a>
                                            <a href="?action=edit&id=<?php echo $u['id']; ?>" class="w-8 h-8 border border-black bg-white flex items-center justify-center hover:bg-black hover:text-white transition-all shadow-[2px_2px_0px_rgba(0,0,0,1)] hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px]" title="Edit User">
                                                <i class="fas fa-edit text-xs"></i>
                                            </a>
                                            <form method="POST" class="inline" data-confirm-delete="CONFIRM DELETION: This action cannot be undone.">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                                <button type="submit" class="w-8 h-8 border border-black bg-white text-red-600 flex items-center justify-center hover:bg-red-600 hover:text-white transition-all shadow-[2px_2px_0px_rgba(0,0,0,1)] hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px]">
                                                    <i class="fas fa-trash-can text-xs"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($action === 'view' && $user): ?>
        <!-- Personnel In-depth Analysis View -->
        <div class="space-y-6 max-w-5xl mx-auto">
            <div class="os-card p-8 bg-black text-white relative overflow-hidden">
                <div class="flex flex-col md:flex-row items-center gap-8 relative z-10">
                    <div class="w-24 h-24 border-4 border-white bg-transparent flex items-center justify-center text-4xl font-black shadow-[4px_4px_0px_#facc15]">
                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                    </div>
                    <div class="flex-1 text-center md:text-left">
                        <div class="flex items-center justify-center md:justify-start gap-3 mb-2">
                            <span class="px-2 py-0.5 bg-yellow-400 text-black border border-white text-[10px] font-black uppercase tracking-widest"><?php echo e($user['role']); ?></span>
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Metadata</span>
                        </div>
                        <h2 class="text-4xl font-black uppercase tracking-tighter leading-none mb-2"><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                        <div class="flex flex-wrap justify-center md:justify-start gap-4">
                            <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400 flex items-center gap-2"><i class="fas fa-at text-yellow-500"></i> <?php echo e($user['username']); ?></span>
                            <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400 flex items-center gap-2"><i class="fas fa-envelope text-yellow-500"></i> <?php echo e($user['email']); ?></span>
                        </div>
                    </div>
                    <div>
                        <a href="?action=edit&id=<?php echo $user['id']; ?>" class="btn-os bg-white text-black border-white hover:bg-yellow-400 hover:border-yellow-400">
                            <i class="fas fa-edit mr-2"></i> Modify Access
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Base Profile Records -->
                <div class="os-card p-6 bg-white">
                    <h4 class="text-xs font-black text-black uppercase tracking-widest mb-6 border-b-2 border-black pb-2">Temporal & Personal Data</h4>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center py-2 border-b border-slate-100">
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Phone Pulse</span>
                            <span class="text-xs font-bold font-mono text-black"><?php echo e($user['phone'] ?: 'N/A'); ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-slate-100">
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Biological Class</span>
                            <span class="text-xs font-bold font-mono text-black uppercase"><?php echo e($user['gender'] ?: 'Unspecified'); ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-slate-100">
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Origin Date</span>
                            <span class="text-xs font-bold font-mono text-black"><?php echo e($user['date_of_birth'] ?: 'N/A'); ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-slate-100">
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Created On</span>
                            <span class="text-xs font-bold font-mono text-black"><?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Academic Configuration -->
                <div class="os-card p-6 bg-white">
                    <h4 class="text-xs font-black text-black uppercase tracking-widest mb-6 border-b-2 border-black pb-2">Academic Protocol</h4>
                    <div class="space-y-4">
                        <?php if ($user['role'] === 'student'): ?>
                            <div class="flex justify-between items-center py-2 border-b border-slate-100">
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Student ID</span>
                                <span class="text-xs font-bold font-mono text-blue-600 bg-blue-50 px-2 py-0.5 border border-blue-200"><?php echo e($user['student_id']); ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-slate-100">
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Current Semester</span>
                                <span class="text-xs font-bold font-mono text-black">Level <?php echo $user['current_semester']; ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-slate-100">
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Batch / Session</span>
                                <span class="text-xs font-bold font-mono text-black"><?php echo e($user['batch_year'] ?? 'N/A'); ?> / <?php echo e($user['session'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-slate-100">
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Admission</span>
                                <span class="text-xs font-bold font-mono text-black"><?php echo e($user['admission_date'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="pt-4">
                                <a href="<?php echo BASE_URL; ?>/modules/super_admin/student-performance.php?student_id=<?php echo $user['student_table_id']; ?>" class="w-full btn-os bg-black text-white text-center block">
                                    <i class="fas fa-chart-pie mr-2"></i> View Performance
                                </a>
                            </div>
                        <?php elseif ($user['role'] === 'teacher'): ?>
                            <div class="flex justify-between items-center py-2 border-b border-slate-100">
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Employee ID</span>
                                <span class="text-xs font-bold font-mono text-green-600 bg-green-50 px-2 py-0.5 border border-green-200"><?php echo e($user['employee_id']); ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-slate-100">
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Designation</span>
                                <span class="text-xs font-bold font-mono text-black uppercase"><?php echo e($user['designation']); ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-slate-100">
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Specialization</span>
                                <span class="text-xs font-bold font-mono text-black"><?php echo e($user['specialization']); ?></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-slate-100">
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Joined</span>
                                <span class="text-xs font-bold font-mono text-black"><?php echo e($user['joining_date']); ?></span>
                            </div>
                        <?php else: ?>
                            <div class="py-8 text-center border-2 border-dashed border-slate-200 bg-slate-50 p-4">
                                <i class="fas fa-shield-halved text-2xl text-slate-300 mb-2"></i>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Administrative personnel has no academic subtype data.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Form UI: Multi-Section Configuration Terminal -->
        <div class="os-card p-0 overflow-hidden max-w-5xl mx-auto bg-white">
            <div class="bg-black p-6 text-white flex justify-between items-center border-b-2 border-black">
                <div>
                    <h3 class="text-xl font-black uppercase tracking-widest text-yellow-400">Configuration <span class="text-white">Panel</span></h3>
                    <p class="text-[10px] font-bold font-mono text-slate-400 uppercase mt-1">
                        System::<?php echo $action === 'edit' ? 'UPDATE' : 'INIT'; ?>_PROTOCOL
                    </p>
                </div>
                <div class="w-10 h-10 border-2 border-white bg-transparent flex items-center justify-center animate-spin-slow">
                    <i class="fas fa-gear text-white"></i>
                </div>
            </div>

            <form method="POST" class="p-8 space-y-8" id="identityForm">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="<?php echo $action === 'edit' ? 'update' : 'create'; ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?php echo e($user['id']); ?>">
                <?php endif; ?>

                <!-- Section: Base Identity -->
                <div>
                    <h4 class="text-xs font-black text-black uppercase tracking-widest mb-6 border-b-2 border-black pb-2 flex items-center gap-2">
                        <i class="fas fa-key text-yellow-500"></i> Account Access
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-1">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Username</label>
                            <input type="text" name="username" value="<?php echo e($user['username'] ?? ''); ?>" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm focus:outline-none focus:bg-yellow-50 transition-all disabled:opacity-50 disabled:cursor-not-allowed" required <?php echo $action === 'edit' ? 'readonly' : ''; ?>>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Email Address</label>
                            <input type="email" name="email" value="<?php echo e($user['email'] ?? ''); ?>" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm focus:outline-none focus:bg-yellow-50 transition-all" required>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                Password 
                                <?php if ($action === 'edit'): ?>
                                    <span class="text-slate-400 text-[9px] lowercase font-normal">(leave blank to keep current)</span>
                                <?php else: ?>
                                    <span class="text-red-500">*</span>
                                <?php endif; ?>
                            </label>
                            <div class="relative">
                                <input type="text" name="password" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm focus:outline-none focus:bg-yellow-50 transition-all" placeholder="<?php echo $action === 'edit' ? 'Enter new password...' : ''; ?>">
                                <button type="button" onclick="this.previousElementSibling.value=Math.random().toString(36).slice(-8)" class="absolute right-2 top-1/2 -translate-y-1/2 text-[9px] font-black uppercase bg-black text-white px-2 py-1 hover:bg-yellow-400 hover:text-black transition-all">Gen</button>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">User Role</label>
                            <div class="relative">
                                <select name="role" id="role_select" onchange="toggleRegistryFields()" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm uppercase appearance-none focus:outline-none focus:bg-yellow-50 transition-all">
                                    <option value="student" <?php echo ($user['role'] ?? $pre_role) === 'student' ? 'selected' : ''; ?>>Student</option>
                                    <option value="teacher" <?php echo ($user['role'] ?? $pre_role) === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                                    <option value="admin" <?php echo ($user['role'] ?? $pre_role) === 'admin' ? 'selected' : ''; ?>>Department Admin</option>
                                    <option value="super_admin" <?php echo ($user['role'] ?? $pre_role) === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400"></i>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Status</label>
                            <div class="relative">
                                <select name="status" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm uppercase appearance-none focus:outline-none focus:bg-yellow-50 transition-all">
                                    <option value="active" <?php echo ($user['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Online</option>
                                    <option value="inactive" <?php echo ($user['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Offline</option>
                                    <option value="suspended" <?php echo ($user['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>Restricted</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Bio Data -->
                <div>
                    <h4 class="text-xs font-black text-black uppercase tracking-widest mb-6 border-b-2 border-black pb-2 flex items-center gap-2">
                        <i class="fas fa-user text-yellow-500"></i> Personal Profile
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="space-y-1">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">First Name</label>
                            <input type="text" name="first_name" value="<?php echo e($user['first_name'] ?? ''); ?>" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm focus:outline-none focus:bg-yellow-50 transition-all" required>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Last Name</label>
                            <input type="text" name="last_name" value="<?php echo e($user['last_name'] ?? ''); ?>" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm focus:outline-none focus:bg-yellow-50 transition-all" required>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Gender</label>
                            <div class="relative">
                                <select name="gender" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm uppercase appearance-none focus:outline-none focus:bg-yellow-50 transition-all">
                                    <option value="male" <?php echo ($user['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($user['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo ($user['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Non-Binary</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400"></i>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Date of Birth</label>
                            <input type="date" name="date_of_birth" value="<?php echo ($user['date_of_birth'] && $user['date_of_birth'] !== '0000-00-00') ? $user['date_of_birth'] : ''; ?>" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm focus:outline-none focus:bg-yellow-50 transition-all">
                        </div>
                    </div>
                </div>

                <!-- Strategic registry fields -->
                <div id="registry_extensor" class="animate-in slide-in-from-top-4 duration-500">
                    <h4 class="text-xs font-black text-black uppercase tracking-widest mb-6 border-b-2 border-black pb-2 flex items-center gap-2">
                        <i class="fas fa-id-card text-yellow-500"></i> Role Details
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="space-y-1">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Department</label>
                            <div class="relative">
                                <select name="department_id" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm uppercase appearance-none focus:outline-none focus:bg-yellow-50 transition-all">
                                    <option value="">Global Unit</option>
                                    <?php foreach ($departments as $d): ?>
                                        <option value="<?php echo $d['id']; ?>" <?php echo ($user['department_id'] ?? $pre_dept) == $d['id'] ? 'selected' : ''; ?>><?php echo e($d['code']); ?> - <?php echo e($d['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400"></i>
                            </div>
                        </div>

                        <!-- Role Specific Conditionals -->
                        <div id="student_fields" class="hidden contents">
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Student ID</label>
                                <input type="text" name="student_id" value="<?php echo e($user['student_id'] ?? ''); ?>" placeholder="STU-2024-001" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm focus:outline-none focus:bg-yellow-50 transition-all">
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Batch & Session</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <input type="number" name="batch_year" value="<?php echo e($user['batch_year'] ?? date('Y')); ?>" placeholder="2024" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm focus:outline-none focus:bg-yellow-50 transition-all">
                                    <input type="text" name="session" value="<?php echo e($user['session'] ?? ''); ?>" placeholder="Spring" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm focus:outline-none focus:bg-yellow-50 transition-all">
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Current Semester</label>
                                <input type="number" name="current_semester" value="<?php echo e($user['current_semester'] ?? 1); ?>" min="1" max="15" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm focus:outline-none focus:bg-yellow-50 transition-all">
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Admission Date</label>
                                <input type="date" name="admission_date" value="<?php echo ($user['admission_date'] && $user['admission_date'] !== '0000-00-00') ? $user['admission_date'] : date('Y-m-d'); ?>" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm focus:outline-none focus:bg-yellow-50 transition-all">
                            </div>
                        </div>

                        <div id="teacher_fields" class="hidden contents">
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Employee ID</label>
                                <input type="text" name="employee_id" value="<?php echo e($user['employee_id'] ?? ''); ?>" placeholder="EMP-1001" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm focus:outline-none focus:bg-yellow-50 transition-all">
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Designation/Rank</label>
                                <input type="text" name="designation" value="<?php echo e($user['designation'] ?? ''); ?>" placeholder="Professor" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm focus:outline-none focus:bg-yellow-50 transition-all">
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Specialization</label>
                                <input type="text" name="specialization" value="<?php echo e($user['specialization'] ?? ''); ?>" placeholder="Quantum Physics" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm focus:outline-none focus:bg-yellow-50 transition-all">
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Joining Date</label>
                                <input type="date" name="joining_date" value="<?php echo ($user['joining_date'] && $user['joining_date'] !== '0000-00-00') ? $user['joining_date'] : date('Y-m-d'); ?>" class="w-full px-4 py-3 bg-slate-50 border-2 border-black font-bold text-sm focus:outline-none focus:bg-yellow-50 transition-all">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t-2 border-black flex items-center justify-end">
                    <button type="submit" class="btn-os bg-yellow-400 text-black border-black hover:bg-black hover:text-white group">
                        <span class="flex items-center gap-2">
                            <i class="fas fa-save"></i>
                            Save User Configuration
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <script>
            function toggleRegistryFields() {
                const role = document.getElementById('role_select').value;
                const studentFields = document.getElementById('student_fields');
                const teacherFields = document.getElementById('teacher_fields');

                // Reset visibility
                studentFields.classList.add('hidden');
                teacherFields.classList.add('hidden');
                studentFields.classList.remove('contents');
                teacherFields.classList.remove('contents');

                if (role === 'student') {
                    studentFields.classList.remove('hidden');
                    studentFields.classList.add('contents');
                } else if (role === 'teacher') {
                    teacherFields.classList.remove('hidden');
                    teacherFields.classList.add('contents');
                }
            }
            window.addEventListener('DOMContentLoaded', toggleRegistryFields);
        </script>
    <?php endif; ?>
</div>

<style>
    @keyframes spin-slow { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .animate-spin-slow { animation: spin-slow 8s linear infinite; }
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
