-- Migration: Add reschedule_type column to class_reschedules
-- Supports 'reschedule' (existing behavior) and 'cancel' (new cancellation feature)

ALTER TABLE class_reschedules 
ADD COLUMN reschedule_type ENUM('reschedule', 'cancel') DEFAULT 'reschedule' AFTER course_offering_id;
