-- =============================================
-- ACADEMIX - Dynamic Routine Management System
-- Migration: 002_routine_system.sql
-- Created: 2026-02-03
-- =============================================

-- Delete existing class_schedule data as requested
DELETE FROM class_schedule;

-- =============================================
-- ROUTINE SLOTS TABLE
-- Configurable time slots for the routine
-- =============================================
CREATE TABLE IF NOT EXISTS routine_slots (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    label VARCHAR(100) NOT NULL,
    slot_type ENUM('theory', 'lab', 'break') NOT NULL DEFAULT 'theory',
    slot_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slot_order (slot_order),
    INDEX idx_slot_type (slot_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ROOMS TABLE
-- Predefined rooms/labs with building info
-- =============================================
CREATE TABLE IF NOT EXISTS rooms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    building VARCHAR(100) NOT NULL,
    room_type ENUM('classroom', 'lab') NOT NULL DEFAULT 'classroom',
    capacity INT UNSIGNED DEFAULT 60,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_room_type (room_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ROUTINE DRAFTS TABLE
-- Draft routines waiting for confirmation
-- =============================================
CREATE TABLE IF NOT EXISTS routine_drafts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    semester_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NOT NULL,
    draft_name VARCHAR(200) NOT NULL,
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_semester_id (semester_id),
    INDEX idx_department_id (department_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ROUTINE ASSIGNMENTS TABLE
-- Class assignments in routines
-- =============================================
CREATE TABLE IF NOT EXISTS routine_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    routine_draft_id INT UNSIGNED NOT NULL,
    course_offering_id INT UNSIGNED NOT NULL,
    slot_id INT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NOT NULL,
    day_of_week ENUM('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (routine_draft_id) REFERENCES routine_drafts(id) ON DELETE CASCADE,
    FOREIGN KEY (course_offering_id) REFERENCES course_offerings(id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES routine_slots(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    -- Prevent duplicate assignments in same slot/day
    UNIQUE KEY unique_slot_day_draft (routine_draft_id, slot_id, day_of_week, room_id),
    INDEX idx_routine_draft_id (routine_draft_id),
    INDEX idx_course_offering_id (course_offering_id),
    INDEX idx_slot_id (slot_id),
    INDEX idx_day_of_week (day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ROUTINE CHANGE REQUESTS TABLE
-- Teacher change requests
-- =============================================
CREATE TABLE IF NOT EXISTS routine_change_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    routine_assignment_id INT UNSIGNED NOT NULL,
    requested_by INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    admin_response TEXT NULL,
    responded_by INT UNSIGNED NULL,
    responded_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (routine_assignment_id) REFERENCES routine_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_routine_assignment_id (routine_assignment_id),
    INDEX idx_requested_by (requested_by),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- SEED DATA: Default Routine Slots
-- Based on Bangladesh Public University System
-- =============================================
INSERT INTO routine_slots (start_time, end_time, label, slot_type, slot_order) VALUES
('08:00:00', '08:50:00', '1st Period', 'theory', 1),
('08:50:00', '09:40:00', '2nd Period', 'theory', 2),
('09:40:00', '10:30:00', '3rd Period', 'theory', 3),
('10:30:00', '11:00:00', 'Tea Break', 'break', 4),
('11:00:00', '11:50:00', '4th Period', 'theory', 5),
('11:50:00', '12:40:00', '5th Period', 'theory', 6),
('12:40:00', '14:00:00', 'Prayer/Lunch', 'break', 7),
('14:00:00', '17:00:00', 'Afternoon Lab', 'lab', 8);

-- =============================================
-- SEED DATA: Rooms/Labs
-- Based on user requirements
-- =============================================
INSERT INTO rooms (code, name, building, room_type, capacity) VALUES
('C1', 'Room 6613', 'Academic Building 1', 'classroom', 60),
('C2', 'Advanced Programming Lab', 'Academic Building 1', 'lab', 40),
('C3', 'Networking Lab', 'Academic Building 1', 'lab', 40),
('C4', 'IoT Lab', 'Academic Building 2', 'lab', 30),
('C5', 'DLD Lab', 'Academic Building 1', 'lab', 40),
('C6', 'Mobile Computing Lab', 'Academic Building 1', 'lab', 40);

-- =============================================
-- Add default room assignment to courses
-- Helps with auto-fill feature
-- =============================================
ALTER TABLE courses 
ADD COLUMN IF NOT EXISTS default_room_id INT UNSIGNED NULL,
ADD FOREIGN KEY (default_room_id) REFERENCES rooms(id) ON DELETE SET NULL;

