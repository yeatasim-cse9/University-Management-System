<?php
/**
 * Login Page
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect(BASE_URL . '/index.php');
}

// Check remember me cookie
if (check_remember_me()) {
    redirect(BASE_URL . '/index.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? ''; // Plain text password
    $remember = isset($_POST['remember_me']);
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        // Check if account is locked
        $lock_check = check_login_attempts($username);
        
        if ($lock_check['locked']) {
            $error = $lock_check['message'];
            log_login_attempt($username, null, 'failed', 'Account locked');
        } else {
            // Query user
            $stmt = $db->prepare("SELECT u.*, up.first_name, up.last_name FROM users u LEFT JOIN user_profiles up ON u.id = up.user_id WHERE (u.username = ? OR u.email = ?) AND u.deleted_at IS NULL");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                // Check if user is active
                if ($user['status'] !== 'active') {
                    $error = 'Your account is not active. Please contact administrator.';
                    log_login_attempt($username, $user['id'], 'failed', 'Account not active');
                } 
                // Compare plain text passwords (as per requirement)
                elseif ($password === $user['password']) {
                    // Successful login
                    reset_failed_attempts($user['id']);
                    
                    // Update last login
                    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->bind_param("i", $user['id']);
                    $stmt->execute();
                    
                    // Initialize session
                    init_session($user);
                    
                    // Handle remember me
                    if ($remember) {
                        set_remember_me($user['id']);
                    }
                    
                    // Log successful login
                    log_login_attempt($username, $user['id'], 'success');
                    create_audit_log('login', 'users', $user['id']);
                    
                    // Redirect to dashboard
                    redirect(BASE_URL . '/index.php');
                } else {
                    // Invalid password
                    $attempts = increment_failed_attempts($username);
                    $remaining = MAX_LOGIN_ATTEMPTS - $attempts;
                    
                    if ($remaining > 0) {
                        $error = "Invalid username or password. {$remaining} attempt(s) remaining.";
                    } else {
                        $error = "Too many failed attempts. Account locked for " . (LOCKOUT_DURATION / 60) . " minutes.";
                    }
                    
                    log_login_attempt($username, $user['id'], 'failed', 'Invalid password');
                }
            } else {
                $error = 'Invalid username or password';
                log_login_attempt($username, null, 'failed', 'User not found');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ACADEMIX_OS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Outfit:wght@500;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#facc15', // Yellow 400
                        secondary: '#000000',
                    },
                    fontFamily: {
                        heading: ['Outfit', 'sans-serif'],
                        body: ['Outfit', 'sans-serif'],
                        mono: ['Outfit', 'sans-serif'],
                    },
                    borderWidth: {
                        '3': '3px',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/custom.css?v=<?php echo time(); ?>">
    <style>
        body {
            background-color: #000000;
            background-image: radial-gradient(#333 1px, transparent 1px);
            background-size: 32px 32px;
        }
        .os-border {
            border: 4px solid #000;
        }
        .os-shadow {
            box-shadow: 8px 8px 0px #000;
        }
        .os-shadow-yellow {
            box-shadow: 8px 8px 0px #facc15;
        }
        .grid-pattern {
            background-image: 
                linear-gradient(rgba(0,0,0,0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,0,0,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
        }
        .stripe-bg {
            background: repeating-linear-gradient(
                -45deg,
                #facc15,
                #facc15 10px,
                #000 10px,
                #000 20px
            );
        }
        .input-os {
            @apply border-3 border-black p-3 bg-white focus:bg-yellow-50 focus:outline-none transition-all font-bold uppercase text-xs tracking-wider;
        }
        .btn-os {
            @apply border-3 border-black bg-yellow-400 py-4 px-6 font-black uppercase text-sm tracking-widest hover:bg-yellow-500 transition-all active:translate-y-1 active:shadow-none;
        }
    </style>
</head>
<body class="font-sans min-h-screen flex items-center justify-center p-4">
    
    <div class="w-full max-w-5xl bg-white os-border os-shadow-yellow flex flex-col md:flex-row overflow-hidden">
        
        <!-- Left Side: Branding / Info (Dark Mode) -->
        <div class="hidden md:flex md:w-5/12 bg-black flex-col p-12 text-white relative">
            <div class="absolute inset-0 opacity-10 grid-pattern"></div>
            
            <div class="relative z-10 flex flex-col h-full">
                <div class="flex items-center gap-3 mb-12">
                    <div class="w-12 h-12 bg-yellow-400 flex items-center justify-center border-2 border-white">
                        <i class="fas fa-graduation-cap text-black text-xl"></i>
                    </div>
                    <span class="text-2xl font-black tracking-tighter uppercase font-outfit">ACADEMIX_OS</span>
                </div>

                <div class="mt-auto space-y-6">
                    <div class="p-6 border-2 border-yellow-400/30 bg-industrial">
                        <div class="text-[10px] font-mono text-yellow-500 uppercase tracking-[0.3em] mb-2">SYSTEM_ID // BX-9921-AZ</div>
                        <h2 class="text-3xl font-black uppercase leading-none mb-4">Central Processing Unit</h2>
                        <div class="flex gap-2">
                            <span class="px-2 py-1 bg-yellow-400 text-black text-[10px] font-bold uppercase">V2.0.4</span>
                            <span class="px-2 py-1 border border-white text-[10px] font-bold uppercase">SECURED</span>
                        </div>
                    </div>
                    
                    <p class="text-slate-400 font-mono text-xs leading-relaxed">
                        > INITIALIZING PROTOCOL...<br>
                        > LOADING ACADEMIC SHIELD...<br>
                        > STATUS: <span class="text-yellow-400">WAITING FOR AUTH</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="w-full md:w-7/12 flex flex-col justify-center p-8 sm:p-12 lg:p-16 bg-white relative">
            <div class="absolute inset-0 opacity-[0.03] grid-pattern"></div>
            
            <div class="max-w-md w-full mx-auto relative z-10">
                
                <div class="flex justify-between items-end mb-12">
                    <div>
                        <div class="h-1 w-12 bg-black mb-4"></div>
                        <h1 class="text-5xl font-black uppercase tracking-tighter text-black leading-none">SIGN_IN</h1>
                        <p class="text-slate-500 font-bold uppercase text-[10px] mt-2 tracking-widest">Authorize access to the neural network</p>
                    </div>
                    <div class="hidden sm:block">
                        <div class="w-16 h-1 stripe-bg"></div>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="mb-8 border-3 border-black bg-white p-4 flex items-center os-shadow">
                        <div class="w-8 h-8 bg-black flex items-center justify-center mr-4 shrink-0">
                            <i class="fas fa-bolt text-yellow-400 text-xs"></i>
                        </div>
                        <p class="text-[10px] font-black text-black uppercase tracking-wider"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-6">
                    <div class="flex flex-col gap-2">
                        <div class="flex justify-between">
                            <label for="username" class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-800">IDENTITY_TOKEN</label>
                            <span class="text-[9px] font-mono text-slate-400">[REQUIRED]</span>
                        </div>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required 
                               class="input-os"
                               placeholder="USERNAME">
                    </div>

                    <div class="flex flex-col gap-2">
                        <div class="flex justify-between">
                            <label for="password" class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-800">ENCRYPTION_KEY</label>
                            <a href="#" class="text-[9px] font-black text-yellow-600 uppercase underline">Recover?</a>
                        </div>
                        <input type="password" id="password" name="password" required 
                               class="input-os"
                               placeholder="••••••••••••">
                    </div>

                    <div class="flex items-center py-2">
                        <input id="remember_me" name="remember_me" type="checkbox" 
                               class="w-5 h-5 border-3 border-black rounded-none appearance-none checked:bg-black checked:border-black cursor-pointer bg-white">
                        <label for="remember_me" class="ml-3 text-[10px] font-black uppercase tracking-widest text-slate-600 cursor-pointer select-none">Persistent Session</label>
                    </div>

                    <button type="submit" class="btn-os w-full os-shadow">
                        Execute Authentication
                    </button>                   
                </form>

                <div class="mt-16 pt-8 border-t-2 border-black/5 flex flex-col sm:flex-row justify-between gap-4">
                    <div class="flex gap-4">
                        <a href="#" class="text-[9px] font-bold uppercase text-slate-400 hover:text-black">Terms</a>
                        <a href="#" class="text-[9px] font-bold uppercase text-slate-400 hover:text-black">Privacy</a>
                    </div>
                    <div class="text-[9px] font-mono text-slate-300">© 2026 ACADEMIX.OS // ALL RIGHTS RESERVED</div>
                </div>
                
            </div>
        </div>
    </div>

</body>
</html>

