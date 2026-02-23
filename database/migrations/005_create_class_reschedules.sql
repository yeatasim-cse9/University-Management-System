-- Migration: Create class_reschedules table for date-specific class rescheduling
-- This table stores temporary reschedules that only affect specific dates

CREATE TABLE IF NOT EXISTS class_reschedules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    routine_assignment_id INT UNSIGNED NOT NULL COMMENT 'The original routine assignment being rescheduled',
    original_date DATE NOT NULL COMMENT 'The date of the class being rescheduled',
    new_date DATE NOT NULL COMMENT 'The new date for the class (can be same day, different slot)',
    new_slot_id INT UNSIGNED NOT NULL COMMENT 'The new time slot',
    new_room_id INT UNSIGNED NOT NULL COMMENT 'The room for the rescheduled class',
    teacher_id INT UNSIGNED NOT NULL COMMENT 'Teacher who created the reschedule',
    reason TEXT COMMENT 'Optional reason for rescheduling',
    status ENUM('active', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (routine_assignment_id) REFERENCES routine_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (new_slot_id) REFERENCES routine_slots(id),
    FOREIGN KEY (new_room_id) REFERENCES rooms(id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    
    -- Prevent duplicate reschedules for the same class on the same date
    UNIQUE KEY unique_reschedule (routine_assignment_id, original_date),
    
    -- Index for efficient date-based queries
    INDEX idx_original_date (original_date),
    INDEX idx_new_date_slot (new_date, new_slot_id)
);
