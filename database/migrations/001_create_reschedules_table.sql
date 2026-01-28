CREATE TABLE IF NOT EXISTS reschedule_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_id INT UNSIGNED NOT NULL,
    status ENUM('pending', 'threshold_reached', 'rescheduled') DEFAULT 'pending',
    teacher_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES class_schedule(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS votes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    suggested_date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES reschedule_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vote (request_id, student_id)
);
