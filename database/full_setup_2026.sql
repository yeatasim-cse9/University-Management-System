-- =============================================
-- ACADEMIX - Academic Management System
-- Complete Database Setup (Schema + 2026 Seed Data)
-- Generated: 2026-01-02
-- =============================================

SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS academix;
CREATE DATABASE academix CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE academix;

SET time_zone = '+06:00';

-- =============================================
-- CORE TABLES
-- =============================================

-- Users Table
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'teacher', 'student') NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    first_login TINYINT(1) DEFAULT 1,
    last_login DATETIME NULL,
    failed_login_attempts INT DEFAULT 0,
    locked_until DATETIME NULL,
    remember_token VARCHAR(64) NULL,
    remember_token_expires DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Profiles Table
CREATE TABLE user_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    city VARCHAR(50) NULL,
    state VARCHAR(50) NULL,
    postal_code VARCHAR(20) NULL,
    country VARCHAR(50) DEFAULT 'Bangladesh',
    date_of_birth DATE NULL,
    gender ENUM('male', 'female', 'other') NULL,
    profile_picture VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Departments Table
CREATE TABLE departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT NULL,
    head_of_department INT UNSIGNED NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (head_of_department) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_code (code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Department Admins Table
CREATE TABLE department_admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_admin_dept (user_id, department_id),
    INDEX idx_user_id (user_id),
    INDEX idx_department_id (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Teachers Table
CREATE TABLE teachers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NOT NULL,
    employee_id VARCHAR(50) UNIQUE NOT NULL,
    designation VARCHAR(100) NULL,
    specialization TEXT NULL,
    joining_date DATE NULL,
    qualification TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_department_id (department_id),
    INDEX idx_employee_id (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Students Table
CREATE TABLE students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NOT NULL,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    batch_year INT NOT NULL,
    session VARCHAR(20) NULL,
    admission_date DATE NULL,
    blood_group VARCHAR(5) NULL,
    guardian_name VARCHAR(100) NULL,
    guardian_phone VARCHAR(20) NULL,
    guardian_email VARCHAR(100) NULL,
    current_semester INT DEFAULT 1,
    cgpa DECIMAL(3,2) DEFAULT 0.00,
    status ENUM('active', 'inactive', 'graduated', 'dropped') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_department_id (department_id),
    INDEX idx_student_id (student_id),
    INDEX idx_batch_year (batch_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ACADEMIC TABLES
-- =============================================

-- Academic Years Table
CREATE TABLE academic_years (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    year VARCHAR(20) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_year (year),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Semesters Table
CREATE TABLE semesters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academic_year_id INT UNSIGNED NOT NULL,
    name VARCHAR(50) NOT NULL,
    semester_number INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('upcoming', 'active', 'completed') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
    INDEX idx_academic_year_id (academic_year_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Courses Table
CREATE TABLE courses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED NOT NULL,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_name VARCHAR(200) NOT NULL,
    credit_hours DECIMAL(3,1) NOT NULL,
    course_type ENUM('theory', 'lab', 'project', 'thesis') DEFAULT 'theory',
    semester_number INT NOT NULL,
    description TEXT NULL,
    syllabus TEXT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    INDEX idx_department_id (department_id),
    INDEX idx_course_code (course_code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Course Offerings Table
CREATE TABLE course_offerings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    semester_id INT UNSIGNED NOT NULL,
    section VARCHAR(10) NOT NULL,
    max_students INT DEFAULT 60,
    enrolled_students INT DEFAULT 0,
    status ENUM('open', 'closed', 'completed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE,
    UNIQUE KEY unique_course_semester_section (course_id, semester_id, section),
    INDEX idx_course_id (course_id),
    INDEX idx_semester_id (semester_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Teacher Course Assignments Table
CREATE TABLE teacher_courses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    course_offering_id INT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (course_offering_id) REFERENCES course_offerings(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_offering (teacher_id, course_offering_id),
    INDEX idx_teacher_id (teacher_id),
    INDEX idx_course_offering_id (course_offering_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student Enrollments Table
CREATE TABLE enrollments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    course_offering_id INT UNSIGNED NOT NULL,
    enrollment_date DATE NOT NULL,
    status ENUM('enrolled', 'dropped', 'completed') DEFAULT 'enrolled',
    grade VARCHAR(5) NULL,
    grade_point DECIMAL(3,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_offering_id) REFERENCES course_offerings(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_offering (student_id, course_offering_id),
    INDEX idx_student_id (student_id),
    INDEX idx_course_offering_id (course_offering_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Class Schedule Table
CREATE TABLE class_schedule (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_offering_id INT UNSIGNED NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room_number VARCHAR(50) NULL,
    building VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_offering_id) REFERENCES course_offerings(id) ON DELETE CASCADE,
    INDEX idx_course_offering_id (course_offering_id),
    INDEX idx_day_of_week (day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Class Reschedules Table (NEW)
CREATE TABLE IF NOT EXISTS class_reschedules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_offering_id INT UNSIGNED NOT NULL,
    original_schedule_id INT UNSIGNED NULL,
    original_date DATE NOT NULL,
    new_date DATE NOT NULL,
    new_start_time TIME NOT NULL,
    new_end_time TIME NOT NULL,
    room_number VARCHAR(50) NULL,
    reason TEXT NULL,
    status ENUM('active', 'cancelled') DEFAULT 'active',
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_offering_id) REFERENCES course_offerings(id) ON DELETE CASCADE,
    FOREIGN KEY (original_schedule_id) REFERENCES class_schedule(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_course_offering_id (course_offering_id),
    INDEX idx_original_date (original_date),
    INDEX idx_new_date (new_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================
-- ATTENDANCE TABLES
-- =============================================

-- Attendance Table
CREATE TABLE attendance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT UNSIGNED NOT NULL,
    course_offering_id INT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') NOT NULL,
    marked_by INT UNSIGNED NOT NULL,
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (course_offering_id) REFERENCES course_offerings(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment_date (enrollment_id, attendance_date),
    INDEX idx_enrollment_id (enrollment_id),
    INDEX idx_course_offering_id (course_offering_id),
    INDEX idx_attendance_date (attendance_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- ASSESSMENT TABLES
-- =============================================

-- Grading Scheme Table
CREATE TABLE grading_scheme (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED NULL,
    grade VARCHAR(5) NOT NULL,
    min_marks DECIMAL(5,2) NOT NULL,
    max_marks DECIMAL(5,2) NOT NULL,
    grade_point DECIMAL(3,2) NOT NULL,
    description VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    INDEX idx_department_id (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Assessment Components Table
CREATE TABLE assessment_components (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED NULL,
    component_name VARCHAR(100) NOT NULL,
    weightage DECIMAL(5,2) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    INDEX idx_department_id (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Assignments Table
CREATE TABLE assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_offering_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    total_marks DECIMAL(5,2) NOT NULL,
    due_date DATETIME NOT NULL,
    attachment VARCHAR(255) NULL,
    created_by INT UNSIGNED NOT NULL,
    status ENUM('draft', 'published', 'closed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_offering_id) REFERENCES course_offerings(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_course_offering_id (course_offering_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Assignment Submissions Table
CREATE TABLE assignment_submissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    submission_file VARCHAR(255) NULL,
    submission_text TEXT NULL,
    submitted_at DATETIME NOT NULL,
    marks_obtained DECIMAL(5,2) NULL,
    feedback TEXT NULL,
    graded_by INT UNSIGNED NULL,
    graded_at DATETIME NULL,
    status ENUM('submitted', 'late', 'graded', 'resubmit') DEFAULT 'submitted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_assignment_student (assignment_id, student_id),
    INDEX idx_assignment_id (assignment_id),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student Marks Table
CREATE TABLE student_marks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT UNSIGNED NOT NULL,
    assessment_component_id INT UNSIGNED NOT NULL,
    marks_obtained DECIMAL(5,2) NULL,
    total_marks DECIMAL(5,2) NOT NULL,
    remarks TEXT NULL,
    entered_by INT UNSIGNED NOT NULL,
    verified_by INT UNSIGNED NULL,
    status ENUM('draft', 'submitted', 'verified', 'correction_requested') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (assessment_component_id) REFERENCES assessment_components(id) ON DELETE CASCADE,
    FOREIGN KEY (entered_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_enrollment_component (enrollment_id, assessment_component_id),
    INDEX idx_enrollment_id (enrollment_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Performance Reviews Table (NEW)
CREATE TABLE IF NOT EXISTS student_performance_reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    reviewer_id INT UNSIGNED NOT NULL,
    review_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_reviewer_id (reviewer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- COMMUNICATION & NOTIFICATION TABLES
-- =============================================

-- Notices Table
CREATE TABLE notices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    target_audience ENUM('all', 'students', 'teachers', 'admins', 'department') NOT NULL,
    department_id INT UNSIGNED NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    publish_date DATETIME NULL,
    expiry_date DATETIME NULL,
    attachment VARCHAR(255) NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_target_audience (target_audience),
    INDEX idx_publish_date (publish_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events Table
CREATE TABLE events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    event_date DATE NOT NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    location VARCHAR(200) NULL,
    event_type VARCHAR(50) NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_event_date (event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Documents Table
CREATE TABLE documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NULL,
    file_size INT NULL,
    category VARCHAR(100) NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Course Materials Table
CREATE TABLE course_materials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_offering_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_offering_id) REFERENCES course_offerings(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_course_offering_id (course_offering_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications Table
CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notice Interactions Table (Read/Delete status for notices)
CREATE TABLE notice_interactions (
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

-- Notification Preferences Table
CREATE TABLE notification_preferences (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    email_notifications TINYINT(1) DEFAULT 1,
    sms_notifications TINYINT(1) DEFAULT 0,
    push_notifications TINYINT(1) DEFAULT 1,
    assignment_notifications TINYINT(1) DEFAULT 1,
    attendance_alerts TINYINT(1) DEFAULT 1,
    grade_notifications TINYINT(1) DEFAULT 1,
    course_announcements TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_prefs (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Settings Table
CREATE TABLE system_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NULL,
    setting_type VARCHAR(50) DEFAULT 'string',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit Logs Table
CREATE TABLE audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100) NULL,
    record_id INT UNSIGNED NULL,
    old_values TEXT NULL,
    new_values TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login History Table
CREATE TABLE login_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    username VARCHAR(50) NOT NULL,
    status ENUM('success', 'failed') NOT NULL,
    failure_reason VARCHAR(200) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;


-- =============================================
-- SEED DATA (2026)
-- =============================================

-- System Settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('system_name', 'ACADEMIX', 'string', 'System name'),
('university_name', 'University of Barisal', 'string', 'University name'),
('session_timeout', '1800', 'integer', 'Session timeout in seconds'),
('max_login_attempts', '5', 'integer', 'Max failure attempts'),
('allowed_file_types', 'pdf,doc,docx,jpg,png', 'string', 'File types'),
('timezone', 'Asia/Dhaka', 'string', 'Timezone');

-- Grading Scheme
INSERT INTO grading_scheme (department_id, grade, min_marks, max_marks, grade_point, description) VALUES
(NULL, 'A+', 80.00, 100.00, 4.00, 'Excellent'),
(NULL, 'A', 75.00, 79.99, 3.75, 'Very Good'),
(NULL, 'A-', 70.00, 74.99, 3.50, 'Good'),
(NULL, 'B+', 65.00, 69.99, 3.25, 'Above Average'),
(NULL, 'B', 60.00, 64.99, 3.00, 'Average'),
(NULL, 'F', 0.00, 39.99, 0.00, 'Fail');

-- Departments
INSERT INTO departments (name, code, description, status) VALUES
('Computer Science & Engineering', 'CSE', 'Premier engineering department', 'active'),
('Electrical & Electronic Engineering', 'EEE', 'Core engineering department', 'active'),
('Business Administration', 'BBA', 'School of business', 'active');

-- Academic Year (2026)
INSERT INTO academic_years (year, start_date, end_date, status) VALUES
('2026-2027', '2026-01-01', '2026-12-31', 'active');

-- Semesters
INSERT INTO semesters (academic_year_id, name, semester_number, start_date, end_date, status) VALUES
(1, 'Spring 2026', 1, '2026-01-10', '2026-06-30', 'active'),
(1, 'Fall 2026', 2, '2026-07-01', '2026-12-31', 'upcoming');

-- Users (Password: 'password' is actually hashed, but here typical MD5 or password_hash needed. 
-- For simplicity in typical dumps, we might put plain logic or pre-hashed. 
-- Since your app uses password_verify, we need a hash. 
-- Let's assume the earlier seed used 'password123'. Hash for 'password123': $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi (Laravel default for 'password')
-- Or I can generate one. I will use a simple known hash for '123456' or similar if I could.
-- Actually the previous seed just used plain text in comments but executed hash in PHP? No, SQL dump usually has hash. 
-- I will use a standard hash for '123456': $2y$10$22n/..
-- BUT, your earlier 'academix.sql' comment said "Plain text password as per requirement".
-- Line 22: password VARCHAR(255) NOT NULL COMMENT 'Plain text password as per requirement'
-- So I will insert PLAIN TEXT '123456'.
INSERT INTO users (username, email, password, role, status, first_login) VALUES
('superadmin', 'super@academix.edu', '123456', 'super_admin', 'active', 0),
('admin_cse', 'admin.cse@academix.edu', '123456', 'admin', 'active', 0),
('teacher_alex', 'alex@academix.edu', '123456', 'teacher', 'active', 0),
('teacher_sarah', 'sarah@academix.edu', '123456', 'teacher', 'active', 0),
('student_john', 'john@academix.edu', '123456', 'student', 'active', 0),
('student_jane', 'jane@academix.edu', '123456', 'student', 'active', 0);

-- Profiles
INSERT INTO user_profiles (user_id, first_name, last_name, phone, city) VALUES
(1, 'Super', 'Administrator', '01700000000', 'Dhaka'),
(2, 'CSE', 'Admin', '01700000001', 'Barisal'),
(3, 'Alex', 'Johnson', '01700000002', 'Barisal'),
(4, 'Sarah', 'Connor', '01700000003', 'Barisal'),
(5, 'John', 'Doe', '01700000004', 'Barisal'),
(6, 'Jane', 'Smith', '01700000005', 'Barisal');

-- Department Admin Assign
INSERT INTO department_admins (user_id, department_id) VALUES
(2, 1); -- CSE Admin

-- Teachers Data
INSERT INTO teachers (user_id, department_id, employee_id, designation, specialization) VALUES
(3, 1, 'T-26001', 'Assistant Professor', 'AI & Machine Learning'),
(4, 1, 'T-26002', 'Lecturer', 'Software Engineering');

-- Students Data
INSERT INTO students (user_id, department_id, student_id, batch_year, session, current_semester, cgpa) VALUES
(5, 1, 'S-26101', 2026, '2025-26', 1, 3.75),
(6, 1, 'S-26102', 2026, '2025-26', 1, 3.90);

-- Courses
INSERT INTO courses (department_id, course_code, course_name, credit_hours, semester_number, status) VALUES
(1, 'CSE-101', 'Introduction to Computer Systems', 3.0, 1, 'active'),
(1, 'CSE-102', 'Structured Programming Language', 3.0, 1, 'active'),
(1, 'CSE-103', 'Discrete Mathematics', 3.0, 1, 'active');

-- Course Offerings (Spring 2026)
INSERT INTO course_offerings (course_id, semester_id, section, max_students, status) VALUES
(1, 1, 'A', 60, 'open'), -- CSE-101
(2, 1, 'A', 60, 'open'), -- CSE-102
(3, 1, 'A', 60, 'open'); -- CSE-103

-- Teacher Assignments
INSERT INTO teacher_courses (teacher_id, course_offering_id) VALUES
(1, 1), -- Alex teaches CSE-101
(2, 2), -- Sarah teaches CSE-102
(1, 3); -- Alex teaches CSE-103

-- Enrollments
INSERT INTO enrollments (student_id, course_offering_id, enrollment_date, status, grade, grade_point) VALUES
(1, 1, '2026-01-15', 'enrolled', 'A', 3.75),
(1, 2, '2026-01-15', 'enrolled', 'A+', 4.00),
(1, 3, '2026-01-15', 'enrolled', 'A-', 3.50),
(2, 1, '2026-01-15', 'enrolled', 'A+', 4.00),
(2, 2, '2026-01-15', 'enrolled', 'A+', 4.00),
(2, 3, '2026-01-15', 'enrolled', 'A', 3.75);

-- Attendance (Sample for one course, one day)
INSERT INTO attendance (enrollment_id, course_offering_id, attendance_date, status, marked_by) VALUES
(1, 1, '2026-01-20', 'present', 3),
(4, 1, '2026-01-20', 'present', 3),
(1, 1, '2026-01-22', 'present', 3),
(4, 1, '2026-01-22', 'absent', 3);

-- Notices
INSERT INTO notices (title, content, target_audience, priority, status, publish_date, created_by) VALUES
('Welcome to Spring 2026', 'Welcome all students to the new semester.', 'all', 'high', 'published', '2026-01-01 09:00:00', 1),
('Mid-term Schedule', 'Mid-terms will start from March 15th.', 'students', 'medium', 'published', '2026-02-20 09:00:00', 2);

-- Performance Reviews (The new feature)
INSERT INTO student_performance_reviews (student_id, reviewer_id, review_text) VALUES
(1, 1, 'John is showing exceptional leadership skills in class projects. Highly recommended for TA position next year.'),
(1, 2, 'Consistent performance in exams, but needs to improve attendance in lab sessions.'),
(2, 1, 'Jane is the top performer of this batch. Outstanding problem solving skills.');
