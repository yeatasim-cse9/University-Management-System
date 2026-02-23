<?php
/**
 * User Profile
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$page_title = 'My Profile';
$user_id = get_current_user_id();

// Get user data
$stmt = $db->prepare("SELECT u.*, up.* FROM users u LEFT JOIN user_profiles up ON u.id = up.user_id WHERE u.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request');
    } else {
        $first_name = sanitize_input($_POST['first_name'] ?? '');
        $last_name = sanitize_input($_POST['last_name'] ?? '');
        $username_new = sanitize_input($_POST['username'] ?? '');
        $email_new = sanitize_input($_POST['email'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $address = sanitize_input($_POST['address'] ?? '');
        $city = sanitize_input($_POST['city'] ?? '');
        $date_of_birth = sanitize_input($_POST['date_of_birth'] ?? '');
        $gender = sanitize_input($_POST['gender'] ?? '');
        
        $profile_picture = $user['profile_picture'] ?? '';
        
        // Validation
        if (empty($username_new) || empty($email_new)) {
            set_flash('error', 'Username and email are required');
        } elseif (!validate_email($email_new)) {
            set_flash('error', 'Invalid email format');
        } else {
            // Check username uniqueness
            $stmt_check = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt_check->bind_param("si", $username_new, $user_id);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                set_flash('error', 'Username is already taken');
            } else {
                // Check email uniqueness
                $stmt_check = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt_check->bind_param("si", $email_new, $user_id);
                $stmt_check->execute();
                if ($stmt_check->get_result()->num_rows > 0) {
                    set_flash('error', 'Email is already taken');
                } else {
                    // Start transaction
                    $db->begin_transaction();
                    try {
                        // Update users table
                        $stmt_u = $db->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                        $stmt_u->bind_param("ssi", $username_new, $email_new, $user_id);
                        $stmt_u->execute();

                        // Handle file upload
                        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                            $file = $_FILES['profile_photo'];
                            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                            
                            if (in_array($ext, $allowed)) {
                                $filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
                                $upload_dir = ASSETS_PATH . '/uploads/profiles/';
                                
                                if (!is_dir($upload_dir)) {
                                    mkdir($upload_dir, 0777, true);
                                }
                                
                                if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                                    // Delete old photo if exists
                                    if (!empty($profile_picture) && file_exists($upload_dir . $profile_picture)) {
                                        unlink($upload_dir . $profile_picture);
                                    }
                                    $profile_picture = $filename;
                                }
                            }
                        }
                        
                        if ($user['user_id']) {
                            // Update existing profile
                            $stmt_p = $db->prepare("UPDATE user_profiles SET first_name = ?, last_name = ?, phone = ?, address = ?, city = ?, date_of_birth = ?, gender = ?, profile_picture = ? WHERE user_id = ?");
                            $stmt_p->bind_param("ssssssssi", $first_name, $last_name, $phone, $address, $city, $date_of_birth, $gender, $profile_picture, $user_id);
                        } else {
                            // Create new profile
                            $stmt_p = $db->prepare("INSERT INTO user_profiles (user_id, first_name, last_name, phone, address, city, date_of_birth, gender, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt_p->bind_param("issssssss", $user_id, $first_name, $last_name, $phone, $address, $city, $date_of_birth, $gender, $profile_picture);
                        }
                        $stmt_p->execute();

                        $db->commit();
                        
                        // Update session
                        $_SESSION['username'] = $username_new;
                        $_SESSION['email'] = $email_new;
                        $_SESSION['profile_picture'] = $profile_picture;

                        create_audit_log('update_profile', 'users/user_profiles', $user_id);
                        set_flash('success', 'Profile updated successfully');
                        redirect(BASE_URL . '/modules/auth/profile.php');
                    } catch (Exception $e) {
                        $db->rollback();
                        set_flash('error', 'Failed to update profile: ' . $e->getMessage());
                    }
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
            <i class="fas fa-id-card text-9xl text-black transform -rotate-12"></i>
        </div>
        
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <span class="bg-black text-white text-[10px] font-black uppercase px-2 py-1 tracking-widest">My Profile</span>
                <span class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest">
                    <span class="w-2 h-2 bg-green-500 rounded-full border border-black"></span>
                    Editable
                </span>
            </div>
            <h1 class="text-4xl md:text-6xl font-black uppercase leading-none tracking-tighter mb-2">
                User <span class="bg-yellow-400 px-2 box-decoration-clone text-black">Profile</span>
            </h1>
            <p class="text-[10px] font-mono font-bold uppercase tracking-widest text-slate-500">
                Update your personal information
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Sidebar: Profile Preview -->
        <div class="lg:col-span-4 space-y-8">
            <div class="os-card p-0 bg-white">
                <div class="p-6 border-b-3 border-black bg-yellow-400">
                    <div class="relative w-32 h-32 mx-auto mb-4">
                        <div id="profile-preview-container" class="w-full h-full border-3 border-black bg-white overflow-hidden shadow-[4px_4px_0px_#000]">
                            <?php if (!empty($user['profile_picture'])): ?>
                                <img src="<?php echo ASSETS_URL . '/uploads/profiles/' . $user['profile_picture']; ?>" alt="Profile" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full bg-black text-white flex items-center justify-center text-4xl font-black">
                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" onclick="document.getElementById('profile_photo_input').click()" 
                                class="absolute -bottom-2 -right-2 w-10 h-10 bg-white text-black border-2 border-black flex items-center justify-center hover:bg-black hover:text-white transition-colors shadow-sm active:scale-95">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                    <div class="text-center">
                        <h2 class="text-xl font-black uppercase text-black leading-none mb-1">
                            <?php echo e((!empty($user['first_name']) && !empty($user['last_name'])) ? $user['first_name'] . ' ' . $user['last_name'] : $user['username']); ?>
                        </h2>
                        <span class="inline-block px-2 py-0.5 bg-black text-white text-[9px] font-bold uppercase tracking-widest">
                            <?php echo str_replace('_', ' ', $user['role']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="p-6 space-y-4">
                    <div class="flex flex-col">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Username</span>
                        <div class="font-mono font-bold text-sm">@<?php echo e($user['username']); ?></div>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Status</span>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            <span class="font-black text-xs uppercase text-green-600">Active</span>
                        </div>
                    </div>

                    <div class="pt-4 border-t-2 border-dashed border-black/20">
                        <a href="change-password.php" class="w-full btn-os btn-os-white text-[10px] py-2">
                            <i class="fas fa-key mr-2"></i> Change Password
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-black text-white p-6 border-3 border-black shadow-[4px_4px_0px_#ccc]">
                <h3 class="text-xs font-black text-yellow-400 uppercase tracking-widest mb-2">Important Note</h3>
                <p class="font-mono text-[10px] leading-relaxed opacity-70">
                    Keep your contact information updated. Your email and phone number are used for system notifications and password recovery.
                </p>
            </div>
        </div>

        <!-- Main Form Area -->
        <div class="lg:col-span-8">
            <div class="os-card p-6 bg-white">
                <div class="flex items-center justify-between mb-8 border-b-3 border-black pb-4">
                    <h3 class="text-xl font-black uppercase tracking-tight">Edit Information</h3>
                </div>

                <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                    <?php csrf_field(); ?>
                    <input type="file" id="profile_photo_input" name="profile_photo" class="hidden" accept="image/*" onchange="previewImage(this)">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Account Info -->
                        <div class="md:col-span-2">
                            <h4 class="text-xs font-black uppercase text-slate-400 tracking-widest mb-4">Account Credentials</h4>
                        </div>
                        
                        <div>
                            <label for="username" class="block text-xs font-black uppercase mb-2">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo e($user['username']); ?>" required class="input-os w-full">
                        </div>
                        <div>
                            <label for="email" class="block text-xs font-black uppercase mb-2">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo e($user['email']); ?>" required class="input-os w-full">
                        </div>

                        <!-- Personal Info -->
                        <div class="md:col-span-2 mt-4">
                            <h4 class="text-xs font-black uppercase text-slate-400 tracking-widest mb-4">Personal Details</h4>
                        </div>
                        
                        <div>
                            <label for="first_name" class="block text-xs font-black uppercase mb-2">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo e($user['first_name'] ?? ''); ?>" class="input-os w-full">
                        </div>
                        <div>
                            <label for="last_name" class="block text-xs font-black uppercase mb-2">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo e($user['last_name'] ?? ''); ?>" class="input-os w-full">
                        </div>
                        <div>
                            <label for="phone" class="block text-xs font-black uppercase mb-2">Phone Number</label>
                            <input type="text" id="phone" name="phone" value="<?php echo e($user['phone'] ?? ''); ?>" class="input-os w-full">
                        </div>
                        <div>
                            <label for="date_of_birth" class="block text-xs font-black uppercase mb-2">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo e($user['date_of_birth'] ?? ''); ?>" class="input-os w-full">
                        </div>
                        <div>
                            <label for="gender" class="block text-xs font-black uppercase mb-2">Gender</label>
                            <select id="gender" name="gender" class="input-os w-full">
                                <option value="">Select Gender</option>
                                <option value="male" <?php echo ($user['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($user['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($user['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div>
                            <label for="city" class="block text-xs font-black uppercase mb-2">City</label>
                            <input type="text" id="city" name="city" value="<?php echo e($user['city'] ?? ''); ?>" class="input-os w-full">
                        </div>
                        <div class="md:col-span-2">
                            <label for="address" class="block text-xs font-black uppercase mb-2">Address</label>
                            <textarea id="address" name="address" rows="2" class="input-os w-full uppercase"><?php echo e($user['address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="pt-6 border-t-3 border-black flex justify-end">
                        <button type="submit" class="btn-os px-8 py-3">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            const container = document.getElementById('profile-preview-container');
            if (container) {
                container.innerHTML = `<img src="${e.target.result}" alt="Preview" class="w-full h-full object-cover">`;
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
