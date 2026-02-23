-- =============================================
-- Dynamic Routine Management System
-- Migration: Create routine management tables
-- Date: 2026-02-03
-- =============================================

USE academix;

-- Disable foreign key checks to allow dropping tables
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================
-- STEP 1: Clean up old schedule system
-- =============================================

-- Drop old schedule-related data
DROP TABLE IF EXISTS class_reschedules;
DROP TABLE IF EXISTS class_schedule;

-- =============================================
-- STEP 2: Create new routine management tables
-- =============================================

-- Slot Types (Theory, Lab, etc.)
CREATE TABLE IF NOT EXISTS routine_slot_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    duration_minutes INT NOT NULL,
    color_code VARCHAR(7) DEFAULT '#3b82f6',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Time Slots (The actual periods in the day)
CREATE TABLE IF NOT EXISTS routine_time_slots (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slot_type_id INT UNSIGNED NOT NULL,
    day_of_week ENUM('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    slot_number INT NOT NULL,
    department_id INT UNSIGNED NULL,
    is_break TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (slot_type_id) REFERENCES routine_slot_types(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    INDEX idx_day (day_of_week),
    INDEX idx_department (department_id),
    UNIQUE KEY unique_slot (day_of_week, slot_number, department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Routine Templates (Draft/Published routines)
CREATE TABLE IF NOT EXISTS routine_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    semester_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NOT NULL,
    status ENUM('draft','published','archived') DEFAULT 'draft',
    created_by INT UNSIGNED NOT NULL,
    published_at DATETIME NULL,
    published_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (published_by) REFERENCES users(id),
    INDEX idx_status (status),
    INDEX idx_semester (semester_id),
    INDEX idx_department (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Routine Assignments (Teacher + Course + Slot mapping)
CREATE TABLE IF NOT EXISTS routine_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_id INT UNSIGNED NOT NULL,
    time_slot_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    course_offering_id INT UNSIGNED NOT NULL,
    room_number VARCHAR(50) NULL,
    building VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES routine_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (time_slot_id) REFERENCES routine_time_slots(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (course_offering_id) REFERENCES course_offerings(id) ON DELETE CASCADE,
    INDEX idx_template (template_id),
    INDEX idx_teacher (teacher_id),
    INDEX idx_slot (time_slot_id),
    UNIQUE KEY unique_slot_template (template_id, time_slot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- STEP 3: Insert default slot types
-- =============================================

INSERT INTO routine_slot_types (name, duration_minutes, color_code) VALUES
('Theory', 50, '#3b82f6'),
('Lab', 100, '#8b5cf6'),
('Break', 20, '#6b7280');

-- =============================================
-- STEP 4: Add max_classes_per_day setting
-- =============================================

INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('max_classes_per_teacher_per_day', '3', 'integer', 'Maximum number of classes a teacher can have per day')
ON DUPLICATE KEY UPDATE setting_value = '3';

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;
