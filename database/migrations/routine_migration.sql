-- =============================================
-- ACADEMIX - Dynamic Class Routine System
-- Migration: Create routine management tables
-- Date: 2026-02-03
-- =============================================

SET FOREIGN_KEY_CHECKS = 0;

-- =============================================
-- ROOMS TABLE
-- Manages classroom and lab allocations
-- =============================================
CREATE TABLE IF NOT EXISTS rooms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED NULL COMMENT 'NULL means shared across departments',
    room_number VARCHAR(50) NOT NULL,
    building VARCHAR(100) NULL,
    room_type ENUM('classroom', 'lab', 'seminar', 'auditorium') DEFAULT 'classroom',
    capacity INT DEFAULT 60,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    INDEX idx_department (department_id),
    INDEX idx_room_type (room_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ROUTINE SLOTS TABLE
-- Configurable time slots (Theory/Lab/Break)
-- =============================================
CREATE TABLE IF NOT EXISTS routine_slots (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED NOT NULL,
    slot_name VARCHAR(100) NOT NULL COMMENT 'e.g., 1st Period, Tea Break, Afternoon Lab',
    slot_type ENUM('theory', 'lab', 'break') NOT NULL DEFAULT 'theory',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    display_order INT DEFAULT 0 COMMENT 'Order of appearance in routine grid',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    INDEX idx_department (department_id),
    INDEX idx_slot_type (slot_type),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ROUTINE DRAFTS TABLE
-- Master table for routine versions (draft/published)
-- =============================================
CREATE TABLE IF NOT EXISTS routine_drafts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED NOT NULL,
    semester_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NULL COMMENT 'Optional title for the routine version',
    status ENUM('draft', 'review', 'published', 'archived') DEFAULT 'draft',
    version INT DEFAULT 1 COMMENT 'Version number for tracking changes',
    created_by INT UNSIGNED NOT NULL,
    published_by INT UNSIGNED NULL,
    published_at TIMESTAMP NULL,
    notes TEXT NULL COMMENT 'Admin notes about this routine version',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (published_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_department (department_id),
    INDEX idx_semester (semester_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ROUTINE ASSIGNMENTS TABLE
-- Class assignments to specific slots
-- =============================================
CREATE TABLE IF NOT EXISTS routine_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    routine_draft_id INT UNSIGNED NOT NULL,
    slot_id INT UNSIGNED NOT NULL,
    day_of_week ENUM('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
    course_offering_id INT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NULL,
    teacher_id INT UNSIGNED NULL COMMENT 'Override teacher if different from course assignment',
    notes TEXT NULL COMMENT 'Special instructions for this class',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (routine_draft_id) REFERENCES routine_drafts(id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES routine_slots(id) ON DELETE CASCADE,
    FOREIGN KEY (course_offering_id) REFERENCES course_offerings(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL,
    UNIQUE KEY unique_slot_day_draft (routine_draft_id, slot_id, day_of_week),
    INDEX idx_routine_draft (routine_draft_id),
    INDEX idx_slot (slot_id),
    INDEX idx_day (day_of_week),
    INDEX idx_course_offering (course_offering_id),
    INDEX idx_room (room_id),
    INDEX idx_teacher (teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ROUTINE CHANGE REQUESTS TABLE
-- Teacher feedback on draft routines
-- =============================================
CREATE TABLE IF NOT EXISTS routine_change_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    routine_draft_id INT UNSIGNED NOT NULL,
    routine_assignment_id INT UNSIGNED NULL COMMENT 'NULL if general request, not for specific assignment',
    requested_by INT UNSIGNED NOT NULL,
    request_type ENUM('conflict', 'preference', 'unavailable', 'other') DEFAULT 'other',
    request_text TEXT NOT NULL,
    status ENUM('pending', 'under_review', 'approved', 'rejected', 'resolved') DEFAULT 'pending',
    admin_response TEXT NULL,
    responded_by INT UNSIGNED NULL,
    responded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (routine_draft_id) REFERENCES routine_drafts(id) ON DELETE CASCADE,
    FOREIGN KEY (routine_assignment_id) REFERENCES routine_assignments(id) ON DELETE SET NULL,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_routine_draft (routine_draft_id),
    INDEX idx_assignment (routine_assignment_id),
    INDEX idx_requested_by (requested_by),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- SEED DATA: Default Rooms for CSE Department
-- =============================================
INSERT INTO rooms (department_id, room_number, building, room_type, capacity) VALUES
(1, '101', 'Academic Building', 'classroom', 60),
(1, '102', 'Academic Building', 'classroom', 60),
(1, '103', 'Academic Building', 'classroom', 45),
(1, '201', 'Academic Building', 'classroom', 60),
(1, '202', 'Academic Building', 'classroom', 60),
(1, 'Lab-1', 'IT Building', 'lab', 40),
(1, 'Lab-2', 'IT Building', 'lab', 40),
(1, 'Lab-3', 'IT Building', 'lab', 30),
(NULL, 'Seminar Hall', 'Main Building', 'seminar', 150),
(NULL, 'Auditorium', 'Main Building', 'auditorium', 500);

-- =============================================
-- SEED DATA: Default Routine Slots for CSE Department
-- Based on Bangladesh Public University Standard
-- =============================================
INSERT INTO routine_slots (department_id, slot_name, slot_type, start_time, end_time, display_order) VALUES
(1, '1st Period', 'theory', '08:00:00', '08:50:00', 1),
(1, '2nd Period', 'theory', '08:50:00', '09:40:00', 2),
(1, '3rd Period', 'theory', '09:40:00', '10:30:00', 3),
(1, 'Tea Break', 'break', '10:30:00', '11:00:00', 4),
(1, '4th Period', 'theory', '11:00:00', '11:50:00', 5),
(1, '5th Period', 'theory', '11:50:00', '12:40:00', 6),
(1, 'Prayer/Lunch', 'break', '12:40:00', '14:00:00', 7),
(1, 'Afternoon Lab', 'lab', '14:00:00', '17:00:00', 8);

-- =============================================
-- SEED DATA: Create Initial Draft Routine for Spring 2026
-- =============================================
INSERT INTO routine_drafts (department_id, semester_id, title, status, created_by) VALUES
(1, 1, 'Spring 2026 Class Routine', 'draft', 2);
