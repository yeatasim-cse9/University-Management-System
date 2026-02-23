<?php
/**
 * Teacher Students List
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('teacher');

$page_title = 'My Students';
$user_id = get_current_user_id();

// Get Teacher ID
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$teacher_id = $teacher['id'];

// Get Search/Filter info
$search = sanitize_input($_GET['search'] ?? '');
$course_filter = intval($_GET['course_id'] ?? 0);

// Build Query
$query = "
    SELECT DISTINCT 
        s.id, s.student_id as matric_no, s.batch_year, s.current_semester,
        up.first_name, up.last_name, up.profile_picture, u.email,
        d.name as dept_name,
        GROUP_CONCAT(DISTINCT c.course_code ORDER BY c.course_code SEPARATOR ', ') as enrolled_courses
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN departments d ON s.department_id = d.id
    JOIN enrollments e ON s.id = e.student_id
    JOIN teacher_courses tc ON e.course_offering_id = tc.course_offering_id
    JOIN course_offerings co ON e.course_offering_id = co.id
    JOIN courses c ON co.course_id = c.id
    WHERE tc.teacher_id = $teacher_id
";

if ($course_filter) {
    $query .= " AND c.id = $course_filter";
}

if ($search) {
    $query .= " AND (
        s.student_id LIKE '%$search%' OR 
        up.first_name LIKE '%$search%' OR 
        up.last_name LIKE '%$search%'
    )";
}

$query .= " GROUP BY s.id ORDER BY s.student_id ASC";

$result = $db->query($query);
$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

// Get Teacher's Courses for Filter
$courses_q = "
    SELECT DISTINCT c.id, c.course_code 
    FROM courses c
    JOIN course_offerings co ON c.id = co.course_id
    JOIN teacher_courses tc ON co.id = tc.course_offering_id
    WHERE tc.teacher_id = $teacher_id
    ORDER BY c.course_code
";
$courses_res = $db->query($courses_q);
$courses = [];
while ($row = $courses_res->fetch_assoc()) {
    $courses[] = $row;
}


// Sidebar
ob_start();
include __DIR__ . '/_sidebar.php';
$sidebar_menu = ob_get_clean();

ob_start();
?>

<div class="space-y-8 animate-in">
    <!-- Header -->
    <div class="os-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden bg-white">
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
                <span class="px-2 py-1 bg-black text-white text-[10px] font-black uppercase tracking-widest border border-black">Directory</span>
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                    <span class="w-1.5 h-1.5 rounded-none bg-black inline-block mr-1"></span>
                    Active
                </span>
            </div>
            <h1 class="text-3xl font-black uppercase tracking-tighter">My <span class="text-transparent bg-clip-text bg-gradient-to-r from-black to-slate-500">Students</span></h1>
        </div>
        
        <div class="flex items-center gap-4 relative z-10">
             <div class="text-right hidden xl:block">
                <h3 class="text-3xl font-black text-black leading-none"><?php echo count($students); ?></h3>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Total Students</p>
            </div>
            <div class="w-10 h-10 bg-white border-2 border-black flex items-center justify-center text-black text-lg shadow-[2px_2px_0px_#000]">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="os-card p-4 bg-white flex flex-col md:flex-row gap-4">
        <div class="flex-1 relative">
            <form action="" method="GET" class="relative">
                <?php if($course_filter): ?><input type="hidden" name="course_id" value="<?php echo $course_filter; ?>"><?php endif; ?>
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-black"></i>
                <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="SEARCH BY NAME OR ID..." class="w-full pl-10 pr-4 py-3 bg-white border-2 border-black font-bold text-sm uppercase placeholder-slate-400 outline-none focus:shadow-[4px_4px_0px_#000] transition-shadow duration-200">
            </form>
        </div>
        <div class="w-full md:w-64">
             <form action="" method="GET">
                <?php if($search): ?><input type="hidden" name="search" value="<?php echo e($search); ?>"><?php endif; ?>
                <select name="course_id" onchange="this.form.submit()" class="w-full px-4 py-3 bg-white border-2 border-black font-bold text-sm uppercase outline-none focus:shadow-[4px_4px_0px_#000] transition-shadow duration-200 cursor-pointer appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23000000%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E')] bg-[length:0.7em] bg-no-repeat bg-[right_1rem_center]">
                    <option value="0">All Courses</option>
                    <?php foreach($courses as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $course_filter == $c['id'] ? 'selected' : ''; ?>>
                            <?php echo e($c['course_code']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <!-- Students Grid -->
    <?php if (empty($students)): ?>
        <div class="os-card p-12 text-center border-dashed">
            <div class="w-16 h-16 bg-slate-100 rounded-none flex items-center justify-center mx-auto mb-4 text-slate-400 border-2 border-slate-300">
                <i class="fas fa-user-graduate text-2xl"></i>
            </div>
            <h3 class="text-xl font-black text-slate-900 uppercase tracking-widest mb-1">No Students Found</h3>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Adjust filters or search criteria</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($students as $student): ?>
                <div class="os-card p-0 bg-white hover:-translate-y-1 transition-transform group h-full flex flex-col">
                    <div class="p-6 border-b-2 border-black flex items-start gap-4">
                        <div class="w-16 h-16 bg-black flex-shrink-0 flex items-center justify-center border-2 border-black shadow-[2px_2px_0px_#000]">
                            <?php if ($student['profile_picture']): ?>
                                <img src="<?php echo ASSETS_URL . '/uploads/profiles/' . $student['profile_picture']; ?>" class="w-full h-full object-cover filter grayscale group-hover:grayscale-0 transition-all">
                            <?php else: ?>
                                <span class="text-2xl font-black text-white uppercase"><?php echo substr($student['first_name'], 0, 1); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-black text-black uppercase leading-tight truncate">
                                <?php echo e($student['first_name'] . ' ' . $student['last_name']); ?>
                            </h3>
                            <div class="inline-block bg-yellow-400 border border-black px-2 py-0.5 mt-1">
                                <span class="text-[10px] font-black uppercase tracking-widest text-black"><?php echo e($student['matric_no']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 flex-1 space-y-3">
                         <div class="flex items-center gap-3">
                            <span class="w-6 h-6 bg-slate-100 border border-black flex items-center justify-center text-black text-[10px]">
                                <i class="fas fa-university"></i>
                            </span>
                            <span class="text-xs font-bold text-black uppercase truncate"><?php echo e($student['dept_name']); ?></span>
                        </div>
                         <div class="flex items-center gap-3">
                            <span class="w-6 h-6 bg-slate-100 border border-black flex items-center justify-center text-black text-[10px]">
                                <i class="fas fa-layer-group"></i>
                            </span>
                            <span class="text-xs font-bold text-black uppercase">Batch <?php echo e($student['batch_year']); ?> • Sem <?php echo e($student['current_semester']); ?></span>
                        </div>
                         <div class="flex items-center gap-3">
                            <span class="w-6 h-6 bg-slate-100 border border-black flex items-center justify-center text-black text-[10px]">
                                <i class="fas fa-book-open"></i>
                            </span>
                             <span class="text-xs font-bold text-black uppercase truncate" title="<?php echo e($student['enrolled_courses']); ?>">
                                <?php echo e($student['enrolled_courses']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="p-4 border-t-2 border-black bg-slate-50 flex gap-3">
                        <a href="mailto:<?php echo e($student['email']); ?>" class="flex-1 py-2 bg-white text-black border-2 border-black text-[10px] font-black uppercase tracking-widest hover:bg-black hover:text-white transition-colors flex items-center justify-center gap-2 shadow-[2px_2px_0px_#000] hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px]">
                            <i class="fas fa-envelope"></i> Email
                        </a>
                        <a href="<?php echo BASE_URL; ?>/modules/teacher/student-performance.php?student_id=<?php echo $student['id']; ?>" class="flex-[2] py-2 bg-black text-white border-2 border-black text-[10px] font-black uppercase tracking-widest hover:bg-yellow-400 hover:text-black transition-colors flex items-center justify-center gap-2 shadow-[2px_2px_0px_#000] hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px]">
                            <i class="fas fa-chart-line"></i> Performance
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/dashboard.php';
?>
