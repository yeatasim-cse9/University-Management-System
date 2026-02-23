<?php
/**
 * Routine Management Helper Functions
 * Core logic for DRMS (Conflict Detection) and DRAS (Auto-Allocation)
 */

/**
 * Check for scheduling conflicts
 * 
 * @param mysqli $db
 * @param string $day Day of week
 * @param string $start_time Start time (H:i)
 * @param string $end_time End time (H:i)
 * @param string|int $room Room ID or Number
 * @param int $teacher_id Teacher User ID
 * @param int $offering_id Course Offering ID (to get semester/section info)
 * @param int|null $exclude_id Schedule ID to exclude (for updates)
 * 
 * @return array Array of errors, empty if no conflict
 */
function check_routine_conflicts($db, $day, $start_time, $end_time, $room, $teacher_id, $offering_id, $exclude_id = null) {
    // echo "DEBUG: Checking conflicts for Day: $day, Time: $start_time - $end_time, Room: $room, Teacher: $teacher_id, Offering: $offering_id\n";
    $errors = [];
    
    // 0. Strict Mode Check (Time Slots)
    $settings_q = $db->query("SELECT setting_value FROM routine_settings WHERE setting_key = 'strict_mode'");
    $strict_mode = ($settings_q->num_rows > 0) ? $settings_q->fetch_assoc()['setting_value'] : '0';
    
    if ($strict_mode == '1') {
        // Verify if start_time and end_time match a defined slot
        $slot_check = $db->prepare("SELECT id FROM time_slots WHERE start_time = ? AND end_time = ?");
        $slot_check->bind_param("ss", $start_time, $end_time);
        $slot_check->execute();
        if ($slot_check->get_result()->num_rows == 0) {
            $errors[] = "Time $start_time - $end_time does not match any valid Time Slot.";
        }
    }

    // 1. Room Conflict
    // "Is this room occupied?"
    $room_sql = "SELECT cs.id, c.course_code, cs.start_time, cs.end_time 
                 FROM class_schedule cs 
                 JOIN course_offerings co ON cs.course_offering_id = co.id 
                 JOIN courses c ON co.course_id = c.id
                 WHERE cs.day_of_week = ? 
                 AND cs.room_id = ? 
                 AND (cs.start_time < ? AND cs.end_time > ?)";
                 
    $params = [$day, $room, $end_time, $start_time];
    $types = "siss"; // s=day, i=room_id, s=time...
    
    if ($exclude_id) {
        $room_sql .= " AND cs.id != ?";
        $params[] = $exclude_id;
        $types .= "i";
    }
    
    $stmt = $db->prepare($room_sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        $errors[] = "Room ID $room is busy: " . $row['course_code'] . " (" . date('H:i', strtotime($row['start_time'])) . "-" . date('H:i', strtotime($row['end_time'])) . ")";
    }
    
    // 2. Teacher Conflict & Constraints
    if ($teacher_id) {
        // A. Is teacher busy in another class?
        $teacher_sql = "SELECT cs.id, c.course_code 
                        FROM class_schedule cs
                        JOIN teacher_courses tc ON cs.course_offering_id = tc.course_offering_id
                        JOIN course_offerings co ON cs.course_offering_id = co.id
                        JOIN courses c ON co.course_id = c.id
                        WHERE tc.teacher_id = ?
                        AND cs.day_of_week = ? 
                        AND (cs.start_time < ? AND cs.end_time > ?)";
                        
        $t_params = [$teacher_id, $day, $end_time, $start_time];
        $t_types = "isss";
        
        if ($exclude_id) {
            $teacher_sql .= " AND cs.id != ?";
            $t_params[] = $exclude_id;
            $t_types .= "i";
        }
        
        $t_stmt = $db->prepare($teacher_sql);
        $t_stmt->bind_param($t_types, ...$t_params);
        $t_stmt->execute();
        $t_res = $t_stmt->get_result();
        
        if ($t_row = $t_res->fetch_assoc()) {
            $errors[] = "Teacher is busy with " . $t_row['course_code'];
        }

        // B. Teacher Availability Check
        $avail_sql = "SELECT status FROM teacher_availability 
                      WHERE user_id = ? AND day = ? 
                      AND (start_time <= ? AND end_time >= ?) 
                      AND status = 'busy'";
        $a_stmt = $db->prepare($avail_sql);
        // Check if the proposed time falls WITHIN a busy block
        // Actually, we usually check overlap. Let's simplify: 
        // If teacher marked 10:00-12:00 as Busy, and we want 11:30-13:00, that's a conflict.
        $avail_sql = "SELECT status FROM teacher_availability 
                      WHERE user_id = ? AND day = ? 
                      AND (start_time < ? AND end_time > ?)"; 
        $a_stmt = $db->prepare($avail_sql);
        $a_stmt->bind_param("isss", $teacher_id, $day, $end_time, $start_time);
        $a_stmt->execute();
        if ($a_stmt->get_result()->num_rows > 0) {
            $errors[] = "Teacher has marked this time as Unavailable.";
        }

        // C. Max Daily Classes Check
        // Count existing classes for this teacher on this day
        $daily_sql = "SELECT COUNT(*) as cnt 
                      FROM class_schedule cs
                      JOIN teacher_courses tc ON cs.course_offering_id = tc.course_offering_id
                      WHERE tc.teacher_id = ? AND cs.day_of_week = ?";
        $d_stmt = $db->prepare($daily_sql);
        $d_stmt->bind_param("is", $teacher_id, $day);
        $d_stmt->execute();
        $daily_count = $d_stmt->get_result()->fetch_assoc()['cnt'];

        // Get Teacher's Limit
        $limit_q = $db->query("SELECT max_daily_classes FROM users WHERE id = $teacher_id");
        $max_daily = ($limit_q->num_rows > 0) ? $limit_q->fetch_assoc()['max_daily_classes'] : 3;

        // If updating an existing record, don't count itself? No, we count existing. 
        // If we represent a NEW addition, count + 1 > max? 
        // But check_routine_conflicts is called BEFORE insertion.
        if ($daily_count >= $max_daily) {
            // Check if we are just updating the SAME class on the SAME day (time change), then it's fine.
            // But if we are moving TO this day, it matters.
            // For simplicity: If exclude_id is set, and the excluded class is ON THIS DAY, subtract 1.
            $on_same_day = false;
            if ($exclude_id) {
                $check_ex = $db->query("SELECT day_of_week FROM class_schedule WHERE id = $exclude_id");
                if ($check_ex->num_rows > 0 && $check_ex->fetch_assoc()['day_of_week'] == $day) {
                    $daily_count--;
                }
            }
            
            if ($daily_count >= $max_daily) {
                $errors[] = "Teacher Daily Limit ($max_daily) reached.";
            }
        }
    }
    
    // 3. Batch Conflict (Student Group)
    // "Do these students have another class?"
    // Get semester and section for this offering
    $off_sql = "SELECT semester_id, section FROM course_offerings WHERE id = ?";
    $off_stmt = $db->prepare($off_sql);
    $off_stmt->bind_param("i", $offering_id);
    $off_stmt->execute();
    $off_data = $off_stmt->get_result()->fetch_assoc();
    
    if ($off_data) {
        $sem_id = $off_data['semester_id'];
        $section = $off_data['section'];
        
        $batch_sql = "SELECT cs.id, c.course_code 
                      FROM class_schedule cs
                      JOIN course_offerings co ON cs.course_offering_id = co.id
                      JOIN courses c ON co.course_id = c.id
                      WHERE co.semester_id = ? 
                      AND co.section = ?
                      AND cs.day_of_week = ? 
                      AND (cs.start_time < ? AND cs.end_time > ?)";
                      
        $b_params = [$sem_id, $section, $day, $end_time, $start_time];
        $b_types = "issss"; // i=sem_id, s=section, s=day...
        
        if ($exclude_id) {
            $batch_sql .= " AND cs.id != ?";
            $b_params[] = $exclude_id;
            $b_types .= "i";
        }
        
        $b_stmt = $db->prepare($batch_sql);
        $b_stmt->bind_param($b_types, ...$b_params);
        $b_stmt->execute();
        $b_res = $b_stmt->get_result();
        
        if ($b_row = $b_res->fetch_assoc()) {
            $errors[] = "Student Batch has class: " . $b_row['course_code'];
        }
    }
    
    return $errors;
}

