<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($page_title ?? 'ACADEMIX'); ?> - University of Barisal</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#facc15', // Yellow 400
                        secondary: '#000000',
                        dark: '#0f172a',
                    },
                    fontFamily: {
                        heading: ['Outfit', 'sans-serif'],
                        body: ['Outfit', 'sans-serif'],
                        mono: ['Outfit', 'sans-serif'],
                    },
                    borderWidth: {
                        '3': '3px',
                        '4': '4px',
                    }
                }
            }
        }
    </script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/custom.css?v=<?php echo time(); ?>">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Sidebar Transitions */
        #sidebar { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        
        /* Mobile & Tablet */
        @media (max-width: 1440px) {
            #sidebar { transform: translateX(-100%); }
            body.sidebar-open #sidebar { transform: translateX(0); }
            body.sidebar-open { overflow: hidden; }
        }
        
        /* Large Desktop */
        @media (min-width: 1441px) {
            #sidebar { transform: translateX(0); }
            #sidebar-backdrop { display: none !important; }
            #main-content { margin-left: 16rem !important; padding-bottom: 0 !important; }
            [data-sidebar-toggle] { display: none !important; }
        }
        
        /* Hide scrollbar for nav */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#f8fafc] font-body text-sm text-black selection:bg-yellow-400 selection:text-black">

    <!-- Mobile Backdrop -->
    <div id="sidebar-backdrop" 
         data-sidebar-backdrop
         class="fixed inset-0 bg-black/50 backdrop-blur-sm z-30 hidden transition-opacity"></div>

    <!-- Sidebar -->
    <aside id="sidebar" 
           data-sidebar
           class="fixed top-0 left-0 z-40 w-64 h-screen bg-white text-black border-r-4 border-black transition-all flex flex-col">
        
        <!-- Logo -->
        <div class="h-20 flex items-center px-6 border-b-4 border-black bg-black">
            <a href="<?php echo BASE_URL; ?>" class="flex items-center gap-3 w-full group">
                <div class="w-10 h-10 border-2 border-yellow-400 bg-black flex items-center justify-center shrink-0 shadow-[4px_4px_0px_#facc15] group-hover:translate-x-1 transition-transform">
                    <i class="fas fa-graduation-cap text-yellow-400 text-lg"></i>
                </div>
                <div class="flex flex-col">
                    <span class="font-heading font-black text-xl text-white tracking-tight leading-none uppercase">ACADEMIX</span>
                    <span class="text-[8px] font-mono font-bold text-yellow-400 uppercase tracking-widest mt-1">OS v2.0</span>
                </div>
            </a>
        </div>
        
        <div class="flex-1 overflow-y-auto py-6 px-4 custom-scrollbar bg-white grid-pattern">
            <?php 
                // Ensure profile picture is loaded
                if (!isset($_SESSION['profile_picture'])) {
                    global $db;
                    if (isset($_SESSION['user_id']) && $db) {
                        $stmt = $db->prepare("SELECT profile_picture FROM user_profiles WHERE user_id = ?");
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $res = $stmt->get_result()->fetch_assoc();
                        $_SESSION['profile_picture'] = $res['profile_picture'] ?? '';
                    }
                }
            ?>
            <!-- User Info Badge -->
            <div class="mb-8 p-4 bg-yellow-400 border-3 border-black shadow-[4px_4px_0px_#000] relative group">
                <div class="flex items-center gap-3 relative z-10">
                    <div class="w-10 h-10 border-2 border-black bg-black flex items-center justify-center text-yellow-400 font-bold overflow-hidden shrink-0">
                        <?php if (!empty($_SESSION['profile_picture'])): ?>
                            <img src="<?php echo ASSETS_URL . '/uploads/profiles/' . $_SESSION['profile_picture']; ?>" alt="Profile" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[12px] font-black text-black truncate leading-none uppercase font-mono mb-1"><?php echo e($_SESSION['username'] ?? 'User'); ?></p>
                        <div class="inline-block px-1.5 py-0.5 bg-black text-[#facc15] text-[8px] font-bold uppercase tracking-widest">
                            <?php echo str_replace('_', ' ', $_SESSION['role'] ?? 'Guest'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Label -->
            <div class="px-2 mb-4 flex items-center gap-2 opacity-50">
                <i class="fas fa-terminal text-[10px]"></i>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] font-mono">System_Menu</p>
            </div>

            <!-- Navigation -->
            <nav class="space-y-2">
                <?php echo $sidebar_menu ?? ''; ?>
            </nav>
        </div>
        
        <!-- Bottom Actions -->
        <div class="p-4 border-t-4 border-black space-y-2 bg-white">
            <a href="<?php echo BASE_URL; ?>/modules/auth/profile.php" class="flex items-center gap-3 px-4 py-3 border-2 border-transparent hover:border-black hover:bg-yellow-50 text-black transition-all group">
                <i class="fas fa-id-badge text-sm group-hover:text-black transition-colors"></i>
                <span class="text-[10px] font-black uppercase tracking-widest font-mono">My Profile</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/modules/auth/logout.php" class="flex items-center gap-3 px-4 py-3 border-2 border-transparent hover:border-black hover:bg-black hover:text-white text-red-600 transition-all group">
                <i class="fas fa-power-off text-sm"></i>
                <span class="text-[10px] font-black uppercase tracking-widest font-mono">Logout</span>
            </a>
        </div>
    </aside>

    <?php
    // Quick Links Logic
    $role = $_SESSION['role'] ?? 'guest';
    $quick_links = [];
    // ... (Keep existing quick links logic) ...
    // Re-implementing simplified generic array to save space in this response, utilizing the existing logic structure
    switch ($role) {
        case 'student':
            $quick_links = [
                ['label' => 'Dashboard', 'url' => 'modules/student/dashboard.php', 'icon' => 'fa-home'],
                ['label' => 'My Courses', 'url' => 'modules/student/my-courses.php', 'icon' => 'fa-book'],
                ['label' => 'Assignments', 'url' => 'modules/student/assignments.php', 'icon' => 'fa-tasks'],
                ['label' => 'Results', 'url' => 'modules/student/results.php', 'icon' => 'fa-chart-bar'],
                ['label' => 'Routine', 'url' => 'modules/student/routine.php', 'icon' => 'fa-calendar-alt'],
            ]; break;
        case 'teacher':
             $quick_links = [
                 ['label' => 'Dashboard', 'url' => 'modules/teacher/dashboard.php', 'icon' => 'fa-home'],
                 ['label' => 'Classes', 'url' => 'modules/teacher/my-courses.php', 'icon' => 'fa-chalkboard-teacher'],
                 ['label' => 'Assignments', 'url' => 'modules/teacher/assignments.php', 'icon' => 'fa-tasks'],
                 ['label' => 'Attendance', 'url' => 'modules/teacher/attendance.php', 'icon' => 'fa-clipboard-check'],
                 ['label' => 'Marks', 'url' => 'modules/teacher/marks-entry.php', 'icon' => 'fa-edit'],
             ]; break;
        case 'admin':
             $quick_links = [
                 ['label' => 'Dashboard', 'url' => 'modules/admin/dashboard.php', 'icon' => 'fa-home'],
                 ['label' => 'Students', 'url' => 'modules/admin/students.php', 'icon' => 'fa-user-graduate'],
                 ['label' => 'Faculty', 'url' => 'modules/admin/teachers.php', 'icon' => 'fa-chalkboard-teacher'],
                 ['label' => 'Courses', 'url' => 'modules/admin/courses.php', 'icon' => 'fa-book'],
             ]; break;
        case 'super_admin':
             $quick_links = [
                 ['label' => 'Dashboard', 'url' => 'modules/super_admin/dashboard.php', 'icon' => 'fa-home'],
                 ['label' => 'Departments', 'url' => 'modules/super_admin/departments.php', 'icon' => 'fa-sitemap'],
                 ['label' => 'Users', 'url' => 'modules/super_admin/users.php', 'icon' => 'fa-users'],
             ]; break;
    }
    
    // Notifications Logic
    $notifications = get_user_notifications($_SESSION['user_id'] ?? 0, 10);
    $unread_count = get_unread_count($_SESSION['user_id'] ?? 0);
    ?>
    
    <!-- Main Content -->
    <div id="main-content" class="flex flex-col min-h-screen bg-[#F8FAFC] transition-all duration-300">
        <!-- Header -->
        <header class="sticky top-0 z-30 bg-white border-b-4 border-black h-20">
            <div class="px-4 sm:px-6 lg:px-8 h-full flex items-center justify-between gap-4">
                
                <!-- Toggle -->
                <div class="flex items-center gap-4">
                    <button data-sidebar-toggle 
                            class="p-2.5 text-black border-3 border-black bg-yellow-400 hover:bg-black hover:text-yellow-400 transition-all active:translate-y-0.5 shadow-[4px_4px_0px_#000]">
                        <i class="fas fa-bars-staggered text-xl"></i>
                    </button>
                </div>

                <!-- Quick Links Strip -->
                <div class="hidden md:flex flex-1 justify-center px-4">
                    <nav class="flex items-center gap-2 p-1 bg-white border-2 border-dashed border-black/20 rounded-full">
                        <?php 
                        $current_path = $_SERVER['PHP_SELF'];
                        foreach ($quick_links as $link): 
                            $isActive = strpos($current_path, $link['url']) !== false;
                        ?>
                            <a href="<?php echo BASE_URL . '/' . $link['url']; ?>" 
                               class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest transition-all <?php echo $isActive ? 'bg-black text-white' : 'text-slate-500 hover:text-black hover:bg-slate-100'; ?>">
                                <?php echo $link['label']; ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-4">
                    <!-- Notifications -->
                    <div class="relative">
                        <button id="notif-btn" 
                                class="w-11 h-11 flex items-center justify-center border-3 border-black bg-white hover:bg-yellow-400 transition-all shadow-[4px_4px_0px_#000] active:shadow-none active:translate-x-[4px] active:translate-y-[4px]">
                            <i class="fas fa-bell text-lg"></i>
                            <?php if ($unread_count > 0): ?>
                                <span id="notif-badge" class="absolute -top-2 -right-2 px-1.5 py-0.5 bg-red-600 text-white text-[9px] font-black border-2 border-black">
                                    <?php echo $unread_count; ?>
                                </span>
                            <?php endif; ?>
                        </button>
                        
                        <!-- Notif Dropdown -->
                        <div id="notif-dropdown" class="hidden absolute top-14 right-0 w-80 md:w-96 bg-white border-4 border-black shadow-[8px_8px_0px_#000] z-50 flex flex-col">
                            <div class="p-4 bg-yellow-400 border-b-4 border-black flex justify-between items-center">
                                <span class="font-black uppercase text-xs">System Signals</span>
                                <button onclick="markAllRead()" class="text-[9px] font-bold underline hover:text-white">CLEAR ALL</button>
                            </div>
                            <div class="max-h-[400px] overflow-y-auto" id="notif-list">
                                <?php if (count($notifications) > 0): ?>
                                    <?php foreach ($notifications as $n): ?>
                                        <div id="notif-<?php echo $n['type']; ?>-<?php echo $n['ref_id']; ?>" 
                                             class="p-4 border-b-2 border-black/10 hover:bg-yellow-50 cursor-pointer transition-colors group"
                                             onclick="markRead('<?php echo $n['type']; ?>', <?php echo $n['ref_id']; ?>, '<?php echo $n['url']; ?>')">
                                            <div class="flex gap-3">
                                                <div class="mt-1">
                                                    <?php if (!$n['is_read']): ?>
                                                        <div class="w-2 h-2 bg-red-600 border border-black rounded-full"></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <p class="text-xs font-black uppercase text-black leading-tight mb-1 group-hover:underline"><?php echo e($n['title']); ?></p>
                                                    <p class="text-[10px] text-slate-500 font-mono mb-1"><?php echo $n['time_ago']; ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="p-8 text-center text-slate-400 text-xs font-mono">NO SIGNALS DETECTED</div>
                                <?php endif; ?>
                            </div>
                            <div class="px-4 py-3 border-t-2 border-black bg-slate-50 text-center hover:bg-yellow-400 transition-colors">
                                <a href="<?php echo BASE_URL . '/modules/' . $_SESSION['role'] . '/notifications.php'; ?>" class="block text-[10px] font-black uppercase tracking-widest">
                                    VIEW ARCHIVE
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="relative flex items-center gap-3 pl-4 pr-2 py-1.5 border-3 border-black bg-white shadow-[4px_4px_0px_#000]">
                        <a href="<?php echo BASE_URL; ?>/modules/auth/profile.php" class="text-xs font-black uppercase hidden sm:block hover:underline decoration-2 decoration-yellow-400 underline-offset-4 transition-all">
                            <?php echo e(explode(' ', $_SESSION['username'])[0]); ?>
                        </a>
                        <button id="user-menu-button" 
                                class="w-6 h-6 bg-yellow-400 text-black flex items-center justify-center font-bold text-[10px] border border-black hover:bg-black hover:text-white transition-colors">
                            <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
                        </button>
                        
                        <div id="user-menu" class="hidden absolute top-14 right-0 w-56 bg-white border-4 border-black shadow-[8px_8px_0px_#000] z-50">
                            <a href="<?php echo BASE_URL; ?>/modules/auth/profile.php" class="block px-4 py-3 text-xs font-black uppercase border-b-2 border-black hover:bg-yellow-400 transition-colors">
                                <i class="fas fa-user-circle mr-2"></i> Profile
                            </a>
                            <a href="<?php echo BASE_URL; ?>/modules/auth/change-password.php" class="block px-4 py-3 text-xs font-black uppercase border-b-2 border-black hover:bg-yellow-400 transition-colors">
                                <i class="fas fa-key mr-2"></i> Security
                            </a>
                            <a href="<?php echo BASE_URL; ?>/modules/auth/logout.php" class="block px-4 py-3 text-xs font-black uppercase hover:bg-red-600 hover:text-white transition-colors">
                                <i class="fas fa-power-off mr-2"></i> Logout
                            </a>
                        </div>
                    </div>

                    <!-- Direct Logout Button (Small) -->
                    <a href="<?php echo BASE_URL; ?>/modules/auth/logout.php" 
                       class="w-10 h-10 flex items-center justify-center border-3 border-black bg-rose-500 text-white hover:bg-black transition-all shadow-[4px_4px_0px_#000] active:shadow-none active:translate-x-[4px] active:translate-y-[4px]"
                       title="Logout">
                        <i class="fas fa-power-off text-xs"></i>
                    </a>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8 z-0">
            <div class="w-full max-w-[1440px] mx-auto min-h-[calc(100vh-160px)] flex flex-col">
                <div class="flex-1">
                    <?php display_flash(); ?>
                    <?php echo $content ?? ''; ?>
                </div>
            </div>
        </main>

        <!-- Footer Navigation -->
        <?php include __DIR__ . '/../footer-nav.php'; ?>
    </div>

    <!-- Scripts -->
    <script src="<?php echo ASSETS_URL; ?>/js/global_modal.js"></script>
    <script src="<?php echo ASSETS_URL; ?>/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        // Notification Logic (Requires PHP BASE_URL)
        async function markRead(type, id, url) {
            try {
                await fetch('<?php echo BASE_URL; ?>/api/mark_read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type, id })
                });
                if (url && url !== '#') window.location.href = url;
            } catch (e) { console.error(e); }
        }
        
        async function markAllRead() {
             try {
                await fetch('<?php echo BASE_URL; ?>/api/mark_read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'mark_all' })
                });
                window.location.reload();
            } catch (e) { console.error(e); }
        }
    </script>
    <?php echo $extra_scripts ?? ''; ?>
</body>
</html>
