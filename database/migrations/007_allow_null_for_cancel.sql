-- Migration: Allow NULL values for new_slot_id and new_room_id in class_reschedules
-- This is needed to support class cancellations (reschedule_type='cancel')
-- where no new slot/room is assigned

ALTER TABLE class_reschedules 
    MODIFY new_slot_id INT UNSIGNED NULL COMMENT 'The new time slot (NULL for cancellations)',
    MODIFY new_room_id INT UNSIGNED NULL COMMENT 'The room for the rescheduled class (NULL for cancellations)';
