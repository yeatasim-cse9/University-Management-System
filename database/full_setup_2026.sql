-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 24, 2026 at 08:01 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `academix`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

CREATE TABLE `academic_years` (
  `id` int(10) UNSIGNED NOT NULL,
  `year` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','inactive','completed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`id`, `year`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES
(1, '2025-2026', '2025-01-01', '2025-12-31', 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41');

-- --------------------------------------------------------

--
-- Table structure for table `assessment_components`
--

CREATE TABLE `assessment_components` (
  `id` int(10) UNSIGNED NOT NULL,
  `department_id` int(10) UNSIGNED DEFAULT NULL,
  `component_name` varchar(100) NOT NULL,
  `weightage` decimal(5,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_offering_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `total_marks` decimal(5,2) NOT NULL,
  `due_date` datetime NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `status` enum('draft','published','closed') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignment_submissions`
--

CREATE TABLE `assignment_submissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `assignment_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `submission_file` varchar(255) DEFAULT NULL,
  `submission_text` text DEFAULT NULL,
  `submitted_at` datetime NOT NULL,
  `marks_obtained` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `graded_by` int(10) UNSIGNED DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL,
  `status` enum('submitted','late','graded','resubmit') DEFAULT 'submitted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(10) UNSIGNED NOT NULL,
  `enrollment_id` int(10) UNSIGNED NOT NULL,
  `course_offering_id` int(10) UNSIGNED NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL,
  `marked_by` int(10) UNSIGNED NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(10) UNSIGNED DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'create_user', 'users', 2, NULL, '{\"username\":\"shahin\",\"role\":\"student\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 07:50:33'),
(2, 1, 'update_user', 'users', 2, NULL, '{\"old_role\":\"student\",\"new_role\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 07:50:43'),
(3, 1, 'update_user', 'users', 2, NULL, '{\"old_role\":\"admin\",\"new_role\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 07:55:54'),
(4, 1, 'update_user', 'users', 2, NULL, '{\"old_role\":\"admin\",\"new_role\":\"student\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 07:56:27'),
(5, 1, 'update_user', 'users', 3, NULL, '{\"old_role\":\"teacher\",\"new_role\":\"admin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 07:58:06'),
(6, 3, 'logout', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:58:32'),
(7, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:58:40'),
(8, 3, 'password_change', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:58:54'),
(9, 3, 'assign_teacher', 'teacher_courses', 68, NULL, '{\"teacher_id\":2,\"offering_id\":33}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 08:00:10'),
(10, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 08:00:40'),
(11, 4, 'password_change', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 08:00:59'),
(12, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 12:30:51'),
(13, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 15:25:04'),
(14, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 15:26:52'),
(15, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 15:27:27'),
(16, 3, 'deactivate_teacher', 'users', 11, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 15:29:45'),
(17, 3, 'deactivate_teacher', 'users', 11, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 15:29:52'),
(18, 3, 'deactivate_teacher', 'users', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 15:30:08'),
(19, 1, 'update_department', 'departments', 1, NULL, '{\"name\":\"Computer Science and engineering\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 15:33:39'),
(20, 1, 'update_grading_scheme', 'grading_scheme', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 15:35:51'),
(21, 1, 'update_department', 'departments', 1, NULL, '{\"name\":\"Computer Science and engineering\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 15:41:21'),
(22, 3, 'deactivate_student', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 15:43:14'),
(23, 1, 'logout', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 16:07:51'),
(24, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 16:21:42'),
(25, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 04:58:04'),
(26, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 05:57:32'),
(27, 4, 'logout', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 06:24:55'),
(28, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 06:25:53'),
(29, 3, 'logout', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 16:22:07'),
(30, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 16:22:37'),
(31, 13, 'login', 'users', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 16:36:05'),
(32, 13, 'password_change', 'users', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 16:36:21'),
(33, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 17:33:13'),
(34, 4, 'logout', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 17:33:22'),
(35, 13, 'login', 'users', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 17:34:12'),
(36, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 17:46:39'),
(37, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-24 08:34:03'),
(38, 4, 'logout', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-24 08:34:32'),
(39, 13, 'login', 'users', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-24 08:35:24'),
(40, 13, 'logout', 'users', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-24 08:35:56'),
(41, 1, 'login', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-24 08:36:05'),
(42, 1, 'logout', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-24 08:46:26'),
(43, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 06:31:36'),
(44, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 17:58:25'),
(45, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 18:01:17'),
(46, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 19:49:37'),
(47, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 04:00:47'),
(48, 3, 'assign_teacher', 'teacher_courses', 69, NULL, '{\"teacher_id\":2,\"offering_id\":33}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 04:14:07'),
(49, 3, 'assign_teacher', 'teacher_courses', 70, NULL, '{\"teacher_id\":2,\"offering_id\":67}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 04:14:40'),
(50, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 04:15:21'),
(51, 13, 'login', 'users', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 05:10:11'),
(52, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 05:44:00'),
(53, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 05:44:23'),
(54, 13, 'login', 'users', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 05:46:38'),
(55, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 07:57:56'),
(56, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 07:58:06'),
(57, 13, 'login', 'users', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 07:58:15'),
(58, 13, 'login', 'users', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 08:35:31'),
(59, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 08:37:12'),
(60, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 08:37:57'),
(61, 13, 'login', 'users', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 10:02:10'),
(62, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 10:02:25'),
(63, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 10:02:47'),
(64, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 17:05:35'),
(65, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 17:06:22'),
(66, 13, 'login', 'users', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 17:18:48'),
(67, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 19:15:37'),
(68, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 19:30:10'),
(69, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-13 18:58:54'),
(70, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-13 19:08:21'),
(71, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-14 05:52:01'),
(72, 13, 'login', 'users', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-14 06:04:12'),
(73, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-14 06:23:00'),
(74, 4, 'logout', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-14 06:26:19'),
(75, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-14 06:26:24'),
(76, 4, 'logout', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-14 06:45:57'),
(77, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-14 06:46:09'),
(78, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-16 05:42:16'),
(79, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-16 05:43:15'),
(80, 13, 'login', 'users', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-16 06:02:50'),
(81, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-17 09:25:38'),
(82, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-17 09:25:52'),
(83, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 16:46:19'),
(84, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 16:46:35'),
(85, 13, 'login', 'users', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 16:51:04'),
(86, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 17:24:29'),
(87, 13, 'login', 'users', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 17:28:08'),
(88, 3, 'logout', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 18:08:40'),
(89, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 18:08:44'),
(90, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 18:09:04'),
(91, 13, 'login', 'users', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 15:37:37'),
(92, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 15:38:38'),
(93, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 15:40:32'),
(94, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 16:18:48'),
(95, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 16:18:58'),
(96, 13, 'login', 'users', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 16:24:39'),
(97, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 17:57:07'),
(98, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 19:37:03'),
(99, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 19:43:00'),
(100, 13, 'login', 'users', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 20:01:11'),
(101, 13, 'login', 'users', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 04:54:07'),
(102, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 04:54:53'),
(103, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 05:01:38'),
(104, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 06:18:52'),
(105, 4, 'login', 'users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 06:19:48'),
(106, 13, 'login', 'users', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 06:20:50');

-- --------------------------------------------------------

--
-- Table structure for table `class_reschedules`
--

CREATE TABLE `class_reschedules` (
  `id` int(10) UNSIGNED NOT NULL,
  `reschedule_type` enum('reschedule','cancel') DEFAULT 'reschedule',
  `routine_assignment_id` int(10) UNSIGNED NOT NULL COMMENT 'The original routine assignment being rescheduled',
  `original_date` date NOT NULL COMMENT 'The date of the class being rescheduled',
  `new_date` date NOT NULL COMMENT 'The new date for the class (can be same day, different slot)',
  `new_slot_id` int(10) UNSIGNED DEFAULT NULL,
  `new_room_id` int(10) UNSIGNED DEFAULT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL COMMENT 'Teacher who created the reschedule',
  `reason` text DEFAULT NULL COMMENT 'Optional reason for rescheduling',
  `status` enum('active','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_reschedules`
--

INSERT INTO `class_reschedules` (`id`, `reschedule_type`, `routine_assignment_id`, `original_date`, `new_date`, `new_slot_id`, `new_room_id`, `teacher_id`, `reason`, `status`, `created_at`, `updated_at`) VALUES
(4, 'cancel', 28, '2026-03-01', '2026-03-01', NULL, NULL, 2, '', 'active', '2026-02-24 06:20:13', '2026-02-24 06:20:13'),
(7, 'reschedule', 37, '2026-03-15', '2026-03-01', 2, 1, 2, '', 'active', '2026-02-24 06:35:20', '2026-02-24 06:35:20'),
(26, 'reschedule', 22, '2026-03-01', '2026-03-01', 1, 1, 2, NULL, 'active', '2026-02-24 06:49:23', '2026-02-24 06:49:23'),
(27, 'reschedule', 22, '2026-03-01', '2026-03-01', 2, 1, 2, NULL, 'active', '2026-02-24 06:49:23', '2026-02-24 06:49:23'),
(28, 'reschedule', 37, '2026-03-01', '2026-02-25', 2, 1, 2, '', 'active', '2026-02-24 06:51:48', '2026-02-24 06:51:48'),
(29, 'reschedule', 37, '2026-03-01', '2026-02-25', 3, 1, 2, '', 'active', '2026-02-24 06:51:48', '2026-02-24 06:51:48');

-- --------------------------------------------------------

--
-- Table structure for table `class_schedule`
--

CREATE TABLE `class_schedule` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_offering_id` int(10) UNSIGNED NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room_number` varchar(50) DEFAULT NULL,
  `building` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(10) UNSIGNED NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(200) NOT NULL,
  `credit_hours` decimal(3,1) NOT NULL,
  `course_type` enum('theory','lab','project','thesis') DEFAULT 'theory',
  `semester_number` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `syllabus` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `default_room_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `department_id`, `course_code`, `course_name`, `credit_hours`, `course_type`, `semester_number`, `description`, `syllabus`, `status`, `created_at`, `updated_at`, `deleted_at`, `default_room_id`) VALUES
(1, 1, 'PHY-1105', 'Course PHY-1105', 3.0, '', 1, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(2, 1, 'CSE-1101', 'Course CSE-1101', 3.0, '', 1, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(3, 1, 'CSE-1103', 'Course CSE-1103', 3.0, '', 1, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(4, 1, 'Chem-1107', 'Course Chem-1107', 3.0, '', 1, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(5, 1, 'CSE-1102', 'Course CSE-1102', 3.0, '', 1, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(6, 1, 'Math-1109', 'Course Math-1109', 3.0, '', 1, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(7, 1, 'Phy-1106', 'Course Phy-1106', 3.0, '', 1, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(8, 1, 'Chem-1108', 'Course Chem-1108', 3.0, '', 1, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(9, 1, 'CSE-1104', 'Course CSE-1104', 3.0, '', 1, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(10, 1, 'Competitive Programm', 'Course Competitive Programming', 3.0, '', 1, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(11, 1, 'ENG-1108', 'Course ENG-1108', 3.0, '', 1, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(12, 1, 'STAT-1211', 'Course STAT-1211', 3.0, '', 2, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(13, 1, 'EEE-1205', 'Course EEE-1205', 3.0, '', 2, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(14, 1, 'Math-1209', 'Course Math-1209', 3.0, '', 2, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(15, 1, 'CSE-1201', 'Course CSE-1201', 3.0, '', 2, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(16, 1, 'EEE-1206', 'Course EEE-1206', 3.0, '', 2, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(17, 1, 'CSE-1203', 'Course CSE-1203', 3.0, '', 2, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(18, 1, 'EEE-1208', 'Course EEE-1208', 3.0, '', 2, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(19, 1, 'CSE-1204', 'Course CSE-1204', 3.0, '', 2, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(20, 1, 'EEE-1207', 'Course EEE-1207', 3.0, '', 2, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(21, 1, 'CSE-1202', 'Course CSE-1202', 3.0, '', 2, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(22, 1, 'CSE-2210', 'Course CSE-2210', 3.0, '', 4, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(23, 1, 'CSE-2205', 'Course CSE-2205', 3.0, '', 4, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(24, 1, 'CSE-2201', 'Course CSE-2201', 3.0, '', 4, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(25, 1, 'Math-2211', 'Course Math-2211', 3.0, '', 4, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(26, 1, 'CSE-2208', 'Course CSE-2208', 3.0, '', 4, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(27, 1, 'CSE-2202', 'Course CSE-2202', 3.0, '', 4, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(28, 1, 'CSE-2207', 'Course CSE-2207', 3.0, '', 4, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(29, 1, 'CSE-2203', 'Course CSE-2203', 3.0, '', 4, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(30, 1, 'CSE-3101', 'Course CSE-3101', 3.0, '', 5, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(31, 1, 'Hum-3109', 'Course Hum-3109', 3.0, '', 5, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(32, 1, 'CSE-3107', 'Course CSE-3107', 3.0, '', 5, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(33, 1, 'CSE-3104', 'Course CSE-3104', 3.0, '', 5, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(34, 1, 'CSE-3103', 'Course CSE-3103', 3.0, '', 5, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(35, 1, 'EEE-3102', 'Course EEE-3102', 3.0, '', 5, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(36, 1, 'Hum-3111', 'Course Hum-3111', 3.0, '', 5, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(37, 1, 'CSE-3105', 'Course CSE-3105', 3.0, '', 5, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(38, 1, 'CSE-3106', 'Course CSE-3106', 3.0, '', 5, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(39, 1, 'CSE-3114', 'Course CSE-3114', 3.0, '', 5, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(40, 1, 'CSE-3205', 'Course CSE-3205', 3.0, '', 6, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(41, 1, 'CSE-3207', 'Course CSE-3207', 3.0, '', 6, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(42, 1, 'CSE-3208', 'Course CSE-3208', 3.0, '', 6, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(43, 1, 'CSE-3201', 'Course CSE-3201', 3.0, '', 6, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(44, 1, 'CSE-3212', 'Course CSE-3212', 3.0, '', 6, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(45, 1, 'CSE-3209', 'Course CSE-3209', 3.0, '', 6, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(46, 1, 'CSE-3206', 'Course CSE-3206', 3.0, '', 6, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(47, 1, 'CSE-3210', 'Course CSE-3210', 3.0, '', 6, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(48, 1, 'CSE-3203', 'Course CSE-3203', 3.0, '', 6, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(49, 1, 'CSE-4226', 'Course CSE-4226', 3.0, '', 8, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(50, 1, 'CSE-4214', 'Course CSE-4214', 3.0, '', 8, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(51, 1, 'CSE-4213', 'Course CSE-4213', 3.0, '', 8, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(52, 1, 'CSE-4201', 'Course CSE-4201', 3.0, '', 8, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL),
(53, 1, 'CSE-4225', 'Course CSE-4225', 3.0, '', 8, NULL, NULL, 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `course_materials`
--

CREATE TABLE `course_materials` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_offering_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `uploaded_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_offerings`
--

CREATE TABLE `course_offerings` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `semester_id` int(10) UNSIGNED NOT NULL,
  `section` varchar(10) NOT NULL,
  `max_students` int(11) DEFAULT 60,
  `enrolled_students` int(11) DEFAULT 0,
  `status` enum('open','closed','completed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_offerings`
--

INSERT INTO `course_offerings` (`id`, `course_id`, `semester_id`, `section`, `max_students`, `enrolled_students`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(2, 2, 1, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(3, 3, 1, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(4, 4, 1, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(5, 5, 1, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(6, 6, 1, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(7, 7, 1, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(8, 8, 1, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(9, 9, 1, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(10, 10, 1, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(11, 11, 1, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(12, 12, 2, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(13, 13, 2, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(14, 14, 2, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(15, 15, 2, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(16, 16, 2, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(17, 17, 2, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(18, 18, 2, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(19, 19, 2, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(20, 20, 2, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(21, 21, 2, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(22, 22, 3, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(23, 23, 3, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(24, 24, 3, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(25, 25, 3, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(26, 26, 3, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(27, 27, 3, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(28, 28, 3, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(29, 29, 3, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(30, 30, 4, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(31, 31, 4, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(32, 32, 4, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(33, 33, 4, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(34, 34, 4, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(35, 35, 4, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(36, 36, 4, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(37, 37, 4, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(38, 38, 4, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(39, 39, 4, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(40, 40, 5, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(41, 41, 5, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(42, 42, 5, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(43, 43, 5, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(44, 44, 5, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(45, 45, 5, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(46, 46, 5, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(47, 47, 5, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(48, 48, 5, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(49, 49, 6, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(50, 50, 6, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(51, 51, 6, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(52, 52, 6, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(53, 53, 6, 'A', 60, 0, 'open', '2026-01-21 07:54:41', '2026-01-21 07:54:41');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `head_of_department` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `code`, `description`, `head_of_department`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Computer Science and engineering', 'CSE', '', NULL, 'active', '2026-01-21 07:54:41', '2026-01-22 15:33:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `department_admins`
--

CREATE TABLE `department_admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `department_admins`
--

INSERT INTO `department_admins` (`id`, `user_id`, `department_id`, `assigned_at`) VALUES
(2, 3, 1, '2026-01-21 07:58:06');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `uploaded_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `course_offering_id` int(10) UNSIGNED NOT NULL,
  `enrollment_date` date NOT NULL,
  `status` enum('enrolled','dropped','completed') DEFAULT 'enrolled',
  `grade` varchar(5) DEFAULT NULL,
  `grade_point` decimal(3,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `course_offering_id`, `enrollment_date`, `status`, `grade`, `grade_point`, `created_at`, `updated_at`) VALUES
(1, 45, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(2, 45, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(3, 45, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(4, 45, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(5, 45, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(6, 45, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(7, 45, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(8, 45, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(9, 45, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(10, 45, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(11, 46, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(12, 46, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(13, 46, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(14, 46, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(15, 46, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(16, 46, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(17, 46, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(18, 46, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(19, 46, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(20, 46, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(21, 47, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(22, 47, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(23, 47, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(24, 47, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(25, 47, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(26, 47, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(27, 47, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(28, 47, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(29, 47, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(30, 47, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(31, 48, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(32, 48, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(33, 48, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(34, 48, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(35, 48, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(36, 48, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(37, 48, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(38, 48, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(39, 48, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(40, 48, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:56', '2026-01-23 05:55:56'),
(41, 49, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(42, 49, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(43, 49, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(44, 49, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(45, 49, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(46, 49, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(47, 49, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(48, 49, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(49, 49, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(50, 49, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(51, 50, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(52, 50, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(53, 50, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(54, 50, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(55, 50, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(56, 50, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(57, 50, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(58, 50, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(59, 50, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(60, 50, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(61, 51, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(62, 51, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(63, 51, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(64, 51, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(65, 51, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(66, 51, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(67, 51, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(68, 51, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(69, 51, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(70, 51, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(71, 52, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(72, 52, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(73, 52, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(74, 52, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(75, 52, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(76, 52, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(77, 52, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(78, 52, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(79, 52, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(80, 52, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(81, 53, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(82, 53, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(83, 53, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(84, 53, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(85, 53, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(86, 53, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(87, 53, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(88, 53, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(89, 53, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(90, 53, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(91, 54, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(92, 54, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(93, 54, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(94, 54, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(95, 54, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(96, 54, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(97, 54, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(98, 54, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(99, 54, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(100, 54, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(121, 55, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(122, 55, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(123, 55, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(124, 55, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(125, 55, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(126, 55, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(127, 55, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(128, 55, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(129, 55, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(130, 55, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(131, 56, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(132, 56, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(133, 56, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(134, 56, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(135, 56, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(136, 56, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(137, 56, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(138, 56, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(139, 56, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(140, 56, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(141, 57, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(142, 57, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(143, 57, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(144, 57, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(145, 57, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(146, 57, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(147, 57, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(148, 57, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(149, 57, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(150, 57, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(151, 58, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(152, 58, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(153, 58, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(154, 58, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(155, 58, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(156, 58, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(157, 58, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(158, 58, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(159, 58, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(160, 58, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(171, 59, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(172, 59, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(173, 59, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(174, 59, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(175, 59, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(176, 59, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(177, 59, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(178, 59, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(179, 59, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(180, 59, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(181, 60, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(182, 60, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(183, 60, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(184, 60, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(185, 60, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(186, 60, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(187, 60, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(188, 60, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(189, 60, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(190, 60, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(191, 61, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(192, 61, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(193, 61, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(194, 61, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(195, 61, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(196, 61, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(197, 61, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(198, 61, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(199, 61, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(200, 61, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(201, 62, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(202, 62, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(203, 62, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(204, 62, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(205, 62, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(206, 62, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(207, 62, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(208, 62, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(209, 62, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(210, 62, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(211, 63, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(212, 63, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(213, 63, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(214, 63, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(215, 63, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(216, 63, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(217, 63, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(218, 63, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(219, 63, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(220, 63, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(221, 64, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(222, 64, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(223, 64, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(224, 64, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(225, 64, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(226, 64, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(227, 64, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(228, 64, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(229, 64, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(230, 64, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(231, 65, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(232, 65, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(233, 65, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(234, 65, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(235, 65, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(236, 65, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(237, 65, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(238, 65, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(239, 65, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(240, 65, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(241, 66, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(242, 66, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(243, 66, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(244, 66, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(245, 66, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(246, 66, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(247, 66, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(248, 66, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(249, 66, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(250, 66, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(251, 67, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(252, 67, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(253, 67, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(254, 67, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(255, 67, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(256, 67, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(257, 67, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(258, 67, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(259, 67, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(260, 67, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(261, 68, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(262, 68, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(263, 68, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(264, 68, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(265, 68, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(266, 68, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(267, 68, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(268, 68, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(269, 68, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(270, 68, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(271, 69, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(272, 69, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(273, 69, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(274, 69, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(275, 69, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(276, 69, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(277, 69, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(278, 69, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(279, 69, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(280, 69, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(281, 70, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(282, 70, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(283, 70, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(284, 70, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(285, 70, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(286, 70, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(287, 70, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(288, 70, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(289, 70, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(290, 70, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(291, 71, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(292, 71, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(293, 71, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(294, 71, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(295, 71, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(296, 71, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(297, 71, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(298, 71, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(299, 71, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(300, 71, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(301, 72, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(302, 72, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(303, 72, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(304, 72, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(305, 72, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(306, 72, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(307, 72, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(308, 72, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(309, 72, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(310, 72, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(311, 73, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(312, 73, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(313, 73, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(314, 73, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(315, 73, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(316, 73, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(317, 73, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(318, 73, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(319, 73, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(320, 73, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(331, 74, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(332, 74, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(333, 74, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(334, 74, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(335, 74, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(336, 74, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(337, 74, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(338, 74, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(339, 74, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(340, 74, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(341, 75, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(342, 75, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(343, 75, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(344, 75, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(345, 75, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(346, 75, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(347, 75, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(348, 75, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(349, 75, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(350, 75, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(351, 76, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(352, 76, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(353, 76, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(354, 76, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(355, 76, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(356, 76, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(357, 76, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(358, 76, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(359, 76, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(360, 76, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(361, 77, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(362, 77, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(363, 77, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(364, 77, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(365, 77, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(366, 77, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(367, 77, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(368, 77, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(369, 77, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(370, 77, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(371, 78, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(372, 78, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(373, 78, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(374, 78, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(375, 78, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(376, 78, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(377, 78, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(378, 78, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(379, 78, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(380, 78, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(381, 79, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(382, 79, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(383, 79, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(384, 79, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(385, 79, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(386, 79, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(387, 79, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(388, 79, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(389, 79, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(390, 79, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(391, 80, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(392, 80, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(393, 80, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(394, 80, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(395, 80, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(396, 80, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(397, 80, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(398, 80, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(399, 80, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(400, 80, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(401, 81, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(402, 81, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(403, 81, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(404, 81, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(405, 81, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(406, 81, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(407, 81, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(408, 81, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(409, 81, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(410, 81, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(411, 82, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(412, 82, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(413, 82, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(414, 82, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(415, 82, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(416, 82, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(417, 82, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(418, 82, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(419, 82, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(420, 82, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(421, 83, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(422, 83, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(423, 83, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(424, 83, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(425, 83, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(426, 83, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(427, 83, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(428, 83, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(429, 83, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(430, 83, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 05:55:57', '2026-01-23 05:55:57'),
(861, 84, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(862, 84, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(863, 84, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(864, 84, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(865, 84, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(866, 84, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(867, 84, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(868, 84, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(869, 84, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(870, 84, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(871, 85, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(872, 85, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(873, 85, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(874, 85, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(875, 85, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(876, 85, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(877, 85, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(878, 85, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(879, 85, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(880, 85, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(881, 86, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(882, 86, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(883, 86, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(884, 86, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(885, 86, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(886, 86, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(887, 86, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(888, 86, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(889, 86, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(890, 86, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(891, 87, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(892, 87, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(893, 87, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(894, 87, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(895, 87, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(896, 87, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(897, 87, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(898, 87, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(899, 87, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(900, 87, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(901, 88, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(902, 88, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(903, 88, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(904, 88, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(905, 88, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(906, 88, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(907, 88, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(908, 88, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(909, 88, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(910, 88, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(911, 89, 30, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(912, 89, 31, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(913, 89, 32, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(914, 89, 33, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(915, 89, 34, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(916, 89, 35, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(917, 89, 36, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(918, 89, 37, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(919, 89, 38, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(920, 89, 39, '0000-00-00', 'enrolled', NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `event_type` varchar(50) DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grading_scheme`
--

CREATE TABLE `grading_scheme` (
  `id` int(10) UNSIGNED NOT NULL,
  `department_id` int(10) UNSIGNED DEFAULT NULL,
  `grade` varchar(5) NOT NULL,
  `min_marks` decimal(5,2) NOT NULL,
  `max_marks` decimal(5,2) NOT NULL,
  `grade_point` decimal(3,2) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grading_scheme`
--

INSERT INTO `grading_scheme` (`id`, `department_id`, `grade`, `min_marks`, `max_marks`, `grade_point`, `description`, `created_at`, `updated_at`) VALUES
(1, NULL, 'A+', 80.00, 100.00, 4.00, 'Outstanding', '2026-01-22 15:39:55', '2026-01-22 15:39:55'),
(2, NULL, 'A', 75.00, 79.00, 3.75, 'Excellent', '2026-01-22 15:39:55', '2026-01-22 15:39:55'),
(3, NULL, 'A-', 70.00, 74.00, 3.50, 'Very Good', '2026-01-22 15:39:55', '2026-01-22 15:39:55'),
(4, NULL, 'B+', 65.00, 69.00, 3.25, 'Good', '2026-01-22 15:39:55', '2026-01-22 15:39:55'),
(5, NULL, 'B', 60.00, 64.00, 3.00, 'Satisfactory', '2026-01-22 15:39:55', '2026-01-22 15:39:55'),
(6, NULL, 'B-', 55.00, 59.00, 2.75, 'Above Average', '2026-01-22 15:39:55', '2026-01-22 15:39:55'),
(7, NULL, 'C+', 50.00, 54.00, 2.50, 'Average', '2026-01-22 15:39:55', '2026-01-22 15:39:55'),
(8, NULL, 'C', 45.00, 49.00, 2.25, 'Below Average', '2026-01-22 15:39:55', '2026-01-22 15:39:55'),
(9, NULL, 'D', 40.00, 44.00, 2.00, 'Pass', '2026-01-22 15:39:55', '2026-01-22 15:39:55'),
(10, NULL, 'F', 0.00, 39.00, 0.00, 'Fail', '2026-01-22 15:39:55', '2026-01-22 15:39:55');

-- --------------------------------------------------------

--
-- Table structure for table `login_history`
--

CREATE TABLE `login_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `status` enum('success','failed') NOT NULL,
  `failure_reason` varchar(200) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_history`
--

INSERT INTO `login_history` (`id`, `user_id`, `username`, `status`, `failure_reason`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 07:58:40'),
(2, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 08:00:40'),
(3, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 12:30:51'),
(4, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 15:25:04'),
(5, 1, 'super@academix.edu', 'failed', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 15:26:48'),
(6, 1, 'super@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 15:26:52'),
(7, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 15:27:27'),
(8, 1, 'super@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 16:21:42'),
(9, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 04:58:04'),
(10, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 05:57:32'),
(11, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 06:25:53'),
(12, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 16:22:37'),
(13, 13, 'mahmudulhasannoman01@gmail.com', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 16:36:05'),
(14, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 17:33:13'),
(15, 13, 'mahmudulhasannoman01@gmail.com', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 17:34:12'),
(16, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-23 17:46:39'),
(17, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-24 08:34:03'),
(18, 13, 'mahmudulhasannoman01@gmail.com', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-24 08:35:24'),
(19, 1, 'superadmin', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-24 08:36:05'),
(20, NULL, 'msd@academix.bu.ac.bd', 'failed', 'User not found', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 06:30:33'),
(21, NULL, 'msd@academix.bu.ac.bd', 'failed', 'User not found', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 06:30:42'),
(22, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 06:31:36'),
(23, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 17:58:25'),
(24, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 18:01:17'),
(25, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 19:49:37'),
(26, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 04:00:47'),
(27, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 04:15:21'),
(28, 13, 'mahmudulhasannoman01@gmail.com', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 05:10:11'),
(29, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 05:44:00'),
(30, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 05:44:23'),
(31, 13, 'mahmudulhasannoman01@gmail.com', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 05:46:38'),
(32, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 07:57:56'),
(33, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 07:58:06'),
(34, 13, 'mahmudulhasannoman01@gmail.com', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 07:58:15'),
(35, 13, 'mahmudulhasannoman01@gmail.com', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 08:35:31'),
(36, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 08:37:12'),
(37, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 08:37:57'),
(38, 13, 'mahmudulhasannoman01@gmail.com', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 10:02:10'),
(39, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 10:02:25'),
(40, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 10:02:47'),
(41, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 17:05:35'),
(42, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 17:06:22'),
(43, 13, 'mahmudulhasannoman01@gmail.com', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 17:18:48'),
(44, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 19:15:37'),
(45, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 19:30:10'),
(46, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-13 18:58:54'),
(47, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-13 19:08:21'),
(48, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-14 05:52:01'),
(49, 13, 'mahmudulhasannoman01@gmail.com', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-14 06:04:12'),
(50, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-14 06:23:00'),
(51, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-14 06:26:24'),
(52, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-14 06:46:09'),
(53, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-16 05:42:16'),
(54, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-16 05:43:15'),
(55, 13, 'mahmudulhasannoman01@gmail.com', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-16 06:02:50'),
(56, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-17 09:25:38'),
(57, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-17 09:25:52'),
(58, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 16:46:19'),
(59, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 16:46:35'),
(60, 13, 'mahmudulhasannoman01@gmail.com', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 16:51:04'),
(61, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 17:24:29'),
(62, 13, 'mahmudulhasannoman01@gmail.com', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 17:28:08'),
(63, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 18:08:44'),
(64, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 18:09:04'),
(65, 13, 'mahmudulhasannoman01@gmail.com', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 15:37:37'),
(66, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 15:38:38'),
(67, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 15:40:32'),
(68, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 16:18:48'),
(69, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 16:18:58'),
(70, 13, 'mahmudulhasannoman01@gmail.com', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 16:24:39'),
(71, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 17:57:07'),
(72, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 19:37:03'),
(73, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 19:43:00'),
(74, 13, 'mahmudulhasannoman01@gmail.com', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 20:01:11'),
(75, 13, 'mahmudulhasannoman01@gmail.com', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 04:54:07'),
(76, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 04:54:53'),
(77, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 05:01:38'),
(78, 3, 'rhf@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 06:18:52'),
(79, 4, 'msd@academix.edu', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 06:19:48'),
(80, 13, 'mahmudulhasannoman01@gmail.com', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 06:20:50');

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `target_audience` enum('all','students','teachers','admins','department') NOT NULL,
  `department_id` int(10) UNSIGNED DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `publish_date` datetime DEFAULT NULL,
  `expiry_date` datetime DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notice_interactions`
--

CREATE TABLE `notice_interactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `notice_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 13, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 1, '2026-01-23 16:41:12'),
(2, 14, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(3, 15, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(4, 16, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(5, 17, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(6, 18, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(7, 19, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(8, 20, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(9, 21, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(10, 22, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(11, 23, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(12, 24, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(13, 25, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(14, 26, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(15, 27, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(16, 28, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(17, 29, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(18, 30, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(19, 31, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(20, 32, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(21, 33, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(22, 34, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(23, 35, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(24, 36, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(25, 37, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(26, 38, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(27, 39, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(28, 40, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(29, 41, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(30, 42, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(31, 43, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(32, 44, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(33, 45, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(34, 46, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(35, 47, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(36, 48, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(37, 49, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(38, 50, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(39, 51, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(40, 52, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(41, 53, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(42, 54, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(43, 55, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(44, 56, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(45, 57, 'Class Rescheduled', 'Your class originally on 2026-01-21 has been rescheduled to 2026-01-27 (11:30 - 11:00). Reason: ', 'alert', 0, '2026-01-23 16:41:12'),
(46, 13, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 1, '2026-02-04 05:28:55'),
(47, 14, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(48, 15, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(49, 16, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(50, 17, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(51, 18, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(52, 19, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(53, 20, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(54, 21, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(55, 22, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(56, 23, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(57, 24, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(58, 25, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(59, 26, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(60, 27, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(61, 28, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(62, 29, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(63, 30, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(64, 31, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(65, 32, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(66, 33, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(67, 34, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(68, 35, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(69, 36, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(70, 37, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(71, 38, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(72, 39, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(73, 40, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(74, 41, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(75, 42, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(76, 43, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(77, 44, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(78, 45, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(79, 46, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(80, 47, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(81, 48, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(82, 49, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(83, 50, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(84, 51, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(85, 52, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(86, 53, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(87, 54, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(88, 55, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(89, 56, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(90, 57, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (09:40:00-10:30:00) to Sunday (11:50:00-12:40:00).', 'info', 0, '2026-02-04 05:28:55'),
(91, 13, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 1, '2026-02-04 05:44:59'),
(92, 14, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(93, 15, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(94, 16, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(95, 17, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(96, 18, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(97, 19, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(98, 20, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(99, 21, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(100, 22, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(101, 23, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(102, 24, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(103, 25, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(104, 26, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(105, 27, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(106, 28, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(107, 29, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(108, 30, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(109, 31, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(110, 32, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(111, 33, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(112, 34, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(113, 35, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(114, 36, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(115, 37, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(116, 38, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(117, 39, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(118, 40, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(119, 41, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(120, 42, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(121, 43, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(122, 44, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(123, 45, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(124, 46, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(125, 47, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(126, 48, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(127, 49, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(128, 50, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(129, 51, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(130, 52, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(131, 53, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(132, 54, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(133, 55, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(134, 56, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(135, 57, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 05:44:59'),
(136, 13, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 1, '2026-02-04 05:47:48'),
(137, 14, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(138, 15, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(139, 16, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(140, 17, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(141, 18, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(142, 19, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(143, 20, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(144, 21, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(145, 22, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(146, 23, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(147, 24, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(148, 25, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(149, 26, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(150, 27, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(151, 28, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(152, 29, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(153, 30, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(154, 31, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(155, 32, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(156, 33, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(157, 34, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(158, 35, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(159, 36, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(160, 37, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(161, 38, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(162, 39, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(163, 40, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(164, 41, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(165, 42, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(166, 43, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(167, 44, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(168, 45, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(169, 46, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(170, 47, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(171, 48, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(172, 49, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(173, 50, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(174, 51, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(175, 52, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(176, 53, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(177, 54, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(178, 55, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(179, 56, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(180, 57, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 05:47:48'),
(181, 13, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 1, '2026-02-04 08:00:53'),
(182, 14, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(183, 15, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(184, 16, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(185, 17, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(186, 18, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(187, 19, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(188, 20, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(189, 21, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(190, 22, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(191, 23, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(192, 24, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(193, 25, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(194, 26, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(195, 27, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(196, 28, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(197, 29, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(198, 30, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(199, 31, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(200, 32, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(201, 33, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(202, 34, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(203, 35, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(204, 36, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(205, 37, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(206, 38, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(207, 39, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(208, 40, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(209, 41, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(210, 42, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(211, 43, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(212, 44, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(213, 45, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(214, 46, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(215, 47, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(216, 48, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(217, 49, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(218, 50, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(219, 51, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(220, 52, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(221, 53, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(222, 54, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(223, 55, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(224, 56, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(225, 57, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:00:00-08:50:00) to Sunday (11:00:00-11:50:00).', 'info', 0, '2026-02-04 08:00:53'),
(226, 13, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 1, '2026-02-04 08:40:21'),
(227, 14, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(228, 15, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(229, 16, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(230, 17, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(231, 18, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(232, 19, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(233, 20, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(234, 21, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(235, 22, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(236, 23, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(237, 24, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(238, 25, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(239, 26, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(240, 27, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(241, 28, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(242, 29, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(243, 30, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(244, 31, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(245, 32, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(246, 33, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(247, 34, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(248, 35, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(249, 36, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(250, 37, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(251, 38, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(252, 39, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(253, 40, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(254, 41, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(255, 42, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:21'),
(256, 43, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:22'),
(257, 44, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:22'),
(258, 45, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:22'),
(259, 46, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:22'),
(260, 47, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:22'),
(261, 48, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:22'),
(262, 49, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:22'),
(263, 50, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:22'),
(264, 51, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:22'),
(265, 52, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:22'),
(266, 53, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:22'),
(267, 54, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:22'),
(268, 55, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:22'),
(269, 56, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:22'),
(270, 57, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (08:50:00-09:40:00) to Sunday (08:00:00-08:50:00).', 'info', 0, '2026-02-04 08:40:22'),
(271, 13, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 1, '2026-02-04 08:42:41'),
(272, 14, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(273, 15, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(274, 16, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(275, 17, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(276, 18, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(277, 19, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(278, 20, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(279, 21, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(280, 22, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(281, 23, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(282, 24, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(283, 25, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(284, 26, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(285, 27, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41');
INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(286, 28, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(287, 29, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(288, 30, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(289, 31, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(290, 32, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(291, 33, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(292, 34, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(293, 35, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(294, 36, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(295, 37, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(296, 38, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(297, 39, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(298, 40, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(299, 41, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(300, 42, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(301, 43, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(302, 44, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(303, 45, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(304, 46, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(305, 47, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(306, 48, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(307, 49, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(308, 50, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(309, 51, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(310, 52, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(311, 53, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(312, 54, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(313, 55, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(314, 56, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(315, 57, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 has been rescheduled from Sunday (11:00:00-11:50:00) to Sunday (14:00:00-17:00:00).', 'info', 0, '2026-02-04 08:42:41'),
(316, 8, 'New Class Routine Published', 'The new class routine has been published. Please check your schedule for the upcoming semester.', 'alert', 0, '2026-02-13 19:08:41'),
(317, 6, 'New Class Routine Published', 'The new class routine has been published. Please check your schedule for the upcoming semester.', 'alert', 0, '2026-02-13 19:08:41'),
(318, 5, 'New Class Routine Published', 'The new class routine has been published. Please check your schedule for the upcoming semester.', 'alert', 0, '2026-02-13 19:08:41'),
(319, 7, 'New Class Routine Published', 'The new class routine has been published. Please check your schedule for the upcoming semester.', 'alert', 0, '2026-02-13 19:08:41'),
(320, 4, 'New Class Routine Published', 'The new class routine has been published. Please check your schedule for the upcoming semester.', 'alert', 1, '2026-02-13 19:08:41'),
(321, 13, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 1, '2026-02-14 06:03:13'),
(322, 14, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(323, 15, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(324, 16, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(325, 17, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(326, 18, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(327, 19, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(328, 20, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(329, 21, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(330, 22, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(331, 23, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(332, 24, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(333, 25, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(334, 26, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(335, 27, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(336, 28, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(337, 29, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(338, 30, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(339, 31, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(340, 32, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(341, 33, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(342, 34, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(343, 35, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(344, 36, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(345, 37, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(346, 38, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(347, 39, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(348, 40, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(349, 41, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(350, 42, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(351, 43, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(352, 44, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(353, 45, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(354, 46, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(355, 47, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(356, 48, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(357, 49, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(358, 50, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(359, 51, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(360, 52, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(361, 53, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(362, 54, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(363, 55, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(364, 56, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(365, 57, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:50:00-09:40:00).', 'info', 0, '2026-02-14 06:03:13'),
(366, 13, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 1, '2026-02-14 06:12:21'),
(367, 14, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(368, 15, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(369, 16, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(370, 17, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(371, 18, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(372, 19, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(373, 20, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(374, 21, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(375, 22, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(376, 23, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(377, 24, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(378, 25, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(379, 26, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(380, 27, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(381, 28, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(382, 29, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(383, 30, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(384, 31, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(385, 32, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(386, 33, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(387, 34, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(388, 35, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(389, 36, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(390, 37, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(391, 38, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(392, 39, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(393, 40, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(394, 41, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(395, 42, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(396, 43, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(397, 44, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(398, 45, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(399, 46, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(400, 47, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(401, 48, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(402, 49, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(403, 50, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(404, 51, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(405, 52, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(406, 53, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(407, 54, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(408, 55, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(409, 56, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(410, 57, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Feb 15, 2026 has been rescheduled to Feb 16, 2026 (08:00:00-08:50:00).', 'info', 0, '2026-02-14 06:12:21'),
(411, 6, 'New Class Routine Published', 'The new class routine has been published. Please check your schedule for the upcoming semester.', 'alert', 0, '2026-02-23 16:26:23'),
(412, 4, 'New Class Routine Published', 'The new class routine has been published. Please check your schedule for the upcoming semester.', 'alert', 1, '2026-02-23 16:26:23'),
(413, 8, 'New Class Routine Published', 'The new class routine has been published. Please check your schedule for the upcoming semester.', 'alert', 0, '2026-02-23 16:26:23'),
(414, 6, 'New Class Routine Published', 'The new class routine has been published. Please check your schedule for the upcoming semester.', 'alert', 0, '2026-02-23 16:35:59'),
(415, 4, 'New Class Routine Published', 'The new class routine has been published. Please check your schedule for the upcoming semester.', 'alert', 1, '2026-02-23 16:35:59'),
(416, 8, 'New Class Routine Published', 'The new class routine has been published. Please check your schedule for the upcoming semester.', 'alert', 0, '2026-02-23 16:35:59'),
(417, 6, 'Draft Routine Updated', 'The class routine has been saved as a draft. Please review your schedule and provide feedback if needed.', 'info', 0, '2026-02-23 16:36:50'),
(418, 4, 'Draft Routine Updated', 'The class routine has been saved as a draft. Please review your schedule and provide feedback if needed.', 'info', 1, '2026-02-23 16:36:50'),
(419, 8, 'Draft Routine Updated', 'The class routine has been saved as a draft. Please review your schedule and provide feedback if needed.', 'info', 0, '2026-02-23 16:36:50'),
(420, 6, 'New Class Routine Published', 'The new class routine has been published. Please check your schedule for the upcoming semester.', 'alert', 0, '2026-02-23 16:38:06'),
(421, 4, 'New Class Routine Published', 'The new class routine has been published. Please check your schedule for the upcoming semester.', 'alert', 1, '2026-02-23 16:38:06'),
(422, 8, 'New Class Routine Published', 'The new class routine has been published. Please check your schedule for the upcoming semester.', 'alert', 0, '2026-02-23 16:38:06'),
(423, 13, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 1, '2026-02-23 19:45:16'),
(424, 14, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(425, 15, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(426, 16, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(427, 17, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(428, 18, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(429, 19, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(430, 20, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(431, 21, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(432, 22, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(433, 23, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(434, 24, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(435, 25, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(436, 26, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(437, 27, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(438, 28, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(439, 29, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(440, 30, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(441, 31, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(442, 32, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(443, 33, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(444, 34, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(445, 35, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(446, 36, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(447, 37, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(448, 38, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(449, 39, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(450, 40, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(451, 41, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(452, 42, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(453, 43, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(454, 44, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(455, 45, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(456, 46, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(457, 47, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(458, 48, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(459, 49, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(460, 50, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(461, 51, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(462, 52, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(463, 53, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(464, 54, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(465, 55, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(466, 56, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(467, 57, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Mar 1, 2026 (11:00:00-11:50:00).', 'info', 0, '2026-02-23 19:45:16'),
(468, 13, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 1, '2026-02-23 19:55:35'),
(469, 14, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(470, 15, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(471, 16, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(472, 17, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(473, 18, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(474, 19, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(475, 20, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(476, 21, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(477, 22, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(478, 23, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(479, 24, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(480, 25, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(481, 26, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(482, 27, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(483, 28, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(484, 29, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(485, 30, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(486, 31, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(487, 32, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(488, 33, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(489, 34, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(490, 35, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(491, 36, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(492, 37, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(493, 38, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(494, 39, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(495, 40, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(496, 41, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(497, 42, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(498, 43, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(499, 44, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(500, 45, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(501, 46, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(502, 47, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(503, 48, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(504, 49, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(505, 50, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(506, 51, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(507, 52, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(508, 53, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(509, 54, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(510, 55, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(511, 56, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(512, 57, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-23 19:55:35'),
(513, 13, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(514, 14, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(515, 15, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(516, 16, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(517, 17, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(518, 18, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(519, 19, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(520, 20, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(521, 21, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(522, 22, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(523, 23, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(524, 24, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(525, 25, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(526, 26, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(527, 27, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(528, 28, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(529, 29, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(530, 30, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(531, 31, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(532, 32, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(533, 33, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(534, 34, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(535, 35, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(536, 36, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(537, 37, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(538, 38, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(539, 39, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(540, 40, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(541, 41, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(542, 42, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(543, 43, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(544, 44, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(545, 45, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(546, 46, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(547, 47, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(548, 48, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(549, 49, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(550, 50, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(551, 51, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(552, 52, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(553, 53, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(554, 54, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(555, 55, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(556, 56, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(557, 57, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:50-09:40).', 'info', 0, '2026-02-24 06:27:57'),
(559, 13, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(560, 14, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(561, 15, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(562, 16, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(563, 17, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(564, 18, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(565, 19, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(566, 20, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(567, 21, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(568, 22, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(569, 23, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(570, 24, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(571, 25, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(572, 26, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(573, 27, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(574, 28, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(575, 29, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(576, 30, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(577, 31, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(578, 32, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(579, 33, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(580, 34, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(581, 35, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(582, 36, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(583, 37, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(584, 38, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(585, 39, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(586, 40, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(587, 41, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(588, 42, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34');
INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(589, 43, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(590, 44, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(591, 45, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(592, 46, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(593, 47, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(594, 48, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(595, 49, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(596, 50, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(597, 51, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(598, 52, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(599, 53, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(600, 54, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(601, 55, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(602, 56, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(603, 57, 'Class Cancelled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 (08:50-09:40) has been cancelled by Md. Samsuddoha.', 'alert', 0, '2026-02-24 06:32:34'),
(604, 13, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(605, 14, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(606, 15, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(607, 16, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(608, 17, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(609, 18, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(610, 19, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(611, 20, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(612, 21, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(613, 22, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(614, 23, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(615, 24, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(616, 25, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(617, 26, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(618, 27, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(619, 28, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(620, 29, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(621, 30, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(622, 31, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(623, 32, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(624, 33, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(625, 34, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(626, 35, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(627, 36, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(628, 37, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(629, 38, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(630, 39, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(631, 40, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(632, 41, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(633, 42, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(634, 43, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(635, 44, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(636, 45, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(637, 46, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(638, 47, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(639, 48, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(640, 49, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(641, 50, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(642, 51, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(643, 52, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(644, 53, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(645, 54, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(646, 55, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(647, 56, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(648, 57, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 (08:00-08:50).', 'info', 0, '2026-02-24 06:46:11'),
(649, 13, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(650, 14, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(651, 15, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(652, 16, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(653, 17, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(654, 18, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(655, 19, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(656, 20, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(657, 21, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(658, 22, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(659, 23, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(660, 24, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(661, 25, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(662, 26, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(663, 27, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(664, 28, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(665, 29, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(666, 30, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(667, 31, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(668, 32, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(669, 33, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(670, 34, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(671, 35, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(672, 36, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(673, 37, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(674, 38, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(675, 39, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(676, 40, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(677, 41, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(678, 42, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(679, 43, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(680, 44, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(681, 45, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(682, 46, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(683, 47, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(684, 48, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(685, 49, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(686, 50, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(687, 51, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(688, 52, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(689, 53, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(690, 54, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(691, 55, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(692, 56, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(693, 57, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 26, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:46:49'),
(694, 13, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:48'),
(695, 14, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:48'),
(696, 15, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:48'),
(697, 16, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:48'),
(698, 17, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:48'),
(699, 18, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:48'),
(700, 19, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:48'),
(701, 20, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:48'),
(702, 21, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:48'),
(703, 22, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:48'),
(704, 23, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:48'),
(705, 24, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:48'),
(706, 25, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:48'),
(707, 26, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:48'),
(708, 27, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:48'),
(709, 28, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:48'),
(710, 29, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:48'),
(711, 30, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:48'),
(712, 31, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:48'),
(713, 32, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:48'),
(714, 33, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:48'),
(715, 34, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:48'),
(716, 35, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49'),
(717, 36, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49'),
(718, 37, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49'),
(719, 38, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49'),
(720, 39, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49'),
(721, 40, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49'),
(722, 41, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49'),
(723, 42, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49'),
(724, 43, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49'),
(725, 44, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49'),
(726, 45, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49'),
(727, 46, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49'),
(728, 47, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49'),
(729, 48, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49'),
(730, 49, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49'),
(731, 50, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49'),
(732, 51, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49'),
(733, 52, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49'),
(734, 53, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49'),
(735, 54, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49'),
(736, 55, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49'),
(737, 56, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49'),
(738, 57, 'Class Rescheduled: CSE-3104', 'Course CSE-3104 on Mar 1, 2026 has been rescheduled to Feb 25, 2026 across 2 time slots: 08:50-09:40, 09:40-10:30.', 'info', 0, '2026-02-24 06:51:49');

-- --------------------------------------------------------

--
-- Table structure for table `notification_preferences`
--

CREATE TABLE `notification_preferences` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `email_notifications` tinyint(1) DEFAULT 1,
  `sms_notifications` tinyint(1) DEFAULT 0,
  `push_notifications` tinyint(1) DEFAULT 1,
  `assignment_notifications` tinyint(1) DEFAULT 1,
  `attendance_alerts` tinyint(1) DEFAULT 1,
  `grade_notifications` tinyint(1) DEFAULT 1,
  `course_announcements` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reschedule_requests`
--

CREATE TABLE `reschedule_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `class_id` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','threshold_reached','rescheduled') DEFAULT 'pending',
  `teacher_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reschedule_votes`
--

CREATE TABLE `reschedule_votes` (
  `id` int(10) UNSIGNED NOT NULL,
  `schedule_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `original_date` date NOT NULL,
  `proposed_date` date NOT NULL,
  `proposed_time` time NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `building` varchar(100) NOT NULL,
  `room_type` enum('classroom','lab') NOT NULL DEFAULT 'classroom',
  `capacity` int(10) UNSIGNED DEFAULT 60,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `code`, `name`, `building`, `room_type`, `capacity`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'C1', 'Room 6613', 'Academic Building 1', 'classroom', 60, 1, '2026-02-03 17:54:19', '2026-02-03 17:54:19'),
(2, 'C2', 'Advanced Programming Lab', 'Academic Building 1', 'classroom', 40, 1, '2026-02-03 17:54:19', '2026-02-14 06:49:44'),
(3, 'C3', 'Networking Lab', 'Academic Building 1', 'classroom', 40, 1, '2026-02-03 17:54:19', '2026-02-14 06:49:44'),
(4, 'C4', 'IoT Lab', 'Academic Building 2', 'classroom', 30, 1, '2026-02-03 17:54:19', '2026-02-14 06:49:44'),
(5, 'C5', 'DLD Lab', 'Academic Building 1', 'classroom', 40, 1, '2026-02-03 17:54:19', '2026-02-14 06:49:44'),
(6, 'C6', 'Mobile Computing Lab', 'Academic Building 1', 'classroom', 40, 1, '2026-02-03 17:54:19', '2026-02-14 06:49:44');

-- --------------------------------------------------------

--
-- Table structure for table `routine_assignments`
--

CREATE TABLE `routine_assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `routine_draft_id` int(10) UNSIGNED NOT NULL,
  `course_offering_id` int(10) UNSIGNED NOT NULL,
  `slot_id` int(10) UNSIGNED NOT NULL,
  `room_id` int(10) UNSIGNED NOT NULL,
  `day_of_week` enum('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `routine_assignments`
--

INSERT INTO `routine_assignments` (`id`, `routine_draft_id`, `course_offering_id`, `slot_id`, `room_id`, `day_of_week`, `created_at`, `updated_at`) VALUES
(22, 1, 28, 1, 1, 'Sunday', '2026-02-04 10:31:06', '2026-02-04 10:31:06'),
(28, 1, 21, 1, 2, 'Sunday', '2026-02-13 19:11:02', '2026-02-13 19:11:02'),
(29, 1, 19, 2, 3, 'Sunday', '2026-02-17 09:29:53', '2026-02-17 09:29:53'),
(32, 1, 50, 1, 4, 'Sunday', '2026-02-22 17:26:11', '2026-02-22 17:26:11'),
(37, 1, 33, 2, 1, 'Sunday', '2026-02-23 19:44:16', '2026-02-23 19:44:16');

-- --------------------------------------------------------

--
-- Table structure for table `routine_change_requests`
--

CREATE TABLE `routine_change_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `routine_assignment_id` int(10) UNSIGNED NOT NULL,
  `requested_by` int(10) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_response` text DEFAULT NULL,
  `responded_by` int(10) UNSIGNED DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `routine_drafts`
--

CREATE TABLE `routine_drafts` (
  `id` int(10) UNSIGNED NOT NULL,
  `semester_id` int(10) UNSIGNED NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `draft_name` varchar(200) NOT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `routine_drafts`
--

INSERT INTO `routine_drafts` (`id`, `semester_id`, `department_id`, `draft_name`, `status`, `published_at`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Routine Draft - 2026-02-03 23:59', 'published', '2026-02-23 22:38:05', 3, '2026-02-03 17:59:09', '2026-02-23 16:38:05');

-- --------------------------------------------------------

--
-- Table structure for table `routine_slots`
--

CREATE TABLE `routine_slots` (
  `id` int(10) UNSIGNED NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `label` varchar(100) NOT NULL,
  `slot_type` enum('theory','lab','break') NOT NULL DEFAULT 'theory',
  `slot_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `routine_slots`
--

INSERT INTO `routine_slots` (`id`, `start_time`, `end_time`, `label`, `slot_type`, `slot_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '08:00:00', '08:50:00', '1st Period', 'theory', 1, 1, '2026-02-03 17:54:19', '2026-02-03 17:54:19'),
(2, '08:50:00', '09:40:00', '2nd Period', 'theory', 2, 1, '2026-02-03 17:54:19', '2026-02-03 17:54:19'),
(3, '09:40:00', '10:30:00', '3rd Period', 'theory', 3, 1, '2026-02-03 17:54:19', '2026-02-03 17:54:19'),
(4, '10:30:00', '11:00:00', 'Tea Break', 'break', 4, 1, '2026-02-03 17:54:19', '2026-02-03 17:54:19'),
(5, '11:00:00', '11:50:00', '4th Period', 'theory', 5, 1, '2026-02-03 17:54:19', '2026-02-03 17:54:19'),
(6, '11:50:00', '12:40:00', '5th Period', 'theory', 6, 1, '2026-02-03 17:54:19', '2026-02-03 17:54:19'),
(7, '12:40:00', '14:00:00', 'Prayer/Lunch', 'break', 7, 1, '2026-02-03 17:54:19', '2026-02-03 17:54:19'),
(8, '14:00:00', '17:00:00', 'Afternoon Lab', 'lab', 8, 1, '2026-02-03 17:54:19', '2026-02-03 17:54:19');

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--

CREATE TABLE `semesters` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `semester_number` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('upcoming','active','completed') DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `semesters`
--

INSERT INTO `semesters` (`id`, `academic_year_id`, `name`, `semester_number`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Fall 2025 - Sem 1', 1, '2025-07-01', '2025-12-31', 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(2, 1, 'Fall 2025 - Sem 2', 2, '2025-07-01', '2025-12-31', 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(3, 1, 'Fall 2025 - Sem 4', 4, '2025-07-01', '2025-12-31', 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(4, 1, 'Fall 2025 - Sem 5', 5, '2025-07-01', '2025-12-31', 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(5, 1, 'Fall 2025 - Sem 6', 6, '2025-07-01', '2025-12-31', 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(6, 1, 'Fall 2025 - Sem 8', 8, '2025-07-01', '2025-12-31', 'active', '2026-01-21 07:54:41', '2026-01-21 07:54:41');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `batch_year` int(11) NOT NULL,
  `session` varchar(20) DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `guardian_email` varchar(100) DEFAULT NULL,
  `current_semester` int(11) DEFAULT 1,
  `cgpa` decimal(3,2) DEFAULT 0.00,
  `status` enum('active','inactive','graduated','dropped') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `department_id`, `student_id`, `batch_year`, `session`, `admission_date`, `blood_group`, `guardian_name`, `guardian_phone`, `guardian_email`, `current_semester`, `cgpa`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 1, '22-CSE-052', 9, '2021-22', '2026-01-21', NULL, NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-21 07:56:27', '2026-01-21 07:56:27'),
(45, 13, 1, '22CSE001', 9, '2021-22', NULL, 'AB+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(46, 14, 1, '22CSE002', 9, '2021-22', NULL, 'A+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(47, 15, 1, '22CSE003', 9, '2021-22', NULL, 'A+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(48, 16, 1, '22CSE004', 9, '2021-22', NULL, 'AB+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(49, 17, 1, '22CSE005', 9, '2021-22', NULL, '', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(50, 18, 1, '22CSE006', 9, '2021-22', NULL, 'O+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(51, 19, 1, '22CSE007', 9, '2021-22', NULL, 'AB+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(52, 20, 1, '22CSE008', 9, '2021-22', NULL, 'B+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(53, 21, 1, '22CSE009', 9, '2021-22', NULL, 'B+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(54, 22, 1, '22CSE010', 9, '2021-22', NULL, 'B+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(55, 23, 1, '22CSE012', 9, '2021-22', NULL, 'O+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(56, 24, 1, '22CSE013', 9, '2021-22', NULL, 'O+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(57, 25, 1, '22CSE015', 9, '2021-22', NULL, 'O+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(58, 26, 1, '22CSE016', 9, '2021-22', NULL, 'O+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(59, 27, 1, '22CSE018', 9, '2021-22', NULL, 'B+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(60, 28, 1, '22CSE019', 9, '2021-22', NULL, 'B+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(61, 29, 1, '22CSE020', 9, '2021-22', NULL, 'A+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(62, 30, 1, '22CSE022', 9, '2021-22', NULL, 'B+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(63, 31, 1, '22CSE023', 9, '2021-22', NULL, 'O+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(64, 32, 1, '22CSE024', 9, '2021-22', NULL, 'O+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(65, 33, 1, '22CSE026', 9, '2021-22', NULL, 'O+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(66, 34, 1, '22CSE027', 9, '2021-22', NULL, 'A+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(67, 35, 1, '22CSE028', 9, '2021-22', NULL, 'B+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(68, 36, 1, '22CSE029', 9, '2021-22', NULL, 'B+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(69, 37, 1, '22CSE030', 9, '2021-22', NULL, 'O+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(70, 38, 1, '22CSE031', 9, '2021-22', NULL, 'O+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(71, 39, 1, '22CSE032', 9, '2021-22', NULL, 'O+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(72, 40, 1, '22CSE033', 9, '2021-22', NULL, 'O+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(73, 41, 1, '22CSE034', 9, '2021-22', NULL, 'O+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(74, 42, 1, '22CSE036', 9, '2021-22', NULL, 'A+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(75, 43, 1, '22CSE037', 9, '2021-22', NULL, 'AB+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(76, 44, 1, '22CSE038', 9, '2021-22', NULL, 'B+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(77, 45, 1, '22CSE039', 9, '2021-22', NULL, 'A+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(78, 46, 1, '22CSE040', 9, '2021-22', NULL, 'O+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(79, 47, 1, '22CSE041', 9, '2021-22', NULL, 'B+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(80, 48, 1, '22CSE042', 9, '2021-22', NULL, 'B+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:01', '2026-01-23 05:55:01'),
(81, 49, 1, '22CSE043', 9, '2021-22', NULL, 'A+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:02', '2026-01-23 05:55:02'),
(82, 50, 1, '22CSE044', 9, '2021-22', NULL, 'AB+', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:02', '2026-01-23 05:55:02'),
(83, 51, 1, '22CSE045', 9, '2021-22', NULL, 'B-', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 05:55:02', '2026-01-23 05:55:02'),
(84, 52, 1, '2\02\0C\0S\0E\00\04\06', 9, '2\00\02\01\0-\02\02', NULL, 'L', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(85, 53, 1, '2\02\0C\0S\0E\00\04\08', 9, '2\00\02\01\0-\02\02', NULL, 'M', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(86, 54, 1, '2\02\0C\0S\0E\00\04\09', 9, '2\00\02\01\0-\02\02', NULL, 'L', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(87, 55, 1, '2\02\0C\0S\0E\00\05\00', 9, '2\00\02\01\0-\02\02', NULL, 'X\0L', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(88, 56, 1, '2\02\0C\0S\0E\00\05\01', 9, '2\00\02\01\0-\02\02', NULL, 'X\0L', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(89, 57, 1, '2\02\0C\0S\0E\00\05\02', 9, '2\00\02\01\0-\02\02', NULL, 'L', NULL, NULL, NULL, 5, 0.00, 'active', '2026-01-23 06:02:50', '2026-01-23 06:02:50');

-- --------------------------------------------------------

--
-- Table structure for table `student_marks`
--

CREATE TABLE `student_marks` (
  `id` int(10) UNSIGNED NOT NULL,
  `enrollment_id` int(10) UNSIGNED NOT NULL,
  `assessment_component_id` int(10) UNSIGNED NOT NULL,
  `marks_obtained` decimal(5,2) DEFAULT NULL,
  `total_marks` decimal(5,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `entered_by` int(10) UNSIGNED NOT NULL,
  `verified_by` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('draft','submitted','verified','correction_requested') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_performance_reviews`
--

CREATE TABLE `student_performance_reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `reviewer_id` int(10) UNSIGNED NOT NULL,
  `review_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `syllabus_topics`
--

CREATE TABLE `syllabus_topics` (
  `id` int(11) NOT NULL,
  `course_offering_id` int(10) UNSIGNED NOT NULL,
  `topic_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('pending','completed') NOT NULL DEFAULT 'pending',
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT 'string',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `specialization` text DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `qualification` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `department_id`, `employee_id`, `designation`, `specialization`, `joining_date`, `qualification`, `created_at`, `updated_at`) VALUES
(2, 4, 1, 'T-MSD', NULL, NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(3, 5, 1, 'T-MMN', NULL, NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(4, 6, 1, 'T-RAA', NULL, NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(5, 7, 1, 'T-MMA', NULL, NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(6, 8, 1, 'T-TI', NULL, NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(7, 9, 1, 'T-ME', NULL, NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(8, 10, 1, 'T-SJ', NULL, NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(9, 11, 1, 'T-MAK', NULL, NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(10, 12, 1, 'T-MHS', NULL, NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_courses`
--

CREATE TABLE `teacher_courses` (
  `id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `course_offering_id` int(10) UNSIGNED NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_courses`
--

INSERT INTO `teacher_courses` (`id`, `teacher_id`, `course_offering_id`, `assigned_at`) VALUES
(1, 3, 2, '2026-01-21 07:54:41'),
(3, 3, 5, '2026-01-21 07:54:41'),
(10, 2, 15, '2026-01-21 07:54:41'),
(13, 4, 17, '2026-01-21 07:54:41'),
(14, 4, 19, '2026-01-21 07:54:41'),
(18, 2, 21, '2026-01-21 07:54:41'),
(21, 2, 22, '2026-01-21 07:54:41'),
(23, 3, 23, '2026-01-21 07:54:41'),
(24, 6, 24, '2026-01-21 07:54:41'),
(25, 4, 26, '2026-01-21 07:54:41'),
(27, 6, 27, '2026-01-21 07:54:41'),
(29, 4, 28, '2026-01-21 07:54:41'),
(30, 5, 29, '2026-01-21 07:54:41'),
(34, 3, 32, '2026-01-21 07:54:41'),
(38, 2, 34, '2026-01-21 07:54:41'),
(42, 4, 37, '2026-01-21 07:54:41'),
(44, 4, 38, '2026-01-21 07:54:41'),
(46, 6, 39, '2026-01-21 07:54:41'),
(49, 6, 40, '2026-01-21 07:54:41'),
(50, 6, 41, '2026-01-21 07:54:41'),
(51, 6, 42, '2026-01-21 07:54:41'),
(53, 3, 43, '2026-01-21 07:54:41'),
(56, 6, 46, '2026-01-21 07:54:41'),
(60, 5, 48, '2026-01-21 07:54:41'),
(61, 4, 49, '2026-01-21 07:54:41'),
(63, 6, 50, '2026-01-21 07:54:41'),
(65, 6, 51, '2026-01-21 07:54:41'),
(66, 5, 52, '2026-01-21 07:54:41'),
(67, 4, 53, '2026-01-21 07:54:41'),
(69, 2, 33, '2026-02-04 04:14:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','teacher','student') NOT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `first_login` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `remember_token_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `status`, `first_login`, `last_login`, `failed_login_attempts`, `locked_until`, `remember_token`, `remember_token_expires`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'superadmin', 'super@academix.edu', '123456', 'super_admin', 'active', 0, '2026-01-24 14:36:05', 0, NULL, NULL, NULL, '2026-01-21 07:47:13', '2026-01-24 08:36:05', NULL),
(2, 'shahin', 'Yeatasimshahin121@gmail.com', '123456', 'student', 'inactive', 1, NULL, 0, NULL, NULL, NULL, '2026-01-21 07:50:33', '2026-01-22 15:43:14', NULL),
(3, 'rhf', 'rhf@academix.edu', '123456', 'admin', 'active', 0, '2026-02-24 12:18:52', 0, NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-02-24 06:18:52', NULL),
(4, 'msd', 'msd@academix.edu', '123456', 'teacher', 'active', 0, '2026-02-24 12:19:48', 0, NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-02-24 06:19:48', NULL),
(5, 'mmn', 'mmn@academix.edu', '123456', 'teacher', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL),
(6, 'raa', 'raa@academix.edu', '123456', 'teacher', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL),
(7, 'mma', 'mma@academix.edu', '123456', 'teacher', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL),
(8, 'ti', 'ti@academix.edu', '123456', 'teacher', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL),
(9, 'me', 'me@academix.edu', '123456', 'teacher', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL),
(10, 'sj', 'sj@academix.edu', '123456', 'teacher', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41', NULL),
(11, 'mak', 'mak@academix.edu', '123456', 'teacher', 'inactive', 1, NULL, 0, NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-22 15:29:45', NULL),
(12, 'mhs', 'mhs@academix.edu', '123456', 'teacher', 'inactive', 1, NULL, 0, NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-22 15:30:08', NULL),
(13, '22CSE001', 'mahmudulhasannoman01@gmail.com', '123456', 'student', 'active', 0, '2026-02-24 12:20:50', 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-02-24 06:20:50', NULL),
(14, '22CSE002', 'rayhankhan.cse9.bu@gmail. com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(15, '22CSE003', 'lija.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(16, '22CSE004', 'lazmi.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(17, '22CSE005', 'rayhan.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(18, '22CSE006', 'bonna.cse9.bu@gamil.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(19, '22CSE007', 'baisakh2015@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(20, '22CSE008', 'durjoy.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(21, '22CSE009', 'sourav.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(22, '22CSE010', '', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(23, '22CSE012', 'ibrahim.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(24, '22CSE013', 'nazmulhasanshipon.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(25, '22CSE015', 'nayeem.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(26, '22CSE016', 'riaj.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(27, '22CSE018', 'omar01.cse9bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(28, '22CSE019', 'sharna.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(29, '22CSE020', 'mdyeamen611@gmail', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(30, '22CSE022', 'omar.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(31, '22CSE023', 'likhon.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(32, '22CSE024', 'sakur.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(33, '22CSE026', 'biswadev.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(34, '22CSE027', 'mdrasel.cse.9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(35, '22CSE028', 'ismita. cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(36, '22CSE029', 'mabin.cse9.bu11@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(37, '22CSE030', 'rafe.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(38, '22CSE031', 'sazzad.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(39, '22CSE032', 'tanim.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(40, '22CSE033', 'niaji.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(41, '22CSE034', 'ab.rahaman.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(42, '22CSE036', 'israt.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(43, '22CSE037', 'biplobhossain.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(44, '22CSE038', 'alnoman.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(45, '22CSE039', 'shafikul.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(46, '22CSE040', 'imam.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(47, '22CSE041', 'polok.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(48, '22CSE042', 'ayesha.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(49, '22CSE043', 'dola.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(50, '22CSE044', 'mehedihasanrubel44@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(51, '22CSE045', 'maharuf.cse9.bu@gmail.com', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 05:48:18', '2026-01-23 06:02:50', NULL),
(52, '2\02\0C\0S\0E\00\04\06', 'a\0m\0i\0n\0.\0c\0s\0e\09\0.\0b\0u\0@\0g\0m\0a\0i\0l\0.\0c\0o\0m', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50', NULL),
(53, '2\02\0C\0S\0E\00\04\08', 'a\0b\0d\0u\0l\0l\0a\0h\0.\0c\0s\0e\09\0.\0b\0u\0@\0g\0m\0a\0i\0l\0.\0c\0o\0m', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50', NULL),
(54, '2\02\0C\0S\0E\00\04\09', 'm\0a\0l\0i\0h\0a\0.\0c\0s\0e\09\0.\0b\0u\0@\0g\0m\0a\0i\0l\0.\0c\0o\0m', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50', NULL),
(55, '2\02\0C\0S\0E\00\05\00', 't\0a\0n\0v\0i\0r\0.\0c\0s\0e\09\0.\0b\0u\0@\0g\0m\0a\0i\0l\0.\0c\0o\0m', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50', NULL),
(56, '2\02\0C\0S\0E\00\05\01', 'm\0d\0i\0m\0a\0m\0.\0c\0s\0e\09\0.\0b\0u\0@\0g\0m\0a\0i\0l\0.\0c\0o\0m', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50', NULL),
(57, '2\02\0C\0S\0E\00\05\02', 'y\0e\0a\0t\0a\0s\0i\0m\0.\0c\0s\0e\09\0.\0b\0u\0@\0g\0m\0a\0i\0l\0.\0c\0o\0m', '123456', 'student', 'active', 1, NULL, 0, NULL, NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(50) DEFAULT 'Bangladesh',
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`id`, `user_id`, `first_name`, `last_name`, `phone`, `address`, `city`, `state`, `postal_code`, `country`, `date_of_birth`, `gender`, `profile_picture`, `created_at`, `updated_at`) VALUES
(1, 1, 'Super', 'Administrator', '01700000000', NULL, 'Dhaka', NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-21 07:47:13', '2026-01-21 07:47:13'),
(2, 2, 'Yeatasim', 'shahin', '', NULL, NULL, NULL, NULL, 'Bangladesh', '2002-01-14', 'male', NULL, '2026-01-21 07:50:33', '2026-01-21 07:50:33'),
(3, 3, 'Rahat Hossain', 'Faisal', '', NULL, NULL, NULL, NULL, 'Bangladesh', '0000-00-00', 'male', NULL, '2026-01-21 07:54:41', '2026-01-21 07:58:06'),
(4, 4, 'Md.', 'Samsuddoha', NULL, NULL, NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(5, 5, 'Md Mahbub', 'E Noor', NULL, NULL, NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(6, 6, 'Md. Rashid', 'Al Asif', NULL, NULL, NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(7, 7, 'Md Manjur', 'Ahmed', NULL, NULL, NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(8, 8, 'Tania', 'Islam', NULL, NULL, NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(9, 9, 'Md.', 'Erfan', NULL, NULL, NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(10, 10, 'Sohely', 'Jahan', NULL, NULL, NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(11, 11, 'Abdullah', 'Al Masud', NULL, NULL, NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(12, 12, 'Mahmudul Hassan', 'Suhag', NULL, NULL, NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-21 07:54:41', '2026-01-21 07:54:41'),
(13, 13, 'MAHMUDUL HASAN', 'NOMAN', '01986255536', 'Bhola,Barishal', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(14, 14, 'RAYHAN', 'KHAN', '01743353969', 'Bakergonj ,Barishal', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(15, 15, 'LIJA', 'MONI', '01407736838', 'Hijla, Barishal', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(16, 16, 'LAZMI RAHMAN', 'AYMAN', '01951594127', 'Mehendigonj, Barishal', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(17, 17, 'MD.', 'RAYHAN', '01724484006', 'Barguna, Taltali', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(18, 18, 'SURIA HOSSAIN', 'BONNA', '01985826624', 'Daulatpur, Khulna', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(19, 19, 'MD. MAHRUF', 'ALAM', '01977987420', 'Nesarabad, Pirojpur', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(20, 20, 'DURJOY', 'KUNDU', '01736888926', 'Rajoir, Madaripur', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(21, 21, 'SOURAV', 'DEBNATH', '01782427035', 'Patuakhali, Galachipa', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(22, 22, 'TANZILA', 'AKTER', '01887798724', 'Bhola, Barishal', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(23, 23, 'MD. IBRAHIM', 'ALI', '01640094279', 'Rajshahi', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(24, 24, 'MD. NAZMUL', 'HASAN', '01603593646', 'Taltali, Barguna', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(25, 25, 'MD. NAYEEM', 'HOSSAIN', '01760428309', 'Bakergong,Barishal', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(26, 26, 'MD RIAJUDDIN', 'SIKDER', '01916740794', 'Jhalokathi, Barishal', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(27, 27, 'KHAN MD. OMAR', 'FARUK', '01733505123', 'Barishal Sadar, Barishal', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(28, 28, 'SHAIDA KHANOM', 'SHARNA', '01747050069', 'Barguna Sadar, Barguna', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(29, 29, 'MD. YEAMIN', 'TALUKDER', '01984493596', 'Barishal Sadar, Barishal', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(30, 30, 'OMAR', 'FARUK', '01626175771', 'jashore,khulna', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(31, 31, 'LIKHON', 'MANDAL', '01861782004', 'Rajoir, Madaripur', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(32, 32, 'ABDUS', 'SAKUR', '01996580603', 'Jhalokathi,Barishal', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(33, 33, 'BISWADEV', 'BISWAS', '01870488020', 'swarupkathi,pirojpur', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(34, 34, 'MD. RASEL', 'HOSSAIN', '01317057701', 'Muladi,Barishal', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(35, 35, 'ISMITA', 'JAHAN', '01608579833', 'Babugonj, Barishal', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(36, 36, 'MARGIA ROWSHON', 'MABIN', '01745723799', 'Barguna', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(37, 37, 'MUHAMMAD REDWANUL HAQUE', 'RAFE', '01626408168', 'Madhupur, Tangail', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(38, 38, 'SAZZAD', 'HOSSAIN', '01725227267', 'Khulna, sadar', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(39, 39, 'TANIM', 'AHMED', '01719578713', 'Sylhet Sadar', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(40, 40, 'MD. MAHMUDUL HASAN', 'NIAJI', '01996564051', 'Jhalokathi, Barishal', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(41, 41, 'AB.', 'RAHAMAN', '01876141522', 'muladi, Barishal', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(42, 42, 'ISRAT JAHAN', 'TAMANNA', '01850168457', 'Shahrasti, Chandpur', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(43, 43, 'MD BIPLOB', 'HOSSAIN', '01771-759389', 'Natullabad,Barishal', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(44, 44, 'MD. ABDULLAH AL', 'NOMAN', '01717943048', 'Baluadanga,Sadar,Dinajpur', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(45, 45, 'MD. SHAFIKUL', 'ISLAM', '01623543764', 'Bakpur,Banaripara,Barishal', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(46, 46, 'IMAM', 'HOSSEN', '01624994532', 'Chattagram', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(47, 47, 'RAFI SHAHRIAR', 'POLOK', '01996071876', 'Jamalpur', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(48, 48, 'AYESHA ISLAM', 'ALPONA', '01323725353', 'Sherpur, Bogura', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(49, 49, 'SATHI', 'DAS', '01311649147', 'Bauphal,Patuakhali', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(50, 50, 'MEHEDI HASAN', 'RUBEL', '01902828420', 'Gazipur Sadar,Gazipur', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(51, 51, 'MAHARUF', 'AHMED', '01883633942', 'Munshiganj sadar, Munshiganj', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 05:53:25', '2026-01-23 05:53:25'),
(52, 52, 'A\0M\0I\0N\0', '\0B\0H\0U\0I\0Y\0A\0N', '0\01\07\06\00\06\07\04\05\02\0', '\'\0M\0u\0n\0s\0h\0i\0g\0a\0n\0j', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(53, 53, 'M\0D\0.\0', '\0A\0B\0D\0U\0L\0L\0A\0H', '0\01\03\02\02\04\04\06\05\09\0', '\'\0N\0a\0g\0a\0r\0k\0a\0n\0d\0a', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(54, 54, 'M\0O\0S\0T\0.\0 \0M\0A\0L\0I\0H\0A\0', '\0A\0K\0T\0E\0R', '0\01\07\04\06\00\08\06\02\03\0', '\'\0N\0o\0t\0h\0u\0l\0l\0a\0b\0a\0d', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(55, 55, 'T\0A\0N\0V\0I\0R\0 \0A\0H\0A\0M\0E\0D\0', '\0F\0O\0Y\0S\0A\0L', '0\01\05\02\01\05\04\06\09\03\0', '\'\0N\0a\0g\0a\0r\0k\0a\0n\0d\0a', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(56, 56, 'M\0D\0.\0 \0I\0M\0A\0M\0', '\0H\0O\0S\0E\0N', '0\01\07\09\09\05\03\02\01\07\0', '\'\0B\0a\0r\0g\0u\0n\0a', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50'),
(57, 57, 'Y\0E\0A\0T\0A\0S\0I\0M\0', '\0B\0I\0L\0L\0A\0H', '0\01\05\07\01\03\03\09\08\09\0', '\'\0T\0e\0r\0o\0k\0h\0a\0d\0a', NULL, NULL, NULL, 'Bangladesh', NULL, NULL, NULL, '2026-01-23 06:02:50', '2026-01-23 06:02:50');

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

CREATE TABLE `votes` (
  `id` int(10) UNSIGNED NOT NULL,
  `request_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `suggested_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_year` (`year`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `assessment_components`
--
ALTER TABLE `assessment_components`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_department_id` (`department_id`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_course_offering_id` (`course_offering_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assignment_student` (`assignment_id`,`student_id`),
  ADD KEY `graded_by` (`graded_by`),
  ADD KEY `idx_assignment_id` (`assignment_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment_date` (`enrollment_id`,`attendance_date`),
  ADD KEY `marked_by` (`marked_by`),
  ADD KEY `idx_enrollment_id` (`enrollment_id`),
  ADD KEY `idx_course_offering_id` (`course_offering_id`),
  ADD KEY `idx_attendance_date` (`attendance_date`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `class_reschedules`
--
ALTER TABLE `class_reschedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reschedule` (`routine_assignment_id`,`original_date`,`new_slot_id`),
  ADD KEY `new_slot_id` (`new_slot_id`),
  ADD KEY `new_room_id` (`new_room_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `idx_original_date` (`original_date`),
  ADD KEY `idx_new_date_slot` (`new_date`,`new_slot_id`);

--
-- Indexes for table `class_schedule`
--
ALTER TABLE `class_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_course_offering_id` (`course_offering_id`),
  ADD KEY `idx_day_of_week` (`day_of_week`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `idx_department_id` (`department_id`),
  ADD KEY `idx_course_code` (`course_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `default_room_id` (`default_room_id`);

--
-- Indexes for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_course_offering_id` (`course_offering_id`);

--
-- Indexes for table `course_offerings`
--
ALTER TABLE `course_offerings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_course_semester_section` (`course_id`,`semester_id`,`section`),
  ADD KEY `idx_course_id` (`course_id`),
  ADD KEY `idx_semester_id` (`semester_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `head_of_department` (`head_of_department`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `department_admins`
--
ALTER TABLE `department_admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_admin_dept` (`user_id`,`department_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_department_id` (`department_id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_offering` (`student_id`,`course_offering_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_course_offering_id` (`course_offering_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_event_date` (`event_date`);

--
-- Indexes for table `grading_scheme`
--
ALTER TABLE `grading_scheme`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_department_id` (`department_id`);

--
-- Indexes for table `login_history`
--
ALTER TABLE `login_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_target_audience` (`target_audience`),
  ADD KEY `idx_publish_date` (`publish_date`);

--
-- Indexes for table `notice_interactions`
--
ALTER TABLE `notice_interactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_interaction` (`notice_id`,`user_id`),
  ADD KEY `idx_notice_id` (`notice_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_prefs` (`user_id`);

--
-- Indexes for table `reschedule_requests`
--
ALTER TABLE `reschedule_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `reschedule_votes`
--
ALTER TABLE `reschedule_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vote` (`schedule_id`,`original_date`,`student_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_schedule_date` (`schedule_id`,`original_date`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_room_type` (`room_type`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `routine_assignments`
--
ALTER TABLE `routine_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slot_day_draft` (`routine_draft_id`,`slot_id`,`day_of_week`,`room_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `idx_routine_draft_id` (`routine_draft_id`),
  ADD KEY `idx_course_offering_id` (`course_offering_id`),
  ADD KEY `idx_slot_id` (`slot_id`),
  ADD KEY `idx_day_of_week` (`day_of_week`);

--
-- Indexes for table `routine_change_requests`
--
ALTER TABLE `routine_change_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `responded_by` (`responded_by`),
  ADD KEY `idx_routine_assignment_id` (`routine_assignment_id`),
  ADD KEY `idx_requested_by` (`requested_by`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `routine_drafts`
--
ALTER TABLE `routine_drafts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_semester_id` (`semester_id`),
  ADD KEY `idx_department_id` (`department_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `routine_slots`
--
ALTER TABLE `routine_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_slot_order` (`slot_order`),
  ADD KEY `idx_slot_type` (`slot_type`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_academic_year_id` (`academic_year_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_department_id` (`department_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_batch_year` (`batch_year`);

--
-- Indexes for table `student_marks`
--
ALTER TABLE `student_marks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment_component` (`enrollment_id`,`assessment_component_id`),
  ADD KEY `assessment_component_id` (`assessment_component_id`),
  ADD KEY `entered_by` (`entered_by`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `idx_enrollment_id` (`enrollment_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `student_performance_reviews`
--
ALTER TABLE `student_performance_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_reviewer_id` (`reviewer_id`);

--
-- Indexes for table `syllabus_topics`
--
ALTER TABLE `syllabus_topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_offering_id` (`course_offering_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_setting_key` (`setting_key`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_department_id` (`department_id`),
  ADD KEY `idx_employee_id` (`employee_id`);

--
-- Indexes for table `teacher_courses`
--
ALTER TABLE `teacher_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher_offering` (`teacher_id`,`course_offering_id`),
  ADD KEY `idx_teacher_id` (`teacher_id`),
  ADD KEY `idx_course_offering_id` (`course_offering_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vote` (`request_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `assessment_components`
--
ALTER TABLE `assessment_components`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `class_reschedules`
--
ALTER TABLE `class_reschedules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `class_schedule`
--
ALTER TABLE `class_schedule`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `course_materials`
--
ALTER TABLE `course_materials`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_offerings`
--
ALTER TABLE `course_offerings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `department_admins`
--
ALTER TABLE `department_admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=921;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grading_scheme`
--
ALTER TABLE `grading_scheme`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `login_history`
--
ALTER TABLE `login_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notice_interactions`
--
ALTER TABLE `notice_interactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=739;

--
-- AUTO_INCREMENT for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reschedule_requests`
--
ALTER TABLE `reschedule_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reschedule_votes`
--
ALTER TABLE `reschedule_votes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `routine_assignments`
--
ALTER TABLE `routine_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `routine_change_requests`
--
ALTER TABLE `routine_change_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `routine_drafts`
--
ALTER TABLE `routine_drafts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `routine_slots`
--
ALTER TABLE `routine_slots`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `student_marks`
--
ALTER TABLE `student_marks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_performance_reviews`
--
ALTER TABLE `student_performance_reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `syllabus_topics`
--
ALTER TABLE `syllabus_topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `teacher_courses`
--
ALTER TABLE `teacher_courses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `votes`
--
ALTER TABLE `votes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assessment_components`
--
ALTER TABLE `assessment_components`
  ADD CONSTRAINT `assessment_components_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`course_offering_id`) REFERENCES `course_offerings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD CONSTRAINT `assignment_submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignment_submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignment_submissions_ibfk_3` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`course_offering_id`) REFERENCES `course_offerings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `class_reschedules`
--
ALTER TABLE `class_reschedules`
  ADD CONSTRAINT `class_reschedules_ibfk_1` FOREIGN KEY (`routine_assignment_id`) REFERENCES `routine_assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_reschedules_ibfk_2` FOREIGN KEY (`new_slot_id`) REFERENCES `routine_slots` (`id`),
  ADD CONSTRAINT `class_reschedules_ibfk_3` FOREIGN KEY (`new_room_id`) REFERENCES `rooms` (`id`),
  ADD CONSTRAINT `class_reschedules_ibfk_4` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`);

--
-- Constraints for table `class_schedule`
--
ALTER TABLE `class_schedule`
  ADD CONSTRAINT `class_schedule_ibfk_1` FOREIGN KEY (`course_offering_id`) REFERENCES `course_offerings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `courses_ibfk_2` FOREIGN KEY (`default_room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD CONSTRAINT `course_materials_ibfk_1` FOREIGN KEY (`course_offering_id`) REFERENCES `course_offerings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_materials_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_offerings`
--
ALTER TABLE `course_offerings`
  ADD CONSTRAINT `course_offerings_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_offerings_ibfk_2` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`head_of_department`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `department_admins`
--
ALTER TABLE `department_admins`
  ADD CONSTRAINT `department_admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `department_admins_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_offering_id`) REFERENCES `course_offerings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grading_scheme`
--
ALTER TABLE `grading_scheme`
  ADD CONSTRAINT `grading_scheme_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `login_history`
--
ALTER TABLE `login_history`
  ADD CONSTRAINT `login_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notices`
--
ALTER TABLE `notices`
  ADD CONSTRAINT `notices_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notices_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notice_interactions`
--
ALTER TABLE `notice_interactions`
  ADD CONSTRAINT `notice_interactions_ibfk_1` FOREIGN KEY (`notice_id`) REFERENCES `notices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notice_interactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD CONSTRAINT `notification_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reschedule_requests`
--
ALTER TABLE `reschedule_requests`
  ADD CONSTRAINT `reschedule_requests_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class_schedule` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reschedule_votes`
--
ALTER TABLE `reschedule_votes`
  ADD CONSTRAINT `reschedule_votes_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `class_schedule` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reschedule_votes_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `routine_assignments`
--
ALTER TABLE `routine_assignments`
  ADD CONSTRAINT `routine_assignments_ibfk_1` FOREIGN KEY (`routine_draft_id`) REFERENCES `routine_drafts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `routine_assignments_ibfk_2` FOREIGN KEY (`course_offering_id`) REFERENCES `course_offerings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `routine_assignments_ibfk_3` FOREIGN KEY (`slot_id`) REFERENCES `routine_slots` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `routine_assignments_ibfk_4` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `routine_change_requests`
--
ALTER TABLE `routine_change_requests`
  ADD CONSTRAINT `routine_change_requests_ibfk_1` FOREIGN KEY (`routine_assignment_id`) REFERENCES `routine_assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `routine_change_requests_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `routine_change_requests_ibfk_3` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `routine_drafts`
--
ALTER TABLE `routine_drafts`
  ADD CONSTRAINT `routine_drafts_ibfk_1` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `routine_drafts_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `routine_drafts_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `semesters`
--
ALTER TABLE `semesters`
  ADD CONSTRAINT `semesters_ibfk_1` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_marks`
--
ALTER TABLE `student_marks`
  ADD CONSTRAINT `student_marks_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_marks_ibfk_2` FOREIGN KEY (`assessment_component_id`) REFERENCES `assessment_components` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_marks_ibfk_3` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_marks_ibfk_4` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_performance_reviews`
--
ALTER TABLE `student_performance_reviews`
  ADD CONSTRAINT `student_performance_reviews_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_performance_reviews_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `syllabus_topics`
--
ALTER TABLE `syllabus_topics`
  ADD CONSTRAINT `fk_syllabus_course_offering` FOREIGN KEY (`course_offering_id`) REFERENCES `course_offerings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teachers_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_courses`
--
ALTER TABLE `teacher_courses`
  ADD CONSTRAINT `teacher_courses_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_courses_ibfk_2` FOREIGN KEY (`course_offering_id`) REFERENCES `course_offerings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `votes`
--
ALTER TABLE `votes`
  ADD CONSTRAINT `votes_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `reschedule_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `votes_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
