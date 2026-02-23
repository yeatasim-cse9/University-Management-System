<?php
/**
 * Exam Eligibility Check
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('admin');

$page_title = 'Exam Eligibility';
$user_id = get_current_user_id();

// Get admin's department(s)
$stmt = $db->prepare("SELECT d.id FROM departments d JOIN department_admins da ON d.id = da.department_id WHERE da.user_id = ? AND d.deleted_at IS NULL");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$department_ids = [];
while ($row = $result->fetch_assoc()) {
    $department_ids[] = $row['id'];
}
$dept_id_list = !empty($department_ids) ? implode(',', $department_ids) : '0';

// Get Attendance Setting
$req_res = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'attendance_required_percentage'");
$required_percentage = $req_res->num_rows > 0 ? intval($req_res->fetch_assoc()['setting_value']) : 75;

$offering_id = $_GET['offering_id'] ?? null;

// Fetch Active Offerings
$offerings = [];
$offerings_res = $db->query("SELECT co.id, c.course_code, c.course_name, co.section, s.name as semester_name 
    FROM course_offerings co 
    JOIN courses c ON co.course_id = c.id 
    JOIN semesters s ON co.semester_id = s.id 
    WHERE c.department_id IN ($dept_id_list) 
    ORDER BY s.start_date DESC, c.course_code ASC");
while ($row = $offerings_res->fetch_assoc()) {
    $offerings[] = $row;
}

$report_data = [];
$course_info = null;

if ($offering_id) {
    $check = $db->query("SELECT co.id, c.course_code, c.course_name, co.section 
        FROM course_offerings co 
        JOIN courses c ON co.course_id = c.id 
        WHERE co.id = $offering_id AND c.department_id IN ($dept_id_list)");
        
    if ($check->num_rows > 0) {
        $course_info = $check->fetch_assoc();
        
        $total_q = $db->query("SELECT COUNT(DISTINCT attendance_date) as total FROM attendance WHERE course_offering_id = $offering_id");
        $total_classes = $total_q->fetch_assoc()['total'];
        
        if ($total_classes > 0) {
            $stud_q = "SELECT s.student_id, up.first_name, up.last_name, 
                SUM(CASE WHEN a.status IN ('present', 'late') THEN 1 ELSE 0 END) as present_count
                FROM enrollments e
                JOIN students s ON e.student_id = s.id
                JOIN user_profiles up ON s.user_id = up.user_id
                LEFT JOIN attendance a ON e.id = a.enrollment_id
                WHERE e.course_offering_id = $offering_id
                GROUP BY s.id
                ORDER BY s.student_id";
            
            $stud_res = $db->query($stud_q);
            while ($row = $stud_res->fetch_assoc()) {
                $percentage = ($row['present_count'] / $total_classes) * 100;
                $row['percentage'] = round($percentage, 2);
                $row['eligible'] = $percentage >= $required_percentage;
                $report_data[] = $row;
            }
        }
    } else {
        set_flash('error', 'Permission denied');
        redirect(BASE_URL . '/modules/admin/exam-eligibility.php');
    }
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
            <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Compliance</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                Threshold: <?php echo $required_percentage; ?>%
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">Exam <span class="text-black">Eligibility</span></h1>
    </div>
</div>

<!-- Search/Filter -->
<div class="os-card p-6 bg-white mb-8">
    <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
        <div class="flex-1 w-full space-y-2">
            <label class="block text-[10px] font-black text-black uppercase tracking-widest italic">Select Operational Sector</label>
            <div class="relative">
                <select name="offering_id" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-xs text-black focus:outline-none focus:bg-yellow-50 transition-all uppercase appearance-none cursor-pointer" required>
                    <option value="">Scan Fleet for Assets...</option>
                    <?php foreach ($offerings as $o): ?>
                        <option value="<?php echo $o['id']; ?>" <?php echo $offering_id == $o['id'] ? 'selected' : ''; ?>>
                            <?php echo e($o['course_code'] . ' — ' . $o['course_name'] . ' (SEC ' . $o['section'] . ') — ' . $o['semester_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                    <i class="fas fa-chevron-down text-black text-xs"></i>
                </div>
            </div>
        </div>
        <button type="submit" class="w-full md:w-auto btn-os bg-black text-white border-black hover:bg-yellow-400 hover:text-black hover:border-black flex items-center gap-2">
            <i class="fas fa-satellite-dish"></i> Execute Scan
        </button>
    </form>
</div>

<?php if ($offering_id && $course_info): ?>
    <div class="space-y-4">
        <div class="flex items-center justify-between ml-1">
            <h2 class="text-sm font-black text-black uppercase tracking-widest italic leading-none">
                Asset Compliance Report: <span class="text-black bg-yellow-200 px-1"><?php echo e($course_info['course_code']); ?> (SEC <?php echo e($course_info['section']); ?>)</span>
            </h2>
            <div class="text-[10px] font-black text-slate-500 uppercase italic tracking-widest">
                Cycles: <?php echo $total_classes; ?>
            </div>
        </div>

        <div class="os-card p-0 bg-white">
            <?php if ($total_classes == 0): ?>
                <div class="p-12 text-center text-slate-500">
                    <i class="fas fa-database text-3xl mb-3 block"></i>
                    <div class="text-[10px] font-black uppercase tracking-widest italic">No attendance data clusters found for this sector</div>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-black text-white text-[10px] font-black uppercase tracking-widest border-b-2 border-black">
                                <th class="px-6 py-4 text-left">Internal ID</th>
                                <th class="px-6 py-4 text-left">Personnel Designation</th>
                                <th class="px-6 py-4 text-left">Adherence Count</th>
                                <th class="px-6 py-4 text-left">Adherence P%</th>
                                <th class="px-6 py-4 text-right">Operational Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y-2 divide-black">
                            <?php foreach ($report_data as $row): ?>
                                <tr class="hover:bg-yellow-50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="text-[11px] font-black text-black uppercase italic"><?php echo e($row['student_id']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-[11px] font-black text-black uppercase italic transition-colors"><?php echo e($row['first_name'] . ' ' . $row['last_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-[10px] font-black text-black uppercase italic"><?php echo $row['present_count']; ?> / <?php echo $total_classes; ?> <span class="text-[8px] text-slate-500 font-bold ml-1">UNITS</span></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-1 h-2 bg-white border border-black w-24">
                                                <div class="h-full <?php echo $row['eligible'] ? 'bg-green-600' : 'bg-red-600'; ?>" style="width: <?php echo $row['percentage']; ?>%"></div>
                                            </div>
                                            <span class="text-[10px] font-black text-black uppercase italic"><?php echo $row['percentage']; ?>%</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="px-2 py-1 border-2 border-black text-[9px] font-black uppercase tracking-widest italic <?php 
                                            echo $row['eligible'] ? 'bg-green-100 text-green-900' : 'bg-red-100 text-red-900'; 
                                        ?>">
                                            <?php echo $row['eligible'] ? 'AUTHORIZED' : 'RESTRICTED'; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t-2 border-black flex justify-end">
                    <button onclick="window.print()" class="btn-os bg-white text-black border-black hover:bg-black hover:text-white flex items-center gap-2">
                        <i class="fas fa-print"></i> Generate Hardcopy Log
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<style>
@media print {
    .no-print, nav, #sidebar, button, form { display: none !important; }
    body { background: white !important; }
    .os-card { box-shadow: none !important; border: 2px solid black !important; }
    .bg-black { background: black !important; color: white !important; }
    table { width: 100% !important; border-collapse: collapse !important; }
    th, td { border: 1px solid black !important; padding: 8px !important; }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
