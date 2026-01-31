<?php
/**
 * Marks Verification
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('admin');

$page_title = 'Marks Verification';
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

$view = $_GET['view'] ?? 'list';
$offering_id = $_GET['offering_id'] ?? null;
$component_id = $_GET['component_id'] ?? null;

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request');
        redirect(BASE_URL . '/modules/admin/marks-verification.php');
    }

    $action = $_POST['action'] ?? '';
    
    if ($action === 'verify_batch') {
        $mark_ids = $_POST['mark_ids'] ?? [];
        if (!empty($mark_ids)) {
            $ids_str = implode(',', array_map('intval', $mark_ids));
            $db->query("UPDATE student_marks sm 
                JOIN enrollments e ON sm.enrollment_id = e.id 
                JOIN course_offerings co ON e.course_offering_id = co.id 
                JOIN courses c ON co.course_id = c.id 
                SET sm.status = 'verified', sm.verified_by = $user_id 
                WHERE sm.id IN ($ids_str) AND c.department_id IN ($dept_id_list) AND sm.status = 'submitted'");
                
            create_audit_log('verify_marks_batch', 'student_marks', null, null, ['count' => count($mark_ids)]);
            set_flash('success', 'Selected marks verified successfully');
        }
        redirect(BASE_URL . "/modules/admin/marks-verification.php?view=detail&offering_id=$offering_id&component_id=$component_id");
        
    } elseif ($action === 'reject_single') {
        $mark_id = intval($_POST['mark_id']);
        $remarks = sanitize_input($_POST['remarks']);
        
        $check = $db->query("SELECT sm.id FROM student_marks sm
            JOIN enrollments e ON sm.enrollment_id = e.id
            JOIN course_offerings co ON e.course_offering_id = co.id
            JOIN courses c ON co.course_id = c.id
            WHERE sm.id = $mark_id AND c.department_id IN ($dept_id_list)");
            
        if ($check->num_rows > 0) {
            $stmt = $db->prepare("UPDATE student_marks SET status = 'correction_requested', remarks = ? WHERE id = ?");
            $stmt->bind_param("si", $remarks, $mark_id);
            $stmt->execute();
            create_audit_log('reject_marks', 'student_marks', $mark_id);
            set_flash('success', 'Correction requested');
        }
        redirect(BASE_URL . "/modules/admin/marks-verification.php?view=detail&offering_id=$offering_id&component_id=$component_id");
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
            <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Audit</span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                Verification Protocol
            </span>
        </div>
        <h1 class="text-3xl font-black uppercase tracking-tighter">Marks <span class="text-black">Verification</span></h1>
    </div>
    
    <?php if ($view === 'detail'): ?>
        <div class="relative z-10">
            <a href="?" class="btn-os bg-white text-black border-black hover:bg-black hover:text-white hover:border-black flex items-center gap-2">
                 <i class="fas fa-arrow-left"></i> Hub Manifest
            </a>
        </div>
    <?php endif; ?>
</div>

<?php if ($view === 'list'): ?>
    <div class="os-card p-0 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-black text-white text-[10px] font-black uppercase tracking-widest border-b-2 border-black">
                        <th class="px-6 py-4 text-left">Academic Target (Course)</th>
                        <th class="px-6 py-4 text-left">Component</th>
                        <th class="px-6 py-4 text-left">Sector</th>
                        <th class="px-6 py-4 text-left">Pending Queue</th>
                        <th class="px-6 py-4 text-right">Operations</th>
                    </tr>
                </thead>
                <tbody class="divide-y-2 divide-black">
                    <?php
                    $list_query = "SELECT c.course_code, c.course_name, co.section, co.id as off_id, 
                        ac.component_name, ac.id as comp_id, COUNT(sm.id) as pending_count
                        FROM student_marks sm
                        JOIN assessment_components ac ON sm.assessment_component_id = ac.id
                        JOIN enrollments e ON sm.enrollment_id = e.id
                        JOIN course_offerings co ON e.course_offering_id = co.id
                        JOIN courses c ON co.course_id = c.id
                        WHERE c.department_id IN ($dept_id_list) AND sm.status = 'submitted'
                        GROUP BY co.id, ac.id
                        ORDER BY c.course_code ASC";
                    
                    $list_res = $db->query($list_query);
                    if ($list_res->num_rows > 0):
                        while ($row = $list_res->fetch_assoc()):
                    ?>
                        <tr class="hover:bg-yellow-50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="text-[11px] font-black text-black uppercase transition-colors"><?php echo e($row['course_code']); ?></div>
                                <div class="text-[9px] font-bold text-slate-500 uppercase italic leading-tight"><?php echo e($row['course_name']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-white border border-black rounded-none text-[9px] font-black uppercase italic tracking-widest text-black"><?php echo e($row['component_name']); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-[10px] font-black text-black uppercase italic">SEC: <?php echo e($row['section']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-yellow-400 text-black border border-black rounded-none text-[9px] font-black italic">
                                    <?php echo $row['pending_count']; ?> Signals Pending
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="?view=detail&offering_id=<?php echo $row['off_id']; ?>&component_id=<?php echo $row['comp_id']; ?>" class="btn-os bg-black text-white hover:bg-white hover:text-black border-black text-xs">
                                    Audit Component
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <i class="fas fa-clipboard-check text-4xl text-slate-300 mb-2 block"></i>
                                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">All academic metrics are verified and synchronized</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($view === 'detail' && $offering_id && $component_id): 
    $info_q = $db->query("SELECT c.course_code, c.course_name, co.section, ac.component_name, ac.weightage 
        FROM course_offerings co 
        JOIN courses c ON co.course_id = c.id
        JOIN assessment_components ac ON ac.id = $component_id
        WHERE co.id = $offering_id");
    $info = $info_q->fetch_assoc();
?>

    <div class="os-card p-8 bg-white mb-8 border-2 border-black shadow-[4px_4px_0px_#000]">
        <div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 italic">Detailed Audit Mode</p>
            <h2 class="text-3xl font-black text-black uppercase tracking-tighter leading-none">
                <?php echo e($info['course_code']); ?> — <?php echo e($info['component_name']); ?>
            </h2>
            <div class="flex items-center gap-4 mt-4">
                <span class="text-[10px] font-black text-black uppercase italic border border-black px-2 py-1">SEC: <?php echo e($info['section']); ?></span>
                <span class="text-[10px] font-black text-black uppercase italic border border-black px-2 py-1">MT WEIGHT: <?php echo e($info['weightage']); ?>%</span>
            </div>
        </div>
    </div>

    <form method="POST" action="">
        <?php csrf_field(); ?>
        <input type="hidden" name="action" value="verify_batch">
        
        <div class="os-card p-0 bg-white">
            <div class="px-6 py-4 bg-black flex justify-between items-center text-white border-b-2 border-black">
                <h3 class="text-[11px] font-black uppercase tracking-widest italic">Student Performance Matrix</h3>
                <div class="flex gap-4">
                    <button type="button" onclick="selectAll()" class="text-[9px] font-black uppercase tracking-widest italic text-yellow-400 hover:text-white transition-colors">Select All</button>
                    <span class="text-slate-500">|</span>
                    <button type="button" onclick="deselectAll()" class="text-[9px] font-black uppercase tracking-widest italic text-red-400 hover:text-white transition-colors">Deselect All</button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-white text-black text-[9px] font-black uppercase tracking-widest border-b-2 border-black">
                            <th class="px-6 py-4 text-left w-10 border-r-2 border-black">
                                <input type="checkbox" id="master-check" onclick="toggleAll(this)" class="w-4 h-4 text-black border-2 border-black rounded-none focus:ring-0 focus:ring-offset-0">
                            </th>
                            <th class="px-6 py-4 text-left border-r-2 border-black">Internal ID</th>
                            <th class="px-6 py-4 text-left border-r-2 border-black">Personnel Designation</th>
                            <th class="px-6 py-4 text-left border-r-2 border-black">Performance Metric</th>
                            <th class="px-6 py-4 text-left border-r-2 border-black">Operator Notes</th>
                            <th class="px-6 py-4 text-right">Protocol</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-2 divide-black">
                        <?php
                        $det_query = "SELECT sm.id, sm.marks_obtained, sm.remarks, sm.status,
                            s.user_id, s.student_id, up.first_name, up.last_name
                            FROM student_marks sm
                            JOIN enrollments e ON sm.enrollment_id = e.id
                            JOIN students s ON e.student_id = s.id
                            JOIN user_profiles up ON s.user_id = up.user_id
                            WHERE e.course_offering_id = $offering_id 
                            AND sm.assessment_component_id = $component_id
                            AND sm.status = 'submitted'
                            ORDER BY s.student_id";
                        
                        $det_res = $db->query($det_query);
                        if ($det_res->num_rows > 0):
                            while ($row = $det_res->fetch_assoc()):
                        ?>
                            <tr class="hover:bg-yellow-50 transition-colors group">
                                <td class="px-6 py-4 border-r-2 border-black">
                                    <input type="checkbox" name="mark_ids[]" value="<?php echo $row['id']; ?>" class="mark-check w-4 h-4 text-black border-2 border-black rounded-none focus:ring-0 focus:ring-offset-0">
                                </td>
                                <td class="px-6 py-4 text-[11px] font-black text-black italic border-r-2 border-black"><?php echo e($row['student_id']); ?></td>
                                <td class="px-6 py-4 border-r-2 border-black">
                                    <div class="text-[11px] font-black text-black uppercase italic transition-colors"><?php echo e($row['first_name'] . ' ' . $row['last_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 border-r-2 border-black">
                                    <span class="px-2 py-1 bg-black text-white rounded-none border border-black text-[10px] font-black italic tracking-widest">
                                        <?php echo e($row['marks_obtained']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-[10px] font-bold text-slate-500 italic border-r-2 border-black"><?php echo e($row['remarks'] ?: 'No operational notes'); ?></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="<?php echo BASE_URL; ?>/modules/admin/students.php?action=view&id=<?php echo $row['user_id']; ?>" target="_blank" class="w-8 h-8 flex items-center justify-center border-2 border-black bg-white hover:bg-black hover:text-white transition-all shadow-[2px_2px_0px_#000]" title="View Student Dossier">
                                            <i class="fas fa-eye text-xs"></i>
                                        </a>
                                        <button type="button" onclick="rejectMark(<?php echo $row['id']; ?>)" class="w-8 h-8 flex items-center justify-center border-2 border-black bg-white hover:bg-red-600 hover:text-white transition-all shadow-[2px_2px_0px_#000]" title="Initiate Correction">
                                            <i class="fas fa-undo text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t-2 border-black flex justify-end">
                <button type="submit" class="btn-os bg-green-600 text-white hover:bg-green-700 border-black flex items-center gap-2">
                    <i class="fas fa-check-double"></i> Authorize Selected Assets
                </button>
            </div>
        </div>
    </form>
    
    <!-- Reject Modal Form (Hidden) -->
    <form id="reject-form" method="POST" action="" class="hidden">
        <?php csrf_field(); ?>
        <input type="hidden" name="action" value="reject_single">
        <input type="hidden" name="mark_id" id="reject_mark_id">
        <input type="hidden" name="remarks" id="reject_remarks">
    </form>

    <script>
    function toggleAll(source) {
        document.querySelectorAll('.mark-check').forEach(cb => cb.checked = source.checked);
        updateRowStyles();
    }
    function selectAll() {
        document.querySelectorAll('.mark-check').forEach(cb => cb.checked = true);
        document.getElementById('master-check').checked = true;
        updateRowStyles();
    }
    function deselectAll() {
        document.querySelectorAll('.mark-check').forEach(cb => cb.checked = false);
        document.getElementById('master-check').checked = false;
        updateRowStyles();
    }
    function updateRowStyles() {
        document.querySelectorAll('.mark-check').forEach(cb => {
            const row = cb.closest('tr');
            if(cb.checked) {
                row.classList.add('bg-yellow-50');
            } else {
                row.classList.remove('bg-yellow-50');
            }
        });
    }
    function rejectMark(id) {
        const remarks = prompt("INITIATE CORRECTION PROTOCOL: Please enter reason for rejection:");
        if (remarks !== null && remarks.trim() !== "") {
            document.getElementById('reject_mark_id').value = id;
            document.getElementById('reject_remarks').value = remarks;
            document.getElementById('reject-form').submit();
        }
    }
    
    // Add visual feedback on row selection
    document.querySelectorAll('.mark-check').forEach(cb => {
        cb.addEventListener('change', function() {
            updateRowStyles();
        });
    });
    </script>
    
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
