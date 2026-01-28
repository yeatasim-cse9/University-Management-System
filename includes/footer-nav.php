<?php
/**
 * Global Dashboard Footer
 * ACADEMIX - Premium Academic Management System
 */
$role = $_SESSION['role'] ?? 'student';
$dashboard_url = BASE_URL . "/modules/{$role}/dashboard.php";
?>

<footer class="mt-8 pt-8 pb-6 border-t-4 border-black bg-black text-white relative overflow-hidden">
    <div class="absolute inset-0 opacity-[0.05] grid-bg pointer-events-none"></div>
    <div class="relative z-10 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8 px-6 lg:px-12">
        <!-- Brand Column -->
        <div class="space-y-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-yellow-400 border-2 border-white flex items-center justify-center text-black shadow-[4px_4px_0px_rgba(255,255,255,0.2)]">
                    <i class="fas fa-graduation-cap text-xl"></i>
                </div>
                <div>
                    <span class="text-2xl font-black text-white tracking-tighter uppercase font-outfit leading-none block">ACADEMIX_OS</span>
                    <span class="text-[9px] font-black text-yellow-500 uppercase tracking-[0.3em] mt-1 block">University // Barisal</span>
                </div>
            </div>
            <p class="text-[11px] text-slate-400 font-mono leading-relaxed max-w-[240px] uppercase">
                > INITIALIZING EDUCATIONAL PROTOCOLS...<br>
                > STATUS: <span class="text-yellow-400">OPTIMIZED</span><br>
                ---<br>
                Pioneering the future of educational infrastructure.
            </p>
            <div class="flex gap-3">
                <a href="#" class="w-10 h-10 bg-white/10 border border-white/20 text-white flex items-center justify-center hover:bg-yellow-400 hover:text-black transition-all shadow-[4px_4px_0px_rgba(0,0,0,0.5)]"><i class="fab fa-facebook-f text-xs"></i></a>
                <a href="#" class="w-10 h-10 bg-white/10 border border-white/20 text-white flex items-center justify-center hover:bg-yellow-400 hover:text-black transition-all shadow-[4px_4px_0px_rgba(0,0,0,0.5)]"><i class="fab fa-twitter text-xs"></i></a>
                <a href="#" class="w-10 h-10 bg-white/10 border border-white/20 text-white flex items-center justify-center hover:bg-yellow-400 hover:text-black transition-all shadow-[4px_4px_0px_rgba(0,0,0,0.5)]"><i class="fab fa-github text-xs"></i></a>
            </div>
        </div>

        <!-- Dynamic Navigation -->
        <div>
            <h4 class="text-[11px] font-black text-yellow-500 uppercase tracking-[0.3em] mb-8 flex items-center gap-2">
                TACTICAL_SECTORS
                <span class="flex-1 h-[1px] bg-white/20"></span>
            </h4>
            <ul class="space-y-4">
                <li><a href="<?php echo $dashboard_url; ?>" class="text-[11px] text-slate-300 font-bold hover:text-yellow-400 hover:translate-x-1 inline-block transition-all uppercase tracking-widest border-l-2 border-transparent hover:border-yellow-400 pl-0 hover:pl-3">Command Center</a></li>
                <?php if ($role === 'student'): ?>
                    <li><a href="<?php echo BASE_URL; ?>/modules/student/routine.php" class="text-[11px] text-slate-300 font-bold hover:text-yellow-400 hover:translate-x-1 inline-block transition-all uppercase tracking-widest border-l-2 border-transparent hover:border-yellow-400 pl-0 hover:pl-3">Temporal Grid</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/modules/student/my-courses.php" class="text-[11px] text-slate-300 font-bold hover:text-yellow-400 hover:translate-x-1 inline-block transition-all uppercase tracking-widest border-l-2 border-transparent hover:border-yellow-400 pl-0 hover:pl-3">Academic Hub</a></li>
                <?php elseif ($role === 'teacher'): ?>
                    <li><a href="<?php echo BASE_URL; ?>/modules/teacher/my-courses.php" class="text-[11px] text-slate-300 font-bold hover:text-yellow-400 hover:translate-x-1 inline-block transition-all uppercase tracking-widest border-l-2 border-transparent hover:border-yellow-400 pl-0 hover:pl-3">Course Registry</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/modules/teacher/attendance.php" class="text-[11px] text-slate-300 font-bold hover:text-yellow-400 hover:translate-x-1 inline-block transition-all uppercase tracking-widest border-l-2 border-transparent hover:border-yellow-400 pl-0 hover:pl-3">Attendance Logs</a></li>
                <?php endif; ?>
                <li><a href="<?php echo BASE_URL; ?>/modules/auth/profile.php" class="text-[11px] text-slate-300 font-bold hover:text-yellow-400 hover:translate-x-1 inline-block transition-all uppercase tracking-widest border-l-2 border-transparent hover:border-yellow-400 pl-0 hover:pl-3">Identity Hub</a></li>
            </ul>
        </div>

        <!-- Resources -->
        <div>
            <h4 class="text-[11px] font-black text-yellow-500 uppercase tracking-[0.3em] mb-8 flex items-center gap-2">
                RESOURCES
                <span class="flex-1 h-[1px] bg-white/20"></span>
            </h4>
            <ul class="space-y-4">
                <li><a href="#" class="text-[11px] text-slate-300 font-bold hover:text-yellow-400 hover:translate-x-1 inline-block transition-all uppercase tracking-widest border-l-2 border-transparent hover:border-yellow-400 pl-0 hover:pl-3">Technical Support</a></li>
                <li><a href="#" class="text-[11px] text-slate-300 font-bold hover:text-yellow-400 hover:translate-x-1 inline-block transition-all uppercase tracking-widest border-l-2 border-transparent hover:border-yellow-400 pl-0 hover:pl-3">Terminal Guide</a></li>
                <li><a href="#" class="text-[11px] text-slate-300 font-bold hover:text-yellow-400 hover:translate-x-1 inline-block transition-all uppercase tracking-widest border-l-2 border-transparent hover:border-yellow-400 pl-0 hover:pl-3">Global Protocol</a></li>
            </ul>
        </div>

        <!-- Terminal Status -->
        <div>
            <div class="bg-yellow-400 p-8 border-4 border-black shadow-[8px_8px_0px_rgba(255,255,255,0.1)] group">
                <h4 class="text-[11px] font-black text-black uppercase tracking-[0.3em] mb-4 border-b-2 border-black/20 pb-2">Terminal Status</h4>
                <div class="space-y-4 text-black">
                    <div class="flex items-center gap-3">
                        <span class="w-3 h-3 bg-black animate-pulse"></span>
                        <span class="text-[12px] font-black uppercase tracking-widest">Link Active</span>
                    </div>
                    <div class="pt-4 border-t-2 border-black/10">
                        <p class="text-[9px] font-black opacity-50 uppercase tracking-widest mb-1">Current Sync</p>
                        <p class="text-[12px] font-black uppercase font-mono"><?php echo date('Y-m-d H:i'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Final Footer -->
    <div class="pt-8 border-t-2 border-white/10 flex flex-col md:flex-row items-center justify-between gap-6 px-6 lg:px-12">
        <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] text-center md:text-left font-mono">
            &copy; <?php echo date('Y'); ?> [ <span class="text-white"><?php echo UNIVERSITY_NAME; ?></span> ] // ALL SYSTEMS NOMINAL
        </p>
        <div class="flex items-center gap-4">
            <div class="px-3 py-1 border-2 border-white/20 text-[9px] font-black text-yellow-400 uppercase tracking-widest">V4.2.0-STABLE</div>
            <div class="w-22 h-1 stripe-accent"></div>
            <span class="text-[10px] font-black text-white uppercase tracking-widest italic font-outfit">ACADEMIX_OS</span>
        </div>
    </div>
</footer>



