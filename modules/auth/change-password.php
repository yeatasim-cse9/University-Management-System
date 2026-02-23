<?php
/**
 * Change Password
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$page_title = 'Change Password';
$error = '';
$success = '';
$is_first_login = $_SESSION['first_login'] == 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All fields are required';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters long';
        } else {
            $user_id = get_current_user_id();
            
            // Get current password
            $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            // Compare plain text passwords
            if ($current_password !== $user['password']) {
                $error = 'Current password is incorrect';
            } else {
                // Update password
                $stmt = $db->prepare("UPDATE users SET password = ?, first_login = 0 WHERE id = ?");
                $stmt->bind_param("si", $new_password, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['first_login'] = 0;
                    create_audit_log('password_change', 'users', $user_id);
                    $success = 'Password changed successfully';
                    
                    if ($is_first_login) {
                        set_flash('success', 'Password changed successfully. Welcome to ACADEMIX!');
                        redirect(BASE_URL . '/index.php');
                    }
                } else {
                    $error = 'Failed to update password. Please try again.';
                }
            }
        }
    }
}

// Sidebar menu based on role
ob_start();
$role = get_current_user_role();
switch ($role) {
    case 'super_admin':
        include __DIR__ . '/../super_admin/_sidebar.php';
        break;
    case 'admin':
        include __DIR__ . '/../admin/_sidebar.php';
        break;
    case 'teacher':
        include __DIR__ . '/../teacher/_sidebar.php';
        break;
    case 'student':
        include __DIR__ . '/../student/_sidebar.php';
        break;
}
$sidebar_menu = ob_get_clean();

// Page content
ob_start();
?>

<div class="space-y-8 animate-in">
    <!-- Header -->
    <div class="bg-white os-border os-shadow p-8 flex flex-col md:flex-row gap-8 justify-between relative overflow-hidden">
        <div class="absolute top-0 right-0 p-4 opacity-10 pointer-events-none">
            <i class="fas fa-lock text-9xl text-black transform rotate-12"></i>
        </div>
        
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <span class="bg-black text-white text-[10px] font-black uppercase px-2 py-1 tracking-widest">Security</span>
                <span class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest">
                    <span class="w-2 h-2 bg-yellow-400 rounded-full border border-black"></span>
                    <?php echo $is_first_login ? 'Setup Required' : 'Standard Protocol'; ?>
                </span>
            </div>
            <h1 class="text-4xl md:text-6xl font-black uppercase leading-none tracking-tighter mb-2">
                Change <span class="bg-yellow-400 px-2 box-decoration-clone text-black">Password</span>
            </h1>
            <p class="text-[10px] font-mono font-bold uppercase tracking-widest text-slate-500">
                Update your security credentials
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Sidebar: Security Info -->
        <div class="lg:col-span-4 space-y-8">
            <div class="os-card p-0 bg-white">
                <div class="p-6 border-b-3 border-black bg-black text-white">
                    <h3 class="text-xl font-black uppercase tracking-tight">Security Status</h3>
                    <p class="text-[10px] font-mono opacity-60 uppercase mt-1">Credentials Check</p>
                </div>
                
                <div class="p-6 space-y-6">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-8 h-8 bg-green-500 rounded-lg border-2 border-black flex items-center justify-center text-black">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h4 class="text-xs font-black uppercase">Account Active</h4>
                        </div>
                        <p class="text-[10px] font-mono text-slate-500">Your account interactions are protected.</p>
                    </div>

                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-8 h-8 bg-yellow-400 rounded-lg border-2 border-black flex items-center justify-center text-black">
                                <i class="fas fa-key"></i>
                            </div>
                            <h4 class="text-xs font-black uppercase">Last Updated</h4>
                        </div>
                        <p class="text-[10px] font-mono text-slate-500">
                            <?php echo $is_first_login ? 'Never (First Login)' : 'Credential update pending'; ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-black text-white p-6 border-3 border-black shadow-[4px_4px_0px_#ccc]">
                <h3 class="text-xs font-black text-yellow-400 uppercase tracking-widest mb-2">Password Policy</h3>
                <ul class="space-y-2 font-mono text-[10px] list-disc pl-4 opacity-70">
                    <li>Minimum 6 characters required</li>
                    <li>Avoid using common phrases</li>
                    <li>Credentials are stored securely</li>
                </ul>
            </div>
        </div>

        <!-- Main Form Area -->
        <div class="lg:col-span-8">
            <div class="os-card p-6 bg-white relative">
                <?php if ($error): ?>
                    <div class="mb-8 bg-red-100 border-l-4 border-red-500 p-4">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-exclamation-circle text-red-500"></i>
                            <p class="text-xs font-black uppercase text-red-700"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="mb-8 bg-green-100 border-l-4 border-green-500 p-4">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <p class="text-xs font-black uppercase text-green-700"><?php echo htmlspecialchars($success); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-6">
                    <?php csrf_field(); ?>
                    
                    <div class="max-w-xl">
                        <div class="mb-6">
                            <label for="current_password" class="block text-xs font-black uppercase mb-2">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required class="input-os w-full" placeholder="Enter current password">
                            <p class="text-[10px] text-slate-400 font-mono mt-1">Required to verify your identity</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div>
                                <label for="new_password" class="block text-xs font-black uppercase mb-2">New Password</label>
                                <input type="password" id="new_password" name="new_password" required class="input-os w-full" placeholder="Min 6 characters">
                            </div>
                            
                            <div>
                                <label for="confirm_password" class="block text-xs font-black uppercase mb-2">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required class="input-os w-full" placeholder="Re-enter new password">
                            </div>
                        </div>

                        <div class="pt-6 border-t-3 border-black flex items-center justify-between">
                            <a href="<?php echo BASE_URL; ?>/index.php" class="text-xs font-black uppercase hover:underline">Cancel</a>
                            <button type="submit" class="btn-os px-8 py-3">
                                Update Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
