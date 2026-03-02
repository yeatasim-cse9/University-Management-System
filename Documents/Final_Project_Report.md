# Final Project Report

## Academix — University Management System

---

| **Item**              | **Detail**                                                                  |
| --------------------- | --------------------------------------------------------------------------- |
| **University**        | University of Barishal                                                      |
| **Department**        | Computer Science & Engineering (CSE)                                        |
| **Course**            | CSE (Software Engineering / Project Work)                                   |
| **Project Title**     | Academix — University Management System                                     |
| **Version**           |                                                                        |
| **Date**              | March 02, 2026                                                              |
| **GitHub Repository** | https://github.com/yeatasim-cse9/University-Management-System               |

---

## Team Members

| #   | Name                   | Role & Responsibility                               |
| --- | ---------------------- | --------------------------------------------------- |
| 1   | **Yeatasim** (Lead)    | Core Architecture, Config, Database, Auth, API & Scripts |
| 2   | A. B. Rahaman          | Admin Module — People & Scheduling                  |
| 3   | Md. Biplob Hossain     | Admin Module — Academic Assets & Exams              |
| 4   | Nayim Sheikh           | Teacher Module & Scheduling                         |
| 5   | Rasel Hossen           | Student Module                                      |
| 6   | Suria Hossain Bonna    | Super Admin Module & Frontend Assets                |

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Problem Statement](#2-problem-statement)
3. [Objectives](#3-objectives)
4. [Features Overview](#4-features-overview)
5. [System Architecture](#5-system-architecture)
6. [Technology Stack](#6-technology-stack)
7. [Database Design](#7-database-design)
8. [Module Descriptions](#8-module-descriptions)
9. [API Endpoints](#9-api-endpoints)
10. [Security Implementation](#10-security-implementation)
11. [Team Workflow & Development Process](#11-team-workflow--development-process)
12. [Testing & Quality Assurance](#12-testing--quality-assurance)
13. [Challenges & Solutions](#13-challenges--solutions)
14. [Future Scope](#14-future-scope)
15. [Conclusion](#15-conclusion)

---

## 1. Introduction

**Academix** is a comprehensive, web-based University Management System designed to digitize and streamline the academic and administrative operations of a university. The system supports four distinct user roles — **Super Admin**, **Department Admin**, **Teacher**, and **Student** — each with a tailored dashboard and set of functionalities.

The application was developed as part of the CSE project at the University of Barishal to address real-world academic management challenges, including course scheduling, attendance tracking, grading, notifications, and performance analytics.

---

## 2. Problem Statement

Universities in Bangladesh often rely on manual or fragmented systems for managing academic processes such as:
- Class routine creation and conflict resolution
- Student enrollment and attendance tracking
- Marks entry, grading, and result publication
- Assignment management and course material distribution
- Communication between administration, faculty, and students

These manual processes lead to scheduling conflicts, data inconsistencies, delays in result processing, and poor communication. Academix aims to provide a **single, integrated platform** that automates and simplifies these workflows.

---

## 3. Objectives

1. Develop a **role-based, multi-user** web application supporting Super Admin, Admin, Teacher, and Student portals.
2. Implement a **Dynamic Routine Allocation System** with automatic conflict detection for class scheduling.
3. Enable **end-to-end academic management**: enrollment, attendance, assessment, grading, and result publication.
4. Provide **real-time notifications** and a notice board for institution-wide communication.
5. Deliver **performance analytics** with dashboards and visual reports for data-driven decision making.
6. Ensure **security** through role-based access control, session management, and audit logging.
7. Follow **professional software engineering practices** including version control, branching strategy, code review, and modular architecture.

---

## 4. Features Overview

### 4.1 Student Portal
- View enrolled courses and weekly class routine
- Check attendance records and receive low-attendance alerts
- View semester results, GPA/CGPA and grade breakdowns
- Download and submit assignments
- Browse course syllabus and track progress
- Read notices and check notification alerts
- View academic performance analytics

### 4.2 Teacher Portal
- View assigned courses and student rosters
- Take and manage daily attendance
- Create assignments and grade submissions
- Enter, edit, and submit student marks
- Upload and share course materials
- View personal class routine (weekly & date-wise)
- Cancel or reschedule classes with request system
- Track student performance and write performance reviews
- Manage course syllabus progress

### 4.3 Department Admin Dashboard
- Manage students: enroll, search, filter, sort
- Manage teachers: onboard, assign courses, search
- Create and manage class schedules with conflict detection
- Manage course offerings per semester
- Time slot and room management
- Verify marks submitted by teachers
- Track course progress and exam eligibility
- Issue departmental notices and notifications
- Handle routine change requests from teachers
- Export routine to downloadable format

### 4.4 Super Admin Panel
- Full user management (CRUD for all roles)
- Department creation and monitoring
- Academic year and semester management
- System-wide course registry
- Global settings configuration (branding, timezone, file limits, etc.)
- System-wide reporting and analytics
- Global notice board and event calendar
- Notification center and student performance overview

### 4.5 Authentication & Common Features
- Secure login with account lockout after failed attempts
- "Remember Me" functionality with token-based persistence
- Password change with current password verification
- Profile management with personal details and photo upload
- Role-based routing and access control
- Session timeout and CSRF protection

---

## 5. System Architecture

### 5.1 Architecture Pattern
Academix follows a **modular monolithic architecture** with a clear separation between:

```
┌──────────────────────────────────────────────────────┐
│                     Browser (Client)                  │
├──────────────────────────────────────────────────────┤
│                  index.php (Entry Point)              │
│           Role-based routing to dashboards            │
├──────────────┬────────────┬──────────┬───────────────┤
│  Super Admin │   Admin    │ Teacher  │   Student     │
│   Module     │   Module   │  Module  │   Module      │
│  (13 pages)  │ (24 pages) │(16 pages)│  (11 pages)   │
├──────────────┴────────────┴──────────┴───────────────┤
│               API Layer (RESTful Endpoints)           │
│     /api/admin/  |  /api/teacher/  |  /api/student/   │
├──────────────────────────────────────────────────────┤
│                  Core Includes Layer                  │
│  auth.php | session.php | functions.php | layouts/    │
├──────────────────────────────────────────────────────┤
│              Config Layer (settings + database)       │
├──────────────────────────────────────────────────────┤
│              MySQL Database (Academix)                │
│              30+ tables, InnoDB, utf8mb4              │
└──────────────────────────────────────────────────────┘
```

### 5.2 Directory Structure

```
Academix/
├── api/                    # RESTful API endpoints
│   ├── admin/              #   Admin-specific APIs (routine management)
│   ├── teacher/            #   Teacher-specific APIs (schedule, routine)
│   ├── student/            #   Student-specific APIs (schedule)
│   ├── mark_read.php       #   Notification read status
│   └── routine_manager.php #   Centralized routine management API
├── assets/
│   ├── css/custom.css      # Global styles, variables, components
│   ├── js/                 # Client-side scripts (main.js, global_modal.js)
│   └── uploads/            # User uploaded files
├── config/
│   ├── settings.php        # Application constants & environment config
│   └── database.php        # PDO database connection
├── database/
│   ├── full_setup_2026.sql # Complete schema + seed data
│   └── migrations/         # 11 incremental migration scripts
├── includes/
│   ├── auth.php            # Authentication & authorization middleware
│   ├── session.php         # Session management
│   ├── functions.php       # 25+ global helper functions
│   ├── routine_helper.php  # Routine processing utilities
│   ├── sidebar_menu.php    # Dynamic role-based sidebar
│   ├── footer-nav.php      # Global footer component
│   ├── layouts/            # Dashboard layout wrapper
│   └── components/         # Reusable UI components (PerformanceMetrics)
├── modules/
│   ├── auth/               # Login, logout, profile, password change
│   ├── super_admin/        # 13 pages for system-wide management
│   ├── admin/              # 24 pages for department management
│   ├── teacher/            # 16 pages for faculty features
│   └── student/            # 11 pages for student portal
├── scripts/                # 20 utility/migration/seeding scripts
├── test_reports/           # 3 test report documents
├── Documents/              # Project documents (SRS, Proposal, DRAC)
├── index.php               # Application entry point
├── Academix.sql            # Legacy full database dump
└── README.md               # Project documentation
```

---

## 6. Technology Stack

| Layer           | Technology                                                    |
| --------------- | ------------------------------------------------------------- |
| **Frontend**    | HTML5, CSS3 (Custom design system), JavaScript (ES6+)         |
| **Backend**     | PHP 8.x (Procedural + OOP)                                   |
| **Database**    | MySQL 8.x with InnoDB engine, utf8mb4 character set           |
| **Server**      | Apache (XAMPP stack)                                          |
| **Charting**    | Chart.js (for performance analytics and dashboards)           |
| **Version Control** | Git & GitHub (feature-branch workflow)                    |
| **IDE**         | VS Code with extensions                                       |
| **Others**      | PDO for database abstraction, JSON for API responses          |

---

## 7. Database Design

### 7.1 Overview
The database `Academix` contains **30+ tables** organized into logical groups, using InnoDB engine with full foreign key constraints and indexing.

### 7.2 Entity Relationship Summary

#### Core Tables
| Table               | Purpose                                      |
| ------------------- | -------------------------------------------- |
| `users`             | All system users with role-based access       |
| `user_profiles`     | Extended profile information                  |
| `departments`       | Academic departments                          |
| `department_admins` | Admin→Department mapping                      |
| `teachers`          | Faculty members linked to users               |
| `students`          | Student records with batch, CGPA, guardian info|

#### Academic Structure
| Table               | Purpose                                      |
| ------------------- | -------------------------------------------- |
| `academic_years`    | Academic year periods                         |
| `semesters`         | Semester periods within academic years        |
| `courses`           | Course definitions (theory, lab, project)     |
| `course_offerings`  | Semester-specific course sections             |
| `teacher_courses`   | Teacher→Course assignment mapping             |
| `enrollments`       | Student→Course enrollment with grades         |

#### Scheduling
| Table               | Purpose                                      |
| ------------------- | -------------------------------------------- |
| `class_schedule`    | Weekly recurring class schedule               |
| `class_reschedules` | Temporary class cancellations/rescheduling    |

#### Attendance & Assessment
| Table                  | Purpose                                   |
| ---------------------- | ----------------------------------------- |
| `attendance`           | Daily attendance records                   |
| `grading_scheme`       | Grade ranges and grade point mapping       |
| `assessment_components`| Weighted assessment types (quiz, mid, final)|
| `assignments`          | Assignment creation with file attachments  |
| `assignment_submissions`| Student submissions with grading          |
| `student_marks`        | Component-wise marks with verification     |
| `student_performance_reviews` | Qualitative faculty reviews          |

#### Communication & System
| Table                       | Purpose                              |
| --------------------------- | ------------------------------------ |
| `notices`                   | Targeted notice board                 |
| `notice_interactions`       | Read/delete tracking per user         |
| `events`                    | Academic calendar events              |
| `documents`                 | Shared document repository            |
| `course_materials`          | Course-specific uploaded resources    |
| `notifications`             | User-specific alerts                  |
| `notification_preferences`  | Per-user notification settings        |
| `system_settings`           | Key-value system configuration        |
| `audit_logs`                | Full action audit trail               |
| `login_history`             | Login attempt tracking                |

### 7.3 Database Migrations
The project uses **11 incremental migration files** to evolve the schema:

| Migration                              | Purpose                                    |
| -------------------------------------- | ------------------------------------------ |
| `001_create_reschedules_table.sql`     | Class reschedule support                    |
| `002_create_performance_reviews_table` | Student performance review system           |
| `002_create_routine_tables.sql`        | DRAS routine allocation tables              |
| `002_routine_system.sql`               | Extended routine system schema              |
| `003_create_notice_interactions.sql`   | Notice read/delete tracking                 |
| `003_enhance_routine_system.sql`       | Enhanced routine with rooms and time slots  |
| `004_create_syllabus_tables.sql`       | Syllabus progress tracking                  |
| `005_create_class_reschedules.sql`     | Refined class cancellation/reschedule       |
| `006_add_reschedule_type.sql`          | Reschedule type differentiation             |
| `007_allow_null_for_cancel.sql`        | Nullable fields for class cancellations     |
| `routine_migration.sql`               | Consolidated routine migration              |

---

## 8. Module Descriptions

### 8.1 Authentication Module (`modules/auth/`)
| Page                | Functionality                                          |
| ------------------- | ------------------------------------------------------ |
| `login.php`         | Secure login with lockout, remember-me, role routing    |
| `logout.php`        | Session destruction and redirect                        |
| `profile.php`       | User profile editing with photo upload                  |
| `change-password.php` | Password change with current password verification   |

### 8.2 Super Admin Module (`modules/super_admin/` — 13 pages)
| Page                        | Functionality                                  |
| --------------------------- | ---------------------------------------------- |
| `dashboard.php`             | System-wide overview with statistics cards      |
| `users.php`                 | Complete user CRUD (create, search, edit, delete)|
| `departments.php`           | Department management with admin assignment     |
| `courses.php`               | Global course registry across departments       |
| `academic-years.php`        | Academic year and semester configuration        |
| `settings.php`              | System settings (branding, limits, timezone)    |
| `reports.php`               | System-wide analytics and reports               |
| `notices.php`               | Global notice board management                  |
| `events.php`                | Academic calendar event management              |
| `notifications.php`         | System notification center                      |
| `student-performance.php`   | Cross-department student performance overview   |
| `department_monitoring.php` | Department activity monitoring                  |

### 8.3 Department Admin Module (`modules/admin/` — 24 pages)
| Major Feature                | Key Pages                                        |
| ---------------------------- | ------------------------------------------------ |
| **Student Management**       | `students.php` — enrollment, search, filter, sort |
| **Teacher Management**       | `teachers.php` — onboarding, directory, search    |
| **Course Management**        | `courses.php`, `course-offerings.php`             |
| **Schedule Management**      | `class-schedule.php`, `manage-routine.php`, `routine-management.php`, `routine-manager.php` |
| **Time & Room Setup**        | `time-slots.php`, `time_slots.php`, `rooms.php`  |
| **Routine Rules**            | `routine_rules.php` — configurable allocation rules |
| **Teacher Assignments**      | `teacher-assignments.php` — course load management |
| **Verification**             | `marks-verification.php`, `exam-eligibility.php`  |
| **Analytics**                | `student-performance.php`, `course_progress.php`  |
| **Communication**            | `notices.php`, `notifications.php`                |
| **Change Requests**          | `routine-change-requests.php`                     |
| **Export**                    | `export_routine.php` — downloadable routine       |

### 8.4 Teacher Module (`modules/teacher/` — 16 pages)
| Major Feature              | Key Pages                                          |
| -------------------------- | -------------------------------------------------- |
| **Dashboard**              | `dashboard.php` — course overview, upcoming classes |
| **My Courses**             | `my-courses.php` — assigned courses & student roster|
| **Attendance**             | `attendance.php` — daily attendance sheet           |
| **Marks Entry**            | `marks-entry.php` — component-wise grade entry      |
| **Assignments**            | `assignments.php` — create, distribute, grade       |
| **Course Materials**       | `course-materials.php`, `upload-material.php`       |
| **Routine**                | `routine.php`, `my-routine.php` — weekly & date-wise|
| **Schedule Changes**       | `my-change-requests.php` — cancel/reschedule classes|
| **Syllabus**               | `syllabus.php` — topic-wise progress tracking       |
| **Student Analytics**      | `student-performance.php`, `students.php`           |
| **Communication**          | `notices.php`, `notifications.php`                  |

### 8.5 Student Module (`modules/student/` — 11 pages)
| Page                 | Functionality                                       |
| -------------------- | --------------------------------------------------- |
| `dashboard.php`      | Portal overview with course cards & quick stats      |
| `my-courses.php`     | View enrolled courses with details                   |
| `routine.php`        | Weekly class schedule view                           |
| `attendance.php`     | Attendance records with percentage tracking          |
| `results.php`        | Semester results with grade details                  |
| `performance.php`    | Academic analytics with charts                       |
| `assignments.php`    | View, download, and submit assignments               |
| `syllabus.php`       | Course syllabus and progress overview                |
| `notices.php`        | Academic notice board                                |
| `notifications.php`  | Personal alerts and updates                          |

---

## 9. API Endpoints

| Endpoint                    | Method(s)      | Description                               |
| --------------------------- | -------------- | ----------------------------------------- |
| `/api/admin/routine.php`    | GET, POST, PUT | Admin routine CRUD operations              |
| `/api/routine_manager.php`  | GET, POST      | Centralized routine management             |
| `/api/teacher/routine.php`  | GET, POST      | Teacher routine view & change requests     |
| `/api/teacher/schedule.php` | GET            | Teacher personal schedule data             |
| `/api/student/schedule.php` | GET            | Student class schedule data                |
| `/api/mark_read.php`        | POST           | Mark notification as read                  |

---

## 10. Security Implementation

| Feature                     | Implementation                                         |
| --------------------------- | ------------------------------------------------------ |
| **Authentication**          | Password-based login with PDO prepared statements      |
| **Role-Based Access Control** | Middleware in `auth.php` enforcing per-page permissions |
| **Session Security**        | HTTP-only cookies, SameSite=Strict, 30-min timeout     |
| **Account Lockout**         | Max 5 failed attempts → 15-minute lockout              |
| **CSRF Protection**         | Token-based CSRF validation on forms                   |
| **Input Sanitization**      | `sanitize_input()` helper for all user inputs          |
| **SQL Injection Prevention**| PDO prepared statements throughout                     |
| **Audit Logging**           | `audit_logs` table tracks all user actions             |
| **Login History**           | `login_history` table logs success/failure with IP     |
| **Remember Me**             | Secure token with expiration (7-day validity)          |
| **File Upload Validation**  | Type whitelist + 5 MB size limit                       |

---

## 11. Team Workflow & Development Process

### 11.1 Branching Strategy
- **Feature Branch Workflow**: `feature/developer-name/component-name`
- Each feature developed independently and merged via pull request
- `main` branch protected from direct pushes

### 11.2 Commit Convention
| Format | Example |
| ------ | ------- |
| `[Module] Action - Detail` | `[Teacher] Feat - Add attendance marking logic` |

### 11.3 Development Phases
The project was executed in **9 phases** across team members:

| Phase | Focus                          | Assigned To        |
| ----- | ------------------------------ | ------------------ |
| 1     | Project Initialization          | Yeatasim           |
| 2     | Core Architecture               | Yeatasim           |
| 3     | Global Assets (CSS/JS)          | Suria Hossain Bonna|
| 4     | Super Admin Module              | Suria Hossain Bonna|
| 5     | Admin — People & Schedule       | A. B. Rahaman      |
| 6     | Admin — Assets & Exams          | Md. Biplob Hossain |
| 7     | Teacher Module                  | Nayim Sheikh       |
| 8     | Student Module                  | Rasel Hossen       |
| 9     | API & Scripts                   | Yeatasim           |

---

## 12. Testing & Quality Assurance

### 12.1 Testing Approach
- **Module-level testing**: Each developer tested their own module before merge
- **Integration testing**: Cross-module functionality tested after merging (e.g., teacher marks entry → admin verification → student results)
- **Test report template**: Standardized template (`TEST_REPORT_TEMPLATE.md`) for consistent reporting

### 12.2 Test Reports Completed
| Report                                               | Module                 | Author          |
| ---------------------------------------------------- | ---------------------- | --------------- |
| `REPORT_ABRahaman_AdminPeople_2026-01-23.md`         | Admin — People         | A. B. Rahaman   |
| `REPORT_MdBiplob_AdminAssets_2026-01-23.md`          | Admin — Assets & Exams | Md. Biplob Hossain |
| `REPORT_Yeatasim_Phase9_API_2026-01-23.md`           | API & Scripts          | Yeatasim        |

### 12.3 Key Test Areas
- ✅ User authentication and role-based redirection
- ✅ CRUD operations for all entities (users, courses, departments)
- ✅ Class scheduling with conflict detection
- ✅ Attendance marking and retrieval
- ✅ Marks entry, verification, and result calculation
- ✅ Assignment upload, submission, and grading
- ✅ Notification delivery and read status
- ✅ Notice publishing with audience targeting
- ✅ Class cancel/reschedule workflows
- ✅ Routine export functionality

---

## 13. Challenges & Solutions

| Challenge                                     | Solution                                               |
| --------------------------------------------- | ------------------------------------------------------ |
| **Scheduling Conflicts**                       | Implemented multi-constraint validation (teacher, room, semester) with real-time conflict detection |
| **Complex Role Permissions**                   | Centralized `auth.php` middleware with per-role page access control |
| **Database Schema Evolution**                  | Adopted incremental migration scripts (11 migrations) for safe schema changes |
| **Team Coordination (6 members)**              | Strict Git branching strategy, commit conventions, and phased development plan |
| **Duplicate Entry Errors in Reschedules**       | Debugged and fixed unique constraint handling in class_reschedules table |
| **Large Module Complexity (Admin: 24 files)**   | Separated concerns into dedicated files per feature with shared helper functions |
| **Dynamic Routine Allocation**                  | Designed DRAS with prioritized allocation algorithm, configurable rules, and conflict reporting |

---

## 14. Future Scope

1. **Mobile Responsive Design**: Full mobile-first responsive re-design for tablet and smartphone access
2. **Email & SMS Notifications**: Integration with email/SMS gateways for real-time alerts
3. **Online Examination System**: Support for quiz and exam-taking directly within the platform
4. **AI-Based Analytics**: Predictive student performance analytics using machine learning
5. **REST API Expansion**: Complete RESTful API for mobile app integration
6. **Multi-University Support**: SaaS model supporting multiple institutions on a single platform
7. **Payment Gateway Integration**: Fee management and online payment processing
8. **Advanced Reporting**: PDF/Excel report generation and data export
9. **Real-Time Chat**: WebSocket-based messaging between students and faculty
10. **Accessibility**: WCAG 2.1 compliance for users with disabilities

---

## 15. Conclusion

**Academix** successfully delivers a fully functional University Management System that addresses the critical academic and administrative needs of the University of Barishal. The system covers the complete academic lifecycle — from department setup and course creation to student enrollment, attendance tracking, assessment, grading, and result publication.

Key accomplishments of the project:

- **68+ PHP pages** across 4 role-specific modules with rich, interactive dashboards
- **30+ database tables** with proper relational integrity, indexing, and migration support
- **Dynamic Routine Allocation System** with automatic conflict detection and manual override
- **Comprehensive security** implementation including RBAC, CSRF protection, and audit logging
- **Professional development workflow** using Git feature branching and standardized conventions

The project demonstrates strong software engineering practices including modular architecture, database normalization, role-based access control, and collaborative team development — making Academix a robust foundation that can be extended for production use in an academic institution.

---

**Submitted by:** Team Vertex.  
**Team Lead:** Yeatasim  
**Date:** March 02, 2026  
**University of Barishal | Department of Computer Science & Engineering**
