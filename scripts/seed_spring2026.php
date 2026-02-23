<?php
// Seeding Script - Direct Version
require_once __DIR__ . '/../includes/functions.php'; // For any helpers if needed, but we'll try to rely on native PHP

$conn = new mysqli('localhost', 'root', '', 'academix');
if ($conn->connect_error) die("DB Connection Failed: " . $conn->connect_error);
$conn->query("SET time_zone = '+06:00'");

echo "Starting Seed...\n";

function run_sql($c, $sql) {
    try {
        $r = $c->query($sql);
        if ($c->error) throw new Exception($c->error);
        return $r;
    } catch (Throwable $e) {
        die("SQL Failed: " . $e->getMessage() . "\nQuery: $sql\n");
    }
}

// 1. Academic Year
$year = '2026';
$res = run_sql($conn, "SELECT id FROM academic_years WHERE year = '$year'");
if ($res->num_rows > 0) {
    $ay_id = $res->fetch_object()->id;
    echo "AY Found: $ay_id\n";
} else {
    run_sql($conn, "INSERT INTO academic_years (year, start_date, end_date, status) VALUES ('$year', '2026-01-01', '2026-12-31', 'active')");
    $ay_id = $conn->insert_id;
    echo "AY Created: $ay_id\n";
}

// 2. Semester
run_sql($conn, "SET FOREIGN_KEY_CHECKS = 0");
run_sql($conn, "DROP TABLE IF EXISTS semesters");
run_sql($conn, "CREATE TABLE semesters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academic_year_id INT UNSIGNED,
    semester_name VARCHAR(100),
    year VARCHAR(20),
    start_date DATE,
    end_date DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
run_sql($conn, "SET FOREIGN_KEY_CHECKS = 1");

$sem_name = 'Spring-2026';
run_sql($conn, "INSERT INTO semesters (academic_year_id, semester_name, year, start_date, end_date, status) VALUES ($ay_id, '$sem_name', '2026', '2026-01-01', '2026-06-30', 'active')");
$sem_id = $conn->insert_id;
echo "Sem Created: $sem_id\n";

// 3. Rooms
run_sql($conn, "SET FOREIGN_KEY_CHECKS = 0");
run_sql($conn, "DROP TABLE IF EXISTS rooms");
run_sql($conn, "CREATE TABLE rooms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(100),
    building VARCHAR(100) DEFAULT 'Main Building',
    capacity INT,
    type VARCHAR(50) DEFAULT 'lab',
    status ENUM('active', 'maintenance') DEFAULT 'active',
    has_projector TINYINT DEFAULT 1,
    has_ac TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
run_sql($conn, "SET FOREIGN_KEY_CHECKS = 1");

$rooms = [
    'C1' => 'Room 6613',
    'C2' => 'Adv. Prog Lab',
    'C3' => 'Network Lab',
    'C4' => 'IoT Lab',
    'C5' => 'DLD Lab',
    'C6' => 'Mobile Lab'
];
$room_map = []; // Code -> ID

foreach ($rooms as $code => $name) {
    // We use the full name as 'room_number' for now, or just the code? 
    // The previous array had "Room 6613".
    // I'll use the Name logic from before but map to room_number column.
    // Also set building based on context if known, else default.
    run_sql($conn, "INSERT INTO rooms (room_number, capacity, type, status) VALUES ('$name ($code)', 50, 'lab', 'active')");
    $room_map[$code] = $conn->insert_id;
    echo "Room Created: $code\n";
}

// 4. Teachers
$teachers = [
    'RHF' => 'Rahat Hossain Faisal',
    'MSD' => 'Md. Samsuddoha',
    'RAA' => 'Md. Rashid Al Asif',
    'TBA' => null,
    'MMA' => 'Md Manjur Ahmed',
    'MMN' => 'Md Mahbub E Noor',
    'MAK' => 'Md. Abdul Kaium',
    'ME'  => 'Md. Erfan',
    'SJ'  => 'Sohely Jahan',
    'MJR' => 'Mahua Jahan Rupa',
    'AAM' => 'Abdullah Al Masud',
    'MHS' => 'Mahmudul Hassan Suhag',
    'MA'  => 'Md. Asaduzzaman',
    'TI'  => 'Tania Islam',
    'TR'  => 'Tazizur Rahman'
];

$teacher_map = []; // Initials -> ID

foreach ($teachers as $init => $name) {
    if (!$name) continue;
    $esc = $conn->real_escape_string($name);
    // Search in user_profiles linked to teachers
    $sql = "SELECT t.id FROM teachers t JOIN user_profiles up ON t.user_id = up.user_id 
            WHERE CONCAT(up.first_name, ' ', up.last_name) LIKE '%$esc%' 
            OR t.employee_id = 'T-$init'";
    $res = run_sql($conn, $sql);
    
    if ($res->num_rows > 0) {
        $teacher_map[$init] = $res->fetch_object()->id;
    } else {
        // Check if user exists by email
        $u = strtolower($init) . "_t";
        $e = strtolower($init) . "@academix.edu";
        
        $ures = run_sql($conn, "SELECT id FROM users WHERE email = '$e'");
        if ($ures->num_rows > 0) {
            $uid = $ures->fetch_object()->id;
            echo "User exists ($e). Using ID $uid.\n";
        } else {
            run_sql($conn, "INSERT INTO users (username, email, password, role) VALUES ('$u', '$e', '123456', 'teacher')");
            $uid = $conn->insert_id;
            
            $parts = explode(' ', $name);
            $last = array_pop($parts);
            $first = implode(' ', $parts);
            run_sql($conn, "INSERT INTO user_profiles (user_id, first_name, last_name) VALUES ($uid, '$first', '$last')");
        }
        
        // Add to teachers if not already there
        $tres = run_sql($conn, "SELECT id FROM teachers WHERE user_id = $uid");
        if ($tres->num_rows > 0) {
            $teacher_map[$init] = $tres->fetch_object()->id;
        } else {
            run_sql($conn, "INSERT INTO teachers (user_id, department_id, employee_id) VALUES ($uid, 1, 'T-$init')");
            $teacher_map[$init] = $conn->insert_id;
            echo "Teacher Created: $init\n";
        }
    }
}

// 5. Routine
// Recreate Tables
run_sql($conn, "SET FOREIGN_KEY_CHECKS = 0");
$tables = ['class_reschedules', 'class_schedule', 'teacher_courses', 'course_offerings', 'courses'];
foreach ($tables as $t) run_sql($conn, "DROP TABLE IF EXISTS $t");

// Courses
run_sql($conn, "CREATE TABLE courses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED DEFAULT 1,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(255),
    credit_hours DECIMAL(3,1) DEFAULT 3.0,
    semester_number INT DEFAULT 1,
    course_type ENUM('theory', 'lab', 'project') DEFAULT 'theory',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL
)");

// Course Offerings
run_sql($conn, "CREATE TABLE course_offerings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    semester_id INT UNSIGNED NOT NULL,
    section VARCHAR(10) DEFAULT 'A',
    capacity INT DEFAULT 60,
    status ENUM('open', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id),
    FOREIGN KEY (semester_id) REFERENCES semesters(id)
)");

// Teacher Courses
run_sql($conn, "CREATE TABLE teacher_courses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    course_offering_id INT UNSIGNED NOT NULL,
    is_primary TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    FOREIGN KEY (course_offering_id) REFERENCES course_offerings(id)
)");

// Class Schedule
run_sql($conn, "CREATE TABLE class_schedule (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_offering_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED,
    room_id INT UNSIGNED NOT NULL,
    day_of_week VARCHAR(20),
    start_time TIME,
    end_time TIME,
    is_recurring TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_offering_id) REFERENCES course_offerings(id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
)");

// Class Reschedules
run_sql($conn, "CREATE TABLE class_reschedules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_offering_id INT UNSIGNED NOT NULL,
    original_date DATE NOT NULL,
    new_date DATE NOT NULL,
    new_start_time TIME NOT NULL,
    new_end_time TIME NOT NULL,
    room_id INT UNSIGNED NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected', 'active', 'cancelled') DEFAULT 'pending',
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_offering_id) REFERENCES course_offerings(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
)");

run_sql($conn, "SET FOREIGN_KEY_CHECKS = 1");

$routine = [
    '12' => [ // 12th Batch (Year 1)
        'Sunday' => [
            ['09:00:00', '10:30:00', 'EEE-1207', 'TBA', 'C1'],
            ['10:30:00', '12:00:00', 'STAT-1211', 'TBA', 'C1'],
            ['12:00:00', '13:30:00', 'MATH-1209', 'TBA', 'C1'],
            ['14:00:00', '15:30:00', 'EEE-1206', 'TBA', 'C5']
        ],
        'Monday' => [
            ['09:00:00', '10:30:00', 'CSE-1201', 'MSD', 'C1'],
            ['10:30:00', '12:00:00', 'EEE-1205', 'RHF', 'C1'],
            ['14:00:00', '15:30:00', 'CSE-1204', 'RAA', 'C1']
        ],
        'Wednesday' => [
            ['09:00:00', '10:30:00', 'CSE-1203', 'RAA', 'C1'],
            ['10:30:00', '12:00:00', 'STAT-1211', 'TBA', 'C1'],
            ['12:00:00', '13:30:00', 'MATH-1209', 'TBA', 'C1'],
            ['14:00:00', '15:30:00', 'EEE-1207', 'TBA', 'C1']
        ],
        'Thursday' => [
            ['09:00:00', '10:30:00', 'EEE-1205', 'RHF', 'C1'],
            ['10:30:00', '12:00:00', 'CSE-1201', 'MSD', 'C1'],
            ['14:00:00', '15:30:00', 'EEE-1208', 'TBA', 'C1']
        ]
    ],
    '11' => [ // 11th Batch
        'Sunday' => [
            ['10:30:00', '12:00:00', 'CSE-2101', 'MSD', 'C2'],
            ['12:00:00', '13:30:00', 'MATH-2109', 'TBA', 'C2']
        ],
        'Monday' => [
            ['10:30:00', '12:00:00', 'CSE-2107', 'RAA', 'C2'],
            ['12:00:00', '13:30:00', 'CSE-2103', 'RHF', 'C2']
        ],
        'Tuesday' => [
            ['10:30:00', '12:00:00', 'CSE-2102', 'MSD', 'C2'],
            ['12:00:00', '13:30:00', 'EEE-2105', 'RHF', 'C1']
        ],
        'Wednesday' => [
            ['09:00:00', '10:30:00', 'EEE-2104', 'RHF', 'C5'],
            ['10:30:00', '12:00:00', 'MATH-2109', 'TBA', 'C2'],
            ['12:00:00', '13:30:00', 'CSE-2108', 'RAA', 'C2']
        ],
        'Thursday' => [
             ['15:30:00', '17:00:00', 'EEE-2106', 'RHF', 'C5']
        ]
    ],
    '10' => [ // 10th Batch
        'Sunday' => [
            ['09:00:00', '10:30:00', 'CSE-3101', 'RHF', 'C3'],
            ['10:30:00', '12:00:00', 'HUM-3109', 'TBA', 'C3'],
            ['12:00:00', '13:30:00', 'CSE-3104', 'MSD', 'C1']
        ],
        'Monday' => [
            ['10:30:00', '12:00:00', 'CSE-3107', 'MMN', 'C3'],
            ['12:00:00', '13:30:00', 'HUM-3111', 'TBA', 'C3']
        ],
        'Tuesday' => [
            ['09:00:00', '10:30:00', 'CSE-3105', 'RAA', 'C1'],
            ['12:00:00', '13:30:00', 'CSE-3103', 'MSD', 'C2']
        ],
        'Wednesday' => [
             ['14:00:00', '15:30:00', 'EEE-3102', 'RHF', 'C5']
        ],
        'Thursday' => [
             ['09:00:00', '10:30:00', 'CSE-3106', 'RAA', 'C2'],
             ['10:30:00', '12:00:00', 'CSE-3114', 'TI', 'C3']
        ]
    ]
];


foreach ($routine as $batch => $days) {
    echo "Processing Batch $batch\n";
    foreach ($days as $day => $slots) {
        foreach ($slots as $slot) {
            $start = $slot[0]; $end = $slot[1]; $code = $slot[2]; $teach = $slot[3]; $room = $slot[4];
            
            // Course
            $cid = 0;
            $res = run_sql($conn, "SELECT id FROM courses WHERE course_code = '$code'");
            if ($res->num_rows > 0) $cid = $res->fetch_object()->id;
            else {
                // Determine type based on code or context, defaulting to theory
                // defaulting credit_hours to 3.0
                // defaulting status to 'active'
                $type = 'theory';
                if (stripos($code, 'Lab') !== false || stripos($code, 'Sessional') !== false) $type = 'lab';
                
                run_sql($conn, "INSERT INTO courses (department_id, course_code, course_name, credit_hours, course_type, status) VALUES (1, '$code', '$code Course', 3.0, '$type', 'active')");
                $cid = $conn->insert_id;
                echo "  New Course: $code\n";
            }
            
            // Offering
            $oid = 0;
            $res = run_sql($conn, "SELECT id FROM course_offerings WHERE course_id = $cid AND semester_id = $sem_id");
            if ($res->num_rows > 0) $oid = $res->fetch_object()->id;
            else {
                run_sql($conn, "INSERT INTO course_offerings (course_id, semester_id, section, capacity, status) VALUES ($cid, $sem_id, 'A', 60, 'open')");
                $oid = $conn->insert_id;
                
                // Teacher
                if ($teach && isset($teacher_map[$teach])) {
                    $tid = $teacher_map[$teach];
                    run_sql($conn, "INSERT IGNORE INTO teacher_courses (teacher_id, course_offering_id) VALUES ($tid, $oid)");
                }
            }
            
            // Schedule
            $rid = $room_map[$room] ?? 'NULL';
            // run_sql($conn, "DELETE FROM class_schedule WHERE course_offering_id = $oid AND day_of_week = '$day'");
            
            // Check dupes
            $dup = run_sql($conn, "SELECT id FROM class_schedule WHERE course_offering_id = $oid AND day_of_week = '$day' AND start_time = '$start'");
            if ($dup->num_rows == 0) {
                 run_sql($conn, "INSERT INTO class_schedule (course_offering_id, room_id, day_of_week, start_time, end_time, is_recurring) VALUES ($oid, $rid, '$day', '$start', '$end', 1)");
                 echo "  Scheduled $code on $day\n";
            }
        }
    }
}
echo "Done.\n";
