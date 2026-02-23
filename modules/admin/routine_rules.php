<?php
/**
 * Routine Rules Configuration
 * "The BRAIN" of the DRAC system.
 */
require_once __DIR__ . '/../../config/settings.php';
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Expected keys
    $keys = ['strict_mode', 'lab_priority', 'teacher_gap_check', 'max_daily_classes'];
    
    foreach ($keys as $key) {
        $val = isset($_POST[$key]) ? $_POST[$key] : '0';
        // Insert or Update
        $stmt = $db->prepare("INSERT INTO routine_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param("sss", $key, $val, $val);
        $stmt->execute();
    }
    $_SESSION['success'] = "Protocols Updated.";
    header("Location: routine_rules.php");
    exit;
}

// Load Settings
$settings = [];
$res = $db->query("SELECT * FROM routine_settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
// Defaults
$s_strict = $settings['strict_mode'] ?? '0';
$s_lab = $settings['lab_priority'] ?? '1';
$s_gap = $settings['teacher_gap_check'] ?? '0';
$s_max = $settings['max_daily_classes'] ?? '3';

$page_title = "Routine Rules";
ob_start();
?>

<!-- Header -->
<div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white mb-8">
    <div class="relative z-10">
        <div class="flex items-center gap-3 mb-2">
            <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">System Core</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                Rule Engine
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">Routine <span class="text-black">Protocols</span></h1>
    </div>
</div>

<form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-8">
    
    <!-- Strict Mode Card -->
    <div class="os-card p-8 bg-black text-white border-2 border-black shadow-[8px_8px_0px_#e2e8f0] relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 text-8xl text-white opacity-5 rotate-12 group-hover:rotate-0 transition-transform">
            <i class="fas fa-gavel"></i>
        </div>
        <div class="relative z-10">
            <div class="flex justify-between items-start mb-6">
                <h3 class="text-xl font-black uppercase tracking-widest text-yellow-400">Strict Mode</h3>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="strict_mode" value="1" class="sr-only peer" <?php echo ($s_strict == '1') ? 'checked' : ''; ?>>
                    <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none ring-2 ring-white/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                </label>
            </div>
            <p class="text-xs text-slate-400 font-mono leading-relaxed mb-4">
                >> WHEN ACTIVE:<br>
                Only allows scheduling in pre-defined [Time Slots].<br>
                Arbitrary time entries will be REJECTED by the kernel.
            </p>
            <div class="p-3 border border-white/20 bg-white/5 text-[10px] uppercase font-bold text-red-300">
                <i class="fas fa-exclamation-triangle mr-1"></i> Warning: Enabling this may block manual overrides.
            </div>
        </div>
    </div>

    <!-- Optimization Rules -->
    <div class="os-card p-8 bg-white border-2 border-black shadow-[8px_8px_0px_#000]">
        <h3 class="text-lg font-black uppercase tracking-widest mb-6 border-b-2 border-black pb-2">Optimization Logic</h3>
        
        <div class="space-y-6">
            <!-- Lab Priority -->
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="font-black text-sm uppercase">Lab Priority Allocation</h4>
                    <p class="text-[10px] text-slate-500 font-mono mt-1">Allocates Labs/3.0 Credits BEFORE Theory courses.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="lab_priority" value="1" class="sr-only peer" <?php echo ($s_lab == '1') ? 'checked' : ''; ?>>
                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-black"></div>
                </label>
            </div>

            <!-- Teacher Gap -->
            <div class="flex items-center justify-between opacity-50 cursor-not-allowed" title="Coming in v2.0">
                <div>
                    <h4 class="font-black text-sm uppercase">Teacher Fatigue Guard</h4>
                    <p class="text-[10px] text-slate-500 font-mono mt-1">Enforce 30m break after 2 consecutive classes.</p>
                </div>
                <label class="relative inline-flex items-center">
                    <input type="checkbox" name="teacher_gap_check" value="1" class="sr-only peer" disabled>
                    <div class="w-9 h-5 bg-gray-200 rounded-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4"></div>
                </label>
            </div>
            
            <!-- Default Max Load -->
             <div>
                <label class="font-black text-sm uppercase block mb-2">Global Daily Max (Classes)</label>
                <input type="number" name="max_daily_classes" value="<?php echo $s_max; ?>" class="w-full border-2 border-black p-2 font-bold text-sm focus:outline-none focus:bg-yellow-50">
                <p class="text-[10px] text-slate-500 font-mono mt-1">Can be overridden per teacher in [Personnel] module.</p>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="md:col-span-2 flex justify-end">
        <button type="submit" class="bg-black text-white px-8 py-4 text-xs font-black uppercase tracking-widest hover:bg-green-600 transition-colors shadow-[4px_4px_0px_#000] border-2 border-black active:translate-y-1 active:shadow-none">
            <i class="fas fa-save mr-2"></i> Update Protocols
        </button>
    </div>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
