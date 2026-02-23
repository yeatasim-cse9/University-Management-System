-- =============================================
-- Enhanced Routine Management System
-- Migration: 5 Slots x 90 min, Fixed Lunch Break, Change Requests
-- Date: 2026-02-03
-- =============================================

USE academix;

-- =============================================
-- STEP 1: Create Change Requests Table
-- =============================================

CREATE TABLE IF NOT EXISTS routine_change_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    assignment_id INT UNSIGNED NULL,
    request_type ENUM('swap', 'time_change', 'room_change', 'cancel', 'general') NOT NULL,
    current_slot_id INT UNSIGNED NULL,
    requested_slot_id INT UNSIGNED NULL,
    requested_room VARCHAR(50) NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_response TEXT NULL,
    responded_by INT UNSIGNED NULL,
    responded_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES routine_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (assignment_id) REFERENCES routine_assignments(id) ON DELETE SET NULL,
    FOREIGN KEY (current_slot_id) REFERENCES routine_time_slots(id) ON DELETE SET NULL,
    FOREIGN KEY (requested_slot_id) REFERENCES routine_time_slots(id) ON DELETE SET NULL,
    FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_template (template_id),
    INDEX idx_teacher (teacher_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- STEP 2: Create Draft Notifications Table
-- =============================================

CREATE TABLE IF NOT EXISTS routine_draft_notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    viewed_at DATETIME NULL,
    FOREIGN KEY (template_id) REFERENCES routine_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_template_teacher (template_id, teacher_id),
    INDEX idx_template (template_id),
    INDEX idx_teacher (teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- STEP 3: Add Default Teacher/Room to Course Offerings
-- =============================================

-- Check if columns exist before adding
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'academix' 
    AND TABLE_NAME = 'course_offerings' 
    AND COLUMN_NAME = 'default_teacher_id');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE course_offerings ADD COLUMN default_teacher_id INT UNSIGNED NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'academix' 
    AND TABLE_NAME = 'course_offerings' 
    AND COLUMN_NAME = 'default_room');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE course_offerings ADD COLUMN default_room VARCHAR(50) NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'academix' 
    AND TABLE_NAME = 'course_offerings' 
    AND COLUMN_NAME = 'default_building');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE course_offerings ADD COLUMN default_building VARCHAR(100) NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================
-- STEP 4: Update Slot Types for 90-min Duration
-- =============================================

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Update existing slot types instead of truncating
-- First check if rows exist, then update or insert
UPDATE routine_slot_types SET duration_minutes = 90, color_code = '#3b82f6' WHERE name = 'Theory';
UPDATE routine_slot_types SET duration_minutes = 90, color_code = '#8b5cf6' WHERE name = 'Lab';
UPDATE routine_slot_types SET duration_minutes = 30, color_code = '#94a3b8' WHERE name = 'Break';

-- Insert if not exists
INSERT IGNORE INTO routine_slot_types (name, duration_minutes, color_code, is_active) VALUES
('Theory', 90, '#3b82f6', 1),
('Lab', 90, '#8b5cf6', 1),
('Break', 30, '#94a3b8', 1);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- STEP 5: Add System Settings for Slot Configuration
-- =============================================

INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('routine_slot_duration', '90', 'integer', 'Default slot duration in minutes'),
('routine_slots_per_day', '5', 'integer', 'Number of slots per day'),
('routine_break_start', '13:30', 'time', 'Fixed lunch/prayer break start time'),
('routine_break_end', '14:00', 'time', 'Fixed lunch/prayer break end time'),
('routine_day_start', '09:00', 'time', 'Class start time'),
('routine_day_end', '17:00', 'time', 'Class end time'),
('routine_working_days', 'Sunday,Monday,Tuesday,Wednesday,Thursday', 'string', 'Working days for routine')
ON DUPLICATE KEY UPDATE 
    setting_value = VALUES(setting_value),
    description = VALUES(description);

-- =============================================
-- STEP 6: Generate Default Time Slots
-- =============================================

-- This will be done via the admin UI using "Generate Standard Slots" button
-- The slots follow this pattern:
-- Slot 1: 09:00 - 10:30
-- Slot 2: 10:30 - 12:00
-- Slot 3: 12:00 - 13:30
-- BREAK:  13:30 - 14:00 (shown as break row)
-- Slot 4: 14:00 - 15:30
-- Slot 5: 15:30 - 17:00

-- =============================================
-- STEP 7: Add Batch Information Column
-- =============================================

-- Add batch column to routine_assignments for combined view
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'academix' 
    AND TABLE_NAME = 'routine_assignments' 
    AND COLUMN_NAME = 'batch_name');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE routine_assignments ADD COLUMN batch_name VARCHAR(50) NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