/**
 * Get configured Time Slots
 */
function get_time_slots($db) {
    return $db->query("SELECT * FROM time_slots ORDER BY start_time ASC")->fetch_all(MYSQLI_ASSOC);
}

/**
 * Find valid time slots for an offering
 * Used for "Suggest Slot" feature
 */
function find_available_slots($db, $offering_id, $duration_minutes = 90) {
    // Get offering details (teacher, semester, section)
    $q = $db->query("SELECT co.*, tc.teacher_id, c.course_type 
                     FROM course_offerings co
                     LEFT JOIN teacher_courses tc ON co.id = tc.course_offering_id
                     LEFT JOIN courses c ON co.course_id = c.id
                     WHERE co.id = $offering_id LIMIT 1");
    $offering = $q->fetch_assoc();
    
    if (!$offering) return [];
    
    $teacher_id = $offering['teacher_id'];
    $duration_sql = ($duration_minutes * 60); // seconds
    
    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
    $start_hour = 9; // 9 AM
    $end_hour = 17; // 5 PM
    
    $suggestions = [];
    
    // Get all rooms (filter by type if needed)
    $room_type = ($offering['course_type'] == 'lab') ? 'lab' : 'theory';
    $rooms_res = $db->query("SELECT id, room_number, building FROM rooms WHERE type = '$room_type' AND status='active'");
    $rooms = [];
    while($r = $rooms_res->fetch_assoc()) $rooms[] = $r;
    
    if (empty($rooms)) $rooms = [['id' => 1, 'room_number' => '6613']]; // Fallback
    
    // Brute force search for simplified suggestions (Limit to 5)
    foreach ($days as $day) {
        for ($hour = $start_hour; $hour < $end_hour; $hour++) {
            // Check 9:00, 10:30, 12:00, 14:00 (Standard slots)
            $slots = [
                sprintf("%02d:00:00", $hour),
                sprintf("%02d:30:00", $hour)
            ];
            
            foreach ($slots as $start_time) {
                // Calculate end time
                $end_time = date('H:i:s', strtotime($start_time) + $duration_sql);
                if (strtotime($end_time) > strtotime("17:00:00")) continue;
                
                // Try each room
                foreach ($rooms as $room_data) {
                    $r_id = $room_data['id'];
                    $r_num = $room_data['room_number'];
                    
                    $conflicts = check_routine_conflicts($db, $day, $start_time, $end_time, $r_id, $teacher_id, $offering_id);
                    if (empty($conflicts)) {
                        $suggestions[] = [
                            'day' => $day,
                            'start_time' => $start_time,
                            'end_time' => $end_time,
                            'room_id' => $r_id,
                            'room' => $r_num,
                            'label' => "$day $start_time ($r_num)"
                        ];
                        if (count($suggestions) >= 30) break 3; // Found enough options
                    }
                }
            }
        }
    }
    
    // Randomize and pick 3
    shuffle($suggestions);
    return array_slice($suggestions, 0, 3);
}

/**
 * Auto-Allocate Routine for a Semester (DRAS Core)
 * 
 * @param mysqli $db
 * @param int $semester_id
 * @param array $department_ids
 * @return array Result stats including success count and errors
 */
function auto_allocate_semester($db, $semester_id, $department_ids) {
    if (empty($department_ids)) return ['success' => 0, 'errors' => ['No department permission']];
    
    $dept_str = implode(',', $department_ids);
    
    // 1. Get all Unscheduled or Partially Scheduled Offerings for this Semester
    // We target offerings that have NO schedule or less than expected (assuming 2 classes per week for theory)
    $sql = "SELECT co.id, co.course_id, c.course_code, c.credit_hours, c.course_type, tc.teacher_id
            FROM course_offerings co
            JOIN courses c ON co.course_id = c.id
            LEFT JOIN teacher_courses tc ON co.id = tc.course_offering_id
            WHERE co.semester_id = ? 
            AND c.department_id IN ($dept_str)
            AND co.status = 'open'";
            
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $semester_id);
    $stmt->execute();
    $offerings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $allocated_count = 0;
    $errors = [];
    
    // Get all Rooms once
    $rooms_res = $db->query("SELECT room_number, type, capacity FROM rooms WHERE status='active'");
    $all_rooms = $rooms_res->fetch_all(MYSQLI_ASSOC);
    
    $lab_rooms = array_filter($all_rooms, function($r) { return $r['type'] === 'lab'; });
    $theory_rooms = array_filter($all_rooms, function($r) { return $r['type'] === 'theory'; });
    
    // Prioritize high credit courses (harder to fit)
    usort($offerings, function($a, $b) {
        return $b['credit_hours'] <=> $a['credit_hours'];
    });
    
    foreach ($offerings as $offering) {
        // Check how many classes already exist
        $check_exist = $db->query("SELECT COUNT(*) as cnt FROM class_schedule WHERE course_offering_id = {$offering['id']}");
        $existing = $check_exist->fetch_assoc()['cnt'];
        
        // Target classes per week: Credit Hours (e.g. 3.0 -> 2 classes of 1.5h OR 3 classes of 1h)
        // Simplifying to: 2 classes per week for 3.0 credits, 1 for less.
        $target_classes = ($offering['credit_hours'] >= 3.0) ? 2 : 1;
        
        if ($existing >= $target_classes) continue;
        
        $needed = $target_classes - $existing;
        $offering_id = $offering['id'];
        $teacher_id = $offering['teacher_id']; // Can be null
        
        // Suitable rooms
        $candidate_rooms = ($offering['course_type'] === 'lab') ? $lab_rooms : $theory_rooms;
        if (empty($candidate_rooms)) $candidate_rooms = $all_rooms; // Fallback
        
        // Try to find slots
        // Heuristic: Try different days for multiple classes
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
        
        for ($i = 0; $i < $needed; $i++) {
            $placed = false;
            
            // Randomize days to distribute load, but try to avoid days where this batch already has many classes?
            // Simple approach: Iterate days/hours
            shuffle($candidate_rooms); // Load balance rooms
            
            // Time Slots (Dynamic)
            $db_slots = get_time_slots($db);
            $time_slots = [];
            foreach ($db_slots as $dbs) {
                if (!$dbs['is_break']) {
                    $time_slots[] = [$dbs['start_time'], $dbs['end_time']];
                }
            }
            // Fallback if empty
            if (empty($time_slots)) {
                 $time_slots = [['09:00:00', '10:30:00'], ['10:30:00', '12:00:00']];
            }
            
            foreach ($days as $day) {
                // Skip if we just placed a class for this course on this day (Spread it out)
                // Check existing schedule for this course on this day
                $check_day = $db->query("SELECT id FROM class_schedule WHERE course_offering_id = $offering_id AND day_of_week='$day'");
                if ($check_day->num_rows > 0) continue;
                
                foreach ($time_slots as $slot) {
                    $start = $slot[0];
                    $end = $slot[1];
                    
                    foreach ($candidate_rooms as $room_data) {
                        $room_id = $room_data['id']; // Use ID now
                        
                        $conflicts = check_routine_conflicts($db, $day, $start, $end, $room_id, $teacher_id, $offering_id);
                        
                        if (empty($conflicts)) {
                            // Found a slot!
                            $ins = $db->prepare("INSERT INTO class_schedule (course_offering_id, day_of_week, start_time, end_time, room_id) VALUES (?, ?, ?, ?, ?)");
                            $ins->bind_param("isssi", $offering_id, $day, $start, $end, $room_id);
                            if ($ins->execute()) {
                                $allocated_count++;
                                $placed = true;
                                break 3; // Break room, slot, and day loops -> next needed class
                            }
                        }
                    }
                }
            }
            
            if (!$placed) {
                $errors[] = "Could not find slot for {$offering['course_code']} (Teacher ID: $teacher_id)";
            }
        }
    }

    // Phase 2: Notification
    if ($allocated_count > 0) {
        // Assuming $_SESSION['user_id'] is available and contains the ID of the user who initiated the auto-allocation
        // In a real application, you might pass user_id as a parameter or retrieve it from session/context.
        // For this example, we'll use a placeholder or assume it's set.
        // If $_SESSION['user_id'] is not guaranteed, you might need to adjust this.
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Fallback to a default user ID if not in session
        $msg = "Auto-Allocation Completed. $allocated_count classes scheduled.";
        $db->query("INSERT INTO notifications (user_id, message) VALUES (" . $user_id . ", '$msg')");
    }
    
    return ['allocated' => $allocated_count, 'errors' => $errors];
}
?>
