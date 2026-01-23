<?php
// scripts/import_students.php

require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (php_sapi_name() !== 'cli') {
    die("Run inside CLI");
}

echo "============================================\n";
echo "Starting Student Import\n";
echo "============================================\n";

// 1. Load CSV Data
$csvFile = __DIR__ . '/students_data.csv';
if (!file_exists($csvFile)) {
    die("Error: CSV file not found at $csvFile\n");
}

$rows = array_map('str_getcsv', file($csvFile));
$header = array_shift($rows); // Remove header

// Map Header Columns to Indices
$colMap = [];
foreach ($header as $index => $colName) {
    if (stripos($colName, 'Class Roll') !== false) $colMap['roll'] = $index;
    if (stripos($colName, 'Name') !== false) $colMap['name'] = $index;
    if (stripos($colName, 'Exam Roll') !== false) $colMap['session'] = $index;
    if (stripos($colName, 'Contact Number') !== false) $colMap['phone'] = $index;
    if (stripos($colName, 'Email') !== false) $colMap['email'] = $index;
    if (stripos($colName, 'Address') !== false) $colMap['address'] = $index;
    if (stripos($colName, 'Blood Group') !== false) $colMap['blood'] = $index;
}

// 2. Prepare Sem 5 Courses
$sem5_courses = [];
echo "Fetching Semester 5 Course Offerings...\n";
$semStmt = $db->query("
    SELECT co.id 
    FROM course_offerings co
    JOIN semesters s ON co.semester_id = s.id
    WHERE s.semester_number = 5
");
while ($row = $semStmt->fetch_assoc()) {
    $sem5_courses[] = $row['id'];
}
echo "Found " . count($sem5_courses) . " courses for Semester 5.\n";

if (empty($sem5_courses)) {
    echo "Warning: No courses found for Semester 5. Enrollment will be skipped.\n";
}

// 3. Process Rows
$successCount = 0;
$failCount = 0;
// Plain text password as requested
$default_password = '123456';

$db->begin_transaction();

foreach ($rows as $row) {
    if (count($row) < 3) continue; // Skip empty lines

    $roll = trim($row[$colMap['roll']] ?? '');
    $fullName = trim($row[$colMap['name']] ?? '');
    $session = trim($row[$colMap['session']] ?? '');
    $phone = trim($row[$colMap['phone']] ?? '');
    $email = trim($row[$colMap['email']] ?? '');
    $address = trim($row[$colMap['address']] ?? '');
    $bloodKey = $colMap['blood'] ?? -1;
    $bloodGroup = ($bloodKey >= 0) ? trim($row[$bloodKey]) : '';

    if (empty($roll) || empty($fullName)) {
        continue;
    }

    $logMsg = "Processing: $roll - $fullName... ";
    echo $logMsg;

    try {
        // --- 1. USERS Table ---
        // Check if user exists
        $userCheck = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $userCheck->bind_param("ss", $roll, $email);
        $userCheck->execute();
        $existing = $userCheck->get_result()->fetch_assoc();

        if ($existing) {
            $user_id = $existing['id'];
            
            // Force Update Password to Plain Text
            $updPass = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updPass->bind_param("si", $default_password, $user_id);
            $updPass->execute();
            
            echo "[User Exists/Updated] ";
        } else {
            // Create User (Plain Text)
            $uStmt = $db->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, 'student', NOW())");
            $uStmt->bind_param("sss", $roll, $email, $default_password);
            if (!$uStmt->execute()) throw new Exception("User insert failed: " . $uStmt->error);
            $user_id = $db->insert_id;
            echo "[User Created] ";
        }

        // --- 2. USER_PROFILES Table ---
        // Split Name
        $parts = explode(' ', $fullName);
        $lastName = (count($parts) > 1) ? array_pop($parts) : ''; // Last word as Last Name
        $firstName = implode(' ', $parts);
        if (empty($lastName)) { $lastName = $firstName; $firstName = ''; } // Fallback if single name

        // Check/Update Profile
        $profCheck = $db->query("SELECT id FROM user_profiles WHERE user_id = $user_id");
        if ($profCheck->num_rows == 0) {
            $pStmt = $db->prepare("INSERT INTO user_profiles (user_id, first_name, last_name, phone, address) VALUES (?, ?, ?, ?, ?)");
            $pStmt->bind_param("issss", $user_id, $firstName, $lastName, $phone, $address);
            $pStmt->execute();
        }

        // --- 3. STUDENTS Table ---
        $stuCheck = $db->prepare("SELECT id FROM students WHERE user_id = ?");
        $stuCheck->bind_param("i", $user_id);
        $stuCheck->execute();
        $existingStu = $stuCheck->get_result()->fetch_assoc();

        if ($existingStu) {
            $student_table_id = $existingStu['id'];
        } else {
            // Fetch Dept ID dynamically to avoid FK errors
            // Try CSE first, then any dept
            $deptRes = $db->query("SELECT id FROM departments WHERE code = 'CSE' LIMIT 1");
            if ($deptRes->num_rows > 0) {
                $dept_id = $deptRes->fetch_assoc()['id'];
            } else {
                // Fallback to ANY department
                $deptRes = $db->query("SELECT id FROM departments LIMIT 1");
                if ($deptRes->num_rows > 0) {
                     $dept_id = $deptRes->fetch_assoc()['id'];
                } else {
                     throw new Exception("No departments found in database. Cannot create student.");
                }
            }
            
            $sStmt = $db->prepare("INSERT INTO students (user_id, student_id, batch_year, session, blood_group, current_semester, department_id) VALUES (?, ?, 9, ?, ?, 5, ?)");
            // Note: student_id column here is the Roll No string (22CSE001)
            $sStmt->bind_param("isssi", $user_id, $roll, $session, $bloodGroup, $dept_id);
            if (!$sStmt->execute()) throw new Exception("Student insert failed: " . $sStmt->error);
            $student_table_id = $db->insert_id;
            echo "[Student Profile Created] ";
        }

        if (!empty($sem5_courses)) {
            $enrolledCount = 0;
            $eStmt = $db->prepare("INSERT IGNORE INTO enrollments (student_id, course_offering_id) VALUES (?, ?)");
            foreach ($sem5_courses as $course_offering_id) {
                $eStmt->bind_param("ii", $student_table_id, $course_offering_id);
                $eStmt->execute();
                if ($db->affected_rows > 0) $enrolledCount++;
            }
            $logMsg .= "[Enrolled in $enrolledCount courses] ";
        }

        $logMsg .= "DONE.\n";
        file_put_contents(__DIR__ . '/debug_import.log', $logMsg, FILE_APPEND);
        echo $logMsg;
        $successCount++;
        
    } catch (Exception $e) {
        $errMsg = "FAILED: " . $e->getMessage() . "\n";
        file_put_contents(__DIR__ . '/debug_import.log', $errMsg, FILE_APPEND);
        echo $errMsg;
        $failCount++;
    }
}

$db->commit();

echo "============================================\n";
echo "Import Complete.\n";
echo "Success: $successCount\n";
echo "Failed: $failCount\n";
echo "============================================\n";
?>
