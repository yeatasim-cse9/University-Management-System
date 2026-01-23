-- Migration: Create notice_interactions table
-- Fixes missing table error in get_user_notifications()

CREATE TABLE IF NOT EXISTS notice_interactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notice_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    is_deleted TINYINT(1) DEFAULT 0,
    read_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_interaction (notice_id, user_id),
    INDEX idx_notice_id (notice_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
