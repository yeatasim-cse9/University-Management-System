-- Migration: Create Syllabus Topics Table
-- Feature: Course Syllabus Tracking

CREATE TABLE IF NOT EXISTS syllabus_topics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_offering_id INT UNSIGNED NOT NULL,
    topic_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    completed_at DATETIME NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_offering_id) REFERENCES course_offerings(id) ON DELETE CASCADE,
    INDEX idx_course_offering_id (course_offering_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
